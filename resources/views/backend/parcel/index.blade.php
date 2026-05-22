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
            data-toggle="modal"
            data-target="#parcelDetailsModal"
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
            data-toggle="modal"
            data-target="#parcelDetailsModal"
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
                            
                            
{{-- Modal --}}
<div class="modal fade" id="parcelDetailsModal" tabindex="-1" role="dialog" aria-labelledby="parcelDetailsLabel" aria-hidden="true"  >
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="parcelDetailsLabel">{{ __('Parcel Details') }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body p-0">
        <div id="parcel-details-loader" class="p-4">
          <div class="text-center">
            <div class="spinner-border" role="status"></div>
            <div class="mt-2">{{ __('Loading...') }}</div>
          </div>
        </div>
        <div id="parcel-details-content" class="d-none"></div>
        <div id="parcel-details-error" class="alert alert-danger m-3 d-none"></div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
      </div>

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
  var content = $('#parcel-details-content');
  var loader = $('#parcel-details-loader');
  var errorBox = $('#parcel-details-error');

  $(document).on('click', '.js-open-parcel', function () {
    var parcelId = $(this).data('parcel-id');

    content.addClass('d-none').html('');
    errorBox.addClass('d-none').text('');
    loader.removeClass('d-none');

    $.ajax({
      url: "{{ route('admin.parcels.details', ['parcel' => 'PARCEL_ID_PLACEHOLDER']) }}".replace('PARCEL_ID_PLACEHOLDER', parcelId),
      method: 'GET',
      dataType: 'html',
      success: function (html) {
        loader.addClass('d-none');
        content.removeClass('d-none').html(html);
      },
      error: function () {
        loader.addClass('d-none');
        errorBox.removeClass('d-none').text("{{ __('Failed to load parcel details.') }}");
      }
    });
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
