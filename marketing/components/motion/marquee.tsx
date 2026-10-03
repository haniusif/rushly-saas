'use client';
import { cn } from '@/lib/cn';
import * as React from 'react';

export function Marquee({
  children,
  speed = 'normal',
  pauseOnHover = true,
  className,
}: {
  children: React.ReactNode;
  speed?: 'normal' | 'slow';
  pauseOnHover?: boolean;
  className?: string;
}) {
  return (
    <div className={cn('group relative overflow-hidden [--gap:3rem]', className)}>
      <div
        className={cn(
          'flex w-max items-center gap-[var(--gap)]',
          speed === 'slow' ? 'animate-marquee-slow' : 'animate-marquee',
          pauseOnHover && 'group-hover:[animation-play-state:paused]',
        )}
      >
        <div className="flex items-center gap-[var(--gap)]">{children}</div>
        <div className="flex items-center gap-[var(--gap)]" aria-hidden>
          {children}
        </div>
      </div>
    </div>
  );
}
