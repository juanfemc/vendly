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
    @php
        $selectedBrandColor = old('brand_color', $store->brand_color ?: '#111111');
        $brandPalette = ['#111111', '#ff6a00', '#4f46e5', '#0f766e', '#be123c', '#ca8a04', '#7c3aed', '#1d4ed8'];
    @endphp

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

        <div style="margin-bottom:12px;">
            <div style="font-weight:600; margin-bottom:10px;">Color principal</div>
            <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:12px;">
                @foreach ($brandPalette as $color)
                    <button
                        type="button"
                        class="brand-swatch"
                        data-color="{{ $color }}"
                        aria-label="Elegir color {{ $color }}"
                        style="width:34px; height:34px; border-radius:999px; border:{{ strtolower($selectedBrandColor) === strtolower($color) ? '3px solid #111827' : '1px solid #d1d5db' }}; background:{{ $color }}; cursor:pointer;"
                    ></button>
                @endforeach
            </div>
            <input id="brand_color" type="text" name="brand_color" value="{{ $selectedBrandColor }}" placeholder="Color principal (#111111)">
        </div>

        <input type="url" name="instagram_url" value="{{ old('instagram_url', $store->instagram_url) }}" placeholder="Instagram URL">
        <input type="url" name="facebook_url" value="{{ old('facebook_url', $store->facebook_url) }}" placeholder="Facebook URL">
        <input type="url" name="tiktok_url" value="{{ old('tiktok_url', $store->tiktok_url) }}" placeholder="TikTok URL">
        <textarea name="shop_copy" placeholder="Texto corto de la portada">{{ old('shop_copy', $store->shop_copy) }}</textarea>
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

<script>
    (() => {
        const input = document.getElementById('brand_color');
        const swatches = document.querySelectorAll('.brand-swatch');

        const paintSelection = (value) => {
            swatches.forEach((swatch) => {
                swatch.style.border = swatch.dataset.color.toLowerCase() === value.toLowerCase()
                    ? '3px solid #111827'
                    : '1px solid #d1d5db';
            });
        };

        swatches.forEach((swatch) => {
            swatch.addEventListener('click', () => {
                input.value = swatch.dataset.color;
                paintSelection(swatch.dataset.color);
            });
        });

        input.addEventListener('input', () => paintSelection(input.value));
    })();
</script>
@endsection
