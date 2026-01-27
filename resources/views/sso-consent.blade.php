<!DOCTYPE html>
<html lang="es">
<head>
    <title>Aceptar Condiciones | {{ koel_branding('name') }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#282828">

    @php
        $branding = koel_branding();
    @endphp

    <link rel="icon" href="{{ koel_branding('logo') ?? static_url('img/icon.png') }}">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #181818;
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .container {
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 2rem;
            max-width: 400px;
            width: 100%;
        }

        .logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .logo img {
            max-width: 120px;
            height: auto;
        }

        h1 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            text-align: center;
        }

        .user-info {
            background-color: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }

        .user-info p {
            margin: 0.25rem 0;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .user-info strong {
            color: #fff;
        }

        .checkbox-group {
            margin-bottom: 1.5rem;
        }

        .checkbox-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 1rem;
            cursor: pointer;
        }

        .checkbox-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            cursor: pointer;
            accent-color: #f97316;
        }

        .checkbox-item label {
            font-size: 0.875rem;
            line-height: 1.4;
            cursor: pointer;
        }

        .checkbox-item a {
            color: #f97316;
            text-decoration: none;
        }

        .checkbox-item a:hover {
            text-decoration: underline;
        }

        .error-message {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .btn {
            width: 100%;
            padding: 0.875rem;
            background-color: #f97316;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn:hover {
            background-color: #ea580c;
        }

        .btn:disabled {
            background-color: rgba(249, 115, 22, 0.5);
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        @if($branding->logo)
        <div class="logo">
            <img src="{{ $branding->logo }}" alt="{{ koel_branding('name') }}">
        </div>
        @endif

        <h1>Completar Registro</h1>

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
                        Acepto los <a href="{{ config('app.terms_url', '#') }}" target="_blank" rel="noopener">Términos y Condiciones</a>
                    </label>
                </div>

                <div class="checkbox-item">
                    <input type="checkbox" name="privacy_accepted" id="privacy_accepted" value="1" required>
                    <label for="privacy_accepted">
                        Acepto la <a href="{{ config('app.privacy_url', '#') }}" target="_blank" rel="noopener">Política de Privacidad</a>
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
</body>
</html>
