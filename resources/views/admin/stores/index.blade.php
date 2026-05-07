@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Tiendas</h2>
    <a href="/admin/stores/create" class="btn">Crear tienda</a>
</div>

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

@foreach($stores as $store)
    <div class="list-card">
        @if ($store->cover_image)
            <img src="{{ asset('storage/' . $store->cover_image) }}" alt="{{ $store->name }}" class="thumb">
        @endif
        <strong>{{ $store->name }}</strong><br>
        Tipo: {{ $store->businessTypeLabel() }}<br>
        URL: /{{ $store->slug }}<br>
        WhatsApp: {{ $store->whatsapp }}<br>
        Usuario: {{ $store->user->name ?? 'Sin usuario' }}<br>
        Creada por admin: {{ $store->creatorAdmin->name ?? 'No registrado' }}<br>
        Shop copy: {{ $store->shop_copy ?: 'Sin texto configurado' }}

        <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('admin.stores.edit', $store) }}" class="btn">Editar</a>
            <form method="POST" action="{{ route('admin.stores.destroy', $store) }}" data-confirm-delete data-confirm-message="Seguro que quieres eliminar esta tienda? Esta accion no se puede deshacer.">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn" style="background:#dc2626;">Eliminar</button>
            </form>
        </div>
    </div>
@endforeach
@endsection
