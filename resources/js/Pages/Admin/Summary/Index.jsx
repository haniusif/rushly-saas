import * as React from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    Package, Truck, CheckCircle2, Clock, ArrowUpRight,
    Users, Bike, Wallet, LineChart, ExternalLink, Store, Trophy, Warehouse, MapPin, Map,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { cn } from '@/lib/utils';

function KpiCard({ icon: Icon, label, value, tone = 'primary', hint }) {
    const tones = {
        primary: 'from-primary/10 to-primary/5  text-primary',
        success: 'from-emerald-100 to-emerald-50 text-emerald-700 dark:from-emerald-950/40 dark:to-emerald-950/10 dark:text-emerald-300',
        warning: 'from-amber-100 to-amber-50 text-amber-700 dark:from-amber-950/40 dark:to-amber-950/10 dark:text-amber-200',
        info:    'from-sky-100 to-sky-50 text-sky-700 dark:from-sky-950/40 dark:to-sky-950/10 dark:text-sky-300',
    };
    return (
        <Card className="rounded-xl shadow-sm border border-border overflow-hidden">
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
        </Card>
    );
}

function TrendChart({ data, tCreated, tDelivered }) {
    if (!data?.length) return null;
    const maxCreated   = Math.max(1, ...data.map(d => d.created));
    const maxDelivered = Math.max(1, ...data.map(d => d.delivered));
    const max = Math.max(maxCreated, maxDelivered);
    return (
        <div className="space-y-3">
            <div className="flex items-end justify-between gap-3 h-40">
                {data.map((d) => {
                    const hC = Math.max(4, Math.round((d.created / max) * 100));
                    const hD = Math.max(4, Math.round((d.delivered / max) * 100));
                    return (
                        <div key={d.iso} className="flex-1 flex flex-col items-center gap-1.5">
                            <div className="flex items-end justify-center gap-1 h-full w-full">
                                <div
                                    title={`${tCreated}: ${d.created}`}
                                    className="w-full max-w-[10px] rounded-t bg-primary/70 transition-all hover:bg-primary"
                                    style={{ height: `${hC}%` }}
                                />
                                <div
                                    title={`${tDelivered}: ${d.delivered}`}
                                    className="w-full max-w-[10px] rounded-t bg-emerald-500/70 transition-all hover:bg-emerald-500"
                                    style={{ height: `${hD}%` }}
                                />
                            </div>
                            <div className="text-[10px] font-medium text-muted-foreground">{d.label}</div>
                        </div>
                    );
                })}
            </div>
            <div className="flex items-center justify-center gap-4 text-[11px]">
                <span className="inline-flex items-center gap-1.5">
                    <span className="h-2 w-2 rounded-sm bg-primary" /> {tCreated}
                </span>
                <span className="inline-flex items-center gap-1.5">
                    <span className="h-2 w-2 rounded-sm bg-emerald-500" /> {tDelivered}
                </span>
            </div>
        </div>
    );
}

function StatusPill({ status, label }) {
    // Map status buckets to color tones for the pill.
    const groups = {
        delivered: [9],                                    // DELIVERED
        pending:   [1],                                    // PENDING
        transit:   [2, 4, 5, 6, 7, 19, 34],                // in-motion states
        returned:  [11, 13, 30],                           // return-y states
        partial:   [32],                                   // partial
    };
    let tone = 'muted';
    if (groups.delivered.includes(status)) tone = 'success';
    else if (groups.pending.includes(status)) tone = 'warning';
    else if (groups.transit.includes(status)) tone = 'info';
    else if (groups.returned.includes(status)) tone = 'danger';
    else if (groups.partial.includes(status)) tone = 'info';

    const tones = {
        muted:   'bg-muted text-muted-foreground',
        success: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
        warning: 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-200',
        info:    'bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300',
        danger:  'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300',
    };
    return (
        <span className={cn('inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium', tones[tone])}>
            {label}
        </span>
    );
}

/**
 * Generic "Top N by shipments" leaderboard card. Reused by the Top
 * merchants and Top hubs cards on /summary. Callers pass the trophy-header
 * title, per-row icon, translated column headers, and the list.
 */
