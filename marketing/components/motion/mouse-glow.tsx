'use client';
import { useEffect, useRef } from 'react';

export function MouseGlow() {
  const ref = useRef<HTMLDivElement>(null);
  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const onMove = (e: MouseEvent) => {
      el.style.setProperty('--mx', `${e.clientX}px`);
      el.style.setProperty('--my', `${e.clientY}px`);
    };
    window.addEventListener('pointermove', onMove);
    return () => window.removeEventListener('pointermove', onMove);
  }, []);
  return (
    <div
      ref={ref}
      aria-hidden
      className="pointer-events-none fixed inset-0 z-0 opacity-70"
      style={{
        background:
          'radial-gradient(600px circle at var(--mx, 50%) var(--my, 20%), rgba(37,99,235,0.15), transparent 45%)',
      }}
    />
  );
}
