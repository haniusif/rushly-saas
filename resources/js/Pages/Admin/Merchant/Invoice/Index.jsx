import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { Eye, FileText, FileSpreadsheet, Receipt, Check } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { cn } from '@/lib/utils';
import MerchantSubHeader from '@/Components/merchant/MerchantSubHeader';

const STATUS_UNPAID    = 0;
const STATUS_PROCESSING = 2;
const STATUS_PAID      = 3;

function Money({ value, currency }) {
    return (
        <span className="tabular-nums">
            <span className="text-muted-foreground text-xs me-0.5">{currency}</span>
            {Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
        </span>
    );
}

function StatusPill({ status, t }) {
    const map = {
        [STATUS_PAID]:       ['bg-emerald-100 text-emerald-700 border-emerald-200', t.status_paid],
        [STATUS_PROCESSING]: ['bg-sky-100 text-sky-700 border-sky-200', t.status_processing],
        [STATUS_UNPAID]:     ['bg-rose-100 text-rose-700 border-rose-200', t.status_unpaid],
    };
    const [klass, label] = map[status] || ['bg-muted text-muted-foreground border-border', '—'];
    return (
        <span className={cn('inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium', klass)}>
            {label}
        </span>
    );
}

export default function Index({ merchant = {}, rows = [], currency = '', permissions = {}, urls = {}, t = {} }) {
    const markPaid = (r) => {
        // Status update endpoint takes invoice_id as a query string per legacy.
        const url = urls.status_update + '?id=' + encodeURIComponent(r.invoice_id) + '&status=' + STATUS_PAID;
        window.location.href = url;
    };
    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title_index, merchant.business_name, t.title]}>
            <Head title={`${t.title} · ${merchant.business_name || ''}`} />
            <MerchantSubHeader
                merchant={merchant}
                title={t.title}
                backUrl={urls.view}
                backLabel={t.back_to_view}
            />

            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                    <th className="px-4 py-3 text-start">#</th>
                                    <th className="px-4 py-3 text-start">{t.invoice_id}</th>
                                    <th className="px-4 py-3 text-start">{t.invoice_date}</th>
                                    <th className="px-4 py-3 text-end">{t.cash_collection}</th>
                                    <th className="px-4 py-3 text-end">{t.total_charge}</th>
                                    <th className="px-4 py-3 text-end">{t.current_payable}</th>
                                    <th className="px-4 py-3 text-start">{t.status}</th>
                                    <th className="px-4 py-3 text-end pe-4">{t.actions}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 && (
                                    <tr><td colSpan={8} className="px-4 py-10 text-center text-muted-foreground"><Receipt className="mx-auto h-6 w-6 mb-2 opacity-40" />{t.no_rows}</td></tr>
                                )}
                                {rows.map((r, idx) => (
                                    <tr key={r.id} className="border-b border-border last:border-0 hover:bg-muted/20 transition-colors">
                                        <td className="px-4 py-3 text-muted-foreground">{idx + 1}</td>
                                        <td className="px-4 py-3 font-mono text-xs font-semibold">{r.invoice_id}</td>
                                        <td className="px-4 py-3 text-xs text-muted-foreground">{r.invoice_date || '—'}</td>
                                        <td className="px-4 py-3 text-end"><Money value={r.cash_collection} currency={currency} /></td>
                                        <td className="px-4 py-3 text-end"><Money value={r.total_charge}    currency={currency} /></td>
                                        <td className="px-4 py-3 text-end font-semibold"><Money value={r.current_payable} currency={currency} /></td>
                                        <td className="px-4 py-3"><StatusPill status={r.status} t={t} /></td>
                                        <td className="px-4 py-3 text-end pe-4">
                                            <div className="inline-flex gap-1 flex-wrap justify-end">
                                                {r.status !== STATUS_PAID && permissions.status_update && (
                                                    <Button type="button" size="sm" onClick={() => markPaid(r)}>
                                                        <Check className="h-3.5 w-3.5 me-1" /> {t.mark_paid}
                                                    </Button>
                                                )}
                                                <a href={r.urls.details} className="inline-flex h-8 items-center rounded-md border border-input bg-background px-2 text-xs font-medium hover:bg-accent">
                                                    <Eye className="h-3.5 w-3.5 me-1" /> {t.view}
                                                </a>
                                                <a href={r.urls.pdf} className="inline-flex h-8 items-center rounded-md border border-rose-200 bg-rose-50 text-rose-700 px-2 text-xs font-medium hover:bg-rose-100">
                                                    <FileText className="h-3.5 w-3.5 me-1" /> {t.pdf}
                                                </a>
                                                <a href={r.urls.csv} className="inline-flex h-8 items-center rounded-md border border-emerald-200 bg-emerald-50 text-emerald-700 px-2 text-xs font-medium hover:bg-emerald-100">
                                                    <FileSpreadsheet className="h-3.5 w-3.5 me-1" /> {t.csv}
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
