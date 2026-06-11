{{-- Footer --}}
<footer class="bg-accent text-gray-300 relative overflow-hidden">
  <div aria-hidden="true" class="pointer-events-none absolute -top-32 -end-32 w-[28rem] h-[28rem] rounded-full bg-primary/15 blur-3xl"></div>

  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-20">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10">

      {{-- Brand + about + app links --}}
      <div class="lg:col-span-4">
        <a href="{{ url('/') }}" class="inline-block">
          <img src="{{ @settings()->light_logo_image }}" alt="{{ @settings()->name }}" class="h-10 w-auto" />
        </a>
        <p class="mt-5 text-sm text-gray-400 leading-relaxed max-w-sm">
          {!! \Str::limit(strip_tags(section(\App\Enums\SectionType::ABOUT, 'about_us') ?? ''), 200) !!}
        </p>

        <div class="mt-8">
          <h4 class="text-sm font-semibold text-white uppercase tracking-wider">{{ __('levels.download_app') }}</h4>
          <div class="mt-4 flex items-center gap-3">
            @if(section(\App\Enums\SectionType::APP_LINK,'playstore_link'))
              <a href="{{ section(\App\Enums\SectionType::APP_LINK,'playstore_link') }}"
                 class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 hover:border-primary/30 transition-all"
                 title="{{ __('levels.play_store') }}">
                <i class="{{ section(\App\Enums\SectionType::APP_LINK,'playstore_icon') }} text-2xl text-white"></i>
              </a>
            @endif
            @if(section(\App\Enums\SectionType::APP_LINK,'ios_link'))
              <a href="{{ section(\App\Enums\SectionType::APP_LINK,'ios_link') }}"
                 class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 hover:border-primary/30 transition-all"
                 title="{{ __('levels.ios_store') }}">
                <i class="{{ section(\App\Enums\SectionType::APP_LINK,'ios_icon') }} text-2xl text-white"></i>
              </a>
            @endif
          </div>
        </div>
      </div>

      {{-- Services --}}
      @if (tenant())
        <div class="lg:col-span-2">
          <h4 class="text-sm font-semibold text-white uppercase tracking-wider">{{ __('levels.available_services') }}</h4>
          <ul class="mt-4 space-y-2.5">
            @foreach ($take_services ?? [] as $footer_service)
              <li><a href="#" class="text-sm text-gray-400 hover:text-white transition-colors">{{ $footer_service->title }}</a></li>
            @endforeach
          </ul>
        </div>
      @endif

      {{-- About / Help --}}
      <div class="{{ tenant() ? 'lg:col-span-2' : 'lg:col-span-4' }}">
        <h4 class="text-sm font-semibold text-white uppercase tracking-wider">{{ __('levels.about') }}</h4>
        <ul class="mt-4 space-y-2.5">
          <li><a href="{{ route('aboutus.index') }}"          class="text-sm text-gray-400 hover:text-white transition-colors">{{ __('levels.about_us') }}</a></li>
          <li><a href="{{ route('get.faq.index') }}"          class="text-sm text-gray-400 hover:text-white transition-colors">{{ __('levels.faq') }}</a></li>
          <li><a href="{{ route('contact.send.page') }}"      class="text-sm text-gray-400 hover:text-white transition-colors">{{ __('levels.contact_us') }}</a></li>
          <li><a href="{{ route('privacy.policy.index') }}"   class="text-sm text-gray-400 hover:text-white transition-colors">{{ __('levels.privacy_policy') }}</a></li>
          <li><a href="{{ route('termsof.condition.index') }}" class="text-sm text-gray-400 hover:text-white transition-colors">{{ __('levels.terms_of_use') }}</a></li>
        </ul>
      </div>

      {{-- Newsletter + Social --}}
      <div class="lg:col-span-4">
        <h4 class="text-sm font-semibold text-white uppercase tracking-wider">
          {{ section(\App\Enums\SectionType::SUBSCRIBE,'subscribe_title') ?: __('levels.subscribe') }}
        </h4>
        <p class="mt-3 text-sm text-gray-400 leading-relaxed">
          {{ section(\App\Enums\SectionType::SUBSCRIBE,'subscribe_description') }}
        </p>
        <form action="{{ route('subscribe.store') }}" method="post" class="mt-5">
          @csrf
          <div class="flex items-stretch bg-white/5 border border-white/10 rounded-xl focus-within:border-primary transition-colors overflow-hidden">
            <input type="email" name="email" required value="{{ old('email') }}"
                   placeholder="{{ __('placeholder.enter_email') }}"
                   class="flex-1 min-w-0 px-4 py-3 bg-transparent text-white placeholder:text-gray-500 focus:outline-none text-sm" />
            <button type="submit" class="btn-primary text-white px-5 font-semibold text-sm">
              <i class="fa fa-paper-plane"></i>
            </button>
          </div>
          @error('email')
            <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
          @enderror
        </form>

        <h4 class="mt-8 text-sm font-semibold text-white uppercase tracking-wider">{{ __('levels.social') }}</h4>
        <div class="mt-4 flex flex-wrap items-center gap-2">
          @foreach ($social_links ?? [] as $social)
            <a href="{{ @$social->link }}" title="{{ $social->name }}"
               class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-white/5 border border-white/10 text-gray-300 hover:text-white hover:bg-primary/20 hover:border-primary/40 transition-all">
              <i class="{{ $social->icon }}"></i>
            </a>
          @endforeach
        </div>
      </div>
    </div>

    {{-- Bottom bar --}}
    <div class="mt-14 pt-8 border-t border-white/10 flex flex-col lg:flex-row items-center justify-between gap-6">
      <p class="text-sm text-gray-400">{{ @settings()->copyright }}</p>

      {{-- Locale switcher --}}
      <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2">
        @php $current = app()->getLocale(); @endphp
        @foreach (['en'=>__('levels.english'),'ar'=>__('levels.arabic'),'bn'=>__('levels.bangla'),'in'=>__('levels.hindi'),'fr'=>__('levels.franch'),'es'=>__('levels.spanish'),'zh'=>__('levels.chinese')] as $code => $label)
          <a href="{{ route('setlocalization', $code) }}"
             class="text-xs font-medium uppercase tracking-wider {{ $current === $code ? 'text-white' : 'text-gray-500 hover:text-white' }} transition-colors">
            {{ $label }}
          </a>
        @endforeach
      </div>
    </div>
  </div>
</footer>
