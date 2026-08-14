import { SectionHeader } from '@/components/ui/eyebrow';
import { ArrowRight, Cable, Webhook, Boxes } from 'lucide-react';

const integrations = [
  { name: 'Salla', tag: 'E-commerce', color: 'from-pink-500/40 to-fuchsia-500/40' },
  { name: 'Zid', tag: 'E-commerce', color: 'from-blue-500/40 to-cyan-500/40' },
  { name: 'Shopify', tag: 'E-commerce', color: 'from-emerald-500/40 to-lime-500/40' },
  { name: 'WooCommerce', tag: 'E-commerce', color: 'from-violet-500/40 to-purple-500/40' },
  { name: 'Magento', tag: 'E-commerce', color: 'from-orange-500/40 to-red-500/40' },
  { name: 'OpenCart', tag: 'E-commerce', color: 'from-sky-500/40 to-blue-500/40' },
  { name: 'WordPress', tag: 'CMS', color: 'from-slate-400/40 to-slate-600/40' },
  { name: 'Odoo', tag: 'ERP', color: 'from-purple-500/40 to-indigo-500/40' },
  { name: 'SAP', tag: 'ERP', color: 'from-cyan-500/40 to-teal-500/40' },
  { name: 'Oracle', tag: 'ERP', color: 'from-red-500/40 to-rose-500/40' },
  { name: 'Dynamics', tag: 'ERP', color: 'from-blue-600/40 to-sky-500/40' },
  { name: 'Rushly API', tag: 'Platform', color: 'from-primary-500/50 to-accent-500/50' },
];

export function Integrations() {
  return (
    <section id="integrations" className="relative py-28">
      <div className="container">
        <SectionHeader
          eyebrow="Integrations"
          title={<>Wired into the stack you already run.</>}
          description="Sync orders, inventory, invoices and statuses with a click. Or build anything you need with REST, webhooks and SDKs."
        />

        <div className="mt-14 grid gap-6 lg:grid-cols-[1.35fr_1fr]">
          <div className="grid grid-cols-3 sm:grid-cols-4 gap-3">
            {integrations.map((it) => (
              <div
                key={it.name}
                className="group relative rounded-2xl border border-white/[0.06] bg-white/[0.02] p-5 flex flex-col items-center justify-center text-center overflow-hidden transition-all hover:border-white/15 hover:-translate-y-1"
              >
                <div className={`absolute -top-10 -right-10 h-28 w-28 rounded-full bg-gradient-to-br ${it.color} blur-2xl opacity-60 group-hover:opacity-100 transition-opacity`} aria-hidden />
                <div className="relative">
                  <div
                    className={`grid place-items-center h-12 w-12 rounded-xl bg-gradient-to-br ${it.color} border border-white/10 shadow-ring mb-3`}
                  >
                    <span className="font-display text-lg text-white">{it.name[0]}</span>
                  </div>
                  <div className="text-sm text-white/80 font-medium">{it.name}</div>
                  <div className="text-[10px] uppercase tracking-widest text-white/40 mt-0.5">{it.tag}</div>
                </div>
              </div>
            ))}
          </div>

          <div className="relative rounded-2xl border border-white/[0.06] bg-gradient-to-b from-white/[0.03] to-transparent p-6 overflow-hidden">
            <div className="absolute -top-20 -right-20 h-56 w-56 rounded-full bg-primary-500/30 blur-3xl" aria-hidden />
            <div className="relative">
              <span className="inline-flex items-center gap-2 rounded-full glass px-3 py-1 text-xs text-white/70">
                <Webhook className="h-3.5 w-3.5 text-primary-300" /> Developer Platform
              </span>
              <h3 className="mt-4 text-2xl font-display gradient-text">Build on Rushly.</h3>
              <p className="mt-2 text-sm text-white/60 leading-relaxed">
                A public REST API, signed & retried webhooks, first-party SDKs, sandboxed test carriers and a full changelog.
              </p>
              <div className="mt-5 rounded-xl border border-white/[0.08] bg-[#050915] overflow-hidden">
                <div className="flex items-center gap-2 px-3 py-1.5 border-b border-white/[0.05] text-[10px] text-white/40">
                  <Cable className="h-3 w-3" /> POST /v1/orders
                </div>
                <pre className="p-4 text-[11px] leading-relaxed font-mono text-white/80 overflow-x-auto no-scrollbar">
{`curl https://api.rushly.tech/v1/orders \\
  -H "Authorization: Bearer $RUSHLY_KEY" \\
  -d '{
    "merchant_id": "m_9f2",
    "reference":   "ORD-42198",
    "channel":     "salla",
    "destination": { "city": "Jeddah", "postal": "23443" },
    "items":       [{ "sku": "AER-01", "qty": 2 }],
    "cod_amount":  349.00
  }'`}
                </pre>
              </div>
              <div className="mt-5 flex flex-wrap items-center gap-2 text-xs">
                <span className="glass rounded-full px-2.5 py-1 inline-flex items-center gap-1.5"><Boxes className="h-3 w-3 text-primary-300" /> Node SDK</span>
                <span className="glass rounded-full px-2.5 py-1 inline-flex items-center gap-1.5"><Boxes className="h-3 w-3 text-primary-300" /> PHP</span>
                <span className="glass rounded-full px-2.5 py-1 inline-flex items-center gap-1.5"><Boxes className="h-3 w-3 text-primary-300" /> Python</span>
                <span className="glass rounded-full px-2.5 py-1 inline-flex items-center gap-1.5"><Boxes className="h-3 w-3 text-primary-300" /> Go</span>
              </div>
              <a href="#" className="mt-6 inline-flex items-center gap-1 text-sm text-primary-200 hover:text-primary-100">
                Read the docs <ArrowRight className="h-3.5 w-3.5" />
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
