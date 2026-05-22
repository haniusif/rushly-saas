@extends('auth.layouts')
@section('title','Merchant Registration')
@section('content')

<div class="merchant-register-page-wrapper" style="width: 100%; padding: 20px;">
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
                    <h1 class="auth-title mt-6" data-en="Merchant Registration" data-ar="تسجيل التاجر">Merchant Registration</h1>
                    <p class="auth-subtitle" data-en="Join Rushly and grow your delivery business" data-ar="انضم إلى راشلي وطوّر عملك في التوصيل">Join Rushly and grow your delivery business</p>
                </div>

                <!-- Merchant Registration Form -->
                <form method="POST" action="{{ route('merchant.sign-up-store') }}" class="auth-form">
                    @csrf
                    
                    <!-- Business Name -->
                    <div class="form-group">
                        <label class="form-label" data-en="Business Name" data-ar="اسم النشاط التجاري">Business Name</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </span>
                            <input id="business_name" type="text"
                                class="form-control @error('business_name') is-invalid @enderror"
                                name="business_name" value="{{ old('business_name') }}" required autocomplete="business_name" autofocus
                                placeholder="Enter your business name"
                                data-en-placeholder="Enter your business name"
                                data-ar-placeholder="أدخل اسم نشاطك التجاري">
                        </div>
                        @error('business_name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- Full Name -->
                    <div class="form-group">
                        <label class="form-label" data-en="Full Name" data-ar="الاسم الكامل">Full Name</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </span>
                            <input id="full_name" type="text"
                                class="form-control @error('full_name') is-invalid @enderror"
                                name="full_name" value="{{ old('full_name') }}" required autocomplete="name"
                                placeholder="Enter your full name"
                                data-en-placeholder="Enter your full name"
                                data-ar-placeholder="أدخل اسمك الكامل">
                        </div>
                        @error('full_name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- Select Hub -->
                    <div class="form-group">
                        <label class="form-label" data-en="Select Hub" data-ar="اختر المركز">Select Hub</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </span>
                            <select class="form-control select2-custom @error('hub_id') is-invalid @enderror" 
                                name="hub_id" id="hub_id" required>
                                <option value="" disabled selected data-en="Select your hub" data-ar="اختر مركزك">Select your hub</option>
                                @foreach ($hubs as $hub)
                                    <option value="{{ $hub->id }}" {{ old('hub_id') == $hub->id ? 'selected' : '' }}>
                                        {{ $hub->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('hub_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- Mobile -->
                    <div class="form-group">
                        <label class="form-label" data-en="Mobile Number" data-ar="رقم الجوال">Mobile Number</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                            </span>
                            <input id="mobile" type="tel"
                                class="form-control @error('mobile') is-invalid @enderror"
                                name="mobile" value="{{ old('mobile', $request->phone ?? '') }}" required autocomplete="mobile"
                                placeholder="Enter mobile number"
                                data-en-placeholder="Enter mobile number"
                                data-ar-placeholder="أدخل رقم الجوال">
                        </div>
                        @error('mobile')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- Password -->
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
                                name="password" required autocomplete="new-password"
                                placeholder="Create a password"
                                data-en-placeholder="Create a password"
                                data-ar-placeholder="أنشئ كلمة مرور">
                            <button type="button" class="password-toggle" onclick="togglePassword('password', 'eye-icon-1')">
                                <svg id="eye-icon-1" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        <!-- Password Requirements -->
                        <div class="password-requirements mt-2">
                            <p class="text-xs text-gray-500 mb-1" data-en="Password must contain:" data-ar="يجب أن تحتوي كلمة المرور على:">Password must contain:</p>
                            <ul class="text-xs text-gray-500 space-y-1">
                                <li class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300 requirement-dot" id="dot-length"></span>
                                    <span data-en="At least 8 characters" data-ar="8 أحرف على الأقل">At least 8 characters</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300 requirement-dot" id="dot-uppercase"></span>
                                    <span data-en="One uppercase letter" data-ar="حرف كبير واحد">One uppercase letter</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300 requirement-dot" id="dot-number"></span>
                                    <span data-en="One number" data-ar="رقم واحد">One number</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="form-group">
                        <label class="form-label" data-en="Business Address" data-ar="عنوان النشاط التجاري">Business Address</label>
                        <div class="input-wrapper textarea-wrapper">
                            <span class="input-icon textarea-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </span>
                            <textarea id="address" name="address" rows="4"
                                class="form-control textarea-control @error('address') is-invalid @enderror"
                                placeholder="Enter your business address"
                                data-en-placeholder="Enter your business address"
                                data-ar-placeholder="أدخل عنوان نشاطك التجاري">{{ old('address') }}</textarea>
                        </div>
                        @error('address')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="form-group">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input id="merchant_registration_checkbox" type="checkbox" name="policy" required class="w-5 h-5 mt-0.5 accent-primary">
                            <span class="text-sm text-gray-600">
                                <span data-en="I agree to" data-ar="أوافق على">I agree to</span>
                                <a href="#" class="auth-link" data-en="Privacy Policy & Terms" data-ar="سياسة الخصوصية والشروط">Privacy Policy & Terms</a>
                            </span>
                        </label>
                        @error('policy')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <button id="merchant_registration_submit" type="submit" class="btn-auth">
                        <span data-en="Register My Account" data-ar="تسجيل حسابي">Register My Account</span>
                        <svg class="btn-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>

                    <!-- Login Link -->
                    <div class="signup-link">
                        <span data-en="Already a member?" data-ar="عضو بالفعل؟">Already a member?</span>
                        <a href="{{ route('login') }}" class="signup-btn" data-en="Login Here" data-ar="سجل الدخول هنا">Login Here</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Side - Image/Branding -->
        <div class="auth-image-section">
            <div class="auth-image-overlay">
                <div class="auth-branding">
                    <h2 data-en="Grow Your Business with Rushly" data-ar="طوّر عملك مع راشلي">Grow Your Business with Rushly</h2>
                    <p data-en="Join thousands of merchants who trust Rushly for their delivery operations" data-ar="انضم لآلاف التجار الذين يثقون براشلي لعمليات التوصيل الخاصة بهم">Join thousands of merchants who trust Rushly for their delivery operations</p>
                    
                    <div class="feature-list">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                            <span data-en="Increase Sales" data-ar="زيادة المبيعات">Increase Sales</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <span data-en="Lower Costs" data-ar="خفض التكاليف">Lower Costs</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <span data-en="Secure Platform" data-ar="منصة آمنة">Secure Platform</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <span data-en="24/7 Support" data-ar="دعم على مدار الساعة">24/7 Support</span>
                        </div>
                    </div>
                </div>

                <!-- System Modules Cards -->
                <div class="modules-section">
                    <h3 class="modules-title" data-en="Logistics Modules" data-ar="وحدات اللوجستيات">Logistics Modules</h3>
                    <div class="modules-grid">
                        <!-- Module 1: TMS -->
                        <div class="module-card">
                            <div class="module-icon module-icon-tms">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path>
                                </svg>
                            </div>
                            <span class="module-title" data-en="TMS" data-ar="نقل">TMS</span>
                            <span class="module-subtitle" data-en="Transport Mgmt" data-ar="إدارة النقل">Transport Mgmt</span>
                        </div>
                        <!-- Module 2: WMS -->
                        <div class="module-card">
                            <div class="module-icon module-icon-wms">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <span class="module-title" data-en="WMS" data-ar="مستودع">WMS</span>
                            <span class="module-subtitle" data-en="Warehouse Mgmt" data-ar="إدارة المستودع">Warehouse Mgmt</span>
                        </div>
                        <!-- Module 3: OMS -->
                        <div class="module-card">
                            <div class="module-icon module-icon-oms">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <span class="module-title" data-en="OMS" data-ar="طلبات">OMS</span>
                            <span class="module-subtitle" data-en="Order Mgmt" data-ar="إدارة الطلبات">Order Mgmt</span>
                        </div>
                        <!-- Module 4: Fleet -->
                        <div class="module-card">
                            <div class="module-icon module-icon-fleet">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                            </div>
                            <span class="module-title" data-en="Fleet" data-ar="أسطول">Fleet</span>
                            <span class="module-subtitle" data-en="Fleet Mgmt" data-ar="إدارة الأسطول">Fleet Mgmt</span>
                        </div>
                        <!-- Module 5: HR -->
                        <div class="module-card">
                            <div class="module-icon module-icon-hr">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <span class="module-title" data-en="HR" data-ar="موارد بشرية">HR</span>
                            <span class="module-subtitle" data-en="Human Resources" data-ar="الموارد البشرية">Human Resources</span>
                        </div>
                        <!-- Module 6: Accounting -->
                        <div class="module-card">
                            <div class="module-icon module-icon-accounting">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <span class="module-title" data-en="Accounting" data-ar="محاسبة">Accounting</span>
                            <span class="module-subtitle" data-en="Finance & Billing" data-ar="المالية والفواتير">Finance & Billing</span>
                        </div>
                        <!-- Module 7: 3PL -->
                        <div class="module-card">
                            <div class="module-icon module-icon-3pl">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path>
                                </svg>
                            </div>
                            <span class="module-title" data-en="3PL" data-ar="طرف ثالث">3PL</span>
                            <span class="module-subtitle" data-en="3PL Integration" data-ar="دمج طرف ثالث">3PL Integration</span>
                        </div>
                        <!-- Module 8: Analytics -->
                        <div class="module-card">
                            <div class="module-icon module-icon-analytics">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <span class="module-title" data-en="Analytics" data-ar="تحليلات">Analytics</span>
                            <span class="module-subtitle" data-en="Reports & BI" data-ar="تقارير وذكاء أعمال">Reports & BI</span>
                        </div>
                        <!-- Module 9: CRM -->
                        <div class="module-card">
                            <div class="module-icon module-icon-crm">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <span class="module-title" data-en="CRM" data-ar="عملاء">CRM</span>
                            <span class="module-subtitle" data-en="Customer Mgmt" data-ar="إدارة العملاء">Customer Mgmt</span>
                        </div>
                        <!-- Module 10: API -->
                        <div class="module-card">
                            <div class="module-icon module-icon-api">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                </svg>
                            </div>
                            <span class="module-title" data-en="API" data-ar="واجهة برمجية">API</span>
                            <span class="module-subtitle" data-en="Integrations" data-ar="تكاملات">Integrations</span>
                        </div>
                        <!-- Module 11: Mobile App -->
                        <div class="module-card">
                            <div class="module-icon module-icon-mobile">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <span class="module-title" data-en="Mobile" data-ar="تطبيق جوال">Mobile</span>
                            <span class="module-subtitle" data-en="Driver App" data-ar="تطبيق السائق">Driver App</span>
                        </div>
                        <!-- Module 12: Notifications -->
                        <div class="module-card">
                            <div class="module-icon module-icon-notifications">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                </svg>
                            </div>
                            <span class="module-title" data-en="Alerts" data-ar="تنبيهات">Alerts</span>
                            <span class="module-subtitle" data-en="Notifications" data-ar="إشعارات">Notifications</span>
                        </div>
                    </div>
                </div>
                
                <div class="auth-illustration">
                    <div class="illustration-box">
                        <svg width="100%" height="200" viewBox="0 0 400 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <!-- Store/Shop Icon -->
                            <rect x="120" y="60" width="160" height="100" rx="8" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.3)" stroke-width="2"/>
                            <path d="M110 60L200 30L290 60" stroke="rgba(255,255,255,0.4)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            <!-- Door -->
                            <rect x="185" y="100" width="30" height="60" rx="4" fill="rgba(255,255,255,0.2)"/>
                            <!-- Windows -->
                            <rect x="140" y="80" width="30" height="30" rx="4" fill="rgba(255,255,255,0.2)"/>
                            <rect x="230" y="80" width="30" height="30" rx="4" fill="rgba(255,255,255,0.2)"/>
                            <!-- Growth Arrow -->
                            <path d="M320 140L320 80M320 80L305 95M320 80L335 95" stroke="rgba(255,255,255,0.5)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            <!-- Coins -->
                            <circle cx="80" cy="120" r="15" fill="rgba(255,255,255,0.2)" stroke="rgba(255,255,255,0.4)" stroke-width="2"/>
                            <text x="80" y="125" text-anchor="middle" fill="white" font-size="16" font-weight="bold">$</text>
                            <circle cx="65" cy="140" r="12" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.3)" stroke-width="2"/>
                            <text x="65" y="144" text-anchor="middle" fill="white" font-size="12" font-weight="bold">$</text>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    /* Password Requirements */
    .password-requirements {
        background: var(--primary-light);
        border-radius: 8px;
        padding: 12px;
        border: 1px solid rgba(162, 31, 92, 0.1);
    }

    .password-requirements ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .requirement-dot.valid {
        background: #10B981 !important;
    }

    /* Textarea Styling */
    .textarea-wrapper {
        align-items: flex-start;
    }

    .textarea-icon {
        margin-top: 14px;
    }

    .textarea-control {
        padding: 14px 16px 14px 48px;
        min-height: 100px;
        resize: vertical;
    }

    body.rtl .textarea-control {
        padding: 14px 48px 14px 16px;
    }

    /* Select2 Custom Styling */
    .select2-custom {
        width: 100% !important;
        padding: 14px 16px 14px 48px !important;
        border: 1px solid var(--border-color) !important;
        border-radius: 12px !important;
        font-size: 15px !important;
        color: var(--text-dark) !important;
    }

    body.rtl .select2-custom {
        padding: 14px 48px 14px 16px !important;
    }

    .select2-container--default .select2-selection--single {
        border: none !important;
        height: 50px !important;
        display: flex;
        align-items: center;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 50px !important;
        padding-left: 0 !important;
        color: var(--text-dark) !important;
    }

    body.rtl .select2-container--default .select2-selection--single .select2-selection__rendered {
        padding-left: 16px !important;
        padding-right: 0 !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 50px !important;
        right: 0 !important;
    }

    body.rtl .select2-container--default .select2-selection--single .select2-selection__arrow {
        right: auto !important;
        left: 0 !important;
    }

    .select2-dropdown {
        border: 1px solid var(--border-color) !important;
        border-radius: 12px !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1) !important;
    }

    .select2-container--open .select2-dropdown {
        border-color: var(--primary) !important;
    }

    .select2-results__option--highlighted[aria-selected] {
        background-color: var(--primary) !important;
    }

    .select2-container--default .select2-results__option--selected {
        background-color: var(--primary-light) !important;
        color: var(--primary) !important;
    }

    /* Password toggle button */
    .password-toggle {
        position: absolute;
        right: 16px;
        background: none;
        border: none;
        cursor: pointer;
        color: var(--text-gray);
        padding: 4px;
    }

    body.rtl .password-toggle {
        right: auto;
        left: 16px;
    }

    .password-toggle:hover {
        color: var(--primary);
    }

    /* Checkbox styling */
    input[type="checkbox"] {
        accent-color: var(--primary);
        cursor: pointer;
        width: 18px;
        height: 18px;
    }

    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .auth-form-wrapper {
        animation: fadeInUp 0.6s ease-out;
    }

    /* Form focus states */
    .form-control:focus,
    .textarea-control:focus,
    .select2-custom:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(162, 31, 92, 0.1);
    }

    .form-control.is-invalid,
    .textarea-control.is-invalid {
        border-color: var(--error);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .select2-custom {
            padding: 12px 14px 12px 44px !important;
        }
        
        body.rtl .select2-custom {
            padding: 12px 44px 12px 14px !important;
        }
    }

    /* System Modules Section */
    .modules-section {
        margin-top: 32px;
        margin-bottom: 32px;
    }

    .modules-title {
        font-size: 18px;
        font-weight: 700;
        color: white;
        margin-bottom: 16px;
        text-align: center;
        opacity: 0.95;
    }

    .modules-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }

    .module-card {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        padding: 14px 10px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        transition: all 0.3s ease;
        cursor: default;
    }

    .module-card:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-4px);
        border-color: rgba(255, 255, 255, 0.3);
    }

    .module-icon {
        width: 36px;
        height: 36px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        margin-bottom: 8px;
        transition: all 0.3s ease;
    }

    .module-card:hover .module-icon {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
    }

    /* Module Icon Colors */
    .module-icon-tms { background: linear-gradient(135deg, #a21f5c, #c73e7a); }
    .module-icon-wms { background: linear-gradient(135deg, #29245a, #3d356b); }
    .module-icon-oms { background: linear-gradient(135deg, #a21f5c, #29245a); }
    .module-icon-fleet { background: linear-gradient(135deg, #29245a, #a21f5c); }
    .module-icon-hr { background: linear-gradient(135deg, #c73e7a, #a21f5c); }
    .module-icon-accounting { background: linear-gradient(135deg, #3d356b, #29245a); }
    .module-icon-3pl { background: linear-gradient(135deg, #a21f5c, #3d356b); }
    .module-icon-analytics { background: linear-gradient(135deg, #29245a, #c73e7a); }
    .module-icon-crm { background: linear-gradient(135deg, #c73e7a, #3d356b); }
    .module-icon-api { background: linear-gradient(135deg, #3d356b, #a21f5c); }
    .module-icon-mobile { background: linear-gradient(135deg, #a21f5c, #c73e7a); }
    .module-icon-notifications { background: linear-gradient(135deg, #29245a, #a21f5c); }

    .module-title {
        font-size: 12px;
        font-weight: 700;
        color: white;
        opacity: 0.95;
        margin-bottom: 2px;
    }

    .module-subtitle {
        font-size: 10px;
        font-weight: 500;
        color: white;
        opacity: 0.7;
    }

    /* Adjust spacing for illustration when modules are present */
    .auth-illustration {
        margin-top: auto;
        width: 100%;
    }

    /* Responsive modules grid */
    @media (max-width: 1024px) {
        .modules-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 768px) {
        .modules-grid {
            grid-template-columns: repeat(4, 1fr);
        }
    }

    @media (max-width: 576px) {
        .modules-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    /* Loading state for button */
    .btn-loading {
        position: relative;
        color: transparent !important;
        pointer-events: none;
    }

    .btn-loading::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        top: 50%;
        left: 50%;
        margin-left: -10px;
        margin-top: -10px;
        border: 2px solid rgba(255,255,255,0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>
@endpush

@push('scripts')
<!-- jQuery -->
<script src="{{static_asset('backend')}}/vendor/jquery/jquery-3.3.1.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    // Set page title
    $(document).prop('title', 'Merchant Registration');

    // Initialize Select2
    $(document).ready(function() {
        $('#hub_id').select2({
            placeholder: '{{ __("Select your hub") }}',
            allowClear: true,
            dir: document.body.classList.contains('rtl') ? 'rtl' : 'ltr'
        });
    });

    // Language switching for merchant register page
    function setLanguage(lang) {
        if (lang === 'ar') {
            document.body.classList.add('rtl');
            document.documentElement.setAttribute('dir', 'rtl');
            document.documentElement.setAttribute('lang', 'ar');
            
            // Update Select2 direction
            $('#hub_id').select2({ dir: 'rtl' });
        } else {
            document.body.classList.remove('rtl');
            document.documentElement.setAttribute('dir', 'ltr');
            document.documentElement.setAttribute('lang', 'en');
            
            // Update Select2 direction
            $('#hub_id').select2({ dir: 'ltr' });
        }
        
        document.getElementById('lang-en').classList.toggle('active', lang === 'en');
        document.getElementById('lang-ar').classList.toggle('active', lang === 'ar');
        
        document.querySelectorAll('[data-en][data-ar]').forEach(el => {
            el.textContent = el.getAttribute(`data-${lang}`);
        });
        
        document.querySelectorAll('[data-en-placeholder][data-ar-placeholder]').forEach(el => {
            el.placeholder = el.getAttribute(`data-${lang}-placeholder`);
        });
        
        // Update Select2 placeholder
        const select2Placeholder = lang === 'ar' ? 'اختر مركزك' : 'Select your hub';
        $('#hub_id').select2({
            placeholder: select2Placeholder,
            allowClear: true,
            dir: lang === 'ar' ? 'rtl' : 'ltr'
        });
        
        localStorage.setItem('rushly-merchant-register-lang', lang);
    }

    // Initialize language on load
    document.addEventListener('DOMContentLoaded', () => {
        const savedLang = localStorage.getItem('rushly-merchant-register-lang');
        const browserLang = navigator.language.startsWith('ar') ? 'ar' : 'en';
        const initialLang = savedLang || browserLang;
        
        if (initialLang === 'ar') {
            setLanguage('ar');
        }
    });

    // Toggle password visibility
    function togglePassword(inputId, eyeIconId) {
        const passwordInput = document.getElementById(inputId);
        const eyeIcon = document.getElementById(eyeIconId);
        
        if (passwordInput && eyeIcon) {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>';
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
            }
        }
    }

    // Password strength indicator
    document.getElementById('password')?.addEventListener('input', function() {
        const password = this.value;
        
        // Check length (8+ characters)
        const lengthDot = document.getElementById('dot-length');
        if (password.length >= 8) {
            lengthDot.classList.remove('bg-gray-300');
            lengthDot.classList.add('valid');
        } else {
            lengthDot.classList.add('bg-gray-300');
            lengthDot.classList.remove('valid');
        }
        
        // Check uppercase
        const uppercaseDot = document.getElementById('dot-uppercase');
        if (/[A-Z]/.test(password)) {
            uppercaseDot.classList.remove('bg-gray-300');
            uppercaseDot.classList.add('valid');
        } else {
            uppercaseDot.classList.add('bg-gray-300');
            uppercaseDot.classList.remove('valid');
        }
        
        // Check number
        const numberDot = document.getElementById('dot-number');
        if (/[0-9]/.test(password)) {
            numberDot.classList.remove('bg-gray-300');
            numberDot.classList.add('valid');
        } else {
            numberDot.classList.add('bg-gray-300');
            numberDot.classList.remove('valid');
        }
    });

    // Form validation feedback
    document.querySelectorAll('.form-control, .textarea-control').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });

    // Form submission loading state
    document.querySelector('form')?.addEventListener('submit', function() {
        const submitBtn = document.getElementById('merchant_registration_submit');
        if (submitBtn) {
            submitBtn.classList.add('btn-loading');
            submitBtn.disabled = true;
        }
    });
</script>
@endpush
@endsection