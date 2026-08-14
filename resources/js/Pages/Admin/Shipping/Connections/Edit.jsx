import * as React from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { ArrowLeft, Save, AlertCircle, Truck, CheckCircle2, XCircle, Search, Trash2 } from 'lucide-react';
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

export default function Edit({ mode = 'create', provider = {}, connection = null, urls = {}, t = {} }) {
    const isEdit = mode === 'edit';

    const form = useForm({
        connection_name:   connection?.connection_name ?? '',
        domain:            connection?.domain ?? '',
        remote_company_id: connection?.remote_company_id ?? '',
        email:             connection?.email ?? '',
        password:          isEdit ? '' : '',
        status:            connection?.status ?? 'active',
        _method: isEdit ? 'put' : 'post',
    });

    const [testResult, setTestResult] = React.useState(null);
    const [resolving, setResolving] = React.useState(false);
    const [testing, setTesting] = React.useState(false);

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true });
    };

    const resolveDomain = async () => {
        if (!form.data.domain) return;
        setResolving(true);
        try {
            const r = await fetch(urls.resolve_domain, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                body: JSON.stringify({ domain: form.data.domain }),
            });
            const json = await r.json();
            if (json.ok && json.remote_company_id) {
                form.setData('remote_company_id', String(json.remote_company_id));
            } else {
                alert(json.message || 'No match — paste the company id manually.');
            }
        } finally {
            setResolving(false);
        }
    };

    const testConnection = async () => {
        setTesting(true);
        setTestResult(null);
        try {
            // For edits, we don't have the saved password client-side — pass
            // connection_id so the backend can hydrate it from the row. We
            // still send any typed value so the user can test a NEW password
            // before saving.
            const body = {
                provider:          provider.code,
                connection_name:   form.data.connection_name,
                domain:            form.data.domain,
                remote_company_id: form.data.remote_company_id,
                email:             form.data.email,
                password:          form.data.password,
            };
            if (isEdit && connection?.id) body.connection_id = connection.id;

            const r = await fetch(urls.test, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                body: JSON.stringify(body),
            });
            setTestResult(await r.json());
        } finally {
            setTesting(false);
        }
    };

    const destroy = () => {
        if (!confirm('Delete this connection? Shipments tied to it stay, but new assigns will need a different connection.')) return;
        router.delete(urls.destroy);
    };

    return (
        <AdminLayout title={`${provider.name} — ${isEdit ? 'edit' : 'add'}`} breadcrumbs={[t.breadcrumb_settings, t.breadcrumb_integrations, t.breadcrumb_shipping, provider.name]}>
            <Head title={`${provider.name} — ${isEdit ? 'edit' : 'add'}`} />

            <div className="mb-4 flex items-center justify-between">
                <a href={urls.index} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.breadcrumb_shipping}
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
                        : <Truck className="h-8 w-8 text-primary" />}
                    <div>
                        <h2 className="text-lg font-semibold">{provider.name}</h2>
                        <p className="text-xs text-muted-foreground">Provider code: <code className="font-mono">{provider.code}</code></p>
                    </div>
                </CardContent>
            </Card>

            <form onSubmit={onSubmit}>
                <div className="grid gap-5 lg:grid-cols-3">
                    <div className="lg:col-span-2 space-y-4">
                        <Card>
                            <CardContent className="p-6 space-y-4">
                                <Field label={t.connection_name} required error={form.errors.connection_name} hint={t.connection_name_hint}>
                                    <Input value={form.data.connection_name} onChange={(e) => form.setData('connection_name', e.target.value)} placeholder="Default" />
                                </Field>

                                <Field label={t.domain} error={form.errors.domain} hint={t.domain_hint}>
                                    <div className="flex gap-2">
                                        <Input className="flex-1" value={form.data.domain} onChange={(e) => form.setData('domain', e.target.value)} placeholder="salesksa.logestechs.com" />
                                        <Button type="button" onClick={resolveDomain} disabled={resolving || !form.data.domain} className="bg-transparent text-foreground border border-input hover:bg-muted/40 shrink-0">
                                            <Search className="h-4 w-4 me-1" /> {t.resolve_domain}
                                        </Button>
                                    </div>
                                </Field>

                                <Field label={t.remote_company_id} error={form.errors.remote_company_id} hint={t.remote_company_id_hint}>
                                    <Input className="font-mono" value={form.data.remote_company_id} onChange={(e) => form.setData('remote_company_id', e.target.value)} placeholder="496" />
                                </Field>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field label={t.email} required error={form.errors.email}>
                                        <Input type="email" autoComplete="off" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} placeholder="account@example.com" />
                                    </Field>
                                    <Field label={t.password} required={!isEdit} error={form.errors.password} hint={isEdit ? t.password_edit_hint : null}>
                                        <Input type="password" autoComplete="new-password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} placeholder={isEdit ? '••••••' : ''} />
                                    </Field>
                                </div>

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
