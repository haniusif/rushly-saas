@extends('backend.partials.master')
@section('title')
    {{ __('ndr.create_title') }} — {{ $parcel->tracking_id }}
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
                            <li class="breadcrumb-item"><a href="{{ route('ndr.index') }}" class="breadcrumb-link">{{ __('ndr.title') }}</a></li>
                            <li class="breadcrumb-item active">{{ __('levels.create') }}</li>
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
                    <h5 class="mb-0">{{ __('ndr.new') }}</h5>
                    <span class="badge badge-pill badge-warning">{{ __('ndr.attempt') }} {{ $attemptNumber }}/3</span>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border mb-3">
                        <div class="row">
                            <div class="col-md-4"><small class="text-muted d-block">{{ __('ndr.tracking_id') }}</small><strong>{{ $parcel->tracking_id }}</strong></div>
                            <div class="col-md-4"><small class="text-muted d-block">{{ __('levels.customer') }}</small>{{ $parcel->customer_name ?? '—' }}</div>
                            <div class="col-md-4"><small class="text-muted d-block">{{ __('levels.phone') }}</small>{{ $parcel->customer_phone ?? '—' }}</div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('ndr.store') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="parcel_id" value="{{ $parcel->id }}" />

                        <div class="form-group">
                            <label>{{ __('ndr.failure_reason') }} <span class="text-danger">*</span></label>
                            <select name="failure_reason" class="form-control" required id="reasonSelect">
                                <option value="">{{ __('levels.choose') }}</option>
                                @foreach ($failureReasons as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('failure_reason') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="form-group">
                            <label>{{ __('ndr.driver_notes') }}</label>
                            <textarea name="driver_notes" rows="3" class="form-control" placeholder="{{ __('ndr.driver_notes_placeholder') }}"></textarea>
                        </div>

                        <div class="form-group">
                            <label>{{ __('ndr.driver_photo') }} <small class="text-muted">({{ __('ndr.driver_photo_hint') }})</small></label>
                            <input type="file" name="driver_photo" accept="image/*" class="form-control-file" />
                            @error('driver_photo') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="form-group">
                            <label>{{ __('ndr.next_attempt_date') }} <small class="text-muted">({{ __('ndr.reschedule_hint') }})</small></label>
                            <input type="date" name="next_attempt_date" class="form-control" />
                        </div>

                        @if ($attemptNumber >= 3)
                            <div class="alert alert-danger">
                                <strong>{{ __('ndr.final_attempt_warning') }}</strong>
                            </div>
                        @endif

                        <div class="d-flex">
                            <a href="{{ route('ndr.index') }}" class="btn btn-light">{{ __('levels.cancel') }}</a>
                            <button type="submit" class="btn btn-primary ml-auto">{{ __('ndr.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
