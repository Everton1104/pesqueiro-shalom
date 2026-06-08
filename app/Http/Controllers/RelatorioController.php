<?php

namespace App\Http\Controllers;

use App\Models\Comanda;
use App\Models\ComandaItem;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RelatorioController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        abort_unless(auth()->user()->is_admin, 403);

        Carbon::setLocale('pt_BR');

        $periodo = $request->query('periodo') === 'mes' ? 'mes' : 'semana';

        try {
            $ref = Carbon::parse($request->query('ref', 'today'))->startOfDay();
        } catch (\Throwable $e) {
            $ref = Carbon::today();
        }

        if ($periodo === 'mes') {
            $start   = $ref->copy()->startOfMonth();
            $end     = $ref->copy()->endOfMonth()->endOfDay();
            $prevRef = $start->copy()->subMonthNoOverflow()->format('Y-m-d');
            $nextRef = $start->copy()->addMonthNoOverflow()->format('Y-m-d');
            $titulo  = ucfirst($start->translatedFormat('F \d\e Y'));
        } else {
            $start   = $ref->copy()->startOfWeek(Carbon::SUNDAY);
            $end     = $ref->copy()->endOfWeek(Carbon::SATURDAY)->endOfDay();
            $prevRef = $start->copy()->subWeek()->format('Y-m-d');
            $nextRef = $start->copy()->addWeek()->format('Y-m-d');
            $titulo  = $start->format('d/m') . ' a ' . $end->format('d/m/Y');
        }

        // Comandas fechadas (pagas) no período
        $comandas = Comanda::where('status', 'fechada')
            ->whereBetween('closed_at', [$start, $end])
            ->get();

        $faturamento = (float) $comandas->sum('total');
        $qtdComandas = $comandas->count();
        $ticketMedio = $qtdComandas ? $faturamento / $qtdComandas : 0;
        $taxaServico = (float) $comandas->sum('service_fee');

        // Por forma de pagamento
        $porPagamento = $comandas->groupBy('payment_method')->map(fn($g) => [
            'total' => (float) $g->sum('total'),
            'qtd'   => $g->count(),
        ])->sortByDesc('total');

        // Faturamento por dia (preenche dias sem venda com zero)
        $porDiaRaw = $comandas
            ->groupBy(fn($c) => $c->closed_at->format('Y-m-d'))
            ->map(fn($g) => (float) $g->sum('total'));

        $porDia = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $k = $d->format('Y-m-d');
            $porDia[$k] = [
                'label' => $d->translatedFormat('D, d/m'),
                'total' => $porDiaRaw[$k] ?? 0.0,
            ];
        }
        $maxDia = collect($porDia)->max('total') ?: 1;

        // Itens vendidos (lançamentos das comandas fechadas no período)
        $itens = ComandaItem::whereIn('comanda_id', $comandas->pluck('id'))
            ->selectRaw('name, SUM(quantity) as qtd, SUM(quantity * unit_price) as receita')
            ->groupBy('name')
            ->orderByDesc('qtd')
            ->get();
        $totalItens = (int) $itens->sum('qtd');

        // Lista paginada das comandas do período (com itens e pagamentos para detalhar como foi paga)
        $listaComandas = Comanda::where('status', 'fechada')
            ->whereBetween('closed_at', [$start, $end])
            ->with(['items', 'pagamentos'])
            ->withCount('items')
            ->orderByDesc('closed_at')
            ->paginate(15)
            ->withQueryString();

        return view('relatorios.index', compact(
            'periodo', 'titulo', 'start', 'end', 'prevRef', 'nextRef',
            'faturamento', 'qtdComandas', 'ticketMedio', 'taxaServico',
            'porPagamento', 'porDia', 'maxDia', 'itens', 'totalItens', 'listaComandas'
        ));
    }
}
