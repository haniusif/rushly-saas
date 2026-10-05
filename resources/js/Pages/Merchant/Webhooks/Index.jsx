import * as React from 'react';
import { Head, useForm, router, usePage } from '@inertiajs/react';
import {
    Webhook, Plus, Trash2, Copy, Check, AlertTriangle, ExternalLink, X,
    RefreshCw, ChevronDown, ChevronRight, Power, Loader2,
} from 'lucide-react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Input } from '@/Components/ui/Input';

// Static copy — this page is not wired into the i18n dictionary beyond its
// sidebar label, matching how other Merchant feature pages ship their text.
const T = {
    title: 'Webhooks',
    subtitle: 'Register endpoints that receive signed POSTs when your shipments change status.',
    create: 'Add endpoint',
    url: 'Endpoint URL',
    url_ph: 'https://example.com/webhooks/rushly',
    environment: 'Environment',
    live: 'Live',
    test: 'Test',
    test_unavailable: 'The Test environment is not configured yet.',
    events: 'Events',
    events_hint: 'Choose the shipment events this endpoint should receive.',
    description: 'Description',
    description_ph: 'Optional note (e.g. Production receiver)',
    submit: 'Add endpoint',
    creating: 'Saving…',
    cancel: 'Cancel',
    created: 'Created',
    last_success: 'Last success',
    last_failure: 'Last failure',
    status: 'Status',
    actions: 'Actions',
    active: 'Active',
    disabled: 'Disabled',
    rotate: 'Rotate secret',
    rotate_confirm: 'Rotate this endpoint’s signing secret? The current secret stops working immediately and you must update your receiver.',
    enable: 'Enable',
    disable: 'Disable',
    disable_confirm: 'Disable this endpoint? It will stop receiving events until you re-enable it.',
    delete: 'Delete',
    delete_confirm: 'Delete this endpoint? It will be disabled and stop receiving events.',
    deliveries: 'Deliveries',
    deliveries_title: 'Recent deliveries',
    deliveries_empty: 'No deliveries recorded yet.',
    deliveries_error: 'Could not load deliveries.',
    del_event: 'Event',
    del_status: 'Status',
    del_code: 'Code',
    del_attempts: 'Attempts',
    del_error: 'Last error',
    del_created: 'Created',
    del_delivered: 'Delivered',
    empty: 'You have no webhook endpoints yet. Add one to get started.',
    never: 'Never',
    secret_title: 'Copy your signing secret now',
    secret_warning: 'This is the only time the secret will be shown. Store it somewhere safe — you will not be able to see it again. Use it to verify the signature on each incoming POST.',
    copy: 'Copy',
    copied: 'Copied',
    docs: 'Read the webhooks documentation',
    no_env: 'No webhook environment is configured. Please contact support.',
};

const EVENT_LABELS = {
    'shipment.created': 'Created',
    'shipment.pickup_scheduled': 'Pickup scheduled',
    'shipment.picked_up': 'Picked up',
    'shipment.at_hub': 'At hub',
    'shipment.in_transit': 'In transit',
    'shipment.out_for_delivery': 'Out for delivery',
    'shipment.delivered': 'Delivered',
    'shipment.delivery_failed': 'Delivery failed',
    'shipment.returning': 'Returning',
    'shipment.returned': 'Returned',
    'shipment.cancelled': 'Cancelled',
};

function EnvBadge({ environment }) {
    const isLive = environment === 'live';
    return (
        <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border ${
            isLive
                ? 'bg-indigo-50 text-indigo-700 border-indigo-200'
                : 'bg-amber-50 text-amber-700 border-amber-200'
        }`}>
            {isLive ? T.live : T.test}
        </span>
    );
}

function StatusPill({ active }) {
    return (
        <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border ${
            active
                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                : 'bg-rose-50 text-rose-700 border-rose-200'
        }`}>
            {active ? T.active : T.disabled}
        </span>
    );
}

