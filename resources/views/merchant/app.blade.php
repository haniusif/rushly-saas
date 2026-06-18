<!DOCTYPE html>
@php
    // Resolve brand for the initial server-rendered <head>. The same helper drives the
    // Inertia `brand` prop (HandleInertiaRequests::brand()) so there's no flash on mount.
    $__brand        = merchantBrand() ?? [];
    $__brandName    = $__brand['name'] ?? config('app.name', 'Merchant');
    $__brandLogo    = $__brand['logo'] ?? null;
    $__brandFavicon = $__brand['favicon'] ?? null;
    $__brandPrimary = $__brand['primary_color'] ?? '#a21f5c';
    $__brandDesc    = $__brandName.' — '.__('merchant.dashboard');
    $__locale       = str_replace('_', '-', app()->getLocale());
    $__dir          = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
@endphp
<html lang="{{ $__locale }}" dir="{{ $__dir }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" inertia>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light" inertia>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    {{-- Identity --}}
    <title inertia>{{ $__brandName }}</title>
    <meta name="application-name" content="{{ $__brandName }}" inertia>
    <meta name="apple-mobile-web-app-title" content="{{ $__brandName }}" inertia>
    <meta name="description" content="{{ $__brandDesc }}" inertia>
    <meta name="theme-color" content="{{ $__brandPrimary }}" inertia>

    {{-- Icons --}}
    @if($__brandFavicon)
        <link rel="icon" type="image/png" href="{{ $__brandFavicon }}" inertia>
        <link rel="shortcut icon" type="image/png" href="{{ $__brandFavicon }}" inertia>
        <link rel="apple-touch-icon" href="{{ $__brandFavicon }}" inertia>
    @endif

    {{-- Social preview --}}
    <meta property="og:type" content="website" inertia>
    <meta property="og:title" content="{{ $__brandName }}" inertia>
    <meta property="og:description" content="{{ $__brandDesc }}" inertia>
    @if($__brandLogo)
        <meta property="og:image" content="{{ $__brandLogo }}" inertia>
    @endif
    <meta property="og:locale" content="{{ $__locale }}" inertia>
    <meta property="og:site_name" content="{{ $__brandName }}" inertia>
    <meta name="twitter:card" content="summary" inertia>
    <meta name="twitter:title" content="{{ $__brandName }}" inertia>
    <meta name="twitter:description" content="{{ $__brandDesc }}" inertia>
    @if($__brandLogo)
        <meta name="twitter:image" content="{{ $__brandLogo }}" inertia>
    @endif

    {{-- Robots: this is an authenticated portal, don't index --}}
    <meta name="robots" content="noindex, nofollow">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|cairo:400,500,600,700|tajawal:400,500,700|roboto:400,500,700&display=swap" rel="stylesheet">

    @routes

    {{-- Manually emit Vite tags via global_asset() so Stancl Tenancy's asset_helper_tenancy
         doesn't rewrite them to /tenancy/assets/... (which 404s and returns text/html). --}}
    @php
        $__viteManifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $__cssEntry = $__viteManifest['resources/css/merchant.css']['file'] ?? null;
        $__jsEntry  = $__viteManifest['resources/js/merchant.jsx']['file'] ?? null;
    @endphp
    @if($__cssEntry)
        <link rel="stylesheet" href="{{ global_asset('build/'.$__cssEntry) }}">
    @endif
    @if($__jsEntry)
        <script type="module" src="{{ global_asset('build/'.$__jsEntry) }}"></script>
    @endif

    @inertiaHead
</head>
<body class="font-sans antialiased">
    @inertia
</body>
</html>
