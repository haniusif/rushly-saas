import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { ArrowLeft, RefreshCw, AlertTriangle, FileJson, ShieldCheck } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';

function StatusBadge({ status }) {
    const cls = {
        processed: 'bg-emerald-100 text-emerald-700 border-emerald-200',
        pending:   'bg-slate-100 text-slate-700 border-slate-200',
        failed:    'bg-rose-100 text-rose-700 border-rose-200',
    }[status] || 'bg-slate-100 text-slate-700 border-slate-200';
    return <span className={`inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium ${cls}`}>{status}</span>;
}

function fmt(iso) {
    if (!iso) return '—';
    try { return new Date(iso).toISOString().replace('T', ' ').slice(0, 19); } catch { return iso; }
}

export default function Show({ event, permissions = {}, urls = {}, t = {} }) {
    const replay = () => {
        if (!confirm(t.replay_confirm)) return;
        router.post(urls.replay, {}, { preserveScroll: true });
    };

    return (
        <AdminLayout title={`${t.page_title} #${event.id}`} breadcrumbs={[t.breadcrumb_settings, t.breadcrumb_integrations, t.breadcrumb_commerce, t.breadcrumb_webhooks, `#${event.id}`]}>
            <Head title={`${t.page_title} #${event.id}`} />

            <div className="mb-4 flex items-center justify-between gap-2">
                <a href={urls.index} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.breadcrumb_webhooks}
                </a>
                {permissions.replay && event.status !== 'processed' && (
                    <Button type="button" onClick={replay}>
                        <RefreshCw className="h-4 w-4 me-1" /> {t.replay}
                    </Button>
                )}
            </div>

            <div className="grid gap-4 lg:grid-cols-3">
                <Card className="lg:col-span-1">
                    <CardContent className="p-5 space-y-3 text-sm">
                        <h3 className="text-base font-semibold flex items-center gap-2"><ShieldCheck className="h-4 w-4" /> Meta</h3>
                        <dl className="space-y-1.5 text-xs">
                            <div className="flex justify-between gap-2"><dt className="text-muted-foreground">id</dt><dd className="font-mono">#{event.id}</dd></div>
                            <div className="flex justify-between gap-2"><dt className="text-muted-foreground">{t.col_provider}</dt><dd className="font-mono">{event.provider_code}</dd></div>
                            <div className="flex justify-between gap-2"><dt className="text-muted-foreground">{t.col_event}</dt><dd className="font-mono">{event.event_type || '—'}</dd></div>
                            <div className="flex justify-between gap-2"><dt className="text-muted-foreground">{t.col_status}</dt><dd><StatusBadge status={event.status} /></dd></div>
                            <div className="flex justify-between gap-2"><dt className="text-muted-foreground">{t.col_received}</dt><dd className="font-mono">{fmt(event.received_at)}</dd></div>
                            <div className="flex justify-between gap-2"><dt className="text-muted-foreground">{t.col_processed}</dt><dd className="font-mono">{fmt(event.processed_at)}</dd></div>
                            <div className="flex justify-between gap-2"><dt className="text-muted-foreground">{t.col_attempts}</dt><dd>{event.attempts}</dd></div>
                            <div className="pt-1.5 border-t border-border">
                                <dt className="text-muted-foreground mb-0.5">{t.idempotency_key}</dt>
                                <dd className="font-mono break-all">{event.idempotency_key}</dd>
                            </div>
                            {event.signature && (
                                <div className="pt-1.5 border-t border-border">
                                    <dt className="text-muted-foreground mb-0.5">{t.signature}</dt>
                                    <dd className="font-mono break-all">{event.signature}</dd>
                                </div>
                            )}
                        </dl>

                        {event.connection && (
                            <>
                                <h3 className="text-base font-semibold pt-2 border-t border-border">{t.connection}</h3>
                                <dl className="space-y-1.5 text-xs">
                                    <div className="flex justify-between gap-2"><dt className="text-muted-foreground">name</dt><dd>{event.connection.connection_name}</dd></div>
                                    <div className="flex justify-between gap-2"><dt className="text-muted-foreground">provider</dt><dd>{event.connection.provider}</dd></div>
                                    <div className="flex justify-between gap-2"><dt className="text-muted-foreground">{t.remote_store_id}</dt><dd className="font-mono">{event.connection.remote_store_id || '—'}</dd></div>
                                </dl>
                            </>
                        )}
                    </CardContent>
                </Card>

                <div className="lg:col-span-2 space-y-4">
                    {event.last_error_full && (
                        <Card className="border-rose-200 bg-rose-50/60">
                            <CardContent className="p-4 flex items-start gap-2 text-rose-800">
                                <AlertTriangle className="h-4 w-4 mt-0.5 shrink-0" />
                                <div>
                                    <h3 className="text-sm font-semibold mb-1">Last error</h3>
                                    <pre className="text-xs whitespace-pre-wrap break-words font-mono">{event.last_error_full}</pre>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    <Card>
                        <CardContent className="p-5">
                            <h3 className="text-sm font-semibold mb-3 flex items-center gap-2"><FileJson className="h-4 w-4" /> {t.payload}</h3>
                            <pre className="text-xs font-mono bg-muted/40 rounded p-3 max-h-[600px] overflow-auto whitespace-pre">
{JSON.stringify(event.payload, null, 2)}
                            </pre>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
