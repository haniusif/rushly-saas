@extends('frontend.layouts.master')

@section('title')
    {{ @$page->title }} | {{ @settings()->name }}
@endsection

@section('content')
@php
  $eyebrow = match($page->page ?? '') {
    'privacy_policy'   => __('levels.privacy_policy'),
    'terms_conditions' => __('levels.terms_of_use'),
    'about_us'         => __('levels.about_us'),
    default            => __('levels.about'),
  };
@endphp

{{-- Hero --}}
<section class="relative overflow-hidden pt-12 pb-10 lg:pt-16 lg:pb-12 bg-surface border-b border-gray-100">
  <div aria-hidden="true" class="pointer-events-none absolute -top-32 end-[-8rem] w-[28rem] h-[28rem] rounded-full bg-gradient-to-br from-primary/10 to-secondary/10 blur-3xl"></div>

  <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
    <span class="inline-block text-xs font-semibold uppercase tracking-wider text-primary">{{ $eyebrow }}</span>
    <h1 class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-accent">
      {{ @$page->title }}
    </h1>
    @if(isset($page->updated_at))
      <p class="mt-4 inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white border border-gray-200 text-xs text-gray-500">
        <svg class="w-3.5 h-3.5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        {{ __('levels.last_updated') }}: {{ $page->updated_at->format('d M Y') }}
      </p>
    @endif
  </div>
</section>

{{-- Article body --}}
<section class="py-12 lg:py-16">
  <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <article class="prose prose-lg max-w-none
                    prose-headings:text-accent prose-headings:font-bold prose-headings:tracking-tight
                    prose-h1:text-3xl prose-h2:text-2xl prose-h3:text-xl prose-h3:mt-10 prose-h3:mb-3
                    prose-h2:mt-12 prose-h2:mb-4
                    prose-p:text-gray-600 prose-p:leading-relaxed
                    prose-strong:text-accent prose-strong:font-semibold
                    prose-a:text-primary prose-a:no-underline hover:prose-a:underline
                    prose-ul:my-4 prose-li:text-gray-600 prose-li:marker:text-primary
                    prose-ol:my-4
                    prose-blockquote:border-s-4 prose-blockquote:border-primary/40 prose-blockquote:bg-surface prose-blockquote:rounded-e-xl prose-blockquote:py-2 prose-blockquote:px-5 prose-blockquote:not-italic
                    prose-hr:border-gray-200
                    prose-img:rounded-xl">
      {!! $page->description !!}
    </article>

    {{-- Sibling-doc pointers + contact CTA --}}
    <div class="mt-16 pt-10 border-t border-gray-100">
      <div class="grid sm:grid-cols-2 gap-6">
        @php
          $siblings = [
            ['route' => 'aboutus.index',           'label' => __('levels.about_us'),       'page' => 'about_us',         'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['route' => 'privacy.policy.index',    'label' => __('levels.privacy_policy'), 'page' => 'privacy_policy',   'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
            ['route' => 'termsof.condition.index', 'label' => __('levels.terms_of_use'),   'page' => 'terms_conditions', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
          ];
          $others = array_values(array_filter($siblings, fn($s) => $s['page'] !== ($page->page ?? '')));
        @endphp
        @foreach ($others as $s)
          <a href="{{ route($s['route']) }}" class="group flex items-start gap-4 p-5 rounded-2xl border border-gray-100 bg-white hover:border-primary/30 hover:shadow-lg hover:shadow-primary/5 transition-all">
            <span class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-gradient-to-br from-primary/10 to-secondary/10 text-primary shrink-0">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $s['icon'] }}"/></svg>
            </span>
            <div class="min-w-0">
              <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('levels.read_article') }}</p>
              <p class="mt-1 text-base font-semibold text-accent group-hover:text-primary transition-colors">{{ $s['label'] }}</p>
            </div>
            <svg class="w-5 h-5 text-gray-300 group-hover:text-primary rtl:rotate-180 ms-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </a>
        @endforeach
      </div>

      <div class="mt-8 rounded-2xl gradient-primary text-white p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <p class="text-sm font-semibold uppercase tracking-wider text-white/70">{{ __('levels.get_in_touch') }}</p>
          <p class="mt-1 text-lg font-semibold">{{ __('levels.we_reply_within') }}</p>
        </div>
        <a href="{{ route('contact.send.page') }}" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-white text-accent font-semibold hover:bg-white/90 transition-colors whitespace-nowrap">
          {{ __('levels.contact_us') }}
          <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
      </div>
    </div>
  </div>
</section>
@endsection
