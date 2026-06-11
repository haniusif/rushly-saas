@php
  $locale = app()->getLocale();
  $isRtl  = in_array($locale, ['ar']);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="{{ @settings()->name }} — {{ __('levels.smart_logistics_platform') }}" />
  <meta name="author" content="{{ @settings()->name }}" />
  <meta property="og:title" content="@yield('title', @settings()->name)" />
  <meta property="og:description" content="{{ @settings()->name }} — {{ __('levels.smart_logistics_platform') }}" />
  <meta property="og:type" content="website" />
  <title>@yield('title', @settings()->name)</title>
  <link rel="icon" type="image/png" href="{{ static_asset('frontend/favicon.png') }}" />

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">

  <!-- Font Awesome (used by icons in some sections) -->
  <link rel="stylesheet" href="{{ static_asset('frontend/css/all.min.css') }}">

  <!-- Swiper (partner carousel) -->
  <link rel="stylesheet" href="{{ static_asset('frontend/css/swiper-bundle.min.css') }}">

  <!-- Odometer (stats) -->
  <link rel="stylesheet" href="{{ static_asset('frontend/css/odometer.css') }}">

  <!-- Tailwind CSS (with typography plugin for prose) -->
  <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary:         '#a21f5c',
            'primary-light': '#f0e6ee',
            'primary-dark':  '#7a1745',
            secondary:       '#29245a',
            'secondary-light':'#3d356b',
            accent:          '#0F172A',
            'accent-light':  '#1E293B',
            surface:         '#F8FAFC',
          },
          fontFamily: {
            sans:   ['Inter', 'sans-serif'],
            arabic: ['Tajawal', 'sans-serif'],
          },
        }
      }
    }
  </script>

  <style>
    * { scroll-behavior: smooth; }
    html, body { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
    body.rtl { font-family: 'Tajawal', sans-serif; }

    .gradient-primary { background: linear-gradient(135deg, #a21f5c 0%, #29245a 100%); }
    .gradient-text {
      background: linear-gradient(135deg, #a21f5c 0%, #29245a 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .btn-primary {
      background: linear-gradient(135deg, #a21f5c 0%, #29245a 100%);
      box-shadow: 0 8px 24px rgba(162, 31, 92, 0.25);
      transition: transform .2s ease, box-shadow .2s ease;
    }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 12px 32px rgba(162, 31, 92, 0.35); }

    .btn-ghost {
      border: 1px solid #e5e7eb; color: #0F172A;
      transition: border-color .2s ease, color .2s ease, background .2s ease;
    }
    .btn-ghost:hover { border-color: #a21f5c; color: #a21f5c; background: rgba(162,31,92,0.04); }

    .nav-link { position: relative; }
    .nav-link::after {
      content: ''; position: absolute; left: 0; right: 0; bottom: -6px;
      height: 2px; width: 0; margin: auto;
      background: linear-gradient(135deg, #a21f5c, #29245a);
      transition: width .25s ease;
    }
    .nav-link:hover::after, .nav-link.active::after { width: 100%; }

    .mobile-menu { transition: max-height .3s ease; max-height: 0; overflow: hidden; }
    .mobile-menu.active { max-height: 800px; }

    .reveal { opacity: 0; transform: translateY(24px); transition: opacity .6s ease, transform .6s ease; }
    .reveal.active { opacity: 1; transform: translateY(0); }

    /* Swiper tweaks */
    .swiper-pagination-bullet { background: #a21f5c; opacity: .3; }
    .swiper-pagination-bullet-active { opacity: 1; }

    /* Odometer (override default theme so it matches our type) */
    .odometer.odometer-auto-theme,
    .odometer.odometer-theme-default {
      font-family: inherit !important;
      line-height: 1 !important;
    }

    /* RTL helpers */
    .rtl .space-x-2  > :not([hidden]) ~ :not([hidden]) { margin-left:0; margin-right:.5rem; }
    .rtl .space-x-3  > :not([hidden]) ~ :not([hidden]) { margin-left:0; margin-right:.75rem; }
    .rtl .space-x-4  > :not([hidden]) ~ :not([hidden]) { margin-left:0; margin-right:1rem; }
    .rtl .space-x-6  > :not([hidden]) ~ :not([hidden]) { margin-left:0; margin-right:1.5rem; }
    .rtl .space-x-8  > :not([hidden]) ~ :not([hidden]) { margin-left:0; margin-right:2rem; }
    .rtl .text-left  { text-align: right; }
    .rtl .text-right { text-align: left; }
  </style>

  @yield('css')
</head>
<body class="bg-white text-accent {{ $isRtl ? 'rtl font-arabic' : 'font-sans' }}">

  {{-- Navigation --}}
  <nav id="site-nav" class="fixed inset-x-0 top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-20">

        {{-- Logo --}}
        <a href="{{ url('/') }}" class="flex items-center gap-3 shrink-0">
          <img src="{{ @settings()->logo_image }}" alt="{{ @settings()->name }}" class="h-10 w-auto" />
        </a>

        {{-- Desktop links --}}
        <div class="hidden lg:flex items-center gap-8">
          <a href="{{ url('/') }}"                   class="nav-link font-medium text-gray-700 hover:text-primary {{ request()->is('/') ? 'active text-primary' : '' }}">{{ __('levels.home') }}</a>
          <a href="{{ url('/') }}#features"          class="nav-link font-medium text-gray-700 hover:text-primary">{{ __('levels.why') }}</a>
          @if (tenant())
            <a href="{{ url('/') }}#services"        class="nav-link font-medium text-gray-700 hover:text-primary">{{ __('levels.our_services') }}</a>
          @endif
          <a href="{{ route('tracking.index') }}"    class="nav-link font-medium text-gray-700 hover:text-primary {{ request()->is('tracking*') ? 'active text-primary' : '' }}">{{ __('levels.tracking') }}</a>
          <a href="{{ route('get.blogs') }}"         class="nav-link font-medium text-gray-700 hover:text-primary {{ request()->is('get-blogs*') ? 'active text-primary' : '' }}">{{ __('levels.blogs') }}</a>
          <a href="{{ route('contact.send.page') }}" class="nav-link font-medium text-gray-700 hover:text-primary {{ request()->is('contact-send*') ? 'active text-primary' : '' }}">{{ __('levels.contact') }}</a>
        </div>

        {{-- Right side: locale + auth --}}
        <div class="hidden lg:flex items-center gap-4">
          {{-- Locale dropdown --}}
          <div class="relative" x-locale-wrap>
            <button type="button" class="flex items-center gap-2 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors text-sm font-medium" onclick="toggleLocaleMenu(event)">
              <i class="fa fa-globe text-primary"></i>
              <span class="uppercase">{{ $locale }}</span>
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div id="locale-menu" class="hidden absolute end-0 mt-2 w-44 bg-white border border-gray-100 rounded-xl shadow-lg overflow-hidden">
              @foreach (['en'=>'English','ar'=>'العربية','bn'=>'বাংলা','in'=>'हिन्दी','fr'=>'Français','es'=>'Español','zh'=>'中文'] as $code => $label)
                <a href="{{ route('setlocalization', $code) }}"
                   class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-gray-50 {{ $locale === $code ? 'text-primary font-semibold bg-primary/5' : 'text-gray-700' }}">
                  <span class="uppercase text-xs text-gray-400 w-6">{{ $code }}</span>
                  <span>{{ $label }}</span>
                </a>
              @endforeach
            </div>
          </div>

          @if (Auth::check())
            <div class="relative">
              <button type="button" onclick="toggleUserMenu(event)" class="flex items-center gap-2 px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-50 text-sm font-medium">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full gradient-primary text-white text-xs font-bold">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                <span>{{ Auth::user()->name }}</span>
              </button>
              <div id="user-menu" class="hidden absolute end-0 mt-2 w-56 bg-white border border-gray-100 rounded-xl shadow-lg overflow-hidden">
                <a href="{{ route('dashboard.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50">
                  <i class="fa fa-home text-primary w-4"></i>{{ __('levels.dashboard') }}
                </a>
                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 border-t border-gray-100">
                  <i class="fas fa-power-off text-primary w-4"></i>{{ __('menus.logout') }}
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
              </div>
            </div>
          @else
            <a href="{{ route('login') }}" class="font-medium text-gray-700 hover:text-primary transition-colors">{{ __('levels.login') }}</a>
            <a href="{{ tenant() ? route('merchant.sign-up') : route('company.sign-up') }}" class="btn-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm">{{ __('levels.register') }}</a>
          @endif
        </div>

        {{-- Mobile menu button --}}
        <button id="mobile-menu-btn" class="lg:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors" aria-label="Menu">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
      </div>
    </div>

    {{-- Mobile menu --}}
    <div id="mobile-menu" class="mobile-menu lg:hidden bg-white border-t border-gray-100">
      <div class="px-4 py-4 space-y-2">
        <a href="{{ url('/') }}"                   class="block py-2 font-medium text-gray-700 hover:text-primary">{{ __('levels.home') }}</a>
        <a href="{{ url('/') }}#features"          class="block py-2 font-medium text-gray-700 hover:text-primary">{{ __('levels.why') }}</a>
        @if (tenant())
          <a href="{{ url('/') }}#services"        class="block py-2 font-medium text-gray-700 hover:text-primary">{{ __('levels.our_services') }}</a>
        @endif
        <a href="{{ route('tracking.index') }}"    class="block py-2 font-medium text-gray-700 hover:text-primary">{{ __('levels.tracking') }}</a>
        <a href="{{ route('get.blogs') }}"         class="block py-2 font-medium text-gray-700 hover:text-primary">{{ __('levels.blogs') }}</a>
        <a href="{{ route('contact.send.page') }}" class="block py-2 font-medium text-gray-700 hover:text-primary">{{ __('levels.contact') }}</a>

        <div class="pt-4 border-t border-gray-100">
          <p class="text-xs uppercase tracking-wider text-gray-500 mb-2">{{ __('levels.language') ?? 'Language' }}</p>
          <div class="flex flex-wrap gap-2">
            @foreach (['en'=>'EN','ar'=>'عربي','bn'=>'বাংলা','fr'=>'FR','es'=>'ES','zh'=>'中文'] as $code => $label)
              <a href="{{ route('setlocalization', $code) }}" class="px-3 py-1.5 rounded-lg text-sm font-medium border {{ $locale === $code ? 'border-primary text-primary bg-primary/5' : 'border-gray-200 text-gray-700' }}">{{ $label }}</a>
            @endforeach
          </div>
        </div>

        <div class="pt-4 border-t border-gray-100 space-y-2">
          @if (Auth::check())
            <a href="{{ route('dashboard.index') }}" class="block w-full text-center py-3 rounded-xl btn-ghost font-semibold">{{ __('levels.dashboard') }}</a>
            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form-m').submit();" class="block w-full text-center py-3 rounded-xl text-gray-700 font-semibold">{{ __('menus.logout') }}</a>
            <form id="logout-form-m" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
          @else
            <a href="{{ route('login') }}" class="block w-full text-center py-3 rounded-xl btn-ghost font-semibold">{{ __('levels.login') }}</a>
            <a href="{{ tenant() ? route('merchant.sign-up') : route('company.sign-up') }}" class="btn-primary text-white block w-full text-center px-5 py-3 rounded-xl font-semibold">{{ __('levels.register') }}</a>
          @endif
        </div>
      </div>
    </div>
  </nav>

  {{-- Page content --}}
  <main class="pt-20">
    @yield('content')
  </main>

  {{-- Footer --}}
  @include('frontend.layouts.footer')

  {{-- Scripts --}}
  <script src="{{ static_asset('frontend/js/jquery.min.js') }}"></script>
  <script src="{{ static_asset('frontend/js/swiper-bundle.min.js') }}"></script>
  <script src="{{ static_asset('frontend/js/jquery.odometer.min.js') }}"></script>
  <script>
    // Mobile menu
    const mobileBtn  = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    mobileBtn && mobileBtn.addEventListener('click', () => {
      mobileMenu.classList.toggle('active');
      const icon = mobileBtn.querySelector('svg');
      icon.innerHTML = mobileMenu.classList.contains('active')
        ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>'
        : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>';
    });

    // Dropdown menus
    function toggleLocaleMenu(e) {
      e.stopPropagation();
      document.getElementById('locale-menu').classList.toggle('hidden');
      const um = document.getElementById('user-menu');
      um && um.classList.add('hidden');
    }
    function toggleUserMenu(e) {
      e.stopPropagation();
      document.getElementById('user-menu').classList.toggle('hidden');
      const lm = document.getElementById('locale-menu');
      lm && lm.classList.add('hidden');
    }
    document.addEventListener('click', () => {
      document.getElementById('locale-menu')?.classList.add('hidden');
      document.getElementById('user-menu')?.classList.add('hidden');
    });

    // Scroll reveal
    const revealOnScroll = () => {
      const wh = window.innerHeight, visible = 120;
      document.querySelectorAll('.reveal').forEach(el => {
        if (el.getBoundingClientRect().top < wh - visible) el.classList.add('active');
      });
    };
    window.addEventListener('load',   revealOnScroll);
    window.addEventListener('scroll', revealOnScroll);

    // Navbar shadow on scroll
    const nav = document.getElementById('site-nav');
    window.addEventListener('scroll', () => {
      if (window.scrollY > 20) nav.classList.add('shadow-sm');
      else nav.classList.remove('shadow-sm');
    });

    // Smooth scroll for anchor links (same page only)
    document.querySelectorAll('a[href*="#"]').forEach(a => {
      a.addEventListener('click', (e) => {
        const href = a.getAttribute('href');
        const hashIdx = href.indexOf('#');
        if (hashIdx < 0) return;
        const id = href.slice(hashIdx + 1);
        // Only act if the anchor is on the current page (href is "#id" or current-path#id)
        const onCurrent = href.startsWith('#') || href.startsWith(window.location.pathname + '#');
        if (!onCurrent || !id) return;
        const target = document.getElementById(id);
        if (target) {
          e.preventDefault();
          window.scrollTo({ top: target.offsetTop - 80, behavior: 'smooth' });
        }
      });
    });

    // Odometer init (stats)
    document.querySelectorAll('.odometer').forEach(el => {
      const target = parseInt(el.getAttribute('data-count') || '0', 10);
      const trigger = () => {
        const rect = el.getBoundingClientRect();
        if (rect.top < window.innerHeight && !el.dataset.run) {
          el.dataset.run = '1';
          el.innerHTML = target;
        }
      };
      window.addEventListener('scroll', trigger);
      trigger();
    });
  </script>

  @yield('js')
</body>
</html>
