import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, RefreshCw, CheckCircle2, XCircle, Clock, Webhook, Eye } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';

function StatusPill({ status, t }) {
    const map = {
        processed:    ['bg-emerald-100 text-emerald-700 border-emerald-200', CheckCircle2, t.status_processed],
        pending:      ['bg-slate-100 text-slate-700 border-slate-200',       Clock,        t.status_pending],
        failed:       ['bg-rose-100 text-rose-700 border-rose-200',          XCircle,      t.status_failed],
    };
    const [cls, Icon, label] = map[status] || map.pending;
    return <span className={`inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-medium ${cls}`}><Icon className="h-3 w-3" /> {label}</span>;
}

function fmt(iso) {
    if (!iso) return '—';
    try { return new Date(iso).toISOString().replace('T', ' ').slice(0, 19); } catch { return iso; }
}

export default function Index({ events = [], providers = [], filters = {}, permissions = {}, urls = {}, t = {} }) {
    const [p, setP] = React.useState(filters.provider || '');
    const [s, setS] = React.useState(filters.status || '');
    const [e, setE] = React.useState(filters.event_type || '');

    const apply = () => {
        router.get(urls.index, { provider: p, status: s, event_type: e }, { preserveState: true, preserveScroll: true });
    };

    const replay = (id) => {
        if (!confirm(t.replay_confirm)) return;
        router.post(`/admin/commerce/webhook-events/${id}/replay`, {}, { preserveScroll: true });
    };

    return (
        <AdminLayout title={t.page_title} breadcrumbs={[t.breadcrumb_settings, t.breadcrumb_integrations, t.breadcrumb_commerce, t.breadcrumb_webhooks]}>
            <Head title={t.page_title} />

            <div className="mb-4 flex items-center justify-between gap-2">
                <a href={urls.connections} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.breadcrumb_commerce}
                </a>
            </div>

            <Card className="mb-4">
                <CardContent className="p-5 flex items-start gap-3">
                    <Webhook className="h-5 w-5 text-primary mt-0.5" />
                    <div>
                        <h2 className="text-lg font-semibold">{t.page_title}</h2>
                        <p className="text-sm text-muted-foreground">{t.help}</p>
                    </div>
                </CardContent>
            </Card>

            <Card className="mb-4">
                <CardContent className="p-4">
                    <div className="grid gap-3 md:grid-cols-4">
                        <div>
                            <label className="block text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1">{t.filter_provider}</label>
                            <select className="w-full h-9 rounded-md border border-input bg-background px-3 text-sm" value={p} onChange={(ev) => setP(ev.target.value)}>
                                <option value="">{t.filter_all}</option>
                                {providers.map((pr) => <option key={pr.code} value={pr.code}>{pr.name}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="block text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1">{t.filter_status}</label>
                            <select className="w-full h-9 rounded-md border border-input bg-background px-3 text-sm" value={s} onChange={(ev) => setS(ev.target.value)}>
                                <option value="">{t.filter_all}</option>
                                <option value="processed">{t.status_processed}</option>
                                <option value="pending">{t.status_pending}</option>
                                <option value="failed">{t.status_failed}</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1">{t.filter_event_type}</label>
                            <Input value={e} onChange={(ev) => setE(ev.target.value)} placeholder="order." />
                        </div>
                        <div className="flex items-end">
                            <Button type="button" onClick={apply} className="w-full">Apply</Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {events.length === 0 ? (
                <Card>
                    <CardContent className="p-8 text-center text-sm text-muted-foreground">{t.no_events}</CardContent>
                </Card>
            ) : (
                <Card>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/40 text-[11px] uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th className="px-3 py-2 text-left">{t.col_id}</th>
                                        <th className="px-3 py-2 text-left">{t.col_provider}</th>
                                        <th className="px-3 py-2 text-left">{t.col_event}</th>
                                        <th className="px-3 py-2 text-left">{t.col_received}</th>
                                        <th className="px-3 py-2 text-left">{t.col_processed}</th>
                                        <th className="px-3 py-2 text-right">{t.col_attempts}</th>
                                        <th className="px-3 py-2 text-left">{t.col_status}</th>
                                        <th className="px-3 py-2 text-left">{t.col_error}</th>
                                        <th className="px-3 py-2 text-right"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {events.map((ev) => (
                                        <tr key={ev.id} className="border-t border-border">
                                            <td className="px-3 py-2 font-mono text-xs">#{ev.id}</td>
                                            <td className="px-3 py-2">{ev.provider_code}</td>
                                            <td className="px-3 py-2 font-mono text-xs">{ev.event_type || '—'}</td>
                                            <td className="px-3 py-2 font-mono text-xs">{fmt(ev.received_at)}</td>
                                            <td className="px-3 py-2 font-mono text-xs">{fmt(ev.processed_at)}</td>
                                            <td className="px-3 py-2 text-right">{ev.attempts}</td>
                                            <td className="px-3 py-2"><StatusPill status={ev.status} t={t} /></td>
                                            <td className="px-3 py-2 text-xs text-rose-600 max-w-[24ch] truncate" title={ev.last_error || ''}>{ev.last_error || '—'}</td>
                                            <td className="px-3 py-2 text-right">
                                                <div className="flex justify-end gap-1.5">
                                                    <Link href={`/admin/commerce/webhook-events/${ev.id}`} className="inline-flex h-7 items-center rounded-md border border-input bg-background px-2 text-xs hover:bg-muted/40">
                                                        <Eye className="h-3.5 w-3.5 me-1" /> {t.view}
                                                    </Link>
                                                    {permissions.replay && ev.status !== 'processed' && (
                                                        <Button type="button" onClick={() => replay(ev.id)} className="h-7 px-2 text-xs">
                                                            <RefreshCw className="h-3.5 w-3.5 me-1" /> {t.replay}
                                                        </Button>
                                                    )}
                                                </div>
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
