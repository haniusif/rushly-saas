@extends('backend.partials.master')
@section('title')
    {{ __('merchantmanage.title') }} {{ __('merchantmanage.payment') }}  {{ __('levels.add') }}
@endsection

@section('maincontent')
<div class="container-fluid dashboard-content">
    <!-- pageheader -->
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('merchantmanage.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('merchantmanage.payment') }}</a></li>
                            <li class="breadcrumb-item active" aria-current="page">{{ __('levels.create') }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- form -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="pageheader-title">{{ __('merchantmanage.create_payment') }}</h2>

                    <form action="{{ route('merchantmanage.payment.store') }}" method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        <div class="row">
                            <!-- LEFT COLUMN -->
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="merchant">{{ __('merchant.title') }}</label> <span class="text-danger">*</span>
                                    <input id="mercant_url" data-url="{{ route('merchant-manage.merchant-search') }}" type="hidden"/>
                                    <select id="merchant" class="form-control" name="merchant" data-url="{{ route('merchant-manage.merchant.account') }}">
                                        <option selected disabled>{{ __('menus.select') }} {{ __('merchant.title') }}</option>
                                        @foreach ($merchants as $merchant)
                                            <option value="{{ $merchant->id }}">{{ $merchant->user->name }} | {{ __('levels.current_balance') }}: {{ $merchant->current_balance }}</option>
                                        @endforeach
                                    </select>
                                    @error('merchant')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="amount">{{ __('merchantmanage.amount') }}</label> <span class="text-danger">*</span>
                                    <input id="amount" type="number" name="amount" class="form-control" placeholder="{{ __('placeholder.Enter_Amount') }}" value="{{ old('amount') }}" required>
                                    @error('amount')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="merchant_account">{{ __('merchantmanage.merchant_account') }}</label> <span class="text-danger">*</span>
                                    <select id="merchant_account" class="form-control" name="merchant_account">
                                        <option disabled selected>{{ __('menus.select') }} {{ __('merchant.title') }} {{ __('account.title') }}</option>
                                    </select>
                                    @error('merchant_account')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" value="1" id="isprocess" name="isprocess" @if ($errors->has('transaction_id') || $errors->has('from_account')) checked @endif>
                                        {{ __('hub.is_processed ') }}?
                                    </label>
                                </div>

                                <div class="process" @if (!$errors->has('transaction_id') && !$errors->has('from_account')) style="display:none" @endif>
                                    <div class="form-group">
                                        <label for="transaction_id">{{ __('merchantmanage.transaction_id') }}</label> <span class="text-danger">*</span>
                                        <input id="transaction_id" type="text" name="transaction_id" class="form-control" placeholder="{{ __('placeholder.Enter_Transaction_ID') }}" value="{{ old('transaction_id') }}">
                                        @error('transaction_id')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="from_account">{{ __('merchantmanage.from_account') }}</label> <span class="text-danger">*</span>
                                        <select class="form-control" name="from_account">
                                            <option selected disabled>{{ __('menus.select') }} {{ __('merchantmanage.from_account') }}</option>
                                            @foreach ($accounts as $account)
                                                @if ($account->gateway == 1)
                                                    <option value="{{ $account->id }}">{{ $account->user->name }} | {{ __('merchant.cash') }}</option>
                                                @elseif(in_array($account->gateway, [3,4,5]))
                                                    <option value="{{ $account->id }}">{{ $account->account_holder_name }} | No: {{ $account->mobile }} | 
                                                        @if($account->type == 1) {{ __('merchant.title') }} @else {{ __('placeholder.persional') }} @endif |
                                                        {{ __('merchantmanage.current_balance') }}: {{ $account->balance }}
                                                    </option>
                                                @else
                                                    <option value="{{ $account->id }}">{{ $account->account_holder_name }} | A.No: {{ $account->account_no }} | {{ __('merchantmanage.current_balance') }}: {{ $account->balance }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        @error('from_account')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="referance_file">{{ __('merchantmanage.reference_file') }}</label>
                                        <input id="referance_file" type="file" name="reference_file" class="form-control">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="description">{{ __('merchantmanage.description') }}</label>
                                    <textarea name="description" class="form-control" placeholder="{{ __('placeholder.Enter_description') }}">{{ old('description') }}</textarea>
                                </div>
                            </div>

                            <!-- RIGHT COLUMN -->
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="shipment_ids">{{ __('merchantmanage.shipment_ids') }}</label>
                                    <div class="input-group">
                                        <textarea id="shipment_ids" name="shipment_ids" class="form-control" rows="10" placeholder="Paste shipment IDs (e.g. 12345, 12346, 12347 or one per line)">{{ old('shipment_ids') }}</textarea>
                                        <div class="input-group-append d-flex flex-column">
                                            <button type="button" id="check_shipments_btn" class="btn btn-info btn-sm mb-1">
                                                <i class="fa fa-search"></i> {{ __('Check') }}
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">
                                        You can paste multiple shipment IDs separated by commas or new lines.
                                    </small>
                                    <div id="shipment_highlight" class="mt-2" style="display:none; font-size:14px;"></div>
                                    @error('shipment_ids')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>{{ __('merchantmanage.total_cod') }}</label>
                                    <input type="text" id="total_cod" class="form-control" readonly value="0">
                                    <small class="form-text text-muted">This value updates automatically after clicking “Check”.</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" id="save_btn" class="btn btn-space btn-primary">{{ __('levels.save') }}</button>
                                <a href="{{ route('merchant.manage.payment.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ static_asset('backend/js/merchantmanaage/create.js') }}"></script>

<script>
$(document).ready(function() {

    function highlightShipments(groups) {
        let html = '';
        groups.forEach((group, index) => {
            let color = '#dc3545';
            html += `<div><b style="color:${color}">Merchant #${group.merchant_id}:</b> ${group.shipments.join(', ')}</div>`;
        });
        $('#shipment_highlight').html(html).show();
    }

    function fetchShipmentCOD(ids) {
        $.ajax({
            url: "{{ route('merchant-manage.payment.payment_get_cod') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                ids: ids
            },
            beforeSend: function() {
                $('#total_cod').val('Calculating...');
                $('#shipment_highlight').hide().empty();
                $('#save_btn').prop('disabled', true);
            },
            success: function(response) {
                if (response.error) {
                    $('#total_cod').val('0');
                    alert(response.message);
                    if (response.grouped) highlightShipments(response.grouped);
                    $('#save_btn').prop('disabled', true);
                    return;
                }

                $('#total_cod').val(response.total_cod);
                $('#amount').val(response.total_cod);
                if (response.merchant_id) {
                    $('#merchant').val(response.merchant_id).trigger('change');
                }
                $('#save_btn').prop('disabled', false);
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                $('#total_cod').val('Error');
                $('#save_btn').prop('disabled', true);
            }
        });
    }

    function handleShipmentCheck() {
        let data = $('#shipment_ids').val().trim();
        if (!data) {
            alert('Please paste or enter shipment IDs first.');
            return;
        }
        let ids = data.split(/[\s,]+/).filter(Boolean);
        fetchShipmentCOD(ids);
    }

    $('#shipment_ids').on('paste', function() {
        setTimeout(handleShipmentCheck, 200);
    });

    $('#check_shipments_btn').on('click', function() {
        handleShipmentCheck();
    });
});
</script>
@endpush
