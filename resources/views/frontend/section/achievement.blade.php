{{-- Stats / Achievement --}}
<section class="py-20 lg:py-24">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="relative overflow-hidden rounded-3xl gradient-primary px-6 py-14 sm:px-12 sm:py-16">
      <div aria-hidden="true" class="absolute -top-20 -end-20 w-80 h-80 rounded-full bg-white/10 blur-3xl"></div>
      <div aria-hidden="true" class="absolute -bottom-24 -start-24 w-96 h-96 rounded-full bg-white/5 blur-3xl"></div>

      <div class="relative text-center mb-10">
        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white tracking-tight">
          {{ __('levels.happy_achievement') }}
        </h2>
      </div>

      <div class="relative grid grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-6">
        @php
          $stats = [
            ['icon' => section(\App\Enums\SectionType::ACHIEVEMENT,'branch_icon'),   'count' => section(\App\Enums\SectionType::ACHIEVEMENT,'branch_count'),   'title' => section(\App\Enums\SectionType::ACHIEVEMENT,'branch_title')],
            ['icon' => section(\App\Enums\SectionType::ACHIEVEMENT,'parcel_icon'),   'count' => section(\App\Enums\SectionType::ACHIEVEMENT,'parcel_count'),   'title' => section(\App\Enums\SectionType::ACHIEVEMENT,'parcel_title')],
            ['icon' => section(\App\Enums\SectionType::ACHIEVEMENT,'merchant_icon'), 'count' => section(\App\Enums\SectionType::ACHIEVEMENT,'merchant_count'), 'title' => section(\App\Enums\SectionType::ACHIEVEMENT,'merchant_title')],
            ['icon' => section(\App\Enums\SectionType::ACHIEVEMENT,'reviews_icon'),  'count' => section(\App\Enums\SectionType::ACHIEVEMENT,'reviews_count'),  'title' => section(\App\Enums\SectionType::ACHIEVEMENT,'reviews_title')],
          ];
        @endphp
        @foreach ($stats as $stat)
          <div class="reveal flex flex-col items-center text-center text-white">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-white/15 backdrop-blur-sm mb-4">
              <i class="{{ $stat['icon'] }} text-2xl"></i>
            </div>
            <div class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight">
              <span class="odometer" data-count="{{ (int) $stat['count'] }}">0</span><span>+</span>
            </div>
            <p class="mt-2 text-sm font-medium text-white/80">{{ $stat['title'] }}</p>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</section>
