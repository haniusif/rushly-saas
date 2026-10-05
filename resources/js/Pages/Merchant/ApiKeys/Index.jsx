import * as React from 'react';
import { Head, useForm, router, usePage } from '@inertiajs/react';
import {
    KeyRound, Plus, Trash2, Copy, Check, AlertTriangle, ExternalLink, X,
} from 'lucide-react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Input } from '@/Components/ui/Input';

// Static copy — this page is not wired into the i18n dictionary beyond its
// sidebar label, matching how other Merchant feature pages ship their text.
const T = {
    title: 'API Keys',
    subtitle: 'Create and manage your Rushly Merchant API keys for the Live and Test environments.',
    create: 'Create key',
    name: 'Name',
    name_ph: 'e.g. Production server',
    environment: 'Environment',
    live: 'Live',
    test: 'Test',
    test_unavailable: 'The Test environment is not configured yet.',
    scopes: 'Scopes',
    scopes_hint: 'Choose the permissions this key is allowed to use.',
    submit: 'Create key',
    creating: 'Creating…',
    cancel: 'Cancel',
    key: 'Key',
    created: 'Created',
    last_used: 'Last used',
    status: 'Status',
    actions: 'Actions',
    active: 'Active',
    revoked: 'Revoked',
    revoke: 'Revoke',
    revoke_confirm: 'Revoke this API key? Applications using it will stop working immediately.',
    empty: 'You have no API keys yet. Create one to get started.',
    never: 'Never',
    token_title: 'Copy your new API key now',
    token_warning: 'This is the only time the full key will be shown. Store it somewhere safe — you will not be able to see it again.',
    copy: 'Copy',
    copied: 'Copied',
    docs: 'Read the API documentation',
    no_env: 'No API environment is configured. Please contact support.',
};

