import * as React from 'react';
import { Head } from '@inertiajs/react';
import { ArrowLeft, ShoppingCart, User, MapPin, Package, ScrollText, Webhook } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';

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
    return <span className={`inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium ${cls}`}>{status || '—'}</span>;
}

function money(v, cur) { if (v == null) return '—'; return `${Number(v).toFixed(2)} ${cur || ''}`.trim(); }
function fmt(iso)      { if (!iso) return '—'; try { return new Date(iso).toISOString().replace('T', ' ').slice(0, 19); } catch { return iso; } }

export default function Show({ order, urls = {}, t = {} }) {
    return (
        <AdminLayout title={`${t.page_title} #${order.id}`} breadcrumbs={[t.breadcrumb_settings, t.breadcrumb_integrations, t.breadcrumb_commerce, t.breadcrumb_orders, `#${order.id}`]}>
            <Head title={`${t.page_title} #${order.id}`} />

            <div className="mb-4 flex items-center justify-between gap-2">
                <a href={urls.index} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.breadcrumb_orders}
                </a>
                {urls.webhook_event && (
                    <a href={urls.webhook_event} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">
                        <Webhook className="h-4 w-4 me-1" /> {t.webhook_source}
                    </a>
                )}
            </div>

            <Card className="mb-4">
                <CardContent className="p-5 flex items-start gap-3">
                    <ShoppingCart className="h-6 w-6 text-primary mt-0.5" />
                    <div className="flex-1">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-semibold">
                                #{order.id}
                                <span className="ms-2 text-sm text-muted-foreground font-normal">
                                    · {order.source_provider_code}
                                    {order.remote_order_number ? ` · ${order.remote_order_number}` : ''}
                                </span>
                            </h2>
                            <StatusPill status={order.status} />
                        </div>
                        <div className="text-xs text-muted-foreground mt-1">
                            Received {fmt(order.received_at)}  ·  Placed {fmt(order.occurred_at)}
                        </div>
                        <div className="text-xs text-muted-foreground mt-1">
                            payment: <span className="font-medium">{order.payment_status}</span>
                            {order.payment_method && <> · {order.payment_method}</>}
                            {' '}· fulfillment: <span className="font-medium">{order.fulfillment_status}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div className="grid gap-4 lg:grid-cols-3">
                <div className="lg:col-span-2 space-y-4">
                    <Card>
                        <CardContent className="p-5">
                            <h3 className="text-sm font-semibold mb-3 flex items-center gap-2"><Package className="h-4 w-4" /> {t.items}</h3>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-muted/40 text-[11px] uppercase tracking-wide text-muted-foreground">
                                        <tr>
                                            <th className="px-2 py-1 text-left">SKU</th>
                                            <th className="px-2 py-1 text-left">Name</th>
                                            <th className="px-2 py-1 text-right">Qty</th>
                                            <th className="px-2 py-1 text-right">Unit</th>
                                            <th className="px-2 py-1 text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {(order.items || []).map((i) => (
                                            <tr key={i.id} className="border-t border-border">
                                                <td className="px-2 py-1 font-mono text-xs">{i.sku || '—'}</td>
                                                <td className="px-2 py-1 text-xs">{i.name}</td>
                                                <td className="px-2 py-1 text-right text-xs">{i.quantity}</td>
                                                <td className="px-2 py-1 text-right font-mono text-xs">{money(i.unit_price, i.currency)}</td>
                                                <td className="px-2 py-1 text-right font-mono text-xs">{money(i.total_price, i.currency)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-5">
                            <h3 className="text-sm font-semibold mb-3 flex items-center gap-2"><ScrollText className="h-4 w-4" /> {t.events}</h3>
                            <ul className="text-xs space-y-2">
                                {(order.events || []).map((e) => (
                                    <li key={e.id} className="flex gap-3 items-start pb-2 border-b border-border last:border-b-0 last:pb-0">
                                        <span className="font-mono text-muted-foreground shrink-0 w-40">{fmt(e.occurred_at)}</span>
                                        <div>
                                            <span className="font-medium">{e.event_type}</span>
                                            {e.payload && (
                                                <pre className="text-[11px] text-muted-foreground font-mono mt-1 whitespace-pre-wrap">
{JSON.stringify(e.payload, null, 2)}
                                                </pre>
                                            )}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>

                    {order.normalized_snapshot && (
                        <Card>
                            <CardContent className="p-5">
                                <h3 className="text-sm font-semibold mb-3">{t.snapshot}</h3>
                                <pre className="text-[11px] font-mono bg-muted/40 rounded p-3 max-h-[400px] overflow-auto whitespace-pre">
{JSON.stringify(order.normalized_snapshot, null, 2)}
                                </pre>
                            </CardContent>
                        </Card>
                    )}
                </div>

                <div className="space-y-4">
                    <Card>
                        <CardContent className="p-5">
                            <h3 className="text-sm font-semibold mb-3 flex items-center gap-2"><User className="h-4 w-4" /> Customer</h3>
                            <dl className="text-xs space-y-1.5">
                                <div className="flex justify-between gap-2"><dt className="text-muted-foreground">name</dt><dd>{order.customer_name || '—'}</dd></div>
                                <div className="flex justify-between gap-2"><dt className="text-muted-foreground">phone</dt><dd className="font-mono">{order.customer_phone || '—'}</dd></div>
                                <div className="flex justify-between gap-2"><dt className="text-muted-foreground">email</dt><dd className="font-mono">{order.customer_email || '—'}</dd></div>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-5">
                            <h3 className="text-sm font-semibold mb-3 flex items-center gap-2"><MapPin className="h-4 w-4" /> {t.shipping}</h3>
                            <dl className="text-xs space-y-1.5">
                                <div className="flex justify-between gap-2"><dt className="text-muted-foreground">to</dt><dd>{order.shipping?.name || '—'}</dd></div>
                                <div className="flex justify-between gap-2"><dt className="text-muted-foreground">line1</dt><dd className="text-right">{order.shipping?.line1 || '—'}</dd></div>
                                {order.shipping?.line2 && <div className="flex justify-between gap-2"><dt className="text-muted-foreground">line2</dt><dd className="text-right">{order.shipping.line2}</dd></div>}
                                <div className="flex justify-between gap-2"><dt className="text-muted-foreground">city</dt><dd>{order.shipping?.city_name || '—'}</dd></div>
                                {order.shipping?.area_name && <div className="flex justify-between gap-2"><dt className="text-muted-foreground">area</dt><dd>{order.shipping.area_name}</dd></div>}
                                <div className="flex justify-between gap-2"><dt className="text-muted-foreground">country</dt><dd>{order.shipping?.country || '—'}</dd></div>
                                <div className="flex justify-between gap-2 pt-1.5 border-t border-border">
                                    <dt className="text-muted-foreground">city_id</dt><dd className="font-mono">{order.shipping?.city_id ?? '—'}</dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-5">
                            <h3 className="text-sm font-semibold mb-3">{t.totals}</h3>
                            <dl className="text-xs space-y-1.5">
                                <div className="flex justify-between gap-2"><dt className="text-muted-foreground">subtotal</dt><dd className="font-mono">{money(order.totals?.subtotal, order.totals?.currency)}</dd></div>
                                <div className="flex justify-between gap-2"><dt className="text-muted-foreground">tax</dt><dd className="font-mono">{money(order.totals?.tax, order.totals?.currency)}</dd></div>
                                <div className="flex justify-between gap-2"><dt className="text-muted-foreground">shipping</dt><dd className="font-mono">{money(order.totals?.shipping_fee, order.totals?.currency)}</dd></div>
                                <div className="flex justify-between gap-2"><dt className="text-muted-foreground">discount</dt><dd className="font-mono">−{money(order.totals?.discount, order.totals?.currency)}</dd></div>
                                <div className="flex justify-between gap-2 pt-1.5 border-t border-border font-semibold"><dt>total</dt><dd className="font-mono">{money(order.totals?.total, order.totals?.currency)}</dd></div>
                                {order.totals?.cod_amount > 0 && (
                                    <div className="flex justify-between gap-2 text-amber-700"><dt>COD</dt><dd className="font-mono">{money(order.totals.cod_amount, order.totals.currency)}</dd></div>
                                )}
                            </dl>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
