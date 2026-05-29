@extends('backend.partials.master')
@section('title')
    {{ __('parcel.title') }} {{ __('levels.list') }}
@endsection
@section('maincontent')
    <!-- wrapper  -->
    <div class="container-fluid  dashboard-content">
        <!-- page header -->
 <div class="row">
    <div class="col-12">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
            <div class="page-breadcrumb">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard.index') }}" class="breadcrumb-link">
                                {{ __('menus.dashboard') }}
                            </a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('parcel.index') }}" class="breadcrumb-link">
                                {{ __('parcel.title') }}
                            </a>
                        </li>
                        <li class="breadcrumb-item active">
                            <a href="#" class="breadcrumb-link">{{ __('levels.list') }}</a>
                        </li>
                    </ol>
                </nav>
            </div>

            <!-- Items per page dropdown floated to end -->
        <form method="GET" class="mt-2" action="{{ request()->fullUrlWithQuery([]) }}"  >
    <div class="form-inline float-end">
        <label class="mr-2">{{ __("Items per page") }}:</label>
        <select name="per_page" class="form-control" onchange="this.form.submit()">
            @foreach([10, 20, 50, 100, 500, 1000] as $limit)
                <option value="{{ $limit }}"
                    {{ (session('per_page', request('per_page', 10)) == $limit) ? 'selected' : '' }}>
                    {{ $limit === 'all' ? 'All' : $limit }}
                </option>
            @endforeach
        </select>
    </div>
</form>

        </div>
    </div>
