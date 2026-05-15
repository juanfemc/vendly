@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Crear producto</h2>
    @if(auth()->user()->isAdmin() || ($store?->allowsCategories() ?? true))
        <a href="/admin/categories" class="btn btn-secondary">Gestionar categorias</a>
    @endif
</div>

@if ($errors->any())
    <div class="flash error">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="list-card">
    @php
        $selectedCategory = old('category');
        $usesCustomCategory = $selectedCategory && ! in_array($selectedCategory, $categoryOptions, true);
        $featuresEditorValue = old('features') !== null ? e(strip_tags(old('features'))) : '';
    @endphp

    <form method="POST" action="/admin/products" enctype="multipart/form-data">
        @csrf

        @if(auth()->user()->isAdmin())
            <label class="field-label" for="store_id">Tienda del producto</label>
            <select name="store_id" id="store_id" required>
                <option value="">Selecciona tienda</option>
                @foreach (($stores ?? collect()) as $storeOption)
                    <option value="{{ $storeOption->id }}" @selected(old('store_id') == $storeOption->id)>{{ $storeOption->name }}</option>
                @endforeach
            </select>
        @endif
        <input type="text" name="name" value="{{ old('name') }}" placeholder="Nombre" required>
        @if(auth()->user()->isAdmin() || ($store?->allowsCategories() ?? true))
            <select name="category" id="category_select">
                <option value="">Selecciona categoria</option>
                @foreach ($categoryOptions as $categoryOption)
                    <option value="{{ $categoryOption }}" @selected($selectedCategory === $categoryOption)>{{ $categoryOption }}</option>
                @endforeach
                <option value="__custom__" @selected($usesCustomCategory)>Otra categoria</option>
            </select>
            <input
                type="text"
                name="custom_category"
                id="custom_category"
                value="{{ $usesCustomCategory ? $selectedCategory : '' }}"
                placeholder="Escribe otra categoria"
                style="{{ $usesCustomCategory ? '' : 'display:none;' }}"
            >
        @else
            <div class="flash" style="margin-bottom:12px;">El plan {{ $store->planLabel() }} no incluye categorias. Este producto quedara sin categoria.</div>
        @endif
        <input type="text" name="material" value="{{ old('material') }}" placeholder="Material (ej: Algodon, Cuero, Acero)">
        <input type="number" step="0.01" name="price" value="{{ old('price') }}" placeholder="Precio" required>
        <label style="display:flex; align-items:center; gap:8px; margin:0 0 12px; color:#374151; font-size:14px;">
            <input type="checkbox" name="has_offer" value="1" @checked(old('has_offer')) style="width:auto; margin:0;" data-offer-toggle>
            Mostrar etiqueta de oferta
        </label>
        <div data-offer-pricing>
            <label class="field-label" for="offer_original_price">Precio antes de oferta</label>
            <input id="offer_original_price" type="number" step="0.01" name="offer_original_price" value="{{ old('offer_original_price') }}" placeholder="Precio anterior">
        </div>
        <p class="settings-help" style="margin-top:-6px;">El precio actual queda como precio de oferta. La etiqueta se muestra en la tienda solo si el plan es Premium.</p>
        @if(! ($store?->isReservationStore() ?? false))
            <label class="field-label" for="stock_quantity">Stock disponible</label>
            <input id="stock_quantity" type="number" name="stock_quantity" min="0" step="1" value="{{ old('stock_quantity') }}" placeholder="Cantidad disponible (vacio = ilimitado)">
            <label style="display:flex; align-items:center; gap:8px; margin:0 0 12px; color:#374151; font-size:14px;">
                <input type="checkbox" name="is_sold_out" value="1" @checked(old('is_sold_out')) style="width:auto; margin:0;">
                Marcar como agotado
            </label>
        @endif
        <textarea name="description" class="long-textarea" rows="8" placeholder="Descripcion larga del producto">{{ old('description') }}</textarea>
        <label class="field-label" for="features_editor">Caracteristicas del producto</label>
        <div class="rich-editor" data-rich-editor>
            <div class="rich-toolbar" aria-label="Herramientas de texto">
                <button type="button" data-command="bold"><strong>B</strong></button>
                <button type="button" data-command="italic"><em>I</em></button>
                <button type="button" data-command="underline"><u>U</u></button>
                <button type="button" data-command="insertUnorderedList">Lista</button>
                <button type="button" data-command="insertOrderedList">1. Lista</button>
            </div>
            <div id="features_editor" class="rich-content" contenteditable="true" data-rich-content>{!! $featuresEditorValue !!}</div>
            <textarea name="features" data-rich-input hidden>{{ old('features') }}</textarea>
        </div>
        <label class="field-label" for="sizes">Tallas disponibles</label>
        <input id="sizes" type="text" name="sizes" value="{{ old('sizes') }}" placeholder="Ej: S, M, L, XL">
        <label class="field-label" for="colors">Colores disponibles</label>
        <input id="colors" type="text" name="colors" value="{{ old('colors') }}" placeholder="Ej: Negro, Blanco, Rojo">
        <label class="field-label" for="product_image">Sube la imagen del producto</label>
        <input id="product_image" type="file" name="image" accept="image/*" data-optimize-image data-max-width="1600" data-max-height="1600" data-quality="0.82" data-output="webp">
        @if(auth()->user()->isAdmin() || ($store?->allowsProductGallery() ?? true))
            <label class="field-label" for="product_images">Sube imagenes adicionales del producto</label>
            <input id="product_images" type="file" name="images[]" accept="image/*" multiple data-optimize-image data-max-width="1600" data-max-height="1600" data-quality="0.82" data-output="webp" data-product-image-preview data-preview-target="product_images_preview">
            <div id="product_images_preview" class="product-image-preview" hidden></div>
        @else
            <div class="flash" style="margin-bottom:12px;">La galeria de imagenes por producto esta disponible desde el plan Pro.</div>
        @endif
 
        <button class="btn">Guardar</button>
    </form>
