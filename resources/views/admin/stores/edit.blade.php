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
        <label class="field-label" for="plan">Plan</label>
        <select id="plan" name="plan" required>
            @foreach (\App\Models\Store::planOptions() as $value => $label)
                <option value="{{ $value }}" @selected(old('plan', $store->plan ?? \App\Models\Store::PLAN_PRO) === $value)>{{ $label }}</option>
            @endforeach
        </select>

        <input type="text" name="slug" value="{{ old('slug', $store->slug) }}" placeholder="Slug (ej: mitienda)">
        <label class="field-label" for="subdomain">Subdominio Pro/Premium</label>
        <input id="subdomain" type="text" name="subdomain" value="{{ old('subdomain', $store->subdomain) }}" placeholder="Subdominio (ej: mitienda)">
        <label class="field-label" for="custom_domain">Dominio personalizado Premium</label>
        <input id="custom_domain" type="text" name="custom_domain" value="{{ old('custom_domain', $store->custom_domain) }}" placeholder="www.tudominio.com">
        <label class="field-label" for="custom_domain_status">Estado del dominio</label>
        <select id="custom_domain_status" name="custom_domain_status">
            <option value="pending" @selected(old('custom_domain_status', $store->custom_domain_status ?? 'pending') === 'pending')>Pendiente</option>
            <option value="verified" @selected(old('custom_domain_status', $store->custom_domain_status) === 'verified')>Verificado</option>
            <option value="failed" @selected(old('custom_domain_status', $store->custom_domain_status) === 'failed')>Fallido</option>
        </select>
        @if(\App\Models\Store::supportsMetaPixelColumn())
            <label class="field-label" for="meta_pixel_id">Meta Pixel ID Premium</label>
            <input id="meta_pixel_id" type="text" inputmode="numeric" pattern="[0-9]*" name="meta_pixel_id" value="{{ old('meta_pixel_id', $store->meta_pixel_id) }}" maxlength="50" placeholder="Ej: 123456789012345">
        @endif
        <input type="text" name="whatsapp" value="{{ old('whatsapp', $store->whatsapp) }}" placeholder="WhatsApp">
        <input type="text" name="location" value="{{ old('location', $store->location) }}" placeholder="Ubicacion o direccion (opcional)">
        <label class="field-label" for="business_hours">Horario de atencion</label>
        <textarea id="business_hours" name="business_hours" placeholder="Ej: Lunes a viernes 8:00 AM - 6:00 PM">{{ old('business_hours', $store->business_hours) }}</textarea>
        @include('admin.stores.partials.theme-fields', ['store' => $store])
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
