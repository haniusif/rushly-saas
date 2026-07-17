import * as React from 'react';
import { Head } from '@inertiajs/react';
import {
    TrendingUp, TrendingDown, Minus, ArrowRight,
    Truck, Package, Clock, XCircle, Undo2, AlertTriangle, CheckCircle2,
    DollarSign, Wallet, HandCoins,
    Users, UserCog, Car, MessageCircle,
    Boxes, PackageMinus, PackageX, Store,
    Plus, Upload, CalendarClock, Printer, FileText,
    Zap, Activity, Flame,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { cn } from '@/lib/utils';

// -------------- KPI ---------------

const KPI_ICON = {
    ship_today: Truck, delivered: CheckCircle2, ofd: Truck, pending: Clock,
    cancelled: XCircle, returned: Undo2, failed: AlertTriangle, success_rate: CheckCircle2,
    ship_week: Package, ship_month: Package,
    revenue_today: DollarSign, revenue_month: DollarSign, cod_collected: HandCoins, cod_pending: Wallet,
    busy_drivers: UserCog, idle_drivers: Users, vehicles: Car, open_tickets: MessageCircle,
    skus: Boxes, low_stock: PackageMinus, oos: PackageX, merchants: Store,
};

const TONE = {
    default: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    success: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
    info:    'bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300',
    warning: 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
    danger:  'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300',
};

function fmtNumber(v) {
    const n = Number(v || 0);
    if (Math.abs(n) >= 1e6) return (n / 1e6).toFixed(1) + 'M';
    if (Math.abs(n) >= 1e3) return (n / 1e3).toFixed(1) + 'k';
    return n.toLocaleString();
}

function fmtMoney(v, currency) {
    const n = Number(v || 0);
    return <><span className="text-base text-muted-foreground font-medium me-1">{currency}</span>{fmtNumber(Math.round(n))}</>;
}

function fmtPercent(v) {
    return <>{Number(v || 0).toFixed(1)}<span className="text-lg text-muted-foreground ms-0.5">%</span></>;
}

function DeltaChip({ value, compare, compareLabel }) {
    if (compare === null || compare === undefined) return null;
    const delta = compare > 0 ? ((value - compare) / compare) * 100 : (value > 0 ? 100 : 0);
    const isFlat = Math.abs(delta) < 0.5;
    const isUp = delta > 0;
    const Icon = isFlat ? Minus : (isUp ? TrendingUp : TrendingDown);
    const cls = isFlat ? 'text-muted-foreground'
        : isUp ? 'text-emerald-600 dark:text-emerald-400'
        : 'text-rose-600 dark:text-rose-400';
    return (
        <div className={cn('inline-flex items-center gap-1 text-[11px] font-medium', cls)}>
            <Icon className="h-3 w-3" />
            {isFlat ? '0%' : `${Math.abs(delta).toFixed(0)}%`}
            <span className="text-muted-foreground/70 ms-0.5">vs {compareLabel}</span>
        </div>
    );
}

function KpiCard({ kpi, currency }) {
    const Icon = KPI_ICON[kpi.key] || Package;
    const tone = TONE[kpi.tone] || TONE.default;
    const shown = kpi.format === 'money' ? fmtMoney(kpi.value, currency)
        : kpi.format === 'percent' ? fmtPercent(kpi.value)
        : fmtNumber(kpi.value);
    return (
        <Card className="rounded-xl border border-border overflow-hidden hover:shadow-md transition-shadow">
            <CardContent className="p-4">
                <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0 flex-1">
                        <div className="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                            {kpi.label}
                        </div>
                        <div className="mt-1 text-2xl font-bold tabular-nums tracking-tight text-foreground">
                            {shown}
                        </div>
                        <div className="mt-1">
                            <DeltaChip value={kpi.value} compare={kpi.compare} compareLabel={kpi.compare_label || 'prev'} />
                        </div>
                    </div>
                    <span className={cn('inline-grid place-items-center h-9 w-9 rounded-lg shrink-0', tone)}>
                        <Icon className="h-4 w-4" />
                    </span>
                </div>
            </CardContent>
        </Card>
    );
}

// -------------- Gauge --------------

function Gauge({ value, target, label, hint }) {
    const size = 128;
    const stroke = 12;
    const r = (size - stroke) / 2;
    const c = 2 * Math.PI * r;
    const clamped = Math.max(0, Math.min(100, Number(value ?? 0)));
    const dash = (clamped / 100) * c;
    const arcColor = clamped >= target ? 'stroke-emerald-500' : clamped >= target - 15 ? 'stroke-amber-500' : 'stroke-rose-500';
    return (
        <Card className="rounded-xl border border-border">
            <CardContent className="p-5 flex flex-col items-center text-center">
                <div className="relative" style={{ width: size, height: size }}>
                    <svg width={size} height={size} className="-rotate-90">
                        <circle cx={size/2} cy={size/2} r={r} strokeWidth={stroke} fill="transparent" className="stroke-muted" />
                        <circle cx={size/2} cy={size/2} r={r} strokeWidth={stroke} fill="transparent"
                                strokeLinecap="round"
                                strokeDasharray={`${dash} ${c - dash}`}
                                className={cn(arcColor, 'transition-all duration-500')} />
                    </svg>
                    <div className="absolute inset-0 flex flex-col items-center justify-center">
                        <div className="text-2xl font-bold tabular-nums">{clamped.toFixed(0)}<span className="text-sm text-muted-foreground">%</span></div>
                        <div className="text-[10px] text-muted-foreground">target {target}%</div>
                    </div>
                </div>
                <div className="mt-3 text-sm font-medium">{label}</div>
                <div className="text-[11px] text-muted-foreground mt-0.5 max-w-[180px]">{hint}</div>
            </CardContent>
        </Card>
    );
}

// -------------- Timeline chart (inline SVG multi-line) --------------

function TimelineChart({ data, series }) {
    const w = 900, h = 220, padL = 30, padR = 8, padT = 12, padB = 22;
    const iw = w - padL - padR, ih = h - padT - padB;
    const maxY = Math.max(1, ...data.flatMap(d => series.map(s => d[s.key] ?? 0)));
    const x = (i) => padL + (i / Math.max(1, data.length - 1)) * iw;
    const y = (v) => padT + ih - (v / maxY) * ih;
    const path = (key) => data.map((d, i) => `${i === 0 ? 'M' : 'L'} ${x(i)} ${y(d[key] ?? 0)}`).join(' ');
    const ticks = [0, Math.round(maxY / 2), maxY];
    return (
        <div className="w-full overflow-x-auto">
            <svg viewBox={`0 0 ${w} ${h}`} className="w-full min-w-[600px]" preserveAspectRatio="none">
                {ticks.map((t, i) => (
                    <React.Fragment key={i}>
                        <line x1={padL} x2={w - padR} y1={y(t)} y2={y(t)} className="stroke-muted" strokeWidth="1" strokeDasharray="2 3" />
                        <text x={padL - 4} y={y(t)} textAnchor="end" dominantBaseline="middle" className="fill-muted-foreground text-[10px]">{fmtNumber(t)}</text>
                    </React.Fragment>
                ))}
                {series.map((s) => (
                    <React.Fragment key={s.key}>
                        <path d={path(s.key)} fill="none" stroke={s.color} strokeWidth="2" strokeLinejoin="round" strokeLinecap="round" />
                        {data.map((d, i) => (
                            <circle key={i} cx={x(i)} cy={y(d[s.key] ?? 0)} r="3" fill={s.color}>
                                <title>{`${d.label} · ${s.label}: ${d[s.key] ?? 0}`}</title>
                            </circle>
                        ))}
                    </React.Fragment>
                ))}
                {data.map((d, i) => (
                    i % Math.max(1, Math.floor(data.length / 7)) === 0 && (
                        <text key={i} x={x(i)} y={h - 6} textAnchor="middle" className="fill-muted-foreground text-[10px]">{d.label}</text>
                    )
                ))}
            </svg>
            <div className="flex flex-wrap gap-3 mt-2 justify-center">
                {series.map((s) => (
                    <div key={s.key} className="inline-flex items-center gap-1.5 text-xs">
                        <span className="inline-block w-2.5 h-2.5 rounded-sm" style={{ background: s.color }} />
                        <span className="text-muted-foreground">{s.label}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}

// -------------- Funnel --------------

function Funnel({ steps }) {
    const first = steps[0]?.value ?? 0;
    return (
        <div className="space-y-2">
            {steps.map((s, i) => {
                const prev = i > 0 ? steps[i - 1].value : first;
                const width = first > 0 ? Math.max(6, (s.value / first) * 100) : 6;
                const conv = prev > 0 ? (s.value / prev) * 100 : 0;
                const drop = prev > 0 ? ((prev - s.value) / prev) * 100 : 0;
                return (
                    <div key={s.key}>
                        <div className="flex items-center justify-between text-xs mb-1">
                            <span className="font-medium">{s.label}</span>
                            <span className="text-muted-foreground tabular-nums">
                                {fmtNumber(s.value)}
                                {i > 0 && (
                                    <span className={cn('ms-2', drop > 20 ? 'text-rose-600' : 'text-muted-foreground')}>
                                        · {conv.toFixed(0)}% conv
                                    </span>
                                )}
                            </span>
                        </div>
                        <div className="h-8 bg-muted rounded overflow-hidden">
                            <div
                                className={cn('h-full rounded transition-all duration-500',
                                    i === steps.length - 1 ? 'bg-emerald-500'
                                    : conv < 60 ? 'bg-amber-500'
                                    : 'bg-primary')}
                                style={{ width: `${width}%` }}
                            />
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

// -------------- Alerts --------------

const ALERT_ICON = { Truck, Clock, PackageX, PackageMinus, MessageCircle, Users, AlertTriangle, CheckCircle2 };
const ALERT_TONE = {
    critical: 'bg-rose-50 border-rose-200 text-rose-900 dark:bg-rose-950/30 dark:border-rose-900/50 dark:text-rose-100',
    high:     'bg-amber-50 border-amber-200 text-amber-900 dark:bg-amber-950/30 dark:border-amber-900/50 dark:text-amber-100',
    medium:   'bg-sky-50 border-sky-200 text-sky-900 dark:bg-sky-950/30 dark:border-sky-900/50 dark:text-sky-100',
    low:      'bg-slate-50 border-slate-200 text-slate-900 dark:bg-slate-950/30 dark:border-slate-800 dark:text-slate-100',
    ok:       'bg-emerald-50 border-emerald-200 text-emerald-900 dark:bg-emerald-950/30 dark:border-emerald-900/50 dark:text-emerald-100',
};
const ALERT_LABEL = { critical: 'Critical', high: 'High', medium: 'Medium', low: 'Low', ok: 'Healthy' };

function AlertItem({ a }) {
    const Icon = ALERT_ICON[a.icon] || AlertTriangle;
    return (
        <a
            href={a.link || '#'}
            onClick={(e) => { if (!a.link) e.preventDefault(); }}
            className={cn(
                'flex items-start gap-3 px-4 py-3 rounded-lg border transition-colors',
                ALERT_TONE[a.severity] || ALERT_TONE.low,
                a.link && 'hover:shadow-sm cursor-pointer'
            )}
        >
            <Icon className="h-5 w-5 shrink-0 mt-0.5" />
            <div className="flex-1 min-w-0">
                <span className="text-[10px] font-bold uppercase tracking-wider opacity-70">{ALERT_LABEL[a.severity]}</span>
                <div className="text-sm font-medium mt-0.5">{a.title}</div>
                {a.hint && <div className="text-xs opacity-70 mt-0.5">{a.hint}</div>}
            </div>
            {a.link && <ArrowRight className="h-4 w-4 opacity-60 mt-0.5" />}
        </a>
    );
}

// -------------- Activity feed --------------

const STATUS_TONE = {
    Delivered: 'bg-emerald-500', Created: 'bg-slate-400', 'Picked up': 'bg-sky-500',
    'At warehouse': 'bg-indigo-500', 'At hub': 'bg-indigo-500', 'To hub': 'bg-indigo-500',
    'Out for delivery': 'bg-amber-500', Rescheduled: 'bg-amber-500',
    Returned: 'bg-rose-500', Cancelled: 'bg-rose-500', 'Pickup assigned': 'bg-sky-500',
};

function ActivityFeed({ items, emptyLabel }) {
    if (!items.length) return <div className="text-sm text-muted-foreground text-center py-8">{emptyLabel}</div>;
    return (
        <ol className="relative border-s-2 border-border ms-3 space-y-3">
            {items.map((e) => {
                const tone = STATUS_TONE[e.status_label] || 'bg-slate-400';
                return (
                    <li key={e.id} className="ms-4 relative">
                        <span className={cn('absolute -start-[22px] top-1.5 h-3 w-3 rounded-full ring-4 ring-background', tone)} />
                        <div className="flex items-baseline justify-between gap-2">
                            <div className="min-w-0">
                                <div className="text-sm font-medium">{e.status_label}</div>
                                <div className="text-xs text-muted-foreground truncate">#{e.tracking_id}</div>
                            </div>
                            <div className="text-[11px] text-muted-foreground tabular-nums shrink-0">{e.at_relative}</div>
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}

// -------------- Sub-tables --------------

function TopMerchantsTable({ rows, currency }) {
    if (!rows.length) return <div className="text-sm text-muted-foreground text-center py-8">No merchants yet</div>;
    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm">
                <thead className="bg-muted/40 text-[11px] uppercase tracking-wider text-muted-foreground">
                    <tr><th className="px-5 py-2 text-start">Merchant</th><th className="px-5 py-2 text-end">Ships</th><th className="px-5 py-2 text-end">Success</th><th className="px-5 py-2 text-end">Revenue</th></tr>
                </thead>
                <tbody className="divide-y divide-border">
                    {rows.map((m) => (
                        <tr key={m.id} className="hover:bg-muted/30">
                            <td className="px-5 py-2">
                                <div className="flex items-center gap-2 min-w-0">
                                    {m.logo_url
                                        ? <img src={m.logo_url} alt="" className="w-7 h-7 rounded object-cover bg-muted" />
                                        : <span className="grid w-7 h-7 place-items-center rounded bg-muted text-muted-foreground text-[10px] font-semibold">{m.name.charAt(0).toUpperCase()}</span>}
                                    <span className="truncate font-medium">{m.name}</span>
                                </div>
                            </td>
                            <td className="px-5 py-2 text-end tabular-nums">{fmtNumber(m.shipments)}</td>
                            <td className="px-5 py-2 text-end tabular-nums">
                                <span className={cn('inline-flex px-1.5 py-0.5 rounded text-[11px] font-medium',
                                    m.success >= 90 ? 'bg-emerald-100 text-emerald-700' :
                                    m.success >= 70 ? 'bg-amber-100 text-amber-700' :
                                    'bg-rose-100 text-rose-700')}>
                                    {m.success.toFixed(0)}%
                                </span>
                            </td>
                            <td className="px-5 py-2 text-end tabular-nums text-muted-foreground">
                                <span className="me-1">{currency}</span>{fmtNumber(m.revenue)}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function TopDriversTable({ rows }) {
    if (!rows.length) return <div className="text-sm text-muted-foreground text-center py-8">No drivers yet</div>;
    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm">
                <thead className="bg-muted/40 text-[11px] uppercase tracking-wider text-muted-foreground">
                    <tr><th className="px-5 py-2 text-start">Driver</th><th className="px-5 py-2 text-end">Assigned</th><th className="px-5 py-2 text-end">Delivered</th><th className="px-5 py-2 text-end">Rate</th></tr>
                </thead>
                <tbody className="divide-y divide-border">
                    {rows.map((d) => (
                        <tr key={d.id} className="hover:bg-muted/30">
                            <td className="px-5 py-2">
                                <div className="flex items-center gap-2 min-w-0">
                                    {d.photo_url
                                        ? <img src={d.photo_url} alt="" className="w-7 h-7 rounded-full object-cover bg-muted" />
                                        : <span className="grid w-7 h-7 place-items-center rounded-full bg-muted text-muted-foreground text-[10px] font-semibold">{d.name.charAt(0).toUpperCase()}</span>}
                                    <div className="min-w-0">
                                        <div className="truncate font-medium">{d.name}</div>
                                        {d.flag === 'top' && <div className="text-[10px] text-emerald-600 font-semibold">Top performer</div>}
                                        {d.flag === 'attention' && <div className="text-[10px] text-amber-600 font-semibold">Needs attention</div>}
                                    </div>
                                </div>
                            </td>
                            <td className="px-5 py-2 text-end tabular-nums">{fmtNumber(d.assigned)}</td>
                            <td className="px-5 py-2 text-end tabular-nums">{fmtNumber(d.delivered)}</td>
                            <td className="px-5 py-2 text-end tabular-nums">
                                <span className={cn('inline-flex px-1.5 py-0.5 rounded text-[11px] font-medium',
                                    d.performance >= 90 ? 'bg-emerald-100 text-emerald-700' :
                                    d.performance >= 60 ? 'bg-amber-100 text-amber-700' :
                                    'bg-rose-100 text-rose-700')}>
                                    {d.performance.toFixed(0)}%
                                </span>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function HubTable({ rows }) {
    if (!rows.length) return <div className="text-sm text-muted-foreground text-center py-8">No hubs active today</div>;
    const max = Math.max(1, ...rows.map((r) => r.shipments));
    return (
        <ul className="p-4 space-y-2.5">
            {rows.map((h) => (
                <li key={h.id}>
                    <div className="flex items-center justify-between text-sm mb-1">
                        <span className="font-medium">{h.name}</span>
                        <span className="tabular-nums text-muted-foreground">{h.shipments}</span>
                    </div>
                    <div className="h-1.5 bg-muted rounded overflow-hidden">
                        <div className="h-full bg-primary" style={{ width: `${(h.shipments / max) * 100}%` }} />
                    </div>
                </li>
            ))}
        </ul>
    );
}

// -------------- Page --------------

const KPI_GROUP_ORDER = ['ops', 'volume', 'finance', 'team', 'catalog'];
const QUICK_ICONS = { Plus, Upload, CalendarClock, Printer, AlertTriangle, FileText };

export default function SummaryIndex({
    kpis = [], health = [], timeline = [], funnel = [],
    top_merchants = [], top_deliverymen = [], ofd_by_hub = [],
    alerts = [], activity = [], quick_actions = [],
    currency = '$', meta = {}, t = {},
}) {
    // Hide catalog KPIs when WMS isn't installed on this tenant — otherwise
    // we surface a wall of zeros that hurts more than it helps.
    const visibleKpis = kpis.filter((k) => k.group !== 'catalog' || meta.has_wms);
    const kpiByGroup = KPI_GROUP_ORDER.reduce((acc, g) => {
        acc[g] = visibleKpis.filter((k) => k.group === g);
        return acc;
    }, {});

    return (
        <AdminLayout title={t.title}>
            <Head title={t.title} />
            <p className="text-sm text-muted-foreground mb-5 -mt-2">{t.subtitle}</p>

            {/* Quick actions strip — answer "what should I do next" at first glance. */}
            <div className="flex flex-wrap gap-2 mb-6">
                {quick_actions.map((qa) => {
                    const QI = QUICK_ICONS[qa.icon] || Plus;
                    return (
                        <a key={qa.key} href={qa.href}
                           className="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg border border-border bg-background hover:bg-muted text-sm">
                            <QI className="h-3.5 w-3.5" />
                            {qa.label}
                        </a>
                    );
                })}
            </div>

            {/* KPI grid — grouped by function so the eye scans clusters, not noise. */}
            <div className="space-y-5 mb-6">
                {KPI_GROUP_ORDER.map((g) => (
                    kpiByGroup[g]?.length > 0 && (
                        <section key={g}>
                            <div className="flex items-center gap-2 mb-2">
                                <div className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">{t[`kpi_group_${g}`]}</div>
                                <div className="flex-1 h-px bg-border" />
                            </div>
                            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                                {kpiByGroup[g].map((k) => <KpiCard key={k.key} kpi={k} currency={currency} />)}
                            </div>
                        </section>
                    )
                ))}
            </div>

            {/* Health gauges */}
            <section className="mb-6">
                <div className="flex items-center gap-2 mb-3">
                    <Activity className="h-4 w-4 text-primary" />
                    <h2 className="text-base font-semibold m-0">{t.health_title}</h2>
                </div>
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    {health.map((h) => <Gauge key={h.key} {...h} />)}
                </div>
            </section>

            {/* Timeline (2 cols) + Funnel (1 col) */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                <Card className="lg:col-span-2">
                    <CardContent className="p-5">
                        <div className="flex items-center gap-2 mb-4">
                            <Zap className="h-4 w-4 text-primary" />
                            <h2 className="text-base font-semibold m-0">{t.timeline_title}</h2>
                        </div>
                        <TimelineChart
                            data={timeline}
                            series={[
                                { key: 'created',   label: 'Created',   color: '#C1276D' },
                                { key: 'delivered', label: 'Delivered', color: '#22C55E' },
                                { key: 'cancelled', label: 'Cancelled', color: '#EF4444' },
                                { key: 'returned',  label: 'Returned',  color: '#F59E0B' },
                            ]}
                        />
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-5">
                        <div className="flex items-center gap-2 mb-4">
                            <Flame className="h-4 w-4 text-primary" />
                            <h2 className="text-base font-semibold m-0">{t.funnel_title}</h2>
                        </div>
                        <Funnel steps={funnel} />
                    </CardContent>
                </Card>
            </div>

            {/* Merchants + Drivers */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
                <Card>
                    <CardContent className="p-0">
                        <div className="px-5 py-4 border-b border-border">
                            <h2 className="text-base font-semibold m-0">{t.top_merchants}</h2>
                        </div>
                        <TopMerchantsTable rows={top_merchants} currency={currency} />
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-0">
                        <div className="px-5 py-4 border-b border-border">
                            <h2 className="text-base font-semibold m-0">{t.top_drivers}</h2>
                        </div>
                        <TopDriversTable rows={top_deliverymen} />
                    </CardContent>
                </Card>
            </div>

            {/* Hub load + Alerts + Activity */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <Card>
                    <CardContent className="p-0">
                        <div className="px-5 py-4 border-b border-border">
                            <h2 className="text-base font-semibold m-0">{t.ofd_by_hub}</h2>
                        </div>
                        <HubTable rows={ofd_by_hub} />
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-5">
                        <div className="flex items-center gap-2 mb-3">
                            <AlertTriangle className="h-4 w-4 text-amber-500" />
                            <h2 className="text-base font-semibold m-0">{t.alerts}</h2>
                        </div>
                        <div className="space-y-2">
                            {alerts.map((a, i) => <AlertItem key={i} a={a} />)}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-5">
                        <div className="flex items-center gap-2 mb-3">
                            <Activity className="h-4 w-4 text-primary" />
                            <h2 className="text-base font-semibold m-0">{t.activity}</h2>
                        </div>
                        <ActivityFeed items={activity} emptyLabel={t.no_activity} />
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
