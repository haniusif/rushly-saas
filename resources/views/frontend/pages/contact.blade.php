@extends('frontend.layouts.master')

@section('title')
    {{ @$page->title }} | {{ @settings()->name }}
@endsection

@section('content')
@php
  $mapLink   = section(\App\Enums\SectionType::MAP_LINK, 'map_link');
  $mapShare  = 'https://maps.app.goo.gl/X59NAHSGMWRCRGhS9';
@endphp

{{-- Hero --}}
<section class="relative overflow-hidden pt-12 pb-12 lg:pt-16 lg:pb-16 bg-surface border-b border-gray-100">
  <div aria-hidden="true" class="pointer-events-none absolute -top-32 end-[-8rem] w-[28rem] h-[28rem] rounded-full bg-gradient-to-br from-primary/10 to-secondary/10 blur-3xl"></div>

  <div class="relative max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
    <span class="inline-block text-xs font-semibold uppercase tracking-wider text-primary">{{ __('levels.contact') }}</span>
    <h1 class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-accent">
      {{ @$page->title ?: __('levels.get_in_touch') }}
    </h1>
    @if(!empty($page->description))
      <div class="mt-4 text-base text-gray-600 leading-relaxed prose prose-sm sm:prose-base mx-auto">
        {!! $page->description !!}
      </div>
    @endif
  </div>
</section>

