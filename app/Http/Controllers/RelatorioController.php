<?php

namespace App\Http\Controllers;

use App\Models\Comanda;
use App\Models\ComandaItem;
use App\Models\Ficha;
use App\Models\FichaItem;
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

        // Fichas pagas (não canceladas) no período — receita reconhecida no pagamento
        $fichas = Ficha::where('status', '!=', 'cancelada')
            ->whereBetween('paid_at', [$start, $end])
            ->get();

        $fatComandas = (float) $comandas->sum('total');
        $fatFichas   = (float) $fichas->sum('total');
        $faturamento = $fatComandas + $fatFichas;

        $qtdComandas = $comandas->count();
        $qtdFichas   = $fichas->count();
        $qtdVendas   = $qtdComandas + $qtdFichas;
        $taxaServico = (float) $comandas->sum('service_fee');

        // Por forma de pagamento (comandas + fichas combinados)
        $acc = [];
        $somaForma = function ($key, $total) use (&$acc) {
            $key = $key ?: '—';
            $acc[$key] = $acc[$key] ?? ['total' => 0.0, 'qtd' => 0];
            $acc[$key]['total'] += (float) $total;
            $acc[$key]['qtd']   += 1;
        };
        foreach ($comandas as $c) {
            $somaForma($c->payment_method, $c->total);
        }
        foreach ($fichas as $f) {
            $somaForma($f->payment_method, $f->total);
        }
        $porPagamento = collect($acc)->sortByDesc('total');

        // Faturamento por dia (comandas pela data de fechamento; fichas pela data de pagamento)
        $porDiaRaw = [];
        foreach ($comandas as $c) {
            $k = $c->closed_at->format('Y-m-d');
            $porDiaRaw[$k] = ($porDiaRaw[$k] ?? 0) + (float) $c->total;
        }
        foreach ($fichas as $f) {
            $k = ($f->paid_at ?? $f->created_at)->format('Y-m-d');
            $porDiaRaw[$k] = ($porDiaRaw[$k] ?? 0) + (float) $f->total;
        }

        $porDia = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $k = $d->format('Y-m-d');
            $porDia[$k] = [
                'label' => $d->translatedFormat('D, d/m'),
                'total' => $porDiaRaw[$k] ?? 0.0,
            ];
        }
        $maxDia = collect($porDia)->max('total') ?: 1;

        // Itens vendidos (comandas + fichas, somados por nome)
        $itensComanda = ComandaItem::whereIn('comanda_id', $comandas->pluck('id'))
            ->selectRaw('name, SUM(quantity) as qtd, SUM(quantity * unit_price) as receita')
            ->groupBy('name')->get();
        $itensFicha = FichaItem::whereIn('ficha_id', $fichas->pluck('id'))
            ->selectRaw('name, SUM(quantity) as qtd, SUM(quantity * unit_price) as receita')
            ->groupBy('name')->get();

        $itens = $itensComanda->concat($itensFicha)
            ->groupBy('name')
            ->map(fn($g, $name) => (object) [
                'name'    => $name,
                'qtd'     => (int) $g->sum('qtd'),
                'receita' => (float) $g->sum('receita'),
            ])
            ->sortByDesc('qtd')
            ->values();
        $totalItens = (int) $itens->sum('qtd');

        // Lista paginada das comandas do período
        $listaComandas = Comanda::where('status', 'fechada')
            ->whereBetween('closed_at', [$start, $end])
            ->with(['items', 'pagamentos'])
            ->withCount('items')
            ->orderByDesc('closed_at')
            ->paginate(15, ['*'], 'page')
            ->withQueryString();

        // Lista paginada das fichas do período (paginador separado: fpage)
        $listaFichas = Ficha::where('status', '!=', 'cancelada')
            ->whereBetween('paid_at', [$start, $end])
            ->withCount('items')
            ->orderByDesc('paid_at')
            ->paginate(15, ['*'], 'fpage')
            ->withQueryString();

        return view('relatorios.index', compact(
            'periodo', 'titulo', 'start', 'end', 'prevRef', 'nextRef',
            'faturamento', 'fatComandas', 'fatFichas', 'qtdComandas', 'qtdFichas', 'qtdVendas',
            'taxaServico', 'porPagamento', 'porDia', 'maxDia', 'itens', 'totalItens',
            'listaComandas', 'listaFichas'
        ));
    }
}
