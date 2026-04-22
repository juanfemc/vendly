@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Crear usuario de tienda</h2>
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
        <input type="password" name="password" placeholder="Contrasena">
        <input type="password" name="password_confirmation" placeholder="Confirmar contrasena">
        <label class="field-label" for="active_starts_at">Fecha de inicio</label>
        <input type="date" id="active_starts_at" name="active_starts_at" value="{{ old('active_starts_at', now()->toDateString()) }}">
        <label class="field-label" for="active_duration_days">Cantidad de tiempo activo (dias)</label>
        <input type="number" id="active_duration_days" name="active_duration_days" value="{{ old('active_duration_days') }}" min="1" max="3650" placeholder="Ej: 30">

        <button type="submit" class="btn">Crear usuario</button>
    </form>
</div>
@endsection
