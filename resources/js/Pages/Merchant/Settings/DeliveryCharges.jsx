import * as React from 'react';
import { Head } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import Pagination from '@/Components/merchant/Pagination';

function StatusPill({ active, label }) {
    return (
        <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border ${
            active
                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                : 'bg-rose-50 text-rose-700 border-rose-200'
        }`}>
            {label || (active ? 'Active' : 'Inactive')}
        </span>
    );
}

export default function DeliveryCharges({ rows = [], currency = '', pagination = null, t = {} }) {
    return (
        <MerchantLayout title={t.title} breadcrumbs={[t.dashboard, t.settings, t.title]}>
            <Head title={t.title} />
            <Card>
                <CardContent className="p-0">
                    <div className="px-5 py-4 border-b border-border">
                        <h2 className="text-base font-semibold m-0">{t.title}</h2>
                    </div>
                    {rows.length === 0 ? (
                        <div className="p-8 text-center text-sm text-muted-foreground">{t.empty}</div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/30 text-xs uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th className="text-start font-medium px-4 py-2.5 w-16">{t.id}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.category}</th>
                                        <th className="text-end  font-medium px-4 py-2.5">{t.weight}</th>
                                        <th className="text-end  font-medium px-4 py-2.5">{t.same_day}</th>
                                        <th className="text-end  font-medium px-4 py-2.5">{t.next_day}</th>
                                        <th className="text-end  font-medium px-4 py-2.5">{t.sub_city}</th>
                                        <th className="text-end  font-medium px-4 py-2.5">{t.outside_city}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.status}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {rows.map((r) => (
                                        <tr key={r.id}>
                                            <td className="px-4 py-2.5 tabular-nums">{r.id}</td>
                                            <td className="px-4 py-2.5">{r.category}</td>
                                            <td className="px-4 py-2.5 text-end tabular-nums">{r.weight}</td>
                                            <td className="px-4 py-2.5 text-end tabular-nums">{currency}{r.same_day}</td>
                                            <td className="px-4 py-2.5 text-end tabular-nums">{currency}{r.next_day}</td>
                                            <td className="px-4 py-2.5 text-end tabular-nums">{currency}{r.sub_city}</td>
                                            <td className="px-4 py-2.5 text-end tabular-nums">{currency}{r.outside_city}</td>
                                            <td className="px-4 py-2.5"><StatusPill active={r.status_active} label={r.status_label} /></td>
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
