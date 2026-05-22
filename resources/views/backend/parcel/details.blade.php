@extends('backend.partials.master')
@section('title')
    {{ __('parcel.title') }}    {{ __('levels.view') }}
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
  .select2-container {
    width: 100% !important;
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


<script>
  $(document).ready(function () {

    // عند فتح المودال يتم تفعيل Select2
    $('#addNdrModal').on('shown.bs.modal', function () {
      const $modal = $(this);

      // تفعيل Select2 لكل القوائم داخل المودال
      $modal.find('.select2').select2({
        dropdownParent: $modal,        // لتجنب مشاكل z-index داخل المودال
        width: '100%',
        placeholder: '{{ __("Select") }}',
        allowClear: true,
        language: {
          noResults: function() {
            return '{{ __("No results found") }}';
          }
        },
        // لجعل البحث متاح دائمًا حتى لو القائمة صغيرة
        minimumResultsForSearch: 0
      });
    });

  });
</script>


@endpush

@section('maincontent')


<!-- wrapper  -->
<div class="container-fluid  dashboard-content">
    <!-- pageheader -->
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{route('parcel.index')}}" class="breadcrumb-link">{{ __('parcel.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{__('levels.details')}}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
            <div class="d-flex justify-content-end mb-5">
             <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#addNdrModal">
          <i class="fas fa-plus"></i> {{ __('Add NDR') }}
        </button>
        </div>
<div class="d-flex justify-content-end d-none">
  
  <form action="{{ route('parcel.3pl_details', $parcel->id) }}" method="POST" class="d-inline-block">
    @csrf
    <div class="input-group">
        <select name="company" class="custom-select" required>
            <option value="">{{ __('Select 3PL Company') }}</option>
            <option value="panda">Panda</option>
            <!--<option value="zajil">Zajil</option>-->

        </select>
        <div class="input-group-append">
            <button type="submit" class="btn btn-danger">{{ __('To 3PL') }}</button>
        </div>
    </div>
</form>


