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
    .pad-item .preco { color: var(--sh-orange2, #f59e0b); font-weight: 800; font-size: .9rem; margin-top: .15rem; }
    .pad-item.is-hidden { display: none; }

    /* Stepper de quantidade no modal */
    .qty-stepper { display: flex; align-items: center; justify-content: center; gap: 1rem; }
    .qty-stepper .btn { width: 56px; height: 56px; font-size: 1.6rem; border-radius: 50%; padding: 0; }
    .qty-stepper input { width: 90px; text-align: center; font-size: 1.8rem; font-weight: 700; }

    /* ── CUPOM DE IMPRESSÃO (impressora térmica 58mm) ── */
    .print-area { display: none; }

    @media print {
        @page { size: 58mm auto; margin: 3mm; }
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
            width: 38mm;          /* menor que a largura útil */
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
    }
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
                            @forelse($comanda->items as $item)
                            <tr>
                                <td>
                                    <span class="fw-semibold">{{ $item->name }}</span>
                                    @if($item->observacao)<div class="text-muted small">{{ $item->observacao }}</div>@endif
                                </td>
                                <td class="text-end">{{ $item->unit_price_formatted }}</td>
                                <td class="text-center">
                                    @if($comanda->is_open)
                                        <form action="{{ route('comandas.itens.update', [$comanda, $item]) }}" method="POST"
                                              class="d-inline-flex align-items-center gap-1">
                                            @csrf @method('PATCH')
                                            <input type="number" name="quantity" value="{{ $item->quantity }}"
                                                   min="1" max="99" class="form-control form-control-sm text-center"
                                                   style="width:64px" onchange="this.form.submit()">
                                        </form>
                                    @else
                                        {{ $item->quantity }}
                                    @endif
                                </td>
                                <td class="text-end fw-semibold">{{ $item->subtotal_formatted }}</td>
                                @if($comanda->is_open)
                                <td class="text-end">
                                    <form action="{{ route('comandas.itens.remove', [$comanda, $item]) }}" method="POST"
                                          onsubmit="return confirm('Remover {{ addslashes($item->name) }}?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm" title="Remover">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </form>
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
                            @if($comanda->service_fee > 0 || !$comanda->is_open)
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
                {{-- Dividir conta --}}
                @if($comanda->items->isNotEmpty())
                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-bold">Dividir conta</span>
                        <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#dividirBox">
                            <span class="material-symbols-outlined">call_split</span> Dividir
                        </button>
                    </div>
                    <div class="collapse" id="dividirBox">
                        <div class="card-body">
                            <ul class="nav nav-pills nav-fill mb-3">
                                <li class="nav-item"><button class="nav-link active" type="button" data-bs-toggle="pill" data-bs-target="#split-igual">Igualmente</button></li>
                                <li class="nav-item"><button class="nav-link" type="button" data-bs-toggle="pill" data-bs-target="#split-itens">Por pessoa</button></li>
                            </ul>
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
                                </div>
                                {{-- Divisão por pessoa (itens) --}}
                                <div class="tab-pane fade" id="split-itens">
                                    <div class="d-flex align-items-end gap-2 mb-1">
                                        <div>
                                            <label class="form-label mb-1">Pessoas</label>
                                            <input type="number" id="it-people" class="form-control" value="2" min="1" max="10" style="width:90px;">
                                        </div>
                                    </div>
                                    <p class="form-text mt-1">Para cada item, informe quantas unidades cada pessoa consumiu.</p>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-1">
                                            <thead class="table-light"><tr id="it-head"></tr></thead>
                                            <tbody id="it-body"></tbody>
                                            <tfoot class="table-light"><tr id="it-foot"></tr></tfoot>
                                        </table>
                                    </div>
                                    <div class="small text-warning" id="it-warn"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="card shadow-sm border-success">
                    <div class="card-header fw-bold bg-success-subtle">Fechar comanda</div>
                    <div class="card-body">
                        <form action="{{ route('comandas.fechar', $comanda) }}" method="POST"
                              onsubmit="return confirm('Fechar a comanda de {{ addslashes($comanda->cliente) }}?')">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Forma de pagamento *</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="">Selecione…</option>
                                    @foreach($methods as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Taxa de serviço (R$)</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" name="service_fee" id="service_fee" value="0" min="0" step="0.01" class="form-control">
                                    <button type="button" class="btn btn-outline-secondary" id="btn-fee-10" title="10% do subtotal">+10%</button>
                                </div>
                                <div class="form-text">10% do subtotal = {{ \App\Models\Comanda::money($comanda->subtotal * 0.10) }}</div>
                            </div>
                            <button class="btn btn-success w-100">
                                <span class="material-symbols-outlined">paid</span> Fechar e registrar pagamento
                            </button>
                        </form>
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
                                Pagamento: {{ $methods[$comanda->payment_method] ?? '—' }}<br>
                                Fechada em {{ optional($comanda->closed_at)->format('d/m/Y H:i') }}
                            </p>
                        @endif
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
// Espera o módulo do Vite (type=module, deferred) definir window.bootstrap
document.addEventListener('DOMContentLoaded', function () {
    // move o cupom para fora do #app (que é ocultado na impressão)
    const printArea = document.querySelector('.print-area');
    if (printArea) document.body.appendChild(printArea);

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

    // Botão +10% no fechamento
    const fee10 = document.getElementById('btn-fee-10');
    if (fee10) fee10.addEventListener('click', () => {
        const sf = document.getElementById('service_fee');
        sf.value = ({{ $comanda->subtotal }} * 0.10).toFixed(2);
        sf.dispatchEvent(new Event('input'));
    });

    // ── Divisão de conta ──
    (function () {
        const eqPeople = document.getElementById('eq-people');
        if (!eqPeople) return; // sem itens, não há divisão

        const SUBTOTAL = {{ $comanda->subtotal }};
        const ITENS = @json($comanda->items->map(fn($i) => ['nome' => $i->name, 'preco' => (float) $i->unit_price, 'qtd' => $i->quantity])->values());
        const brl = v => 'R$ ' + (Number(v) || 0).toFixed(2).replace('.', ',');
        const feeEl = document.getElementById('service_fee');
        const fee = () => feeEl ? (parseFloat(feeEl.value) || 0) : 0;

        // Divisão igual
        const eqResult = document.getElementById('eq-result');
        const eqBase   = document.getElementById('eq-base');
        function calcEqual() {
            const n = Math.max(1, parseInt(eqPeople.value) || 1);
            const base = SUBTOTAL + fee();
            eqResult.textContent = brl(base / n);
            eqBase.textContent = 'Total ' + brl(base) + ' ÷ ' + n + (n > 1 ? ' pessoas' : ' pessoa');
        }
        eqPeople.addEventListener('input', calcEqual);
        if (feeEl) feeEl.addEventListener('input', calcEqual);
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
            document.querySelectorAll('.it-total').forEach(td => td.textContent = brl(tot[td.dataset.person]));
            itWarn.textContent = ok ? '' : '⚠ A soma por item ainda não bate com a quantidade lançada.';
        }
        function build() {
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
            for (let p = 0; p < n; p++) f += '<td class="text-center fw-bold it-total" data-person="' + p + '" style="color:var(--sh-orange2);">—</td>';
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
