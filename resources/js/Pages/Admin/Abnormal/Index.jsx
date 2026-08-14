import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import {
    Filter, Eraser, ChevronLeft, ChevronRight, ExternalLink,
    AlertTriangle, Flame, AlertOctagon, Archive, Settings,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Select } from '@/Components/ui/Select';
import { cn } from '@/lib/utils';

const SEVERITY_STYLES = {
    warning:  'bg-amber-100 text-amber-800 border-amber-200',
    danger:   'bg-rose-100 text-rose-800 border-rose-200',
    critical: 'bg-slate-900 text-amber-300 border-slate-800',
};
const STATUS_STYLES = {
    open:          'bg-orange-100 text-orange-800',
    investigating: 'bg-sky-100 text-sky-800',
    resolved:      'bg-emerald-100 text-emerald-800',
    closed_lost:   'bg-slate-200 text-slate-800',
};

function StatCard({ icon: Icon, label, value, gradient }) {
    return (
        <Card>
            <CardContent className={cn('p-4 text-white relative overflow-hidden rounded-lg', gradient)}>
                <div className="flex items-start justify-between">
                    <div>
                        <div className="text-3xl font-bold tabular-nums leading-none">{value}</div>
                        <div className="mt-1 text-[11px] uppercase tracking-wider opacity-90 font-semibold">{label}</div>
                    </div>
                    <Icon className="h-7 w-7 opacity-30" />
                </div>
            </CardContent>
        </Card>
    );
}

