@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">

            <div class="text-center mb-4 mt-4">
                <img src="/favicon.svg" alt="" style="width:54px;height:54px;">
                <h3 class="fw-bold mt-2 mb-0" style="letter-spacing:.04em;">
                    <span style="color:var(--sh-orange2);">Pesqueiro</span> Shalom
                </h3>
                <p class="text-muted small mb-0">Painel administrativo</p>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">{{ __('Email') }}</label>
                            <input id="email" type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                                   placeholder="seu@email.com">
                            @error('email')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('Senha') }}</label>
                            <input id="password" type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   name="password" required autocomplete="current-password"
                                   placeholder="••••••••">
                            @error('password')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">{{ __('Manter conectado') }}</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            {{ __('Entrar') }}
                        </button>

                        @if (Route::has('password.request'))
                            <div class="text-center">
                                <a class="small text-muted" href="{{ route('password.request') }}">
                                    {{ __('Esqueceu a senha?') }}
                                </a>
                            </div>
                        @endif
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
