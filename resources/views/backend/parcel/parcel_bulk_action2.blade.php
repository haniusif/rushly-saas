@extends('backend.partials.master')
@section('title')
    {{ __('parcel.title') }} {{ __('Bulk action') }}
@endsection
@section('maincontent')
    <div class="container-fluid  dashboard-content">
        <!-- pageheader -->
        <div class="row">
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                <div class="page-header">
                    <div class="page-breadcrumb">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}"
                                        class="breadcrumb-link">{{ __('parcel.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('parcel.index') }}"
                                        class="breadcrumb-link">{{ __('parcel.title') }}</a></li>
                                <li class="breadcrumb-item"><a href=""
                                        class="breadcrumb-link active">{{ __('Bulk action') }}</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 col-lg-12 col-xl-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="pageheader-title">{{ __('Bulk action') }}</h2>
                        <form action="{{ route('parcel.store') }}" method="POST" enctype="multipart/form-data"
                            id="basicform">
                            @csrf
                            
                               <select class="input p-2 select2 select-bulk-type" id="selectAssignType">
                                        <option>{{ __('levels.select_bulk_type') }}</option>
                                        <option value="assignpickupbulk">{{ __('levels.assign_pickup') }}</option>
                                        <option value="transfer_to_hub_multiple_parcel">{{ __('levels.hub_transfer') }}
                                        </option>
                                        <option value="received_by_hub_multiple_parcel">{{ __('levels.received_by_hub') }}
                                        </option>
                                        <option value="delivery_man_assign_multiple_parcel">
                                            {{ __('levels.delivery_man_assign') }}</option>
                                        <option value="assign_return_merchant">{{ __('levels.assign_return_merchant') }}
                                        </option>
                                    </select>
  
                            <div class="row mt-2">
                                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12  d-flex justify-content-end">
                                    <a href="{{ route('parcel.index') }}"
                                        class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
                                    <button type="submit"
                                        class="btn btn-space btn-primary">{{ __('levels.save') }}</button>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>


                            @include('backend.parcel.pickup_assign_modal')
                            @include('backend.parcel.pickup_re_schedule')
                            @include('backend.parcel.received_by_pickup')
                            @include('backend.parcel.transfer_to_hub')
                            @include('backend.parcel.received_by_hub')
                            @include('backend.parcel.delivery_man_assign')
                            @include('backend.parcel.delivery_reschedule')
                            @include('backend.parcel.partial_delivered_modal')
                            @include('backend.parcel.delivered_modal')
                            @include('backend.parcel.received_warehouse')
                            @include('backend.parcel.return_to_qourier')
                            @include('backend.parcel.return_assign_to_merchant')
                            @include('backend.parcel.re_schedule_return_assign_to_merchant')
                            @include('backend.parcel.return_received_by_merchant')
                            @include('backend.parcel.transfer_to_hub_multiple_parcel')
                            @include('backend.parcel.received_by_hub_multiple_parcel')
                            @include('backend.parcel.assign_pickup_bulk')
                            @include('backend.parcel.delivery_man_assign_multiple_parcel')
                            @include('backend.parcel.assign_return_to_merchant_bulk')
                            
        </div>
    </div>
@endsection()

<!-- css  -->
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style>
        #selectAssignType .select2-container .select2-selection--single {
            height: 32px !important;
        }
    </style>
@endpush
<!-- js  -->
@push('scripts')
    <script src="{{ static_asset('js/onscan.js/onscan.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src="{{ static_asset('backend/js/parcel/custom.js') }}"></script>


@endpush
