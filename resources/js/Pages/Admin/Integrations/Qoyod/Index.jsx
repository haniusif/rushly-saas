import * as React from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { ArrowLeft, Save, AlertCircle, Plug, RefreshCw, CheckCircle2, XCircle, Plus } from 'lucide-react';
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

function StatusPill({ status }) {
    const map = {
        synced:  ['bg-emerald-100 text-emerald-700 border-emerald-200', 'synced'],
        pending: ['bg-amber-100   text-amber-700   border-amber-200',   'pending'],
        failed:  ['bg-rose-100    text-rose-700    border-rose-200',    'failed'],
    };
    const [cls, label] = map[status] || ['bg-slate-100 text-slate-700 border-slate-200', status || '—'];
    return <span className={`inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium ${cls}`}>{label}</span>;
}

function VendorRow({ v, syncUrl, t }) {
    const post = () => router.post(syncUrl.replace('__ID__', v.id), {}, { preserveScroll: true });
    return (
        <tr className="border-t border-border">
            <td className="px-4 py-3"><code className="text-[11px] font-mono">{v.courier_key}</code></td>
            <td className="px-4 py-3">{v.display_name}</td>
            <td className="px-4 py-3">{v.qoyod_vendor_id || <span className="text-muted-foreground">—</span>}</td>
            <td className="px-4 py-3"><StatusPill status={v.qoyod_sync_status} /></td>
            <td className="px-4 py-3 text-end">
                <Button type="button" onClick={post} className="h-8 px-3 text-xs"><RefreshCw className="h-3.5 w-3.5 me-1" /> {t.sync_again}</Button>
            </td>
        </tr>
    );
}

function AddVendorForm({ url, t }) {
    const form = useForm({ courier_key: '', display_name: '' });
    const onSubmit = (e) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: () => form.reset() });
    };
    return (
        <form onSubmit={onSubmit} className="flex flex-wrap items-end gap-3 mt-4 pt-4 border-t border-border">
            <Field label={t.courier_key}>
                <Input value={form.data.courier_key} onChange={(e) => form.setData('courier_key', e.target.value)} placeholder="aramex" className="w-32" />
            </Field>
            <Field label={t.display_name}>
                <Input value={form.data.display_name} onChange={(e) => form.setData('display_name', e.target.value)} placeholder="Aramex" className="w-48" />
            </Field>
            <Button type="submit" disabled={form.processing}><Plus className="h-4 w-4 me-1" /> {t.add_vendor}</Button>
        </form>
    );
}

