@php $item = $item ?? $cardapio ?? null; @endphp

<div class="mb-3">
    <label for="category" class="form-label fw-semibold">Categoria</label>
    <select name="category" id="category" class="form-select @error('category') is-invalid @enderror" required>
        <option value="">Selecione...</option>
        @foreach($categories as $cat)
            <option value="{{ $cat }}" {{ old('category', $item?->category) === $cat ? 'selected' : '' }}>
                {{ $cat }}
            </option>
        @endforeach
    </select>
    @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="name" class="form-label fw-semibold">Nome</label>
    <input type="text" name="name" id="name"
           class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $item?->name) }}" required maxlength="150">
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="description" class="form-label fw-semibold">
        Descrição <span class="text-muted fw-normal">(opcional — ex: 500g, ingredientes)</span>
    </label>
    <input type="text" name="description" id="description"
           class="form-control @error('description') is-invalid @enderror"
           value="{{ old('description', $item?->description) }}" maxlength="255">
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">
        Foto do produto <span class="text-muted fw-normal">(opcional, JPG/PNG/WEBP, máx 2 MB)</span>
    </label>

    @if($item?->image)
        <div class="d-flex align-items-center gap-3 mb-2 p-2 border rounded" style="max-width:320px">
            <img src="{{ Storage::url($item->image) }}" alt="{{ $item->name }}"
                 style="width:64px;height:64px;object-fit:cover;border-radius:6px;">
            <div>
                <div class="small text-muted mb-1">Imagem atual</div>
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="remove_image" id="remove_image" value="1">
                    <label class="form-check-label text-danger small" for="remove_image">Remover imagem</label>
                </div>
            </div>
        </div>
    @endif

    <input type="file" name="image" id="image"
           class="form-control @error('image') is-invalid @enderror"
           accept="image/jpeg,image/png,image/webp"
           onchange="previewImage(this)">
    @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror

    <img id="img-preview" src="" alt="Pré-visualização"
         style="display:none;margin-top:.5rem;width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #dee2e6;">
</div>

<div class="row">
    <div class="col-sm-4 mb-3">
        <label for="price" class="form-label fw-semibold">Preço (R$)</label>
        <input type="number" name="price" id="price"
               class="form-control @error('price') is-invalid @enderror"
               value="{{ old('price', $item?->price) }}"
               step="0.01" min="0" required>
        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-sm-4 mb-3">
        <label for="sort_order" class="form-label fw-semibold">Ordem</label>
        <input type="number" name="sort_order" id="sort_order"
               class="form-control @error('sort_order') is-invalid @enderror"
               value="{{ old('sort_order', $item?->sort_order ?? $nextOrder ?? 0) }}"
               min="0" required>
        @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-sm-4 mb-3">
        <label for="status" class="form-label fw-semibold">Status</label>
        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
            @foreach($statuses as $value => $info)
                <option value="{{ $value }}" {{ old('status', $item?->status ?? 'active') === $value ? 'selected' : '' }}>
                    {{ $info['label'] }}
                </option>
            @endforeach
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('img-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}
</script>
