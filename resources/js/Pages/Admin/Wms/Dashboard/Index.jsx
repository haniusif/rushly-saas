import * as React from 'react';
import { Head } from '@inertiajs/react';
import {
    Package, Boxes, Truck, FileInput, AlertTriangle, Bug, Clock, ArrowRightLeft,
    ExternalLink, BarChart3, BookOpen,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { ucwords } from '@/Components/wms/ListPage';
import { cn } from '@/lib/utils';

function fmt(n) {
    return Number(n || 0).toLocaleString();
}

function KpiCard({ icon: Icon, label, value, accent = 'bg-primary/10 text-primary', href }) {
    const body = (
        <CardContent className="flex items-center gap-4 p-5">
            <div className={cn('grid h-12 w-12 place-items-center rounded-lg', accent)}>
                <Icon className="h-5 w-5" />
            </div>
            <div className="min-w-0">
                <div className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{label}</div>
                <div className="mt-0.5 text-2xl font-semibold tabular-nums">{fmt(value)}</div>
            </div>
        </CardContent>
    );
    return href
        ? <a href={href}><Card className="hover:shadow-md transition-shadow">{body}</Card></a>
        : <Card>{body}</Card>;
}

function MovementChart({ data, currency }) {
    if (!data?.length) return null;
    const max = Math.max(...data.flatMap((d) => [Math.abs(d.credit), Math.abs(d.debit)]), 1);
    return (
        <Card>
            <CardContent className="p-5">
                <div className="mb-3 text-sm font-semibold">Stock movement (7 days)</div>
                <div className="space-y-2">
                    {data.map((d) => (
                        <div key={d.label} className="grid grid-cols-12 items-center gap-2 text-xs">
                            <div className="col-span-2 text-muted-foreground">{d.label}</div>
                            <div className="col-span-5 flex justify-end">
                                <div className="h-3 rounded bg-emerald-500/80" style={{ width: `${Math.abs(d.credit) / max * 100}%` }} />
                            </div>
                            <div className="col-span-5">
                                <div className="h-3 rounded bg-rose-500/80" style={{ width: `${Math.abs(d.debit) / max * 100}%` }} />
                            </div>
                        </div>
                    ))}
                </div>
                <div className="mt-3 flex items-center gap-4 text-[10px] text-muted-foreground">
                    <span className="inline-flex items-center gap-1"><span className="h-2 w-2 rounded bg-emerald-500" /> Credit</span>
                    <span className="inline-flex items-center gap-1"><span className="h-2 w-2 rounded bg-rose-500" /> Debit</span>
                </div>
            </CardContent>
        </Card>
    );
}

function FulChart({ data }) {
    if (!data?.length) return null;
    const max = Math.max(...data.map((d) => d.n), 1);
    return (
        <Card>
            <CardContent className="p-5">
                <div className="mb-3 text-sm font-semibold">Fulfillment status</div>
                <div className="space-y-2">
                    {data.map((d) => (
                        <div key={d.status} className="grid grid-cols-12 items-center gap-2 text-xs">
                            <div className="col-span-3 text-muted-foreground">{ucwords(d.status)}</div>
                            <div className="col-span-7">
                                <div className="h-3 rounded bg-sky-500/80" style={{ width: `${d.n / max * 100}%` }} />
                            </div>
                            <div className="col-span-2 text-end font-medium tabular-nums">{d.n}</div>
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}

function AlertPanel({ title, rows, empty, render, action }) {
    return (
        <Card>
            <CardContent className="p-5">
                <div className="mb-3 flex items-center justify-between">
                    <div className="text-sm font-semibold">{title}</div>
                    {action}
                </div>
                {rows.length === 0 ? (
                    <div className="text-xs text-muted-foreground py-2">{empty}</div>
                ) : (
                    <div className="divide-y divide-border">
                        {rows.map(render)}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

export default function Index({ kpi = {}, movement = [], fulChart = [], low_stock = [], expiring = [], sla_breached = [], urls = {}, t = {} }) {
    return (
        <AdminLayout title={t.title}>
            <Head title={t.title} />

            {urls.knowledge_base ? (
                <div className="mb-4 flex justify-end">
                    <Button asChild variant="outline" size="sm">
                        <a href={urls.knowledge_base} className="inline-flex items-center gap-2">
                            <BookOpen className="h-4 w-4" />
                            {t.knowledge_base || 'Knowledge base'}
                        </a>
                    </Button>
                </div>
            ) : null}

            <div className="mb-5 grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                <KpiCard icon={Package}        label={t.total_skus}          value={kpi.total_skus}         accent="bg-blue-100 text-blue-700" href={urls.products} />
                <KpiCard icon={Boxes}          label={t.total_units}         value={kpi.total_units}        accent="bg-violet-100 text-violet-700" href={urls.stock} />
                <KpiCard icon={Truck}          label={t.pending_fulfillments} value={kpi.pending_fulfillments} accent="bg-amber-100 text-amber-700" href={urls.fulfillment} />
                <KpiCard icon={FileInput}      label={t.grns_today}          value={kpi.grns_today}         accent="bg-emerald-100 text-emerald-700" href={urls.grn} />
                <KpiCard icon={AlertTriangle}  label={t.low_stock}           value={kpi.low_stock_count}    accent="bg-rose-100 text-rose-700" href={urls.products} />
                <KpiCard icon={Bug}            label={t.damage_month}        value={kpi.damage_this_month}  accent="bg-orange-100 text-orange-700" href={urls.damage} />
                <KpiCard icon={Clock}          label={t.sla_breached}        value={kpi.sla_breached}       accent="bg-rose-100 text-rose-700" href={urls.fulfillment} />
                <KpiCard icon={ArrowRightLeft} label={t.pending_adjustments} value={kpi.pending_adjustments} accent="bg-cyan-100 text-cyan-700" href={urls.adjustments} />
            </div>

            <div className="mb-5 grid gap-4 lg:grid-cols-2">
                <MovementChart data={movement} />
                <FulChart data={fulChart} />
            </div>

            <div className="grid gap-4 lg:grid-cols-3">
                <AlertPanel
                    title={t.low_stock_panel}
                    rows={low_stock}
                    empty={t.no_data}
                    render={(r) => (
                        <a key={r.id} href={r.url} className="flex items-center justify-between gap-3 py-2 text-sm hover:bg-muted/30 rounded -mx-2 px-2 transition-colors">
                            <div className="min-w-0">
                                <div className="font-medium truncate">{r.name}</div>
                                <div className="text-xs text-muted-foreground font-mono">{r.sku}</div>
                            </div>
                            <div className="text-end text-xs">
                                <div className="font-semibold text-rose-700">{r.on_hand}</div>
                                <div className="text-muted-foreground">≥ {r.reorder}</div>
                            </div>
                        </a>
                    )}
                />
                <AlertPanel
                    title={t.expiring_panel}
                    rows={expiring}
                    empty={t.no_data}
                    render={(r) => (
                        <div key={r.id} className="flex items-center justify-between gap-3 py-2 text-sm">
                            <div className="min-w-0">
                                <div className="font-medium truncate">{r.product}</div>
                                <div className="text-xs text-muted-foreground font-mono">{r.sku} · {r.location}</div>
                            </div>
                            <div className="text-end text-xs">
                                <div className="font-semibold">{r.qty}</div>
                                <div className="text-amber-700">{r.expiry}</div>
                            </div>
                        </div>
                    )}
                />
                <AlertPanel
                    title={t.sla_panel}
                    rows={sla_breached}
                    empty={t.no_data}
                    render={(r) => (
                        <a key={r.id} href={r.url} className="flex items-center justify-between gap-3 py-2 text-sm hover:bg-muted/30 rounded -mx-2 px-2 transition-colors">
                            <div className="min-w-0">
                                <div className="font-medium truncate font-mono text-xs">{r.number}</div>
                                <div className="text-xs text-muted-foreground">{r.parcel}</div>
                            </div>
                            <div className="text-xs text-rose-700">{r.deadline}</div>
                        </a>
                    )}
                />
            </div>
        </AdminLayout>
    );
}
