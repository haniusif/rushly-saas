import * as React from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    Package, Truck, CheckCircle2, Clock,
    LineChart, Store, Trophy, Warehouse, MapPin,
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

/**
 * Generic "Top N by shipments" leaderboard card. Reused by the Top
 * merchants and Top hubs cards on /summary. Callers pass the trophy-header
 * title, per-row icon, translated column headers, and the list.
 */
/**
 * Deterministic hue from a string so the letter-avatar for a given
 * merchant/driver stays the same colour across renders.
 */
function stringHue(s = '') {
    let h = 0;
    for (let i = 0; i < s.length; i++) h = ((h << 5) - h + s.charCodeAt(i)) | 0;
    return Math.abs(h) % 360;
}

function InitialAvatar({ name = '', shape = 'square' }) {
    const initial = (name || '?').trim().charAt(0).toUpperCase();
    const hue = stringHue(name);
    return (
        <span
            className={cn(
                'inline-grid place-items-center h-7 w-7 shrink-0 text-[11px] font-semibold text-white',
                shape === 'circle' ? 'rounded-full' : 'rounded-md'
            )}
            style={{ backgroundColor: `hsl(${hue}, 62%, 48%)` }}
            aria-hidden
        >
            {initial}
        </span>
    );
}

/**
 * @param {string} [imageKey] - when set, each row uses item[imageKey] as an
 *                              <img> src; falls back to a colored letter
 *                              avatar so rows stay visually distinct.
 */
function TopByShipmentsCard({ items = [], icon: RowIcon = Store, imageKey, avatarShape = 'square', title, subtitle, colName, colQty, empty }) {
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
                            return (
                                <div key={m.id} className="grid grid-cols-[auto_1fr_auto_auto] items-center gap-3 px-5 py-2.5 hover:bg-muted/30 transition-colors">
                                    <span className="w-6 text-center text-xs font-semibold text-muted-foreground tabular-nums">
                                        {i + 1}
                                    </span>
                                    <div className="flex items-center gap-2 min-w-0">
                                        {imageKey && m[imageKey] ? (
                                            <img
                                                src={m[imageKey]}
                                                alt=""
                                                loading="lazy"
                                                className={cn(
                                                    'h-7 w-7 object-cover shrink-0 bg-muted',
                                                    avatarShape === 'circle' ? 'rounded-full' : 'rounded-md'
                                                )}
                                            />
                                        ) : imageKey ? (
                                            <InitialAvatar name={m.name} shape={avatarShape} />
                                        ) : (
                                            <span className="inline-grid place-items-center h-7 w-7 rounded-md bg-primary/10 text-primary shrink-0">
                                                <RowIcon className="h-3.5 w-3.5" />
                                            </span>
                                        )}
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
                        {items.map((d, i) => (
                            <div key={d.id} className="grid grid-cols-[auto_1fr_repeat(3,auto)] items-center gap-3 px-5 py-2.5 hover:bg-muted/30 transition-colors">
                                <span className="w-6 text-center text-xs font-semibold text-muted-foreground tabular-nums">
                                    {i + 1}
                                </span>
                                <div className="flex items-center gap-2 min-w-0">
                                    {d.photo_url ? (
                                        <img
                                            src={d.photo_url}
                                            alt=""
                                            loading="lazy"
                                            className="h-7 w-7 rounded-full object-cover shrink-0 bg-muted"
                                        />
                                    ) : (
                                        <InitialAvatar name={d.name} shape="circle" />
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
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

export default function Index({
    kpis = {},
    trend = [],
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

            {/* KPI row */}
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                <KpiCard icon={Package}      tone="primary" label={t.kpi_today}     value={kpis.today_shipments} />
                <KpiCard icon={Truck}        tone="info"    label={t.kpi_ofd}       value={kpis.ofd} />
                <KpiCard icon={CheckCircle2} tone="success" label={t.kpi_delivered} value={kpis.delivered_today} />
                <KpiCard icon={Clock}        tone="warning" label={t.kpi_pending}   value={kpis.pending} />
            </div>

            <div className="space-y-6">
                <div className="space-y-6">
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
                            imageKey="logo_url"
                            title={t.top_merchants_title}
                            subtitle={t.current_month}
                            colName={t.top_merchants_col_name}
                            colQty={t.top_merchants_col_qty}
                            empty={t.top_merchants_empty}
                        />
                    </div>

                    {/* 50/50 row: Top hubs (month) + OFD by hub (today) */}
                    <div className="grid gap-6 md:grid-cols-2">
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
                            items={ofd_by_hub}
                            icon={Warehouse}
                            title={t.ofd_by_hub_title}
                            subtitle={t.ofd_by_hub_subtitle}
                            colName={t.ofd_by_hub_col_name}
                            colQty={t.ofd_by_hub_col_qty}
                            empty={t.ofd_by_hub_empty}
                        />
                    </div>

                    <TopByShipmentsCard
                        items={top_cities}
                        icon={MapPin}
                        title={t.top_cities_title}
                        subtitle={t.current_month}
                        colName={t.top_cities_col_name}
                        colQty={t.top_merchants_col_qty}
                        empty={t.top_cities_empty}
                    />

                    {/* Deliveryman performance — full width, 3 numeric cols */}
                    <DeliverymenPerformanceCard items={top_deliverymen} t={t} />
                </div>

            </div>
        </AdminLayout>
    );
}
