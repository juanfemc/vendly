@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Crear usuario</h2>
</div>

@if ($errors->any())
    <div class="flash error">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="list-card">
    <form method="POST" action="/admin/users">
        @csrf

        <input type="text" name="name" value="{{ old('name') }}" placeholder="Nombre">
        <input type="email" name="email" value="{{ old('email') }}" placeholder="Email">
        <label class="field-label" for="role">Rol</label>
        <select id="role" name="role">
            <option value="store" @selected(old('role', 'store') === 'store')>Usuario de tienda</option>
            <option value="admin" @selected(old('role') === 'admin')>Administrador</option>
        </select>
        <input type="password" name="password" placeholder="Contrasena">
        <input type="password" name="password_confirmation" placeholder="Confirmar contrasena">

        <div id="active_period_fields">
            <label class="field-label" for="active_starts_at">Fecha de inicio</label>
            <input type="date" id="active_starts_at" name="active_starts_at" value="{{ old('active_starts_at', now()->toDateString()) }}">
            <label class="field-label" for="active_duration_days">Cantidad de tiempo activo (dias)</label>
            <input type="number" id="active_duration_days" name="active_duration_days" value="{{ old('active_duration_days') }}" min="1" max="3650" placeholder="Ej: 30">
        </div>

        <button type="submit" class="btn">Crear usuario</button>
    </form>
</div>

<script>
    (() => {
        const role = document.getElementById('role');
        const activeFields = document.getElementById('active_period_fields');

        if (!role || !activeFields) {
            return;
        }

        const syncFields = () => {
            activeFields.style.display = role.value === 'store' ? 'block' : 'none';
        };

        role.addEventListener('change', syncFields);
        syncFields();
    })();
</script>
@endsection
