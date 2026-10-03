import { Boxes, Warehouse, Truck, PackageCheck, MapPin, ShoppingCart, User, Code2, ArrowRight } from 'lucide-react';
import { SectionHeader } from '@/components/ui/eyebrow';
import { Reveal } from '@/components/motion/reveal';

const products = [
  { name: 'Rushly OMS', tag: 'Order Management', icon: Boxes, desc: 'Unify orders from every channel with a workflow engine built for logistics operators.', accent: 'from-primary-500/25 to-primary-500/0' },
  { name: 'Rushly WMS', tag: 'Warehouse', icon: Warehouse, desc: 'Bin-level stock, cycle counts, batches, expiry and transfers across every hub.', accent: 'from-secondary-500/25 to-secondary-500/0' },
  { name: 'Rushly Fleet', tag: 'Fleet & Driver', icon: Truck, desc: 'Shift, driver, vehicle and COD control. Live positions and performance in one panel.', accent: 'from-accent-500/25 to-accent-500/0' },
  { name: 'Rushly Fulfillment', tag: '3PL Ops', icon: PackageCheck, desc: 'Multi-merchant fulfillment with SLA clocks, wave picking and merchant-facing views.', accent: 'from-emerald-500/25 to-emerald-500/0' },
  { name: 'Rushly Delivery', tag: 'Last Mile', icon: MapPin, desc: 'Smart routing, ETA prediction and proof-of-delivery, engineered for dense city runs.', accent: 'from-rose-500/25 to-rose-500/0' },
  { name: 'Rushly Merchant', tag: 'Merchant Portal', icon: ShoppingCart, desc: 'A branded self-serve portal — pickups, invoices, returns, statements, wallet.', accent: 'from-amber-500/25 to-amber-500/0' },
  { name: 'Rushly Customer', tag: 'Customer Experience', icon: User, desc: 'Branded tracking, notifications, ratings and NDR self-service for end customers.', accent: 'from-cyan-500/25 to-cyan-500/0' },
  { name: 'Rushly API', tag: 'Developer Platform', icon: Code2, desc: 'REST + webhooks + SDKs. Build merchant apps, integrations and internal tools.', accent: 'from-indigo-500/25 to-indigo-500/0' },
];

export function Products() {
  return (
    <section id="products" className="relative py-28">
      <div className="container">
        <SectionHeader
          eyebrow="Products"
          title={<>Eight products. One platform. One contract.</>}
          description="Adopt what you need today, turn on the rest when you’re ready. No new logins, no re-implementation."
        />
        <div className="mt-14 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          {products.map((p, i) => (
            <Reveal key={p.name} delay={i * 0.05}>
              <a
                href="#"
                className="group relative block h-full rounded-2xl border border-white/[0.06] bg-gradient-to-b from-white/[0.03] to-white/[0.01] p-6 overflow-hidden transition-all duration-300 hover:border-white/15 hover:-translate-y-1"
              >
                <div
                  className={`absolute -top-16 -right-16 h-48 w-48 rounded-full bg-gradient-to-br ${p.accent} blur-2xl opacity-70 group-hover:opacity-100 transition-opacity`}
                  aria-hidden
                />
                <div className="relative">
                  <div className="flex items-center justify-between">
                    <span className="grid place-items-center h-10 w-10 rounded-xl bg-gradient-to-br from-white/[0.08] to-white/[0.02] border border-white/10">
                      <p.icon className="h-5 w-5 text-primary-200" />
                    </span>
                    <span className="text-[10px] uppercase tracking-[0.22em] text-white/40">{p.tag}</span>
                  </div>
                  <h3 className="mt-5 text-lg font-display">{p.name}</h3>
                  <p className="mt-2 text-sm text-white/60 leading-relaxed">{p.desc}</p>
                  <div className="mt-5 inline-flex items-center gap-1 text-xs text-primary-200 opacity-0 group-hover:opacity-100 transition-opacity">
                    Learn more <ArrowRight className="h-3 w-3" />
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
