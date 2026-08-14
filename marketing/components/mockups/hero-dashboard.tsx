'use client';
import { motion } from 'framer-motion';
import { ArrowUpRight, Boxes, MapPin, Truck, Sparkles, TrendingUp, PackageCheck } from 'lucide-react';
import { Counter } from '@/components/motion/counter';
import { DotPulse } from '@/components/ui/badge';

const shipments = [
  { id: 'RSH-482910', from: 'Riyadh DC-1', to: 'Jeddah', status: 'In transit', tone: 'brand', eta: '2h 14m' },
  { id: 'RSH-482911', from: 'Dammam Hub', to: 'Al Khobar', status: 'Out for delivery', tone: 'brand', eta: '18m' },
  { id: 'RSH-482912', from: 'Riyadh DC-1', to: 'Mecca', status: 'Delivered', tone: 'success', eta: '—' },
  { id: 'RSH-482913', from: 'Jeddah Fulf.', to: 'Taif', status: 'Picking', tone: 'warn', eta: '3h' },
];

const chart = [22, 31, 28, 38, 34, 46, 44, 52, 49, 58, 62, 71, 68, 78, 82];

export function HeroDashboard() {
  return (
    <div className="relative">
      <div className="absolute -inset-24 pointer-events-none" aria-hidden>
        <div className="absolute inset-0 bg-aurora opacity-40 blur-3xl animate-spin-slow" />
      </div>

      <div className="relative rounded-[28px] p-2 glass-strong shadow-[0_40px_120px_-40px_rgba(37,99,235,0.55)]">
        <div className="rounded-[22px] bg-gradient-to-b from-[#0b1224] via-[#070d1c] to-[#050915] border border-white/[0.05] overflow-hidden">
          <TitleBar />
          <div className="grid grid-cols-12 gap-3 p-3">
            <Sidebar />
            <Main />
          </div>
        </div>
      </div>

      <FloatingCard
        className="hidden md:flex absolute -left-6 top-16"
        delay={0.2}
      >
        <span className="grid place-items-center h-9 w-9 rounded-xl bg-emerald-400/15 border border-emerald-400/20">
          <PackageCheck className="h-4 w-4 text-emerald-300" />
        </span>
        <div>
          <div className="text-[10px] uppercase tracking-widest text-white/40">Fulfilled today</div>
          <div className="text-lg font-display tabular-nums">
            <Counter to={12480} />
          </div>
        </div>
      </FloatingCard>

      <FloatingCard
        className="hidden md:flex absolute -right-4 top-40"
        delay={0.35}
      >
        <span className="grid place-items-center h-9 w-9 rounded-xl bg-primary-500/15 border border-primary-400/20">
          <TrendingUp className="h-4 w-4 text-primary-200" />
        </span>
        <div>
          <div className="text-[10px] uppercase tracking-widest text-white/40">GMV routed</div>
          <div className="text-lg font-display tabular-nums">
            $<Counter to={2.84} decimals={2} />M
          </div>
        </div>
      </FloatingCard>

      <FloatingCard
        className="hidden md:flex absolute -right-8 bottom-16"
        delay={0.5}
      >
        <span className="grid place-items-center h-9 w-9 rounded-xl bg-violet-500/15 border border-violet-400/25">
          <Sparkles className="h-4 w-4 text-violet-300" />
        </span>
        <div>
          <div className="text-[10px] uppercase tracking-widest text-white/40">AI dispatch</div>
          <div className="text-sm font-medium">Auto-assigned 812 orders</div>
        </div>
      </FloatingCard>
    </div>
  );
}

function FloatingCard({
  children,
  className,
  delay = 0,
}: {
  children: React.ReactNode;
  className?: string;
  delay?: number;
}) {
  return (
    <motion.div
      initial={{ opacity: 0, y: 14 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay, duration: 0.7, ease: [0.22, 1, 0.36, 1] }}
      className={className}
    >
      <div className="animate-float">
        <div className="glass-strong rounded-2xl px-3 py-2.5 shadow-ring flex items-center gap-3 min-w-[190px]">
          {children}
        </div>
      </div>
    </motion.div>
  );
}

function TitleBar() {
  return (
    <div className="flex items-center gap-3 border-b border-white/[0.05] px-4 py-3">
      <div className="flex gap-1.5">
        <span className="h-2.5 w-2.5 rounded-full bg-white/10" />
        <span className="h-2.5 w-2.5 rounded-full bg-white/10" />
        <span className="h-2.5 w-2.5 rounded-full bg-white/10" />
      </div>
      <div className="flex-1 flex items-center gap-2 justify-center">
        <div className="glass rounded-full px-3 py-1 text-[11px] text-white/60 inline-flex items-center gap-2">
          <MapPin className="h-3 w-3" />
          app.rushly.tech / operations
        </div>
      </div>
      <div className="flex items-center gap-2">
        <span className="h-6 w-6 rounded-full bg-gradient-to-br from-primary-400 to-accent-400" />
      </div>
    </div>
  );
}

