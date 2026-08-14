import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, PackageCheck, CheckCircle2, XCircle, Clock, Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';

function StatusPill({ status }) {
    const map = {
        pending:     ['bg-slate-100 text-slate-700 border-slate-200', Clock],
        in_progress: ['bg-sky-100 text-sky-700 border-sky-200', Loader2],
        completed:   ['bg-emerald-100 text-emerald-700 border-emerald-200', CheckCircle2],
        failed:      ['bg-rose-100 text-rose-700 border-rose-200', XCircle],
        cancelled:   ['bg-amber-100 text-amber-700 border-amber-200', XCircle],
    };
    const [cls, Icon] = map[status] || map.pending;
    return <span className={`inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-medium ${cls}`}><Icon className="h-3 w-3" /> {status}</span>;
}

function fmt(iso) { if (!iso) return '—'; try { return new Date(iso).toISOString().replace('T',' ').slice(0,19); } catch { return iso; } }

export default function Index({ fulfillments = [], filters = {}, strategies = [], urls = {}, t = {} }) {
    const [status, setStatus] = React.useState(filters.status || '');
    const [strategy, setStrategy] = React.useState(filters.strategy || '');

    const apply = () => router.get(urls.index, { status, strategy }, { preserveState: true, preserveScroll: true });

    return (
        <AdminLayout title={t.page_title} breadcrumbs={[t.breadcrumb_settings, t.breadcrumb_integrations, t.breadcrumb_commerce, t.breadcrumb_oms, t.breadcrumb_fulfillments]}>
            <Head title={t.page_title} />

            <div className="mb-4 flex items-center justify-between gap-2">
                <a href="/admin/oms/orders" className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.breadcrumb_oms}
                </a>
                <Link href={urls.routes} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">
                    Fulfillment routes →
                </Link>
            </div>

            <Card className="mb-4">
                <CardContent className="p-5 flex items-start gap-3">
                    <PackageCheck className="h-5 w-5 text-primary mt-0.5" />
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
                            <label className="block text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1">Status</label>
                            <select className="w-full h-9 rounded-md border border-input bg-background px-3 text-sm" value={status} onChange={(e) => setStatus(e.target.value)}>
                                <option value="">All</option>
                                {['pending','in_progress','completed','failed','cancelled'].map(s => <option key={s} value={s}>{s}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="block text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1">Strategy</label>
                            <select className="w-full h-9 rounded-md border border-input bg-background px-3 text-sm" value={strategy} onChange={(e) => setStrategy(e.target.value)}>
                                <option value="">All</option>
                                {strategies.map(s => <option key={s} value={s}>{s}</option>)}
                            </select>
                        </div>
                        <div className="flex items-end"><Button type="button" onClick={apply} className="w-full">Apply</Button></div>
                    </div>
                </CardContent>
            </Card>

            {fulfillments.length === 0 ? (
                <Card><CardContent className="p-8 text-center text-sm text-muted-foreground">{t.no_rows}</CardContent></Card>
            ) : (
                <Card>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/40 text-[11px] uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th className="px-3 py-2 text-left">ID</th>
                                        <th className="px-3 py-2 text-left">Order</th>
                                        <th className="px-3 py-2 text-left">Strategy</th>
                                        <th className="px-3 py-2 text-left">Route</th>
                                        <th className="px-3 py-2 text-left">Status</th>
                                        <th className="px-3 py-2 text-left">External ref</th>
                                        <th className="px-3 py-2 text-left">Started</th>
                                        <th className="px-3 py-2 text-left">Completed</th>
                                        <th className="px-3 py-2 text-left">Error</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {fulfillments.map((f) => (
                                        <tr key={f.id} className="border-t border-border">
                                            <td className="px-3 py-2 font-mono text-xs">#{f.id}</td>
                                            <td className="px-3 py-2 text-xs">
                                                <Link href={`/admin/oms/orders/${f.order_id}`} className="text-primary hover:underline">
                                                    #{f.order_id}
                                                </Link>
                                                {f.order_remote && <span className="text-muted-foreground"> · {f.order_remote}</span>}
                                            </td>
                                            <td className="px-3 py-2 text-xs">{f.strategy}</td>
                                            <td className="px-3 py-2 text-xs">{f.route_name || '—'}</td>
                                            <td className="px-3 py-2"><StatusPill status={f.status} /></td>
                                            <td className="px-3 py-2 font-mono text-xs">{f.external_reference || '—'}</td>
                                            <td className="px-3 py-2 font-mono text-xs">{fmt(f.started_at)}</td>
                                            <td className="px-3 py-2 font-mono text-xs">{fmt(f.completed_at)}</td>
                                            <td className="px-3 py-2 text-xs text-rose-600 max-w-[24ch] truncate" title={f.last_error || ''}>{f.last_error || '—'}</td>
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
