@extends('layouts.admin')

@section('content')
<style>
    .store-settings-form {
        display: grid;
        gap: 18px;
    }

    .settings-section {
        display: grid;
        gap: 14px;
        padding: 18px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #ffffff;
    }

    .settings-section-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        padding-bottom: 12px;
        border-bottom: 1px solid #eef2f7;
    }

    .settings-section-title {
        margin: 0;
        color: #111827;
        font-size: 18px;
        line-height: 1.2;
    }

    .settings-section-copy {
        margin: 5px 0 0;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.5;
    }

    .settings-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .settings-grid--three {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .settings-field {
        min-width: 0;
    }

    .settings-field--full {
        grid-column: 1 / -1;
    }

    .settings-field input,
    .settings-field textarea,
    .settings-field select {
        margin-bottom: 0;
    }

    .settings-readonly-input {
        background: #f9fafb;
        color: #374151;
    }

    .settings-help {
        margin: 8px 0 0;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.45;
    }

    .settings-check-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 8px;
    }

    .settings-check {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        color: #374151;
        font-size: 14px;
    }

    .settings-check input {
        width: auto;
        margin: 0;
    }

    .settings-media-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .settings-media-card {
        display: grid;
        gap: 10px;
        min-width: 0;
    }

    .announcement-list {
        display: grid;
        gap: 10px;
    }

    .announcement-row {
        display: grid;
        grid-template-columns: 34px minmax(0, 1fr);
        gap: 10px;
        align-items: center;
    }

    .announcement-number {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eef2ff;
        color: #3730a3;
        font-size: 13px;
        font-weight: 800;
    }

    .settings-media-preview {
        width: 100%;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #f9fafb;
        object-fit: cover;
        display: block;
    }

    .settings-media-preview--logo {
        max-width: 120px;
        aspect-ratio: 1;
    }

    .settings-media-preview--cover {
        max-width: 520px;
        aspect-ratio: 16 / 9;
    }

    .settings-actions {
        position: sticky;
        bottom: 0;
        z-index: 20;
        display: flex;
        justify-content: flex-end;
        padding: 14px 0 0;
        background: linear-gradient(180deg, rgba(245, 246, 250, 0), #f5f6fa 34%);
    }

    .settings-actions .btn {
        min-width: 180px;
    }

    @media (max-width: 720px) {
        .settings-section {
            padding: 14px;
        }

        .settings-section-head {
            display: grid;
        }

        .settings-grid,
        .settings-grid--three,
        .settings-media-grid {
            grid-template-columns: 1fr;
        }

        .settings-actions .btn {
            width: 100%;
        }
    }
</style>

<div class="header">
    <div>
        <h2>Configuracion de tienda</h2>
        <p style="margin:6px 0 0; color:#6b7280;">Actualiza la informacion, apariencia y contenido publico de tu tienda.</p>
    </div>
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

<form method="POST" action="/admin/store-settings" enctype="multipart/form-data" class="store-settings-form">
    @csrf

    <section class="settings-section">
        <div class="settings-section-head">
            <div>
                <h3 class="settings-section-title">Datos principales</h3>
                <p class="settings-section-copy">Informacion basica que ayuda a tus clientes a identificar y contactar la tienda.</p>
            </div>
        </div>

        <div class="settings-grid">
            <div class="settings-field">
                <label class="field-label" for="store_name">Nombre de la tienda</label>
                <input id="store_name" type="text" name="name" value="{{ old('name', $store->name) }}" placeholder="Nombre tienda">
            </div>

            <input type="hidden" name="business_type" value="{{ old('business_type', $store->business_type ?? 'store') }}">

            <div class="settings-field">
                <label class="field-label" for="store_whatsapp">WhatsApp</label>
                <input id="store_whatsapp" type="text" name="whatsapp" value="{{ old('whatsapp', $store->whatsapp) }}" placeholder="WhatsApp">
            </div>

            <div class="settings-field settings-field--full">
                <label class="field-label" for="store_url">URL de tu tienda</label>
                <input id="store_url" class="settings-readonly-input" type="text" value="{{ url('/' . $store->slug) }}" readonly>
                <p class="settings-help">El slug no se puede cambiar desde esta pantalla.</p>
            </div>

            <div class="settings-field">
                <label class="field-label" for="store_plan">Plan actual</label>
                <input id="store_plan" class="settings-readonly-input" type="text" value="{{ $store->planLabel() }}" readonly>
                <p class="settings-help">El plan lo cambia el administrador.</p>
            </div>

            @if($store->allowsSubdomain())
                @php
                    $storefrontHost = parse_url(config('app.url'), PHP_URL_HOST) ?: request()->getHost();
                    $customDomain = old('custom_domain', $store->custom_domain);
                @endphp
                <div class="settings-field">
                    <label class="field-label" for="store_subdomain">Subdominio</label>
                    <input id="store_subdomain" type="text" name="subdomain" value="{{ old('subdomain', $store->subdomain) }}" placeholder="mitienda">
                    <p class="settings-help">
                        Tu tienda podra usarse como {{ old('subdomain', $store->subdomain) ?: 'mitienda' }}.{{ $storefrontHost }}.
                    </p>
                </div>

                @if($store->allowsCustomDomain())
                    <div class="settings-field settings-field--full">
                        <label class="field-label" for="store_custom_domain">Dominio personalizado</label>
                        <input id="store_custom_domain" type="text" name="custom_domain" value="{{ $customDomain }}" placeholder="www.tudominio.com">
                        <p class="settings-help">
                            Disponible para Premium. Primero guarda el dominio y luego apunta un CNAME hacia {{ $storefrontHost }}.
                        </p>
                        @if($customDomain)
                            <p class="settings-help">
                                Estado: {{ ($store->custom_domain_status ?? 'pending') === 'verified' ? 'verificado' : 'pendiente de verificacion' }}.
                            </p>
                            <p class="settings-help">
                                DNS recomendado: tipo CNAME, nombre {{ str_starts_with($customDomain, 'www.') ? 'www' : $customDomain }}, destino {{ $storefrontHost }}.
                            </p>
                        @endif
                    </div>
                @else
                    <div class="settings-field settings-field--full">
                        <label class="field-label" for="store_custom_domain_locked">Dominio personalizado</label>
                        <input id="store_custom_domain_locked" class="settings-readonly-input" type="text" value="Disponible desde el plan Premium" readonly>
                        <p class="settings-help">El plan Pro puede usar subdominio; el dominio propio queda reservado para Premium.</p>
                    </div>
                @endif
            @else
                <div class="settings-field">
                    <label class="field-label" for="store_subdomain_locked">Subdominio</label>
                    <input id="store_subdomain_locked" class="settings-readonly-input" type="text" value="Disponible desde el plan Pro" readonly>
                    <p class="settings-help">El plan Basico mantiene la URL normal de la tienda.</p>
                </div>
                <div class="settings-field">
                    <label class="field-label" for="store_custom_domain_locked">Dominio personalizado</label>
                    <input id="store_custom_domain_locked" class="settings-readonly-input" type="text" value="Disponible desde el plan Premium" readonly>
                    <p class="settings-help">El dominio propio requiere plan Premium.</p>
                </div>
            @endif

            <div class="settings-field">
                <label class="field-label" for="store_location">Ubicacion o direccion</label>
                <input id="store_location" type="text" name="location" value="{{ old('location', $store->location) }}" placeholder="Ubicacion o direccion (opcional)">
            </div>

            <div class="settings-field">
                <label class="field-label" for="business_hours">Horario de atencion</label>
                <textarea id="business_hours" name="business_hours" placeholder="Ej: Lunes a viernes 8:00 AM - 6:00 PM">{{ old('business_hours', $store->business_hours) }}</textarea>
            </div>
        </div>
    </section>

    @if($store->isReservationStore())
        @php
            $selectedReservationDays = old('reservation_available_days', $store->reservation_available_days ?? []);
        @endphp
        <section class="settings-section">
            <div class="settings-section-head">
                <div>
                    <h3 class="settings-section-title">Reservas</h3>
                    <p class="settings-section-copy">Define los dias y horas en que tus clientes pueden solicitar una reserva.</p>
                </div>
            </div>

            <div class="settings-field">
                <div class="field-label">Dias disponibles</div>
                <div class="settings-check-grid">
                    @foreach(\App\Models\Store::reservationDayOptions() as $dayValue => $dayLabel)
                        <label class="settings-check">
                            <input type="checkbox" name="reservation_available_days[]" value="{{ $dayValue }}" @checked(in_array($dayValue, $selectedReservationDays, true))>
                            {{ $dayLabel }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="settings-grid">
                <div class="settings-field">
                    <label class="field-label" for="reservation_time_start">Hora inicial</label>
                    <input id="reservation_time_start" type="time" name="reservation_time_start" value="{{ old('reservation_time_start', $store->reservation_time_start) }}">
                </div>
                <div class="settings-field">
                    <label class="field-label" for="reservation_time_end">Hora final</label>
                    <input id="reservation_time_end" type="time" name="reservation_time_end" value="{{ old('reservation_time_end', $store->reservation_time_end) }}">
                </div>
            </div>
        </section>
    @endif

    @if($store->allowsCommercialNotices())
    <section class="settings-section">
        <div class="settings-section-head">
            <div>
                <h3 class="settings-section-title">Avisos comerciales</h3>
                <p class="settings-section-copy">Mensajes cortos que rotan arriba de la tienda para comunicar descuentos, entregas, pagos o promociones.</p>
            </div>
        </div>

        @php
            $announcementItems = old('announcement_items', $store->announcement_items ?? []);
            $announcementTexts = collect($announcementItems)->pluck('text')->values();
        @endphp

        <div class="settings-grid">
            <div class="settings-field">
                <label class="field-label" for="free_shipping_minimum">Envio gratis desde</label>
                <input id="free_shipping_minimum" type="number" name="free_shipping_minimum" value="{{ old('free_shipping_minimum', $store->free_shipping_minimum) }}" min="0" step="1000" placeholder="Ej: 150000">
                <p class="settings-help">Si agregas un monto, se mostrara automaticamente como aviso superior.</p>
            </div>

            <div class="settings-field settings-field--full">
                <div class="field-label">Avisos rotativos</div>
                <div class="announcement-list">
                    @for($announcementIndex = 0; $announcementIndex < 5; $announcementIndex++)
                        <label class="announcement-row">
                            <span class="announcement-number">{{ $announcementIndex + 1 }}</span>
                            <input
                                type="text"
                                name="announcement_items[{{ $announcementIndex }}][text]"
                                value="{{ old('announcement_items.' . $announcementIndex . '.text', $announcementTexts[$announcementIndex] ?? '') }}"
                                maxlength="140"
                                placeholder="{{ [
                                    '10% OFF pagando por transferencia',
                                    'Entregas hoy hasta las 6:00 p.m.',
                                    'Recoge en tienda sin costo adicional',
                                    'Aceptamos Nequi, Daviplata y efectivo',
                                    'Pedidos personalizados por WhatsApp',
                                ][$announcementIndex] }}"
                            >
                        </label>
                    @endfor
                </div>
                <p class="settings-help">Usa mensajes breves. La tienda mostrara uno a la vez y rotara automaticamente.</p>
            </div>
        </div>
    </section>
    @else
        <section class="settings-section">
            <div class="settings-section-head">
                <div>
                    <h3 class="settings-section-title">Avisos comerciales</h3>
                    <p class="settings-section-copy">Tu plan actual no incluye avisos superiores ni envio gratis destacado.</p>
                </div>
            </div>
            <p class="settings-help">En el plan Basico la tienda se mantiene simple: productos, carrito por WhatsApp, logo, portada y personalizacion basica.</p>
        </section>
    @endif

    @if($store->allowsFullCustomization())
        <section class="settings-section">
            <div class="settings-section-head">
                <div>
                    <h3 class="settings-section-title">Apariencia</h3>
                    <p class="settings-section-copy">Configura paletas, colores y fuente de la experiencia publica.</p>
                </div>
            </div>
            @include('admin.stores.partials.theme-fields', ['store' => $store])
        </section>
    @else
        <section class="settings-section">
            <div class="settings-section-head">
                <div>
                    <h3 class="settings-section-title">Apariencia</h3>
                    <p class="settings-section-copy">Tu plan actual incluye personalizacion basica: logo, portada y datos principales.</p>
                </div>
            </div>
            <p class="settings-help">La personalizacion completa de colores, fuentes y vistas del catalogo esta disponible desde el plan Pro.</p>
        </section>
    @endif

    <section class="settings-section">
        <div class="settings-section-head">
            <div>
                <h3 class="settings-section-title">Contenido</h3>
                <p class="settings-section-copy">Textos que aparecen en la tienda y en la pagina de nosotros.</p>
            </div>
        </div>

        <div class="settings-grid">
            <div class="settings-field settings-field--full">
                <label class="field-label" for="shop_copy">Quienes somos</label>
                <textarea id="shop_copy" name="shop_copy" placeholder="Cuenta brevemente que hace la tienda y que la diferencia">{{ old('shop_copy', $store->shop_copy) }}</textarea>
            </div>
            <div class="settings-field">
                <label class="field-label" for="mission">Mision</label>
                <textarea id="mission" name="mission" placeholder="Que hace hoy la tienda y para que existe">{{ old('mission', $store->mission) }}</textarea>
            </div>
            <div class="settings-field">
                <label class="field-label" for="vision">Vision</label>
                <textarea id="vision" name="vision" placeholder="Hacia donde quiere crecer la tienda">{{ old('vision', $store->vision) }}</textarea>
            </div>
        </div>
    </section>

    <section class="settings-section">
        <div class="settings-section-head">
            <div>
                <h3 class="settings-section-title">Redes sociales</h3>
                <p class="settings-section-copy">Enlaces opcionales para mostrar accesos sociales en la tienda.</p>
            </div>
        </div>

        <div class="settings-grid settings-grid--three">
            <div class="settings-field">
                <label class="field-label" for="instagram_url">Instagram</label>
                <input id="instagram_url" type="url" name="instagram_url" value="{{ old('instagram_url', $store->instagram_url) }}" placeholder="Instagram URL">
            </div>
            <div class="settings-field">
                <label class="field-label" for="facebook_url">Facebook</label>
                <input id="facebook_url" type="url" name="facebook_url" value="{{ old('facebook_url', $store->facebook_url) }}" placeholder="Facebook URL">
            </div>
            <div class="settings-field">
                <label class="field-label" for="tiktok_url">TikTok</label>
                <input id="tiktok_url" type="url" name="tiktok_url" value="{{ old('tiktok_url', $store->tiktok_url) }}" placeholder="TikTok URL">
            </div>
        </div>
    </section>

    @if($store->allowsFullCustomization())
        <section class="settings-section">
            <div class="settings-section-head">
                <div>
                    <h3 class="settings-section-title">Catalogo y portada</h3>
                    <p class="settings-section-copy">Ajustes visuales de productos y acciones sobre la portada.</p>
                </div>
            </div>

            <div class="settings-grid">
                <div class="settings-field">
                    <label class="field-label" for="responsive_product_columns">Columnas de productos en responsive</label>
                    <select id="responsive_product_columns" name="responsive_product_columns">
                        <option value="1" @selected((int) old('responsive_product_columns', $store->responsive_product_columns ?? 2) === 1)>1 columna</option>
                        <option value="2" @selected((int) old('responsive_product_columns', $store->responsive_product_columns ?? 2) === 2)>2 columnas</option>
                        <option value="3" @selected((int) old('responsive_product_columns', $store->responsive_product_columns ?? 2) === 3)>3 columnas</option>
                    </select>
                </div>
                <div class="settings-field">
                    <label class="field-label" for="show_hero_products_action">Boton y texto sobre la portada</label>
                    <select id="show_hero_products_action" name="show_hero_products_action">
                        <option value="1" @selected((bool) old('show_hero_products_action', $store->show_hero_products_action ?? false))>Habilitado</option>
                        <option value="0" @selected(! (bool) old('show_hero_products_action', $store->show_hero_products_action ?? false))>Deshabilitado</option>
                    </select>
                </div>
            </div>
        </section>
    @endif

    <section class="settings-section">
        <div class="settings-section-head">
            <div>
                <h3 class="settings-section-title">Imagenes de marca</h3>
                <p class="settings-section-copy">Sube logo y portada optimizados para la vitrina publica.</p>
            </div>
        </div>

        <div class="settings-media-grid">
            <div class="settings-media-card">
                <label class="field-label" for="store_logo_image">Logo de la tienda</label>
                @if ($store->logo_image)
                    <img class="settings-media-preview settings-media-preview--logo" src="{{ asset('storage/' . $store->logo_image) }}" alt="{{ $store->name }}">
                @endif
                <input id="store_logo_image" type="file" name="logo_image" accept="image/*" data-optimize-image data-max-width="720" data-max-height="720" data-quality="0.86" data-output="webp">
            </div>

            <div class="settings-media-card">
                <label class="field-label" for="store_cover_image">Portada de la tienda</label>
                @if ($store->cover_image)
                    <img class="settings-media-preview settings-media-preview--cover" src="{{ asset('storage/' . $store->cover_image) }}" alt="{{ $store->name }}">
                @endif
                <input id="store_cover_image" type="file" name="cover_image" accept="image/*" data-optimize-image data-max-width="1920" data-max-height="1080" data-quality="0.82" data-output="webp">
            </div>
        </div>
    </section>

    <div class="settings-actions">
        <button class="btn">Guardar cambios</button>
    </div>
</form>

@endsection
