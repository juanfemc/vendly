<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>

<body class="login-page">
    <div class="login-card">
        <div class="logo-wrap">
            <img src="{{ asset('images/vendly-logo.svg') }}" alt="Vendly" class="login-logo-image">
        </div>

        <h1 class="title">Iniciar sesión</h1>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="field">
                <div class="input-wrap">
                    <span class="input-icon">@</span>
                    <input id="email" class="input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="correo@ejemplo.com">
                </div>
                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <div class="input-wrap">
                    <span class="input-icon">•</span>
                    <input id="password" class="input" type="password" name="password" required autocomplete="current-password" placeholder="********">
                </div>
                @error('password')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="submit">Entrar al Sistema</button>
        </form>
    </div>
</body>

</html>
