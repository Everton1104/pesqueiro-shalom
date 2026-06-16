@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
<style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 20; vertical-align: middle; font-size: 18px; line-height: 1; }
    #qr-reader { width:100%; max-width:340px; margin:0 auto; border-radius:12px; overflow:hidden; }
    #qr-reader video { border-radius:12px; }
    .item-grande { font-size: 1.15rem; padding: .6rem .25rem; border-bottom: 1px solid var(--sh-border, #2e2e2e); }
    .item-grande .q { font-weight: 800; color: var(--sh-orange2, #f59e0b); margin-right: .5rem; }
</style>

<div class="container-lg" style="max-width:620px;">
    <h3 class="mb-1 fw-bold"><span class="material-symbols-outlined" style="font-size:28px;">storefront</span> Balcão</h3>
    <p class="text-muted">Leia o QR da ficha para ver o que entregar.</p>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex gap-2 align-items-end">
                <div class="flex-grow-1">
                    <label class="form-label mb-1 fw-semibold small">Código da ficha</label>
                    <input type="text" id="codigo-input" class="form-control" placeholder="Leia o QR ou digite o código…" autocomplete="off">
                </div>
                <button type="button" id="btn-buscar" class="btn btn-primary">Buscar</button>
                <button type="button" id="qr-toggle" class="btn btn-outline-primary" title="Ler QR com a câmera">
                    <span class="material-symbols-outlined">qr_code_scanner</span>
                </button>
            </div>
            {{-- Alternar modo: scan por câmera × digitação (salvo na sessão) --}}
            <div class="form-check form-switch mt-2 mb-0">
                <input class="form-check-input" type="checkbox" role="switch" id="scan-mode-toggle">
                <label class="form-check-label small" for="scan-mode-toggle" id="scan-mode-label">Modo scan (câmera)</label>
            </div>
            <div id="qr-camera" class="d-none mt-3 text-center">
                <div id="qr-reader"></div>
                <div class="text-muted small mt-2" id="qr-status">Aponte a câmera para o QR da ficha…</div>
                <button type="button" id="qr-close" class="btn btn-sm btn-outline-secondary mt-2">
                    <span class="material-symbols-outlined">close</span> Fechar câmera
                </button>
            </div>
        </div>
    </div>

    {{-- Resultado --}}
    <div id="resultado"></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const input = document.getElementById('codigo-input');
    const resultado = document.getElementById('resultado');
    const buscarUrl = @json(route('balcao.buscar'));

    // Modo de leitura: scan (câmera) × digitação (autofocus). Default + persistência na sessão.
    const savedScanMode = @json(session('scan_mode'));
    let scanMode = (savedScanMode === null || savedScanMode === undefined) ? true : !!savedScanMode;
    // Foca o campo só no modo digitação (ativa o autofocus/teclado)
    const focarInput = () => { if (!scanMode && input) input.focus(); };

    // ── Busca da ficha ──
    async function buscar(codigo) {
        codigo = (codigo || '').trim();
        if (!codigo) return;
        resultado.innerHTML = '<div class="text-muted text-center py-3">Buscando…</div>';
        try {
            const r = await fetch(buscarUrl + '?codigo=' + encodeURIComponent(codigo), { headers: { 'Accept': 'application/json' } });
            const data = await r.json();
            render(data);
        } catch (e) {
            resultado.innerHTML = '<div class="alert alert-danger">Erro de conexão. Tente de novo.</div>';
        }
    }

    const esc = s => String(s == null ? '' : s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

    const obsHtml = o => o ? '<div class="text-warning" style="font-size:.9rem;">⚠ ' + esc(o) + '</div>' : '';

    // Lista somente-leitura (itens já entregues / conferência)
    function itensHtml(itens) {
        return itens.map(i => '<div class="item-grande"><span class="q">' + i.quantity + 'x</span>' + esc(i.name) +
            (i.entregue ? ' <span class="badge bg-secondary ms-1">entregue</span>' : '') +
            obsHtml(i.obs) + '</div>').join('');
    }

    // Linha com checkbox para escolher o que entregar agora (retirada parcial)
    function itensCheckHtml(itens) {
        return itens.map(i =>
            '<label class="item-grande d-flex align-items-center gap-2" style="cursor:pointer;">' +
              '<input type="checkbox" class="form-check-input m-0 flex-shrink-0 chk-item" value="' + i.id + '" checked>' +
              '<span><span class="q">' + i.quantity + 'x</span>' + esc(i.name) + obsHtml(i.obs) + '</span>' +
            '</label>').join('');
    }

    function render(data) {
        if (!data.ok) {
            resultado.innerHTML = '<div class="alert alert-danger"><span class="material-symbols-outlined">error</span> ' + esc(data.msg || 'Ficha inválida.') + '</div>';
            return;
        }

        const f = data.ficha;
        const cliente = f.cliente ? ('<div class="text-muted">Cliente: <strong>' + esc(f.cliente) + '</strong></div>') : '';

        if (data.ja_entregue) {
            // Ficha já retirada — mostra itens colapsados pra conferência
            resultado.innerHTML =
                '<div class="card shadow-sm border-danger">' +
                  '<div class="card-header bg-danger-subtle fw-bold text-danger">' +
                    '<span class="material-symbols-outlined">block</span> Ficha ' + f.codigo + ' — não é mais válida' +
                  '</div>' +
                  '<div class="card-body">' +
                    '<p class="mb-2">Esta ficha <strong>já foi retirada no balcão</strong>.</p>' + cliente +
                    '<button class="btn btn-sm btn-outline-secondary mt-2" type="button" data-bs-toggle="collapse" data-bs-target="#itensEntregues">' +
                      '<span class="material-symbols-outlined">visibility</span> Ver itens entregues</button>' +
                    '<div class="collapse mt-2" id="itensEntregues">' + itensHtml(data.itens) + '</div>' +
                  '</div>' +
                '</div>';
            return;
        }

        // Ficha válida — separa o que ainda está pendente do que já foi retirado antes
        const pendentes = data.itens.filter(i => !i.entregue);
        const entregues = data.itens.filter(i => i.entregue);

        const jaRetirados = entregues.length
            ? '<button class="btn btn-sm btn-outline-secondary mt-3" type="button" data-bs-toggle="collapse" data-bs-target="#jaRetirados">' +
                '<span class="material-symbols-outlined">history</span> Já retirados (' + entregues.length + ')</button>' +
              '<div class="collapse mt-2" id="jaRetirados">' + itensHtml(entregues) + '</div>'
            : '';

        resultado.innerHTML =
            '<div class="card shadow-sm border-success">' +
              '<div class="card-header bg-success-subtle fw-bold">' +
                '<span class="material-symbols-outlined">inventory_2</span> Entregar — Ficha ' + f.codigo +
              '</div>' +
              '<div class="card-body">' + cliente +
                '<p class="text-muted small mb-1 mt-2">Desmarque o que o cliente <strong>não</strong> vai levar agora — fica pendente pra próxima leitura.</p>' +
                '<div class="my-1">' + itensCheckHtml(pendentes) + '</div>' +
                '<button type="button" id="btn-entregar" class="btn btn-success w-100 py-2 fw-bold mt-2">' +
                  '<span class="material-symbols-outlined">check_circle</span> Confirmar retirada</button>' +
                jaRetirados +
              '</div>' +
            '</div>';

        document.getElementById('btn-entregar').addEventListener('click', async function () {
            const ids = [...document.querySelectorAll('.chk-item:checked')].map(c => c.value);
            if (ids.length === 0) {
                alert('Marque pelo menos um item para entregar.');
                return;
            }
            this.disabled = true;
            try {
                const params = new URLSearchParams();
                ids.forEach(id => params.append('itens[]', id));
                const r = await fetch(data.entregar_url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params,
                });
                const res = await r.json();
                if (res.ok && res.parcial) {
                    // Sobrou item pendente: recarrega a ficha mostrando o que ainda falta
                    buscar(f.codigo);
                } else if (res.ok) {
                    resultado.innerHTML = '<div class="alert alert-success text-center fs-5"><span class="material-symbols-outlined">task_alt</span> Entrega concluída — Ficha ' + f.codigo + '!</div>';
                    input.value = ''; focarInput();
                } else {
                    this.disabled = false;
                    resultado.innerHTML = '<div class="alert alert-danger">' + (res.msg || 'Não foi possível confirmar.') + '</div>';
                }
            } catch (e) {
                this.disabled = false;
                resultado.innerHTML = '<div class="alert alert-danger">Erro de conexão.</div>';
            }
        });
    }

    document.getElementById('btn-buscar').addEventListener('click', () => buscar(input.value));
    input.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); buscar(input.value); } });

    // ── Leitor de QR pela câmera (fica sempre aberta) ──
    const qrToggle = document.getElementById('qr-toggle');
    const qrCamera = document.getElementById('qr-camera');
    const qrClose  = document.getElementById('qr-close');
    const qrStatus = document.getElementById('qr-status');
    let html5Qr = null, cameraOn = false, processando = false;

    async function pararCamera() {
        if (html5Qr && cameraOn) { try { await html5Qr.stop(); } catch (e) {} }
        cameraOn = false;
        qrCamera.classList.add('d-none');
    }

    async function abrirCamera() {
        if (cameraOn) return;
        if (typeof Html5Qrcode === 'undefined') { qrStatus.textContent = 'Não foi possível carregar o leitor de QR.'; return; }
        qrCamera.classList.remove('d-none');
        if (input) input.blur(); // fecha o teclado pra não cobrir a câmera
        qrStatus.textContent = 'Aponte a câmera para o QR da ficha…';
        if (!html5Qr) html5Qr = new Html5Qrcode('qr-reader');
        try {
            await html5Qr.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 220, height: 220 } },
                (texto) => {
                    if (processando) return;            // evita reler o mesmo QR
                    processando = true;
                    qrStatus.textContent = 'Ficha lida! Buscando…';
                    input.value = (texto || '').trim();
                    buscar(input.value);
                    // re-arma a leitura após alguns segundos, mantendo a câmera aberta
                    setTimeout(() => {
                        processando = false;
                        qrStatus.textContent = 'Aponte a câmera para a próxima ficha…';
                    }, 3500);
                }, () => {});
            cameraOn = true;
        } catch (e) {
            cameraOn = false;
            qrStatus.textContent = 'Não foi possível acessar a câmera. Toque no botão da câmera e permita o acesso.';
        }
    }

    qrToggle.addEventListener('click', () => {
        if (qrCamera.classList.contains('d-none')) abrirCamera(); else pararCamera();
    });
    qrClose.addEventListener('click', pararCamera);

    // ── Checkbox: modo scan (câmera) × digitação (teclado), salvo na sessão ──
    const modeToggle = document.getElementById('scan-mode-toggle');
    const modeLabel  = document.getElementById('scan-mode-label');

    function aplicarModo() {
        if (modeToggle) modeToggle.checked = scanMode;
        if (modeLabel)  modeLabel.textContent = scanMode ? 'Modo scan (câmera)' : 'Modo digitação (teclado)';
        if (scanMode) {
            if (qrCamera.classList.contains('d-none')) abrirCamera();
        } else {
            pararCamera();
            focarInput();
        }
    }

    if (modeToggle) {
        modeToggle.addEventListener('change', () => {
            scanMode = modeToggle.checked;
            aplicarModo();
            const body = new URLSearchParams(); body.append('scan', scanMode ? 1 : 0);
            fetch(@json(route('scan-mode')), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                body,
            }).catch(() => {});
        });
    }

    aplicarModo(); // estado inicial conforme a sessão
});
</script>
@endsection
