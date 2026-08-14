import { Marquee } from '@/components/motion/marquee';

const logos = [
  'ARAMEX', 'SMSA', 'JET', 'REDBOX', 'SAEE', 'AJEX', 'FASTLO', 'SPL',
  'BARQ', 'SHIPA', 'NAQEL', 'IMILE', 'DHL', 'FEDEX', 'ARAMCO', 'STC',
];

export function TrustedBy() {
  return (
    <section className="relative py-16">
      <div className="container">
        <p className="text-center text-[11px] uppercase tracking-[0.25em] text-white/40">
          Powering logistics operations at
        </p>
        <div className="mt-8 relative">
          <div className="pointer-events-none absolute inset-y-0 left-0 w-24 z-10 bg-gradient-to-r from-bg to-transparent" />
          <div className="pointer-events-none absolute inset-y-0 right-0 w-24 z-10 bg-gradient-to-l from-bg to-transparent" />
          <Marquee>
            {logos.map((l) => (
              <span
                key={l}
                className="font-display text-2xl md:text-3xl tracking-[0.14em] text-white/40 hover:text-white/80 transition-colors"
              >
                {l}
              </span>
            ))}
          </Marquee>
        </div>
      </div>
    </section>
  );
}
