import * as React from 'react';
import { cn } from '@/lib/utils';
import { Card, CardContent } from '@/Components/ui/Card';

/**
 * Compact KPI tile used across the Performance Dashboard.
 *
 * Variants:
 *   - default: white card with subtle border
 *   - accent:  primary tint
 *   - warn / good / bad: colored top border
 *
 * Pass `proxy: true` to add a small "proxy" pill — for KPIs sourced from
 * substitute data (e.g. on-time rate derived from delivery-type assumed SLA).
 */
export default function KpiTile({
    label, value, sub, icon: Icon, accent, proxy, trend, fmt, t,
}) {
    const display = fmt === 'pct' && typeof value === 'number'
        ? `${(value * 100).toFixed(1)}%`
        : fmt === 'money' && typeof value === 'number'
            ? value.toLocaleString(undefined, { maximumFractionDigits: 2 })
            : (value ?? '—');

    return (
        <Card className={cn(
            'relative overflow-hidden transition-shadow hover:shadow-md',
            accent === 'good' && 'border-emerald-200',
            accent === 'warn' && 'border-amber-200',
            accent === 'bad'  && 'border-rose-200',
            accent === 'info' && 'border-sky-200',
        )}>
            {accent && (
                <span className={cn(
                    'absolute inset-x-0 top-0 h-1',
                    accent === 'good' && 'bg-emerald-500',
                    accent === 'warn' && 'bg-amber-500',
                    accent === 'bad'  && 'bg-rose-500',
                    accent === 'info' && 'bg-sky-500',
                )} />
            )}
            <CardContent className="p-4">
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                        <div className="text-[11px] uppercase tracking-wider font-medium text-muted-foreground truncate">{label}</div>
                        <div className="mt-1 text-2xl font-semibold tabular-nums truncate">{display}</div>
                        {sub && <div className="text-xs text-muted-foreground mt-0.5 truncate">{sub}</div>}
                    </div>
                    {Icon && (
                        <div className={cn(
                            'shrink-0 rounded-md h-9 w-9 flex items-center justify-center',
                            accent === 'good' && 'bg-emerald-50 text-emerald-600',
                            accent === 'warn' && 'bg-amber-50 text-amber-600',
                            accent === 'bad'  && 'bg-rose-50 text-rose-600',
                            accent === 'info' && 'bg-sky-50 text-sky-600',
                            !accent && 'bg-primary/10 text-primary',
                        )}>
                            <Icon className="h-4 w-4" />
                        </div>
                    )}
                </div>
                <div className="flex items-center gap-2 mt-2">
                    {typeof trend === 'number' && (
                        <span className={cn(
                            'inline-flex items-center gap-0.5 text-[11px] font-medium tabular-nums',
                            trend > 0 ? 'text-emerald-600' : trend < 0 ? 'text-rose-600' : 'text-muted-foreground'
                        )}>
                            {trend > 0 ? '▲' : trend < 0 ? '▼' : '•'} {Math.abs(trend * 100).toFixed(1)}%
                        </span>
                    )}
                    {proxy && (
                        <span
                            className="inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-amber-50 text-amber-700 border border-amber-200"
                            title={t?.proxy_title || "Computed from substitute data — see tooltip on the metric for the formula."}
                        >
                            {t?.proxy_label || "proxy"}
                        </span>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
