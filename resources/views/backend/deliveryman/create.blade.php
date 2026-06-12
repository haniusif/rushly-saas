@extends('backend.partials.master')

@section('title')
    {{ __('deliveryman.title') }} {{ __('levels.add') }}
@endsection

@push('css')
<style>
    /* ===== Form chrome ===== */
    .rl-section-card {
        margin-bottom: 18px;
        border: 1px solid #e6e8ee;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(16,24,40,.04);
    }
    .rl-section-card .card-body { padding: 22px 24px; }
    .rl-section-head {
        display: flex; align-items: center; gap: 10px;
        font-weight: 600; color: #1f2937; font-size: 16px;
        margin: 0;
    }
    .rl-section-head .num {
        display: inline-flex; align-items: center; justify-content: center;
        width: 28px; height: 28px; border-radius: 50%;
        background: #eaf3ff; color: #0066ff; font-size: 13px; font-weight: 700;
    }
    .rl-section-head .badge { font-size: 12px; }
    .rl-required { color: #dc3545; margin-inline-start: 4px; }
    .rl-help { color: #6c757d; font-size: 12px; }
    .rl-section-card label {
        font-size: 12px; font-weight: 600;
        color: #4b5563; letter-spacing: .2px;
        text-transform: uppercase;
        margin-bottom: 6px;
    }
    .rl-section-card .form-control {
        border-radius: 10px;
        border: 1px solid #d0d5dd;
        padding: 10px 12px;
        font-size: 14px;
        transition: border-color .15s, box-shadow .15s;
    }
    .rl-section-card .form-control:focus {
        border-color: #2680ff;
        box-shadow: 0 0 0 3px rgba(38,128,255,.18);
    }
    .rl-section-card .form-control.is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 3px rgba(220,53,69,.12);
    }
    .rl-section-card hr { border: 0; height: 1px; background: #eef0f4; margin: 14px 0 18px; }
    .rl-uploads-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; }

    /* ===== Conditional blocks (driver_type) =====
       Exclude wizard steps so the wizard's own .is-active gate wins on
       elements that are both wizard step AND conditional block (the bank step). */
    .rl-conditional-block:not(.rl-wizard-step) { display: none; }
    .rl-conditional-block:not(.rl-wizard-step).is-visible { display: block; }

    /* ===== Wizard shell + stepper ===== */
    .rl-wizard-shell {
        background: #fff; border: 1px solid #e6e8ee; border-radius: 14px;
        padding: 16px 18px; margin-bottom: 18px;
        box-shadow: 0 1px 2px rgba(16,24,40,.04);
    }
    .rl-wizard-progressbar {
        height: 6px; background: #eef0f4; border-radius: 999px; overflow: hidden;
        margin-bottom: 14px;
    }
    .rl-wizard-progressbar > i {
        display: block; height: 100%; width: 0%;
        background: linear-gradient(90deg, #2680ff, #00c4ff);
        border-radius: 999px; transition: width .35s ease;
    }
    .rl-wizard-stepper {
        display: flex; flex-wrap: wrap; align-items: center;
        gap: 6px; margin: 0;
    }
    .rl-wizard-stepper .rl-wizard-pill {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 8px 14px; border-radius: 999px;
        background: #f4f6fa; color: #475467;
        font-size: 12.5px; font-weight: 600; white-space: nowrap;
        cursor: pointer; user-select: none;
        transition: background-color .15s, color .15s, transform .12s ease;
    }
    .rl-wizard-stepper .rl-wizard-pill:hover { background: #e6ebf3; }
    .rl-wizard-stepper .rl-wizard-pill:active { transform: scale(.98); }
    .rl-wizard-stepper .rl-wizard-pill .num {
        display: inline-flex; align-items: center; justify-content: center;
        width: 22px; height: 22px; border-radius: 50%;
        background: #d0d5dd; color: #fff; font-size: 11px; font-weight: 700;
    }
    .rl-wizard-stepper .rl-wizard-pill.is-active {
        background: linear-gradient(135deg, #2680ff, #00b6ff);
        color: #fff;
        box-shadow: 0 4px 14px rgba(38,128,255,.32);
    }
    .rl-wizard-stepper .rl-wizard-pill.is-active .num { background: rgba(255,255,255,.28); }
    .rl-wizard-stepper .rl-wizard-pill.is-done {
        background: #e6f7ec; color: #117a3d;
    }
    .rl-wizard-stepper .rl-wizard-pill.is-done .num { background: #2ea860; color: #fff; }
    .rl-wizard-stepper .rl-wizard-pill.is-skipped { display: none; }
    .rl-wizard-stepper .rl-wizard-pill.has-error {
        background: #fdecee; color: #b42318;
    }
    .rl-wizard-stepper .rl-wizard-pill.has-error .num { background: #dc3545; color: #fff; }

    .rl-wizard-step { display: none; }
    .rl-wizard-step.is-active { display: block; animation: rl-fade .22s ease-out; }
    @keyframes rl-fade {
        from { opacity: 0; transform: translateY(4px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .rl-wizard-nav {
        display: flex; justify-content: space-between; align-items: center;
        gap: 12px; padding: 14px 0 28px 0;
        flex-wrap: wrap;
    }
    .rl-wizard-progress { color: #6c757d; font-size: 13px; font-weight: 500; }
    .rl-wizard-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .rl-wizard-actions .btn { padding: 9px 16px; border-radius: 10px; font-weight: 600; }

    [dir="rtl"] .rl-wizard-nav .fa-chevron-left,
    [dir="rtl"] .rl-wizard-nav .fa-chevron-right { transform: scaleX(-1); }

    /* ===== Summary panel ===== */
    .rl-summary {
        position: sticky; top: 80px;
        background: #fff; border: 1px solid #e6e8ee; border-radius: 14px;
        padding: 20px;
        box-shadow: 0 1px 2px rgba(16,24,40,.04);
    }
    .rl-summary h6 {
        font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase;
        color: #98a2b3; margin: 14px 0 6px;
    }
    .rl-summary .rl-avatar {
        width: 64px; height: 64px; border-radius: 50%;
        background: linear-gradient(135deg, #2680ff, #00b6ff);
        color: #fff; display: flex; align-items: center; justify-content: center;
        font-size: 24px; font-weight: 700;
        margin: 0 auto 10px;
    }
    .rl-summary .rl-name {
        font-size: 16px; font-weight: 700; color: #1f2937; text-align: center;
    }
    .rl-summary .rl-meta { color: #667085; font-size: 12px; text-align: center; }
    .rl-summary .rl-row {
        display: flex; justify-content: space-between; align-items: baseline;
        gap: 8px; padding: 5px 0;
        font-size: 13px;
    }
    .rl-summary .rl-row .lbl { color: #667085; }
    .rl-summary .rl-row .val { color: #1f2937; font-weight: 600; text-align: end; word-break: break-word; }
    .rl-summary .rl-badge {
        display: inline-block; padding: 3px 10px; border-radius: 999px;
        font-size: 11px; font-weight: 700;
    }
    .rl-summary .rl-badge--type { background: #eef4ff; color: #1d4fb8; }
    .rl-summary .rl-badge--active { background: #e6f7ec; color: #117a3d; }
    .rl-summary .rl-badge--suspended { background: #fff3cd; color: #855e00; }
    .rl-summary .rl-badge--leave { background: #fff8e1; color: #856404; }
    .rl-summary .rl-badge--terminated { background: #fdecee; color: #b42318; }
    .rl-summary .rl-warning {
        background: #fff3cd; color: #855e00; border-radius: 10px;
        padding: 8px 10px; font-size: 12px; margin-top: 8px;
        display: none;
    }
    .rl-summary .rl-warning.is-visible { display: block; }
    .rl-summary .rl-empty { color: #98a2b3; font-style: italic; font-size: 13px; }

    @media (max-width: 640px) {
        .rl-wizard-stepper .rl-wizard-pill { font-size: 11px; padding: 6px 10px; }
        .rl-wizard-nav { justify-content: center; }
        .rl-wizard-progress { width: 100%; text-align: center; order: -1; }
        .rl-summary { position: static; }
    }
</style>
@endpush

@section('maincontent')
<div class="container-fluid dashboard-content">

    {{-- Breadcrumb --}}
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('deliveryman.index') }}" class="breadcrumb-link">{{ __('deliveryman.title') }}</a></li>
                            <li class="breadcrumb-item active">{{ __('levels.create') }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('deliveryman.store') }}" method="POST" enctype="multipart/form-data" id="deliveryman-form">
        @csrf

        {{-- Wizard stepper (clickable pills + thin progress bar) --}}
        <div class="rl-wizard-shell">
            <div class="rl-wizard-progressbar"><i id="rl-wizard-bar"></i></div>
            <div class="rl-wizard-stepper" id="rl-wizard-stepper">
                <span class="rl-wizard-pill" data-go="1"><span class="num">1</span> {{ __('deliveryman.section_basic') }}</span>
                <span class="rl-wizard-pill" data-go="2"><span class="num">2</span> {{ __('deliveryman.section_id') }}</span>
                <span class="rl-wizard-pill" data-go="3"><span class="num">3</span> {{ __('deliveryman.section_address') }}</span>
                <span class="rl-wizard-pill" data-go="4"><span class="num">4</span> {{ __('deliveryman.section_employment') }}</span>
                <span class="rl-wizard-pill" data-go="5"><span class="num">5</span> {{ __('deliveryman.section_license') }}</span>
                <span class="rl-wizard-pill" data-go="6" data-show-for="freelancer"><span class="num">6</span> {{ __('deliveryman.section_bank') }}</span>
                <span class="rl-wizard-pill" data-go="7"><span class="num">7</span> {{ __('deliveryman.section_documents') }}</span>
            </div>
        </div>

        <div class="row">
        <div class="col-lg-8 col-md-12">

        {{-- 1. Basic identity --}}
        <div class="card rl-section-card rl-wizard-step" data-step="1">
            <div class="card-body">
                <h4 class="rl-section-head">
                    <span class="num">1</span> {{ __('deliveryman.section_basic') }}
                </h4>
                <hr>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>{{ __('deliveryman.full_name') }} <span class="rl-required">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ __('deliveryman.name_en') }}</label>
                        <input type="text" name="name_en" class="form-control" value="{{ old('name_en') }}">
                    </div>

                    <div class="col-md-6 form-group">
                        <label>{{ __('deliveryman.mobile') }} <span class="rl-required">*</span></label>
                        <input type="text" name="mobile" class="form-control @error('mobile') is-invalid @enderror" value="{{ old('mobile') }}" required>
                        @error('mobile') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ __('deliveryman.alt_mobile') }}</label>
                        <input type="text" name="alt_mobile" class="form-control" value="{{ old('alt_mobile') }}">
                    </div>

                    <div class="col-md-6 form-group">
                        <label>{{ __('deliveryman.email') }} <span class="rl-required">*</span></label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                        @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ __('deliveryman.password') }} <span class="rl-required">*</span></label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                        @error('password') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-4 form-group">
                        <label>{{ __('deliveryman.gender') }}</label>
                        <select name="gender" class="form-control">
                            <option value="">—</option>
                            <option value="male"   {{ old('gender') === 'male' ? 'selected' : '' }}>{{ __('deliveryman.gender_male') }}</option>
                            <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>{{ __('deliveryman.gender_female') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ __('deliveryman.dob') }}</label>
                        <input type="date" name="dob" class="form-control" value="{{ old('dob') }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ __('deliveryman.nationality') }}</label>
                        <select name="nationality" class="form-control" data-rl-search="1">
                            <option value="">—</option>
                            @php $isAr = app()->getLocale() === 'ar'; @endphp
                            @foreach($nationalities as $c)
                                @php $label = $isAr ? ($c->name ?: $c->en_name) : ($c->en_name ?: $c->name); @endphp
                                <option value="{{ $label }}" {{ old('nationality') === $label ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if($nationalities->isEmpty())
                            <small class="rl-help">{{ __('deliveryman.nationality_empty') }}</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. ID --}}
        <div class="card rl-section-card rl-wizard-step" data-step="2">
            <div class="card-body">
                <h4 class="rl-section-head">
                    <span class="num">2</span> {{ __('deliveryman.section_id') }}
                </h4>
                <hr>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>{{ __('deliveryman.id_type') }}</label>
                        <select name="id_type" class="form-control">
                            <option value="">—</option>
                            <option value="national_id" {{ old('id_type') === 'national_id' ? 'selected' : '' }}>{{ __('deliveryman.id_type_national') }}</option>
                            <option value="iqama"       {{ old('id_type') === 'iqama' ? 'selected' : '' }}>{{ __('deliveryman.id_type_iqama') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ __('deliveryman.id_number') }}</label>
                        <input type="text" name="id_number" class="form-control" value="{{ old('id_number') }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ __('deliveryman.id_expiry') }}</label>
                        <input type="date" name="id_expiry" class="form-control" value="{{ old('id_expiry') }}">
                    </div>

                    <div class="col-md-6 form-group">
                        <label>{{ __('deliveryman.id_image') }}</label>
                        <input type="file" name="id_image_id" class="form-control" accept="image/*">
                        <small class="rl-help">{{ __('deliveryman.file_help') }}</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Address --}}
        <div class="card rl-section-card rl-wizard-step" data-step="3">
            <div class="card-body">
                <h4 class="rl-section-head">
                    <span class="num">3</span> {{ __('deliveryman.section_address') }}
                </h4>
                <hr>
                <div class="row">
                    <div class="col-md-12 form-group">
                        <label>{{ __('deliveryman.address') }} <span class="rl-required">*</span></label>
                        <input type="text" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}" required>
                        @error('address') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ __('deliveryman.district') }}</label>
                        <input type="text" name="district" class="form-control" value="{{ old('district') }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ __('deliveryman.short_national_address') }}</label>
                        <input type="text" name="short_national_address" class="form-control" value="{{ old('short_national_address') }}" placeholder="{{ __('deliveryman.short_national_address_placeholder') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. Employment --}}
        <div class="card rl-section-card rl-wizard-step" data-step="4">
            <div class="card-body">
                <h4 class="rl-section-head">
                    <span class="num">4</span> {{ __('deliveryman.section_employment') }}
                </h4>
                <hr>
                <div class="row">
                    <div class="col-md-12 form-group">
                        <label>{{ __('deliveryman.driver_type') }} <span class="rl-required">*</span></label>
                        <div class="btn-group btn-group-toggle" data-toggle="buttons">
                            @php $dt = old('driver_type', 'company_courier'); @endphp
                            <label class="btn btn-outline-primary {{ $dt === 'freelancer' ? 'active' : '' }}">
                                <input type="radio" name="driver_type" value="freelancer" {{ $dt === 'freelancer' ? 'checked' : '' }}> {{ __('deliveryman.driver_type_freelancer') }}
                            </label>
                            <label class="btn btn-outline-primary {{ $dt === 'outsourced' ? 'active' : '' }}">
                                <input type="radio" name="driver_type" value="outsourced" {{ $dt === 'outsourced' ? 'checked' : '' }}> {{ __('deliveryman.driver_type_outsourced') }}
                            </label>
                            <label class="btn btn-outline-primary {{ $dt === 'company_courier' ? 'active' : '' }}">
                                <input type="radio" name="driver_type" value="company_courier" {{ $dt === 'company_courier' ? 'checked' : '' }}> {{ __('deliveryman.driver_type_company_courier') }}
                            </label>
                        </div>
                        @error('driver_type') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-6 form-group rl-conditional-block" data-show-for="company_courier">
                        <label>{{ __('deliveryman.employee_number') }}</label>
                        <input type="text" name="employee_number" class="form-control" value="{{ old('employee_number') }}">
                    </div>

                    <div class="col-md-6 form-group rl-conditional-block" data-show-for="outsourced">
                        <label>{{ __('deliveryman.supplier_company') }} <span class="rl-required">*</span></label>
                        <select name="supplier_company_id" class="form-control">
                            <option value="">—</option>
                            @foreach($supplierCompanies as $sc)
                                <option value="{{ $sc->id }}" {{ old('supplier_company_id') == $sc->id ? 'selected' : '' }}>{{ $sc->name }}</option>
                            @endforeach
                        </select>
                        @if($supplierCompanies->isEmpty())
                            <small class="rl-help">{{ __('deliveryman.supplier_company_empty') }}</small>
                        @endif
                    </div>

                    <div class="col-md-6 form-group">
                        <label>{{ __('deliveryman.joining_date') }}</label>
                        <input type="date" name="joining_date" class="form-control" value="{{ old('joining_date') }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ __('deliveryman.contract_end_date') }}</label>
                        <input type="date" name="contract_end_date" class="form-control" value="{{ old('contract_end_date') }}">
                        <small class="rl-help">{{ __('deliveryman.contract_expiry_hint') }}</small>
                    </div>

                    <div class="col-md-6 form-group">
                        <label>{{ __('deliveryman.status') }} <span class="rl-required">*</span></label>
                        <select name="status" class="form-control" required>
                            <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>{{ __('deliveryman.status_active') }}</option>
                            <option value="2" {{ old('status') == 2 ? 'selected' : '' }}>{{ __('deliveryman.status_suspended') }}</option>
                            <option value="3" {{ old('status') == 3 ? 'selected' : '' }}>{{ __('deliveryman.status_leave') }}</option>
                            <option value="4" {{ old('status') == 4 ? 'selected' : '' }}>{{ __('deliveryman.status_terminated') }}</option>
                        </select>
                    </div>

                    <div class="col-md-6 form-group">
                        <label>{{ __('deliveryman.hub') }} <span class="rl-required">*</span></label>
                        <select name="hub_id" class="form-control @error('hub_id') is-invalid @enderror" required>
                            <option value="">—</option>
                            @foreach($hubs as $hub)
                                <option value="{{ $hub->id }}" {{ old('hub_id') == $hub->id ? 'selected' : '' }}>{{ $hub->name }}</option>
                            @endforeach
                        </select>
                        @error('hub_id') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-6 form-group">
                        <label>{{ __('deliveryman.direct_manager') }}</label>
                        <select name="direct_manager_id" class="form-control">
                            <option value="">—</option>
                            @foreach($managers as $m)
                                <option value="{{ $m->id }}" {{ old('direct_manager_id') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ __('deliveryman.operational_area') }}</label>
                        <select name="operational_area_id" class="form-control">
                            <option value="">—</option>
                            @foreach($operationalAreas as $oa)
                                <option value="{{ $oa->id }}" {{ old('operational_area_id') == $oa->id ? 'selected' : '' }}>{{ $oa->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 form-group">
                        <label>{{ __('deliveryman.salary') }}</label>
                        <input type="number" step="any" name="salary" class="form-control" value="{{ old('salary') }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ __('levels.delivery_charge') }}</label>
                        <input type="number" step="any" name="delivery_charge" class="form-control" value="{{ old('delivery_charge') }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ __('levels.pickup_charge') }}</label>
                        <input type="number" step="any" name="pickup_charge" class="form-control" value="{{ old('pickup_charge') }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ __('levels.return_charge') }}</label>
                        <input type="number" step="any" name="return_charge" class="form-control" value="{{ old('return_charge') }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>{{ __('levels.opening_balance') }}</label>
                        <input type="number" step="any" name="opening_balance" class="form-control" value="{{ old('opening_balance') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- 5. License --}}
        <div class="card rl-section-card rl-wizard-step" data-step="5">
            <div class="card-body">
                <h4 class="rl-section-head">
                    <span class="num">5</span> {{ __('deliveryman.section_license') }}
                </h4>
                <hr>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>{{ __('deliveryman.license_number') }}</label>
                        <input type="text" name="license_number" class="form-control" value="{{ old('license_number') }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ __('deliveryman.license_expiry') }}</label>
                        <input type="date" name="license_expiry" class="form-control" value="{{ old('license_expiry') }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ __('deliveryman.iqama_expiry') }}</label>
                        <input type="date" name="iqama_expiry" class="form-control" value="{{ old('iqama_expiry') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- 6. Freelancer bank info (conditional; wizard skips when not freelancer) --}}
        <div class="card rl-section-card rl-wizard-step rl-conditional-block" data-step="6" data-show-for="freelancer">
            <div class="card-body">
                <h4 class="rl-section-head">
                    <span class="num">6</span> {{ __('deliveryman.section_bank') }}
                </h4>
                <hr>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>{{ __('deliveryman.bank_account_no') }}</label>
                        <input type="text" name="bank_account_no" class="form-control" value="{{ old('bank_account_no') }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ __('deliveryman.iban') }}</label>
                        <input type="text" name="iban" class="form-control" value="{{ old('iban') }}" placeholder="{{ __('deliveryman.iban_placeholder') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- 7. Official documents (uploads) --}}
        <div class="card rl-section-card rl-wizard-step" data-step="7">
            <div class="card-body">
                <h4 class="rl-section-head">
                    <span class="num">7</span> {{ __('deliveryman.section_documents') }}
                </h4>
                <hr>
                <div class="rl-uploads-grid">
                    <div>
                        <label>{{ __('deliveryman.personal_photo') }}</label>
                        <input type="file" name="image_id" class="form-control" accept="image/*">
                    </div>
                    <div>
                        <label>{{ __('deliveryman.license_photo') }}</label>
                        <input type="file" name="driving_license_image_id" class="form-control" accept="image/*">
                    </div>
                    <div class="rl-conditional-block" data-show-for="freelancer">
                        <label>{{ __('deliveryman.iqama_photo') }}</label>
                        <input type="file" name="iqama_image_id" class="form-control" accept="image/*">
                    </div>
                    <div class="rl-conditional-block" data-show-for="freelancer">
                        <label>{{ __('deliveryman.contract_photo') }}</label>
                        <input type="file" name="contract_image_id" class="form-control" accept="image/*">
                    </div>
                    <div class="rl-conditional-block" data-show-for="freelancer">
                        <label>{{ __('deliveryman.promissory_note_photo') }}</label>
                        <input type="file" name="promissory_note_image_id" class="form-control" accept="image/*">
                    </div>
                </div>
            </div>
        </div>

        </div>{{-- /col-lg-8 (form sections) --}}

        {{-- Live summary panel (sticky on desktop, stacks under form on mobile) --}}
        <aside class="col-lg-4 col-md-12">
            <div class="rl-summary" id="rl-summary">
                <div class="rl-avatar" id="rl-summary-avatar">?</div>
                <div class="rl-name" id="rl-summary-name">—</div>
                <div class="rl-meta" id="rl-summary-meta">{{ __('deliveryman.create_deliveryman') }}</div>

                <h6>{{ __('deliveryman.section_basic') }}</h6>
                <div class="rl-row"><span class="lbl">{{ __('deliveryman.mobile') }}</span><span class="val" id="rl-sum-mobile">—</span></div>
                <div class="rl-row"><span class="lbl">{{ __('deliveryman.email') }}</span><span class="val" id="rl-sum-email">—</span></div>
                <div class="rl-row"><span class="lbl">{{ __('deliveryman.nationality') }}</span><span class="val" id="rl-sum-nat">—</span></div>

                <h6>{{ __('deliveryman.section_employment') }}</h6>
                <div class="rl-row"><span class="lbl">{{ __('deliveryman.driver_type') }}</span><span class="val"><span class="rl-badge rl-badge--type" id="rl-sum-type">—</span></span></div>
                <div class="rl-row"><span class="lbl">{{ __('deliveryman.hub') }}</span><span class="val" id="rl-sum-hub">—</span></div>
                <div class="rl-row"><span class="lbl">{{ __('deliveryman.status') }}</span><span class="val"><span class="rl-badge rl-badge--active" id="rl-sum-status">—</span></span></div>
                <div class="rl-row"><span class="lbl">{{ __('deliveryman.joining_date') }}</span><span class="val" id="rl-sum-join">—</span></div>

                <div class="rl-warning" id="rl-sum-contract-warn">
                    <i class="fa fa-exclamation-triangle"></i>
                    {{ __('deliveryman.contract_expiry_hint') }}
                </div>
            </div>
        </aside>
        </div>{{-- /row --}}

        <div class="rl-wizard-nav">
            <button type="button" class="btn btn-outline-secondary" id="rl-wizard-prev">
                <i class="fa fa-chevron-left"></i> {{ __('deliveryman.wizard_prev') }}
            </button>

            <div class="rl-wizard-progress" id="rl-wizard-progress">—</div>

            <div class="rl-wizard-actions">
                <a href="{{ route('deliveryman.index') }}" class="btn btn-secondary">{{ __('levels.cancel') }}</a>
                <button type="button" class="btn btn-primary" id="rl-wizard-next">
                    {{ __('deliveryman.wizard_next') }} <i class="fa fa-chevron-right"></i>
                </button>
                <button type="submit" class="btn btn-success" id="rl-wizard-submit" style="display:none;">
                    <i class="fa fa-check"></i> {{ __('deliveryman.wizard_submit') }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('js')
<script>
(function () {
    /* =========================================================
       Conditional blocks (driver_type)
       Hidden blocks get their inputs disabled so they don't submit
       and don't trip required-field validation that doesn't apply.
    ========================================================= */
    const radios     = document.querySelectorAll('input[name="driver_type"]');
    const condBlocks = document.querySelectorAll('.rl-conditional-block');

    function currentType() {
        const checked = document.querySelector('input[name="driver_type"]:checked');
        return checked ? checked.value : null;
    }

    function applyConditional() {
        const t = currentType();
        condBlocks.forEach(block => {
            const allowed = (block.dataset.showFor || '').split(',').map(s => s.trim());
            const ok = allowed.includes(t);
            block.classList.toggle('is-visible', ok);
            block.querySelectorAll('input, select, textarea').forEach(el => {
                el.disabled = !ok;
            });
        });
        document.querySelectorAll('#rl-wizard-stepper .rl-wizard-pill[data-show-for]').forEach(pill => {
            const allowed = (pill.dataset.showFor || '').split(',').map(s => s.trim());
            pill.classList.toggle('is-skipped', !allowed.includes(t));
        });
    }

    /* =========================================================
       Wizard with per-step validation
       Strategy:
         - All inputs always live in the DOM. checkValidity() is run only
           on the current step before advancing — so going backward never
           blocks, and skipping forward via the pill stepper revalidates
           every step in between.
         - Field-level invalid styling is added/removed on each check via
           the .is-invalid class. Server-side @@error('field') also writes
           that class, so the same CSS path applies for both.
         - A focus + scroll on the first invalid input gives the user a
           clear next action.
    ========================================================= */
    const steps     = Array.from(document.querySelectorAll('.rl-wizard-step'));
    const pills     = Array.from(document.querySelectorAll('#rl-wizard-stepper .rl-wizard-pill'));
    const btnPrev   = document.getElementById('rl-wizard-prev');
    const btnNext   = document.getElementById('rl-wizard-next');
    const btnSubmit = document.getElementById('rl-wizard-submit');
    const progress  = document.getElementById('rl-wizard-progress');
    const progBar   = document.getElementById('rl-wizard-bar');
    const form      = document.getElementById('deliveryman-form');
    const stepOfTemplate = @json(__('deliveryman.wizard_step_of'));

    let active = 1;

    function visibleStepNums() {
        const t = currentType();
        return steps
            .filter(s => {
                if (!s.classList.contains('rl-conditional-block')) return true;
                const allowed = (s.dataset.showFor || '').split(',').map(x => x.trim());
                return allowed.includes(t);
            })
            .map(s => parseInt(s.dataset.step, 10));
    }

    function stepEl(n) {
        return steps.find(s => parseInt(s.dataset.step, 10) === n);
    }

    // Returns { ok: true } or { ok: false, firstInvalid: HTMLElement }.
    function validateStep(n) {
        const el = stepEl(n);
        if (!el) return { ok: true };
        const inputs = el.querySelectorAll('input, select, textarea');
        let firstInvalid = null;
        inputs.forEach(i => {
            if (i.disabled || i.type === 'hidden' || i.type === 'file') {
                i.classList.remove('is-invalid');
                return;
            }
            if (!i.checkValidity()) {
                i.classList.add('is-invalid');
                if (!firstInvalid) firstInvalid = i;
            } else {
                i.classList.remove('is-invalid');
            }
        });
        return firstInvalid ? { ok: false, firstInvalid } : { ok: true };
    }

    function showInvalid(input) {
        const ownerStep = input.closest('.rl-wizard-step');
        if (ownerStep) {
            active = parseInt(ownerStep.dataset.step, 10);
            render();
        }
        try { input.focus({ preventScroll: false }); } catch (_) { input.focus(); }
        input.scrollIntoView({ behavior: 'smooth', block: 'center' });
        // reportValidity shows the browser's native validation tooltip on the
        // input — without this, checkValidity is silent and the user gets no
        // feedback explaining what's wrong with the field.
        if (typeof input.reportValidity === 'function') {
            // Defer until after the scroll lands so the tooltip anchors correctly.
            setTimeout(() => { try { input.reportValidity(); } catch (_) {} }, 200);
        }
    }

    function markPillErrors() {
        // After a validation attempt, paint pills whose step has any .is-invalid.
        pills.forEach(p => {
            const n = parseInt(p.dataset.go, 10);
            const s = stepEl(n);
            const hasInvalid = !!(s && s.querySelector('.is-invalid'));
            p.classList.toggle('has-error', hasInvalid);
        });
    }

    function render() {
        const visible = visibleStepNums();
        if (!visible.includes(active)) active = visible[0] || 1;
        const idx = visible.indexOf(active);

        steps.forEach(s => {
            s.classList.toggle('is-active', parseInt(s.dataset.step, 10) === active);
        });
        pills.forEach(p => {
            const n = parseInt(p.dataset.go, 10);
            p.classList.toggle('is-active', n === active);
            const positionInVisible = visible.indexOf(n);
            p.classList.toggle('is-done', positionInVisible > -1 && positionInVisible < idx);
        });

        btnPrev.disabled = idx <= 0;
        const isLast = idx >= visible.length - 1;
        btnNext.style.display   = isLast ? 'none' : 'inline-block';
        btnSubmit.style.display = isLast ? 'inline-block' : 'none';

        progress.textContent = stepOfTemplate
            .replace(':current', idx + 1)
            .replace(':total',   visible.length);

        const pct = visible.length > 0
            ? Math.round(((idx + 1) / visible.length) * 100)
            : 0;
        if (progBar) progBar.style.width = pct + '%';

        markPillErrors();
    }

    function scrollToActive() {
        const el = stepEl(active);
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function attemptAdvanceTo(targetStep) {
        // Going backward or staying: free.
        if (targetStep <= active) {
            active = targetStep;
            render();
            scrollToActive();
            return;
        }
        // Going forward: validate every visible step from active up to targetStep-1.
        const visible = visibleStepNums();
        const from = visible.indexOf(active);
        const to   = visible.indexOf(targetStep);
        if (from === -1 || to === -1) return;
        for (let i = from; i < to; i++) {
            const res = validateStep(visible[i]);
            if (!res.ok) {
                showInvalid(res.firstInvalid);
                markPillErrors();
                return;
            }
        }
        active = targetStep;
        render();
        scrollToActive();
    }

    btnPrev.addEventListener('click', () => {
        const visible = visibleStepNums();
        const idx = visible.indexOf(active);
        if (idx > 0) attemptAdvanceTo(visible[idx - 1]);
    });
    btnNext.addEventListener('click', () => {
        const visible = visibleStepNums();
        const idx = visible.indexOf(active);
        if (idx < visible.length - 1) attemptAdvanceTo(visible[idx + 1]);
    });

    pills.forEach(p => {
        p.addEventListener('click', () => {
            if (p.classList.contains('is-skipped')) return;
            attemptAdvanceTo(parseInt(p.dataset.go, 10));
        });
    });

    radios.forEach(r => r.addEventListener('change', () => { applyConditional(); render(); syncSummary(); }));

    // Live-clear invalid styling as the user fixes a field.
    form.addEventListener('input',  e => {
        if (e.target.checkValidity?.()) e.target.classList.remove('is-invalid');
        syncSummary();
    });
    form.addEventListener('change', e => {
        if (e.target.checkValidity?.()) e.target.classList.remove('is-invalid');
        syncSummary();
    });

    /* =========================================================
       Live summary panel
       Reads form values straight from the DOM. No new sources of truth.
    ========================================================= */
    const sum = {
        avatar:    document.getElementById('rl-summary-avatar'),
        name:      document.getElementById('rl-summary-name'),
        meta:      document.getElementById('rl-summary-meta'),
        mobile:    document.getElementById('rl-sum-mobile'),
        email:     document.getElementById('rl-sum-email'),
        nat:       document.getElementById('rl-sum-nat'),
        type:      document.getElementById('rl-sum-type'),
        hub:       document.getElementById('rl-sum-hub'),
        status:    document.getElementById('rl-sum-status'),
        join:      document.getElementById('rl-sum-join'),
        contractW: document.getElementById('rl-sum-contract-warn'),
    };
    const statusLabels = {
        '1': @json(__('deliveryman.status_active')),
        '2': @json(__('deliveryman.status_suspended')),
        '3': @json(__('deliveryman.status_leave')),
        '4': @json(__('deliveryman.status_terminated')),
    };
    const statusClass = {
        '1': 'rl-badge rl-badge--active',
        '2': 'rl-badge rl-badge--suspended',
        '3': 'rl-badge rl-badge--leave',
        '4': 'rl-badge rl-badge--terminated',
    };
    const typeLabels = {
        'freelancer':       @json(__('deliveryman.driver_type_freelancer')),
        'outsourced':       @json(__('deliveryman.driver_type_outsourced')),
        'company_courier':  @json(__('deliveryman.driver_type_company_courier')),
    };

    function fieldVal(name) {
        const el = form.querySelector('[name="' + name + '"]');
        return el ? (el.value || '').trim() : '';
    }
    function selectLabel(name) {
        const el = form.querySelector('select[name="' + name + '"]');
        if (!el || el.selectedIndex < 0) return '';
        const opt = el.options[el.selectedIndex];
        return opt && opt.value ? (opt.textContent || '').trim() : '';
    }
    function initials(name) {
        if (!name) return '?';
        return name.trim().split(/\s+/).slice(0, 2).map(w => w[0]).join('').toUpperCase();
    }
    function daysUntil(yyyyMmDd) {
        if (!yyyyMmDd) return null;
        const d = new Date(yyyyMmDd + 'T00:00:00');
        if (isNaN(d.getTime())) return null;
        const today = new Date();
        today.setHours(0,0,0,0);
        return Math.round((d - today) / 86400000);
    }

    function syncSummary() {
        const name      = fieldVal('name') || fieldVal('name_en');
        const mobile    = fieldVal('mobile');
        const email     = fieldVal('email');
        const nat       = fieldVal('nationality');
        const type      = currentType();
        const hubLabel  = selectLabel('hub_id');
        const statusV   = fieldVal('status') || '1';
        const join      = fieldVal('joining_date');
        const contractE = fieldVal('contract_end_date');

        sum.name.textContent   = name || '—';
        sum.avatar.textContent = initials(name);
        sum.mobile.textContent = mobile || '—';
        sum.email.textContent  = email  || '—';
        sum.nat.textContent    = nat    || '—';
        sum.type.textContent   = typeLabels[type] || '—';
        sum.hub.textContent    = hubLabel || '—';
        sum.join.textContent   = join || '—';

        sum.status.textContent = statusLabels[statusV] || '—';
        sum.status.className   = statusClass[statusV] || 'rl-badge';

        const dleft = daysUntil(contractE);
        const soon  = dleft !== null && dleft >= 0 && dleft <= 30;
        sum.contractW.classList.toggle('is-visible', !!soon);
    }

    // Final guard: on Submit, validate ALL visible steps. If anything is
    // invalid, block submission and jump to the first failing step.
    form.addEventListener('submit', (e) => {
        const visible = visibleStepNums();
        for (const n of visible) {
            const res = validateStep(n);
            if (!res.ok) {
                e.preventDefault();
                showInvalid(res.firstInvalid);
                markPillErrors();
                return;
            }
        }
    });

    // First paint. If the server bounced back with @@error styling, land on
    // the first failing step so the user sees the issue immediately.
    applyConditional();
    const firstServerInvalid = form.querySelector('.is-invalid');
    if (firstServerInvalid) {
        const stepWithError = firstServerInvalid.closest('.rl-wizard-step');
        if (stepWithError) active = parseInt(stepWithError.dataset.step, 10);
    }
    render();
    syncSummary();
})();
</script>
@endpush
