@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
<style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 20; vertical-align: middle; font-size: 18px; line-height: 1; }
    #qrcode img { border: 1px solid #dee2e6; border-radius: 8px; padding: 6px; background: #fff; }

    /* ── Pad de itens (touch-friendly) ── */
    .pad-filters { display: flex; flex-wrap: wrap; gap: .5rem; }
    .pad-filters .btn { border-radius: 999px; }
    .pad-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: .75rem; }
    .pad-item {
        border: 1px solid var(--sh-border, #2e2e2e); border-radius: 14px;
        background: var(--sh-bg2, #1a1a1a); overflow: hidden;
        cursor: pointer; text-align: left; padding: 0; transition: transform .08s, box-shadow .15s, border-color .15s;
        display: flex; flex-direction: column; -webkit-tap-highlight-color: transparent; user-select: none;
    }
    .pad-item:hover { box-shadow: 0 .4rem 1.1rem rgba(217,119,6,.22); border-color: var(--sh-orange, #d97706); }
    .pad-item:active { transform: scale(.97); }
    .pad-item .thumb {
        width: 100%; aspect-ratio: 1/1; object-fit: cover; background: var(--sh-bg3, #252525);
        display: flex; align-items: center; justify-content: center; font-size: 2.4rem;
    }
    .pad-item .info { padding: .5rem .6rem; }
    .pad-item .nome { font-weight: 600; font-size: .9rem; line-height: 1.15; color: var(--sh-text, #e8e8e8); }
    .pad-item .descricao { font-size: .75rem; line-height: 1.2; color: var(--sh-muted, #9ca3af); margin-top: .15rem; }
    .pad-item .preco { color: var(--sh-orange2, #f59e0b); font-weight: 800; font-size: .9rem; margin-top: .15rem; }
    .pad-item.is-hidden { display: none; }

    /* Stepper de quantidade no modal */
    .qty-stepper { display: flex; align-items: center; justify-content: center; gap: 1rem; }
    .qty-stepper .btn { width: 56px; height: 56px; font-size: 1.6rem; border-radius: 50%; padding: 0; }
    .qty-stepper input { width: 90px; text-align: center; font-size: 1.8rem; font-weight: 700; }

    /* ── CUPOM DE IMPRESSÃO (impressora térmica 80mm) ── */
    .print-area { display: none; }
    .resumo-area { display: none; }

    @media print {
        @page { size: 80mm auto; margin: 3mm; }
        html, body { background: #fff !important; margin: 0; padding: 0; }

        /* remove a UI do fluxo (evita páginas em branco) e mostra só o cupom */
        body > #app { display: none !important; }

        .print-area {
            display: block;
            width: 100%;
            color: #000;
            font-family: 'Nunito', Arial, sans-serif;
        }
        .pa-brand {
            text-align: center; font-weight: 800; font-size: 11pt;
            letter-spacing: .06em; margin-bottom: 2mm;
        }
        .pa-qrbox {
            border: 2px solid #000; border-radius: 4px;
            padding: 2mm;
            width: 50mm;          /* menor que a largura útil (80mm) */
            margin: 0 auto;        /* centralizado */
        }
        .pa-qrbox img, .pa-qrbox canvas {
            width: 100% !important; height: auto !important;
            display: block; image-rendering: pixelated;
        }
        .pa-code {
            text-align: center; font-family: 'Courier New', monospace;
            font-size: 22pt; font-weight: 700; letter-spacing: .18em;
            margin: 2.5mm 0 1mm;
        }
        .pa-code-label {
            text-align: center; font-size: 7pt; text-transform: uppercase;
            letter-spacing: .12em; color: #000;
        }
        .pa-info {
            text-align: center; font-size: 9pt; line-height: 1.5;
            margin-top: 2.5mm; padding-top: 2mm; border-top: 1px dashed #000;
        }
        .pa-info .pa-cliente { font-size: 11pt; font-weight: 800; }

        /* ── Resumo da comanda (cupom alternativo) ── */
        body.print-resumo .print-area { display: none !important; }
        body.print-resumo .resumo-area {
            display: block; width: 100%; color: #000;
            font-family: 'Nunito', Arial, sans-serif;
        }
        .ra-brand {
            text-align: center; font-weight: 800; font-size: 11pt;
            letter-spacing: .06em; margin-bottom: 1mm;
        }
        .ra-cliente { text-align: center; font-size: 11pt; font-weight: 800; }
        .ra-meta {
            text-align: center; font-size: 8pt; line-height: 1.4;
            margin-bottom: 2mm;
        }
        .ra-meta .ra-code { font-family: 'Courier New', monospace; letter-spacing: .12em; }
        .ra-items, .ra-tot { width: 100%; border-collapse: collapse; font-size: 9pt; }
        .ra-items { border-top: 1px dashed #000; margin-top: 2mm; }
        .ra-items td { padding: .7mm 0; vertical-align: top; }
        .ra-items .q { white-space: nowrap; padding-right: 1.5mm; }
        .ra-items .v { text-align: right; white-space: nowrap; padding-left: 1.5mm; }
        .ra-items .obs { font-size: 7.5pt; }
        .ra-tot { border-top: 1px dashed #000; margin-top: 2mm; padding-top: 1mm; }
        .ra-tot td { padding: .4mm 0; }
        .ra-tot .v { text-align: right; }
        .ra-tot .ra-total td {
            font-weight: 800; font-size: 11pt;
            border-top: 1px solid #000; padding-top: 1mm;
        }
        .ra-foot { text-align: center; font-size: 8pt; margin-top: 2.5mm; }

        /* ── Divisão da conta (cupom alternativo) ── */
        body.print-divisao .print-area,
        body.print-divisao .resumo-area { display: none !important; }
        body.print-divisao .divisao-area {
            display: block; width: 100%; color: #000;
            font-family: 'Nunito', Arial, sans-serif;
        }
        .da-title {
            text-align: center; font-weight: 800; font-size: 10pt;
            text-transform: uppercase; letter-spacing: .08em;
            margin-top: 2mm; padding-top: 2mm; border-top: 1px dashed #000;
        }
        .da-big {
            text-align: center; font-size: 18pt; font-weight: 800; margin: 1.5mm 0;
        }
        .da-pessoa {
            margin-top: 2.5mm; padding-top: 1.5mm; border-top: 1px dashed #000;
            font-size: 9pt;
        }
        .da-pessoa .da-nome {
            display: flex; justify-content: space-between;
            font-weight: 800; font-size: 10pt;
        }
        .da-pessoa .da-linha {
            display: flex; justify-content: space-between; font-size: 8.5pt;
        }
    }

    .divisao-area { display: none; }
</style>

<div class="container-lg">

    {{-- Cupom impresso (visível só na impressão — 58mm) --}}
    <div class="print-area">
        <div class="pa-brand">PESQUEIRO SHALOM</div>
        <div class="pa-qrbox"><div id="qrcode-print"></div></div>
        <div class="pa-code">{{ $comanda->codigo }}</div>
        <div class="pa-code-label">código da comanda</div>
        <div class="pa-info">
            <div>Aberta em {{ $comanda->created_at->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    {{-- Resumo impresso (visível só ao imprimir o resumo — 58mm) --}}
    <div class="resumo-area">
        <div class="ra-brand">PESQUEIRO SHALOM</div>
        <div class="ra-cliente">{{ $comanda->cliente }}</div>
        <div class="ra-meta">
            <div>Comanda <span class="ra-code">{{ $comanda->codigo }}</span></div>
            <div>Aberta em {{ $comanda->created_at->format('d/m/Y H:i') }}</div>
            @if(!$comanda->is_open && $comanda->closed_at)
                <div>Fechada em {{ $comanda->closed_at->format('d/m/Y H:i') }}</div>
            @endif
        </div>
        <table class="ra-items">
            @forelse($comanda->items as $item)
                <tr>
                    <td class="q">{{ $item->quantity }}x</td>
                    <td>{{ $item->name }}</td>
                    <td class="v">{{ $item->subtotal_formatted }}</td>
                </tr>
                @if($item->observacao)
                    <tr><td></td><td colspan="2" class="obs">— {{ $item->observacao }}</td></tr>
                @endif
            @empty
                <tr><td colspan="3" style="text-align:center;padding:2mm 0;">Nenhum item lançado.</td></tr>
            @endforelse
        </table>
        <table class="ra-tot">
            <tr><td>Subtotal</td><td class="v">{{ $comanda->subtotal_formatted }}</td></tr>
            @if($comanda->service_fee > 0)
                <tr><td>Taxa de serviço</td><td class="v">{{ $comanda->service_fee_formatted }}</td></tr>
            @endif
            <tr class="ra-total"><td>TOTAL</td><td class="v">{{ $comanda->total_formatted }}</td></tr>
            @if($comanda->tem_pagamentos_parciais)
                <tr><td>Já pago</td><td class="v">{{ $comanda->pago_formatted }}</td></tr>
                <tr class="ra-total"><td>RESTANTE</td><td class="v">{{ $comanda->restante_formatted }}</td></tr>
            @endif
            @if(!$comanda->is_open && $comanda->payment_method)
                <tr><td>Pagamento</td><td class="v">{{ \App\Models\Comanda::paymentLabel($comanda->payment_method) }}</td></tr>
            @endif
        </table>
        <div class="ra-foot">Obrigado pela preferência!</div>
    </div>

    {{-- Divisão da conta impressa (visível só ao imprimir a divisão — 80mm) --}}
    <div class="divisao-area">
        <div class="ra-brand">PESQUEIRO SHALOM</div>
        <div class="ra-cliente">{{ $comanda->cliente }}</div>
        <div class="ra-meta">
            <div>Comanda <span class="ra-code">{{ $comanda->codigo }}</span></div>
            <div>{{ now()->format('d/m/Y H:i') }}</div>
        </div>
        <div id="divisao-body"></div>
        <div class="ra-foot">Obrigado pela preferência!</div>
    </div>

    <div class="mb-3">
        <a href="{{ route('comandas.index') }}" class="text-muted text-decoration-none small">
            <span class="material-symbols-outlined">arrow_back</span> Voltar para comandas
        </a>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $emojiMap = [
            'PORÇÕES'=>'🍽','COMIDA'=>'🍚','SALGADOS'=>'🥟','CERVEJAS'=>'🍺',
            'BEBIDAS'=>'🥤','BEBIDAS QUENTES'=>'🥃','BORBONS'=>'🥃','CAIPIRINHAS'=>'🍹','DRINKS'=>'🍸'
        ];
    @endphp

    {{-- Cabeçalho + QR --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h4 class="mb-0 fw-bold">{{ $comanda->cliente }}</h4>
                    @php $s = $statuses[$comanda->status]; @endphp
                    <span class="badge {{ $s['class'] }}">{{ $s['label'] }}</span>
                </div>
                <div class="text-muted small">
                    Código <span class="badge bg-light text-dark border font-monospace">{{ $comanda->codigo }}</span>
                    · Aberta {{ $comanda->created_at->diffForHumans() }}
                    @if($comanda->user) · por {{ $comanda->user->name }} @endif
                </div>
                @if($comanda->observacao)
                    <div class="text-muted small mt-1"><em>{{ $comanda->observacao }}</em></div>
                @endif
            </div>
            <div class="text-center">
                <div id="qrcode" class="d-inline-block"></div>
                <div class="mt-1 no-print">
                    <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                        <span class="material-symbols-outlined">print</span> Imprimir QR
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── PAD de itens (apenas comanda aberta) ── --}}
    @if($comanda->is_open)
    <div class="card shadow-sm mb-4 no-print">
        <div class="card-header d-flex align-items-center justify-content-between gap-2">
            <span class="fw-bold">Adicionar itens</span>
            @if(auth()->user()->is_admin)
                <button type="button" id="toggle-reorder" class="btn btn-sm btn-outline-secondary">
                    <span class="material-symbols-outlined">swap_horiz</span> Reordenar
                </button>
            @endif
        </div>
        <div class="card-body">
            <div class="pad-filters mb-3" id="pad-filters">
                <button type="button" class="btn btn-sm btn-primary pad-filter active" data-cat="__all">Todos</button>
                @foreach($padCategorias as $categoria)
                    <button type="button" class="btn btn-sm btn-outline-primary pad-filter" data-cat="{{ $categoria }}">{{ $categoria }}</button>
                @endforeach
            </div>
            @if(auth()->user()->is_admin)
                <div id="reorder-hint" class="text-warning small mb-3 d-none">
                    <span class="material-symbols-outlined" style="font-size:16px;">drag_indicator</span>
                    Arraste os itens para reordenar (pode misturar categorias). Clique em “Concluir” ao terminar.
                </div>
            @endif
            @if($padItens->isEmpty())
                <p class="text-muted mb-0">Nenhum item disponível no cardápio.</p>
            @else
            <div class="pad-grid" id="pad-grid">
                @foreach($padItens as $ci)
                <div class="pad-item" data-cat="{{ $ci->category }}"
                     data-id="{{ $ci->id }}"
                     data-name="{{ $ci->name }}"
                     data-price="{{ $ci->price_formatted }}">
                    @if($ci->image)
                        <img src="{{ Storage::url($ci->image) }}" alt="{{ $ci->name }}" class="thumb">
                    @else
                        <div class="thumb">{{ $emojiMap[$ci->category] ?? '🍴' }}</div>
                    @endif
                    <div class="info">
                        <div class="nome">{{ $ci->name }}</div>
                        @if($ci->description)
                            <div class="descricao">{{ $ci->description }}</div>
                        @endif
                        <div class="preco">{{ $ci->price_formatted }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endif

    <div class="row g-4">

        {{-- Itens da comanda --}}
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold">Itens da comanda</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th class="text-end" style="width:90px">Unit.</th>
                                <th class="text-center" style="width:130px">Qtd</th>
                                <th class="text-end" style="width:100px">Subtotal</th>
                                @if($comanda->is_open)<th style="width:48px"></th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @php $paidMap = $comanda->itemPaidQuantities(); @endphp
                            @forelse($comanda->items as $item)
                            @php $itemPago = $paidMap[$item->id] ?? 0; @endphp
                            <tr>
                                <td>
                                    <span class="fw-semibold">{{ $item->name }}</span>
                                    @if($itemPago > 0)
                                        <span class="badge bg-success-subtle text-success border border-success ms-1" title="Itens já acertados (congelados)">
                                            <span class="material-symbols-outlined align-middle" style="font-size:14px;">lock</span> {{ $itemPago }}x pago
                                        </span>
                                    @endif
                                    @if($item->observacao)<div class="text-muted small">{{ $item->observacao }}</div>@endif
                                </td>
                                <td class="text-end">{{ $item->unit_price_formatted }}</td>
                                <td class="text-center">
                                    @if($comanda->is_open)
                                        <form action="{{ route('comandas.itens.update', [$comanda, $item]) }}" method="POST"
                                              class="d-inline-flex align-items-center gap-1">
                                            @csrf @method('PATCH')
                                            <input type="number" name="quantity" value="{{ $item->quantity }}"
                                                   min="{{ max(1, $itemPago) }}" max="99" class="form-control form-control-sm text-center"
                                                   style="width:64px" onchange="this.form.submit()">
                                        </form>
                                    @else
                                        {{ $item->quantity }}
                                    @endif
                                </td>
                                <td class="text-end fw-semibold">{{ $item->subtotal_formatted }}</td>
                                @if($comanda->is_open)
                                <td class="text-end">
                                    @if($itemPago > 0)
                                        <button class="btn btn-outline-secondary btn-sm" disabled title="Item já acertado — estorne o acerto para remover">
                                            <span class="material-symbols-outlined">lock</span>
                                        </button>
                                    @else
                                        <form action="{{ route('comandas.itens.remove', [$comanda, $item]) }}" method="POST"
                                              onsubmit="return confirm('Remover {{ addslashes($item->name) }}?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-outline-danger btn-sm" title="Remover">
                                                <span class="material-symbols-outlined">delete</span>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Nenhum item lançado ainda.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="text-end fw-semibold">Subtotal</td>
                                <td class="text-end fw-bold" colspan="{{ $comanda->is_open ? 2 : 1 }}">{{ $comanda->subtotal_formatted }}</td>
                            </tr>
                            @if($comanda->service_fee > 0)
                            <tr>
                                <td colspan="3" class="text-end fw-semibold">Taxa de serviço</td>
                                <td class="text-end" colspan="{{ $comanda->is_open ? 2 : 1 }}">{{ $comanda->service_fee_formatted }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Total</td>
                                <td class="text-end fw-bold fs-5" colspan="{{ $comanda->is_open ? 2 : 1 }}">{{ $comanda->total_formatted }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Fechar --}}
        <div class="col-12 col-lg-5 no-print">
            @if($comanda->is_open)
                {{-- ── Fechamento da comanda: divisão + acertos + fechar (unificado) ── --}}
                <div class="card shadow-sm border-success" id="acertos-card">
                    <div class="card-header fw-bold bg-success-subtle d-flex align-items-center justify-content-between">
                        <span><span class="material-symbols-outlined align-middle">point_of_sale</span> Fechamento da comanda</span>
                        @if($comanda->tem_pagamentos_parciais)
                            <span class="badge bg-success">{{ $comanda->pagamentos->count() }} acerto(s)</span>
                        @endif
                    </div>
                    <div class="card-body">
                        @if($comanda->items->isNotEmpty())
                            {{-- Resumo --}}
                            <div class="d-flex justify-content-between small text-muted">
                                <span>Total dos itens</span><span>{{ $comanda->subtotal_formatted }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Já pago</span><span class="fw-semibold text-success">{{ $comanda->pago_formatted }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1 pt-1 border-top">
                                <span class="fw-bold">Restante</span>
                                <span class="fw-bold fs-4" style="color:var(--sh-orange2);">{{ $comanda->restante_formatted }}</span>
                            </div>

                            @if($comanda->restante > 0)
                                {{-- Dividir conta (colapsável) --}}
                                <button class="btn btn-outline-primary btn-sm w-100 mt-3" type="button" data-bs-toggle="collapse" data-bs-target="#dividirBox">
                                    <span class="material-symbols-outlined align-middle">call_split</span> Dividir conta
                                </button>
                                <div class="collapse mt-3" id="dividirBox">
                                    <ul class="nav nav-pills nav-fill mb-3">
                                        <li class="nav-item"><button class="nav-link active" type="button" data-bs-toggle="pill" data-bs-target="#split-igual">Igualmente</button></li>
                                        <li class="nav-item"><button class="nav-link" type="button" data-bs-toggle="pill" data-bs-target="#split-itens">Por pessoa</button></li>
                                    </ul>
                                    @if($comanda->tem_pagamentos_parciais)
                                        <div class="alert alert-info py-2 small mb-3">
                                            Já pago <strong>{{ $comanda->pago_formatted }}</strong> · restante <strong>{{ $comanda->restante_formatted }}</strong>.
                                            Valores pagos "soltos" (sem itens) viram crédito e são abatidos igualmente de cada pessoa.
                                        </div>
                                    @endif
                                    <div class="tab-content">
                                        {{-- Divisão igual --}}
                                        <div class="tab-pane fade show active" id="split-igual">
                                            <label class="form-label">Número de pessoas</label>
                                            <input type="number" id="eq-people" class="form-control" value="2" min="1" max="50">
                                            <div class="mt-3 text-center">
                                                <div class="text-muted small">Cada pessoa paga</div>
                                                <div class="fs-2 fw-bold" id="eq-result" style="color:var(--sh-orange2);">—</div>
                                                <div class="text-muted small" id="eq-base"></div>
                                            </div>
                                            <div class="d-grid gap-2 mt-3">
                                                <button type="button" class="btn btn-success" onclick="registrarParteIgual()">
                                                    <span class="material-symbols-outlined">paid</span> Registrar 1 parte como paga
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="imprimirDivisaoIgual()">
                                                    <span class="material-symbols-outlined">print</span> Imprimir divisão
                                                </button>
                                            </div>
                                        </div>
                                        {{-- Divisão por pessoa (itens) --}}
                                        <div class="tab-pane fade" id="split-itens">
                                            <div class="d-flex align-items-end gap-2 mb-1">
                                                <div>
                                                    <label class="form-label mb-1">Pessoas</label>
                                                    <input type="number" id="it-people" class="form-control" value="2" min="1" max="10" style="width:90px;">
                                                </div>
                                            </div>
                                            <p class="form-text mt-1">Atribua a cada pessoa os itens que consumiu. Itens já acertados não aparecem.</p>
                                            <div class="table-responsive">
                                                <table class="table table-sm align-middle mb-1">
                                                    <thead class="table-light"><tr id="it-head"></tr></thead>
                                                    <tbody id="it-body"></tbody>
                                                    <tfoot class="table-light"><tr id="it-foot"></tr></tfoot>
                                                </table>
                                            </div>
                                            <div class="small text-warning" id="it-warn"></div>
                                            <button type="button" class="btn btn-outline-secondary w-100 mt-2" onclick="imprimirDivisaoPessoa()">
                                                <span class="material-symbols-outlined">print</span> Imprimir divisão
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Lista de acertos --}}
                            @if($comanda->pagamentos->isNotEmpty())
                                <ul class="list-group list-group-flush mt-3">
                                    @foreach($comanda->pagamentos as $pg)
                                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-semibold">{{ $pg->valor_formatted }}
                                                    <span class="badge bg-light text-dark border ms-1">{{ $pg->method_label }}</span>
                                                </div>
                                                <div class="text-muted small">
                                                    {{ $pg->descricao ?: 'Acerto' }} · {{ $pg->created_at->format('d/m H:i') }}
                                                    @if($pg->user) · {{ $pg->user->name }} @endif
                                                </div>
                                                @if($pg->detalhe_linhas)
                                                    <div class="text-muted small fst-italic">{{ implode(' · ', $pg->detalhe_linhas) }}</div>
                                                @endif
                                            </div>
                                            <div class="d-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                        onclick="imprimirComprovante({{ $pg->id }})" title="Comprovante">
                                                    <span class="material-symbols-outlined">receipt</span>
                                                </button>
                                                <form action="{{ route('comandas.pagamentos.remove', [$comanda, $pg]) }}" method="POST"
                                                      onsubmit="return confirm('Estornar este acerto de {{ $pg->valor_formatted }}?')">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger" title="Estornar">
                                                        <span class="material-symbols-outlined">undo</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            @if($comanda->restante > 0)
                                {{-- Registrar pagamento (manual ou vindo da divisão) --}}
                                <form action="{{ route('comandas.pagamentos.add', $comanda) }}" method="POST" class="mt-3" id="acerto-form">
                                    @csrf
                                    <input type="hidden" name="detalhe" id="acerto-detalhe">
                                    <div class="fw-semibold small mb-2 text-muted text-uppercase">Registrar pagamento</div>
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <label class="form-label mb-1 small">Quem está pagando (opcional)</label>
                                            <input type="text" name="descricao" id="acerto-descricao" class="form-control form-control-sm"
                                                   maxlength="60" placeholder="Ex: Casal, Pessoa 1…">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label mb-1 small">Valor *</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">R$</span>
                                                <input type="number" name="valor" id="acerto-valor" class="form-control"
                                                       step="0.01" min="0.01" max="{{ $comanda->restante }}"
                                                       value="{{ $comanda->restante }}" required>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label mb-1 small">Forma *</label>
                                            <select name="payment_method" class="form-select form-select-sm" required>
                                                <option value="">Selecione…</option>
                                                @foreach($methods as $key => $label)
                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <button class="btn btn-outline-success w-100 mt-2">
                                        <span class="material-symbols-outlined">add_card</span> Registrar acerto
                                    </button>
                                </form>
                            @else
                                <div class="alert alert-success mt-3 mb-0 py-2 text-center small">
                                    <span class="material-symbols-outlined align-middle">check_circle</span>
                                    Comanda totalmente acertada. Pode fechar.
                                </div>
                            @endif

                            <hr class="my-3">

                            {{-- Fechar — só quando tudo foi acertado --}}
                            @if($comanda->restante > 0)
                                <button class="btn btn-success w-100" disabled>
                                    <span class="material-symbols-outlined">paid</span> Fechar comanda
                                </button>
                                <div class="form-text text-center">Registre os pagamentos até zerar o restante para poder fechar.</div>
                            @else
                                <form action="{{ route('comandas.fechar', $comanda) }}" method="POST"
                                      onsubmit="return confirm('Fechar a comanda de {{ addslashes($comanda->cliente) }}?')">
                                    @csrf
                                    <button class="btn btn-success w-100">
                                        <span class="material-symbols-outlined">paid</span> Fechar comanda
                                    </button>
                                </form>
                            @endif
                        @else
                            <p class="text-muted small mb-0">Adicione itens para dividir e fechar a comanda.</p>
                        @endif

                        <button type="button" class="btn btn-outline-secondary w-100 mt-2" onclick="imprimirResumo()">
                            <span class="material-symbols-outlined">receipt_long</span> Imprimir resumo
                        </button>
                        <form action="{{ route('comandas.cancelar', $comanda) }}" method="POST" class="mt-2 requires-auth"
                              data-confirm="Cancelar esta comanda? Esta ação não pode ser desfeita.">
                            @csrf
                            <button class="btn btn-outline-danger btn-sm w-100">Cancelar comanda</button>
                        </form>
                    </div>
                </div>
            @else
                <div class="card shadow-sm">
                    <div class="card-body">
                        <p class="mb-1"><strong>Comanda {{ $statuses[$comanda->status]['label'] }}.</strong></p>
                        @if($comanda->status === 'fechada')
                            <p class="text-muted small mb-0">
                                Pagamento: {{ \App\Models\Comanda::paymentLabel($comanda->payment_method) }}<br>
                                Fechada em {{ optional($comanda->closed_at)->format('d/m/Y H:i') }}
                            </p>
                            @if($comanda->payment_method === \App\Models\Comanda::PAYMENT_MISTO && $comanda->pagamentos->isNotEmpty())
                                <ul class="list-group list-group-flush small mt-2">
                                    @foreach($comanda->pagamentos->sortBy('created_at') as $pg)
                                        <li class="list-group-item px-0 py-1 d-flex justify-content-between">
                                            <span>{{ $pg->method_label }}@if($pg->descricao) <span class="text-muted">· {{ $pg->descricao }}</span>@endif</span>
                                            <span class="fw-semibold">{{ $pg->valor_formatted }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        @endif
                        <button type="button" class="btn btn-outline-secondary w-100 mt-3" onclick="imprimirResumo()">
                            <span class="material-symbols-outlined">receipt_long</span> Imprimir resumo
                        </button>
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>

{{-- Modal: quantidade + observação ao tocar num item do pad --}}
@if($comanda->is_open)
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('comandas.itens.add', $comanda) }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="cardapio_item_id" id="modal-item-id">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-item-name">Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted text-center mb-3" id="modal-item-price"></p>

                <label class="form-label d-block text-center">Quantidade</label>
                <div class="qty-stepper mb-4">
                    <button type="button" class="btn btn-outline-secondary" id="qty-minus">−</button>
                    <input type="number" name="quantity" id="modal-qty" class="form-control" value="1" min="1" max="99" inputmode="numeric">
                    <button type="button" class="btn btn-outline-secondary" id="qty-plus">+</button>
                </div>

                <div class="mb-1">
                    <label class="form-label">Observação</label>
                    <textarea name="observacao" id="modal-obs" class="form-control" rows="2" maxlength="255"
                              placeholder="ex: sem cebola, bem gelada…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary">
                    <span class="material-symbols-outlined">add</span> Adicionar
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
@if(auth()->user()->is_admin)
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
@endif
<script>
// Imprime o resumo da comanda (alterna o cupom mostrado na impressão)
function imprimirResumo() {
    document.body.classList.add('print-resumo');
    window.print();
}
window.addEventListener('afterprint', () => document.body.classList.remove('print-resumo', 'print-divisao'));

// ── Acertos (pagamentos parciais) ──
@php
    $pagamentosJs = $comanda->pagamentos->mapWithKeys(fn($p) => [$p->id => [
        'descricao' => $p->descricao,
        'valor'     => $p->valor_formatted,
        'metodo'    => $p->method_label,
        'data'      => $p->created_at->format('d/m/Y H:i'),
    ]]);
@endphp
const PAGAMENTOS = @json($pagamentosJs);
const RESTANTE_ATUAL = @json($comanda->restante_formatted);

function brlToNumber(s) {
    return parseFloat(String(s).replace(/[^0-9,.-]/g, '').replace(/\./g, '').replace(',', '.')) || 0;
}
function htmlEscape(s) {
    return String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
}

// Preenche o formulário de acerto com um valor (e o detalhe da divisão) vindo da divisão
function preencherAcerto(valor, descricao, detalhe) {
    const v = document.getElementById('acerto-valor');
    const d = document.getElementById('acerto-descricao');
    const det = document.getElementById('acerto-detalhe');
    if (!v) { alert('A comanda já está totalmente acertada.'); return; }
    v.value = (Math.round((Number(valor) || 0) * 100) / 100).toFixed(2);
    if (d && descricao) d.value = descricao;
    if (det) det.value = detalhe ? JSON.stringify(detalhe) : '';
    const card = document.getElementById('acertos-card');
    if (card) card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    v.focus();
}

// Editar o valor à mão invalida o detalhamento da divisão (deixa de bater)
document.addEventListener('DOMContentLoaded', () => {
    const v = document.getElementById('acerto-valor');
    const det = document.getElementById('acerto-detalhe');
    if (v && det) v.addEventListener('input', () => { det.value = ''; });
});

// Imprime o comprovante de um acerto já registrado
function imprimirComprovante(id) {
    const p = PAGAMENTOS[id];
    if (!p) return;
    const body = document.getElementById('divisao-body');
    let h = '<div class="da-title">Comprovante de acerto</div>';
    if (p.descricao) h += '<div class="ra-meta"><div>' + htmlEscape(p.descricao) + '</div></div>';
    h += '<div class="da-big">' + p.valor + '</div>';
    h += '<div style="text-align:center;font-size:9pt;">' + p.metodo + '</div>';
    h += '<div style="text-align:center;font-size:8pt;margin-top:1mm;">' + p.data + '</div>';
    h += '<div class="da-pessoa"><div class="da-nome"><span>Restante da mesa</span><span>' + RESTANTE_ATUAL + '</span></div></div>';
    body.innerHTML = h;
    document.body.classList.add('print-divisao');
    window.print();
}

// Espera o módulo do Vite (type=module, deferred) definir window.bootstrap
document.addEventListener('DOMContentLoaded', function () {
    // move os cupons para fora do #app (que é ocultado na impressão)
    const printArea = document.querySelector('.print-area');
    if (printArea) document.body.appendChild(printArea);
    const resumoArea = document.querySelector('.resumo-area');
    if (resumoArea) document.body.appendChild(resumoArea);
    const divisaoArea = document.querySelector('.divisao-area');
    if (divisaoArea) document.body.appendChild(divisaoArea);

    new QRCode(document.getElementById('qrcode'), {
        text: @json($comanda->codigo),
        width: 130, height: 130,
        correctLevel: QRCode.CorrectLevel.M,
    });

    // QR do cupom impresso — alta resolução, escala via CSS para 100% da largura (58mm)
    const qrPrintEl = document.getElementById('qrcode-print');
    if (qrPrintEl) {
        new QRCode(qrPrintEl, {
            text: @json($comanda->codigo),
            width: 320, height: 320,
            correctLevel: QRCode.CorrectLevel.M,
        });
    }

    @if($comanda->is_open)
    // ── Filtro de categorias do pad ──
    let reorderMode = false;
    const filters = document.querySelectorAll('.pad-filter');
    const items   = document.querySelectorAll('.pad-item');
    filters.forEach(btn => btn.addEventListener('click', () => {
        if (reorderMode) return; // em modo reordenar, clique não filtra
        const cat = btn.dataset.cat;
        filters.forEach(b => { b.classList.toggle('active', b === btn); b.classList.toggle('btn-primary', b === btn); b.classList.toggle('btn-outline-primary', b !== btn); });
        items.forEach(it => it.classList.toggle('is-hidden', cat !== '__all' && it.dataset.cat !== cat));
    }));

    @if(auth()->user()->is_admin)
    // ── Reordenar itens do pad (drag-and-drop por item, mistura categorias) ──
    (function () {
        const padGrid   = document.getElementById('pad-grid');
        const toggleBtn = document.getElementById('toggle-reorder');
        const hint      = document.getElementById('reorder-hint');
        if (!padGrid || !toggleBtn || !window.Sortable) return;

        const sortable = Sortable.create(padGrid, {
            draggable: '.pad-item',
            animation: 150,
            disabled: true,
            onEnd: function () {
                const order = Array.from(padGrid.querySelectorAll('.pad-item')).map(el => parseInt(el.dataset.id));
                fetch(@json(route('comandas.pad-order')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ order })
                });
            }
        });

        toggleBtn.addEventListener('click', () => {
            reorderMode = !reorderMode;
            sortable.option('disabled', !reorderMode);
            if (hint) hint.classList.toggle('d-none', !reorderMode);
            toggleBtn.classList.toggle('btn-outline-secondary', !reorderMode);
            toggleBtn.classList.toggle('btn-warning', reorderMode);
            toggleBtn.innerHTML = reorderMode
                ? '<span class="material-symbols-outlined">check</span> Concluir'
                : '<span class="material-symbols-outlined">swap_horiz</span> Reordenar';

            if (reorderMode) {
                // mostra todos os itens e ativa "Todos" para poder misturar categorias
                items.forEach(it => { it.classList.remove('is-hidden'); it.style.cursor = 'grab'; });
                filters.forEach(b => {
                    const all = b.dataset.cat === '__all';
                    b.classList.toggle('active', all);
                    b.classList.toggle('btn-primary', all);
                    b.classList.toggle('btn-outline-primary', !all);
                });
            } else {
                items.forEach(it => it.style.cursor = '');
            }
        });
    })();
    @endif

    // ── Abrir modal ao tocar/clicar num item ──
    const modalEl   = document.getElementById('addItemModal');
    const modal     = new bootstrap.Modal(modalEl);
    const elId      = document.getElementById('modal-item-id');
    const elName    = document.getElementById('modal-item-name');
    const elPrice   = document.getElementById('modal-item-price');
    const elQty     = document.getElementById('modal-qty');
    const elObs     = document.getElementById('modal-obs');

    items.forEach(it => it.addEventListener('click', () => {
        if (reorderMode) return; // em modo reordenar, clique não adiciona
        elId.value    = it.dataset.id;
        elName.textContent  = it.dataset.name;
        elPrice.textContent = it.dataset.price;
        elQty.value   = 1;
        elObs.value   = '';
        modal.show();
    }));

    // Stepper
    const clamp = v => Math.max(1, Math.min(99, v || 1));
    document.getElementById('qty-minus').addEventListener('click', () => elQty.value = clamp(parseInt(elQty.value) - 1));
    document.getElementById('qty-plus').addEventListener('click',  () => elQty.value = clamp(parseInt(elQty.value) + 1));
    modalEl.addEventListener('shown.bs.modal', () => { elQty.focus(); elQty.select(); });

    // ── Divisão de conta ──
    (function () {
        const eqPeople = document.getElementById('eq-people');
        if (!eqPeople) return; // sem itens, não há divisão

        const SUBTOTAL = {{ $comanda->subtotal }};
        const PAGO     = {{ $comanda->pago }};
        const RESTANTE = {{ $comanda->restante }}; // o que ainda falta acertar
        @php
            $paidMap = $comanda->itemPaidQuantities();
            $itensJs = $comanda->items->map(fn($i) => [
                'id'    => $i->id,
                'nome'  => $i->name,
                'preco' => (float) $i->unit_price,
                'qtd'   => $i->quantity - ($paidMap[$i->id] ?? 0), // só o que ainda falta (congela o já pago)
            ])->filter(fn($i) => $i['qtd'] > 0)->values();
        @endphp
        const ITENS = @json($itensJs);
        const brl = v => 'R$ ' + (Number(v) || 0).toFixed(2).replace('.', ',');
        // Soma dos itens ainda não congelados e crédito já pago "solto" (acertos sem itens) a abater igualmente
        const UNFROZEN_SUM = ITENS.reduce((s, it) => s + it.preco * it.qtd, 0);
        const PREPAGO = Math.max(0, Math.round((UNFROZEN_SUM - RESTANTE) * 100) / 100);

        // Divisão igual
        const eqResult = document.getElementById('eq-result');
        const eqBase   = document.getElementById('eq-base');
        function calcEqual() {
            const n = Math.max(1, parseInt(eqPeople.value) || 1);
            const base = RESTANTE; // divide o que ainda falta, não o total cheio
            eqResult.textContent = brl(base / n);
            eqBase.textContent = (PAGO > 0 ? 'Restante ' : 'Total ') + brl(base) + ' ÷ ' + n + (n > 1 ? ' pessoas' : ' pessoa')
                + (PAGO > 0 ? ' · já pago ' + brl(PAGO) : '');
        }
        eqPeople.addEventListener('input', calcEqual);
        calcEqual();

        // Divisão por pessoa (matriz itens × pessoas)
        const itPeople = document.getElementById('it-people');
        const itHead = document.getElementById('it-head');
        const itBody = document.getElementById('it-body');
        const itFoot = document.getElementById('it-foot');
        const itWarn = document.getElementById('it-warn');

        function recalc() {
            const tot = {};
            document.querySelectorAll('.it-total').forEach(td => tot[td.dataset.person] = 0);
            let ok = true;
            ITENS.forEach((it, idx) => {
                let rowSum = 0;
                document.querySelectorAll('.it-alloc[data-item="' + idx + '"]').forEach(i => {
                    const q = parseInt(i.value) || 0;
                    tot[i.dataset.person] = (tot[i.dataset.person] || 0) + q * it.preco;
                    rowSum += q;
                });
                if (rowSum !== it.qtd) ok = false;
            });
            const pessoas = document.querySelectorAll('.it-total').length || 1;
            const desconto = PREPAGO > 0 ? PREPAGO / pessoas : 0;
            document.querySelectorAll('.it-total').forEach(td => td.textContent = brl((tot[td.dataset.person] || 0) - desconto));
            itWarn.textContent = ok ? '' : '⚠ A soma por item ainda não bate com a quantidade lançada.';
        }
        function build() {
            if (ITENS.length === 0) {
                itHead.innerHTML = '';
                itBody.innerHTML = '<tr><td class="text-center text-muted py-2">Todos os itens já foram acertados.</td></tr>';
                itFoot.innerHTML = '';
                return;
            }
            const n = Math.max(1, Math.min(10, parseInt(itPeople.value) || 1));
            let h = '<th>Item</th><th class="text-end">Unit.</th><th class="text-center">Qtd</th>';
            for (let p = 0; p < n; p++) h += '<th class="text-center">P' + (p + 1) + '</th>';
            itHead.innerHTML = h;
            itBody.innerHTML = ITENS.map((it, idx) => {
                let r = '<tr><td>' + it.nome + '</td><td class="text-end">' + brl(it.preco) + '</td><td class="text-center">' + it.qtd + '</td>';
                for (let p = 0; p < n; p++)
                    r += '<td class="p-1"><input type="number" class="form-control form-control-sm text-center it-alloc" data-item="' + idx + '" data-person="' + p + '" min="0" max="' + it.qtd + '" value="0" style="width:52px;margin:0 auto;"></td>';
                return r + '</tr>';
            }).join('');
            let f = '<td colspan="3" class="text-end fw-semibold">Cada pessoa</td>';
            for (let p = 0; p < n; p++)
                f += '<td class="text-center"><div class="fw-bold it-total" data-person="' + p + '" style="color:var(--sh-orange2);">—</div>'
                   + '<button type="button" class="btn btn-sm btn-success mt-1 py-0 px-1" style="font-size:.7rem;" onclick="registrarPessoa(' + p + ')">Pago</button></td>';
            itFoot.innerHTML = f;
            document.querySelectorAll('.it-alloc').forEach(inp => inp.addEventListener('input', () => {
                const idx = inp.dataset.item, max = ITENS[idx].qtd;
                let sum = 0;
                document.querySelectorAll('.it-alloc[data-item="' + idx + '"]').forEach(i => sum += parseInt(i.value) || 0);
                if (sum > max) inp.value = Math.max(0, (parseInt(inp.value) || 0) - (sum - max));
                recalc();
            }));
            recalc();
        }
        itPeople.addEventListener('input', build);
        build();

        // ── Impressão da divisão ──
        const escapeHtml = s => String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
        const body = document.getElementById('divisao-body');

        window.imprimirDivisaoIgual = function () {
            const n = Math.max(1, parseInt(eqPeople.value) || 1);
            const base = RESTANTE;
            let h = '<div class="da-title">Divisão igual</div>';
            if (PAGO > 0) h += '<div class="ra-meta"><div>Já pago ' + brl(PAGO) + '</div></div>';
            h += '<div class="ra-meta"><div>' + (PAGO > 0 ? 'Restante ' : 'Total ') + brl(base) + '</div><div>÷ ' + n + (n > 1 ? ' pessoas' : ' pessoa') + '</div></div>';
            h += '<div style="text-align:center;font-size:8pt;">Cada pessoa paga</div>';
            h += '<div class="da-big">' + brl(base / n) + '</div>';
            body.innerHTML = h;
            document.body.classList.add('print-divisao');
            window.print();
        };

        window.imprimirDivisaoPessoa = function () {
            const n = Math.max(1, Math.min(10, parseInt(itPeople.value) || 1));
            const desconto = PREPAGO > 0 ? Math.round((PREPAGO / n) * 100) / 100 : 0;
            let h = '<div class="da-title">Divisão por pessoa</div>';
            let geral = 0;
            for (let p = 0; p < n; p++) {
                const linhas = [];
                let total = 0;
                ITENS.forEach((it, idx) => {
                    const inp = document.querySelector('.it-alloc[data-item="' + idx + '"][data-person="' + p + '"]');
                    const q = inp ? (parseInt(inp.value) || 0) : 0;
                    if (q > 0) { linhas.push({ nome: it.nome, q: q, val: q * it.preco }); total += q * it.preco; }
                });
                const liquido = total - desconto;
                geral += liquido;
                h += '<div class="da-pessoa"><div class="da-nome"><span>Pessoa ' + (p + 1) + '</span><span>' + brl(liquido) + '</span></div>';
                if (linhas.length === 0) {
                    h += '<div class="da-linha"><span>—</span><span></span></div>';
                } else {
                    linhas.forEach(l => { h += '<div class="da-linha"><span>' + l.q + 'x ' + escapeHtml(l.nome) + '</span><span>' + brl(l.val) + '</span></div>'; });
                }
                if (desconto > 0) h += '<div class="da-linha"><span>Crédito já pago</span><span>− ' + brl(desconto) + '</span></div>';
                h += '</div>';
            }
            h += '<div class="da-pessoa"><div class="da-nome"><span>Total geral</span><span>' + brl(geral) + '</span></div></div>';
            if (PAGO > 0) {
                h += '<div style="font-size:8pt;text-align:center;margin-top:1.5mm;">Já pago ' + brl(PAGO) + ' · restante ' + brl(RESTANTE) + '.</div>';
            }
            body.innerHTML = h;
            document.body.classList.add('print-divisao');
            window.print();
        };

        // ── Enviar uma parte da divisão para o formulário de acerto ──
        window.registrarParteIgual = function () {
            const n = Math.max(1, parseInt(eqPeople.value) || 1);
            const fracao = '1 de ' + n + (n > 1 ? ' pessoas' : ' pessoa') + ' (divisão igual)';
            preencherAcerto(RESTANTE / n, n > 1 ? '1 de ' + n + ' pessoas' : 'Pagamento',
                { tipo: 'igual', fracao: fracao });
        };
        window.registrarPessoa = function (p) {
            const itens = [];
            let itensTotal = 0;
            ITENS.forEach((it, idx) => {
                const inp = document.querySelector('.it-alloc[data-item="' + idx + '"][data-person="' + p + '"]');
                const q = inp ? (parseInt(inp.value) || 0) : 0;
                if (q > 0) { itens.push({ id: it.id, nome: it.nome, qtd: q, preco: it.preco }); itensTotal += q * it.preco; }
            });
            if (itensTotal <= 0) { alert('Atribua itens à Pessoa ' + (p + 1) + ' antes de registrar.'); return; }

            const pessoas = Math.max(1, Math.min(10, parseInt(itPeople.value) || 1));
            const desconto = PREPAGO > 0 ? Math.round((PREPAGO / pessoas) * 100) / 100 : 0;
            const valor = Math.round((itensTotal - desconto) * 100) / 100;
            if (valor < 0.01) { alert('O crédito já pago cobre a parte da Pessoa ' + (p + 1) + '. Nada a registrar.'); return; }

            const det = { tipo: 'pessoa', itens: itens };
            if (desconto > 0) det.desconto = desconto;
            preencherAcerto(valor, 'Pessoa ' + (p + 1), det);
        };
    })();

    // ── Atualização automática (polling) da comanda ──
    (function () {
        const INIT_SIG = @json($comanda->liveSignature());
        const URL = @json(route('comandas.sig', $comanda));
        function safeToReload() {
            if (document.querySelector('.modal.show')) return false; // não interrompe modal
            const a = document.activeElement;
            if (a && /^(INPUT|TEXTAREA|SELECT)$/.test(a.tagName)) return false; // não interrompe digitação
            return true;
        }
        setInterval(async () => {
            try {
                const r = await fetch(URL, { headers: { 'Accept': 'application/json' } });
                if (!r.ok) return;
                const d = await r.json();
                if (d.sig !== INIT_SIG && safeToReload()) location.reload();
            } catch (e) { /* silencioso */ }
        }, 8000);
    })();
    @endif

    @if(session('print'))
    // Comanda recém-criada: abre a impressão e permanece na tela da comanda
    setTimeout(function () { window.print(); }, 400);
    @endif
});
</script>
@endsection