function Sidebar() {
  const links = ['Overview', 'Orders', 'Shipments', 'Fleet', 'Warehouse', 'Merchants', 'Finance', 'Analytics'];
  return (
    <aside className="col-span-3 hidden md:block rounded-2xl border border-white/[0.04] bg-white/[0.02] p-3">
      <div className="text-[10px] uppercase tracking-widest text-white/40 px-2 mb-2">Workspace</div>
      <div className="glass rounded-xl px-2.5 py-2 mb-3 text-xs flex items-center gap-2">
        <span className="h-5 w-5 rounded-md bg-gradient-to-br from-primary-500 to-accent-500" />
        <span className="text-white/80">Al-Nasr Logistics</span>
      </div>
      <ul className="space-y-0.5 text-xs">
        {links.map((l, i) => (
          <li
            key={l}
            className={`flex items-center gap-2 rounded-lg px-2.5 py-1.5 ${
              i === 2 ? 'bg-white/[0.06] text-white' : 'text-white/60 hover:bg-white/[0.03]'
            }`}
          >
            <span className={`h-1.5 w-1.5 rounded-full ${i === 2 ? 'bg-primary-400' : 'bg-white/20'}`} />
            {l}
          </li>
        ))}
      </ul>
    </aside>
  );
}

function Main() {
  return (
    <div className="col-span-12 md:col-span-9 space-y-3">
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <Stat label="Live shipments" value={<Counter to={4218} />} delta="+12.4%" />
        <Stat label="Orders / hr" value={<Counter to={1064} />} delta="+8.1%" />
        <Stat label="On-time rate" value={<><Counter to={98.6} decimals={1} />%</>} delta="+0.7pp" />
        <Stat label="Avg. cost / order" value={<>$<Counter to={2.14} decimals={2} /></>} delta="-4.2%" />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-5 gap-3">
        <ChartPanel />
        <MapPanel />
      </div>

      <ShipmentsTable />
    </div>
  );
}

function Stat({ label, value, delta }: { label: string; value: React.ReactNode; delta: string }) {
  return (
    <div className="rounded-xl border border-white/[0.05] bg-gradient-to-b from-white/[0.03] to-transparent p-3">
      <div className="text-[10px] uppercase tracking-widest text-white/40">{label}</div>
      <div className="mt-1 flex items-end justify-between">
        <div className="text-xl font-display tabular-nums">{value}</div>
        <div className="text-[10px] rounded-full px-1.5 py-0.5 border border-emerald-400/20 bg-emerald-400/10 text-emerald-300 inline-flex items-center gap-0.5">
          <ArrowUpRight className="h-2.5 w-2.5" /> {delta}
        </div>
      </div>
    </div>
  );
}

function ChartPanel() {
  const max = Math.max(...chart);
  const w = 320;
  const h = 130;
  const step = w / (chart.length - 1);
  const points = chart.map((v, i) => `${i * step},${h - (v / max) * (h - 12) - 6}`).join(' ');
  const area = `M0,${h} L${points.replace(/ /g, ' L')} L${w},${h} Z`;
  return (
    <div className="lg:col-span-3 rounded-xl border border-white/[0.05] bg-white/[0.02] p-4">
      <div className="flex items-center justify-between mb-2">
        <div>
          <div className="text-xs text-white/60">Fulfillment throughput</div>
          <div className="text-lg font-display tabular-nums">
            <Counter to={82_140} />
            <span className="text-xs text-white/40 ml-1">units / week</span>
          </div>
        </div>
        <div className="flex items-center gap-1 text-[11px]">
          {['1D', '1W', '1M', '3M'].map((l, i) => (
            <span key={l} className={`px-2 py-0.5 rounded-full ${i === 1 ? 'glass text-white' : 'text-white/40'}`}>
              {l}
            </span>
          ))}
        </div>
      </div>
      <svg viewBox={`0 0 ${w} ${h}`} className="w-full h-[130px]" preserveAspectRatio="none">
        <defs>
          <linearGradient id="ha" x1="0" x2="0" y1="0" y2="1">
            <stop offset="0%" stopColor="#60a5fa" stopOpacity="0.35" />
            <stop offset="100%" stopColor="#60a5fa" stopOpacity="0" />
          </linearGradient>
          <linearGradient id="hl" x1="0" x2="1" y1="0" y2="0">
            <stop offset="0%" stopColor="#60a5fa" />
            <stop offset="60%" stopColor="#22d3ee" />
            <stop offset="100%" stopColor="#a78bfa" />
          </linearGradient>
        </defs>
        <path d={area} fill="url(#ha)" />
        <motion.polyline
          fill="none"
          stroke="url(#hl)"
          strokeWidth="2"
          strokeLinecap="round"
          strokeLinejoin="round"
          points={points}
          initial={{ pathLength: 0 }}
          animate={{ pathLength: 1 }}
          transition={{ duration: 1.6, ease: [0.22, 1, 0.36, 1] }}
        />
      </svg>
    </div>
  );
}

