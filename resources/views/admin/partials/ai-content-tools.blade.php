@php
    $aiStore = $aiStore ?? null;
    $aiProduct = $aiProduct ?? null;
    $aiContext = $aiContext ?? 'product';
    $aiCreditService = app(\App\Services\AiCreditService::class);
    $aiCreditBalance = $aiStore?->allowsAiContent() ? $aiCreditService->balance($aiStore) : 0;
@endphp

@if($aiStore?->allowsAiContent())
    <div
        class="ai-assistant-panel"
        data-ai-panel
        data-ai-context="{{ $aiContext }}"
        data-ai-endpoint="{{ route('admin.ai.content') }}"
        data-ai-image-endpoint="{{ route('admin.ai.images') }}"
        data-store-id="{{ $aiStore->id }}"
        @if($aiProduct) data-product-id="{{ $aiProduct->id }}" @endif
    >
        <div class="ai-assistant-panel__head">
            <div>
                <h3>Asistente IA</h3>
                <p>
                    @if($aiContext === 'announcements')
                        Genera avisos promocionales breves para la franja superior.
                    @elseif($aiContext === 'store_images')
                        Genera una portada profesional para la tienda y revisala antes de guardar.
                    @else
                        Genera textos e imagenes para el producto y revisalos antes de guardar.
                    @endif
                </p>
            </div>
        </div>

        <div class="ai-assistant-actions">
            @if($aiContext === 'announcements')
                <button type="button" class="btn btn-secondary" data-ai-type="announcement">Crear avisos</button>
            @elseif($aiContext === 'store_images')
                <button type="button" class="btn btn-secondary" data-ai-image-type="store_cover_image">Crear portada</button>
            @else
                <button type="button" class="btn btn-secondary" data-ai-type="product_name">Mejorar nombre</button>
                <button type="button" class="btn btn-secondary" data-ai-type="product_description">Generar descripcion</button>
                <button type="button" class="btn btn-secondary" data-ai-type="product_features">Generar caracteristicas</button>
                @if($aiStore->allowsCustomProductBadges())
                    <button type="button" class="btn btn-secondary" data-ai-type="product_badges">Sugerir etiquetas</button>
                @endif
                <button type="button" class="btn btn-secondary" data-ai-image-type="product_image">Crear imagen ecommerce</button>
            @endif
        </div>

        <p class="ai-assistant-status" data-ai-status>Disponible solo en plan Premium.</p>
        <div class="ai-assistant-credits">
            <strong><span data-ai-credit-balance>{{ $aiCreditBalance }}</span> creditos IA</strong>
            <span>Premium incluye {{ \App\Services\AiCreditService::MONTHLY_PREMIUM_CREDITS }} al mes.</span>
        </div>
        <div class="ai-assistant-packages" aria-label="Paquetes extra de creditos IA">
            @foreach(\App\Services\AiCreditService::PACKAGES as $package)
                <span>{{ $package['credits'] }} creditos: ${{ number_format($package['price_cop'], 0, ',', '.') }}</span>
            @endforeach
        </div>
        <div class="ai-assistant-preview" data-ai-preview hidden></div>
    </div>
@endif
