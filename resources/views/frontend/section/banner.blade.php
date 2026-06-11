{{-- Hero --}}
<section id="home" class="relative overflow-hidden pt-12 pb-20 lg:pt-20 lg:pb-28">
  {{-- Soft background accents --}}
  <div aria-hidden="true" class="pointer-events-none absolute -top-32 end-[-8rem] w-[36rem] h-[36rem] rounded-full bg-gradient-to-br from-primary/10 to-secondary/10 blur-3xl"></div>
  <div aria-hidden="true" class="pointer-events-none absolute top-1/3 start-[-10rem] w-[24rem] h-[24rem] rounded-full bg-primary/5 blur-3xl"></div>

  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid lg:grid-cols-12 gap-10 lg:gap-16 items-center">

      {{-- Copy --}}
      <div class="lg:col-span-6 reveal">
        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wider text-primary bg-primary/8 border border-primary/15">
          <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
          {{ __('levels.smart_logistics_platform') }}
        </span>

        <h1 class="mt-6 text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-accent leading-[1.05]">
          <span class="block">{{ section(\App\Enums\SectionType::BANNER, 'title_1') }}</span>
          <span class="block gradient-text">{{ section(\App\Enums\SectionType::BANNER, 'title_2') }}</span>
          <span class="block">{{ section(\App\Enums\SectionType::BANNER, 'title_3') }}</span>
        </h1>

        <p class="mt-6 text-lg sm:text-xl text-gray-600 max-w-xl">
          {{ section(\App\Enums\SectionType::BANNER, 'sub_title') }}
        </p>

        {{-- Tracking form --}}
        <form action="{{ route('tracking.index') }}" method="get" class="mt-8 max-w-xl">
          <div class="flex items-stretch bg-white border border-gray-200 rounded-2xl shadow-sm focus-within:border-primary focus-within:shadow-md transition-all overflow-hidden">
            <div class="flex items-center ps-4 text-gray-400">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input type="text" name="tracking_id" required
                   placeholder="{{ __('levels.enter_tracking_id') }}"
                   class="flex-1 min-w-0 px-3 py-4 bg-transparent text-accent placeholder:text-gray-400 focus:outline-none" />
            <button type="submit" class="btn-primary text-white px-5 sm:px-7 py-4 font-semibold whitespace-nowrap">
              {{ __('levels.track_now') }}
            </button>
          </div>
        </form>

        {{-- Secondary actions / trust --}}
        <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm">
          <a href="{{ tenant() ? route('merchant.sign-up') : route('company.sign-up') }}" class="inline-flex items-center gap-1.5 font-semibold text-accent hover:text-primary transition-colors">
            {{ __('levels.register') }}
            <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </a>
          <span class="hidden sm:inline-block w-1 h-1 rounded-full bg-gray-300"></span>
          <span class="inline-flex items-center gap-1 text-gray-500">
            <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.367 2.446a1 1 0 00-.364 1.118l1.287 3.957c.3.921-.755 1.688-1.54 1.118l-3.367-2.446a1 1 0 00-1.176 0L5.043 17.02c-.784.57-1.838-.197-1.539-1.118l1.287-3.957a1 1 0 00-.364-1.118L1.06 8.381c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.957z"/></svg>
            {{ __('levels.trusted_by_merchants') }}
          </span>
        </div>
      </div>

      {{-- Visual --}}
      <div class="lg:col-span-6 reveal">
        <div class="relative">
          <div aria-hidden="true" class="absolute inset-0 -m-6 rounded-[2rem] bg-gradient-to-tr from-primary/15 via-secondary/10 to-transparent blur-2xl"></div>
          @if (section(\App\Enums\SectionType::BANNER, 'banner'))
            <img src="{{ section(\App\Enums\SectionType::BANNER, 'banner') }}"
                 alt="{{ section(\App\Enums\SectionType::BANNER, 'title_1') }}"
                 class="relative w-full max-w-xl mx-auto rounded-2xl shadow-2xl shadow-primary/10" />
          @else
            <div class="relative aspect-[4/3] w-full max-w-xl mx-auto rounded-2xl bg-gradient-to-br from-primary/5 to-secondary/10 border border-gray-100 flex items-center justify-center">
              <svg class="w-24 h-24 text-primary/30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0l-8 5-8-5m16 0v7a2 2 0 01-2 2H6a2 2 0 01-2-2v-7"/></svg>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</section>
