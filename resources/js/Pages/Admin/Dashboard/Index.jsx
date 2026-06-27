import * as React from 'react';
import { Head } from '@inertiajs/react';
import {
    Package, Users as UsersIcon, Store, Truck, Warehouse, Wallet,
    TrendingUp, TrendingDown, CheckCircle2, Clock, Send,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { cn } from '@/lib/utils';

function fmtNumber(n) {
    return Number(n || 0).toLocaleString();
}

function fmtMoney(n, currency) {
    const v = Number(n || 0);
    return (
        <span className="tabular-nums">
            <span className="text-muted-foreground text-xs me-0.5">{currency}</span>
            {v.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })}
        </span>
    );
}

function KpiCard({ icon: Icon, label, value, accent = 'bg-primary/10 text-primary' }) {
    return (
        <Card>
            <CardContent className="flex items-center gap-4 p-5">
                <div className={cn('grid h-12 w-12 place-items-center rounded-lg', accent)}>
                    <Icon className="h-5 w-5" />
                </div>
                <div className="min-w-0">
                    <div className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{label}</div>
                    <div className="mt-0.5 text-2xl font-semibold tabular-nums">{fmtNumber(value)}</div>
                </div>
            </CardContent>
        </Card>
    );
}

function Sparkline({ series = [], stroke = 'currentColor', fill = 'none' }) {
    if (!series.length) return null;
    const w = 220, h = 56, pad = 4;
    const max = Math.max(...series, 1);
    const min = Math.min(...series, 0);
    const range = max - min || 1;
    const step = (w - pad * 2) / Math.max(series.length - 1, 1);
    const points = series.map((v, i) => {
        const x = pad + i * step;
        const y = pad + (h - pad * 2) * (1 - (v - min) / range);
        return `${x.toFixed(1)},${y.toFixed(1)}`;
    }).join(' ');
    const area = `${pad},${h - pad} ${points} ${(pad + (series.length - 1) * step).toFixed(1)},${h - pad}`;
    return (
        <svg viewBox={`0 0 ${w} ${h}`} className="w-full h-14">
            <polygon points={area} fill={fill} opacity={fill === 'none' ? 0 : 0.12} />
            <polyline points={points} fill="none" stroke={stroke} strokeWidth="2" strokeLinejoin="round" strokeLinecap="round" />
        </svg>
    );
}

function ChartCard({ title, primary, secondary, dates, totalIn, totalOut, currency, t }) {
    const hasSeries = (primary?.length || 0) + (secondary?.length || 0) > 0
        && (primary?.some((x) => x) || secondary?.some((x) => x));
    const hasTotals = (totalIn || 0) !== 0 || (totalOut || 0) !== 0;
    return (
        <Card>
            <CardContent className="p-5">
                <div className="mb-3">
                    <div className="text-sm font-semibold">{title}</div>
                    {dates?.length > 1 && <div className="mt-0.5 text-xs text-muted-foreground">{dates[0]} – {dates[dates.length - 1]}</div>}
                </div>
                {hasTotals && (
                    <div className="mb-3 grid grid-cols-2 gap-3">
                        <div className="rounded-md bg-emerald-50 px-3 py-2">
                            <div className="inline-flex items-center gap-1 text-[11px] font-medium uppercase tracking-wide text-emerald-700">
                                <TrendingUp className="h-3 w-3" /> {t.income}
                            </div>
                            <div className="mt-0.5 text-sm font-semibold text-emerald-800">{fmtMoney(totalIn, currency)}</div>
                        </div>
                        <div className="rounded-md bg-rose-50 px-3 py-2">
                            <div className="inline-flex items-center gap-1 text-[11px] font-medium uppercase tracking-wide text-rose-700">
                                <TrendingDown className="h-3 w-3" /> {t.expense}
                            </div>
                            <div className="mt-0.5 text-sm font-semibold text-rose-800">{fmtMoney(totalOut, currency)}</div>
                        </div>
                    </div>
                )}
                {hasSeries ? (
                    <>
                        <div className="text-emerald-600"><Sparkline series={primary || []} stroke="currentColor" fill="currentColor" /></div>
                        <div className="text-rose-500 -mt-2"><Sparkline series={secondary || []} stroke="currentColor" fill="currentColor" /></div>
                    </>
                ) : !hasTotals ? (
                    <div className="grid place-items-center h-16 text-xs text-muted-foreground">{t.no_data}</div>
                ) : null}
            </CardContent>
        </Card>
    );
}

