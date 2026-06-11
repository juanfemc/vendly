@extends('layouts.admin')

@section('content')
<div class="header">
    <h2>Editar producto</h2>
    @if(auth()->user()->isAdmin() || ($product->store?->allowsCategories() ?? true))
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

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

<div class="list-card">
    @php
        $selectedCategory = old('category', $product->category);
        $usesCustomCategory = $selectedCategory && ! in_array($selectedCategory, $categoryOptions, true);
        $descriptionEditorValue = old('description') !== null
            ? \App\Support\ProductText::plain(old('description'))
            : \App\Support\ProductText::plain($product->description);
        $featuresEditorValue = old('features') !== null
            ? \App\Support\ProductText::rich(old('features'))
            : \App\Support\ProductText::rich($product->features);
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
        @include('admin.partials.ai-content-tools', ['aiStore' => $product->store, 'aiProduct' => $product, 'aiContext' => 'product'])
        <input type="text" name="name" value="{{ old('name', $product->name) }}" placeholder="Nombre">
        @if(auth()->user()->isAdmin() || ($product->store?->allowsCategories() ?? true))
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
            <div class="flash" style="margin-bottom:12px;">El plan {{ $product->store->planLabel() }} no incluye categorias. Al guardar, el producto quedara sin categoria.</div>
        @endif
        <input type="text" name="material" value="{{ old('material', $product->material) }}" placeholder="Material (ej: Algodon, Cuero, Acero)">
        <input type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}" placeholder="Precio">
        @if($product->store?->allowsOfferBadges())
            <label style="display:flex; align-items:center; gap:8px; margin:0 0 12px; color:#374151; font-size:14px;">
                <input type="checkbox" name="has_offer" value="1" @checked(old('has_offer', $product->has_offer)) style="width:auto; margin:0;" data-offer-toggle>
                Mostrar etiqueta de oferta
            </label>
            <div data-offer-pricing>
                <label class="field-label" for="offer_original_price">Precio antes de oferta</label>
                <input id="offer_original_price" type="number" step="0.01" name="offer_original_price" value="{{ old('offer_original_price', $product->offer_original_price) }}" placeholder="Precio anterior">
            </div>
            <p class="settings-help" style="margin-top:-6px;">El precio actual queda como precio de oferta.</p>
        @endif
        @if($product->store?->allowsCustomProductBadges())
            <label class="field-label" for="custom_badges">Etiquetas personalizadas</label>
            <input id="custom_badges" type="text" name="custom_badges" value="{{ old('custom_badges', implode(', ', $product->customBadges())) }}" maxlength="255" placeholder="Ej: Nuevo, Mas vendido, Ultimas unidades">
            <p class="settings-help" style="margin-top:-6px;">Se muestran hasta 3 etiquetas cortas, separadas por coma.</p>
        @endif
        @if(! ($product->store?->isReservationStore() ?? false))
            <label class="field-label" for="stock_quantity">Stock disponible</label>
            <input id="stock_quantity" type="number" name="stock_quantity" min="0" step="1" value="{{ old('stock_quantity', $product->stock_quantity) }}" placeholder="Cantidad disponible (vacio = ilimitado)">
            <label style="display:flex; align-items:center; gap:8px; margin:0 0 12px; color:#374151; font-size:14px;">
                <input type="checkbox" name="is_sold_out" value="1" @checked(old('is_sold_out', $product->is_sold_out)) style="width:auto; margin:0;">
                Marcar como agotado
            </label>
        @endif
        <textarea name="description" class="long-textarea" rows="8" placeholder="Descripcion larga del producto">{{ $descriptionEditorValue }}</textarea>
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
            <textarea name="features" data-rich-input hidden>{{ old('features') !== null ? \App\Support\ProductText::rich(old('features')) : \App\Support\ProductText::rich($product->features) }}</textarea>
        </div>
        <label class="field-label" for="sizes">Tallas disponibles</label>
        <input id="sizes" type="text" name="sizes" value="{{ old('sizes', implode(', ', $product->sizes ?? [])) }}" placeholder="Ej: S, M, L, XL">
        <label class="field-label" for="colors">Colores disponibles</label>
        <input id="colors" type="text" name="colors" value="{{ old('colors', implode(', ', $product->colors ?? [])) }}" placeholder="Ej: Negro, Blanco, Rojo">

        @if ($product->image)
            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="thumb" style="width:140px; height:140px;">
        @endif

        @if (($product->store?->allowsProductGallery() ?? true) && ! empty($product->images))
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
        <input id="product_image" type="file" name="image" accept="image/*" data-optimize-image data-max-width="1600" data-max-height="1600" data-quality="0.82" data-output="webp" data-max-size="2097152">
        @if(auth()->user()->isAdmin() || ($product->store?->allowsProductGallery() ?? true))
            <label class="field-label" for="product_images">Agrega imagenes adicionales del producto</label>
            <input id="product_images" type="file" name="images[]" accept="image/*" multiple data-optimize-image data-max-width="1600" data-max-height="1600" data-quality="0.82" data-output="webp" data-max-size="2097152" data-max-total-size="8388608" data-product-image-preview data-preview-target="product_images_preview">
            <div id="product_images_preview" class="product-image-preview" hidden></div>
        @else
            <div class="flash" style="margin-bottom:12px;">La galeria de imagenes por producto esta disponible desde el plan Pro.</div>
        @endif

        <button type="submit" class="btn">Actualizar</button>
    </form>
