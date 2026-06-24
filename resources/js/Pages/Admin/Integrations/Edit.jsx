import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, AlertCircle, Plug, Info } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';
import { cn } from '@/lib/utils';

function Field({ label, required, error, hint, children }) {
    return (
        <div className="space-y-1.5">
            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {label} {required && <span className="text-destructive">*</span>}
            </Label>
            {children}
            {hint && <p className="text-[11px] text-muted-foreground">{hint}</p>}
            {error && <p className="text-xs text-destructive flex items-center gap-1"><AlertCircle className="h-3 w-3" /> {error}</p>}
        </div>
    );
}

function Toggle({ label, value, onChange }) {
    return (
        <button
            type="button"
            onClick={() => onChange(!value)}
            className="flex items-center justify-between gap-4 w-full rounded-md border border-border bg-muted/20 p-3 text-start"
        >
            <span className="text-sm font-medium">{label}</span>
            <span className={cn(
                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors shrink-0',
                value ? 'bg-primary' : 'bg-muted-foreground/30'
            )}>
                <span className={cn(
                    'inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow',
                    value ? 'translate-x-6' : 'translate-x-1'
                )} />
            </span>
        </button>
    );
}

function MonoVal({ children }) {
    return <code className="text-[11px] font-mono break-all">{children}</code>;
}

export default function Edit({ setting = {}, lookups = {}, placeholders = {}, env_keys = {}, urls = {}, t = {} }) {
    const form = useForm({
        is_enabled: setting.is_enabled ? '1' : '0',
        app_url: setting.app_url ?? '',
        writeback_token: setting.writeback_token ?? '',
        api_base: setting.api_base ?? '',
        default_city_id: setting.default_city_id ?? '',
        default_category_id: setting.default_category_id ?? '',
        default_delivery_type_id: setting.default_delivery_type_id ?? '',
        _method: 'put',
    });

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true });
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.breadcrumb_settings, t.breadcrumb_integrations, setting.name]}>
            <Head title={t.title} />

            <div className="mb-4">
                <a href={urls.cancel} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.back}
                </a>
            </div>

            <form onSubmit={onSubmit}>
                <div className="grid gap-5 lg:grid-cols-3">
                    <div className="lg:col-span-2 space-y-4">
                        <Card>
                            <CardContent className="p-6">
                                <div className="mb-4 flex items-center gap-3">
                                    {setting.logo_url
                                        ? <img src={setting.logo_url} alt={setting.name} className="h-10 w-10 rounded-lg object-contain bg-muted/40 p-1" />
                                        : <div className="h-10 w-10 rounded-lg bg-muted/40 flex items-center justify-center text-base font-bold">{setting.name?.[0]}</div>}
                                    <h2 className="text-lg font-semibold">{t.title}</h2>
                                </div>

                                <div className="mb-5">
                                    <Toggle
                                        label={t.is_enabled_label}
                                        value={form.data.is_enabled === '1'}
                                        onChange={(v) => form.setData('is_enabled', v ? '1' : '0')}
                                    />
                                </div>

                                <div className="space-y-4 pt-4 border-t border-border">
                                    <div>
                                        <h3 className="text-sm font-semibold">{t.bridge_section}</h3>
                                        <p className="text-[11px] text-muted-foreground mt-1">
                                            {t.bridge_help} <MonoVal>{env_keys.app_url_env}</MonoVal> / <MonoVal>{env_keys.writeback_env}</MonoVal>
                                        </p>
                                    </div>

                                    <Field label={t.app_url} error={form.errors.app_url} hint={t.app_url_hint}>
                                        <Input
                                            type="url"
                                            value={form.data.app_url}
                                            onChange={(e) => form.setData('app_url', e.target.value)}
                                            placeholder={placeholders.app_url}
                                        />
                                    </Field>

                                    <Field label={t.writeback_token} error={form.errors.writeback_token} hint={t.writeback_hint}>
                                        <Input
                                            value={form.data.writeback_token}
                                            onChange={(e) => form.setData('writeback_token', e.target.value)}
                                            placeholder="32+ char random string"
                                            className="font-mono text-sm"
                                        />
                                    </Field>

                                    <Field label={t.api_base} error={form.errors.api_base} hint={t.api_base_hint}>
                                        <Input
                                            type="url"
                                            value={form.data.api_base}
                                            onChange={(e) => form.setData('api_base', e.target.value)}
                                            placeholder={placeholders.api_base}
                                        />
                                    </Field>
                                </div>

                                <div className="space-y-4 pt-5 mt-5 border-t border-border">
                                    <div>
                                        <h3 className="text-sm font-semibold">{t.defaults_section}</h3>
                                        <p className="text-[11px] text-muted-foreground mt-1">{t.defaults_help}</p>
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-3">
                                        <Field label={t.default_city} error={form.errors.default_city_id}>
                                            <Select value={form.data.default_city_id} onChange={(e) => form.setData('default_city_id', e.target.value)}>
                                                <option value="">{t.none_option}</option>
                                                {(lookups.cities || []).map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
                                            </Select>
                                        </Field>
                                        <Field label={t.default_category} error={form.errors.default_category_id}>
                                            <Select value={form.data.default_category_id} onChange={(e) => form.setData('default_category_id', e.target.value)}>
                                                <option value="">{t.none_option}</option>
                                                {(lookups.categories || []).map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
                                            </Select>
                                        </Field>
                                        <Field label={t.default_delivery_type} error={form.errors.default_delivery_type_id}>
                                            <Select value={form.data.default_delivery_type_id} onChange={(e) => form.setData('default_delivery_type_id', e.target.value)}>
                                                <option value="">{t.none_option}</option>
                                                {(lookups.delivery_types || []).map((d) => <option key={d.value} value={d.value}>{d.label}</option>)}
                                            </Select>
                                        </Field>
                                    </div>
                                </div>

                                <div className="mt-6 flex items-center gap-2 border-t border-border pt-4">
                                    <Button type="submit" disabled={form.processing}>
                                        <Save className="h-4 w-4 me-1" /> {t.save}
                                    </Button>
                                    <a href={urls.cancel} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">{t.cancel}</a>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <aside>
                        <Card>
                            <CardContent className="p-5">
                                <div className="flex items-center gap-2 mb-3">
                                    <Plug className="h-4 w-4 text-primary" />
                                    <h3 className="text-sm font-semibold">{t.where_title}</h3>
                                </div>
                                <ul className="space-y-2 text-[12px]">
                                    <li>
                                        <span className="text-muted-foreground">{t.where_bridge_code}: </span>
                                        <MonoVal>rushly-{setting.platform}/</MonoVal>
                                    </li>
                                    <li>
                                        <span className="text-muted-foreground">{t.where_link_table}: </span>
                                        <MonoVal>{setting.platform}_orders</MonoVal>
                                    </li>
                                    <li>
                                        <span className="text-muted-foreground">{t.where_external_endpoint}: </span>
                                        <MonoVal>POST /api/v10/external/{setting.platform}/parcel</MonoVal>
                                    </li>
                                    <li>
                                        <span className="text-muted-foreground">{t.where_writeback_endpoint}: </span>
                                        <MonoVal>POST /internal/parcel-status</MonoVal>
                                    </li>
                                </ul>
                                <div className="mt-4 pt-3 border-t border-border flex items-start gap-2 text-[11px] text-muted-foreground">
                                    <Info className="h-3.5 w-3.5 mt-0.5 shrink-0" />
                                    <span>{t.bridge_help}</span>
                                </div>
                            </CardContent>
                        </Card>
                    </aside>
                </div>
            </form>
        </AdminLayout>
    );
}
