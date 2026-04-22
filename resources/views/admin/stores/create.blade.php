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
        <input type="text" name="slug" value="{{ old('slug') }}" placeholder="Slug (ej: mitienda)">
        <input type="text" name="whatsapp" value="{{ old('whatsapp') }}" placeholder="WhatsApp">
        <textarea name="shop_copy" placeholder="Texto corto del shop header">{{ old('shop_copy') }}</textarea>
        <label class="field-label" for="store_cover_image">Sube la portada de la tienda</label>
        <input id="store_cover_image" type="file" name="cover_image" accept="image/*" data-optimize-image data-max-width="1920" data-max-height="1080" data-quality="0.82" data-output="webp">

        <button class="btn">Crear</button>
    </form>
</div>
@endsection
