import { cn } from '@/lib/cn';

export function Eyebrow({ children, className }: { children: React.ReactNode; className?: string }) {
  return (
    <div className={cn('inline-flex items-center gap-2 text-[11px] uppercase tracking-[0.22em] text-white/50', className)}>
      <span className="h-px w-8 bg-gradient-to-r from-transparent via-primary-400/70 to-primary-400" />
      {children}
    </div>
  );
}

export function SectionHeader({
  eyebrow,
  title,
  description,
  align = 'center',
}: {
  eyebrow?: string;
  title: React.ReactNode;
  description?: React.ReactNode;
  align?: 'center' | 'left';
}) {
  return (
    <div className={cn('max-w-3xl', align === 'center' ? 'mx-auto text-center' : 'text-left')}>
      {eyebrow ? <Eyebrow className={align === 'center' ? 'mx-auto' : ''}>{eyebrow}</Eyebrow> : null}
      <h2 className="mt-4 text-display-lg font-display text-balance gradient-text">{title}</h2>
      {description ? (
        <p className="mt-5 text-base md:text-lg text-white/60 text-balance leading-relaxed">{description}</p>
      ) : null}
    </div>
  );
}
