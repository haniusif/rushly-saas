@extends('auth.layouts')
@section('title', __('levels.reset_password'))
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

      <h1 class="mt-10 text-3xl sm:text-4xl font-bold tracking-tight text-accent">{{ __('levels.reset_password') }}</h1>
      <p class="mt-3 text-base text-gray-500">{{ __('levels.reset_password_lead') }}</p>

      <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
          <label for="email" class="block text-sm font-semibold text-accent mb-1.5">{{ __('levels.email') }}</label>
          <div class="relative">
            <span class="absolute inset-y-0 start-0 flex items-center ps-4 text-gray-400 pointer-events-none">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </span>
            <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus
                   class="w-full ps-12 pe-4 py-3.5 bg-white border @error('email') border-red-300 @else border-gray-200 @enderror rounded-xl text-accent focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all" />
          </div>
          @error('email')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <div>
          <label for="password" class="block text-sm font-semibold text-accent mb-1.5">{{ __('levels.new_password') }}</label>
          <div class="relative">
            <span class="absolute inset-y-0 start-0 flex items-center ps-4 text-gray-400 pointer-events-none">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </span>
            <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="••••••••"
                   class="w-full ps-12 pe-12 py-3.5 bg-white border @error('password') border-red-300 @else border-gray-200 @enderror rounded-xl text-accent placeholder:text-gray-400 focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all" />
            <button type="button" onclick="togglePassword()" class="absolute inset-y-0 end-0 flex items-center pe-4 text-gray-400 hover:text-primary transition-colors">
              <svg id="eye-icon" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
            </button>
          </div>
          @error('password')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <div>
          <label for="password_confirmation" class="block text-sm font-semibold text-accent mb-1.5">{{ __('levels.confirm_password') }}</label>
          <div class="relative">
            <span class="absolute inset-y-0 start-0 flex items-center ps-4 text-gray-400 pointer-events-none">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </span>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••"
                   class="w-full ps-12 pe-4 py-3.5 bg-white border border-gray-200 rounded-xl text-accent placeholder:text-gray-400 focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all" />
          </div>
        </div>

        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 py-3.5 rounded-xl text-white font-semibold bg-gradient-to-r from-primary to-secondary shadow-lg shadow-primary/25 hover:shadow-xl hover:shadow-primary/40 hover:-translate-y-px transition-all">
          {{ __('levels.reset_password') }}
          <svg class="w-5 h-5 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </button>
      </form>

      <p class="mt-6 text-center text-sm text-gray-500">
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
        {{ __('levels.password') }}
      </span>
      <h2 class="mt-8 text-3xl lg:text-4xl font-bold leading-tight">{{ __('levels.new_password') }}</h2>
      <ul class="mt-10 space-y-4">
        @foreach ([__('levels.password_tip_length'), __('levels.password_tip_mix'), __('levels.password_tip_uniq')] as $tip)
          <li class="flex items-start gap-3">
            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-white/15 border border-white/20 shrink-0">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </span>
            <span class="pt-0.5 text-white/85">{{ $tip }}</span>
          </li>
        @endforeach
      </ul>
    </div>
  </div>
</div>
@endsection