</div>


        </div>
    </div>
   
   
   
   
    <!-- end pageheader -->
    <div class="row">
        <!-- data table  -->
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <p class="h4">{{ __('invoice.invoice') }} : #{{ @$parcel->invoice_no }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <p class="h4">{{ __('levels.cash_on_delivery') }}</p>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table    table-sm">
                                    <tbody>
                                        <tr>
                                            <td>{{__('levels.delivery_fee')}}</td>
                                            <td>{{ settings()->currency }} {{@number_format(($parcel->total_delivery_amount - $parcel->cod_amount),2)}}</td>
                                        </tr>
                                        <tr>
                                            <td>{{__('levels.cod')}}</td>
                                            <td>{{$parcel->cod_amount}}</td>
                                        </tr>
                                        <tr>
                                            <td  ><strong>{{__('levels.total_cost')}}</strong></td>
                                            <td  ><strong>{{ settings()->currency }} {{@$parcel->total_delivery_amount}}</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <p class="h4">{{ __('levels.delivery_info') }}</p>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table  table-sm">
                                    <tbody>
                                        <tr>
                                            <td>{{__('levels.delivery_type')}}</td>
                                            <td>
                                                {{ @$parcel->delivery_type_name }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>{{__('levels.weight')}}</td>
                                            <td>{{@$parcel->weight}} {{@$parcel->deliveryCategory->title}}</td>
                                        </tr>
                                        <tr>
                                            <td>{{__('levels.amount_to_collect')}}</td>
                                            <td>{{@$parcel->cash_collection}}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <p class="h4">{{ __('levels.sender_info') }}</p>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table    table-sm">
                                    <tbody>
                                        <tr>
                                            <td>{{__('levels.business_name')}}</td>
                                            <td>
                                                {{ @$parcel->merchant->business_name }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>{{__('levels.mobile')}}</td>
                                            <td> {{@$parcel->merchant->user->mobile}}</td>
                                        </tr>
                                        <tr>
                                            <td>{{__('levels.email')}}</td>
                                            <td>{{@$parcel->merchant->user->email}}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <p class="h4">{{ __('levels.recipient_info') }}</p>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <tbody>
                                        <tr>
                                            <td>{{__('levels.name')}}</td>
                                            <td>
                                                {{ @$parcel->customer_name }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>{{__('levels.phone')}}</td>
                                            <td> {{@$parcel->customer_phone}}</td>
                                        </tr>
                                        <tr>
                                            <td>{{__('levels.address')}}</td>
                                            <td>{{@$parcel->customer_address}}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @if(count($data) >= 1)
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <p class="h4">{{ __('3PL info') }}</p>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <tbody>
                                        @foreach ($data as $key => $value)
                                        <tr>
                                            <td>{{ $key }}</td>
                                            <td>
                                                {{ $value }}
                                            </td>
                                        </tr>
                                         @endforeach 
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                
                @if($parcel->images && $parcel->images->count() > 0)

<div class="card mt-4">
    <div class="card-header">
        <h5>Parcel Images</h5>
    </div>

    <div class="card-body">
        <div class="d-flex flex-wrap">

            @foreach($parcel->images as $image)
                <div class="m-2 text-center">
 
                    <a href="{{ url('storage/app/public/'.$image->image_path) }}" target="_blank">
                        <img src="{{ url('storage/app/public/'.$image->image_path) }}"
                             width="120"
                             height="120"
                             style="object-fit:cover; border-radius:8px; border:1px solid #ddd;">
                    </a>

                    <div class="mt-1">
                        <small class="text-muted">
                            {{ ucfirst($image->type) }}
                        </small>
                    </div>

                </div>
            @endforeach

        </div>
    </div>
</div>

@endif
                
            @if($parcel->rejected_parcels && $parcel->rejected_parcels->count() > 0)
  <div class="col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <p class="h4 mb-0">{{ __('NDR (Non-Delivery Reasons)') }}</p>
      </div>

      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-sm align-middle">
            <thead class="thead-light">
              <tr>
                <th>#</th>
                <th>{{ __('Reason') }}</th>
                <th>{{ __('Delivery Man') }}</th>
                <th>{{ __('Comments') }}</th>
                <th>{{ __('Attachments') }}</th>
                <th>{{ __('Created At') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($parcel->rejected_parcels as $key => $rejected_parcel)
                <tr>
                  <td>{{ $key + 1 }}</td>

                  {{-- Reason --}}
                  <td>
                    {{ $rejected_parcel->reason?->en_name ?? '-' }}
                    <br>
                    <small class="text-muted">{{ $rejected_parcel->reason?->name ?? '' }}</small>
                  </td>

                  {{-- Delivery Man --}}
                  <td>
                    {{ $rejected_parcel->deliveryman?->user?->name ?? $rejected_parcel->deliveryman?->name ?? __('N/A') }}
                    <br>
                    <small class="text-muted">
                      {{ $rejected_parcel->deliveryman?->user?->mobile ?? '-' }}
                    </small>
                  </td>

                  {{-- Comments --}}
                  <td>{{ $rejected_parcel->comments ?? '-' }}</td>

                  {{-- Attachments --}}
                  <td>
                    @php
                      $attachments = is_array($rejected_parcel->attachments) ? $rejected_parcel->attachments : [];
                    @endphp

                    @if(count($attachments) > 0)
                      <div class="d-flex flex-wrap">
                        @foreach($attachments as $file)
                          @php
                            $isImage = preg_match('/\.(jpg|jpeg|png|gif)$/i', $file);
                          @endphp

                          @if($isImage)
                            <a href="{{ $file }}" target="_blank" class="m-1" title="View Image">
                              <img src="{{ static_asset($file) }}" alt="Attachment" width="70" height="70" class="rounded border">
                            </a>
                          @else
                            <a href="{{ $file }}" target="_blank" class="btn btn-outline-primary btn-sm m-1">
                              <i class="fas fa-file-pdf"></i> {{ __('View File') }}
                            </a>
                          @endif
                        @endforeach
                      </div>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>

                  {{-- Date --}}
                  <td>{{ $rejected_parcel->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
@endif


            </div>
        </div>
        <!-- end data table  -->
    </div>
</div>


<div class="modal fade" id="addNdrModal" tabindex="-1" role="dialog" aria-labelledby="addNdrModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form action="{{ route('parcel.add_ndr', $parcel->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-header  text-white">
          <h5 class="modal-title" id="addNdrModalLabel">{{ __('Add NDR (Non-Delivery Report)') }}</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">

          <div class="form-group">
            <label>{{ __('Rejection Reason') }}</label>
            <select name="rejection_reason_id" class="form-control select2" required>
              <option value="">{{ __('Select Reason') }}</option>
              @foreach(\App\Models\Backend\RejectionReason::all() as $reason)
                <option value="{{ $reason->id }}">{{ $reason->en_name }} ({{ $reason->name }})</option>
              @endforeach
            </select>
          </div>

            
          {{-- اختيار المندوب --}}
          <div class="form-group">
            <label>{{ __('Delivery Man') }} ( <span class="text-danger">{{  $parcel->lastDeliveryMan?->deliveryMan?->user?->name }}</span> ) </label>
            <select name="deliveryman_id" class="form-control select2" required>
              <option value="">{{ __('Select Delivery Man') }}</option>
              @foreach($deliveryman as $man)
                <option value="{{ $man->id }}"
                  @if($parcel->lastDeliveryMan?->delivery_man_id == $man->id) selected @endif>
                  {{ $man->user->name ?? $man->name ?? 'N/A' }} - {{ $man->user->mobile ?? '-' }}
                </option>
              @endforeach
            </select>
          </div>

          {{-- التعليقات --}}
          <div class="form-group">
            <label>{{ __('Comments') }}</label>
            <textarea name="comments" class="form-control" rows="3" placeholder="{{ __('Add your notes...') }}"></textarea>
          </div>

          {{-- المرفقات --}}
          <div class="form-group">
            <label>{{ __('Attachments') }}</label>
            <input type="file" name="attachments[]" class="form-control" multiple accept="image/*,application/pdf">
            <small class="form-text text-muted">{{ __('You can upload multiple files or photos.') }}</small>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
          <button type="submit" class="btn btn-danger">{{ __('Save NDR') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>


 
@endsection()


