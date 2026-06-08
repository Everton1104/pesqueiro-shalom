@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
<style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 20; vertical-align: middle; line-height: 1; }
    .admin-card { transition: transform .12s, border-color .15s, box-shadow .15s; }
    .admin-card:hover { transform: translateY(-3px); border-color: var(--sh-orange); box-shadow: 0 .6rem 1.4rem rgba(217,119,6,.18); }
    .admin-icon {
        width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: rgba(217,119,6,.14); color: var(--sh-orange2);
    }
    .admin-icon .material-symbols-outlined { font-size: 28px; }
</style>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8">

            <div class="d-flex align-items-center gap-3 mb-1 mt-2">
                <h4 class="fw-bold mb-0">Painel Administrativo</h4>
            </div>
            <p class="text-muted mb-4">Olá, {{ Auth::user()->name }} 👋 — o que vamos gerenciar hoje?</p>

            @if(session('status'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(auth()->user()->is_admin)
            <div class="card admin-card mb-3">
                <div class="card-body d-flex align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="admin-icon"><span class="material-symbols-outlined">restaurant_menu</span></div>
                        <div>
                            <h5 class="mb-1 fw-bold">Cardápio</h5>
                            <p class="text-muted mb-0 small">Gerencie categorias, itens, preços e visibilidade.</p>
                        </div>
                    </div>
                    <a href="{{ route('cardapio.index') }}" class="btn btn-primary">Gerenciar</a>
                </div>
            </div>
            @endif

            <div class="card admin-card">
                <div class="card-body d-flex align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="admin-icon"><span class="material-symbols-outlined">receipt_long</span></div>
                        <div>
                            <h5 class="mb-1 fw-bold">Comandas</h5>
                            <p class="text-muted mb-0 small">Abra comandas por cliente, lance itens e feche com pagamento.</p>
                        </div>
                    </div>
                    <a href="{{ route('comandas.index') }}" class="btn btn-primary">Abrir comandas</a>
                </div>
            </div>

            @if(auth()->user()->is_admin)
            <div class="card admin-card mt-3">
                <div class="card-body d-flex align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="admin-icon"><span class="material-symbols-outlined">bar_chart</span></div>
                        <div>
                            <h5 class="mb-1 fw-bold">Relatórios</h5>
                            <p class="text-muted mb-0 small">Resumo de faturamento e itens vendidos por semana e mês.</p>
                        </div>
                    </div>
                    <a href="{{ route('relatorios.index') }}" class="btn btn-primary">Ver relatórios</a>
                </div>
            </div>
            @endif

            @if(auth()->user()->is_admin)
            <div class="card admin-card mt-3">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="admin-icon"><span class="material-symbols-outlined">lock</span></div>
                        <div>
                            <h5 class="mb-1 fw-bold">Senha de autorização</h5>
                            <p class="text-muted mb-0 small">
                                Exigida de funcionários para <strong>cancelar comandas</strong> ou <strong>excluir do histórico</strong>.
                                Status: {{ \App\Models\Setting::get('cancel_auth_password') ? 'definida ✓' : 'ainda não definida' }}.
                            </p>
                        </div>
                    </div>
                    <form action="{{ route('admin.auth-password') }}" method="POST" class="row g-2">
                        @csrf
                        <div class="col-sm-8">
                            <input type="text" name="auth_password" class="form-control"
                                   placeholder="Nova senha de autorização" minlength="4" maxlength="50" required autocomplete="off">
                        </div>
                        <div class="col-sm-4">
                            <button class="btn btn-primary w-100">Salvar senha</button>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            <div class="mt-3">
                <a href="{{ url('/') }}" target="_blank" class="text-muted small">Ver cardápio público ↗</a>
            </div>

        </div>
    </div>
</div>
@endsection
