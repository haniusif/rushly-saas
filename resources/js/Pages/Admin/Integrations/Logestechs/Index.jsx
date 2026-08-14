import * as React from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { ArrowLeft, Save, AlertCircle, Plug, CheckCircle2, XCircle, Truck } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { cn } from '@/lib/utils';

function Field({ label, error, hint, children }) {
    return (
        <div className="space-y-1.5">
            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{label}</Label>
            {children}
            {hint && <p className="text-[11px] text-muted-foreground">{hint}</p>}
            {error && <p className="text-xs text-destructive flex items-center gap-1"><AlertCircle className="h-3 w-3" /> {error}</p>}
        </div>
    );
}

function Toggle({ label, value, onChange }) {
    return (
        <button type="button" onClick={() => onChange(!value)} className="flex items-center justify-between gap-4 w-full rounded-md border border-border bg-muted/20 p-3 text-start">
            <span className="text-sm font-medium">{label}</span>
            <span className={cn('relative inline-flex h-6 w-11 items-center rounded-full transition-colors shrink-0', value ? 'bg-primary' : 'bg-muted-foreground/30')}>
                <span className={cn('inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow', value ? 'translate-x-6' : 'translate-x-1')} />
            </span>
        </button>
    );
}

export default function Index({ settings = {}, counts = {}, permissions = {}, urls = {}, t = {} }) {
    const form = useForm({
        enabled:                   settings.enabled ? '1' : '0',
        base_url:                  settings.base_url ?? '',
        integration_source:        settings.integration_source ?? '',
        default_target_company_id: settings.default_target_company_id ?? '',
        default_email:             settings.default_email ?? '',
        _method: 'put',
    });

    const [testCompanyId, setTestCompanyId] = React.useState(settings.default_target_company_id ?? '');

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true });
    };

    const testConnection = () => router.post(urls.test, { target_company_id: testCompanyId }, { preserveScroll: true });

    const usingEnvFallback = !settings.base_url && settings.env_base_url;

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.breadcrumb_settings, t.breadcrumb_integrations, t.title]}>
            <Head title={t.title} />

            <div className="mb-4">
                <a href={urls.integrations} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.breadcrumb_integrations}
                </a>
            </div>

            <Card className="mb-4">
                <CardContent className="p-5 flex items-start gap-3">
                    <Truck className="h-5 w-5 text-primary mt-0.5" />
                    <div>
                        <h2 className="text-lg font-semibold">{t.title}</h2>
                        <p className="text-sm text-muted-foreground">{t.help}</p>
                    </div>
                </CardContent>
            </Card>

            <form onSubmit={onSubmit}>
                <div className="grid gap-5 lg:grid-cols-3">
                    <div className="lg:col-span-2 space-y-4">
                        <Card>
                            <CardContent className="p-6 space-y-4">
                                <h3 className="text-sm font-semibold">{t.connection_section}</h3>

                                <Toggle label={t.enabled} value={form.data.enabled === '1'} onChange={(v) => form.setData('enabled', v ? '1' : '0')} />

                                <Field label={t.base_url} error={form.errors.base_url} hint={t.base_url_hint}>
                                    <Input
                                        value={form.data.base_url}
                                        onChange={(e) => form.setData('base_url', e.target.value)}
                                        placeholder={settings.env_base_url || 'https://apisv2.logestechs.com/api'}
                                        className="font-mono text-sm"
                                    />
                                    {usingEnvFallback && (
                                        <p className="text-[11px] text-muted-foreground">
                                            {t.env_fallback}: <code className="font-mono">{settings.env_base_url}</code>
                                        </p>
                                    )}
                                </Field>

                                <Field label={t.integration_source} error={form.errors.integration_source} hint={t.integration_source_hint}>
                                    <Input
                                        value={form.data.integration_source}
                                        onChange={(e) => form.setData('integration_source', e.target.value)}
                                        placeholder="API"
                                    />
                                </Field>

                                <div className="pt-2 border-t border-border">
                                    <h3 className="text-sm font-semibold">{t.defaults_section}</h3>
                                    <p className="text-[12px] text-muted-foreground mt-1">{t.defaults_help}</p>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field label={t.default_target_company_id} error={form.errors.default_target_company_id} hint={t.default_target_company_id_hint}>
                                        <Input
                                            value={form.data.default_target_company_id}
                                            onChange={(e) => form.setData('default_target_company_id', e.target.value)}
                                            placeholder="e.g. 496"
                                        />
                                    </Field>
                                    <Field label={t.default_email} error={form.errors.default_email} hint={t.default_email_hint}>
                                        <Input
                                            type="email"
                                            value={form.data.default_email}
                                            onChange={(e) => form.setData('default_email', e.target.value)}
                                            placeholder="account@example.com"
                                            autoComplete="off"
                                        />
                                    </Field>
                                </div>

                                <div className="flex flex-wrap gap-2 pt-3 border-t border-border">
                                    <Button type="submit" disabled={form.processing}><Save className="h-4 w-4 me-1" /> {t.save}</Button>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="p-6 space-y-4">
                                <h3 className="text-sm font-semibold">{t.test_connection}</h3>
                                <Field label={t.test_target_company_id} hint={t.test_target_company_id_hint}>
                                    <Input
                                        value={testCompanyId}
                                        onChange={(e) => setTestCompanyId(e.target.value)}
                                        placeholder="496"
                                        className="max-w-xs"
                                    />
                                </Field>
                                <Button type="button" onClick={testConnection} className="bg-transparent text-foreground border border-input hover:bg-muted/40">
                                    <CheckCircle2 className="h-4 w-4 me-1" /> {t.test_connection}
                                </Button>
                            </CardContent>
                        </Card>
                    </div>

                    <aside className="space-y-4">
                        <Card>
                            <CardContent className="p-5 space-y-3">
                                <h3 className="text-sm font-semibold">{t.effective_section}</h3>
                                <p className="text-[11px] text-muted-foreground">{t.effective_help}</p>
                                <dl className="text-sm space-y-2">
                                    <div>
                                        <dt className="text-muted-foreground text-[11px] uppercase tracking-wide">{t.base_url}</dt>
                                        <dd className="font-mono text-xs break-all">
                                            {settings.effective_base_url || <span className="text-amber-600">—</span>}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground text-[11px] uppercase tracking-wide">{t.integration_source}</dt>
                                        <dd className="font-mono text-xs">{settings.effective_source}</dd>
                                    </div>
                                </dl>
                                <div className="pt-3 border-t border-border text-[11px] text-muted-foreground">
                                    {settings.ready
                                        ? <p className="flex items-center gap-1"><CheckCircle2 className="h-3.5 w-3.5 text-emerald-600" /> Ready to assign.</p>
                                        : <p className="flex items-center gap-1"><XCircle className="h-3.5 w-3.5 text-amber-600" /> Missing: enabled toggle + base URL.</p>}
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="p-5 space-y-3">
                                <h3 className="text-sm font-semibold">{t.status_section}</h3>
                                <dl className="text-sm space-y-2">
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">{t.parcels_assigned}</dt>
                                        <dd className="tabular-nums">{counts.parcels_assigned ?? 0}</dd>
                                    </div>
                                </dl>
                            </CardContent>
                        </Card>
                    </aside>
                </div>
            </form>
        </AdminLayout>
    );
}
