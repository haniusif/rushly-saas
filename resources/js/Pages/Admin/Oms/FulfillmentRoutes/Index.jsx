import * as React from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Plus, GitBranch, Edit, CheckCircle2, XCircle } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';

export default function Index({ routes = [], strategies = [], urls = {}, t = {} }) {
    const strategyLabel = (code) => strategies.find((s) => s.code === code)?.label || code;

    return (
        <AdminLayout title={t.page_title} breadcrumbs={[t.breadcrumb_settings, t.breadcrumb_integrations, t.breadcrumb_commerce, t.breadcrumb_oms, t.breadcrumb_routes]}>
            <Head title={t.page_title} />

            <div className="mb-4 flex items-center justify-between gap-2">
                <a href="/admin/oms/orders" className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.breadcrumb_oms}
                </a>
                <Link href={urls.create}>
                    <Button type="button"><Plus className="h-4 w-4 me-1" /> {t.add}</Button>
                </Link>
            </div>

            <Card className="mb-4">
                <CardContent className="p-5 flex items-start gap-3">
                    <GitBranch className="h-5 w-5 text-primary mt-0.5" />
                    <div>
                        <h2 className="text-lg font-semibold">{t.page_title}</h2>
                        <p className="text-sm text-muted-foreground">{t.help}</p>
                    </div>
                </CardContent>
            </Card>

            {routes.length === 0 ? (
                <Card>
                    <CardContent className="p-8 text-center text-sm text-muted-foreground">{t.no_routes}</CardContent>
                </Card>
            ) : (
                <Card>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/40 text-[11px] uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th className="px-3 py-2 text-left">Priority</th>
                                        <th className="px-3 py-2 text-left">Name</th>
                                        <th className="px-3 py-2 text-left">Provider</th>
                                        <th className="px-3 py-2 text-left">Conditions</th>
                                        <th className="px-3 py-2 text-left">Strategy</th>
                                        <th className="px-3 py-2 text-center">Active</th>
                                        <th className="px-3 py-2 text-right"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {routes.map((r) => (
                                        <tr key={r.id} className="border-t border-border">
                                            <td className="px-3 py-2 font-mono text-xs">{r.priority}</td>
                                            <td className="px-3 py-2 text-sm font-medium">{r.name}</td>
                                            <td className="px-3 py-2 text-xs">{r.source_provider_code || '—'}</td>
                                            <td className="px-3 py-2 text-xs text-muted-foreground">
                                                {[
                                                    r.merchant_id       && `merchant:${r.merchant_id}`,
                                                    r.shipping_city_id  && `city:${r.shipping_city_id}`,
                                                    r.shipping_country  && `country:${r.shipping_country}`,
                                                    r.min_total != null && `≥${r.min_total}`,
                                                    r.max_total != null && `≤${r.max_total}`,
                                                    r.is_cod === true   && 'cod',
                                                    r.is_cod === false  && 'non-cod',
                                                ].filter(Boolean).join(' · ') || 'any'}
                                            </td>
                                            <td className="px-3 py-2 text-xs">
                                                <div className="font-medium">{strategyLabel(r.strategy)}</div>
                                                {r.shipping_connection_id && <div className="text-[10px] text-muted-foreground">conn #{r.shipping_connection_id}</div>}
                                                {r.hub_id && <div className="text-[10px] text-muted-foreground">hub #{r.hub_id}</div>}
                                            </td>
                                            <td className="px-3 py-2 text-center">
                                                {r.is_active
                                                    ? <CheckCircle2 className="h-4 w-4 text-emerald-600 inline" />
                                                    : <XCircle className="h-4 w-4 text-muted-foreground inline" />}
                                            </td>
                                            <td className="px-3 py-2 text-right">
                                                <Link href={`/admin/fulfillment/routes/${r.id}/edit`} className="inline-flex h-7 items-center rounded-md border border-input bg-background px-2 text-xs hover:bg-muted/40">
                                                    <Edit className="h-3.5 w-3.5 me-1" /> edit
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            )}
        </AdminLayout>
    );
}
