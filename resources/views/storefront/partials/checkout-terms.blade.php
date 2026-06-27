@if($store?->requiresTermsAcceptance())
    @php
        $termsMode = $mode ?? 'default';
        $termsTitle = $store->termsAcceptanceTitle();
        $termsCopy = trim(strip_tags((string) $store->terms_content));
        $termsLink = trim((string) $store->terms_url);
    @endphp

    <div class="checkout-terms checkout-terms--{{ $termsMode }}">
        <label class="checkout-terms-label">
            <input
                type="checkbox"
                name="terms_acceptance"
                value="1"
                @checked(old('terms_acceptance'))
                required
            >
            <span>
                <strong>{{ $termsTitle }}</strong>
                @if($termsLink !== '')
                    <a href="{{ $termsLink }}" target="_blank" rel="noopener noreferrer">Ver terminos</a>
                @endif
            </span>
        </label>

        @if($termsCopy !== '')
            <p class="checkout-terms-copy">{{ \Illuminate\Support\Str::limit($termsCopy, 220) }}</p>
        @endif

        @error('terms_acceptance')
            <p class="checkout-terms-error">{{ $message }}</p>
        @enderror
    </div>
@endif