</div>

<script>
    (() => {
        const categorySelect = document.getElementById('category_select');
        const customCategory = document.getElementById('custom_category');

        if (!categorySelect || !customCategory) {
            return;
        }

        const syncCategoryField = () => {
            const isCustom = categorySelect.value === '__custom__';
            customCategory.style.display = isCustom ? 'block' : 'none';

            if (isCustom) {
                customCategory.focus();
                categorySelect.name = 'category_selector';
                customCategory.name = 'category';
                return;
            }

            categorySelect.name = 'category';
            customCategory.name = 'custom_category';
        };

        categorySelect.addEventListener('change', syncCategoryField);
        syncCategoryField();
    })();

    (() => {
        document.querySelectorAll('[data-rich-editor]').forEach((editor) => {
            const content = editor.querySelector('[data-rich-content]');
            const input = editor.querySelector('[data-rich-input]');

            if (!content || !input) {
                return;
            }

            editor.querySelectorAll('[data-command]').forEach((button) => {
                button.addEventListener('click', () => {
                    content.focus();
                    document.execCommand(button.dataset.command, false, null);
                    input.value = content.innerHTML;
                });
            });

            content.addEventListener('input', () => {
                input.value = content.innerHTML;
            });

            content.closest('form')?.addEventListener('submit', () => {
                input.value = content.innerHTML;
            });
        });
    })();

    (() => {
        const toggle = document.querySelector('[data-offer-toggle]');
        const pricing = document.querySelector('[data-offer-pricing]');

        if (!toggle || !pricing) {
            return;
        }

        const syncOfferPricing = () => {
            pricing.hidden = !toggle.checked;
        };

        toggle.addEventListener('change', syncOfferPricing);
        syncOfferPricing();
    })();
</script>
@endsection
