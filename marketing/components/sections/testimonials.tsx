import { Quote, PlayCircle, Star } from 'lucide-react';
import { SectionHeader } from '@/components/ui/eyebrow';
import { Reveal } from '@/components/motion/reveal';

const testimonials = [
  {
    quote: 'We rewired our entire fulfillment on Rushly in 6 weeks. Cost per order dropped 21% and our merchants stopped calling.',
    author: 'Layla Al-Otaibi',
    role: 'COO, Nassab Logistics',
    stars: 5,
    gradient: 'from-primary-500/40 to-accent-500/40',
  },
  {
    quote: 'The AI dispatcher does what our senior planners do — except at 3am, on Fridays, at 4× the volume.',
    author: 'Faisal Nasser',
    role: 'Head of Ops, Baytha 3PL',
    stars: 5,
    gradient: 'from-secondary-500/40 to-primary-500/40',
  },
  {
    quote: 'We used to run six admin panels. Now we run one. Onboarding a new merchant takes an afternoon.',
    author: 'Mona Al-Harbi',
    role: 'Founder, Ateer Fulfillment',
    stars: 5,
    gradient: 'from-accent-500/40 to-secondary-500/40',
  },
];

export function Testimonials() {
  return (
    <section className="relative py-28">
      <div className="container">
        <SectionHeader
          eyebrow="Customers"
          title={<>Operators. Not evangelists.</>}
          description="Real teams running real ops on Rushly — in Saudi, the Gulf, and now four continents."
        />

        <div className="mt-14 grid gap-4 lg:grid-cols-3">
          {testimonials.map((t, i) => (
            <Reveal key={t.author} delay={i * 0.06}>
              <figure className="relative h-full rounded-2xl border border-white/[0.07] bg-gradient-to-b from-white/[0.04] to-white/[0.01] p-6 overflow-hidden group">
                <div className={`absolute -top-16 -right-16 h-48 w-48 rounded-full bg-gradient-to-br ${t.gradient} blur-3xl opacity-60`} aria-hidden />
                <div className="relative">
                  <Quote className="h-8 w-8 text-white/20" />
                  <blockquote className="mt-4 text-lg leading-relaxed font-display text-balance">
                    “{t.quote}”
                  </blockquote>
                  <div className="mt-6 flex items-center gap-1 text-amber-300">
                    {Array.from({ length: t.stars }).map((_, k) => (
                      <Star key={k} className="h-3.5 w-3.5 fill-current" />
                    ))}
                  </div>
                  <figcaption className="mt-6 flex items-center gap-3">
                    <span className={`h-10 w-10 rounded-full bg-gradient-to-br ${t.gradient} border border-white/10`} />
                    <div>
                      <div className="text-sm font-medium">{t.author}</div>
                      <div className="text-xs text-white/50">{t.role}</div>
                    </div>
                  </figcaption>
                </div>
              </figure>
            </Reveal>
          ))}
        </div>

        <Reveal delay={0.2}>
          <div className="mt-10 relative rounded-3xl border border-white/[0.07] overflow-hidden bg-gradient-to-br from-[#0b1428] to-[#050915]">
            <div className="absolute inset-0 bg-aurora opacity-20 blur-3xl" />
            <div className="relative grid md:grid-cols-[1.2fr_1fr] gap-6 p-6 md:p-10 items-center">
              <div>
                <span className="inline-flex items-center gap-2 rounded-full glass px-3 py-1 text-xs">Case study · 3 min read</span>
                <h3 className="mt-4 text-display-md font-display gradient-text text-balance">
                  How Nassab hit 21% lower cost per order in one quarter.
                </h3>
                <p className="mt-4 text-white/60 max-w-lg leading-relaxed">
                  A national 3PL swapped a stack of five point tools for one Rushly workspace —
                  and rebuilt their SLA reporting in the process.
                </p>
                <a href="#" className="mt-6 inline-flex items-center gap-2 text-sm text-primary-200 hover:text-primary-100">
                  <PlayCircle className="h-5 w-5" /> Watch the 3-minute story
                </a>
              </div>
              <div className="relative aspect-video rounded-2xl border border-white/[0.08] overflow-hidden bg-gradient-to-br from-primary-600/25 to-accent-600/25">
                <div className="absolute inset-0 grid place-items-center">
                  <button className="glass-strong h-16 w-16 rounded-full grid place-items-center hover:scale-110 transition-transform">
                    <PlayCircle className="h-8 w-8 text-white" />
                  </button>
                </div>
                <div className="absolute bottom-3 left-3 text-xs text-white/70 glass rounded-full px-2.5 py-1">
                  Nassab · COO
                </div>
              </div>
            </div>
          </div>
        </Reveal>
      </div>
    </section>
  );
}
