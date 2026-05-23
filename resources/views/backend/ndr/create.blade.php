@extends('backend.partials.master')
@section('title')
    {{ __('Create NDR') }} — {{ $parcel->tracking_id }}
@endsection

@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('ndr.index') }}" class="breadcrumb-link">{{ __('NDR') }}</a></li>
                            <li class="breadcrumb-item active">{{ __('Create') }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">{{ __('New NDR') }}</h5>
                    <span class="badge badge-pill badge-warning">{{ __('Attempt') }} {{ $attemptNumber }}/3</span>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border mb-3">
                        <div class="row">
                            <div class="col-md-4"><small class="text-muted d-block">{{ __('Tracking ID') }}</small><strong>{{ $parcel->tracking_id }}</strong></div>
                            <div class="col-md-4"><small class="text-muted d-block">{{ __('Customer') }}</small>{{ $parcel->customer_name ?? '—' }}</div>
                            <div class="col-md-4"><small class="text-muted d-block">{{ __('Phone') }}</small>{{ $parcel->customer_phone ?? '—' }}</div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('ndr.store') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="parcel_id" value="{{ $parcel->id }}" />

                        <div class="form-group">
                            <label>{{ __('Failure Reason') }} <span class="text-danger">*</span></label>
                            <select name="failure_reason" class="form-control" required id="reasonSelect">
                                <option value="">{{ __('Choose…') }}</option>
                                @foreach ($failureReasons as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('failure_reason') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="form-group">
                            <label>{{ __('Driver Notes') }}</label>
                            <textarea name="driver_notes" rows="3" class="form-control" placeholder="{{ __('What happened at the delivery point…') }}"></textarea>
                        </div>

                        <div class="form-group">
                            <label>{{ __('Driver Photo') }} <small class="text-muted">({{ __('optional, max 5MB') }})</small></label>
                            <input type="file" name="driver_photo" accept="image/*" class="form-control-file" />
                            @error('driver_photo') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="form-group">
                            <label>{{ __('Next Attempt Date') }} <small class="text-muted">({{ __('if rescheduling') }})</small></label>
                            <input type="date" name="next_attempt_date" class="form-control" />
                        </div>

                        @if ($attemptNumber >= 3)
                            <div class="alert alert-danger">
                                <strong>{{ __('This is attempt 3 — saving will auto-set the parcel to RETURN_TO_COURIER and the NDR to status=returned.') }}</strong>
                            </div>
                        @endif

                        <div class="d-flex">
                            <a href="{{ route('ndr.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary ml-auto">{{ __('Save NDR') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
