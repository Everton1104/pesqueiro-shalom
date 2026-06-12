@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
<style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 20; vertical-align: middle; font-size: 18px; line-height: 1; }
    .scan-box { border: 2px dashed var(--sh-orange, #d97706); border-radius: 12px; background: rgba(217,119,6,.07); }
    .scan-box .form-label { color: var(--sh-orange2, #f59e0b) !important; }
    .btn-qr { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:2px;
              line-height:1; padding:.4rem .55rem; }
    .btn-qr .material-symbols-outlined { font-size:26px; }
    .btn-qr small { font-size:.6rem; font-weight:700; letter-spacing:.02em; }
    #qr-reader { width:100%; max-width:340px; margin:0 auto; border-radius:12px; overflow:hidden; }
    #qr-reader video { border-radius:12px; }
</style>

<div class="container-lg">

    <div class="d-flex align-items-center justify-content-between mb-1 mt-2">
        <h4 class="mb-0 fw-bold">Comandas</h4>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#novaComandaModal">
            <span class="material-symbols-outlined">add</span> Nova comanda
        </button>
    </div>
    <div class="text-muted mb-4" style="font-size:.72rem;">
        <kbd>Tab</kbd> nova comanda · <kbd>Esc</kbd> voltar para comandas
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

    {{-- Leitor de QR Code (leitor USB digita o código e dá Enter; ou câmera do celular) --}}
    <form action="{{ route('comandas.scan') }}" method="GET" id="scan-form" class="scan-box p-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <span class="material-symbols-outlined text-primary d-none d-sm-inline" style="font-size:28px;">qr_code_scanner</span>
            <div class="flex-grow-1">
                <label class="form-label mb-1 fw-semibold small text-primary">Ler QR ou buscar por nome</label>
                <input type="text" name="codigo" id="scan-input" class="form-control" value="{{ $q ?? '' }}"
                       placeholder="Aproxime o QR do leitor, ou digite o código / nome do cliente…" autofocus autocomplete="off">
            </div>
            {{-- Botão da câmera: abre o leitor de QR pelo celular --}}
            <button type="button" id="qr-toggle" class="btn btn-outline-primary btn-qr" title="Ler QR com a câmera">
                <span class="material-symbols-outlined">qr_code_2</span>
                <small>Ler QR</small>
            </button>
            <button class="btn btn-primary">Abrir</button>
        </div>

        {{-- Câmera colapsada — só aparece ao tocar em "Ler QR" --}}
        <div id="qr-camera" class="d-none mt-3 text-center">
            <div id="qr-reader"></div>
            <div class="text-muted small mt-2" id="qr-status">Aponte a câmera para o QR da comanda…</div>
            <button type="button" id="qr-close" class="btn btn-sm btn-outline-secondary mt-2">
                <span class="material-symbols-outlined">close</span> Fechar câmera
            </button>
        </div>
    </form>

    {{-- Comandas abertas --}}
    <h6 class="text-uppercase text-muted fw-bold mb-3 d-flex align-items-center gap-2" style="letter-spacing:.08em; font-size:.78rem;">
        Abertas <span class="badge bg-success" id="abertas-count">{{ $abertas->count() }}</span>
        @if(!empty($q))
            <span class="text-muted" style="text-transform:none; letter-spacing:0; font-weight:600;">
                · buscando “{{ $q }}”
                <a href="{{ route('comandas.index') }}" class="ms-1">limpar</a>
            </span>
        @endif
    </h6>

    <p class="text-muted {{ $abertas->isEmpty() ? '' : 'd-none' }}" id="abertas-empty">Nenhuma comanda aberta no momento.</p>
    <p class="text-muted d-none" id="no-filter-results">Nenhuma comanda corresponde à busca.</p>

    <div class="row g-3 mb-5" id="abertas-grid">
        @foreach($abertas as $comanda)
        <div class="col-12 col-md-6 col-lg-4 comanda-card"
             data-cliente="{{ \Illuminate\Support\Str::lower($comanda->cliente) }}"
             data-codigo="{{ \Illuminate\Support\Str::lower($comanda->codigo) }}">
            <a href="{{ route('comandas.show', $comanda) }}" class="text-decoration-none">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0 fw-bold text-white">{{ $comanda->cliente }}</h5>
                            <span class="badge bg-success">Aberta</span>
                        </div>
                        <div class="text-muted small mb-2">
                            <span class="badge bg-light text-dark border font-monospace">{{ $comanda->codigo }}</span>
                            · {{ $comanda->items_count }} {{ $comanda->items_count == 1 ? 'item' : 'itens' }}
                        </div>
                        <div class="d-flex justify-content-between align-items-end">
                            <span class="text-muted small">{{ $comanda->created_at->diffForHumans() }}</span>
                            <span class="fs-5 fw-bold text-white">{{ $comanda->total_formatted }}</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>

    {{-- Últimas fechadas --}}
    @if($fechadas->isNotEmpty())
        <h6 class="text-uppercase text-muted fw-bold mb-3" style="letter-spacing:.08em; font-size:.78rem;">Últimas fechadas</h6>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Cliente</th>
                        <th>Código</th>
                        <th>Pagamento</th>
                        <th class="text-end">Total</th>
                        <th>Fechada</th>
                        <th style="width:48px"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($fechadas as $comanda)
                    <tr>
                        <td class="fw-semibold">{{ $comanda->cliente }}</td>
                        <td><span class="badge bg-light text-dark border font-monospace">{{ $comanda->codigo }}</span></td>
                        <td>{{ \App\Models\Comanda::paymentLabel($comanda->payment_method) }}</td>
                        <td class="text-end fw-semibold">{{ $comanda->total_formatted }}</td>
                        <td class="text-muted small">{{ optional($comanda->closed_at)->format('d/m H:i') }}</td>
                        <td class="text-end">
                            <form action="{{ route('comandas.destroy', $comanda) }}" method="POST" class="requires-auth d-inline"
                                  data-confirm="Excluir a comanda de {{ addslashes($comanda->cliente) }} do histórico? Não pode ser desfeito.">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" title="Excluir do histórico">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>

{{-- Modal: nova comanda --}}
<div class="modal fade" id="novaComandaModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('comandas.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Nova comanda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nome do cliente *</label>
                    <input type="text" name="cliente" id="cliente-input" class="form-control" required maxlength="150" autocomplete="off">
                </div>
                <div class="mb-1">
                    <label class="form-label">Observação</label>
                    <input type="text" name="observacao" class="form-control" maxlength="255">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary">Abrir comanda</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    // Mantém o foco no campo de leitura para o leitor de QR funcionar como teclado
    const scan = document.getElementById('scan-input');
    const modalEl = document.getElementById('novaComandaModal');
    const clienteInput = document.getElementById('cliente-input');
    let modalOpen = false;
    modalEl.addEventListener('shown.bs.modal', () => { modalOpen = true; clienteInput && clienteInput.focus(); });
    modalEl.addEventListener('hidden.bs.modal', () => { modalOpen = false; scan && scan.focus(); });

    // Atalho: TAB abre o modal de nova comanda e foca o nome
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Tab' && !modalOpen) {
            e.preventDefault();
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    });

    document.addEventListener('click', (e) => {
        if (modalOpen) return;
        const cam = document.getElementById('qr-camera');
        if (cam && !cam.classList.contains('d-none')) return; // câmera aberta: não rouba o foco
        if (e.target.closest('a, button, input, select, textarea')) return;
        scan && scan.focus();
    });

    // ── Filtro instantâneo das comandas abertas por nome/código ──
    const grid    = document.getElementById('abertas-grid');
    const countEl = document.getElementById('abertas-count');
    const emptyEl = document.getElementById('abertas-empty');
    const noRes   = document.getElementById('no-filter-results');

    function applyFilter() {
        const term = (scan ? scan.value : '').trim().toLowerCase();
        const cards = Array.from(grid.querySelectorAll('.comanda-card'));
        let visiveis = 0;
        cards.forEach(card => {
            const hit = !term || card.dataset.cliente.includes(term) || card.dataset.codigo.includes(term);
            card.classList.toggle('d-none', !hit);
            if (hit) visiveis++;
        });
        emptyEl.classList.toggle('d-none', cards.length !== 0);
        noRes.classList.toggle('d-none', !(cards.length > 0 && visiveis === 0));
    }
    if (scan) scan.addEventListener('input', applyFilter);
    applyFilter(); // aplica filtro inicial (caso venha com ?q=)

    // ── Atualização automática (polling) das comandas abertas ──
    const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
    function cardHtml(c) {
        const itens = c.itens + ' ' + (c.itens === 1 ? 'item' : 'itens');
        return '<div class="col-12 col-md-6 col-lg-4 comanda-card" data-cliente="' + esc(c.cliente.toLowerCase()) + '" data-codigo="' + esc(c.codigo.toLowerCase()) + '">'
            + '<a href="' + c.url + '" class="text-decoration-none"><div class="card h-100 shadow-sm"><div class="card-body">'
            + '<div class="d-flex justify-content-between align-items-start mb-2"><h5 class="card-title mb-0 fw-bold text-white">' + esc(c.cliente) + '</h5><span class="badge bg-success">Aberta</span></div>'
            + '<div class="text-muted small mb-2"><span class="badge bg-light text-dark border font-monospace">' + esc(c.codigo) + '</span> · ' + itens + '</div>'
            + '<div class="d-flex justify-content-between align-items-end"><span class="text-muted small">' + esc(c.criada) + '</span><span class="fs-5 fw-bold text-white">' + esc(c.total) + '</span></div>'
            + '</div></div></a></div>';
    }
    const sigOf = list => list.map(c => c.codigo + ':' + c.itens + ':' + c.total).join('|');
    let lastSig = null;

    async function poll() {
        if (modalOpen) return; // não atualiza enquanto cria comanda
        try {
            const r = await fetch(@json(route('comandas.abertas')), { headers: { 'Accept': 'application/json' } });
            if (!r.ok) return;
            const data = await r.json();
            const sig = sigOf(data.comandas);
            if (sig === lastSig) return; // nada mudou
            lastSig = sig;
            grid.innerHTML = data.comandas.map(cardHtml).join('');
            countEl.textContent = data.count;
            applyFilter(); // reaplica a busca atual
        } catch (e) { /* silencioso */ }
    }
    setInterval(poll, 8000);

    // ── Leitor de QR pela câmera do celular ──
    const qrToggle = document.getElementById('qr-toggle');
    const qrCamera = document.getElementById('qr-camera');
    const qrClose  = document.getElementById('qr-close');
    const qrStatus = document.getElementById('qr-status');
    const scanForm = document.getElementById('scan-form');
    let html5Qr = null;
    let lendo = false;

    async function pararCamera() {
        if (html5Qr && lendo) {
            try { await html5Qr.stop(); } catch (e) { /* já parada */ }
        }
        lendo = false;
        qrCamera.classList.add('d-none');
    }

    async function abrirCamera() {
        if (typeof Html5Qrcode === 'undefined') {
            alert('Não foi possível carregar o leitor de QR. Verifique sua conexão.');
            return;
        }
        qrCamera.classList.remove('d-none');
        qrStatus.textContent = 'Aponte a câmera para o QR da comanda…';
        if (!html5Qr) html5Qr = new Html5Qrcode('qr-reader');

        try {
            lendo = true;
            await html5Qr.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 220, height: 220 } },
                async (texto) => {
                    if (!lendo) return;
                    lendo = false;                 // evita leituras múltiplas
                    qrStatus.textContent = 'Comanda lida! Abrindo…';
                    try { await html5Qr.stop(); } catch (e) {}
                    scan.value = (texto || '').trim();
                    scanForm.submit();             // route comandas.scan resolve o código
                },
                () => { /* frame sem QR — silencioso */ }
            );
        } catch (e) {
            lendo = false;
            qrStatus.textContent = 'Não foi possível acessar a câmera. Permita o acesso e tente de novo.';
        }
    }

    if (qrToggle) {
        qrToggle.addEventListener('click', () => {
            if (qrCamera.classList.contains('d-none')) abrirCamera();
            else pararCamera();
        });
    }
    if (qrClose) qrClose.addEventListener('click', pararCamera);
})();
</script>
@endsection