function LedgerRow({ row, currency }) {
    const net = row.income - row.expense;
    const positive = net >= 0;
    return (
        <div className="flex items-center justify-between border-b border-border py-2.5 last:border-0">
            <div className="text-sm font-medium">{row.label}</div>
            <div className="flex items-center gap-4 text-sm">
                <div className="text-emerald-700 tabular-nums">+{fmtMoney(row.income, currency)}</div>
                <div className="text-rose-700 tabular-nums">−{fmtMoney(row.expense, currency)}</div>
                <div className={cn('w-24 text-end font-semibold tabular-nums', positive ? 'text-emerald-700' : 'text-rose-700')}>
                    {positive ? '+' : '−'}{fmtMoney(Math.abs(net), currency)}
                </div>
            </div>
        </div>
    );
}

function PipelineCard({ pipeline, t }) {
    const total = (pipeline.assigned || 0) + (pipeline.partial_delivered || 0) + (pipeline.delivered || 0);
    const pct = (n) => total ? Math.round((n / total) * 100) : 0;
    return (
        <Card>
            <CardContent className="p-5">
                <div className="mb-3 text-sm font-semibold">{t.pipeline_title}</div>
                <div className="mb-2 flex h-2 overflow-hidden rounded-full bg-muted">
                    <div className="bg-amber-400" style={{ width: `${pct(pipeline.assigned)}%` }} />
                    <div className="bg-sky-400" style={{ width: `${pct(pipeline.partial_delivered)}%` }} />
                    <div className="bg-emerald-500" style={{ width: `${pct(pipeline.delivered)}%` }} />
                </div>
                <div className="grid grid-cols-3 gap-3 text-sm">
                    <PipeStat icon={Clock}      color="text-amber-700"   label={t.pipeline_assigned}   value={pipeline.assigned} />
                    <PipeStat icon={Send}       color="text-sky-700"     label={t.pipeline_partial}    value={pipeline.partial_delivered} />
                    <PipeStat icon={CheckCircle2} color="text-emerald-700" label={t.pipeline_delivered} value={pipeline.delivered} />
                </div>
            </CardContent>
        </Card>
    );
}

function PipeStat({ icon: Icon, color, label, value }) {
    return (
        <div>
            <div className={cn('inline-flex items-center gap-1 text-xs font-medium', color)}>
                <Icon className="h-3.5 w-3.5" /> {label}
            </div>
            <div className="text-2xl font-semibold tabular-nums">{fmtNumber(value)}</div>
        </div>
    );
}

function StatusPill({ status, t = {} }) {
    const map = {
        1:  ['bg-slate-100 text-slate-700',     t.status_pending          || 'Pending'],
        2:  ['bg-amber-100 text-amber-700',     t.status_picked           || 'Picked'],
        3:  ['bg-sky-100 text-sky-700',         t.status_in_transit       || 'In transit'],
        4:  ['bg-sky-100 text-sky-700',         t.status_at_hub           || 'At hub'],
        5:  ['bg-amber-100 text-amber-700',     t.status_assigned         || 'Assigned'],
        6:  ['bg-sky-100 text-sky-700',         t.status_out_for_delivery || 'Out for delivery'],
        9:  ['bg-emerald-100 text-emerald-700', t.status_delivered        || 'Delivered'],
        10: ['bg-emerald-100 text-emerald-700', t.status_partial          || 'Partial'],
    };
    const [klass, label] = map[status] || ['bg-muted text-muted-foreground', '—'];
    return <span className={cn('rounded-full px-2 py-0.5 text-[11px] font-medium', klass)}>{label}</span>;
}