function DeliveryStatusPill({ status }) {
    const map = {
        delivered: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        failed: 'bg-rose-50 text-rose-700 border-rose-200',
        pending: 'bg-amber-50 text-amber-700 border-amber-200',
    };
    return (
        <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium border ${map[status] || 'bg-muted/40 text-muted-foreground border-border'}`}>
            {status || '—'}
        </span>
    );
}

function CopyButton({ value, label = T.copy, labelCopied = T.copied, className = '' }) {
    const [copied, setCopied] = React.useState(false);
    const copy = async () => {
        try {
            if (navigator?.clipboard?.writeText) {
                await navigator.clipboard.writeText(value);
            } else {
                const ta = document.createElement('textarea');
                ta.value = value;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
            }
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch (e) { /* clipboard blocked */ }
    };
    return (
        <button
            type="button"
            onClick={copy}
            className={`inline-flex items-center gap-1.5 h-9 px-3 text-sm font-medium rounded-md border border-input bg-background hover:bg-muted/40 ${className}`}
        >
            {copied ? <Check className="h-4 w-4 text-emerald-600" /> : <Copy className="h-4 w-4" />}
            {copied ? labelCopied : label}
        </button>
    );
}

function fmtDate(value) {
    if (!value) return T.never;
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function SecretReveal({ secret, endpoint }) {
    if (!secret) return null;
    return (
        <Card className="mb-5 border-emerald-300">
            <CardContent className="p-5">
                <div className="flex items-start gap-3">
                    <AlertTriangle className="h-5 w-5 text-amber-500 shrink-0 mt-0.5" />
                    <div className="flex-1 min-w-0">
                        <h3 className="text-sm font-semibold m-0">{T.secret_title}</h3>
                        <p className="text-xs text-muted-foreground mt-1 mb-3">{T.secret_warning}</p>
                        <div className="flex flex-col sm:flex-row gap-2 sm:items-center">
                            <code className="flex-1 min-w-0 truncate rounded-md border border-input bg-muted/40 px-3 py-2 text-sm font-mono">
                                {secret}
                            </code>
                            <CopyButton value={secret} />
                        </div>
                        {endpoint?.url && (
                            <p className="text-[11px] text-muted-foreground mt-2 truncate">
                                {endpoint.url} · <EnvBadge environment={endpoint.environment} />
                            </p>
                        )}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

function CreateDialog({ open, onClose, availableEnvironments, events, docsUrl }) {
    const defaultEnv = availableEnvironments[0] || 'live';
    const form = useForm({
        url: '',
        environment: defaultEnv,
        events: [],
        description: '',
    });

    React.useEffect(() => {
        if (open) form.setData('environment', availableEnvironments[0] || 'live');
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]);

    if (!open) return null;

    const testAvailable = availableEnvironments.includes('test');

    const toggleEvent = (event) => {
        const has = form.data.events.includes(event);
        form.setData('events', has
            ? form.data.events.filter((s) => s !== event)
            : [...form.data.events, event]);
    };

    const submit = (e) => {
        e.preventDefault();
        form.post(route('merchant-panel.webhooks.store'), {
            preserveScroll: true,
            onSuccess: () => { form.reset(); onClose(); },
        });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div className="absolute inset-0 bg-black/40 backdrop-blur-sm" onClick={onClose} />
            <div className="relative w-full max-w-lg">
                <Card>
                    <CardContent className="p-0">
                        <div className="flex items-center justify-between px-5 py-4 border-b border-border">
                            <h2 className="text-base font-semibold m-0 flex items-center gap-2">
                                <Webhook className="h-4 w-4 text-primary" /> {T.create}
                            </h2>
                            <button type="button" onClick={onClose} className="text-muted-foreground hover:text-foreground">
                                <X className="h-4 w-4" />
                            </button>
                        </div>
                        <form onSubmit={submit}>
                            <div className="p-5 space-y-5 max-h-[70vh] overflow-y-auto">
                                <div className="space-y-1.5">
                                    <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                        {T.url} <span className="text-destructive">*</span>
                                    </label>
                                    <Input
                                        value={form.data.url}
                                        onChange={(e) => form.setData('url', e.target.value)}
                                        placeholder={T.url_ph}
                                        maxLength={2048}
                                    />
                                    {form.errors.url && <p className="text-xs text-destructive">{form.errors.url}</p>}
                                </div>

                                <div className="space-y-1.5">
                                    <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                        {T.environment} <span className="text-destructive">*</span>
                                    </label>
                                    <div className="flex gap-2">
                                        {['live', 'test'].map((env) => {
                                            const disabled = !availableEnvironments.includes(env);
                                            const selected = form.data.environment === env;
                                            return (
                                                <button
                                                    key={env}
                                                    type="button"
                                                    disabled={disabled}
                                                    onClick={() => form.setData('environment', env)}
                                                    className={`flex-1 h-10 rounded-md border text-sm font-medium transition-colors ${
                                                        selected
                                                            ? 'border-primary bg-primary/10 text-primary'
                                                            : 'border-input bg-background hover:bg-muted/40'
                                                    } ${disabled ? 'opacity-50 cursor-not-allowed' : ''}`}
                                                >
                                                    {env === 'live' ? T.live : T.test}
                                                </button>
                                            );
                                        })}
                                    </div>
                                    {!testAvailable && (
                                        <p className="text-[11px] text-muted-foreground">{T.test_unavailable}</p>
                                    )}
                                    {form.errors.environment && <p className="text-xs text-destructive">{form.errors.environment}</p>}
                                </div>

                                <div className="space-y-2">
                                    <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                        {T.events} <span className="text-destructive">*</span>
                                    </label>
                                    <p className="text-[11px] text-muted-foreground -mt-1">{T.events_hint}</p>
                                    <div className="grid gap-2 sm:grid-cols-2">
                                        {events.map((event) => (
                                            <label
                                                key={event}
                                                className="flex items-center gap-2 rounded-md border border-input px-3 py-2 text-sm cursor-pointer hover:bg-muted/40"
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={form.data.events.includes(event)}
                                                    onChange={() => toggleEvent(event)}
                                                    className="h-4 w-4 rounded border-input"
                                                />
                                                <span className="min-w-0">
                                                    <span className="block truncate">{EVENT_LABELS[event] || event}</span>
                                                    <span className="block text-[10px] text-muted-foreground font-mono truncate">{event}</span>
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                    {form.errors.events && <p className="text-xs text-destructive">{form.errors.events}</p>}
                                </div>

                                <div className="space-y-1.5">
                                    <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                        {T.description}
                                    </label>
                                    <Input
                                        value={form.data.description}
                                        onChange={(e) => form.setData('description', e.target.value)}
                                        placeholder={T.description_ph}
                                        maxLength={191}
                                    />
                                    {form.errors.description && <p className="text-xs text-destructive">{form.errors.description}</p>}
                                </div>

                                {docsUrl && (
                                    <a
                                        href={docsUrl}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="inline-flex items-center gap-1.5 text-xs text-primary hover:underline"
                                    >
                                        <ExternalLink className="h-3 w-3" /> {T.docs}
                                    </a>
                                )}
                            </div>
                            <div className="flex items-center justify-end gap-2 px-5 py-4 border-t border-border">
                                <button
                                    type="button"
                                    onClick={onClose}
                                    className="inline-flex items-center h-10 px-4 text-sm font-medium rounded-md border border-input bg-background hover:bg-muted/40"
                                >
                                    {T.cancel}
                                </button>
                                <button
                                    type="submit"
                                    disabled={form.processing || availableEnvironments.length === 0}
                                    className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:opacity-90 disabled:opacity-50"
                                >
                                    <Plus className="h-4 w-4" /> {form.processing ? T.creating : T.submit}
                                </button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}

function DeliveriesPanel({ endpoint }) {
    const [state, setState] = React.useState({ loading: true, error: null, rows: [] });

    React.useEffect(() => {
        let cancelled = false;
        setState({ loading: true, error: null, rows: [] });
        const url = route('merchant-panel.webhooks.deliveries', endpoint.uuid)
            + '?environment=' + encodeURIComponent(endpoint.environment);
        fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then((r) => r.json())
            .then((json) => {
                if (cancelled) return;
                if (json?.success) {
                    setState({ loading: false, error: null, rows: Array.isArray(json.data) ? json.data : [] });
                } else {
                    setState({ loading: false, error: json?.error || T.deliveries_error, rows: [] });
                }
            })
            .catch(() => {
                if (!cancelled) setState({ loading: false, error: T.deliveries_error, rows: [] });
            });
        return () => { cancelled = true; };
    }, [endpoint.uuid, endpoint.environment]);

    return (
        <div className="bg-muted/20 border-t border-border px-4 py-3">
            <h4 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-2">{T.deliveries_title}</h4>
            {state.loading ? (
                <div className="flex items-center gap-2 text-sm text-muted-foreground py-3">
                    <Loader2 className="h-4 w-4 animate-spin" /> …
                </div>
            ) : state.error ? (
                <div className="text-sm text-rose-700 py-2">{state.error}</div>
            ) : state.rows.length === 0 ? (
                <div className="text-sm text-muted-foreground py-2">{T.deliveries_empty}</div>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full text-xs">
                        <thead className="text-[10px] uppercase tracking-wide text-muted-foreground">
                            <tr>
                                <th className="text-start font-medium px-2 py-1.5">{T.del_event}</th>
                                <th className="text-start font-medium px-2 py-1.5">{T.del_status}</th>
                                <th className="text-start font-medium px-2 py-1.5">{T.del_code}</th>
                                <th className="text-start font-medium px-2 py-1.5">{T.del_attempts}</th>
                                <th className="text-start font-medium px-2 py-1.5">{T.del_error}</th>
                                <th className="text-start font-medium px-2 py-1.5">{T.del_created}</th>
                                <th className="text-start font-medium px-2 py-1.5">{T.del_delivered}</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {state.rows.map((d) => (
                                <tr key={d.uuid}>
                                    <td className="px-2 py-1.5 font-mono">{d.event}</td>
                                    <td className="px-2 py-1.5"><DeliveryStatusPill status={d.status} /></td>
                                    <td className="px-2 py-1.5 text-muted-foreground">{d.response_code ?? '—'}</td>
                                    <td className="px-2 py-1.5 text-muted-foreground">{d.attempts ?? '—'}</td>
                                    <td className="px-2 py-1.5 text-muted-foreground max-w-[220px] truncate" title={d.last_error || ''}>{d.last_error || '—'}</td>
                                    <td className="px-2 py-1.5 whitespace-nowrap text-muted-foreground">{fmtDate(d.created_at)}</td>
                                    <td className="px-2 py-1.5 whitespace-nowrap text-muted-foreground">{fmtDate(d.delivered_at)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}

export default function Index({
    endpoints = [],
    availableEnvironments = [],
    events = [],
    docsUrl = null,
    loadError = null,
    createdSecret = null,
    createdEndpoint = null,
}) {
    const { props } = usePage();
    const flash = props?.flash || {};
    const [dialogOpen, setDialogOpen] = React.useState(false);
    const [expanded, setExpanded] = React.useState(null);

    const rotate = (endpoint) => {
        if (typeof window !== 'undefined' && !window.confirm(T.rotate_confirm)) return;
        router.post(
            route('merchant-panel.webhooks.rotate-secret', endpoint.uuid),
            { environment: endpoint.environment },
            { preserveScroll: true },
        );
    };

    const toggleActive = (endpoint) => {
        if (endpoint.is_active && typeof window !== 'undefined' && !window.confirm(T.disable_confirm)) return;
        router.post(
            route('merchant-panel.webhooks.update', endpoint.uuid),
            { environment: endpoint.environment, is_active: !endpoint.is_active },
            { preserveScroll: true },
        );
    };

    const remove = (endpoint) => {
        if (typeof window !== 'undefined' && !window.confirm(T.delete_confirm)) return;
        router.delete(
            route('merchant-panel.webhooks.destroy', endpoint.uuid),
            { data: { environment: endpoint.environment }, preserveScroll: true },
        );
    };

    const toggleExpand = (endpoint) => {
        setExpanded((cur) => (cur === endpoint.uuid ? null : endpoint.uuid));
    };

    const noEnv = availableEnvironments.length === 0;

    return (
        <MerchantLayout title={T.title} breadcrumbs={['Dashboard', T.title]}>
            <Head title={T.title} />

            {flash.error && (
                <div className="mb-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {flash.error}
                </div>
            )}
            {loadError && !flash.error && (
                <div className="mb-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {loadError}
                </div>
            )}

            <SecretReveal secret={createdSecret} endpoint={createdEndpoint} />

            <Card>
                <CardContent className="p-0">
                    <div className="flex items-center justify-between gap-3 px-5 py-4 border-b border-border">
                        <div className="min-w-0">
                            <h2 className="text-base font-semibold m-0">{T.title}</h2>
                            <p className="text-xs text-muted-foreground mt-0.5">{T.subtitle}</p>
                        </div>
                        <button
                            type="button"
                            onClick={() => setDialogOpen(true)}
                            disabled={noEnv}
                            className="inline-flex items-center gap-1.5 h-9 px-3 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:opacity-90 disabled:opacity-50 shrink-0"
                        >
                            <Plus className="h-4 w-4" /> {T.create}
                        </button>
                    </div>

                    {noEnv ? (
                        <div className="p-8 text-center text-sm text-muted-foreground">{T.no_env}</div>
                    ) : endpoints.length === 0 ? (
                        <div className="p-8 text-center text-sm text-muted-foreground">{T.empty}</div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/30 text-xs uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th className="text-start font-medium px-4 py-2.5 w-6"></th>
                                        <th className="text-start font-medium px-4 py-2.5">{T.url}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{T.environment}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{T.events}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{T.last_success}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{T.last_failure}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{T.status}</th>
                                        <th className="text-end font-medium px-4 py-2.5 w-44">{T.actions}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {endpoints.map((ep) => (
                                        <React.Fragment key={`${ep.environment}-${ep.uuid}`}>
                                            <tr>
                                                <td className="px-4 py-2.5 align-top">
                                                    <button
                                                        type="button"
                                                        onClick={() => toggleExpand(ep)}
                                                        className="text-muted-foreground hover:text-foreground"
                                                        title={T.deliveries}
                                                    >
                                                        {expanded === ep.uuid ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                                                    </button>
                                                </td>
                                                <td className="px-4 py-2.5">
                                                    <div className="font-medium max-w-[280px] truncate" title={ep.url}>{ep.url}</div>
                                                    {ep.description && (
                                                        <div className="text-[11px] text-muted-foreground max-w-[280px] truncate">{ep.description}</div>
                                                    )}
                                                </td>
                                                <td className="px-4 py-2.5"><EnvBadge environment={ep.environment} /></td>
                                                <td className="px-4 py-2.5">
                                                    <div className="flex flex-wrap gap-1 max-w-[260px]">
                                                        {(ep.events || []).map((s) => (
                                                            <span key={s} className="inline-flex items-center rounded border border-border bg-muted/40 px-1.5 py-0.5 text-[10px] font-mono">
                                                                {s.replace('shipment.', '')}
                                                            </span>
                                                        ))}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-2.5 whitespace-nowrap text-muted-foreground">{fmtDate(ep.last_success_at)}</td>
                                                <td className="px-4 py-2.5 whitespace-nowrap text-muted-foreground">{fmtDate(ep.last_failure_at)}</td>
                                                <td className="px-4 py-2.5"><StatusPill active={!!ep.is_active} /></td>
                                                <td className="px-4 py-2.5">
                                                    <div className="flex items-center justify-end gap-1.5">
                                                        <button
                                                            type="button"
                                                            onClick={() => rotate(ep)}
                                                            className="inline-flex items-center gap-1 h-7 px-2 text-xs rounded-md border border-input bg-background hover:bg-muted/40"
                                                            title={T.rotate}
                                                        >
                                                            <RefreshCw className="h-3 w-3" /> {T.rotate}
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => toggleActive(ep)}
                                                            className="inline-flex items-center gap-1 h-7 px-2 text-xs rounded-md border border-input bg-background hover:bg-muted/40"
                                                            title={ep.is_active ? T.disable : T.enable}
                                                        >
                                                            <Power className="h-3 w-3" /> {ep.is_active ? T.disable : T.enable}
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => remove(ep)}
                                                            className="inline-flex items-center gap-1 h-7 px-2 text-xs rounded-md border border-rose-200 text-rose-700 hover:bg-rose-50"
                                                            title={T.delete}
                                                        >
                                                            <Trash2 className="h-3 w-3" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            {expanded === ep.uuid && (
                                                <tr>
                                                    <td colSpan={8} className="p-0">
                                                        <DeliveriesPanel endpoint={ep} />
                                                    </td>
                                                </tr>
                                            )}
                                        </React.Fragment>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {docsUrl && (
                        <div className="px-5 py-3 border-t border-border">
                            <a
                                href={docsUrl}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex items-center gap-1.5 text-xs text-primary hover:underline"
                            >
                                <ExternalLink className="h-3 w-3" /> {T.docs}
                            </a>
                        </div>
                    )}
                </CardContent>
            </Card>

            <CreateDialog
                open={dialogOpen}
                onClose={() => setDialogOpen(false)}
                availableEnvironments={availableEnvironments}
                events={events}
                docsUrl={docsUrl}
            />
        </MerchantLayout>
    );
}
