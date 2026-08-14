import { ShoppingCart, PackageCheck, Truck, Warehouse, Building2, Users, ArrowRight } from 'lucide-react';
import { SectionHeader } from '@/components/ui/eyebrow';
import { Reveal } from '@/components/motion/reveal';

const solutions = [
  { icon: ShoppingCart, name: 'For Merchants', desc: 'Sell everywhere, ship from one panel. Wallets, invoicing, COD reconciliation, returns.', kpi: '+38% shipped orders / month' },
  { icon: PackageCheck, name: 'For Fulfillment Centers', desc: 'Onboard merchants in a day. Wave picking, SLA clocks, merchant-facing views.', kpi: '3× orders per FTE' },
  { icon: Truck, name: 'For Delivery Companies', desc: 'Fleet, hubs, drivers, cash. Smart routing, ETA, proof-of-delivery.', kpi: '−24% km driven' },
  { icon: Warehouse, name: 'For Warehouses', desc: 'Bin-level control, batch/expiry, transfers, cycle counts, min/max reorder.', kpi: '99.6% inventory accuracy' },
  { icon: Building2, name: 'For Enterprises', desc: 'Multi-region, SSO, roles, audit, SLA, dedicated support and infrastructure.', kpi: '99.99% availability' },
  { icon: Users, name: 'For SMEs', desc: 'Start free, scale as you grow. No implementation team required.', kpi: 'Live in under 24h' },
];

export function Solutions() {
  return (
    <section id="solutions" className="relative py-28">
      <div className="container">
        <SectionHeader
          eyebrow="Solutions"
          title={<>Built for every role in the supply chain.</>}
          description="From a merchant shipping their first 100 orders to a 3PL processing 100,000 a day — Rushly scales without a re-implementation."
        />
        <div className="mt-14 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {solutions.map((s, i) => (
            <Reveal key={s.name} delay={i * 0.05}>
              <a
                href="#"
                className="group relative block rounded-2xl border border-white/[0.06] bg-gradient-to-b from-white/[0.04] to-white/[0.01] p-6 h-full overflow-hidden transition-all hover:border-white/15 hover:-translate-y-1"
              >
                <div className="absolute -top-10 -right-10 h-40 w-40 rounded-full bg-primary-500/20 blur-3xl opacity-0 group-hover:opacity-100 transition-opacity" aria-hidden />
                <div className="relative">
                  <div className="flex items-center justify-between">
                    <span className="grid place-items-center h-11 w-11 rounded-xl bg-gradient-to-br from-primary-500/20 to-accent-500/20 border border-white/10">
                      <s.icon className="h-5 w-5 text-primary-100" />
                    </span>
                    <span className="text-[10px] uppercase tracking-[0.22em] text-emerald-300">
                      {s.kpi}
                    </span>
                  </div>
                  <h3 className="mt-5 text-lg font-display">{s.name}</h3>
                  <p className="mt-2 text-sm text-white/60 leading-relaxed">{s.desc}</p>
                  <div className="mt-6 inline-flex items-center gap-1 text-xs text-primary-200 opacity-70 group-hover:opacity-100 transition-opacity">
                    Explore the playbook <ArrowRight className="h-3 w-3" />
                  </div>
                </div>
              </a>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}
