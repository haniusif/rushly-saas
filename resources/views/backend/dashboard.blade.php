@extends('backend.partials.master')
@section('title')
    {{ __('menus.dashboard') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content rl-dashboard">

        {{-- ===== Page header ===== --}}
        <div class="rl-page-head">
            <div class="rl-page-head__title">
                <h2 class="rl-page-head__h">{{ settings()->name }} · {{ __('menus.dashboard') }}</h2>
                <p class="rl-page-head__sub">{{ __('dashboard.operations_overview') }}</p>
            </div>
            <form action="{{ route('dashboard.index', ['test' => 'custom']) }}" method="get" class="rl-filter">
                <div class="rl-filter__field">
                    <i class="ti ti-calendar"></i>
                    <input type="text" name="filter_date" placeholder="YYYY-MM-DD" autocomplete="off"
                        class="form-control date_range_picker" value="{{ $request->filter_date }}" required />
                </div>
                <input type="hidden" name="days" value="custom" />
                <button type="submit" class="btn rl-btn-primary">
                    <i class="ti ti-filter"></i> {{ __('levels.filter') }}
                </button>
            </form>
        </div>

        {{-- ===== Follow-up Center widget (NDR + Abnormal at a glance) ===== --}}
        @php
            $fuOpenNdrs       = \App\Models\Backend\Ndr::companywise()->whereIn('status', ['open','in_progress'])->count();
            $fuTodayNdrs      = \App\Models\Backend\Ndr::companywise()->whereDate('created_at', today())->count();
            $fuCriticalAbn    = \App\Models\Backend\AbnormalShipment::companywise()->where('severity','critical')->whereIn('status',['open','investigating'])->count();
            $fuOpenAbn        = \App\Models\Backend\AbnormalShipment::companywise()->whereIn('status',['open','investigating'])->count();
            $fuShowFollowup   = ($fuOpenNdrs + $fuOpenAbn) > 0;
        @endphp
        @if ($fuShowFollowup)
            <div class="rl-followup">
                <div class="rl-followup__head">
                    <div class="rl-followup__icon">🛟</div>
                    <div>
                        <h6 class="rl-followup__title">{{ __('dashboard.followup_center') }}</h6>
                        <small class="rl-followup__sub">{{ __('dashboard.followup_subtitle') }}</small>
                    </div>
                </div>
                <div class="rl-followup__pills">
                    <a href="{{ route('ndr.index') }}" class="rl-pill rl-pill--red">
                        <span class="rl-pill__num">{{ $fuOpenNdrs }}</span>
                        <span class="rl-pill__lbl">{{ __('dashboard.open_ndrs') }}</span>
                    </a>
                    <a href="{{ route('ndr.index', ['date_from' => now()->toDateString(), 'date_to' => now()->toDateString()]) }}" class="rl-pill rl-pill--amber">
                        <span class="rl-pill__num">{{ $fuTodayNdrs }}</span>
                        <span class="rl-pill__lbl">{{ __('dashboard.today_ndrs') }}</span>
                    </a>
                    <a href="{{ route('abnormal.index') }}" class="rl-pill rl-pill--orange">
                        <span class="rl-pill__num">{{ $fuOpenAbn }}</span>
                        <span class="rl-pill__lbl">{{ __('dashboard.open_abnormal') }}</span>
                    </a>
                    <a href="{{ route('abnormal.index', ['severity' => 'critical']) }}" class="rl-pill rl-pill--dark">
                        <span class="rl-pill__num">{{ $fuCriticalAbn }}</span>
                        <span class="rl-pill__lbl">{{ __('dashboard.critical') }}</span>
                    </a>
                </div>
            </div>
        @endif

        {{-- ===== KPI grid ===== --}}
        <div class="rl-kpi-grid">
            @if (hasPermission('total_parcel') == true)
                <a href="{{ route('parcel.index') }}" class="rl-kpi rl-kpi--sky">
                    <div class="rl-kpi__icon"><i class="ti ti-box"></i></div>
                    <div class="rl-kpi__body">
                        <span class="rl-kpi__label">{{ __('dashboard.total_parcel') }}</span>
                        <span class="rl-kpi__num">{{ $data['total_parcel'] }}</span>
                    </div>
                </a>
            @endif

            @if (hasPermission('total_user') == true)
                <a href="{{ route('users.index') }}" class="rl-kpi rl-kpi--violet">
                    <div class="rl-kpi__icon"><i class="ti ti-users"></i></div>
                    <div class="rl-kpi__body">
                        <span class="rl-kpi__label">{{ __('dashboard.total_user') }}</span>
                        <span class="rl-kpi__num">{{ $data['total_user'] }}</span>
                    </div>
                </a>
            @endif

            @if (hasPermission('total_merchant') == true)
                <a href="{{ route('merchant.index') }}" class="rl-kpi rl-kpi--teal">
                    <div class="rl-kpi__icon"><i class="ti ti-building-store"></i></div>
                    <div class="rl-kpi__body">
                        <span class="rl-kpi__label">{{ __('dashboard.total_merchant') }}</span>
                        <span class="rl-kpi__num">{{ $data['total_merchant'] }}</span>
                    </div>
                </a>
            @endif

            @if (hasPermission('total_delivery_man') == true)
                <a href="{{ route('deliveryman.index') }}" class="rl-kpi rl-kpi--indigo">
                    <div class="rl-kpi__icon"><i class="ti ti-car"></i></div>
                    <div class="rl-kpi__body">
                        <span class="rl-kpi__label">{{ __('dashboard.total_delivery_man') }}</span>
                        <span class="rl-kpi__num">{{ $data['total_delivery_man'] }}</span>
                    </div>
                </a>
            @endif

            @if (hasPermission('total_hubs') == true)
                <a href="{{ route('hubs.index') }}" class="rl-kpi rl-kpi--slate">
                    <div class="rl-kpi__icon"><i class="ti ti-building-warehouse"></i></div>
                    <div class="rl-kpi__body">
                        <span class="rl-kpi__label">{{ __('dashboard.total_hubs') }}</span>
                        <span class="rl-kpi__num">{{ $data['total_hubs'] }}</span>
                    </div>
                </a>
            @endif

            @if (hasPermission('total_accounts') == true)
                <a href="{{ route('accounts.index') }}" class="rl-kpi rl-kpi--rose">
                    <div class="rl-kpi__icon"><i class="ti ti-credit-card"></i></div>
                    <div class="rl-kpi__body">
                        <span class="rl-kpi__label">{{ __('dashboard.total_accounts') }}</span>
                        <span class="rl-kpi__num">{{ $data['total_accounts'] }}</span>
                    </div>
                </a>
            @endif

            @if (hasPermission('total_partial_deliverd') == true)
                <a href="{{ route('parcel.filter', ['parcel_status' => \App\Enums\ParcelStatus::PARTIAL_DELIVERED]) }}"
                    class="rl-kpi rl-kpi--amber">
                    <div class="rl-kpi__icon"><i class="ti ti-package-export"></i></div>
                    <div class="rl-kpi__body">
                        <span class="rl-kpi__label">{{ __('dashboard.total_partial_deliverd') }}</span>
                        <span class="rl-kpi__num">{{ $data['total_partial_deliverd'] }}</span>
                    </div>
                </a>
            @endif

            @if (hasPermission('total_parcels_deliverd') == true)
                <a href="{{ route('parcel.filter', ['parcel_status' => \App\Enums\ParcelStatus::DELIVERED]) }}"
                    class="rl-kpi rl-kpi--green">
                    <div class="rl-kpi__icon"><i class="ti ti-circle-check"></i></div>
                    <div class="rl-kpi__body">
                        <span class="rl-kpi__label">{{ __('dashboard.total_deliverd') }}</span>
                        <span class="rl-kpi__num">{{ $data['total_deliverd'] }}</span>
                    </div>
                </a>
            @endif
        </div>

        {{-- ===== Statements ===== --}}
        @if (hasPermission('all_statements') == true)
            @php
                $stmts = [
                    ['title' => __('dashboard.delivery_man') . ' · ' . __('dashboard.statements'), 'income' => $d_income, 'expense' => $d_expense, 'icon' => 'ti ti-car'],
                    ['title' => __('dashboard.merchant') . ' · ' . __('dashboard.statements'), 'income' => $m_income, 'expense' => $m_expense, 'icon' => 'ti ti-building-store'],
                    ['title' => __('hub.title') . ' · ' . __('dashboard.statements'), 'income' => $h_income, 'expense' => $h_expense, 'icon' => 'ti ti-building-warehouse'],
                ];
            @endphp
            <div class="rl-stmt-grid">
                @foreach ($stmts as $s)
                    <div class="rl-stmt">
                        <div class="rl-stmt__head">
                            <i class="{{ $s['icon'] }}"></i>
                            <span>{{ $s['title'] }}</span>
                        </div>
                        <div class="rl-stmt__row">
                            <span class="rl-stmt__lbl">{{ __('income.title') }}</span>
                            <span class="rl-stmt__val rl-stmt__val--pos">{{ settings()->currency }}{{ $s['income'] }}</span>
                        </div>
                        <div class="rl-stmt__row">
                            <span class="rl-stmt__lbl">{{ __('expense.title') }}</span>
                            <span class="rl-stmt__val rl-stmt__val--neg">{{ settings()->currency }}{{ $s['expense'] }}</span>
                        </div>
                        <div class="rl-stmt__row rl-stmt__row--total">
                            <span class="rl-stmt__lbl">{{ __('dashboard.balance') }}</span>
                            <span class="rl-stmt__val">{{ settings()->currency }}{{ $s['income'] - $s['expense'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ===== Charts ===== --}}
        <div class="rl-charts">
            @if (hasPermission('income_expense_charts') == true)
                <div class="rl-card">
                    <div class="rl-card__body">
                        <div class="apexcharts" id="apexincomeexpense"></div>
                    </div>
                    <div class="rl-card__foot">
                        <span class="rl-chip rl-chip--primary">{{ settings()->currency }} {{ $data['income'] }}</span>
                        <span class="rl-chip rl-chip--muted">{{ settings()->currency }} {{ $data['expense'] }}</span>
                    </div>
                </div>
            @endif

            @if (hasPermission('courier_revenue_charts') == true)
                <div class="rl-card">
                    <div class="rl-card__body courier-pie-charts">
                        <div class="apexcharts" id="apexpiecourierrevenue"></div>
                    </div>
                    <div class="rl-card__foot">
                        <span class="rl-chip rl-chip--primary">{{ settings()->currency }} {{ $data['courier_income'] }}</span>
                        <span class="rl-chip rl-chip--muted">{{ settings()->currency }} {{ $data['courier_expense'] }}</span>
                    </div>
                </div>
            @endif
        </div>

        {{-- ===== Calendar ===== --}}
        @if (hasPermission('calendar_read') == true)
            <div class="rl-card rl-card--calendar">
                <div class="rl-card__body">
                    <div id="datetimepicker12"></div>
                </div>
            </div>
        @endif

    </div>
    </div>
    </div>
    <!-- end wrapper  -->
@endsection

<!-- css  -->
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <link rel="stylesheet" type="text/css" href="{{ static_asset('backend/vendor/calender/main.css') }}" />
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/eonasdan-bootstrap-datetimepicker/4.17.49/css/bootstrap-datetimepicker.min.css"
        integrity="sha512-ipfmbgqfdejR27dWn02UftaAzUfxJ3HR4BDQYuITYSj6ZQfGT1NulP4BOery3w/dT2PYAe3bG5Zm/owm7MuFhA==" crossorigin="anonymous"
        referrerpolicy="no-referrer" />
    <style>
        /* ===== Rushly dashboard ===== */
        .rl-dashboard { padding: 18px 22px 32px; }

        /* Page header */
        .rl-page-head {
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px; flex-wrap: wrap;
            background: #ffffff;
            border: 1px solid #eef0f5;
            border-radius: 14px;
            padding: 18px 22px;
            box-shadow: 0 1px 2px rgba(15,23,42,0.04);
            margin-bottom: 18px;
        }
        .rl-page-head__h { margin: 0; font-size: 20px; font-weight: 700; color: #0f172a; letter-spacing: -.01em; }
        .rl-page-head__sub { margin: 4px 0 0; color: #64748b; font-size: 13px; }
        .rl-filter { display: flex; align-items: center; gap: 8px; }
        .rl-filter__field {
            position: relative; display: flex; align-items: center;
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
            padding: 0 12px; height: 40px;
        }
        .rl-filter__field i { color: #64748b; font-size: 13px; }
        .rl-filter__field .form-control {
            border: 0; background: transparent; height: 38px;
            padding: 0 10px; box-shadow: none; min-width: 140px; font-size: 13px;
        }
        .rl-btn-primary {
            display: inline-flex; align-items: center; gap: 6px;
            height: 40px; padding: 0 16px; border-radius: 10px;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #fff; font-weight: 600; font-size: 13px; border: 0;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .rl-btn-primary:hover { color: #fff; transform: translateY(-1px); box-shadow: 0 6px 14px rgba(37,99,235,.25); }

        /* Follow-up Center */
        .rl-followup {
            background: linear-gradient(120deg, #fef2f2 0%, #fff7ed 100%);
            border: 1px solid #fee2e2;
            border-radius: 14px;
            padding: 16px 20px;
            display: flex; align-items: center; justify-content: space-between;
            gap: 18px; flex-wrap: wrap;
            margin-bottom: 18px;
        }
        .rl-followup__head { display: flex; align-items: center; gap: 12px; }
        .rl-followup__icon {
            width: 44px; height: 44px; border-radius: 12px;
            background: #fff; display: flex; align-items: center; justify-content: center;
            font-size: 22px; box-shadow: 0 2px 6px rgba(220,38,38,.12);
        }
        .rl-followup__title { margin: 0; color: #7f1d1d; font-size: 15px; font-weight: 700; letter-spacing: .01em; }
        .rl-followup__sub { color: #92400e; font-size: 12px; }
        .rl-followup__pills { display: flex; flex-wrap: wrap; gap: 10px; }
        .rl-pill {
            display: flex; flex-direction: column; align-items: center;
            min-width: 110px; padding: 10px 14px; border-radius: 10px;
            background: #fff; box-shadow: 0 1px 3px rgba(15,23,42,.05);
            text-decoration: none; transition: transform .15s ease, box-shadow .15s ease;
        }
        .rl-pill:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(15,23,42,.08); text-decoration: none; }
        .rl-pill__num { font-size: 20px; font-weight: 800; line-height: 1; }
        .rl-pill__lbl { font-size: 11px; color: #64748b; margin-top: 4px; }
        .rl-pill--red    .rl-pill__num { color: #b91c1c; }
        .rl-pill--amber  .rl-pill__num { color: #92400e; }
        .rl-pill--orange .rl-pill__num { color: #9a3412; }
        .rl-pill--dark   { background: #1f2937; }
        .rl-pill--dark   .rl-pill__num { color: #fcd34d; }
        .rl-pill--dark   .rl-pill__lbl { color: #fcd34d; }

        /* KPI grid */
        .rl-kpi-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 14px; margin-bottom: 22px;
        }
        .rl-kpi {
            display: flex; align-items: center; gap: 14px;
            background: #fff; border: 1px solid #eef0f5; border-radius: 14px;
            padding: 16px 18px; text-decoration: none;
            transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
            position: relative; overflow: hidden;
        }
        .rl-kpi:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(15,23,42,.06); text-decoration: none; border-color: #e2e8f0; }
        .rl-kpi::before {
            content: ""; position: absolute; inset-inline-start: 0; top: 0; bottom: 0;
            width: 4px; background: var(--rl-accent, #2563eb); border-radius: 4px 0 0 4px;
        }
        html[dir="rtl"] .rl-kpi::before { border-radius: 0 4px 4px 0; }
        .rl-kpi__icon {
            width: 48px; height: 48px; flex: 0 0 48px;
            border-radius: 12px;
            background: var(--rl-tint, #eff6ff);
            color: var(--rl-accent, #2563eb);
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
        }
        .rl-kpi__body { display: flex; flex-direction: column; flex: 1; min-width: 0; }
        .rl-kpi__label { color: #64748b; font-size: 12.5px; font-weight: 500; letter-spacing: .01em; }
        .rl-kpi__num { color: #0f172a; font-size: 26px; font-weight: 800; line-height: 1.1; margin-top: 4px; }

        .rl-kpi--sky    { --rl-accent: #0284c7; --rl-tint: #e0f2fe; }
        .rl-kpi--violet { --rl-accent: #7c3aed; --rl-tint: #ede9fe; }
        .rl-kpi--teal   { --rl-accent: #0d9488; --rl-tint: #ccfbf1; }
        .rl-kpi--indigo { --rl-accent: #4338ca; --rl-tint: #e0e7ff; }
        .rl-kpi--slate  { --rl-accent: #334155; --rl-tint: #e2e8f0; }
        .rl-kpi--rose   { --rl-accent: #e11d48; --rl-tint: #ffe4e6; }
        .rl-kpi--amber  { --rl-accent: #b45309; --rl-tint: #fef3c7; }
        .rl-kpi--green  { --rl-accent: #16a34a; --rl-tint: #dcfce7; }

        /* Statements */
        .rl-stmt-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 14px; margin-bottom: 22px;
        }
        .rl-stmt {
            background: #fff; border: 1px solid #eef0f5; border-radius: 14px;
            overflow: hidden; box-shadow: 0 1px 2px rgba(15,23,42,.04);
        }
        .rl-stmt__head {
            display: flex; align-items: center; gap: 10px;
            padding: 14px 18px;
            background: #f8fafc; border-bottom: 1px solid #eef0f5;
            color: #0f172a; font-weight: 700; font-size: 14px;
        }
        .rl-stmt__head i { color: #2563eb; font-size: 15px; }
        .rl-stmt__row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 18px; border-bottom: 1px solid #f1f5f9;
            font-size: 13.5px;
        }
        .rl-stmt__row:last-child { border-bottom: 0; }
        .rl-stmt__lbl { color: #64748b; font-weight: 500; }
        .rl-stmt__val { color: #0f172a; font-weight: 700; }
        .rl-stmt__val--pos { color: #15803d; }
        .rl-stmt__val--neg { color: #b91c1c; }
        .rl-stmt__row--total { background: #f8fafc; }
        .rl-stmt__row--total .rl-stmt__lbl { color: #0f172a; font-weight: 700; }
        .rl-stmt__row--total .rl-stmt__val { font-size: 15px; }

        /* Charts + calendar cards */
        .rl-charts {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
            gap: 14px; margin-bottom: 22px;
        }
        .rl-card {
            background: #fff; border: 1px solid #eef0f5; border-radius: 14px;
            box-shadow: 0 1px 2px rgba(15,23,42,.04);
        }
        .rl-card__body { padding: 18px; }
        .rl-card__foot {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 18px; border-top: 1px solid #f1f5f9;
        }
        .rl-chip {
            display: inline-flex; align-items: center; padding: 6px 12px;
            border-radius: 999px; font-size: 13px; font-weight: 700;
        }
        .rl-chip--primary { background: #eff6ff; color: #1d4ed8; }
        .rl-chip--muted   { background: #f1f5f9; color: #475569; }
        .rl-card--calendar .rl-card__body { padding: 22px; }

        /* Override stray legacy navbar margins */
        .notification .nav-link.nav-icons { margin-top: 0px !important; }
        .admin-panel.notification .nav-link.nav-icons .indicator { top: 15px !important; }

        @media (max-width: 575.98px) {
            .rl-dashboard { padding: 12px 14px 24px; }
            .rl-kpi__num { font-size: 22px; }
            .rl-page-head { padding: 14px; }
            .rl-page-head__h { font-size: 17px; }
        }
    </style>
@endpush
<!-- js  -->
@push('scripts')
    <script type="text/javascript" src="{{ static_asset('backend/js/charts/apexcharts.js') }}"></script>
    @include('backend.dashboard-charts')
    @include('backend.calender-js')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript"
        src="{{ static_asset('backend/js/date-range-picker/dashboard-date-range-picker-custom.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js"
        crossorigin="anonymous"></script>

    <script type="text/javascript">
        $('#datetimepicker12').datetimepicker({
            inline: true,
            sideBySide: true
        });
    </script>
@endpush
