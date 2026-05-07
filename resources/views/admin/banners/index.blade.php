@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Banners / Noticias</h2>
    <a href="/admin/banners/create" class="btn">Crear banner</a>
</div>

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

@if ($banners->isEmpty())
    <div class="list-card">No hay banners registrados.</div>
@endif

@foreach($banners as $banner)
    <div class="list-card">
        <img src="{{ asset('storage/' . $banner->image) }}" alt="{{ $banner->title ?: 'Banner' }}" style="width:100%; max-width:420px; max-height:180px; object-fit:cover; border-radius:10px; display:block; margin-bottom:12px;">
        <strong>{{ $banner->title ?: 'Sin titulo' }}</strong><br>
        Tienda: {{ $banner->applies_to_all ? 'Todas las tiendas' : ($banner->store->name ?? 'Sin tienda') }}<br>
        Texto: {{ $banner->subtitle ?: 'Sin subtítulo' }}<br>
        Orden: {{ $banner->sort_order }}<br>
        Estado: {{ $banner->is_active ? 'Activo' : 'Inactivo' }}<br><br>

        <a href="{{ route('admin.banners.edit', $banner) }}" class="btn" style="display:inline-block; margin-right:8px;">Editar</a>

        <form method="POST" action="{{ route('admin.banners.toggle', $banner) }}" style="display:inline-block; margin-right:8px;">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn">
                {{ $banner->is_active ? 'Desactivar' : 'Activar' }}
            </button>
        </form>

        <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" style="display:inline-block;" data-confirm-delete data-confirm-message="Seguro que quieres eliminar este banner? Esta accion no se puede deshacer.">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-secondary">Eliminar</button>
        </form>
    </div>
@endforeach
@endsection
