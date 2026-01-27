<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>{{ __('sso.page_title') }} | {{ koel_branding('name') }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#181818">

    @php
        $branding = koel_branding();
    @endphp

    <link rel="icon" href="{{ koel_branding('logo') ?? static_url('img/icon.png') }}">

    <style>
        /* Mismas variables que resources/assets/css/partials/vars.pcss (Login) */
        :root {
            --color-fg: #ffffff;
            --color-bg: #181818;
            --color-highlight: #19d163;
            --color-highlight-fg: #000000;
            --color-danger: #c34848;
            --font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
        }

        /* k-fg-10 = color-mix(in srgb, var(--color-fg), transparent 90%) */
        /* k-fg-70 = color-mix(in srgb, var(--color-fg), transparent 30%) */

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-family);
            background-color: var(--color-bg);
            color: var(--color-fg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .page {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 360px;
            padding: 1.75rem;
            border-radius: 0.75rem;
            border: 1px solid transparent;
            background-color: color-mix(in srgb, var(--color-fg) 10%, transparent);
        }

        .identity {
            text-align: center;
            margin-bottom: 2rem;
        }

        .identity .logo {
            margin-bottom: 0.75rem;
        }

        .identity .logo img {
            width: 156px;
            height: auto;
            display: inline-block;
            vertical-align: middle;
        }

        .identity .site-name {
            font-size: 1rem;
            font-weight: 600;
            color: var(--color-fg);
            margin: 0;
        }

        h1 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            text-align: center;
        }

        .user-info {
            background-color: color-mix(in srgb, var(--color-fg) 10%, transparent);
            padding: 1rem;
            border-radius: 0.25rem;
            margin-bottom: 1.5rem;
        }

        .user-info p {
            margin: 0.25rem 0;
            font-size: 0.875rem;
            color: color-mix(in srgb, var(--color-fg) 70%, transparent);
        }

        .user-info strong {
            color: var(--color-fg);
        }

        .checkbox-group {
            margin-bottom: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .checkbox-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            cursor: pointer;
        }

        .checkbox-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            cursor: pointer;
            accent-color: var(--color-highlight);
        }

        .checkbox-item label {
            font-size: 0.875rem;
            line-height: 1.4;
            cursor: pointer;
        }

        .checkbox-item a {
            color: var(--color-highlight);
            text-decoration: none;
        }

        .checkbox-item a:hover {
            text-decoration: underline;
        }

        .error-message {
            background-color: color-mix(in srgb, var(--color-danger) 15%, transparent);
            border: 1px solid color-mix(in srgb, var(--color-danger) 50%, transparent);
            color: var(--color-danger);
            padding: 0.75rem;
            border-radius: 0.25rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .btn {
            width: 100%;
            padding: 0.5rem 0.875rem;
            background-color: var(--color-highlight);
            color: var(--color-highlight-fg);
            border: none;
            border-radius: 0.25rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: box-shadow 0.15s;
        }

        .btn:hover {
            box-shadow: inset 0 0 0 10rem rgba(0, 0, 0, 0.1);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="page">
    <div class="container">
        <div class="identity">
            <div class="logo">
                <img src="{{ $branding->logo ?? static_url('img/icon.png') }}" alt="{{ koel_branding('name') }}" width="156" height="auto">
            </div>
            <p class="site-name">{{ koel_branding('name') }}</p>
        </div>

        <h1>{{ __('sso.complete_registration') }}</h1>

        <div class="user-info">
            <p><strong>{{ $name }}</strong></p>
            <p>{{ $email }}</p>
        </div>

        @if($errors->any())
        <div class="error-message">
            Debes aceptar todos los términos para continuar.
        </div>
        @endif

        <form method="POST" action="{{ route('sso.consent.store') }}">
            @csrf

            <div class="checkbox-group">
                <div class="checkbox-item">
                    <input type="checkbox" name="terms_accepted" id="terms_accepted" value="1" required>
                    <label for="terms_accepted">
                        Acepto los <a href="{{ $terms_url ?? '#' }}" target="_blank" rel="noopener">Términos y Condiciones</a>
                    </label>
                </div>

                <div class="checkbox-item">
                    <input type="checkbox" name="privacy_accepted" id="privacy_accepted" value="1" required>
                    <label for="privacy_accepted">
                        Acepto la <a href="{{ $privacy_url ?? '#' }}" target="_blank" rel="noopener">Política de Privacidad</a>
                    </label>
                </div>

                <div class="checkbox-item">
                    <input type="checkbox" name="age_verified" id="age_verified" value="1" required>
                    <label for="age_verified">
                        Confirmo que tengo 13 años o más
                    </label>
                </div>
            </div>

            <button type="submit" class="btn">
                Aceptar y Continuar
            </button>
        </form>
    </div>
    </div>
</body>
</html>
