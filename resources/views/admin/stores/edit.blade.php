@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Editar tienda</h2>
</div>

@if ($errors->any())
    <div class="flash error">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="list-card">
    <form method="POST" action="{{ route('admin.stores.update', $store) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <select name="user_id" required>
            <option value="">Selecciona usuario de tienda</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected(old('user_id', $store->user_id) == $user->id)>{{ $user->name }} - {{ $user->email }}</option>
            @endforeach
        </select>

        <input type="text" name="name" value="{{ old('name', $store->name) }}" placeholder="Nombre tienda">

        <select name="business_type" required>
            @foreach (\App\Models\Store::businessTypeOptions() as $value => $label)
                <option value="{{ $value }}" @selected(old('business_type', $store->business_type) === $value)>{{ $label }}</option>
            @endforeach
        </select>

        <input type="text" name="slug" value="{{ old('slug', $store->slug) }}" placeholder="Slug (ej: mitienda)">
        <input type="text" name="whatsapp" value="{{ old('whatsapp', $store->whatsapp) }}" placeholder="WhatsApp">
        <textarea name="shop_copy" placeholder="Texto corto del shop header">{{ old('shop_copy', $store->shop_copy) }}</textarea>
        <label class="field-label" for="responsive_product_columns">Columnas de productos en responsive</label>
        <select id="responsive_product_columns" name="responsive_product_columns">
            <option value="1" @selected((int) old('responsive_product_columns', $store->responsive_product_columns ?? 2) === 1)>1 columna</option>
            <option value="2" @selected((int) old('responsive_product_columns', $store->responsive_product_columns ?? 2) === 2)>2 columnas</option>
            <option value="3" @selected((int) old('responsive_product_columns', $store->responsive_product_columns ?? 2) === 3)>3 columnas</option>
        </select>
        <label class="field-label" for="show_hero_products_action">Boton y texto sobre la portada</label>
        <select id="show_hero_products_action" name="show_hero_products_action">
            <option value="1" @selected((bool) old('show_hero_products_action', $store->show_hero_products_action ?? false))>Habilitado</option>
            <option value="0" @selected(! (bool) old('show_hero_products_action', $store->show_hero_products_action ?? false))>Deshabilitado</option>
        </select>

        @if ($store->logo_image)
            <img src="{{ asset('storage/' . $store->logo_image) }}" alt="{{ $store->name }}" style="width:100px; height:100px; object-fit:cover; border-radius:14px; display:block; margin-bottom:12px;">
        @endif
        <label class="field-label" for="store_logo_image">Sube el logo de la tienda</label>
        <input id="store_logo_image" type="file" name="logo_image" accept="image/*" data-optimize-image data-max-width="720" data-max-height="720" data-quality="0.86" data-output="webp">

        @if ($store->cover_image)
            <img src="{{ asset('storage/' . $store->cover_image) }}" alt="{{ $store->name }}" style="width:100%; max-width:420px; max-height:220px; object-fit:cover; border-radius:10px; display:block; margin-bottom:12px;">
        @endif
        <label class="field-label" for="store_cover_image">Sube la portada de la tienda</label>
        <input id="store_cover_image" type="file" name="cover_image" accept="image/*" data-optimize-image data-max-width="1920" data-max-height="1080" data-quality="0.82" data-output="webp">

        <button class="btn">Guardar cambios</button>
    </form>
</div>
@endsection
