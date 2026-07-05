<?php

namespace App\Http\Controllers;

use App\Models\Comanda;
use App\Models\Ficha;
use Illuminate\Http\Request;

class CozinhaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $dados = $this->montarFila();

        return view('cozinha.index', $dados);
    }

    // JSON para polling (fila de pendentes + concluídos recentes)
    public function fila()
    {
        $dados = $this->montarFila();

        return response()->json([
            'pendentes'  => $dados['pendentes'],
            'concluidos' => $dados['concluidos'],
            'count'      => count($dados['pendentes']),
        ]);
    }

    // Conclui (marca como entregue) todos os itens de cozinha de uma ficha
    public function concluir(Ficha $ficha)
    {
        if ($ficha->status === 'cancelada') {
            return response()->json(['ok' => false, 'msg' => 'Esta ficha foi cancelada.'], 422);
        }

        $ficha->items()->where('destino', 'cozinha')->where('status', 'pendente')
            ->update(['status' => 'entregue', 'delivered_at' => now()]);

        $ficha->recalcStatus();

        return response()->json(['ok' => true]);
    }

    // Conclui (marca como entregue) os itens de cozinha pendentes de uma comanda
    public function concluirComanda(Comanda $comanda)
    {
        if ($comanda->status !== 'aberta') {
            return response()->json(['ok' => false, 'msg' => 'Esta comanda não está aberta.'], 422);
        }

        $comanda->items()->cozinhaPendente()->update(['status' => 'entregue', 'delivered_at' => now()]);

        return response()->json(['ok' => true]);
    }

    // Monta as duas listas (pendentes / concluídos) — fichas E comandas, por ordem de chegada
    private function montarFila(): array
    {
        $pendentes = collect()
            ->merge($this->fichasPendentes())
            ->merge($this->comandasPendentes())
            ->sortBy('criado_em')
            ->values()->all();

        $concluidos = collect()
            ->merge($this->fichasConcluidas())
            ->merge($this->comandasConcluidas())
            ->sortByDesc('criado_em')
            ->take(20)->values()->all();

        return ['pendentes' => $pendentes, 'concluidos' => $concluidos];
    }

    private function payloadFicha(Ficha $ficha): array
    {
        return [
            'origem'       => 'ficha',
            'key'          => 'ficha-' . $ficha->id,
            'codigo'       => $ficha->codigo,
            'cliente'      => $ficha->cliente ?: '—',
            'hora'         => $ficha->created_at->format('H:i'),
            'desde'        => $ficha->created_at->diffForHumans(null, true),
            'criado_em'    => $ficha->created_at->getTimestamp(),
            'concluir_url' => route('cozinha.concluir', $ficha),
            'itens'        => $ficha->cozinhaItems()->map(fn($i) => [
                'name'     => $i->name,
                'quantity' => $i->quantity,
                'obs'      => $i->observacao,
                'preparo'  => (bool) $i->preparo, // true = porção/comida; false = acompanhamento
            ])->values(),
        ];
    }

    private function payloadComanda(Comanda $comanda): array
    {
        return [
            'origem'       => 'comanda',
            'key'          => 'comanda-' . $comanda->id,
            'codigo'       => $comanda->codigo,
            'cliente'      => $comanda->cliente ?: '—',
            'hora'         => $comanda->created_at->format('H:i'),
            'desde'        => $comanda->created_at->diffForHumans(null, true),
            'criado_em'    => $comanda->created_at->getTimestamp(),
            'concluir_url' => route('cozinha.comandas.concluir', $comanda),
            // Na comanda só há itens de preparo (sem "acompanha")
            'itens'        => $comanda->items->filter(fn($i) => $i->preparo)->map(fn($i) => [
                'name'     => $i->name,
                'quantity' => $i->quantity,
                'obs'      => $i->observacao,
                'preparo'  => true,
            ])->values(),
        ];
    }

    private function fichasPendentes()
    {
        return Ficha::where('status', '!=', 'cancelada')
            ->whereHas('items', fn($q) => $q->where('destino', 'cozinha')->where('status', 'pendente'))
            ->with('items')
            ->orderBy('id')
            ->get()
            ->map(fn($f) => $this->payloadFicha($f));
    }

    private function fichasConcluidas()
    {
        return Ficha::where('status', '!=', 'cancelada')
            ->whereHas('items', fn($q) => $q->where('destino', 'cozinha'))
            ->whereDoesntHave('items', fn($q) => $q->where('destino', 'cozinha')->where('status', 'pendente'))
            ->with('items')
            ->latest('id')
            ->take(20)
            ->get()
            ->map(fn($f) => $this->payloadFicha($f));
    }

    private function comandasPendentes()
    {
        return Comanda::where('status', 'aberta')
            ->whereHas('items', fn($q) => $q->where('preparo', true)->where('status', 'pendente'))
            ->with('items')
            ->orderBy('id')
            ->get()
            ->map(fn($c) => $this->payloadComanda($c));
    }

    private function comandasConcluidas()
    {
        // Comandas abertas com itens de cozinha, mas nenhum pendente (todos já entregues)
        return Comanda::where('status', 'aberta')
            ->whereHas('items', fn($q) => $q->where('preparo', true))
            ->whereDoesntHave('items', fn($q) => $q->where('preparo', true)->where('status', 'pendente'))
            ->with('items')
            ->latest('id')
            ->take(20)
            ->get()
            ->map(fn($c) => $this->payloadComanda($c));
    }
}
