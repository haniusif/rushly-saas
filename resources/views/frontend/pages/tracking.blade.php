@extends('frontend.layouts.master')

@section('title')
    {{ __('levels.parcel_tracking') }} | {{ @settings()->name }}
@endsection

@section('content')
@php
  $tid          = $request->tracking_id ?? '';
  $hasQuery     = !empty($tid);
  $hasParcel    = $hasQuery && $parcel;
  $statusLabel  = $hasParcel ? \App\Support\ParcelStatusHelper::label((int) $parcel->status) : null;
  $statusBadge  = $hasParcel ? \App\Support\ParcelStatusHelper::badgeClass((int) $parcel->status) : null;
  $isCancelled  = $hasParcel ? \App\Support\ParcelStatusHelper::isCanceled((int) $parcel->status) : false;
@endphp

{{-- Search hero --}}
<section class="relative overflow-hidden pt-10 pb-12 lg:pt-16 lg:pb-16 bg-surface border-b border-gray-100">
  <div aria-hidden="true" class="pointer-events-none absolute -top-32 end-[-8rem] w-[28rem] h-[28rem] rounded-full bg-gradient-to-br from-primary/10 to-secondary/10 blur-3xl"></div>

  <div class="relative max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
    <span class="inline-block text-xs font-semibold uppercase tracking-wider text-primary">{{ __('levels.tracking') }}</span>
    <h1 class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-accent">
      {{ __('levels.track_your_parcel') }}
    </h1>
    <p class="mt-3 text-base text-gray-600">{{ __('levels.track_your_parcel_desc') }}</p>

    <form action="{{ route('tracking.index') }}" method="GET" class="mt-8">
      <div class="flex items-stretch bg-white border border-gray-200 rounded-2xl shadow-sm focus-within:border-primary focus-within:shadow-md transition-all overflow-hidden">
        <div class="flex items-center ps-4 text-gray-400">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <input type="text" name="tracking_id" value="{{ $tid }}" required
               placeholder="{{ __('levels.enter_tracking_id') }}"
               class="flex-1 min-w-0 px-3 py-4 bg-transparent text-accent placeholder:text-gray-400 focus:outline-none" />
        <button type="submit" class="btn-primary text-white px-5 sm:px-7 py-4 font-semibold whitespace-nowrap">
          {{ __('levels.track_now') }}
        </button>
      </div>
    </form>
  </div>
</section>

{{-- Result --}}
<section class="py-12 lg:py-16">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

    @if($hasParcel)
      {{-- Status summary card --}}
      <div class="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
        <div class="p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-100">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('levels.parcel_tracking_no') }}</p>
            <p class="mt-1 text-xl sm:text-2xl font-bold text-accent">#{{ $tid }}</p>
          </div>
          <div class="text-start sm:text-end">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('levels.current_status') }}</p>
            <span class="mt-1 inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-semibold
                         {{ $isCancelled ? 'bg-red-50 text-red-600 border border-red-100' : 'bg-primary/8 text-primary border border-primary/15' }}">
              <span class="w-1.5 h-1.5 rounded-full {{ $isCancelled ? 'bg-red-500' : 'bg-primary' }}"></span>
              {{ $statusLabel }}
            </span>
          </div>
        </div>

        {{-- Timeline --}}
        <div class="p-6 sm:p-8">
          <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-6">{{ __('levels.shipment_history') }}</h2>

          <ol class="relative">
            {{-- Vertical guide line --}}
            <span aria-hidden="true" class="absolute top-2 bottom-2 start-[15px] w-px bg-gray-200"></span>

            @foreach ($parcelevents as $log)
              @php $danger = !empty($log->cancel_parcel_id); @endphp
              <li class="relative ps-12 pb-7 last:pb-0">
                <span class="absolute start-0 top-0 inline-flex items-center justify-center w-8 h-8 rounded-full
                             {{ $danger ? 'bg-red-50 border-2 border-red-200 text-red-600' : 'bg-white border-2 border-primary/30 text-primary' }}">
                  @if($danger)
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                  @else
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                  @endif
                </span>
                <div class="flex flex-col sm:flex-row sm:items-baseline sm:justify-between gap-1">
                  <p class="font-semibold text-accent">
                    {{ __('parcelLogs.'.$log->parcel_status) }}
                    @if($danger)
                      <span class="ms-2 text-xs font-medium text-red-500">{{ __('levels.cancelled') ?? 'cancelled' }}</span>
                    @endif
                  </p>
                  <p class="text-xs text-gray-500 whitespace-nowrap">
                    {!! dateFormat($log->created_at) !!} · {{ date('h:i a', strtotime($log->created_at)) }}
                  </p>
                </div>
              </li>
            @endforeach

            <li class="relative ps-12">
              <span class="absolute start-0 top-0 inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary text-white">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
              </span>
              <div class="flex flex-col sm:flex-row sm:items-baseline sm:justify-between gap-1">
                <p class="font-semibold text-accent">{{ __('levels.parcel_created') }}</p>
                <p class="text-xs text-gray-500 whitespace-nowrap">
                  {!! dateFormat($parcel->created_at) !!} · {{ date('h:i a', strtotime($parcel->created_at)) }}
                </p>
              </div>
            </li>
          </ol>
        </div>
      </div>

    @elseif($hasQuery && !$parcel)
      {{-- Not found --}}
      <div class="rounded-2xl border border-gray-100 bg-white shadow-sm p-10 sm:p-14 text-center">
        <div class="mx-auto w-20 h-20 rounded-2xl bg-primary/5 flex items-center justify-center">
          <svg class="w-10 h-10 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h2 class="mt-6 text-xl sm:text-2xl font-bold text-accent">{{ __('levels.parcel_not_found') }}</h2>
        <p class="mt-2 text-sm text-gray-500 max-w-md mx-auto">{{ __('levels.parcel_not_found_desc') }}</p>
        @if($tid)
          <p class="mt-4 inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-50 text-xs font-mono text-gray-600 border border-gray-100">
            #{{ $tid }}
          </p>
        @endif
      </div>
    @endif

  </div>
</section>
@endsection
