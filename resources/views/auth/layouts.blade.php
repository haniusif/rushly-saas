<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="description" content="Rushly - Smart Logistics Management Platform"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - Rushly</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#a21f5c',
                        secondary: '#29245a',
                        accent: '#0F172A',
                        'surface': '#F8FAFC',
                        'primary-light': '#f0e6ee',
                        'secondary-light': '#3d356b'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        arabic: ['Tajawal', 'sans-serif']
                    }
                }
            }
        }
    </script>
    
    @stack('styles')
    
    <style>
        :root {
            --primary: #a21f5c;
            --secondary: #29245a;
            --primary-light: #f0e6ee;
            --text-dark: #0F172A;
            --text-gray: #64748B;
            --border-color: #E2E8F0;
            --success: #10B981;
            --error: #EF4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #F8FAFC;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        body.rtl {
            font-family: 'Tajawal', sans-serif;
            direction: rtl;
        }

        .gradient-primary {
            background: linear-gradient(135deg, #a21f5c 0%, #29245a 100%);
        }

        .gradient-text {
            background: linear-gradient(135deg, #a21f5c 0%, #29245a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Language Switcher */
        .lang-switcher-fixed {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            background: white;
            border-radius: 50px;
            padding: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
        }

        body.rtl .lang-switcher-fixed {
            right: auto;
            left: 20px;
        }

        .lang-btn {
            padding: 8px 16px;
            border-radius: 50px;
            border: none;
            background: transparent;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-gray);
        }

        .lang-btn.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 2px 8px rgba(162, 31, 92, 0.3);
        }

        .lang-btn:hover:not(.active) {
            background: var(--primary-light);
        }

        /* Container */
        .auth-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            min-height: 700px;
            width: 100%;
            max-width: 1400px;
        }

        @media (max-width: 968px) {
            .auth-container {
                grid-template-columns: 1fr;
                min-height: auto;
            }
            
            .auth-image-section {
                display: none;
            }
        }

        /* Form Section */
        .auth-form-section {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
        }

        @media (max-width: 576px) {
            .auth-form-section {
                padding: 40px 24px;
            }
        }

        .auth-form-wrapper {
            max-width: 420px;
            margin: 0 auto;
            width: 100%;
        }

        /* Logo */
        .auth-logo img.logo-img {
            height: 80px;
            width: auto;
            object-fit: contain;
        }

        .auth-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-dark);
            margin-top: 24px;
        }

        .auth-subtitle {
            font-size: 16px;
            color: var(--text-gray);
            margin-top: 8px;
            line-height: 1.5;
        }

        /* Form Styles */
        .auth-form {
            margin-top: 40px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            color: var(--text-gray);
            z-index: 10;
        }

        body.rtl .input-icon {
            left: auto;
            right: 16px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: white;
            color: var(--text-dark);
        }

        body.rtl .form-control {
            padding: 14px 48px 14px 16px;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(162, 31, 92, 0.1);
        }

        .form-control.is-invalid {
            border-color: var(--error);
        }

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

        .invalid-feedback {
            display: block;
            color: var(--error);
            font-size: 13px;
            margin-top: 6px;
        }

        /* Alert Messages */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .alert-success {
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
        }

        .alert-error {
            background: linear-gradient(135deg, #EF4444, #DC2626);
            color: white;
        }

        /* Button */
        .btn-auth {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 14px rgba(162, 31, 92, 0.3);
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(162, 31, 92, 0.4);
        }

        .btn-icon {
            transition: transform 0.3s ease;
        }

        .btn-auth:hover .btn-icon {
            transform: translateX(4px);
        }

        body.rtl .btn-auth:hover .btn-icon {
            transform: translateX(-4px);
        }

        /* Links */
        .auth-links {
            margin-top: 24px;
            text-align: center;
        }

        .auth-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .auth-link:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

        .auth-divider {
            margin: 24px 0;
            color: var(--text-gray);
        }

        .signup-link {
            text-align: center;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--border-color);
            font-size: 15px;
            color: var(--text-gray);
        }

        .signup-btn {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
            margin-left: 4px;
        }

        body.rtl .signup-btn {
            margin-left: 0;
            margin-right: 4px;
        }

        .signup-btn:hover {
            text-decoration: underline;
        }

        .back-link {
            margin-top: 16px;
            text-align: center;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-gray);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .back-btn:hover {
            color: var(--primary);
        }

        body.rtl .back-btn {
            flex-direction: row-reverse;
        }

        /* Image Section */
        .auth-image-section {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .auth-image-overlay {
            padding: 60px;
            position: relative;
            z-index: 10;
            color: white;
            text-align: center;
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
        }

        .auth-branding h2 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 16px;
            line-height: 1.3;
            color: rgba(255,255,255,0.9);
        }

        .auth-branding > p {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 40px;
            line-height: 1.6;
            color: rgba(255,255,255,0.8);
        }

        .feature-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 40px;
            align-items: flex-start;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 16px;
            font-weight: 500;
            color: rgba(255,255,255,0.9);
        }

        .feature-icon {
            width: 32px;
            height: 32px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .auth-illustration {
            margin-top: auto;
            width: 100%;
        }

        .illustration-box {
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
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

        /* RTL Adjustments */
        body.rtl .feature-item {
            flex-direction: row-reverse;
        }
        
        body.rtl .feature-list {
            align-items: flex-end;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .auth-title {
                font-size: 26px;
            }
            
            .auth-branding h2 {
                font-size: 28px;
            }
            
            .auth-image-overlay {
                padding: 40px 24px;
            }
        }

        @media (max-width: 576px) {
            .lang-switcher-fixed {
                top: 10px;
                right: 10px;
            }
            
            body.rtl .lang-switcher-fixed {
                left: 10px;
            }
        }
    </style>
</head>
<body class="@yield('body-class')">
    @yield('content')

    @stack('scripts')
    
    <script>
        // Language switching for auth pages
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
            
            localStorage.setItem('rushly-auth-lang', lang);
        }

        // Initialize language on load
        document.addEventListener('DOMContentLoaded', () => {
            const savedLang = localStorage.getItem('rushly-auth-lang');
            const browserLang = navigator.language.startsWith('ar') ? 'ar' : 'en';
            const initialLang = savedLang || browserLang;
            
            if (initialLang === 'ar') {
                setLanguage('ar');
            }
        });

        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
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
    </script>
</body>
</html>