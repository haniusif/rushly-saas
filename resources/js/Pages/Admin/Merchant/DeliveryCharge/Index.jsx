import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { Plus, Edit, Trash2, Truck } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { cn } from '@/lib/utils';
import MerchantSubHeader from '@/Components/merchant/MerchantSubHeader';

function Money({ value, currency }) {
    return (
        <span className="tabular-nums">
            <span className="text-muted-foreground text-xs me-0.5">{currency}</span>
            {Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
        </span>
    );
}

export default function Index({ merchant = {}, rows = [], currency = '', permissions = {}, urls = {}, t = {} }) {
    const deleteRow = (r) => {
        if (!window.confirm(t.delete_confirm)) return;
        router.delete(r.urls.delete, { preserveScroll: true });
    };
    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title_index, merchant.business_name, t.title]}>
            <Head title={`${t.title} · ${merchant.business_name || ''}`} />
            <MerchantSubHeader
                merchant={merchant}
                title={t.title}
                backUrl={urls.view}
                backLabel={t.back_to_view}
                actions={permissions.create && (
                    <a href={urls.create} className="inline-flex h-9 items-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90">
                        <Plus className="h-4 w-4 me-1" /> {t.add}
                    </a>
                )}
            />

            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                    <th className="px-4 py-3 text-start">#</th>
                                    <th className="px-4 py-3 text-start">{t.category}</th>
                                    <th className="px-4 py-3 text-end">{t.weight}</th>
                                    <th className="px-4 py-3 text-end">{t.extra_weight}</th>
                                    <th className="px-4 py-3 text-end">{t.same_day}</th>
                                    <th className="px-4 py-3 text-end">{t.next_day}</th>
                                    <th className="px-4 py-3 text-end">{t.sub_city}</th>
                                    <th className="px-4 py-3 text-end">{t.outside_city}</th>
                                    <th className="px-4 py-3 text-start">{t.status}</th>
                                    {(permissions.update || permissions.delete) && <th className="px-4 py-3 text-end pe-4">{t.actions}</th>}
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 && (
                                    <tr><td colSpan={10} className="px-4 py-10 text-center text-muted-foreground"><Truck className="mx-auto h-6 w-6 mb-2 opacity-40" />{t.no_rows}</td></tr>
                                )}
                                {rows.map((r, idx) => (
                                    <tr key={r.id} className="border-b border-border last:border-0 hover:bg-muted/20 transition-colors">
                                        <td className="px-4 py-3 text-muted-foreground">{idx + 1}</td>
                                        <td className="px-4 py-3 font-medium">{r.category || '—'}</td>
                                        <td className="px-4 py-3 text-end tabular-nums">{r.weight}</td>
                                        <td className="px-4 py-3 text-end tabular-nums">{r.extra_weight}</td>
                                        <td className="px-4 py-3 text-end"><Money value={r.same_day}     currency={currency} /></td>
                                        <td className="px-4 py-3 text-end"><Money value={r.next_day}     currency={currency} /></td>
                                        <td className="px-4 py-3 text-end"><Money value={r.sub_city}     currency={currency} /></td>
                                        <td className="px-4 py-3 text-end"><Money value={r.outside_city} currency={currency} /></td>
                                        <td className="px-4 py-3">
                                            <span className={cn(
                                                'inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium',
                                                r.status === 1 ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-rose-100 text-rose-700 border-rose-200',
                                            )}>
                                                {r.status === 1 ? t.active : t.inactive}
                                            </span>
                                        </td>
                                        {(permissions.update || permissions.delete) && (
                                            <td className="px-4 py-3 text-end pe-4">
                                                <div className="inline-flex gap-1">
                                                    {permissions.update && (
                                                        <a href={r.urls.edit} className="inline-flex h-8 w-8 items-center justify-center rounded-md border border-input bg-background hover:bg-accent" title={t.edit}>
                                                            <Edit className="h-3.5 w-3.5" />
                                                        </a>
                                                    )}
                                                    {permissions.delete && (
                                                        <button type="button" onClick={() => deleteRow(r)} className="inline-flex h-8 w-8 items-center justify-center rounded-md border border-input bg-background text-destructive hover:bg-destructive/5" title={t.delete}>
                                                            <Trash2 className="h-3.5 w-3.5" />
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        )}
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
