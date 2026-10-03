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
  .otp-boxes {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 8px;
    direction: ltr;
  }
  .otp-box {
    width: 100%;
    aspect-ratio: 1 / 1;
    max-height: 56px;
    text-align: center;
    font-size: 22px;
    font-weight: 600;
    border-radius: 12px;
    border: 1px solid var(--border-color);
    background: #f9fafb;
    outline: none;
    transition: all .15s ease;
  }
  .otp-box:focus {
    background: #fff;
    border-color: var(--primary);
    box-shadow: 0 0 0 4px var(--primary-light);
  }
  .otp-box.filled { background: #fff; border-color: var(--primary); }
  .otp-box.error {
    border-color: var(--error);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--error) 15%, transparent);
  }
  .otp-error { color: var(--error); font-size: 13px; margin-top: 10px; }
  .otp-status { color: var(--success); font-size: 13px; margin-top: 6px; }
</style>
@endpush

@section('content')
@php
  $__lb     = $loginBrand ?? loginBrand();
  $__lbName = $__lb['name'] ?? config('app.name', 'Rushly');
  $__lbLogo = $__lb['logo'] ?? null;
  $__lbBg   = $__lb['login_bg'] ?? null;
  $locale   = app()->getLocale();
@endphp

@if($__lbBg)
  {{-- Match /login (centered layout): replace the tinted-gradient body
       with the picture and dim it so the card on top stays readable.
       Overrides the @push('styles') gradient. --}}
  <style>
    body.login-centered { background: url('{{ $__lbBg }}') center/cover no-repeat fixed !important; }
    body.login-centered::before {
      content: ""; position: fixed; inset: 0; background: rgba(0,0,0,0.35);
      pointer-events: none; z-index: 0;
    }
    body.login-centered > * { position: relative; z-index: 1; }
  </style>
@endif

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

  {{-- Locale switcher (matches /login-centered) --}}
  <div class="flex items-center gap-1 p-1 rounded-full bg-gray-50 border border-gray-200 mx-auto mb-6 w-fit">
    @foreach (['en' => 'EN', 'ar' => 'عربي'] as $code => $label)
      <a href="{{ route('setlocalization', $code) }}"
         class="px-3 py-1 rounded-full text-xs font-semibold transition-all
                {{ $locale === $code ? 'gradient-primary text-white shadow' : 'text-gray-500 hover:text-primary' }}">
        {{ $label }}
      </a>
    @endforeach
  </div>

  @if(session('status'))
    <p class="otp-status text-center mb-3">{{ session('status') }}</p>
  @endif

  <form method="POST" action="{{ route('login.otp.verify') }}" class="space-y-4" id="otp-form">
    @csrf
    <input type="hidden" name="code" id="code" value="{{ old('code') }}">
    <div>
      <div class="otp-boxes" id="otp-boxes">
        @for($i = 0; $i < 6; $i++)
          <input type="text"
                 class="otp-box @error('code') error @enderror"
                 inputmode="numeric"
                 maxlength="1"
                 autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                 aria-label="Digit {{ $i + 1 }}"
                 data-otp-idx="{{ $i }}"
                 @if($i === 0) autofocus @endif>
        @endfor
      </div>
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
  document.addEventListener('DOMContentLoaded', () => {
    const form   = document.getElementById('otp-form');
    const hidden = document.getElementById('code');
    const boxes  = Array.from(document.querySelectorAll('.otp-box'));
    if (!form || !hidden || boxes.length !== 6) return;

    // Hydrate from any old() value or from a repopulated hidden value.
    const initial = (hidden.value || '').replace(/\D+/g, '').slice(0, 6);
    if (initial) {
      initial.split('').forEach((ch, i) => {
        boxes[i].value = ch;
        boxes[i].classList.add('filled');
      });
    }

    const syncHidden = () => {
      hidden.value = boxes.map(b => b.value).join('');
    };

    boxes.forEach((box, i) => {
      box.addEventListener('input', (e) => {
        // Keep only the last typed digit (handles autofill mid-box too).
        const digits = (box.value || '').replace(/\D+/g, '');
        box.value = digits.slice(-1);
        box.classList.toggle('filled', box.value.length === 1);
        syncHidden();
        if (box.value && i < 5) boxes[i + 1].focus();
      });

      box.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !box.value && i > 0) {
          boxes[i - 1].focus();
          boxes[i - 1].value = '';
          boxes[i - 1].classList.remove('filled');
          syncHidden();
          e.preventDefault();
        } else if (e.key === 'ArrowLeft' && i > 0) {
          boxes[i - 1].focus();
          e.preventDefault();
        } else if (e.key === 'ArrowRight' && i < 5) {
          boxes[i + 1].focus();
          e.preventDefault();
        }
      });

      box.addEventListener('paste', (e) => {
        const text = (e.clipboardData || window.clipboardData).getData('text') || '';
        const digits = text.replace(/\D+/g, '').slice(0, 6 - i);
        if (!digits) return;
        e.preventDefault();
        digits.split('').forEach((ch, k) => {
          if (boxes[i + k]) {
            boxes[i + k].value = ch;
            boxes[i + k].classList.add('filled');
          }
        });
        const nextIdx = Math.min(i + digits.length, 5);
        boxes[nextIdx].focus();
        syncHidden();
      });

      box.addEventListener('focus', () => box.select());
    });
  });
</script>
@endsection
