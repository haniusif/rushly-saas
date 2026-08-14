@extends('backend.partials.master')

@section('title', __('zatca.settings_title'))

@section('mainContent')
<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fa fa-file-invoice"></i> {{ __('zatca.settings_title') }}
                    </h5>
                    <span class="badge {{ $setting->isReady() ? 'badge-success' : 'badge-warning' }}">
                        {{ $setting->isReady() ? __('zatca.ready_yes') : __('zatca.ready_no') }}
                    </span>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-4">{{ __('zatca.settings_subtitle') }}</p>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('merchant.panel.zatca.settings.update') }}">
                        @csrf
                        @method('PUT')

                        <h6 class="text-primary mt-2 mb-3">{{ __('zatca.seller_info') }}</h6>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>{{ __('zatca.seller_name_en') }} <span class="text-danger">*</span></label>
                                <input type="text" name="seller_name_en" class="form-control"
                                    value="{{ old('seller_name_en', $setting->seller_name_en) }}" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('zatca.seller_name_ar') }} <span class="text-danger">*</span></label>
                                <input type="text" name="seller_name_ar" class="form-control" dir="rtl"
                                    value="{{ old('seller_name_ar', $setting->seller_name_ar) }}" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('zatca.vat_number') }} <span class="text-danger">*</span></label>
                                <input type="text" name="vat_number" class="form-control" maxlength="15"
                                    pattern="3\d{13}3"
                                    placeholder="3xxxxxxxxxxxxx3"
                                    value="{{ old('vat_number', $setting->vat_number) }}" required>
                                <small class="form-text text-muted">{{ __('zatca.vat_number_hint') }}</small>
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('zatca.cr_number') }}</label>
                                <input type="text" name="cr_number" class="form-control"
                                    value="{{ old('cr_number', $setting->cr_number) }}">
                            </div>
                        </div>

                        <h6 class="text-primary mt-4 mb-3">{{ __('zatca.address') }}</h6>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>{{ __('zatca.street_en') }}</label>
                                <input type="text" name="address_street_en" class="form-control"
                                    value="{{ old('address_street_en', $setting->address_street_en) }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('zatca.street_ar') }}</label>
                                <input type="text" name="address_street_ar" class="form-control" dir="rtl"
                                    value="{{ old('address_street_ar', $setting->address_street_ar) }}">
                            </div>
                            <div class="form-group col-md-2">
                                <label>{{ __('zatca.building_no') }}</label>
                                <input type="text" name="building_number" class="form-control"
                                    value="{{ old('building_number', $setting->building_number) }}">
                            </div>
                            <div class="form-group col-md-5">
                                <label>{{ __('zatca.district_en') }}</label>
                                <input type="text" name="district_en" class="form-control"
                                    value="{{ old('district_en', $setting->district_en) }}">
                            </div>
                            <div class="form-group col-md-5">
                                <label>{{ __('zatca.district_ar') }}</label>
                                <input type="text" name="district_ar" class="form-control" dir="rtl"
                                    value="{{ old('district_ar', $setting->district_ar) }}">
                            </div>
                            <div class="form-group col-md-4">
                                <label>{{ __('zatca.city_en') }}</label>
                                <input type="text" name="city_en" class="form-control"
                                    value="{{ old('city_en', $setting->city_en) }}">
                            </div>
                            <div class="form-group col-md-4">
                                <label>{{ __('zatca.city_ar') }}</label>
                                <input type="text" name="city_ar" class="form-control" dir="rtl"
                                    value="{{ old('city_ar', $setting->city_ar) }}">
                            </div>
                            <div class="form-group col-md-2">
                                <label>{{ __('zatca.postal_code') }}</label>
                                <input type="text" name="postal_code" class="form-control"
                                    value="{{ old('postal_code', $setting->postal_code) }}">
                            </div>
                            <div class="form-group col-md-2">
                                <label>{{ __('zatca.country_code') }}</label>
                                <input type="text" name="country_code" class="form-control" maxlength="2"
                                    value="{{ old('country_code', $setting->country_code ?: 'SA') }}">
                            </div>
                        </div>

                        <h6 class="text-primary mt-4 mb-3">{{ __('zatca.tax') }}</h6>
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>{{ __('zatca.vat_rate') }} (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="vat_rate" class="form-control"
                                    value="{{ old('vat_rate', $setting->vat_rate ?: 15) }}" required>
                            </div>
                            <div class="form-group col-md-3">
                                <label>{{ __('zatca.currency') }}</label>
                                <input type="text" name="currency" class="form-control" maxlength="3"
                                    value="{{ old('currency', $setting->currency ?: 'SAR') }}" required>
                            </div>
                            <div class="form-group col-md-3">
                                <label>{{ __('zatca.mode') }}</label>
                                <select name="mode" class="form-control" required>
                                    @foreach ($modes as $val => $label)
                                        <option value="{{ $val }}" {{ old('mode', $setting->mode) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label>{{ __('zatca.invoice_prefix') }}</label>
                                <input type="text" name="invoice_prefix" class="form-control" maxlength="20"
                                    value="{{ old('invoice_prefix', $setting->invoice_prefix ?: 'ZAT-') }}">
                            </div>
                        </div>

                        <div class="form-row mt-3">
                            <div class="form-group col-md-6">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="enabled" value="1" class="custom-control-input" id="zatca-enabled"
                                        {{ old('enabled', $setting->enabled) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="zatca-enabled">{{ __('zatca.enabled') }}</label>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="auto_generate" value="1" class="custom-control-input" id="zatca-auto"
                                        {{ old('auto_generate', $setting->auto_generate) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="zatca-auto">{{ __('zatca.auto_generate') }}</label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary mt-3">
                            <i class="fa fa-save"></i> {{ __('zatca.save') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
