import * as React from 'react';
import { Head } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import Pagination from '@/Components/merchant/Pagination';

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
                                        <th className="text-start font-medium px-4 py-2.5">{t.card_type}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.from_account}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.transaction_id}</th>
                                        <th className="text-end   font-medium px-4 py-2.5 w-40">{t.amount}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {rows.map((r) => (
                                        <tr key={r.id}>
                                            <td className="px-4 py-2.5 tabular-nums align-top">{r.serial}</td>
                                            <td className="px-4 py-2.5 align-top">{r.card_type}</td>
                                            <td className="px-4 py-2.5 align-top">
                                                {r.account_lines.length ? (
                                                    <div className="space-y-0.5 text-xs">
                                                        {r.account_lines.map((line, i) => <div key={i}>{line}</div>)}
                                                    </div>
                                                ) : <span className="text-muted-foreground">—</span>}
                                            </td>
                                            <td className="px-4 py-2.5 font-mono text-xs align-top">{r.transaction_id || <span className="text-muted-foreground">—</span>}</td>
                                            <td className="px-4 py-2.5 text-end tabular-nums font-medium align-top">
                                                {r.amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} <span className="text-xs text-muted-foreground">{currency}</span>
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
