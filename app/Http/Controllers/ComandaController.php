<?php

namespace App\Http\Controllers;

use App\Models\CardapioItem;
use App\Models\Comanda;
use App\Models\ComandaItem;
use App\Models\ComandaPagamento;
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
        $comanda->load('items.cardapioItem', 'pagamentos.user');

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

        // Congela o que já foi acertado: não dá para reduzir abaixo da quantidade já paga
        $jaPago = $comanda->itemPaidQuantities()[$item->id] ?? 0;
        if ($jaPago > 0 && $data['quantity'] < $jaPago) {
            return back()->with('error', "Não dá para reduzir abaixo do que já foi acertado ({$jaPago}x).");
        }

        $item->update($data);

        return back()->with('status', 'Quantidade atualizada.');
    }

    public function removeItem(Comanda $comanda, ComandaItem $item)
    {
        $this->garantirAberta($comanda);
        abort_unless($item->comanda_id === $comanda->id, 404);

        // Congela o que já foi acertado: item já pago não pode ser removido
        if (($comanda->itemPaidQuantities()[$item->id] ?? 0) > 0) {
            return back()->with('error', 'Item já acertado não pode ser removido. Estorne o acerto antes.');
        }

        $item->delete();

        return back()->with('status', 'Item removido da comanda.');
    }

    // Registra um pagamento parcial (acerto) numa comanda aberta
    public function addPagamento(Request $request, Comanda $comanda)
    {
        $this->garantirAberta($comanda);

        if ($comanda->items()->count() === 0) {
            return back()->with('error', 'Não há itens para acertar nesta comanda.');
        }

        $restante = $comanda->restante;

        $data = $request->validate([
            'descricao'      => 'nullable|string|max:60',
            'valor'          => 'required|numeric|min:0.01|max:' . max(0.01, $restante),
            'payment_method' => 'required|in:' . implode(',', array_keys(Comanda::PAYMENT_METHODS)),
            'detalhe'        => 'nullable|string',
        ], [
            'valor.max' => 'O valor não pode ser maior que o restante (' . $comanda->restante_formatted . ').',
        ]);

        // Detalhe da divisão (itens da pessoa ou fração da divisão igual)
        $detalhe = null;
        if (!empty($data['detalhe'])) {
            $decoded = json_decode($data['detalhe'], true);
            if (is_array($decoded)) {
                $detalhe = $decoded;
            }
        }

        // Congela itens já acertados: não permite dividir de novo o que já foi pago
        if ($detalhe && ($detalhe['tipo'] ?? null) === 'pessoa') {
            $jaPagos = $comanda->itemPaidQuantities();
            foreach ($detalhe['itens'] ?? [] as $it) {
                $itemId = $it['id'] ?? null;
                $qtd    = (int) ($it['qtd'] ?? 0);
                $item   = $itemId ? $comanda->items()->find($itemId) : null;
                if (!$item) {
                    continue;
                }
                $restanteItem = $item->quantity - ($jaPagos[$itemId] ?? 0);
                if ($qtd > $restanteItem) {
                    return back()->with('error', 'Esses itens já foram acertados. Atualize a divisão.');
                }
            }
        }

        $comanda->pagamentos()->create([
            'descricao'      => $data['descricao'] ?? null,
            'detalhe'        => $detalhe,
            'valor'          => $data['valor'],
            'payment_method' => $data['payment_method'],
            'user_id'        => auth()->id(),
        ]);

        $comanda->refresh();

        return back()->with('status', 'Acerto de ' . Comanda::money($data['valor']) . ' registrado. Restante: ' . $comanda->restante_formatted . '.');
    }

    // Remove um pagamento parcial (estorno) de uma comanda aberta
    public function removePagamento(Comanda $comanda, ComandaPagamento $pagamento)
    {
        $this->garantirAberta($comanda);
        abort_unless($pagamento->comanda_id === $comanda->id, 404);

        $pagamento->delete();

        return back()->with('status', 'Acerto estornado.');
    }

    public function fechar(Request $request, Comanda $comanda)
    {
        $this->garantirAberta($comanda);

        if ($comanda->items()->count() === 0) {
            return back()->with('error', 'Não é possível fechar uma comanda sem itens.');
        }

        // Todo o pagamento passa pelos acertos da "Conta da mesa": só fecha quando zerar o restante.
        if ($comanda->restante > 0) {
            return back()->with('error', 'Registre os acertos até zerar o restante (' . $comanda->restante_formatted . ') antes de fechar.');
        }

        // Forma de pagamento da comanda: única quando todos os acertos batem,
        // ou "misto" quando o pagamento foi quebrado em formas diferentes.
        $formasUsadas = $comanda->pagamentos()->pluck('payment_method')->unique()->values();
        $formaComanda = $formasUsadas->count() > 1
            ? Comanda::PAYMENT_MISTO
            : $formasUsadas->first();

        $comanda->update([
            'status'         => 'fechada',
            'payment_method' => $formaComanda,
            'service_fee'    => 0,
            'total'          => $comanda->subtotal, // taxa de serviço não é mais aplicada
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
