<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,  minimum-scale=0.8, maximum-scale = 0.8, user-scalable = no , shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="shortcut icon" href="{{ settings()->favicon_image }}" type="image/x-icon">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{static_asset('backend')}}/vendor/bootstrap-five/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css">
 <link href='https://fonts.googleapis.com/css?family=Cairo' rel='stylesheet'>
    <link rel="stylesheet" href="{{static_asset('backend')}}/libs/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" integrity="sha512-MV7K8+y+gLIBoVD59lQIYicR65iaqukzvf/nwasF0nqhPay5w/9lJmVM2hMDcnK1OnMGCdVK+iQrJ7lzPJQd1w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.17.0/dist/tabler-icons.min.css">

    <link rel="stylesheet" href="{{static_asset('backend')}}/vendor/fonts/fontawesome/css/fontawesome-all.css">
    <link rel="stylesheet" href="{{static_asset('backend')}}/vendor/charts/chartist-bundle/chartist.css">
    <link rel="stylesheet" href="{{static_asset('backend')}}/vendor/charts/morris-bundle/morris.css">
    <link rel="stylesheet" href="{{static_asset('backend')}}/vendor/fonts/material-design-iconic-font/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="{{static_asset('backend')}}/vendor/charts/c3charts/c3.css">
    <link rel="stylesheet" href="{{static_asset('backend')}}/vendor/fonts/flag-icon-css/flag-icon.min.css">
    <link rel="stylesheet" href="{{static_asset('backend')}}/libs/css/datepicker.min.css">
    <link rel="stylesheet" href="{{static_asset('backend')}}/libs/css/custom.css">
    <link rel="stylesheet" href="{{static_asset('backend')}}/css/custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/6.5.1/css/flag-icons.min.css" />
    <link rel="stylesheet" href="{{ static_asset('backend/vendor') }}/toastr/toastr.min.css">

    {{-- Tailwind Play CDN, namespaced under `tw-` so utilities don't collide with the
         globally-loaded Bootstrap 4/5 classes used elsewhere on these legacy pages. --}}
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            prefix: 'tw-',
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Cairo', 'Inter', 'system-ui', 'sans-serif'] },
                    colors: {
                        brand: {
                            50:  '#fdf2f8',
                            100: '#fce7f3',
                            200: '#fbcfe8',
                            300: '#f9a8d4',
                            400: '#ec4899',
                            500: '#d43782',
                            600: '#a21f5c',
                            700: '#831d4d',
                            800: '#6b1640',
                            900: '#4a0e2c',
                        },
                    },
                    boxShadow: {
                        'card': '0 1px 2px 0 rgb(0 0 0 / 0.04), 0 1px 3px 0 rgb(0 0 0 / 0.06)',
                        'card-hover': '0 4px 12px -2px rgb(0 0 0 / 0.08), 0 2px 4px 0 rgb(0 0 0 / 0.06)',
                    },
                },
            },
        };
    </script>
    <style>
        /* Modern surface tokens for redesigned pages */
        .tw-surface { background:#fff; border:1px solid #eef0f3; border-radius:12px; }
        .tw-surface-muted { background:#fafafa; border:1px solid #eef0f3; border-radius:12px; }
        .tw-divider { border-top:1px solid #eef0f3; }
        /* RTL-safe utility helpers */
        [dir="rtl"] .tw-rtl-flip { transform:scaleX(-1); }
        /* Smooth focus rings on Tailwind-styled inputs without affecting Bootstrap ones */
        .tw-input:focus, .tw-select:focus, .tw-textarea:focus {
            outline:none;
            border-color:#a21f5c;
            box-shadow:0 0 0 3px rgba(162,31,92,.15);
        }
    </style>

    <!-- push target to head -->
    @stack('styles')

      <style>
      html , body , *   {
        font-family: 'Cairo';
    }
     </style>
    {{-- Per-status custom badge colors (see app/Support/ParcelStatusHelper.php $colorMap) --}}
    {!! \App\Support\ParcelStatusHelper::styleBlock() !!}
    <title>@yield('title')</title>
</head>
<body >
    <!-- main wrapper -->
    <div class="dashboard-main-wrapper login-dashboard-main-wrapper">

