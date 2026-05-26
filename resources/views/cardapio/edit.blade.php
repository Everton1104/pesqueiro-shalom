@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-7">

            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('cardapio.index') }}" class="btn btn-sm btn-outline-secondary me-3">&larr; Voltar</a>
                <h4 class="mb-0 fw-bold">Editar: {{ $cardapio->name }}</h4>
            </div>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('cardapio.update', $cardapio) }}" method="POST" enctype="multipart/form-data">
                        @csrf @method('PUT')
                        @include('cardapio._form')
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">Salvar alterações</button>
                            <a href="{{ route('cardapio.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
