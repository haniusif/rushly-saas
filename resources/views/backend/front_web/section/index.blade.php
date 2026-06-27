@extends('backend.partials.master')
@section('title')
    {{ __('levels.sections') }} {{ __('levels.list') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 sm:tw-px-2">

            <nav class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-4">
                <span class="tw-text-gray-700">{{ __('levels.front_web') }}</span>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700 tw-font-medium">{{ __('levels.sections') }}</span>
            </nav>

            <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                <div class="tw-flex tw-items-center tw-justify-between tw-gap-3 tw-px-5 tw-py-4 tw-border-b tw-border-gray-100">
                    <div>
                        <h1 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('levels.sections') }}</h1>
                        <p class="tw-text-xs tw-text-gray-500 tw-mt-1 tw-m-0">Public landing-page sections you can edit.</p>
                    </div>
                </div>

                <div class="tw-overflow-x-auto">
                    <table class="tw-w-full tw-text-sm">
                        <thead class="tw-bg-gray-50">
                            <tr class="tw-text-left tw-text-xs tw-uppercase tw-tracking-wider tw-text-gray-500">
                                <th class="tw-px-5 tw-py-3 tw-font-medium tw-w-12">#</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.type') }}</th>
                                @if (hasPermission('section_update'))
                                    <th class="tw-px-5 tw-py-3 tw-font-medium tw-text-right">{{ __('levels.actions') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="tw-divide-y tw-divide-gray-100">
                            @php $i = method_exists($sections, 'firstItem') ? $sections->firstItem() : 1; @endphp
                            @forelse ($sections as $section)
                                <tr class="hover:tw-bg-gray-50 tw-transition-colors">
                                    <td class="tw-px-5 tw-py-3 tw-text-gray-500 tw-tabular-nums">{{ $i++ }}</td>
                                    <td class="tw-px-5 tw-py-3 tw-font-medium tw-text-gray-900">{{ @$section->my_type }}</td>
                                    @if (hasPermission('section_update'))
                                        <td class="tw-px-5 tw-py-3 tw-text-right">
                                            <a href="{{ route('section.edit', $section->type) }}"
                                               class="tw-inline-flex tw-items-center tw-gap-1.5 tw-px-3 tw-py-1.5 tw-text-xs tw-font-medium tw-text-brand-700 tw-bg-brand-50 hover:tw-bg-brand-100 tw-rounded-md tw-no-underline">
                                                <i class="fas fa-edit"></i> {{ __('levels.edit') }}
                                            </a>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="3" class="tw-px-5 tw-py-12 tw-text-center">
                                    <div class="tw-text-gray-300 tw-mb-2"><i class="fa fa-th-large tw-text-3xl"></i></div>
                                    <p class="tw-text-sm tw-text-gray-500 tw-m-0">No sections defined.</p>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (method_exists($sections, 'hasPages') && $sections->hasPages())
                    <div class="tw-flex tw-flex-col sm:tw-flex-row sm:tw-items-center sm:tw-justify-between tw-gap-3 tw-px-5 tw-py-4 tw-border-t tw-border-gray-100">
                        <p class="tw-text-xs tw-text-gray-500 tw-m-0">
                            {!! __('Showing') !!} <span class="tw-font-medium tw-text-gray-700">{{ $sections->firstItem() }}</span>
                            {!! __('to') !!} <span class="tw-font-medium tw-text-gray-700">{{ $sections->lastItem() }}</span>
                            {!! __('of') !!} <span class="tw-font-medium tw-text-gray-700">{{ $sections->total() }}</span>
                            {!! __('results') !!}
                        </p>
                        <div>{{ $sections->links() }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
