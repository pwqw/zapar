<!DOCTYPE html>
<html lang="en">
<head>
    <title>@yield('title')</title>

    <meta name="description" content="{{ koel_tagline() }}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">

    <meta name="theme-color" content="#282828">
    <meta name="msapplication-navbutton-color" content="#282828">

    @php
        $og = koel_opengraph();
        $branding = koel_branding();
        $ogImageFileName = koel_opengraph('image');
        $defaultOgImage = $ogImageFileName ? image_storage_url($ogImageFileName) : $branding->logo;
        $ogTitle = $og_title ?? koel_branding('name');
        $ogDescription = $og_description ?? koel_tagline();
        $ogImage = $og_image ?? $defaultOgImage;
        $ogUrl = $og_url ?? url()->current();
        $ogType = $og_type ?? 'website';
    @endphp
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:site_name" content="{{ koel_branding('name') }}">
    @if($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
    @endif
    <meta property="og:url" content="{{ $ogUrl }}">

    <link rel="manifest" href="{{ static_url('manifest.json') }}" />
    <meta name="msapplication-config" content="{{ static_url('browserconfig.xml') }}" />
    <link rel="icon" type="image/x-icon" href="{{ $branding->favicon ?? koel_branding('logo') ?? static_url('img/favicon.ico') }}" />
    <link rel="icon" href="{{ koel_branding('logo') ?? static_url('img/icon.png') }}">
    <link rel="apple-touch-icon" href="{{ koel_branding('logo') ?? static_url('img/icon.png') }}">

    <script>
        // Work around for "global is not defined" error with local-storage.js
        window.global = window
    </script>
</head>
<body class="text-k-fg-70">
<div id="app"></div>

<script>
    window.BASE_URL = @json(base_url());
    window.IS_DEMO = @json(config('koel.misc.demo'));
    window.ALLOW_ANONYMOUS = @json(config('koel.misc.allow_anonymous'));

    window.PUSHER_APP_KEY = @json(config('broadcasting.connections.pusher.key'));
    window.PUSHER_APP_CLUSTER = @json(config('broadcasting.connections.pusher.options.cluster'));

    window.BRANDING = @json(koel_branding());
    window.WELCOME_MESSAGE = @json(koel_welcome_message());
</script>

@stack('scripts')
</body>
</html>
