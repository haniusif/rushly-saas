@extends('auth.layouts')
@section('title', __('levels.sign_in'))
@section('body-class', 'login-redesign')

@push('styles')
<style>
  /* Override layout's body centering — we want a full-bleed split */
  body.login-redesign {
    display: block !important;
    min-height: 100vh;
    background: #F8FAFC;
  }
  body.login-redesign.rtl { font-family: 'Tajawal', sans-serif; }

  /* RTL flips for end-/start- utilities used inline */
  body.rtl .lg\:rounded-s-3xl { border-top-left-radius: 0; border-bottom-left-radius: 0; border-top-right-radius: 1.5rem; border-bottom-right-radius: 1.5rem; }
  body.rtl .lg\:rounded-e-3xl { border-top-right-radius: 0; border-bottom-right-radius: 0; border-top-left-radius: 1.5rem; border-bottom-left-radius: 1.5rem; }
</style>
@endpush

@section('content')
@php
  $isTenant       = function_exists('tenant') ? tenant() : null;
  $locale         = app()->getLocale();
  $isRtl          = in_array($locale, ['ar']);
  $platformHost   = config('app.platform_base_host', 'rushly-logistic.com');
  $currentHost    = request()->getHost();
  $isCustomDomain = $isTenant
                  && $currentHost !== $platformHost
                  && !\Illuminate\Support\Str::endsWith($currentHost, '.'.$platformHost);
@endphp

