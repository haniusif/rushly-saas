@extends('auth.layouts')
@section('title','Confirm Email')
@section('content')

<div class="confirm-email-page-wrapper" style="width: 100%; padding: 20px;">
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
                    <h1 class="auth-title mt-6" data-en="Verify Your Email" data-ar="تحقق من بريدك الإلكتروني">Verify Your Email</h1>
                    <p class="auth-subtitle" data-en="We've sent a verification link to your email address" data-ar="لقد أرسلنا رابط تحقق إلى بريدك الإلكتروني">We've sent a verification link to your email address</p>
                </div>

                <!-- Success Message -->
                @if (session('resent'))
                    <div class="alert alert-success mb-6">
                        <div class="flex items-center gap-3">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span data-en="A fresh verification link has been sent to your email address." data-ar="تم إرسال رابط تحقق جديد إلى بريدك الإلكتروني.">A fresh verification link has been sent to your email address.</span>
                        </div>
                    </div>
                @endif

                <!-- Email Confirmation Info -->
                <div class="email-info-box mb-6">
                    <div class="flex items-start gap-4">
                        <div class="email-icon-wrapper">
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-2" data-en="Check Your Inbox" data-ar="تحقق من صندوق الوارد">Check Your Inbox</h3>
                            <p class="text-sm text-gray-600" data-en="Please check your email and click the verification link to activate your account." data-ar="الرجاء التحقق من بريدك الإلكتروني والنقر على رابط التحقق لتفعيل حسابك.">Please check your email and click the verification link to activate your account.</p>
                        </div>
                    </div>
                </div>

                <!-- Resend Form -->
                <form method="POST" action="{{ route('verification.resend') }}" class="auth-form">
                    @csrf
                    
                    <button type="submit" class="btn-auth">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span data-en="Resend Verification Email" data-ar="إعادة إرسال بريد التحقق">Resend Verification Email</span>
                    </button>
                </form>

                <!-- Email Change Option -->
                <div class="email-change-option mt-6 text-center">
                    <p class="text-sm text-gray-600 mb-2" data-en="Wrong email address?" data-ar="عنوان البريد الإلكتروني خاطئ؟">Wrong email address?</p>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="auth-link text-sm" data-en="Sign in with a different account" data-ar="تسجيل الدخول بحساب آخر">Sign in with a different account</button>
                    </form>
                </div>

                <!-- Back to Login -->
                <div class="back-link mt-6">
                    <a href="{{ route('login') }}" class="back-btn">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span data-en="Back to Login" data-ar="العودة لتسجيل الدخول">Back to Login</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Right Side - Image/Branding -->
        <div class="auth-image-section">
            <div class="auth-image-overlay">
                <div class="auth-branding">
                    <h2 data-en="Secure Email Verification" data-ar="التحقق الآمن من البريد الإلكتروني">Secure Email Verification</h2>
                    <p data-en="Verify your email to access all Rushly features and secure your account" data-ar="تحقق من بريدك الإلكتروني للوصول إلى جميع ميزات راشلي وتأمين حسابك">Verify your email to access all Rushly features and secure your account</p>
                    
                    <div class="feature-list">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <span data-en="Account Security" data-ar="أمان الحساب">Account Security</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <span data-en="Email Notifications" data-ar="إشعارات البريد">Email Notifications</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <span data-en="Full Access" data-ar="الوصول الكامل">Full Access</span>
                        </div>
                    </div>
                </div>
                
                <div class="auth-illustration">
                    <div class="illustration-box">
                        <svg width="100%" height="200" viewBox="0 0 400 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <!-- Email Icon -->
                            <rect x="100" y="50" width="200" height="120" rx="12" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.3)" stroke-width="2"/>
                            <path d="M100 60L200 120L300 60" stroke="rgba(255,255,255,0.4)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <!-- Check Mark -->
                            <circle cx="280" cy="90" r="25" fill="rgba(255,255,255,0.2)" stroke="rgba(255,255,255,0.4)" stroke-width="2"/>
                            <path d="M268 90L275 97L292 80" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            <!-- Lines -->
                            <line x1="130" y1="130" x2="250" y2="130" stroke="rgba(255,255,255,0.3)" stroke-width="2" stroke-linecap="round"/>
                            <line x1="130" y1="145" x2="220" y2="145" stroke="rgba(255,255,255,0.3)" stroke-width="2" stroke-linecap="round"/>
                            <!-- Send Arrow -->
                            <path d="M320 100L360 100M360 100L345 85M360 100L345 115" stroke="rgba(255,255,255,0.5)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Email Info Box */
    .email-info-box {
        background: linear-gradient(135deg, var(--primary-light), #f8f4f7);
        border: 1px solid rgba(162, 31, 92, 0.2);
        border-radius: 12px;
        padding: 20px;
    }

    .email-icon-wrapper {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        flex-shrink: 0;
    }

    /* Alert Styles */
    .alert {
        padding: 16px 20px;
        border-radius: 12px;
        font-size: 14px;
    }

    .alert-success {
        background: linear-gradient(135deg, #10B981, #059669);
        color: white;
    }

    .alert .flex {
        display: flex;
        align-items: center;
    }

    /* Email Change Option */
    .email-change-option {
        padding-top: 20px;
        border-top: 1px solid var(--border-color);
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

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.7;
        }
    }

    .email-icon-wrapper {
        animation: pulse 2s ease-in-out infinite;
    }
</style>
@endpush

@push('scripts')
<script>
    // Language switching for email confirm page
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
        
        localStorage.setItem('rushly-email-confirm-lang', lang);
    }

    // Initialize language on load
    document.addEventListener('DOMContentLoaded', () => {
        const savedLang = localStorage.getItem('rushly-email-confirm-lang');
        const browserLang = navigator.language.startsWith('ar') ? 'ar' : 'en';
        const initialLang = savedLang || browserLang;
        
        if (initialLang === 'ar') {
            setLanguage('ar');
        }
    });

    // Auto-hide success message after 5 seconds
    document.addEventListener('DOMContentLoaded', () => {
        const alertBox = document.querySelector('.alert-success');
        if (alertBox) {
            setTimeout(() => {
                alertBox.style.transition = 'opacity 0.5s ease';
                alertBox.style.opacity = '0';
                setTimeout(() => {
                    alertBox.style.display = 'none';
                }, 500);
            }, 5000);
        }
    });
</script>
@endpush
@endsection