import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ShoppingCart, Eye } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';

function StatusPill({ status }) {
    const map = {
        pending:         'bg-slate-100 text-slate-700 border-slate-200',
        confirmed:       'bg-sky-100 text-sky-700 border-sky-200',
        in_fulfillment:  'bg-indigo-100 text-indigo-700 border-indigo-200',
        shipped:         'bg-amber-100 text-amber-700 border-amber-200',
        delivered:       'bg-emerald-100 text-emerald-700 border-emerald-200',
        cancelled:       'bg-rose-100 text-rose-700 border-rose-200',
        returned:        'bg-orange-100 text-orange-700 border-orange-200',
    };
    const cls = map[status] || 'bg-slate-100 text-slate-700 border-slate-200';
    return <span className={`inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium ${cls}`}>{status || '—'}</span>;
}

function money(v, cur) {
    if (v == null) return '—';
    return `${Number(v).toFixed(2)} ${cur || ''}`.trim();
}

function fmt(iso) {
    if (!iso) return '—';
    try { return new Date(iso).toISOString().replace('T', ' ').slice(0, 19); } catch { return iso; }
}

export default function Index({ orders = [], connections = [], statuses = [], filters = {}, urls = {}, t = {} }) {
    const [status, setStatus] = React.useState(filters.status || '');
    const [connId, setConnId] = React.useState(filters.connection_id || '');

    const apply = () => {
        router.get(urls.index, { status, connection_id: connId }, { preserveState: true, preserveScroll: true });
    };

    return (
        <AdminLayout title={t.page_title} breadcrumbs={[t.breadcrumb_settings, t.breadcrumb_integrations, t.breadcrumb_commerce, t.breadcrumb_orders]}>
            <Head title={t.page_title} />

            <div className="mb-4 flex items-center justify-between gap-2">
                <a href={urls.connections} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.breadcrumb_commerce}
                </a>
            </div>

            <Card className="mb-4">
                <CardContent className="p-5 flex items-start gap-3">
                    <ShoppingCart className="h-5 w-5 text-primary mt-0.5" />
                    <div>
                        <h2 className="text-lg font-semibold">{t.page_title}</h2>
                        <p className="text-sm text-muted-foreground">{t.help}</p>
                    </div>
                </CardContent>
            </Card>

            <Card className="mb-4">
                <CardContent className="p-4">
                    <div className="grid gap-3 md:grid-cols-3">
                        <div>
                            <label className="block text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1">{t.filter_status}</label>
                            <select className="w-full h-9 rounded-md border border-input bg-background px-3 text-sm" value={status} onChange={(e) => setStatus(e.target.value)}>
                                <option value="">{t.filter_all}</option>
                                {statuses.map((s) => <option key={s} value={s}>{s}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="block text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1">{t.filter_connection}</label>
                            <select className="w-full h-9 rounded-md border border-input bg-background px-3 text-sm" value={connId} onChange={(e) => setConnId(e.target.value)}>
                                <option value="">{t.filter_all}</option>
                                {connections.map((c) => <option key={c.id} value={c.id}>{c.name} ({c.provider})</option>)}
                            </select>
                        </div>
                        <div className="flex items-end">
                            <Button type="button" onClick={apply} className="w-full">Apply</Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {orders.length === 0 ? (
                <Card>
                    <CardContent className="p-8 text-center text-sm text-muted-foreground">{t.no_orders}</CardContent>
                </Card>
            ) : (
                <Card>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/40 text-[11px] uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th className="px-3 py-2 text-left">{t.col_id}</th>
                                        <th className="px-3 py-2 text-left">{t.col_source}</th>
                                        <th className="px-3 py-2 text-left">{t.col_remote}</th>
                                        <th className="px-3 py-2 text-left">{t.col_customer}</th>
                                        <th className="px-3 py-2 text-left">{t.col_city}</th>
                                        <th className="px-3 py-2 text-right">{t.col_total}</th>
                                        <th className="px-3 py-2 text-left">{t.col_status}</th>
                                        <th className="px-3 py-2 text-left">{t.col_payment}</th>
                                        <th className="px-3 py-2 text-left">{t.col_fulfillment}</th>
                                        <th className="px-3 py-2 text-left">{t.col_received}</th>
                                        <th className="px-3 py-2 text-right"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {orders.map((o) => (
                                        <tr key={o.id} className="border-t border-border">
                                            <td className="px-3 py-2 font-mono text-xs">#{o.id}</td>
                                            <td className="px-3 py-2">{o.source_provider_code}</td>
                                            <td className="px-3 py-2 font-mono text-xs">{o.remote_order_number || o.remote_order_id}</td>
                                            <td className="px-3 py-2">
                                                <div className="text-xs">{o.customer_name || '—'}</div>
                                                <div className="text-[11px] text-muted-foreground font-mono">{o.customer_phone || ''}</div>
                                            </td>
                                            <td className="px-3 py-2 text-xs">{o.shipping_city_name || '—'}</td>
                                            <td className="px-3 py-2 text-right font-mono text-xs">{money(o.total, o.currency)}</td>
                                            <td className="px-3 py-2"><StatusPill status={o.status} /></td>
                                            <td className="px-3 py-2 text-xs">{o.payment_status}</td>
                                            <td className="px-3 py-2 text-xs">{o.fulfillment_status}</td>
                                            <td className="px-3 py-2 font-mono text-xs">{fmt(o.received_at)}</td>
                                            <td className="px-3 py-2 text-right">
                                                <Link href={`/admin/oms/orders/${o.id}`} className="inline-flex h-7 items-center rounded-md border border-input bg-background px-2 text-xs hover:bg-muted/40">
                                                    <Eye className="h-3.5 w-3.5 me-1" /> {t.view}
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
