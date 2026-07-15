import * as React from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    Package, Truck, CheckCircle2, Clock, ArrowUpRight, PlusCircle,
    Users, Bike, Wallet, LineChart, ExternalLink,
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

function QuickAction({ href, icon: Icon, title, subtitle }) {
    return (
        <a
            href={href}
            className="group flex items-start gap-3 rounded-xl border border-border bg-card p-4 hover:border-primary/40 hover:bg-primary/5 transition-all"
        >
            <span className="inline-grid place-items-center h-10 w-10 rounded-lg bg-primary/10 text-primary group-hover:scale-110 transition-transform">
                <Icon className="h-4 w-4" />
            </span>
            <div className="flex-1 min-w-0">
                <div className="text-sm font-semibold text-foreground">{title}</div>
                {subtitle && <div className="mt-0.5 text-xs text-muted-foreground truncate">{subtitle}</div>}
            </div>
            <ArrowUpRight className="h-4 w-4 text-muted-foreground group-hover:text-primary transition-colors" />
        </a>
    );
}

export default function Index({
    greeting_name = '',
    currency = '',
    kpis = {},
    trend = [],
    recent = [],
    totals = {},
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
                {/* Left column: trend + recent */}
                <div className="lg:col-span-2 space-y-6">
                    <Card className="rounded-xl shadow-sm border border-border">
                        <CardContent className="p-5">
                            <div className="mb-4 flex items-center gap-2">
                                <LineChart className="h-4 w-4 text-primary" />
                                <div className="text-sm font-semibold">{t.seven_day_title}</div>
                            </div>
                            <TrendChart data={trend} tCreated={t.legend_created} tDelivered={t.legend_delivered} />
                        </CardContent>
                    </Card>

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

                {/* Right column: quick actions + roster tile */}
                <div className="space-y-6">
                    <Card className="rounded-xl shadow-sm border border-border">
                        <CardContent className="p-5">
                            <div className="mb-4 text-sm font-semibold">{t.quick_actions}</div>
                            <div className="space-y-2">
                                <QuickAction href={urls.create_parcel} icon={PlusCircle} title={t.create_parcel} />
                                <QuickAction href={urls.list_parcels}  icon={Package}    title={t.list_parcels} />
                                <QuickAction href={urls.add_merchant}  icon={Users}      title={t.add_merchant} />
                                <QuickAction href={urls.reports}       icon={LineChart}  title={t.reports} />
                            </div>
                        </CardContent>
                    </Card>

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
