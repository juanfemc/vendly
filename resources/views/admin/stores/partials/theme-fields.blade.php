@php
    $store = $store ?? null;
    $selectedBrandColor = old('brand_color', $store?->brand_color ?: '#111111');
    $selectedBackgroundColor = old('background_color', $store?->background_color ?: '#ffffff');
    $selectedTextColor = old('text_color', $store?->text_color ?: '#171717');
    $selectedFontFamily = old('font_family', $store?->font_family ?: 'system');
    $brandPalette = ['#111111', '#7c3aed', '#4f46e5', '#1d4ed8', '#0f766e', '#ca8a04', '#ff6a00', '#be123c'];
    $backgroundPalette = ['#ffffff', '#f8fafc', '#f7f7f7', '#fff7ed', '#f0fdf4', '#eff6ff', '#f5f3ff', '#111827'];
    $textPalette = ['#171717', '#1f2937', '#334155', '#0f172a', '#3f3f46', '#ffffff', '#f8fafc', '#111111'];
@endphp

<style>
    .theme-panel {
        margin-bottom: 18px;
        padding: 16px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #f9fafb;
    }

    .theme-title {
        margin-bottom: 14px;
        font-weight: 700;
    }

    .theme-palette {
        display: flex;
        flex-wrap: wrap;
        gap: 9px;
        margin: 0 0 12px;
    }

    .theme-swatch {
        width: 34px;
        height: 34px;
        border: 1px solid #d1d5db;
        border-radius: 999px;
        cursor: pointer;
        box-shadow: inset 0 0 0 2px rgba(255, 255, 255, 0.7);
    }

    .theme-swatch.is-selected {
        border: 3px solid #111827;
    }

    .font-preview-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin: 0 0 12px;
    }

    .font-preview-option {
        padding: 12px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        background: #ffffff;
        cursor: pointer;
        text-align: left;
    }

    .font-preview-option.is-selected {
        border-color: #111827;
        box-shadow: 0 0 0 2px rgba(17, 24, 39, 0.08);
    }

    .font-preview-name {
        display: block;
        margin-bottom: 6px;
        color: #111827;
        font-size: 13px;
        font-weight: 800;
    }

    .font-preview-sample {
        display: block;
        color: #4b5563;
        font-size: 17px;
        line-height: 1.35;
    }

    @media (max-width: 720px) {
        .font-preview-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="theme-panel" data-theme-panel>
    <div class="theme-title">Tema de la tienda</div>

    <label class="field-label" for="brand_color">Color principal</label>
    <div class="theme-palette" aria-label="Paleta de color principal">
        @foreach($brandPalette as $color)
            <button
                type="button"
                class="theme-swatch @if(strtolower($selectedBrandColor) === strtolower($color)) is-selected @endif"
                data-theme-target="brand_color"
                data-theme-color="{{ $color }}"
                style="background: {{ $color }};"
                aria-label="Usar {{ $color }} como color principal"
            ></button>
        @endforeach
    </div>
    <input id="brand_color" type="text" name="brand_color" value="{{ $selectedBrandColor }}" placeholder="Color principal (#111111)">

    <label class="field-label" for="background_color">Color de fondo</label>
    <div class="theme-palette" aria-label="Paleta de color de fondo">
        @foreach($backgroundPalette as $color)
            <button
                type="button"
                class="theme-swatch @if(strtolower($selectedBackgroundColor) === strtolower($color)) is-selected @endif"
                data-theme-target="background_color"
                data-theme-color="{{ $color }}"
                style="background: {{ $color }};"
                aria-label="Usar {{ $color }} como color de fondo"
            ></button>
        @endforeach
    </div>
    <input id="background_color" type="text" name="background_color" value="{{ $selectedBackgroundColor }}" placeholder="Color de fondo (#ffffff)">

    <label class="field-label" for="text_color">Color de letras</label>
    <div class="theme-palette" aria-label="Paleta de color de letras">
        @foreach($textPalette as $color)
            <button
                type="button"
                class="theme-swatch @if(strtolower($selectedTextColor) === strtolower($color)) is-selected @endif"
                data-theme-target="text_color"
                data-theme-color="{{ $color }}"
                style="background: {{ $color }};"
                aria-label="Usar {{ $color }} como color de letras"
            ></button>
        @endforeach
    </div>
    <input id="text_color" type="text" name="text_color" value="{{ $selectedTextColor }}" placeholder="Color de letras (#171717)">

    <label class="field-label" for="font_family">Fuente</label>
    <div class="font-preview-grid" aria-label="Ejemplos de fuente">
        @foreach(\App\Models\Store::FONT_FAMILIES as $value => $font)
            <button
                type="button"
                class="font-preview-option @if($selectedFontFamily === $value) is-selected @endif"
                data-font-value="{{ $value }}"
                style="font-family: {{ $font['css'] }};"
            >
                <span class="font-preview-name">{{ $font['label'] }}</span>
                <span class="font-preview-sample">NovaShop vende facil</span>
            </button>
        @endforeach
    </div>
    <select id="font_family" name="font_family">
        @foreach(\App\Models\Store::fontFamilyOptions() as $value => $label)
            <option value="{{ $value }}" @selected($selectedFontFamily === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>

@pushOnce('scripts')
    <script>
        (() => {
            const panels = document.querySelectorAll('[data-theme-panel]');

            panels.forEach((panel) => {
                panel.querySelectorAll('[data-theme-color]').forEach((swatch) => {
                    swatch.addEventListener('click', () => {
                        const input = panel.querySelector(`#${swatch.dataset.themeTarget}`);

                        if (! input) {
                            return;
                        }

                        input.value = swatch.dataset.themeColor;

                        panel
                            .querySelectorAll(`[data-theme-target="${swatch.dataset.themeTarget}"]`)
                            .forEach((item) => item.classList.toggle('is-selected', item === swatch));
                    });
                });

                const fontSelect = panel.querySelector('#font_family');

                panel.querySelectorAll('[data-font-value]').forEach((option) => {
                    option.addEventListener('click', () => {
                        if (! fontSelect) {
                            return;
                        }

                        fontSelect.value = option.dataset.fontValue;

                        panel
                            .querySelectorAll('[data-font-value]')
                            .forEach((item) => item.classList.toggle('is-selected', item === option));
                    });
                });
            });
        })();
    </script>
@endPushOnce
