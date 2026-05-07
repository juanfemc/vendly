@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Testimonios</h2>
    <a href="{{ route('admin.testimonials.create') }}" class="btn">Crear testimonio</a>
</div>

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="flash error">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

@if ($needsMigration ?? false)
    <div class="flash error">
        La tabla de testimonios todavia no existe. Ejecuta <strong>php artisan migrate</strong> para habilitar esta seccion.
    </div>
@endif

@if ($testimonials->isEmpty() && ! ($needsMigration ?? false))
    <div class="list-card">No hay testimonios registrados.</div>
@endif

@if (! ($needsMigration ?? false))
    @foreach($testimonials as $testimonial)
        <div class="list-card">
            <strong>{{ $testimonial->name }}</strong><br>
            Rol: {{ $testimonial->role ?: 'Sin rol' }}<br>
            Iniciales: {{ $testimonial->initials ?: 'Sin iniciales' }}<br>
            Orden: {{ $testimonial->sort_order }}<br>
            Estado: {{ $testimonial->is_active ? 'Activo' : 'Inactivo' }}<br>
            Texto: {{ $testimonial->quote }}<br><br>

            <a href="{{ route('admin.testimonials.edit', $testimonial) }}" class="btn" style="display:inline-block; margin-right:8px;">Editar</a>

            <form method="POST" action="{{ route('admin.testimonials.toggle', $testimonial) }}" style="display:inline-block; margin-right:8px;">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn">
                    {{ $testimonial->is_active ? 'Desactivar' : 'Activar' }}
                </button>
            </form>

            <form method="POST" action="{{ route('admin.testimonials.destroy', $testimonial) }}" style="display:inline-block;" data-confirm-delete data-confirm-message="Seguro que quieres eliminar este testimonio? Esta accion no se puede deshacer.">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-secondary">Eliminar</button>
            </form>
        </div>
    @endforeach
@endif
@endsection
