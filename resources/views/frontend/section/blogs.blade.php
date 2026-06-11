{{-- Latest blog --}}
<section class="py-20 lg:py-28 bg-surface">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-6 reveal">
      <div class="max-w-xl">
        <span class="inline-block text-xs font-semibold uppercase tracking-wider text-primary">{{ __('levels.blogs') }}</span>
        <h2 class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-accent">
          {{ __('levels.latest_from_blog') }}
        </h2>
      </div>
      <a href="{{ route('get.blogs') }}" class="inline-flex items-center gap-1.5 font-semibold text-accent hover:text-primary transition-colors">
        {{ __('levels.view_all_blogs') }}
        <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </a>
    </div>

    @if(count($blogs))
      <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
        @foreach ($blogs as $blog)
          <article class="reveal group bg-white border border-gray-100 rounded-2xl overflow-hidden hover:border-primary/30 hover:shadow-xl hover:shadow-primary/5 hover:-translate-y-1 transition-all duration-300">
            <a href="{{ route('blog.details', $blog->id) }}" class="block aspect-[16/10] overflow-hidden bg-gray-100">
              <img src="{{ $blog->image }}" alt="{{ $blog->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
            </a>
            <div class="p-6">
              <a href="{{ route('blog.details', $blog->id) }}" class="block">
                <h3 class="text-lg font-semibold text-accent group-hover:text-primary transition-colors line-clamp-2">{{ $blog->title }}</h3>
              </a>
              <div class="mt-5 pt-5 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                <span class="inline-flex items-center gap-1.5">
                  <i class="fa fa-user text-primary"></i> {{ $blog->user->name ?? '' }}
                </span>
                <span class="inline-flex items-center gap-3">
                  <span class="inline-flex items-center gap-1"><i class="fa fa-eye text-primary"></i>{{ $blog->views }}</span>
                  <span class="inline-flex items-center gap-1"><i class="fa fa-calendar text-primary"></i>{{ $blog->updated_at->format('d M Y') }}</span>
                </span>
              </div>
            </div>
          </article>
        @endforeach
      </div>
    @endif
  </div>
</section>
