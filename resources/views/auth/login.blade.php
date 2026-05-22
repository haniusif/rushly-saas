@extends('auth.layouts')
@section('title','Login')
@section('content')

<div class="login-page-wrapper" style="width: 100%; padding: 20px;">
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
                    <h1 class="auth-title mt-6" data-en="Welcome Back" data-ar="مرحباً بعودتك">Welcome Back</h1>
                    <p class="auth-subtitle" data-en="Please enter your user information" data-ar="الرجاء إدخال معلومات المستخدم الخاصة بك">Please enter your user information</p>
                </div>

                <!-- Login Form -->
                <form method="POST" action="{{ route('login') }}" class="auth-form">
                    @csrf
                    
                    <!-- Email Input -->
                    <div class="form-group">
                        <label class="form-label" data-en="Email or Mobile" data-ar="البريد الإلكتروني أو الجوال">Email or Mobile</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                </svg>
                            </span>
                            <input id="email" type="text"
                                class="form-control @error('email') is-invalid @enderror"
                                name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                                placeholder="Enter Email or Mobile"
                                data-en-placeholder="Enter Email or Mobile"
                                data-ar-placeholder="أدخل البريد الإلكتروني أو الجوال">
                        </div>
                        @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- Password Input -->
                    <div class="form-group">
                        <label class="form-label" data-en="Password" data-ar="كلمة المرور">Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </span>
                            <input id="password" type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                name="password" required autocomplete="current-password" 
                                placeholder="Password"
                                data-en-placeholder="Password"
                                data-ar-placeholder="كلمة المرور">
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <svg id="eye-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex justify-between items-center mb-6 flex-wrap gap-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember" id="remember" class="w-4 h-4 accent-primary" {{ old('remember') ? 'checked' : '' }}>
                            <span class="text-sm text-gray-600" data-en="Remember Me" data-ar="تذكرني">Remember Me</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="auth-link text-sm" data-en="Forgot Password?" data-ar="نسيت كلمة المرور؟">Forgot Password?</a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-auth">
                        <span data-en="Sign In" data-ar="تسجيل الدخول">Sign In</span>
                        <svg class="btn-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>

                    <!-- Sign Up Link -->
                    <!--<div class="signup-link">-->
                    <!--    <span data-en="Don't have an account?" data-ar="ليس لديك حساب؟">Don't have an account?</span>-->
                    <!--    @if(tenant())-->
                    <!--        <a href="{{ route('merchant.sign-up') }}" class="signup-btn" data-en="Sign Up" data-ar="إنشاء حساب">Sign Up</a>-->
                    <!--    @else-->
                    <!--        <a href="{{ route('company.sign-up') }}" class="signup-btn" data-en="Sign Up" data-ar="إنشاء حساب">Sign Up</a>-->
                    <!--    @endif-->
                    <!--</div>-->
                </form>
            </div>
        </div>

        <!-- Right Side - Image/Branding -->
        <div class="auth-image-section">
            <div class="auth-image-overlay">
                <div class="auth-branding">
                    <h2 data-en="Manage Your Logistics Smarter" data-ar="أدر لوجستياتك بذكاء">Manage Your Logistics Smarter</h2>
                    <p data-en="Join thousands of businesses that trust Rushly for their delivery operations" data-ar="انضم لآلاف الشركات التي تثق براشلي لعمليات التوصيل الخاصة بها">Join thousands of businesses that trust Rushly for their delivery operations</p>
                    
                    <div class="feature-list">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span data-en="Real-time Tracking" data-ar="تتبع لحظي">Real-time Tracking</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span data-en="Driver Management" data-ar="إدارة السائقين">Driver Management</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span data-en="Analytics & Reports" data-ar="تحليلات وتقارير">Analytics & Reports</span>
                        </div>
                    </div>
                </div>
                
                <div class="auth-illustration">
                    <div class="illustration-box">
                        <svg width="100%" height="200" viewBox="0 0 400 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="50" y="50" width="300" height="100" rx="10" fill="rgba(255,255,255,0.1)"/>
                            <circle cx="200" cy="100" r="30" fill="rgba(255,255,255,0.2)"/>
                            <path d="M100 150 L300 150" stroke="rgba(255,255,255,0.2)" stroke-width="2"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection