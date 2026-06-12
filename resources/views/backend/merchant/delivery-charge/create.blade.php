@section('title')
    {{ __('merchant.title') }} {{ __('merchant.delivery_charge') }} {{ __('levels.add') }}
@endsection
@extends('backend.merchant.view')
@section('backend.merchant.layout.list')
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('merchant.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('merchantmanage.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{route('merchant.index') }}" class="breadcrumb-link">{{ __('merchant.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{route('merchant.shops.index',$singleMerchant->id) }}" class="breadcrumb-link">{{ __('merchantshops.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.create') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <h2 class="pageheader-title">{{ __('merchant.create_delivery_charge') }}</h2>

            @if($deliveryCharges->isEmpty())
                {{-- Empty state: nothing to pick from. Rather than rendering an
                     empty <select> that looks broken, surface the actual cause
                     (no tenant-level rate cards exist) and link straight to
                     where the user can fix it. --}}
                <div class="alert alert-warning d-flex align-items-start" role="alert" style="gap: 12px;">
                    <i class="fa fa-info-circle mt-1" aria-hidden="true"></i>
                    <div>
                        <strong>{{ __('merchant.delivery_charge_empty_title') }}</strong>
                        <div class="mt-1">{{ __('merchant.delivery_charge_empty_body') }}</div>
                        <div class="mt-2">
                            <a href="{{ route('delivery-charge.create') }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-plus" aria-hidden="true"></i>
                                {{ __('merchant.delivery_charge_empty_cta') }}
                            </a>
                            <a href="{{ route('delivery-charge.index') }}" class="btn btn-sm btn-outline-secondary ml-2">
                                {{ __('merchant.delivery_charge_empty_view_all') }}
                            </a>
                            <a href="{{ route('merchant.deliveryCharge.index', $singleMerchant->id) }}" class="btn btn-sm btn-link">
                                {{ __('levels.cancel') }}
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <form action="{{route('merchant.deliveryCharge.store',$singleMerchant->id)}}" method="POST" enctype="multipart/form-data" id="basicform">
                    @csrf
                    <div class="row">
                        <div class="form-group col-6">
                            <label for="delivery_charge_id">{{ __('levels.category') }}</label> <span class="text-danger">*</span>
                            <select id="deliveryChargeID" name="delivery_charge_id" class="form-control @error('delivery_charge_id') is-invalid @enderror" data-url="{{ route('merchant.deliveryCharge.deliveryChargeInfo') }}">
                                @foreach($deliveryCharges as $deliverycharge)
                                    <option value="{{ $deliverycharge->id }}" {{ (old('delivery_charge_id') == $deliverycharge->id) ? 'selected' : '' }}>{{ $deliverycharge->category->title }} @if(isset($deliverycharge->weight)) ( {{ $deliverycharge->weight }} ) @endif</option>
                                @endforeach
                            </select>
                            @error('delivery_charge_id')
                                <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                    <div id="deliveryChargeInfo"></div>
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save') }}</button>
                                <a href="{{ route('merchant.deliveryCharge.index',$singleMerchant->id) }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
                            </div>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection()

@push('scripts')
    <script src="{{ static_asset('backend/js/merchantDeliveryCharge/create.js') }}"></script>
@endpush

