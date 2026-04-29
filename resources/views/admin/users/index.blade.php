@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Usuarios</h2>
    <a href="/admin/users/create" class="btn">Crear usuario</a>
</div>

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

@if ($users->isEmpty())
    <div class="list-card">No hay usuarios registrados.</div>
@endif

@foreach($users as $user)
    <div class="list-card">
        <strong>{{ $user->name }}</strong><br>
        Email: {{ $user->email }}<br>
        Rol: {{ $user->role === 'admin' ? 'Administrador' : 'Usuario de tienda' }}<br>
        Estado: {{ $user->isActive() ? 'Activa' : 'Inactiva' }}<br>

        @if($user->role === 'store')
            Tiempo permitido:
            {{ $user->active_duration_days ? $user->active_duration_days . ' dia(s)' : 'Sin limite definido' }}<br>
            Fecha de inicio:
            {{ $user->active_starts_at ? $user->active_starts_at->format('d/m/Y') : 'Sin fecha' }}<br>
            Fecha final:
            {{ $user->active_ends_at ? $user->active_ends_at->format('d/m/Y') : 'Sin fecha final' }}<br>
            Tiempo restante: {{ $user->active_remaining_label }}
        @endif

        <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:14px;">
            <a href="{{ route('admin.users.edit', $user) }}" class="btn">Editar</a>

            @if($user->role === 'store')
                <form method="POST" action="{{ route('admin.users.toggle', $user) }}" onsubmit="return confirm('Deseas {{ $user->is_active ? 'pausar' : 'reactivar' }} esta cuenta?');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn" style="background:{{ $user->is_active ? '#f59e0b' : '#16a34a' }};">
                        {{ $user->is_active ? 'Pausar cuenta' : 'Reactivar cuenta' }}
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Eliminar este usuario y su tienda? Esta accion no se puede deshacer.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn" style="background:#dc2626;">Eliminar</button>
                </form>
            @endif
        </div>
    </div>
@endforeach
@endsection
