'use client';
import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Boxes, Truck, Users, Warehouse, PackageCheck, LineChart } from 'lucide-react';
import { SectionHeader } from '@/components/ui/eyebrow';
import { OrdersMock, ShipmentsMock, DriversMock, InventoryMock, FulfillmentMock, AnalyticsMock } from '@/components/mockups/mini-dashboards';
import { cn } from '@/lib/cn';

const tabs = [
  { key: 'orders', label: 'Orders', icon: Boxes, mock: <OrdersMock />, headline: 'Every order, every channel, in one place', desc: 'Sync orders from Salla, Zid, Shopify, WooCommerce and your own storefront. Assign, split, hold, cancel — with a full audit trail.' },
  { key: 'shipments', label: 'Shipments', icon: Truck, mock: <ShipmentsMock />, headline: 'Every shipment, every leg, live', desc: 'A single timeline for pickups, sortation, line-haul and last-mile. Auto-detected exceptions, live ETAs, one-click hand-off between hubs.' },
  { key: 'drivers', label: 'Drivers', icon: Users, mock: <DriversMock />, headline: 'Fleet at a glance, driver by driver', desc: 'Track shifts, cash-on-delivery balances, performance and route load. AI dispatcher fills the gaps you don’t see.' },
  { key: 'inventory', label: 'Inventory', icon: Warehouse, mock: <InventoryMock />, headline: 'Bin-level control across every warehouse', desc: 'Cycle counts, batch and expiry, transfers between hubs, min/max reorder — with no spreadsheet in sight.' },
  { key: 'fulfillment', label: 'Fulfillment', icon: PackageCheck, mock: <FulfillmentMock />, headline: 'Pick, pack, ship — measured to the second', desc: 'Wave picking, cart picking, single-piece pack stations. Every scan feeds the SLA clock so nothing slips.' },
  { key: 'analytics', label: 'Analytics', icon: LineChart, mock: <AnalyticsMock />, headline: 'The metric that matters, whichever one that is', desc: 'On-time rate, cost per order, driver utilization, first-attempt success — sliced by hub, merchant, city or day.' },
];

export function PlatformOverview() {
  const [active, setActive] = useState(tabs[0].key);
  const current = tabs.find((t) => t.key === active)!;
  return (
    <section id="platform" className="relative py-28">
      <div className="container">
        <SectionHeader
          eyebrow="Platform"
          title={<>One system of record for logistics.</>}
          description="Rushly replaces the spreadsheet, the WhatsApp group and the six admin panels you check every morning."
        />

        <div className="mt-14 grid gap-8 lg:grid-cols-[1fr_1.15fr] items-center">
          <div className="order-2 lg:order-1">
            <div className="flex flex-wrap gap-2">
              {tabs.map((t) => (
                <button
                  key={t.key}
                  onClick={() => setActive(t.key)}
                  className={cn(
                    'inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm transition-all',
                    active === t.key
                      ? 'glass-strong border-white/15 text-white shadow-glow'
                      : 'glass border-white/[0.06] text-white/60 hover:text-white',
                  )}
                >
                  <t.icon className="h-4 w-4" />
                  {t.label}
                </button>
              ))}
            </div>

            <AnimatePresence mode="wait">
              <motion.div
                key={current.key}
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -6 }}
                transition={{ duration: 0.35 }}
                className="mt-8"
              >
                <h3 className="text-display-md font-display gradient-text text-balance">{current.headline}</h3>
                <p className="mt-4 text-white/60 leading-relaxed max-w-lg">{current.desc}</p>
                <ul className="mt-6 grid gap-2 text-sm text-white/70">
                  {['Real-time sync across channels', 'Audit-grade history', 'Role-based access control', 'Webhooks for every state change'].map(
                    (l) => (
                      <li key={l} className="flex items-center gap-2">
                        <span className="h-1.5 w-1.5 rounded-full bg-primary-400" />
                        {l}
                      </li>
                    ),
                  )}
                </ul>
              </motion.div>
            </AnimatePresence>
          </div>

          <div className="order-1 lg:order-2 relative">
            <div className="absolute -inset-20 bg-aurora opacity-30 blur-3xl pointer-events-none animate-pulse-glow" aria-hidden />
            <AnimatePresence mode="wait">
              <motion.div
                key={current.key}
                initial={{ opacity: 0, y: 20, scale: 0.98 }}
                animate={{ opacity: 1, y: 0, scale: 1 }}
                exit={{ opacity: 0, y: -14, scale: 0.98 }}
                transition={{ duration: 0.45, ease: [0.22, 1, 0.36, 1] }}
                className="relative"
              >
                <div className="glass-strong rounded-3xl p-4">{current.mock}</div>
              </motion.div>
            </AnimatePresence>
          </div>
        </div>
      </div>
    </section>
  );
}
