@extends('auth.layouts')
@section('title', __('levels.sign_in'))
@section('body-class', 'login-fullbleed')

@push('styles')
<style>
  body.login-fullbleed {
    display: block !important;
    min-height: 100vh; position: relative; overflow-x: hidden;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  }
  body.login-fullbleed::before {
    content: ""; position: absolute; inset: 0;
    background:
      radial-gradient(circle at 20% 30%, rgba(255,255,255,0.15) 0, transparent 40%),
      radial-gradient(circle at 80% 70%, rgba(0,0,0,0.18) 0, transparent 40%);
    pointer-events: none;
  }
  .fb-wrap { position: relative; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
  .fb-card {
    width: 100%; max-width: 420px;
    background: rgba(255,255,255,0.08); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.18); border-radius: 20px;
    padding: 40px 32px; color: #fff;
    box-shadow: 0 20px 60px rgba(0,0,0,0.25);
  }
  .fb-card input { background: rgba(255,255,255,0.10) !important; border: 1px solid rgba(255,255,255,0.20) !important; color: #fff !important; }
  .fb-card input::placeholder { color: rgba(255,255,255,0.55); }
  .fb-card input:focus { background: rgba(255,255,255,0.18) !important; border-color: rgba(255,255,255,0.45) !important; outline: none; }
  .fb-card label { color: rgba(255,255,255,0.85); }
  .fb-card a { color: rgba(255,255,255,0.9); }
  .fb-card a:hover { color: #fff; }
  .fb-btn { background: #fff; color: var(--primary); }
  .fb-btn:hover { background: rgba(255,255,255,0.92); }
</style>
@endpush

@section('content')
@php
  $__lb       = $loginBrand ?? loginBrand();
  $__lbName   = $__lb['name'] ?? config('app.name', 'Rushly');
  $__lbLogo   = $__lb['light_logo'] ?? $__lb['logo'] ?? null;
  $locale     = app()->getLocale();
@endphp

<div class="fb-wrap">
  <div class="fb-card">
    {{-- Logo + name --}}
    <div class="flex flex-col items-center text-center mb-7">
      @if($__lbLogo)
        <img src="{{ $__lbLogo }}" alt="{{ $__lbName }}" class="h-12 w-auto mb-3" style="filter: drop-shadow(0 2px 8px rgba(0,0,0,0.2));" />
      @else
        <span class="inline-grid place-items-center h-12 w-12 rounded-2xl bg-white/20 font-bold text-xl text-white mb-3">
          {{ strtoupper(mb_substr($__lbName, 0, 1)) }}
        </span>
      @endif
      <h1 class="text-2xl font-bold tracking-tight">{{ $__lbName }}</h1>
      <p class="text-sm text-white/70 mt-1.5">{{ __('levels.sign_in_subtitle') }}</p>
    </div>

    {{-- Locale switcher --}}
    <div class="flex items-center gap-1 p-1 rounded-full bg-white/10 border border-white/15 mx-auto mb-6 w-fit">
      @foreach (['en' => 'EN', 'ar' => 'عربي'] as $code => $label)
        <a href="{{ route('setlocalization', $code) }}"
           class="px-3 py-1 rounded-full text-xs font-semibold transition-all
                  {{ $locale === $code ? 'bg-white text-gray-900 shadow' : 'text-white/70 hover:text-white' }}">
          {{ $label }}
        </a>
      @endforeach
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
      @csrf
      <div>
        <label for="email" class="block text-sm font-medium mb-1.5">{{ __('levels.email_or_mobile') }}</label>
        <input id="email" name="email" type="text" value="{{ old('email') }}" required autofocus
          class="w-full h-11 px-4 rounded-xl transition-colors">
        @error('email')<p class="text-xs text-red-200 mt-1">{{ $message }}</p>@enderror
      </div>
      <div>
        <label for="password" class="block text-sm font-medium mb-1.5">{{ __('levels.password') }}</label>
        <input id="password" name="password" type="password" required autocomplete="current-password"
          class="w-full h-11 px-4 rounded-xl transition-colors">
        @error('password')<p class="text-xs text-red-200 mt-1">{{ $message }}</p>@enderror
      </div>

      <div class="flex items-center justify-between text-sm">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" name="remember" class="h-4 w-4 rounded">
          {{ __('levels.remember_me') }}
        </label>
        @if (Route::has('password.request'))
          <a href="{{ route('password.request') }}" class="font-medium hover:underline">{{ __('levels.forgot_password') }}</a>
        @endif
      </div>

      <button type="submit" class="fb-btn w-full h-11 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all">
        {{ __('levels.sign_in') }}
      </button>
    </form>
  </div>
</div>
@endsection
