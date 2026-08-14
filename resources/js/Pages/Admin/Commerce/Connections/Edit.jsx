import * as React from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { ArrowLeft, Save, AlertCircle, Store, CheckCircle2, XCircle, Trash2, Info, KeyRound } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';

function Field({ label, error, hint, required, children }) {
    return (
        <div className="space-y-1.5">
            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {label}{required && <span className="text-destructive ms-1">*</span>}
            </Label>
            {children}
            {hint && <p className="text-[11px] text-muted-foreground">{hint}</p>}
            {error && <p className="text-xs text-destructive flex items-center gap-1"><AlertCircle className="h-3 w-3" /> {error}</p>}
        </div>
    );
}

export default function Edit({ mode = 'create', provider = {}, connection = null, merchants = [], urls = {}, t = {} }) {
    const isEdit = mode === 'edit';
    const supports = provider.supports || [];
    const isOAuth   = supports.includes('oauth');
    const hasApiKey = !isOAuth;        // static-key providers (WC, Custom REST) — Phase 2 simple rule

    const form = useForm({
        connection_name:   connection?.connection_name ?? '',
        remote_store_id:   connection?.remote_store_id ?? '',
        domain:            connection?.domain ?? '',
        merchant_id:       connection?.merchant_id ?? '',
        access_token:      '',         // never round-trip the plaintext
        refresh_token:     '',
        token_expires_at:  connection?.token_expires_at ?? '',
        api_key:           '',
        api_secret:        '',
        webhook_secret:    '',
        status:            connection?.status ?? 'active',
        _method: isEdit ? 'put' : 'post',
    });

    const [testResult, setTestResult] = React.useState(null);
    const [testing, setTesting] = React.useState(false);

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true });
    };

    const testConnection = async () => {
        setTesting(true);
        setTestResult(null);
        try {
            const body = {
                provider:           provider.code,
                connection_name:    form.data.connection_name,
                remote_store_id:    form.data.remote_store_id,
                domain:             form.data.domain,
                merchant_id:        form.data.merchant_id,
                access_token:       form.data.access_token,
                refresh_token:      form.data.refresh_token,
                token_expires_at:   form.data.token_expires_at,
                api_key:            form.data.api_key,
                api_secret:         form.data.api_secret,
                webhook_secret:     form.data.webhook_secret,
            };
            if (isEdit && connection?.id) body.connection_id = connection.id;

            const r = await fetch(urls.test, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(body),
            });
            setTestResult(await r.json());
        } finally {
            setTesting(false);
        }
    };

    const destroy = () => {
        if (!confirm('Delete this connection? Orders mirrored from this storefront stay, but new orders will stop arriving.')) return;
        router.delete(urls.destroy);
    };

    return (
        <AdminLayout title={`${provider.name} — ${isEdit ? 'edit' : 'add'}`} breadcrumbs={[t.breadcrumb_settings, t.breadcrumb_integrations, t.breadcrumb_commerce, provider.name]}>
            <Head title={`${provider.name} — ${isEdit ? 'edit' : 'add'}`} />

            <div className="mb-4 flex items-center justify-between">
                <a href={urls.index} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.breadcrumb_commerce}
                </a>
                {isEdit && (
                    <Button type="button" onClick={destroy} className="bg-rose-600 hover:bg-rose-700 text-white">
                        <Trash2 className="h-4 w-4 me-1" /> {t.delete}
                    </Button>
                )}
            </div>

            <Card className="mb-4">
                <CardContent className="p-5 flex items-center gap-3">
                    {provider.logo_url
                        ? <img src={provider.logo_url} alt={provider.name} className="h-10 w-10 rounded object-contain" />
                        : <Store className="h-8 w-8 text-primary" />}
                    <div className="flex-1">
                        <h2 className="text-lg font-semibold">{provider.name}</h2>
                        <p className="text-xs text-muted-foreground">Provider code: <code className="font-mono">{provider.code}</code></p>
                    </div>
                </CardContent>
            </Card>

            {isOAuth && provider.code === 'salla' && (
                <Card className="mb-4 border-sky-200 bg-sky-50/60">
                    <CardContent className="p-4 flex items-start gap-3">
                        <KeyRound className="h-4 w-4 mt-0.5 shrink-0 text-sky-700" />
                        <div className="flex-1 text-xs leading-relaxed text-sky-900">
                            <p>Install via OAuth to have Salla issue fresh tokens directly onto this connection. Requires that this tenant's Salla client_id / client_secret are configured under Admin → Integrations → Salla.</p>
                            <p className="mt-1 text-sky-700">
                                Callback URL to paste into your Salla Partner Portal:
                                <code className="ms-1 font-mono">{window.location.origin}/admin/commerce/connections/salla/oauth/callback</code>
                            </p>
                        </div>
                        <a
                            href="/admin/commerce/connections/salla/oauth/redirect"
                            className="inline-flex h-9 items-center rounded-md bg-sky-600 px-3 text-sm font-medium text-white hover:bg-sky-700 shrink-0"
                        >
                            <KeyRound className="h-4 w-4 me-1" /> Install via Salla OAuth
                        </a>
                    </CardContent>
                </Card>
            )}
            {isOAuth && provider.code !== 'salla' && (
                <Card className="mb-4 border-amber-200 bg-amber-50/60">
                    <CardContent className="p-4 flex items-start gap-2 text-amber-800">
                        <Info className="h-4 w-4 mt-0.5 shrink-0" />
                        <p className="text-xs leading-relaxed">{t.oauth_install_note}</p>
                    </CardContent>
                </Card>
            )}

            <form onSubmit={onSubmit}>
                <div className="grid gap-5 lg:grid-cols-3">
                    <div className="lg:col-span-2 space-y-4">
                        <Card>
                            <CardContent className="p-6 space-y-4">
                                <Field label={t.connection_name} required error={form.errors.connection_name} hint={t.connection_name_hint}>
                                    <Input value={form.data.connection_name} onChange={(e) => form.setData('connection_name', e.target.value)} placeholder="Default" />
                                </Field>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field label={t.remote_store_id} error={form.errors.remote_store_id} hint={t.remote_store_id_hint}>
                                        <Input className="font-mono" value={form.data.remote_store_id} onChange={(e) => form.setData('remote_store_id', e.target.value)} placeholder="" />
                                    </Field>
                                    <Field label={t.domain} error={form.errors.domain} hint={t.domain_hint}>
                                        <Input value={form.data.domain} onChange={(e) => form.setData('domain', e.target.value)} placeholder="" />
                                    </Field>
                                </div>

                                <Field label={t.merchant_id} error={form.errors.merchant_id} hint={t.merchant_id_hint}>
                                    <select
                                        className="w-full h-9 rounded-md border border-input bg-background px-3 text-sm"
                                        value={form.data.merchant_id ?? ''}
                                        onChange={(e) => form.setData('merchant_id', e.target.value)}
                                    >
                                        <option value="">— select Rushly merchant —</option>
                                        {merchants.map((m) => (
                                            <option key={m.id} value={m.id}>{m.name} (#{m.id})</option>
                                        ))}
                                    </select>
                                </Field>
                            </CardContent>
                        </Card>

                        {isOAuth && (
                            <Card>
                                <CardContent className="p-6 space-y-4">
                                    <h3 className="text-sm font-semibold">OAuth credentials</h3>
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <Field label={t.access_token} error={form.errors.access_token} hint={isEdit ? t.access_token_edit_hint : null}>
                                            <Input type="password" autoComplete="new-password" value={form.data.access_token} onChange={(e) => form.setData('access_token', e.target.value)} placeholder={connection?.access_token_masked || ''} />
                                        </Field>
                                        <Field label={t.refresh_token} error={form.errors.refresh_token} hint={isEdit ? t.access_token_edit_hint : null}>
                                            <Input type="password" autoComplete="new-password" value={form.data.refresh_token} onChange={(e) => form.setData('refresh_token', e.target.value)} placeholder={connection?.refresh_token_masked || ''} />
                                        </Field>
                                    </div>
                                    <Field label={t.token_expires_at} error={form.errors.token_expires_at}>
                                        <Input type="datetime-local" value={form.data.token_expires_at?.slice(0, 16) || ''} onChange={(e) => form.setData('token_expires_at', e.target.value)} />
                                    </Field>
                                </CardContent>
                            </Card>
                        )}

                        {hasApiKey && (
                            <Card>
                                <CardContent className="p-6 space-y-4">
                                    <h3 className="text-sm font-semibold">API credentials</h3>
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <Field label={t.api_key} error={form.errors.api_key} hint={isEdit ? t.access_token_edit_hint : null}>
                                            <Input type="password" autoComplete="new-password" value={form.data.api_key} onChange={(e) => form.setData('api_key', e.target.value)} placeholder={connection?.api_key_masked || ''} />
                                        </Field>
                                        <Field label={t.api_secret} error={form.errors.api_secret} hint={isEdit ? t.access_token_edit_hint : null}>
                                            <Input type="password" autoComplete="new-password" value={form.data.api_secret} onChange={(e) => form.setData('api_secret', e.target.value)} placeholder={connection?.api_secret_masked || ''} />
                                        </Field>
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        <Card>
                            <CardContent className="p-6 space-y-4">
                                <h3 className="text-sm font-semibold">Webhook authentication</h3>
                                <Field label={t.webhook_secret} error={form.errors.webhook_secret} hint={t.webhook_secret_hint}>
                                    <Input type="password" autoComplete="new-password" value={form.data.webhook_secret} onChange={(e) => form.setData('webhook_secret', e.target.value)} placeholder={connection?.webhook_secret_masked || ''} />
                                </Field>

                                <div className="flex flex-wrap gap-2 pt-3 border-t border-border">
                                    <Button type="submit" disabled={form.processing}><Save className="h-4 w-4 me-1" /> {t.save}</Button>
                                    <Button type="button" onClick={testConnection} disabled={testing} className="bg-transparent text-foreground border border-input hover:bg-muted/40">
                                        <CheckCircle2 className="h-4 w-4 me-1" /> {t.test_connection}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <aside className="space-y-4">
                        {testResult && (
                            <Card>
                                <CardContent className="p-5">
                                    <div className="flex items-start gap-2">
                                        {testResult.ok
                                            ? <CheckCircle2 className="h-5 w-5 text-emerald-600 mt-0.5" />
                                            : <XCircle className="h-5 w-5 text-rose-600 mt-0.5" />}
                                        <div className="flex-1">
                                            <h3 className="text-sm font-semibold">{testResult.ok ? 'Connection OK' : 'Connection failed'}</h3>
                                            <p className="text-xs text-muted-foreground mt-1 break-words">{testResult.message}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {isEdit && connection && (
                            <Card>
                                <CardContent className="p-5 space-y-2 text-xs">
                                    <div className="flex justify-between"><dt className="text-muted-foreground">{t.last_tested}</dt><dd className="font-mono">{connection.last_tested_at || '—'}</dd></div>
                                    <div className="flex justify-between"><dt className="text-muted-foreground">{t.last_synced}</dt><dd className="font-mono">{connection.last_sync_at || '—'}</dd></div>
                                    <div className="flex justify-between"><dt className="text-muted-foreground">{t.last_event}</dt><dd className="font-mono">{connection.last_event_at || '—'}</dd></div>
                                    <div className="flex justify-between"><dt className="text-muted-foreground">{t.is_default}</dt><dd>{connection.is_default ? 'yes' : 'no'}</dd></div>
                                </CardContent>
                            </Card>
                        )}
                    </aside>
                </div>
            </form>
        </AdminLayout>
    );
}
