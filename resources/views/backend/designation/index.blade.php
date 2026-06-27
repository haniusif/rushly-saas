@extends('backend.partials.master')
@section('title')
    {{ __('designation.title') }} {{ __('levels.list') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 sm:tw-px-2">

            {{-- Breadcrumb --}}
            <nav class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-4">
                <a href="{{ route('dashboard.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('levels.dashboard') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700">{{ __('menus.user_role') }}</span>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700 tw-font-medium">{{ __('designation.title') }}</span>
            </nav>

            <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">

                {{-- Header --}}
                <div class="tw-flex tw-items-center tw-justify-between tw-gap-3 tw-px-5 tw-py-4 tw-border-b tw-border-gray-100">
                    <div>
                        <h1 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('designation.title') }}</h1>
                        <p class="tw-text-xs tw-text-gray-500 tw-mt-1 tw-m-0">{{ $designations->total() }} {{ __('Showing') }}</p>
                    </div>
                    @if (hasPermission('designation_create') == true)
                        <a href="{{ url('admin/designations/create') }}"
                           class="tw-inline-flex tw-items-center tw-gap-2 tw-h-10 tw-px-4 tw-text-sm tw-font-medium tw-text-white tw-bg-brand-600 hover:tw-bg-brand-700 tw-rounded-lg tw-no-underline">
                            <i class="fa fa-plus tw-text-xs"></i>
                            {{ __('levels.add') }}
                        </a>
                    @endif
                </div>

                {{-- Table --}}
                <div class="tw-overflow-x-auto">
                    <table class="tw-w-full tw-text-sm">
                        <thead class="tw-bg-gray-50">
                            <tr class="tw-text-left tw-text-xs tw-uppercase tw-tracking-wider tw-text-gray-500">
                                <th class="tw-px-5 tw-py-3 tw-font-medium tw-w-12">#</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.title') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.status') }}</th>
                                @if (hasPermission('designation_update') == true || hasPermission('designation_delete') == true)
                                    <th class="tw-px-5 tw-py-3 tw-font-medium tw-text-right">{{ __('levels.actions') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="tw-divide-y tw-divide-gray-100">
                            @php $i = $designations->firstItem(); @endphp
                            @forelse ($designations as $designation)
                                <tr class="hover:tw-bg-gray-50 tw-transition-colors">
                                    <td class="tw-px-5 tw-py-3 tw-text-gray-500 tw-tabular-nums">{{ $i++ }}</td>
                                    <td class="tw-px-5 tw-py-3 tw-font-medium tw-text-gray-900">{{ $designation->title }}</td>
                                    <td class="tw-px-5 tw-py-3">{!! $designation->my_status !!}</td>
                                    @if (hasPermission('designation_update') == true || hasPermission('designation_delete') == true)
                                        <td class="tw-px-5 tw-py-3 tw-text-right">
                                            <div class="dropdown tw-inline-block">
                                                <button tabindex="-1" data-toggle="dropdown" type="button"
                                                        class="tw-inline-flex tw-items-center tw-justify-center tw-w-9 tw-h-9 tw-rounded-lg hover:tw-bg-gray-100 tw-text-gray-600 tw-border-0 tw-bg-transparent"
                                                        aria-label="actions">
                                                    <i class="fa fa-ellipsis-h"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    @if (hasPermission('designation_update') == true)
                                                        <a href="{{ route('designations.edit', $designation->id) }}" class="dropdown-item">
                                                            <i class="fas fa-edit"></i> {{ __('levels.edit') }}
                                                        </a>
                                                    @endif
                                                    @if (hasPermission('designation_delete') == true)
                                                        <form id="delete" action="{{ route('designation.delete', $designation->id) }}" method="POST" data-title="{{ __('delete.designation') }}">
                                                            @method('DELETE')
                                                            @csrf
                                                            <input type="hidden" value="{{ __('designation.title') }}" id="deleteTitle">
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
                                <tr>
                                    <td colspan="4" class="tw-px-5 tw-py-12 tw-text-center">
                                        <div class="tw-text-gray-300 tw-mb-2"><i class="fa fa-id-badge tw-text-3xl"></i></div>
                                        <p class="tw-text-sm tw-text-gray-500 tw-m-0">No designations yet.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($designations->hasPages())
                    <div class="tw-flex tw-flex-col sm:tw-flex-row sm:tw-items-center sm:tw-justify-between tw-gap-3 tw-px-5 tw-py-4 tw-border-t tw-border-gray-100">
                        <p class="tw-text-xs tw-text-gray-500 tw-m-0">
                            {!! __('Showing') !!}
                            <span class="tw-font-medium tw-text-gray-700">{{ $designations->firstItem() }}</span>
                            {!! __('to') !!}
                            <span class="tw-font-medium tw-text-gray-700">{{ $designations->lastItem() }}</span>
                            {!! __('of') !!}
                            <span class="tw-font-medium tw-text-gray-700">{{ $designations->total() }}</span>
                            {!! __('results') !!}
                        </p>
                        <div>{{ $designations->links() }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection()
