import * as React from 'react';
import { Head } from '@inertiajs/react';
import {
    Package, Truck, CheckCircle2, Clock,
    Bike, LineChart, Store, Trophy, Warehouse, MapPin, PieChart, Gauge,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { cn } from '@/lib/utils';

// -------------- KPI --------------

function KpiCard({ icon: Icon, label, value, tone = 'primary', hint, href }) {
    const tones = {
        primary: 'from-primary/10 to-primary/5  text-primary',
        success: 'from-emerald-100 to-emerald-50 text-emerald-700 dark:from-emerald-950/40 dark:to-emerald-950/10 dark:text-emerald-300',
        warning: 'from-amber-100 to-amber-50 text-amber-700 dark:from-amber-950/40 dark:to-amber-950/10 dark:text-amber-200',
        info:    'from-sky-100 to-sky-50 text-sky-700 dark:from-sky-950/40 dark:to-sky-950/10 dark:text-sky-300',
    };
    const inner = (
        <CardContent className="p-5">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <div className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{label}</div>
                    <div className="mt-1.5 text-3xl font-bold tabular-nums tracking-tight text-foreground">
                        {Number(value ?? 0).toLocaleString()}
                    </div>
                    {hint && <div className="mt-1 text-xs text-muted-foreground">{hint}</div>}
                </div>
                <span className={cn('inline-grid place-items-center h-10 w-10 rounded-xl bg-gradient-to-br', tones[tone])}>
                    <Icon className="h-5 w-5" />
                </span>
            </div>
        </CardContent>
    );
    // Render as an anchor when there's a drill-in URL; the whole card
    // becomes a click target. Keeps the reader's mental model simple:
    // number = link → filtered list.
    if (href) {
        return (
            <a href={href} className="block group no-underline text-inherit">
                <Card className="rounded-xl shadow-sm border border-border overflow-hidden transition-all hover:shadow-md hover:-translate-y-0.5">
                    {inner}
                </Card>
            </a>
        );
    }
    return (
        <Card className="rounded-xl shadow-sm border border-border overflow-hidden">
            {inner}
        </Card>
    );
}

// -------------- 7-day area chart (inline SVG) --------------

/**
 * Smooth two-series area chart: `created` renders as a filled area with a
 * line stroke, `delivered` as a line-only overlay so the reader can see
 * both without one series hiding the other. Pure SVG — no chart lib.
 */
function AreaChart({ data = [], tCreated, tDelivered }) {
    if (!data.length) return null;
    const w = 640, h = 200, padL = 30, padR = 8, padT = 12, padB = 24;
    const iw = w - padL - padR, ih = h - padT - padB;
    const maxY = Math.max(1, ...data.map(d => Math.max(d.created, d.delivered)));

    const x = (i) => padL + (i / Math.max(1, data.length - 1)) * iw;
    const y = (v) => padT + ih - (v / maxY) * ih;

    const line = (key) => data.map((d, i) => `${i === 0 ? 'M' : 'L'} ${x(i)} ${y(d[key] ?? 0)}`).join(' ');
    const area = () => {
        const path = data.map((d, i) => `${i === 0 ? 'M' : 'L'} ${x(i)} ${y(d.created ?? 0)}`).join(' ');
        return `${path} L ${x(data.length - 1)} ${y(0)} L ${x(0)} ${y(0)} Z`;
    };
    const ticks = [0, Math.round(maxY / 2), maxY];

    return (
        <div className="w-full">
            <svg viewBox={`0 0 ${w} ${h}`} className="w-full h-56" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="summ-area" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="0%"  stopColor="var(--primary, #C1276D)" stopOpacity="0.35" />
                        <stop offset="100%" stopColor="var(--primary, #C1276D)" stopOpacity="0.02" />
                    </linearGradient>
                </defs>
                {ticks.map((tv, i) => (
                    <React.Fragment key={i}>
                        <line x1={padL} x2={w - padR} y1={y(tv)} y2={y(tv)} className="stroke-muted" strokeWidth="1" strokeDasharray="2 3" />
                        <text x={padL - 4} y={y(tv)} textAnchor="end" dominantBaseline="middle" className="fill-muted-foreground text-[10px]">{tv}</text>
                    </React.Fragment>
                ))}
                <path d={area()} fill="url(#summ-area)" />
                <path d={line('created')}   fill="none" strokeWidth="2.2" strokeLinejoin="round" strokeLinecap="round" style={{ stroke: 'var(--primary, #C1276D)' }} />
                <path d={line('delivered')} fill="none" strokeWidth="2.2" strokeLinejoin="round" strokeLinecap="round" strokeDasharray="4 3" stroke="#22C55E" />
                {data.map((d, i) => (
                    <React.Fragment key={i}>
                        <circle cx={x(i)} cy={y(d.created ?? 0)}   r="3" style={{ fill: 'var(--primary, #C1276D)' }}>
                            <title>{`${d.label} · ${tCreated}: ${d.created}`}</title>
                        </circle>
                        <circle cx={x(i)} cy={y(d.delivered ?? 0)} r="3" fill="#22C55E">
                            <title>{`${d.label} · ${tDelivered}: ${d.delivered}`}</title>
                        </circle>
                    </React.Fragment>
                ))}
                {data.map((d, i) => (
                    <text key={i} x={x(i)} y={h - 6} textAnchor="middle" className="fill-muted-foreground text-[10px]">{d.label}</text>
                ))}
            </svg>
            <div className="flex items-center justify-center gap-4 text-[11px] mt-1">
                <span className="inline-flex items-center gap-1.5">
                    <span className="inline-block w-3 h-0.5 bg-primary" /> {tCreated}
                </span>
                <span className="inline-flex items-center gap-1.5">
                    <span className="inline-block w-3 h-0.5" style={{ background: '#22C55E' }} /> {tDelivered}
                </span>
            </div>
        </div>
    );
}

// -------------- Status donut (inline SVG) --------------

const STATUS_COLOR = {
    delivered: '#22C55E',
    ofd:       '#F59E0B',
    pending:   '#64748B',
    returned:  '#EF4444',
    cancelled: '#B91C1C',
    other:     '#3B82F6',
};

function StatusDonut({ items = [], t = {} }) {
    const total = items.reduce((s, x) => s + Number(x.value || 0), 0);
    const size = 176, stroke = 22, r = (size - stroke) / 2, c = 2 * Math.PI * r;
    if (total === 0) {
        return (
            <div className="flex flex-col items-center py-6 text-center">
                <div className="relative" style={{ width: size, height: size }}>
                    <svg width={size} height={size} className="-rotate-90">
                        <circle cx={size/2} cy={size/2} r={r} strokeWidth={stroke} fill="transparent" className="stroke-muted" />
                    </svg>
                </div>
                <div className="text-sm text-muted-foreground mt-3">{t.status_donut_empty}</div>
            </div>
        );
    }
    // Cumulative dashoffsets sweep the circle clockwise.
    let cumulative = 0;
    return (
        <div className="flex flex-col md:flex-row items-center gap-5">
            <div className="relative" style={{ width: size, height: size }}>
                <svg width={size} height={size} className="-rotate-90">
                    <circle cx={size/2} cy={size/2} r={r} strokeWidth={stroke} fill="transparent" className="stroke-muted" />
                    {items.map((it, i) => {
                        const frac = Number(it.value) / total;
                        if (frac <= 0) return null;
                        const dash = frac * c;
                        const offset = c - cumulative;
                        cumulative += dash;
                        return (
                            <circle
                                key={i}
                                cx={size/2} cy={size/2} r={r}
                                fill="transparent" strokeWidth={stroke}
                                strokeDasharray={`${dash} ${c - dash}`}
                                strokeDashoffset={offset}
                                style={{ stroke: STATUS_COLOR[it.key] || '#94A3B8', transition: 'stroke-dasharray .5s' }}
                            >
                                <title>{`${it.label}: ${it.value}`}</title>
                            </circle>
                        );
                    })}
                </svg>
                <div className="absolute inset-0 flex flex-col items-center justify-center">
                    <div className="text-2xl font-bold tabular-nums">{total.toLocaleString()}</div>
                    <div className="text-[10px] text-muted-foreground uppercase tracking-wider">today</div>
                </div>
            </div>
            <ul className="flex-1 space-y-1.5 text-sm min-w-0 w-full">
                {items.filter((x) => Number(x.value) > 0).map((it) => {
                    const pct = ((Number(it.value) / total) * 100).toFixed(0);
                    const key = it.key;
                    // Map server label keys to i18n strings; falls back to
                    // the label the server sent.
                    const label = (t[`status_${key}`]) || it.label;
                    const Row = it.url ? 'a' : 'li';
                    const rowProps = it.url ? { href: it.url } : {};
                    return (
                        <Row
                            key={key}
                            {...rowProps}
                            className={cn(
                                'flex items-center gap-2 py-1 px-1 -mx-1 rounded transition-colors',
                                it.url && 'hover:bg-muted/60 no-underline text-inherit cursor-pointer',
                            )}
                        >
                            <span className="inline-block w-2.5 h-2.5 rounded-sm shrink-0" style={{ background: STATUS_COLOR[key] || '#94A3B8' }} />
                            <span className="flex-1 truncate">{label}</span>
                            <span className="tabular-nums font-medium">{Number(it.value).toLocaleString()}</span>
                            <span className="text-[11px] text-muted-foreground w-8 text-end tabular-nums">{pct}%</span>
                        </Row>
                    );
                })}
            </ul>
        </div>
    );
}

// -------------- Weekly success ring --------------

function WeekRing({ week, t = {} }) {
    const size = 128, stroke = 12, r = (size - stroke) / 2, c = 2 * Math.PI * r;
    const pct = Math.max(0, Math.min(100, Number(week?.success_rate ?? 0)));
    const dash = (pct / 100) * c;
    const arc = pct >= 90 ? 'stroke-emerald-500'
        : pct >= 70 ? 'stroke-amber-500'
        : 'stroke-rose-500';
    return (
        <div className="flex flex-col items-center text-center">
            <div className="relative" style={{ width: size, height: size }}>
                <svg width={size} height={size} className="-rotate-90">
                    <circle cx={size/2} cy={size/2} r={r} strokeWidth={stroke} fill="transparent" className="stroke-muted" />
                    <circle cx={size/2} cy={size/2} r={r} strokeWidth={stroke} fill="transparent"
                            strokeLinecap="round"
                            strokeDasharray={`${dash} ${c - dash}`}
                            className={cn(arc, 'transition-all duration-500')} />
                </svg>
                <div className="absolute inset-0 flex flex-col items-center justify-center">
                    <div className="text-2xl font-bold tabular-nums">{pct.toFixed(0)}<span className="text-sm text-muted-foreground">%</span></div>
                </div>
            </div>
            <div className="mt-3 text-sm font-medium">{t.week_ring_title}</div>
            <div className="text-[11px] text-muted-foreground mt-0.5">
                {Number(week?.delivered ?? 0).toLocaleString()} {t.week_ring_delivered} · {Number(week?.created ?? 0).toLocaleString()} {t.week_ring_created}
            </div>
        </div>
    );
}

// -------------- Shared leaderboard card (unchanged) --------------

function TopByShipmentsCard({ items = [], icon: RowIcon = Store, imageKey, title, subtitle, colName, colQty, empty }) {
    const max = Math.max(1, ...items.map(m => m.shipments));
    return (
        <Card className="rounded-xl shadow-sm border border-border">
            <CardContent className="p-0">
                <div className="px-5 pt-5 pb-3 flex items-start gap-2">
                    <Trophy className="h-4 w-4 text-primary mt-0.5" />
                    <div>
                        <div className="text-sm font-semibold">{title}</div>
                        {subtitle && <div className="text-[11px] text-muted-foreground mt-0.5">{subtitle}</div>}
                    </div>
                </div>
                {items.length === 0 ? (
                    <div className="px-5 pb-6 text-sm text-muted-foreground">{empty}</div>
                ) : (
                    <div className="divide-y divide-border">
                        <div className="grid grid-cols-[auto_1fr_auto_auto] items-center gap-3 px-5 py-2 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                            <span className="w-6 text-center">#</span>
                            <span>{colName}</span>
                            <span className="hidden xl:block w-16" />
                            <span className="justify-self-end">{colQty}</span>
                        </div>
                        {items.map((m, i) => {
                            const pct = Math.max(2, Math.round((m.shipments / max) * 100));
                            // Each row optionally carries its own drill-in
                            // URL from the server; when set, wrap the row
                            // in an <a> so the whole row is clickable.
                            const Row = m.url ? 'a' : 'div';
                            const rowProps = m.url ? { href: m.url } : {};
                            return (
                                <Row
                                    key={m.id}
                                    {...rowProps}
                                    className={cn(
                                        'grid grid-cols-[auto_1fr_auto_auto] items-center gap-3 px-5 py-2.5 hover:bg-muted/30 transition-colors',
                                        m.url && 'no-underline text-inherit',
                                    )}
                                >
                                    <span className="w-6 text-center text-xs font-semibold text-muted-foreground tabular-nums">
                                        {i + 1}
                                    </span>
                                    <div className="flex items-center gap-2 min-w-0">
                                        {imageKey && m[imageKey] ? (
                                            <img
                                                src={m[imageKey]}
                                                alt=""
                                                loading="lazy"
                                                className="h-7 w-7 rounded-md object-cover shrink-0 bg-muted"
                                            />
                                        ) : (
                                            <span className="inline-grid place-items-center h-7 w-7 rounded-md bg-primary/10 text-primary shrink-0">
                                                <RowIcon className="h-3.5 w-3.5" />
                                            </span>
                                        )}
                                        <span className="text-sm font-medium truncate">{m.name}</span>
                                    </div>
                                    <div className="hidden xl:block w-16 h-1.5 rounded-full bg-muted overflow-hidden">
                                        <div className="h-full bg-primary" style={{ width: `${pct}%` }} />
                                    </div>
                                    <span className="justify-self-end text-sm tabular-nums font-medium">{Number(m.shipments).toLocaleString()}</span>
                                </Row>
                            );
                        })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function DeliverymenPerformanceCard({ items = [], t = {} }) {
    const bandTone = (pct) => {
        if (pct >= 85) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300';
        if (pct >= 60) return 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-200';
        return 'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300';
    };
    return (
        <Card className="rounded-xl shadow-sm border border-border">
            <CardContent className="p-0">
                <div className="px-5 pt-5 pb-3 flex items-start gap-2">
                    <Trophy className="h-4 w-4 text-primary mt-0.5" />
                    <div>
                        <div className="text-sm font-semibold">{t.top_deliverymen_title}</div>
                        {t.current_month && (
                            <div className="text-[11px] text-muted-foreground mt-0.5">{t.current_month}</div>
                        )}
                    </div>
                </div>
                {items.length === 0 ? (
                    <div className="px-5 pb-6 text-sm text-muted-foreground">{t.top_deliverymen_empty}</div>
                ) : (
                    <div className="divide-y divide-border">
                        <div className="grid grid-cols-[auto_1fr_repeat(3,auto)] items-center gap-3 px-5 py-2 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                            <span className="w-6 text-center">#</span>
                            <span>{t.top_deliverymen_col_name}</span>
                            <span className="justify-self-end w-16">{t.top_deliverymen_col_assigned}</span>
                            <span className="justify-self-end w-16">{t.top_deliverymen_col_delivered}</span>
                            <span className="justify-self-end w-20">{t.top_deliverymen_col_performance}</span>
                        </div>
                        {items.map((d, i) => {
                            const Row = d.url ? 'a' : 'div';
                            const rowProps = d.url ? { href: d.url } : {};
                            return (
                                <Row
                                    key={d.id}
                                    {...rowProps}
                                    className={cn(
                                        'grid grid-cols-[auto_1fr_repeat(3,auto)] items-center gap-3 px-5 py-2.5 hover:bg-muted/30 transition-colors',
                                        d.url && 'no-underline text-inherit',
                                    )}
                                >
                                    <span className="w-6 text-center text-xs font-semibold text-muted-foreground tabular-nums">
                                        {i + 1}
                                    </span>
                                    <div className="flex items-center gap-2 min-w-0">
                                        {d.photo_url ? (
                                            <img src={d.photo_url} alt="" loading="lazy" className="h-7 w-7 rounded-full object-cover shrink-0 bg-muted" />
                                        ) : (
                                            <span className="inline-grid place-items-center h-7 w-7 rounded-full bg-primary/10 text-primary shrink-0">
                                                <Bike className="h-3.5 w-3.5" />
                                            </span>
                                        )}
                                        <span className="text-sm font-medium truncate">{d.name}</span>
                                    </div>
                                    <span className="justify-self-end w-16 text-sm tabular-nums text-muted-foreground">
                                        {Number(d.assigned).toLocaleString()}
                                    </span>
                                    <span className="justify-self-end w-16 text-sm tabular-nums font-medium">
                                        {Number(d.delivered).toLocaleString()}
                                    </span>
                                    <span className="justify-self-end w-20 flex justify-end">
                                        <span className={cn('inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold tabular-nums', bandTone(d.performance))}>
                                            {Number(d.performance).toFixed(1)}%
                                        </span>
                                    </span>
                                </Row>
                            );
                        })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

// -------------- Page --------------

export default function Index({
    kpis = {},
    kpi_urls = {},
    trend = [],
    status_breakdown = [],
    week_summary = {},
    top_merchants = [],
    top_hubs = [],
    top_cities = [],
    ofd_by_hub = [],
    top_deliverymen = [],
    t = {},
}) {
    return (
        <AdminLayout title={t.title} breadcrumbs={[]}>
            <Head title={t.title} />

            {/* Row 1 — KPIs (each card links to the corresponding filtered list) */}
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                <KpiCard icon={Package}      tone="primary" label={t.kpi_today}     value={kpis.today_shipments} href={kpi_urls.today_shipments} />
                <KpiCard icon={Truck}        tone="info"    label={t.kpi_ofd}       value={kpis.ofd}             href={kpi_urls.ofd} />
                <KpiCard icon={CheckCircle2} tone="success" label={t.kpi_delivered} value={kpis.delivered_today} href={kpi_urls.delivered_today} />
                <KpiCard icon={Clock}        tone="warning" label={t.kpi_pending}   value={kpis.pending}         href={kpi_urls.pending} />
            </div>

            {/* Row 2 — Charts: 7-day area (wide) + Status donut + Week success ring */}
            <div className="grid gap-6 mb-6 lg:grid-cols-3">
                <Card className="rounded-xl shadow-sm border border-border lg:col-span-2">
                    <CardContent className="p-5">
                        <div className="mb-4 flex items-center gap-2">
                            <LineChart className="h-4 w-4 text-primary" />
                            <div className="text-sm font-semibold">{t.seven_day_title}</div>
                        </div>
                        <AreaChart data={trend} tCreated={t.legend_created} tDelivered={t.legend_delivered} />
                    </CardContent>
                </Card>
                <Card className="rounded-xl shadow-sm border border-border">
                    <CardContent className="p-5">
                        <div className="mb-4 flex items-start gap-2">
                            <PieChart className="h-4 w-4 text-primary mt-0.5" />
                            <div>
                                <div className="text-sm font-semibold">{t.status_donut_title}</div>
                                {t.status_donut_subtitle && (
                                    <div className="text-[11px] text-muted-foreground mt-0.5">{t.status_donut_subtitle}</div>
                                )}
                            </div>
                        </div>
                        <StatusDonut items={status_breakdown} t={t} />
                    </CardContent>
                </Card>
            </div>

            {/* Row 3 — Weekly success ring + Top merchants + Deliveryman performance */}
            <div className="grid gap-6 mb-6 lg:grid-cols-4">
                <Card className="rounded-xl shadow-sm border border-border lg:col-span-1">
                    <CardContent className="p-5">
                        <div className="mb-3 flex items-start gap-2">
                            <Gauge className="h-4 w-4 text-primary mt-0.5" />
                            <div>
                                <div className="text-sm font-semibold">{t.week_ring_title}</div>
                                {t.week_ring_subtitle && (
                                    <div className="text-[11px] text-muted-foreground mt-0.5">{t.week_ring_subtitle}</div>
                                )}
                            </div>
                        </div>
                        <WeekRing week={week_summary} t={t} />
                    </CardContent>
                </Card>
                <div className="lg:col-span-3 grid gap-6 md:grid-cols-2">
                    <TopByShipmentsCard
                        items={top_merchants}
                        icon={Store}
                        imageKey="logo_url"
                        title={t.top_merchants_title}
                        subtitle={t.current_month}
                        colName={t.top_merchants_col_name}
                        colQty={t.top_merchants_col_qty}
                        empty={t.top_merchants_empty}
                    />
                    <DeliverymenPerformanceCard items={top_deliverymen} t={t} />
                </div>
            </div>

            {/* Row 4 — Hubs, Cities, OFD by hub side-by-side */}
            <div className="grid gap-6 lg:grid-cols-3">
                <TopByShipmentsCard
                    items={top_hubs}
                    icon={Warehouse}
                    title={t.top_hubs_title}
                    subtitle={t.current_month}
                    colName={t.top_hubs_col_name}
                    colQty={t.top_merchants_col_qty}
                    empty={t.top_hubs_empty}
                />
                <TopByShipmentsCard
                    items={top_cities}
                    icon={MapPin}
                    title={t.top_cities_title}
                    subtitle={t.current_month}
                    colName={t.top_cities_col_name}
                    colQty={t.top_merchants_col_qty}
                    empty={t.top_cities_empty}
                />
                <TopByShipmentsCard
                    items={ofd_by_hub}
                    icon={Warehouse}
                    title={t.ofd_by_hub_title}
                    subtitle={t.ofd_by_hub_subtitle}
                    colName={t.ofd_by_hub_col_name}
                    colQty={t.ofd_by_hub_col_qty}
                    empty={t.ofd_by_hub_empty}
                />
            </div>
        </AdminLayout>
    );
}
