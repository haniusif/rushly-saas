@extends('frontend.layouts.master')
@section('title')
    {{ @$page->title }} | {{ settings()->name }}
@endsection
@section('content')

{{-- Hero --}}
<section class="relative overflow-hidden pt-12 pb-10 lg:pt-16 lg:pb-12 bg-surface border-b border-gray-100">
  <div aria-hidden="true" class="pointer-events-none absolute -top-32 end-[-8rem] w-[28rem] h-[28rem] rounded-full bg-gradient-to-br from-primary/10 to-secondary/10 blur-3xl"></div>

  <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
    <span class="inline-block text-xs font-semibold uppercase tracking-wider text-primary">{{ __('levels.faq') }}</span>
    <h1 class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-accent">
      {{ @$page->title }}
    </h1>
    @if(!empty($page->description))
      <div class="mt-4 text-gray-600 leading-relaxed prose prose-sm sm:prose-base max-w-2xl mx-auto">
        {!! $page->description !!}
      </div>
    @endif
  </div>
</section>

{{-- FAQ list --}}
<section class="py-12 lg:py-16">
  <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-accent mb-8">
      {{ __('levels.read_our_commonly_asked_questions') }}
    </h2>

    @if($faqs->isEmpty())
      <div class="rounded-2xl border border-dashed border-gray-200 bg-surface p-10 text-center text-gray-500">
        {{ __('levels.read_our_commonly_asked_questions') }}
      </div>
    @else
      <div class="space-y-3" data-faq-accordion>
        @foreach ($faqs as $key => $faq)
          <details name="faq-accordion" class="group rounded-2xl border border-gray-200 bg-white open:border-primary/30 open:shadow-[0_8px_24px_rgba(162,31,92,0.08)] transition-all">
            @php $n = $key + 1 + (($faqs->currentPage() - 1) * $faqs->perPage()); @endphp
            <summary class="list-none cursor-pointer flex items-start gap-4 px-5 py-4 sm:px-6 sm:py-5 select-none">
              <span class="mt-0.5 flex-shrink-0 inline-flex items-center justify-center min-w-[2.75rem] h-8 px-2 rounded-lg bg-primary/10 text-primary text-sm font-bold tracking-tight">
                Q{{ $n }}
              </span>
              <span class="flex-1 text-base sm:text-lg font-semibold text-accent leading-snug">
                {{ @$faq->question }}
              </span>
              <svg class="mt-1 flex-shrink-0 w-5 h-5 text-gray-400 group-open:text-primary group-open:rotate-180 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
              </svg>
            </summary>
            <div class="px-5 pb-5 sm:px-6 sm:pb-6">
              <div class="flex items-start gap-4 ps-0 sm:ps-[3.75rem]">
                <span class="mt-0.5 flex-shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-lg bg-secondary/10 text-secondary text-sm font-bold">
                  A
                </span>
                <div class="flex-1 prose prose-sm sm:prose-base max-w-none
                            prose-p:text-gray-600 prose-p:leading-relaxed prose-p:my-0
                            prose-a:text-primary prose-a:no-underline hover:prose-a:underline
                            prose-strong:text-accent
                            prose-li:text-gray-600 prose-li:marker:text-primary">
                  {!! $faq->answer !!}
                </div>
              </div>
            </div>
          </details>
        @endforeach
      </div>

      @if($faqs->hasPages())
        <div class="mt-10 flex justify-center">
          {{ $faqs->links() }}
        </div>
      @endif
    @endif
  </div>
</section>

{{-- Fallback for browsers that don't support <details name=""> exclusive accordion (Safari < 17.4, Firefox < 123) --}}
<script>
  (function () {
    var group = document.querySelector('[data-faq-accordion]');
    if (!group) return;
    var items = group.querySelectorAll('details[name="faq-accordion"]');
    // Skip if browser supports the native `name` attribute (it handles exclusivity itself)
    var probe = document.createElement('details');
    if ('name' in probe) return;
    items.forEach(function (d) {
      d.addEventListener('toggle', function () {
        if (!d.open) return;
        items.forEach(function (o) { if (o !== d) o.open = false; });
      });
    });
  })();
</script>

@endsection
