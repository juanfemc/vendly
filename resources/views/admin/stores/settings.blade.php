@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Configuración de tienda</h2>
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

<div class="list-card">
    <form method="POST" action="/admin/store-settings" enctype="multipart/form-data">
        @csrf

        <input type="text" name="name" value="{{ old('name', $store->name) }}" placeholder="Nombre tienda">
        <input type="hidden" name="business_type" value="{{ old('business_type', $store->business_type ?? 'store') }}">
        <div style="margin-bottom:12px;">
            <div style="font-weight:600; margin-bottom:10px;">URL de tu tienda</div>
            <input type="text" value="{{ url('/' . $store->slug) }}" readonly style="background:#f9fafb; color:#374151;">
            <div style="margin-top:8px; color:#6b7280; font-size:13px;">El slug no se puede cambiar desde esta pantalla.</div>
        </div>
        <input type="text" name="whatsapp" value="{{ old('whatsapp', $store->whatsapp) }}" placeholder="WhatsApp">
        <input type="text" name="location" value="{{ old('location', $store->location) }}" placeholder="Ubicacion o direccion (opcional)">
        <label class="field-label" for="business_hours">Horario de atencion</label>
        <textarea id="business_hours" name="business_hours" placeholder="Ej: Lunes a viernes 8:00 AM - 6:00 PM">{{ old('business_hours', $store->business_hours) }}</textarea>

        @include('admin.stores.partials.theme-fields', ['store' => $store])

        <input type="url" name="instagram_url" value="{{ old('instagram_url', $store->instagram_url) }}" placeholder="Instagram URL">
        <input type="url" name="facebook_url" value="{{ old('facebook_url', $store->facebook_url) }}" placeholder="Facebook URL">
        <input type="url" name="tiktok_url" value="{{ old('tiktok_url', $store->tiktok_url) }}" placeholder="TikTok URL">
        <label class="field-label" for="shop_copy">Quienes somos</label>
        <textarea id="shop_copy" name="shop_copy" placeholder="Cuenta brevemente que hace la tienda y que la diferencia">{{ old('shop_copy', $store->shop_copy) }}</textarea>
        <label class="field-label" for="mission">Mision</label>
        <textarea id="mission" name="mission" placeholder="Que hace hoy la tienda y para que existe">{{ old('mission', $store->mission) }}</textarea>
        <label class="field-label" for="vision">Vision</label>
        <textarea id="vision" name="vision" placeholder="Hacia donde quiere crecer la tienda">{{ old('vision', $store->vision) }}</textarea>
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
        <label class="field-label" for="store_logo_image">Sube el logo de tu tienda</label>
        <input id="store_logo_image" type="file" name="logo_image" accept="image/*" data-optimize-image data-max-width="720" data-max-height="720" data-quality="0.86" data-output="webp">

        @if ($store->cover_image)
            <img src="{{ asset('storage/' . $store->cover_image) }}" alt="{{ $store->name }}" style="width:100%; max-width:420px; max-height:220px; object-fit:cover; border-radius:10px; display:block; margin-bottom:12px;">
        @endif

        <label class="field-label" for="store_cover_image">Sube la portada de tu tienda</label>
        <input id="store_cover_image" type="file" name="cover_image" accept="image/*" data-optimize-image data-max-width="1920" data-max-height="1080" data-quality="0.82" data-output="webp">

        <button class="btn">Guardar cambios</button>
    </form>
</div>

@endsection
