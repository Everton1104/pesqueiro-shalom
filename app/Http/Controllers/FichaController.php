<?php

namespace App\Http\Controllers;

use App\Models\CardapioItem;
use App\Models\Comanda;
use App\Models\Ficha;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FichaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        // Itens vendáveis do cardápio (mesma base do pad da comanda)
        $vendaveis = CardapioItem::sellable()->orderBy('sort_order')->get();

        // Ordem personalizada do pad (admins arrastam na tela da comanda; reaproveitamos aqui)
        $ordemItens = json_decode(Setting::get('pad_item_order', '[]'), true);
        $ordemItens = is_array($ordemItens) ? $ordemItens : [];
        if ($ordemItens) {
            $pos = array_flip($ordemItens);
            $vendaveis = $vendaveis
                ->sortBy(fn($i) => $pos[$i->id] ?? (count($ordemItens) + (int) $i->sort_order))
                ->values();
        }

        $ordemCat  = ['CERVEJAS', 'CAIPIRINHAS', 'DRINKS', 'BORBONS', 'BEBIDAS QUENTES', 'BEBIDAS', 'PORÇÕES', 'COMIDA', 'SALGADOS'];
        $presentes = $vendaveis->pluck('category')->unique();
        $padCategorias = collect($ordemCat)->filter(fn($c) => $presentes->contains($c))
            ->merge($presentes->reject(fn($c) => in_array($c, $ordemCat)))
            ->values();

        // Ficha recém-paga a imprimir (vinda do store) — mantém o caixa nesta tela
        $printFicha = null;
        if ($id = session('print_ficha_id')) {
            $printFicha = Ficha::with('items')->find($id);
        }

        return view('fichas.index', [
            'padItens'      => $vendaveis,
            'padCategorias' => $padCategorias,
            'recentes'      => Ficha::with('items')->latest()->take(12)->get(),
            'methods'       => Comanda::PAYMENT_METHODS,
            'cozinhaCats'   => Ficha::COZINHA_CATEGORIES,
            'printFicha'    => $printFicha,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'itens'                => 'required|array|min:1',
            'itens.*.id'           => 'required|integer|exists:cardapio_items,id',
            'itens.*.qtd'          => 'required|integer|min:1|max:99',
            'itens.*.obs'          => 'nullable|string|max:255',
            'itens.*.cozinha'      => 'nullable',
            'payment_method'       => 'required|in:' . implode(',', array_keys(Comanda::PAYMENT_METHODS)),
            'cliente'              => 'nullable|string|max:150',
        ]);

        // Carrega os itens do cardápio de uma vez
        $ids   = collect($data['itens'])->pluck('id')->unique();
        $cards = CardapioItem::whereIn('id', $ids)->get()->keyBy('id');

        // Define o destino de cada linha (cozinha quando é porção/comida OU marcado para acompanhar a porção)
        $vaiParaCozinha = function ($it) use ($cards) {
            $card = $cards->get($it['id']);
            if (!$card) {
                return false;
            }
            return $card->requer_preparo || filter_var($it['cozinha'] ?? false, FILTER_VALIDATE_BOOLEAN);
        };

        // Há item que sai pela cozinha? Então o nome do cliente é obrigatório (é por ele que se chama na entrega)
        $temCozinha = collect($data['itens'])->contains($vaiParaCozinha);
        if ($temCozinha && empty(trim($data['cliente'] ?? ''))) {
            return back()->withInput()->with('error', 'Informe o nome do cliente — há itens que vão para a cozinha.');
        }

        $ficha = DB::transaction(function () use ($data, $cards) {
            $ficha = Ficha::create([
                'codigo'         => Ficha::gerarCodigo(),
                'cliente'        => trim($data['cliente'] ?? '') ?: null,
                'status'         => 'paga',
                'payment_method' => $data['payment_method'],
                'total'          => 0,
                'user_id'        => auth()->id(),
                'paid_at'        => now(),
            ]);

            $total = 0;
            foreach ($data['itens'] as $it) {
                $card = $cards->get($it['id']);
                if (!$card) {
                    continue;
                }
                $qtd = (int) $it['qtd'];
                $total += (float) $card->price * $qtd;

                $preparo  = (bool) $card->requer_preparo;
                $marcado  = filter_var($it['cozinha'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $destino  = ($preparo || $marcado) ? 'cozinha' : 'balcao';

                $ficha->items()->create([
                    'cardapio_item_id' => $card->id,
                    'name'             => $card->name,
                    'category'         => $card->category,
                    'unit_price'       => $card->price,
                    'quantity'         => $qtd,
                    'observacao'       => trim($it['obs'] ?? '') ?: null,
                    'destino'          => $destino,
                    'preparo'          => $preparo,
                    'status'           => 'pendente',
                ]);
            }

            $ficha->update(['total' => $total]);

            return $ficha;
        });

        // Volta para o caixa já pronto para a próxima ficha; a impressão dispara na própria tela
        return redirect()->route('fichas.index')
            ->with('status', 'Ficha ' . $ficha->codigo . ' paga — ' . $ficha->total_formatted . '.')
            ->with('print_ficha_id', $ficha->id);
    }

    public function show(Ficha $ficha)
    {
        $ficha->load('items', 'user');

        return view('fichas.show', ['ficha' => $ficha]);
    }

    public function cancelar(Request $request, Ficha $ficha)
    {
        if (!$this->autorizado($request)) {
            return back()->with('error', 'Senha de autorização inválida ou não definida.');
        }

        $ficha->update(['status' => 'cancelada', 'concluded_at' => now()]);

        return redirect()->route('fichas.index')->with('status', 'Ficha ' . $ficha->codigo . ' cancelada.');
    }

    public function destroy(Request $request, Ficha $ficha)
    {
        if (!$this->autorizado($request)) {
            return back()->with('error', 'Senha de autorização inválida ou não definida.');
        }

        $codigo = $ficha->codigo;
        $ficha->delete();

        return redirect()->route('fichas.index')->with('status', 'Ficha ' . $codigo . ' excluída.');
    }

    private function autorizado(Request $request): bool
    {
        if (auth()->user()->is_admin) {
            return true;
        }

        $hash = Setting::get('cancel_auth_password');

        return $hash && Hash::check((string) $request->input('auth_password', ''), $hash);
    }
}
