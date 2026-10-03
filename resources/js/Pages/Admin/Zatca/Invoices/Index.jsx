import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    Filter, Eraser, ChevronLeft, ChevronRight, Eye, Download, RefreshCw,
    FileText, CheckCircle2, AlertCircle, Coins,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import { cn } from '@/lib/utils';

const STATUS_STYLES = {
    pending:    'bg-amber-100 text-amber-800',
    generated:  'bg-emerald-100 text-emerald-800',
    failed:     'bg-rose-100 text-rose-800',
    regenerated:'bg-sky-100 text-sky-800',
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

function fmt(n) {
    return new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0);
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
        const empty = { status: '', type: '', q: '', date_from: '', date_to: '' };
        setDraft(empty);
        router.get(urls.index, {}, { preserveState: false });
    };
    const goPage = (url) => url && router.get(url, {}, { preserveState: true });

    return (
        <AdminLayout title={t.title || 'ZATCA Invoices'} breadcrumbs={[t.title || 'ZATCA', t.list || 'Invoices']}>
            <Head title={t.title || 'ZATCA Invoices'} />

            <div className="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard icon={FileText} label={t.total_invoices} value={stats.total ?? 0} gradient="bg-gradient-to-br from-blue-500 to-blue-700" />
                <StatCard icon={CheckCircle2} label={t.generated} value={stats.generated ?? 0} gradient="bg-gradient-to-br from-emerald-500 to-emerald-700" />
                <StatCard icon={AlertCircle} label={t.failed} value={stats.failed ?? 0} gradient="bg-gradient-to-br from-rose-500 to-rose-700" />
                <StatCard icon={Coins} label={t.vat_collected} value={fmt(stats.vat_amount)} gradient="bg-gradient-to-br from-violet-500 to-violet-700" />
            </div>

            <Card className="mb-4">
                <CardContent className="p-4">
                    <form onSubmit={submitFilter} className="grid gap-2 md:grid-cols-6">
                        <Input placeholder={t.search} value={draft.q || ''} onChange={(e) => setDraft({ ...draft, q: e.target.value })} className="md:col-span-2" />
                        <Select value={draft.status || ''} onChange={(e) => setDraft({ ...draft, status: e.target.value })}>
                            <option value="">{t.all_statuses}</option>
                            {(lookups.statuses || []).map((s) => (
                                <option key={s.value} value={s.value}>{s.label}</option>
                            ))}
                        </Select>
                        <Select value={draft.type || ''} onChange={(e) => setDraft({ ...draft, type: e.target.value })}>
                            <option value="">{t.all_types}</option>
                            {(lookups.types || []).map((tp) => (
                                <option key={tp.value} value={tp.value}>{tp.label}</option>
                            ))}
                        </Select>
                        <Input type="date" value={draft.date_from || ''} onChange={(e) => setDraft({ ...draft, date_from: e.target.value })} />
                        <Input type="date" value={draft.date_to || ''} onChange={(e) => setDraft({ ...draft, date_to: e.target.value })} />
                        <div className="md:col-span-6 flex gap-2">
                            <Button type="submit" disabled={submitting}>
                                <Filter className="mr-2 h-4 w-4" />{t.filter}
                            </Button>
                            <Button type="button" variant="outline" onClick={clear}>
                                <Eraser className="mr-2 h-4 w-4" />{t.clear}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead className="bg-slate-50 text-slate-700">
                                <tr className="text-left">
                                    <th className="px-4 py-3 font-semibold">{t.invoice_number}</th>
                                    <th className="px-4 py-3 font-semibold">{t.type}</th>
                                    <th className="px-4 py-3 font-semibold">{t.buyer}</th>
                                    <th className="px-4 py-3 font-semibold">{t.issued_at}</th>
                                    <th className="px-4 py-3 font-semibold text-right">{t.subtotal}</th>
                                    <th className="px-4 py-3 font-semibold text-right">{t.vat_amount}</th>
                                    <th className="px-4 py-3 font-semibold text-right">{t.total_inclusive}</th>
                                    <th className="px-4 py-3 font-semibold">{t.status}</th>
                                    <th className="px-4 py-3 font-semibold">{t.actions}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {rows.length === 0 && (
                                    <tr><td colSpan={9} className="px-4 py-12 text-center text-slate-500">{t.no_rows}</td></tr>
                                )}
                                {rows.map((r) => (
                                    <tr key={r.id} className="hover:bg-slate-50">
                                        <td className="px-4 py-3 font-mono text-xs">{r.invoice_number}</td>
                                        <td className="px-4 py-3">{r.type_label}</td>
                                        <td className="px-4 py-3 max-w-[180px] truncate">{r.buyer_name || '—'}</td>
                                        <td className="px-4 py-3 text-slate-600">{(r.issued_at || '').replace('T', ' ').slice(0, 16)}</td>
                                        <td className="px-4 py-3 text-right tabular-nums">{fmt(r.subtotal)}</td>
                                        <td className="px-4 py-3 text-right tabular-nums">{fmt(r.vat_amount)}</td>
                                        <td className="px-4 py-3 text-right font-semibold tabular-nums">{fmt(r.total_inclusive)} {r.currency}</td>
                                        <td className="px-4 py-3">
                                            <span className={cn('inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium', STATUS_STYLES[r.status] || 'bg-slate-100 text-slate-700')}>
                                                {r.status_label}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex gap-1">
                                                <Link href={r.url} className="inline-flex h-8 w-8 items-center justify-center rounded-md border hover:bg-slate-100"><Eye className="h-4 w-4" /></Link>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {(pagination.last_page > 1) && (
                        <div className="flex items-center justify-between border-t bg-slate-50 px-4 py-3">
                            <div className="text-xs text-slate-600">
                                {`Showing ${pagination.from ?? 0}–${pagination.to ?? 0} of ${pagination.total ?? 0}`}
                            </div>
                            <div className="flex gap-2">
                                <Button variant="outline" size="sm" disabled={!pagination.prev_url} onClick={() => goPage(pagination.prev_url)}>
                                    <ChevronLeft className="h-4 w-4" />
                                </Button>
                                <Button variant="outline" size="sm" disabled={!pagination.next_url} onClick={() => goPage(pagination.next_url)}>
                                    <ChevronRight className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    )}
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