<div class="min-h-screen grid grid-cols-1 lg:grid-cols-2 bg-surface">

  {{-- ============ LEFT: FORM ============ --}}
  <div class="relative flex items-center justify-center px-6 py-12 sm:px-12 lg:px-16">
    {{-- Back to site --}}
    <a href="{{ url('/') }}" class="absolute top-6 start-6 inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-primary transition-colors">
      <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      {{ __('levels.home') }}
    </a>

    {{-- Locale switcher --}}
    <div class="absolute top-6 end-6 flex items-center gap-1 p-1 rounded-full bg-white border border-gray-200 shadow-sm">
      @foreach (['en' => 'EN', 'ar' => 'عربي'] as $code => $label)
        <a href="{{ route('setlocalization', $code) }}"
           class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all
                  {{ $locale === $code ? 'gradient-primary text-white shadow' : 'text-gray-500 hover:text-primary' }}">
          {{ $label }}
        </a>
      @endforeach
    </div>

    <div class="w-full max-w-md">
      {{-- Logo --}}
      <a href="{{ url('/') }}" class="inline-block">
        <img src="{{ @settings()->logo_image }}" alt="{{ @settings()->name }}" class="h-10 w-auto" />
      </a>

      {{-- Heading --}}
      <h1 class="mt-10 text-3xl sm:text-4xl font-bold tracking-tight text-accent">
        @if($isTenant)
          {{ __('levels.welcome_back') }}
        @else
          {{ __('levels.sign_in_to') }} <span class="gradient-text">{{ @settings()->name }}</span>
        @endif
      </h1>
      <p class="mt-3 text-base text-gray-500">
        {{ $isTenant ? __('levels.sign_in_subtitle') : __('levels.sign_in_subtitle_central') }}
      </p>

      {{-- Form --}}
      <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf

        {{-- Email / Mobile --}}
        <div>
          <label for="email" class="block text-sm font-semibold text-accent mb-1.5">{{ __('levels.email_or_mobile') }}</label>
          <div class="relative">
            <span class="absolute inset-y-0 start-0 flex items-center ps-4 text-gray-400 pointer-events-none">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </span>
            <input id="email" type="text" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                   placeholder="you@company.com"
                   class="form-control w-full ps-12 pe-4 py-3.5 bg-white border @error('email') border-red-300 @else border-gray-200 @enderror rounded-xl text-accent placeholder:text-gray-400 focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all" />
          </div>
          @error('email')
            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
          @enderror
        </div>

        {{-- Password --}}
        <div>
          <label for="password" class="block text-sm font-semibold text-accent mb-1.5">{{ __('levels.password') }}</label>
          <div class="relative">
            <span class="absolute inset-y-0 start-0 flex items-center ps-4 text-gray-400 pointer-events-none">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </span>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   placeholder="••••••••"
                   class="form-control w-full ps-12 pe-12 py-3.5 bg-white border @error('password') border-red-300 @else border-gray-200 @enderror rounded-xl text-accent placeholder:text-gray-400 focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all" />
            <button type="button" onclick="togglePassword()" class="absolute inset-y-0 end-0 flex items-center pe-4 text-gray-400 hover:text-primary transition-colors">
              <svg id="eye-icon" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
            </button>
          </div>
          @error('password')
            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
          @enderror
        </div>

        {{-- Remember + Forgot --}}
        <div class="flex items-center justify-between flex-wrap gap-3">
          <label for="remember" class="flex items-center gap-2 cursor-pointer select-none">
            <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}
                   class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary/20" />
            <span class="text-sm text-gray-600">{{ __('levels.remember_me') }}</span>
          </label>
          <a href="{{ route('password.request') }}" class="text-sm font-semibold text-primary hover:text-secondary transition-colors">
            {{ __('levels.forgot_password') }}
          </a>
        </div>

        {{-- Submit --}}
        <button type="submit"
                class="w-full inline-flex items-center justify-center gap-2 py-3.5 rounded-xl text-white font-semibold
                       bg-gradient-to-r from-primary to-secondary shadow-lg shadow-primary/25
                       hover:shadow-xl hover:shadow-primary/40 hover:-translate-y-px transition-all">
          {{ __('levels.sign_in') }}
          <svg class="w-5 h-5 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </button>
      </form>

      {{-- Footer: register link + security --}}
      @unless($isCustomDomain)
      <p class="mt-6 text-center text-sm text-gray-500">
        {{ __('levels.dont_have_account') }}
        <a href="{{ $isTenant ? route('merchant.sign-up') : route('company.sign-up') }}"
           class="ms-1 font-semibold text-primary hover:text-secondary transition-colors">
          {{ __('levels.create_account') }}
        </a>
      </p>
      @endunless

      <p class="mt-8 flex items-center justify-center gap-1.5 text-xs text-gray-400">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        {{ __('levels.secured_by') }}
      </p>
    </div>
  </div>

  {{-- ============ RIGHT: GRADIENT PANEL ============ --}}
  <div class="hidden lg:flex relative overflow-hidden gradient-primary text-white">
    {{-- Decorative blobs --}}
    <div aria-hidden="true" class="pointer-events-none absolute -top-32 -end-32 w-[36rem] h-[36rem] rounded-full bg-white/10 blur-3xl"></div>
    <div aria-hidden="true" class="pointer-events-none absolute -bottom-32 -start-24 w-[28rem] h-[28rem] rounded-full bg-white/5 blur-3xl"></div>

    {{-- Subtle grid pattern --}}
    <div aria-hidden="true" class="absolute inset-0 opacity-[0.05]"
         style="background-image: linear-gradient(to right, #fff 1px, transparent 1px), linear-gradient(to bottom, #fff 1px, transparent 1px); background-size: 48px 48px;"></div>

    <div class="relative z-10 flex flex-col justify-between p-12 lg:p-16 w-full">
      <div>
        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wider bg-white/15 backdrop-blur-sm border border-white/20">
          <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
          {{ __('levels.smart_logistics_platform') }}
        </span>

        <h2 class="mt-8 text-3xl lg:text-4xl xl:text-5xl font-bold tracking-tight leading-tight">
          {{ @settings()->name }}<br/>
          <span class="text-white/80">{{ __('levels.smart_logistics_platform') }}</span>
        </h2>

        <ul class="mt-12 space-y-6 max-w-md">
          @php
            $bullets = [
              ['title' => __('levels.value_prop_realtime'), 'desc' => __('levels.value_prop_realtime_desc'), 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
              ['title' => __('levels.value_prop_fleet'),    'desc' => __('levels.value_prop_fleet_desc'),    'icon' => 'M3 8h11v8H3zM14 11h4l3 3v2h-7z'],
              ['title' => __('levels.value_prop_billing'),  'desc' => __('levels.value_prop_billing_desc'),  'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ];
          @endphp
          @foreach($bullets as $b)
            <li class="flex items-start gap-4">
              <span class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-white/15 backdrop-blur-sm border border-white/20 shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $b['icon'] }}"/></svg>
              </span>
              <div>
                <p class="font-semibold text-white">{{ $b['title'] }}</p>
                <p class="mt-1 text-sm text-white/70 leading-relaxed">{{ $b['desc'] }}</p>
              </div>
            </li>
          @endforeach
        </ul>
      </div>

    </div>
  </div>
</div>
@endsection
