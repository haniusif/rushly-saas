import * as React from 'react';
import { Head } from '@inertiajs/react';
import { Eye, Download, FileText } from 'lucide-react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import Pagination from '@/Components/merchant/Pagination';

function Money({ value, currency }) {
    const n = Number(value) || 0;
    return (
        <span className="tabular-nums">
            {n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
            <span className="text-xs text-muted-foreground ms-0.5">{currency}</span>
        </span>
    );
}

export default function Index({ rows = [], currency = '', pagination = null, t = {} }) {
    return (
        <MerchantLayout title={`${t.title} ${t.list}`} breadcrumbs={[t.dashboard, t.title, t.list]}>
            <Head title={t.title} />
            <Card>
                <CardContent className="p-0">
                    <div className="px-5 py-4 border-b border-border">
                        <h2 className="text-base font-semibold m-0">{t.title} {t.list}</h2>
                    </div>
                    {rows.length === 0 ? (
                        <div className="p-8 text-center text-sm text-muted-foreground">{t.empty}</div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/30 text-xs uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th className="text-start font-medium px-4 py-2.5 w-14">{t.id}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.invoice_id}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.invoice_date}</th>
                                        <th className="text-end   font-medium px-4 py-2.5">{t.cash_collection}</th>
                                        <th className="text-end   font-medium px-4 py-2.5">{t.total_charge}</th>
                                        <th className="text-end   font-medium px-4 py-2.5">{t.current_payable}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.status}</th>
                                        <th className="text-end   font-medium px-4 py-2.5 w-60">{t.actions}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {rows.map((r) => (
                                        <tr key={r.id}>
                                            <td className="px-4 py-2.5 tabular-nums">{r.serial}</td>
                                            <td className="px-4 py-2.5 font-medium">{r.invoice_id}</td>
                                            <td className="px-4 py-2.5 text-xs text-muted-foreground">{r.invoice_date}</td>
                                            <td className="px-4 py-2.5 text-end"><Money value={r.cash_collection} currency={currency} /></td>
                                            <td className="px-4 py-2.5 text-end"><Money value={r.total_charge}    currency={currency} /></td>
                                            <td className="px-4 py-2.5 text-end font-medium"><Money value={r.current_payable} currency={currency} /></td>
                                            <td className="px-4 py-2.5">
                                                <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border bg-muted/40 border-border">
                                                    {r.status_label}
                                                </span>
                                            </td>
                                            <td className="px-4 py-2.5 text-end">
                                                <div className="inline-flex gap-1.5">
                                                    <a href={r.details_url} className="inline-flex items-center gap-1.5 h-7 px-2.5 text-xs rounded-md border border-input hover:bg-muted/40 no-underline">
                                                        <Eye className="h-3 w-3" /> {t.view}
                                                    </a>
                                                    <a href={r.csv_url} className="inline-flex items-center gap-1.5 h-7 px-2.5 text-xs rounded-md border border-input hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-200 no-underline">
                                                        <Download className="h-3 w-3" /> {t.csv}
                                                    </a>
                                                    <a href={r.pdf_url} className="inline-flex items-center gap-1.5 h-7 px-2.5 text-xs rounded-md border border-input hover:bg-rose-50 hover:text-rose-700 hover:border-rose-200 no-underline">
                                                        <FileText className="h-3 w-3" /> {t.pdf}
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    <Pagination pagination={pagination} />
                </CardContent>
            </Card>
        </MerchantLayout>
    );
}