function MapPanel() {
  const pts = [
    { x: 20, y: 60, live: true },
    { x: 44, y: 40 },
    { x: 68, y: 55, live: true },
    { x: 34, y: 78 },
    { x: 78, y: 30 },
    { x: 58, y: 80, live: true },
    { x: 88, y: 68 },
    { x: 14, y: 30 },
  ];
  return (
    <div className="lg:col-span-2 rounded-xl border border-white/[0.05] bg-white/[0.02] p-4 relative overflow-hidden">
      <div className="text-xs text-white/60 flex items-center gap-2">
        <DotPulse /> Live fleet · KSA
      </div>
      <div className="mt-3 aspect-[4/3] rounded-lg relative overflow-hidden bg-gradient-to-br from-[#0a1229] to-[#050915]">
        <svg viewBox="0 0 100 100" className="absolute inset-0 h-full w-full opacity-30" preserveAspectRatio="none">
          <defs>
            <pattern id="grid" width="8" height="8" patternUnits="userSpaceOnUse">
              <path d="M8 0 H0 V8" stroke="rgba(255,255,255,0.08)" fill="none" strokeWidth="0.4" />
            </pattern>
          </defs>
          <rect width="100" height="100" fill="url(#grid)" />
        </svg>
        <svg viewBox="0 0 100 100" className="absolute inset-0 h-full w-full">
          <path
            d="M6,58 Q22,50 40,52 T74,44 T96,50 M14,72 Q34,66 52,72 T86,66 M20,32 Q40,26 60,32 T90,28"
            stroke="rgba(96,165,250,0.35)"
            strokeWidth="0.6"
            fill="none"
          />
        </svg>
        {pts.map((p, i) => (
          <div key={i} className="absolute -translate-x-1/2 -translate-y-1/2" style={{ left: `${p.x}%`, top: `${p.y}%` }}>
            {p.live && (
              <span className="absolute inset-0 -m-1 rounded-full bg-primary-400/50 animate-ping-soft" />
            )}
            <span
              className={`block h-2 w-2 rounded-full ${
                p.live ? 'bg-primary-400 shadow-[0_0_10px_rgba(96,165,250,0.9)]' : 'bg-white/40'
              }`}
            />
          </div>
        ))}
      </div>
    </div>
  );
}

function ShipmentsTable() {
  return (
    <div className="rounded-xl border border-white/[0.05] bg-white/[0.02] overflow-hidden">
      <div className="flex items-center justify-between px-4 py-2.5 border-b border-white/[0.05]">
        <div className="text-xs text-white/60 inline-flex items-center gap-2">
          <Truck className="h-3.5 w-3.5" /> Live shipments
        </div>
        <div className="text-[10px] text-white/40">Updated 2s ago</div>
      </div>
      <table className="w-full text-xs">
        <thead className="text-[10px] uppercase tracking-widest text-white/40">
          <tr>
            <th className="text-left font-normal px-4 py-2">Tracking</th>
            <th className="text-left font-normal px-4 py-2">Route</th>
            <th className="text-left font-normal px-4 py-2">Status</th>
            <th className="text-right font-normal px-4 py-2">ETA</th>
          </tr>
        </thead>
        <tbody>
          {shipments.map((s) => (
            <tr key={s.id} className="border-t border-white/[0.03] hover:bg-white/[0.02] transition-colors">
              <td className="px-4 py-2.5 font-mono text-[11px] text-white/70">{s.id}</td>
              <td className="px-4 py-2.5 text-white/70">
                {s.from} <span className="text-white/30 mx-1">→</span> {s.to}
              </td>
              <td className="px-4 py-2.5">
                <span
                  className={`inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[10px] border ${
                    s.tone === 'success'
                      ? 'border-emerald-400/25 text-emerald-300 bg-emerald-400/10'
                      : s.tone === 'warn'
                        ? 'border-amber-400/25 text-amber-300 bg-amber-400/10'
                        : 'border-primary-400/25 text-primary-200 bg-primary-500/10'
                  }`}
                >
                  <span className="h-1 w-1 rounded-full bg-current" /> {s.status}
                </span>
              </td>
              <td className="px-4 py-2.5 text-right tabular-nums text-white/70">{s.eta}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

export { Boxes };
