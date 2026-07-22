@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
<style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 20; vertical-align: middle; font-size: 18px; line-height: 1; }

    /* ── CUPOM DE IMPRESSÃO (impressora térmica 80mm) ── */
    .print-area { display: none; }

    @media print {
        @page { size: 80mm auto; margin: 3mm; }
        html, body { background: #fff !important; margin: 0; padding: 0; }
        body > #app { display: none !important; }

        .print-area { display: block; width: 100%; color: #000; font-family: 'Nunito', Arial, sans-serif; }
        .pa-brand { text-align: center; font-weight: 800; font-size: 12pt; letter-spacing: .06em; text-transform: uppercase; }
        .pa-tipo { text-align: center; font-size: 8pt; text-transform: uppercase; letter-spacing: .12em; margin: 1mm 0; font-weight: 700; }
        .pa-cliente { text-align: center; font-size: 10pt; font-weight: 700; }
        .pa-data { text-align: center; font-size: 8pt; margin-bottom: 2mm; }
        .pa-divisor { border: none; border-top: 1px dashed #000; margin: 1mm 0 2mm; }
        .pa-items { width: 100%; border-collapse: collapse; font-size: 9pt; }
        .pa-items td { padding: 1mm 0; vertical-align: top; }
        .pa-items .chk { font-size: 13pt; line-height: 1; padding-right: 2mm; white-space: nowrap; }
    }
</style>

<div class="container-lg">
    {{-- Cupom impresso (visível só na impressão — 80mm): lista de entrega (todos os itens) --}}
    <div class="print-area">
        <div class="pa-brand">Pesqueiro Shalom</div>
        <div class="pa-tipo">Ficha · {{ $ficha->codigo }}</div>
        @if($ficha->cliente)<div class="pa-cliente">{{ $ficha->cliente }}</div>@endif
        <div class="pa-data">{{ $ficha->created_at->format('d/m/Y H:i') }}</div>
        <hr class="pa-divisor">
        <table class="pa-items">
            @foreach($ficha->items as $i)
                @for($k = 0; $k < (int) $i->quantity; $k++)
                    <tr><td class="chk">☐</td><td>{{ $i->name }}@if($i->observacao && $k === 0)<br><small>obs: {{ $i->observacao }}</small>@endif</td></tr>
                @endfor
            @endforeach
        </table>
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
                <div class="mt-1 no-print">
                    <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                        <span class="material-symbols-outlined">print</span> Imprimir lista de entrega
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4 no-print">
        <div class="card-header fw-bold"><span class="material-symbols-outlined align-middle">receipt_long</span> Itens da ficha</div>
        <ul class="list-group list-group-flush">
            @foreach($ficha->items as $i)
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>
                    {{ $i->quantity }}x {{ $i->name }}
                    @if($i->destino === 'cozinha') <span class="badge bg-warning text-dark">cozinha</span> @endif
                    @if($i->observacao)<div class="text-warning small">⚠ {{ $i->observacao }}</div>@endif
                </span>
                <span class="badge bg-light text-dark border">{{ $i->unit_price_formatted }}</span>
            </li>
            @endforeach
        </ul>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const printArea = document.querySelector('.print-area');
    if (printArea) document.body.appendChild(printArea);

    // Reimpressão: dispara a impressão ao chegar via ?print=1
    @if(request()->query('print'))
    setTimeout(function () { window.print(); }, 400);
    @endif
});
</script>
@endsection
