import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft, CheckCircle2, PackageMinus, Store, Building2, User, Clock, Layers, ClipboardList,
    MapPin, Boxes, AlertTriangle,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Pill, tableHeadClass, tableRowClass, ucwords } from '@/Components/wms/ListPage';

const OB_COLORS   = { pending: 'amber', processing: 'sky', completed: 'emerald', cancelled: 'rose' };
const TYPE_COLORS = { fulfillment: 'sky', manual: 'grey', transfer: 'violet', return_to_merchant: 'amber' };

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

export default function Show({ outbound: o = {}, items = [], totals = {}, permissions = {}, urls = {}, t = {} }) {
    const isOpen = o.status !== 'completed' && o.status !== 'cancelled';
    const onComplete = () => {
        if (!window.confirm(t.complete_confirm)) return;
        router.put(urls.complete, {}, { preserveScroll: true });
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.list, o.outbound_number || `#${o.id}`]}>
            <Head title={o.outbound_number || t.title} />

            {/* Toolbar */}
            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <Link href={urls.index} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.back_to_list}
                </Link>
                {isOpen && permissions.manage && (
                    <Button type="button" onClick={onComplete} className="bg-emerald-600 text-white hover:bg-emerald-700">
                        <CheckCircle2 className="h-4 w-4 me-1" /> {t.complete}
                    </Button>
                )}
            </div>

            <div className="grid gap-5 lg:grid-cols-3">
                {/* Identity */}
                <div className="space-y-5">
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-start gap-3 pb-4 border-b border-border">
                                <div className="grid h-14 w-14 place-items-center rounded-md bg-primary/10 text-primary shrink-0">
                                    <PackageMinus className="h-7 w-7" />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <div className="text-lg font-bold leading-tight font-mono truncate">{o.outbound_number || '—'}</div>
                                    <div className="text-xs text-muted-foreground mt-0.5">{t.title}</div>
                                    <div className="mt-2 flex items-center gap-1.5 flex-wrap">
                                        <Pill color={OB_COLORS[o.status] || 'grey'}>{ucwords(o.status || '')}</Pill>
                                        <Pill color={TYPE_COLORS[o.type] || 'grey'}>{ucwords(o.type || '')}</Pill>
                                    </div>
                                </div>
                            </div>

                            <div className="mt-4 space-y-0.5">
                                <Row icon={Layers} label={t.type}>{ucwords(o.type || '')}</Row>
                                <Row icon={Store} label={t.merchant}>{o.merchant}</Row>
                                <Row icon={Building2} label={t.hub}>{o.hub}</Row>
                                {o.fulfillment_url && (
                                    <Row icon={ClipboardList} label={t.fulfillment} mono>
                                        <a href={o.fulfillment_url} className="text-primary hover:underline">{o.fulfillment_number || `#${o.fulfillment_id}`}</a>
                                    </Row>
                                )}
                                <Row icon={User} label={t.processed_by}>{o.processed_by}</Row>
                                <Row icon={Clock} label={t.created_at}>{o.created_at}</Row>
                                <Row icon={Clock} label={t.completed_at}>{o.completed_at}</Row>
                            </div>
                        </CardContent>
                    </Card>

                    {isOpen && totals.insufficient > 0 && (
                        <div className="rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                            <div className="flex items-center gap-1.5 font-semibold">
                                <AlertTriangle className="h-4 w-4" /> {t.insufficient_title}
                            </div>
                            <p className="mt-1 text-rose-700/90">{t.insufficient_body}</p>
                        </div>
                    )}
                    {isOpen && totals.insufficient === 0 && (
                        <div className="rounded-md border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                            <div className="flex items-center gap-1.5 font-semibold">
                                <Boxes className="h-4 w-4" /> {t.open_title}
                            </div>
                            <p className="mt-1 text-sky-700/90">{t.open_body}</p>
                        </div>
                    )}
                </div>

                {/* Items */}
                <div className="lg:col-span-2 space-y-5">
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <StatCard icon={Boxes} label={t.lines} value={totals.lines ?? 0} />
                        <StatCard label={t.quantity} value={totals.quantity ?? 0} tone="green" />
                        <StatCard label={t.short_lines} value={totals.insufficient ?? 0} tone={(totals.insufficient ?? 0) > 0 ? 'red' : 'default'} />
                    </div>

                    <Card>
                        <CardContent className="p-0">
                            <div className="flex items-center justify-between px-5 py-4 border-b border-border">
                                <div className="text-sm font-semibold tracking-tight">{t.items}</div>
                                {isOpen && <div className="text-xs text-muted-foreground">{t.available_hint}</div>}
                            </div>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className={tableHeadClass}>
                                            <th className="px-4 py-3 text-start w-10">#</th>
                                            <th className="px-4 py-3 text-start">{t.product}</th>
                                            <th className="px-4 py-3 text-start">{t.location}</th>
                                            <th className="px-4 py-3 text-end">{t.quantity}</th>
                                            {isOpen && <th className="px-4 py-3 text-end">{t.available}</th>}
                                            <th className="px-4 py-3 text-start">{t.batch}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {items.length === 0 && (
                                            <tr>
                                                <td colSpan={isOpen ? 6 : 5} className="px-4 py-10 text-center text-sm text-muted-foreground">
                                                    <Boxes className="mx-auto mb-2 h-10 w-10 text-muted-foreground/40" />
                                                    {t.no_items}
                                                </td>
                                            </tr>
                                        )}
                                        {items.map((it, i) => (
                                            <tr key={it.id ?? i} className={`${tableRowClass} ${isOpen && it.insufficient ? 'bg-rose-50/60 hover:bg-rose-50' : ''}`}>
                                                <td className="px-4 py-3 align-top text-muted-foreground">{i + 1}</td>
                                                <td className="px-4 py-3 align-top">
                                                    {it.product_url
                                                        ? <a href={it.product_url} className="font-medium leading-tight hover:underline">{it.product || '—'}</a>
                                                        : <div className="font-medium leading-tight">{it.product || '—'}</div>}
                                                    <div className="mt-0.5 font-mono text-[11px] text-muted-foreground">{it.sku || '—'}</div>
                                                </td>
                                                <td className="px-4 py-3 align-top font-mono text-xs">
                                                    {it.location ? (<><MapPin className="h-3 w-3 inline me-1 text-muted-foreground" />{it.location}</>) : '—'}
                                                </td>
                                                <td className="px-4 py-3 align-top text-end tabular-nums font-semibold">{it.quantity}</td>
                                                {isOpen && (
                                                    <td className={`px-4 py-3 align-top text-end tabular-nums ${it.insufficient ? 'text-rose-700 font-semibold' : 'text-muted-foreground'}`}>
                                                        {it.available}
                                                        {it.insufficient && <AlertTriangle className="h-3 w-3 inline ms-1" />}
                                                    </td>
                                                )}
                                                <td className="px-4 py-3 align-top font-mono text-xs">{it.batch_number || '—'}</td>
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