export default function Index({
    currency = '',
    kpis = {},
    pipeline = {},
    ledgers = [],
    hub_parcels = [],
    recent_parcels = [],
    series = {},
    totals = {},
    t = {},
}) {
    const maxHubCount = Math.max(...hub_parcels.map((h) => h.parcels_count), 1);

    return (
        <AdminLayout title={t.dashboard}>
            <Head title={t.dashboard} />

            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <KpiCard icon={Package}   label={t.parcels}     value={kpis.parcels}     accent="bg-blue-100 text-blue-700" />
                <KpiCard icon={UsersIcon} label={t.users}       value={kpis.users}       accent="bg-violet-100 text-violet-700" />
                <KpiCard icon={Store}     label={t.merchants}   value={kpis.merchants}   accent="bg-rose-100 text-rose-700" />
                <KpiCard icon={Truck}     label={t.deliverymen} value={kpis.deliverymen} accent="bg-amber-100 text-amber-700" />
                <KpiCard icon={Warehouse} label={t.hubs}        value={kpis.hubs}        accent="bg-emerald-100 text-emerald-700" />
                <KpiCard icon={Wallet}    label={t.accounts}    value={kpis.accounts}    accent="bg-cyan-100 text-cyan-700" />
            </div>

            <div className="mt-6 grid gap-4 lg:grid-cols-3">
                <div className="lg:col-span-2 space-y-4">
                    <PipelineCard pipeline={pipeline} t={t} />

                    <Card>
                        <CardContent className="p-5">
                            <div className="mb-3 flex items-center justify-between">
                                <div className="text-sm font-semibold">{t.ledger_summary}</div>
                                <div className="text-xs text-muted-foreground">{t.income} / {t.expense} / net</div>
                            </div>
                            <div>
                                {ledgers.map((row) => <LedgerRow key={row.key} row={row} currency={currency} />)}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-0">
                            <div className="p-5 pb-3">
                                <div className="text-sm font-semibold">{t.recent_parcels}</div>
                            </div>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-y border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                            <th className="px-5 py-2.5 text-start">{t.tracking_id}</th>
                                            <th className="px-5 py-2.5 text-start">{t.merchant}</th>
                                            <th className="px-5 py-2.5 text-start">{t.status}</th>
                                            <th className="px-5 py-2.5 text-end">{t.cash}</th>
                                            <th className="px-5 py-2.5 text-start">{t.created_at}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {recent_parcels.length === 0 && (
                                            <tr>
                                                <td colSpan={5} className="px-5 py-8 text-center text-muted-foreground">{t.no_data}</td>
                                            </tr>
                                        )}
                                        {recent_parcels.map((p) => (
                                            <tr key={p.id} className="border-b border-border last:border-0 hover:bg-muted/20 transition-colors">
                                                <td className="px-5 py-2.5 font-mono text-xs">{p.tracking_id}</td>
                                                <td className="px-5 py-2.5">{p.merchant_name || '—'}</td>
                                                <td className="px-5 py-2.5"><StatusPill status={p.status} t={t} /></td>
                                                <td className="px-5 py-2.5 text-end font-medium">{fmtMoney(p.cash_collection, currency)}</td>
                                                <td className="px-5 py-2.5 text-muted-foreground text-xs">{p.created_at || '—'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-4">
                    <ChartCard
                        title={t.income_vs_expense}
                        primary={series.income}
                        secondary={series.expense}
                        dates={series.dates}
                        totalIn={totals.income}
                        totalOut={totals.expense}
                        currency={currency}
                        t={t}
                    />
                    <ChartCard
                        title={t.merchant_revenue}
                        primary={series.merchantIncome}
                        secondary={series.merchantExpense}
                        dates={series.dates}
                        totalIn={totals.merchantIncome}
                        totalOut={totals.merchantExpense}
                        currency={currency}
                        t={t}
                    />
                    <ChartCard
                        title={t.courier_revenue}
                        primary={series.deliverymanIncome}
                        secondary={series.deliverymanExpense}
                        dates={series.dates}
                        totalIn={totals.deliverymanIncome}
                        totalOut={totals.deliverymanExpense}
                        currency={currency}
                        t={t}
                    />

                    <Card>
                        <CardContent className="p-5">
                            <div className="mb-3 text-sm font-semibold">{t.hub_parcels_title}</div>
                            {hub_parcels.length === 0 && (
                                <div className="text-xs text-muted-foreground">{t.no_data}</div>
                            )}
                            <div className="space-y-2.5">
                                {hub_parcels.map((h) => (
                                    <div key={h.id}>
                                        <div className="mb-1 flex items-center justify-between text-sm">
                                            <span className="truncate">{h.name}</span>
                                            <span className="font-medium tabular-nums text-muted-foreground">{fmtNumber(h.parcels_count)}</span>
                                        </div>
                                        <div className="h-1.5 overflow-hidden rounded-full bg-muted">
                                            <div
                                                className="h-full rounded-full bg-gradient-to-r from-primary to-primary/60"
                                                style={{ width: `${(h.parcels_count / maxHubCount) * 100}%` }}
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
