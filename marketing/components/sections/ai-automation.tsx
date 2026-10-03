import { Brain, Radar, Clock, Boxes, MapPin, Shield, LineChart, Zap } from 'lucide-react';
import { SectionHeader } from '@/components/ui/eyebrow';
import { Reveal, Stagger, StaggerItem } from '@/components/motion/reveal';

const capabilities = [
  { icon: Radar, title: 'Auto Dispatch', desc: 'Assign orders to the right hub, driver and carrier based on cost, SLA and capacity.' },
  { icon: MapPin, title: 'Smart Routing', desc: 'City-aware route optimization that beats hand-planned runs by 20–35% in km driven.' },
  { icon: Clock, title: 'ETA Prediction', desc: 'A model trained on your own delivery history, updated every minute against live traffic.' },
  { icon: Boxes, title: 'Capacity Planning', desc: 'Forecast tomorrow’s driver hours, vehicles and pack stations before the day starts.' },
  { icon: LineChart, title: 'Inventory Forecasting', desc: 'Predict stock-outs, seasonality and reorder timing per SKU per warehouse.' },
  { icon: Zap, title: 'Delivery Optimization', desc: 'Rebalance the schedule mid-day as new orders and cancellations arrive.' },
  { icon: Shield, title: 'Fraud Detection', desc: 'Flag suspicious COD, address chains and repeated returns before they hit ops.' },
  { icon: Brain, title: 'AI Analytics', desc: 'Ask questions in plain English. Get the chart, the number and the reason behind it.' },
];

export function AIAutomation() {
  return (
    <section id="ai" className="relative py-28">
      <div className="absolute inset-x-0 top-40 h-64 bg-grid-fade opacity-70 pointer-events-none" />
      <div className="container relative">
        <SectionHeader
          eyebrow="AI Automation"
          title={<>An AI operations layer, running your business at 3am.</>}
          description="Not features that write emails. Models that dispatch orders, route drivers, forecast stock and catch fraud — measurably, every day."
        />

        <div className="mt-14 relative">
          <div className="absolute inset-0 -z-10 grid place-items-center">
            <div className="h-72 w-72 rounded-full bg-accent-600/25 blur-3xl animate-pulse-glow" />
          </div>

          <Stagger className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            {capabilities.map((c) => (
              <StaggerItem key={c.title}>
                <div className="group relative h-full rounded-2xl border border-white/[0.06] bg-gradient-to-b from-white/[0.04] to-white/[0.01] p-5 overflow-hidden transition-all hover:border-white/15 hover:-translate-y-1">
                  <div className="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity bg-[radial-gradient(200px_at_var(--x,50%)_0%,rgba(96,165,250,0.15),transparent_60%)]" />
                  <div className="relative">
                    <div className="grid place-items-center h-10 w-10 rounded-xl bg-gradient-to-br from-primary-500/25 to-accent-500/25 border border-white/10">
                      <c.icon className="h-5 w-5 text-primary-100" />
                    </div>
                    <h3 className="mt-5 text-base font-display">{c.title}</h3>
                    <p className="mt-2 text-sm text-white/55 leading-relaxed">{c.desc}</p>
                  </div>
                </div>
              </StaggerItem>
            ))}
          </Stagger>
        </div>

        <Reveal delay={0.15}>
          <div className="mt-16 relative rounded-3xl border border-white/[0.07] overflow-hidden bg-gradient-to-br from-[#0b1224] to-[#050915]">
            <div className="absolute inset-0 opacity-30 bg-aurora blur-2xl" />
            <div className="relative grid gap-6 md:grid-cols-3 p-8 md:p-10">
              <Metric big="4.6×" small="Faster order-to-dispatch after enabling AI dispatch." />
              <Metric big="−24%" small="Kilometers driven per completed delivery on average." />
              <Metric big="+9.2pp" small="First-attempt delivery success across peak seasons." />
            </div>
          </div>
        </Reveal>
      </div>
    </section>
  );
}

function Metric({ big, small }: { big: string; small: string }) {
  return (
    <div>
      <div className="text-display-lg font-display gradient-brand tabular-nums">{big}</div>
      <p className="mt-2 text-white/60 max-w-xs">{small}</p>
    </div>
  );
}
