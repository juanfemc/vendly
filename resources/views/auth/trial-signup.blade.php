<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Crear tienda gratis | Vendly</title>
    <style>
        :root {
            --bg: #080808;
            --panel: #111111;
            --card: #ffffff;
            --ink: #111111;
            --muted: #656565;
            --line: #e9e9e9;
            --soft: #f7f7f7;
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
                radial-gradient(circle at 16% 10%, rgba(255, 107, 0, 0.2), transparent 26%),
                linear-gradient(135deg, #030303 0%, #101010 52%, #1a120c 100%);
            color: #ffffff;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .signup-shell {
            width: min(1080px, calc(100% - 32px));
            min-height: 100vh;
            margin: 0 auto;
            display: grid;
            grid-template-columns: minmax(0, 0.92fr) minmax(380px, 460px);
            gap: 42px;
            align-items: center;
            padding: 36px 0;
        }

        .signup-copy {
            display: grid;
            gap: 22px;
        }

        .signup-brand {
            display: inline-flex;
            width: fit-content;
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

        .signup-badge {
            width: fit-content;
            min-height: 30px;
            display: inline-flex;
            align-items: center;
            padding: 0 12px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            color: rgba(255, 255, 255, 0.82);
            font-size: 12px;
            font-weight: 800;
        }

        .signup-copy h1 {
            max-width: 620px;
            margin: 0;
            font-size: clamp(38px, 5vw, 66px);
            line-height: 0.96;
            letter-spacing: -0.06em;
        }

        .signup-copy h1 span {
            color: var(--brand);
        }

        .signup-copy p {
            max-width: 520px;
            margin: 0;
            color: rgba(255, 255, 255, 0.74);
            font-size: 16px;
            line-height: 1.65;
        }

        .signup-benefits {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 2px 0 0;
            padding: 0;
            list-style: none;
        }

        .signup-benefits li {
            min-height: 36px;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 0 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.06);
            color: rgba(255, 255, 255, 0.84);
            font-size: 13px;
            font-weight: 700;
        }

        .signup-benefits li::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--brand);
            box-shadow: 0 0 0 5px rgba(255, 107, 0, 0.14);
        }

        .signup-card {
            border-radius: 24px;
            background: var(--card);
            color: var(--ink);
            box-shadow: 0 30px 90px rgba(0, 0, 0, 0.32);
            overflow: hidden;
        }

        .signup-card-head {
            padding: 26px 28px 16px;
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
            line-height: 1.08;
            letter-spacing: -0.04em;
        }

        .signup-card-head p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .signup-form {
            display: grid;
            gap: 15px;
            padding: 22px 28px 28px;
        }

        .field {
            display: grid;
            gap: 7px;
        }

        .field label {
            color: #232323;
            font-size: 13px;
            font-weight: 800;
        }

        .field input {
            width: 100%;
            min-height: 48px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: #fbfbfb;
            color: #111111;
            padding: 0 14px;
            font: inherit;
            outline: none;
            transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .field input:focus {
            border-color: var(--brand);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(255, 107, 0, 0.12);
        }

        .field small {
            color: #777777;
            font-size: 12px;
            line-height: 1.35;
        }

        .password-field {
            position: relative;
        }

        .password-field input {
            padding-right: 92px;
        }

        .password-toggle {
            position: absolute;
            right: 8px;
            top: 8px;
            height: 32px;
            border: 0;
            border-radius: 999px;
            background: #eeeeee;
            color: #111111;
            padding: 0 12px;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }

        .consent-field {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.45;
        }

        .consent-field input {
            width: 17px;
            height: 17px;
            margin: 1px 0 0;
            accent-color: var(--brand);
            flex: 0 0 auto;
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

        .signup-submit:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        .signup-note {
            margin: -2px 0 0;
            color: #777777;
            font-size: 12px;
            text-align: center;
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

        .error {
            color: #b42318;
            font-size: 12px;
            line-height: 1.4;
        }

        .turnstile-box {
            display: grid;
            gap: 8px;
        }

        .turnstile-box .cf-turnstile {
            max-width: 100%;
            overflow: hidden;
        }

        .security-warning {
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
                gap: 26px;
                align-items: start;
                padding-top: 24px;
            }

            .signup-copy {
                gap: 16px;
            }

            .signup-copy h1 {
                max-width: 720px;
            }

            .signup-copy p {
                font-size: 15px;
            }
        }

        @media (max-width: 560px) {
            .signup-shell {
                width: min(100% - 20px, 1080px);
                padding: 16px 0;
            }

            .signup-brand {
                font-size: 23px;
            }

            .signup-brand img {
                width: 38px;
                height: 38px;
            }

            .signup-copy h1 {
                font-size: 34px;
            }

            .signup-benefits {
                display: grid;
            }

            .signup-card {
                border-radius: 20px;
            }

            .signup-card-head,
            .signup-form {
                padding-left: 18px;
                padding-right: 18px;
            }
        }
    </style>
    @if($turnstileSiteKey)
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

            <span class="signup-badge">Prueba gratis {{ $trialDays }} dias</span>
            <h1>Crea tu tienda y empieza a vender por <span>WhatsApp</span>.</h1>
            <p>Entra al panel en segundos. Tu tienda se crea automaticamente y luego completas lo esencial con una guia paso a paso.</p>

            <ul class="signup-benefits">
                <li>Sin tarjeta de credito</li>
                <li>Dashboard inmediato</li>
                <li>Mensajes automaticos</li>
            </ul>
        </section>

        <section class="signup-card" aria-label="Formulario de registro">
            <div class="signup-card-head">
                <span>Inicio rapido</span>
                <h2>Crear mi tienda gratis</h2>
                <p>Solo necesitamos estos datos para crear tu cuenta y activar tu prueba.</p>
            </div>

            <form class="signup-form" method="POST" action="{{ route('trial-signup.store') }}">
                @csrf

                <div class="field">
                    <label for="user_name">Tu nombre</label>
                    <input id="user_name" name="user_name" value="{{ old('user_name') }}" required autocomplete="name" placeholder="Ej. Juan">
                    <small>Asi te saludaremos dentro del panel.</small>
                    @error('user_name')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="whatsapp">WhatsApp</label>
                    <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" required inputmode="tel" autocomplete="tel" placeholder="Ej. 300 123 4567">
                    <small>Lo verificaremos en el dashboard para activar seguridad y mensajes.</small>
                    @error('whatsapp')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="user_email">Correo</label>
                    <input id="user_email" type="email" name="user_email" value="{{ old('user_email') }}" required autocomplete="email" placeholder="tu@email.com">
                    <small>Lo usaremos para iniciar sesion y recuperar tu cuenta.</small>
                    @error('user_email')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="password">Contrasena</label>
                    <div class="password-field">
                        <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="Minimo 8 caracteres">
                        <button class="password-toggle" type="button" data-password-toggle>Mostrar</button>
                    </div>
                    <small>No necesitas confirmarla. Puedes verla antes de enviar.</small>
                    @error('password')<div class="error">{{ $message }}</div>@enderror
                </div>

                <input type="hidden" name="store_name" value="{{ old('store_name') }}">

                @if($turnstileSiteKey)
                    <div class="turnstile-box">
                        <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}"></div>
                        @error('turnstile_token')<div class="error">{{ $message }}</div>@enderror
                    </div>
                @elseif($requiresTurnstile && ! $turnstileReady)
                    <div class="security-warning" role="alert">
                        La proteccion anti abuso no esta configurada. Intenta nuevamente mas tarde.
                    </div>
                @endif

                <label class="consent-field" for="whatsapp_consent">
                    <input id="whatsapp_consent" name="whatsapp_consent" type="checkbox" value="1" @checked(old('whatsapp_consent')) required>
                    <span>{{ \App\Http\Requests\TrialSignupRequest::WHATSAPP_CONSENT_TEXT }}</span>
                </label>
                @error('whatsapp_consent')<div class="error">{{ $message }}</div>@enderror

                <button class="signup-submit" type="submit" @disabled($requiresTurnstile && ! $turnstileReady)>Crear mi tienda gratis</button>
                <p class="signup-note">Gratis {{ $trialDays }} dias. Sin tarjeta. Configuras tu tienda despues.</p>
                <p class="signup-login">Ya tienes cuenta? <a href="{{ route('login') }}">Iniciar sesion</a></p>
            </form>
        </section>
    </main>

    <script>
        document.querySelector('[data-password-toggle]')?.addEventListener('click', (event) => {
            const button = event.currentTarget;
            const input = document.getElementById('password');
            const isHidden = input.type === 'password';

            input.type = isHidden ? 'text' : 'password';
            button.textContent = isHidden ? 'Ocultar' : 'Mostrar';
        });

        document.querySelector('.signup-form')?.addEventListener('submit', (event) => {
            const token = document.querySelector('[name="cf-turnstile-response"]')?.value;

            if (! token) {
                return;
            }

            let input = document.querySelector('[name="turnstile_token"]');

            if (! input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'turnstile_token';
                event.currentTarget.appendChild(input);
            }

            input.value = token;
        });
    </script>
</body>

</html>
