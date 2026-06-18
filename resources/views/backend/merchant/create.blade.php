@extends('backend.partials.master')
@section('title')
    {{ __('merchant.title') }} {{ __('levels.add') }}
@endsection

@section('maincontent')
<style>
.gs-wrap{display:flex;gap:24px;align-items:flex-start}
.gs-nav{flex:0 0 240px;position:sticky;top:88px;background:#fff;border:1px solid #eef0f3;border-radius:10px;padding:10px;box-shadow:0 1px 2px rgba(16,24,40,.04)}
.gs-nav .nav-link{display:flex;align-items:center;gap:10px;padding:10px 12px;color:#475467;border-radius:8px;font-weight:500;cursor:pointer;border:0;background:transparent;width:100%;text-align:left}
.gs-nav .nav-link i{width:18px;text-align:center;opacity:.8}
.gs-nav .nav-link:hover{background:#f5f7fa;color:#101828}
.gs-nav .nav-link.active{background:#eef0ff;color:#3538cd}
.gs-nav .nav-link .gs-step{margin-inline-start:auto;font-size:11px;color:#98a2b3;font-weight:600}
.gs-body{flex:1;min-width:0}
.gs-section{background:#fff;border:1px solid #eef0f3;border-radius:10px;padding:24px 28px;display:none;box-shadow:0 1px 2px rgba(16,24,40,.04)}
.gs-section.active{display:block}
.gs-section h4{font-size:18px;font-weight:600;color:#101828;margin:0}
.gs-section .gs-sub{color:#667085;font-size:13px;margin-top:4px;margin-bottom:20px}
.gs-section hr{border:0;border-top:1px solid #eef0f3;margin:0 0 20px}
.gs-row{display:flex;gap:20px;flex-wrap:wrap}
.gs-row > *{flex:1;min-width:220px}
.gs-required{color:#d92d20;margin-inline-start:2px}
.gs-file-preview{display:flex;gap:14px;align-items:center;padding:12px;border:1px dashed #d0d5dd;border-radius:8px;background:#fafbfc}
.gs-file-preview img{max-height:48px;max-width:120px;object-fit:contain}
.gs-file-preview .gs-file-input{flex:1}
.gs-helper{font-size:12px;color:#667085;margin-top:4px}
.gs-save-bar{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:14px 18px;margin-top:16px;background:#fff;border:1px solid #eef0f3;border-radius:10px;box-shadow:0 1px 2px rgba(16,24,40,.04)}
.gs-save-bar .gs-actions{display:flex;gap:8px}
.gs-pwd-wrap{position:relative}
.gs-pwd-wrap .gs-pwd-toggle{position:absolute;top:50%;transform:translateY(-50%);inset-inline-end:10px;background:transparent;border:0;color:#667085;cursor:pointer;font-size:12px}
.gs-cod-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px}
.gs-cod-grid .gs-cod-item{padding:12px;border:1px solid #eef0f3;border-radius:8px;background:#fafbfc}
.gs-cod-grid .gs-cod-item label{font-size:12px;color:#475467;text-transform:capitalize;margin-bottom:6px;display:block}
@media (max-width: 991px){
    .gs-wrap{flex-direction:column}
    .gs-nav{position:static;width:100%;flex:none}
    .gs-nav .nav{display:flex;flex-wrap:wrap}
    .gs-nav .nav-link{flex:1 1 auto}
}
</style>

<div class="container-fluid dashboard-content">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('merchantmanage.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('merchant.index') }}" class="breadcrumb-link">{{ __('merchant.title') }}</a></li>
                            <li class="breadcrumb-item active" aria-current="page">{{ __('levels.create') }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('merchant.store') }}" method="POST" enctype="multipart/form-data" id="merchant-create-form">
        @csrf

        <div class="gs-wrap">
            {{-- left nav --}}
            <aside class="gs-nav">
                <div class="nav flex-column" role="tablist">
                    <button type="button" class="nav-link active" data-gs-target="account">
                        <i class="fa fa-user"></i> {{ __('levels.name') }} / {{ __('levels.email') }} <span class="gs-step">1</span>
                    </button>
                    <button type="button" class="nav-link" data-gs-target="business">
                        <i class="fa fa-briefcase"></i> {{ __('levels.business_name') }} <span class="gs-step">2</span>
                    </button>
                    <button type="button" class="nav-link" data-gs-target="documents">
                        <i class="fa fa-file"></i> {{ __('levels.nid') }} / {{ __('levels.trade_license') }} <span class="gs-step">3</span>
                    </button>
                    <button type="button" class="nav-link" data-gs-target="billing">
                        <i class="fa fa-credit-card"></i> {{ __('levels.opening_balance') }} <span class="gs-step">4</span>
                    </button>
                    <button type="button" class="nav-link" data-gs-target="cod">
                        <i class="fa fa-money-bill"></i> {{ __('levels.cod_charge') }} <span class="gs-step">5</span>
                    </button>
                </div>
            </aside>

            <div class="gs-body">

                {{-- 1. ACCOUNT --}}
                <section class="gs-section active" data-gs-panel="account">
                    <h4>{{ __('levels.name') }} / {{ __('levels.email') }}</h4>
                    <p class="gs-sub">{{ __('merchant.create_merchant') }}</p>
                    <hr>

                    <div class="gs-row">
                        <div class="form-group">
                            <label for="name">{{ __('levels.name') }}<span class="gs-required">*</span></label>
                            <input id="name" type="text" name="name" placeholder="{{ __('placeholder.Enter_name') }}" autocomplete="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                        </div>

                        <div class="form-group">
                            <label for="mobile">{{ __('levels.phone') }}<span class="gs-required">*</span></label>
                            <input id="mobile" type="tel" name="mobile" inputmode="tel" pattern="[0-9+\- ]{6,20}" placeholder="{{ __('placeholder.Enter_mobile') }}" autocomplete="tel" class="form-control @error('mobile') is-invalid @enderror" value="{{ old('mobile') }}" required>
                            <div class="gs-helper">{{ __('placeholder.Enter_mobile') }} — digits, +, - and space allowed.</div>
                            @error('mobile')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">{{ __('levels.email') }}</label>
                        <input id="email" type="email" name="email" placeholder="{{ __('placeholder.enter_email') }}" autocomplete="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                        @error('email')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                    </div>

                    <div class="form-group">
                        <label for="password">{{ __('levels.password') }}<span class="gs-required">*</span></label>
                        <div class="gs-pwd-wrap">
                            <input id="password" type="password" name="password" minlength="6" placeholder="{{ __('placeholder.Enter_password') }}" autocomplete="new-password" class="form-control @error('password') is-invalid @enderror" required>
                            <button type="button" class="gs-pwd-toggle" data-gs-pwd-target="#password">{{ __('levels.show') ?? 'Show' }}</button>
                        </div>
                        @error('password')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                    </div>
                </section>

                {{-- 2. BUSINESS --}}
                <section class="gs-section" data-gs-panel="business">
                    <h4>{{ __('levels.business_name') }}</h4>
                    <p class="gs-sub">{{ __('levels.address') }} / {{ __('levels.hub') }} / {{ __('levels.status') }}</p>
                    <hr>

                    <div class="form-group">
                        <label for="business_name">{{ __('levels.business_name') }}<span class="gs-required">*</span></label>
                        <input id="business_name" type="text" name="business_name" placeholder="{{ __('placeholder.enter_business_name') }}" autocomplete="organization" class="form-control @error('business_name') is-invalid @enderror" value="{{ old('business_name') }}" required>
                        @error('business_name')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                    </div>

                    <div class="form-group">
                        <label for="address">{{ __('levels.address') }}<span class="gs-required">*</span></label>
                        <textarea id="address" name="address" rows="3" placeholder="{{ __('placeholder.Enter_address') }}" class="form-control @error('address') is-invalid @enderror" required>{{ old('address') }}</textarea>
                        @error('address')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                    </div>

                    <div class="gs-row">
                        <div class="form-group">
                            <label for="hub">{{ __('levels.hub') }}<span class="gs-required">*</span></label>
                            <div style="display:flex;gap:6px">
                                <select class="form-control @error('hub') is-invalid @enderror" id="hub" name="hub" required style="flex:1">
                                    <option value="" disabled {{ old('hub') ? '' : 'selected' }}>{{ __('menus.select') }} {{ __('hub.title') }}</option>
                                    @foreach($hubs as $hub)
                                        <option value="{{ $hub->id }}" {{ old('hub') == $hub->id ? 'selected' : '' }}>{{ $hub->name }}</option>
                                    @endforeach
                                </select>
                                @if(hasPermission('hub_create'))
                                <button type="button" class="btn btn-outline-primary" id="gs-add-hub-btn" title="{{ __('hub.title') }} {{ __('levels.add') }}" style="flex:0 0 42px;display:inline-flex;align-items:center;justify-content:center;font-size:18px;line-height:1">+</button>
                                @endif
                            </div>
                            @error('hub')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                        </div>

                        <div class="form-group">
                            <label for="status">{{ __('levels.status') }}<span class="gs-required">*</span></label>
                            <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>{{ __('levels.active') }}</option>
                                <option value="0" {{ old('status') === '0' ? 'selected' : '' }}>{{ __('levels.inactive') }}</option>
                            </select>
                            @error('status')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="image_id">{{ __('levels.image') }}</label>
                        <div class="gs-file-preview">
                            <img src="" alt="" id="image_id-preview" style="display:none">
                            <div class="gs-file-input">
                                <input id="image_id" type="file" name="image_id" accept="image/*" class="form-control @error('image_id') is-invalid @enderror" data-gs-preview="#image_id-preview">
                                <div class="gs-helper">JPG / PNG, up to 2 MB.</div>
                                @error('image_id')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                            </div>
                        </div>
                    </div>
                </section>

                {{-- 3. DOCUMENTS --}}
                <section class="gs-section" data-gs-panel="documents">
                    <h4>{{ __('levels.nid') }} / {{ __('levels.trade_license') }}</h4>
                    <p class="gs-sub">{{ __('merchant.create_merchant') }}</p>
                    <hr>

                    <div class="form-group">
                        <label for="nid">{{ __('levels.nid') }}<span class="gs-required">*</span></label>
                        <div class="gs-file-preview">
                            <img src="" alt="" id="nid-preview" style="display:none">
                            <div class="gs-file-input">
                                <input id="nid" type="file" name="nid" accept="image/*,application/pdf" class="form-control @error('nid') is-invalid @enderror" data-gs-preview="#nid-preview" required>
                                <div class="gs-helper">Image or PDF.</div>
                                @error('nid')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="trade_license">{{ __('levels.trade_license') }}<span class="gs-required">*</span></label>
                        <div class="gs-file-preview">
                            <img src="" alt="" id="trade_license-preview" style="display:none">
                            <div class="gs-file-input">
                                <input id="trade_license" type="file" name="trade_license" accept="image/*,application/pdf" class="form-control @error('trade_license') is-invalid @enderror" data-gs-preview="#trade_license-preview" required>
                                <div class="gs-helper">Image or PDF.</div>
                                @error('trade_license')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                            </div>
                        </div>
                    </div>
                </section>

                {{-- 4. BILLING --}}
                <section class="gs-section" data-gs-panel="billing">
                    <h4>{{ __('levels.opening_balance') }} / {{ __('levels.vat') }}</h4>
                    <p class="gs-sub">{{ __('levels.payment_period') }} / {{ __('levels.return_charges') }} / {{ __('levels.reference') }}</p>
                    <hr>

                    <div class="gs-row">
                        <div class="form-group">
                            <label for="opening_balance">{{ __('levels.opening_balance') }}</label>
                            <input id="opening_balance" type="number" name="opening_balance" step="0.01" min="0" placeholder="{{ __('placeholder.Enter_opening_balance') }}" class="form-control @error('opening_balance') is-invalid @enderror" value="{{ old('opening_balance', 0) }}">
                            @error('opening_balance')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                        </div>

                        <div class="form-group">
                            <label for="vat">{{ __('levels.vat') }} (%)</label>
                            <input id="vat" type="number" name="vat" step="0.01" min="0" max="100" placeholder="{{ __('placeholder.Enter_vat') }}" class="form-control @error('vat') is-invalid @enderror" value="{{ old('vat', 0) }}">
                            @error('vat')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                        </div>
                    </div>

                    <div class="gs-row">
                        <div class="form-group">
                            <label for="payment_period">{{ __('levels.payment_period') }}</label>
                            <input id="payment_period" type="number" name="payment_period" min="0" placeholder="{{ __('levels.payment_period') }}" class="form-control" value="{{ old('payment_period', 2) }}">
                        </div>

                        <div class="form-group">
                            <label for="return_charges">{{ __('levels.return_charges') }} (%)</label>
                            <input id="return_charges" type="number" name="return_charges" min="0" max="100" placeholder="{{ __('levels.return_charges') }}" class="form-control" value="{{ old('return_charges', 100) }}">
                        </div>

                        <div class="form-group">
                            <label for="wallet_use_activation">{{ __('levels.wallet_use_activation') }}</label>
                            <select id="wallet_use_activation" name="wallet_use_activation" class="form-control">
                                <option value="{{ App\Enums\Status::ACTIVE }}" {{ (string) old('wallet_use_activation', App\Enums\Status::ACTIVE) === (string) App\Enums\Status::ACTIVE ? 'selected' : '' }}>{{ __('levels.active') }}</option>
                                <option value="{{ App\Enums\Status::INACTIVE }}" {{ (string) old('wallet_use_activation') === (string) App\Enums\Status::INACTIVE ? 'selected' : '' }}>{{ __('levels.inactive') }}</option>
                            </select>
                        </div>
                    </div>

                    <h5 class="mt-4" style="font-size:14px;color:#475467;font-weight:600">{{ __('levels.reference') }}</h5>
                    <hr>

                    <div class="gs-row">
                        <div class="form-group">
                            <label for="reference_name">{{ __('levels.reference_name') }}</label>
                            <input id="reference_name" type="text" name="reference_name" placeholder="{{ __('levels.reference_name') }}" class="form-control" value="{{ old('reference_name') }}">
                        </div>

                        <div class="form-group">
                            <label for="reference_phone">{{ __('levels.reference_phone') }}</label>
                            <input id="reference_phone" type="tel" name="reference_phone" inputmode="tel" pattern="[0-9+\- ]{6,20}" placeholder="{{ __('levels.reference_phone') }}" class="form-control" value="{{ old('reference_phone') }}">
                        </div>
                    </div>
                </section>

                {{-- 4.5 CUSTOM THEME --}}
                <section class="gs-section" data-gs-panel="theme">
                    <h4>{{ __('merchant.custom_theme') }}</h4>
                    <p class="gs-sub">{{ __('merchant.custom_theme_help') }}</p>
                    <hr>

                    <h5 class="mt-3" style="font-size:13px;color:#475467;font-weight:600">{{ __('merchant.theme_section_colors') }}</h5>
                    <div class="gs-row">
                        @foreach([
                            ['primary_color',      '#a21f5c'],
                            ['text_color',         '#ffffff'],
                            ['sidebar_color',      '#0f172a'],
                            ['sidebar_text_color', '#f1f5f9'],
                            ['topbar_color',       '#ffffff'],
                            ['topbar_text_color',  '#0f172a'],
                            ['accent_color',       '#0ea5e9'],
                        ] as [$name, $fallback])
                            <div class="form-group">
                                <label for="{{ $name }}">{{ __('merchant.'.$name) }}</label>
                                <div class="input-group">
                                    <input type="color" id="{{ $name }}_picker" class="form-control rl-theme-swatch" style="max-width:60px;padding:4px" value="{{ old($name, $fallback) }}" data-target="{{ $name }}">
                                    <input type="text" id="{{ $name }}" name="{{ $name }}" class="form-control rl-theme-hex" maxlength="7" pattern="^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$" placeholder="{{ __('merchant.theme_inherit') }}" value="{{ old($name) }}" data-picker="{{ $name }}_picker">
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <h5 class="mt-3" style="font-size:13px;color:#475467;font-weight:600">{{ __('merchant.theme_section_layout') }}</h5>
                    <div class="gs-row">
                        <div class="form-group">
                            <label for="sidebar_style">{{ __('merchant.sidebar_style') }}</label>
                            <select id="sidebar_style" name="sidebar_style" class="form-control rl-theme-select">
                                <option value="">{{ __('merchant.theme_inherit') }}</option>
                                @foreach(['dark','light','brand'] as $k)
                                    <option value="{{ $k }}" @selected(old('sidebar_style') === $k)>{{ __('merchant.sidebar_style_'.$k) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="font_family">{{ __('merchant.font_family') }}</label>
                            <select id="font_family" name="font_family" class="form-control rl-theme-select">
                                <option value="">{{ __('merchant.theme_inherit') }}</option>
                                @foreach(['inter','cairo','tajawal','roboto','system'] as $k)
                                    <option value="{{ $k }}" @selected(old('font_family') === $k)>{{ __('merchant.font_'.$k) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="border_radius">{{ __('merchant.border_radius') }}</label>
                            <select id="border_radius" name="border_radius" class="form-control rl-theme-select">
                                <option value="">{{ __('merchant.theme_inherit') }}</option>
                                @foreach(['sharp','default','rounded'] as $k)
                                    <option value="{{ $k }}" @selected(old('border_radius') === $k)>{{ __('merchant.border_radius_'.$k) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="density">{{ __('merchant.density') }}</label>
                            <select id="density" name="density" class="form-control rl-theme-select">
                                <option value="">{{ __('merchant.theme_inherit') }}</option>
                                @foreach(['comfortable','dense'] as $k)
                                    <option value="{{ $k }}" @selected(old('density') === $k)>{{ __('merchant.density_'.$k) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <h5 class="mt-3" style="font-size:13px;color:#475467;font-weight:600">{{ __('merchant.theme_section_logos') }}</h5>
                    <div class="gs-row">
                        <div class="form-group">
                            <label for="logo">{{ __('merchant.logo_dark') }}</label>
                            <input id="logo" type="file" name="logo" accept="image/*" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="light_logo">{{ __('merchant.logo_light') }}</label>
                            <input id="light_logo" type="file" name="light_logo" accept="image/*" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="favicon">{{ __('merchant.favicon') }}</label>
                            <input id="favicon" type="file" name="favicon" accept=".ico,image/*" class="form-control">
                            <small class="text-muted">{{ __('merchant.favicon_help') }}</small>
                        </div>
                    </div>
                </section>

                {{-- 5. COD CHARGES --}}
                <section class="gs-section" data-gs-panel="cod">
                    <h4>{{ __('levels.cod_charge') }}</h4>
                    <p class="gs-sub">{{ __('merchant.create_merchant') }}</p>
                    <hr>

                    <div class="gs-cod-grid">
                        @foreach(\Config::get('rxcourier.cod_charges') as $key => $charge)
                            <div class="gs-cod-item">
                                <input type="hidden" value="{{ $key }}" name="area[]">
                                <label for="charge-{{ $key }}">{{ str_replace('_', ' ', ucwords($key)) }}</label>
                                <input id="charge-{{ $key }}" type="number" name="charge[{{ $key }}]" step="0.01" min="0" class="form-control" value="{{ old('charge.'.$key, $charge) }}" placeholder="0.00">
                            </div>
                        @endforeach
                    </div>
                </section>

                {{-- save bar --}}
                <div class="gs-save-bar">
                    <span class="gs-helper" id="gs-active-section">{{ __('levels.create') }} {{ __('merchant.title') }}</span>
                    <div class="gs-actions">
                        <a href="{{ route('merchant.index') }}" class="btn btn-secondary">{{ __('levels.cancel') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('levels.save') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @if(hasPermission('hub_create'))
    {{-- quick-add hub modal --}}
    <div id="gs-hub-modal" style="display:none;position:fixed;inset:0;background:rgba(16,24,40,.5);z-index:1050;align-items:center;justify-content:center;padding:16px">
        <div style="background:#fff;border-radius:12px;width:100%;max-width:480px;box-shadow:0 20px 25px -5px rgba(0,0,0,.1)">
            <div style="padding:20px 24px;border-bottom:1px solid #eef0f3;display:flex;justify-content:space-between;align-items:center">
                <h5 style="margin:0;font-size:16px;font-weight:600;color:#101828">{{ __('hub.title') }} {{ __('levels.add') }}</h5>
                <button type="button" id="gs-hub-modal-close" style="background:transparent;border:0;color:#667085;font-size:22px;line-height:1;cursor:pointer">&times;</button>
            </div>
            <div style="padding:20px 24px">
                <div id="gs-hub-modal-error" class="alert alert-danger" style="display:none;font-size:13px;padding:8px 12px;margin-bottom:12px"></div>
                <div class="form-group">
                    <label for="gs-hub-name">{{ __('levels.name') }}<span class="gs-required">*</span></label>
                    <input id="gs-hub-name" type="text" class="form-control" placeholder="{{ __('placeholder.Enter_name') }}">
                </div>
                <div class="form-group">
                    <label for="gs-hub-phone">{{ __('levels.phone') }}<span class="gs-required">*</span></label>
                    <input id="gs-hub-phone" type="tel" inputmode="tel" class="form-control" placeholder="{{ __('placeholder.Enter_phone') }}">
                </div>
                <div class="form-group">
                    <label for="gs-hub-address">{{ __('levels.address') }}<span class="gs-required">*</span></label>
                    <textarea id="gs-hub-address" rows="2" class="form-control" placeholder="{{ __('placeholder.Enter_address') }}"></textarea>
                </div>
            </div>
            <div style="padding:14px 24px;border-top:1px solid #eef0f3;display:flex;justify-content:flex-end;gap:8px">
                <button type="button" class="btn btn-secondary" id="gs-hub-modal-cancel">{{ __('levels.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="gs-hub-modal-save">{{ __('levels.save') }}</button>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var navBtns = document.querySelectorAll('.gs-nav .nav-link');
    var panels  = document.querySelectorAll('.gs-section');
    var label   = document.getElementById('gs-active-section');

    navBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = btn.getAttribute('data-gs-target');
            navBtns.forEach(function (b) { b.classList.remove('active'); });
            panels.forEach(function (p) { p.classList.remove('active'); });
            btn.classList.add('active');
            var panel = document.querySelector('.gs-section[data-gs-panel="' + target + '"]');
            if (panel) panel.classList.add('active');
            if (label) label.textContent = btn.innerText.replace(/\s+\d+\s*$/, '').trim();
        });
    });

    // file preview
    document.querySelectorAll('input[type=file][data-gs-preview]').forEach(function (inp) {
        inp.addEventListener('change', function (e) {
            var file = e.target.files && e.target.files[0];
            var img  = document.querySelector(inp.getAttribute('data-gs-preview'));
            if (!file || !img) return;
            if (file.type && file.type.startsWith('image/')) {
                img.src = URL.createObjectURL(file);
                img.style.display = 'block';
            } else {
                img.style.display = 'none';
            }
        });
    });

    // password visibility toggle
    document.querySelectorAll('[data-gs-pwd-target]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.querySelector(btn.getAttribute('data-gs-pwd-target'));
            if (!input) return;
            var hidden = input.type === 'password';
            input.type = hidden ? 'text' : 'password';
            btn.textContent = hidden ? 'Hide' : 'Show';
        });
    });

    // jump to first invalid section on submit failure
    var form = document.getElementById('merchant-create-form');
    if (form) {
        form.addEventListener('invalid', function (e) {
            var field = e.target;
            var section = field.closest('.gs-section');
            if (!section) return;
            var panel = section.getAttribute('data-gs-panel');
            var btn = document.querySelector('.gs-nav .nav-link[data-gs-target="' + panel + '"]');
            if (btn) btn.click();
        }, true);
    }

    // quick-add hub
    var hubModal = document.getElementById('gs-hub-modal');
    var hubBtn   = document.getElementById('gs-add-hub-btn');
    if (hubBtn && hubModal) {
        var nameEl    = document.getElementById('gs-hub-name');
        var phoneEl   = document.getElementById('gs-hub-phone');
        var addressEl = document.getElementById('gs-hub-address');
        var errEl     = document.getElementById('gs-hub-modal-error');
        var saveBtn   = document.getElementById('gs-hub-modal-save');
        var closeEls  = [document.getElementById('gs-hub-modal-close'), document.getElementById('gs-hub-modal-cancel')];
        var hubSelect = document.getElementById('hub');

        function openModal() {
            errEl.style.display = 'none';
            errEl.textContent   = '';
            nameEl.value = phoneEl.value = addressEl.value = '';
            hubModal.style.display = 'flex';
            setTimeout(function () { nameEl.focus(); }, 50);
        }
        function closeModal() { hubModal.style.display = 'none'; }

        hubBtn.addEventListener('click', openModal);
        closeEls.forEach(function (el) { el && el.addEventListener('click', closeModal); });
        hubModal.addEventListener('click', function (e) { if (e.target === hubModal) closeModal(); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && hubModal.style.display === 'flex') closeModal();
        });

        saveBtn.addEventListener('click', function () {
            errEl.style.display = 'none';
            errEl.textContent   = '';

            if (!nameEl.value.trim() || !phoneEl.value.trim() || !addressEl.value.trim()) {
                errEl.textContent = 'Name, phone and address are required.';
                errEl.style.display = 'block';
                return;
            }

            var token = document.querySelector('meta[name=csrf-token]');
            var csrf  = token ? token.getAttribute('content')
                              : (document.querySelector('input[name=_token]') || {}).value;
            var fd = new FormData();
            fd.append('name',    nameEl.value.trim());
            fd.append('phone',   phoneEl.value.trim());
            fd.append('address', addressEl.value.trim());
            fd.append('_token',  csrf);

            saveBtn.disabled = true;
            saveBtn.textContent = '...';

            fetch('{{ route('hubs.quick-store') }}', {
                method: 'POST',
                body: fd,
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
            .then(function (r) { return r.json().then(function (j) { return { status: r.status, body: j }; }); })
            .then(function (res) {
                saveBtn.disabled = false;
                saveBtn.textContent = '{{ __('levels.save') }}';
                if (res.status === 200 && res.body.ok) {
                    var opt = document.createElement('option');
                    opt.value = res.body.hub.id;
                    opt.textContent = res.body.hub.name;
                    opt.selected = true;
                    hubSelect.appendChild(opt);
                    closeModal();
                } else if (res.body && res.body.errors) {
                    var msgs = [];
                    Object.keys(res.body.errors).forEach(function (k) {
                        (res.body.errors[k] || []).forEach(function (m) { msgs.push(m); });
                    });
                    errEl.textContent = msgs.join(' ');
                    errEl.style.display = 'block';
                } else {
                    errEl.textContent = (res.body && res.body.message) || 'Could not save the hub.';
                    errEl.style.display = 'block';
                }
            })
            .catch(function () {
                saveBtn.disabled = false;
                saveBtn.textContent = '{{ __('levels.save') }}';
                errEl.textContent = 'Network error.';
                errEl.style.display = 'block';
            });
        });
    }
});
</script>
@endsection
