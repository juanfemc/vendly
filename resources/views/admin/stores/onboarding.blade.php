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
    .onboarding-checklist {
        display: grid;
        gap: 16px;
        padding: 20px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #ffffff;
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

    .onboarding-checklist h2 {
        margin: 0;
        font-size: 18px;
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
        .onboarding-checklist {
            padding: 16px;
            border-radius: 14px;
        }

        .onboarding-form-grid {
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
            <h1>Primeros pasos de tu tienda</h1>
            <p>Completa lo esencial para que tu tienda se vea confiable, tenga datos claros y puedas empezar a publicar productos.</p>
        </div>

        <aside class="onboarding-progress-card">
            <span>Progreso</span>
            <strong>{{ $progress }}%</strong>
            <div class="onboarding-progress-track" aria-hidden="true">
                <span style="--progress: {{ $progress }}%"></span>
            </div>
        </aside>
    </section>

    <div class="onboarding-layout">
        <form method="POST" action="{{ route('admin.store.onboarding.update') }}" enctype="multipart/form-data" class="onboarding-form">
            @csrf

            <div class="onboarding-form-grid">
                <div class="onboarding-field">
                    <label for="onboarding_name">Nombre de tienda</label>
                    <input id="onboarding_name" name="name" value="{{ old('name', $store->name) }}" required>
                    @error('name')<span class="onboarding-error">{{ $message }}</span>@enderror
                </div>

                <div class="onboarding-field">
                    <label for="onboarding_whatsapp">WhatsApp de pedidos</label>
                    <input id="onboarding_whatsapp" name="whatsapp" value="{{ old('whatsapp', $store->whatsapp) }}" required inputmode="tel">
                    @error('whatsapp')<span class="onboarding-error">{{ $message }}</span>@enderror
                </div>

                <div class="onboarding-field">
                    <label for="onboarding_location">Ciudad o direccion</label>
                    <input id="onboarding_location" name="location" value="{{ old('location', $store->location) }}" placeholder="Ej: Cali, Colombia">
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
                    <span class="onboarding-task-icon">{{ $task['complete'] ? '✓' : '•' }}</span>
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
@endsection
