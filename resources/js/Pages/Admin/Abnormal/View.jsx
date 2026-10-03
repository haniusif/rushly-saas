import * as React from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import {
    ArrowLeft, AlertTriangle, AlertOctagon, ExternalLink, Phone,
    Store, User as UserIcon, Clock, Calendar, Send, MessageSquare,
    TrendingUp, X, CheckCircle2, AlertCircle, Activity,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Select } from '@/Components/ui/Select';
import { Textarea } from '@/Components/ui/Textarea';
import { cn } from '@/lib/utils';

const SEVERITY_STYLES = {
    warning:  'bg-amber-100 text-amber-800 border-amber-200',
    danger:   'bg-rose-100 text-rose-800 border-rose-200',
    critical: 'bg-slate-900 text-amber-300 border-slate-800',
};
const STATUS_STYLES = {
    open:          'bg-orange-100 text-orange-800 border-orange-200',
    investigating: 'bg-sky-100 text-sky-800 border-sky-200',
    resolved:      'bg-emerald-100 text-emerald-800 border-emerald-200',
    closed_lost:   'bg-slate-200 text-slate-800 border-slate-300',
};
const STATUS_COLORS = {
    grey:   'bg-slate-400', yellow: 'bg-amber-500', orange: 'bg-orange-500',
    blue:   'bg-sky-500',   purple: 'bg-violet-500', green:  'bg-emerald-500',
    red:    'bg-rose-500',  teal:   'bg-teal-500',
};

function InfoRow({ label, value }) {
    return (
        <div className="flex items-baseline justify-between gap-3 border-b border-border py-2 last:border-0 text-sm">
            <span className="text-[11px] uppercase tracking-wide text-muted-foreground">{label}</span>
            <span className="text-end font-medium">{value || '—'}</span>
        </div>
    );
}

function ActionForm({ url, payload, onSubmit, confirm, children, className }) {
    const submit = (e) => {
        e.preventDefault();
        if (confirm && !window.confirm(confirm)) return;
        router.post(url, payload, { preserveScroll: true });
    };
    return <form onSubmit={onSubmit || submit} className={className}>{children}</form>;
}

