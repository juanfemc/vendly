<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Crear tienda gratis | Vendly</title>
    <style>
        :root {
            --bg: #070707;
            --panel: #111111;
            --card: #ffffff;
            --ink: #111111;
            --muted: #686868;
            --line: #e9e9e9;
            --brand: #ff6b00;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background:
                radial-gradient(circle at 18% 12%, rgba(255, 107, 0, 0.22), transparent 28%),
                linear-gradient(135deg, #050505 0%, #111111 48%, #19110b 100%);
            color: #ffffff;
        }

        a {
            color: inherit;
        }

        .signup-shell {
            width: min(1120px, calc(100% - 32px));
            min-height: 100vh;
            margin: 0 auto;
            display: grid;
            grid-template-columns: minmax(0, 0.86fr) minmax(420px, 1fr);
            gap: 42px;
            align-items: center;
            padding: 42px 0;
        }

        .signup-copy {
            display: grid;
            gap: 24px;
        }

        .signup-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 28px;
            font-weight: 900;
            letter-spacing: -0.04em;
        }

        .signup-brand img {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            object-fit: cover;
        }

        .signup-copy h1 {
            margin: 0;
            font-size: clamp(38px, 5vw, 68px);
            line-height: 0.95;
            letter-spacing: -0.06em;
        }

        .signup-copy h1 span {
            color: var(--brand);
        }

        .signup-copy p {
            max-width: 520px;
            margin: 0;
            color: rgba(255, 255, 255, 0.72);
            font-size: 17px;
            line-height: 1.7;
        }

        .signup-benefits {
            display: grid;
            gap: 12px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .signup-benefits li {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255, 255, 255, 0.82);
            font-size: 14px;
            font-weight: 700;
        }

        .signup-benefits li::before {
            content: "";
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--brand);
            box-shadow: 0 0 0 6px rgba(255, 107, 0, 0.14);
        }

        .signup-card {
            border-radius: 26px;
            background: var(--card);
            color: var(--ink);
            box-shadow: 0 30px 90px rgba(0, 0, 0, 0.32);
            overflow: hidden;
        }

        .signup-card-head {
            padding: 30px 30px 18px;
            border-bottom: 1px solid var(--line);
        }

        .signup-card-head span {
            display: inline-flex;
            min-height: 28px;
            align-items: center;
            padding: 0 11px;
            border-radius: 999px;
            background: rgba(255, 107, 0, 0.12);
            color: #b84a00;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .signup-card-head h2 {
            margin: 14px 0 6px;
            font-size: 28px;
            line-height: 1.12;
            letter-spacing: -0.04em;
        }

        .signup-card-head p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.55;
        }

        .signup-form {
            display: grid;
            gap: 18px;
            padding: 26px 30px 30px;
        }

        .field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .field {
            display: grid;
            gap: 7px;
        }

        .field--full {
            grid-column: 1 / -1;
        }

        .field label {
            color: #232323;
            font-size: 13px;
            font-weight: 800;
        }

        .field input,
        .field select {
            width: 100%;
            min-height: 48px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: #fafafa;
            color: #111111;
            padding: 0 14px;
            font: inherit;
            outline: none;
            transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .field input:focus,
        .field select:focus {
            border-color: var(--brand);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(255, 107, 0, 0.12);
        }

        .error {
            color: #b42318;
            font-size: 12px;
            line-height: 1.4;
        }

        .signup-submit {
            width: 100%;
            min-height: 52px;
            border: 0;
            border-radius: 999px;
            background: var(--brand);
            color: #111111;
            font-size: 15px;
            font-weight: 900;
            cursor: pointer;
            box-shadow: 0 18px 38px rgba(255, 107, 0, 0.28);
        }

        .consent-field {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .consent-field input {
            width: 17px;
            height: 17px;
            margin: 1px 0 0;
            accent-color: var(--brand);
            flex: 0 0 auto;
        }

        .signup-login {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
            text-align: center;
        }

        .signup-login a {
            color: #111111;
            font-weight: 900;
        }

        .turnstile-box {
            display: grid;
            gap: 8px;
            grid-column: 1 / -1;
        }

        .turnstile-box .cf-turnstile {
            max-width: 100%;
            overflow: hidden;
        }

        .security-warning {
            grid-column: 1 / -1;
            border: 1px solid #fed7aa;
            border-radius: 14px;
            background: #fff7ed;
            color: #9a3412;
            padding: 12px 14px;
            font-size: 13px;
            line-height: 1.45;
        }

        @media (max-width: 900px) {
            .signup-shell {
                grid-template-columns: 1fr;
                gap: 28px;
                align-items: start;
                padding-top: 28px;
            }

            .signup-copy {
                gap: 18px;
            }

            .signup-copy p {
                font-size: 15px;
            }
        }

        @media (max-width: 560px) {
            .signup-shell {
                width: min(100% - 20px, 1120px);
                padding: 18px 0;
            }

            .signup-brand {
                font-size: 24px;
            }

            .signup-copy h1 {
                font-size: 36px;
            }

            .signup-card {
                border-radius: 20px;
            }

            .signup-card-head,
            .signup-form {
                padding-left: 18px;
                padding-right: 18px;
            }

            .field-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    @if($requiresWhatsAppVerification && $turnstileSiteKey)
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
</head>

<body>
    <main class="signup-shell">
        <section class="signup-copy" aria-label="Beneficios">
            <a href="{{ url('/') }}" class="signup-brand">
                <img src="{{ asset('images/vendly-whatsapp-dark.png') }}" alt="">
                <span>vendly</span>
            </a>

            <h1>Crea tu tienda y vende por <span>WhatsApp</span>.</h1>
            <p>Activa una prueba gratis de {{ $trialDays }} dias. No necesitas tarjeta de credito y puedes empezar con tu catalogo en minutos.</p>

            <ul class="signup-benefits">
                <li>Usuario y tienda creados automaticamente</li>
                <li>Prueba Premium durante {{ $trialDays }} dias</li>
                <li>Panel listo para subir productos y compartir tu enlace</li>
            </ul>
        </section>

        <section class="signup-card" aria-label="Formulario de registro">
            <div class="signup-card-head">
                <span>Gratis {{ $trialDays }} dias</span>
                <h2>Empieza tu tienda</h2>
                <p>Completa estos datos y entraremos directo al panel de administracion.</p>
            </div>

            <form class="signup-form" method="POST" action="{{ route('trial-signup.store') }}">
                @csrf

                <div class="field-grid">
                    <div class="field">
                        <label for="user_name">Tu nombre</label>
                        <input id="user_name" name="user_name" value="{{ old('user_name') }}" required autocomplete="name">
                        @error('user_name')<div class="error">{{ $message }}</div>@enderror
                    </div>

                    <div class="field">
                        <label for="user_email">Correo</label>
                        <input id="user_email" type="email" name="user_email" value="{{ old('user_email') }}" required autocomplete="email">
                        @error('user_email')<div class="error">{{ $message }}</div>@enderror
                    </div>

                    <div class="field">
                        <label for="store_name">Nombre de la tienda</label>
                        <input id="store_name" name="store_name" value="{{ old('store_name') }}" required autocomplete="organization">
                        @error('store_name')<div class="error">{{ $message }}</div>@enderror
                    </div>

                    <div class="field">
                        <label for="whatsapp">WhatsApp de pedidos</label>
                        <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" required inputmode="tel" autocomplete="tel" placeholder="Ej. 300 123 4567">
                        @error('whatsapp')<div class="error">{{ $message }}</div>@enderror
                    </div>

                    @if($requiresWhatsAppVerification)
                        <div class="field">
                            <label for="whatsapp_verification_code">Codigo de verificacion</label>
                            <div style="display:flex;gap:8px">
                                <input id="whatsapp_verification_code" name="whatsapp_verification_code" value="{{ old('whatsapp_verification_code') }}" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="000000">
                                <button id="send-whatsapp-code" type="button" style="white-space:nowrap" @disabled($requiresTurnstile && ! $turnstileReady)>Enviar codigo</button>
                            </div>
                            <div id="whatsapp-code-status" aria-live="polite"></div>
                            <input id="whatsapp_verification_token" name="whatsapp_verification_token" type="hidden" value="{{ old('whatsapp_verification_token') }}">
                            @error('whatsapp_verification_code')<div class="error">{{ $message }}</div>@enderror
                        </div>

                        @if($turnstileSiteKey)
                            <div class="turnstile-box">
                                <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}"></div>
                                <div id="turnstile-error" class="error" aria-live="polite"></div>
                            </div>
                        @elseif($requiresTurnstile && ! $turnstileReady)
                            <div class="security-warning" role="alert">
                                La proteccion anti abuso no esta configurada. Intenta nuevamente mas tarde.
                            </div>
                        @endif
                    @endif

                    <div class="field">
                        <label for="location">Ciudad o ubicacion</label>
                        <input id="location" name="location" value="{{ old('location') }}" autocomplete="address-level2">
                        @error('location')<div class="error">{{ $message }}</div>@enderror
                    </div>

                    <div class="field">
                        <label for="password">Contrasena</label>
                        <input id="password" type="password" name="password" required autocomplete="new-password">
                        @error('password')<div class="error">{{ $message }}</div>@enderror
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Confirmar contrasena</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                    </div>
                </div>

                <label class="consent-field" for="whatsapp_consent">
                    <input id="whatsapp_consent" name="whatsapp_consent" type="checkbox" value="1" @checked(old('whatsapp_consent')) required>
                    <span>{{ \App\Http\Requests\TrialSignupRequest::WHATSAPP_CONSENT_TEXT }}</span>
                </label>
                @error('whatsapp_consent')<div class="error">{{ $message }}</div>@enderror

                <button class="signup-submit" type="submit">Crear mi tienda gratis</button>

                <p class="signup-login">Ya tienes cuenta? <a href="{{ route('login') }}">Iniciar sesion</a></p>
            </form>
        </section>
    </main>
    @if($requiresWhatsAppVerification)
        <script>
            document.getElementById('send-whatsapp-code')?.addEventListener('click', async (event) => {
                const button = event.currentTarget;
                const status = document.getElementById('whatsapp-code-status');
                button.disabled = true;
                status.textContent = 'Enviando codigo...';

                try {
                    const turnstileToken = document.querySelector('[name="cf-turnstile-response"]')?.value || '';
                    const response = await fetch(@json(route('trial-signup.whatsapp.verify')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                        },
                        body: JSON.stringify({
                            whatsapp: document.getElementById('whatsapp').value,
                            turnstile_token: turnstileToken,
                        }),
                    });
                    const data = await response.json();
                    if (data.verification_token) {
                        document.getElementById('whatsapp_verification_token').value = data.verification_token;
                    }
                    const turnstileError = document.getElementById('turnstile-error');
                    turnstileError?.replaceChildren();
                    status.textContent = data.message || data.errors?.whatsapp?.[0] || data.errors?.turnstile_token?.[0] || 'No se pudo enviar el codigo.';
                    if (turnstileError && data.errors?.turnstile_token?.[0]) {
                        turnstileError.textContent = data.errors.turnstile_token[0];
                    }
                    window.turnstile?.reset();
                } catch (error) {
                    status.textContent = 'No se pudo enviar el codigo. Intenta nuevamente.';
                    window.turnstile?.reset();
                } finally {
                    button.disabled = false;
                }
            });
        </script>
    @endif
</body>

</html>
