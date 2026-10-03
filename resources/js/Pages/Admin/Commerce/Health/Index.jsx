import * as React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Activity, CheckCircle2, XCircle, PauseCircle, HelpCircle, Clock, Store, Truck } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';

const HEALTH_META = {
    ok:      { icon: CheckCircle2, cls: 'text-emerald-600', label: 'healthy' },
    stale:   { icon: Clock,        cls: 'text-amber-600',   label: 'stale' },
    invalid: { icon: XCircle,      cls: 'text-rose-600',    label: 'invalid' },
    paused:  { icon: PauseCircle,  cls: 'text-slate-500',   label: 'paused' },
    unknown: { icon: HelpCircle,   cls: 'text-slate-400',   label: 'never tested' },
};

function HealthBadge({ health }) {
    const meta = HEALTH_META[health] || HEALTH_META.unknown;
    const Icon = meta.icon;
    return <span className={`inline-flex items-center gap-1 text-xs font-medium ${meta.cls}`}><Icon className="h-4 w-4" /> {meta.label}</span>;
}

function fmt(iso) { if (!iso) return '—'; try { return new Date(iso).toISOString().replace('T',' ').slice(0,19); } catch { return iso; } }

export default function Index({ connections = [], summary = {}, urls = {}, t = {} }) {
    return (
        <AdminLayout title={t.page_title}>
            <Head title={t.page_title} />

            <Card className="mb-4">
                <CardContent className="p-5 flex items-start gap-3">
                    <Activity className="h-6 w-6 text-primary mt-0.5" />
                    <div className="flex-1">
                        <h2 className="text-lg font-semibold">{t.page_title}</h2>
                        <p className="text-sm text-muted-foreground">{t.subtitle}</p>

                        <div className="mt-3 flex flex-wrap gap-3 text-xs">
                            <span className="inline-flex items-center gap-1"><CheckCircle2 className="h-3.5 w-3.5 text-emerald-600" /> {summary.ok || 0} healthy</span>
                            <span className="inline-flex items-center gap-1"><Clock       className="h-3.5 w-3.5 text-amber-600" />   {summary.stale || 0} stale</span>
                            <span className="inline-flex items-center gap-1"><XCircle     className="h-3.5 w-3.5 text-rose-600" />    {summary.invalid || 0} invalid</span>
                            <span className="inline-flex items-center gap-1"><PauseCircle className="h-3.5 w-3.5 text-slate-500" />   {summary.paused || 0} paused</span>
                            <span className="inline-flex items-center gap-1"><HelpCircle  className="h-3.5 w-3.5 text-slate-400" />   {summary.unknown || 0} unknown</span>
                            <span className="ms-auto text-muted-foreground">total: {summary.total || 0}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {connections.length === 0 ? (
                <Card><CardContent className="p-8 text-center text-sm text-muted-foreground">{t.no_rows}</CardContent></Card>
            ) : (
                <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    {connections.map((c) => {
                        const KindIcon = c.kind === 'shipping' ? Truck : Store;
                        return (
                            <a key={`${c.kind}-${c.id}`} href={c.edit_url}
                               className="block rounded-md border border-input bg-background p-4 hover:bg-muted/40 transition-colors">
                                <div className="flex items-start gap-3">
                                    <KindIcon className="h-5 w-5 text-primary shrink-0 mt-0.5" />
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center justify-between gap-2">
                                            <h3 className="text-sm font-semibold truncate">{c.name}</h3>
                                            <HealthBadge health={c.health} />
                                        </div>
                                        <div className="text-[11px] text-muted-foreground mt-0.5">
                                            {c.kind} · {c.provider}
                                            {c.is_default && <span className="ms-1 text-amber-600 font-medium">· default</span>}
                                        </div>
                                        <dl className="text-[11px] mt-2 space-y-0.5">
                                            {c.remote_id && <div className="flex gap-2"><dt className="text-muted-foreground w-16">remote</dt><dd className="font-mono truncate flex-1">{c.remote_id}</dd></div>}
                                            <div className="flex gap-2"><dt className="text-muted-foreground w-16">status</dt><dd className="font-mono">{c.status}</dd></div>
                                            <div className="flex gap-2"><dt className="text-muted-foreground w-16">tested</dt><dd className="font-mono">{fmt(c.last_tested_at)}</dd></div>
                                            {c.kind === 'commerce' && <div className="flex gap-2"><dt className="text-muted-foreground w-16">event</dt><dd className="font-mono">{fmt(c.last_event_at)}</dd></div>}
                                            <div className="flex gap-2"><dt className="text-muted-foreground w-16">sync</dt><dd className="font-mono">{fmt(c.last_sync_at)}</dd></div>
                                        </dl>
                                    </div>
                                </div>
                            </a>
                        );
                    })}
                </div>
            )}

            <div className="mt-6 flex gap-3">
                <Link href={urls.commerce_connections} className="text-sm text-primary hover:underline">
                    Manage commerce connections →
                </Link>
                <Link href={urls.shipping_connections} className="text-sm text-primary hover:underline">
                    Manage shipping connections →
                </Link>
            </div>
        </AdminLayout>
    );
}
