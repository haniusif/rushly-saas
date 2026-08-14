'use client';
import { motion } from 'framer-motion';
import { Barcode, Bot, Package, Warehouse, Boxes, Scan } from 'lucide-react';
import { SectionHeader } from '@/components/ui/eyebrow';
import { DotPulse } from '@/components/ui/badge';

const chips = [
  { icon: Bot, label: 'AMR fleet' },
  { icon: Barcode, label: 'Barcode / RFID' },
  { icon: Package, label: 'Pick & pack' },
  { icon: Boxes, label: 'Sortation' },
  { icon: Warehouse, label: 'Bin-level' },
  { icon: Scan, label: 'Cycle counts' },
];

export function WarehouseShowcase() {
  return (
    <section className="relative py-28">
      <div className="container">
        <SectionHeader
          eyebrow="Warehouse"
          title={<>A warehouse that runs like software.</>}
          description="From goods-in to sortation to dispatch — every scan, every bin, every robot, feeding one operating system."
        />

        <div className="mt-14 relative rounded-[28px] border border-white/[0.07] overflow-hidden bg-gradient-to-b from-[#0a1229] to-[#050915]">
          <div className="absolute inset-0 pointer-events-none">
            <div className="absolute inset-0 bg-aurora opacity-20 blur-3xl" />
            <svg viewBox="0 0 100 100" className="absolute inset-0 h-full w-full opacity-20" preserveAspectRatio="none">
              <defs>
                <pattern id="wg" width="4" height="4" patternUnits="userSpaceOnUse">
                  <path d="M4 0 H0 V4" stroke="rgba(255,255,255,0.1)" fill="none" strokeWidth="0.2" />
                </pattern>
              </defs>
              <rect width="100" height="100" fill="url(#wg)" />
            </svg>
          </div>

          <div className="relative grid md:grid-cols-[1fr_1.2fr] gap-6 p-6 md:p-10">
            <div>
              <div className="inline-flex items-center gap-2 glass rounded-full px-3 py-1 text-xs">
                <DotPulse /> Live from Rushly DC-1, Riyadh
              </div>
              <h3 className="mt-6 text-display-md font-display gradient-brand">
                Robots. Barcodes. Batches. All talking to one platform.
              </h3>
              <p className="mt-4 text-white/60 leading-relaxed max-w-md">
                Rushly WMS orchestrates the floor — from AMR fleets and conveyor sortation
                to manual pack stations — with a single API and a single dashboard.
              </p>

              <div className="mt-8 grid grid-cols-2 gap-2.5">
                {chips.map((c) => (
                  <div key={c.label} className="glass rounded-xl px-3 py-2.5 flex items-center gap-2 text-sm text-white/80">
                    <c.icon className="h-4 w-4 text-primary-300" /> {c.label}
                  </div>
                ))}
              </div>
            </div>

            <IsometricWarehouse />
          </div>
        </div>
      </div>
    </section>
  );
}

function IsometricWarehouse() {
  return (
    <div className="relative">
      <div className="relative aspect-[4/3] rounded-2xl overflow-hidden border border-white/[0.06] bg-gradient-to-br from-[#0b1428] to-[#050915]">
        <div className="absolute inset-0 [transform:perspective(1200px)_rotateX(48deg)_rotateZ(-38deg)_scale(1.05)] origin-center">
          <FloorGrid />
          <Racks row={0} />
          <Racks row={1} />
          <Racks row={2} />
          <Racks row={3} />
        </div>

        <RobotDot delay={0} />
        <RobotDot delay={0.4} className="left-[26%] top-[52%]" />
        <RobotDot delay={0.9} className="left-[62%] top-[46%]" />

        <div className="absolute top-4 left-4 glass rounded-xl px-3 py-2 text-xs">
          <div className="text-[10px] uppercase tracking-widest text-white/40">AMRs online</div>
          <div className="text-lg font-display tabular-nums">28<span className="text-white/40 text-xs"> / 32</span></div>
        </div>

        <div className="absolute bottom-4 right-4 glass rounded-xl px-3 py-2 text-xs">
          <div className="text-[10px] uppercase tracking-widest text-white/40">Picks / hour</div>
          <div className="text-lg font-display tabular-nums">2,418</div>
        </div>

        <div className="absolute inset-0 bg-gradient-to-t from-[#020617]/60 to-transparent pointer-events-none" />
      </div>
    </div>
  );
}

function FloorGrid() {
  return (
    <div
      className="absolute inset-0"
      style={{
        backgroundImage:
          'linear-gradient(rgba(96,165,250,0.12) 1px, transparent 1px), linear-gradient(90deg, rgba(96,165,250,0.12) 1px, transparent 1px)',
        backgroundSize: '40px 40px',
      }}
    />
  );
}

function Racks({ row }: { row: number }) {
  return (
    <div className="absolute inset-x-0" style={{ top: `${18 + row * 18}%` }}>
      <div className="flex gap-8 px-8">
        {Array.from({ length: 5 }).map((_, i) => (
          <motion.div
            key={i}
            initial={{ opacity: 0, y: 12 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6, delay: row * 0.1 + i * 0.05 }}
            className="flex-1 h-8 rounded-md bg-gradient-to-b from-primary-500/40 to-primary-700/30 border border-white/10 shadow-[0_0_20px_-4px_rgba(96,165,250,0.5)]"
          />
        ))}
      </div>
    </div>
  );
}

function RobotDot({ className, delay = 0 }: { className?: string; delay?: number }) {
  return (
    <motion.div
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      transition={{ delay, duration: 0.6 }}
      className={`absolute left-[46%] top-[62%] ${className ?? ''}`}
    >
      <div className="relative">
        <span className="absolute inset-0 -m-2 rounded-full bg-primary-400/50 animate-ping-soft" />
        <span className="block h-3 w-3 rounded-full bg-primary-300 shadow-[0_0_14px_2px_rgba(96,165,250,0.9)]" />
      </div>
    </motion.div>
  );
}
