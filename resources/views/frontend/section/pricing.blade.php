{{-- Pricing --}}
<section id="pricing" class="py-20 lg:py-28 bg-surface">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto text-center reveal">
      <span class="inline-block text-xs font-semibold uppercase tracking-wider text-primary">{{ __('levels.pricing') }}</span>
      <h2 class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-accent">
        {{ @settings()->name }} {{ __('levels.pricing') }}
      </h2>
    </div>

    @php
      $tabs = [
        ['key' => 'same_day',     'label' => __('levels.same_day'),     'field' => 'same_day'],
        ['key' => 'next_day',     'label' => __('levels.next_day'),     'field' => 'next_day'],
        ['key' => 'sub_city',     'label' => __('levels.sub_city'),     'field' => 'sub_city'],
        ['key' => 'outside_city', 'label' => __('levels.outside_city'), 'field' => 'outside_city'],
      ];
    @endphp

    {{-- Tabs --}}
    <div class="mt-10 flex justify-center reveal">
      <div class="inline-flex flex-wrap items-center gap-1 p-1 bg-white border border-gray-200 rounded-2xl shadow-sm">
        @foreach ($tabs as $i => $tab)
          <button type="button"
                  data-pricing-tab="{{ $tab['key'] }}"
                  class="pricing-tab px-4 sm:px-5 py-2.5 rounded-xl text-sm font-semibold transition-all
                         {{ $i === 0 ? 'gradient-primary text-white shadow-md' : 'text-gray-600 hover:text-primary' }}">
            {{ $tab['label'] }}
          </button>
        @endforeach
      </div>
    </div>

    {{-- Panels --}}
    <div class="mt-10">
      @foreach ($tabs as $i => $tab)
        <div data-pricing-panel="{{ $tab['key'] }}" class="pricing-panel @if($i !== 0) hidden @endif">
          @if(count($pricing))
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 lg:gap-5">
              @foreach ($pricing as $price)
                <div class="reveal bg-white border border-gray-100 rounded-2xl p-5 text-center hover:border-primary/30 hover:shadow-lg transition-all">
                  <p class="text-xs uppercase tracking-wider text-gray-500 font-semibold">
                    {{ __('levels.up_to') }} {{ $price->weight }}
                  </p>
                  @if(!empty($price->category->title))
                    <p class="mt-1 text-xs text-gray-400">{{ $price->category->title }}</p>
                  @endif
                  <div class="mt-3 text-2xl font-extrabold gradient-text">
                    {{ @settings()->currency }} {{ $price->{$tab['field']} }}
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <p class="text-center text-gray-500">{{ __('levels.pricing') }}</p>
          @endif
        </div>
      @endforeach
    </div>
  </div>
</section>

<script>
  document.querySelectorAll('.pricing-tab').forEach(btn => {
    btn.addEventListener('click', () => {
      const key = btn.getAttribute('data-pricing-tab');
      document.querySelectorAll('.pricing-tab').forEach(b => {
        b.classList.remove('gradient-primary', 'text-white', 'shadow-md');
        b.classList.add('text-gray-600', 'hover:text-primary');
      });
      btn.classList.remove('text-gray-600', 'hover:text-primary');
      btn.classList.add('gradient-primary', 'text-white', 'shadow-md');
      document.querySelectorAll('.pricing-panel').forEach(p => p.classList.add('hidden'));
      document.querySelector(`[data-pricing-panel="${key}"]`).classList.remove('hidden');
    });
  });
</script>
