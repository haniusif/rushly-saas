@extends('backend.partials.master')
@section('title')
    {{ __('parcel.title') }} {{ __('levels.list') }}
@endsection
@section('maincontent')
    @php
        $currency = settings()->currency;
        $fmt      = fn ($n) => number_format((float) ($n ?? 0), 2);
        // Shared query slice used by both export links + table state restoration.
        $filterParams = [
            'parcel_date'           => $request->parcel_date,
            'parcel_status'         => $request->parcel_status,
            'parcel_customer'       => $request->parcel_customer,
            'parcel_customer_phone' => $request->parcel_customer_phone,
            'invoice_id'            => $request->invoice_id,
        ];
    @endphp
    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 sm:tw-px-2">

            {{-- Breadcrumb --}}
            <nav class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-4">
                <a href="{{ route('dashboard.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('levels.dashboard') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <a href="{{ route('merchant-panel.parcel.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('parcel.title') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700 tw-font-medium">{{ __('levels.list') }}</span>
            </nav>

            {{-- Filter card --}}
            <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-p-5 tw-mb-4">
                <form action="{{ route('merchant-panel.parcel.filter') }}" method="GET">
                    <div class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 lg:tw-grid-cols-3 xl:tw-grid-cols-6 tw-gap-3">
                        <div>
                            <label for="date" class="tw-block tw-text-xs tw-uppercase tw-tracking-wider tw-font-medium tw-text-gray-500 tw-mb-1.5">{{ __('parcel.date') }}</label>
                            <input type="text" autocomplete="off" id="date" name="parcel_date"
                                   class="date_range_picker tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-border-gray-200 tw-rounded-lg"
                                   value="{{ old('parcel_date', $request->parcel_date) }}" placeholder="{{ __('merchantPlaceholder.date') }}">
                            @error('parcel_date')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>
                        <div>
                            <label for="parcelStatus" class="tw-block tw-text-xs tw-uppercase tw-tracking-wider tw-font-medium tw-text-gray-500 tw-mb-1.5">{{ __('parcel.status') }}</label>
                            <select id="parcelStatus" name="parcel_status"
                                    class="tw-select tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-border-gray-200 tw-rounded-lg @error('parcel_status') tw-border-red-300 @enderror">
                                <option value="">{{ __('menus.select') }} {{ __('parcel.status') }}</option>
                                @foreach (trans('merchantParcelStatusFilter') as $key => $status)
                                    <option value="{{ $key }}" {{ old('parcel_status', $request->parcel_status) == $key ? 'selected' : '' }}>{{ $status }}</option>
                                @endforeach
                            </select>
                            @error('parcel_status')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>
                        <div>
                            <label for="parcel_customer" class="tw-block tw-text-xs tw-uppercase tw-tracking-wider tw-font-medium tw-text-gray-500 tw-mb-1.5">{{ __('parcel.customer_name') }}</label>
                            <input id="parcel_customer" type="text" name="parcel_customer" placeholder="{{ __('parcel.customer_name') }}" autocomplete="off"
                                   class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-border-gray-200 tw-rounded-lg"
                                   value="{{ old('parcel_customer', $request->parcel_customer) }}">
                        </div>
                        <div>
                            <label for="parcel_customer_phone" class="tw-block tw-text-xs tw-uppercase tw-tracking-wider tw-font-medium tw-text-gray-500 tw-mb-1.5">{{ __('parcel.customer_phone') }}</label>
                            <input id="parcel_customer_phone" type="text" name="parcel_customer_phone" placeholder="{{ __('parcel.customer_phone') }}" autocomplete="off"
                                   class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-border-gray-200 tw-rounded-lg"
                                   value="{{ old('parcel_customer_phone', $request->parcel_customer_phone) }}">
                        </div>
                        <div>
                            <label for="invoice_id" class="tw-block tw-text-xs tw-uppercase tw-tracking-wider tw-font-medium tw-text-gray-500 tw-mb-1.5">{{ __('parcel.invoice_id') }}</label>
                            <input id="invoice_id" type="text" name="invoice_id" placeholder="{{ __('parcel.invoice_id') }}" autocomplete="off"
                                   class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-border-gray-200 tw-rounded-lg"
                                   value="{{ old('invoice_id', $request->invoice_id) }}">
                        </div>
                        <div class="tw-flex tw-items-end tw-gap-2">
                            <button type="submit" class="tw-inline-flex tw-items-center tw-gap-1.5 tw-h-10 tw-px-4 tw-text-sm tw-font-medium tw-text-white tw-bg-brand-600 hover:tw-bg-brand-700 tw-rounded-lg">
                                <i class="fa fa-filter tw-text-xs"></i> {{ __('levels.filter') }}
                            </button>
                            <a href="{{ route('merchant-panel.parcel.index') }}"
                               class="tw-inline-flex tw-items-center tw-gap-1.5 tw-h-10 tw-px-4 tw-text-sm tw-font-medium tw-text-gray-700 tw-bg-white hover:tw-bg-gray-100 tw-border tw-border-gray-200 tw-rounded-lg tw-no-underline">
                                <i class="fa fa-eraser tw-text-xs"></i> {{ __('levels.clear') }}
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table card --}}
            <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                <div class="tw-flex tw-flex-col lg:tw-flex-row lg:tw-items-center lg:tw-justify-between tw-gap-3 tw-px-5 tw-py-4 tw-border-b tw-border-gray-100 merchantParcelPage">
                    <div>
                        <h1 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('parcel.title') }}</h1>
                        <p class="tw-text-xs tw-text-gray-500 tw-mt-1 tw-m-0">{{ $parcels->total() }} {{ __('Showing') }}</p>
                    </div>
                    <div class="tw-flex tw-items-center tw-gap-2 tw-flex-wrap">
                        {{-- Hidden multi-parcel label print form (kept as before, just restyled trigger) --}}
                        <form action="{{ route('parcel.multiple.print-label') }}" method="get" target="_blank" id="print_label_form" class="tw-inline-flex">
                            @csrf
                            <div id="print_label_content"></div>
                            <button type="submit" class="multiplelabelprint tw-inline-flex tw-items-center tw-gap-1.5 tw-h-9 tw-px-3 tw-text-xs tw-font-medium tw-text-white tw-bg-brand-600 hover:tw-bg-brand-700 tw-rounded-md" data-parcels='' style="display: none">
                                <i class="fa fa-print"></i> {{ __('levels.print_label') }}
                            </button>
                        </form>

                        <a href="{{ route('merchant-panel.parcel.file-export', $filterParams) }}"
                           class="tw-inline-flex tw-items-center tw-gap-1.5 tw-h-9 tw-px-3 tw-text-xs tw-font-medium tw-text-emerald-700 tw-bg-emerald-50 hover:tw-bg-emerald-100 tw-rounded-md tw-no-underline">
                            <i class="fa fa-download"></i> {{ __('parcel.export_xlsx') }}
                        </a>
                        <a href="{{ route('merchant-panel.parcel.file-export', array_merge($filterParams, ['type' => 'csv'])) }}"
                           class="tw-inline-flex tw-items-center tw-gap-1.5 tw-h-9 tw-px-3 tw-text-xs tw-font-medium tw-text-emerald-700 tw-bg-emerald-50 hover:tw-bg-emerald-100 tw-rounded-md tw-no-underline">
                            <i class="fa fa-download"></i> {{ __('parcel.export_csv') }}
                        </a>
                        <a href="{{ route('merchant-panel.parcel.parcel-import') }}"
                           class="tw-inline-flex tw-items-center tw-gap-1.5 tw-h-9 tw-px-3 tw-text-xs tw-font-medium tw-text-sky-700 tw-bg-sky-50 hover:tw-bg-sky-100 tw-rounded-md tw-no-underline">
                            <i class="fa fa-upload"></i> {{ __('parcel.import_parcel') }}
                        </a>
                        <a href="{{ route('merchant-panel.parcel.create') }}"
                           class="tw-inline-flex tw-items-center tw-gap-1.5 tw-h-10 tw-px-4 tw-text-sm tw-font-medium tw-text-white tw-bg-brand-600 hover:tw-bg-brand-700 tw-rounded-lg tw-no-underline">
                            <i class="fa fa-plus tw-text-xs"></i> {{ __('levels.add') }}
                        </a>
                    </div>
                </div>

                <div class="tw-overflow-x-auto">
                    <table id="table" class="tw-w-full tw-text-sm">
                        <thead class="tw-bg-gray-50">
                            <tr class="tw-text-left tw-text-xs tw-uppercase tw-tracking-wider tw-text-gray-500">
                                <th class="tw-px-4 tw-py-3 tw-font-medium tw-w-10 parcel-index permission-check-box">
                                    <input type="checkbox" id="tick-all" class="form-check-input tw-w-4 tw-h-4 tw-accent-brand-600" />
                                </th>
                                <th class="tw-px-4 tw-py-3 tw-font-medium tw-w-12">#</th>
                                <th class="tw-px-4 tw-py-3 tw-font-medium">{{ __('parcel.tracking_id') }}</th>
                                <th class="tw-px-4 tw-py-3 tw-font-medium">{{ __('parcel.recipient_info') }}</th>
                                <th class="tw-px-4 tw-py-3 tw-font-medium tw-text-right">{{ __('parcel.amount') }}</th>
                                <th class="tw-px-4 tw-py-3 tw-font-medium">{{ __('parcel.status') }}</th>
                                <th class="tw-px-4 tw-py-3 tw-font-medium">{{ __('parcel.payment') }}</th>
                            </tr>
                        </thead>
                        <tbody class="tw-divide-y tw-divide-gray-100">
                            @php $i = method_exists($parcels, 'firstItem') ? $parcels->firstItem() : 1; @endphp
                            @forelse ($parcels as $parcel)
                                <tr class="hover:tw-bg-gray-50 tw-transition-colors tw-align-top">
                                    <td class="tw-px-4 tw-py-3 parcel-index permission-check-box">
                                        <input type="checkbox" name="parcels[][{{ $parcel->id }}]" value="{{ $parcel->id }}" class="common-key form-check-input tw-w-4 tw-h-4 tw-accent-brand-600" />
                                    </td>
                                    <td class="tw-px-4 tw-py-3">
                                        <div class="dropdown tw-inline-block">
                                            <button tabindex="-1" data-toggle="dropdown" type="button"
                                                    class="tw-inline-flex tw-items-center tw-justify-center tw-w-8 tw-h-8 tw-rounded-md hover:tw-bg-gray-100 tw-text-gray-600 tw-border-0 tw-bg-transparent">
                                                <i class="fa fa-ellipsis-h"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a href="{{ route('merchant-panel.parcel.details', $parcel->id) }}" class="dropdown-item">
                                                    <i class="fa fa-eye"></i> {{ __('levels.view') }}
                                                </a>
                                                <a href="{{ route('merchant-panel.parcel.logs', $parcel->id) }}" class="dropdown-item">
                                                    <i class="fas fa-history"></i> {{ __('levels.parcel_logs') }}
                                                </a>
                                                <a href="{{ route('merchant-parcel.clone', $parcel->id) }}" class="dropdown-item">
                                                    <i class="fas fa-clone"></i> {{ __('levels.clone') }}
                                                </a>
                                                @if ($parcel->status !== \App\Enums\ParcelStatus::DELIVERED && $parcel->status == \App\Enums\ParcelStatus::PENDING)
                                                    <a href="{{ route('merchant-panel.parcel.edit', $parcel->id) }}" class="dropdown-item">
                                                        <i class="fas fa-edit"></i> {{ __('levels.edit') }}
                                                    </a>
                                                    <form id="delete" action="{{ route('merchant-panel.parcel.delete', $parcel->id) }}" method="POST" data-title="{{ __('delete.parcel') }}">
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
                                    <td class="tw-px-4 tw-py-3">
                                        <code class="tw-text-xs tw-px-2 tw-py-0.5 tw-bg-gray-100 tw-text-gray-700 tw-rounded">{{ $parcel->tracking_id }}</code>
                                    </td>
                                    <td class="tw-px-4 tw-py-3 merchantpayment">
                                        <div class="tw-space-y-1 tw-min-w-0 tw-max-w-xs">
                                            <div class="tw-flex tw-items-start tw-gap-2 tw-text-sm">
                                                <i class="fa fa-user tw-text-gray-400 tw-mt-1 tw-text-xs"></i>
                                                <span class="tw-font-medium tw-text-gray-900 tw-truncate">{{ $parcel->customer_name }}</span>
                                            </div>
                                            <div class="tw-flex tw-items-start tw-gap-2 tw-text-xs tw-text-gray-600">
                                                <i class="fas fa-phone tw-text-gray-400 tw-mt-0.5"></i>
                                                <span class="tw-tabular-nums">{{ $parcel->customer_phone }}</span>
                                            </div>
                                            <div class="tw-flex tw-items-start tw-gap-2 tw-text-xs tw-text-gray-600">
                                                <i class="fas fa-map tw-text-gray-400 tw-mt-0.5"></i>
                                                <span>{{ $parcel->city?->en_name }}{{ $parcel->city && $parcel->area ? ' · ' : '' }}{{ $parcel->area?->en_name }}</span>
                                            </div>
                                            <div class="tw-flex tw-items-start tw-gap-2 tw-text-xs tw-text-gray-600">
                                                <i class="fas fa-map-marker-alt tw-text-gray-400 tw-mt-0.5"></i>
                                                <span class="tw-line-clamp-2">{{ $parcel->customer_address }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="tw-px-4 tw-py-3 tw-text-right tw-tabular-nums">
                                        <div class="tw-space-y-0.5 tw-text-xs">
                                            <div class="tw-flex tw-justify-between tw-gap-3">
                                                <span class="tw-text-gray-500">{{ __('levels.cod') }}:</span>
                                                <span class="tw-text-gray-900">{{ $currency }} {{ $fmt($parcel->cash_collection) }}</span>
                                            </div>
                                            <div class="tw-flex tw-justify-between tw-gap-3">
                                                <span class="tw-text-gray-500">{{ __('levels.total_delivery_amount') }}:</span>
                                                <span class="tw-text-gray-900">{{ $currency }} {{ $fmt($parcel->total_delivery_amount) }}</span>
                                            </div>
                                            <div class="tw-flex tw-justify-between tw-gap-3">
                                                <span class="tw-text-gray-500">{{ __('levels.vat_amount') }}:</span>
                                                <span class="tw-text-gray-900">{{ $currency }} {{ $fmt($parcel->vat_amount) }}</span>
                                            </div>
                                            <div class="tw-flex tw-justify-between tw-gap-3 tw-pt-1 tw-border-t tw-border-gray-100">
                                                <span class="tw-text-gray-500">{{ __('levels.current_payable') }}:</span>
                                                <span class="tw-font-semibold tw-text-gray-900">{{ $currency }} {{ $fmt($parcel->current_payable) }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="tw-px-4 tw-py-3">
                                        <div>{!! $parcel->parcel_status !!}</div>
                                        <div class="tw-text-[11px] tw-text-gray-500 tw-mt-1 tw-tabular-nums">
                                            {{ __('parcel.updated_on') }}: {{ \Carbon\Carbon::parse($parcel->updated_at)->format('Y-m-d h:i A') }}
                                        </div>
                                    </td>
                                    <td class="tw-px-4 tw-py-3">
                                        @if ($parcel->invoice)
                                            @php
                                                $paid = $parcel->invoice->status == \App\Enums\InvoiceStatus::PAID;
                                            @endphp
                                            <span class="tw-inline-flex tw-items-center tw-px-2 tw-py-0.5 tw-text-xs tw-font-medium tw-rounded-full {{ $paid ? 'tw-bg-emerald-50 tw-text-emerald-700' : 'tw-bg-amber-50 tw-text-amber-700' }}">
                                                {{ __('invoice.' . @$parcel->invoice->status) }}
                                            </span>
                                            <div class="tw-text-xs tw-text-gray-700 tw-mt-1 tw-tabular-nums">{{ @$parcel->invoice->invoice_id }}</div>
                                            @if ($paid)
                                                <div class="tw-text-[11px] tw-text-gray-500 tw-tabular-nums">{{ __('Paid at') }} {{ @dateFormat(@$parcel->invoice->updated_at) }}</div>
                                            @endif
                                        @else
                                            <span class="tw-text-xs tw-text-gray-400">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="tw-px-4 tw-py-12 tw-text-center">
                                        <div class="tw-text-gray-300 tw-mb-2"><i class="fa fa-box-open tw-text-3xl"></i></div>
                                        <p class="tw-text-sm tw-text-gray-500 tw-m-0">No parcels in this range.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($parcels->hasPages())
                    <div class="tw-flex tw-flex-col sm:tw-flex-row sm:tw-items-center sm:tw-justify-between tw-gap-3 tw-px-5 tw-py-4 tw-border-t tw-border-gray-100">
                        <p class="tw-text-xs tw-text-gray-500 tw-m-0">
                            {!! __('Showing') !!} <span class="tw-font-medium tw-text-gray-700">{{ $parcels->firstItem() }}</span>
                            {!! __('to') !!} <span class="tw-font-medium tw-text-gray-700">{{ $parcels->lastItem() }}</span>
                            {!! __('of') !!} <span class="tw-font-medium tw-text-gray-700">{{ $parcels->total() }}</span>
                            {!! __('results') !!}
                        </p>
                        <div>{{ $parcels->appends($request->all())->links() }}</div>
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
    <script type="text/javascript" src="{{ static_asset('backend/js/date-range-picker/date-range-picker-custom.js') }}"></script>
    <script>
        var dateParcel = '{{ $request->parcel_date }}';
    </script>
    <script src="{{ static_asset('backend/js/merchant_panel/parcel/filter.js') }}"></script>
    <script src="{{ static_asset('backend/js/parcel/parcel-search.js') }}"></script>

    <script type="text/javascript">
        // Bulk-select for the multi-parcel label print form. The "tick-all"
        // checkbox cascades to .common-key inputs; whenever any is toggled,
        // we rebuild the hidden inputs in #print_label_content and show/hide
        // the print button based on whether the selection is non-empty.
        $(document).ready(function () {
            $('#tick-all').on('change', function () {
                $('.common-key').prop('checked', $(this).is(':checked'));
                showPrintBtn();
            });

            $(document).on('click', '.common-key', function () {
                showPrintBtn();
            });

            function showPrintBtn() {
                var $checked = $('.common-key:checked');
                if ($checked.length > 0) {
                    $('.multiplelabelprint').show();
                    var inputs = '';
                    $checked.each(function () {
                        inputs += '<input type="hidden" name="parcels[]" value="' + $(this).val() + '"/>';
                    });
                    $('#print_label_content').html(inputs);
                } else {
                    $('.multiplelabelprint').hide();
                    $('#tick-all').prop('checked', false);
                    $('#print_label_content').html('');
                }
            }
        });
    </script>
@endpush
