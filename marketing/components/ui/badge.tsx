import * as React from 'react';
import { cn } from '@/lib/cn';

export function Badge({
  children,
  className,
  tone = 'default',
}: {
  children: React.ReactNode;
  className?: string;
  tone?: 'default' | 'success' | 'warn' | 'brand';
}) {
  const tones: Record<string, string> = {
    default: 'border-white/10 bg-white/[0.04] text-white/80',
    success: 'border-emerald-400/20 bg-emerald-400/[0.08] text-emerald-300',
    warn: 'border-amber-400/20 bg-amber-400/[0.08] text-amber-300',
    brand: 'border-primary-400/25 bg-primary-500/[0.10] text-primary-100',
  };
  return (
    <span
      className={cn(
        'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-medium tracking-wide uppercase',
        tones[tone],
        className,
      )}
    >
      {children}
    </span>
  );
}

export function DotPulse({ tone = 'success' }: { tone?: 'success' | 'brand' | 'warn' }) {
  const color = tone === 'success' ? 'bg-emerald-400' : tone === 'warn' ? 'bg-amber-400' : 'bg-primary-400';
  return (
    <span className="relative inline-flex h-2 w-2">
      <span className={cn('absolute inline-flex h-full w-full rounded-full opacity-75 animate-ping-soft', color)} />
      <span className={cn('relative inline-flex h-2 w-2 rounded-full', color)} />
    </span>
  );
}
