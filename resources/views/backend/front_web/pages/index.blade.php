@extends('backend.partials.master')
@section('title')
    {{ __('levels.pages') }} {{ __('levels.list') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 sm:tw-px-2">

            <nav class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-4">
                <span class="tw-text-gray-700">{{ __('levels.front_web') }}</span>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700 tw-font-medium">{{ __('levels.pages') }}</span>
            </nav>

            <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                <div class="tw-flex tw-items-center tw-justify-between tw-gap-3 tw-px-5 tw-py-4 tw-border-b tw-border-gray-100">
                    <div>
                        <h1 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('levels.pages') }}</h1>
                        <p class="tw-text-xs tw-text-gray-500 tw-mt-1 tw-m-0">{{ @$pages->total() ?? count($pages) }} {{ __('Showing') }}</p>
                    </div>
                </div>

                <div class="tw-overflow-x-auto">
                    <table class="tw-w-full tw-text-sm">
                        <thead class="tw-bg-gray-50">
                            <tr class="tw-text-left tw-text-xs tw-uppercase tw-tracking-wider tw-text-gray-500">
                                <th class="tw-px-5 tw-py-3 tw-font-medium tw-w-12">#</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.slug') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.title') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.status') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.updated') }}</th>
                                @if (hasPermission('pages_update'))
                                    <th class="tw-px-5 tw-py-3 tw-font-medium tw-text-right">{{ __('levels.actions') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="tw-divide-y tw-divide-gray-100">
                            @php $i = method_exists($pages, 'firstItem') ? $pages->firstItem() : 1; @endphp
                            @forelse ($pages as $page)
                                <tr class="hover:tw-bg-gray-50 tw-transition-colors">
                                    <td class="tw-px-5 tw-py-3 tw-text-gray-500 tw-tabular-nums">{{ $i++ }}</td>
                                    <td class="tw-px-5 tw-py-3">
                                        <code class="tw-text-xs tw-px-2 tw-py-0.5 tw-bg-gray-100 tw-text-gray-700 tw-rounded">{{ @$page->page }}</code>
                                    </td>
                                    <td class="tw-px-5 tw-py-3 tw-font-medium tw-text-gray-900">{{ @$page->title }}</td>
                                    <td class="tw-px-5 tw-py-3">{!! @$page->my_status !!}</td>
                                    <td class="tw-px-5 tw-py-3 tw-text-xs tw-text-gray-500 tw-tabular-nums">{{ dateFormat($page->updated_at) }}</td>
                                    @if (hasPermission('pages_update'))
                                        <td class="tw-px-5 tw-py-3 tw-text-right">
                                            <a href="{{ route('pages.edit', $page->id) }}"
                                               class="tw-inline-flex tw-items-center tw-gap-1.5 tw-px-3 tw-py-1.5 tw-text-xs tw-font-medium tw-text-brand-700 tw-bg-brand-50 hover:tw-bg-brand-100 tw-rounded-md tw-no-underline">
                                                <i class="fas fa-edit"></i> {{ __('levels.edit') }}
                                            </a>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="6" class="tw-px-5 tw-py-12 tw-text-center">
                                    <div class="tw-text-gray-300 tw-mb-2"><i class="fa fa-file-text-o tw-text-3xl"></i></div>
                                    <p class="tw-text-sm tw-text-gray-500 tw-m-0">No pages yet.</p>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (method_exists($pages, 'hasPages') && $pages->hasPages())
                    <div class="tw-flex tw-flex-col sm:tw-flex-row sm:tw-items-center sm:tw-justify-between tw-gap-3 tw-px-5 tw-py-4 tw-border-t tw-border-gray-100">
                        <p class="tw-text-xs tw-text-gray-500 tw-m-0">
                            {!! __('Showing') !!} <span class="tw-font-medium tw-text-gray-700">{{ $pages->firstItem() }}</span>
                            {!! __('to') !!} <span class="tw-font-medium tw-text-gray-700">{{ $pages->lastItem() }}</span>
                            {!! __('of') !!} <span class="tw-font-medium tw-text-gray-700">{{ $pages->total() }}</span>
                            {!! __('results') !!}
                        </p>
                        <div>{{ $pages->links() }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
