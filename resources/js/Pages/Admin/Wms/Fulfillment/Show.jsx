import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft, Play, PackageCheck, Truck, ClipboardList, Store, Building2, User, Package,
    AlertTriangle, Clock, StickyNote, MapPin, Boxes, Check,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Pill, tableHeadClass, tableRowClass, ucwords } from '@/Components/wms/ListPage';

const FUL_COLORS = {
    pending: 'grey', picking: 'amber', packing: 'sky',
    ready: 'emerald', dispatched: 'emerald', cancelled: 'rose',
};
const ITEM_COLORS = { pending: 'grey', short: 'amber', picked: 'emerald' };

function StatCard({ icon: Icon, label, value, tone = 'default' }) {
    const toneClass = {
        default: 'bg-slate-50 border-slate-200',
        green:   'bg-emerald-50 border-emerald-200',
        amber:   'bg-amber-50 border-amber-200',
        red:     'bg-rose-50 border-rose-200',
    }[tone];
    return (
        <div className={`rounded-md border px-4 py-3 ${toneClass}`}>
            <div className="flex items-center gap-1.5 text-[10px] uppercase tracking-wider font-semibold text-muted-foreground">
                {Icon && <Icon className="h-3 w-3" />} {label}
            </div>
            <div className="mt-1 text-2xl font-bold tabular-nums">{value}</div>
        </div>
    );
}

function Row({ icon: Icon, label, children, mono = false }) {
    return (
        <div className="flex items-start gap-3 py-2 border-b border-border/60 last:border-0">
            <div className="flex items-center gap-1.5 text-[11px] uppercase tracking-wider font-semibold text-muted-foreground w-28 shrink-0 pt-0.5">
                {Icon && <Icon className="h-3 w-3" />} {label}
            </div>
            <div className={`text-sm flex-1 min-w-0 break-words ${mono ? 'font-mono' : ''}`}>
                {children || <span className="text-muted-foreground">—</span>}
            </div>
        </div>
    );
}

// Vertical pipeline: done steps green, the current one pulsing amber, the rest grey.
function Pipeline({ steps }) {
    return (
        <ol className="space-y-1.5">
            {steps.map((s) => (
                <li
                    key={s.key}
                    className={`flex items-center gap-3 rounded-md px-3 py-2 text-sm ${
                        s.state === 'now' ? 'bg-amber-50 border border-amber-200'
                        : s.state === 'done' ? 'bg-emerald-50/60'
                        : 'bg-muted/30'
                    }`}
                >
                    <span className={`grid h-5 w-5 place-items-center rounded-full shrink-0 ${
                        s.state === 'done' ? 'bg-emerald-500 text-white'
                        : s.state === 'now' ? 'bg-amber-500 text-white animate-pulse'
                        : 'bg-slate-300 text-white'
                    }`}>
                        {s.state === 'done' ? <Check className="h-3 w-3" /> : <span className="h-1.5 w-1.5 rounded-full bg-white" />}
                    </span>
                    <span className={`font-medium ${s.state === 'todo' ? 'text-muted-foreground' : ''}`}>{s.label}</span>
                    <span className="ms-auto text-xs text-muted-foreground">{s.at || ''}</span>
                </li>
            ))}
        </ol>
    );
}

