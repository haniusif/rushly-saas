import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, AlertCircle, Link2, Settings as SettingsIcon, Info } from 'lucide-react';
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

export default function Edit({ store = {}, settings = {}, lookups = {}, urls = {}, t = {} }) {
    const form = useForm({
        rushly_merchant_id:        store.rushly_merchant_id ?? '',
        rushly_shop_id:            store.rushly_shop_id ?? '',
        auto_create_parcel:        settings.auto_create_parcel ? '1' : '0',
        trigger_status:            settings.trigger_status ?? 'payment_pending',
        default_rushly_shop_id:    settings.default_rushly_shop_id ?? '',
        default_city_id:           settings.default_city_id ?? '',
        default_category_id:       settings.default_category_id ?? '',
        default_delivery_type_id:  settings.default_delivery_type_id ?? '',
        support_email:             settings.support_email ?? '',
        _method: 'put',
    });

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true });
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.breadcrumb_settings, t.breadcrumb_integrations, t.breadcrumb_stores, store.store_name || store.salla_merchant_id]}>
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
                                <div className="mb-5">
                                    <h2 className="text-lg font-semibold">{store.store_name || '—'}</h2>
                                    <p className="text-xs text-muted-foreground mt-0.5">
                                        Salla ID <code className="font-mono">{store.salla_merchant_id}</code>
                                        {store.store_domain && <> · <code className="font-mono">{store.store_domain}</code></>}
                                        {store.owner_email && <> · {store.owner_email}</>}
                                    </p>
                                </div>

                                <div className="space-y-4">
                                    <div className="flex items-center gap-2">
                                        <Link2 className="h-4 w-4 text-primary" />
                                        <h3 className="text-sm font-semibold">{t.link_section}</h3>
                                    </div>
                                    <p className="text-[11px] text-muted-foreground">{t.link_help}</p>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <Field label={t.rushly_merchant} error={form.errors.rushly_merchant_id}>
                                            <Select value={form.data.rushly_merchant_id} onChange={(e) => form.setData('rushly_merchant_id', e.target.value)}>
                                                <option value="">{t.none_option}</option>
                                                {(lookups.merchants || []).map((m) => <option key={m.value} value={m.value}>{m.label}</option>)}
                                            </Select>
                                        </Field>
                                        <Field label={t.rushly_shop_id} error={form.errors.rushly_shop_id} hint={t.rushly_shop_hint}>
                                            <Select value={form.data.rushly_shop_id} onChange={(e) => form.setData('rushly_shop_id', e.target.value)} disabled={(lookups.shops || []).length === 0}>
                                                <option value="">{t.none_option}</option>
                                                {(lookups.shops || []).map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                                            </Select>
                                        </Field>
                                    </div>
                                </div>

                                <div className="space-y-4 pt-5 mt-5 border-t border-border">
                                    <div className="flex items-center gap-2">
                                        <SettingsIcon className="h-4 w-4 text-primary" />
                                        <h3 className="text-sm font-semibold">{t.flow_section}</h3>
                                    </div>
                                    <p className="text-[11px] text-muted-foreground">{t.flow_help}</p>

                                    <Toggle
                                        label={t.auto_create_parcel}
                                        value={form.data.auto_create_parcel === '1'}
                                        onChange={(v) => form.setData('auto_create_parcel', v ? '1' : '0')}
                                    />

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <Field label={t.trigger_status} error={form.errors.trigger_status}>
                                            <Input
                                                value={form.data.trigger_status}
                                                onChange={(e) => form.setData('trigger_status', e.target.value)}
                                                placeholder="payment_pending"
                                            />
                                        </Field>
                                        <Field label={t.support_email} error={form.errors.support_email}>
                                            <Input
                                                type="email"
                                                value={form.data.support_email}
                                                onChange={(e) => form.setData('support_email', e.target.value)}
                                            />
                                        </Field>
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <Field label={t.default_rushly_shop} error={form.errors.default_rushly_shop_id}>
                                            <Select value={form.data.default_rushly_shop_id} onChange={(e) => form.setData('default_rushly_shop_id', e.target.value)} disabled={(lookups.shops || []).length === 0}>
                                                <option value="">{t.none_option}</option>
                                                {(lookups.shops || []).map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                                            </Select>
                                        </Field>
                                        <Field label={t.default_delivery_type} error={form.errors.default_delivery_type_id}>
                                            <Select value={form.data.default_delivery_type_id} onChange={(e) => form.setData('default_delivery_type_id', e.target.value)}>
                                                <option value="">{t.none_option}</option>
                                                {(lookups.delivery_types || []).map((d) => <option key={d.value} value={d.value}>{d.label}</option>)}
                                            </Select>
                                        </Field>
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-2">
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
                                <div className="flex items-start gap-2 text-[11px] text-muted-foreground">
                                    <Info className="h-3.5 w-3.5 mt-0.5 shrink-0" />
                                    <div className="space-y-1.5">
                                        <p>To pick a Rushly shop, first save a Rushly merchant — the shop list reloads with that merchant's shops.</p>
                                        <p>When auto-create is on, every <code className="font-mono">order.created</code> webhook from Salla queues a parcel using these defaults.</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </aside>
                </div>
            </form>
        </AdminLayout>
    );
}
