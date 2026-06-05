@extends('layouts.admin')

@php
    $templates = \App\Support\StoreTemplateCatalog::all();
    $selectedTemplate = '';
@endphp

@section('content')
<div class="header">
    <div>
        <h2>Crear cliente y tienda</h2>
        <p class="resource-subtitle">Alta rapida para dejar una tienda lista en un solo flujo.</p>
    </div>
    <a href="/admin/stores" class="btn btn-secondary">Volver a tiendas</a>
</div>

@if ($errors->any())
    <div class="flash error">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<style>
    .onboarding-shell {
        display: grid;
        grid-template-columns: minmax(280px, .78fr) minmax(0, 1.22fr);
        gap: 22px;
        align-items: start;
    }

    .onboarding-card {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 18px 44px rgba(15, 23, 42, .06);
        overflow: hidden;
    }

    .onboarding-card__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        padding: 22px 22px 16px;
        border-bottom: 1px solid #eef0f3;
    }

    .onboarding-card__head h3 {
        margin: 0;
        color: #111827;
        font-size: 18px;
        line-height: 1.2;
    }

    .onboarding-card__head p {
        margin: 7px 0 0;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.45;
    }

    .onboarding-step {
        display: grid;
        place-items: center;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #111827;
        color: #ffffff;
        font-size: 13px;
        font-weight: 900;
        flex: 0 0 auto;
    }

    .onboarding-card__body {
        display: grid;
        gap: 14px;
        padding: 22px;
    }

    .onboarding-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .onboarding-field {
        display: grid;
        gap: 7px;
    }

    .onboarding-field--full {
        grid-column: 1 / -1;
    }

    .onboarding-field label,
    .template-picker-title {
        color: #374151;
        font-size: 13px;
        font-weight: 800;
    }

    .onboarding-field input,
    .onboarding-field select,
    .onboarding-field textarea {
        width: 100%;
        margin: 0;
    }

    .onboarding-field small {
        color: #6b7280;
        font-size: 12px;
        line-height: 1.4;
    }

    .template-picker {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .template-option {
        position: relative;
        display: grid;
        gap: 9px;
        min-height: 118px;
        padding: 14px;
        border: 1px solid #d9dde3;
        border-radius: 14px;
        background: #ffffff;
        color: #111827;
        cursor: pointer;
    }

    .template-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .template-option:has(input:checked) {
        border-color: #111827;
        box-shadow: inset 0 0 0 1px #111827, 0 12px 28px rgba(17, 24, 39, .08);
    }

    .template-option strong {
        font-size: 15px;
    }

    .template-option span {
        color: #6b7280;
        font-size: 12px;
        line-height: 1.45;
    }

    .onboarding-actions {
        position: sticky;
        bottom: 0;
        z-index: 3;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-top: 22px;
        padding: 18px 22px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: rgba(255, 255, 255, .94);
        backdrop-filter: blur(14px);
        box-shadow: 0 -12px 34px rgba(15, 23, 42, .08);
    }

    .onboarding-actions p {
        margin: 0;
        color: #6b7280;
        font-size: 13px;
    }

    .onboarding-actions strong {
        display: block;
        margin-bottom: 3px;
        color: #111827;
        font-size: 14px;
    }

    @media (max-width: 980px) {
        .onboarding-shell,
        .onboarding-grid,
        .template-picker {
            grid-template-columns: 1fr;
        }

        .onboarding-actions {
            align-items: stretch;
            flex-direction: column;
        }

        .onboarding-actions .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<form method="POST" action="{{ route('admin.stores.store-with-user') }}" enctype="multipart/form-data" data-client-store-form>
    @csrf

    <div class="onboarding-shell">
        <section class="onboarding-card">
            <div class="onboarding-card__head">
                <div>
                    <h3>Cliente</h3>
                    <p>Datos de acceso para el dueño de la tienda.</p>
                </div>
                <span class="onboarding-step">1</span>
            </div>

            <div class="onboarding-card__body">
                <div class="onboarding-field">
                    <label for="user_name">Nombre del cliente</label>
                    <input id="user_name" type="text" name="user_name" value="{{ old('user_name') }}" placeholder="Ej: Laura Gomez" required autocomplete="name">
                </div>

                <div class="onboarding-field">
                    <label for="user_email">Email de acceso</label>
                    <input id="user_email" type="email" name="user_email" value="{{ old('user_email') }}" placeholder="cliente@email.com" required autocomplete="email">
                </div>

                <div class="onboarding-grid">
                    <div class="onboarding-field">
                        <label for="password">Contrasena temporal</label>
                        <input id="password" type="password" name="password" placeholder="Minimo 8 caracteres" required autocomplete="new-password">
                    </div>
                    <div class="onboarding-field">
                        <label for="password_confirmation">Confirmar contrasena</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Repite la contrasena" required autocomplete="new-password">
                    </div>
                </div>

                <div class="onboarding-grid">
                    <div class="onboarding-field">
                        <label for="active_starts_at">Fecha de inicio</label>
                        <input id="active_starts_at" type="date" name="active_starts_at" value="{{ old('active_starts_at', now()->toDateString()) }}">
                    </div>
                    <div class="onboarding-field">
                        <label for="active_duration_days">Dias activos</label>
                        <input id="active_duration_days" type="number" name="active_duration_days" value="{{ old('active_duration_days') }}" min="1" max="3650" placeholder="Ej: 30">
                    </div>
                </div>
            </div>
        </section>

        <section class="onboarding-card">
            <div class="onboarding-card__head">
                <div>
                    <h3>Tienda</h3>
                    <p>Identidad, plan y configuracion inicial del negocio.</p>
                </div>
                <span class="onboarding-step">2</span>
            </div>

            <div class="onboarding-card__body">
                <div class="onboarding-grid">
                    <div class="onboarding-field">
                        <label for="store_name">Nombre de tienda</label>
                        <input id="store_name" type="text" name="name" value="{{ old('name') }}" placeholder="Ej: Mixtas" required data-store-name>
                    </div>

                    <div class="onboarding-field">
                        <label for="whatsapp">WhatsApp</label>
                        <input id="whatsapp" type="text" name="whatsapp" value="{{ old('whatsapp') }}" placeholder="Ej: 573001112233" required>
                    </div>
                </div>

                <div class="onboarding-grid">
                    <div class="onboarding-field">
                        <label for="plan">Plan</label>
                        <select id="plan" name="plan" required>
                            @foreach (\App\Models\Store::planOptions() as $value => $label)
                                <option value="{{ $value }}" @selected(old('plan', \App\Models\Store::PLAN_PRO) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="onboarding-field">
                        <label for="business_type">Tipo de negocio</label>
                        <select id="business_type" name="business_type" required data-business-type>
                            @foreach (\App\Models\Store::businessTypeOptions() as $value => $label)
                                <option value="{{ $value }}" @selected(old('business_type', 'store') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="onboarding-field onboarding-field--full">
                    <span class="template-picker-title">Plantilla inicial</span>
                    <small style="display:block; margin:6px 0 10px; color:#64748b;">Muy pronto podras iniciar tiendas desde plantillas prediseñadas.</small>
                    <div class="template-picker">
                        <label class="template-option">
                            <input type="radio" name="template_key" value="" data-template-business="" @checked($selectedTemplate === '')>
                            <strong>Clasica</strong>
                            <span>Usa el tipo de negocio elegido sin plantilla especializada.</span>
                        </label>
                        @foreach($templates as $template)
                            <label class="template-option">
                                <input
                                    type="radio"
                                    name="template_key"
                                    value="{{ $template['key'] }}"
                                    data-template-business="{{ $template['business_type'] }}"
                                    disabled
                                    @checked($selectedTemplate === $template['key'])
                                >
                                <strong>{{ $template['name'] }}</strong>
                                <span>{{ $template['subtitle'] }} Muy pronto.</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="onboarding-grid">
                    <div class="onboarding-field">
                        <label for="slug">Slug publico</label>
                        <input id="slug" type="text" name="slug" value="{{ old('slug') }}" placeholder="mi-tienda" data-store-slug>
                        <small>URL principal: /mi-tienda</small>
                    </div>
                    <div class="onboarding-field">
                        <label for="subdomain">Subdominio Pro/Premium</label>
                        <input id="subdomain" type="text" name="subdomain" value="{{ old('subdomain') }}" placeholder="mitienda">
                    </div>
                </div>

                <div class="onboarding-grid">
                    <div class="onboarding-field">
                        <label for="custom_domain">Dominio personalizado Premium</label>
                        <input id="custom_domain" type="text" name="custom_domain" value="{{ old('custom_domain') }}" placeholder="www.tudominio.com">
                    </div>
                    @if(\App\Models\Store::supportsMetaPixelColumn())
                        <div class="onboarding-field">
                            <label for="meta_pixel_id">Meta Pixel ID Premium</label>
                            <input id="meta_pixel_id" type="text" inputmode="numeric" pattern="[0-9]*" name="meta_pixel_id" value="{{ old('meta_pixel_id') }}" maxlength="50" placeholder="123456789012345">
                        </div>
                    @endif
                </div>

                <div class="onboarding-field">
                    <label for="location">Ubicacion o direccion</label>
                    <input id="location" type="text" name="location" value="{{ old('location') }}" placeholder="Ciudad, barrio o direccion">
                </div>

                <div class="onboarding-field">
                    <label for="business_hours">Horario de atencion</label>
                    <textarea id="business_hours" name="business_hours" rows="3" placeholder="Ej: Lunes a viernes 8:00 AM - 6:00 PM">{{ old('business_hours') }}</textarea>
                </div>

                <div class="onboarding-field">
                    <label for="shop_copy">Descripcion breve</label>
                    <textarea id="shop_copy" name="shop_copy" rows="3" placeholder="Cuenta que vende la tienda y que la diferencia">{{ old('shop_copy') }}</textarea>
                </div>

                @include('admin.stores.partials.theme-fields')

                <div class="onboarding-grid">
                    <div class="onboarding-field">
                        <label for="store_cover_image">Portada</label>
                        <input id="store_cover_image" type="file" name="cover_image" accept="image/*" data-optimize-image data-max-width="1920" data-max-height="1080" data-quality="0.82" data-output="webp">
                    </div>
                    <div class="onboarding-field">
                        <label for="store_logo_image">Logo</label>
                        <input id="store_logo_image" type="file" name="logo_image" accept="image/*" data-optimize-image data-max-width="800" data-max-height="800" data-quality="0.86" data-output="webp">
                    </div>
                </div>

                <input type="hidden" name="custom_domain_status" value="{{ \App\Models\Store::CUSTOM_DOMAIN_PENDING }}">
                <input type="hidden" name="responsive_product_columns" value="2">
                <input type="hidden" name="show_hero_products_action" value="0">
            </div>
        </section>
    </div>

    <div class="onboarding-actions">
        <p>
            <strong>Se creara todo junto</strong>
            Usuario dueño, tienda, plan, plantilla e identidad inicial.
        </p>
        <button class="btn" type="submit">Crear cliente y tienda</button>
    </div>
</form>

<script>
    (() => {
        const form = document.querySelector('[data-client-store-form]');

        if (!form) return;

        const storeName = form.querySelector('[data-store-name]');
        const slug = form.querySelector('[data-store-slug]');
        const businessType = form.querySelector('[data-business-type]');
        const templates = form.querySelectorAll('[data-template-business]');

        const slugify = (value) => String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .slice(0, 80);

        let slugTouched = Boolean(slug?.value);

        slug?.addEventListener('input', () => {
            slugTouched = true;
        });

        storeName?.addEventListener('input', () => {
            if (!slug || slugTouched) return;
            slug.value = slugify(storeName.value);
        });

        templates.forEach((input) => {
            input.addEventListener('change', () => {
                if (!input.checked || !input.dataset.templateBusiness || !businessType) return;
                businessType.value = input.dataset.templateBusiness;
            });

            if (input.checked && input.dataset.templateBusiness && businessType) {
                businessType.value = input.dataset.templateBusiness;
            }
        });
    })();
</script>
@endsection
