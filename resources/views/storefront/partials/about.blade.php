@if($showAboutSection ?? false)
    <section class="store-about" id="quienes-somos">
        <div class="store-about-copy">
            <h2>Quiénes somos</h2>
            @if(trim((string) $store->shop_copy) !== '')
                <p>{{ $store->shop_copy }}</p>
            @endif
        </div>

        <div class="store-about-values">
            <article>
                <h3>Mision</h3>
                <p>{{ $store->mission }}</p>
            </article>

            <article>
                <h3>Vision</h3>
                <p>{{ $store->vision }}</p>
            </article>
        </div>
    </section>
@endif