function TopByShipmentsCard({ items = [], icon: RowIcon = Store, title, colName, colQty, empty }) {
    const max = Math.max(1, ...items.map(m => m.shipments));
    return (
        <Card className="rounded-xl shadow-sm border border-border">
            <CardContent className="p-0">
                <div className="px-5 pt-5 pb-3 flex items-center gap-2">
                    <Trophy className="h-4 w-4 text-primary" />
                    <div className="text-sm font-semibold">{title}</div>
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
                            return (
                                <div key={m.id} className="grid grid-cols-[auto_1fr_auto_auto] items-center gap-3 px-5 py-2.5 hover:bg-muted/30 transition-colors">
                                    <span className="w-6 text-center text-xs font-semibold text-muted-foreground tabular-nums">
                                        {i + 1}
                                    </span>
                                    <div className="flex items-center gap-2 min-w-0">
                                        <span className="inline-grid place-items-center h-7 w-7 rounded-md bg-primary/10 text-primary shrink-0">
                                            <RowIcon className="h-3.5 w-3.5" />
                                        </span>
                                        <span className="text-sm font-medium truncate">{m.name}</span>
                                    </div>
                                    <div className="hidden xl:block w-16 h-1.5 rounded-full bg-muted overflow-hidden">
                                        <div
                                            className="h-full bg-primary/70"
                                            style={{ width: `${pct}%` }}
                                        />
                                    </div>
                                    <span className="justify-self-end text-sm font-semibold tabular-nums">
                                        {Number(m.shipments).toLocaleString()}
                                    </span>
                                </div>
                            );
                        })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

