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
                    <h1 class="auth-title mt-6" data-en="Create New Password" data-ar="إنشاء كلمة مرور جديدة">Create New Password</h1>
                    <p class="auth-subtitle" data-en="Enter your new password below to reset your account" data-ar="أدخل كلمة المرور الجديدة أدناه لإعادة تعيين حسابك">Enter your new password below to reset your account</p>
                </div>

                <!-- Reset Password Form -->
                <form method="POST" action="{{ route('password.update') }}" class="auth-form">
                    @csrf 
                    <input type="hidden" name="token" value="{{ $token }}"> 
                    
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
                                name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus
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
                        <label class="form-label" data-en="New Password" data-ar="كلمة المرور الجديدة">New Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </span>
                            <input id="password" type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                name="password" required autocomplete="new-password"
                                placeholder="Enter new password"
                                data-en-placeholder="Enter new password"
                                data-ar-placeholder="أدخل كلمة المرور الجديدة">
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
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
                                    <span data-en="At least 8 characters" data-ar="8 أحرف على الأقل">At least 8 characters</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
                                    <span data-en="One uppercase letter" data-ar="حرف كبير واحد">One uppercase letter</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
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
                                placeholder="Confirm new password"
                                data-en-placeholder="Confirm new password"
                                data-ar-placeholder="تأكيد كلمة المرور الجديدة">
                            <button type="button" class="password-toggle" onclick="togglePassword('password-confirm', 'eye-icon-2')">
                                <svg id="eye-icon-2" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-auth">
                        <span data-en="Reset Password" data-ar="إعادة تعيين كلمة المرور">Reset Password</span>
                        <svg class="btn-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>

                    <!-- Back to Login -->
                    <div class="back-link mt-6">
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
                        <a href="{{ route('register') }}" class="signup-btn" data-en="Sign Up" data-ar="إنشاء حساب">Sign Up</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Side - Image/Branding -->
        <div class="auth-image-section">
            <div class="auth-image-overlay">
                <div class="auth-branding">
                    <h2 data-en="Secure Password Reset" data-ar="إعادة تعيين كلمة المرور الآمنة">Secure Password Reset</h2>
                    <p data-en="Your account security is our priority. Create a strong password to protect your data" data-ar="أمان حسابك هو أولويتنا. أنشئ كلمة مرور قوية لحماية بياناتك">Your account security is our priority. Create a strong password to protect your data</p>
                    
                    <div class="feature-list">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <span data-en="Encrypted & Secure" data-ar="مشفر وآمن">Encrypted & Secure</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <span data-en="Account Protected" data-ar="الحساب محمي">Account Protected</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <span data-en="Instant Access" data-ar="وصول فوري">Instant Access</span>
                        </div>
                    </div>
                </div>
                
                <div class="auth-illustration">
                    <div class="illustration-box">
                        <svg width="100%" height="200" viewBox="0 0 400 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <!-- Lock with Key -->
                            <rect x="160" y="50" width="80" height="60" rx="8" fill="rgba(255,255,255,0.2)" stroke="rgba(255,255,255,0.4)" stroke-width="2"/>
                            <path d="M180 50V35C180 25 190 20 200 20C210 20 220 25 220 35V50" stroke="rgba(255,255,255,0.4)" stroke-width="3" stroke-linecap="round"/>
                            <circle cx="200" cy="80" r="10" fill="rgba(255,255,255,0.3)"/>
                            <!-- Key -->
                            <path d="M260 70L290 70M290 70L285 65M290 70L285 75" stroke="rgba(255,255,255,0.5)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="260" cy="70" r="8" fill="rgba(255,255,255,0.3)" stroke="rgba(255,255,255,0.5)" stroke-width="2"/>
                            <!-- Check Shield -->
                            <path d="M120 130L140 120L160 130V150C160 165 140 175 140 175C140 175 120 165 120 150V130Z" fill="rgba(255,255,255,0.2)" stroke="rgba(255,255,255,0.4)" stroke-width="2"/>
                            <path d="M133 145L137 149L147 139" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <!-- Dots -->
                            <circle cx="280" cy="140" r="4" fill="rgba(255,255,255,0.4)"/>
                            <circle cx="300" cy="140" r="4" fill="rgba(255,255,255,0.4)"/>
                            <circle cx="320" cy="140" r="4" fill="rgba(255,255,255,0.4)"/>
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
</style>
@endpush

@push('scripts')
<script>
    // Language switching for reset password page
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
        
        localStorage.setItem('rushly-reset-lang', lang);
    }

    // Initialize language on load
    document.addEventListener('DOMContentLoaded', () => {
        const savedLang = localStorage.getItem('rushly-reset-lang');
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

    // Form validation feedback
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });

    // Password strength indicator (optional enhancement)
    document.getElementById('password')?.addEventListener('input', function() {
        const password = this.value;
        const requirements = document.querySelectorAll('.password-requirements li span');
        
        // Check length
        if (password.length >= 8) {
            requirements[0].previousElementSibling.classList.remove('bg-gray-300');
            requirements[0].previousElementSibling.classList.add('bg-green-500');
        } else {
            requirements[0].previousElementSibling.classList.add('bg-gray-300');
            requirements[0].previousElementSibling.classList.remove('bg-green-500');
        }
        
        // Check uppercase
        if (/[A-Z]/.test(password)) {
            requirements[1].previousElementSibling.classList.remove('bg-gray-300');
            requirements[1].previousElementSibling.classList.add('bg-green-500');
        } else {
            requirements[1].previousElementSibling.classList.add('bg-gray-300');
            requirements[1].previousElementSibling.classList.remove('bg-green-500');
        }
        
        // Check number
        if (/[0-9]/.test(password)) {
            requirements[2].previousElementSibling.classList.remove('bg-gray-300');
            requirements[2].previousElementSibling.classList.add('bg-green-500');
        } else {
            requirements[2].previousElementSibling.classList.add('bg-gray-300');
            requirements[2].previousElementSibling.classList.remove('bg-green-500');
        }
    });
</script>
@endpush
@endsection