export default function View({
    abnormal = {}, events = [], lookups = {}, permissions = {}, urls = {}, t = {},
}) {
    const isFinal = abnormal.status === 'resolved' || abnormal.status === 'closed_lost';

    const assignForm = useForm({ assigned_to: abnormal.assigned_to || '' });
    const submitAssign = (e) => {
        e.preventDefault();
        assignForm.put(urls.assign, { preserveScroll: true });
    };

    const resolveForm = useForm({ note: '' });
    const submitResolve = (e) => {
        e.preventDefault();
        resolveForm.put(urls.resolve, { preserveScroll: true });
    };

    const fireAction = (action, payload = {}, confirm) => {
        if (confirm && !window.confirm(confirm)) return;
        router.post(urls.action, { action, ...payload }, { preserveScroll: true });
    };

    const parcel = abnormal.parcel || {};

    return (
        <AdminLayout title={`${t.title} #${abnormal.id}`} breadcrumbs={[t.title_index, `#${abnormal.id}`]}>
            <Head title={`${t.title} #${abnormal.id}`} />

            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <a href={urls.index} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.back}
                </a>
                <div className="flex items-center gap-2">
                    <span className={cn('rounded border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider', SEVERITY_STYLES[abnormal.severity] || 'bg-muted text-muted-foreground border-border')}>
                        {abnormal.severity}
                    </span>
                    <span className={cn('rounded-md border px-2 py-0.5 text-[11px] font-semibold', STATUS_STYLES[abnormal.status] || 'bg-muted text-muted-foreground border-border')}>
                        {abnormal.status_label}
                    </span>
                    <a href={urls.parcel_view} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent">
                        <ExternalLink className="h-4 w-4 me-1" /> {t.view_parcel}
                    </a>
                </div>
            </div>

            <div className="grid gap-5 lg:grid-cols-12">
                {/* Left — parcel + timeline */}
                <div className="lg:col-span-7 space-y-5">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="mb-3 flex items-center justify-between">
                                <div className="inline-flex items-center gap-2">
                                    <AlertOctagon className="h-5 w-5 text-rose-500" />
                                    <h2 className="font-semibold text-lg font-mono">
                                        {parcel.tracking_id || `Parcel #${parcel.id}`}
                                    </h2>
                                </div>
                            </div>
                            <InfoRow label={t.customer}  value={parcel.customer_name} />
                            <InfoRow label={t.phone}     value={parcel.customer_phone && (
                                <span className="inline-flex items-center gap-1.5"><Phone className="h-3 w-3" /> {parcel.customer_phone}</span>
                            )} />
                            <InfoRow label={t.merchant}  value={parcel.merchant && (
                                <span className="inline-flex items-center gap-1.5"><Store className="h-3 w-3" /> {parcel.merchant}</span>
                            )} />
                            <InfoRow label={t.detected}  value={abnormal.detected_at} />
                            <InfoRow label={t.last_event} value={
                                abnormal.last_event_at && (
                                    <span>
                                        {abnormal.last_event_at}
                                        <span className="text-xs text-muted-foreground ms-2">({abnormal.last_event_rel})</span>
                                    </span>
                                )
                            } />
                            <InfoRow label={t.assigned_to} value={abnormal.assigned_name || t.nobody_yet} />

                            {/* Stale-days progress */}
                            <div className="mt-5">
                                <div className="mb-1.5 text-xs uppercase tracking-wide text-muted-foreground font-semibold">{t.stale_progress}</div>
                                <div className="relative h-3 rounded-full bg-muted overflow-hidden">
                                    <div
                                        className={cn('h-full transition-all',
                                            abnormal.stale_pct >= 100 ? 'bg-rose-500'
                                            : abnormal.stale_pct >= 70 ? 'bg-orange-500'
                                            : 'bg-amber-500',
                                        )}
                                        style={{ width: `${abnormal.stale_pct}%` }}
                                    />
                                </div>
                                <div className="mt-1.5 text-xs text-muted-foreground">
                                    {abnormal.stale_days} {t.days_of} {abnormal.auto_escalate} {t.days}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Timeline */}
                    <Card>
                        <CardContent className="pt-6">
                            <div className="mb-3 flex items-center gap-2 text-sm font-semibold">
                                <Activity className="h-4 w-4" /> {t.event_timeline}
                            </div>
                            {events.length === 0
                                ? <div className="text-xs text-muted-foreground py-4">{t.no_events}</div>
                                : (
                                    <div className="space-y-3">
                                        {events.map((ev) => (
                                            <div key={ev.id} className="flex gap-3 items-start">
                                                <span className={cn('mt-1.5 h-2.5 w-2.5 rounded-full shadow-sm shrink-0', STATUS_COLORS[ev.color] || STATUS_COLORS.grey)} />
                                                <div className="flex-1 min-w-0">
                                                    <div className="font-medium text-sm">{ev.label}</div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {ev.created_at}
                                                        {ev.hub_id && <span className="ms-2">· Hub #{ev.hub_id}</span>}
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                        </CardContent>
                    </Card>
                </div>

                {/* Right — actions */}
                <div className="lg:col-span-5 space-y-5">
                    {permissions.manage && (
                        <>
                            <Card>
                                <CardContent className="pt-6">
                                    <div className="mb-3 flex items-center gap-2 text-sm font-semibold">
                                        <UserIcon className="h-4 w-4 text-primary" /> {t.investigation}
                                    </div>

                                    <form onSubmit={submitAssign} className="space-y-2">
                                        <label className="text-[11px] uppercase tracking-wide text-muted-foreground font-semibold">{t.assign_investigator}</label>
                                        <div className="flex items-center gap-2">
                                            <Select value={assignForm.data.assigned_to} onChange={(e) => assignForm.setData('assigned_to', e.target.value)} className="flex-1">
                                                <option value="">—</option>
                                                {(lookups.deliverymen || []).map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                                            </Select>
                                            <Button type="submit" variant="outline" disabled={assignForm.processing}>
                                                <Send className="h-4 w-4 me-1" /> {t.assign}
                                            </Button>
                                        </div>
                                    </form>

                                    <hr className="my-4 border-border" />

                                    <div className="text-[11px] uppercase tracking-wide text-muted-foreground font-semibold mb-2">{t.take_action}</div>
                                    <div className="grid gap-2 sm:grid-cols-2">
                                        <Button type="button" variant="outline" onClick={() => { window.location.href = urls.create_ndr; }}>
                                            <AlertCircle className="h-4 w-4 me-1 text-rose-500" /> {t.create_ndr}
                                        </Button>
                                        <Button type="button" variant="outline" onClick={() => fireAction('log_contact', { note: 'Customer contact logged.' })}>
                                            <MessageSquare className="h-4 w-4 me-1 text-sky-600" /> {t.log_contact}
                                        </Button>
                                        <Button type="button" variant="outline" onClick={() => fireAction('escalate')}>
                                            <TrendingUp className="h-4 w-4 me-1 text-amber-600" /> {t.escalate}
                                        </Button>
                                        <Button type="button" variant="outline" onClick={() => fireAction('close_lost', {}, t.close_lost_warn)} className="text-destructive border-destructive/30 hover:bg-destructive/5">
                                            <X className="h-4 w-4 me-1" /> {t.close_lost}
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Resolve form */}
                            {!isFinal && (
                                <Card>
                                    <CardContent className="pt-6">
                                        <div className="mb-3 flex items-center gap-2 text-sm font-semibold">
                                            <CheckCircle2 className="h-4 w-4 text-emerald-600" /> {t.resolve}
                                        </div>
                                        <form onSubmit={submitResolve} className="space-y-2">
                                            <Textarea
                                                rows={3}
                                                value={resolveForm.data.note}
                                                onChange={(e) => resolveForm.setData('note', e.target.value)}
                                                placeholder={t.resolution_placeholder}
                                            />
                                            <Button type="submit" disabled={resolveForm.processing}>
                                                <CheckCircle2 className="h-4 w-4 me-1" /> {t.resolve}
                                            </Button>
                                        </form>
                                    </CardContent>
                                </Card>
                            )}
                        </>
                    )}

                    {/* Resolution note history */}
                    {abnormal.resolution_note && (
                        <Card>
                            <CardContent className="pt-6">
                                <div className="mb-3 flex items-center gap-2 text-sm font-semibold">
                                    <MessageSquare className="h-4 w-4 text-muted-foreground" /> {t.resolution_note}
                                </div>
                                <pre className="rounded-md bg-muted/40 p-3 text-xs whitespace-pre-wrap font-sans leading-relaxed">{abnormal.resolution_note}</pre>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
