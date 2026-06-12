@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
<style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 20; vertical-align: middle; font-size: 18px; line-height: 1; }
    .ped-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem; }
    .ped-card { border: 2px solid var(--sh-orange, #d97706); border-radius: 14px; background: var(--sh-bg2, #1a1a1a); overflow: hidden; }
    .ped-card .ped-head { padding: .75rem 1rem; background: rgba(217,119,6,.15); display: flex; justify-content: space-between; align-items: center; }
    .ped-card .ped-cliente { font-size: 1.25rem; font-weight: 800; line-height: 1.1; }
    .ped-card .ped-codigo { font-family: monospace; font-size: .8rem; opacity: .8; }
    .ped-card .ped-hora { font-size: .75rem; opacity: .7; }
    .ped-card .ped-itens { padding: .5rem 1rem; }
    .ped-card .ped-itens .li { font-size: 1.15rem; font-weight: 700; padding: .25rem 0; }
    .ped-card .ped-itens .li .q { font-weight: 800; color: var(--sh-orange2, #f59e0b); margin-right: .4rem; }
    .ped-card .ped-itens .obs-li { font-size: .9rem; font-weight: 600; color: #ffca6b; margin: .05rem 0 .15rem 1.5rem; }
    .ped-card .acompanha { margin-top: .5rem; padding-top: .45rem; border-top: 1px dashed var(--sh-border, #2e2e2e); font-size: .95rem; color: #9ad0ff; }
    .ped-card .acompanha .acompanha-tit { font-weight: 800; text-transform: uppercase; font-size: .68rem; letter-spacing: .04em; margin-right: .25rem; }
    .ped-card .ped-foot { padding: .5rem 1rem .85rem; }
    .ped-novo { animation: pulseNovo 1s ease-in-out 2; }
    @keyframes pulseNovo { 0%,100% { box-shadow: 0 0 0 0 rgba(217,119,6,0);} 50% { box-shadow: 0 0 0 .5rem rgba(217,119,6,.45);} }

    .done-card { border: 1px solid var(--sh-border, #2e2e2e); border-radius: 12px; background: var(--sh-bg2, #1a1a1a); margin-bottom: .5rem; }
    .done-head { padding: .6rem .9rem; display: flex; justify-content: space-between; align-items: center; cursor: pointer; }
    .done-head .nome { font-weight: 700; }

    /* ── CUPOM DE ENTREGA (impressora da cozinha — 80mm) ── */
    .print-area { display: none; }
    @media print {
        @page { size: 80mm auto; margin: 3mm; }
        html, body { background: #fff !important; margin: 0; padding: 0; }
        body > #app { display: none !important; }
        .print-area { display: block; width: 100%; color: #000; font-family: 'Nunito', Arial, sans-serif; }
        .pa-brand { text-align: center; font-weight: 800; font-size: 11pt; letter-spacing: .06em; }
        .pa-tipo { text-align: center; font-size: 9pt; text-transform: uppercase; letter-spacing: .14em; margin: 1mm 0 2mm; font-weight: 700; }
        .pa-cliente { text-align: center; font-size: 20pt; font-weight: 800; line-height: 1.1; margin: 2mm 0 1mm; }
        .pa-code { text-align: center; font-family: 'Courier New', monospace; font-size: 20pt; font-weight: 700; letter-spacing: .18em; }
        .pa-code-label { text-align: center; font-size: 7pt; text-transform: uppercase; letter-spacing: .12em; }
        .pa-items { width: 100%; border-collapse: collapse; font-size: 11pt; border-top: 1px dashed #000; margin-top: 2.5mm; }
        .pa-items td { padding: .9mm 0; vertical-align: top; }
        .pa-items .q { white-space: nowrap; padding-right: 2mm; font-weight: 800; }
        .pa-items .sec td { font-weight: 800; font-size: 8.5pt; text-transform: uppercase; padding-top: 1.8mm; letter-spacing: .04em; }
        .pa-info { text-align: center; font-size: 8pt; margin-top: 2.5mm; padding-top: 2mm; border-top: 1px dashed #000; }
    }
</style>

<div class="container-lg">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h3 class="mb-0 fw-bold"><span class="material-symbols-outlined" style="font-size:28px;">soup_kitchen</span> Cozinha</h3>
        <div class="d-flex gap-2">
            <button id="btn-som" class="btn btn-outline-warning btn-sm">
                <span class="material-symbols-outlined">notifications_active</span> Ativar som
            </button>
            <span class="badge bg-secondary align-self-center" id="conn-status">atualizando…</span>
        </div>
    </div>

    {{-- Fila de pedidos --}}
    <h5 class="fw-bold mb-2"><span class="material-symbols-outlined align-middle">pending_actions</span> Pedidos na fila <span class="badge bg-warning text-dark" id="count-pendentes">0</span></h5>
    <div id="fila-vazia" class="text-muted text-center py-4 d-none">Nenhum pedido na fila. 🎣</div>
    <div class="ped-grid" id="fila"></div>

    {{-- Concluídos --}}
    <h5 class="fw-bold mt-4 mb-2"><span class="material-symbols-outlined align-middle">check_circle</span> Concluídos</h5>
    <div id="concluidos-vazio" class="text-muted py-2 d-none">Nenhum pedido concluído ainda.</div>
    <div id="concluidos"></div>
</div>

{{-- Cupom de entrega (impresso ao concluir) --}}
<div class="print-area" id="cozinha-print"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const filaUrl = @json(route('cozinha.fila'));
    let dados = @json(['pendentes' => $pendentes, 'concluidos' => $concluidos]);
    let conhecidos = new Set(dados.pendentes.map(p => p.id));
    // Som já vem ATIVADO por padrão; o navegador só libera o áudio após uma interação,
    // então liberamos automaticamente no 1º toque/tecla no tablet (sem precisar do botão).
    let somAtivo = true;
    let audioCtx = null;
    const btnSom = document.getElementById('btn-som');

    function marcarAtivo() {
        btnSom.classList.remove('btn-outline-warning', 'btn-outline-danger');
        btnSom.classList.add('btn-warning');
        btnSom.innerHTML = '<span class="material-symbols-outlined">notifications_active</span> Som ativado';
    }
    function marcarBloqueado() {
        btnSom.classList.remove('btn-warning');
        btnSom.classList.add('btn-outline-danger');
        btnSom.innerHTML = '<span class="material-symbols-outlined">notifications_off</span> Toque para ativar o som';
    }

    function garantirAudio() {
        if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        if (audioCtx.state === 'suspended') audioCtx.resume();
        return audioCtx;
    }

    // ── Som (beep via WebAudio) ──
    function beep() {
        if (!somAtivo) return;
        garantirAudio();
        if (!audioCtx || audioCtx.state !== 'running') return;
        try {
            const pulses = 8;     // 8 pulsos
            const dur = 0.30;     // duração de cada pulso
            const gap = 0.08;     // intervalo entre pulsos → alerta de ~3s
            for (let n = 0; n < pulses; n++) {
                const o = audioCtx.createOscillator(), g = audioCtx.createGain();
                o.connect(g); g.connect(audioCtx.destination);
                o.type = 'sine'; o.frequency.value = 2000;
                const t = audioCtx.currentTime + n * (dur + gap);
                g.gain.setValueAtTime(0.0001, t);
                g.gain.exponentialRampToValueAtTime(0.4, t + 0.02);
                g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
                o.start(t); o.stop(t + dur + 0.02);
            }
        } catch (e) {}
    }

    // Libera o áudio na primeira interação e atualiza o botão
    function liberarAudio() {
        garantirAudio();
        if (audioCtx && audioCtx.state === 'running') {
            marcarAtivo();
            ['pointerdown', 'touchstart', 'keydown'].forEach(ev => document.removeEventListener(ev, liberarAudio));
        }
    }
    ['pointerdown', 'touchstart', 'keydown'].forEach(ev => document.addEventListener(ev, liberarAudio));

    btnSom.addEventListener('click', function () {
        somAtivo = true;
        garantirAudio();
        marcarAtivo();
        beep();
    });

    // Estado inicial: tenta ligar já; se o navegador exigir toque, mostra o aviso
    garantirAudio();
    if (audioCtx && audioCtx.state === 'running') marcarAtivo();
    else marcarBloqueado();

    const esc = s => String(s == null ? '' : s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

    // Cupom de entrega impresso ao concluir (nome + código + itens) — vai pra impressora da cozinha
    const printArea = document.getElementById('cozinha-print');
    if (printArea) document.body.appendChild(printArea); // fora do #app (oculto na impressão)

    function imprimirEntrega(p) {
        if (!printArea) return;
        const prep  = p.itens.filter(i => i.preparo);
        const acomp = p.itens.filter(i => !i.preparo);
        const linha = i => '<tr><td class="q">' + i.quantity + 'x</td><td>' + esc(i.name) +
            (i.obs ? '<br><small>obs: ' + esc(i.obs) + '</small>' : '') + '</td></tr>';
        let corpo = '';
        if (prep.length)  corpo += '<tr class="sec"><td colspan="2">Porção</td></tr>' + prep.map(linha).join('');
        if (acomp.length) corpo += '<tr class="sec"><td colspan="2">Acompanha</td></tr>' + acomp.map(linha).join('');
        printArea.innerHTML =
            '<div class="pa-brand">PESQUEIRO SHALOM</div>' +
            '<div class="pa-tipo">Pedido pronto — Cozinha</div>' +
            '<div class="pa-cliente">' + esc(p.cliente) + '</div>' +
            '<div class="pa-code">' + esc(p.codigo) + '</div>' +
            '<div class="pa-code-label">código da ficha</div>' +
            '<table class="pa-items">' + corpo + '</table>' +
            '<div class="pa-info">Entregar ao cliente · ' + esc(p.hora) + '</div>';
        setTimeout(function () { window.print(); }, 150);
    }

    // Itens que a cozinha prepara (em destaque)
    function preparoHtml(itens) {
        return itens.filter(i => i.preparo).map(i =>
            '<div class="li"><span class="q">' + i.quantity + 'x</span>' + esc(i.name) +
            (i.obs ? '<div class="obs-li">⚠ ' + esc(i.obs) + '</div>' : '') + '</div>').join('');
    }
    // Itens que acompanham (bebidas etc. que saem junto — não são preparados)
    function acompanhaHtml(itens) {
        const ac = itens.filter(i => !i.preparo);
        if (!ac.length) return '';
        return '<div class="acompanha"><span class="acompanha-tit">Acompanha:</span> ' +
            ac.map(i => i.quantity + 'x ' + esc(i.name) + (i.obs ? ' (' + esc(i.obs) + ')' : '')).join(', ') + '</div>';
    }
    // Lista compacta (concluídos) — inclui obs e marca acompanhamentos
    function itensSmallHtml(itens) {
        return itens.map(i => '<div class="small">' + i.quantity + 'x ' + esc(i.name) +
            (i.preparo ? '' : ' <span class="text-info">(acompanha)</span>') +
            (i.obs ? ' — ' + esc(i.obs) : '') + '</div>').join('');
    }

    function render() {
        // Fila
        const fila = document.getElementById('fila');
        fila.innerHTML = dados.pendentes.map(p =>
            '<div class="ped-card' + (p._novo ? ' ped-novo' : '') + '">' +
              '<div class="ped-head"><div><div class="ped-cliente">' + esc(p.cliente) + '</div>' +
                '<span class="ped-codigo">' + esc(p.codigo) + '</span></div>' +
                '<div class="text-end ped-hora">' + esc(p.hora) + '<br>há ' + esc(p.desde) + '</div></div>' +
              '<div class="ped-itens">' + preparoHtml(p.itens) + acompanhaHtml(p.itens) + '</div>' +
              '<div class="ped-foot"><button class="btn btn-success w-100 fw-bold" data-concluir="' + esc(p.concluir_url) + '">' +
                '<span class="material-symbols-outlined">done_all</span> Concluído</button></div>' +
            '</div>'
        ).join('');
        document.getElementById('fila-vazia').classList.toggle('d-none', dados.pendentes.length > 0);
        document.getElementById('count-pendentes').textContent = dados.pendentes.length;

        fila.querySelectorAll('[data-concluir]').forEach(b => b.addEventListener('click', async function () {
            this.disabled = true;
            const pedido = dados.pendentes.find(p => p.concluir_url === this.dataset.concluir);
            try {
                const r = await fetch(this.dataset.concluir, { method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
                if (r.ok) {
                    if (pedido) imprimirEntrega(pedido); // imprime o cupom de entrega
                    atualizar();
                }
            } catch (e) { this.disabled = false; }
        }));

        // Concluídos (colapsados por cliente)
        const conc = document.getElementById('concluidos');
        conc.innerHTML = dados.concluidos.map((p, idx) =>
            '<div class="done-card">' +
              '<div class="done-head" data-bs-toggle="collapse" data-bs-target="#done' + p.id + '">' +
                '<span class="nome">' + esc(p.cliente) + ' <span class="text-muted small font-monospace">' + esc(p.codigo) + '</span></span>' +
                '<span class="badge bg-secondary">' + esc(p.hora) + '</span></div>' +
              '<div class="collapse" id="done' + p.id + '"><div class="px-3 pb-2">' + itensSmallHtml(p.itens) + '</div></div>' +
            '</div>'
        ).join('');
        document.getElementById('concluidos-vazio').classList.toggle('d-none', dados.concluidos.length > 0);
    }

    async function atualizar() {
        try {
            const r = await fetch(filaUrl, { headers: { 'Accept': 'application/json' } });
            const novo = await r.json();
            // detecta pedidos novos na fila
            let temNovo = false;
            novo.pendentes.forEach(p => { if (!conhecidos.has(p.id)) { p._novo = true; temNovo = true; } });
            dados = novo;
            conhecidos = new Set(novo.pendentes.map(p => p.id));
            render();
            if (temNovo) beep();
            document.getElementById('conn-status').textContent = 'atualizado';
            document.getElementById('conn-status').className = 'badge bg-success align-self-center';
        } catch (e) {
            document.getElementById('conn-status').textContent = 'sem conexão';
            document.getElementById('conn-status').className = 'badge bg-danger align-self-center';
        }
    }

    render();
    setInterval(atualizar, 5000);
});
</script>
@endsection
