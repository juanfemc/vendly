@extends('layouts.admin')

@section('meta_title', 'Vendly - Plantillas.')

@section('content')
<div class="header">
    <div>
        <h2>Plantillas</h2>
        <p style="margin:6px 0 0; color:#64748b;">Elige el diseño principal de tu tienda.</p>
    </div>
    <a href="/dashboard" class="btn btn-secondary">Volver al panel</a>
</div>

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="flash error">{{ session('error') }}</div>
@endif

@if(($stores ?? collect())->count() > 1)
    <div class="list-card">
        <form method="GET" action="{{ route('admin.templates.index') }}">
            <label class="field-label" for="store_id">Tienda</label>
            <select id="store_id" name="store_id" onchange="this.form.submit()">
                @foreach($stores as $storeOption)
                    <option value="{{ $storeOption->id }}" @selected((int) $storeOption->id === (int) $store->id)>
                        {{ $storeOption->name }} - {{ $storeOption->planLabel() }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>
@else
    <div class="list-card resource-card">
        <div class="resource-card__main">
            <h3 class="resource-card__title">{{ $store->name }}</h3>
            <p class="resource-card__subtitle">Tienda seleccionada - Plan {{ $store->planLabel() }}</p>
        </div>
    </div>
@endif

<div class="panel-list">
    @foreach($templates as $template)
        @php
            $isActive = $store->business_type === $template['business_type'];
        @endphp

        <article class="list-card resource-card">
            <div class="resource-card__main">
                <div class="resource-card__header">
                    <div>
                        <h3 class="resource-card__title">Plantilla {{ $template['name'] }}</h3>
                        <p class="resource-card__subtitle">{{ $template['subtitle'] }}</p>
                    </div>
                    <div class="resource-badges">
                        <span class="resource-badge">Pro y Premium</span>
                        @if($isActive)
                            <span class="resource-badge resource-badge--active">Activa</span>
                        @endif
                    </div>
                </div>

                <p class="resource-card__description">{{ $template['description'] }}</p>

                <div class="resource-metrics">
                    @foreach($template['features'] as $feature)
                        <div class="resource-metric">
                            <span class="resource-metric__label">Incluye</span>
                            <span class="resource-metric__value">{{ $feature }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="resource-actions">
                @if($isActive)
                    <button type="button" class="btn btn-muted" disabled>Plantilla activa</button>
                @else
                    <form method="POST" action="{{ route('admin.templates.apply', $template['key']) }}">
                        @csrf
                        <input type="hidden" name="store_id" value="{{ $store->id }}">
                        <button type="submit" class="btn">Usar plantilla</button>
                    </form>
                @endif
            </div>
        </article>
    @endforeach
</div>
@endsection
