@extends('backend.partials.master')
@section('title')
    {{ __('merchant.title') }}  {{ __('levels.edit') }}
@endsection
@section('maincontent')
<div class="container-fluid dashboard-content rl-merchant-edit">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('merchantmanage.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{route('merchant.index') }}" class="breadcrumb-link">{{ __('merchant.title') }}</a></li>
                            <li class="breadcrumb-item active">{{ __('levels.edit') }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <form action="{{route('merchant.update',$merchant)}}" method="POST" enctype="multipart/form-data" id="basicform">
        @method('PUT')
        @csrf
        <input type="hidden" name="id" id="id" value="{{$merchant->id}}">
        <input id="merchant_unique_id" type="hidden" name="merchant_unique_id" class="form-control">

        {{-- Page header summary --}}
        <div class="rl-mx-card rl-mx-card--head">
            <div class="rl-mx-head">
                <div class="rl-mx-avatar">
                    <img src="{{ $merchant->user->image }}" alt="{{ $merchant->user->name }}">
                </div>
                <div class="rl-mx-meta">
                    <div class="rl-mx-name">{{ $merchant->business_name }}</div>
                    <div class="rl-mx-sub">
                        <i class="ti ti-user"></i> {{ $merchant->user->name }}
                        <span class="rl-mx-dot"></span>
                        <i class="ti ti-mail"></i> {{ $merchant->user->email }}
                    </div>
                </div>
                <div class="rl-mx-actions">
                    <a href="{{ route('merchant.index') }}" class="btn btn-secondary"><i class="ti ti-arrow-left"></i> {{ __('levels.cancel') }}</a>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> {{ __('levels.save_change') }}</button>
                </div>
            </div>
        </div>

        {{-- Business info --}}
        <div class="rl-mx-card">
            <div class="rl-mx-card__head"><i class="ti ti-building-store"></i><span>{{ __('levels.business_name') }}</span></div>
            <div class="rl-mx-card__body">
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label for="business_name">{{ __('levels.business_name') }} <span class="text-danger">*</span></label>
                            <input id="business_name" type="text" name="business_name" placeholder="{{ __('placeholder.enter_business_name') }}" autocomplete="off" class="form-control @error('business_name') is-invalid @enderror" value="{{ old('business_name',$merchant->business_name) }}" required>
                            @error('business_name')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                        </div>
                        <div class="form-group">
                            <label for="address">{{ __('levels.address') }} <span class="text-danger">*</span></label>
                            <textarea id="address" name="address" rows="3" placeholder="{{ __('placeholder.Enter_address') }}" class="form-control">{{ old('address',$merchant->address) }}</textarea>
                            @error('address')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label for="opening_balance">{{ __('levels.opening_balance') }}</label>
                            <input id="opening_balance" type="number" name="opening_balance" placeholder="{{ __('placeholder.Enter_opening_balance') }}" autocomplete="off" class="form-control @error('opening_balance') is-invalid @enderror" value="{{ old('opening_balance',$merchant->opening_balance) }}">
                            @error('opening_balance')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                        </div>
                        <div class="form-group">
                            <label for="vat">{{ __('levels.vat') }}</label>
                            <input id="vat" type="number" name="vat" placeholder="{{ __('placeholder.Enter_vat') }}" autocomplete="off" class="form-control @error('vat') is-invalid @enderror" value="{{ old('vat',$merchant->vat) }}">
                            @error('vat')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                        </div>
                        <div class="form-group">
                            <label for="return_charges">{{ __('levels.return_charges') }} (%)</label>
                            <input type="number" id="return_charges" placeholder="{{ __('levels.return_charges') }}" name="return_charges" class="form-control" value="{{ old('return_charges',$merchant->return_charges) }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Account --}}
        <div class="rl-mx-card">
            <div class="rl-mx-card__head"><i class="ti ti-user-circle"></i><span>{{ __('levels.name') }} &amp; {{ __('levels.email') }}</span></div>
            <div class="rl-mx-card__body">
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label for="name">{{ __('levels.name') }} <span class="text-danger">*</span></label>
                            <input id="name" type="text" name="name" placeholder="{{ __('placeholder.Enter_name') }}" autocomplete="off" class="form-control @error('name') is-invalid @enderror" value="{{ old('name',$merchant->user->name) }}" required>
                            @error('name')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                        </div>
                        <div class="form-group">
                            <label for="mobile">{{ __('levels.phone') }} <span class="text-danger">*</span></label>
                            <input id="mobile" type="number" name="mobile" placeholder="Enter Mobile" autocomplete="off" class="form-control @error('mobile') is-invalid @enderror" value="{{ old('mobile',$merchant->user->mobile) }}">
                            @error('mobile')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                        </div>
                        <div class="form-group">
                            <label for="email">{{ __('levels.email') }}</label>
                            <input id="email" type="text" name="email" placeholder="{{ __('placeholder.enter_email') }}" autocomplete="off" class="form-control @error('email') is-invalid @enderror" value="{{ old('email',$merchant->user->email) }}">
                            @error('email')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label for="password">{{ __('levels.password') }} <span class="text-muted">— {{ __('placeholder.Enter_password') }}</span></label>
                            <input id="password" type="password" name="password" placeholder="{{ __('placeholder.Enter_password') }}" autocomplete="new-password" class="form-control @error('password') is-invalid @enderror" value="{{ old('password') }}">
                            @error('password')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                        </div>
                        <div class="form-group">
                            <label for="hub">{{ __('levels.hub') }} <span class="text-danger">*</span></label>
                            <select class="form-control p-1" id="hub" name="hub" required>
                                <option disabled selected>{{ __('menus.select') }} {{ __('hub.title') }}</option>
                                @foreach($hubs as $hub)
                                    <option {{ (old('hub',$merchant->user->hub_id) == $hub->id) ? 'selected' : '' }} value="{{ $hub->id }}">{{ $hub->name }}</option>
                                @endforeach
                            </select>
                            @error('hub')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                        </div>
                        <div class="form-group">
                            <label for="status">{{ __('levels.status') }} <span class="text-danger">*</span></label>
                            <select name="status" class="form-control @error('status') is-invalid @enderror">
                                @foreach(trans('status') as $key => $status)
                                    <option value="{{ $key }}" {{ (old('status',$merchant->user->status) == $key) ? 'selected' : '' }}>{{ $status }}</option>
                                @endforeach
                            </select>
                            @error('status')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Documents --}}
        <div class="rl-mx-card">
            <div class="rl-mx-card__head"><i class="ti ti-files"></i><span>{{ __('levels.nid') }} / {{ __('levels.trade_license') }} / {{ __('levels.image') }}</span></div>
            <div class="rl-mx-card__body">
                <div class="row">
                    <div class="col-12 col-md-4">
                        <div class="rl-mx-doc">
                            <div class="rl-mx-doc__label">{{ __('levels.image') }}</div>
                            <a href="{{ $merchant->user->image }}" target="_blank" class="rl-mx-doc__preview">
                                <img src="{{ $merchant->user->image }}" alt="image">
                            </a>
                            <input id="image_id" type="file" name="image_id" class="form-control @error('image_id') is-invalid @enderror">
                            @error('image_id')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="rl-mx-doc">
                            <div class="rl-mx-doc__label">{{ __('levels.nid') }}</div>
                            <a href="{{ static_asset($merchant->nid) }}" target="_blank" class="rl-mx-doc__preview">
                                <img src="{{ static_asset($merchant->nid) }}" alt="nid">
                            </a>
                            <input id="nid" type="file" name="nid" class="form-control @error('nid') is-invalid @enderror">
                            @error('nid')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="rl-mx-doc">
                            <div class="rl-mx-doc__label">{{ __('levels.trade_license') }}</div>
                            <a href="{{ static_asset($merchant->trade) }}" target="_blank" class="rl-mx-doc__preview">
                                <img src="{{ static_asset($merchant->trade) }}" alt="trade license">
                            </a>
                            <input id="trade_license" type="file" name="trade_license" class="form-control @error('trade_license') is-invalid @enderror">
                            @error('trade_license')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Commercial Registration --}}
        <div class="rl-mx-card">
            <div class="rl-mx-card__head"><i class="ti ti-license"></i><span>{{ __('merchant.commercial_registration') }}</span></div>
            <div class="rl-mx-card__body">
                <div class="row">
                    <div class="col-12 col-md-4">
                        <div class="form-group">
                            <label for="cr_number">{{ __('merchant.cr_number') }}</label>
                            <input type="text" id="cr_number" name="cr_number" class="form-control" value="{{ old('cr_number',$merchant->cr_number) }}" placeholder="{{ __('merchant.cr_number') }}">
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="form-group">
                            <label for="cr_expiry">{{ __('merchant.cr_expiry') }}</label>
                            <input type="date" id="cr_expiry" name="cr_expiry" class="form-control" value="{{ old('cr_expiry', optional($merchant->cr_expiry)->format('Y-m-d')) }}">
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="form-group">
                            <label for="tax_number">{{ __('merchant.tax_number') }}</label>
                            <input type="text" id="tax_number" name="tax_number" class="form-control" value="{{ old('tax_number',$merchant->tax_number) }}" placeholder="{{ __('merchant.tax_number') }}">
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="rl-mx-doc">
                            <div class="rl-mx-doc__label">{{ __('merchant.cr_file') }}</div>
                            @if($merchant->cr_file_url)
                                <a href="{{ $merchant->cr_file_url }}" target="_blank" class="rl-mx-doc__preview">
                                    <img src="{{ $merchant->cr_file_url }}" alt="cr">
                                </a>
                            @else
                                <div class="rl-mx-doc__preview rl-mx-doc__preview--empty"><i class="ti ti-file-off"></i></div>
                            @endif
                            <input type="file" name="cr_file" class="form-control">
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="rl-mx-doc">
                            <div class="rl-mx-doc__label">{{ __('merchant.contract_file') }}</div>
                            @if($merchant->contract_file_url)
                                <a href="{{ $merchant->contract_file_url }}" target="_blank" class="rl-mx-doc__preview">
                                    <img src="{{ $merchant->contract_file_url }}" alt="contract">
                                </a>
                            @else
                                <div class="rl-mx-doc__preview rl-mx-doc__preview--empty"><i class="ti ti-file-off"></i></div>
                            @endif
                            <input type="file" name="contract_file" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Owner ID --}}
        <div class="rl-mx-card">
            <div class="rl-mx-card__head"><i class="ti ti-id"></i><span>{{ __('merchant.owner_id') }}</span></div>
            <div class="rl-mx-card__body">
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label for="owner_id_number">{{ __('merchant.owner_id_number') }}</label>
                            <input type="text" id="owner_id_number" name="owner_id_number" class="form-control" value="{{ old('owner_id_number',$merchant->owner_id_number) }}" placeholder="{{ __('merchant.owner_id_number') }}">
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="rl-mx-doc">
                            <div class="rl-mx-doc__label">{{ __('merchant.owner_id_file') }}</div>
                            @if($merchant->owner_id_file_url)
                                <a href="{{ $merchant->owner_id_file_url }}" target="_blank" class="rl-mx-doc__preview">
                                    <img src="{{ $merchant->owner_id_file_url }}" alt="owner id">
                                </a>
                            @else
                                <div class="rl-mx-doc__preview rl-mx-doc__preview--empty"><i class="ti ti-file-off"></i></div>
                            @endif
                            <input type="file" name="owner_id_file" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Classification & Services --}}
        <div class="rl-mx-card">
            <div class="rl-mx-card__head"><i class="ti ti-stars"></i><span>{{ __('merchant.classification_and_services') }}</span></div>
            <div class="rl-mx-card__body">
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label for="classification">{{ __('merchant.classification') }}</label>
                            <select id="classification" name="classification" class="form-control">
                                <option value="">— {{ __('menus.select') }} —</option>
                                @foreach(['regular','premium','express'] as $opt)
                                    <option value="{{ $opt }}" {{ old('classification',$merchant->classification) === $opt ? 'selected' : '' }}>{{ __('merchant.classification_'.$opt) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ __('merchant.services') }}</label>
                            @php $svc = old('services', $merchant->services ?? []); @endphp
                            <div class="rl-mx-checks">
                                @foreach(['last_mile','fulfillment','storage'] as $opt)
                                    <label class="rl-mx-check">
                                        <input type="checkbox" name="services[]" value="{{ $opt }}" {{ in_array($opt, (array) $svc) ? 'checked' : '' }}>
                                        <span>{{ __('merchant.service_'.$opt) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label for="delivery_type">{{ __('merchant.delivery_type') }}</label>
                            <select id="delivery_type" name="delivery_type" class="form-control">
                                <option value="">— {{ __('menus.select') }} —</option>
                                @foreach(['dry','refrigerated'] as $opt)
                                    <option value="{{ $opt }}" {{ old('delivery_type',$merchant->delivery_type) === $opt ? 'selected' : '' }}>{{ __('merchant.delivery_type_'.$opt) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="expected_daily_shipments">{{ __('merchant.expected_daily_shipments') }}</label>
                            <select id="expected_daily_shipments" name="expected_daily_shipments" class="form-control">
                                <option value="">— {{ __('menus.select') }} —</option>
                                @foreach(['20-100','100-500','500+'] as $opt)
                                    <option value="{{ $opt }}" {{ old('expected_daily_shipments',$merchant->expected_daily_shipments) === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Geography (countries + cities coverage) --}}
        @php
            $selectedCountryIds = collect(old('country_ids', $merchant->countries->pluck('id')->all()))->map(fn ($i) => (int) $i)->all();
            $selectedCityIds    = collect(old('city_ids',    $merchant->cities->pluck('id')->all()))->map(fn ($i) => (int) $i)->all();
            $coversAllCities    = (bool) old('covers_all_cities', $merchant->covers_all_cities);
        @endphp
        <div class="rl-mx-card">
            <div class="rl-mx-card__head"><i class="ti ti-globe"></i><span>{{ __('merchant.geography') }}</span></div>
            <div class="rl-mx-card__body">
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label for="country_ids">{{ __('merchant.countries_covered') }} <span class="text-danger">*</span></label>
                            <select id="country_ids" name="country_ids[]" class="form-control select2" multiple required>
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}" {{ in_array($country->id, $selectedCountryIds, true) ? 'selected' : '' }}>{{ $country->name }} @if($country->en_name) — {{ $country->en_name }} @endif</option>
                                @endforeach
                            </select>
                            @error('country_ids') <small class="text-danger">{{ $message }}</small> @enderror
                            @error('country_ids.*') <small class="text-danger">{{ $message }}</small> @enderror
                            <small class="form-text text-muted">{{ __('merchant.countries_covered_help') }}</small>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label>{{ __('merchant.cities_covered') }}</label>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="hidden" name="covers_all_cities" value="0">
                                <input type="checkbox" id="covers_all_cities" name="covers_all_cities" value="1" class="custom-control-input" {{ $coversAllCities ? 'checked' : '' }}>
                                <label class="custom-control-label" for="covers_all_cities">{{ __('merchant.covers_all_cities') }}</label>
                            </div>
                            <select id="city_ids" name="city_ids[]" class="form-control select2" multiple {{ $coversAllCities ? 'disabled' : '' }}>
                                @foreach($cities as $city)
                                    <option value="{{ $city->id }}" {{ in_array($city->id, $selectedCityIds, true) ? 'selected' : '' }} data-country="{{ $city->country_id }}">{{ $city->name }} @if($city->en_name) — {{ $city->en_name }} @endif</option>
                                @endforeach
                            </select>
                            @error('city_ids') <small class="text-danger">{{ $message }}</small> @enderror
                            <small class="form-text text-muted">{{ __('merchant.cities_covered_help') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
        // Toggle city select availability based on the "Cover all cities" checkbox.
        (function () {
            var cb = document.getElementById('covers_all_cities');
            var sel = document.getElementById('city_ids');
            if (!cb || !sel) return;
            cb.addEventListener('change', function () {
                sel.disabled = this.checked;
                if (this.checked) {
                    Array.prototype.forEach.call(sel.options, function (o) { o.selected = false; });
                }
                if (window.jQuery) jQuery(sel).trigger('change');
            });
        })();
        </script>

        {{-- National Address --}}
        <div class="rl-mx-card">
            <div class="rl-mx-card__head"><i class="ti ti-map-pin"></i><span>{{ __('merchant.national_address') }}</span></div>
            <div class="rl-mx-card__body">
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label for="national_address_short_code">{{ __('merchant.national_address_short_code') }}</label>
                            <input type="text" id="national_address_short_code" name="national_address_short_code" class="form-control" value="{{ old('national_address_short_code',$merchant->national_address_short_code) }}" placeholder="{{ __('merchant.national_address_short_code') }}">
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="rl-mx-doc">
                            <div class="rl-mx-doc__label">{{ __('merchant.national_address_file') }}</div>
                            @if($merchant->national_address_file_url)
                                <a href="{{ $merchant->national_address_file_url }}" target="_blank" class="rl-mx-doc__preview">
                                    <img src="{{ $merchant->national_address_file_url }}" alt="national address">
                                </a>
                            @else
                                <div class="rl-mx-doc__preview rl-mx-doc__preview--empty"><i class="ti ti-file-off"></i></div>
                            @endif
                            <input type="file" name="national_address_file" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bank --}}
        <div class="rl-mx-card">
            <div class="rl-mx-card__head"><i class="ti ti-building-bank"></i><span>{{ __('merchant.bank_details') }}</span></div>
            <div class="rl-mx-card__body">
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label for="iban">{{ __('merchant.iban') }}</label>
                            <input type="text" id="iban" name="iban" class="form-control" value="{{ old('iban',$merchant->iban) }}" placeholder="SA00 0000 0000 0000 0000 0000">
                        </div>
                        <div class="form-group">
                            <label for="bank_name">{{ __('merchant.bank_name') }}</label>
                            <input type="text" id="bank_name" name="bank_name" class="form-control" value="{{ old('bank_name',$merchant->bank_name) }}" placeholder="{{ __('merchant.bank_name') }}">
                        </div>
                        <div class="form-group">
                            <label for="swift_code">{{ __('merchant.swift_code') }}</label>
                            <input type="text" id="swift_code" name="swift_code" class="form-control" value="{{ old('swift_code',$merchant->swift_code) }}" placeholder="{{ __('merchant.swift_code') }}">
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="rl-mx-doc">
                            <div class="rl-mx-doc__label">{{ __('merchant.iban_file') }}</div>
                            @if($merchant->iban_file_url)
                                <a href="{{ $merchant->iban_file_url }}" target="_blank" class="rl-mx-doc__preview">
                                    <img src="{{ $merchant->iban_file_url }}" alt="iban">
                                </a>
                            @else
                                <div class="rl-mx-doc__preview rl-mx-doc__preview--empty"><i class="ti ti-file-off"></i></div>
                            @endif
                            <input type="file" name="iban_file" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reference & Payment --}}
        <div class="rl-mx-card">
            <div class="rl-mx-card__head"><i class="ti ti-cash"></i><span>{{ __('levels.reference') }} &amp; {{ __('levels.payment_period') }}</span></div>
            <div class="rl-mx-card__body">
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label for="reference_name">{{ __('levels.reference_name') }}</label>
                            <input type="text" id="reference_name" placeholder="{{ __('levels.reference_name') }}" name="reference_name" class="form-control" value="{{ old('reference_name',$merchant->reference_name) }}">
                        </div>
                        <div class="form-group">
                            <label for="reference_phone">{{ __('levels.reference_phone') }}</label>
                            <input type="number" id="reference_phone" placeholder="{{ __('levels.reference_phone') }}" name="reference_phone" class="form-control" value="{{ old('reference_phone',$merchant->reference_phone) }}">
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label for="payment_period">{{ __('levels.payment_period') }}</label>
                            <input type="text" id="payment_period" placeholder="{{ __('levels.payment_period') }}" name="payment_period" class="form-control" value="{{ old('payment_period',$merchant->payment_period) }}">
                            @error('payment_period')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                        </div>
                        <div class="form-group">
                            <label for="wallet_use_activation">{{ __('levels.wallet_use_activation') }}</label>
                            <select name="wallet_use_activation" class="form-control select2">
                                <option value="{{ App\Enums\Status::ACTIVE }}" @selected($merchant->wallet_use_activation == App\Enums\Status::ACTIVE)>{{ __('levels.active') }}</option>
                                <option value="{{ App\Enums\Status::INACTIVE }}" @selected($merchant->wallet_use_activation == App\Enums\Status::INACTIVE)>{{ __('levels.inactive') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Custom theme (overrides tenant brand for this merchant's portal) --}}
        <div class="rl-mx-card rl-theme-card">
            <div class="rl-mx-card__head"><i class="ti ti-palette"></i><span>{{ __('merchant.custom_theme') }}</span></div>
            <div class="rl-mx-card__body">
                <p class="text-muted" style="font-size:12px;margin:-4px 0 12px">{{ __('merchant.custom_theme_help') }}</p>
                <div class="row">
                    <div class="col-12 col-lg-7">
                        <h6 class="rl-theme-section">{{ __('merchant.theme_section_colors') }}</h6>
                        <div class="rl-theme-grid">
                            @foreach([
                                ['primary_color',      $merchant->primary_color,      '#a21f5c'],
                                ['text_color',         $merchant->text_color,         '#ffffff'],
                                ['sidebar_color',      $merchant->sidebar_color,      '#0f172a'],
                                ['sidebar_text_color', $merchant->sidebar_text_color, '#f1f5f9'],
                                ['topbar_color',       $merchant->topbar_color,       '#ffffff'],
                                ['topbar_text_color',  $merchant->topbar_text_color,  '#0f172a'],
                                ['accent_color',       $merchant->accent_color,       '#0ea5e9'],
                            ] as [$name, $current, $fallback])
                                <div class="form-group">
                                    <label for="{{ $name }}">{{ __('merchant.'.$name) }}</label>
                                    <div class="input-group">
                                        <input type="color" id="{{ $name }}_picker" class="form-control rl-theme-swatch" value="{{ old($name, $current ?: $fallback) }}" data-target="{{ $name }}">
                                        <input type="text" id="{{ $name }}" name="{{ $name }}" class="form-control rl-theme-hex" maxlength="7" pattern="^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$" placeholder="{{ __('merchant.theme_inherit') }}" value="{{ old($name, $current) }}" data-picker="{{ $name }}_picker">
                                    </div>
                                    @error($name)<small class="text-danger mt-2">{{ $message }}</small>@enderror
                                </div>
                            @endforeach
                        </div>

                        <h6 class="rl-theme-section">{{ __('merchant.theme_section_layout') }}</h6>
                        <div class="rl-theme-grid">
                            <div class="form-group">
                                <label for="sidebar_style">{{ __('merchant.sidebar_style') }}</label>
                                <select id="sidebar_style" name="sidebar_style" class="form-control rl-theme-select">
                                    <option value="">{{ __('merchant.theme_inherit') }}</option>
                                    @foreach(['dark','light','brand'] as $k)
                                        <option value="{{ $k }}" @selected(old('sidebar_style', $merchant->sidebar_style) === $k)>{{ __('merchant.sidebar_style_'.$k) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="font_family">{{ __('merchant.font_family') }}</label>
                                <select id="font_family" name="font_family" class="form-control rl-theme-select">
                                    <option value="">{{ __('merchant.theme_inherit') }}</option>
                                    @foreach(['inter','cairo','tajawal','roboto','system'] as $k)
                                        <option value="{{ $k }}" @selected(old('font_family', $merchant->font_family) === $k)>{{ __('merchant.font_'.$k) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="border_radius">{{ __('merchant.border_radius') }}</label>
                                <select id="border_radius" name="border_radius" class="form-control rl-theme-select">
                                    <option value="">{{ __('merchant.theme_inherit') }}</option>
                                    @foreach(['sharp','default','rounded'] as $k)
                                        <option value="{{ $k }}" @selected(old('border_radius', $merchant->border_radius) === $k)>{{ __('merchant.border_radius_'.$k) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="density">{{ __('merchant.density') }}</label>
                                <select id="density" name="density" class="form-control rl-theme-select">
                                    <option value="">{{ __('merchant.theme_inherit') }}</option>
                                    @foreach(['comfortable','dense'] as $k)
                                        <option value="{{ $k }}" @selected(old('density', $merchant->density) === $k)>{{ __('merchant.density_'.$k) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <h6 class="rl-theme-section">{{ __('merchant.theme_section_logos') }}</h6>
                        <div class="rl-theme-grid">
                            <div class="form-group">
                                <label for="logo">{{ __('merchant.logo_dark') }}</label>
                                @if($merchant->logo_url)
                                    <div class="mb-2"><img src="{{ $merchant->logo_url }}" alt="logo" class="rl-theme-logo-preview" style="background:#f4f4f5"></div>
                                @endif
                                <input id="logo" type="file" name="logo" accept="image/*" class="form-control">
                                @error('logo')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                            </div>
                            <div class="form-group">
                                <label for="light_logo">{{ __('merchant.logo_light') }}</label>
                                @if($merchant->light_logo_url)
                                    <div class="mb-2"><img src="{{ $merchant->light_logo_url }}" alt="light logo" class="rl-theme-logo-preview" style="background:#29245a"></div>
                                @endif
                                <input id="light_logo" type="file" name="light_logo" accept="image/*" class="form-control">
                                @error('light_logo')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                            </div>
                            <div class="form-group">
                                <label for="favicon">{{ __('merchant.favicon') }}</label>
                                @if($merchant->favicon_url)
                                    <div class="mb-2"><img src="{{ $merchant->favicon_url }}" alt="favicon" class="rl-theme-logo-preview" style="background:#f4f4f5;height:32px;width:32px"></div>
                                @endif
                                <input id="favicon" type="file" name="favicon" accept=".ico,image/*" class="form-control">
                                <small class="text-muted">{{ __('merchant.favicon_help') }}</small>
                                @error('favicon')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- Live preview --}}
                    <div class="col-12 col-lg-5">
                        <div class="rl-theme-preview-wrap">
                            <h6 class="rl-theme-section">{{ __('merchant.theme_preview') }}</h6>
                            <div id="rl-theme-preview" class="rl-theme-preview" data-business="{{ $merchant->business_name }}">
                                <aside class="rl-tp-side">
                                    <div class="rl-tp-side-head">
                                        <span class="rl-tp-logo">{{ strtoupper(mb_substr($merchant->business_name, 0, 1)) }}</span>
                                        <span class="rl-tp-side-name">{{ $merchant->business_name }}</span>
                                    </div>
                                    <ul class="rl-tp-nav">
                                        <li class="rl-tp-active">{{ __('merchant.dashboard') }}</li>
                                        <li>{{ __('merchant.shop') }}</li>
                                        <li>{{ __('merchant.payment_info') }}</li>
                                    </ul>
                                </aside>
                                <div class="rl-tp-main">
                                    <header class="rl-tp-top">
                                        <span class="rl-tp-search">{{ __('merchant.search_placeholder') ?? 'Search…' }}</span>
                                        <span class="rl-tp-avatar">{{ strtoupper(mb_substr($merchant->user->name ?? 'M', 0, 1)) }}</span>
                                    </header>
                                    <div class="rl-tp-body">
                                        <div class="rl-tp-card"><span class="rl-tp-link">{{ __('merchant.dashboard') }}</span></div>
                                        <div class="rl-tp-card rl-tp-card--ghost"></div>
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">{{ __('merchant.custom_theme_help') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- COD charges --}}
        @if(count($merchant->cod_charges))
        <div class="rl-mx-card">
            <div class="rl-mx-card__head"><i class="ti ti-receipt"></i><span>{{ __('levels.cod_charge') }}</span></div>
            <div class="rl-mx-card__body">
                <div class="row">
                    @foreach($merchant->cod_charges as $key => $charge)
                        <input type="hidden" value="{{ $key }}" name="area[]">
                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label class="select-input">{{ str_replace('_', ' ', ucwords($key)) }}</label>
                                <input type="number" name="charge[{{ $key }}]" autocomplete="off" class="form-control" value="{{ old('charge.'.$key,$charge) }}" placeholder="charge">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Sticky save bar --}}
        <div class="rl-mx-savebar">
            <a href="{{ route('merchant.index') }}" class="btn btn-secondary"><i class="ti ti-x"></i> {{ __('levels.cancel') }}</a>
            <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> {{ __('levels.save_change') }}</button>
        </div>
    </form>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.rl-merchant-edit { padding-bottom: 96px; }
.rl-mx-card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(0,0,0,.04);
    margin-bottom: 18px;
    overflow: hidden;
}
.rl-mx-card__head {
    display: flex; align-items: center; gap: 10px;
    padding: 14px 20px;
    background: #fafbfc;
    border-bottom: 1px solid #eef0f2;
    font-weight: 600; color: #1f2937;
    font-size: 15px;
}
.rl-mx-card__head i { font-size: 20px; color: #a8262c; }
.rl-mx-card__body { padding: 20px; }

/* Header summary */
.rl-mx-card--head .rl-mx-head {
    display: flex; align-items: center; gap: 16px;
    padding: 18px 20px;
}
.rl-mx-avatar img {
    width: 64px; height: 64px; border-radius: 50%;
    object-fit: cover; border: 2px solid #f1f3f5;
}
.rl-mx-meta { flex: 1; min-width: 0; }
.rl-mx-name { font-size: 18px; font-weight: 700; color: #111827; }
.rl-mx-sub  {
    color: #6b7280; font-size: 13px; margin-top: 4px;
    display: flex; align-items: center; flex-wrap: wrap; gap: 6px;
}
.rl-mx-sub i { font-size: 14px; }
.rl-mx-dot { width: 4px; height: 4px; background: #d1d5db; border-radius: 50%; display: inline-block; }
.rl-mx-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.rl-mx-actions .btn i { margin-right: 4px; }

/* Document tiles */
.rl-mx-doc {
    border: 1px dashed #d1d5db;
    border-radius: 10px;
    padding: 14px;
    text-align: center;
    background: #fafbfc;
}
.rl-mx-doc__label { font-weight: 600; color: #374151; margin-bottom: 10px; }
.rl-mx-doc__preview {
    display: block;
    width: 100%; aspect-ratio: 16 / 10;
    overflow: hidden; border-radius: 8px;
    background: #fff; border: 1px solid #e5e7eb;
    margin-bottom: 10px;
}
.rl-mx-doc__preview img {
    width: 100%; height: 100%; object-fit: contain;
}
.rl-mx-doc__preview--empty {
    display: flex; align-items: center; justify-content: center;
    color: #9ca3af; font-size: 28px;
}
.rl-mx-checks {
    display: flex; flex-wrap: wrap; gap: 8px;
}
.rl-mx-check {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 12px;
    border: 1px solid #d1d5db; border-radius: 999px;
    background: #fff; cursor: pointer;
    font-size: 13px; margin: 0;
}
.rl-mx-check input { margin: 0; }
.rl-mx-check:has(input:checked) {
    background: #a8262c0d; border-color: #a8262c; color: #a8262c; font-weight: 600;
}

/* Auto-direction for bilingual text fields: Arabic flows RTL, English/numbers flow LTR */
.rl-merchant-edit input[type=text],
.rl-merchant-edit input[type=email],
.rl-merchant-edit input[type=number],
.rl-merchant-edit input[type=password],
.rl-merchant-edit textarea {
    unicode-bidi: plaintext;
    text-align: start;
}

/* Sticky save bar */
.rl-mx-savebar {
    position: sticky; bottom: 0; z-index: 5;
    margin-top: 18px;
    background: rgba(255,255,255,.95);
    backdrop-filter: blur(6px);
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 12px 16px;
    display: flex; justify-content: flex-end; gap: 10px;
    box-shadow: 0 -4px 16px rgba(0,0,0,.04);
}
.rl-mx-savebar .btn i { margin-right: 4px; }

@media (max-width: 768px) {
    .rl-mx-card--head .rl-mx-head { flex-wrap: wrap; }
    .rl-mx-actions { width: 100%; }
    .rl-mx-actions .btn { flex: 1; }
}

/* ===== Custom theme section ===== */
.rl-theme-card .rl-theme-section { font-size: 12px; font-weight: 700; color: #475467; text-transform: uppercase; letter-spacing: .04em; margin: 8px 0 12px; }
.rl-theme-card .rl-theme-section:not(:first-child) { margin-top: 20px; padding-top: 16px; border-top: 1px dashed #e5e7eb; }
.rl-theme-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 16px; }
@media (max-width: 575px) { .rl-theme-grid { grid-template-columns: 1fr; } }
.rl-theme-swatch { max-width: 60px; padding: 4px; cursor: pointer; }
.rl-theme-hex { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
.rl-theme-logo-preview { height: 48px; padding: 6px; border-radius: 6px; }

/* Live preview canvas */
.rl-theme-preview-wrap { position: sticky; top: 20px; }
.rl-theme-preview {
    display: flex; height: 260px; border-radius: 12px; overflow: hidden;
    border: 1px solid #e5e7eb; background: #f8fafc;
    --tp-sidebar-bg: #0f172a; --tp-sidebar-fg: #f1f5f9; --tp-sidebar-active-bg: #a21f5c; --tp-sidebar-active-fg: #ffffff;
    --tp-topbar-bg: #ffffff; --tp-topbar-fg: #0f172a; --tp-topbar-tint: rgba(15,23,42,0.06);
    --tp-accent: #0ea5e9; --tp-radius: 8px;
    font-family: inherit;
}
.rl-theme-preview .rl-tp-side { width: 92px; flex-shrink: 0; background: var(--tp-sidebar-bg); color: var(--tp-sidebar-fg); display: flex; flex-direction: column; }
.rl-theme-preview .rl-tp-side-head { display: flex; align-items: center; gap: 6px; padding: 12px 10px; font-size: 11px; font-weight: 600; border-bottom: 1px solid rgba(255,255,255,.08); }
.rl-theme-preview .rl-tp-logo { display: inline-grid; place-items: center; width: 22px; height: 22px; border-radius: calc(var(--tp-radius) - 2px); background: var(--tp-sidebar-active-bg); color: var(--tp-sidebar-active-fg); font-size: 11px; font-weight: 700; }
.rl-theme-preview .rl-tp-side-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rl-theme-preview .rl-tp-nav { list-style: none; padding: 8px; margin: 0; font-size: 10px; }
.rl-theme-preview .rl-tp-nav li { padding: 6px 8px; border-radius: var(--tp-radius); opacity: .7; margin-bottom: 4px; }
.rl-theme-preview .rl-tp-active { background: var(--tp-sidebar-active-bg); color: var(--tp-sidebar-active-fg); opacity: 1; font-weight: 600; }
.rl-theme-preview .rl-tp-main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
.rl-theme-preview .rl-tp-top { height: 42px; flex-shrink: 0; background: var(--tp-topbar-bg); color: var(--tp-topbar-fg); display: flex; align-items: center; gap: 8px; padding: 0 10px; border-bottom: 1px solid rgba(0,0,0,.06); }
.rl-theme-preview .rl-tp-search { flex: 1; background: var(--tp-topbar-tint); border-radius: var(--tp-radius); padding: 4px 8px; font-size: 10px; opacity: .7; }
.rl-theme-preview .rl-tp-avatar { width: 22px; height: 22px; border-radius: 50%; background: var(--tp-topbar-tint); display: inline-grid; place-items: center; font-size: 10px; font-weight: 600; }
.rl-theme-preview .rl-tp-body { flex: 1; padding: 12px; display: flex; flex-direction: column; gap: 8px; }
.rl-theme-preview .rl-tp-card { background: #fff; border: 1px solid #e5e7eb; border-radius: var(--tp-radius); padding: 10px; font-size: 11px; }
.rl-theme-preview .rl-tp-card--ghost { background: #fff; flex: 1; }
.rl-theme-preview .rl-tp-link { color: var(--tp-accent); font-weight: 600; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function () {
    var preview = document.getElementById('rl-theme-preview');
    if (!preview) return;

    // Two-way sync: color picker <-> hex input. Empty hex = clear to inherit (picker shows fallback).
    document.querySelectorAll('.rl-theme-swatch').forEach(function (picker) {
        var hex = document.getElementById(picker.dataset.target);
        picker.addEventListener('input', function () { hex.value = picker.value; update(); });
    });
    document.querySelectorAll('.rl-theme-hex').forEach(function (hex) {
        var picker = document.getElementById(hex.dataset.picker);
        hex.addEventListener('input', function () {
            if (/^#([A-Fa-f0-9]{6})$/.test(hex.value)) picker.value = hex.value;
            update();
        });
    });
    document.querySelectorAll('.rl-theme-select').forEach(function (sel) {
        sel.addEventListener('change', update);
    });

    function val(id, fallback) {
        var el = document.getElementById(id);
        if (!el) return fallback;
        var v = (el.value || '').trim();
        return v ? v : fallback;
    }

    var FONTS = {
        inter: '"Inter", sans-serif',
        cairo: '"Cairo", "Tajawal", sans-serif',
        tajawal: '"Tajawal", sans-serif',
        roboto: '"Roboto", sans-serif',
        system: 'ui-sans-serif, system-ui, -apple-system, sans-serif',
    };
    var RADII = { sharp: '2px', default: '8px', rounded: '14px' };

    function update() {
        var primary       = val('primary_color',      '#a21f5c');
        var textOn        = val('text_color',         '#ffffff');
        var sidebarBg     = val('sidebar_color',      '');
        var sidebarFg     = val('sidebar_text_color', '');
        var topbarBg      = val('topbar_color',       '');
        var topbarFg      = val('topbar_text_color',  '');
        var accent        = val('accent_color',       primary);
        var sidebarStyle  = val('sidebar_style',      '');
        var font          = val('font_family',        '');
        var radius        = val('border_radius',      '');

        if (!sidebarBg) sidebarBg = sidebarStyle === 'light' ? '#ffffff' : (sidebarStyle === 'brand' ? primary : '#0f172a');
        if (!sidebarFg) sidebarFg = sidebarStyle === 'light' ? '#0f172a' : (sidebarStyle === 'brand' ? textOn : '#f1f5f9');
        if (!topbarBg)  topbarBg  = primary;
        if (!topbarFg)  topbarFg  = textOn;

        preview.style.setProperty('--tp-sidebar-bg', sidebarBg);
        preview.style.setProperty('--tp-sidebar-fg', sidebarFg);
        preview.style.setProperty('--tp-sidebar-active-bg', primary);
        preview.style.setProperty('--tp-sidebar-active-fg', textOn);
        preview.style.setProperty('--tp-topbar-bg', topbarBg);
        preview.style.setProperty('--tp-topbar-fg', topbarFg);
        preview.style.setProperty('--tp-topbar-tint', 'rgba(255,255,255,0.2)');
        preview.style.setProperty('--tp-accent', accent);
        preview.style.setProperty('--tp-radius', RADII[radius] || RADII.default);
        preview.style.fontFamily = FONTS[font] || '';
    }
    update();
})();
</script>
@endpush