/**
 * Deliveryman performance leaderboard. Different shape from the plain
 * TopByShipmentsCard: three numeric columns (assigned, delivered, %)
 * plus a color-coded performance chip.
 */
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
                        {t.top_deliverymen_subtitle && (
                            <div className="text-[11px] text-muted-foreground mt-0.5">{t.top_deliverymen_subtitle}</div>
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
                        {items.map((d, i) => (
                            <div key={d.id} className="grid grid-cols-[auto_1fr_repeat(3,auto)] items-center gap-3 px-5 py-2.5 hover:bg-muted/30 transition-colors">
                                <span className="w-6 text-center text-xs font-semibold text-muted-foreground tabular-nums">
                                    {i + 1}
                                </span>
                                <div className="flex items-center gap-2 min-w-0">
                                    <span className="inline-grid place-items-center h-7 w-7 rounded-md bg-primary/10 text-primary shrink-0">
                                        <Bike className="h-3.5 w-3.5" />
                                    </span>
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
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

export default function Index({
    greeting_name = '',
    currency = '',
    kpis = {},
    trend = [],
    recent = [],
    totals = {},
    top_merchants = [],
    top_hubs = [],
    top_cities = [],
    top_areas = [],
    top_deliverymen = [],
    urls = {},
    t = {},
}) {
    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title]}>
            <Head title={t.title} />

            {/* Header */}
            <div className="mb-6 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{t.greeting}</div>
                    <h1 className="mt-1 text-2xl font-bold tracking-tight">
                        {greeting_name ? `${t.greeting}, ${greeting_name.split(' ')[0]}` : t.greeting}
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">{t.subtitle}</p>
                </div>
                <a
                    href={urls.full_dashboard}
                    className="inline-flex h-9 items-center gap-1.5 rounded-lg border border-input bg-background px-3 text-xs font-medium hover:bg-accent transition-colors"
                >
                    <ExternalLink className="h-3.5 w-3.5" />
                    {t.full_dashboard}
                </a>
            </div>

            {/* KPI row */}
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                <KpiCard icon={Package}      tone="primary" label={t.kpi_today}      value={kpis.today_shipments} />
                <KpiCard icon={Truck}        tone="info"    label={t.kpi_in_transit} value={kpis.in_transit} />
                <KpiCard icon={CheckCircle2} tone="success" label={t.kpi_delivered}  value={kpis.delivered_today} />
                <KpiCard icon={Clock}        tone="warning" label={t.kpi_pending}    value={kpis.pending} />
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                {/* Left column: trend + top merchants (side-by-side) + recent */}
                <div className="lg:col-span-2 space-y-6">
                    {/* 50/50 row: 7-day trend + Top merchants */}
                    <div className="grid gap-6 md:grid-cols-2">
                        <Card className="rounded-xl shadow-sm border border-border">
                            <CardContent className="p-5">
                                <div className="mb-4 flex items-center gap-2">
                                    <LineChart className="h-4 w-4 text-primary" />
                                    <div className="text-sm font-semibold">{t.seven_day_title}</div>
                                </div>
                                <TrendChart data={trend} tCreated={t.legend_created} tDelivered={t.legend_delivered} />
                            </CardContent>
                        </Card>

                        <TopByShipmentsCard
                            items={top_merchants}
                            icon={Store}
                            title={t.top_merchants_title}
                            colName={t.top_merchants_col_name}
                            colQty={t.top_merchants_col_qty}
                            empty={t.top_merchants_empty}
                        />
                    </div>

                    {/* Top hubs — full width of the left column */}
                    <TopByShipmentsCard
                        items={top_hubs}
                        icon={Warehouse}
                        title={t.top_hubs_title}
                        colName={t.top_hubs_col_name}
                        colQty={t.top_merchants_col_qty}
                        empty={t.top_hubs_empty}
                    />

                    {/* 50/50 row: Top cities + Top areas */}
                    <div className="grid gap-6 md:grid-cols-2">
                        <TopByShipmentsCard
                            items={top_cities}
                            icon={MapPin}
                            title={t.top_cities_title}
                            colName={t.top_cities_col_name}
                            colQty={t.top_merchants_col_qty}
                            empty={t.top_cities_empty}
                        />
                        <TopByShipmentsCard
                            items={top_areas}
                            icon={Map}
                            title={t.top_areas_title}
                            colName={t.top_areas_col_name}
                            colQty={t.top_merchants_col_qty}
                            empty={t.top_areas_empty}
                        />
                    </div>

                    {/* Deliveryman performance — full width, 3 numeric cols */}
                    <DeliverymenPerformanceCard items={top_deliverymen} t={t} />

                    <Card className="rounded-xl shadow-sm border border-border">
                        <CardContent className="p-0">
                            <div className="px-5 pt-5 pb-3 flex items-center justify-between">
                                <div className="text-sm font-semibold">{t.recent_title}</div>
                                <a href={urls.list_parcels} className="text-xs text-primary hover:underline inline-flex items-center gap-1">
                                    {t.list_parcels} <ArrowUpRight className="h-3 w-3" />
                                </a>
                            </div>
                            {recent.length === 0 ? (
                                <div className="px-5 pb-6 text-sm text-muted-foreground">{t.no_recent}</div>
                            ) : (
                                <div className="divide-y divide-border">
                                    {recent.map((p) => (
                                        <div key={p.id} className="flex items-center gap-3 px-5 py-3 hover:bg-muted/30 transition-colors">
                                            <span className="inline-grid place-items-center h-8 w-8 rounded-lg bg-primary/10 text-primary shrink-0">
                                                <Package className="h-4 w-4" />
                                            </span>
                                            <div className="flex-1 min-w-0">
                                                <div className="text-sm font-medium truncate">
                                                    {p.tracking_id || `#${p.id}`}
                                                    {p.customer_name && <span className="text-muted-foreground font-normal"> · {p.customer_name}</span>}
                                                </div>
                                                <div className="text-[11px] text-muted-foreground truncate">
                                                    {p.merchant && `${p.merchant} · `}{p.created_at}
                                                </div>
                                            </div>
                                            <StatusPill status={p.status} label={p.status_label} />
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Right column: roster tile */}
                <div className="space-y-6">
                    <Card className="rounded-xl shadow-sm border border-border">
                        <CardContent className="p-5">
                            <div className="mb-3 text-sm font-semibold">{t.roster_title}</div>
                            <dl className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <dt className="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Users className="h-4 w-4" /> {t.roster_merchants}
                                    </dt>
                                    <dd className="text-sm font-semibold tabular-nums">{Number(totals.merchants ?? 0).toLocaleString()}</dd>
                                </div>
                                <div className="flex items-center justify-between">
                                    <dt className="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Bike className="h-4 w-4" /> {t.roster_deliverymen}
                                    </dt>
                                    <dd className="text-sm font-semibold tabular-nums">{Number(totals.deliverymen ?? 0).toLocaleString()}</dd>
                                </div>
                                <div className="flex items-center justify-between">
                                    <dt className="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Wallet className="h-4 w-4" /> {t.roster_pending_pay}
                                    </dt>
                                    <dd className="text-sm font-semibold tabular-nums">
                                        <span className="text-muted-foreground text-xs me-1">{currency}</span>
                                        {Number(totals.pending_pay ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
