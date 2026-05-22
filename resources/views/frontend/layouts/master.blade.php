<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="Rushly - Smart Logistics Management Platform for Modern Delivery Businesses. Manage shipments, drivers, customers, and operations in one powerful cloud-based platform."/>
  <meta name="keywords" content="logistics, delivery management, SaaS, courier, e-commerce fulfillment, shipment tracking"/>
  <meta name="author" content="Rushly"/>
  <meta property="og:title" content="Rushly - Smart Logistics Management Platform"/>
  <meta property="og:description" content="Manage shipments, drivers, customers, and operations in one powerful cloud-based platform."/>
  <meta property="og:type" content="website"/>
  <title>Rushly - Smart Logistics Management Platform</title>
  
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
            'accent-light': '#1E293B',
            'surface': '#F8FAFC',
            'primary-light': '#f0e6ee',
            'secondary-light': '#3d356b'
          },
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
            arabic: ['Tajawal', 'sans-serif']
          },
          animation: {
            'float': 'float 6s ease-in-out infinite',
            'fade-in': 'fadeIn 0.6s ease-out forwards'
          },
          keyframes: {
            float: {
              '0%, 100%': { transform: 'translateY(0px)' },
              '50%': { transform: 'translateY(-10px)' }
            },
            fadeIn: {
              '0%': { opacity: '0', transform: 'translateY(20px)' },
              '100%': { opacity: '1', transform: 'translateY(0)' }
            }
          }
        }
      }
    }
  </script>
  
  <!-- Custom CSS -->
  <style>
    * {
      scroll-behavior: smooth;
    }
    
    body {
      font-family: 'Inter', sans-serif;
      -webkit-font-smoothing: antialiased;
    }
    
    body.rtl {
      direction: rtl;
      font-family: 'Tajawal', sans-serif;
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
    
    .card-hover {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .card-hover:hover {
      transform: translateY(-8px);
      box-shadow: 0 25px 50px -12px rgba(162, 31, 92, 0.25);
    }
    
    .nav-link {
      position: relative;
      transition: color 0.3s ease;
    }
    
    .nav-link::after {
      content: '';
      position: absolute;
      bottom: -4px;
      left: 0;
      width: 0;
      height: 2px;
      background: linear-gradient(135deg, #a21f5c, #29245a);
      transition: width 0.3s ease;
    }
    
    .rtl .nav-link::after {
      left: auto;
      right: 0;
    }
    
    .nav-link:hover::after {
      width: 100%;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #a21f5c 0%, #29245a 100%);
      transition: all 0.3s ease;
      box-shadow: 0 4px 14px rgba(162, 31, 92, 0.4);
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(162, 31, 92, 0.6);
    }
    
    .btn-secondary {
      border: 2px solid #a21f5c;
      color: #a21f5c;
      transition: all 0.3s ease;
    }
    
    .btn-secondary:hover {
      background: rgba(162, 31, 92, 0.08);
      transform: translateY(-2px);
    }
    
    .feature-icon {
      background: linear-gradient(135deg, rgba(162, 31, 92, 0.1) 0%, rgba(41, 36, 90, 0.1) 100%);
      transition: all 0.3s ease;
    }
    
    .feature-card:hover .feature-icon {
      background: linear-gradient(135deg, #a21f5c 0%, #29245a 100%);
      transform: scale(1.05);
    }
    
    .step-number {
      background: linear-gradient(135deg, #a21f5c 0%, #29245a 100%);
      min-width: 48px;
      height: 48px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      font-weight: 700;
      color: white;
      font-size: 1.25rem;
    }
    
    .dashboard-mockup {
      background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
      border: 1px solid #e2e8f0;
      border-radius: 20px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
      overflow: hidden;
    }
    
    .mobile-menu {
      transition: all 0.3s ease;
      max-height: 0;
      overflow: hidden;
    }
    
    .mobile-menu.active {
      max-height: 500px;
    }
    
    /* Language Selector in Menu */
    .lang-selector {
      display: flex;
      align-items: center;
      gap: 4px;
      padding: 6px 12px;
      border-radius: 8px;
      background: rgba(162, 31, 92, 0.08);
      cursor: pointer;
      transition: all 0.3s ease;
      font-weight: 500;
      font-size: 0.9rem;
    }
    
    .lang-selector:hover {
      background: rgba(162, 31, 92, 0.15);
    }
    
    .lang-option {
      padding: 4px 10px;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-weight: 500;
    }
    
    .lang-option.active {
      background: linear-gradient(135deg, #a21f5c, #29245a);
      color: white;
    }
    
    .lang-option:hover:not(.active) {
      background: rgba(162, 31, 92, 0.1);
    }
    
    .lang-divider {
      width: 1px;
      height: 20px;
      background: rgba(162, 31, 92, 0.3);
    }
    
    @media (max-width: 768px) {
      .hero-content {
        text-align: center;
      }
      
      .hero-buttons {
        justify-content: center;
      }
    }
    
    /* Smooth reveal on scroll */
    .reveal {
      opacity: 0;
      transform: translateY(30px);
      transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    .reveal.active {
      opacity: 1;
      transform: translateY(0);
    }
    
    /* RTL specific adjustments */
    .rtl .flex { flex-direction: row; }
    .rtl .space-x-2 > :not([hidden]) ~ :not([hidden]) { margin-left: 0; margin-right: 0.5rem; }
    .rtl .space-x-3 > :not([hidden]) ~ :not([hidden]) { margin-left: 0; margin-right: 0.75rem; }
    .rtl .space-x-4 > :not([hidden]) ~ :not([hidden]) { margin-left: 0; margin-right: 1rem; }
    .rtl .space-x-6 > :not([hidden]) ~ :not([hidden]) { margin-left: 0; margin-right: 1.5rem; }
    .rtl .space-x-8 > :not([hidden]) ~ :not([hidden]) { margin-left: 0; margin-right: 2rem; }
    .rtl .-space-x-2 > :not([hidden]) ~ :not([hidden]) { margin-left: 0; margin-right: -0.5rem; }
    .rtl .ml-20 { margin-left: 0; margin-right: 5rem; }
    .rtl .mr-20 { margin-right: 0; margin-left: 5rem; }
    .rtl .ml-6 { margin-left: 0; margin-right: 1.5rem; }
    .rtl .mr-6 { margin-right: 0; margin-left: 1.5rem; }
    .rtl .pl-4 { padding-left: 0; padding-right: 1rem; }
    .rtl .pr-4 { padding-right: 0; padding-left: 1rem; }
    .rtl .left-0 { left: auto; right: 0; }
    .rtl .right-0 { right: auto; left: 0; }
    .rtl .-left-4 { left: auto; right: -1rem; }
    .rtl .-right-4 { right: auto; left: -1rem; }
    .rtl .text-left { text-align: right; }
    .rtl .text-right { text-align: left; }
    .rtl .justify-start { justify-content: flex-end; }
    .rtl .justify-end { justify-content: flex-start; }
  </style>
</head>
<body class="bg-white text-accent">

  <!-- Navigation -->
  <nav class="fixed w-full bg-white/95 backdrop-blur-sm z-40 border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-20">
        <!-- Logo -->
        <div class="flex items-center">
          <a href="#" class="flex items-center space-x-3">
            <img src="https://admin.rushly-logistic.com/public/uploads/users/20250612185542.png" alt="Rushly Logo" class="h-10 w-auto">
            <span class="text-2xl font-bold gradient-text hidden sm:block">Rushly</span>
          </a>
        </div>
        
        <!-- Desktop Navigation -->
        <div class="hidden md:flex items-center space-x-8">
          <a href="#home" class="nav-link font-medium text-gray-700 hover:text-primary" data-en="Home" data-ar="الرئيسية">Home</a>
          <a href="#features" class="nav-link font-medium text-gray-700 hover:text-primary" data-en="Features" data-ar="الميزات">Features</a>
          <a href="#solutions" class="nav-link font-medium text-gray-700 hover:text-primary" data-en="Solutions" data-ar="الحلول">Solutions</a>
          <a href="#pricing" class="nav-link font-medium text-gray-700 hover:text-primary" data-en="Pricing" data-ar="الأسعار">Pricing</a>
          <a href="#contact" class="nav-link font-medium text-gray-700 hover:text-primary" data-en="Contact" data-ar="اتصل بنا">Contact</a>
          
          <!-- Language Selector in Menu -->
          <div class="flex items-center space-x-1 pl-4 border-l border-gray-200">
            <span class="lang-option active" onclick="setLanguage('en')" id="lang-en">EN</span>
            <span class="lang-divider"></span>
            <span class="lang-option" onclick="setLanguage('ar')" id="lang-ar">عربي</span>
          </div>
        </div>
        
        <!-- Desktop CTA Buttons -->
        <div class="hidden md:flex items-center space-x-4">
            @if(Auth::check())
            <a href="{{ route('dashboard.index') }}" class="font-medium text-gray-700 hover:text-primary transition-colors" data-en="Login" data-ar="دخول">Hi , {{ Auth::user()->name }}</a>
            @else
            <a href="{{ route('login') }}" class="font-medium text-gray-700 hover:text-primary transition-colors" data-en="Login" data-ar="دخول">Login</a>
            @endif
          
          <a href="{{ route('dashboard.index') }}" class="btn-primary text-white px-6 py-3 rounded-xl font-semibold" data-en="Get Started" data-ar="ابدأ الآن">Get Started</a>
        </div>
        
        <!-- Mobile Menu Button -->
        <button id="mobile-menu-btn" class="md:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
        </button>
      </div>
    </div>
    
    <!-- Mobile Menu -->
    <div id="mobile-menu" class="mobile-menu md:hidden bg-white border-t border-gray-100">
      <div class="px-4 py-4 space-y-3">
        <a href="#home" class="block py-2 font-medium text-gray-700 hover:text-primary" data-en="Home" data-ar="الرئيسية">Home</a>
        <a href="#features" class="block py-2 font-medium text-gray-700 hover:text-primary" data-en="Features" data-ar="الميزات">Features</a>
        <a href="#solutions" class="block py-2 font-medium text-gray-700 hover:text-primary" data-en="Solutions" data-ar="الحلول">Solutions</a>
        <a href="#pricing" class="block py-2 font-medium text-gray-700 hover:text-primary" data-en="Pricing" data-ar="الأسعار">Pricing</a>
        <a href="#contact" class="block py-2 font-medium text-gray-700 hover:text-primary" data-en="Contact" data-ar="اتصل بنا">Contact</a>
        
        <!-- Mobile Language Selector -->
        <div class="pt-4 border-t border-gray-100">
          <p class="text-sm text-gray-500 mb-3" data-en="Select Language" data-ar="اختر اللغة">Select Language</p>
          <div class="flex items-center space-x-2">
            <button class="flex-1 py-2 px-4 rounded-lg font-medium lang-option active" onclick="setLanguage('en')" id="mobile-lang-en">English</button>
            <button class="flex-1 py-2 px-4 rounded-lg font-medium lang-option" onclick="setLanguage('ar')" id="mobile-lang-ar">العربية</button>
          </div>
        </div>
        
        <div class="pt-4 border-t border-gray-100 space-y-3">
          <a href="{{ route('login') }}" class="block w-full text-center py-2 font-medium text-gray-700 hover:text-primary" data-en="Login" data-ar="دخول">Login</a>
          <a href="{{ route('dashboard.index') }}" class="btn-primary text-white px-6 py-3 rounded-xl font-semibold block text-center" data-en="Get Started" data-ar="ابدأ الآن">Get Started</a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section id="home" class="pt-32 pb-20 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Background decoration -->
    <div class="absolute top-0 right-0 -mr-20 -mt-20 w-96 h-96 bg-gradient-to-br from-primary/10 to-secondary/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 bg-gradient-to-tr from-secondary/10 to-primary/10 rounded-full blur-3xl"></div>
    
    <div class="max-w-7xl mx-auto">
      <div class="grid lg:grid-cols-2 gap-12 items-center">
        <!-- Hero Content -->
        <div class="hero-content space-y-8 reveal active">
          <div>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold leading-tight mb-6">
              <span class="hero-headline" data-en="Smart Logistics Management Platform for <span class='gradient-text'>Modern Delivery Businesses</span>" data-ar="منصة إدارة لوجستية ذكية لـ <span class='gradient-text'>شركات التوصيل الحديثة</span>">Smart Logistics Management Platform for <span class="gradient-text">Modern Delivery Businesses</span>
            </h1>
            <p class="text-xl text-gray-600 mb-8 max-w-xl hero-subheadline" data-en="Manage shipments, drivers, customers, and operations in one powerful cloud-based platform." data-ar="أدر الشحنات والسائقين والعملاء والعمليات في منصة سحابية قوية واحدة.">Manage shipments, drivers, customers, and operations in one powerful cloud-based platform.</p>
          </div>
          
          <div class="hero-buttons flex flex-wrap gap-4">
            <a href="{{ route('dashboard.index') }}" class="btn-primary text-white px-8 py-4 rounded-xl font-semibold text-lg inline-flex items-center space-x-2">
              <span class="cta-primary" data-en="Get Started" data-ar="ابدأ الآن">Get Started</span>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
              </svg>
            </a>
            <a href="#" class="btn-secondary px-8 py-4 rounded-xl font-semibold text-lg inline-flex items-center space-x-2">
              <span class="cta-secondary" data-en="Request Demo" data-ar="طلب عرض توضيحي">Request Demo</span>
            </a>
          </div>
          
          <!-- Trust badges -->
          <div class="flex items-center space-x-6 pt-4">
            <div class="flex -space-x-2">
              <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-secondary border-2 border-white"></div>
              <div class="w-10 h-10 rounded-full bg-gray-200 border-2 border-white"></div>
              <div class="w-10 h-10 rounded-full bg-gray-300 border-2 border-white"></div>
            </div>
            <p class="text-gray-600">
              <span class="font-semibold text-accent trust-count" data-en="2,500+" data-ar="+2,500">2,500+</span> <span class="trust-text" data-en="businesses trust Rushly" data-ar="شركة تثق في راشلي">businesses trust Rushly</span>
            </p>
          </div>
        </div>
        
        <!-- Hero Image/Dashboard Mockup -->
        <div class="relative reveal active" style="animation-delay: 0.2s">
          <div class="dashboard-mockup p-6 animate-float">
            <!-- Dashboard Header -->
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
              <div class="flex items-center space-x-3">
                <div class="w-3 h-3 rounded-full bg-red-400"></div>
                <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                <div class="w-3 h-3 rounded-full bg-green-400"></div>
              </div>
              <div class="text-sm text-gray-500">rushly.app/dashboard</div>
            </div>
            
            <!-- Dashboard Content -->
            <div class="space-y-4">
              <!-- Stats Row -->
              <div class="grid grid-cols-3 gap-4">
                <div class="bg-gradient-to-br from-primary/5 to-secondary/5 p-4 rounded-xl">
                  <p class="text-sm text-gray-500" data-en="Active Shipments" data-ar="الشحنات النشطة">Active Shipments</p>
                  <p class="text-2xl font-bold text-accent">1,247</p>
                </div>
                <div class="bg-gradient-to-br from-primary/5 to-secondary/5 p-4 rounded-xl">
                  <p class="text-sm text-gray-500" data-en="Drivers Online" data-ar="السائقون المتصلون">Drivers Online</p>
                  <p class="text-2xl font-bold text-accent">89</p>
                </div>
                <div class="bg-gradient-to-br from-primary/5 to-secondary/5 p-4 rounded-xl">
                  <p class="text-sm text-gray-500" data-en="Deliveries Today" data-ar="تسليمات اليوم">Deliveries Today</p>
                  <p class="text-2xl font-bold text-accent">432</p>
                </div>
              </div>
              
              <!-- Map Placeholder -->
              <div class="bg-gradient-to-br from-gray-50 to-gray-100 border-2 border-dashed border-gray-200 rounded-xl h-48 flex items-center justify-center">
                <div class="text-center">
                  <svg class="w-12 h-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  </svg>
                  <p class="text-gray-500 text-sm" data-en="Real-time GPS Tracking Map" data-ar="خريطة التتبع عبر GPS لحظياً">Real-time GPS Tracking Map</p>
                </div>
              </div>
              
              <!-- Recent Activity -->
              <div class="space-y-3">
                <p class="font-medium text-gray-700" data-en="Recent Deliveries" data-ar="التسليمات الأخيرة">Recent Deliveries</p>
                <div class="space-y-2">
                  <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                      <div class="w-2 h-2 rounded-full bg-green-500"></div>
                      <span class="text-sm font-medium">Order #RSH-2847</span>
                    </div>
                    <span class="text-sm text-gray-500" data-en="Delivered" data-ar="تم التسليم">Delivered</span>
                  </div>
                  <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                      <div class="w-2 h-2 rounded-full bg-yellow-500"></div>
                      <span class="text-sm font-medium">Order #RSH-2846</span>
                    </div>
                    <span class="text-sm text-gray-500" data-en="In Transit" data-ar="قيد النقل">In Transit</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Floating badge -->
          <div class="absolute -bottom-4 -right-4 bg-white p-4 rounded-2xl shadow-xl border border-gray-100 animate-float" style="animation-delay: 1s">
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 gradient-primary rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <div>
                <p class="font-semibold text-accent" data-en="99.9% Uptime" data-ar="وقت تشغيل 99.9%">99.9% Uptime</p>
                <p class="text-xs text-gray-500" data-en="Enterprise-grade reliability" data-ar="موثوقية على مستوى المؤسسات">Enterprise-grade reliability</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Trusted By Section -->
  <section class="py-12 bg-surface border-y border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <p class="text-center text-gray-500 mb-8 font-medium" data-en="Trusted by leading logistics companies worldwide" data-ar="موثوق به من قبل شركات لوجستية رائدة عالمياً">Trusted by leading logistics companies worldwide</p>
      <div class="flex flex-wrap justify-center items-center gap-8 md:gap-16 opacity-70">
        <div class="logo-placeholder w-24 h-12 bg-gray-200 rounded-lg flex items-center justify-center font-semibold text-gray-500">Amazon</div>
        <div class="logo-placeholder w-24 h-12 bg-gray-200 rounded-lg flex items-center justify-center font-semibold text-gray-500">Aramex</div>
        <div class="logo-placeholder w-24 h-12 bg-gray-200 rounded-lg flex items-center justify-center font-semibold text-gray-500">DHL</div>
        <div class="logo-placeholder w-24 h-12 bg-gray-200 rounded-lg flex items-center justify-center font-semibold text-gray-500">FedEx</div>
        <div class="logo-placeholder w-24 h-12 bg-gray-200 rounded-lg flex items-center justify-center font-semibold text-gray-500">Noon</div>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section id="features" class="py-20 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
      <div class="text-center max-w-3xl mx-auto mb-16 reveal">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">
          <span class="features-title" data-en="Powerful Features to Run Your <span class='gradient-text'>Logistics Business</span>" data-ar="ميزات قوية لإدارة <span class='gradient-text'>عملك اللوجستي</span>">Powerful Features to Run Your <span class="gradient-text">Logistics Business</span>
        </h2>
        <p class="text-xl text-gray-600 features-desc" data-en="Everything you need to streamline operations, reduce costs, and delight customers." data-ar="كل ما تحتاجه لتبسيط العمليات وخفض التكاليف وإسعاد العملاء.">Everything you need to streamline operations, reduce costs, and delight customers.</p>
      </div>
      
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
        <!-- Feature 1 -->
        <div class="feature-card card-hover bg-white p-8 rounded-2xl border border-gray-100 reveal">
          <div class="feature-icon w-14 h-14 rounded-2xl flex items-center justify-center mb-6">
            <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
          </div>
          <h3 class="text-xl font-bold mb-3" data-en="Shipment Management" data-ar="إدارة الشحنات">Shipment Management</h3>
          <p class="text-gray-600" data-en="Create, track, and manage shipments easily with intuitive tools and automated workflows." data-ar="أنشئ وتتبع وأدر الشحنات بسهولة باستخدام أدوات بديهية وسير عمل آلي.">Create, track, and manage shipments easily with intuitive tools and automated workflows.</p>
        </div>
        
        <!-- Feature 2 -->
        <div class="feature-card card-hover bg-white p-8 rounded-2xl border border-gray-100 reveal" style="animation-delay: 0.1s">
          <div class="feature-icon w-14 h-14 rounded-2xl flex items-center justify-center mb-6">
            <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
          </div>
          <h3 class="text-xl font-bold mb-3" data-en="Driver Management" data-ar="إدارة السائقين">Driver Management</h3>
          <p class="text-gray-600" data-en="Manage drivers, assignments, and performance with real-time insights and scheduling tools." data-ar="أدر السائقين والمهام والأداء مع رؤى لحظية وأدوات جدولة.">Manage drivers, assignments, and performance with real-time insights and scheduling tools.</p>
        </div>
        
        <!-- Feature 3 -->
        <div class="feature-card card-hover bg-white p-8 rounded-2xl border border-gray-100 reveal" style="animation-delay: 0.2s">
          <div class="feature-icon w-14 h-14 rounded-2xl flex items-center justify-center mb-6">
            <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
          </div>
          <h3 class="text-xl font-bold mb-3" data-en="Real-time Tracking" data-ar="التتبع اللحظي">Real-time Tracking</h3>
          <p class="text-gray-600" data-en="Track deliveries live with GPS tracking and provide customers with accurate ETAs." data-ar="تتبع التسليمات مباشرة عبر GPS وقدم للعملاء أوقات وصول دقيقة.">Track deliveries live with GPS tracking and provide customers with accurate ETAs.</p>
        </div>
        
        <!-- Feature 4 -->
        <div class="feature-card card-hover bg-white p-8 rounded-2xl border border-gray-100 reveal">
          <div class="feature-icon w-14 h-14 rounded-2xl flex items-center justify-center mb-6">
            <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
          </div>
          <h3 class="text-xl font-bold mb-3" data-en="Customer Management" data-ar="إدارة العملاء">Customer Management</h3>
          <p class="text-gray-600" data-en="Manage customers and delivery history with CRM tools and communication features." data-ar="أدر العملاء وسجل التسليمات باستخدام أدوات CRM وميزات التواصل.">Manage customers and delivery history with CRM tools and communication features.</p>
        </div>
        
        <!-- Feature 5 -->
        <div class="feature-card card-hover bg-white p-8 rounded-2xl border border-gray-100 reveal" style="animation-delay: 0.1s">
          <div class="feature-icon w-14 h-14 rounded-2xl flex items-center justify-center mb-6">
            <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
          </div>
          <h3 class="text-xl font-bold mb-3" data-en="Analytics & Reports" data-ar="التحليلات والتقارير">Analytics & Reports</h3>
          <p class="text-gray-600" data-en="View performance insights and reports to make data-driven decisions for growth." data-ar="اعرض رؤى الأداء والتقارير لاتخاذ قرارات مدعومة بالبيانات للنمو.">View performance insights and reports to make data-driven decisions for growth.</p>
        </div>
        
        <!-- Feature 6 -->
        <div class="feature-card card-hover bg-white p-8 rounded-2xl border border-gray-100 reveal" style="animation-delay: 0.2s">
          <div class="feature-icon w-14 h-14 rounded-2xl flex items-center justify-center mb-6">
            <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
          </div>
          <h3 class="text-xl font-bold mb-3" data-en="Multi-Branch Support" data-ar="دعم الفروع المتعددة">Multi-Branch Support</h3>
          <p class="text-gray-600" data-en="Manage multiple branches and teams with role-based access and centralized control." data-ar="أدر فروعاً وفرقاً متعددة مع صلاحيات قائمة على الأدوار وتحكم مركزي.">Manage multiple branches and teams with role-based access and centralized control.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Dashboard Preview Section -->
  <section class="py-20 bg-gradient-to-b from-white to-surface px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
      <div class="grid lg:grid-cols-2 gap-16 items-center">
        <div class="reveal">
          <h2 class="text-3xl md:text-4xl font-bold mb-6">
            <span class="dashboard-title" data-en="Everything you need in one <span class='gradient-text'>unified dashboard</span>" data-ar="كل ما تحتاجه في <span class='gradient-text'>لوحة تحكم موحدة</span> واحدة">Everything you need in one <span class="gradient-text">unified dashboard</span>
          </h2>
          <p class="text-xl text-gray-600 mb-8 dashboard-desc" data-en="Get complete visibility and control over your entire logistics operation from a single, intuitive interface." data-ar="احصل على رؤية كاملة وتحكم في عملياتك اللوجستية بأكملها من واجهة بديهية واحدة.">Get complete visibility and control over your entire logistics operation from a single, intuitive interface.</p>
          
          <ul class="space-y-4">
            <li class="flex items-start space-x-4">
              <div class="flex-shrink-0 w-6 h-6 gradient-primary rounded-full flex items-center justify-center mt-1">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
              </div>
              <span class="text-lg text-gray-700" data-en="Live shipment tracking with real-time updates" data-ar="تتبع مباشر للشحنات مع تحديثات لحظية">Live shipment tracking with real-time updates</span>
            </li>
            <li class="flex items-start space-x-4">
              <div class="flex-shrink-0 w-6 h-6 gradient-primary rounded-full flex items-center justify-center mt-1">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
              </div>
              <span class="text-lg text-gray-700" data-en="Driver monitoring and performance analytics" data-ar="مراقبة السائقين وتحليلات الأداء">Driver monitoring and performance analytics</span>
            </li>
            <li class="flex items-start space-x-4">
              <div class="flex-shrink-0 w-6 h-6 gradient-primary rounded-full flex items-center justify-center mt-1">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
              </div>
              <span class="text-lg text-gray-700" data-en="Real-time analytics and customizable reports" data-ar="تحليلات لحظية وتقارير قابلة للتخصيص">Real-time analytics and customizable reports</span>
            </li>
            <li class="flex items-start space-x-4">
              <div class="flex-shrink-0 w-6 h-6 gradient-primary rounded-full flex items-center justify-center mt-1">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
              </div>
              <span class="text-lg text-gray-700" data-en="Full operational control with role-based permissions" data-ar="تحكم تشغيلي كامل مع صلاحيات قائمة على الأدوار">Full operational control with role-based permissions</span>
            </li>
          </ul>
        </div>
        
        <div class="relative reveal" style="animation-delay: 0.2s">
          <div class="dashboard-mockup p-4">
            <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl border border-gray-200 overflow-hidden">
              <!-- Dashboard Header -->
              <div class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                  <div class="w-10 h-10 gradient-primary rounded-xl flex items-center justify-center">
                    <span class="text-white font-bold">R</span>
                  </div>
                  <div>
                    <p class="font-semibold text-accent">Rushly Dashboard</p>
                    <p class="text-xs text-gray-500" data-en="Operations Overview" data-ar="نظرة عامة على العمليات">Operations Overview</p>
                  </div>
                </div>
                <div class="flex items-center space-x-2">
                  <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full" data-en="All Systems Operational" data-ar="جميع الأنظمة تعمل">All Systems Operational</span>
                </div>
              </div>
              
              <!-- Dashboard Body -->
              <div class="p-6">
                <!-- Quick Stats -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                  <div class="bg-white p-4 rounded-xl border border-gray-100">
                    <p class="text-sm text-gray-500 mb-1" data-en="On-Time Delivery" data-ar="التسليم في الوقت المحدد">On-Time Delivery</p>
                    <p class="text-2xl font-bold text-accent">98.7%</p>
                    <span class="text-xs text-green-600">↑ 2.3% vs last week</span>
                  </div>
                  <div class="bg-white p-4 rounded-xl border border-gray-100">
                    <p class="text-sm text-gray-500 mb-1" data-en="Avg. Delivery Time" data-ar="متوسط وقت التسليم">Avg. Delivery Time</p>
                    <p class="text-2xl font-bold text-accent">24 min</p>
                    <span class="text-xs text-green-600">↑ 5% faster</span>
                  </div>
                </div>
                
                <!-- Map Visualization -->
                <div class="bg-gradient-to-br from-blue-50 to-cyan-50 border border-blue-100 rounded-xl p-4 mb-6">
                  <div class="flex items-center justify-between mb-3">
                    <p class="font-medium text-accent" data-en="Live Fleet Map" data-ar="خريطة الأسطول الحية">Live Fleet Map</p>
                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded">12 active</span>
                  </div>
                  <div class="h-32 bg-gradient-to-br from-blue-100 to-cyan-100 rounded-lg flex items-center justify-center">
                    <div class="grid grid-cols-4 gap-2">
                      <div class="w-3 h-3 bg-primary rounded-full animate-pulse"></div>
                      <div class="w-3 h-3 bg-secondary rounded-full animate-pulse" style="animation-delay: 0.2s"></div>
                      <div class="w-3 h-3 bg-primary rounded-full animate-pulse" style="animation-delay: 0.4s"></div>
                      <div class="w-3 h-3 bg-secondary rounded-full animate-pulse" style="animation-delay: 0.6s"></div>
                    </div>
                  </div>
                </div>
                
                <!-- Activity Feed -->
                <div>
                  <p class="font-medium text-accent mb-3" data-en="Recent Activity" data-ar="النشاط الأخير">Recent Activity</p>
                  <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                      <div class="flex items-center space-x-3">
                        <div class="w-2 h-2 rounded-full bg-green-500"></div>
                        <span class="text-sm" data-en="Delivery completed • Order #4821" data-ar="تم التسليم • طلب #4821">Delivery completed • Order #4821</span>
                      </div>
                      <span class="text-xs text-gray-500">2m ago</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                      <div class="flex items-center space-x-3">
                        <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                        <span class="text-sm" data-en="New shipment assigned • Driver #D89" data-ar="تم تعيين شحنة جديدة • سائق #D89">New shipment assigned • Driver #D89</span>
                      </div>
                      <span class="text-xs text-gray-500">8m ago</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Solutions Section -->
  <section id="solutions" class="py-20 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
      <div class="text-center max-w-3xl mx-auto mb-16 reveal">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">
          <span class="solutions-title" data-en="Built for Every <span class='gradient-text'>Logistics Business</span>" data-ar="مصمم لكل <span class='gradient-text'>عمل لوجستي</span>">Built for Every <span class="gradient-text">Logistics Business</span>
        </h2>
        <p class="text-xl text-gray-600 solutions-desc" data-en="Whether you're a startup or enterprise, Rushly scales with your needs." data-ar="سواء كنت شركة ناشئة أو مؤسسة كبرى، راشلي يتوسع مع احتياجاتك.">Whether you're a startup or enterprise, Rushly scales with your needs.</p>
      </div>
      
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Solution 1 -->
        <div class="card-hover bg-white p-8 rounded-2xl border border-gray-100 text-center reveal">
          <div class="w-16 h-16 gradient-primary rounded-2xl flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
          </div>
          <h3 class="text-xl font-bold mb-3" data-en="Courier Companies" data-ar="شركات التوصيل">Courier Companies</h3>
          <p class="text-gray-600" data-en="Streamline last-mile delivery, optimize routes, and improve customer satisfaction." data-ar="بسّط توصيل الميل الأخير، وحسّن المسارات، وارفع رضا العملاء.">Streamline last-mile delivery, optimize routes, and improve customer satisfaction.</p>
        </div>
        
        <!-- Solution 2 -->
        <div class="card-hover bg-white p-8 rounded-2xl border border-gray-100 text-center reveal" style="animation-delay: 0.1s">
          <div class="w-16 h-16 gradient-primary rounded-2xl flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
            </svg>
          </div>
          <h3 class="text-xl font-bold mb-3" data-en="E-commerce Businesses" data-ar="أعمال التجارة الإلكترونية">E-commerce Businesses</h3>
          <p class="text-gray-600" data-en="Integrate with your store, automate fulfillment, and provide seamless delivery experiences." data-ar="ادمج مع متجرك، وأتمت التنفيذ، وقدم تجارب تسليم سلسة.">Integrate with your store, automate fulfillment, and provide seamless delivery experiences.</p>
        </div>
        
        <!-- Solution 3 -->
        <div class="card-hover bg-white p-8 rounded-2xl border border-gray-100 text-center reveal" style="animation-delay: 0.2s">
          <div class="w-16 h-16 gradient-primary rounded-2xl flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
            </svg>
          </div>
          <h3 class="text-xl font-bold mb-3" data-en="Delivery Startups" data-ar="شركات التوصيل الناشئة">Delivery Startups</h3>
          <p class="text-gray-600" data-en="Launch fast with enterprise-grade tools without the enterprise complexity or cost." data-ar="انطلق بسرعة بأدوات على مستوى المؤسسات دون تعقيد أو تكلفة المؤسسات.">Launch fast with enterprise-grade tools without the enterprise complexity or cost.</p>
        </div>
        
        <!-- Solution 4 -->
        <div class="card-hover bg-white p-8 rounded-2xl border border-gray-100 text-center reveal" style="animation-delay: 0.3s">
          <div class="w-16 h-16 gradient-primary rounded-2xl flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
          </div>
          <h3 class="text-xl font-bold mb-3" data-en="Enterprise Logistics" data-ar="اللوجستيات المؤسسية">Enterprise Logistics</h3>
          <p class="text-gray-600" data-en="Scale globally with advanced analytics, API integrations, and dedicated support." data-ar="توسع عالمياً مع تحليلات متقدمة ودمج واجهات برمجية ودعم مخصص.">Scale globally with advanced analytics, API integrations, and dedicated support.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- How It Works Section -->
  <section class="py-20 bg-surface px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
      <div class="text-center max-w-3xl mx-auto mb-16 reveal">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">
          <span class="how-title" data-en="Get Started in <span class='gradient-text'>4 Simple Steps</span>" data-ar="ابدأ في <span class='gradient-text'>4 خطوات بسيطة</span>">Get Started in <span class="gradient-text">4 Simple Steps</span>
        </h2>
        <p class="text-xl text-gray-600 how-desc" data-en="No complex setup. No long contracts. Start optimizing your logistics today." data-ar="بدون إعداد معقد. بدون عقود طويلة. ابدأ في تحسين لوجستياتك اليوم.">No complex setup. No long contracts. Start optimizing your logistics today.</p>
      </div>
      
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
        <!-- Step 1 -->
        <div class="reveal text-center">
          <div class="step-number mx-auto mb-6">1</div>
          <h3 class="text-xl font-bold mb-3" data-en="Create your account" data-ar="أنشئ حسابك">Create your account</h3>
          <p class="text-gray-600" data-en="Sign up in seconds with your business email. No credit card required to start." data-ar="سجل في ثوانٍ باستخدام بريدك التجاري. لا حاجة لبطاقة ائتمان للبدء.">Sign up in seconds with your business email. No credit card required to start.</p>
        </div>
        
        <!-- Step 2 -->
        <div class="reveal text-center" style="animation-delay: 0.1s">
          <div class="step-number mx-auto mb-6">2</div>
          <h3 class="text-xl font-bold mb-3" data-en="Add drivers and shipments" data-ar="أضف السائقين والشحنات">Add drivers and shipments</h3>
          <p class="text-gray-600" data-en="Import your team and start creating shipments with our intuitive interface." data-ar="استورد فريقك وابدأ في إنشاء الشحنات عبر واجهتنا البديهية.">Import your team and start creating shipments with our intuitive interface.</p>
        </div>
        
        <!-- Step 3 -->
        <div class="reveal text-center" style="animation-delay: 0.2s">
          <div class="step-number mx-auto mb-6">3</div>
          <h3 class="text-xl font-bold mb-3" data-en="Track and manage deliveries" data-ar="تتبع وأدر التسليمات">Track and manage deliveries</h3>
          <p class="text-gray-600" data-en="Monitor everything in real-time and communicate with drivers and customers effortlessly." data-ar="راقب كل شيء لحظياً وتواصل مع السائقين والعملاء بسهولة.">Monitor everything in real-time and communicate with drivers and customers effortlessly.</p>
        </div>
        
        <!-- Step 4 -->
        <div class="reveal text-center" style="animation-delay: 0.3s">
          <div class="step-number mx-auto mb-6">4</div>
          <h3 class="text-xl font-bold mb-3" data-en="Grow your logistics business" data-ar="طوّر عملك اللوجستي">Grow your logistics business</h3>
          <p class="text-gray-600" data-en="Use insights to optimize routes, reduce costs, and scale your operations confidently." data-ar="استخدم الرؤى لتحسين المسارات وخفض التكاليف وتوسيع عملياتك بثقة.">Use insights to optimize routes, reduce costs, and scale your operations confidently.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Pricing Preview Section -->
  <section id="pricing" class="py-20 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
      <div class="text-center max-w-3xl mx-auto mb-16 reveal">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">
          <span class="pricing-title" data-en="Simple, Transparent <span class='gradient-text'>Pricing</span>" data-ar="أسعار <span class='gradient-text'>بسيطة وواضحة</span>">Simple, Transparent <span class="gradient-text">Pricing</span>
        </h2>
        <p class="text-xl text-gray-600 pricing-desc" data-en="Choose the plan that fits your business. All plans include core features and 24/7 support." data-ar="اختر الخطة التي تناسب عملك. جميع الخطط تشمل الميزات الأساسية ودعم على مدار الساعة.">Choose the plan that fits your business. All plans include core features and 24/7 support.</p>
      </div>
      
      <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
        <!-- Starter Plan -->
        <div class="card-hover bg-white p-8 rounded-2xl border border-gray-100 reveal">
          <h3 class="text-2xl font-bold mb-2" data-en="Starter" data-ar="المبتدئ">Starter</h3>
          <p class="text-gray-600 mb-6" data-en="Perfect for small teams getting started" data-ar="مثالي للفرق الصغيرة في البداية">Perfect for small teams getting started</p>
          <div class="mb-6">
            <span class="text-4xl font-bold">$49</span>
            <span class="text-gray-500">/month</span>
          </div>
          <ul class="space-y-3 mb-8">
            <li class="flex items-center space-x-3">
              <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span class="text-gray-700" data-en="Up to 5 drivers" data-ar="حتى 5 سائقين">Up to 5 drivers</span>
            </li>
            <li class="flex items-center space-x-3">
              <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span class="text-gray-700" data-en="500 shipments/month" data-ar="500 شحنة/شهر">500 shipments/month</span>
            </li>
            <li class="flex items-center space-x-3">
              <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span class="text-gray-700" data-en="Basic analytics" data-ar="تحليلات أساسية">Basic analytics</span>
            </li>
            <li class="flex items-center space-x-3">
              <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span class="text-gray-700" data-en="Email support" data-ar="دعم عبر البريد">Email support</span>
            </li>
          </ul>
          <a href="#" class="btn-secondary w-full py-3 rounded-xl font-semibold block text-center" data-en="Get Started" data-ar="ابدأ الآن">Get Started</a>
        </div>
        
        <!-- Professional Plan (Featured) -->
        <div class="card-hover bg-white p-8 rounded-2xl border-2 border-primary relative reveal" style="animation-delay: 0.1s">
          <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
            <span class="gradient-primary text-white px-4 py-1 rounded-full text-sm font-medium" data-en="Most Popular" data-ar="الأكثر شعبية">Most Popular</span>
          </div>
          <h3 class="text-2xl font-bold mb-2" data-en="Professional" data-ar="المحترف">Professional</h3>
          <p class="text-gray-600 mb-6" data-en="For growing businesses that need more power" data-ar="للشركات النامية التي تحتاج لمزيد من القوة">For growing businesses that need more power</p>
          <div class="mb-6">
            <span class="text-4xl font-bold">$129</span>
            <span class="text-gray-500">/month</span>
          </div>
          <ul class="space-y-3 mb-8">
            <li class="flex items-center space-x-3">
              <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span class="text-gray-700" data-en="Up to 25 drivers" data-ar="حتى 25 سائق">Up to 25 drivers</span>
            </li>
            <li class="flex items-center space-x-3">
              <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span class="text-gray-700" data-en="2000 shipments/month" data-ar="2000 شحنة/شهر">2000 shipments/month</span>
            </li>
            <li class="flex items-center space-x-3">
              <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span class="text-gray-700" data-en="Advanced analytics & reports" data-ar="تحليلات وتقارير متقدمة">Advanced analytics & reports</span>
            </li>
            <li class="flex items-center space-x-3">
              <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span class="text-gray-700" data-en="Priority support + API access" data-ar="دعم مميز + وصول للواجهة البرمجية">Priority support + API access</span>
            </li>
          </ul>
          <a href="#" class="btn-primary w-full py-3 rounded-xl font-semibold text-white block text-center" data-en="Get Started" data-ar="ابدأ الآن">Get Started</a>
        </div>
        
        <!-- Enterprise Plan -->
        <div class="card-hover bg-white p-8 rounded-2xl border border-gray-100 reveal" style="animation-delay: 0.2s">
          <h3 class="text-2xl font-bold mb-2" data-en="Enterprise" data-ar="المؤسسات">Enterprise</h3>
          <p class="text-gray-600 mb-6" data-en="For large organizations with complex needs" data-ar="للمؤسسات الكبيرة ذات الاحتياجات المعقدة">For large organizations with complex needs</p>
          <div class="mb-6">
            <span class="text-4xl font-bold" data-en="Custom" data-ar="مخصص">Custom</span>
          </div>
          <ul class="space-y-3 mb-8">
            <li class="flex items-center space-x-3">
              <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span class="text-gray-700" data-en="Unlimited drivers & shipments" data-ar="سائقين وشحنات غير محدودة">Unlimited drivers & shipments</span>
            </li>
            <li class="flex items-center space-x-3">
              <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span class="text-gray-700" data-en="Custom integrations & SSO" data-ar="دمج مخصص وSSO">Custom integrations & SSO</span>
            </li>
            <li class="flex items-center space-x-3">
              <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span class="text-gray-700" data-en="Dedicated account manager" data-ar="مدير حساب مخصص">Dedicated account manager</span>
            </li>
            <li class="flex items-center space-x-3">
              <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span class="text-gray-700" data-en="SLA guarantees & 24/7 support" data-ar="ضمانات SLA ودعم 24/7">SLA guarantees & 24/7 support</span>
            </li>
          </ul>
          <a href="#" class="btn-secondary w-full py-3 rounded-xl font-semibold block text-center" data-en="Contact Sales" data-ar="تواصل مع المبيعات">Contact Sales</a>
        </div>
      </div>
      
      <div class="text-center mt-12 reveal">
        <a href="#" class="inline-flex items-center space-x-2 text-primary font-semibold hover:underline">
          <span class="pricing-link" data-en="View full pricing details" data-ar="عرض تفاصيل الأسعار الكاملة">View full pricing details</span>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </a>
      </div>
    </div>
  </section>

  <!-- Call To Action Section -->
  <section class="py-20 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto text-center reveal">
      <div class="bg-gradient-to-br from-primary to-secondary rounded-3xl p-10 md:p-16">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-6 cta-headline" data-en="Start Managing Your Logistics Smarter Today" data-ar="ابدأ في إدارة لوجستياتك بذكاء اليوم">Start Managing Your Logistics Smarter Today</h2>
        <p class="text-xl text-white/90 mb-10 max-w-2xl mx-auto cta-subhead" data-en="Join thousands of delivery businesses that trust Rushly to power their operations." data-ar="انضم لآلاف شركات التوصيل التي تثق براشلي لتشغيل عملياتها.">Join thousands of delivery businesses that trust Rushly to power their operations.</p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
          <a href="#" class="bg-white text-primary px-8 py-4 rounded-xl font-semibold text-lg hover:bg-gray-50 transition-colors cta-primary" data-en="Get Started Free" data-ar="ابدأ مجاناً">Get Started Free</a>
          <a href="#" class="border-2 border-white text-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-white/10 transition-colors cta-secondary" data-en="Request Demo" data-ar="طلب عرض توضيحي">Request Demo</a>
        </div>
        <p class="text-white/80 text-sm mt-6 cta-note" data-en="No credit card required • 14-day free trial • Cancel anytime" data-ar="لا حاجة لبطاقة ائتمان • تجربة مجانية 14 يوم • إلغاء في أي وقت">No credit card required • 14-day free trial • Cancel anytime</p>
      </div>
    </div>
  </section>

  <!-- Footer Section -->
  <footer id="contact" class="bg-secondary text-white pt-20 pb-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-12 mb-16">
        <!-- Brand Column -->
        <div class="lg:col-span-1">
          <a href="#" class="flex items-center space-x-3 mb-6">
            <img src="https://rushly-logistic.com/public/uploads/settings/202506121825217744.png" alt="Rushly Logo" class="h-10 w-auto">
            <span class="text-2xl font-bold text-white hidden sm:block">Rushly</span>
          </a>
          <p class="text-gray-300 mb-6 max-w-xs footer-desc" data-en="Smart logistics management platform for modern delivery businesses." data-ar="منصة إدارة لوجستية ذكية لشركات التوصيل الحديثة.">Smart logistics management platform for modern delivery businesses.</p>
          <div class="flex space-x-4">
            <a href="#" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition-colors">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"></path>
              </svg>
            </a>
            <a href="#" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition-colors">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z"></path>
                <circle cx="4" cy="4" r="2"></circle>
              </svg>
            </a>
            <a href="#" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition-colors">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"></path>
              </svg>
            </a>
          </div>
        </div>
        
        <!-- Product Links -->
        <div>
          <h4 class="font-semibold text-lg mb-4" data-en="Product" data-ar="المنتج">Product</h4>
          <ul class="space-y-3">
            <li><a href="#features" class="text-gray-300 hover:text-white transition-colors" data-en="Features" data-ar="الميزات">Features</a></li>
            <li><a href="#pricing" class="text-gray-300 hover:text-white transition-colors" data-en="Pricing" data-ar="الأسعار">Pricing</a></li>
            <li><a href="#solutions" class="text-gray-300 hover:text-white transition-colors" data-en="Solutions" data-ar="الحلول">Solutions</a></li>
            <li><a href="#" class="text-gray-300 hover:text-white transition-colors" data-en="Integrations" data-ar="الدمج">Integrations</a></li>
            <li><a href="#" class="text-gray-300 hover:text-white transition-colors" data-en="API Docs" data-ar="وثائق API">API Docs</a></li>
          </ul>
        </div>
        
        <!-- Company Links -->
        <div>
          <h4 class="font-semibold text-lg mb-4" data-en="Company" data-ar="الشركة">Company</h4>
          <ul class="space-y-3">
            <li><a href="#" class="text-gray-300 hover:text-white transition-colors" data-en="About Us" data-ar="من نحن">About Us</a></li>
            <li><a href="#" class="text-gray-300 hover:text-white transition-colors" data-en="Careers" data-ar="الوظائف">Careers</a></li>
            <li><a href="#" class="text-gray-300 hover:text-white transition-colors" data-en="Blog" data-ar="المدونة">Blog</a></li>
            <li><a href="#" class="text-gray-300 hover:text-white transition-colors" data-en="News" data-ar="الأخبار">News</a></li>
            <li><a href="#contact" class="text-gray-300 hover:text-white transition-colors" data-en="Contact" data-ar="اتصل بنا">Contact</a></li>
          </ul>
        </div>
        
        <!-- Contact Info -->
       <div class="contact-widget">
  
  <h4 class="font-semibold text-lg mb-4 text-white"
      data-en="Contact"
      data-ar="اتصل بنا">
      Contact
  </h4>

  <ul class="space-y-3">

    <!-- Email -->
    <li class="flex items-start gap-3 group">
      <svg class="w-5 h-5 text-primary mt-1 flex-shrink-0 group-hover:scale-110 transition"
           fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
      </svg>

      <a href="mailto:info@rushly-logistic.com"
         class="text-gray-300 hover:text-primary transition">
         info@rushly-logistic.com
      </a>
    </li>


    <!-- KSA Phone -->
    <li class="flex items-start gap-3 group">
      <svg class="w-5 h-5 text-primary mt-1 flex-shrink-0 group-hover:scale-110 transition"
           fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21
                 l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502
                 l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
      </svg>

      <a href="tel:+966508256178"
         class="text-gray-300 hover:text-primary transition"
         dir="ltr">
         +966 50 825 6178
      </a>
    </li>


    <!-- UAE Phone -->
    <li class="flex items-start gap-3 group">
      <svg class="w-5 h-5 text-primary mt-1 flex-shrink-0 group-hover:scale-110 transition"
           fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21
                 l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502
                 l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
      </svg>

      <a href="tel:+971501280293"
         class="text-gray-300 hover:text-primary transition"
         dir="ltr">
         +971 50 128 0293
      </a>
    </li>


    <!-- Riyadh -->
    <li class="flex items-start gap-3 group">
      <svg class="w-5 h-5 text-primary mt-1 flex-shrink-0 group-hover:scale-110 transition"
           fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243
                 a8 8 0 1111.314 0z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
      </svg>

      <span class="text-gray-300"
            data-en="Riyadh, Saudi Arabia"
            data-ar="الرياض، المملكة العربية السعودية">
            Riyadh, Saudi Arabia
      </span>
    </li>


    <!-- Dubai -->
    <li class="flex items-start gap-3 group">
      <svg class="w-5 h-5 text-primary mt-1 flex-shrink-0 group-hover:scale-110 transition"
           fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243
                 a8 8 0 1111.314 0z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
      </svg>

      <span class="text-gray-300"
            data-en="Dubai, United Arab Emirates"
            data-ar="دبي، الإمارات العربية المتحدة">
            Dubai, United Arab Emirates
      </span>
    </li>

  </ul>

</div>
      </div>
      
      <!-- Bottom Footer -->
      <div class="pt-8 border-t border-gray-700 flex flex-col md:flex-row justify-between items-center">
        <p class="text-gray-400 text-sm mb-4 md:mb-0">
          &copy; 2024 Rushly. <span data-en="All rights reserved." data-ar="جميع الحقوق محفوظة.">All rights reserved.</span>
        </p>
        <div class="flex flex-wrap justify-center gap-6 text-sm">
          <a href="#" class="text-gray-400 hover:text-white transition-colors" data-en="Privacy Policy" data-ar="سياسة الخصوصية">Privacy Policy</a>
          <a href="#" class="text-gray-400 hover:text-white transition-colors" data-en="Terms of Service" data-ar="شروط الخدمة">Terms of Service</a>
          <a href="#" class="text-gray-400 hover:text-white transition-colors" data-en="Cookie Policy" data-ar="سياسة ملفات التعريف">Cookie Policy</a>
          <a href="#" class="text-gray-400 hover:text-white transition-colors" data-en="Security" data-ar="الأمان">Security</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- JavaScript for Interactions & Language Toggle -->
  <script>
    // Language data
    const translations = {
      en: {
        dir: 'ltr',
        font: 'Inter'
      },
      ar: {
        dir: 'rtl',
        font: 'Tajawal'
      }
    };

    // Set language function
    function setLanguage(lang) {
      // Update HTML dir attribute
      document.documentElement.setAttribute('dir', translations[lang].dir);
      document.documentElement.setAttribute('lang', lang);
      
      // Update body font
      document.body.style.fontFamily = translations[lang].font + ', sans-serif';
      
      // Toggle active state on desktop buttons
      document.getElementById('lang-en').classList.toggle('active', lang === 'en');
      document.getElementById('lang-ar').classList.toggle('active', lang === 'ar');
      
      // Toggle active state on mobile buttons
      document.getElementById('mobile-lang-en').classList.toggle('active', lang === 'en');
      document.getElementById('mobile-lang-ar').classList.toggle('active', lang === 'ar');
      
      // Update all translatable elements
      document.querySelectorAll('[data-en][data-ar]').forEach(el => {
        el.innerHTML = el.getAttribute(`data-${lang}`);
      });
      
      // Store preference
      localStorage.setItem('rushly-lang', lang);
      
      // Re-trigger reveal animations for RTL layout
      setTimeout(() => {
        revealOnScroll();
      }, 100);
    }

    // Initialize language from localStorage or browser
    document.addEventListener('DOMContentLoaded', () => {
      const savedLang = localStorage.getItem('rushly-lang');
      const browserLang = navigator.language.startsWith('ar') ? 'ar' : 'en';
      const initialLang = savedLang || browserLang;
      
      if (initialLang === 'ar') {
        setLanguage('ar');
      }
    });

    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    
    mobileMenuBtn.addEventListener('click', () => {
      mobileMenu.classList.toggle('active');
      const icon = mobileMenuBtn.querySelector('svg');
      if (mobileMenu.classList.contains('active')) {
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>';
      } else {
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>';
      }
    });
    
    // Close mobile menu when clicking a link
    document.querySelectorAll('#mobile-menu a').forEach(link => {
      link.addEventListener('click', () => {
        mobileMenu.classList.remove('active');
        mobileMenuBtn.querySelector('svg').innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>';
      });
    });
    
    // Scroll reveal animation
    const revealElements = document.querySelectorAll('.reveal');
    
    const revealOnScroll = () => {
      const windowHeight = window.innerHeight;
      const elementVisible = 150;
      
      revealElements.forEach(element => {
        const elementTop = element.getBoundingClientRect().top;
        if (elementTop < windowHeight - elementVisible) {
          element.classList.add('active');
        }
      });
    };
    
    // Initial check and scroll listener
    window.addEventListener('load', revealOnScroll);
    window.addEventListener('scroll', revealOnScroll);
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    });
    
    // Navbar background on scroll
    window.addEventListener('scroll', () => {
      const nav = document.querySelector('nav');
      if (window.scrollY > 50) {
        nav.classList.add('shadow-sm');
        nav.classList.replace('bg-white/95', 'bg-white/98');
      } else {
        nav.classList.remove('shadow-sm');
        nav.classList.replace('bg-white/98', 'bg-white/95');
      }
    });
  </script>
</body>
</html>

