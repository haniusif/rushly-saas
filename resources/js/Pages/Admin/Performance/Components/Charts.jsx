import * as React from 'react';
import { cn } from '@/lib/utils';

/**
 * Lightweight inline-SVG charts to avoid pulling in another runtime dep.
 * Matches the pattern already used by the WMS dashboard (Admin/Wms/Dashboard).
 *
 * Components:
 *   <LineSeries>  — daily series with 2 lines (delivered vs assigned)
 *   <BarSeries>   — horizontal bar (rating distribution)
 *   <Donut>       — single-arc donut for "share" KPIs
 */

const BAND_COLORS = {
    delivered: '#10b981', // emerald-500
    assigned:  '#a21f5c', // brand
    primary:   '#a21f5c',
    accent:    '#6366f1',
};

export function LineSeries({ data, height = 220, t }) {
    const w = 720;
    const pad = { top: 12, right: 12, bottom: 28, left: 32 };
    if (!data?.length) return <EmptyChart height={height} t={t} />;

    const maxY = Math.max(1, ...data.map((d) => Math.max(d.delivered ?? 0, d.assigned ?? 0)));
    const innerW = w - pad.left - pad.right;
    const innerH = height - pad.top - pad.bottom;
    const x = (i) => pad.left + (innerW * (i / Math.max(1, data.length - 1)));
    const y = (v) => pad.top + innerH - (innerH * (v / maxY));

    const path = (key) => data.map((d, i) => `${i === 0 ? 'M' : 'L'} ${x(i).toFixed(1)} ${y(d[key] ?? 0).toFixed(1)}`).join(' ');
    const ticks = 4;
    const gridY = Array.from({ length: ticks + 1 }, (_, i) => Math.round(maxY * (i / ticks)));

    return (
        <div className="w-full overflow-x-auto">
            <svg viewBox={`0 0 ${w} ${height}`} className="w-full min-w-[600px] text-muted-foreground">
                {/* gridlines */}
                {gridY.map((g, i) => {
                    const yy = y(g);
                    return (
                        <g key={i}>
                            <line x1={pad.left} x2={w - pad.right} y1={yy} y2={yy} stroke="currentColor" strokeOpacity={0.08} />
                            <text x={6} y={yy + 3} fontSize={10} fill="currentColor" opacity={0.6}>{g}</text>
                        </g>
                    );
                })}
                {/* x labels */}
                {data.map((d, i) => (i % Math.max(1, Math.floor(data.length / 8)) === 0) && (
                    <text key={i} x={x(i)} y={height - 8} fontSize={10} textAnchor="middle" fill="currentColor" opacity={0.6}>
                        {d.label}
                    </text>
                ))}
                {/* lines */}
                <path d={path('assigned')}  fill="none" stroke={BAND_COLORS.assigned}  strokeWidth={2} />
                <path d={path('delivered')} fill="none" stroke={BAND_COLORS.delivered} strokeWidth={2} />
                {/* dots */}
                {data.map((d, i) => (
                    <g key={`d-${i}`}>
                        <circle cx={x(i)} cy={y(d.delivered ?? 0)} r="2" fill={BAND_COLORS.delivered} />
                        <circle cx={x(i)} cy={y(d.assigned ?? 0)}  r="2" fill={BAND_COLORS.assigned} />
                    </g>
                ))}
            </svg>
            <div className="flex items-center gap-4 text-xs text-muted-foreground px-2">
                <span className="inline-flex items-center gap-1.5">
                    <span className="h-2 w-2 rounded-full" style={{ background: BAND_COLORS.delivered }} /> {t?.chart_delivered || 'Delivered'}
                </span>
                <span className="inline-flex items-center gap-1.5">
                    <span className="h-2 w-2 rounded-full" style={{ background: BAND_COLORS.assigned }} /> {t?.chart_assigned || 'Assigned'}
                </span>
            </div>
        </div>
    );
}

export function BarSeries({ data, accent = BAND_COLORS.primary, t }) {
    if (!data?.length) return <EmptyChart height={140} t={t} />;
    const max = Math.max(1, ...data.map((d) => d.count ?? 0));
    return (
        <div className="space-y-2">
            {data.map((d) => (
                <div key={d.bucket || d.label} className="flex items-center gap-3">
                    <span className="w-14 text-xs text-muted-foreground tabular-nums">{d.bucket || d.label}</span>
                    <div className="flex-1 h-3 bg-muted rounded-full overflow-hidden">
                        <div className="h-full rounded-full transition-all" style={{ width: `${((d.count ?? 0) / max) * 100}%`, background: accent }} />
                    </div>
                    <span className="w-10 text-right text-xs tabular-nums">{d.count ?? 0}</span>
                </div>
            ))}
        </div>
    );
}

export function Donut({ value, label, size = 96, stroke = 10, color = BAND_COLORS.primary }) {
    // value is 0..1
    const v = Math.max(0, Math.min(1, value ?? 0));
    const r = (size - stroke) / 2;
    const c = Math.PI * 2 * r;
    return (
        <div className="inline-flex items-center justify-center relative" style={{ width: size, height: size }}>
            <svg width={size} height={size} viewBox={`0 0 ${size} ${size}`}>
                <circle cx={size / 2} cy={size / 2} r={r} fill="none" stroke="currentColor" strokeOpacity={0.1} strokeWidth={stroke} />
                <circle
                    cx={size / 2} cy={size / 2} r={r} fill="none" stroke={color} strokeWidth={stroke}
                    strokeDasharray={`${c * v} ${c * (1 - v)}`}
                    transform={`rotate(-90 ${size / 2} ${size / 2})`}
                    strokeLinecap="round"
                />
            </svg>
            <div className="absolute inset-0 flex flex-col items-center justify-center">
                <div className="text-lg font-semibold tabular-nums">{(v * 100).toFixed(0)}%</div>
                {label && <div className="text-[10px] uppercase tracking-wider text-muted-foreground">{label}</div>}
            </div>
        </div>
    );
}

function EmptyChart({ height, t }) {
    return (
        <div className="flex items-center justify-center text-xs text-muted-foreground" style={{ height }}>
            {t?.chart_no_data || 'No data for this range.'}
        </div>
    );
}
