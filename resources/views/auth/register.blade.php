@extends('auth.layouts')
@section('title','Register')
@section('content')

<div class="register-page-wrapper" style="width: 100%; padding: 20px;">
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
                    <h1 class="auth-title mt-6" data-en="Create Account" data-ar="إنشاء حساب">Create Account</h1>
                    <p class="auth-subtitle" data-en="Join Rushly and start managing your logistics smarter" data-ar="انضم إلى راشلي وابدأ في إدارة لوجستياتك بذكاء">Join Rushly and start managing your logistics smarter</p>
                </div>

                <!-- Register Form -->
                <form method="POST" action="{{ route('register') }}" class="auth-form">
                    @csrf
                    
                    <!-- Name Input -->
                    <div class="form-group">
                        <label class="form-label" data-en="Full Name" data-ar="الاسم الكامل">Full Name</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </span>
                            <input id="name" type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                name="name" value="{{ old('name') }}" required autocomplete="name" autofocus
                                placeholder="Enter your full name"
                                data-en-placeholder="Enter your full name"
                                data-ar-placeholder="أدخل اسمك الكامل">
                        </div>
                        @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

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
                                name="email" value="{{ old('email') }}" required autocomplete="email"
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

                    <!-- Confirm Password Input -->
                    <div class="form-group">
                        <label class="form-label" data-en="Confirm Password" data-ar="تأكيد كلمة المرور">Confirm Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </span>
                            <input id="password-confirm" type="password"
                                class="form-control"
                                name="password_confirmation" required autocomplete="new-password"
                                placeholder="Confirm your password"
                                data-en-placeholder="Confirm your password"
                                data-ar-placeholder="أكد كلمة المرور">
                            <button type="button" class="password-toggle" onclick="togglePassword('password-confirm', 'eye-icon-2')">
                                <svg id="eye-icon-2" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="form-group">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="terms" required class="w-5 h-5 mt-0.5 accent-primary">
                            <span class="text-sm text-gray-600">
                                <span data-en="By creating an account, you agree to our" data-ar="بإنشاء حساب، فإنك توافق على">By creating an account, you agree to our</span>
                                <a href="#" class="auth-link" data-en="Terms and Conditions" data-ar="الشروط والأحكام">Terms and Conditions</a>
                                <span data-en="and" data-ar="و">and</span>
                                <a href="#" class="auth-link" data-en="Privacy Policy" data-ar="سياسة الخصوصية">Privacy Policy</a>
                            </span>
                        </label>
                        @error('terms')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-auth">
                        <span data-en="Create Account" data-ar="إنشاء حساب">Create Account</span>
                        <svg class="btn-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>

                    <!-- Divider -->
                    <div class="auth-divider flex items-center gap-4 my-6">
                        <div class="flex-1 h-px bg-gray-200"></div>
                        <span class="text-sm text-gray-500" data-en="OR" data-ar="أو">OR</span>
                        <div class="flex-1 h-px bg-gray-200"></div>
                    </div>

                    <!-- Social Register (Optional) -->
                    <div class="social-buttons space-y-3">
                        <button type="button" class="btn-social btn-google">
                            <svg width="20" height="20" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            <span data-en="Continue with Google" data-ar="متابعة باستخدام جوجل">Continue with Google</span>
                        </button>
                        <button type="button" class="btn-social btn-facebook">
                            <svg width="20" height="20" fill="#1877F2" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            <span data-en="Continue with Facebook" data-ar="متابعة باستخدام فيسبوك">Continue with Facebook</span>
                        </button>
                    </div>

                    <!-- Login Link -->
                    <div class="signup-link">
                        <span data-en="Already have an account?" data-ar="لديك حساب بالفعل؟">Already have an account?</span>
                        <a href="{{ route('login') }}" class="signup-btn" data-en="Sign In" data-ar="تسجيل الدخول">Sign In</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Side - Image/Branding -->
        <div class="auth-image-section">
            <div class="auth-image-overlay">
                <div class="auth-branding">
                    <h2 data-en="Join Rushly Today" data-ar="انضم إلى راشلي اليوم">Join Rushly Today</h2>
                    <p data-en="Start your 14-day free trial. No credit card required." data-ar="ابدأ تجربتك المجانية لمدة 14 يومًا. لا حاجة لبطاقة ائتمان.">Start your 14-day free trial. No credit card required.</p>
                    
                    <div class="feature-list">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span data-en="14-Day Free Trial" data-ar="تجربة مجانية 14 يوم">14-Day Free Trial</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span data-en="No Credit Card Required" data-ar="لا حاجة لبطاقة ائتمان">No Credit Card Required</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span data-en="24/7 Support" data-ar="دعم على مدار الساعة">24/7 Support</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span data-en="Cancel Anytime" data-ar="إلغاء في أي وقت">Cancel Anytime</span>
                        </div>
                    </div>
                </div>
                
                <div class="auth-illustration">
                    <div class="illustration-box">
                        <svg width="100%" height="200" viewBox="0 0 400 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <!-- User Group -->
                            <circle cx="150" cy="80" r="25" fill="rgba(255,255,255,0.2)"/>
                            <path d="M125 130C125 115 135 105 150 105C165 105 175 115 175 130" fill="rgba(255,255,255,0.2)"/>
                            <circle cx="200" cy="70" r="20" fill="rgba(255,255,255,0.15)"/>
                            <path d="M180 120C180 108 188 100 200 100C212 100 220 108 220 120" fill="rgba(255,255,255,0.15)"/>
                            <circle cx="250" cy="85" r="18" fill="rgba(255,255,255,0.15)"/>
                            <path d="M232 125C232 115 240 108 250 108C260 108 268 115 268 125" fill="rgba(255,255,255,0.15)"/>
                            <!-- Plus Icon -->
                            <circle cx="320" cy="100" r="30" fill="rgba(255,255,255,0.2)" stroke="rgba(255,255,255,0.4)" stroke-width="2" stroke-dasharray="4 4"/>
                            <path d="M320 85V115M305 100H335" stroke="white" stroke-width="3" stroke-linecap="round"/>
                            <!-- Check Badge -->
                            <circle cx="80" cy="140" r="20" fill="rgba(255,255,255,0.25)"/>
                            <path d="M72 140L77 145L88 134" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
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

    /* Social Buttons */
    .btn-social {
        width: 100%;
        padding: 14px 24px;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.3s ease;
        border: 1px solid var(--border-color);
        background: white;
        cursor: pointer;
    }

    .btn-social:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .btn-google:hover {
        background: #f8fafc;
        border-color: #e2e8f0;
    }

    .btn-facebook {
        background: #1877F2;
        color: white;
        border-color: #1877F2;
    }

    .btn-facebook:hover {
        background: #166fe5;
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
    }
</style>
@endpush

@push('scripts')
<script>
    // Language switching for register page
    function setLanguage(lang) {
        if (lang === 'ar') {
            document.body.classList.add('rtl');
            document.documentElement.setAttribute('dir', 'rtl');
            document.documentElement.setAttribute('lang', 'ar');
        } else {
            document.body.classList.remove('rtl');
            document.documentElement.setAttribute('dir', 'ltr');
            document.documentElement.setAttribute('lang', 'en');
        }
        
        document.getElementById('lang-en').classList.toggle('active', lang === 'en');
        document.getElementById('lang-ar').classList.toggle('active', lang === 'ar');
        
        document.querySelectorAll('[data-en][data-ar]').forEach(el => {
            el.textContent = el.getAttribute(`data-${lang}`);
        });
        
        document.querySelectorAll('[data-en-placeholder][data-ar-placeholder]').forEach(el => {
            el.placeholder = el.getAttribute(`data-${lang}-placeholder`);
        });
        
        localStorage.setItem('rushly-register-lang', lang);
    }

    // Initialize language on load
    document.addEventListener('DOMContentLoaded', () => {
        const savedLang = localStorage.getItem('rushly-register-lang');
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
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });
</script>
@endpush
@endsection