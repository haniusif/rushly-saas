@extends('auth.layouts')
@section('title','Reset Password')
@section('content')

<div class="reset-password-page-wrapper" style="width: 100%; padding: 20px;">
    <!-- Language Toggle -->
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
        <!-- Left Side - Form -->
        <div class="auth-form-section">
            <div class="auth-form-wrapper">
                <!-- Logo -->
                <div class="auth-logo text-center mb-8">
                    <a href="{{ url('/') }}" class="navbar-brand inline-block">
                        <img class="logo-img" src="{{ settings()->logo_image }}" alt="Rushly Logo">
                    </a>
                    <h1 class="auth-title mt-6" data-en="Reset Password" data-ar="إعادة تعيين كلمة المرور">Reset Password</h1>
                    <p class="auth-subtitle" data-en="Don't worry, we'll send you an email to reset your password" data-ar="لا تقلق، سنرسل لك بريدًا إلكترونيًا لإعادة تعيين كلمة المرور">Don't worry, we'll send you an email to reset your password</p>
                </div>

                <!-- Success Message -->
                @if (session('status'))
                    <div class="alert alert-success mb-6">
                        <div class="flex items-center gap-3">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>{{ session('status') }}</span>
                        </div>
                    </div>
                @endif

                <!-- Reset Password Form -->
                <form method="POST" action="{{ route('password.email') }}" class="auth-form">
                    @csrf
                    
                    <!-- Email Input -->
                    <div class="form-group">
                        <label class="form-label" data-en="Email Address" data-ar="البريد الإلكتروني">Email Address</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                </svg>
                            </span>
                            <input id="email" type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                                placeholder="Enter your email address"
                                data-en-placeholder="Enter your email address"
                                data-ar-placeholder="أدخل بريدك الإلكتروني">
                        </div>
                        @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-auth">
                        <span data-en="Send Reset Link" data-ar="إرسال رابط إعادة التعيين">Send Reset Link</span>
                        <svg class="btn-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>

                    <!-- Back to Login -->
                    <div class="back-link">
                        <a href="{{ route('login') }}" class="back-btn">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            <span data-en="Back to Login" data-ar="العودة لتسجيل الدخول">Back to Login</span>
                        </a>
                    </div>

                    <!-- Sign Up Link -->
                    <div class="signup-link">
                        <span data-en="Don't have an account?" data-ar="ليس لديك حساب؟">Don't have an account?</span>
                        @if(tenant())
                            <a href="{{ route('register') }}" class="signup-btn" data-en="Sign Up" data-ar="إنشاء حساب">Sign Up</a>
                        @else
                            <a href="{{ route('company.sign-up') }}" class="signup-btn" data-en="Sign Up" data-ar="إنشاء حساب">Sign Up</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Side - Image/Branding -->
        <div class="auth-image-section">
            <div class="auth-image-overlay">
                <div class="auth-branding">
                    <h2 data-en="Secure Password Recovery" data-ar="استعادة كلمة المرور الآمنة">Secure Password Recovery</h2>
                    <p data-en="We'll send you a secure link to reset your password in seconds" data-ar="سنرسل لك رابطًا آمنًا لإعادة تعيين كلمة المرور في ثوانٍ">We'll send you a secure link to reset your password in seconds</p>
                    
                    <div class="feature-list">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <span data-en="Secure & Encrypted" data-ar="آمن ومشفّر">Secure & Encrypted</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <span data-en="Quick Process" data-ar="عملية سريعة">Quick Process</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <span data-en="Email Verification" data-ar="التحقق عبر البريد">Email Verification</span>
                        </div>
                    </div>
                </div>
                
                <div class="auth-illustration">
                    <div class="illustration-box">
                        <svg width="100%" height="200" viewBox="0 0 400 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <!-- Lock Icon -->
                            <rect x="175" y="60" width="50" height="40" rx="6" fill="rgba(255,255,255,0.2)"/>
                            <path d="M185 60V45C185 35 195 30 200 30C205 30 215 35 215 45V60" stroke="rgba(255,255,255,0.3)" stroke-width="3" stroke-linecap="round"/>
                            <circle cx="200" cy="80" r="8" fill="rgba(255,255,255,0.4)"/>
                            <!-- Email Icon -->
                            <rect x="150" y="120" width="100" height="60" rx="8" fill="rgba(255,255,255,0.15)"/>
                            <path d="M150 130L200 160L250 130" stroke="rgba(255,255,255,0.3)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <!-- Check Mark -->
                            <circle cx="280" cy="100" r="25" fill="rgba(255,255,255,0.2)"/>
                            <path d="M270 100L277 107L290 93" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection