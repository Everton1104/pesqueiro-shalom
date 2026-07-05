@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
<style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 20; vertical-align: middle; font-size: 18px; line-height: 1; }

    /* ── Pad de itens (reaproveitado da comanda) ── */
    .pad-filters { display: flex; flex-wrap: wrap; gap: .5rem; }
    .pad-filters .btn { border-radius: 999px; }
    .pad-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(135px, 1fr)); gap: .75rem; }
    .pad-item {
        border: 1px solid var(--sh-border, #2e2e2e); border-radius: 14px;
        background: var(--sh-bg2, #1a1a1a); overflow: hidden;
        cursor: pointer; text-align: left; padding: 0; transition: transform .08s, box-shadow .15s, border-color .15s;
        display: flex; flex-direction: column; -webkit-tap-highlight-color: transparent; user-select: none; position: relative;
    }
    .pad-item:hover { box-shadow: 0 .4rem 1.1rem rgba(217,119,6,.22); border-color: var(--sh-orange, #d97706); }
    .pad-item:active { transform: scale(.97); }
    .pad-item .thumb {
        width: 100%; aspect-ratio: 1/1; object-fit: cover; background: var(--sh-bg3, #252525);
        display: flex; align-items: center; justify-content: center; font-size: 2.2rem;
    }
    .pad-item .info { padding: .5rem .6rem; }
    .pad-item .nome { font-weight: 600; font-size: .88rem; line-height: 1.15; color: var(--sh-text, #e8e8e8); }
    .pad-item .preco { color: var(--sh-orange2, #f59e0b); font-weight: 800; font-size: .88rem; margin-top: .15rem; }
    .pad-item .badge-cozinha { position: absolute; top: 6px; right: 6px; font-size: .6rem; }
    .pad-item .qty-bolha {
        position: absolute; top: 6px; left: 6px; min-width: 22px; height: 22px; border-radius: 999px;
        background: var(--sh-orange, #d97706); color: #fff; font-weight: 800; font-size: .75rem;
        display: none; align-items: center; justify-content: center; padding: 0 5px;
    }
    .pad-item.is-hidden { display: none; }

    /* ── Carrinho ── */
    .cart-sticky { position: sticky; top: 1rem; }
    .cart-item { display: flex; align-items: center; gap: .5rem; padding: .4rem 0; border-bottom: 1px solid var(--sh-border, #2e2e2e); }
    .cart-item .ci-nome { flex-grow: 1; font-size: .9rem; }
    .cart-item .ci-step { display: flex; align-items: center; gap: .35rem; }
    .cart-item .ci-step .btn { width: 30px; height: 30px; padding: 0; border-radius: 50%; line-height: 1; }
    .cart-item .ci-qtd { min-width: 22px; text-align: center; font-weight: 700; }
    .method-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }
    .method-grid .btn { padding: .8rem; font-weight: 700; }
    .method-grid .btn.active { box-shadow: 0 0 0 .2rem rgba(217,119,6,.4); }

    /* ── Stepper de quantidade (modal de item) ── */
    .qty-stepper { display: flex; align-items: center; justify-content: center; gap: 1rem; }
    .qty-stepper .btn { width: 52px; height: 52px; font-size: 1.5rem; border-radius: 50%; padding: 0; }
    .qty-stepper input { width: 84px; text-align: center; font-size: 1.6rem; font-weight: 700; }

    /* ── CUPOM DE IMPRESSÃO (impressora térmica 80mm) ── */
    .print-area { display: none; }
    @media print {
        @page { size: 80mm auto; margin: 3mm; }
        html, body { background: #fff !important; margin: 0; padding: 0; }
        body > #app { display: none !important; }
        .print-area { display: block; width: 100%; color: #000; font-family: 'Nunito', Arial, sans-serif; }
        .pa-brand { text-align: center; font-weight: 800; font-size: 11pt; letter-spacing: .06em; margin-bottom: 1mm; }
        .pa-tipo { text-align: center; font-size: 8pt; text-transform: uppercase; letter-spacing: .14em; margin-bottom: 2mm; }
        .pa-qrbox { border: 2px solid #000; border-radius: 4px; padding: 2mm; width: 50mm; margin: 0 auto; }
        .pa-qrbox img, .pa-qrbox canvas { width: 100% !important; height: auto !important; display: block; image-rendering: pixelated; }
        .pa-code { text-align: center; font-family: 'Courier New', monospace; font-size: 22pt; font-weight: 700; letter-spacing: .18em; margin: 2.5mm 0 1mm; }
        .pa-code-label { text-align: center; font-size: 7pt; text-transform: uppercase; letter-spacing: .12em; }
        .pa-cliente { text-align: center; font-size: 12pt; font-weight: 800; margin-top: 2mm; }
        .pa-items { width: 100%; border-collapse: collapse; font-size: 9pt; border-top: 1px dashed #000; margin-top: 2.5mm; }
        .pa-items td { padding: 1mm 0; vertical-align: top; }
        .pa-items .chk { font-size: 13pt; line-height: 1; padding-right: 2mm; white-space: nowrap; }
        .pa-items .q { white-space: nowrap; padding-right: 2mm; font-weight: 700; }
        .pa-items .sec td { font-weight: 800; font-size: 8pt; text-transform: uppercase; padding-top: 1.5mm; }
        .pa-tot { width: 100%; border-top: 1px dashed #000; margin-top: 1.5mm; padding-top: 1mm; font-weight: 800; font-size: 12pt; }
        .pa-tot td { padding: .4mm 0; }
        .pa-tot .v { text-align: right; }
        .pa-info { text-align: center; font-size: 8pt; margin-top: 2.5mm; padding-top: 2mm; border-top: 1px dashed #000; }
    }
</style>

@if($printFicha)
<div class="print-area" id="print-area">
    <div class="pa-brand">PESQUEIRO SHALOM</div>
    <div class="pa-tipo">Ficha — pré-paga</div>
    <div class="pa-code">{{ $printFicha->codigo }}</div>
    @if($printFicha->cliente)<div class="pa-cliente">{{ $printFicha->cliente }}</div>@endif
    <table class="pa-items">
        <tr class="sec"><td colspan="3">Entregar — risque o que for entregue</td></tr>
        @foreach($printFicha->items as $i)
            <tr><td class="chk">☐</td><td class="q">{{ $i->quantity }}x</td><td>{{ $i->name }}@if($i->observacao)<br><small>obs: {{ $i->observacao }}</small>@endif</td></tr>
        @endforeach
    </table>
    <table class="pa-tot"><tr><td>TOTAL</td><td class="v">{{ $printFicha->total_formatted }}</td></tr></table>
    <div class="pa-info">
        {{ $printFicha->payment_label }} · {{ $printFicha->created_at->format('d/m/Y H:i') }}
    </div>
</div>
@endif

<div class="container-lg">
    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h3 class="mb-0 fw-bold"><span class="material-symbols-outlined" style="font-size:28px;">confirmation_number</span> Fichas — Caixa</h3>
        <a href="{{ route('fichas.index') }}" class="btn btn-outline-secondary btn-sm">
            <span class="material-symbols-outlined">refresh</span> Nova ficha
        </a>
    </div>

    <div class="row g-4">
        {{-- PAD --}}
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header fw-bold">Toque nos produtos para montar a ficha</div>
                <div class="card-body">
                    <div class="pad-filters mb-3" id="pad-filters">
                        <button type="button" class="btn btn-sm btn-primary pad-filter active" data-cat="__all">Todos</button>
                        @foreach($padCategorias as $categoria)
                            <button type="button" class="btn btn-sm btn-outline-primary pad-filter" data-cat="{{ $categoria }}">{{ $categoria }}</button>
                        @endforeach
                    </div>
                    @php
                        $emojiMap = [
                            'PORÇÕES'=>'🍽','COMIDA'=>'🍚','SALGADOS'=>'🥟','CERVEJAS'=>'🍺',
                            'BEBIDAS'=>'🥤','BEBIDAS QUENTES'=>'🥃','BORBONS'=>'🥃','CAIPIRINHAS'=>'🍹','DRINKS'=>'🍸'
                        ];
                    @endphp
                    @if($padItens->isEmpty())
                        <p class="text-muted mb-0">Nenhum item disponível no cardápio.</p>
                    @else
                    <div class="pad-grid" id="pad-grid">
                        @foreach($padItens as $ci)
                        @php $cozinha = in_array($ci->category, $cozinhaCats, true); @endphp
                        <div class="pad-item" data-cat="{{ $ci->category }}"
                             data-id="{{ $ci->id }}"
                             data-name="{{ $ci->name }}"
                             data-price="{{ $ci->price }}"
                             data-cozinha="{{ $cozinha ? '1' : '0' }}">
                            <span class="qty-bolha" data-qtybolha="{{ $ci->id }}">0</span>
                            @if($cozinha)<span class="badge bg-warning text-dark badge-cozinha">cozinha</span>@endif
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
        </div>

        {{-- CARRINHO --}}
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm border-success cart-sticky">
                <div class="card-header fw-bold bg-success-subtle d-flex align-items-center justify-content-between">
                    <span><span class="material-symbols-outlined align-middle">shopping_cart</span> Ficha atual</span>
                    <button type="button" id="cart-clear" class="btn btn-sm btn-outline-secondary d-none">Limpar</button>
                </div>
                <div class="card-body">
                    <div id="cart-empty" class="text-muted text-center py-4">Nenhum item ainda.</div>
                    <div id="cart-list"></div>

                    <div id="cart-cozinha-aviso" class="alert alert-warning small mt-3 d-none mb-0">
                        <span class="material-symbols-outlined align-middle">soup_kitchen</span>
                        Há itens de cozinha — o nome do cliente será pedido no pagamento.
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <span class="fw-bold">Total</span>
                        <span class="fw-bold fs-3" style="color:var(--sh-orange2);" id="cart-total">R$ 0,00</span>
                    </div>

                    <button type="button" id="btn-pagar" class="btn btn-success w-100 mt-3 py-2 fw-bold" disabled>
                        <span class="material-symbols-outlined">point_of_sale</span> Pagar e imprimir ficha
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Fichas recentes --}}
    @if($recentes->isNotEmpty())
    <div class="card shadow-sm mt-4">
        <div class="card-header fw-bold">Fichas recentes</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Código</th><th>Cliente</th><th>Itens</th><th class="text-end">Total</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($recentes as $f)
                    <tr>
                        <td><span class="badge bg-light text-dark border font-monospace">{{ $f->codigo }}</span></td>
                        <td>{{ $f->cliente ?? '—' }}</td>
                        <td>{{ $f->items->sum('quantity') }}</td>
                        <td class="text-end">{{ $f->total_formatted }}</td>
                        <td><span class="badge {{ $f->status_badge['class'] }}">{{ $f->status_badge['label'] }}</span></td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('fichas.show', ['ficha' => $f, 'print' => 1]) }}" class="btn btn-sm btn-outline-primary" title="Imprimir lista de entrega" target="_blank">
                                    <span class="material-symbols-outlined">print</span>
                                </a>
                                <a href="{{ route('fichas.show', $f) }}" class="btn btn-sm btn-outline-secondary" title="Ver / reimprimir">
                                    <span class="material-symbols-outlined">receipt_long</span>
                                </a>
                                <form action="{{ route('fichas.destroy', $f) }}" method="POST" class="requires-auth"
                                      data-confirm="Excluir a ficha {{ $f->codigo }}? Esta ação exige a senha de autorização.">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Excluir ficha">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

{{-- Modal: adicionar item (quantidade + observação + enviar p/ cozinha) --}}
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="add-nome">Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted text-center mb-3" id="add-preco"></p>

                <label class="form-label d-block text-center">Quantidade</label>
                <div class="qty-stepper mb-3">
                    <button type="button" class="btn btn-outline-secondary" id="add-qtd-minus">−</button>
                    <input type="text" id="add-qtd" class="form-control" value="1" inputmode="numeric" autocomplete="off">
                    <button type="button" class="btn btn-outline-secondary" id="add-qtd-plus">+</button>
                </div>

                <div class="mb-3">
                    <label class="form-label">Observação</label>
                    <textarea id="add-obs" class="form-control" rows="2" maxlength="255" placeholder="ex: sem cebola, bem gelada…"></textarea>
                </div>

                <div class="form-check form-switch" id="add-cozinha-box">
                    <input class="form-check-input" type="checkbox" id="add-cozinha">
                    <label class="form-check-label" for="add-cozinha">
                        Entregar junto com a porção <span class="text-muted">(vai pela cozinha)</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="add-confirm">
                    <span class="material-symbols-outlined">add</span> Adicionar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal de pagamento --}}
<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('fichas.store') }}" method="POST" class="modal-content" id="pay-form">
            @csrf
            <div id="pay-itens-inputs"></div>
            <div class="modal-header">
                <h5 class="modal-title">Pagamento da ficha</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-semibold">Total a receber</span>
                    <span class="fw-bold fs-3" style="color:var(--sh-orange2);" id="pay-total">R$ 0,00</span>
                </div>

                <div id="pay-cliente-box" class="mb-3 d-none">
                    <label class="form-label fw-semibold">Nome do cliente <span class="text-warning">(para chamar na cozinha)</span></label>
                    <input type="text" name="cliente" id="pay-cliente" class="form-control" maxlength="150" placeholder="ex: João, mesa do canto…">
                </div>

                <label class="form-label fw-semibold">Forma de pagamento</label>
                <div class="method-grid">
                    @foreach($methods as $key => $label)
                        <button type="button" class="btn btn-outline-primary pay-method" data-method="{{ $key }}">{{ $label }}</button>
                    @endforeach
                </div>
                <input type="hidden" name="payment_method" id="pay-method">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-success" id="pay-confirm" disabled>
                    <span class="material-symbols-outlined">print</span> Confirmar e imprimir
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    @if($printFicha)
    // Ficha recém-paga: move a área de impressão para fora do #app e imprime
    (function () {
        const printArea = document.querySelector('.print-area');
        if (printArea) document.body.appendChild(printArea);
        setTimeout(function () { window.print(); }, 400);
    })();
    @endif

    const moeda = v => 'R$ ' + Number(v).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    const escHtml = s => String(s == null ? '' : s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
    const cart = []; // linhas: { key, id, name, price, preparo, cozinha, marcado, qtd, obs }
    let seq = 0;

    // ── Filtro de categorias ──
    const filters = document.querySelectorAll('.pad-filter');
    const items   = document.querySelectorAll('.pad-item');
    filters.forEach(f => f.addEventListener('click', () => {
        filters.forEach(x => { x.classList.remove('active', 'btn-primary'); x.classList.add('btn-outline-primary'); });
        f.classList.add('active', 'btn-primary'); f.classList.remove('btn-outline-primary');
        const cat = f.dataset.cat;
        items.forEach(it => it.classList.toggle('is-hidden', cat !== '__all' && it.dataset.cat !== cat));
    }));

    // ── Modal de adicionar item (qtd + observação + enviar p/ cozinha) ──
    const addModalEl = document.getElementById('addItemModal');
    const addModal   = bootstrap.Modal.getOrCreateInstance(addModalEl);
    const mNome  = document.getElementById('add-nome');
    const mPreco = document.getElementById('add-preco');
    const mQtd   = document.getElementById('add-qtd');
    const mObs   = document.getElementById('add-obs');
    const mCozinhaBox = document.getElementById('add-cozinha-box');
    const mCozinha    = document.getElementById('add-cozinha');
    let itemAtual = null;
    const clampQtd = v => Math.max(1, Math.min(99, parseInt(v) || 1));

    items.forEach(it => it.addEventListener('click', () => {
        itemAtual = {
            id: it.dataset.id,
            name: it.dataset.name,
            price: parseFloat(it.dataset.price),
            preparo: it.dataset.cozinha === '1',
        };
        mNome.textContent = itemAtual.name;
        mPreco.textContent = moeda(itemAtual.price);
        mQtd.value = 1;
        mObs.value = '';
        mCozinha.checked = false;
        // Porção/comida sempre vai pra cozinha → esconde a opção; demais itens podem ser marcados
        mCozinhaBox.classList.toggle('d-none', itemAtual.preparo);
        addModal.show();
    }));

    // Quando o modal termina de abrir: foca e seleciona a quantidade (digita o valor e dá Enter)
    addModalEl.addEventListener('shown.bs.modal', () => { mQtd.focus(); mQtd.select(); });

    document.getElementById('add-qtd-minus').addEventListener('click', () => mQtd.value = Math.max(1, clampQtd(mQtd.value) - 1));
    document.getElementById('add-qtd-plus').addEventListener('click',  () => mQtd.value = Math.min(99, clampQtd(mQtd.value) + 1));

    function confirmarAdd() {
        if (!itemAtual) return;
        const marcado = !itemAtual.preparo && mCozinha.checked;
        const cozinha = itemAtual.preparo || marcado; // destino cozinha
        const obs = mObs.value.trim();
        const qtd = clampQtd(mQtd.value);

        // Agrupa com uma linha igual (mesmo item, mesma observação e mesmo destino)
        const igual = cart.find(l => l.id === itemAtual.id && l.obs === obs && l.cozinha === cozinha && l.marcado === marcado);
        if (igual) {
            igual.qtd += qtd;
        } else {
            cart.push({
                key: ++seq,
                id: itemAtual.id,
                name: itemAtual.name,
                price: itemAtual.price,
                preparo: itemAtual.preparo,
                cozinha: cozinha,
                marcado: marcado,
                qtd: qtd,
                obs: obs,
            });
        }
        addModal.hide();
        render();
    }
    document.getElementById('add-confirm').addEventListener('click', confirmarAdd);
    // Enter adiciona (Shift+Enter na observação quebra linha)
    addModalEl.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !(e.target === mObs && e.shiftKey)) {
            e.preventDefault();
            confirmarAdd();
        }
    });

    function setQtd(key, qtd) {
        const linha = cart.find(l => l.key === key);
        if (!linha) return;
        linha.qtd = qtd;
        if (linha.qtd <= 0) cart.splice(cart.indexOf(linha), 1);
        render();
    }

    const elList   = document.getElementById('cart-list');
    const elEmpty  = document.getElementById('cart-empty');
    const elTotal  = document.getElementById('cart-total');
    const elPagar  = document.getElementById('btn-pagar');
    const elClear  = document.getElementById('cart-clear');
    const elAviso  = document.getElementById('cart-cozinha-aviso');

    function temCozinha() { return cart.some(l => l.cozinha); }
    function total() { return cart.reduce((s, l) => s + l.price * l.qtd, 0); }

    function badge(l) {
        if (l.preparo) return ' <span class="badge bg-warning text-dark" style="font-size:.6rem;">cozinha</span>';
        if (l.marcado) return ' <span class="badge bg-info text-dark" style="font-size:.6rem;">junto c/ porção</span>';
        return '';
    }

    function render() {
        elList.innerHTML = '';
        const vazio = cart.length === 0;
        elEmpty.classList.toggle('d-none', !vazio);
        elPagar.disabled = vazio;
        elClear.classList.toggle('d-none', vazio);
        elAviso.classList.toggle('d-none', !temCozinha());

        cart.forEach(l => {
            const row = document.createElement('div');
            row.className = 'cart-item';
            row.innerHTML =
                '<div class="ci-nome">' + escHtml(l.name) + badge(l) +
                (l.obs ? '<div class="text-warning small">' + escHtml(l.obs) + '</div>' : '') +
                '<div class="text-muted small">' + moeda(l.price) + '</div></div>' +
                '<div class="ci-step">' +
                    '<button type="button" class="btn btn-outline-secondary" data-dec="' + l.key + '">−</button>' +
                    '<span class="ci-qtd">' + l.qtd + '</span>' +
                    '<button type="button" class="btn btn-outline-secondary" data-inc="' + l.key + '">+</button>' +
                '</div>';
            elList.appendChild(row);
        });

        elList.querySelectorAll('[data-inc]').forEach(b => b.addEventListener('click', () => { const l = cart.find(x => x.key == b.dataset.inc); if (l) setQtd(l.key, l.qtd + 1); }));
        elList.querySelectorAll('[data-dec]').forEach(b => b.addEventListener('click', () => { const l = cart.find(x => x.key == b.dataset.dec); if (l) setQtd(l.key, l.qtd - 1); }));

        elTotal.textContent = moeda(total());

        // bolhas de quantidade no pad (soma por item)
        const somaPorId = {};
        cart.forEach(l => somaPorId[l.id] = (somaPorId[l.id] || 0) + l.qtd);
        document.querySelectorAll('[data-qtybolha]').forEach(b => {
            const q = somaPorId[b.dataset.qtybolha];
            if (q) { b.textContent = q; b.style.display = 'flex'; }
            else { b.style.display = 'none'; }
        });
    }

    elClear.addEventListener('click', () => { cart.length = 0; render(); });

    // ── Modal de pagamento ──
    const payModalEl = document.getElementById('payModal');
    const payModal   = bootstrap.Modal.getOrCreateInstance(payModalEl);
    const payTotal   = document.getElementById('pay-total');
    const payClienteBox = document.getElementById('pay-cliente-box');
    const payCliente = document.getElementById('pay-cliente');
    const payMethod  = document.getElementById('pay-method');
    const payConfirm = document.getElementById('pay-confirm');
    const payInputs  = document.getElementById('pay-itens-inputs');

    elPagar.addEventListener('click', () => {
        if (cart.length === 0) return;
        payTotal.textContent = moeda(total());
        payMethod.value = '';
        payConfirm.disabled = true;
        document.querySelectorAll('.pay-method').forEach(m => m.classList.remove('active', 'btn-primary'));
        const cozinha = temCozinha();
        payClienteBox.classList.toggle('d-none', !cozinha);
        payCliente.required = cozinha;
        payCliente.value = '';

        // monta inputs ocultos com os itens (createElement evita problemas com aspas na observação)
        payInputs.innerHTML = '';
        cart.forEach((l, idx) => {
            const mk = (name, val) => {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'itens[' + idx + '][' + name + ']';
                inp.value = val;
                payInputs.appendChild(inp);
            };
            mk('id', l.id);
            mk('qtd', l.qtd);
            mk('cozinha', l.marcado ? '1' : '0');
            mk('obs', l.obs || '');
        });

        payModal.show();
    });

    document.querySelectorAll('.pay-method').forEach(m => m.addEventListener('click', () => {
        document.querySelectorAll('.pay-method').forEach(x => x.classList.remove('active', 'btn-primary'));
        m.classList.add('active', 'btn-primary');
        payMethod.value = m.dataset.method;
        payConfirm.disabled = false;
    }));

    document.getElementById('pay-form').addEventListener('submit', e => {
        if (!payMethod.value) { e.preventDefault(); return; }
        if (payCliente.required && !payCliente.value.trim()) { e.preventDefault(); payCliente.focus(); }
    });
});
</script>
@endsection
