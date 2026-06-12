<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Pesqueiro Shalom') }}</title>

    <!-- Fonts -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito:400,600,700,800&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <!-- Tema do sistema (carregado após o Bootstrap) -->
    <link href="{{ asset('css/theme.css') }}?v=2" rel="stylesheet">
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md sh-navbar shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <img src="/favicon.svg" alt="">
                    Pesqueiro <span>Shalom</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">
                        @auth
                            @if(auth()->user()->is_admin)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('cardapio.*') ? 'active' : '' }}" href="{{ route('cardapio.index') }}">Cardápio</a>
                            </li>
                            @endif
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('comandas.*') ? 'active' : '' }}" href="{{ route('comandas.index') }}">Comandas</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('fichas.*') ? 'active' : '' }}" href="{{ route('fichas.index') }}">Fichas</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('balcao.*') ? 'active' : '' }}" href="{{ route('balcao.index') }}">Balcão</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('cozinha.*') ? 'active' : '' }}" href="{{ route('cozinha.index') }}">Cozinha</a>
                            </li>
                            @if(auth()->user()->is_admin)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('relatorios.*') ? 'active' : '' }}" href="{{ route('relatorios.index') }}">Relatórios</a>
                            </li>
                            @endif
                        @endauth
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('/') }}" target="_blank">Ver cardápio ↗</a>
                            </li>
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    {{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>
    </div>

    @auth
    {{-- Modal de autorização (ações protegidas para não-admins) --}}
    <div class="modal fade" id="authModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Autorização necessária</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-2" id="authModalMsg">Esta ação exige a senha de autorização do administrador.</p>
                    <input type="password" id="authPasswordInput" class="form-control" placeholder="Senha de autorização" autocomplete="off">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="authConfirmBtn">Autorizar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.SH_IS_ADMIN = @json((bool) auth()->user()->is_admin);

        // Atalho global: ESC volta para a tela de comandas (se um modal estiver aberto, só fecha o modal)
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            if (document.querySelector('.modal.show')) return;
            window.location.href = @json(route('comandas.index'));
        });

        // Gate de autorização para formulários .requires-auth
        (function () {
            let pendingForm = null;
            const modalEl = document.getElementById('authModal');
            const pwd = document.getElementById('authPasswordInput');
            const confirmBtn = document.getElementById('authConfirmBtn');

            function submitForm(form) { form.dataset.authed = '1'; form.submit(); }

            document.addEventListener('submit', function (e) {
                const form = e.target;
                if (!(form instanceof HTMLFormElement) || !form.classList.contains('requires-auth')) return;
                if (form.dataset.authed === '1') return;
                e.preventDefault();

                if (window.SH_IS_ADMIN) {
                    if (confirm(form.dataset.confirm || 'Confirmar ação?')) submitForm(form);
                    return;
                }
                pendingForm = form;
                pwd.value = '';
                document.getElementById('authModalMsg').textContent = form.dataset.confirm || 'Esta ação exige a senha de autorização do administrador.';
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
                setTimeout(() => pwd.focus(), 300);
            });

            confirmBtn.addEventListener('click', function () {
                if (!pendingForm) return;
                let inp = pendingForm.querySelector('input[name="auth_password"]');
                if (!inp) { inp = document.createElement('input'); inp.type = 'hidden'; inp.name = 'auth_password'; pendingForm.appendChild(inp); }
                inp.value = pwd.value;
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                submitForm(pendingForm);
            });

            pwd.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); confirmBtn.click(); } });
        })();
    </script>
    @endauth

    {{-- Auto-dismiss das mensagens flash (.alert-dismissible) após 5s — avisos fixos da tela não têm essa classe --}}
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.alert-dismissible').forEach(function (el) {
            setTimeout(function () {
                if (window.bootstrap && bootstrap.Alert) {
                    bootstrap.Alert.getOrCreateInstance(el).close();
                } else {
                    el.classList.remove('show');
                    setTimeout(function () { el.remove(); }, 200);
                }
            }, 5000);
        });
    });
    </script>
</body>
</html>
