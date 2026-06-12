@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
<style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 20; vertical-align: middle; font-size: 18px; line-height: 1; }
    #qrcode img { border: 1px solid #dee2e6; border-radius: 8px; padding: 6px; background: #fff; }

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
        .pa-items td { padding: .7mm 0; vertical-align: top; }
        .pa-items .q { white-space: nowrap; padding-right: 2mm; }
        .pa-items .v { text-align: right; white-space: nowrap; }
        .pa-items .sec td { font-weight: 800; font-size: 8pt; text-transform: uppercase; padding-top: 1.5mm; }
        .pa-tot { width: 100%; border-top: 1px dashed #000; margin-top: 1.5mm; padding-top: 1mm; font-weight: 800; font-size: 12pt; }
        .pa-tot td { padding: .4mm 0; }
        .pa-tot .v { text-align: right; }
        .pa-info { text-align: center; font-size: 8pt; margin-top: 2.5mm; padding-top: 2mm; border-top: 1px dashed #000; }
    }
</style>

<div class="container-lg">
    @php $itensBalcao = $ficha->balcaoItems(); $itensCozinha = $ficha->cozinhaItems(); @endphp

    {{-- Cupom impresso (visível só na impressão — 80mm) --}}
    <div class="print-area">
        <div class="pa-brand">PESQUEIRO SHALOM</div>
        <div class="pa-tipo">Ficha — pré-paga</div>
        <div class="pa-qrbox"><div id="qrcode-print"></div></div>
        <div class="pa-code">{{ $ficha->codigo }}</div>
        <div class="pa-code-label">código da ficha</div>
        @if($ficha->cliente)<div class="pa-cliente">{{ $ficha->cliente }}</div>@endif
        <table class="pa-items">
            @if($itensBalcao->isNotEmpty())
                <tr class="sec"><td colspan="2">Retirar no balcão</td></tr>
                @foreach($itensBalcao as $i)
                    <tr><td class="q">{{ $i->quantity }}x</td><td>{{ $i->name }}</td></tr>
                @endforeach
            @endif
            @if($itensCozinha->isNotEmpty())
                <tr class="sec"><td colspan="2">Cozinha (chamaremos pelo nome)</td></tr>
                @foreach($itensCozinha as $i)
                    <tr><td class="q">{{ $i->quantity }}x</td><td>{{ $i->name }}</td></tr>
                @endforeach
            @endif
        </table>
        <table class="pa-tot"><tr><td>TOTAL</td><td class="v">{{ $ficha->total_formatted }}</td></tr></table>
        <div class="pa-info">
            {{ $ficha->payment_label }} · {{ $ficha->created_at->format('d/m/Y H:i') }}<br>
            Apresente o QR no balcão para retirar.
        </div>
    </div>

    {{-- Tela --}}
    <div class="d-flex align-items-center gap-2 mb-3 no-print">
        <a href="{{ route('fichas.index') }}" class="btn btn-outline-secondary btn-sm">
            <span class="material-symbols-outlined">arrow_back</span> Voltar ao caixa
        </a>
    </div>

    @if(session('status'))<div class="alert alert-success alert-dismissible fade show no-print">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h4 class="mb-0 fw-bold">{{ $ficha->cliente ?? 'Ficha' }}</h4>
                    <span class="badge {{ $ficha->status_badge['class'] }}">{{ $ficha->status_badge['label'] }}</span>
                </div>
                <div class="text-muted small">
                    Código <span class="badge bg-light text-dark border font-monospace">{{ $ficha->codigo }}</span>
                    · {{ $ficha->payment_label }} · {{ $ficha->total_formatted }}
                    · {{ $ficha->created_at->diffForHumans() }}
                    @if($ficha->user) · por {{ $ficha->user->name }} @endif
                </div>
            </div>
            <div class="text-center">
                <div id="qrcode" class="d-inline-block"></div>
                <div class="mt-1 no-print">
                    <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                        <span class="material-symbols-outlined">print</span> Imprimir ficha
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 no-print">
        @if($itensBalcao->isNotEmpty())
        <div class="col-12 col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold"><span class="material-symbols-outlined align-middle">storefront</span> Balcão</div>
                <ul class="list-group list-group-flush">
                    @foreach($itensBalcao as $i)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ $i->quantity }}x {{ $i->name }}@if($i->observacao)<div class="text-warning small">⚠ {{ $i->observacao }}</div>@endif</span>
                        <span class="badge {{ $i->status === 'entregue' ? 'bg-secondary' : 'bg-success' }}">{{ $i->status === 'entregue' ? 'Entregue' : 'A retirar' }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
        @if($itensCozinha->isNotEmpty())
        <div class="col-12 col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold"><span class="material-symbols-outlined align-middle">soup_kitchen</span> Cozinha</div>
                <ul class="list-group list-group-flush">
                    @foreach($itensCozinha as $i)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ $i->quantity }}x {{ $i->name }}@unless($i->preparo) <span class="badge bg-info text-dark">acompanha</span>@endunless @if($i->observacao)<div class="text-warning small">⚠ {{ $i->observacao }}</div>@endif</span>
                        <span class="badge {{ $i->status === 'entregue' ? 'bg-secondary' : 'bg-warning text-dark' }}">{{ $i->status === 'entregue' ? 'Entregue' : 'Em preparo' }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>

    <div class="mt-4 no-print d-flex gap-2">
        @if($ficha->status !== 'cancelada')
        <form action="{{ route('fichas.cancelar', $ficha) }}" method="POST" class="requires-auth d-inline"
              data-confirm="Cancelar a ficha {{ $ficha->codigo }}?">
            @csrf
            <button class="btn btn-outline-danger btn-sm">
                <span class="material-symbols-outlined">block</span> Cancelar ficha
            </button>
        </form>
        @endif
        <form action="{{ route('fichas.destroy', $ficha) }}" method="POST" class="requires-auth d-inline"
              data-confirm="Excluir a ficha {{ $ficha->codigo }}? Esta ação exige a senha de autorização.">
            @csrf @method('DELETE')
            <button class="btn btn-danger btn-sm">
                <span class="material-symbols-outlined">delete</span> Excluir ficha
            </button>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const printArea = document.querySelector('.print-area');
    if (printArea) document.body.appendChild(printArea);

    new QRCode(document.getElementById('qrcode'), {
        text: @json($ficha->codigo), width: 130, height: 130, correctLevel: QRCode.CorrectLevel.M,
    });
    const qrPrintEl = document.getElementById('qrcode-print');
    if (qrPrintEl) new QRCode(qrPrintEl, { text: @json($ficha->codigo), width: 320, height: 320, correctLevel: QRCode.CorrectLevel.M });

    @if(session('print'))
    setTimeout(function () { window.print(); }, 400);
    @endif
});
</script>
@endsection
