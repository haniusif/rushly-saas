import type { Config } from 'tailwindcss';

const config: Config = {
  darkMode: 'class',
  content: [
    './app/**/*.{ts,tsx}',
    './components/**/*.{ts,tsx}',
    './lib/**/*.{ts,tsx}',
  ],
  theme: {
    container: {
      center: true,
      padding: { DEFAULT: '1.25rem', lg: '2rem' },
      screens: { '2xl': '1360px' },
    },
    extend: {
      colors: {
        bg: '#020617',
        surface: '#0F172A',
        primary: {
          DEFAULT: '#2563EB',
          50: '#eff6ff',
          100: '#dbeafe',
          400: '#60a5fa',
          500: '#3b82f6',
          600: '#2563EB',
          700: '#1d4ed8',
          900: '#1e3a8a',
        },
        secondary: {
          DEFAULT: '#06B6D4',
          400: '#22d3ee',
          500: '#06B6D4',
          600: '#0891b2',
        },
        accent: {
          DEFAULT: '#7C3AED',
          400: '#a78bfa',
          500: '#8b5cf6',
          600: '#7C3AED',
        },
        muted: '#94A3B8',
        success: '#22C55E',
        warning: '#F59E0B',
      },
      fontFamily: {
        display: ['var(--font-display)', 'Space Grotesk', 'system-ui', 'sans-serif'],
        sans: ['var(--font-sans)', 'Inter', 'system-ui', 'sans-serif'],
        mono: ['ui-monospace', 'SFMono-Regular', 'monospace'],
      },
      fontSize: {
        'display-2xl': ['clamp(3.5rem, 8vw, 7.5rem)', { lineHeight: '0.95', letterSpacing: '-0.04em', fontWeight: '600' }],
        'display-xl':  ['clamp(2.75rem, 6vw, 5.5rem)', { lineHeight: '1', letterSpacing: '-0.035em', fontWeight: '600' }],
        'display-lg':  ['clamp(2.25rem, 4.5vw, 4rem)', { lineHeight: '1.05', letterSpacing: '-0.03em', fontWeight: '600' }],
        'display-md':  ['clamp(1.75rem, 3vw, 2.75rem)', { lineHeight: '1.1', letterSpacing: '-0.025em', fontWeight: '600' }],
      },
      backgroundImage: {
        'grid-fade': 'radial-gradient(ellipse at top, rgba(37,99,235,0.15), transparent 60%)',
        'aurora':
          'conic-gradient(from 180deg at 50% 50%, rgba(37,99,235,0.25) 0deg, rgba(6,182,212,0.25) 120deg, rgba(124,58,237,0.25) 240deg, rgba(37,99,235,0.25) 360deg)',
        'sheen':
          'linear-gradient(110deg, rgba(255,255,255,0) 30%, rgba(255,255,255,0.08) 50%, rgba(255,255,255,0) 70%)',
      },
      boxShadow: {
        glow: '0 0 60px -10px rgba(37,99,235,0.55)',
        'glow-cyan': '0 0 60px -10px rgba(6,182,212,0.55)',
        'glow-violet': '0 0 60px -10px rgba(124,58,237,0.55)',
        card: '0 1px 0 0 rgba(255,255,255,0.06) inset, 0 20px 60px -20px rgba(2,6,23,0.8)',
        ring: '0 0 0 1px rgba(255,255,255,0.06), 0 20px 40px -20px rgba(2,6,23,0.7)',
      },
      animation: {
        'marquee': 'marquee 40s linear infinite',
        'marquee-slow': 'marquee 80s linear infinite',
        'spin-slow': 'spin 20s linear infinite',
        'float': 'float 6s ease-in-out infinite',
        'float-slow': 'float 9s ease-in-out infinite',
        'pulse-glow': 'pulseGlow 3s ease-in-out infinite',
        'sheen': 'sheen 3s linear infinite',
        'ping-soft': 'pingSoft 2.4s cubic-bezier(0, 0, 0.2, 1) infinite',
      },
      keyframes: {
        marquee: {
          from: { transform: 'translateX(0)' },
          to: { transform: 'translateX(-50%)' },
        },
        float: {
          '0%,100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-12px)' },
        },
        pulseGlow: {
          '0%,100%': { opacity: '0.6', filter: 'blur(24px)' },
          '50%': { opacity: '1', filter: 'blur(32px)' },
        },
        sheen: {
          '0%': { backgroundPosition: '-200% 0' },
          '100%': { backgroundPosition: '200% 0' },
        },
        pingSoft: {
          '0%': { transform: 'scale(1)', opacity: '0.9' },
          '75%,100%': { transform: 'scale(2.4)', opacity: '0' },
        },
      },
    },
  },
  plugins: [require('tailwindcss-animate')],
};

export default config;
