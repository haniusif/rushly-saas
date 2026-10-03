import { SectionHeader } from '@/components/ui/eyebrow';
import { Counter } from '@/components/motion/counter';
import { Reveal } from '@/components/motion/reveal';

const kpis = [
  { value: 99.99, suffix: '%', decimals: 2, label: 'Platform uptime', sub: 'Multi-region, autoscaling infrastructure' },
  { value: 82, prefix: '', suffix: 'M+', label: 'Orders orchestrated', sub: 'Since launch, across MENA and beyond' },
  { value: 14_200, suffix: '+', label: 'Merchants shipping', sub: 'From growing storefronts to global brands' },
  { value: 46, suffix: 'M+', label: 'Shipments routed', sub: 'Fed by Rushly Smart Routing daily' },
  { value: 12.4, suffix: 's', decimals: 1, label: 'p95 dispatch latency', sub: 'Order-in to driver-assigned' },
  { value: 24, suffix: '/7', label: 'Enterprise support', sub: 'Follow-the-sun SLA, dedicated CSM' },
];

export function Performance() {
  return (
    <section className="relative py-28">
      <div className="absolute inset-x-0 top-40 h-64 bg-grid-fade opacity-70 pointer-events-none" />
      <div className="container relative">
        <SectionHeader
          eyebrow="Performance"
          title={<>Built for the scale of national logistics.</>}
          description="These numbers aren’t marketing. They’re from the Rushly workspaces running production today."
        />

        <div className="mt-14 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {kpis.map((k, i) => (
            <Reveal key={k.label} delay={i * 0.05}>
              <div className="relative rounded-2xl border border-white/[0.06] bg-gradient-to-b from-white/[0.04] to-white/[0.01] p-6 overflow-hidden">
                <div className="absolute -top-14 -right-14 h-40 w-40 rounded-full bg-primary-500/20 blur-3xl" aria-hidden />
                <div className="relative">
                  <div className="text-display-lg font-display tabular-nums gradient-brand leading-none">
                    <Counter to={k.value} suffix={k.suffix ?? ''} prefix={k.prefix ?? ''} decimals={k.decimals ?? 0} />
                  </div>
                  <div className="mt-4 text-white font-medium">{k.label}</div>
                  <div className="text-sm text-white/50 mt-1">{k.sub}</div>
                </div>
              </div>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}
