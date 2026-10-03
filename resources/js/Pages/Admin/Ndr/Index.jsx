import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import {
    Filter, Eraser, ChevronLeft, ChevronRight, Eye, Download,
    AlertTriangle, Clock, CheckCircle2, TrendingDown,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import { cn } from '@/lib/utils';

const ATTEMPT_STYLES = {
    1: 'bg-amber-100 text-amber-800',
    2: 'bg-orange-100 text-orange-800',
    3: 'bg-rose-100 text-rose-800',
};
const STATUS_STYLES = {
    open:        'bg-orange-100 text-orange-800',
    in_progress: 'bg-sky-100 text-sky-800',
    resolved:    'bg-emerald-100 text-emerald-800',
    returned:    'bg-rose-100 text-rose-800',
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
                    <Icon className="h-7 w-7 opacity-25" />
                </div>
            </CardContent>
        </Card>
    );
}

function ucwords(s) {
    return String(s || '').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

export default function Index({
    rows = [], pagination = {}, filters = {}, stats = {},
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
        const empty = { status: '', failure_reason: '', deliveryman_id: '', date_from: '', date_to: '' };
        setDraft(empty);
        router.get(urls.index, {}, { preserveState: false });
    };
    const goPage = (url) => url && router.get(url, {}, { preserveState: true });

    const showing = (t.showing_results || '')
        .replace(':from', pagination.from ?? 0)
        .replace(':to', pagination.to ?? 0)
        .replace(':total', pagination.total ?? 0);

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title, t.list]}>
            <Head title={`${t.title} · ${t.list}`} />

            {/* Stats */}
            <div className="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    icon={AlertTriangle}
                    label={t.today_ndrs}
                    value={stats.today}
                    gradient="bg-gradient-to-br from-indigo-500 to-indigo-600"
                />
                <StatCard
                    icon={Clock}
                    label={t.pending}
                    value={(stats.open || 0) + (stats.in_progress || 0)}
                    gradient="bg-gradient-to-br from-amber-500 to-amber-600"
                />
                <StatCard
                    icon={CheckCircle2}
                    label={t.resolved}
                    value={stats.resolved}
                    gradient="bg-gradient-to-br from-emerald-500 to-emerald-600"
                />
                <StatCard
                    icon={TrendingDown}
                    label={t.return_rate}
                    value={`${stats.return_rate}%`}
                    gradient="bg-gradient-to-br from-rose-500 to-rose-600"
                />
            </div>

            {/* Filter bar */}
            <Card className="mb-5">
                <CardContent className="pt-6">
                    <form onSubmit={submitFilter} className="grid gap-3 md:grid-cols-12">
                        <div className="md:col-span-2">
                            <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.status}</label>
                            <Select value={draft.status || ''} onChange={(e) => setDraft((d) => ({ ...d, status: e.target.value }))} className="mt-1.5">
                                <option value="">{t.all_status}</option>
                                {(lookups.statuses || []).map((s) => (
                                    <option key={s} value={s}>{ucwords(s)}</option>
                                ))}
                            </Select>
                        </div>
                        <div className="md:col-span-3">
                            <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.failure_reason}</label>
                            <Select value={draft.failure_reason || ''} onChange={(e) => setDraft((d) => ({ ...d, failure_reason: e.target.value }))} className="mt-1.5">
                                <option value="">{t.all_reasons}</option>
                                {(lookups.reasons || []).map((r) => (
                                    <option key={r.value} value={r.value}>{r.label}</option>
                                ))}
                            </Select>
                        </div>
                        <div className="md:col-span-3">
                            <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.deliveryman}</label>
                            <Select value={draft.deliveryman_id || ''} onChange={(e) => setDraft((d) => ({ ...d, deliveryman_id: e.target.value }))} className="mt-1.5">
                                <option value="">{t.any_deliveryman}</option>
                                {(lookups.deliverymen || []).map((d) => (
                                    <option key={d.id} value={d.id}>{d.name}</option>
                                ))}
                            </Select>
                        </div>
                        <div className="md:col-span-2">
                            <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.from}</label>
                            <Input type="date" value={draft.date_from || ''} onChange={(e) => setDraft((d) => ({ ...d, date_from: e.target.value }))} className="mt-1.5" />
                        </div>
                        <div className="md:col-span-2">
                            <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.to}</label>
                            <Input type="date" value={draft.date_to || ''} onChange={(e) => setDraft((d) => ({ ...d, date_to: e.target.value }))} className="mt-1.5" />
                        </div>
                        <div className="md:col-span-12 flex items-center justify-end gap-2 pt-1">
                            <a href={urls.export} className="inline-flex h-9 items-center justify-center rounded-md border border-emerald-200 bg-emerald-50 text-emerald-700 px-3 text-sm font-medium hover:bg-emerald-100 transition-colors">
                                <Download className="h-4 w-4 me-1" /> {t.export}
                            </a>
                            <Button type="button" variant="outline" onClick={clear}>
                                <Eraser className="h-4 w-4 me-1" /> {t.clear}
                            </Button>
                            <Button type="submit" disabled={submitting}>
                                <Filter className="h-4 w-4 me-1" /> {t.filter}
                            </Button>
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
                                    <th className="px-4 py-3 text-start">{t.attempt}</th>
                                    <th className="px-4 py-3 text-start">{t.failure_reason}</th>
                                    <th className="px-4 py-3 text-start">{t.deliveryman}</th>
                                    <th className="px-4 py-3 text-start">{t.status}</th>
                                    <th className="px-4 py-3 text-start">{t.created}</th>
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
                                        <td className="px-4 py-3">
                                            <span className={cn(
                                                'inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                                ATTEMPT_STYLES[r.attempt_number] || 'bg-muted text-muted-foreground',
                                            )}>
                                                {r.attempt_number}/3
                                            </span>
                                        </td>
                                        <td className="px-4 py-3">{r.failure_label}</td>
                                        <td className="px-4 py-3 text-muted-foreground">{r.deliveryman || '—'}</td>
                                        <td className="px-4 py-3">
                                            <span className={cn(
                                                'inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold',
                                                STATUS_STYLES[r.status] || 'bg-muted text-muted-foreground',
                                            )}>
                                                {r.status_label}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-xs text-muted-foreground">{r.created_at || '—'}</td>
                                        <td className="px-4 py-3 text-end pe-4">
                                            <a href={r.url} className="inline-flex h-8 items-center rounded-md border border-input bg-background px-2.5 text-xs font-medium hover:bg-accent transition-colors">
                                                <Eye className="h-3.5 w-3.5 me-1" /> {t.view}
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
