{{-- Partners (trust strip) --}}
<section class="py-16 lg:py-20 bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <p class="text-center text-xs font-semibold uppercase tracking-wider text-gray-500 reveal">
      {{ __('levels.our_partner') }}
    </p>

    @if(count($partners))
      <div class="mt-8 swiper partners-swiper reveal">
        <div class="swiper-wrapper items-center">
          @foreach ($partners as $partner)
            <div class="swiper-slide flex items-center justify-center">
              <a href="{{ @$partner->link }}" target="_blank" rel="noopener" class="block">
                <img src="{{ $partner->image }}" alt="" class="max-h-12 w-auto opacity-60 grayscale hover:opacity-100 hover:grayscale-0 transition-all duration-300" />
              </a>
            </div>
          @endforeach
        </div>
      </div>

      <script>
        document.addEventListener('DOMContentLoaded', () => {
          if (typeof Swiper === 'undefined') return;
          new Swiper('.partners-swiper', {
            slidesPerView: 2,
            spaceBetween: 32,
            loop: {{ count($partners) > 4 ? 'true' : 'false' }},
            autoplay: { delay: 2500, disableOnInteraction: false },
            breakpoints: {
              640:  { slidesPerView: 3 },
              768:  { slidesPerView: 4 },
              1024: { slidesPerView: 5 },
              1280: { slidesPerView: 6 },
            }
          });
        });
      </script>
    @endif
  </div>
</section>