export default function Show({ fulfillment: f = {}, items = [], totals = {}, pipeline = [], permissions = {}, urls = {}, t = {} }) {
    const onPack = () => router.put(urls.pack, {}, { preserveScroll: true });
    const onDispatch = () => {
        if (!window.confirm(t.dispatch_confirm)) return;
        router.put(urls.dispatch, {}, { preserveScroll: true });
    };

    const progress = totals.required ? Math.min(100, Math.round(((totals.picked ?? 0) / totals.required) * 100)) : 0;

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.list, f.fulfillment_number || `#${f.id}`]}>
            <Head title={f.fulfillment_number || t.title} />

            {/* Toolbar */}
            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <Link href={urls.index} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.back_to_list}
                </Link>
                {permissions.manage && (
                    <div className="flex flex-wrap items-center gap-2">
                        {(f.status === 'pending' || f.status === 'picking') && (
                            <Link href={urls.picking} className="inline-flex h-9 items-center rounded-md bg-amber-500 text-white px-3 text-sm font-medium hover:bg-amber-600">
                                <Play className="h-4 w-4 me-1" /> {f.status === 'picking' ? t.continue_picking : t.start_picking}
                            </Link>
                        )}
                        {f.status === 'packing' && (
                            <Button type="button" onClick={onPack}>
                                <PackageCheck className="h-4 w-4 me-1" /> {t.confirm_pack}
                            </Button>
                        )}
                        {f.status === 'ready' && (
                            <Button type="button" onClick={onDispatch} className="bg-emerald-600 text-white hover:bg-emerald-700">
                                <Truck className="h-4 w-4 me-1" /> {t.dispatch}
                            </Button>
                        )}
                    </div>
                )}
            </div>

            <div className="grid gap-5 lg:grid-cols-3">
                {/* Left column */}
                <div className="space-y-5">
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-start gap-3 pb-4 border-b border-border">
                                <div className="grid h-14 w-14 place-items-center rounded-md bg-primary/10 text-primary shrink-0">
                                    <ClipboardList className="h-7 w-7" />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <div className="text-lg font-bold leading-tight font-mono truncate">{f.fulfillment_number || '—'}</div>
                                    <div className="text-xs text-muted-foreground mt-0.5">{t.title}</div>
                                    <div className="mt-2 flex items-center gap-1.5 flex-wrap">
                                        <Pill color={FUL_COLORS[f.status] || 'grey'}>{ucwords(f.status || '')}</Pill>
                                        {f.sla_breached && <Pill color="rose"><AlertTriangle className="h-3 w-3 me-1" /> {t.sla_breached}</Pill>}
                                    </div>
                                </div>
                            </div>

                            <div className="mt-4 space-y-0.5">
                                <Row icon={Package} label={t.parcel} mono>
                                    {f.parcel_url
                                        ? <a href={f.parcel_url} className="text-primary hover:underline">{f.parcel}</a>
                                        : f.parcel}
                                </Row>
                                <Row icon={User} label={t.customer}>{f.customer}</Row>
                                <Row icon={Store} label={t.merchant}>{f.merchant}</Row>
                                <Row icon={Building2} label={t.hub}>{f.hub}</Row>
                                <Row icon={User} label={t.picker}>{f.picker}</Row>
                                <Row icon={User} label={t.packer}>{f.packer}</Row>
                                <Row icon={Clock} label={t.sla_deadline}>
                                    {f.sla_deadline && (
                                        <span className={f.sla_breached ? 'text-rose-700 font-semibold' : ''}>
                                            {f.sla_deadline}
                                            {f.sla_relative && <span className="ms-1 text-xs text-muted-foreground font-normal">({f.sla_relative})</span>}
                                        </span>
                                    )}
                                </Row>
                                <Row icon={Clock} label={t.created_at}>{f.created_at}</Row>
                            </div>

                            {f.notes && (
                                <div className="mt-4 pt-4 border-t border-border">
                                    <div className="flex items-center gap-1.5 text-[10px] uppercase tracking-wider font-semibold text-muted-foreground mb-1.5">
                                        <StickyNote className="h-3 w-3" /> {t.notes}
                                    </div>
                                    <p className="text-sm whitespace-pre-line text-foreground/90">{f.notes}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="mb-3 text-sm font-semibold tracking-tight">{t.pipeline}</div>
                            <Pipeline steps={pipeline} />
                        </CardContent>
                    </Card>
                </div>

                {/* Right column */}
                <div className="lg:col-span-2 space-y-5">
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <StatCard icon={Boxes} label={t.lines} value={totals.lines ?? 0} />
                        <StatCard label={t.required} value={totals.required ?? 0} />
                        <StatCard label={t.picked} value={totals.picked ?? 0} tone="green" />
                        <StatCard label={t.short} value={totals.short ?? 0} tone={(totals.short ?? 0) > 0 ? 'amber' : 'default'} />
                    </div>

                    <Card>
                        <CardContent className="p-0">
                            <div className="px-5 py-4 border-b border-border">
                                <div className="flex items-center justify-between">
                                    <div className="text-sm font-semibold tracking-tight">{t.items}</div>
                                    <div className="text-xs text-muted-foreground tabular-nums">{totals.picked ?? 0} / {totals.required ?? 0} · {progress}%</div>
                                </div>
                                <div className="mt-2 h-1.5 w-full rounded-full bg-muted overflow-hidden">
                                    <div className="h-full bg-emerald-500 transition-all" style={{ width: `${progress}%` }} />
                                </div>
                            </div>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className={tableHeadClass}>
                                            <th className="px-4 py-3 text-start w-10">#</th>
                                            <th className="px-4 py-3 text-start">{t.product}</th>
                                            <th className="px-4 py-3 text-start">{t.location}</th>
                                            <th className="px-4 py-3 text-end">{t.required}</th>
                                            <th className="px-4 py-3 text-end">{t.picked}</th>
                                            <th className="px-4 py-3 text-start">{t.status}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {items.length === 0 && (
                                            <tr>
                                                <td colSpan={6} className="px-4 py-10 text-center text-sm text-muted-foreground">
                                                    <Boxes className="mx-auto mb-2 h-10 w-10 text-muted-foreground/40" />
                                                    {t.no_items}
                                                </td>
                                            </tr>
                                        )}
                                        {items.map((it, i) => (
                                            <tr key={it.id ?? i} className={`${tableRowClass} ${
                                                it.status === 'picked' ? 'bg-emerald-50/50 hover:bg-emerald-50'
                                                : it.status === 'short' ? 'bg-amber-50/60 hover:bg-amber-50' : ''
                                            }`}>
                                                <td className="px-4 py-3 align-top text-muted-foreground">{i + 1}</td>
                                                <td className="px-4 py-3 align-top">
                                                    <div className="font-medium leading-tight">{it.product || '—'}</div>
                                                    <div className="mt-0.5 font-mono text-[11px] text-muted-foreground">{it.sku || '—'}</div>
                                                </td>
                                                <td className="px-4 py-3 align-top font-mono text-xs">
                                                    {it.location ? (<><MapPin className="h-3 w-3 inline me-1 text-muted-foreground" />{it.location}</>) : '—'}
                                                </td>
                                                <td className="px-4 py-3 align-top text-end tabular-nums">{it.quantity_required}</td>
                                                <td className="px-4 py-3 align-top text-end tabular-nums font-semibold">{it.quantity_picked}</td>
                                                <td className="px-4 py-3 align-top">
                                                    <Pill color={ITEM_COLORS[it.status] || 'grey'}>{it.status_label}</Pill>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
