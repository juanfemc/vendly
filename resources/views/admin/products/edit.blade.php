@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Editar producto</h2>
    <a href="/admin/categories" class="btn btn-secondary">Gestionar categorias</a>
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
        $selectedCategory = old('category', $product->category);
        $usesCustomCategory = $selectedCategory && ! in_array($selectedCategory, $categoryOptions, true);
        $featuresEditorValue = old('features') !== null
            ? e(strip_tags(old('features')))
            : ($product->features ?? '');
    @endphp

    <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
 
        @if(auth()->user()->isAdmin())
            <label class="field-label" for="store_id">Tienda del producto</label>
            <select name="store_id" id="store_id" required>
                <option value="">Selecciona tienda</option>
                @foreach (($stores ?? collect()) as $storeOption)
                    <option value="{{ $storeOption->id }}" @selected(old('store_id', $product->store_id) == $storeOption->id)>{{ $storeOption->name }}</option>
                @endforeach
            </select>
        @endif
        <input type="text" name="name" value="{{ old('name', $product->name) }}" placeholder="Nombre">
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
        <input type="text" name="material" value="{{ old('material', $product->material) }}" placeholder="Material (ej: Algodon, Cuero, Acero)">
        <input type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}" placeholder="Precio">
        <textarea name="description" class="long-textarea" rows="8" placeholder="Descripcion larga del producto">{{ old('description', $product->description) }}</textarea>
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
            <textarea name="features" data-rich-input hidden>{{ old('features', $product->features) }}</textarea>
        </div>
        <label class="field-label" for="sizes">Tallas disponibles</label>
        <input id="sizes" type="text" name="sizes" value="{{ old('sizes', implode(', ', $product->sizes ?? [])) }}" placeholder="Ej: S, M, L, XL">
        <label class="field-label" for="colors">Colores disponibles</label>
        <input id="colors" type="text" name="colors" value="{{ old('colors', implode(', ', $product->colors ?? [])) }}" placeholder="Ej: Negro, Blanco, Rojo">

        @if ($product->image)
            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="thumb" style="width:140px; height:140px;">
        @endif

        @if (! empty($product->images))
            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:12px;">
                @foreach ($product->images as $productImage)
                    <label style="display:grid; gap:6px; width:96px; font-size:12px; color:#374151;">
                        <img src="{{ asset('storage/' . $productImage) }}" alt="{{ $product->name }}" class="thumb" style="width:86px; height:86px; margin-bottom:0;">
                        <span style="display:flex; align-items:center; gap:6px;">
                            <input type="checkbox" name="remove_images[]" value="{{ $productImage }}" style="width:auto; margin:0;">
                            Quitar
                        </span>
                    </label>
                @endforeach
            </div>
        @endif

        <label class="field-label" for="product_image">Sube una nueva imagen del producto</label>
        <input id="product_image" type="file" name="image" accept="image/*" data-optimize-image data-max-width="1600" data-max-height="1600" data-quality="0.82" data-output="webp">
        <label class="field-label" for="product_images">Agrega imagenes adicionales del producto</label>
        <input id="product_images" type="file" name="images[]" accept="image/*" multiple data-optimize-image data-max-width="1600" data-max-height="1600" data-quality="0.82" data-output="webp" data-product-image-preview data-preview-target="product_images_preview">
        <div id="product_images_preview" class="product-image-preview" hidden></div>

        <button type="submit" class="btn">Actualizar</button>
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
</script>
@endsection
