@extends('layouts.admin')

@section('content')
    <div class="header">
        <h2>Perfil</h2>
    </div>

    @if (session('status') === 'profile-updated')
        <div class="flash success">Perfil actualizado correctamente.</div>
    @endif

    @if (session('status') === 'password-updated')
        <div class="flash success">Contraseña actualizada correctamente.</div>
    @endif

    <div class="grid">
        <section class="card">
            <h3 style="margin-top:0;">Información de la cuenta</h3>
            <p style="color:#6b7280; margin-top:0;">Actualiza tu nombre y correo electrónico.</p>

            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PATCH')

                <label for="name">Nombre</label>
                <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required>
                @error('name')
                    <div class="flash error">{{ $message }}</div>
                @enderror

                <label for="email">Correo</label>
                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                @error('email')
                    <div class="flash error">{{ $message }}</div>
                @enderror

                <button type="submit" class="btn">Guardar cambios</button>
            </form>
        </section>

        <section class="card">
            <h3 style="margin-top:0;">Cambiar contraseña</h3>
            <p style="color:#6b7280; margin-top:0;">Usa una contraseña segura para proteger tu cuenta.</p>

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                @method('PUT')

                <label for="current_password">Contraseña actual</label>
                <input id="current_password" type="password" name="current_password" autocomplete="current-password">
                @if ($errors->updatePassword->has('current_password'))
                    <div class="flash error">{{ $errors->updatePassword->first('current_password') }}</div>
                @endif

                <label for="password">Nueva contraseña</label>
                <input id="password" type="password" name="password" autocomplete="new-password">
                @if ($errors->updatePassword->has('password'))
                    <div class="flash error">{{ $errors->updatePassword->first('password') }}</div>
                @endif

                <label for="password_confirmation">Confirmar nueva contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password">
                @if ($errors->updatePassword->has('password_confirmation'))
                    <div class="flash error">{{ $errors->updatePassword->first('password_confirmation') }}</div>
                @endif

                <button type="submit" class="btn">Actualizar contraseña</button>
            </form>
        </section>

    </div>
@endsection
