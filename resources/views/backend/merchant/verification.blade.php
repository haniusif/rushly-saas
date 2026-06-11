@extends('auth.layouts')
@section('title', __('levels.otp_verification'))
@section('body-class', 'login-redesign')

@push('styles')
<style>
  body.login-redesign { display: block !important; min-height: 100vh; background: #F8FAFC; }
  body.login-redesign.rtl { font-family: 'Tajawal', sans-serif; }
</style>
@endpush

@section('content')
@php
  $locale = app()->getLocale();
  $mobile = session('mobile');
  $maskedMobile = $mobile ? substr($mobile, 0, 2).'••••••••'.substr($mobile, -2) : '';
@endphp

<div class="min-h-screen grid grid-cols-1 lg:grid-cols-2 bg-surface">
  <div class="relative flex items-center justify-center px-6 py-12 sm:px-12 lg:px-16">
    <a href="{{ url('/') }}" class="absolute top-6 start-6 inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-primary transition-colors">
      <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      {{ __('levels.home') }}
    </a>
    <div class="absolute top-6 end-6 flex items-center gap-1 p-1 rounded-full bg-white border border-gray-200 shadow-sm">
      @foreach (['en' => 'EN', 'ar' => 'عربي'] as $code => $label)
        <a href="{{ route('setlocalization', $code) }}" class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all {{ $locale === $code ? 'gradient-primary text-white shadow' : 'text-gray-500 hover:text-primary' }}">{{ $label }}</a>
      @endforeach
    </div>

    <div class="w-full max-w-md">
      <a href="{{ url('/') }}" class="inline-block">
        <img src="{{ @settings()->logo_image }}" alt="{{ @settings()->name }}" class="h-10 w-auto" />
      </a>

      <div class="mt-10 inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-primary/10 to-secondary/10 text-primary">
        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
      </div>
      <h1 class="mt-6 text-3xl sm:text-4xl font-bold tracking-tight text-accent">{{ __('levels.otp_verification') }}</h1>
      <p class="mt-3 text-base text-gray-500">{{ __('levels.otp_lead') }}</p>

      @if(\Session::has('success'))
        <div class="mt-6 flex items-start gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-100 text-sm text-emerald-700">
          <svg class="w-5 h-5 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span>{!! \Session::get('success') !!}</span>
        </div>
      @elseif(\Session::has('warning'))
        <div class="mt-6 flex items-start gap-3 p-4 rounded-xl bg-amber-50 border border-amber-100 text-sm text-amber-700">
          <svg class="w-5 h-5 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
          <span>{!! \Session::get('warning') !!}</span>
        </div>
      @endif

      @if($maskedMobile)
        <div class="mt-6 p-4 rounded-xl bg-white border border-gray-100 flex items-center gap-3">
          <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-primary/8 text-primary">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
          </span>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('levels.mobile_number') }}</p>
            <p class="mt-0.5 text-sm font-mono text-accent">{{ $maskedMobile }}</p>
          </div>
        </div>
      @endif

      <form method="POST" action="{{ route('merchant.otp-verification') }}" class="mt-6 space-y-5">
        @csrf
        <input type="hidden" name="mobile" value="{{ $mobile }}">

        <div>
          <label for="otp" class="block text-sm font-semibold text-accent mb-1.5">{{ __('levels.enter_otp') }}</label>
          <input id="otp" type="text" inputmode="numeric" pattern="[0-9]*" name="otp" value="{{ old('otp') }}" required autocomplete="one-time-code" autofocus
                 placeholder="• • • • • •"
                 class="w-full px-4 py-4 bg-white border @error('otp') border-red-300 @else border-gray-200 @enderror rounded-xl text-accent text-center text-2xl tracking-[0.5em] font-bold focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all" />
          @error('otp')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 py-3.5 rounded-xl text-white font-semibold bg-gradient-to-r from-primary to-secondary shadow-lg shadow-primary/25 hover:shadow-xl hover:shadow-primary/40 hover:-translate-y-px transition-all">
          {{ __('levels.verify_account') }}
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        </button>
      </form>

      <form id="resend-otp" method="POST" action="{{ route('merchant.resend-otp') }}" class="mt-6 text-center">
        @csrf
        <input type="hidden" name="mobile" value="{{ $mobile }}">
        <button type="submit" class="text-sm font-semibold text-primary hover:text-secondary transition-colors inline-flex items-center gap-1.5">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
          {{ __('levels.resend_code') }}
        </button>
      </form>

      <p class="mt-8 text-center text-sm text-gray-500">
        {{ __('levels.already_a_member') }}
        <a href="{{ route('login') }}" class="ms-1 font-semibold text-primary hover:text-secondary transition-colors">{{ __('levels.login_here') }}</a>
      </p>
    </div>
  </div>

  <div class="hidden lg:flex relative overflow-hidden gradient-primary text-white">
    <div aria-hidden="true" class="absolute -top-32 -end-32 w-[36rem] h-[36rem] rounded-full bg-white/10 blur-3xl"></div>
    <div aria-hidden="true" class="absolute -bottom-32 -start-24 w-[28rem] h-[28rem] rounded-full bg-white/5 blur-3xl"></div>

    <div class="relative z-10 flex flex-col justify-center p-12 lg:p-16 w-full max-w-lg">
      <span class="inline-flex w-fit items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wider bg-white/15 backdrop-blur-sm border border-white/20">
        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
        {{ __('levels.secured_by') }}
      </span>
      <h2 class="mt-8 text-3xl lg:text-4xl font-bold leading-tight">{{ __('levels.otp_verification') }}</h2>
      <p class="mt-4 text-white/80 leading-relaxed">{{ __('levels.otp_lead') }}</p>
    </div>
  </div>
</div>
@endsection
