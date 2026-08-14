'use client';
import { animate, useInView, useMotionValue, useTransform, motion } from 'framer-motion';
import { useEffect, useRef } from 'react';

export function Counter({
  from = 0,
  to,
  suffix = '',
  prefix = '',
  decimals = 0,
  duration = 2,
  className,
}: {
  from?: number;
  to: number;
  suffix?: string;
  prefix?: string;
  decimals?: number;
  duration?: number;
  className?: string;
}) {
  const ref = useRef<HTMLSpanElement>(null);
  const inView = useInView(ref, { once: true, margin: '-30px' });
  const mv = useMotionValue(from);
  const display = useTransform(mv, (v) => `${prefix}${v.toLocaleString('en-US', { maximumFractionDigits: decimals, minimumFractionDigits: decimals })}${suffix}`);

  useEffect(() => {
    if (!inView) return;
    const controls = animate(mv, to, { duration, ease: [0.22, 1, 0.36, 1] });
    return controls.stop;
  }, [inView, mv, to, duration]);

  return <motion.span ref={ref} className={className}>{display}</motion.span>;
}
