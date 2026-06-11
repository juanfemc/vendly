@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Editar banner</h2>
    <a href="/admin/banners" class="btn btn-secondary">Volver</a>
</div>

@if ($errors->any())
    <div class="flash error">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="list-card">
    <form method="POST" action="{{ route('admin.banners.update', $banner) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div style="margin-bottom:12px; color:#6b7280; font-size:13px;">
            {{ $banner->applies_to_all ? 'Este banner aplica para todas las tiendas.' : 'Tienda: ' . ($banner->store->name ?? 'Sin tienda') }}
        </div>

        @if($banner->image)
            <img src="{{ asset('storage/' . $banner->image) }}" alt="{{ $banner->title ?: 'Banner' }}" style="width:100%; max-width:420px; max-height:180px; object-fit:cover; border-radius:10px; display:block; margin-bottom:12px;">
        @endif

        <input type="text" name="title" value="{{ old('title', $banner->title) }}" placeholder="Titulo del banner opcional">
        <textarea name="subtitle" placeholder="Texto corto opcional">{{ old('subtitle', $banner->subtitle) }}</textarea>
        <input type="text" name="link" value="{{ old('link', $banner->link) }}" placeholder="Link opcional">
        <input type="number" name="sort_order" value="{{ old('sort_order', $banner->sort_order) }}" placeholder="Orden">
        <label style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $banner->is_active)) style="width:auto; margin:0;">
            Activo
        </label>
        <label class="field-label" for="banner_image">Cambiar imagen del banner</label>
        <input id="banner_image" type="file" name="image" accept="image/*" data-optimize-image data-max-width="1920" data-max-height="1080" data-quality="0.82" data-output="webp" data-max-size="4194304">

        <button class="btn">Actualizar banner</button>
    </form>
</div>
@endsection
