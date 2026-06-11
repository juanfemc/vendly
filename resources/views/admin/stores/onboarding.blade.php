@extends('layouts.admin')

@section('content')
<style>
    .onboarding-page {
        display: grid;
        gap: 18px;
    }

    .onboarding-hero {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(220px, 320px);
        gap: 18px;
        align-items: stretch;
        padding: 22px;
        border-radius: 18px;
        background: linear-gradient(135deg, #111827, #24140a);
        color: #ffffff;
        box-shadow: 0 20px 44px rgba(15, 23, 42, 0.12);
    }

    .onboarding-hero h1 {
        margin: 0 0 8px;
        font-size: clamp(28px, 4vw, 42px);
        line-height: 1.04;
        letter-spacing: -0.04em;
    }

    .onboarding-hero p {
        max-width: 620px;
        margin: 0;
        color: rgba(255, 255, 255, 0.76);
        line-height: 1.6;
    }

    .onboarding-progress-card {
        display: grid;
        align-content: center;
        gap: 12px;
        padding: 18px;
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.08);
    }

    .onboarding-progress-card strong {
        font-size: 34px;
        line-height: 1;
    }

    .onboarding-progress-card small {
        color: rgba(255, 255, 255, 0.72);
    }

    .onboarding-progress-track {
        height: 9px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.14);
        overflow: hidden;
    }

    .onboarding-progress-track span {
        display: block;
        width: var(--progress, 0%);
        height: 100%;
        border-radius: inherit;
        background: #ff6b00;
    }

    .onboarding-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(260px, 340px);
        gap: 18px;
        align-items: start;
    }

    .onboarding-form,
    .onboarding-checklist,
    .onboarding-verification-card {
        display: grid;
        gap: 16px;
        padding: 20px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #ffffff;
    }

    .onboarding-verification-card {
        border-color: #fed7aa;
        background: linear-gradient(135deg, #fff7ed, #ffffff);
    }

    .onboarding-verification-card.is-complete {
        border-color: #bbf7d0;
        background: linear-gradient(135deg, #f0fdf4, #ffffff);
    }

    .onboarding-verification-head {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: flex-start;
    }

    .onboarding-verification-head h2,
    .onboarding-checklist h2 {
        margin: 0;
        font-size: 18px;
        letter-spacing: -0.02em;
    }

    .onboarding-verification-head p {
        margin: 5px 0 0;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.45;
    }

    .onboarding-status-pill {
        flex: 0 0 auto;
        min-height: 28px;
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 0 10px;
        background: #ffedd5;
        color: #9a3412;
        font-size: 12px;
        font-weight: 900;
    }

    .onboarding-verification-card.is-complete .onboarding-status-pill {
        background: #dcfce7;
        color: #166534;
    }

    .onboarding-verification-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 10px;
        align-items: end;
    }

    .onboarding-code-row {
        display: grid;
        grid-template-columns: minmax(120px, 180px) auto;
        gap: 10px;
        align-items: end;
    }

    .onboarding-inline-status {
        min-height: 18px;
        color: #6b7280;
        font-size: 12px;
        line-height: 1.4;
    }

    .onboarding-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .onboarding-field {
        display: grid;
        gap: 7px;
        min-width: 0;
    }

    .onboarding-field--full {
        grid-column: 1 / -1;
    }

    .onboarding-field label {
        color: #111827;
        font-size: 13px;
        font-weight: 800;
    }

    .onboarding-field small {
        color: #6b7280;
        font-size: 12px;
        line-height: 1.4;
    }

    .onboarding-field input,
    .onboarding-field select,
    .onboarding-field textarea {
        width: 100%;
        min-height: 46px;
        margin: 0;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #f9fafb;
        color: #111827;
        padding: 0 12px;
        font: inherit;
    }

    .onboarding-field textarea {
        min-height: 96px;
        padding: 12px;
        resize: vertical;
    }

    .onboarding-field input[type="color"] {
        height: 46px;
        padding: 5px;
    }

    .onboarding-current-logo {
        width: 74px;
        height: 74px;
        border-radius: 16px;
        object-fit: cover;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
    }

    .onboarding-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        padding-top: 4px;
    }

    .onboarding-error {
        color: #b42318;
        font-size: 12px;
    }

    .onboarding-task {
        display: grid;
        grid-template-columns: 34px minmax(0, 1fr);
        gap: 10px;
        align-items: start;
        padding: 12px;
        border-radius: 14px;
        background: #f9fafb;
    }

    .onboarding-task-icon {
        width: 34px;
        height: 34px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e5e7eb;
        color: #6b7280;
        font-weight: 900;
    }

    .onboarding-task.is-complete .onboarding-task-icon {
        background: #dcfce7;
        color: #166534;
    }

    .onboarding-task strong {
        display: block;
        color: #111827;
        font-size: 14px;
    }

    .onboarding-task span:last-child {
        display: block;
        margin-top: 3px;
        color: #6b7280;
        font-size: 12px;
        line-height: 1.45;
    }

    @media (max-width: 900px) {
        .onboarding-hero,
        .onboarding-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 620px) {
        .onboarding-hero,
        .onboarding-form,
        .onboarding-checklist,
        .onboarding-verification-card {
            padding: 16px;
            border-radius: 14px;
        }

        .onboarding-form-grid,
        .onboarding-verification-grid,
        .onboarding-code-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="onboarding-page">
    @if (session('success'))
        <div class="flash success">{{ session('success') }}</div>
    @endif

    <section class="onboarding-hero">
        <div>
            <h1>Tu tienda ya esta creada</h1>
            <p>Completa estos pasos para activar seguridad, preparar tu imagen y publicar tu primer producto.</p>
        </div>

        <aside class="onboarding-progress-card">
            <span>Progreso</span>
            <strong>{{ $progress }}%</strong>
            <div class="onboarding-progress-track" aria-hidden="true">
                <span style="--progress: {{ $progress }}%"></span>
            </div>
            <small>{{ collect($checklist)->where('complete', true)->count() }} de {{ count($checklist) }} completado</small>
        </aside>
    </section>

    <section class="onboarding-verification-card {{ $store->whatsapp_verified_at ? 'is-complete' : '' }}">
        <div class="onboarding-verification-head">
            <div>
                <h2>Verifica tu WhatsApp</h2>
                <p>Este numero protege tu prueba gratis y permite enviar bienvenida, recordatorios y avisos importantes.</p>
            </div>
            <span class="onboarding-status-pill">{{ $store->whatsapp_verified_at ? 'Verificado' : 'Pendiente' }}</span>
        </div>

        @if($store->whatsapp_verified_at)
            <p class="onboarding-inline-status">Tu WhatsApp {{ $store->whatsapp }} fue verificado correctamente.</p>
        @else
            <div class="onboarding-verification-grid">
                <div class="onboarding-field">
                    <label for="verify_whatsapp">WhatsApp a verificar</label>
                    <input id="verify_whatsapp" value="{{ old('whatsapp', $store->whatsapp) }}" inputmode="tel" autocomplete="tel">
                    <small>Si quieres cambiarlo, guardalo tambien en el formulario de configuracion.</small>
                </div>
                <button type="button" class="btn" data-send-whatsapp-code>Enviar codigo</button>
            </div>

            <div class="onboarding-code-row">
                <div class="onboarding-field">
                    <label for="verify_whatsapp_code">Codigo de 6 digitos</label>
                    <input id="verify_whatsapp_code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="000000">
                    <input id="verify_whatsapp_token" type="hidden">
                </div>
                <button type="button" class="btn btn-secondary" data-confirm-whatsapp-code>Verificar numero</button>
            </div>

            <div class="onboarding-inline-status" data-whatsapp-status aria-live="polite"></div>
        @endif
    </section>

    <div class="onboarding-layout">
        <form method="POST" action="{{ route('admin.store.onboarding.update') }}" enctype="multipart/form-data" class="onboarding-form">
            @csrf

            <div class="onboarding-form-grid">
                <div class="onboarding-field">
                    <label for="onboarding_name">Nombre de tienda</label>
                    <input id="onboarding_name" name="name" value="{{ old('name', $store->name) }}" required>
                    <small>El nombre debe ser facil de recordar y reconocer.</small>
                    @error('name')<span class="onboarding-error">{{ $message }}</span>@enderror
                </div>

                <div class="onboarding-field">
                    <label for="onboarding_whatsapp">WhatsApp de pedidos</label>
                    <input id="onboarding_whatsapp" name="whatsapp" value="{{ old('whatsapp', $store->whatsapp) }}" required inputmode="tel">
                    <small>Si cambias este numero tendras que verificarlo nuevamente.</small>
                    @error('whatsapp')<span class="onboarding-error">{{ $message }}</span>@enderror
                </div>

                <div class="onboarding-field">
                    <label for="onboarding_location">Ciudad o direccion</label>
                    <input id="onboarding_location" name="location" value="{{ old('location', $store->location) }}" placeholder="Ej: Cali, Colombia">
                    <small>Opcional. Puedes completarlo cuando configures envios.</small>
                    @error('location')<span class="onboarding-error">{{ $message }}</span>@enderror
                </div>

                <div class="onboarding-field">
                    <label for="onboarding_brand_color">Color principal</label>
                    <input id="onboarding_brand_color" type="color" name="brand_color" value="{{ old('brand_color', $store->brand_color ?: '#ff6b00') }}">
                    @error('brand_color')<span class="onboarding-error">{{ $message }}</span>@enderror
                </div>

                <div class="onboarding-field">
                    <label for="onboarding_logo">Logo</label>
                    @if($store->logo_image)
                        <img class="onboarding-current-logo" src="{{ asset('storage/' . $store->logo_image) }}" alt="{{ $store->name }}">
                    @endif
                    <input id="onboarding_logo" type="file" name="logo_image" accept="image/*">
                    <small>Usa una imagen cuadrada para que se vea mejor.</small>
                    @error('logo_image')<span class="onboarding-error">{{ $message }}</span>@enderror
                </div>

                <div class="onboarding-field onboarding-field--full">
                    <label for="onboarding_shop_copy">Descripcion corta</label>
                    <textarea id="onboarding_shop_copy" name="shop_copy" placeholder="Cuenta en una frase que vendes y por que deberian comprarte.">{{ old('shop_copy', $store->shop_copy) }}</textarea>
                    @error('shop_copy')<span class="onboarding-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="onboarding-actions">
                <button type="submit" class="btn">Guardar y continuar</button>
                <a href="{{ url('/' . $store->slug) }}" class="btn btn-secondary" target="_blank" rel="noopener noreferrer">Ver tienda</a>
            </div>
        </form>

        <aside class="onboarding-checklist">
            <h2>Checklist</h2>
            @foreach($checklist as $task)
                <div class="onboarding-task {{ $task['complete'] ? 'is-complete' : '' }}">
                    <span class="onboarding-task-icon">{{ $task['complete'] ? 'OK' : '-' }}</span>
                    <span>
                        <strong>{{ $task['label'] }}</strong>
                        <span>{{ $task['description'] }}</span>
                    </span>
                </div>
            @endforeach

            <a href="{{ route('admin.products.create') }}" class="btn btn-secondary">Agregar primer producto</a>
        </aside>
    </div>
</div>

@if(! $store->whatsapp_verified_at)
    <script>
        (() => {
            const status = document.querySelector('[data-whatsapp-status]');
            const sendButton = document.querySelector('[data-send-whatsapp-code]');
            const confirmButton = document.querySelector('[data-confirm-whatsapp-code]');
            const phoneInput = document.getElementById('verify_whatsapp');
            const codeInput = document.getElementById('verify_whatsapp_code');
            const tokenInput = document.getElementById('verify_whatsapp_token');

            const postJson = async (url, payload) => {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token()),
                    },
                    body: JSON.stringify(payload),
                });
                const data = await response.json().catch(() => ({}));

                if (! response.ok) {
                    const message = data.message
                        || data.errors?.whatsapp?.[0]
                        || data.errors?.whatsapp_verification_code?.[0]
                        || 'No pudimos completar la accion. Intenta nuevamente.';
                    throw new Error(message);
                }

                return data;
            };

            sendButton?.addEventListener('click', async () => {
                sendButton.disabled = true;
                status.textContent = 'Enviando codigo...';

                try {
                    const data = await postJson(@json(route('admin.store.onboarding.whatsapp.send')), {
                        whatsapp: phoneInput.value,
                    });

                    tokenInput.value = data.verification_token || '';
                    status.textContent = data.message || 'Codigo enviado.';
                    if (! tokenInput.value) {
                        window.location.reload();
                        return;
                    }
                    codeInput.focus();
                } catch (error) {
                    status.textContent = error.message;
                } finally {
                    sendButton.disabled = false;
                }
            });

            confirmButton?.addEventListener('click', async () => {
                confirmButton.disabled = true;
                status.textContent = 'Verificando codigo...';

                try {
                    const data = await postJson(@json(route('admin.store.onboarding.whatsapp.verify')), {
                        whatsapp: phoneInput.value,
                        whatsapp_verification_code: codeInput.value,
                        whatsapp_verification_token: tokenInput.value,
                    });

                    status.textContent = data.message || 'WhatsApp verificado.';
                    window.location.reload();
                } catch (error) {
                    status.textContent = error.message;
                } finally {
                    confirmButton.disabled = false;
                }
            });
        })();
    </script>
@endif
@endsection
