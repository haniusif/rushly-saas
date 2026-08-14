'use client';
import { useEffect, useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { ArrowRight, ChevronDown, Sparkles, Boxes, Truck, Warehouse, Cpu, Building2, Users, Code2, BookOpen, LineChart, ShoppingCart, MapPin, PackageCheck, LayoutGrid, Menu, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/cn';

const menus: Record<string, { title: string; items: { label: string; desc: string; icon: React.ComponentType<{ className?: string }> }[] }[]> = {
  Solutions: [
    {
      title: 'By role',
      items: [
        { label: 'For Merchants', desc: 'Sell everywhere, ship from one panel', icon: ShoppingCart },
        { label: 'For Fulfillment', desc: 'Run 3PL operations at scale', icon: PackageCheck },
        { label: 'For Delivery Companies', desc: 'Own the last mile', icon: Truck },
        { label: 'For Warehouses', desc: 'Bin-level inventory control', icon: Warehouse },
      ],
    },
    {
      title: 'By size',
      items: [
        { label: 'Enterprise', desc: 'Multi-region, SSO, SLA', icon: Building2 },
        { label: 'Growth SMEs', desc: 'Scale ops without hiring', icon: Users },
        { label: 'Marketplaces', desc: 'Onboard merchants in minutes', icon: LayoutGrid },
        { label: 'Aggregators', desc: 'Route through any carrier', icon: MapPin },
      ],
    },
  ],
  Products: [
    {
      title: 'Platform',
      items: [
        { label: 'Rushly OMS', desc: 'Order orchestration', icon: Boxes },
        { label: 'Rushly WMS', desc: 'Warehouse management', icon: Warehouse },
        { label: 'Rushly Fleet', desc: 'Driver + vehicle ops', icon: Truck },
        { label: 'Rushly Fulfillment', desc: 'Pick, pack, ship', icon: PackageCheck },
      ],
    },
    {
      title: 'Experience',
      items: [
        { label: 'Merchant Portal', desc: 'Self-serve for sellers', icon: ShoppingCart },
        { label: 'Customer Portal', desc: 'Branded tracking', icon: MapPin },
        { label: 'Analytics', desc: 'Every metric that matters', icon: LineChart },
        { label: 'Rushly API', desc: 'Build on the platform', icon: Code2 },
      ],
    },
  ],
  Industries: [
    {
      title: 'Verticals',
      items: [
        { label: 'E-commerce', desc: 'Salla, Zid, Shopify native', icon: ShoppingCart },
        { label: 'Retail', desc: 'Omnichannel fulfillment', icon: Building2 },
        { label: 'Grocery', desc: 'Same-day, chilled routing', icon: PackageCheck },
        { label: 'Pharma', desc: 'Cold-chain, expiry tracking', icon: Warehouse },
      ],
    },
  ],
  Resources: [
    {
      title: 'Learn',
      items: [
        { label: 'Docs', desc: 'REST, webhooks, SDKs', icon: BookOpen },
        { label: 'API Reference', desc: 'Every endpoint, versioned', icon: Code2 },
        { label: 'Guides', desc: 'Playbooks from real ops teams', icon: BookOpen },
        { label: 'Changelog', desc: 'Weekly ship notes', icon: Sparkles },
      ],
    },
  ],
  Developers: [
    {
      title: 'Build on Rushly',
      items: [
        { label: 'Quickstart', desc: 'First webhook in 5 minutes', icon: Sparkles },
        { label: 'SDKs', desc: 'Node, PHP, Python, Go', icon: Code2 },
        { label: 'Webhooks', desc: 'Signed, retried, idempotent', icon: Cpu },
        { label: 'Sandbox', desc: 'Test without real shipments', icon: Boxes },
      ],
    },
  ],
  Company: [
    {
      title: 'About',
      items: [
        { label: 'Our story', desc: 'Why Rushly exists', icon: Building2 },
        { label: 'Customers', desc: 'The teams shipping on Rushly', icon: Users },
        { label: 'Careers', desc: 'Join the platform team', icon: Sparkles },
        { label: 'Contact', desc: 'Talk to sales or support', icon: MapPin },
      ],
    },
  ],
};

const items = ['Solutions', 'Products', 'Industries', 'Resources', 'Pricing', 'Developers', 'Company'] as const;

export function Nav() {
  const [scrolled, setScrolled] = useState(false);
  const [open, setOpen] = useState<string | null>(null);
  const [mobile, setMobile] = useState(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 12);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  return (
    <>
      <div
        className={cn(
          'fixed inset-x-0 top-0 z-50 transition-all duration-500',
          scrolled ? 'py-2' : 'py-4',
        )}
        onMouseLeave={() => setOpen(null)}
      >
        <div className="container">
          <div
            className={cn(
              'flex items-center justify-between gap-6 rounded-full px-3 py-2 pl-5 transition-all duration-500',
              scrolled ? 'glass-strong shadow-ring' : 'bg-transparent',
            )}
          >
            <a href="#" className="flex items-center gap-2 shrink-0">
              <Logo />
              <span className="font-display text-lg tracking-tight">Rushly</span>
              <span className="ml-1 hidden md:inline text-[10px] uppercase tracking-widest text-white/40 border border-white/10 rounded-full px-1.5 py-0.5">
                OS
              </span>
            </a>

            <nav className="hidden lg:flex items-center gap-1">
              {items.map((label) =>
                label === 'Pricing' ? (
                  <a
                    key={label}
                    href="#pricing"
                    className="px-3 py-2 text-sm text-white/70 hover:text-white transition-colors"
                  >
                    {label}
                  </a>
                ) : (
                  <button
                    key={label}
                    onMouseEnter={() => setOpen(label)}
                    onFocus={() => setOpen(label)}
                    className={cn(
                      'inline-flex items-center gap-1 rounded-full px-3 py-2 text-sm transition-colors',
                      open === label ? 'text-white' : 'text-white/70 hover:text-white',
                    )}
                  >
                    {label}
                    <ChevronDown className="h-3.5 w-3.5 opacity-60" />
                  </button>
                ),
              )}
            </nav>

            <div className="flex items-center gap-2">
              <Button variant="ghost" size="sm" className="hidden md:inline-flex">
                Sign in
              </Button>
              <Button variant="secondary" size="sm" className="hidden md:inline-flex">
                Book demo
              </Button>
              <Button size="sm" className="hidden md:inline-flex">
                Start free <ArrowRight className="h-4 w-4" />
              </Button>
              <button
                onClick={() => setMobile((v) => !v)}
                className="lg:hidden inline-flex items-center justify-center h-10 w-10 rounded-full glass"
                aria-label="Menu"
              >
                {mobile ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
              </button>
            </div>
          </div>

          <AnimatePresence>
            {open && menus[open] && (
              <motion.div
                key={open}
                initial={{ opacity: 0, y: -8 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -8 }}
                transition={{ duration: 0.2, ease: 'easeOut' }}
                className="absolute left-1/2 -translate-x-1/2 mt-3 w-[min(96vw,960px)]"
                onMouseEnter={() => setOpen(open)}
              >
                <div className="glass-strong rounded-3xl p-6 shadow-ring">
                  <div className="grid gap-6 md:grid-cols-2">
                    {menus[open].map((col) => (
                      <div key={col.title}>
                        <div className="text-[11px] uppercase tracking-[0.22em] text-white/40 mb-3 px-3">
                          {col.title}
                        </div>
                        <div className="grid gap-1">
                          {col.items.map((it) => (
                            <a
                              key={it.label}
                              href="#"
                              className="group flex items-start gap-3 rounded-2xl p-3 hover:bg-white/[0.04] transition-colors"
                            >
                              <span className="grid place-items-center h-9 w-9 rounded-xl bg-gradient-to-br from-primary-600/30 to-accent-600/30 border border-white/10">
                                <it.icon className="h-4 w-4 text-primary-100" />
                              </span>
                              <span className="min-w-0">
                                <span className="block text-sm font-medium text-white">
                                  {it.label}
                                </span>
                                <span className="block text-xs text-white/50 mt-0.5">{it.desc}</span>
                              </span>
                            </a>
                          ))}
                        </div>
                      </div>
                    ))}
                  </div>
                  <div className="mt-5 flex items-center justify-between border-t border-white/[0.06] pt-4 px-3">
                    <div className="text-xs text-white/50 inline-flex items-center gap-2">
                      <Sparkles className="h-3.5 w-3.5 text-primary-300" />
                      New — AI Dispatch is live for all workspaces
                    </div>
                    <a href="#" className="text-xs text-primary-300 hover:text-primary-200 inline-flex items-center gap-1">
                      Read the announcement <ArrowRight className="h-3 w-3" />
                    </a>
                  </div>
                </div>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      </div>

      <AnimatePresence>
        {mobile && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 z-40 lg:hidden bg-bg/95 backdrop-blur-xl pt-24 px-6"
          >
            <div className="grid gap-1">
              {items.map((label) => (
                <a
                  key={label}
                  href="#"
                  onClick={() => setMobile(false)}
                  className="flex items-center justify-between rounded-2xl border border-white/[0.06] px-4 py-4 text-lg"
                >
                  {label}
                  <ChevronDown className="h-4 w-4 -rotate-90 opacity-60" />
                </a>
              ))}
            </div>
            <div className="mt-6 flex gap-3">
              <Button variant="secondary" className="flex-1">
                Book demo
              </Button>
              <Button className="flex-1">
                Start free <ArrowRight className="h-4 w-4" />
              </Button>
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </>
  );
}

function Logo() {
  return (
    <svg viewBox="0 0 32 32" className="h-7 w-7" aria-hidden>
      <defs>
        <linearGradient id="lg" x1="0" x2="1" y1="0" y2="1">
          <stop offset="0%" stopColor="#60a5fa" />
          <stop offset="55%" stopColor="#22d3ee" />
          <stop offset="100%" stopColor="#a78bfa" />
        </linearGradient>
      </defs>
      <rect x="1.5" y="1.5" width="29" height="29" rx="9" fill="url(#lg)" opacity="0.15" />
      <rect x="1.5" y="1.5" width="29" height="29" rx="9" stroke="url(#lg)" strokeWidth="1.5" fill="none" />
      <path d="M9 22 L16 8 L23 22 M12 17 H20" stroke="url(#lg)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" fill="none" />
    </svg>
  );
}
