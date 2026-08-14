import * as React from 'react';
import { Head } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Card, CardContent } from '@/Components/ui/Card';

export default function CodCharges({ rows = [], t = {} }) {
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
                                        <th className="text-start font-medium px-5 py-2.5 w-16">{t.id}</th>
                                        <th className="text-start font-medium px-5 py-2.5">{t.location}</th>
                                        <th className="text-end font-medium px-5 py-2.5 w-32">{t.charge}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {rows.map((r) => (
                                        <tr key={r.id}>
                                            <td className="px-5 py-2.5 tabular-nums">{r.id}</td>
                                            <td className="px-5 py-2.5">{r.location}</td>
                                            <td className="px-5 py-2.5 text-end tabular-nums">{r.charge}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardContent>
            </Card>
        </MerchantLayout>
    );
}
