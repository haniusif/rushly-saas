@extends('backend.partials.master')
@section('title')
    {{ __('menus.general_settings') }}
@endsection

@section('maincontent')
<style>
.gs-wrap{display:flex;gap:24px;align-items:flex-start}
.gs-nav{flex:0 0 240px;position:sticky;top:88px;background:#fff;border:1px solid #eef0f3;border-radius:10px;padding:10px;box-shadow:0 1px 2px rgba(16,24,40,.04)}
.gs-nav .nav-link{display:flex;align-items:center;gap:10px;padding:10px 12px;color:#475467;border-radius:8px;font-weight:500;cursor:pointer;border:0;background:transparent;width:100%;text-align:left}
.gs-nav .nav-link i{width:18px;text-align:center;opacity:.8}
.gs-nav .nav-link:hover{background:#f5f7fa;color:#101828}
.gs-nav .nav-link.active{background:#eef0ff;color:#3538cd}
.gs-nav .nav-link.active i{opacity:1}
.gs-body{flex:1;min-width:0}
.gs-section{background:#fff;border:1px solid #eef0f3;border-radius:10px;padding:24px 28px;display:none;box-shadow:0 1px 2px rgba(16,24,40,.04)}
.gs-section.active{display:block}
.gs-section h4{font-size:18px;font-weight:600;color:#101828;margin:0}
.gs-section .gs-sub{color:#667085;font-size:13px;margin-top:4px;margin-bottom:20px}
.gs-section hr{border:0;border-top:1px solid #eef0f3;margin:0 0 20px}
.gs-row{display:flex;gap:20px}
.gs-row > *{flex:1;min-width:0}
.gs-file-preview{display:flex;gap:14px;align-items:center;padding:12px;border:1px dashed #d0d5dd;border-radius:8px;background:#fafbfc}
.gs-file-preview.dark{background:#101828}
.gs-file-preview img{max-height:48px;max-width:120px;object-fit:contain}
.gs-file-preview .gs-file-input{flex:1}
.gs-color-swatch{display:flex;align-items:center;gap:10px}
.gs-color-swatch input[type=color]{width:48px;height:40px;padding:2px;border:1px solid #d0d5dd;border-radius:6px;cursor:pointer}
.gs-color-swatch .gs-color-hex{flex:1}
.gs-save-bar{display:flex;justify-content:flex-end;gap:10px;padding-top:16px;margin-top:24px;border-top:1px solid #eef0f3}
.gs-toggle{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 16px;border:1px solid #eef0f3;border-radius:8px;background:#fafbfc}
.gs-toggle .gs-toggle-text{flex:1}
.gs-toggle .gs-toggle-text strong{display:block;color:#101828;font-size:14px}
.gs-toggle .gs-toggle-text small{color:#667085}
.gs-switch{position:relative;display:inline-block;width:44px;height:24px;flex-shrink:0}
.gs-switch input{opacity:0;width:0;height:0}
.gs-switch .gs-slider{position:absolute;cursor:pointer;inset:0;background:#d0d5dd;border-radius:24px;transition:.2s}
.gs-switch .gs-slider:before{content:"";position:absolute;height:18px;width:18px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.2s;box-shadow:0 1px 2px rgba(16,24,40,.15)}
.gs-switch input:checked + .gs-slider{background:#3538cd}
.gs-switch input:checked + .gs-slider:before{transform:translateX(20px)}
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
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="breadcrumb-link">{{ __('dashboard.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link active">{{ __('menus.settings') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('general-settings.index') }}" class="breadcrumb-link active">{{ __('menus.general_settings') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('general-settings.update') }}" method="POST" enctype="multipart/form-data" id="basicform">
        @method('PUT')
        @csrf

        <div class="gs-wrap">
            {{-- left nav --}}
            <aside class="gs-nav">
                <div class="nav flex-column" role="tablist">
                    <button type="button" class="nav-link active" data-gs-target="brand"><i class="fa fa-tag"></i> {{ __('levels.application_name') }}</button>
                    <button type="button" class="nav-link" data-gs-target="contact"><i class="fa fa-address-book"></i> {{ __('levels.phone') }} / {{ __('levels.email') }}</button>
                    <button type="button" class="nav-link" data-gs-target="locale"><i class="fa fa-globe"></i> {{ __('levels.currency') }}</button>
                    <button type="button" class="nav-link" data-gs-target="theme"><i class="fa fa-paint-brush"></i> {{ __('levels.primary_color') }}</button>
                    <button type="button" class="nav-link" data-gs-target="logos"><i class="fa fa-image"></i> {{ __('levels.logo') }}</button>
                </div>
            </aside>

            {{-- right pane --}}
            <div class="gs-body">

                {{-- BRAND --}}
                <section class="gs-section active" data-gs-panel="brand">
                    <h4>{{ __('levels.application_name') }}</h4>
                    <p class="gs-sub">{{ __('menus.general_settings') }}</p>
                    <hr>

                    <div class="form-group">
                        <label for="name">{{ __('levels.application_name') }}</label>
                        <input id="name" type="text" name="name" placeholder="{{ __('placeholder.Enter_name') }}" autocomplete="off" class="form-control @error('name') is-invalid @enderror" value="{{ $settings->name }}" required>
                        @error('name')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                    </div>

                    <div class="form-group">
                        <label for="copyright">{{ __('levels.copyright') }}</label>
                        <input id="copyright" type="text" name="copyright" placeholder="{{ __('placeholder.Enter_copyright') }}" autocomplete="off" class="form-control @error('copyright') is-invalid @enderror" value="{{ $settings->copyright }}">
                        @error('copyright')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                    </div>

                    <div class="form-group">
                        <div class="gs-toggle">
                            <div class="gs-toggle-text">
                                <strong>Show landing page on <code>/</code></strong>
                                <small>When off, visiting <code>/</code> redirects to the login page instead of the public landing page.</small>
                            </div>
                            <label class="gs-switch" for="show_landing_page">
                                <input type="hidden" name="show_landing_page" value="0">
                                <input type="checkbox" id="show_landing_page" name="show_landing_page" value="1" {{ old('show_landing_page', $settings->show_landing_page) ? 'checked' : '' }}>
                                <span class="gs-slider"></span>
                            </label>
                        </div>
                    </div>
                </section>

                {{-- CONTACT --}}
                <section class="gs-section" data-gs-panel="contact">
                    <h4>{{ __('levels.phone') }} / {{ __('levels.email') }}</h4>
                    <p class="gs-sub">{{ __('levels.address') }}</p>
                    <hr>

                    <div class="gs-row">
                        <div class="form-group">
                            <label for="phone">{{ __('levels.phone') }}</label>
                            <input id="phone" type="text" name="phone" placeholder="{{ __('placeholder.Enter_phone') }}" autocomplete="off" class="form-control @error('phone') is-invalid @enderror" value="{{ $settings->phone }}" required>
                            @error('phone')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                        </div>
                        <div class="form-group">
                            <label for="email">{{ __('levels.email') }}</label>
                            <input id="email" type="email" name="email" placeholder="{{ __('placeholder.enter_email') }}" autocomplete="off" class="form-control @error('email') is-invalid @enderror" value="{{ $settings->email }}" required>
                            @error('email')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">{{ __('levels.address') }}</label>
                        <textarea id="address" name="address" rows="3" placeholder="{{ __('placeholder.enter_address') }}" class="form-control @error('address') is-invalid @enderror" required>{{ $settings->address }}</textarea>
                        @error('address')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                    </div>
                </section>

                {{-- LOCALIZATION --}}
                <section class="gs-section" data-gs-panel="locale">
                    <h4>{{ __('levels.currency') }}</h4>
                    <p class="gs-sub">{{ __('settings.parcel_tracking') }} / {{ __('invoice.invoice') }}</p>
                    <hr>

                    <div class="form-group">
                        <label for="currency">{{ __('levels.currency') }}</label>
                        <select class="form-control @error('currency') is-invalid @enderror" id="currency" name="currency" required>
                            <option value="" disabled>{{ __('placeholder.Enter_name') }}</option>
                            @forelse ($currencies as $currency)
                                <option value="{{ $currency->symbol }}" {{ $settings->currency == $currency->symbol ? 'selected' : '' }}>{{ @$currency->name }} {{ @$currency->symbol }}</option>
                            @empty
                                <option value="&#36;" {{ $settings->currency == '$' ? 'selected' : '' }}>Dollar &#36;</option>
                            @endforelse
                        </select>
                        @error('currency')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                    </div>

                    <div class="gs-row">
                        <div class="form-group">
                            <label for="par_track_prefix">{{ __('settings.parcel_tracking') }} {{ __('levels.prefix') }}</label>
                            <input id="par_track_prefix" type="text" name="par_track_prefix" class="form-control" placeholder="{{ __('settings.parcel_tracking') }} {{ __('levels.prefix') }}" value="{{ @\Illuminate\Support\Str::upper($settings->par_track_prefix) }}">
                        </div>
                        <div class="form-group">
                            <label for="invoice_prefix">{{ __('invoice.invoice') }} {{ __('levels.prefix') }}</label>
                            <input id="invoice_prefix" type="text" name="invoice_prefix" class="form-control" placeholder="{{ __('invoice.invoice') }} {{ __('levels.prefix') }}" value="{{ @\Illuminate\Support\Str::upper($settings->invoice_prefix) }}">
                        </div>
                    </div>
                </section>

                {{-- THEME --}}
                <section class="gs-section" data-gs-panel="theme">
                    <h4>{{ __('levels.primary_color') }} / {{ __('levels.text_color') }}</h4>
                    <p class="gs-sub">{{ __('menus.general_settings') }}</p>
                    <hr>

                    <div class="gs-row">
                        <div class="form-group">
                            <label for="primary_color">{{ __('levels.primary_color') }}</label>
                            <div class="gs-color-swatch">
                                <input id="primary_color" type="color" name="primary_color" value="{{ old('primary_color', settings()->primary_color) }}">
                                <input type="text" class="form-control gs-color-hex" value="{{ old('primary_color', settings()->primary_color) }}" data-gs-mirror="#primary_color" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="text_color">{{ __('levels.text_color') }}</label>
                            <div class="gs-color-swatch">
                                <input id="text_color" type="color" name="text_color" value="{{ old('text_color', settings()->text_color) }}">
                                <input type="text" class="form-control gs-color-hex" value="{{ old('text_color', settings()->text_color) }}" data-gs-mirror="#text_color" readonly>
                            </div>
                        </div>
                    </div>

                    {{-- Tenant default login layout (merchant overrides override this) --}}
                    <div class="gs-row">
                        <div class="form-group">
                            <label for="login_layout">{{ __('merchant.login_layout') }}</label>
                            <select id="login_layout" name="login_layout" class="form-control">
                                @foreach(['split','centered','fullbleed'] as $opt)
                                    <option value="{{ $opt }}" @selected(old('login_layout', settings()->login_layout ?? 'split') === $opt)>
                                        {{ __('merchant.login_layout_'.$opt) }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">{{ __('merchant.login_layout_help') }}</small>
                        </div>
                    </div>
                </section>

                {{-- LOGOS --}}
                <section class="gs-section" data-gs-panel="logos">
                    <h4>{{ __('levels.logo') }} / {{ __('levels.light_logo') }} / {{ __('levels.favicon') }}</h4>
                    <p class="gs-sub">{{ __('menus.general_settings') }}</p>
                    <hr>

                    <div class="form-group">
                        <label for="logo">{{ __('levels.logo') }}</label>
                        <div class="gs-file-preview">
                            <img src="{{ $settings->logo_image }}" alt="logo" id="logo-preview">
                            <div class="gs-file-input">
                                <input id="logo" type="file" name="logo" accept="image/*" class="form-control @error('logo') is-invalid @enderror" data-gs-preview="#logo-preview">
                                @error('logo')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="light_logo">{{ __('levels.light_logo') }}</label>
                        <div class="gs-file-preview dark">
                            <img src="{{ $settings->light_logo_image }}" alt="light logo" id="light-logo-preview">
                            <div class="gs-file-input">
                                <input id="light_logo" type="file" name="light_logo" accept="image/*" class="form-control @error('light_logo') is-invalid @enderror" data-gs-preview="#light-logo-preview">
                                @error('light_logo')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="favicon">{{ __('levels.favicon') }}</label>
                        <div class="gs-file-preview">
                            <img src="{{ $settings->favicon_image }}" alt="favicon" id="favicon-preview" style="max-height:32px;max-width:32px">
                            <div class="gs-file-input">
                                <input id="favicon" type="file" name="favicon" accept="image/*" class="form-control @error('favicon') is-invalid @enderror" data-gs-preview="#favicon-preview">
                                @error('favicon')<small class="text-danger mt-2">{{ $message }}</small>@enderror
                            </div>
                        </div>
                    </div>
                </section>

                @if(hasPermission('general_settings_update'))
                <div class="gs-save-bar">
                    <button type="submit" class="btn btn-primary btn-space">{{ __('levels.save_change') }}</button>
                </div>
                @endif
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // section switcher
    var navBtns = document.querySelectorAll('.gs-nav .nav-link');
    var panels  = document.querySelectorAll('.gs-section');
    navBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = btn.getAttribute('data-gs-target');
            navBtns.forEach(function (b) { b.classList.remove('active'); });
            panels.forEach(function (p) { p.classList.remove('active'); });
            btn.classList.add('active');
            var panel = document.querySelector('.gs-section[data-gs-panel="' + target + '"]');
            if (panel) panel.classList.add('active');
        });
    });

    // file preview
    document.querySelectorAll('input[type=file][data-gs-preview]').forEach(function (inp) {
        inp.addEventListener('change', function (e) {
            var file = e.target.files && e.target.files[0];
            if (!file) return;
            var img = document.querySelector(inp.getAttribute('data-gs-preview'));
            if (img) img.src = URL.createObjectURL(file);
        });
    });

    // color swatch <-> hex mirror
    document.querySelectorAll('input[type=color]').forEach(function (c) {
        c.addEventListener('input', function () {
            var mirror = document.querySelector('[data-gs-mirror="#' + c.id + '"]');
            if (mirror) mirror.value = c.value;
        });
    });
});
</script>
@endsection
