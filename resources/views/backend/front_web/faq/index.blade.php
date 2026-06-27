@extends('backend.partials.master')
@section('title')
    {{ __('levels.faq') }} {{ __('levels.list') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 sm:tw-px-2">

            <nav class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-4">
                <span class="tw-text-gray-700">{{ __('levels.front_web') }}</span>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700 tw-font-medium">{{ __('levels.faq') }}</span>
            </nav>

            <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                <div class="tw-flex tw-items-center tw-justify-between tw-gap-3 tw-px-5 tw-py-4 tw-border-b tw-border-gray-100">
                    <div>
                        <h1 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('levels.faq') }}</h1>
                        <p class="tw-text-xs tw-text-gray-500 tw-mt-1 tw-m-0">{{ @$faqs->total() ?? count($faqs) }} {{ __('Showing') }}</p>
                    </div>
                    @if (hasPermission('faq_create'))
                        <a href="{{ route('faq.create') }}"
                           class="tw-inline-flex tw-items-center tw-gap-2 tw-h-10 tw-px-4 tw-text-sm tw-font-medium tw-text-white tw-bg-brand-600 hover:tw-bg-brand-700 tw-rounded-lg tw-no-underline">
                            <i class="fa fa-plus tw-text-xs"></i> {{ __('levels.add') }}
                        </a>
                    @endif
                </div>

                <div class="tw-overflow-x-auto">
                    <table class="tw-w-full tw-text-sm">
                        <thead class="tw-bg-gray-50">
                            <tr class="tw-text-left tw-text-xs tw-uppercase tw-tracking-wider tw-text-gray-500">
                                <th class="tw-px-5 tw-py-3 tw-font-medium tw-w-12">#</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.question') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.answer') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium tw-text-right">{{ __('levels.position') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.status') }}</th>
                                @if (hasPermission('faq_update') || hasPermission('faq_delete'))
                                    <th class="tw-px-5 tw-py-3 tw-font-medium tw-text-right">{{ __('levels.actions') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="tw-divide-y tw-divide-gray-100">
                            @php $i = method_exists($faqs, 'firstItem') ? $faqs->firstItem() : 1; @endphp
                            @forelse ($faqs as $faq)
                                <tr class="hover:tw-bg-gray-50 tw-transition-colors">
                                    <td class="tw-px-5 tw-py-3 tw-text-gray-500 tw-tabular-nums tw-align-top">{{ $i++ }}</td>
                                    <td class="tw-px-5 tw-py-3 tw-font-medium tw-text-gray-900 tw-align-top tw-max-w-md">{{ @$faq->question }}</td>
                                    <td class="tw-px-5 tw-py-3 tw-text-gray-700 tw-align-top tw-max-w-md">
                                        <div class="tw-line-clamp-2 tw-text-xs tw-text-gray-600">{!! strip_tags(@$faq->answer) !!}</div>
                                    </td>
                                    <td class="tw-px-5 tw-py-3 tw-text-right tw-text-gray-700 tw-tabular-nums tw-align-top">{{ @$faq->position }}</td>
                                    <td class="tw-px-5 tw-py-3 tw-align-top">{!! @$faq->my_status !!}</td>
                                    @if (hasPermission('faq_update') || hasPermission('faq_delete'))
                                        <td class="tw-px-5 tw-py-3 tw-text-right tw-align-top">
                                            <div class="dropdown tw-inline-block">
                                                <button tabindex="-1" data-toggle="dropdown" type="button"
                                                        class="tw-inline-flex tw-items-center tw-justify-center tw-w-9 tw-h-9 tw-rounded-lg hover:tw-bg-gray-100 tw-text-gray-600 tw-border-0 tw-bg-transparent">
                                                    <i class="fa fa-ellipsis-h"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    @if (hasPermission('faq_update'))
                                                        <a href="{{ route('faq.edit', $faq->id) }}" class="dropdown-item">
                                                            <i class="fas fa-edit"></i> {{ __('levels.edit') }}
                                                        </a>
                                                    @endif
                                                    @if (hasPermission('faq_delete'))
                                                        <form id="delete" action="{{ route('faq.delete', $faq->id) }}" method="POST" data-title="{{ __('Do you want to delete faq ?') }}">
                                                            @method('DELETE')
                                                            @csrf
                                                            <button type="submit" class="dropdown-item tw-text-red-600">
                                                                <i class="fa fa-trash"></i> {{ __('levels.delete') }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="6" class="tw-px-5 tw-py-12 tw-text-center">
                                    <div class="tw-text-gray-300 tw-mb-2"><i class="fa fa-question tw-text-3xl"></i></div>
                                    <p class="tw-text-sm tw-text-gray-500 tw-m-0">No FAQs yet.</p>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (method_exists($faqs, 'hasPages') && $faqs->hasPages())
                    <div class="tw-flex tw-flex-col sm:tw-flex-row sm:tw-items-center sm:tw-justify-between tw-gap-3 tw-px-5 tw-py-4 tw-border-t tw-border-gray-100">
                        <p class="tw-text-xs tw-text-gray-500 tw-m-0">
                            {!! __('Showing') !!} <span class="tw-font-medium tw-text-gray-700">{{ $faqs->firstItem() }}</span>
                            {!! __('to') !!} <span class="tw-font-medium tw-text-gray-700">{{ $faqs->lastItem() }}</span>
                            {!! __('of') !!} <span class="tw-font-medium tw-text-gray-700">{{ $faqs->total() }}</span>
                            {!! __('results') !!}
                        </p>
                        <div>{{ $faqs->links() }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
