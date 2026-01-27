@extends('base')

@section('title', koel_branding('name'))

@push('scripts')
    <script>
        window.LOCALE = @json(app()->getLocale());
        window.MAILER_CONFIGURED = @json(mailer_configured());
        window.SSO_PROVIDERS = @json(collect_sso_providers());
        window.ACCEPTED_AUDIO_EXTENSIONS = @json(collect_accepted_audio_extensions());

        @if (isset($consent_legal_urls))
            window.CONSENT_LEGAL_URLS = @json($consent_legal_urls);
        @else
            window.CONSENT_LEGAL_URLS = null;
        @endif

        @if (session()->has('demo_account'))
            window.DEMO_ACCOUNT = @json(session('demo_account'));
        @elseif (isset($token))
            window.AUTH_TOKEN = @json($token);
        @endif
    </script>
    @vite(['resources/assets/js/app.ts'])
@endpush
