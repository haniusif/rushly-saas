'use client';
import { motion } from 'framer-motion';
import { ArrowUpRight, Box, MapPin, Truck, Package, Warehouse, LineChart, Route, Activity, CheckCircle2 } from 'lucide-react';

const Frame = ({ children, title }: { children: React.ReactNode; title: string }) => (
  <div className="rounded-2xl border border-white/[0.06] bg-gradient-to-b from-white/[0.03] to-transparent overflow-hidden shadow-ring">
    <div className="flex items-center justify-between border-b border-white/[0.05] px-4 py-2.5">
      <div className="flex items-center gap-2 text-xs text-white/70">
        <span className="h-1.5 w-1.5 rounded-full bg-primary-400" />
        {title}
      </div>
      <span className="text-[10px] uppercase tracking-widest text-white/30">Live</span>
    </div>
    <div className="p-4">{children}</div>
  </div>
);

export function OrdersMock() {
  const rows = [
    { id: '#84210', mer: 'Nassab', status: 'Paid', tone: 'success' },
    { id: '#84209', mer: 'Ateer', status: 'Picking', tone: 'warn' },
    { id: '#84208', mer: 'Souq&', status: 'Shipped', tone: 'brand' },
    { id: '#84207', mer: 'Baytha', status: 'Paid', tone: 'success' },
  ];
  return (
    <Frame title="Orders">
      <div className="flex items-end justify-between mb-3">
        <div>
          <div className="text-[10px] uppercase tracking-widest text-white/40">Today</div>
          <div className="text-2xl font-display tabular-nums">4,218</div>
        </div>
        <div className="text-[10px] rounded-full px-1.5 py-0.5 border border-emerald-400/25 bg-emerald-400/10 text-emerald-300 inline-flex items-center gap-0.5">
          <ArrowUpRight className="h-2.5 w-2.5" /> +12.4%
        </div>
      </div>
      <div className="space-y-1.5">
        {rows.map((r) => (
          <div key={r.id} className="flex items-center justify-between text-xs bg-white/[0.02] rounded-lg px-2.5 py-1.5 border border-white/[0.04]">
            <div className="flex items-center gap-2">
              <span className="h-6 w-6 grid place-items-center rounded-md bg-white/[0.04] border border-white/[0.06]">
                <Box className="h-3 w-3 text-white/60" />
              </span>
              <span className="font-mono text-[11px] text-white/60">{r.id}</span>
              <span className="text-white/80">{r.mer}</span>
            </div>
            <StatusPill tone={r.tone as any}>{r.status}</StatusPill>
          </div>
        ))}
      </div>
    </Frame>
  );
}

export function ShipmentsMock() {
  const chart = [12, 18, 22, 26, 32, 28, 34, 42, 40, 48, 52, 58];
  const max = Math.max(...chart);
  return (
    <Frame title="Shipments">
      <div className="mb-3 flex items-end justify-between">
        <div>
          <div className="text-[10px] uppercase tracking-widest text-white/40">In transit</div>
          <div className="text-2xl font-display tabular-nums">1,847</div>
        </div>
        <div className="text-[10px] text-white/40">last 12h</div>
      </div>
      <div className="flex items-end gap-1.5 h-24">
        {chart.map((v, i) => (
          <motion.div
            key={i}
            initial={{ height: 0 }}
            whileInView={{ height: `${(v / max) * 100}%` }}
            viewport={{ once: true }}
            transition={{ duration: 0.7, delay: i * 0.04, ease: [0.22, 1, 0.36, 1] }}
            className="flex-1 rounded-t-md bg-gradient-to-t from-primary-500/60 via-primary-400/80 to-secondary-400/80 shadow-[0_0_20px_-6px_rgba(96,165,250,0.6)]"
          />
        ))}
      </div>
    </Frame>
  );
}

export function DriversMock() {
  const drivers = [
    { name: 'Fahad A.', route: 'RUH-04', status: 'On route', tone: 'brand' },
    { name: 'Omar S.', route: 'JED-11', status: 'Delivered', tone: 'success' },
    { name: 'Yousef M.', route: 'DMM-02', status: 'Break', tone: 'warn' },
  ];
  return (
    <Frame title="Fleet">
      <div className="flex items-end justify-between mb-3">
        <div>
          <div className="text-[10px] uppercase tracking-widest text-white/40">Active drivers</div>
          <div className="text-2xl font-display tabular-nums">312</div>
        </div>
      </div>
      <div className="space-y-1.5">
        {drivers.map((d) => (
          <div key={d.name} className="flex items-center justify-between bg-white/[0.02] rounded-lg px-2.5 py-1.5 border border-white/[0.04] text-xs">
            <div className="flex items-center gap-2">
              <span className="h-7 w-7 rounded-full bg-gradient-to-br from-primary-500/40 to-accent-500/40 border border-white/10" />
              <div>
                <div className="text-white/85">{d.name}</div>
                <div className="text-[10px] text-white/40 font-mono">{d.route}</div>
              </div>
            </div>
            <StatusPill tone={d.tone as any}>{d.status}</StatusPill>
          </div>
        ))}
      </div>
    </Frame>
  );
}

