'use client';
import { useState } from 'react';
import { AnimatePresence, motion } from 'framer-motion';
import { Plus } from 'lucide-react';
import { SectionHeader } from '@/components/ui/eyebrow';
import { cn } from '@/lib/cn';

const faqs = [
  { q: 'How is Rushly different from a courier or a shipping API?', a: 'Rushly is the operating system that sits above your carriers. Rushly runs OMS, WMS, Fleet, Fulfillment and the merchant/customer portals — with carrier and channel integrations baked in.' },
  { q: 'Can we bring our own drivers, warehouses and carriers?', a: 'Yes. Rushly is designed multi-tenant, multi-hub, multi-carrier from day one. Own fleet, contracted 3PLs and public carriers can all be routed side-by-side.' },
  { q: 'How long does implementation take?', a: 'Merchants launch in under 24 hours. Fulfillment centers typically go live in 2–4 weeks. Enterprise rollouts run 6–12 weeks including migration, SSO and SLAs.' },
  { q: 'What about data residency and compliance?', a: 'SOC 2, ISO 27001 and GDPR-ready. Regional deployments in KSA, UAE, EU and North America. On-prem and VPC options are available on Enterprise.' },
  { q: 'Do you support Salla, Zid and Shopify natively?', a: 'Yes — first-party OAuth apps for Salla, Zid, Shopify, WooCommerce, Magento and OpenCart. Custom channels ship via the Rushly API and webhooks.' },
  { q: 'Can we self-host?', a: 'Rushly Cloud covers 98% of deployments. For sovereign clouds or dedicated infrastructure, our Enterprise plan includes single-tenant + on-prem deployments.' },
];

export function FAQ() {
  const [open, setOpen] = useState<number | null>(0);
  return (
    <section className="relative py-28">
      <div className="container">
        <SectionHeader eyebrow="FAQ" title={<>The obvious questions, answered.</>} description="If it isn’t here, ping us. We reply in hours, not days." />
        <div className="mt-14 mx-auto max-w-3xl divide-y divide-white/[0.06] rounded-3xl border border-white/[0.07] glass overflow-hidden">
          {faqs.map((f, i) => (
            <button
              key={i}
              onClick={() => setOpen(open === i ? null : i)}
              className="w-full text-left px-6 py-5 group"
            >
              <div className="flex items-start gap-4">
                <span className="flex-1 text-base md:text-lg text-white font-medium">{f.q}</span>
                <span
                  className={cn(
                    'shrink-0 grid place-items-center h-8 w-8 rounded-full border border-white/10 bg-white/[0.03] transition-transform',
                    open === i && 'rotate-45',
                  )}
                >
                  <Plus className="h-4 w-4" />
                </span>
              </div>
              <AnimatePresence initial={false}>
                {open === i && (
                  <motion.div
                    initial={{ height: 0, opacity: 0 }}
                    animate={{ height: 'auto', opacity: 1 }}
                    exit={{ height: 0, opacity: 0 }}
                    transition={{ duration: 0.3, ease: [0.22, 1, 0.36, 1] }}
                    className="overflow-hidden"
                  >
                    <p className="mt-3 pr-12 text-white/60 leading-relaxed">{f.a}</p>
                  </motion.div>
                )}
              </AnimatePresence>
            </button>
          ))}
        </div>
      </div>
    </section>
  );
}
