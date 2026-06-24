import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { Receipt, ChevronLeft, ChevronRight } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';

const STATUS_TINT = {
    0: 'bg-rose-100 text-rose-700 border-rose-200',
    2: 'bg-amber-100 text-amber-700 border-amber-200',
    3: 'bg-emerald-100 text-emerald-700 border-emerald-200',
};
function StatusPill({ status, label }) {
    return <span className={`inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium ${STATUS_TINT[status] || 'bg-slate-100 text-slate-700 border-slate-200'}`}>{label}</span>;
}
function Money({ value, currency }) {
    return <span className="tabular-nums">{currency}{Number(value || 0).toFixed(2)}</span>;
}

function Table({ data, currency, t }) {
    const { rows = [], pagination = {} } = data || {};
    const showing = (t.showing_results || '').replace(':from', pagination.from ?? 0).replace(':to', pagination.to ?? 0).replace(':total', pagination.total ?? 0);
    const goPage = (u) => u && router.get(u, {}, { preserveState: true });
    return (
        <>
            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                    <th className="px-4 py-3 text-start">#</th>
                                    <th className="px-4 py-3 text-start">{t.merchant}</th>
                                    <th className="px-4 py-3 text-start">{t.invoice_id}</th>
                                    <th className="px-4 py-3 text-start">{t.invoice_date}</th>
                                    <th className="px-4 py-3 text-end">{t.cash_collection}</th>
                                    <th className="px-4 py-3 text-end">{t.total_charge}</th>
                                    <th className="px-4 py-3 text-end">{t.current_payable}</th>
                                    <th className="px-4 py-3 text-start">{t.status}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 && (
                                    <tr><td colSpan={8} className="px-4 py-10 text-center text-muted-foreground"><div className="flex flex-col items-center gap-2"><Receipt className="h-10 w-10 text-muted-foreground/40" /><span>{t.no_rows}</span></div></td></tr>
                                )}
                                {rows.map((r, idx) => (
                                    <tr key={r.id} className="border-b border-border last:border-0 hover:bg-muted/20 align-top">
                                        <td className="px-4 py-3 text-muted-foreground">{(pagination.from || 1) + idx}</td>
                                        <td className="px-4 py-3">
                                            <div className="font-medium">{r.merchant_name || '—'}</div>
                                            <div className="text-[11px] text-muted-foreground">{r.merchant_email}</div>
                                        </td>
                                        <td className="px-4 py-3 font-mono text-xs">{r.invoice_id}</td>
                                        <td className="px-4 py-3 text-muted-foreground tabular-nums">{r.invoice_date}</td>
                                        <td className="px-4 py-3 text-end"><Money value={r.cash_collection} currency={currency} /></td>
                                        <td className="px-4 py-3 text-end"><Money value={r.total_charge} currency={currency} /></td>
                                        <td className="px-4 py-3 text-end"><Money value={r.current_payable} currency={currency} /></td>
                                        <td className="px-4 py-3"><StatusPill status={r.status} label={r.status_label} /></td>
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
                        <Button variant="outline" size="sm" disabled={!pagination.prev_url} onClick={() => goPage(pagination.prev_url)}><ChevronLeft className="h-4 w-4 me-1" /> {t.prev}</Button>
                        <span className="text-xs text-muted-foreground">{pagination.current_page} / {pagination.last_page}</span>
                        <Button variant="outline" size="sm" disabled={!pagination.next_url} onClick={() => goPage(pagination.next_url)}>{t.next} <ChevronRight className="h-4 w-4 ms-1" /></Button>
                    </div>
                </div>
            )}
        </>
    );
}

export default function Index({ tabs = {}, currency = '', t = {} }) {
    const [tab, setTab] = React.useState('paid');
    const tabDefs = [
        { key: 'paid',       label: t.tab_paid,       count: tabs.paid?.pagination?.total ?? 0 },
        { key: 'processing', label: t.tab_processing, count: tabs.processing?.pagination?.total ?? 0 },
        { key: 'unpaid',     label: t.tab_unpaid,     count: tabs.unpaid?.pagination?.total ?? 0 },
    ];
    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title, t.list]}>
            <Head title={`${t.title} · ${t.list}`} />
            <Card className="mb-3">
                <CardContent className="p-2">
                    <div className="flex gap-1 border-b border-border">
                        {tabDefs.map((td) => (
                            <button
                                key={td.key}
                                onClick={() => setTab(td.key)}
                                className={`px-4 py-2 text-sm font-medium border-b-2 ${tab === td.key ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'}`}
                            >
                                {td.label} <span className="ms-1 text-xs text-muted-foreground tabular-nums">({td.count})</span>
                            </button>
                        ))}
                    </div>
                </CardContent>
            </Card>
            <Table data={tabs[tab]} currency={currency} t={t} />
        </AdminLayout>
    );
}