export function InventoryMock() {
  const items = [
    { sku: 'AER-01', name: 'Aerobie Pro', stock: 82, cap: 100, tone: 'success' },
    { sku: 'DRV-42', name: 'Drive 512G', stock: 24, cap: 100, tone: 'warn' },
    { sku: 'KTL-77', name: 'Kettle Blk', stock: 6, cap: 100, tone: 'danger' },
    { sku: 'CBL-19', name: 'USB-C 2m', stock: 58, cap: 100, tone: 'success' },
  ];
  return (
    <Frame title="Inventory">
      <div className="mb-3">
        <div className="text-[10px] uppercase tracking-widest text-white/40">SKUs across 4 warehouses</div>
        <div className="text-2xl font-display tabular-nums">12,806</div>
      </div>
      <div className="space-y-2">
        {items.map((it) => {
          const barTone =
            it.tone === 'success' ? 'from-emerald-400 to-emerald-300' :
            it.tone === 'warn' ? 'from-amber-400 to-amber-300' :
            'from-rose-400 to-rose-300';
          return (
            <div key={it.sku} className="text-xs">
              <div className="flex items-center justify-between mb-1">
                <span className="text-white/70">{it.name}</span>
                <span className="font-mono text-white/50">{it.stock}%</span>
              </div>
              <div className="h-1.5 rounded-full bg-white/[0.05] overflow-hidden">
                <motion.div
                  initial={{ width: 0 }}
                  whileInView={{ width: `${it.stock}%` }}
                  viewport={{ once: true }}
                  transition={{ duration: 0.9, ease: [0.22, 1, 0.36, 1] }}
                  className={`h-full rounded-full bg-gradient-to-r ${barTone}`}
                />
              </div>
            </div>
          );
        })}
      </div>
    </Frame>
  );
}

export function FulfillmentMock() {
  const stages = [
    { name: 'Received', v: 100, icon: Package },
    { name: 'Picked', v: 84, icon: CheckCircle2 },
    { name: 'Packed', v: 71, icon: Warehouse },
    { name: 'Shipped', v: 58, icon: Truck },
  ];
  return (
    <Frame title="Fulfillment">
      <div className="mb-3">
        <div className="text-[10px] uppercase tracking-widest text-white/40">Pipeline · today</div>
        <div className="text-2xl font-display tabular-nums">12,480</div>
      </div>
      <div className="space-y-2">
        {stages.map((s) => (
          <div key={s.name}>
            <div className="flex items-center justify-between text-xs mb-1">
              <span className="inline-flex items-center gap-1.5 text-white/70">
                <s.icon className="h-3 w-3 text-primary-300" /> {s.name}
              </span>
              <span className="font-mono text-white/50">{Math.round(s.v * 124.8)}</span>
            </div>
            <div className="h-1.5 rounded-full bg-white/[0.05] overflow-hidden">
              <motion.div
                initial={{ width: 0 }}
                whileInView={{ width: `${s.v}%` }}
                viewport={{ once: true }}
                transition={{ duration: 0.9, ease: [0.22, 1, 0.36, 1] }}
                className="h-full rounded-full bg-gradient-to-r from-primary-500 via-secondary-500 to-accent-500"
              />
            </div>
          </div>
        ))}
      </div>
    </Frame>
  );
}

export function AnalyticsMock() {
  const spark = Array.from({ length: 24 }).map((_, i) => 30 + Math.sin(i / 2) * 12 + i * 1.5);
  const w = 260, h = 80;
  const max = Math.max(...spark);
  const step = w / (spark.length - 1);
  const pts = spark.map((v, i) => `${i * step},${h - (v / max) * (h - 6) - 3}`).join(' ');
  return (
    <Frame title="Analytics">
      <div className="grid grid-cols-2 gap-3 mb-3">
        <div>
          <div className="text-[10px] uppercase tracking-widest text-white/40">On-time</div>
          <div className="text-xl font-display tabular-nums">98.6%</div>
        </div>
        <div>
          <div className="text-[10px] uppercase tracking-widest text-white/40">CSAT</div>
          <div className="text-xl font-display tabular-nums">4.87</div>
        </div>
      </div>
      <svg viewBox={`0 0 ${w} ${h}`} className="w-full h-20">
        <defs>
          <linearGradient id="ag" x1="0" x2="0" y1="0" y2="1">
            <stop offset="0%" stopColor="#a78bfa" stopOpacity="0.5" />
            <stop offset="100%" stopColor="#a78bfa" stopOpacity="0" />
          </linearGradient>
          <linearGradient id="al" x1="0" x2="1" y1="0" y2="0">
            <stop offset="0%" stopColor="#22d3ee" />
            <stop offset="100%" stopColor="#a78bfa" />
          </linearGradient>
        </defs>
        <path d={`M0,${h} L${pts.replace(/ /g, ' L')} L${w},${h} Z`} fill="url(#ag)" />
        <polyline points={pts} fill="none" stroke="url(#al)" strokeWidth="1.8" strokeLinecap="round" />
      </svg>
    </Frame>
  );
}

