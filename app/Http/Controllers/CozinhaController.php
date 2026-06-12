<?php

namespace App\Http\Controllers;

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

    // Monta as duas listas (pendentes / concluídos) agrupadas por ficha
    private function montarFila(): array
    {
        $payload = fn($ficha) => [
            'id'      => $ficha->id,
            'codigo'  => $ficha->codigo,
            'cliente' => $ficha->cliente ?: '—',
            'hora'    => $ficha->created_at->format('H:i'),
            'desde'   => $ficha->created_at->diffForHumans(null, true),
            'itens'   => $ficha->cozinhaItems()->map(fn($i) => [
                'name'     => $i->name,
                'quantity' => $i->quantity,
                'obs'      => $i->observacao,
                'preparo'  => (bool) $i->preparo, // true = porção/comida a preparar; false = acompanhamento
            ])->values(),
            'concluir_url' => route('cozinha.concluir', $ficha),
        ];

        // Fila: TODAS as fichas (não canceladas) com algum item de cozinha pendente — ordem de chegada, sem limite
        $pendentes = Ficha::where('status', '!=', 'cancelada')
            ->whereHas('items', fn($q) => $q->where('destino', 'cozinha')->where('status', 'pendente'))
            ->with('items')
            ->orderBy('id')
            ->get()
            ->map($payload)->values()->all();

        // Concluídos: fichas com itens de cozinha, mas nenhum mais pendente — os 20 mais recentes
        $concluidos = Ficha::where('status', '!=', 'cancelada')
            ->whereHas('items', fn($q) => $q->where('destino', 'cozinha'))
            ->whereDoesntHave('items', fn($q) => $q->where('destino', 'cozinha')->where('status', 'pendente'))
            ->with('items')
            ->latest('id')
            ->take(20)
            ->get()
            ->map($payload)->values()->all();

        return ['pendentes' => $pendentes, 'concluidos' => $concluidos];
    }
}
