<?php

namespace App\Http\Controllers;

use App\Models\CardapioItem;
use App\Models\Comanda;
use App\Models\ComandaItem;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ComandaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $q = trim($request->query('q', ''));

        // Carrega todas as abertas; a busca por nome é aplicada no cliente (e via polling)
        return view('comandas.index', [
            'abertas'  => Comanda::aberta()->withCount('items')->with('items')->latest()->get(),
            'fechadas' => Comanda::where('status', 'fechada')->latest('closed_at')->take(15)->get(),
            'statuses' => Comanda::STATUSES,
            'q'        => $q,
        ]);
    }

    // Endpoint de polling: comandas abertas em JSON
    public function abertasJson()
    {
        $abertas = Comanda::aberta()->withCount('items')->with('items')->latest()->get()->map(fn($c) => [
            'cliente' => $c->cliente,
            'codigo'  => $c->codigo,
            'itens'   => $c->items_count,
            'total'   => $c->total_formatted,
            'criada'  => $c->created_at->diffForHumans(),
            'url'     => route('comandas.show', $c),
        ]);

        return response()->json(['comandas' => $abertas, 'count' => $abertas->count()]);
    }

    // Endpoint de polling: assinatura do estado de uma comanda
    public function sig(Comanda $comanda)
    {
        return response()->json(['sig' => $comanda->liveSignature(), 'status' => $comanda->status]);
    }

    // Salva a ordem das categorias do pad (somente admin)
    public function savePadOrder(Request $request)
    {
        abort_unless(auth()->user()->is_admin, 403);

        $data = $request->validate(['order' => 'required|array', 'order.*' => 'integer']);
        Setting::set('pad_item_order', json_encode(array_values($data['order'])));

        return response()->json(['ok' => true]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente'    => 'required|string|max:150',
            'observacao' => 'nullable|string|max:255',
        ]);

        $data['codigo']  = $this->gerarCodigo();
        $data['status']  = 'aberta';
        $data['user_id'] = auth()->id();

        $comanda = Comanda::create($data);

        return redirect()->route('comandas.show', $comanda)
            ->with('status', 'Comanda de ' . $comanda->cliente . ' aberta!')
            ->with('print', true);
    }

    public function show(Comanda $comanda)
    {
        $comanda->load('items.cardapioItem');

        // Itens vendáveis do cardápio
        $vendaveis = CardapioItem::whereIn('status', ['active', 'unavailable'])->orderBy('sort_order')->get();

        // Ordem personalizada dos itens do pad (admins arrastam — pode misturar categorias)
        $ordemItens = json_decode(Setting::get('pad_item_order', '[]'), true);
        $ordemItens = is_array($ordemItens) ? $ordemItens : [];
        if ($ordemItens) {
            $pos = array_flip($ordemItens); // id => posição
            $vendaveis = $vendaveis
                ->sortBy(fn($i) => $pos[$i->id] ?? (count($ordemItens) + (int) $i->sort_order))
                ->values();
        }

        // Categorias presentes (para os botões de filtro): ordem base + extras no fim
        $ordemCat  = ['CERVEJAS', 'CAIPIRINHAS', 'DRINKS', 'BORBONS', 'BEBIDAS QUENTES', 'BEBIDAS', 'PORÇÕES', 'COMIDA', 'SALGADOS'];
        $presentes = $vendaveis->pluck('category')->unique();
        $padCategorias = collect($ordemCat)->filter(fn($c) => $presentes->contains($c))
            ->merge($presentes->reject(fn($c) => in_array($c, $ordemCat)))
            ->values();

        return view('comandas.show', [
            'comanda'       => $comanda,
            'padItens'      => $vendaveis,
            'padCategorias' => $padCategorias,
            'statuses'      => Comanda::STATUSES,
            'methods'       => Comanda::PAYMENT_METHODS,
        ]);
    }

    // Leitura do QR Code (leitor USB digita o código + Enter) OU busca por nome do cliente
    public function scan(Request $request)
    {
        $termo = trim($request->query('codigo', ''));

        if ($termo === '') {
            return redirect()->route('comandas.index');
        }

        // 1) Código exato (QR Code)
        $porCodigo = Comanda::where('codigo', $termo)->first();
        if ($porCodigo) {
            return redirect()->route('comandas.show', $porCodigo);
        }

        // 2) Busca por nome do cliente entre as comandas abertas
        $porNome = Comanda::aberta()->where('cliente', 'like', '%' . $termo . '%')->get();

        if ($porNome->count() === 1) {
            return redirect()->route('comandas.show', $porNome->first());
        }

        if ($porNome->count() > 1) {
            // Vários resultados: mostra a lista filtrada na index
            return redirect()->route('comandas.index', ['q' => $termo]);
        }

        return redirect()->route('comandas.index')
            ->with('error', 'Nenhuma comanda aberta encontrada para "' . $termo . '".');
    }

    public function addItem(Request $request, Comanda $comanda)
    {
        $this->garantirAberta($comanda);

        $data = $request->validate([
            'cardapio_item_id' => 'required|integer|exists:cardapio_items,id',
            'quantity'         => 'required|integer|min:1|max:99',
            'observacao'       => 'nullable|string|max:255',
        ]);

        $item = CardapioItem::findOrFail($data['cardapio_item_id']);

        $comanda->items()->create([
            'cardapio_item_id' => $item->id,
            'name'             => $item->name,
            'unit_price'       => $item->price,
            'quantity'         => $data['quantity'],
            'observacao'       => $data['observacao'] ?? null,
        ]);

        return back()->with('status', $data['quantity'] . 'x ' . $item->name . ' adicionado.');
    }

    public function updateItem(Request $request, Comanda $comanda, ComandaItem $item)
    {
        $this->garantirAberta($comanda);
        abort_unless($item->comanda_id === $comanda->id, 404);

        $data = $request->validate(['quantity' => 'required|integer|min:1|max:99']);
        $item->update($data);

        return back()->with('status', 'Quantidade atualizada.');
    }

    public function removeItem(Comanda $comanda, ComandaItem $item)
    {
        $this->garantirAberta($comanda);
        abort_unless($item->comanda_id === $comanda->id, 404);

        $item->delete();

        return back()->with('status', 'Item removido da comanda.');
    }

    public function fechar(Request $request, Comanda $comanda)
    {
        $this->garantirAberta($comanda);

        $data = $request->validate([
            'payment_method' => 'required|in:' . implode(',', array_keys(Comanda::PAYMENT_METHODS)),
            'service_fee'    => 'nullable|numeric|min:0',
        ]);

        if ($comanda->items()->count() === 0) {
            return back()->with('error', 'Não é possível fechar uma comanda sem itens.');
        }

        $fee = (float) ($data['service_fee'] ?? 0);

        $comanda->update([
            'status'         => 'fechada',
            'payment_method' => $data['payment_method'],
            'service_fee'    => $fee,
            'total'          => $comanda->subtotal + $fee,
            'closed_at'      => now(),
        ]);

        return redirect()->route('comandas.index')
            ->with('status', 'Comanda de ' . $comanda->cliente . ' fechada — ' . $comanda->total_formatted . '.');
    }

    public function cancelar(Request $request, Comanda $comanda)
    {
        $this->garantirAberta($comanda);

        if (!$this->autorizado($request)) {
            return back()->with('error', 'Senha de autorização inválida ou não definida.');
        }

        $comanda->update(['status' => 'cancelada', 'closed_at' => now()]);

        return redirect()->route('comandas.index')
            ->with('status', 'Comanda de ' . $comanda->cliente . ' cancelada.');
    }

    // Exclui a comanda do histórico (apaga itens em cascata)
    public function destroy(Request $request, Comanda $comanda)
    {
        if (!$this->autorizado($request)) {
            return back()->with('error', 'Senha de autorização inválida ou não definida.');
        }

        $nome = $comanda->cliente;
        $comanda->delete();

        return redirect()->route('comandas.index')
            ->with('status', 'Comanda de ' . $nome . ' excluída do histórico.');
    }

    // Admin libera direto; demais precisam da senha de autorização definida no painel
    private function autorizado(Request $request): bool
    {
        if (auth()->user()->is_admin) {
            return true;
        }

        $hash = Setting::get('cancel_auth_password');

        return $hash && Hash::check((string) $request->input('auth_password', ''), $hash);
    }

    private function garantirAberta(Comanda $comanda): void
    {
        abort_unless($comanda->status === 'aberta', 403, 'Comanda não está aberta.');
    }

    private function gerarCodigo(): string
    {
        do {
            $codigo = strtoupper(Str::random(6));
        } while (Comanda::where('codigo', $codigo)->exists());

        return $codigo;
    }
}
