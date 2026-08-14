import { ArrowRight, PlayCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Reveal } from '@/components/motion/reveal';

export function FinalCTA() {
  return (
    <section className="relative py-32">
      <div className="container">
        <Reveal>
          <div className="relative overflow-hidden rounded-[32px] border border-white/[0.08] bg-gradient-to-br from-[#0a1229] via-[#070f22] to-[#050915] p-10 md:p-16">
            <div className="absolute -top-40 left-1/2 -translate-x-1/2 h-[540px] w-[900px] rounded-full bg-aurora opacity-40 blur-3xl pointer-events-none animate-pulse-glow" />
            <div className="absolute inset-0 grid-bg opacity-40 mask-b-fade pointer-events-none" />
            <div className="relative text-center max-w-3xl mx-auto">
              <span className="inline-flex items-center gap-2 rounded-full glass px-3 py-1.5 text-xs">
                Ready when you are
              </span>
              <h2 className="mt-6 text-display-xl font-display text-balance gradient-brand">
                Start your logistics transformation today.
              </h2>
              <p className="mt-6 text-white/60 text-lg leading-relaxed text-balance">
                One platform. Every shipment, every warehouse, every merchant, every courier.
                Live in 24 hours — no re-implementation required.
              </p>
              <div className="mt-10 flex flex-wrap items-center justify-center gap-3">
                <Button size="xl">
                  Start free <ArrowRight className="h-4 w-4" />
                </Button>
                <Button size="xl" variant="secondary">
                  Book a demo
                </Button>
                <Button size="xl" variant="ghost">
                  <PlayCircle className="h-4 w-4" /> Watch the tour
                </Button>
              </div>
              <div className="mt-8 text-xs text-white/40">
                No credit card required · SOC 2 · ISO 27001 · GDPR
              </div>
            </div>
          </div>
        </Reveal>
      </div>
    </section>
  );
}
