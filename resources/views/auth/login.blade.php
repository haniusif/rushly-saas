@extends('auth.layouts')
@section('title','Login')
@section('body-class', 'login-bento')

@push('styles')
<style>
    :root {
        --bg-0: #0B0F1A;
        --bg-1: #131829;
        --bg-2: #1A2138;
        --bg-3: #232B47;
        --line: #1F2A44;
        --line-strong: #2C385A;
        --ink: #F4F6FB;
        --ink-muted: #8B95B0;
        --ink-dim: #5E6884;
        --cyan: #00E5FF;
        --cyan-soft: rgba(0, 229, 255, 0.14);
        --cyan-line: rgba(0, 229, 255, 0.30);
        --lime: #7CFFB2;
        --amber: #FFC857;
        --rose: #FF6B8A;
    }

    body.login-bento {
        background: var(--bg-0);
        color: var(--ink);
        display: block;
        min-height: 100vh;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        overflow-x: hidden;
        background-image:
            radial-gradient(800px 500px at 12% 8%, rgba(0,229,255,0.10), transparent 60%),
            radial-gradient(700px 600px at 88% 92%, rgba(124,255,178,0.06), transparent 60%);
    }

    body.login-bento.rtl {
        font-family: 'Tajawal', sans-serif;
    }

    .login-page-wrapper {
        padding: 0 !important;
        width: 100%;
        min-height: 100vh;
        display: grid;
        place-items: stretch;
    }

    /* Override base lang switcher to fit dark mode */
    body.login-bento .lang-switcher-fixed {
        background: rgba(19, 24, 41, 0.85);
        border: 1px solid var(--line-strong);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.4);
    }
    body.login-bento .lang-btn {
        color: var(--ink-muted);
    }
    body.login-bento .lang-btn.active {
        background: linear-gradient(135deg, var(--cyan), #4DD0FF);
        color: #06121C;
        box-shadow: 0 0 18px rgba(0,229,255,0.45);
    }
    body.login-bento .lang-btn:hover:not(.active) {
        background: var(--bg-2);
        color: var(--ink);
    }

    /* Override base container for full-bleed split */
    body.login-bento .auth-container {
        display: grid;
        grid-template-columns: minmax(420px, 1fr) 1.15fr;
        background: transparent;
        border-radius: 0;
        box-shadow: none;
        min-height: 100vh;
        width: 100%;
        max-width: none;
    }

    /* === LEFT: form === */
    body.login-bento .auth-form-section {
        background: linear-gradient(180deg, #0E1322 0%, #0B0F1A 100%);
        padding: 56px 64px;
        border-right: 1px solid var(--line);
    }
    body.login-bento.rtl .auth-form-section {
        border-right: none;
        border-left: 1px solid var(--line);
    }
    body.login-bento .auth-form-wrapper {
        max-width: 440px;
    }

    body.login-bento .auth-logo .logo-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #FFFFFF !important;
        padding: 10px 18px;
        border-radius: 14px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.35), 0 0 0 1px rgba(255,255,255,0.06);
        text-decoration: none;
    }
    body.login-bento .auth-logo .logo-chip img.logo-img {
        height: 44px !important;
        width: auto;
        object-fit: contain;
        display: block;
    }

    body.login-bento .auth-logo { text-align: left; }
    body.login-bento.rtl .auth-logo { text-align: right; }

    body.login-bento .auth-title {
        color: var(--ink);
        font-size: 30px;
        line-height: 1.15;
        letter-spacing: -0.02em;
        margin-top: 28px;
    }
    body.login-bento .auth-subtitle {
        color: var(--ink-muted);
        font-size: 15px;
    }

    body.login-bento .form-label {
        color: var(--ink);
        font-size: 13px;
        font-weight: 500;
        letter-spacing: 0.01em;
    }

    body.login-bento .input-wrapper {
        position: relative;
    }
    body.login-bento .input-icon { color: var(--ink-dim); }

    body.login-bento .form-control {
        background: var(--bg-1);
        border: 1px solid var(--line-strong);
        color: var(--ink);
        border-radius: 10px;
        padding: 14px 16px 14px 46px;
        font-size: 14.5px;
        transition: border-color .2s, box-shadow .2s, background .2s;
    }
    body.login-bento.rtl .form-control { padding: 14px 46px 14px 16px; }
    body.login-bento .form-control::placeholder { color: var(--ink-dim); }
    body.login-bento .form-control:focus {
        background: var(--bg-2);
        border-color: var(--cyan-line);
        box-shadow: 0 0 0 4px var(--cyan-soft);
    }
    body.login-bento .form-control.is-invalid {
        border-color: var(--rose);
        box-shadow: 0 0 0 4px rgba(255,107,138,0.12);
    }
    body.login-bento .password-toggle { color: var(--ink-dim); }
    body.login-bento .password-toggle:hover { color: var(--cyan); }
    body.login-bento .invalid-feedback { color: var(--rose); }

    body.login-bento input[type="checkbox"]#remember {
        appearance: none;
        -webkit-appearance: none;
        width: 16px; height: 16px;
        border: 1px solid var(--line-strong);
        background: var(--bg-1);
        border-radius: 4px;
        cursor: pointer;
        position: relative;
        transition: all .2s;
    }
    body.login-bento input[type="checkbox"]#remember:checked {
        background: var(--cyan);
        border-color: var(--cyan);
    }
    body.login-bento input[type="checkbox"]#remember:checked::after {
        content: "";
        position: absolute;
        left: 4px; top: 1px;
        width: 4px; height: 8px;
        border: solid #06121C;
        border-width: 0 2px 2px 0;
        transform: rotate(45deg);
    }
    body.login-bento .remember-row { color: var(--ink-muted); }

    body.login-bento .auth-link { color: var(--cyan); }
    body.login-bento .auth-link:hover { color: #66F0FF; text-decoration: none; }

    body.login-bento .btn-auth {
        background: linear-gradient(135deg, var(--cyan) 0%, #4DD0FF 100%);
        color: #06121C;
        border-radius: 10px;
        padding: 15px 24px;
        font-size: 15px;
        font-weight: 600;
        letter-spacing: 0.01em;
        box-shadow: 0 6px 20px rgba(0,229,255,0.25), inset 0 1px 0 rgba(255,255,255,0.3);
    }
    body.login-bento .btn-auth:hover {
        box-shadow: 0 10px 30px rgba(0,229,255,0.45), inset 0 1px 0 rgba(255,255,255,0.4);
        transform: translateY(-1px);
    }

    .meta-row {
        margin-top: 28px;
        display: flex;
        gap: 18px;
        flex-wrap: wrap;
        font-size: 12.5px;
        color: var(--ink-dim);
    }
    .meta-row a { color: var(--ink-muted); text-decoration: none; }
    .meta-row a:hover { color: var(--cyan); }
    .meta-sep { color: var(--line-strong); }

    /* === RIGHT: bento === */
    body.login-bento .auth-image-section {
        background:
            radial-gradient(600px 400px at 80% 10%, rgba(0,229,255,0.10), transparent 60%),
            radial-gradient(500px 500px at 15% 85%, rgba(124,255,178,0.07), transparent 60%),
            #0B0F1A;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 56px;
        position: relative;
    }
    /* Faint dot grid */
    body.login-bento .auth-image-section::before {
        content: "";
        position: absolute;
        inset: 0;
        background-image: radial-gradient(rgba(255,255,255,0.04) 1px, transparent 1px);
        background-size: 22px 22px;
        pointer-events: none;
    }

    .bento {
        position: relative;
        z-index: 2;
        width: 100%;
        max-width: 640px;
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        grid-auto-rows: 110px;
        gap: 14px;
    }
    .tile {
        background: linear-gradient(180deg, #1A2240 0%, #141A30 100%);
        border: 1px solid var(--line-strong);
        border-radius: 18px;
        padding: 18px 20px;
        position: relative;
        overflow: hidden;
        transition: transform .25s ease, border-color .25s ease, box-shadow .25s ease;
        display: flex;
        flex-direction: column;
        box-shadow: 0 4px 14px rgba(0,0,0,0.25), inset 0 1px 0 rgba(255,255,255,0.04);
    }
    .tile:hover {
        transform: translateY(-2px);
        border-color: var(--cyan-line);
        box-shadow: 0 12px 30px rgba(0,0,0,0.45), 0 0 0 1px var(--cyan-line);
    }
    .tile-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--ink-dim);
        font-weight: 600;
    }
    .tile-value {
        margin-top: 6px;
        font-size: 30px;
        line-height: 1.05;
        font-weight: 700;
        color: var(--ink);
        letter-spacing: -0.02em;
        font-variant-numeric: tabular-nums;
    }
    .tile-meta {
        margin-top: auto;
        font-size: 12.5px;
        color: var(--ink-muted);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .delta-up { color: var(--lime); font-weight: 600; }
    .delta-down { color: var(--rose); font-weight: 600; }

    .tile-hero { grid-column: span 4; grid-row: span 2; }
    .tile-stat { grid-column: span 2; grid-row: span 1; }
    .tile-wide { grid-column: span 4; grid-row: span 2; }
    .tile-tall { grid-column: span 2; grid-row: span 2; }
    .tile-quote { grid-column: span 6; grid-row: span 2; }

    /* Hero tile: parcels today */
    .tile-hero .hero-top {
        display: flex; align-items: center; gap: 12px;
    }
    .tile-hero .truck-badge {
        width: 38px; height: 38px;
        background: var(--cyan-soft);
        border: 1px solid var(--cyan-line);
        border-radius: 10px;
        display: grid; place-items: center;
        color: var(--cyan);
    }
    .tile-hero .big-number {
        margin-top: 14px;
        font-size: 56px;
        line-height: 1;
        font-weight: 800;
        letter-spacing: -0.04em;
        background: linear-gradient(180deg, #FFFFFF 0%, #B7C2DA 100%);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        font-variant-numeric: tabular-nums;
    }
    .tile-hero .sub {
        margin-top: 4px;
        color: var(--ink-muted);
        font-size: 13px;
    }
    .tile-hero .spark {
        margin-top: auto;
        height: 38px;
        width: 100%;
    }

    /* Tracking tile */
    .tile-wide .track-head {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 10px;
    }
    .tile-wide .track-id {
        font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, monospace;
        font-size: 12.5px;
        color: var(--ink);
        background: var(--bg-2);
        border: 1px solid var(--line-strong);
        padding: 4px 10px;
        border-radius: 6px;
    }
    .live-pill {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 600;
        color: var(--lime);
        background: rgba(124,255,178,0.10);
        border: 1px solid rgba(124,255,178,0.30);
        padding: 4px 10px; border-radius: 999px;
    }
    .live-dot {
        width: 6px; height: 6px; border-radius: 50%;
        background: var(--lime);
        box-shadow: 0 0 0 0 rgba(124,255,178,0.7);
        animation: pulse 1.6s infinite;
    }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(124,255,178,0.6); }
        70% { box-shadow: 0 0 0 8px rgba(124,255,178,0); }
        100% { box-shadow: 0 0 0 0 rgba(124,255,178,0); }
    }
    .progress-track {
        height: 8px;
        background: var(--bg-2);
        border-radius: 999px;
        overflow: hidden;
        margin: 14px 0 10px;
    }
    .progress-fill {
        height: 100%;
        width: 0;
        background: linear-gradient(90deg, var(--cyan), var(--lime));
        border-radius: 999px;
        animation: fill 1.6s ease-out forwards;
        box-shadow: 0 0 10px rgba(0,229,255,0.6);
    }
    @keyframes fill { to { width: var(--p, 70%); } }
    .stops {
        display: flex; justify-content: space-between;
        font-size: 11.5px; color: var(--ink-dim);
    }
    .stop-active { color: var(--cyan); font-weight: 600; }

    /* Tall tile (drivers) */
    .tile-tall .stack {
        display: flex; flex-direction: column; gap: 10px;
        margin-top: 12px; flex: 1;
    }
    .driver-row {
        display: flex; align-items: center; gap: 10px;
        font-size: 13px; color: var(--ink-muted);
    }
    .av {
        width: 26px; height: 26px; border-radius: 50%;
        background: linear-gradient(135deg, var(--cyan), #4DD0FF);
        color: #06121C; font-weight: 700; font-size: 11px;
        display: grid; place-items: center;
        border: 1px solid rgba(255,255,255,0.15);
    }
    .av.b { background: linear-gradient(135deg, var(--lime), #4FE69D); }
    .av.c { background: linear-gradient(135deg, var(--amber), #FFA642); }
    .status-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--lime); margin-left: auto; }
    body.login-bento.rtl .status-dot { margin-left: 0; margin-right: auto; }

    /* Quote tile */
    .tile-quote .stars {
        color: var(--amber); font-size: 14px; letter-spacing: 2px;
    }
    .tile-quote blockquote {
        margin: 10px 0 0;
        font-size: 17px;
        line-height: 1.45;
        color: var(--ink);
        font-weight: 500;
        letter-spacing: -0.005em;
    }
    .tile-quote .by {
        margin-top: auto;
        color: var(--ink-muted);
        font-size: 13px;
        display: flex; align-items: center; gap: 10px;
    }
    .by .av { width: 30px; height: 30px; font-size: 12px; }

    /* Marketing tile variants */
    .logo-strip {
        display: flex; align-items: center; gap: 18px; flex-wrap: wrap;
        margin-top: auto;
    }
    .logo-strip .l {
        color: var(--ink-muted);
        font-weight: 700;
        font-size: 14px;
        letter-spacing: 0.02em;
        opacity: 0.85;
    }
    .uptime-ring {
        width: 64px; height: 64px;
        border-radius: 50%;
        background: conic-gradient(var(--cyan) 0 99.9%, var(--bg-2) 0);
        display: grid; place-items: center;
        position: relative;
    }
    .uptime-ring::before {
        content: ""; position: absolute; inset: 5px;
        background: var(--bg-1); border-radius: 50%;
    }
    .uptime-ring span { position: relative; z-index: 1; font-size: 13px; font-weight: 700; color: var(--ink); }
    .globe-card {
        display: flex; align-items: center; gap: 14px;
    }
    .globe-card svg { color: var(--cyan); }

    /* Mobile */
    @media (max-width: 1024px) {
        body.login-bento .auth-container { grid-template-columns: 1fr; }
        body.login-bento .auth-image-section { display: none; }
        body.login-bento .auth-form-section { padding: 48px 28px; border: none; }
    }

    /* Animations (opacity stays 1, just slide-up) */
    @keyframes riseIn {
        from { transform: translateY(8px); }
        to { transform: translateY(0); }
    }
    @media (prefers-reduced-motion: no-preference) {
        .tile { animation: riseIn .5s ease-out both; }
        .tile:nth-child(1) { animation-delay: .04s; }
        .tile:nth-child(2) { animation-delay: .10s; }
        .tile:nth-child(3) { animation-delay: .16s; }
        .tile:nth-child(4) { animation-delay: .22s; }
        .tile:nth-child(5) { animation-delay: .28s; }
        .tile:nth-child(6) { animation-delay: .34s; }
    }
</style>
@endpush

@section('content')
@php $isTenant = function_exists('tenant') ? tenant() : null; @endphp

<div class="login-page-wrapper">
    <!-- Language Toggle (kept from layout) -->
    <div class="lang-switcher-fixed">
        <button class="lang-btn active" onclick="setLanguage('en')" id="lang-en">
            <img src="https://flagcdn.com/w20/gb.png" class="w-5 h-4 rounded-sm" alt="EN">
            <span>EN</span>
        </button>
        <button class="lang-btn" onclick="setLanguage('ar')" id="lang-ar">
            <img src="https://flagcdn.com/w20/sa.png" class="w-5 h-4 rounded-sm" alt="AR">
            <span>عربي</span>
        </button>
    </div>

    <div class="auth-container">
        <!-- ============== LEFT: FORM ============== -->
        <div class="auth-form-section">
            <div class="auth-form-wrapper">
                <div class="auth-logo">
                    <a href="{{ url('/') }}" class="logo-chip" aria-label="Rushly">
                        <img class="logo-img" src="{{ settings()->logo_image }}" alt="Rushly Logo">
                    </a>

                    @if($isTenant)
                        <h1 class="auth-title" data-en="Welcome back" data-ar="مرحباً بعودتك">Welcome back</h1>
                        <p class="auth-subtitle" data-en="Sign in to your operations console" data-ar="سجل دخولك إلى لوحة العمليات">Sign in to your operations console</p>
                    @else
                        <h1 class="auth-title" data-en="Sign in to Rushly" data-ar="تسجيل الدخول إلى راشلي">Sign in to Rushly</h1>
                        <p class="auth-subtitle" data-en="Access your logistics business account" data-ar="ادخل إلى حساب أعمال اللوجستيات">Access your logistics business account</p>
                    @endif
                </div>

                <form method="POST" action="{{ route('login') }}" class="auth-form">
                    @csrf

                    <div class="form-group">
                        <label class="form-label" data-en="Email or Mobile" data-ar="البريد الإلكتروني أو الجوال">Email or Mobile</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </span>
                            <input id="email" type="text"
                                class="form-control @error('email') is-invalid @enderror"
                                name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                                placeholder="you@company.com"
                                data-en-placeholder="you@company.com"
                                data-ar-placeholder="بريدك@الشركة.com">
                        </div>
                        @error('email')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" data-en="Password" data-ar="كلمة المرور">Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </span>
                            <input id="password" type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                name="password" required autocomplete="current-password"
                                placeholder="••••••••"
                                data-en-placeholder="••••••••"
                                data-ar-placeholder="••••••••">
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <svg id="eye-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="flex justify-between items-center mb-6 flex-wrap gap-3 remember-row">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <span class="text-sm" data-en="Remember me" data-ar="تذكرني">Remember me</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="auth-link text-sm" data-en="Forgot password?" data-ar="نسيت كلمة المرور؟">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn-auth">
                        <span data-en="Sign in" data-ar="تسجيل الدخول">Sign in</span>
                        <svg class="btn-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </form>

                <div class="meta-row">
                    <span data-en="Protected by enterprise-grade encryption" data-ar="محمي بتشفير على مستوى المؤسسات">Protected by enterprise-grade encryption</span>
                </div>
            </div>
        </div>

        <!-- ============== RIGHT: BENTO ============== -->
        <div class="auth-image-section">
            @if($isTenant)
                {{-- ========== OPS BENTO (admin.rushly.test) ========== --}}
                <div class="bento" aria-hidden="true">
                    <!-- Hero: parcels today -->
                    <div class="tile tile-hero">
                        <div class="hero-top">
                            <div class="truck-badge">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8h11v8H3zM14 11h4l3 3v2h-7z"/>
                                    <circle cx="7" cy="18" r="1.6" stroke-width="2"/>
                                    <circle cx="17" cy="18" r="1.6" stroke-width="2"/>
                                </svg>
                            </div>
                            <div>
                                <div class="tile-label" data-en="Parcels today" data-ar="الطرود اليوم">Parcels today</div>
                                <div class="sub" data-en="Across all hubs" data-ar="عبر جميع المراكز">Across all hubs</div>
                            </div>
                        </div>
                        <div class="big-number">1,247</div>
                        <svg class="spark" viewBox="0 0 200 40" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="sparkFill" x1="0" x2="0" y1="0" y2="1">
                                    <stop offset="0%" stop-color="#00E5FF" stop-opacity=".35"/>
                                    <stop offset="100%" stop-color="#00E5FF" stop-opacity="0"/>
                                </linearGradient>
                            </defs>
                            <path d="M0 30 L20 26 L40 28 L60 20 L80 22 L100 14 L120 18 L140 10 L160 14 L180 6 L200 9 L200 40 L0 40 Z" fill="url(#sparkFill)"/>
                            <path d="M0 30 L20 26 L40 28 L60 20 L80 22 L100 14 L120 18 L140 10 L160 14 L180 6 L200 9" fill="none" stroke="#00E5FF" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>

                    <!-- On-time -->
                    <div class="tile tile-stat">
                        <div class="tile-label" data-en="On-time" data-ar="في الوقت">On-time</div>
                        <div class="tile-value">94.6%</div>
                        <div class="tile-meta"><span class="delta-up">▲ 2.3%</span> <span data-en="vs last week" data-ar="مقابل الأسبوع الماضي">vs last week</span></div>
                    </div>

                    <!-- Revenue -->
                    <div class="tile tile-stat">
                        <div class="tile-label" data-en="Revenue" data-ar="الإيرادات">Revenue</div>
                        <div class="tile-value">$48.2K</div>
                        <div class="tile-meta"><span class="delta-up">▲ 12%</span> MTD</div>
                    </div>

                    <!-- Live tracking -->
                    <div class="tile tile-wide">
                        <div class="track-head">
                            <div>
                                <div class="tile-label" data-en="Live tracking" data-ar="تتبع مباشر">Live tracking</div>
                                <div class="track-id" style="margin-top:8px;">RUSH-8821</div>
                            </div>
                            <span class="live-pill"><span class="live-dot"></span> LIVE</span>
                        </div>
                        <div class="progress-track"><div class="progress-fill" style="--p: 87%"></div></div>
                        <div class="stops">
                            <span>Hub: Riyadh</span>
                            <span class="stop-active" data-en="Out for delivery" data-ar="في الطريق للتسليم">Out for delivery</span>
                            <span data-en="ETA 14:32" data-ar="الوصول 14:32">ETA 14:32</span>
                        </div>
                    </div>

                    <!-- Active drivers -->
                    <div class="tile tile-tall">
                        <div class="tile-label" data-en="Drivers online" data-ar="السائقون متصلون">Drivers online</div>
                        <div class="tile-value" style="font-size:26px;">42 / 58</div>
                        <div class="stack">
                            <div class="driver-row"><span class="av">AM</span> Ahmed M. <span class="status-dot"></span></div>
                            <div class="driver-row"><span class="av b">SK</span> Saad K. <span class="status-dot"></span></div>
                            <div class="driver-row"><span class="av c">RY</span> Rami Y. <span class="status-dot"></span></div>
                        </div>
                    </div>

                    <!-- Quote -->
                    <div class="tile tile-quote">
                        <div class="stars">★★★★★</div>
                        <blockquote data-en="“Cut our dispatch time by 40% and gave the team a single source of truth for every parcel.”" data-ar="“قلّل وقت الإرسال لدينا بنسبة 40٪ ومنح الفريق مصدراً واحداً للحقيقة لكل طرد.”">"Cut our dispatch time by 40% and gave the team a single source of truth for every parcel."</blockquote>
                        <div class="by">
                            <span class="av">AB</span>
                            <div>
                                <div style="color:var(--ink); font-weight:600;">Asher — Boutique Ops Lead</div>
                                <div style="font-size:12px;">Riyadh, KSA</div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                {{-- ========== MARKETING BENTO (rushly.test) ========== --}}
                <div class="bento" aria-hidden="true">
                    <!-- Hero: businesses -->
                    <div class="tile tile-hero">
                        <div class="hero-top">
                            <div class="truck-badge">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6"/>
                                </svg>
                            </div>
                            <div>
                                <div class="tile-label" data-en="Businesses" data-ar="الشركات">Businesses</div>
                                <div class="sub" data-en="Trust Rushly for fulfillment" data-ar="تثق براشلي للتنفيذ">Trust Rushly for fulfillment</div>
                            </div>
                        </div>
                        <div class="big-number">12,400+</div>
                        <div class="logo-strip">
                            <span class="l">ASHER</span>
                            <span class="l">OG×ST</span>
                            <span class="l">GMART</span>
                            <span class="l">TAMARA</span>
                        </div>
                    </div>

                    <!-- Uptime -->
                    <div class="tile tile-stat" style="align-items:flex-start;">
                        <div class="tile-label" data-en="Uptime" data-ar="مدة التشغيل">Uptime</div>
                        <div style="display:flex; align-items:center; gap:12px; margin-top:6px;">
                            <div class="uptime-ring"><span>99.9%</span></div>
                            <div style="font-size:12px; color:var(--ink-muted);" data-en="Last 90 days" data-ar="آخر 90 يوماً">Last 90 days</div>
                        </div>
                    </div>

                    <!-- Support -->
                    <div class="tile tile-stat">
                        <div class="tile-label" data-en="Support" data-ar="الدعم">Support</div>
                        <div class="tile-value">24 / 7</div>
                        <div class="tile-meta"><span class="delta-up">●</span> <span data-en="Avg response 4 min" data-ar="متوسط الاستجابة 4 دقائق">Avg response 4 min</span></div>
                    </div>

                    <!-- Coverage -->
                    <div class="tile tile-wide">
                        <div class="track-head">
                            <div>
                                <div class="tile-label" data-en="Coverage" data-ar="التغطية">Coverage</div>
                                <div style="font-size:13px; color:var(--ink); margin-top:6px;" data-en="14 countries · 380 cities" data-ar="14 دولة · 380 مدينة">14 countries · 380 cities</div>
                            </div>
                            <span class="live-pill"><span class="live-dot"></span> GROWING</span>
                        </div>
                        <div class="progress-track"><div class="progress-fill" style="--p: 72%"></div></div>
                        <div class="stops">
                            <span>MENA</span>
                            <span class="stop-active">GCC</span>
                            <span data-en="Africa (2026)" data-ar="أفريقيا (2026)">Africa (2026)</span>
                        </div>
                    </div>

                    <!-- Integrations -->
                    <div class="tile tile-tall">
                        <div class="tile-label" data-en="Integrations" data-ar="التكاملات">Integrations</div>
                        <div class="tile-value" style="font-size:26px;">60+</div>
                        <div class="stack">
                            <div class="driver-row"><span class="av">SH</span> Shopify <span class="status-dot"></span></div>
                            <div class="driver-row"><span class="av b">WC</span> WooCommerce <span class="status-dot"></span></div>
                            <div class="driver-row"><span class="av c">ZD</span> Zid <span class="status-dot"></span></div>
                        </div>
                    </div>

                    <!-- Quote -->
                    <div class="tile tile-quote">
                        <div class="stars">★★★★★</div>
                        <blockquote data-en="“Switching to Rushly let us scale across the Gulf without doubling our ops team. The API just works.”" data-ar="“الانتقال إلى راشلي مكّننا من التوسع في الخليج دون مضاعفة فريق العمليات. واجهة برمجة التطبيقات تعمل بكل بساطة.”">"Switching to Rushly let us scale across the Gulf without doubling our ops team. The API just works."</blockquote>
                        <div class="by">
                            <span class="av">OG</span>
                            <div>
                                <div style="color:var(--ink); font-weight:600;">OG Streetwear — Founder</div>
                                <div style="font-size:12px;">UAE</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
