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

/**
 * Read a value out of the form state by a spec field name. `settings.foo`
 * reaches into the settings bag; anything else is a top-level column.
 */
const readField = (data, name) =>
    name.startsWith('settings.')
        ? (data.settings?.[name.slice('settings.'.length)] ?? '')
        : (data[name] ?? '');

export default function Edit({ mode = 'create', provider = {}, connection = null, fields = [], urls = {}, t = {} }) {
    const isEdit = mode === 'edit';

    // Seed the form from the provider's field spec rather than a fixed shape.
    // A secret with a stored value arrives as the '••••••' mask; it is kept as
    // the initial value so the operator can see it is already set, and the
    // backend drops the mask on save rather than storing it.
    const initial = React.useMemo(() => {
        const top = { connection_name: connection?.connection_name ?? '', settings: {} };
        for (const f of fields) {
            if (f.name.startsWith('settings.')) {
                const k = f.name.slice('settings.'.length);
                top.settings[k] = connection?.settings?.[k] ?? '';
            } else {
                top[f.name] = connection?.[f.name] ?? '';
            }
        }
        return top;
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const form = useForm({
        ...initial,
        status:  connection?.status ?? 'active',
        _method: isEdit ? 'put' : 'post',
    });

    const setField = (name, value) => {
        if (name.startsWith('settings.')) {
            const k = name.slice('settings.'.length);
            form.setData('settings', { ...(form.data.settings || {}), [k]: value });
        } else {
            form.setData(name, value);
        }
    };

    // Only render the domain-resolve control when a field asks for it.
    const resolveField = fields.find((f) => f.resolve);

    const [testResult, setTestResult] = React.useState(null);
    const [resolving, setResolving] = React.useState(false);
    const [testing, setTesting] = React.useState(false);

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true });
    };

    const resolveDomain = async () => {
        if (!resolveField) return;
        const value = readField(form.data, resolveField.name);
        if (!value) return;
        setResolving(true);
        try {
            const r = await fetch(urls.resolve_domain, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                body: JSON.stringify({ domain: value }),
            });
            const json = await r.json();
            if (json.ok && json.remote_company_id) {
                setField('remote_company_id', String(json.remote_company_id));
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
            // Send every field the provider declares, not a fixed four. The
            // backend hydrates anything blank (a secret left untouched) from
            // the stored row, so the test exercises real credentials.
            const body = { provider: provider.code, connection_name: form.data.connection_name };
            for (const f of fields) {
                if (f.name.startsWith('settings.')) {
                    body.settings = body.settings || {};
                    body.settings[f.name.slice('settings.'.length)] = readField(form.data, f.name);
                } else {
                    body[f.name] = readField(form.data, f.name);
                }
            }
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

                                {/* Fields come from the provider's spec in
                                    config/shipping.php. Logestechs renders
                                    domain + company id + email + password;
                                    EcoExpress renders client id + secret +
                                    account number. Neither is hardcoded here. */}
                                {fields.map((f) => {
                                    const err = form.errors[f.name];
                                    const val = readField(form.data, f.name);
                                    const storedSecret = f.secret && isEdit && String(val).startsWith('\u2022');

                                    return (
                                        <Field
                                            key={f.name}
                                            label={f.label}
                                            required={f.required && !(isEdit && f.secret)}
                                            error={err}
                                            hint={storedSecret ? t.password_edit_hint : (f.hint || null)}
                                        >
                                            {f.resolve ? (
                                                <div className="flex gap-2">
                                                    <Input
                                                        className="flex-1"
                                                        value={val}
                                                        onChange={(e) => setField(f.name, e.target.value)}
                                                        placeholder={f.placeholder || ''}
                                                    />
                                                    <Button
                                                        type="button"
                                                        onClick={resolveDomain}
                                                        disabled={resolving || !val}
                                                        className="bg-transparent text-foreground border border-input hover:bg-muted/40 shrink-0"
                                                    >
                                                        <Search className="h-4 w-4 me-1" /> {t.resolve_domain}
                                                    </Button>
                                                </div>
                                            ) : (
                                                <Input
                                                    type={f.type === 'password' ? 'password' : (f.type === 'email' ? 'email' : 'text')}
                                                    autoComplete={f.secret ? 'new-password' : 'off'}
                                                    className={f.mono ? 'font-mono' : undefined}
                                                    value={val}
                                                    onChange={(e) => setField(f.name, e.target.value)}
                                                    placeholder={f.placeholder || ''}
                                                    onFocus={storedSecret ? (e) => { setField(f.name, ''); e.target.value = ''; } : undefined}
                                                />
                                            )}
                                        </Field>
                                    );
                                })}

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