function ucwords(s) {
    return String(s || '').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

export default function Index({
    rows = [], pagination = {}, filters = {}, summary = {}, threshold = 0,
    lookups = {}, urls = {}, t = {},
}) {
    const [draft, setDraft] = React.useState({ ...filters });
    const [submitting, setSubmitting] = React.useState(false);

    const submitFilter = (e) => {
        e?.preventDefault?.();
        setSubmitting(true);
        router.get(urls.index, draft, {
            preserveState: true, preserveScroll: true, replace: true,
            onFinish: () => setSubmitting(false),
        });
    };
    const clear = () => {
        setDraft({ min_days: '', severity: '', status: '', assigned_to: '' });
        router.get(urls.index, {}, { preserveState: false });
    };
    const goPage = (url) => url && router.get(url, {}, { preserveState: true });

    const showing = (t.showing_results || '')
        .replace(':from', pagination.from ?? 0)
        .replace(':to', pagination.to ?? 0)
        .replace(':total', pagination.total ?? 0);

    return (
        <AdminLayout title={t.title}>
            <Head title={t.title} />

            {/* Action bar */}
            <div className="mb-4 flex items-center justify-end">
                <a href={urls.settings} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent transition-colors">
                    <Settings className="h-4 w-4 me-1" /> {t.settings}
                </a>
            </div>

            {/* Summary cards */}
            <div className="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    icon={AlertTriangle}
                    label={t.stalled_3_days}
                    value={summary.stalled_3}
                    gradient="bg-gradient-to-br from-amber-500 to-amber-600"
                />
                <StatCard
                    icon={Flame}
                    label={t.stalled_5_days}
                    value={summary.stalled_5}
                    gradient="bg-gradient-to-br from-rose-500 to-rose-600"
                />
                <StatCard
                    icon={AlertOctagon}
                    label={t.stalled_7_days}
                    value={summary.stalled_7}
                    gradient="bg-gradient-to-br from-rose-900 to-rose-800"
                />
                <StatCard
                    icon={Archive}
                    label={t.closed_as_lost}
                    value={summary.closed_lost}
                    gradient="bg-gradient-to-br from-slate-600 to-slate-800"
                />
            </div>

            {/* Filter bar */}
            <Card className="mb-5">
                <CardContent className="pt-6">
                    <form onSubmit={submitFilter} className="grid gap-3 md:grid-cols-12">
                        <div className="md:col-span-2">
                            <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.duration}</label>
                            <Select value={draft.min_days || ''} onChange={(e) => setDraft((d) => ({ ...d, min_days: e.target.value }))} className="mt-1.5">
                                <option value="">{t.all}</option>
                                {(lookups.min_days || []).map((d) => <option key={d} value={d}>{d}+ {t.days}</option>)}
                            </Select>
                        </div>
                        <div className="md:col-span-3">
                            <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.severity}</label>
                            <Select value={draft.severity || ''} onChange={(e) => setDraft((d) => ({ ...d, severity: e.target.value }))} className="mt-1.5">
                                <option value="">{t.any_severity}</option>
                                {(lookups.severities || []).map((s) => <option key={s} value={s}>{ucwords(s)}</option>)}
                            </Select>
                        </div>
                        <div className="md:col-span-2">
                            <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.status}</label>
                            <Select value={draft.status || ''} onChange={(e) => setDraft((d) => ({ ...d, status: e.target.value }))} className="mt-1.5">
                                <option value="">{t.all_status}</option>
                                {(lookups.statuses || []).map((s) => <option key={s} value={s}>{ucwords(s)}</option>)}
                            </Select>
                        </div>
                        <div className="md:col-span-3">
                            <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.any_investigator}</label>
                            <Select value={draft.assigned_to || ''} onChange={(e) => setDraft((d) => ({ ...d, assigned_to: e.target.value }))} className="mt-1.5">
                                <option value="">{t.any_investigator}</option>
                                {(lookups.deliverymen || []).map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                            </Select>
                        </div>
                        <div className="md:col-span-2 flex items-end gap-2">
                            <Button type="button" variant="outline" onClick={clear}>
                                <Eraser className="h-4 w-4 me-1" /> {t.clear}
                            </Button>
                            <Button type="submit" disabled={submitting}>
                                <Filter className="h-4 w-4 me-1" /> {t.filter}
                            </Button>
                        </div>
                        <div className="md:col-span-12 text-xs text-muted-foreground">
                            {t.detection_threshold}: <strong className="text-foreground">{threshold} {t.days}</strong>
                        </div>
                    </form>
                </CardContent>
            </Card>

            {/* Table */}
            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                    <th className="px-4 py-3 text-start">#</th>
                                    <th className="px-4 py-3 text-start">{t.tracking}</th>
                                    <th className="px-4 py-3 text-start">{t.customer}</th>
                                    <th className="px-4 py-3 text-start">{t.last_event}</th>
                                    <th className="px-4 py-3 text-center">{t.stale_days}</th>
                                    <th className="px-4 py-3 text-start">{t.severity}</th>
                                    <th className="px-4 py-3 text-start">{t.status}</th>
                                    <th className="px-4 py-3 text-end pe-4">{t.actions}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 && (
                                    <tr>
                                        <td colSpan={8} className="px-4 py-10 text-center text-muted-foreground">{t.no_rows}</td>
                                    </tr>
                                )}
                                {rows.map((r) => (
                                    <tr key={r.id} className="border-b border-border last:border-0 hover:bg-muted/20 transition-colors">
                                        <td className="px-4 py-3 text-muted-foreground">{r.id}</td>
                                        <td className="px-4 py-3 font-mono text-xs">{r.tracking_id}</td>
                                        <td className="px-4 py-3">{r.customer_name || '—'}</td>
                                        <td className="px-4 py-3 text-xs text-muted-foreground">{r.last_event || '—'}</td>
                                        <td className="px-4 py-3 text-center font-semibold tabular-nums">{r.stale_days}</td>
                                        <td className="px-4 py-3">
                                            <span className={cn(
                                                'inline-flex items-center rounded border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                                SEVERITY_STYLES[r.severity] || 'bg-muted text-muted-foreground border-border',
                                            )}>
                                                {r.severity}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className={cn(
                                                'inline-flex items-center rounded px-2 py-0.5 text-[11px] font-semibold',
                                                STATUS_STYLES[r.status] || 'bg-muted text-muted-foreground',
                                            )}>
                                                {r.status_label}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-end pe-4">
                                            <a href={r.url} className="inline-flex h-8 items-center rounded-md border border-input bg-background px-2.5 text-xs font-medium hover:bg-accent transition-colors">
                                                <ExternalLink className="h-3.5 w-3.5 me-1" /> {t.open}
                                            </a>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            {pagination.last_page > 1 && (
                <div className="mt-4 flex items-center justify-between text-sm">
                    <div className="text-muted-foreground">{showing}</div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" disabled={!pagination.prev_url} onClick={() => goPage(pagination.prev_url)}>
                            <ChevronLeft className="h-4 w-4 me-1" /> Prev
                        </Button>
                        <span className="text-xs text-muted-foreground">{pagination.current_page} / {pagination.last_page}</span>
                        <Button variant="outline" size="sm" disabled={!pagination.next_url} onClick={() => goPage(pagination.next_url)}>
                            Next <ChevronRight className="h-4 w-4 ms-1" />
                        </Button>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
