@extends('auth.layouts')
@section('title', __('levels.merchant_registration'))
@section('body-class', 'login-redesign')

@push('styles')
<style>
  body.login-redesign {
    display: block !important;
    min-height: 100vh;
    background: #F8FAFC;
  }
  body.login-redesign.rtl { font-family: 'Tajawal', sans-serif; }
</style>
@endpush

@section('content')
@php
  $locale = app()->getLocale();
@endphp

<div class="min-h-screen grid grid-cols-1 lg:grid-cols-5 bg-surface">

  {{-- ============ LEFT: FORM ============ --}}
  <div class="relative lg:col-span-3 flex items-start justify-center px-6 py-12 sm:px-12 lg:px-16">
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

    <div class="w-full max-w-xl mt-12 lg:mt-16">
      {{-- Logo --}}
      <a href="{{ url('/') }}" class="inline-block">
        <img src="{{ @settings()->logo_image }}" alt="{{ @settings()->name }}" class="h-10 w-auto" />
      </a>

      {{-- Heading --}}
      <h1 class="mt-10 text-3xl sm:text-4xl font-bold tracking-tight text-accent">
        {{ __('levels.merchant_registration') }}
      </h1>
      <p class="mt-3 text-base text-gray-500">
        {{ __('levels.merchant_registration_lead') }}
      </p>

      {{-- Form --}}
      <form method="POST" action="{{ route('merchant.sign-up-store') }}" class="mt-8 space-y-5">
        @csrf

        <div class="grid sm:grid-cols-2 gap-5">
          {{-- Business name --}}
          <div>
            <label for="business_name" class="block text-sm font-semibold text-accent mb-1.5">
              {{ __('levels.business_name') }} <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <span class="absolute inset-y-0 start-0 flex items-center ps-4 text-gray-400 pointer-events-none">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
              </span>
              <input id="business_name" type="text" name="business_name" value="{{ old('business_name') }}" required autofocus
                     placeholder="{{ __('levels.enter_business_name') }}"
                     class="w-full ps-12 pe-4 py-3 bg-white border @error('business_name') border-red-300 @else border-gray-200 @enderror rounded-xl text-accent placeholder:text-gray-400 focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all" />
            </div>
            @error('business_name')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
          </div>

          {{-- Full name --}}
          <div>
            <label for="full_name" class="block text-sm font-semibold text-accent mb-1.5">
              {{ __('levels.full_name') }} <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <span class="absolute inset-y-0 start-0 flex items-center ps-4 text-gray-400 pointer-events-none">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              </span>
              <input id="full_name" type="text" name="full_name" value="{{ old('full_name') }}" required autocomplete="name"
                     placeholder="{{ __('levels.enter_full_name') }}"
                     class="w-full ps-12 pe-4 py-3 bg-white border @error('full_name') border-red-300 @else border-gray-200 @enderror rounded-xl text-accent placeholder:text-gray-400 focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all" />
            </div>
            @error('full_name')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
          </div>
        </div>

        {{-- Hub --}}
        <div>
          <label for="hub_id" class="block text-sm font-semibold text-accent mb-1.5">
            {{ __('levels.hub') ?? 'Hub' }} <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <span class="absolute inset-y-0 start-0 flex items-center ps-4 text-gray-400 pointer-events-none">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </span>
            <select id="hub_id" name="hub_id" required
                    class="w-full ps-12 pe-10 py-3 bg-white border @error('hub_id') border-red-300 @else border-gray-200 @enderror rounded-xl text-accent focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all appearance-none">
              <option value="" disabled {{ old('hub_id') ? '' : 'selected' }}>{{ __('levels.select_hub') }}</option>
              @foreach ($hubs as $hub)
                <option value="{{ $hub->id }}" {{ old('hub_id') == $hub->id ? 'selected' : '' }}>{{ $hub->name }}</option>
              @endforeach
            </select>
            <span class="absolute inset-y-0 end-0 flex items-center pe-4 text-gray-400 pointer-events-none">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </span>
          </div>
          @error('hub_id')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <div class="grid sm:grid-cols-2 gap-5">
          {{-- Mobile --}}
          <div>
            <label for="mobile" class="block text-sm font-semibold text-accent mb-1.5">
              {{ __('levels.mobile_number') }} <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <span class="absolute inset-y-0 start-0 flex items-center ps-4 text-gray-400 pointer-events-none">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
              </span>
              <input id="mobile" type="tel" name="mobile" value="{{ old('mobile', $request->phone ?? '') }}" required autocomplete="tel"
                     placeholder="{{ __('levels.enter_mobile') }}"
                     class="w-full ps-12 pe-4 py-3 bg-white border @error('mobile') border-red-300 @else border-gray-200 @enderror rounded-xl text-accent placeholder:text-gray-400 focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all" />
            </div>
            @error('mobile')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
          </div>

          {{-- Password --}}
          <div>
            <label for="password" class="block text-sm font-semibold text-accent mb-1.5">
              {{ __('levels.password') }} <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <span class="absolute inset-y-0 start-0 flex items-center ps-4 text-gray-400 pointer-events-none">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
              </span>
              <input id="password" type="password" name="password" required autocomplete="new-password"
                     placeholder="••••••••"
                     class="w-full ps-12 pe-12 py-3 bg-white border @error('password') border-red-300 @else border-gray-200 @enderror rounded-xl text-accent placeholder:text-gray-400 focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all" />
              <button type="button" onclick="togglePassword()" class="absolute inset-y-0 end-0 flex items-center pe-4 text-gray-400 hover:text-primary transition-colors">
                <svg id="eye-icon" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
              </button>
            </div>
            @error('password')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
          </div>
        </div>

        {{-- Address --}}
        <div>
          <label for="address" class="block text-sm font-semibold text-accent mb-1.5">
            {{ __('levels.address') }} <span class="text-red-500">*</span>
          </label>
          <textarea id="address" name="address" rows="3" required
                    placeholder="{{ __('levels.enter_address') }}"
                    class="w-full px-4 py-3 bg-white border @error('address') border-red-300 @else border-gray-200 @enderror rounded-xl text-accent placeholder:text-gray-400 focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all resize-y">{{ old('address') }}</textarea>
          @error('address')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        {{-- Policy --}}
        <div>
          <label for="policy" class="flex items-start gap-3 cursor-pointer select-none">
            <input id="policy" type="checkbox" name="policy" required
                   class="w-4 h-4 mt-1 rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary/20" />
            <span class="text-sm text-gray-600">
              {{ __('levels.i_agree_to') }}
              <a href="{{ route('privacy.policy.index') }}" target="_blank" class="font-semibold text-primary hover:text-secondary transition-colors">{!! __('levels.privacy_and_terms') !!}</a>
            </span>
          </label>
          @error('policy')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        {{-- Submit --}}
        <button type="submit"
                class="w-full inline-flex items-center justify-center gap-2 py-3.5 rounded-xl text-white font-semibold
                       bg-gradient-to-r from-primary to-secondary shadow-lg shadow-primary/25
                       hover:shadow-xl hover:shadow-primary/40 hover:-translate-y-px transition-all">
          {{ __('levels.register_my_account') }}
          <svg class="w-5 h-5 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </button>
      </form>

      {{-- Login link --}}
      <p class="mt-6 text-center text-sm text-gray-500">
        {{ __('levels.already_a_member') }}
        <a href="{{ route('login') }}" class="ms-1 font-semibold text-primary hover:text-secondary transition-colors">
          {{ __('levels.login_here') }}
        </a>
      </p>

      <p class="mt-8 flex items-center justify-center gap-1.5 text-xs text-gray-400">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        {{ __('levels.secured_by') }}
      </p>
    </div>
  </div>

  {{-- ============ RIGHT: GRADIENT PANEL ============ --}}
  <div class="hidden lg:flex lg:col-span-2 relative overflow-hidden gradient-primary text-white">
    <div aria-hidden="true" class="pointer-events-none absolute -top-32 -end-32 w-[36rem] h-[36rem] rounded-full bg-white/10 blur-3xl"></div>
    <div aria-hidden="true" class="pointer-events-none absolute -bottom-32 -start-24 w-[28rem] h-[28rem] rounded-full bg-white/5 blur-3xl"></div>
    <div aria-hidden="true" class="absolute inset-0 opacity-[0.05]"
         style="background-image: linear-gradient(to right, #fff 1px, transparent 1px), linear-gradient(to bottom, #fff 1px, transparent 1px); background-size: 48px 48px;"></div>

    <div class="relative z-10 flex flex-col justify-center p-10 lg:p-14 xl:p-16 w-full">
      <span class="inline-flex w-fit items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wider bg-white/15 backdrop-blur-sm border border-white/20">
        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
        {{ __('levels.smart_logistics_platform') }}
      </span>

      <h2 class="mt-8 text-3xl lg:text-4xl font-bold tracking-tight leading-tight">
        {{ __('levels.grow_your_business') }}
      </h2>
      <p class="mt-4 text-white/80 leading-relaxed">{{ __('levels.merchant_grow_subtitle') }}</p>

      <ul class="mt-10 space-y-5">
        @php
          $perks = [
            ['title' => __('levels.merchant_perk_reach'), 'desc' => __('levels.merchant_perk_reach_desc'), 'icon' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 002 2 2 2 0 012-2h2.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['title' => __('levels.merchant_perk_cod'),   'desc' => __('levels.merchant_perk_cod_desc'),   'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['title' => __('levels.merchant_perk_tools'), 'desc' => __('levels.merchant_perk_tools_desc'), 'icon' => 'M9 17v-2a4 4 0 014-4h6m-6 6l3-3m-3 3l-3-3m3-7H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-7'],
          ];
        @endphp
        @foreach ($perks as $p)
          <li class="flex items-start gap-4">
            <span class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-white/15 backdrop-blur-sm border border-white/20 shrink-0">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $p['icon'] }}"/></svg>
            </span>
            <div>
              <p class="font-semibold text-white">{{ $p['title'] }}</p>
              <p class="mt-1 text-sm text-white/70 leading-relaxed">{{ $p['desc'] }}</p>
            </div>
          </li>
        @endforeach
      </ul>
    </div>
  </div>
</div>
@endsection
