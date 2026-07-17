

<!doctype html>
@php
    // Match the app locale + direction so error pages don't force LTR/EN
    // on an Arabic session. Fallback to en/ltr when locale detection fails.
    $__errLocale = app()->getLocale() ?: 'en';
    $__errDir    = in_array($__errLocale, ['ar','he','fa','ur'], true) ? 'rtl' : 'ltr';
@endphp
<html lang="{{ $__errLocale }}" dir="{{ $__errDir }}">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap CSS -->

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css">
    <link href="{{static_asset('backend')}}/vendor/fonts/circular-std/style.css" rel="stylesheet">
    <link rel="stylesheet" href="{{static_asset('backend')}}/libs/css/style.css">
    {{-- Single FA build (CDN v6). The local fontawesome-all.css was FA5 and
         was duplicating the same icons at ~200 KB extra. --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <title>@yield('title')</title>
</head> 
<body class="bg-light">
   <!-- ============================================================== -->
    <!-- main wrapper -->
    <!-- ============================================================== -->
    <div class="dashboard-main-wrapper p-0">
        <!-- ============================================================== -->
        <!-- navbar -->
        <!-- ============================================================== -->
        <nav class="navbar navbar-expand dashboard-top-header bg-white">
            <div class="container-fluid">
                <!-- ============================================================== -->
                <!-- brand logo -->
                <!-- ============================================================== -->
                <div class="dashboard-nav-brand">
                </div>
                <!-- ============================================================== -->
                <!-- end brand logo -->
                <!-- ============================================================== -->
            </div>
        </nav>
        <!-- ============================================================== -->
        <!-- end navbar -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- wrapper  -->
        <!-- ============================================================== -->
        <div class="bg-light text-center">
            <div class="container">
                <div class="row">
                    <div class="offset-xl-2 col-xl-8 offset-lg-2 col-lg-8 col-md-12 col-sm-12 col-12">
                        <div class="error-section">
                            <div class="error-section-content">
                                @yield('logo')
                                <h1 class="display-3 text-secondary" style="font-size: 10em">@yield('code')</h1>
                                <h1 class="text-secondary">@yield('message-headline')</h1>
                                <h2  >@yield('message-title')</h2>
                                <div> @yield('message')</div>
                                @php
                                    // Try tenant name first; fall back to the app config name so
                                    // this never renders as "Contact with " on a broken tenant.
                                    try { $__errBrand = optional(\App\Models\Backend\GeneralSettings::find(1))->name; } catch (\Throwable $e) { $__errBrand = null; }
                                    $__errBrand = $__errBrand ?: config('app.name');
                                @endphp
                                @if(isset($administrator_contact))
                                    <a href="{{ url(config('app.url')) }}" class="btn btn-secondary btn-lg">{{ __('Contact') }} {{ $__errBrand }}</a>
                                @else
                                    <a href="{{ url('/') }}" class="btn btn-secondary btn-lg">{{ __('Back to homepage') }}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- footer -->
        <!-- ============================================================== -->
        <div class="footer fixed-bottom">
            <div class="container-fluid">
                <div class="row">
                    <div class=" col-12">
                            &copy; {{ date('Y') }} {{ $__errBrand }}. {{ __('All rights reserved.') }}
                    </div>

                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- end footer -->
        <!-- ============================================================== -->
        </div>
    </div>
    <!-- ============================================================== -->
    <!-- end main wrapper -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- end main wrapper -->
    <!-- ============================================================== -->
    {{-- No JS on error pages. The previous ../assets/vendor/… paths were
         relative and 404'd from any URL that wasn't at depth-1; the pages
         don't rely on JS to render anyway. --}}
</body>

</html>
