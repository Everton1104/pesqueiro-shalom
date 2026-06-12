<?php

namespace App\Http\Controllers;

use App\Models\Ficha;
use Illuminate\Http\Request;

class BalcaoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('balcao.index');
    }

    // Resolve uma ficha pelo código (QR) e devolve os itens de balcão em JSON
    public function buscar(Request $request)
    {
        $codigo = trim($request->query('codigo', ''));

        if ($codigo === '') {
            return response()->json(['ok' => false, 'msg' => 'Informe o código da ficha.'], 422);
        }

        $ficha = Ficha::where('codigo', $codigo)->with('items')->first();

        if (!$ficha) {
            return response()->json(['ok' => false, 'msg' => 'Ficha não encontrada.'], 404);
        }

        if ($ficha->status === 'cancelada') {
            return response()->json(['ok' => false, 'msg' => 'Esta ficha foi cancelada.'], 422);
        }

        $balcao = $ficha->balcaoItems();

        if ($balcao->isEmpty()) {
            return response()->json([
                'ok'    => false,
                'msg'   => 'Esta ficha não tem itens para retirar no balcão (apenas cozinha).',
            ], 422);
        }

        $pendentes = $balcao->where('status', 'pendente');
        $jaEntregue = $pendentes->isEmpty();

        return response()->json([
            'ok'          => true,
            'valida'      => !$jaEntregue,
            'ja_entregue' => $jaEntregue,
            'ficha'       => [
                'id'      => $ficha->id,
                'codigo'  => $ficha->codigo,
                'cliente' => $ficha->cliente,
            ],
            'itens' => $balcao->map(fn($i) => [
                'name'     => $i->name,
                'quantity' => $i->quantity,
                'obs'      => $i->observacao,
                'entregue' => $i->status === 'entregue',
            ])->values(),
            'entregar_url' => route('balcao.entregar', $ficha),
        ]);
    }

    // Marca os itens de balcão como entregues
    public function entregar(Ficha $ficha)
    {
        if ($ficha->status === 'cancelada') {
            return response()->json(['ok' => false, 'msg' => 'Esta ficha foi cancelada.'], 422);
        }

        $pendentes = $ficha->items()->where('destino', 'balcao')->where('status', 'pendente');

        if ($pendentes->count() === 0) {
            return response()->json(['ok' => false, 'msg' => 'Esta ficha já foi retirada no balcão.'], 422);
        }

        $pendentes->update(['status' => 'entregue', 'delivered_at' => now()]);

        $ficha->recalcStatus();

        return response()->json(['ok' => true, 'msg' => 'Entrega confirmada.']);
    }
}
