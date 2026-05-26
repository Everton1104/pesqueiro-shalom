@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">

            <h4 class="fw-bold mb-4">Painel Administrativo</h4>

            @if(session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <div class="card">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-1 fw-bold">Cardápio</h5>
                        <p class="text-muted mb-0 small">Gerencie categorias, itens, preços e visibilidade.</p>
                    </div>
                    <a href="{{ route('cardapio.index') }}" class="btn btn-primary">Gerenciar</a>
                </div>
            </div>

            <div class="mt-3">
                <a href="{{ url('/') }}" class="text-muted small">Ver cardápio público &rarr;</a>
            </div>

        </div>
    </div>
</div>
@endsection
