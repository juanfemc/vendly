@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Crear banner / noticia</h2>
</div>

@if ($errors->any())
    <div class="flash error">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="list-card">
    <form method="POST" action="/admin/banners" enctype="multipart/form-data">
        @csrf

        <select name="store_id" required>
            <option value="">Selecciona tienda</option>
            <option value="all" @selected(old('store_id') === 'all')>Todas las tiendas</option>
            @foreach ($stores as $store)
                <option value="{{ $store->id }}" @selected(old('store_id') == $store->id)>{{ $store->name }}</option>
            @endforeach
        </select>
        <input type="text" name="title" value="{{ old('title') }}" placeholder="Titulo del banner">
        <textarea name="subtitle" placeholder="Texto corto del banner">{{ old('subtitle') }}</textarea>
        <input type="text" name="link" value="{{ old('link') }}" placeholder="Link opcional">
        <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" placeholder="Orden">
        <label style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
            <input type="checkbox" name="is_active" value="1" checked style="width:auto; margin:0;">
            Activo
        </label>
        <label class="field-label" for="banner_image">Sube la imagen del banner</label>
        <input id="banner_image" type="file" name="image" accept="image/*" required data-optimize-image data-max-width="1920" data-max-height="1080" data-quality="0.82" data-output="webp">

        <button class="btn">Guardar banner</button>
    </form>
</div>
@endsection
