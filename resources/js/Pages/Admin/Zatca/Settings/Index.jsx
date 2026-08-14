import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Save, CheckCircle2, AlertCircle, FileText, Building2, Receipt } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';

function Field({ label, required, children, hint }) {
    return (
        <div className="space-y-1">
            <label className="text-xs font-medium uppercase tracking-wide text-slate-600">
                {label} {required && <span className="text-rose-500">*</span>}
            </label>
            {children}
            {hint && <p className="text-[11px] text-slate-500">{hint}</p>}
        </div>
    );
}

function SectionTitle({ icon: Icon, children }) {
    return (
        <div className="mt-6 mb-3 flex items-center gap-2 text-sm font-semibold text-indigo-700">
            <Icon className="h-4 w-4" />
            {children}
        </div>
    );
}

export default function Index({ setting = {}, lookups = {}, urls = {}, t = {} }) {
    const { data, setData, put, processing, errors } = useForm({
        seller_name_en:    setting.seller_name_en || '',
        seller_name_ar:    setting.seller_name_ar || '',
        vat_number:        setting.vat_number || '',
        cr_number:         setting.cr_number || '',
        address_street_en: setting.address_street_en || '',
        address_street_ar: setting.address_street_ar || '',
        building_number:   setting.building_number || '',
        district_en:       setting.district_en || '',
        district_ar:       setting.district_ar || '',
        city_en:           setting.city_en || '',
        city_ar:           setting.city_ar || '',
        postal_code:       setting.postal_code || '',
        country_code:      setting.country_code || 'SA',
        vat_rate:          setting.vat_rate ?? 15,
        currency:          setting.currency || 'SAR',
        mode:              setting.mode || 'sandbox',
        enabled:           !!setting.enabled,
        auto_generate:     setting.auto_generate ?? true,
        invoice_prefix:    setting.invoice_prefix || 'ZAT-',
    });

    const submit = (e) => {
        e.preventDefault();
        put(urls.update, { preserveScroll: true });
    };

    const ready = setting.is_ready;

    return (
        <AdminLayout title={t.title || 'ZATCA Settings'} breadcrumbs={[t.title || 'ZATCA', 'Settings']}>
            <Head title={t.title || 'ZATCA Settings'} />

            <Card>
                <CardContent className="p-5">
                    <div className="mb-4 flex items-start justify-between">
                        <div>
                            <h2 className="text-lg font-semibold flex items-center gap-2">
                                <FileText className="h-5 w-5 text-indigo-600" />
                                {t.title}
                            </h2>
                            <p className="mt-1 text-xs text-slate-600">{t.subtitle}</p>
                        </div>
                        <span className={`inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium ${
                            ready ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'
                        }`}>
                            {ready ? <CheckCircle2 className="h-3.5 w-3.5" /> : <AlertCircle className="h-3.5 w-3.5" />}
                            {ready ? t.ready_yes : t.ready_no}
                        </span>
                    </div>

                    <form onSubmit={submit} className="space-y-2">
                        <SectionTitle icon={Building2}>{t.seller_info}</SectionTitle>
                        <div className="grid gap-3 md:grid-cols-2">
                            <Field label={t.seller_name_en} required>
                                <Input value={data.seller_name_en} onChange={(e) => setData('seller_name_en', e.target.value)} />
                                {errors.seller_name_en && <p className="text-[11px] text-rose-600">{errors.seller_name_en}</p>}
                            </Field>
                            <Field label={t.seller_name_ar} required>
                                <Input dir="rtl" value={data.seller_name_ar} onChange={(e) => setData('seller_name_ar', e.target.value)} />
                                {errors.seller_name_ar && <p className="text-[11px] text-rose-600">{errors.seller_name_ar}</p>}
                            </Field>
                            <Field label={t.vat_number} required hint="15 digits, starts and ends with 3">
                                <Input maxLength={15} value={data.vat_number} onChange={(e) => setData('vat_number', e.target.value.replace(/\D/g, ''))} />
                                {errors.vat_number && <p className="text-[11px] text-rose-600">{errors.vat_number}</p>}
                            </Field>
                            <Field label={t.cr_number}>
                                <Input value={data.cr_number} onChange={(e) => setData('cr_number', e.target.value)} />
                            </Field>
                        </div>

                        <SectionTitle icon={Building2}>{t.address}</SectionTitle>
                        <div className="grid gap-3 md:grid-cols-2">
                            <Field label={t.street_en}>
                                <Input value={data.address_street_en} onChange={(e) => setData('address_street_en', e.target.value)} />
                            </Field>
                            <Field label={t.street_ar}>
                                <Input dir="rtl" value={data.address_street_ar} onChange={(e) => setData('address_street_ar', e.target.value)} />
                            </Field>
                        </div>
                        <div className="grid gap-3 md:grid-cols-4">
                            <Field label={t.building_no}>
                                <Input value={data.building_number} onChange={(e) => setData('building_number', e.target.value)} />
                            </Field>
                            <Field label={t.district_en}>
                                <Input value={data.district_en} onChange={(e) => setData('district_en', e.target.value)} />
                            </Field>
                            <Field label={t.city_en}>
                                <Input value={data.city_en} onChange={(e) => setData('city_en', e.target.value)} />
                            </Field>
                            <Field label={t.postal_code}>
                                <Input value={data.postal_code} onChange={(e) => setData('postal_code', e.target.value)} />
                            </Field>
                        </div>
                        <div className="grid gap-3 md:grid-cols-3">
                            <Field label={t.district_ar}>
                                <Input dir="rtl" value={data.district_ar} onChange={(e) => setData('district_ar', e.target.value)} />
                            </Field>
                            <Field label={t.city_ar}>
                                <Input dir="rtl" value={data.city_ar} onChange={(e) => setData('city_ar', e.target.value)} />
                            </Field>
                            <Field label={t.country_code}>
                                <Input maxLength={2} value={data.country_code} onChange={(e) => setData('country_code', e.target.value.toUpperCase())} />
                            </Field>
                        </div>

                        <SectionTitle icon={Receipt}>{t.tax}</SectionTitle>
                        <div className="grid gap-3 md:grid-cols-4">
                            <Field label={`${t.vat_rate} (%)`}>
                                <Input type="number" step="0.01" min={0} max={100} value={data.vat_rate} onChange={(e) => setData('vat_rate', parseFloat(e.target.value))} />
                            </Field>
                            <Field label={t.currency}>
                                <Input maxLength={3} value={data.currency} onChange={(e) => setData('currency', e.target.value.toUpperCase())} />
                            </Field>
                            <Field label={t.mode}>
                                <Select value={data.mode} onChange={(e) => setData('mode', e.target.value)}>
                                    {Object.entries(lookups.modes || {}).map(([val, label]) => (
                                        <option key={val} value={val}>{label}</option>
                                    ))}
                                </Select>
                            </Field>
                            <Field label={t.invoice_prefix}>
                                <Input value={data.invoice_prefix} onChange={(e) => setData('invoice_prefix', e.target.value)} />
                            </Field>
                        </div>

                        <div className="mt-5 flex flex-wrap items-center gap-6 border-t pt-4">
                            <label className="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" className="h-4 w-4" checked={data.enabled} onChange={(e) => setData('enabled', e.target.checked)} />
                                <span className="font-medium">{t.enabled}</span>
                            </label>
                            <label className="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" className="h-4 w-4" checked={data.auto_generate} onChange={(e) => setData('auto_generate', e.target.checked)} />
                                <span className="font-medium">{t.auto_generate}</span>
                            </label>
                            <div className="ml-auto">
                                <Button type="submit" disabled={processing}>
                                    <Save className="mr-2 h-4 w-4" />
                                    {t.save}
                                </Button>
                            </div>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
