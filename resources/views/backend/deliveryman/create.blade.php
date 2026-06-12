@extends('backend.partials.master')

@section('title')
    {{ __('deliveryman.title') }} {{ __('levels.add') }}
@endsection

@push('css')
<style>
    .rl-section-card { margin-bottom: 18px; }
    .rl-section-head { display: flex; align-items: center; gap: 8px; }
    .rl-section-head .badge { font-size: 12px; }
    .rl-required { color: #dc3545; }
    .rl-help { color: #6c757d; font-size: 12px; }
    .rl-conditional-block { display: none; }
    .rl-conditional-block.is-visible { display: block; }
    .rl-uploads-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
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

        {{-- 1. Basic identity --}}
        <div class="card rl-section-card">
            <div class="card-body">
                <h4 class="rl-section-head">
                    <span class="badge badge-primary">1</span> البيانات الأساسية
                </h4>
                <hr>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>الاسم الكامل <span class="rl-required">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-md-6 form-group">
                        <label>الاسم بالإنجليزية</label>
                        <input type="text" name="name_en" class="form-control" value="{{ old('name_en') }}">
                    </div>

                    <div class="col-md-6 form-group">
                        <label>رقم الجوال <span class="rl-required">*</span></label>
                        <input type="text" name="mobile" class="form-control @error('mobile') is-invalid @enderror" value="{{ old('mobile') }}" required>
                        @error('mobile') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-md-6 form-group">
                        <label>رقم جوال بديل</label>
                        <input type="text" name="alt_mobile" class="form-control" value="{{ old('alt_mobile') }}">
                    </div>

                    <div class="col-md-6 form-group">
                        <label>البريد الإلكتروني <span class="rl-required">*</span></label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                        @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-md-6 form-group">
                        <label>كلمة المرور <span class="rl-required">*</span></label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                        @error('password') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-4 form-group">
                        <label>الجنس</label>
                        <select name="gender" class="form-control">
                            <option value="">—</option>
                            <option value="male"   {{ old('gender') === 'male' ? 'selected' : '' }}>ذكر</option>
                            <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>أنثى</option>
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>تاريخ الميلاد</label>
                        <input type="date" name="dob" class="form-control" value="{{ old('dob') }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>الجنسية</label>
                        <input type="text" name="nationality" class="form-control" value="{{ old('nationality') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. ID --}}
        <div class="card rl-section-card">
            <div class="card-body">
                <h4 class="rl-section-head">
                    <span class="badge badge-primary">2</span> بيانات الهوية
                </h4>
                <hr>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>نوع الهوية</label>
                        <select name="id_type" class="form-control">
                            <option value="">—</option>
                            <option value="national_id" {{ old('id_type') === 'national_id' ? 'selected' : '' }}>هوية وطنية</option>
                            <option value="iqama"       {{ old('id_type') === 'iqama' ? 'selected' : '' }}>إقامة</option>
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>رقم الهوية</label>
                        <input type="text" name="id_number" class="form-control" value="{{ old('id_number') }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>تاريخ الانتهاء</label>
                        <input type="date" name="id_expiry" class="form-control" value="{{ old('id_expiry') }}">
                    </div>

                    <div class="col-md-6 form-group">
                        <label>صورة الهوية</label>
                        <input type="file" name="id_image_id" class="form-control" accept="image/*">
                        <small class="rl-help">JPEG/PNG, حد أقصى 5 ميغابايت</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Address --}}
        <div class="card rl-section-card">
            <div class="card-body">
                <h4 class="rl-section-head">
                    <span class="badge badge-primary">3</span> بيانات العنوان
                </h4>
                <hr>
                <div class="row">
                    <div class="col-md-12 form-group">
                        <label>العنوان التفصيلي <span class="rl-required">*</span></label>
                        <input type="text" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}" required>
                        @error('address') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-md-6 form-group">
                        <label>الحي</label>
                        <input type="text" name="district" class="form-control" value="{{ old('district') }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>الرقم المختصر للعنوان الوطني</label>
                        <input type="text" name="short_national_address" class="form-control" value="{{ old('short_national_address') }}" placeholder="مثال: ABCD1234">
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. Employment --}}
        <div class="card rl-section-card">
            <div class="card-body">
                <h4 class="rl-section-head">
                    <span class="badge badge-primary">4</span> بيانات التوظيف
                </h4>
                <hr>
                <div class="row">
                    <div class="col-md-12 form-group">
                        <label>نوع المندوب <span class="rl-required">*</span></label>
                        <div class="btn-group btn-group-toggle" data-toggle="buttons">
                            @php $dt = old('driver_type', 'company_courier'); @endphp
                            <label class="btn btn-outline-primary {{ $dt === 'freelancer' ? 'active' : '' }}">
                                <input type="radio" name="driver_type" value="freelancer" {{ $dt === 'freelancer' ? 'checked' : '' }}> Freelancer
                            </label>
                            <label class="btn btn-outline-primary {{ $dt === 'outsourced' ? 'active' : '' }}">
                                <input type="radio" name="driver_type" value="outsourced" {{ $dt === 'outsourced' ? 'checked' : '' }}> Outsourced
                            </label>
                            <label class="btn btn-outline-primary {{ $dt === 'company_courier' ? 'active' : '' }}">
                                <input type="radio" name="driver_type" value="company_courier" {{ $dt === 'company_courier' ? 'checked' : '' }}> Company Courier
                            </label>
                        </div>
                        @error('driver_type') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-6 form-group rl-conditional-block" data-show-for="company_courier">
                        <label>رقم الموظف</label>
                        <input type="text" name="employee_number" class="form-control" value="{{ old('employee_number') }}">
                    </div>

                    <div class="col-md-6 form-group rl-conditional-block" data-show-for="outsourced">
                        <label>الشركة المزودة <span class="rl-required">*</span></label>
                        <select name="supplier_company_id" class="form-control">
                            <option value="">—</option>
                            @foreach($supplierCompanies as $sc)
                                <option value="{{ $sc->id }}" {{ old('supplier_company_id') == $sc->id ? 'selected' : '' }}>{{ $sc->name }}</option>
                            @endforeach
                        </select>
                        @if($supplierCompanies->isEmpty())
                            <small class="rl-help">لا توجد شركات مزودة مسجلة بعد.</small>
                        @endif
                    </div>

                    <div class="col-md-6 form-group">
                        <label>تاريخ الانضمام</label>
                        <input type="date" name="joining_date" class="form-control" value="{{ old('joining_date') }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>تاريخ انتهاء العقد</label>
                        <input type="date" name="contract_end_date" class="form-control" value="{{ old('contract_end_date') }}">
                        <small class="rl-help">سيظهر تنبيه عند اقتراب الانتهاء خلال 30 يومًا.</small>
                    </div>

                    <div class="col-md-6 form-group">
                        <label>الحالة <span class="rl-required">*</span></label>
                        <select name="status" class="form-control" required>
                            <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>نشط</option>
                            <option value="2" {{ old('status') == 2 ? 'selected' : '' }}>موقوف</option>
                            <option value="3" {{ old('status') == 3 ? 'selected' : '' }}>إجازة</option>
                            <option value="4" {{ old('status') == 4 ? 'selected' : '' }}>منتهي التعاقد</option>
                        </select>
                    </div>

                    <div class="col-md-6 form-group">
                        <label>الفرع / Hub <span class="rl-required">*</span></label>
                        <select name="hub_id" class="form-control @error('hub_id') is-invalid @enderror" required>
                            <option value="">—</option>
                            @foreach($hubs as $hub)
                                <option value="{{ $hub->id }}" {{ old('hub_id') == $hub->id ? 'selected' : '' }}>{{ $hub->name }}</option>
                            @endforeach
                        </select>
                        @error('hub_id') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-6 form-group">
                        <label>المدير المباشر</label>
                        <select name="direct_manager_id" class="form-control">
                            <option value="">—</option>
                            @foreach($managers as $m)
                                <option value="{{ $m->id }}" {{ old('direct_manager_id') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>المنطقة التشغيلية</label>
                        <select name="operational_area_id" class="form-control">
                            <option value="">—</option>
                            @foreach($operationalAreas as $oa)
                                <option value="{{ $oa->id }}" {{ old('operational_area_id') == $oa->id ? 'selected' : '' }}>{{ $oa->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 form-group">
                        <label>الراتب</label>
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
        <div class="card rl-section-card">
            <div class="card-body">
                <h4 class="rl-section-head">
                    <span class="badge badge-primary">5</span> بيانات الرخصة
                </h4>
                <hr>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>رقم الرخصة</label>
                        <input type="text" name="license_number" class="form-control" value="{{ old('license_number') }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>تاريخ انتهاء الرخصة</label>
                        <input type="date" name="license_expiry" class="form-control" value="{{ old('license_expiry') }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>تاريخ انتهاء الإقامة</label>
                        <input type="date" name="iqama_expiry" class="form-control" value="{{ old('iqama_expiry') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- 6. Freelancer bank info (conditional) --}}
        <div class="card rl-section-card rl-conditional-block" data-show-for="freelancer">
            <div class="card-body">
                <h4 class="rl-section-head">
                    <span class="badge badge-primary">6</span> البيانات البنكية (Freelancer)
                </h4>
                <hr>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>رقم الحساب البنكي</label>
                        <input type="text" name="bank_account_no" class="form-control" value="{{ old('bank_account_no') }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>الآيبان (IBAN)</label>
                        <input type="text" name="iban" class="form-control" value="{{ old('iban') }}" placeholder="SA00 0000 0000 0000 0000 0000">
                    </div>
                </div>
            </div>
        </div>

        {{-- 7. Official documents (uploads) --}}
        <div class="card rl-section-card">
            <div class="card-body">
                <h4 class="rl-section-head">
                    <span class="badge badge-primary">7</span> المستندات الرسمية
                </h4>
                <hr>
                <div class="rl-uploads-grid">
                    <div>
                        <label>صورة شخصية</label>
                        <input type="file" name="image_id" class="form-control" accept="image/*">
                    </div>
                    <div>
                        <label>صورة الرخصة</label>
                        <input type="file" name="driving_license_image_id" class="form-control" accept="image/*">
                    </div>
                    <div class="rl-conditional-block" data-show-for="freelancer">
                        <label>صورة الإقامة</label>
                        <input type="file" name="iqama_image_id" class="form-control" accept="image/*">
                    </div>
                    <div class="rl-conditional-block" data-show-for="freelancer">
                        <label>صورة العقد</label>
                        <input type="file" name="contract_image_id" class="form-control" accept="image/*">
                    </div>
                    <div class="rl-conditional-block" data-show-for="freelancer">
                        <label>صورة السند لأمر</label>
                        <input type="file" name="promissory_note_image_id" class="form-control" accept="image/*">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-4">
            <a href="{{ route('deliveryman.index') }}" class="btn btn-secondary ml-2">{{ __('levels.cancel') }}</a>
            <button type="submit" class="btn btn-primary">{{ __('levels.save') }}</button>
        </div>
    </form>
</div>
@endsection

@push('js')
<script>
(function () {
    // Show/hide blocks keyed by driver_type. Disable inputs in hidden blocks
    // so the browser doesn't submit them and validation skips required fields
    // that don't apply to the current type.
    //
    // Markup contract:
    //   <... class="rl-conditional-block" data-show-for="freelancer">
    //   <... class="rl-conditional-block" data-show-for="freelancer,outsourced">  // multiple types ok
    const radios = document.querySelectorAll('input[name="driver_type"]');
    const blocks = document.querySelectorAll('.rl-conditional-block');

    function currentType() {
        const checked = document.querySelector('input[name="driver_type"]:checked');
        return checked ? checked.value : null;
    }

    function applyVisibility() {
        const t = currentType();
        blocks.forEach(block => {
            const allowed = (block.dataset.showFor || '').split(',').map(s => s.trim());
            const shouldShow = allowed.includes(t);
            block.classList.toggle('is-visible', shouldShow);
            block.querySelectorAll('input, select, textarea').forEach(el => {
                el.disabled = !shouldShow;
            });
        });
    }

    radios.forEach(r => r.addEventListener('change', applyVisibility));
    applyVisibility();
})();
</script>
@endpush
