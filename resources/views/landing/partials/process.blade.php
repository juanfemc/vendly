<section class="landing-section process-section" id="proceso">
    <div class="landing-shell process-grid">
        <div>
            <div class="section-kicker">Cómo funciona</div>
            <div class="section-head">
                <h2>Pasas de vender por mensajes sueltos a vender con una tienda clara.</h2>
                <p>Nos enfocamos en que tu cliente vea, entienda y compre sin que tengas que explicar todo desde cero.</p>
            </div>
        </div>

        <div class="steps">
            @foreach($steps as $step)
                <article class="card step-card">
                    <strong>{{ $step['title'] }}</strong>
                    <p>{{ $step['copy'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