</div>

@if(($product->store?->allowsProductReviews() ?? false))
    <div class="list-card product-review-panel">
        <div class="product-review-panel__head">
            <div>
                <span class="product-review-panel__eyebrow">Moderacion</span>
                <h3>Resenas del producto</h3>
                <p>Aprueba las resenas antes de que aparezcan en la tienda.</p>
            </div>
            <div class="product-review-panel__summary">
                <strong>{{ ($productReviews ?? collect())->where('is_approved', false)->count() }}</strong>
                <span>Pendientes</span>
            </div>
        </div>

        <div class="product-review-admin-list">
        @forelse(($productReviews ?? collect()) as $review)
            <article class="product-review-admin-card {{ $review->is_approved ? 'is-approved' : 'is-pending' }}">
                <div class="resource-card__main">
                    <div class="product-review-admin-card__head">
                        <div>
                            <h4>{{ $review->name }}</h4>
                            <p>{{ number_format((float) $review->rating, 1) }} estrellas</p>
                        </div>
                        <div class="resource-badges">
                            @if($review->is_approved)
                                <span class="resource-badge resource-badge--active">Aprobada</span>
                            @else
                                <span class="resource-badge resource-badge--warning">Pendiente</span>
                            @endif
                        </div>
                    </div>
                    @if($review->comment)
                        <p class="product-review-admin-card__comment">{{ $review->comment }}</p>
                    @else
                        <p class="product-review-admin-card__comment is-empty">Sin comentario adicional.</p>
                    @endif
                </div>

                <div class="resource-actions">
                    @unless($review->is_approved)
                        <form method="POST" action="{{ route('admin.product-reviews.approve', $review) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-success">Aprobar</button>
                        </form>
                    @endunless
                    <form method="POST" action="{{ route('admin.product-reviews.destroy', $review) }}" data-confirm-delete data-confirm-message="Seguro que quieres eliminar esta resena?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="panel-empty" style="margin:0;">
                <h3>No hay resenas todavia</h3>
                <p>Cuando un cliente escriba una resena, aparecera aqui para aprobarla.</p>
            </div>
        @endforelse
        </div>
    </div>
@endif

<style>
    .product-review-panel {
        margin-top: 18px;
        padding: 0;
        overflow: hidden;
    }

    .product-review-panel__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        padding: 22px;
        border-bottom: 1px solid #e5e7eb;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    }

    .product-review-panel__eyebrow {
        display: inline-flex;
        margin-bottom: 8px;
        color: #4f46e5;
        font-size: 12px;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .product-review-panel__head h3 {
        margin: 0;
        color: #111827;
        font-size: 22px;
    }

    .product-review-panel__head p {
        margin: 6px 0 0;
        color: #6b7280;
        line-height: 1.5;
    }

    .product-review-panel__summary {
        min-width: 112px;
        min-height: 82px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        display: grid;
        place-items: center;
        background: #ffffff;
        color: #111827;
        text-align: center;
    }

    .product-review-panel__summary strong {
        font-size: 28px;
        line-height: 1;
    }

    .product-review-panel__summary span {
        color: #6b7280;
        font-size: 12px;
        font-weight: 800;
    }

    .product-review-admin-list {
        display: grid;
        gap: 12px;
        padding: 18px;
    }

    .product-review-admin-card {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 18px;
        align-items: start;
        border: 1px solid #e5e7eb;
        border-left: 4px solid #f59e0b;
        border-radius: 14px;
        padding: 16px;
        background: #ffffff;
    }

    .product-review-admin-card.is-approved {
        border-left-color: #16a34a;
    }

    .product-review-admin-card__head {
        display: flex;
        justify-content: space-between;
        gap: 14px;
    }

    .product-review-admin-card h4 {
        margin: 0;
        color: #111827;
        font-size: 16px;
    }

    .product-review-admin-card__head p {
        margin: 5px 0 0;
        color: #f59e0b;
        font-size: 13px;
        font-weight: 900;
    }

    .product-review-admin-card__comment {
        margin: 12px 0 0;
        color: #4b5563;
        line-height: 1.65;
    }

    .product-review-admin-card__comment.is-empty {
        color: #9ca3af;
        font-style: italic;
    }

    @media (max-width: 900px) {
        .product-review-panel__head,
        .product-review-admin-card,
        .product-review-admin-card__head {
            grid-template-columns: 1fr;
            display: grid;
        }

        .product-review-panel__summary {
            width: 100%;
            min-height: 68px;
        }
    }
</style>

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
@if($product->store?->allowsAiContent())
    <script src="{{ asset('js/admin-ai-content.js') }}?v={{ filemtime(public_path('js/admin-ai-content.js')) }}" defer></script>
@endif
@endsection
