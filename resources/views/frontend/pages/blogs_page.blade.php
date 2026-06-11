@extends('frontend.layouts.master')

@section('title')
    {{ __('levels.blogs') }} | {{ @settings()->name }}
@endsection

@section('content')
@php
  $list      = $blogs->items();
  $featured  = ($blogs->currentPage() === 1 && count($list) > 0) ? $list[0] : null;
  $rest      = $featured ? array_slice($list, 1) : $list;
@endphp

{{-- Hero --}}
<section class="relative overflow-hidden pt-12 pb-12 lg:pt-16 lg:pb-16 bg-surface border-b border-gray-100">
  <div aria-hidden="true" class="pointer-events-none absolute -top-32 end-[-8rem] w-[28rem] h-[28rem] rounded-full bg-gradient-to-br from-primary/10 to-secondary/10 blur-3xl"></div>

  <div class="relative max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
    <span class="inline-block text-xs font-semibold uppercase tracking-wider text-primary">{{ __('levels.blogs') }}</span>
    <h1 class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-accent">
      {{ __('levels.latest_from_blog') }}
    </h1>
    <p class="mt-3 text-base text-gray-600">{{ __('levels.blog_lead') }}</p>
  </div>
</section>

{{-- Posts --}}
<section class="py-12 lg:py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    @if(count($list) === 0)
      <div class="rounded-2xl border border-gray-100 bg-white shadow-sm p-12 text-center">
        <div class="mx-auto w-16 h-16 rounded-2xl bg-primary/5 flex items-center justify-center">
          <svg class="w-8 h-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
        </div>
        <p class="mt-4 text-gray-500">{{ __('levels.no_blogs_yet') }}</p>
      </div>
    @else

      {{-- Featured post (first page only) --}}
      @if($featured)
        <a href="{{ route('blog.details', $featured->id) }}"
           class="group block rounded-2xl border border-gray-100 bg-white shadow-sm hover:shadow-xl hover:shadow-primary/5 hover:border-primary/30 transition-all overflow-hidden mb-10">
          <div class="grid lg:grid-cols-2 gap-0">
            <div class="aspect-[16/10] lg:aspect-auto overflow-hidden bg-gray-100">
              <img src="{{ $featured->image }}" alt="{{ $featured->title }}"
                   class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
            </div>
            <div class="p-7 sm:p-10 lg:p-12 flex flex-col justify-center">
              <span class="inline-flex w-fit items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider bg-primary/8 text-primary border border-primary/15">
                <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                {{ __('levels.featured') }}
              </span>
              <h2 class="mt-5 text-2xl sm:text-3xl font-bold text-accent group-hover:text-primary transition-colors leading-tight">
                {{ $featured->title }}
              </h2>
              <div class="mt-6 flex items-center gap-4 text-xs text-gray-500">
                <span class="inline-flex items-center gap-1.5"><i class="fa fa-user text-primary"></i>{{ $featured->user->name ?? '' }}</span>
                <span class="inline-flex items-center gap-1.5"><i class="fa fa-eye text-primary"></i>{{ $featured->views }}</span>
                <span class="inline-flex items-center gap-1.5"><i class="fa fa-calendar text-primary"></i>{{ $featured->updated_at->format('d M Y') }}</span>
              </div>
              <span class="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold text-primary group-hover:gap-2.5 transition-all">
                {{ __('levels.read_article') }}
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
              </span>
            </div>
          </div>
        </a>
      @endif

      {{-- Grid --}}
      @if(count($rest))
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
          @foreach ($rest as $blog)
            <article class="group bg-white border border-gray-100 rounded-2xl overflow-hidden hover:border-primary/30 hover:shadow-xl hover:shadow-primary/5 hover:-translate-y-1 transition-all duration-300">
              <a href="{{ route('blog.details', $blog->id) }}" class="block aspect-[16/10] overflow-hidden bg-gray-100">
                <img src="{{ $blog->image }}" alt="{{ $blog->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
              </a>
              <div class="p-6">
                <a href="{{ route('blog.details', $blog->id) }}">
                  <h3 class="text-lg font-semibold text-accent group-hover:text-primary transition-colors line-clamp-2">{{ $blog->title }}</h3>
                </a>
                <div class="mt-5 pt-5 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                  <span class="inline-flex items-center gap-1.5">
                    <i class="fa fa-user text-primary"></i> {{ $blog->user->name ?? '' }}
                  </span>
                  <span class="inline-flex items-center gap-3">
                    <span class="inline-flex items-center gap-1"><i class="fa fa-eye text-primary"></i>{{ $blog->views }}</span>
                    <span class="inline-flex items-center gap-1"><i class="fa fa-calendar text-primary"></i>{{ $blog->updated_at->format('d M Y') }}</span>
                  </span>
                </div>
              </div>
            </article>
          @endforeach
        </div>
      @endif

      {{-- Pagination --}}
      @if($blogs->hasPages())
        <nav class="mt-12 flex items-center justify-between gap-4" aria-label="Pagination">
          @if($blogs->onFirstPage())
            <span class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium text-gray-400 border border-gray-200 bg-white cursor-not-allowed">
              <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
              {{ __('levels.previous') }}
            </span>
          @else
            <a href="{{ $blogs->previousPageUrl() }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-accent border border-gray-200 bg-white hover:border-primary hover:text-primary hover:bg-primary/5 transition-colors">
              <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
              {{ __('levels.previous') }}
            </a>
          @endif

          <span class="text-sm text-gray-500">
            {{ $blogs->currentPage() }} / {{ $blogs->lastPage() }}
          </span>

          @if($blogs->hasMorePages())
            <a href="{{ $blogs->nextPageUrl() }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-accent border border-gray-200 bg-white hover:border-primary hover:text-primary hover:bg-primary/5 transition-colors">
              {{ __('levels.next') }}
              <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
          @else
            <span class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium text-gray-400 border border-gray-200 bg-white cursor-not-allowed">
              {{ __('levels.next') }}
              <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </span>
          @endif
        </nav>
      @endif

    @endif
  </div>
</section>
@endsection
