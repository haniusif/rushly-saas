{{-- Features / Why us --}}
<section id="features" class="py-20 lg:py-28 bg-surface">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto text-center reveal">
      <span class="inline-block text-xs font-semibold uppercase tracking-wider text-primary">{{ __('levels.features') }}</span>
      <h2 class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-accent">
        {{ __('levels.why') }} <span class="gradient-text">{{ @settings()->name }}</span>
      </h2>
    </div>

    @if(count($whycouriers))
      <div class="mt-14 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
        @foreach ($whycouriers as $whycourier)
          <div class="reveal group relative bg-white border border-gray-100 rounded-2xl p-7 hover:border-primary/30 hover:shadow-xl hover:shadow-primary/5 hover:-translate-y-1 transition-all duration-300">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-xl bg-gradient-to-br from-primary/10 to-secondary/10 group-hover:from-primary group-hover:to-secondary transition-colors duration-300 p-3">
              <img src="{{ $whycourier->image }}" alt="{{ $whycourier->title }}" class="w-full h-full object-contain group-hover:brightness-0 group-hover:invert transition-all duration-300" />
            </div>
            <h3 class="mt-5 text-lg font-semibold text-accent">{{ $whycourier->title }}</h3>
            @if(!empty($whycourier->description))
              <p class="mt-2 text-sm text-gray-600 leading-relaxed">{!! \Str::limit(strip_tags($whycourier->description), 140) !!}</p>
            @endif
          </div>
        @endforeach
      </div>
    @endif
  </div>
</section>
