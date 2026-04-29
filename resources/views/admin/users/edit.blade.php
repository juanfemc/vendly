@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Editar usuario</h2>
</div>

@if ($errors->any())
    <div class="flash error">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="list-card">
    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PUT')

        <input type="text" name="name" value="{{ old('name', $user->name) }}" placeholder="Nombre" required>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" placeholder="Email" required>
        <input type="text" value="{{ $user->role === 'admin' ? 'Administrador' : 'Usuario de tienda' }}" readonly>
        <input type="password" name="password" placeholder="Nueva contrasena (opcional)">
        <input type="password" name="password_confirmation" placeholder="Confirmar nueva contrasena">

        @if($user->role === 'store')
            <label class="field-label" for="active_starts_at">Fecha de inicio</label>
            <input type="date" id="active_starts_at" name="active_starts_at" value="{{ old('active_starts_at', optional($user->active_starts_at)->toDateString()) }}">
            <label class="field-label" for="active_duration_days">Cantidad de tiempo activo (dias)</label>
            <input type="number" id="active_duration_days" name="active_duration_days" value="{{ old('active_duration_days', $user->active_duration_days) }}" min="1" max="3650" placeholder="Ej: 30">

            <div class="flash success">
                Fecha final actual:
                <strong>{{ $user->active_ends_at ? $user->active_ends_at->format('d/m/Y') : 'Sin fecha final' }}</strong>
            </div>
        @endif

        <button type="submit" class="btn">Guardar cambios</button>
    </form>
</div>
@endsection
