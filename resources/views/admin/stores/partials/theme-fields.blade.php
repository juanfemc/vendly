@php
    $store = $store ?? null;
    $selectedBrandColor = \App\Support\BrandTheme::normalizeColor(old('brand_color', $store?->brand_color), '#111111');
    $selectedBackgroundColor = \App\Support\BrandTheme::normalizeColor(old('background_color', $store?->background_color), '#ffffff');
    $selectedTextColor = \App\Models\Store::automaticTextColorFor($selectedBackgroundColor);
    $selectedFontFamily = old('font_family', $store?->font_family ?: 'system');
    $selectedFontFamily = array_key_exists($selectedFontFamily, \App\Models\Store::FONT_FAMILIES) ? $selectedFontFamily : 'system';
    $brandPalette = ['#111111', '#7c3aed', '#4f46e5', '#1d4ed8', '#0f766e', '#ca8a04', '#ff6a00', '#be123c'];
    $backgroundPalette = ['#ffffff', '#f8fafc', '#f7f7f7', '#fff7ed', '#f0fdf4', '#eff6ff', '#f5f3ff', '#111827'];
    $themePresets = [
        ['name' => 'Claro limpio', 'brand' => '#111111', 'background' => '#ffffff', 'font' => 'system'],
        ['name' => 'Boutique', 'brand' => '#be123c', 'background' => '#fff7ed', 'font' => 'serif'],
        ['name' => 'Natural', 'brand' => '#0f766e', 'background' => '#f0fdf4', 'font' => 'rounded'],
        ['name' => 'Tecnologia', 'brand' => '#1d4ed8', 'background' => '#eff6ff', 'font' => 'system'],
        ['name' => 'Elegante', 'brand' => '#111827', 'background' => '#f8fafc', 'font' => 'serif'],
        ['name' => 'Energia', 'brand' => '#ff6a00', 'background' => '#fff7ed', 'font' => 'rounded'],
    ];
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

    .theme-presets {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
        margin: 0 0 18px;
    }

    .theme-preset {
        display: grid;
        gap: 10px;
        padding: 12px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        background: #ffffff;
        color: #111827;
        cursor: pointer;
        text-align: left;
    }

    .theme-preset:hover,
    .theme-preset:focus {
        border-color: #111827;
        outline: none;
    }

    .theme-preset-samples {
        display: grid;
        grid-template-columns: 1.1fr 1fr;
        gap: 6px;
        height: 42px;
    }

    .theme-preset-brand,
    .theme-preset-bg {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
    }

    .theme-preset-name {
        font-size: 13px;
        font-weight: 800;
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

    .theme-color-row {
        display: grid;
        grid-template-columns: 58px minmax(0, 1fr);
        gap: 10px;
        align-items: center;
        margin-bottom: 12px;
    }

    .theme-color-picker {
        width: 58px;
        height: 44px;
        margin: 0;
        padding: 4px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        background: #ffffff;
        cursor: pointer;
    }

    .theme-color-row input[type="text"] {
        margin-bottom: 0;
    }

    .theme-contrast-note {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: -2px 0 16px;
        padding: 10px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #ffffff;
        color: #4b5563;
        font-size: 13px;
        line-height: 1.45;
    }

    .theme-contrast-chip {
        width: 34px;
        height: 34px;
        border: 1px solid #d1d5db;
        border-radius: 999px;
        flex-shrink: 0;
    }

    .theme-live-preview {
        display: grid;
        gap: 12px;
        margin: 0 0 18px;
        padding: 14px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: {{ $selectedBackgroundColor }};
        color: {{ $selectedTextColor }};
        font-family: {{ \App\Models\Store::FONT_FAMILIES[$selectedFontFamily]['css'] ?? \App\Models\Store::FONT_FAMILIES['system']['css'] }};
    }

    .theme-live-preview-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 12px;
        border-radius: 10px;
        background: color-mix(in srgb, var(--theme-preview-brand, {{ $selectedBrandColor }}) 10%, #ffffff);
    }

    .theme-live-preview-brand {
        font-size: 16px;
        font-weight: 900;
    }

    .theme-live-preview-link {
        color: inherit;
        font-size: 12px;
        font-weight: 800;
    }

    .theme-live-preview-card {
        display: grid;
        grid-template-columns: 74px minmax(0, 1fr);
        gap: 12px;
        align-items: center;
        padding: 12px;
        border-radius: 12px;
        background: #ffffff;
        color: #111111;
        box-shadow: 0 12px 30px rgba(17, 24, 39, 0.08);
    }

    .theme-live-preview-media {
        width: 74px;
        aspect-ratio: 1;
        border-radius: 10px;
        background: linear-gradient(135deg, var(--theme-preview-brand, {{ $selectedBrandColor }}), #f7f7f7);
    }

    .theme-live-preview-copy {
        min-width: 0;
    }

    .theme-live-preview-copy strong {
        display: block;
        margin-bottom: 5px;
        font-size: 14px;
    }

    .theme-live-preview-copy span {
        display: block;
        margin-bottom: 10px;
        color: #4b5563;
        font-size: 12px;
    }

    .theme-live-preview-button {
        width: fit-content;
        padding: 8px 12px;
        border-radius: 999px;
        background: var(--theme-preview-brand, {{ $selectedBrandColor }});
        color: var(--theme-preview-brand-contrast, {{ \App\Support\BrandTheme::from($selectedBrandColor)->contrast }});
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
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
        .theme-color-row {
            grid-template-columns: 1fr;
        }

        .theme-color-picker {
            width: 100%;
        }

        .font-preview-grid {
            grid-template-columns: 1fr;
        }

        .theme-live-preview-card {
            grid-template-columns: 1fr;
        }

        .theme-live-preview-media {
            width: 100%;
            aspect-ratio: 1.8;
        }
    }
</style>

<div class="theme-panel" data-theme-panel>
    <div class="theme-title">Tema de la tienda</div>

    <label class="field-label">Vista previa en vivo</label>
    <div
        class="theme-live-preview"
        data-theme-preview
        style="--theme-preview-brand: {{ $selectedBrandColor }}; --theme-preview-brand-contrast: {{ \App\Support\BrandTheme::from($selectedBrandColor)->contrast }};"
    >
        <div class="theme-live-preview-nav">
            <span class="theme-live-preview-brand">{{ $store?->name ?: 'NovaShop' }}</span>
            <span class="theme-live-preview-link">Menu</span>
        </div>
        <div class="theme-live-preview-card">
            <span class="theme-live-preview-media" aria-hidden="true"></span>
            <div class="theme-live-preview-copy">
                <strong>Producto destacado</strong>
                <span>Tarjeta blanca sobre el fondo de la tienda.</span>
                <span class="theme-live-preview-button">Comprar ahora</span>
            </div>
        </div>
    </div>

    <label class="field-label">Paletas prearmadas</label>
    <div class="theme-presets" aria-label="Paletas prearmadas">
        @foreach($themePresets as $preset)
            <button
                type="button"
                class="theme-preset"
                data-theme-preset
                data-theme-preset-brand="{{ $preset['brand'] }}"
                data-theme-preset-background="{{ $preset['background'] }}"
                data-theme-preset-font="{{ $preset['font'] }}"
            >
                <span class="theme-preset-samples" aria-hidden="true">
                    <span class="theme-preset-brand" style="background: {{ $preset['brand'] }};"></span>
                    <span class="theme-preset-bg" style="background: {{ $preset['background'] }};"></span>
                </span>
                <span class="theme-preset-name">{{ $preset['name'] }}</span>
            </button>
        @endforeach
    </div>

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
    <div class="theme-color-row">
        <input class="theme-color-picker" type="color" value="{{ $selectedBrandColor }}" data-theme-picker="brand_color" aria-label="Elegir cualquier color principal">
        <input id="brand_color" type="text" name="brand_color" value="{{ $selectedBrandColor }}" placeholder="Color principal (#111111)" data-theme-text="brand_color">
    </div>

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
    <div class="theme-color-row">
        <input class="theme-color-picker" type="color" value="{{ $selectedBackgroundColor }}" data-theme-picker="background_color" aria-label="Elegir cualquier color de fondo">
        <input id="background_color" type="text" name="background_color" value="{{ $selectedBackgroundColor }}" placeholder="Color de fondo (#ffffff)" data-theme-text="background_color">
    </div>

    <input id="text_color" type="hidden" name="text_color" value="{{ $selectedTextColor }}" data-theme-text="text_color">
    <div class="theme-contrast-note">
        <span class="theme-contrast-chip" data-theme-contrast-chip style="background: {{ $selectedTextColor }};"></span>
        <span>El color de letras se ajusta automaticamente para mantener buen contraste con el fondo.</span>
    </div>

    <label class="field-label" for="font_family">Fuente</label>
    <div class="font-preview-grid" aria-label="Ejemplos de fuente">
        @foreach(\App\Models\Store::FONT_FAMILIES as $value => $font)
            <button
                type="button"
                class="font-preview-option @if($selectedFontFamily === $value) is-selected @endif"
                data-font-value="{{ $value }}"
                data-font-css="{{ $font['css'] }}"
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
                const normalizeHex = (value) => {
                    const color = String(value || '').trim();

                    if (/^#[0-9a-fA-F]{6}$/.test(color)) {
                        return color;
                    }

                    if (/^#[0-9a-fA-F]{3}$/.test(color)) {
                        return '#' + color.slice(1).split('').map((char) => char + char).join('');
                    }

                    return null;
                };

                const getContrastColor = (value) => {
                    const normalizedColor = normalizeHex(value) || '#ffffff';
                    const expandedColor = normalizedColor.length === 4
                        ? '#' + normalizedColor.slice(1).split('').map((char) => char + char).join('')
                        : normalizedColor;
                    const red = parseInt(expandedColor.slice(1, 3), 16);
                    const green = parseInt(expandedColor.slice(3, 5), 16);
                    const blue = parseInt(expandedColor.slice(5, 7), 16);
                    const luminance = ((0.299 * red) + (0.587 * green) + (0.114 * blue)) / 255;

                    return luminance < 0.55 ? '#ffffff' : '#111111';
                };

                const updateLivePreview = () => {
                    const preview = panel.querySelector('[data-theme-preview]');
                    const brandColor = normalizeHex(panel.querySelector('[data-theme-text="brand_color"]')?.value) || '#111111';
                    const backgroundColor = normalizeHex(panel.querySelector('[data-theme-text="background_color"]')?.value) || '#ffffff';
                    const textColor = panel.querySelector('[data-theme-text="text_color"]')?.value || getContrastColor(backgroundColor);
                    const selectedFont = panel.querySelector('[data-font-value].is-selected');
                    const fontCss = selectedFont?.dataset.fontCss || 'Arial, sans-serif';

                    if (! preview) {
                        return;
                    }

                    preview.style.setProperty('--theme-preview-brand', brandColor);
                    preview.style.setProperty('--theme-preview-brand-contrast', getContrastColor(brandColor));
                    preview.style.background = backgroundColor;
                    preview.style.color = textColor;
                    preview.style.fontFamily = fontCss;
                };

                const syncColorControls = (target, value) => {
                    const normalizedColor = normalizeHex(value);
                    const textInput = panel.querySelector(`[data-theme-text="${target}"]`);
                    const pickerInput = panel.querySelector(`[data-theme-picker="${target}"]`);

                    if (textInput && normalizedColor) {
                        textInput.value = normalizedColor;
                    }

                    if (pickerInput && normalizedColor) {
                        pickerInput.value = normalizedColor;
                    }

                    panel
                        .querySelectorAll(`[data-theme-target="${target}"]`)
                        .forEach((item) => item.classList.toggle('is-selected', normalizedColor && item.dataset.themeColor.toLowerCase() === normalizedColor.toLowerCase()));

                    if (target === 'background_color' && normalizedColor) {
                        const automaticTextColor = getContrastColor(normalizedColor);
                        const textColorInput = panel.querySelector('[data-theme-text="text_color"]');
                        const contrastChip = panel.querySelector('[data-theme-contrast-chip]');

                        if (textColorInput) {
                            textColorInput.value = automaticTextColor;
                        }

                        if (contrastChip) {
                            contrastChip.style.background = automaticTextColor;
                        }
                    }

                    updateLivePreview();
                };

                panel.querySelectorAll('[data-theme-color]').forEach((swatch) => {
                    swatch.addEventListener('click', () => {
                        syncColorControls(swatch.dataset.themeTarget, swatch.dataset.themeColor);
                    });
                });

                panel.querySelectorAll('[data-theme-picker]').forEach((picker) => {
                    picker.addEventListener('input', () => {
                        syncColorControls(picker.dataset.themePicker, picker.value);
                    });
                });

                panel.querySelectorAll('[data-theme-text]').forEach((input) => {
                    input.addEventListener('input', () => {
                        syncColorControls(input.dataset.themeText, input.value);
                    });
                });

                panel.querySelectorAll('[data-theme-text]').forEach((input) => {
                    syncColorControls(input.dataset.themeText, input.value);
                });

                const fontSelect = panel.querySelector('#font_family');

                const syncFontControls = (value) => {
                    if (! fontSelect) {
                        return;
                    }

                    fontSelect.value = value;

                    panel
                        .querySelectorAll('[data-font-value]')
                        .forEach((item) => item.classList.toggle('is-selected', item.dataset.fontValue === value));

                    updateLivePreview();
                };

                panel.querySelectorAll('[data-theme-preset]').forEach((preset) => {
                    preset.addEventListener('click', () => {
                        syncColorControls('brand_color', preset.dataset.themePresetBrand);
                        syncColorControls('background_color', preset.dataset.themePresetBackground);
                        syncFontControls(preset.dataset.themePresetFont || 'system');
                    });
                });

                panel.querySelectorAll('[data-font-value]').forEach((option) => {
                    option.addEventListener('click', () => {
                        syncFontControls(option.dataset.fontValue);
                    });
                });

                updateLivePreview();
            });
        })();
    </script>
@endPushOnce
