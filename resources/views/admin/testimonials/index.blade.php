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
    <div class="panel-empty">
        <h3>No hay testimonios registrados</h3>
        <p>Agrega testimonios para reforzar confianza en la pagina principal de Vendly.</p>
        <a href="{{ route('admin.testimonials.create') }}" class="btn">Crear testimonio</a>
    </div>
@endif

@if (! ($needsMigration ?? false))
    <div class="panel-list">
        @foreach($testimonials as $testimonial)
            <article class="list-card resource-card">
                <div class="resource-card__main">
                    <div class="resource-card__header">
                        <div>
                            <h3 class="resource-card__title">{{ $testimonial->name }}</h3>
                            <p class="resource-card__subtitle">{{ $testimonial->role ?: 'Sin rol' }}</p>
                        </div>
                        <div class="resource-badges">
                            <span class="resource-badge {{ $testimonial->is_active ? 'resource-badge--active' : 'resource-badge--inactive' }}">
                                {{ $testimonial->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                            <span class="resource-badge">Orden {{ $testimonial->sort_order }}</span>
                            <span class="resource-badge">{{ $testimonial->initials ?: 'Sin iniciales' }}</span>
                        </div>
                    </div>

                    <p class="resource-card__description">{{ $testimonial->quote }}</p>
                </div>

                <div class="resource-actions">
                    <a href="{{ route('admin.testimonials.edit', $testimonial) }}" class="btn">Editar</a>

                    <form method="POST" action="{{ route('admin.testimonials.toggle', $testimonial) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn {{ $testimonial->is_active ? 'btn-warning' : 'btn-success' }}">
                            {{ $testimonial->is_active ? 'Desactivar' : 'Activar' }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.testimonials.destroy', $testimonial) }}" data-confirm-delete data-confirm-message="Seguro que quieres eliminar este testimonio? Esta accion no se puede deshacer.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </article>
        @endforeach
    </div>
@endif
@endsection
