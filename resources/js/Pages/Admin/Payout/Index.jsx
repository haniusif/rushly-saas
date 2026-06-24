import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { CreditCard, ChevronLeft, ChevronRight } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';

function Money({ value, currency }) {
    return <span className="tabular-nums">{currency}{Number(value || 0).toFixed(2)}</span>;
}

export default function Index({ rows = [], pagination = {}, currency = '', t = {} }) {
    const goPage = (u) => u && router.get(u, {}, { preserveState: true });
    const showing = (t.showing_results || '').replace(':from', pagination.from ?? 0).replace(':to', pagination.to ?? 0).replace(':total', pagination.total ?? 0);
    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title, t.list]}>
            <Head title={`${t.title} · ${t.list}`} />
            <div className="mb-3 flex items-center gap-2 text-sm text-muted-foreground"><CreditCard className="h-4 w-4" /><span>{showing}</span></div>
            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                    <th className="px-4 py-3 text-start">#</th>
                                    <th className="px-4 py-3 text-start">{t.card_type}</th>
                                    <th className="px-4 py-3 text-start">{t.merchant}</th>
                                    <th className="px-4 py-3 text-start">{t.from_account}</th>
                                    <th className="px-4 py-3 text-start">{t.transaction_id}</th>
                                    <th className="px-4 py-3 text-end">{t.amount}</th>
                                    <th className="px-4 py-3 text-start">{t.when}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 && (
                                    <tr><td colSpan={7} className="px-4 py-10 text-center text-muted-foreground"><div className="flex flex-col items-center gap-2"><CreditCard className="h-10 w-10 text-muted-foreground/40" /><span>{t.no_rows}</span></div></td></tr>
                                )}
                                {rows.map((r, idx) => (
                                    <tr key={r.id} className="border-b border-border last:border-0 hover:bg-muted/20 align-top">
                                        <td className="px-4 py-3 text-muted-foreground">{(pagination.from || 1) + idx}</td>
                                        <td className="px-4 py-3 capitalize">{r.card_type || '—'}</td>
                                        <td className="px-4 py-3">
                                            <div className="font-medium">{r.merchant_name || '—'}</div>
                                            <div className="text-[11px] text-muted-foreground">{r.merchant_email}</div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div>{r.from_account || '—'}</div>
                                            <div className="font-mono text-[11px] text-muted-foreground">{r.from_account_no}</div>
                                        </td>
                                        <td className="px-4 py-3 font-mono text-xs">{r.transaction_id || '—'}</td>
                                        <td className="px-4 py-3 text-end"><Money value={r.amount} currency={currency} /></td>
                                        <td className="px-4 py-3 text-muted-foreground tabular-nums">{r.created_at}</td>
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
        </AdminLayout>
    );
}