export function RoutesMock() {
  return (
    <Frame title="Smart routing">
      <div className="flex items-end justify-between mb-3">
        <div>
          <div className="text-[10px] uppercase tracking-widest text-white/40">Optimized routes</div>
          <div className="text-2xl font-display tabular-nums">184</div>
        </div>
        <div className="text-[10px] rounded-full px-1.5 py-0.5 border border-emerald-400/25 bg-emerald-400/10 text-emerald-300 inline-flex items-center gap-0.5">
          −24% km
        </div>
      </div>
      <div className="relative h-28 rounded-lg overflow-hidden bg-gradient-to-br from-[#0a1229] to-[#050915]">
        <svg viewBox="0 0 200 100" className="absolute inset-0 h-full w-full">
          <path d="M10,80 Q40,20 70,50 T130,40 T190,20" stroke="url(#rl)" strokeWidth="1.6" fill="none" strokeDasharray="3 3" />
          <path d="M10,80 Q40,60 70,70 T130,60 T190,50" stroke="rgba(255,255,255,0.15)" strokeWidth="1" fill="none" strokeDasharray="2 3" />
          <defs>
            <linearGradient id="rl" x1="0" x2="1" y1="0" y2="0">
              <stop offset="0%" stopColor="#22d3ee" />
              <stop offset="100%" stopColor="#a78bfa" />
            </linearGradient>
          </defs>
          {[[10, 80], [70, 50], [130, 40], [190, 20]].map(([x, y], i) => (
            <g key={i}>
              <circle cx={x} cy={y} r="3" fill="#0b1224" stroke="#22d3ee" strokeWidth="1.5" />
            </g>
          ))}
        </svg>
      </div>
    </Frame>
  );
}

export function TrackingMock() {
  const stages = ['Order', 'Warehouse', 'Sortation', 'Out for delivery', 'Delivered'];
  const active = 3;
  return (
    <Frame title="Customer tracking">
      <div className="mb-3">
        <div className="text-[10px] uppercase tracking-widest text-white/40">Tracking</div>
        <div className="text-sm font-mono">RSH-482911</div>
      </div>
      <ol className="space-y-2.5">
        {stages.map((s, i) => (
          <li key={s} className="flex items-center gap-3 text-xs">
            <span
              className={`grid place-items-center h-6 w-6 rounded-full border ${
                i < active ? 'bg-emerald-400/15 border-emerald-400/40 text-emerald-300' :
                i === active ? 'bg-primary-400/15 border-primary-400/50 text-primary-200' :
                'bg-white/[0.04] border-white/10 text-white/40'
              }`}
            >
              {i < active ? <CheckCircle2 className="h-3 w-3" /> : <span className="h-1.5 w-1.5 rounded-full bg-current" />}
            </span>
            <span className={i <= active ? 'text-white/80' : 'text-white/40'}>{s}</span>
            {i === active ? <span className="ml-auto text-[10px] text-primary-200">Live · 18m</span> : null}
          </li>
        ))}
      </ol>
    </Frame>
  );
}

export function FinanceMock() {
  return (
    <Frame title="Finance">
      <div className="grid grid-cols-2 gap-3 mb-4">
        <div>
          <div className="text-[10px] uppercase tracking-widest text-white/40">COD collected</div>
          <div className="text-lg font-display tabular-nums">$1.14M</div>
        </div>
        <div>
          <div className="text-[10px] uppercase tracking-widest text-white/40">Payouts</div>
          <div className="text-lg font-display tabular-nums">$986K</div>
        </div>
      </div>
      <div className="grid grid-cols-7 gap-1">
        {[42, 68, 51, 74, 62, 82, 58].map((v, i) => (
          <div key={i} className="rounded-md bg-white/[0.04] overflow-hidden h-14 flex items-end">
            <motion.div
              initial={{ height: 0 }}
              whileInView={{ height: `${v}%` }}
              viewport={{ once: true }}
              transition={{ duration: 0.7, delay: i * 0.05, ease: [0.22, 1, 0.36, 1] }}
              className="w-full bg-gradient-to-t from-emerald-500/70 to-emerald-300/80"
            />
          </div>
        ))}
      </div>
    </Frame>
  );
}

function StatusPill({ children, tone }: { children: React.ReactNode; tone: 'success' | 'warn' | 'brand' | 'danger' }) {
  const map: Record<string, string> = {
    success: 'border-emerald-400/25 text-emerald-300 bg-emerald-400/10',
    warn: 'border-amber-400/25 text-amber-300 bg-amber-400/10',
    brand: 'border-primary-400/25 text-primary-200 bg-primary-500/10',
    danger: 'border-rose-400/25 text-rose-300 bg-rose-400/10',
  };
  return (
    <span className={`inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[10px] border ${map[tone]}`}>
      <span className="h-1 w-1 rounded-full bg-current" />
      {children}
    </span>
  );
}

export const iconRegistry = { MapPin, Truck, Route, Activity, LineChart };
