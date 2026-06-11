@extends('auth.layouts')
@section('title', __('levels.verify_email'))
@section('body-class', 'login-redesign')

@push('styles')
<style>
  body.login-redesign { display: block !important; min-height: 100vh; background: #F8FAFC; }
  body.login-redesign.rtl { font-family: 'Tajawal', sans-serif; }
</style>
@endpush

@section('content')
@php $locale = app()->getLocale(); @endphp

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
        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
      </div>
      <h1 class="mt-6 text-3xl sm:text-4xl font-bold tracking-tight text-accent">{{ __('levels.verify_email') }}</h1>
      <p class="mt-3 text-base text-gray-500">{{ __('levels.verify_email_lead') }}</p>

      @if (session('resent'))
        <div class="mt-6 flex items-start gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-100 text-sm text-emerald-700">
          <svg class="w-5 h-5 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span>{{ __('levels.fresh_link_sent') }}</span>
        </div>
      @endif

      <div class="mt-8 p-5 rounded-xl bg-white border border-gray-100">
        <p class="text-sm font-semibold text-accent">{{ __('levels.check_inbox') }}</p>
        <p class="mt-1 text-sm text-gray-500">{{ __('levels.check_inbox_desc') }}</p>
      </div>

      <form method="POST" action="{{ route('verification.resend') }}" class="mt-6">
        @csrf
        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 py-3.5 rounded-xl text-white font-semibold bg-gradient-to-r from-primary to-secondary shadow-lg shadow-primary/25 hover:shadow-xl hover:shadow-primary/40 hover:-translate-y-px transition-all">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
          {{ __('levels.resend_verification') }}
        </button>
      </form>

      <div class="mt-6 text-center">
        <p class="text-sm text-gray-500">{{ __('levels.wrong_email') }}</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-1 inline">
          @csrf
          <button type="submit" class="text-sm font-semibold text-primary hover:text-secondary transition-colors">{{ __('levels.sign_in_diff_account') }}</button>
        </form>
      </div>

      <p class="mt-8 text-center text-sm text-gray-500">
        <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 font-semibold text-primary hover:text-secondary transition-colors">
          <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
          {{ __('levels.back_to_login') }}
        </a>
      </p>
    </div>
  </div>

  <div class="hidden lg:flex relative overflow-hidden gradient-primary text-white">
    <div aria-hidden="true" class="absolute -top-32 -end-32 w-[36rem] h-[36rem] rounded-full bg-white/10 blur-3xl"></div>
    <div aria-hidden="true" class="absolute -bottom-32 -start-24 w-[28rem] h-[28rem] rounded-full bg-white/5 blur-3xl"></div>

    <div class="relative z-10 flex flex-col justify-center p-12 lg:p-16 w-full max-w-lg">
      <span class="inline-flex w-fit items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wider bg-white/15 backdrop-blur-sm border border-white/20">
        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
        {{ __('levels.verify_email') }}
      </span>
      <h2 class="mt-8 text-3xl lg:text-4xl font-bold leading-tight">{{ __('levels.check_inbox') }}</h2>
      <p class="mt-4 text-white/80 leading-relaxed">{{ __('levels.check_inbox_desc') }}</p>
    </div>
  </div>
</div>
@endsection