</div>


        <!-- end page header -->
        <div class="row">
            <!-- data table  -->
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('parcel.filter') }}" method="GET">
                          
                            <div class="row">
                                <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-2">
                                    <label for="parcel_date">{{ __('parcel.date') }}</label>
                                    <input type="text" autocomplete="off" id="date" name="parcel_date"
                                        placeholder="Enter Date" class="form-control date_range_picker"
                                        value="{{ old('parcel_date', $request->parcel_date) }}">
                                    @error('parcel_date')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-2">
                                    <label for="parcelStatus">{{ __('parcel.status') }}</label>
                                    <select style="width: 100%" id="parcelStatus" name="parcel_status"
                                        class="form-control @error('parcel_status') is-invalid @enderror">
                                        <option value="" selected> {{ __('menus.select') }}
                                            {{ __('levels.status') }}</option>
                                        @foreach (trans('parcelStatusFilter') as $key => $status)
                                            <option value="{{ $key }}"
                                                {{ old('parcel_status', $request->parcel_status) == $key ? 'selected' : '' }}>
                                                {{ $status }}</option>
                                        @endforeach
                                    </select>
                                    @error('parcel_status')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-2">
                                    <label for="parcelMerchantid">{{ __('parcel.merchant') }}</label>
                                    <select style="width: 100%" id="parcelMerchantid" name="parcel_merchant_id"
                                        class="form-control @error('parcel_merchant_id') is-invalid @enderror"
                                        data-url="{{ route('parcel.merchant.shops') }}">
                                        <option value=""> {{ __('menus.select') }} {{ __('merchant.title') }}
                                        </option>
                                    </select>
                                    @error('parcel_merchant_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-2">
                                    <label for="parcelDeliveryManID">{{ __('Delivery courier') }}</label>
                                    <select style="width: 100%" id="parcelDeliveryManID" name="parcel_deliveryman_id"
                                        data-url="{{ route('parcel.deliveryman.search') }}"
                                        class="form-control @error('parcel_deliveryman_id') is-invalid @enderror">
                                        <option value="">{{ __('menus.select') }} {{ __('deliveryman.title') }}
                                        </option>
                                    </select>
                                    @error('parcel_deliveryman_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-2">
                                    <label for="parcelPickupmanId">{{ __('Pickup courier') }}</label>
                                    <select style="width: 100%" id="parcelPickupmanId" name="parcel_pickupman_id"
                                        data-url="{{ route('parcel.deliveryman.search') }}"
                                        class="form-control @error('parcel_pickupman_id') is-invalid @enderror">
                                        <option value=""> {{ __('menus.select') }} {{ __('parcel.pickup_man') }}
                                        </option>
                                    </select>
                                    @error('parcel_pickupman_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-2">
                                    <label for="invoice_id">{{ __('parcel.invoice_id') }}</label>
                                    <input id="invoice_id" type="text" name="invoice_id"
                                        placeholder="{{ __('parcel.invoice_id') }}" autocomplete="off" class="form-control"
                                        value="{{ old('invoice_id', $request->invoice_id) }}">
                                    @error('parcel_customer_phone')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                
                             <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-2">
    <label for="has_3pl">{{ __('3PL') }}</label>
    <select style="width: 100%" id="has_3pl" name="has_3pl" class="form-control @error('has_3pl') is-invalid @enderror">
        <option value="">{{ __('All') }}</option>
        <option value="panda" {{ request('has_3pl') == 'panda' ? 'selected' : '' }}>
            {{ __('panda') }}
        </option>
    </select>

    @error('has_3pl')
        <small class="text-danger mt-2">{{ $message }}</small>
    @enderror
</div>

                                

                                <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-2 pt-1 pl-0">
                                    <div class="col-12 pt-3 d-flex justify-content text-right">
                                        <button type="submit" class="btn btn-sm btn-space btn-primary"><i
                                                class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                                        <a href="{{ route('parcel.index') }}" class="btn btn-sm btn-space btn-secondary"><i
                                                class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
                                    </div>
                                </div>
                                
                              
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card">
                    <div class="row pl-4 pr-4 pt-4">
                        <div class="col-12 col-xl-12 mb-3">
                            <form action="{{ route('parcel.specific.search') }}" method="get">
                              
                                <div class="d-flex parcelsearchFlex">
                                    <p class="h3">


                         
                                            

                                        {{ __('parcel.title') }}</p>
                                    <input id="Psearch" class="form-control parcelSearch group-input d-lg-block "
                                        name="search" type="text" placeholder="{{ __('levels.search') }}..."
                                        value="{{ $request->search }}">
                                    <button type="submit" class="btn btn-sm btn-space btn-primary group-btn  d-lg-block"
                                        style="margin-bottom: 0px;margin-left:0px!important"><i class="fa fa-filter"></i>
                                        {{ __('levels.search') }}</button>
                                </div>
                            </form>
                        </div>
                       
                                            
                        <div class="row">
                        <div class="col-12 d-lg-none ">
                            <form action="{{ route('parcel.specific.search') }}" method="get">
                               
                                <div class="d-flex parcelsearchFlex ml-0">
                                    <input id="Psearch" class="parcelml-0 form-control  group-input w-100 "
                                        name="search" type="text" placeholder="{{ __('levels.search') }}..."
                                        value="{{ $request->search }}">
                                    <button type="submit" class="btn btn-sm btn-space btn-primary group-btn"
                                        style="margin-bottom: 0px;margin-left:0px!important"><i class="fa fa-filter"></i>
                                        {{ __('levels.search') }}</button>
                                </div>
                            </form>
                        </div>
                        </div>
                        @if (hasPermission('parcel_create'))
                            <div class="col-12 col-xl-12 mt-2 mt-lg-12 mt-xl-0">
                                <div class="text-right d-flex justify-content-start parcel-index-bulk">
                                    {{-- multiple parcel label print --}}
                                    <form action="{{ route('parcel.multiple.print-label') }}" method="get"
                                        target="_blank" id="print_label_form">
                                        @csrf
                                        <div id="print_label_content"></div>
                                        <button type="submit" class="btn btn-sm btn-primary mr-2 multiplelabelprint"
                                            data-parcels='' style="display: none">{{ __('levels.print_label') }}</button>
                                    </form>
                                    {{-- end multiple parcel label print --}}

                                    @if ($request->parcel_status == \App\Enums\ParcelStatus::DELIVERY_MAN_ASSIGN)
                                        <a href="{{ route('parcel.parcel-bulkassign-print', ['parcels' => $parcelsPrint->pluck('id')->toArray(), 'delivery_man_id' => $request->parcel_deliveryman_id]) }}"
                                            class="btn btn-sm btn-primary ml-1 mb-1" target="_blank"
                                            data-toggle="tooltip" data-placement="top"
                                            title="Print">{{ __('parcel.print') }}</a>
                                    @endif
                                    <a href="{{ route('parcel.parcelDeliveryMan') }}" target="_blank"
                                        class="btn btn-sm btn-secondary mr-1 " data-toggle="tooltip" data-placement="top"
                                        title="Parcel Map"><i class="fa fa-map-location"></i> {{ __('parcel.map') }}</a>

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
                                    
                                    @php
                                    $exportUrl = route('parcel.parcel-export', request()->query());
                                    @endphp

                                    <a href="{{ $exportUrl }}" class="btn btn-sm btn-info ml-1"
                                        data-toggle="tooltip" data-placement="top"
                                        title="{{ __('Export :TOTAL Shipments', ['TOTAL' => $parcels->total() ?? 0 ]) }}">
                                        <i class="fa fa-download"></i>
                                        {{ __('Export :TOTAL Shipments', ['TOTAL' => $parcels->total() ?? 0 ]) }}
                                    </a>


                                    <a href="{{ route('parcel.parcel-import') }}" class="btn btn-sm btn-success ml-1 "
                                        data-toggle="tooltip" data-placement="top" title="Parcel Import"><i
                                            class="fa fa-plus"></i> {{ __('parcel.import_parcel') }}</a>
                                    <a href="{{ route('parcel.create') }}" class="btn btn-sm btn-primary ml-1 "
                                        data-toggle="tooltip" data-placement="top" title="Add"><i
                                            class="fa fa-plus"></i> {{ __('levels.add') }}</a>
                                            
                                          
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="table" class="table    parcelTable" style="width:100%">
                                <thead>
                                    <tr>
                                        <th class="parcel-index permission-check-box">
                                            <input type="checkbox" id="tick-all" class="form-check-input" />
                                        </th>
                                        <th>{{ __('###') }}</th>
                                        <th>{{ __('parcel.tracking_id') }}</th>
                                        <th>{{ __('AWB') }}</th>
                                        <th>{{ __('parcel.recipient_info') }}</th>
                                        <th>{{ __('parcel.merchant') }}</th>
                                        <th>{{ __('parcel.amount') }}</th>
                                        <th>{{ __('parcel.priority') }}</th>
                                        <th>{{ __('parcel.status') }}</th>
                                        @if (hasPermission('parcel_status_update') == true)
                                            <th>{{ __('parcel.status_update') }}</th>
                                        @endif
                                        <th>{{ __('parcel.payment') }}</th>
                                        <th>{{ __('Attempts') }}</th>
                                        
                                        <th>{{ __('View Proof of Delivery') }}</th>
                                        <th>{{ __('Courier name / 3PL') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $i = 1;
                                    @endphp
                                    @foreach ($parcels as $parcel)
                                        <tr>
                                            <td class="parcel-index permission-check-box">
                                                <input type="checkbox" name="parcels[][{{ $parcel->id }}]"
                                                    value="{{ $parcel->id }}" class="common-key form-check-input" />
                                            </td>
                                            
                                            <td>
                                                <div class="row">
                                                    <button tabindex="-1" data-toggle="dropdown" type="button"
                                                        class="btn btn-sm ml-2 bnone">...</button>
                                                    <div class="dropdown-menu">
                                                        <a href="{{ route('parcel.details', $parcel->id) }}"
                                                            class="dropdown-item"><i class="fa fa-eye"
                                                                aria-hidden="true"></i> {{ __('levels.view') }}</a>
                                                        <a href="{{ route('parcel.logs', $parcel->id) }}"
                                                            class="dropdown-item"><i class="fas fa-history"
                                                                aria-hidden="true"></i> {{ __('levels.parcel_logs') }}</a>
                                                        <a href="{{ route('parcel.clone', $parcel->id) }}"
                                                            class="dropdown-item"><i class="fas fa-clone"
                                                                aria-hidden="true"></i> {{ __('levels.clone') }}</a>
                                                        <a href="{{ route('parcel.print', $parcel->id) }}"
                                                            class="dropdown-item"><i class="fas fa-print"
                                                                aria-hidden="true"></i> {{ __('levels.print') }}</a>
                                                        <a href="{{ route('parcel.print-label', $parcel->id) }}"
                                                            target="_blank" class="dropdown-item"><i class="fas fa-print"
                                                                aria-hidden="true"></i> {{ __('levels.print_label') }}</a>
                                                        @if (
                                                            \App\Enums\ParcelStatus::DELIVERED !== $parcel->status &&
                                                                \App\Enums\ParcelStatus::PARTIAL_DELIVERED !== $parcel->status)
                                                            @if (hasPermission('parcel_update') == true)
                                                                <a href="{{ route('parcel.edit', $parcel->id) }}"
                                                                    class="dropdown-item"><i class="fas fa-edit"
                                                                        aria-hidden="true"></i>
                                                                    {{ __('levels.edit') }}</a>
                                                            @endif
                                                            @if (hasPermission('parcel_delete'))
                                                                <form id="delete" value="Test"
                                                                    action="{{ route('parcel.delete', $parcel->id) }}"
                                                                    method="POST"
                                                                    data-title="{{ __('delete.parcel') }}">
                                                                    @method('DELETE')
                                                                    @csrf
                                                                    <input type="hidden" name="" value="Parcel"
                                                                        id="deleteTitle">
                                                                    <button type="submit" class="dropdown-item"><i
                                                                            class="fa fa-trash" aria-hidden="true"></i>
                                                                        {{ __('levels.delete') }}</button>
                                                                </form>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                           
<td>
    @if($parcel->lastParcel3pl)
        {{ __('levels.track_id') }}:
        <span 
            class="active text-primary" 
            id="track-{{ $parcel->id }}" 
            style="cursor: pointer; text-decoration: underline;"
            onclick="copyToClipboard('track-{{ $parcel->id }}', this)"
            title="{{ __('Click to copy') }}"
        >
            {{ $parcel->tracking_id }}
        </span>

        <br>
        <button title="{{ $parcel->lastParcel3pl->current_status ?? "" }}"
            type="button"
            class="btn btn-outline-danger btn-sm mt-2 js-open-parcel"
            data-bs-toggle="offcanvas"
            data-bs-target="#parcelTrackingOffcanvas"
            data-parcel-id="{{ $parcel->id }}"
        >
            {{ __('Assigned to 3PL') }}:
            {{ $parcel->lastParcel3pl->company_name ?? $parcel->lastParcel3pl->parcel_3pl_name }}
        </button>
    @else
        {{ __('levels.track_id') }}:
        <span 
            class="active text-primary" 
            id="track-{{ $parcel->id }}" 
            style="cursor: pointer; text-decoration: underline;"
            onclick="copyToClipboard('track-{{ $parcel->id }}', this)"
            title="{{ __('Click to copy') }}"
        >
            {{ $parcel->tracking_id }}
        </span>

        <button
            type="button"
            class="btn btn-outline-info btn-sm mt-2 js-open-parcel"
            data-bs-toggle="offcanvas"
            data-bs-target="#parcelTrackingOffcanvas"
            data-parcel-id="{{ $parcel->id }}"
        >
            {{ $parcel->tracking_id }}
        </button>
    @endif
</td>






                                                     <td>
                                                         <a href="{{ route('parcel.print-label', $parcel->id) }}" >
                                                             <i class="fa fa-file text-danger fa-lg fa-2x"></i>
                                                         </a>
                                                     </td>
                                            <td class="merchantpayment">
                                                <div class="w150">
                                                    <div class="d-flex">
                                                        <i class="fa fa-user"></i>&nbsp;<p>{{ $parcel->customer_name }}
                                                        </p>
                                                    </div>
                                                    <div class="d-flex">
                                                        <i class="fas fa-phone"></i>&nbsp;<p>{{ $parcel->customer_phone }}
                                                        </p>
                                                    </div>
                                                    
                                                     <div class="d-flex">
                                                           
                                                        <i class="fas fa-map"></i>&nbsp;<p>
                                                            {{ $parcel->city?->en_name }} , {{ $parcel->area?->en_name }}</p>
                                                    </div>
                                                    
                                                    <div class="d-flex">

                                                        <i class="fas fa-map-marker-alt"></i>&nbsp;<p>
                                                            {{ $parcel->customer_address }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="merchantpayment">
                                                <p>{{ $parcel->merchant->business_name ?? "-" }}</p>
                                                <p>{{ $parcel->merchant->user->mobile ?? "-" }}</p>
                                                <p>{{ $parcel->merchant->address ?? "-" }}</p>
                                            </td>

<td>
    <div class="w250">

<!--
    <span class="editable text-dark"
              data-id="{{ $parcel->id }}"
              data-field="cash_collection"
              contenteditable="true"
              onblur="saveInlineEdit(this)"
              onkeypress="if(event.key === 'Enter'){ event.preventDefault(); this.blur(); }"
              style="border-bottom:1px dashed #999; cursor:text;">
            {{ $parcel->cash_collection }}
        </span>
        -->
        {{-- COD (Editable) --}}
        {{ __('COD') }}:
        <span class="editable text-dark" 
            >
            <b>{{ $parcel->cash_collection }}</b>
        </span>
        {{ settings()->currency }}
       
        
         @if(hasPermission('parcel_finance_update'))
        <br>
        {{-- Total Charge Amount --}}
        {{ __('Total Charge Amount') }}:
        <span class="text-dark total_delivery_amount-{{ $parcel->id }}">
            {{ settings()->currency }}{{ $parcel->total_delivery_amount }}
        </span>
        <br>

        {{-- VAT --}}
        {{ __('Vat') }}:
        <span class="text-dark vat_amount-{{ $parcel->id }}">
            {{ settings()->currency }}{{ $parcel->vat_amount }}
        </span>
        <br>

        {{-- Current Payable --}}
        {{ __('Current Payable') }}:
        <b class="text-dark current_payable-{{ $parcel->id }}">
            {{ settings()->currency }}{{ $parcel->current_payable }}
        </b>
        <br>
        
        @endif

    </div>
</td>


                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input switch-id" type="checkbox"
                                                        data-url="{{ route('parcel.priority.status') }}"
                                                        data-id="{{ $parcel->id }}" role="switch"
                                                        value="{{ $parcel->priority_type_id }}"
                                                        @if ($parcel->priority_type_id == 1) checked @else @endif>
                                                </div>
                                            </td>
                                            <td>{!! $parcel->parcel_status !!} <br>
                                                @if ($parcel->partial_delivered && $parcel->status != \App\Enums\ParcelStatus::PARTIAL_DELIVERED)
                                                    <span
                                                        class="badge badge-pill badge-success mt-2">{{ trans('parcelStatus.' . \App\Enums\ParcelStatus::PARTIAL_DELIVERED) }}</span>
                                                @endif
                                                <br />
                                                <span>{{ __('parcel.updated_on') }}:
                                                    {{ \Carbon\Carbon::parse($parcel->updated_at)->format('Y-m-d h:i:s A') }}</span>
                                            </td>
                                            @if (hasPermission('parcel_status_update') == true)
                                                <td>
                                                    @if (
                                                        \App\Enums\ParcelStatus::DELIVERED !== $parcel->status &&
                                                            \App\Enums\ParcelStatus::PARTIAL_DELIVERED !== $parcel->status &&
                                                            \App\Enums\ParcelStatus::RETURN_RECEIVED_BY_MERCHANT !== $parcel->status)
                                                        <div class="input-group mb-3">
                                                            <div class="input-group-prepend be-addon">
                                                                <button tabindex="-1" data-toggle="dropdown"
                                                                    type="button"
                                                                    class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span
                                                                        class="sr-only">Toggle Dropdown</span></button>
                                                                <div class="dropdown-menu">
                                                                    {!! parcelStatus($parcel) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @else
                                                        ...
                                                    @endif
                                                </td>
                                            @endif
                                            <td>
                                                @if ($parcel->invoice)
                                                    <p>{{ __('invoice.' . @$parcel->invoice->status) }}</p>
                                                    {{ @$parcel->invoice->invoice_id }}<br />
                                                    @if ($parcel->invoice->status == App\Enums\InvoiceStatus::PAID)
                                                        Paid At: {{ @dateFormat(@$parcel->invoice->updated_at) }}
                                                    @endif
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>{{ $parcel->number_of_attempts  }}</td>
                                            <td>
                                                @if ($parcel->status == \App\Enums\ParcelStatus::DELIVERED)
                                                    <a href="{{ route('parcel.deliveredInfo', $parcel->id) }}"
                                                        class="btn btn-sm btn-warning ml-1 " data-toggle="tooltip"
                                                        data-placement="top" title="View">{{ __('View') }}</a>
                                                @endif
                                            </td>
                                            <td>
                                               
                                                 @if($parcel->lastParcel3pl)
    
            <spam class="text-danger" >{{ $parcel->lastParcel3pl->company_name ?? $parcel->lastParcel3pl->parcel_3pl_name }}</spam>
       
    @endif
    
      <span class="badge bg-info">
  {{ optional($parcel->lastParcelEvent)->deliveryMan->user->name ?? '—' }}
</span>

    
    
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            
                            
{{-- Tracking offcanvas --}}
<div class="offcanvas offcanvas-end pkg-track-offcanvas" tabindex="-1" id="parcelTrackingOffcanvas" aria-labelledby="parcelTrackingOffcanvasLabel">
  <div class="offcanvas-header pkg-track-offcanvas__head">
    <h5 class="offcanvas-title" id="parcelTrackingOffcanvasLabel">{{ __('Package history and tracking') }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
  </div>
  <div class="offcanvas-body p-0">
    <div id="parcel-track-loader" class="p-5 text-center">
      <div class="spinner-border text-primary" role="status"></div>
      <div class="mt-2">{{ __('Loading...') }}</div>
    </div>
    <div id="parcel-track-content"></div>
    <div id="parcel-track-error" class="alert alert-danger m-3 d-none"></div>
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
                 @if ($parcels instanceof \Illuminate\Pagination\AbstractPaginator)
    <span>{{ $parcels->appends($request->all())->links() }}</span>
    <p class="p-2 small">
        {!! __('Showing') !!}
        <span class="font-medium">{{ $parcels->firstItem() }}</span>
        {!! __('to') !!}
        <span class="font-medium">{{ $parcels->lastItem() }}</span>
        {!! __('of') !!}
        <span class="font-medium">{{ $parcels->total() }}</span>
        {!! __('results') !!}
    </p>
@else
    <p class="p-2 small text-muted">
        Showing all <span class="font-medium">{{ $parcels->count() }}</span> results
    </p>
@endif

                </div>
            </div>
            <!-- end data table  -->
        </div>
    </div>
    


    <!-- end wrapper  -->
@endsection()

<!-- css  -->
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style>
        #selectAssignType .select2-container .select2-selection--single {
            height: 32px !important;
        }

        /* ===== Tracking offcanvas (uses system theme colors) ===== */
        .pkg-track-offcanvas {
            width: min(1080px, 96vw) !important;
            --pkg-primary: {{ settings()->primary_color ?: '#007bff' }};
        }
        .pkg-track-offcanvas__head { background: #f8f9fa; border-bottom: 1px solid #e9ecef; }
        .pkg-track-offcanvas .offcanvas-title { font-weight: 700; }

        .pkg-track { font-size: 14px; color: #2b3445; --pkg-primary: {{ settings()->primary_color ?: '#007bff' }}; }
        .pkg-track .row { margin: 0; }

        /* action toolbar */
        .pkg-actions {
            display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px;
            padding: 14px 20px; background: #fff; border-bottom: 1px solid #e9ecef;
        }
        .pkg-actions__dd { position: relative; display: inline-block; }
        .pkg-actions__btn {
            display: inline-flex; align-items: center; gap: 6px; font-weight: 600;
            border: 1px solid #d8dee6; background: #fff; color: #2b3445;
        }
        .pkg-actions__btn:hover { background: #f1f3f7; color: #2b3445; }
        .pkg-actions__btn--print { background: #e23b3b; border-color: #e23b3b; color: #fff; }
        .pkg-actions__btn--print:hover { background: #c92f2f; border-color: #c92f2f; color: #fff; }
        .pkg-ofc-statusmenu { display: none; position: absolute; top: 100%; right: 0; left: auto; z-index: 1080; min-width: 200px; }
        .pkg-ofc-statusmenu.show { display: block; }
        .pkg-ofc-statusmenu .dropdown-item { cursor: pointer; white-space: normal; }
        .pkg-track__left { background: #fff; padding: 24px 20px; border-right: 1px solid #e9ecef; }
        .pkg-track__right { background: #f8f9fa; padding: 24px 20px; }

        /* parties (sender / recipient / attachment) */
        .pkg-party { position: relative; display: flex; gap: 14px; padding-bottom: 26px; }
        .pkg-party::before {
            content: ""; position: absolute; left: 17px; top: 36px; bottom: -6px;
            width: 2px; background: #dee2e6;
        }
        .pkg-party--last::before { display: none; }
        .pkg-party__icon {
            flex: 0 0 36px; width: 36px; height: 36px; border-radius: 50%;
            background: var(--pkg-primary); color: #fff; display: flex; align-items: center;
            justify-content: center; z-index: 1;
        }
        .pkg-party__body { flex: 1 1 auto; min-width: 0; }
        .pkg-party__title {
            background: #eef1f5; color: #1f2d3d; font-weight: 700; border-radius: 8px;
            padding: 8px 14px; margin-bottom: 10px; border-left: 3px solid var(--pkg-primary);
        }
        .pkg-party__name { font-weight: 700; margin-bottom: 4px; }
        .pkg-party__addr { color: #5a6675; line-height: 1.5; margin-bottom: 8px; word-break: break-word; }
        .pkg-party__phone {
            display: inline-flex; align-items: center; gap: 8px; background: #eef1f5;
            border-radius: 30px; padding: 6px 6px 6px 14px; font-weight: 600;
        }
        .pkg-wa {
            width: 30px; height: 30px; border-radius: 50%; background: #25d366; color: #fff;
            display: inline-flex; align-items: center; justify-content: center; text-decoration: none;
        }
        .pkg-wa:hover { color: #fff; opacity: .9; }

        /* attachment gallery */
        .pkg-gallery { display: flex; flex-wrap: wrap; gap: 10px; }
        .pkg-gallery__item { display: block; width: 130px; text-align: center; text-decoration: none; color: #5a6675; }
        .pkg-gallery__item img {
            width: 130px; height: 150px; object-fit: cover; border-radius: 10px; border: 1px solid #e2e2e2;
        }
        .pkg-gallery__cap { display: block; font-size: 11px; margin-top: 4px; }
        .pkg-empty { color: #98a2b3; font-style: italic; }

        /* detail card */
        .pkg-detail { background: #fff; border: 1px solid #e9ecef; border-radius: 12px; padding: 18px 20px; margin-bottom: 26px; }
        .pkg-detail__head { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 16px; flex-wrap: wrap; }
        .pkg-detail__box {
            width: 60px; height: 60px; border-radius: 50%; border: 2px solid var(--pkg-primary);
            display: flex; align-items: center; justify-content: center; color: var(--pkg-primary); font-size: 22px;
        }
        .pkg-detail__barcode { flex: 1 1 auto; text-align: right; }
        .pkg-detail__barcode img { max-width: 100%; height: auto; }
        .pkg-detail__grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 24px; }
        .pkg-row { display: flex; justify-content: space-between; gap: 12px; padding: 7px 0; border-bottom: 1px dashed #eee; }
        .pkg-row span { color: #7a8699; }
        .pkg-row strong { text-align: right; word-break: break-word; }
        .pkg-row--wide { grid-column: 1 / -1; }
        .pkg-detail__status { display: flex; align-items: center; justify-content: space-between; margin-top: 14px; }
        .pkg-detail__status > span:first-child { font-weight: 700; }
        .pkg-status-badge { padding: 7px 18px; font-size: 13px; }

        /* timeline */
        .pkg-timeline { position: relative; padding-left: 4px; }
        .pkg-tl__date { font-weight: 700; color: #1f2d3d; margin: 14px 0 8px; }
        .pkg-tl__item { display: grid; grid-template-columns: 130px 24px 1fr; gap: 10px; align-items: start; }
        .pkg-tl__meta { text-align: right; font-size: 12px; color: #5a6675; padding-top: 2px; }
        .pkg-tl__actor { font-weight: 700; color: #2b3445; }
        .pkg-tl__hub { text-transform: uppercase; font-size: 11px; }
        .pkg-tl__time { font-size: 11px; color: #98a2b3; }
        .pkg-tl__line { position: relative; display: flex; justify-content: center; }
        .pkg-tl__line::before { content: ""; position: absolute; top: 0; bottom: -18px; width: 2px; background: var(--pkg-primary); opacity: .35; }
        .pkg-tl__item:last-child .pkg-tl__line::before { bottom: 50%; }
        .pkg-tl__dot { width: 14px; height: 14px; border-radius: 50%; margin-top: 4px; z-index: 1; border: 3px solid #f8f9fa; }
        .pkg-tl__content { display: flex; align-items: flex-start; gap: 10px; flex-wrap: wrap; padding-bottom: 18px; }
        .pkg-tl__bubble { flex: 1 1 auto; min-width: 160px; background: var(--pkg-primary); color: #fff; border-radius: 8px; padding: 10px 14px; font-weight: 600; }
        .pkg-tl__note { font-weight: 400; font-size: 12px; margin-top: 4px; opacity: .9; }
        .pkg-gallery__img--contain { object-fit: contain !important; background: #fff; }

        @media (max-width: 575.98px) {
            .pkg-detail__grid { grid-template-columns: 1fr; }
            .pkg-tl__item { grid-template-columns: 90px 20px 1fr; }
        }
    </style>
@endpush
<!-- js  -->
@push('scripts')
    <script src="{{ static_asset('js/onscan.js/onscan.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript" src="{{ static_asset('backend/js/date-range-picker/date-range-picker-custom.js') }}">
    </script>
    <script>
        var merchantUrl = '{{ route('parcel.merchant.get') }}';
        var merchantID = '{{ $request->parcel_merchant_id }}';
        var deliveryManID = '{{ $request->parcel_deliveryman_id }}';
        var pickupManID = '{{ $request->parcel_pickupman_id }}';
        var dateParcel = '{{ $request->parcel_date }}';
    </script>
    <script src="{{ static_asset('backend/js/parcel/custom.js') }}"></script>
    <script src="{{ static_asset('backend/js/parcel/filter.js') }}"></script>
    <script src="{{ static_asset('backend/js/parcel/priorityChange.js') }}"></script>
    
    
 
<script>
(function () {
  var content = $('#parcel-track-content');
  var loader = $('#parcel-track-loader');
  var errorBox = $('#parcel-track-error');
  var trackUrl = "{{ route('parcel.tracking_offcanvas', ['id' => 'PARCEL_ID_PLACEHOLDER']) }}";

  $(document).on('click', '.js-open-parcel', function () {
    var parcelId = $(this).data('parcel-id');

    content.html('');
    errorBox.addClass('d-none').text('');
    loader.removeClass('d-none');

    $.ajax({
      url: trackUrl.replace('PARCEL_ID_PLACEHOLDER', parcelId),
      method: 'GET',
      dataType: 'html',
      success: function (html) {
        loader.addClass('d-none');
        content.html(html);
        // Neutralize the injected status items' own modal triggers — they are
        // re-driven through the already-bound original row items (see below).
        content.find('.pkg-ofc-statusmenu .dropdown-item').each(function () {
          this.removeAttribute('data-toggle');
          this.removeAttribute('data-target');
          this.removeAttribute('data-bs-toggle');
          this.removeAttribute('data-bs-target');
        });
      },
      error: function () {
        loader.addClass('d-none');
        errorBox.removeClass('d-none').text("{{ __('Failed to load parcel details.') }}");
      }
    });
  });

  function hidePkgOfc() {
    var el = document.getElementById('parcelTrackingOffcanvas');
    if (el && window.bootstrap && bootstrap.Offcanvas) {
      (bootstrap.Offcanvas.getInstance(el) || new bootstrap.Offcanvas(el)).hide();
    }
  }

  // Find the equivalent already-bound action element in the parcel table (its
  // jQuery handlers + modal data-api are wired on page load) and click it.
  function fireOriginal(parcel, matchClass) {
    var $orig = $('[data-parcel="' + parcel + '"]').filter(function () {
      return (matchClass ? this.getAttribute('class') === matchClass
                         : $(this).hasClass('parcel-id-delivery-man'))
             && $(this).closest('#parcel-track-content').length === 0;
    }).first();
    if ($orig.length) { hidePkgOfc(); $orig[0].click(); }
  }

  // Manual dropdown toggle (avoids BS4/BS5 data-api ambiguity inside the offcanvas)
  $(document).on('click', '#parcel-track-content .pkg-ofc-statusbtn', function (e) {
    e.preventDefault(); e.stopPropagation();
    $(this).siblings('.pkg-ofc-statusmenu').toggleClass('show');
  });
  $(document).on('click', function () {
    $('#parcel-track-content .pkg-ofc-statusmenu.show').removeClass('show');
  });

  $(document).on('click', '#parcel-track-content .pkg-ofc-statusmenu .dropdown-item', function (e) {
    e.preventDefault();
    fireOriginal(this.getAttribute('data-parcel'), this.getAttribute('class'));
  });

  $(document).on('click', '#parcel-track-content .pkg-ofc-postpone', function (e) {
    e.preventDefault();
    fireOriginal(this.getAttribute('data-parcel'), null);
  });
})();
</script>

<script>
function copyToClipboard(elementId, el) {
    const text = document.getElementById(elementId).innerText.trim();
    navigator.clipboard.writeText(text).then(() => {
        // 💡 إظهار رسالة نجاح قصيرة داخل العنصر نفسه
        const original = el.innerText;
        el.innerText = "✅ {{ __('Copied!') }}";
        el.style.color = "#28a745";
        setTimeout(() => {
            el.innerText = original;
            el.style.color = "";
        }, 1500);
    }).catch(() => {
        alert("{{ __('Failed to copy') }}");
    });
}
</script>


<script>
function saveInlineEdit(el) {
    let value = el.innerText.trim();
    let parcelId = el.getAttribute("data-id");
    let field = el.getAttribute("data-field");

    if (value === "") {
        alert("Value cannot be empty");
        return;
    }

    el.style.opacity = "0.6";

    $.ajax({
        url: "{{ route('parcel.inline.update') }}",
        method: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            id: parcelId,
            
            cash_collection: value
        },
        success: function(res) {
            
            

            let p = res;
            
            // console.log(p);

            // Update Total Delivery Amount
            $(".total_delivery_amount-" + parcelId).text("{{ settings()->currency }}" + p.total_delivery_amount);

            // Update VAT
            $(".vat_amount-" + parcelId).text("{{ settings()->currency }}" + p.vat_amount);

            // Update Current Payable
            $(".current_payable-" + parcelId).text("{{ settings()->currency }}" + p.current_payable);

            // UI Feedback
            el.style.opacity = "1";
            el.style.background = "#d4edda";

            setTimeout(() => {
                el.style.background = "transparent";
            }, 800);
        },
        error: function () {
            alert("Failed to update. Try again.");
            el.style.opacity = "1";
        }
    });
}
</script>


@endpush
