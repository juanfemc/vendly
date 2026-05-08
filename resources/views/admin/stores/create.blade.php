@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Crear tienda</h2>
</div>

@if ($errors->any())
    <div class="flash error">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="list-card">
    <form method="POST" action="/admin/stores" enctype="multipart/form-data">
        @csrf

        <select name="user_id" required>
            <option value="">Selecciona usuario de tienda</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} - {{ $user->email }}</option>
            @endforeach
        </select>
        <input type="text" name="name" value="{{ old('name') }}" placeholder="Nombre tienda">
        <select name="business_type" required>
            @foreach (\App\Models\Store::businessTypeOptions() as $value => $label)
                <option value="{{ $value }}" @selected(old('business_type', 'store') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <label class="field-label" for="plan">Plan</label>
        <select id="plan" name="plan" required>
            @foreach (\App\Models\Store::planOptions() as $value => $label)
                <option value="{{ $value }}" @selected(old('plan', \App\Models\Store::PLAN_BASIC) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <input type="text" name="slug" value="{{ old('slug') }}" placeholder="Slug (ej: mitienda)">
        <label class="field-label" for="subdomain">Subdominio Pro/Premium</label>
        <input id="subdomain" type="text" name="subdomain" value="{{ old('subdomain') }}" placeholder="Subdominio (ej: mitienda)">
        <input type="text" name="whatsapp" value="{{ old('whatsapp') }}" placeholder="WhatsApp">
        <input type="text" name="location" value="{{ old('location') }}" placeholder="Ubicacion o direccion (opcional)">
        <label class="field-label" for="business_hours">Horario de atencion</label>
        <textarea id="business_hours" name="business_hours" placeholder="Ej: Lunes a viernes 8:00 AM - 6:00 PM">{{ old('business_hours') }}</textarea>
        @include('admin.stores.partials.theme-fields')
        <label class="field-label" for="shop_copy">Quienes somos</label>
        <textarea id="shop_copy" name="shop_copy" placeholder="Cuenta brevemente que hace la tienda y que la diferencia">{{ old('shop_copy') }}</textarea>
        <label class="field-label" for="mission">Mision</label>
        <textarea id="mission" name="mission" placeholder="Que hace hoy la tienda y para que existe">{{ old('mission') }}</textarea>
        <label class="field-label" for="vision">Vision</label>
        <textarea id="vision" name="vision" placeholder="Hacia donde quiere crecer la tienda">{{ old('vision') }}</textarea>
        <label class="field-label" for="responsive_product_columns">Columnas de productos en responsive</label>
        <select id="responsive_product_columns" name="responsive_product_columns">
            <option value="1" @selected((int) old('responsive_product_columns', 2) === 1)>1 columna</option>
            <option value="2" @selected((int) old('responsive_product_columns', 2) === 2)>2 columnas</option>
            <option value="3" @selected((int) old('responsive_product_columns', 2) === 3)>3 columnas</option>
        </select>
        <label class="field-label" for="show_hero_products_action">Boton y texto sobre la portada</label>
        <select id="show_hero_products_action" name="show_hero_products_action">
            <option value="1" @selected((bool) old('show_hero_products_action', false))>Habilitado</option>
            <option value="0" @selected(! (bool) old('show_hero_products_action', false))>Deshabilitado</option>
        </select>
        <label class="field-label" for="store_cover_image">Sube la portada de la tienda</label>
        <input id="store_cover_image" type="file" name="cover_image" accept="image/*" data-optimize-image data-max-width="1920" data-max-height="1080" data-quality="0.82" data-output="webp">

        <button class="btn">Crear</button>
    </form>
</div>
@endsection
