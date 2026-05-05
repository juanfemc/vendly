@php
    $store = $store ?? null;
    $selectedBrandColor = old('brand_color', $store?->brand_color ?: '#111111');
    $selectedBackgroundColor = old('background_color', $store?->background_color ?: '#ffffff');
    $selectedTextColor = old('text_color', $store?->text_color ?: '#171717');
    $selectedFontFamily = old('font_family', $store?->font_family ?: 'system');
@endphp

<div style="margin-bottom:12px;">
    <div style="font-weight:600; margin-bottom:10px;">Tema de la tienda</div>

    <label class="field-label" for="brand_color">Color principal</label>
    <input id="brand_color" type="text" name="brand_color" value="{{ $selectedBrandColor }}" placeholder="Color principal (#111111)">

    <label class="field-label" for="background_color">Color de fondo</label>
    <input id="background_color" type="text" name="background_color" value="{{ $selectedBackgroundColor }}" placeholder="Color de fondo (#ffffff)">

    <label class="field-label" for="text_color">Color de letras</label>
    <input id="text_color" type="text" name="text_color" value="{{ $selectedTextColor }}" placeholder="Color de letras (#171717)">

    <label class="field-label" for="font_family">Fuente</label>
    <select id="font_family" name="font_family">
        @foreach(\App\Models\Store::fontFamilyOptions() as $value => $label)
            <option value="{{ $value }}" @selected($selectedFontFamily === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>
