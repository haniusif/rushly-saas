import * as React from 'react';
import { cn } from '@/lib/utils';

const BAND_STYLES = {
    excellent:         { bg: 'bg-emerald-100', text: 'text-emerald-700', dot: 'bg-emerald-500', label: 'Excellent' },
    very_good:         { bg: 'bg-sky-100',     text: 'text-sky-700',     dot: 'bg-sky-500',     label: 'Very Good' },
    good:              { bg: 'bg-indigo-100',  text: 'text-indigo-700',  dot: 'bg-indigo-500',  label: 'Good' },
    needs_improvement: { bg: 'bg-amber-100',   text: 'text-amber-700',   dot: 'bg-amber-500',   label: 'Needs Improvement' },
    critical:          { bg: 'bg-rose-100',    text: 'text-rose-700',    dot: 'bg-rose-500',    label: 'Critical' },
};

export default function ScoreBadge({ score, band, size = 'sm' }) {
    const style = BAND_STYLES[band] || BAND_STYLES.critical;
    return (
        <span className={cn(
            'inline-flex items-center gap-1.5 rounded-full font-medium tabular-nums',
            style.bg, style.text,
            size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-3 py-1 text-sm',
        )}>
            <span className={cn('h-1.5 w-1.5 rounded-full', style.dot)} />
            {score}
            <span className="opacity-70 font-normal">· {style.label}</span>
        </span>
    );
}
