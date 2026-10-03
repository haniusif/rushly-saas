'use client';
import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Check, ArrowRight, Sparkles } from 'lucide-react';
import { SectionHeader } from '@/components/ui/eyebrow';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/cn';

const plans = [
  {
    name: 'Starter',
    tag: 'For growing merchants',
    priceMonthly: 149,
    priceYearly: 119,
    unit: '/ month',
    features: ['Rushly OMS + Merchant Portal', 'Up to 5,000 orders / month', '3 e-commerce integrations', 'Standard support', 'Community Slack'],
    cta: 'Start free',
  },
  {
    name: 'Scale',
    tag: 'For 3PL & fulfillment',
    priceMonthly: 749,
    priceYearly: 599,
    unit: '/ month',
    features: ['Everything in Starter', 'Rushly WMS + Fleet + Fulfillment', 'Up to 100,000 orders / month', 'AI Dispatch + Smart Routing', 'Priority support · 4h SLA'],
    highlighted: true,
    cta: 'Start free',
  },
  {
    name: 'Enterprise',
    tag: 'For national logistics',
    priceMonthly: null,
    priceYearly: null,
    unit: '',
    features: ['Everything in Scale', 'Unlimited orders + regions', 'SSO / SAML, VPC peering, on-prem', 'Dedicated CSM + solution architects', '99.99% SLA · 1h response'],
    cta: 'Talk to sales',
  },
];

export function Pricing() {
  const [yearly, setYearly] = useState(true);
  return (
    <section id="pricing" className="relative py-28">
      <div className="container">
        <SectionHeader
          eyebrow="Pricing"
          title={<>Simple, predictable pricing that scales with volume.</>}
          description="Start free. Grow at your pace. One contract for every product. No per-seat surprises."
        />

        <div className="mt-8 flex justify-center">
          <div className="glass rounded-full p-1 inline-flex items-center text-sm">
            <button
              onClick={() => setYearly(false)}
              className={cn('px-4 py-1.5 rounded-full', !yearly ? 'bg-white text-bg' : 'text-white/70')}
            >
              Monthly
            </button>
            <button
              onClick={() => setYearly(true)}
              className={cn('px-4 py-1.5 rounded-full inline-flex items-center gap-2', yearly ? 'bg-white text-bg' : 'text-white/70')}
            >
              Yearly
              <span className={cn('text-[10px] rounded-full px-1.5 py-0.5', yearly ? 'bg-bg text-emerald-400' : 'bg-emerald-400/15 text-emerald-300')}>Save 20%</span>
            </button>
          </div>
        </div>

        <div className="mt-12 grid gap-5 lg:grid-cols-3">
          {plans.map((p) => (
            <div
              key={p.name}
              className={cn(
                'relative rounded-3xl p-8 border overflow-hidden',
                p.highlighted
                  ? 'border-primary-400/40 bg-gradient-to-b from-primary-600/[0.14] via-primary-600/[0.06] to-transparent shadow-glow'
                  : 'border-white/[0.07] bg-gradient-to-b from-white/[0.03] to-transparent',
              )}
            >
              {p.highlighted && (
                <div className="absolute -top-3 left-1/2 -translate-x-1/2">
                  <span className="inline-flex items-center gap-1.5 rounded-full glass-strong px-3 py-1 text-[11px] uppercase tracking-widest">
                    <Sparkles className="h-3 w-3 text-primary-200" /> Most popular
                  </span>
                </div>
              )}
              <div className="text-xs uppercase tracking-[0.22em] text-white/50">{p.tag}</div>
              <h3 className="mt-2 text-2xl font-display">{p.name}</h3>

              <div className="mt-6 h-16 flex items-end">
                <AnimatePresence mode="wait">
                  <motion.div
                    key={`${p.name}-${yearly}`}
                    initial={{ opacity: 0, y: 8 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: -8 }}
                    transition={{ duration: 0.25 }}
                    className="flex items-end gap-2"
                  >
                    {p.priceMonthly === null ? (
                      <span className="text-4xl font-display gradient-brand">Custom</span>
                    ) : (
                      <>
                        <span className="text-5xl font-display tabular-nums">
                          ${yearly ? p.priceYearly : p.priceMonthly}
                        </span>
                        <span className="pb-1.5 text-sm text-white/50">{p.unit}</span>
                      </>
                    )}
                  </motion.div>
                </AnimatePresence>
              </div>

              <Button className="mt-6 w-full" variant={p.highlighted ? 'primary' : 'secondary'} size="lg">
                {p.cta} <ArrowRight className="h-4 w-4" />
              </Button>

              <ul className="mt-8 space-y-3 text-sm">
                {p.features.map((f) => (
                  <li key={f} className="flex items-start gap-2 text-white/70">
                    <span className="mt-0.5 grid place-items-center h-4 w-4 rounded-full bg-primary-500/20 border border-primary-400/30 shrink-0">
                      <Check className="h-2.5 w-2.5 text-primary-200" />
                    </span>
                    {f}
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>

        <div className="mt-8 text-center text-xs text-white/40">
          Prices in USD. Volume tiers, on-prem, and multi-workspace pricing on request.
        </div>
      </div>
    </section>
  );
}
