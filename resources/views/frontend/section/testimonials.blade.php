{{-- Testimonials --}}
{{-- TODO(cms): no Testimonial model yet. When ready, add `testimonials` table + CRUD,
     then replace this stub array with $testimonials from a repository. --}}
@php
  $testimonials = [
    [
      'name'    => 'Sara Al-Mansour',
      'role'    => 'Founder, Layla Cosmetics',
      'avatar'  => null,
      'quote'   => __('levels.what_customers_say') === 'What our customers say'
                    ? 'Switching to '.@settings()->name.' cut our shipping ops time in half. The merchant panel is so much cleaner than the dashboards we used before.'
                    : 'تحسّنت عملياتنا اللوجستية بشكل ملحوظ منذ بدأنا الشحن مع '.@settings()->name.'. لوحة التحكم سريعة وواضحة.',
    ],
    [
      'name'    => 'Khalid R.',
      'role'    => 'Ops Lead, Fast Fashion',
      'avatar'  => null,
      'quote'   => __('levels.what_customers_say') === 'What our customers say'
                    ? 'Tracking is instant and our customers stopped asking us "where is my order?". That alone paid for the integration.'
                    : 'تتبع فوري للشحنات وعملاؤنا توقفوا عن السؤال عن حالة الطلبات. وفّرنا الكثير من الوقت.',
    ],
    [
      'name'    => 'Mona F.',
      'role'    => 'E-commerce Manager, Boutique Nour',
      'avatar'  => null,
      'quote'   => __('levels.what_customers_say') === 'What our customers say'
                    ? 'COD reconciliation used to take a full day every week. Now it is one click — and the numbers actually match.'
                    : 'تسوية الدفع عند الاستلام كانت تستغرق يوماً كاملاً أسبوعياً. الآن أصبحت بنقرة واحدة والأرقام دقيقة.',
    ],
  ];
@endphp

<section class="py-20 lg:py-28">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto text-center reveal">
      <span class="inline-block text-xs font-semibold uppercase tracking-wider text-primary">{{ __('levels.what_customers_say') }}</span>
      <h2 class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-accent">
        {{ __('levels.what_customers_say') }}
      </h2>
    </div>

    <div class="mt-14 grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
      @foreach ($testimonials as $t)
        <figure class="reveal relative bg-white border border-gray-100 rounded-2xl p-7 lg:p-8 hover:border-primary/30 hover:shadow-xl hover:shadow-primary/5 transition-all duration-300">
          <svg class="w-9 h-9 text-primary/30 mb-4" fill="currentColor" viewBox="0 0 32 32" aria-hidden="true">
            <path d="M9.352 4C4.456 7.456 1 13.12 1 19.36 1 24.832 4.32 28 8.32 28c3.776 0 6.56-3.04 6.56-6.624 0-3.616-2.624-6.272-6.048-6.272-.704 0-1.6.128-1.824.224.32-2.336 3.52-6.4 6.752-8.512L9.352 4zm17.92 0c-4.864 3.456-8.32 9.12-8.32 15.36 0 5.472 3.328 8.64 7.328 8.64 3.776 0 6.56-3.04 6.56-6.624 0-3.616-2.624-6.272-6.048-6.272-.704 0-1.6.128-1.824.224.32-2.336 3.52-6.4 6.752-8.512L27.272 4z"/>
          </svg>
          <blockquote class="text-gray-700 leading-relaxed">{{ $t['quote'] }}</blockquote>
          <figcaption class="mt-6 pt-6 border-t border-gray-100 flex items-center gap-3">
            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full gradient-primary text-white text-sm font-bold">
              {{ strtoupper(substr($t['name'], 0, 1)) }}
            </span>
            <div>
              <p class="text-sm font-semibold text-accent">{{ $t['name'] }}</p>
              <p class="text-xs text-gray-500">{{ $t['role'] }}</p>
            </div>
          </figcaption>
        </figure>
      @endforeach
    </div>
  </div>
</section>
