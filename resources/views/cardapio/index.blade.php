@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 20;
        vertical-align: middle;
        font-size: 18px;
        line-height: 1;
    }
    .drag-handle { cursor: grab; color: #adb5bd; }
    .drag-handle:active { cursor: grabbing; }
    .cat-drag-handle { cursor: grab; color: #adb5bd; }
    .cat-drag-handle:active { cursor: grabbing; }
    .sortable-ghost { opacity: .4; }
    .cat-sortable-ghost { opacity: .4; }
</style>

<div class="container-lg">

    <div class="d-flex align-items-center justify-content-between mb-4 mt-2">
        <h4 class="mb-0 fw-bold">Gerenciar Cardápio</h4>
        <div class="d-flex gap-2">
            <button id="btn-expand-all"   class="btn btn-outline-secondary btn-sm">Expandir tudo</button>
            <button id="btn-collapse-all" class="btn btn-outline-secondary btn-sm">Recolher tudo</button>
            <a href="{{ route('cardapio.create') }}" class="btn btn-primary btn-sm">+ Novo item</a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
    $statusBadge = [
        'active'      => ['label' => 'Ativo',        'class' => 'bg-success'],
        'hidden'      => ['label' => 'Oculto',        'class' => 'bg-secondary'],
        'unavailable' => ['label' => 'Indisponível',  'class' => 'bg-danger'],
        'coming_soon' => ['label' => 'Em breve',      'class' => 'bg-warning text-dark'],
    ];
    $statusIcon = [
        'active'      => 'check_circle',
        'hidden'      => 'visibility_off',
        'unavailable' => 'cancel',
        'coming_soon' => 'schedule',
    ];
    $emojiMap = [
        'PORÇÕES'=>'🍽','COMIDA'=>'🍚','SALGADOS'=>'🥟','CERVEJAS'=>'🍺',
        'BEBIDAS'=>'🥤','BEBIDAS QUENTES'=>'🥃','BORBONS'=>'🥃','CAIPIRINHAS'=>'🍹','DRINKS'=>'🍸'
    ];
    @endphp

    {{-- Container sortável de cards de categoria --}}
    <div id="categories-sortable">

        @foreach($items as $cat => $catItems)
        @php
            $collapseId = 'cat-' . Str::slug($cat);
            $catId = $categoryMeta[$cat]->id ?? 0;
        @endphp
        <div class="card mb-3" data-cat-id="{{ $catId }}">

            <div class="card-header d-flex align-items-center justify-content-between py-2">
                {{-- Handle de drag da categoria --}}
                <div class="d-flex align-items-center gap-2">
                    <span class="cat-drag-handle me-1" title="Arrastar categoria">
                        <span class="material-symbols-outlined">drag_indicator</span>
                    </span>
                    <span class="collapse-toggle d-flex align-items-center gap-2"
                          data-bs-toggle="collapse"
                          data-bs-target="#{{ $collapseId }}"
                          style="cursor:pointer; user-select:none;">
                        <span class="collapse-icon" style="transition:transform .2s; display:inline-flex;">
                            <span class="material-symbols-outlined">expand_more</span>
                        </span>
                        <span class="fw-bold text-uppercase" style="letter-spacing:.08em; font-size:.82rem;">{{ $cat }}</span>
                        <span class="badge bg-secondary ms-1">{{ $catItems->count() }}</span>
                    </span>
                </div>
                <span class="text-muted small d-none d-md-inline">
                    <span class="material-symbols-outlined" style="font-size:14px;">swap_vert</span>
                    Arraste para reordenar
                </span>
            </div>

            <div class="collapse show" id="{{ $collapseId }}">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:28px"></th>
                                <th style="width:56px"></th>
                                <th>Nome</th>
                                <th class="d-none d-md-table-cell">Descrição</th>
                                <th class="text-end">Preço</th>
                                <th class="text-center" style="width:110px">Status</th>
                                <th class="text-end" style="width:110px">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="sortable-list" data-category="{{ $cat }}">
                            @foreach($catItems as $item)
                            @php $s = $statusBadge[$item->status] ?? $statusBadge['hidden']; @endphp
                            <tr data-id="{{ $item->id }}"
                                class="{{ in_array($item->status, ['hidden','unavailable']) ? 'opacity-50' : '' }}">
                                <td class="drag-handle" title="Arrastar item">
                                    <span class="material-symbols-outlined">drag_indicator</span>
                                </td>
                                <td>
                                    @if($item->image)
                                        <img src="{{ Storage::url($item->image) }}" alt=""
                                             style="width:40px;height:40px;object-fit:cover;border-radius:6px;border:1px solid #dee2e6;">
                                    @else
                                        <div style="width:40px;height:40px;border-radius:6px;background:#f4f4f4;display:flex;align-items:center;justify-content:center;font-size:1.3rem;border:1px solid #dee2e6;">
                                            {{ $emojiMap[$cat] ?? '🍴' }}
                                        </div>
                                    @endif
                                </td>
                                <td class="fw-semibold">{{ $item->name }}</td>
                                <td class="text-muted small d-none d-md-table-cell">{{ $item->description ?? '—' }}</td>
                                <td class="text-end fw-semibold">{{ $item->price_formatted }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $s['class'] }}">{{ $s['label'] }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('cardapio.edit', $item) }}"
                                       class="btn btn-outline-secondary btn-sm me-1" title="Editar">
                                        <span class="material-symbols-outlined">edit</span>
                                    </a>
                                    <form action="{{ route('cardapio.cycle', $item) }}" method="POST" class="d-inline"
                                          title="Ciclar status">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-outline-primary btn-sm me-1">
                                            <span class="material-symbols-outlined">{{ $statusIcon[$item->status] ?? 'help' }}</span>
                                        </button>
                                    </form>
                                    <form action="{{ route('cardapio.destroy', $item) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Remover {{ addslashes($item->name) }}?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm" title="Remover">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endforeach

    </div>{{-- #categories-sortable --}}

    @if($items->isEmpty())
        <div class="text-center text-muted py-5">
            <p>Nenhum item cadastrado ainda.</p>
            <a href="{{ route('cardapio.create') }}" class="btn btn-primary mt-2">Adicionar primeiro item</a>
        </div>
    @endif

</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
    const CSRF              = '{{ csrf_token() }}';
    const REORDER_ITEMS_URL = '{{ parse_url(route("cardapio.reorder"), PHP_URL_PATH) }}';
    const REORDER_CATS_URL  = '{{ parse_url(route("cardapio.categories.reorder"), PHP_URL_PATH) }}';

    function postOrder(url, ids) {
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ ids }),
        });
    }

    // ── Drag-and-drop de CATEGORIAS ──
    Sortable.create(document.getElementById('categories-sortable'), {
        handle: '.cat-drag-handle',
        animation: 200,
        ghostClass: 'cat-sortable-ghost',
        onEnd() {
            const ids = [...document.querySelectorAll('#categories-sortable > .card[data-cat-id]')]
                .map(el => el.dataset.catId);
            postOrder(REORDER_CATS_URL, ids);
        }
    });

    // ── Drag-and-drop de ITENS dentro de cada categoria ──
    document.querySelectorAll('.sortable-list').forEach(tbody => {
        Sortable.create(tbody, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd() {
                const ids = [...tbody.querySelectorAll('tr[data-id]')].map(tr => tr.dataset.id);
                postOrder(REORDER_ITEMS_URL, ids);
            }
        });
    });

    // ── Seta do collapse ──
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(toggle => {
        const target = document.querySelector(toggle.dataset.bsTarget);
        const icon   = toggle.querySelector('.collapse-icon');
        if (!target || !icon) return;
        target.addEventListener('hide.bs.collapse', () => icon.style.transform = 'rotate(-90deg)');
        target.addEventListener('show.bs.collapse', () => icon.style.transform = 'rotate(0deg)');
    });

    // ── Expandir / Recolher tudo ──
    document.getElementById('btn-expand-all').addEventListener('click', () => {
        document.querySelectorAll('.collapse').forEach(el =>
            bootstrap.Collapse.getOrCreateInstance(el).show());
    });
    document.getElementById('btn-collapse-all').addEventListener('click', () => {
        document.querySelectorAll('.collapse').forEach(el =>
            bootstrap.Collapse.getOrCreateInstance(el).hide());
    });
})();
</script>
@endsection
