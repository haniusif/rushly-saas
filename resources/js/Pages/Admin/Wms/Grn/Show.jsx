import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft, Printer, CheckCircle2, Trash2, PackageCheck, Store, Building2,
    Hash, User, CalendarClock, Clock, AlertTriangle, StickyNote, Boxes, MapPin,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Pill, tableHeadClass, tableRowClass } from '@/Components/wms/ListPage';

const GRN_COLORS  = { draft: 'grey', in_progress: 'sky', completed: 'emerald', discrepancy: 'rose' };
const COND_COLORS = { good: 'emerald', damaged: 'rose', expired: 'grey' };

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
            <div className="flex items-center gap-1.5 text-[11px] uppercase tracking-wider font-semibold text-muted-foreground w-32 shrink-0 pt-0.5">
                {Icon && <Icon className="h-3 w-3" />} {label}
            </div>
            <div className={`text-sm flex-1 min-w-0 break-words ${mono ? 'font-mono' : ''}`}>
                {children || <span className="text-muted-foreground">—</span>}
            </div>
        </div>
    );
}

export default function Show({ grn = {}, items = [], totals = {}, permissions = {}, urls = {}, t = {} }) {
    const isOpen = grn.status === 'draft' || grn.status === 'in_progress';

    const onComplete = () => {
        if (!window.confirm(t.complete_confirm)) return;
        router.put(urls.complete, {}, { preserveScroll: true });
    };
    const onDelete = () => {
        if (!window.confirm(t.delete_confirm)) return;
        router.delete(urls.destroy);
    };

    const variance = (totals.received ?? 0) - (totals.expected ?? 0);
    const varianceTone = variance === 0 ? 'green' : 'red';

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.list, grn.grn_number || `#${grn.id}`]}>
            <Head title={grn.grn_number || t.title} />

            {/* Toolbar */}
            <div className="mb-4 flex flex-wrap items-center justify-between gap-2 print:hidden">
                <Link href={urls.index} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.back_to_list}
                </Link>
                <div className="flex flex-wrap items-center gap-2">
                    <Button type="button" variant="outline" onClick={() => window.print()}>
                        <Printer className="h-4 w-4 me-1" /> {t.print}
                    </Button>
                    {isOpen && permissions.manage && (
                        <>
                            <Button type="button" onClick={onComplete} className="bg-emerald-600 text-white hover:bg-emerald-700">
                                <CheckCircle2 className="h-4 w-4 me-1" /> {t.complete}
                            </Button>
                            <Button type="button" variant="outline" onClick={onDelete} className="text-rose-600 border-rose-200 hover:bg-rose-50">
                                <Trash2 className="h-4 w-4 me-1" /> {t.delete}
                            </Button>
                        </>
                    )}
                </div>
            </div>

            <div className="grid gap-5 lg:grid-cols-3">
                {/* Identity card */}
                <div className="space-y-5">
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-start gap-3 pb-4 border-b border-border">
                                <div className="grid h-14 w-14 place-items-center rounded-md bg-primary/10 text-primary shrink-0">
                                    <PackageCheck className="h-7 w-7" />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <div className="text-lg font-bold leading-tight font-mono truncate">{grn.grn_number || '—'}</div>
                                    <div className="text-xs text-muted-foreground mt-0.5">{t.title}</div>
                                    <div className="mt-2">
                                        <Pill color={GRN_COLORS[grn.status] || 'grey'}>{grn.status_label}</Pill>
                                    </div>
                                </div>
                            </div>

                            <div className="mt-4 space-y-0.5">
                                <Row icon={Store} label={t.merchant}>{grn.merchant}</Row>
                                <Row icon={Building2} label={t.hub}>{grn.hub}</Row>
                                <Row icon={Hash} label={t.reference} mono>{grn.reference_number}</Row>
                                <Row icon={User} label={t.received_by}>{grn.received_by}</Row>
                                <Row icon={CalendarClock} label={t.received_at}>{grn.received_at}</Row>
                                <Row icon={Clock} label={t.created_at}>{grn.created_at}</Row>
                            </div>

                            {grn.notes && (
                                <div className="mt-4 pt-4 border-t border-border">
                                    <div className="flex items-center gap-1.5 text-[10px] uppercase tracking-wider font-semibold text-muted-foreground mb-1.5">
                                        <StickyNote className="h-3 w-3" /> {t.notes}
                                    </div>
                                    <p className="text-sm whitespace-pre-line text-foreground/90">{grn.notes}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {grn.has_discrepancy && (
                        <div className="rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 print:hidden">
                            <div className="flex items-center gap-1.5 font-semibold">
                                <AlertTriangle className="h-4 w-4" /> {t.discrepancy_title}
                            </div>
                            <p className="mt-1 text-rose-700/90">{t.discrepancy_body}</p>
                        </div>
                    )}

                    {isOpen && (
                        <div className="rounded-md border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800 print:hidden">
                            <div className="flex items-center gap-1.5 font-semibold">
                                <Boxes className="h-4 w-4" /> {t.open_title}
                            </div>
                            <p className="mt-1 text-sky-700/90">{t.open_body}</p>
                        </div>
                    )}
                </div>

                {/* Lines */}
                <div className="lg:col-span-2 space-y-5">
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <StatCard icon={Boxes} label={t.lines} value={totals.lines ?? 0} />
                        <StatCard label={t.expected} value={totals.expected ?? 0} />
                        <StatCard label={t.received} value={totals.received ?? 0} tone="green" />
                        <StatCard label={t.variance} value={variance > 0 ? `+${variance}` : variance} tone={varianceTone} />
                    </div>

                    <Card>
                        <CardContent className="p-0">
                            <div className="flex items-center justify-between px-5 py-4 border-b border-border">
                                <div className="text-sm font-semibold tracking-tight">{t.line_items}</div>
                                <div className="text-xs text-muted-foreground">{t.condition_hint}</div>
                            </div>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className={tableHeadClass}>
                                            <th className="px-4 py-3 text-start w-10">#</th>
                                            <th className="px-4 py-3 text-start">{t.product}</th>
                                            <th className="px-4 py-3 text-start">{t.location}</th>
                                            <th className="px-4 py-3 text-end">{t.expected}</th>
                                            <th className="px-4 py-3 text-end">{t.received}</th>
                                            <th className="px-4 py-3 text-start">{t.batch}</th>
                                            <th className="px-4 py-3 text-start">{t.expiry}</th>
                                            <th className="px-4 py-3 text-start">{t.condition}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {items.length === 0 && (
                                            <tr>
                                                <td colSpan={8} className="px-4 py-10 text-center text-sm text-muted-foreground">
                                                    <Boxes className="mx-auto mb-2 h-10 w-10 text-muted-foreground/40" />
                                                    {t.no_items}
                                                </td>
                                            </tr>
                                        )}
                                        {items.map((it, i) => (
                                            <tr key={it.id ?? i} className={`${tableRowClass} ${it.mismatch ? 'bg-rose-50/60 hover:bg-rose-50' : ''}`}>
                                                <td className="px-4 py-3 text-muted-foreground align-top">{i + 1}</td>
                                                <td className="px-4 py-3 align-top">
                                                    <div className="font-medium leading-tight">{it.product || '—'}</div>
                                                    <div className="mt-0.5 font-mono text-[11px] text-muted-foreground">{it.sku || '—'}</div>
                                                </td>
                                                <td className="px-4 py-3 align-top font-mono text-xs">
                                                    {it.location ? (<><MapPin className="h-3 w-3 inline me-1 text-muted-foreground" />{it.location}</>) : '—'}
                                                </td>
                                                <td className="px-4 py-3 align-top text-end tabular-nums">{it.expected_qty}</td>
                                                <td className="px-4 py-3 align-top text-end tabular-nums">
                                                    <span className={it.mismatch ? 'font-semibold text-rose-700' : ''}>{it.received_qty}</span>
                                                    {it.mismatch && (
                                                        <span className="ms-1 text-[11px] text-rose-600">({it.diff > 0 ? `+${it.diff}` : it.diff})</span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 align-top font-mono text-xs">{it.batch_number || '—'}</td>
                                                <td className="px-4 py-3 align-top text-xs tabular-nums">{it.expiry_date || '—'}</td>
                                                <td className="px-4 py-3 align-top">
                                                    <Pill color={COND_COLORS[it.condition] || 'grey'}>{it.condition_label}</Pill>
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
