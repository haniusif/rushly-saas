@extends('backend.partials.master')
@section('title')
    {{ __('levels.plans') }} {{ __('levels.list') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 sm:tw-px-2">

            <nav class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-4">
                <a href="{{ route('dashboard.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('levels.dashboard') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700 tw-font-medium">{{ __('levels.plans') }}</span>
            </nav>

            <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">

                <div class="tw-flex tw-items-center tw-justify-between tw-gap-3 tw-px-5 tw-py-4 tw-border-b tw-border-gray-100">
                    <div>
                        <h1 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('levels.plans') }}</h1>
                        <p class="tw-text-xs tw-text-gray-500 tw-mt-1 tw-m-0">{{ $plans->total() }} {{ __('Showing') }}</p>
                    </div>
                    @if (hasPermission('plans_create') == true)
                        <a href="{{ route('plan.create') }}"
                           class="tw-inline-flex tw-items-center tw-gap-2 tw-h-10 tw-px-4 tw-text-sm tw-font-medium tw-text-white tw-bg-brand-600 hover:tw-bg-brand-700 tw-rounded-lg tw-no-underline">
                            <i class="fa fa-plus tw-text-xs"></i>
                            {{ __('levels.add') }}
                        </a>
                    @endif
                </div>

                <div class="tw-overflow-x-auto">
                    <table class="tw-w-full tw-text-sm">
                        <thead class="tw-bg-gray-50">
                            <tr class="tw-text-left tw-text-xs tw-uppercase tw-tracking-wider tw-text-gray-500">
                                <th class="tw-px-5 tw-py-3 tw-font-medium tw-w-12">#</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.name') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium tw-text-right">{{ __('levels.price') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium tw-text-right">{{ __('levels.parcel_count') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium tw-text-right">{{ __('levels.max_deliveryman') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium tw-text-right">{{ __('levels.days_count') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.modules') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.status') }}</th>
                                @if (hasPermission('plans_read') == true || hasPermission('plans_update') == true || hasPermission('plans_delete') == true)
                                    <th class="tw-px-5 tw-py-3 tw-font-medium tw-text-right">{{ __('levels.actions') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="tw-divide-y tw-divide-gray-100">
                            @php $i = $plans->firstItem(); @endphp
                            @forelse ($plans as $plan)
                                <tr class="hover:tw-bg-gray-50 tw-transition-colors">
                                    <td class="tw-px-5 tw-py-3 tw-text-gray-500 tw-tabular-nums">{{ $i++ }}</td>
                                    <td class="tw-px-5 tw-py-3">
                                        <div class="tw-font-medium tw-text-gray-900">{{ $plan->name }}</div>
                                        @if ($plan->description)
                                            <div class="tw-text-xs tw-text-gray-500 tw-mt-0.5 tw-line-clamp-1">{{ $plan->description }}</div>
                                        @endif
                                    </td>
                                    <td class="tw-px-5 tw-py-3 tw-text-right tw-tabular-nums tw-font-semibold tw-text-gray-900">{{ $plan->price }}</td>
                                    <td class="tw-px-5 tw-py-3 tw-text-right tw-tabular-nums tw-text-gray-700">{{ $plan->parcel_count }}</td>
                                    <td class="tw-px-5 tw-py-3 tw-text-right tw-tabular-nums tw-text-gray-700">{{ $plan->deliveryman_count }}</td>
                                    <td class="tw-px-5 tw-py-3 tw-text-right tw-tabular-nums tw-text-gray-700">{{ $plan->days_count }}</td>
                                    <td class="tw-px-5 tw-py-3">
                                        <button class="modalBtn tw-inline-flex tw-items-center tw-gap-1.5 tw-px-2.5 tw-py-1 tw-text-xs tw-font-medium tw-text-brand-700 tw-bg-brand-50 hover:tw-bg-brand-100 tw-rounded-full tw-border-0"
                                                data-toggle="modal" data-target="#dynamic-modal"
                                                data-url="{{ route('plan.modules.view', $plan->id) }}"
                                                data-title="{{ $plan->name }}">
                                            <i class="fa fa-eye tw-text-[10px]"></i>
                                            {{ count($plan->modules ?? []) }}
                                        </button>
                                    </td>
                                    <td class="tw-px-5 tw-py-3">{!! $plan->my_status !!}</td>
                                    @if (hasPermission('plans_read') == true || hasPermission('plans_update') == true || hasPermission('plans_delete') == true)
                                        <td class="tw-px-5 tw-py-3 tw-text-right">
                                            <div class="dropdown tw-inline-block">
                                                <button tabindex="-1" data-toggle="dropdown" type="button"
                                                        class="tw-inline-flex tw-items-center tw-justify-center tw-w-9 tw-h-9 tw-rounded-lg hover:tw-bg-gray-100 tw-text-gray-600 tw-border-0 tw-bg-transparent"
                                                        aria-label="actions">
                                                    <i class="fa fa-ellipsis-h"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    @if (hasPermission('plans_update') == true)
                                                        <a href="{{ route('plan.edit', $plan->id) }}" class="dropdown-item">
                                                            <i class="fas fa-edit"></i> {{ __('levels.edit') }}
                                                        </a>
                                                    @endif
                                                    @if (hasPermission('plans_delete') == true)
                                                        <form id="delete" action="{{ route('plan.delete', $plan->id) }}" method="POST" data-title="{{ __('delete.plan') }}">
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
                                <tr>
                                    <td colspan="9" class="tw-px-5 tw-py-12 tw-text-center">
                                        <div class="tw-text-gray-300 tw-mb-2"><i class="fa fa-grip-vertical tw-text-3xl"></i></div>
                                        <p class="tw-text-sm tw-text-gray-500 tw-m-0">No plans yet.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($plans->hasPages())
                    <div class="tw-flex tw-flex-col sm:tw-flex-row sm:tw-items-center sm:tw-justify-between tw-gap-3 tw-px-5 tw-py-4 tw-border-t tw-border-gray-100">
                        <p class="tw-text-xs tw-text-gray-500 tw-m-0">
                            {!! __('Showing') !!}
                            <span class="tw-font-medium tw-text-gray-700">{{ $plans->firstItem() }}</span>
                            {!! __('to') !!}
                            <span class="tw-font-medium tw-text-gray-700">{{ $plans->lastItem() }}</span>
                            {!! __('of') !!}
                            <span class="tw-font-medium tw-text-gray-700">{{ $plans->total() }}</span>
                            {!! __('results') !!}
                        </p>
                        <div>{{ $plans->links() }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection()

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="{{ static_asset('backend/js/filter/index.js') }}"></script>
    <script type="text/javascript">
        $("#month").datepicker({
            format: "yyyy-mm",
            startView: "months",
            minViewMode: "months"
        });
    </script>
@endpush
