{{-- Services --}}
<section id="services" class="py-20 lg:py-28">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto text-center reveal">
      <span class="inline-block text-xs font-semibold uppercase tracking-wider text-primary">{{ __('levels.our_services') }}</span>
      <h2 class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-accent">
        {{ __('levels.our_services') }}
      </h2>
    </div>

    @if(count($services))
      <div class="mt-14 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach ($services as $service)
          <a href="{{ route('service.details', $service->id) }}"
             class="reveal group relative flex flex-col bg-white border border-gray-100 rounded-2xl p-6 hover:border-primary/30 hover:shadow-xl hover:shadow-primary/5 hover:-translate-y-1 transition-all duration-300">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-primary/8 p-2.5">
              <img src="{{ $service->image }}" alt="{{ $service->title }}" class="w-full h-full object-contain" />
            </div>
            <h3 class="mt-5 text-base font-semibold text-accent">{{ $service->title }}</h3>
            <p class="mt-2 text-sm text-gray-600 leading-relaxed flex-1">
              {!! \Str::limit(strip_tags($service->description), 110) !!}
            </p>
            <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-primary group-hover:gap-2 transition-all">
              {{ __('levels.learn_more') }}
              <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </span>
          </a>
        @endforeach
      </div>
    @endif
  </div>
</section>
