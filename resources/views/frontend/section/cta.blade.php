{{-- CTA banner --}}
<section class="py-12 lg:py-20">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="relative overflow-hidden rounded-3xl gradient-primary px-6 py-12 sm:px-12 sm:py-16 lg:py-20">
      <div aria-hidden="true" class="absolute -top-32 -end-24 w-[28rem] h-[28rem] rounded-full bg-white/10 blur-3xl"></div>
      <div aria-hidden="true" class="absolute -bottom-32 -start-24 w-[24rem] h-[24rem] rounded-full bg-white/5 blur-3xl"></div>

      <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
        <div class="max-w-2xl text-center lg:text-start">
          <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white tracking-tight">
            {{ __('levels.start_shipping_today') }}
          </h2>
          <p class="mt-4 text-base sm:text-lg text-white/85 leading-relaxed">
            {{ __('levels.start_shipping_today_desc') }}
          </p>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 justify-center lg:justify-end">
          <a href="{{ tenant() ? route('merchant.sign-up') : route('company.sign-up') }}"
             class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-white text-accent font-semibold hover:bg-white/90 transition-colors whitespace-nowrap">
            {{ __('levels.register') }}
            <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </a>
          <a href="{{ route('contact.send.page') }}"
             class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl border border-white/30 text-white font-semibold hover:bg-white/10 transition-colors whitespace-nowrap">
            {{ __('levels.book_demo') }}
          </a>
        </div>
      </div>
    </div>
  </div>
</section>
