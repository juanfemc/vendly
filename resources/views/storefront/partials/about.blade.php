@if($showAboutSection ?? false)
    <section class="store-about" id="quienes-somos">
        <div class="store-about-copy">
            <span>Sobre nosotros</span>
            <h2>Quienes somos</h2>
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

        <div class="store-about-contact">
            <article>
                <h3>Contacto</h3>
                <a href="https://wa.me/{{ preg_replace('/\D+/', '', $store->whatsapp) }}" target="_blank" rel="noopener noreferrer">
                    WhatsApp: {{ $store->whatsapp }}
                </a>
            </article>

            @if(trim((string) $store->location) !== '')
                <article>
                    <h3>Ubicacion</h3>
                    <p>{{ $store->location }}</p>
                </article>
            @endif

            @if(trim((string) $store->business_hours) !== '')
                <article>
                    <h3>Horario de atencion</h3>
                    <p>{{ $store->business_hours }}</p>
                </article>
            @endif
        </div>
    </section>
@endif