const SCOPE_LABELS = {
    'shipments:create': 'Create shipments',
    'shipments:read': 'Read shipments',
    'shipments:cancel': 'Cancel shipments',
    'tracking:read': 'Read tracking',
    'statuses:read': 'Read statuses',
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
            {active ? T.active : T.revoked}
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
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

function TokenReveal({ token, client }) {
    if (!token) return null;
    return (
        <Card className="mb-5 border-emerald-300">
            <CardContent className="p-5">
                <div className="flex items-start gap-3">
                    <AlertTriangle className="h-5 w-5 text-amber-500 shrink-0 mt-0.5" />
                    <div className="flex-1 min-w-0">
                        <h3 className="text-sm font-semibold m-0">{T.token_title}</h3>
                        <p className="text-xs text-muted-foreground mt-1 mb-3">{T.token_warning}</p>
                        <div className="flex flex-col sm:flex-row gap-2 sm:items-center">
                            <code className="flex-1 min-w-0 truncate rounded-md border border-input bg-muted/40 px-3 py-2 text-sm font-mono">
                                {token}
                            </code>
                            <CopyButton value={token} />
                        </div>
                        {client?.name && (
                            <p className="text-[11px] text-muted-foreground mt-2">
                                {client.name} · <EnvBadge environment={client.environment} />
                            </p>
                        )}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

function CreateDialog({ open, onClose, availableEnvironments, scopes, docsUrl }) {
    const defaultEnv = availableEnvironments[0] || 'live';
    const form = useForm({
        name: '',
        environment: defaultEnv,
        scopes: [],
    });

    React.useEffect(() => {
        if (open) form.setData('environment', availableEnvironments[0] || 'live');
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]);

    if (!open) return null;

    const testAvailable = availableEnvironments.includes('test');

    const toggleScope = (scope) => {
        const has = form.data.scopes.includes(scope);
        form.setData('scopes', has
            ? form.data.scopes.filter((s) => s !== scope)
            : [...form.data.scopes, scope]);
    };

    const submit = (e) => {
        e.preventDefault();
        form.post(route('merchant-panel.api-keys.store'), {
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
                                <KeyRound className="h-4 w-4 text-primary" /> {T.create}
                            </h2>
                            <button type="button" onClick={onClose} className="text-muted-foreground hover:text-foreground">
                                <X className="h-4 w-4" />
                            </button>
                        </div>
                        <form onSubmit={submit}>
                            <div className="p-5 space-y-5">
                                <div className="space-y-1.5">
                                    <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                        {T.name} <span className="text-destructive">*</span>
                                    </label>
                                    <Input
                                        value={form.data.name}
                                        onChange={(e) => form.setData('name', e.target.value)}
                                        placeholder={T.name_ph}
                                        maxLength={120}
                                    />
                                    {form.errors.name && <p className="text-xs text-destructive">{form.errors.name}</p>}
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
                                        {T.scopes} <span className="text-destructive">*</span>
                                    </label>
                                    <p className="text-[11px] text-muted-foreground -mt-1">{T.scopes_hint}</p>
                                    <div className="grid gap-2 sm:grid-cols-2">
                                        {scopes.map((scope) => (
                                            <label
                                                key={scope}
                                                className="flex items-center gap-2 rounded-md border border-input px-3 py-2 text-sm cursor-pointer hover:bg-muted/40"
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={form.data.scopes.includes(scope)}
                                                    onChange={() => toggleScope(scope)}
                                                    className="h-4 w-4 rounded border-input"
                                                />
                                                <span className="min-w-0">
                                                    <span className="block truncate">{SCOPE_LABELS[scope] || scope}</span>
                                                    <span className="block text-[10px] text-muted-foreground font-mono truncate">{scope}</span>
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                    {form.errors.scopes && <p className="text-xs text-destructive">{form.errors.scopes}</p>}
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

export default function Index({
    keys = [],
    availableEnvironments = [],
    scopes = [],
    docsUrl = null,
    loadError = null,
    createdToken = null,
    createdClient = null,
}) {
    const { props } = usePage();
    const flash = props?.flash || {};
    const [dialogOpen, setDialogOpen] = React.useState(false);

    const revoke = (client) => {
        if (typeof window !== 'undefined' && !window.confirm(T.revoke_confirm)) return;
        router.post(
            route('merchant-panel.api-keys.revoke', client.uuid),
            { environment: client.environment },
            { preserveScroll: true },
        );
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

            <TokenReveal token={createdToken} client={createdClient} />

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
                    ) : keys.length === 0 ? (
                        <div className="p-8 text-center text-sm text-muted-foreground">{T.empty}</div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/30 text-xs uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th className="text-start font-medium px-4 py-2.5">{T.name}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{T.environment}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{T.key}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{T.scopes}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{T.created}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{T.last_used}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{T.status}</th>
                                        <th className="text-end font-medium px-4 py-2.5 w-24">{T.actions}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {keys.map((k) => (
                                        <tr key={`${k.environment}-${k.uuid}`}>
                                            <td className="px-4 py-2.5 font-medium">{k.name}</td>
                                            <td className="px-4 py-2.5"><EnvBadge environment={k.environment} /></td>
                                            <td className="px-4 py-2.5">
                                                <code className="text-xs font-mono text-muted-foreground">
                                                    {k.key_prefix ? `${k.key_prefix}…` : '—'}
                                                </code>
                                            </td>
                                            <td className="px-4 py-2.5">
                                                <div className="flex flex-wrap gap-1 max-w-[260px]">
                                                    {(k.scopes || []).map((s) => (
                                                        <span key={s} className="inline-flex items-center rounded border border-border bg-muted/40 px-1.5 py-0.5 text-[10px] font-mono">
                                                            {s}
                                                        </span>
                                                    ))}
                                                </div>
                                            </td>
                                            <td className="px-4 py-2.5 whitespace-nowrap text-muted-foreground">{fmtDate(k.created_at)}</td>
                                            <td className="px-4 py-2.5 whitespace-nowrap text-muted-foreground">{fmtDate(k.last_used_at)}</td>
                                            <td className="px-4 py-2.5"><StatusPill active={!!k.is_active} /></td>
                                            <td className="px-4 py-2.5 text-end">
                                                {k.is_active ? (
                                                    <button
                                                        type="button"
                                                        onClick={() => revoke(k)}
                                                        className="inline-flex items-center gap-1.5 h-7 px-2.5 text-xs rounded-md border border-rose-200 text-rose-700 hover:bg-rose-50"
                                                    >
                                                        <Trash2 className="h-3 w-3" /> {T.revoke}
                                                    </button>
                                                ) : (
                                                    <span className="text-xs text-muted-foreground">—</span>
                                                )}
                                            </td>
                                        </tr>
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
                scopes={scopes}
                docsUrl={docsUrl}
            />
        </MerchantLayout>
    );
}