{{-- Form + contact card --}}
<section class="py-12 lg:py-16">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid lg:grid-cols-5 gap-8 lg:gap-10">

      {{-- Form --}}
      <div class="lg:col-span-3">
        <div class="rounded-2xl border border-gray-100 bg-white shadow-sm p-6 sm:p-8 lg:p-10">
          <h2 class="text-xl sm:text-2xl font-bold text-accent">{{ __('levels.send_us_message') }}</h2>
          <p class="mt-2 text-sm text-gray-500">{{ __('levels.we_reply_within') }}</p>

          <form action="{{ route('contact.message.send') }}" method="post" enctype="multipart/form-data" class="mt-8 space-y-5">
            @csrf

            <div class="grid sm:grid-cols-2 gap-5">
              {{-- Name --}}
              <div>
                <label for="contact-name" class="block text-sm font-semibold text-accent mb-1.5">
                  {{ __('levels.name') }} <span class="text-red-500">*</span>
                </label>
                <input id="contact-name" type="text" name="name" value="{{ old('name') }}"
                       placeholder="{{ __('levels.enter_name') }}"
                       class="w-full px-4 py-3 bg-white border @error('name') border-red-300 @else border-gray-200 @enderror rounded-xl text-accent placeholder:text-gray-400 focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all" />
                @error('name')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
              </div>

              {{-- Email --}}
              <div>
                <label for="contact-email" class="block text-sm font-semibold text-accent mb-1.5">
                  {{ __('levels.email') }} <span class="text-red-500">*</span>
                </label>
                <input id="contact-email" type="email" name="email" value="{{ old('email') }}"
                       placeholder="{{ __('levels.enter_email') }}"
                       class="w-full px-4 py-3 bg-white border @error('email') border-red-300 @else border-gray-200 @enderror rounded-xl text-accent placeholder:text-gray-400 focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all" />
                @error('email')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
              </div>
            </div>

            {{-- Subject --}}
            <div>
              <label for="contact-subject" class="block text-sm font-semibold text-accent mb-1.5">
                {{ __('levels.subject') }} <span class="text-red-500">*</span>
              </label>
              <input id="contact-subject" type="text" name="subject" value="{{ old('subject') }}"
                     placeholder="{{ __('levels.enter_subject') }}"
                     class="w-full px-4 py-3 bg-white border @error('subject') border-red-300 @else border-gray-200 @enderror rounded-xl text-accent placeholder:text-gray-400 focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all" />
              @error('subject')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            {{-- Message --}}
            <div>
              <label for="contact-message" class="block text-sm font-semibold text-accent mb-1.5">
                {{ __('levels.message') }} <span class="text-red-500">*</span>
              </label>
              <textarea id="contact-message" name="message" rows="6"
                        placeholder="{{ __('levels.enter_your_message') }}"
                        class="w-full px-4 py-3 bg-white border @error('message') border-red-300 @else border-gray-200 @enderror rounded-xl text-accent placeholder:text-gray-400 focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all resize-y">{{ old('message') }}</textarea>
              @error('message')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            {{-- Submit --}}
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl text-white font-semibold
                           bg-gradient-to-r from-primary to-secondary shadow-lg shadow-primary/25
                           hover:shadow-xl hover:shadow-primary/40 hover:-translate-y-px transition-all">
              {{ __('levels.submit') }}
              <svg class="w-5 h-5 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
          </form>
        </div>
      </div>

      {{-- Contact card --}}
      <div class="lg:col-span-2">
        <div class="rounded-2xl border border-gray-100 bg-white shadow-sm p-6 sm:p-8 h-full">
          <h2 class="text-xl sm:text-2xl font-bold text-accent">{{ __('levels.contact_info') }}</h2>
          <p class="mt-2 text-sm text-gray-500">{{ __('levels.get_in_touch') }}.</p>

          <ul class="mt-7 space-y-5">
            @if(@settings()->email)
              <li class="flex items-start gap-4">
                <span class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-gradient-to-br from-primary/10 to-secondary/10 text-primary shrink-0">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </span>
                <div class="min-w-0">
                  <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('levels.email') }}</p>
                  <a href="mailto:{{ @settings()->email }}" class="mt-1 block text-sm font-medium text-accent hover:text-primary transition-colors break-all">
                    {{ @settings()->email }}
                  </a>
                </div>
              </li>
            @endif

            @if(@settings()->phone)
              <li class="flex items-start gap-4">
                <span class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-gradient-to-br from-primary/10 to-secondary/10 text-primary shrink-0">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                </span>
                <div class="min-w-0">
                  <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('levels.phone') }}</p>
                  <a href="tel:{{ @settings()->phone }}" class="mt-1 block text-sm font-medium text-accent hover:text-primary transition-colors">
                    {{ @settings()->phone }}
                  </a>
                </div>
              </li>
            @endif

            @if(@settings()->address)
              <li class="flex items-start gap-4">
                <span class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-gradient-to-br from-primary/10 to-secondary/10 text-primary shrink-0">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </span>
                <div class="min-w-0">
                  <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('levels.address') }}</p>
                  <a href="{{ $mapShare }}" target="_blank" rel="noopener" class="mt-1 inline-flex items-start gap-1.5 text-sm font-medium text-accent hover:text-primary transition-colors leading-relaxed group">
                    <span>{{ @settings()->address }}</span>
                    <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-gray-400 group-hover:text-primary transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                  </a>
                </div>
              </li>
            @endif
          </ul>

          @if(count($social_links ?? []))
            <div class="mt-8 pt-6 border-t border-gray-100">
              <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('levels.social') }}</p>
              <div class="mt-3 flex flex-wrap items-center gap-2">
                @foreach ($social_links as $social)
                  <a href="{{ @$social->link }}" title="{{ $social->name }}" target="_blank" rel="noopener"
                     class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-50 border border-gray-100 text-gray-500 hover:text-primary hover:border-primary/30 hover:bg-primary/5 transition-all">
                    <i class="{{ $social->icon }}"></i>
                  </a>
                @endforeach
              </div>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</section>

{{-- Map --}}
@if(!empty($mapLink))
  <section class="pb-16 lg:pb-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500">{{ __('levels.find_us_on_map') }}</h3>
      </div>
      <div class="rounded-2xl overflow-hidden border border-gray-100 shadow-sm">
        <iframe src="{{ $mapLink }}" width="100%" height="500" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade" class="block w-full"></iframe>
      </div>
    </div>
  </section>
@endif
@endsection
