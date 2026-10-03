import * as React from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { ArrowLeft, Save, Trash2, AlertCircle, GitBranch } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';

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

export default function Edit({ mode = 'create', route = null, strategies = [], providers = [], shipping_connections = [], urls = {}, t = {} }) {
    const isEdit = mode === 'edit';

    const form = useForm({
        name:                   route?.name ?? '',
        priority:               route?.priority ?? 100,
        is_active:              route?.is_active ?? true,
        merchant_id:            route?.merchant_id ?? '',
        source_provider_code:   route?.source_provider_code ?? '',
        shipping_city_id:       route?.shipping_city_id ?? '',
        shipping_country:       route?.shipping_country ?? '',
        min_total:              route?.min_total ?? '',
        max_total:              route?.max_total ?? '',
        is_cod:                 route?.is_cod ?? '',    // '', true, false — tri-state
        strategy:               route?.strategy ?? (strategies[0]?.code || 'merchant_self'),
        shipping_connection_id: route?.shipping_connection_id ?? '',
        hub_id:                 route?.hub_id ?? '',
        notes:                  route?.notes ?? '',
        _method: isEdit ? 'put' : 'post',
    });

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true });
    };

    const destroy = () => {
        if (!confirm('Delete this route? Existing fulfillments stay; new orders lose this routing rule.')) return;
        router.delete(urls.destroy);
    };

    return (
        <AdminLayout title={`${t.page_title} — ${isEdit ? 'edit' : 'add'}`} breadcrumbs={[t.breadcrumb_settings, t.breadcrumb_integrations, t.breadcrumb_commerce, t.breadcrumb_oms, t.breadcrumb_routes, isEdit ? 'edit' : 'add']}>
            <Head title={`${t.page_title} — ${isEdit ? 'edit' : 'add'}`} />

            <div className="mb-4 flex items-center justify-between">
                <a href={urls.index} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.breadcrumb_routes}
                </a>
                {isEdit && (
                    <Button type="button" onClick={destroy} className="bg-rose-600 hover:bg-rose-700 text-white">
                        <Trash2 className="h-4 w-4 me-1" /> {t.delete}
                    </Button>
                )}
            </div>

            <Card className="mb-4">
                <CardContent className="p-5 flex items-center gap-3">
                    <GitBranch className="h-6 w-6 text-primary" />
                    <div>
                        <h2 className="text-lg font-semibold">{form.data.name || 'New route'}</h2>
                        <p className="text-xs text-muted-foreground">All filled conditions must match; blank conditions are ignored.</p>
                    </div>
                </CardContent>
            </Card>

            <form onSubmit={onSubmit}>
                <div className="grid gap-5 lg:grid-cols-3">
                    <div className="lg:col-span-2 space-y-4">
                        <Card>
                            <CardContent className="p-6 space-y-4">
                                <h3 className="text-sm font-semibold">Identity</h3>
                                <div className="grid gap-4 md:grid-cols-3">
                                    <div className="md:col-span-2">
                                        <Field label="Name" error={form.errors.name}>
                                            <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} placeholder="KSA Salla → Aramex" />
                                        </Field>
                                    </div>
                                    <Field label="Priority" hint="Lower wins ties" error={form.errors.priority}>
                                        <Input type="number" value={form.data.priority} onChange={(e) => form.setData('priority', e.target.value)} />
                                    </Field>
                                </div>
                                <div>
                                    <label className="inline-flex items-center gap-2 text-sm">
                                        <input type="checkbox" checked={!!form.data.is_active} onChange={(e) => form.setData('is_active', e.target.checked)} />
                                        Active
                                    </label>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="p-6 space-y-4">
                                <h3 className="text-sm font-semibold">Conditions</h3>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field label="Source provider" hint="e.g. 'salla', 'zid'. Blank = any">
                                        <select className="w-full h-9 rounded-md border border-input bg-background px-3 text-sm" value={form.data.source_provider_code} onChange={(e) => form.setData('source_provider_code', e.target.value)}>
                                            <option value="">any</option>
                                            {providers.map((p) => <option key={p.code} value={p.code}>{p.name}</option>)}
                                        </select>
                                    </Field>
                                    <Field label="Merchant ID" hint="Rushly merchants.id. Blank = any">
                                        <Input value={form.data.merchant_id} onChange={(e) => form.setData('merchant_id', e.target.value)} />
                                    </Field>
                                    <Field label="Shipping city ID" hint="Local cities.id. Blank = any">
                                        <Input value={form.data.shipping_city_id} onChange={(e) => form.setData('shipping_city_id', e.target.value)} />
                                    </Field>
                                    <Field label="Shipping country" hint="Match provider's country string. Blank = any">
                                        <Input value={form.data.shipping_country} onChange={(e) => form.setData('shipping_country', e.target.value)} placeholder="Saudi Arabia" />
                                    </Field>
                                    <Field label="Min total">
                                        <Input value={form.data.min_total} onChange={(e) => form.setData('min_total', e.target.value)} />
                                    </Field>
                                    <Field label="Max total">
                                        <Input value={form.data.max_total} onChange={(e) => form.setData('max_total', e.target.value)} />
                                    </Field>
                                    <Field label="COD filter" hint="tri-state: any / cod-only / non-cod-only">
                                        <select className="w-full h-9 rounded-md border border-input bg-background px-3 text-sm" value={String(form.data.is_cod)} onChange={(e) => form.setData('is_cod', e.target.value === '' ? '' : e.target.value === 'true')}>
                                            <option value="">any</option>
                                            <option value="true">COD only</option>
                                            <option value="false">non-COD only</option>
                                        </select>
                                    </Field>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="p-6 space-y-4">
                                <h3 className="text-sm font-semibold">Strategy</h3>
                                <Field label="Strategy" error={form.errors.strategy}>
                                    <select className="w-full h-9 rounded-md border border-input bg-background px-3 text-sm" value={form.data.strategy} onChange={(e) => form.setData('strategy', e.target.value)}>
                                        {strategies.map((s) => <option key={s.code} value={s.code}>{s.label}</option>)}
                                    </select>
                                </Field>
                                {form.data.strategy === 'threepl_dropship' && (
                                    <Field label="Shipping connection" hint="Required for 3PL dropship — the shipping connection to hand off to" error={form.errors.shipping_connection_id}>
                                        <select className="w-full h-9 rounded-md border border-input bg-background px-3 text-sm" value={form.data.shipping_connection_id} onChange={(e) => form.setData('shipping_connection_id', e.target.value)}>
                                            <option value="">— pick —</option>
                                            {shipping_connections.map((c) => <option key={c.id} value={c.id}>{c.name} ({c.provider})</option>)}
                                        </select>
                                    </Field>
                                )}
                                {form.data.strategy === 'wms' && (
                                    <Field label="Warehouse (hub) ID" hint="Optional. Blank = WMS default" error={form.errors.hub_id}>
                                        <Input value={form.data.hub_id} onChange={(e) => form.setData('hub_id', e.target.value)} />
                                    </Field>
                                )}
                                <Field label="Notes">
                                    <textarea className="w-full min-h-[60px] rounded-md border border-input bg-background px-3 py-2 text-sm" value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} />
                                </Field>

                                <div className="pt-3 border-t border-border">
                                    <Button type="submit" disabled={form.processing}><Save className="h-4 w-4 me-1" /> {t.save}</Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}
