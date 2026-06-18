@extends('backend.partials.master')
@section('title')
    {{ __('abnormal.title') }} — {{ __('settings.title') }}
@endsection

@push('styles')
<style>
  .pill-radio { display: inline-flex; gap: 6px; flex-wrap: wrap; }
  .pill-radio label { padding: 6px 14px; border: 1px solid #e5e7eb; border-radius: 999px; cursor: pointer; background: #f9fafb; font-size: 13px; transition: all .2s; }
  .pill-radio input { display: none; }
  .pill-radio input:checked + span { color: #fff; }
  .pill-radio label:has(input:checked) { background: #6366f1; border-color: #6366f1; color: #fff; }
  .switch-row { display: flex; align-items: center; padding: 10px 0; border-bottom: 1px dashed #e5e7eb; }
  .switch-row:last-child { border-bottom: none; }
  .switch-row label { flex: 1; margin: 0; cursor: pointer; }
</style>
@endpush

@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('abnormal.index') }}" class="breadcrumb-link">{{ __('abnormal.title') }}</a></li>
                            <li class="breadcrumb-item active">{{ __('settings.title') }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('abnormal.settings.update') }}">
                @csrf @method('PUT')

                <div class="card mb-3">
                    <div class="card-header"><h6 class="mb-0">{{ __('abnormal.detection') }}</h6></div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label class="d-block small text-muted">{{ __('abnormal.detection_after_inactivity') }}</label>
                            <div class="pill-radio">
                                @foreach ([1,2,3,4,5,7] as $d)
                                    <label>
                                        <input type="radio" name="threshold_days" value="{{ $d }}" {{ (int)$config['threshold_days']===$d ? 'checked' : '' }}>
                                        <span>{{ $d }} {{ __('day(s)') }}</span>
                                    </label>
                                @endforeach
                                <label>
                                    <input type="number" name="threshold_days" min="1" max="60" class="form-control form-control-sm d-inline" style="width:70px;display:inline-block;margin-left:6px;" value="{{ $config['threshold_days'] }}" />
                                </label>
                            </div>
                            <small class="text-muted">{{ __('abnormal.default_3_days') }}</small>
                        </div>

                        <div class="form-group mb-0">
                            <label class="d-block small text-muted">{{ __('abnormal.auto_escalation_threshold') }}</label>
                            <div class="pill-radio">
                                @foreach ([5,7,10,14] as $d)
                                    <label>
                                        <input type="radio" name="auto_escalation_days" value="{{ $d }}" {{ (int)$config['auto_escalation_days']===$d ? 'checked' : '' }}>
                                        <span>{{ $d }} {{ __('days') }}</span>
                                    </label>
                                @endforeach
                                <label>
                                    <input type="number" name="auto_escalation_days" min="1" max="60" class="form-control form-control-sm d-inline" style="width:70px;display:inline-block;margin-left:6px;" value="{{ $config['auto_escalation_days'] }}" />
                                </label>
                            </div>
                            <small class="text-muted">{{ __('abnormal.auto_escalation_hint') }}</small>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header"><h6 class="mb-0">{{ __('abnormal.exclude_from_detection') }}</h6></div>
                    <div class="card-body">
                        <div class="switch-row">
                            <label for="ex_h">{{ __('abnormal.public_holidays') }} <small class="text-muted d-block">{{ __('abnormal.public_holidays_hint') }}</small></label>
                            <input type="checkbox" id="ex_h" name="exclude_holidays"  {{ $config['exclude_holidays']  ? 'checked' : '' }} />
                        </div>
                        <div class="switch-row">
                            <label for="ex_c">{{ __('abnormal.pending_customs') }} <small class="text-muted d-block">{{ __('abnormal.pending_customs_hint') }}</small></label>
                            <input type="checkbox" id="ex_c" name="exclude_customs"   {{ $config['exclude_customs']   ? 'checked' : '' }} />
                        </div>
                        <div class="switch-row">
                            <label for="ex_o">{{ __('abnormal.sender_hold') }} <small class="text-muted d-block">{{ __('abnormal.sender_hold_hint') }}</small></label>
                            <input type="checkbox" id="ex_o" name="exclude_on_hold"   {{ $config['exclude_on_hold']   ? 'checked' : '' }} />
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header"><h6 class="mb-0">{{ __('abnormal.notifications') }}</h6></div>
                    <div class="card-body">
                        <div class="switch-row">
                            <label for="dd">{{ __('abnormal.daily_digest_8am') }} <small class="text-muted d-block">{{ __('abnormal.daily_digest_hint') }}</small></label>
                            <input type="checkbox" id="dd" name="daily_digest_enabled" {{ $config['daily_digest_enabled'] ? 'checked' : '' }} />
                        </div>
                    </div>
                </div>

                <div class="d-flex">
                    <a href="{{ route('abnormal.index') }}" class="btn btn-light">{{ __('levels.cancel') }}</a>
                    <button class="btn btn-primary ml-auto">{{ __('abnormal.save_settings') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