export default function Index({ settings = {}, counts = {}, vendors = [], permissions = {}, urls = {}, t = {} }) {
    const form = useForm({
        enabled:              settings.enabled ? '1' : '0',
        api_key:              settings.api_key_set ? '••••' + (settings.api_key_tail || '') : '',
        default_inventory_id: settings.default_inventory_id ?? '',
        default_product_id:   settings.default_product_id ?? '',
        default_account_id:   settings.default_account_id ?? '',
        vat_percent:          settings.vat_percent ?? '15.00',
        _method: 'put',
    });

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true });
    };

    const testConnection = () => router.post(urls.test, {}, { preserveScroll: true });
    const resyncAll      = () => router.post(urls.resync_all, {}, { preserveScroll: true });

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
                    <Plug className="h-5 w-5 text-primary mt-0.5" />
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
                                <div>
                                    <h3 className="text-sm font-semibold">{t.connection_section}</h3>
                                </div>

                                <Toggle label={t.enabled} value={form.data.enabled === '1'} onChange={(v) => form.setData('enabled', v ? '1' : '0')} />

                                <Field label={t.api_key} error={form.errors.api_key} hint={t.api_key_hint}>
                                    <Input value={form.data.api_key} onChange={(e) => form.setData('api_key', e.target.value)} placeholder="Paste your Qoyod API key" className="font-mono text-sm" />
                                </Field>

                                <div className="pt-2 border-t border-border space-y-1">
                                    <h3 className="text-sm font-semibold">{t.defaults_section}</h3>
                                    <p className="text-[11px] text-muted-foreground">{t.defaults_help}</p>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field label={t.inventory_id} error={form.errors.default_inventory_id}>
                                        <Input type="number" value={form.data.default_inventory_id} onChange={(e) => form.setData('default_inventory_id', e.target.value)} />
                                    </Field>
                                    <Field label={t.product_id} error={form.errors.default_product_id}>
                                        <Input type="number" value={form.data.default_product_id} onChange={(e) => form.setData('default_product_id', e.target.value)} />
                                    </Field>
                                    <Field label={t.account_id} error={form.errors.default_account_id}>
                                        <Input type="number" value={form.data.default_account_id} onChange={(e) => form.setData('default_account_id', e.target.value)} />
                                    </Field>
                                    <Field label={t.vat_percent} error={form.errors.vat_percent}>
                                        <Input type="number" step="0.01" min="0" max="100" value={form.data.vat_percent} onChange={(e) => form.setData('vat_percent', e.target.value)} />
                                    </Field>
                                </div>

                                <div className="flex flex-wrap gap-2 pt-3 border-t border-border">
                                    <Button type="submit" disabled={form.processing}><Save className="h-4 w-4 me-1" /> {t.save}</Button>
                                    <Button type="button" onClick={testConnection} className="bg-transparent text-foreground border border-input hover:bg-muted/40"><CheckCircle2 className="h-4 w-4 me-1" /> {t.test_connection}</Button>
                                    <Button type="button" onClick={resyncAll} className="bg-transparent text-foreground border border-input hover:bg-muted/40"><RefreshCw className="h-4 w-4 me-1" /> {t.resync_all}</Button>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="p-6">
                                <h3 className="text-sm font-semibold mb-2">{t.vendors_section}</h3>
                                <p className="text-[11px] text-muted-foreground mb-4">{t.vendors_help}</p>
                                {vendors.length === 0 ? (
                                    <p className="text-sm text-muted-foreground py-4">No courier vendors yet — add one below.</p>
                                ) : (
                                    <table className="w-full text-sm">
                                        <thead className="bg-muted/40">
                                            <tr className="text-[11px] uppercase tracking-wide text-muted-foreground">
                                                <th className="text-start px-4 py-2">{t.courier_key}</th>
                                                <th className="text-start px-4 py-2">{t.display_name}</th>
                                                <th className="text-start px-4 py-2">{t.vendor_id}</th>
                                                <th className="text-start px-4 py-2">{t.sync_status}</th>
                                                <th className="text-end px-4 py-2"></th>
                                            </tr>
                                        </thead>
                                        <tbody>{vendors.map((v) => <VendorRow key={v.id} v={v} syncUrl={urls.sync_vendor} t={t} />)}</tbody>
                                    </table>
                                )}
                                {permissions.update && <AddVendorForm url={urls.add_vendor} t={t} />}
                            </CardContent>
                        </Card>
                    </div>

                    <aside>
                        <Card>
                            <CardContent className="p-5 space-y-3">
                                <h3 className="text-sm font-semibold">{t.status_section}</h3>
                                <dl className="text-sm space-y-2">
                                    <div className="flex justify-between"><dt className="text-muted-foreground">{t.merchants_total}</dt><dd className="tabular-nums">{counts.merchants_total}</dd></div>
                                    <div className="flex justify-between"><dt className="text-muted-foreground">{t.merchants_synced}</dt><dd className="tabular-nums text-emerald-700">{counts.merchants_synced}</dd></div>
                                    <div className="flex justify-between"><dt className="text-muted-foreground">{t.merchants_failed}</dt><dd className={cn('tabular-nums', counts.merchants_failed > 0 && 'text-rose-700')}>{counts.merchants_failed}</dd></div>
                                </dl>
                                <div className="pt-3 border-t border-border text-[11px] text-muted-foreground space-y-1">
                                    {settings.ready
                                        ? <p className="flex items-center gap-1"><CheckCircle2 className="h-3.5 w-3.5 text-emerald-600" /> Ready to push.</p>
                                        : <p className="flex items-center gap-1"><XCircle className="h-3.5 w-3.5 text-amber-600" /> Missing: API key + the 3 default IDs.</p>}
                                </div>
                            </CardContent>
                        </Card>
                    </aside>
                </div>
            </form>
        </AdminLayout>
    );
}
