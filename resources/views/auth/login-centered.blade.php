@extends('auth.layouts')
@section('title', __('levels.sign_in'))
@section('body-class', 'login-centered')

@push('styles')
<style>
  body.login-centered {
    background: linear-gradient(135deg, color-mix(in srgb, var(--primary) 10%, transparent), color-mix(in srgb, var(--secondary) 8%, transparent));
    display: flex; align-items: center; justify-content: center; padding: 24px;
  }
  body.rtl.login-centered .lc-card { direction: rtl; }
  .lc-card {
    width: 100%; max-width: 440px; background: #fff;
    border-radius: 24px; padding: 40px 36px;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.18), 0 0 0 1px rgba(0,0,0,0.04);
  }
</style>
@endpush

@section('content')
@php
  $__lb       = $loginBrand ?? loginBrand();
  $__lbName   = $__lb['name'] ?? config('app.name', 'Rushly');
  $__lbLogo   = $__lb['logo'] ?? null;
  $locale     = app()->getLocale();
@endphp

<div class="lc-card">
  {{-- Logo + name --}}
  <div class="flex flex-col items-center text-center mb-8">
    @if($__lbLogo)
      <img src="{{ $__lbLogo }}" alt="{{ $__lbName }}" class="h-12 w-auto mb-4" />
    @else
      <span class="inline-grid place-items-center h-12 w-12 rounded-2xl text-white font-bold text-xl mb-4" style="background: linear-gradient(135deg, var(--primary), var(--secondary));">
        {{ strtoupper(mb_substr($__lbName, 0, 1)) }}
      </span>
    @endif
    <h1 class="text-2xl font-bold tracking-tight" style="color: var(--text-dark)">{{ __('levels.welcome_back') }}</h1>
    <p class="text-sm text-gray-500 mt-1.5">{{ __('levels.sign_in_subtitle') }}</p>
  </div>

  {{-- Locale switcher --}}
  <div class="flex items-center gap-1 p-1 rounded-full bg-gray-50 border border-gray-200 mx-auto mb-6 w-fit">
    @foreach (['en' => 'EN', 'ar' => 'عربي'] as $code => $label)
      <a href="{{ route('setlocalization', $code) }}"
         class="px-3 py-1 rounded-full text-xs font-semibold transition-all
                {{ $locale === $code ? 'gradient-primary text-white shadow' : 'text-gray-500 hover:text-primary' }}">
        {{ $label }}
      </a>
    @endforeach
  </div>

  {{-- Form --}}
  <form method="POST" action="{{ route('login') }}" class="space-y-4">
    @csrf
    <div>
      <label for="email" class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">{{ __('levels.email_or_mobile') }}</label>
      <input id="email" name="email" type="text" value="{{ old('email') }}" required autofocus
        class="w-full h-11 px-4 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all outline-none">
      @error('email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
      <label for="password" class="block text-sm font-medium mb-1.5" style="color: var(--text-dark)">{{ __('levels.password') }}</label>
      <input id="password" name="password" type="password" required autocomplete="current-password"
        class="w-full h-11 px-4 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all outline-none">
      @error('password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="flex items-center justify-between">
      <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-gray-300">
        {{ __('levels.remember_me') }}
      </label>
      @if (Route::has('password.request'))
        <a href="{{ route('password.request') }}" class="text-sm font-medium hover:underline" style="color: var(--primary)">{{ __('levels.forget_password') }}</a>
      @endif
    </div>

    <button type="submit" class="w-full h-11 rounded-xl gradient-primary text-white font-semibold shadow-lg hover:shadow-xl transition-shadow">
      {{ __('levels.sign_in') }}
    </button>
  </form>
</div>
@endsection
