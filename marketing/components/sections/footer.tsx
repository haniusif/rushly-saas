import { ArrowRight, Github, Linkedin, Twitter } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { DotPulse } from '@/components/ui/badge';

const cols = [
  {
    title: 'Platform',
    links: ['Rushly OMS', 'Rushly WMS', 'Rushly Fleet', 'Rushly Fulfillment', 'Rushly Delivery', 'Rushly Merchant', 'Rushly Customer', 'Rushly API'],
  },
  {
    title: 'Solutions',
    links: ['Merchants', 'Fulfillment Centers', 'Delivery Companies', 'Warehouses', 'Enterprises', 'SMEs', 'Marketplaces'],
  },
  {
    title: 'Integrations',
    links: ['Salla', 'Zid', 'Shopify', 'WooCommerce', 'Magento', 'OpenCart', 'Odoo', 'SAP'],
  },
  {
    title: 'Developers',
    links: ['Documentation', 'API Reference', 'Webhooks', 'SDKs', 'Status', 'Changelog', 'Sandbox'],
  },
  {
    title: 'Company',
    links: ['About', 'Customers', 'Careers', 'Press', 'Contact', 'Trust & security', 'Privacy', 'Terms'],
  },
];

export function Footer() {
  return (
    <footer className="relative overflow-hidden border-t border-white/[0.06] mt-32">
      <div className="absolute inset-x-0 -top-40 h-80 bg-grid-fade opacity-70 pointer-events-none" />
      <div className="container relative py-20">
        <div className="grid gap-14 md:grid-cols-2 lg:grid-cols-[1.2fr_2fr]">
          <div>
            <div className="flex items-center gap-2 mb-6">
              <span className="grid place-items-center h-9 w-9 rounded-xl bg-gradient-to-br from-primary-500 via-secondary-500 to-accent-500 shadow-glow" />
              <span className="font-display text-xl">Rushly</span>
            </div>
            <p className="text-white/60 max-w-sm leading-relaxed">
              The AI Logistics Operating System. One platform. Every shipment, every warehouse, every merchant, every courier.
            </p>
            <form className="mt-8 max-w-sm">
              <label className="text-xs uppercase tracking-[0.22em] text-white/40">Ship notes, weekly</label>
              <div className="mt-3 flex items-center gap-2 rounded-full glass p-1 pl-4">
                <input
                  type="email"
                  placeholder="you@company.com"
                  className="flex-1 bg-transparent text-sm placeholder:text-white/40 outline-none"
                />
                <Button size="sm" type="submit">
                  Subscribe <ArrowRight className="h-4 w-4" />
                </Button>
              </div>
            </form>
            <div className="mt-8 inline-flex items-center gap-2 rounded-full glass px-3 py-1.5 text-xs">
              <DotPulse />
              All systems operational
            </div>
          </div>
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-8">
            {cols.map((c) => (
              <div key={c.title}>
                <div className="text-xs uppercase tracking-[0.22em] text-white/40 mb-4">{c.title}</div>
                <ul className="space-y-2.5">
                  {c.links.map((l) => (
                    <li key={l}>
                      <a href="#" className="text-sm text-white/70 hover:text-white transition-colors">
                        {l}
                      </a>
                    </li>
                  ))}
                </ul>
              </div>
            ))}
          </div>
        </div>

        <div className="mt-16 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 border-t border-white/[0.06] pt-8">
          <div className="text-xs text-white/40">
            © {new Date().getFullYear()} Rushly Technologies. All rights reserved. Built for teams that ship.
          </div>
          <div className="flex items-center gap-2">
            <a href="#" className="grid place-items-center h-9 w-9 rounded-full glass hover:bg-white/[0.06]">
              <Twitter className="h-4 w-4" />
            </a>
            <a href="#" className="grid place-items-center h-9 w-9 rounded-full glass hover:bg-white/[0.06]">
              <Linkedin className="h-4 w-4" />
            </a>
            <a href="#" className="grid place-items-center h-9 w-9 rounded-full glass hover:bg-white/[0.06]">
              <Github className="h-4 w-4" />
            </a>
          </div>
        </div>

        <div className="mt-16 relative select-none">
          <div className="text-[clamp(4rem,18vw,17rem)] leading-[0.85] font-display font-semibold tracking-[-0.05em] gradient-brand opacity-[0.16] whitespace-nowrap">
            RUSHLY
          </div>
        </div>
      </div>
    </footer>
  );
}
