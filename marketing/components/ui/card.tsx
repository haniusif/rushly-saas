import * as React from 'react';
import { cn } from '@/lib/cn';

export function Card({
  children,
  className,
  hover = false,
}: {
  children: React.ReactNode;
  className?: string;
  hover?: boolean;
}) {
  return (
    <div
      className={cn(
        'group relative rounded-2xl border border-white/[0.07] bg-gradient-to-b from-white/[0.04] to-white/[0.01] p-6 shadow-card',
        hover && 'transition-all duration-300 hover:border-white/15 hover:-translate-y-1 hover:shadow-glow',
        className,
      )}
    >
      <div className="pointer-events-none absolute inset-0 rounded-2xl bg-gradient-to-b from-white/[0.06] to-transparent opacity-0 group-hover:opacity-100 transition-opacity" />
      <div className="relative">{children}</div>
    </div>
  );
}
