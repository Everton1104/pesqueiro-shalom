@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
<style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 20; vertical-align: middle; line-height: 1; }
    .metric { border-radius: 14px; }
    .metric .ic {
        width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: rgba(217,119,6,.14); color: var(--sh-orange2);
    }
    .metric .ic .material-symbols-outlined { font-size: 24px; }
    .metric .val { font-size: clamp(1.05rem, 4.4vw, 1.5rem); font-weight: 800; line-height: 1.1; }
    .metric .lbl { font-size: .74rem; text-transform: uppercase; letter-spacing: .06em; color: var(--sh-muted); }
    .day-row { display: flex; align-items: center; gap: .75rem; padding: .25rem 0; }
    .day-row .nome { width: 92px; font-size: .8rem; color: var(--sh-muted); text-transform: capitalize; flex-shrink: 0; }
    .day-bar-wrap { flex: 1; background: var(--sh-bg3); border-radius: 6px; height: 22px; overflow: hidden; }
    .day-bar { height: 100%; background: linear-gradient(90deg, var(--sh-orange), var(--sh-orange2)); border-radius: 6px; min-width: 2px; }
    .day-row .val { width: 96px; text-align: right; font-size: .82rem; font-weight: 700; flex-shrink: 0; }
</style>

@php $brl = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.'); @endphp

<div class="container-lg">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 mt-2">
        <h4 class="mb-0 fw-bold">Relatórios</h4>
        <div class="btn-group">
            <a href="{{ route('relatorios.index', ['periodo' => 'semana', 'ref' => $start->format('Y-m-d')]) }}"
               class="btn btn-sm {{ $periodo === 'semana' ? 'btn-primary' : 'btn-outline-primary' }}">Semanal</a>
            <a href="{{ route('relatorios.index', ['periodo' => 'mes', 'ref' => $start->format('Y-m-d')]) }}"
               class="btn btn-sm {{ $periodo === 'mes' ? 'btn-primary' : 'btn-outline-primary' }}">Mensal</a>
        </div>
    </div>

    {{-- Navegação de período --}}
    <div class="d-flex align-items-center justify-content-center gap-3 mb-4">
        <a href="{{ route('relatorios.index', ['periodo' => $periodo, 'ref' => $prevRef]) }}"
           class="btn btn-outline-secondary btn-sm"><span class="material-symbols-outlined">chevron_left</span></a>
        <span class="fw-bold text-center" style="min-width:150px;">{{ $titulo }}</span>
        <a href="{{ route('relatorios.index', ['periodo' => $periodo, 'ref' => $nextRef]) }}"
           class="btn btn-outline-secondary btn-sm"><span class="material-symbols-outlined">chevron_right</span></a>
    </div>

    {{-- Métricas --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card metric h-100"><div class="card-body d-flex align-items-center gap-3">
                <div class="ic"><span class="material-symbols-outlined">payments</span></div>
                <div><div class="val" style="color:var(--sh-orange2);">{{ $brl($faturamento) }}</div><div class="lbl">Faturamento</div></div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card metric h-100"><div class="card-body d-flex align-items-center gap-3">
                <div class="ic"><span class="material-symbols-outlined">receipt_long</span></div>
                <div><div class="val">{{ $qtdComandas }}</div><div class="lbl">Comandas fechadas</div></div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card metric h-100"><div class="card-body d-flex align-items-center gap-3">
                <div class="ic"><span class="material-symbols-outlined">trending_up</span></div>
                <div><div class="val">{{ $brl($ticketMedio) }}</div><div class="lbl">Ticket médio</div></div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card metric h-100"><div class="card-body d-flex align-items-center gap-3">
                <div class="ic"><span class="material-symbols-outlined">lunch_dining</span></div>
                <div><div class="val">{{ $totalItens }}</div><div class="lbl">Itens vendidos</div></div>
            </div></div>
        </div>
    </div>

    @if($qtdComandas === 0)
        <div class="card"><div class="card-body text-center text-muted py-5">
            Nenhuma comanda fechada neste período.
        </div></div>
    @else
    <div class="row g-4 mb-4">
        {{-- Faturamento por dia --}}
        <div class="col-12 col-lg-7">
            <div class="card h-100">
                <div class="card-header fw-bold">Faturamento por dia</div>
                <div class="card-body">
                    @foreach($porDia as $dia)
                        <div class="day-row">
                            <div class="nome">{{ $dia['label'] }}</div>
                            <div class="day-bar-wrap">
                                <div class="day-bar" style="width: {{ $dia['total'] > 0 ? max(2, $dia['total'] / $maxDia * 100) : 0 }}%;"></div>
                            </div>
                            <div class="val">{{ $dia['total'] > 0 ? $brl($dia['total']) : '—' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Por forma de pagamento --}}
        <div class="col-12 col-lg-5">
            <div class="card h-100">
                <div class="card-header fw-bold">Por forma de pagamento</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><th>Forma</th><th class="text-center">Qtd</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            @foreach($porPagamento as $forma => $dados)
                                <tr>
                                    <td>{{ $forma ? \App\Models\Comanda::paymentLabel($forma) : '—' }}</td>
                                    <td class="text-center">{{ $dados['qtd'] }}</td>
                                    <td class="text-end fw-semibold">{{ $brl($dados['total']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr><td class="fw-bold">Total</td><td class="text-center fw-bold">{{ $qtdComandas }}</td><td class="text-end fw-bold">{{ $brl($faturamento) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
                @if($taxaServico > 0)
                    <div class="card-footer text-muted small">Inclui {{ $brl($taxaServico) }} em taxa de serviço.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Itens vendidos --}}
    <div class="card mb-5">
        <div class="card-header fw-bold">Itens vendidos <span class="badge bg-secondary ms-1">{{ $itens->count() }}</span></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th style="width:48px">#</th><th>Item</th><th class="text-center">Qtd vendida</th><th class="text-end">Receita</th></tr>
                </thead>
                <tbody>
                    @foreach($itens as $i => $item)
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td class="fw-semibold">{{ $item->name }}</td>
                            <td class="text-center">{{ (int) $item->qtd }}</td>
                            <td class="text-end fw-semibold">{{ $brl($item->receita) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Comandas do período --}}
    <div class="card mb-5">
        <div class="card-header fw-bold d-flex align-items-center justify-content-between">
            <span>Comandas do período <span class="badge bg-secondary ms-1">{{ $listaComandas->total() }}</span></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Comanda</th>
                        <th>Fechada</th>
                        <th class="text-center">Itens</th>
                        <th>Pagamento</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($listaComandas as $c)
                        <tr>
                            <td>
                                <a href="{{ route('comandas.show', $c) }}" class="fw-semibold text-decoration-none">{{ $c->codigo }}</a>
                                @if($c->cliente)
                                    <div class="text-muted small">{{ $c->cliente }}</div>
                                @endif
                            </td>
                            <td class="text-nowrap">{{ $c->closed_at?->format('d/m H:i') }}</td>
                            <td class="text-center">{{ $c->items_count }}</td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ \App\Models\Comanda::paymentLabel($c->payment_method) }}</span>
                                @if($c->pagamentos->count() > 1 || $c->payment_method === \App\Models\Comanda::PAYMENT_MISTO)
                                    <div class="text-muted small mt-1">
                                        @foreach($c->pagamentos as $pg)
                                            <div>{{ $pg->method_label }} — {{ $brl($pg->valor) }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="text-end fw-semibold">{{ $brl($c->total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($listaComandas->hasPages())
            <div class="card-footer">
                {{ $listaComandas->links() }}
            </div>
        @endif
    </div>
    @endif

</div>
@endsection
