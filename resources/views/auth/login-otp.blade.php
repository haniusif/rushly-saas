@extends('auth.layouts')
@section('title', __('auth.login_otp_subject'))
@section('body-class', 'login-centered')

@push('styles')
<style>
  body.login-centered {
    background: linear-gradient(135deg, color-mix(in srgb, var(--primary) 10%, transparent), color-mix(in srgb, var(--secondary) 8%, transparent));
    display: flex; align-items: center; justify-content: center; padding: 24px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  }
  body.login-centered.rtl { font-family: 'Tajawal', 'Cairo', sans-serif; }
  .lc-card {
    width: 100%; max-width: 440px; background: #fff;
    border-radius: 24px; padding: 40px 36px;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.18), 0 0 0 1px rgba(0,0,0,0.04);
    direction: inherit;
  }
  .otp-input {
    width: 100%;
    height: 56px;
    text-align: center;
    letter-spacing: 12px;
    font-size: 22px;
    font-weight: 600;
    padding-left: 12px;
    border-radius: 12px;
    border: 1px solid var(--border-color);
    background: #f9fafb;
    outline: none;
    transition: all .2s ease;
  }
  .otp-input:focus {
    background: #fff;
    border-color: var(--primary);
    box-shadow: 0 0 0 4px var(--primary-light);
  }
  .otp-error { color: var(--error); font-size: 13px; margin-top: 6px; }
  .otp-status { color: var(--success); font-size: 13px; margin-top: 6px; }
</style>
@endpush

@section('content')
@php
  $__lb     = $loginBrand ?? loginBrand();
  $__lbName = $__lb['name'] ?? config('app.name', 'Rushly');
  $__lbLogo = $__lb['logo'] ?? null;
@endphp

<div class="lc-card">
  <div class="flex flex-col items-center text-center mb-8">
    @if($__lbLogo)
      <img src="{{ $__lbLogo }}" alt="{{ $__lbName }}" class="h-12 w-auto mb-4" />
    @else
      <span class="inline-grid place-items-center h-12 w-12 rounded-2xl text-white font-bold text-xl mb-4" style="background: linear-gradient(135deg, var(--primary), var(--secondary));">
        {{ strtoupper(mb_substr($__lbName, 0, 1)) }}
      </span>
    @endif
    <h1 class="text-2xl font-bold tracking-tight" style="color: var(--text-dark)">
      {{ __('auth.login_otp_subject') }}
    </h1>
    <p class="text-sm text-gray-500 mt-1.5">
      {{ __('auth.login_otp_sent') }}
    </p>
  </div>

  @if(session('status'))
    <p class="otp-status text-center mb-3">{{ session('status') }}</p>
  @endif

  <form method="POST" action="{{ route('login.otp.verify') }}" class="space-y-4">
    @csrf
    <div>
      <input id="code" name="code" type="text" inputmode="numeric" maxlength="6" pattern="[0-9]{6}"
             autocomplete="one-time-code" autofocus required
             class="otp-input"
             value="{{ old('code') }}">
      @error('code')<p class="otp-error text-center">{{ $message }}</p>@enderror
    </div>

    <button type="submit" class="w-full h-11 rounded-xl gradient-primary text-white font-semibold shadow-lg hover:shadow-xl transition-shadow">
      {{ __('levels.sign_in') }}
    </button>
  </form>

  <div class="flex items-center justify-between mt-6 text-sm">
    <form method="POST" action="{{ route('login.otp.resend') }}">
      @csrf
      <button type="submit" class="font-medium hover:underline" style="color: var(--primary)">
        {{ __('auth.resend_otp_msg') }}
      </button>
    </form>
    <a href="{{ route('login') }}" class="text-gray-500 hover:underline">
      &larr; {{ __('levels.sign_in') }}
    </a>
  </div>
</div>

<script>
  // Digits-only + auto-submit when 6 chars are entered.
  document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('code');
    if (!el) return;
    el.addEventListener('input', () => {
      el.value = el.value.replace(/\D+/g, '').slice(0, 6);
      if (el.value.length === 6) {
        el.form && el.form.submit();
      }
    });
  });
</script>
@endsection
