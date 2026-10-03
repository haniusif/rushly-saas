'use client';
import { motion } from 'framer-motion';
import { ArrowRight, PlayCircle, Sparkles } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { DotPulse } from '@/components/ui/badge';
import { HeroDashboard } from '@/components/mockups/hero-dashboard';

export function Hero() {
  return (
    <section className="relative pt-32 pb-24 md:pt-40 md:pb-28 overflow-hidden">
      <div className="absolute inset-0 -z-10">
        <div className="absolute inset-x-0 top-0 h-[900px] bg-grid-fade" />
        <div className="absolute inset-x-0 top-40 h-[600px] grid-bg grid-fade-mask opacity-70" />
        <div className="absolute left-1/2 top-24 -translate-x-1/2 h-[540px] w-[900px] rounded-full bg-aurora opacity-40 blur-[80px] animate-pulse-glow" />
      </div>

      <div className="container relative">
        <div className="grid gap-14 lg:grid-cols-[1.05fr_1fr] items-center">
          <div>
            <motion.div
              initial={{ opacity: 0, y: 12 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.7, ease: [0.22, 1, 0.36, 1] }}
              className="inline-flex items-center gap-2 rounded-full glass px-3 py-1.5 text-xs"
            >
              <DotPulse />
              <span className="text-white/70">Now with AI Dispatch, Smart Routing & ETA Prediction</span>
              <Sparkles className="h-3 w-3 text-primary-300" />
            </motion.div>

            <motion.h1
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.9, delay: 0.05, ease: [0.22, 1, 0.36, 1] }}
              className="mt-6 text-display-2xl font-display text-balance"
            >
              <span className="gradient-text">The AI Logistics</span>
              <br />
              <span className="gradient-brand">Operating System.</span>
            </motion.h1>

            <motion.p
              initial={{ opacity: 0, y: 16 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.9, delay: 0.15 }}
              className="mt-7 max-w-xl text-lg text-white/60 leading-relaxed text-balance"
            >
              One platform for every shipment, every warehouse, every merchant, every courier.
              Orchestrate orders, warehouses, fleets and last-mile — powered by AI, built for
              enterprise scale.
            </motion.p>

            <motion.div
              initial={{ opacity: 0, y: 16 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.9, delay: 0.25 }}
              className="mt-9 flex flex-wrap items-center gap-3"
            >
              <Button size="lg">
                Start free <ArrowRight className="h-4 w-4" />
              </Button>
              <Button size="lg" variant="secondary">
                Book demo
              </Button>
              <Button size="lg" variant="ghost">
                <PlayCircle className="h-4 w-4" /> Watch 2-min tour
              </Button>
            </motion.div>

            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              transition={{ duration: 1, delay: 0.4 }}
              className="mt-10 flex items-center gap-6 text-xs text-white/50"
            >
              <div className="flex items-center gap-2">
                <span className="grid place-items-center h-5 w-5 rounded-full bg-emerald-400/15 border border-emerald-400/25">
                  <span className="h-1.5 w-1.5 rounded-full bg-emerald-400" />
                </span>
                No credit card required
              </div>
              <div className="hidden md:flex items-center gap-2">
                <span className="grid place-items-center h-5 w-5 rounded-full bg-primary-400/15 border border-primary-400/25">
                  <span className="h-1.5 w-1.5 rounded-full bg-primary-400" />
                </span>
                SOC 2 · ISO 27001 · GDPR
              </div>
            </motion.div>
          </div>

          <motion.div
            initial={{ opacity: 0, y: 24, scale: 0.98 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            transition={{ duration: 1, delay: 0.2, ease: [0.22, 1, 0.36, 1] }}
            className="relative"
          >
            <HeroDashboard />
          </motion.div>
        </div>
      </div>
    </section>
  );
}
