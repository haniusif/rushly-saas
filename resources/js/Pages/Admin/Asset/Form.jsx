import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, AlertCircle, Boxes, FileText } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';
import { Textarea } from '@/Components/ui/Textarea';

function Field({ label, required, error, children }) {
    return (
        <div className="space-y-1.5">
            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {label} {required && <span className="text-destructive">*</span>}
            </Label>
            {children}
            {error && <p className="text-xs text-destructive flex items-center gap-1"><AlertCircle className="h-3 w-3" /> {error}</p>}
        </div>
    );
}

export default function Form({ mode = 'create', entity = null, lookups = {}, urls = {}, t = {} }) {
    const isEdit = mode === 'edit';
    const form = useForm({
        name: entity?.name ?? '',
        assetcategory_id: entity?.assetcategory_id ?? '',
        hub_id: entity?.hub_id ?? '',
        supplyer_name: entity?.supplyer_name ?? '',
        quantity: entity?.quantity ?? '',
        warranty: entity?.warranty ?? '',
        invoice_no: entity?.invoice_no ?? '',
        amount: entity?.amount ?? '',
        description: entity?.description ?? '',
        ...(isEdit ? { id: entity?.id, _method: 'put' } : {}),
    });

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true });
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.list_title, isEdit ? t.edit : t.add]}>
            <Head title={t.title} />
            <div className="mb-4">
                <a href={urls.cancel} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40"><ArrowLeft className="h-4 w-4 me-1" /> {t.back}</a>
            </div>
            <form onSubmit={onSubmit}>
                <Card>
                    <CardContent className="p-6">
                        <div className="mb-5 flex items-center gap-2"><Boxes className="h-5 w-5 text-primary" /><h2 className="text-lg font-semibold">{t.title}</h2></div>
                        <div className="grid gap-4 md:grid-cols-2">
                            <Field label={t.name} required error={form.errors.name}>
                                <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                            </Field>
                            <Field label={t.category} required error={form.errors.assetcategory_id}>
                                <Select value={form.data.assetcategory_id} onChange={(e) => form.setData('assetcategory_id', e.target.value)}>
                                    <option value="">—</option>
                                    {(lookups.categories || []).map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
                                </Select>
                            </Field>
                            <Field label={t.hub} required error={form.errors.hub_id}>
                                <Select value={form.data.hub_id} onChange={(e) => form.setData('hub_id', e.target.value)}>
                                    <option value="">—</option>
                                    {(lookups.hubs || []).map((h) => <option key={h.value} value={h.value}>{h.label}</option>)}
                                </Select>
                            </Field>
                            <Field label={t.supplier} error={form.errors.supplyer_name}>
                                <Input value={form.data.supplyer_name} onChange={(e) => form.setData('supplyer_name', e.target.value)} />
                            </Field>
                            <Field label={t.quantity} error={form.errors.quantity}>
                                <Input value={form.data.quantity} onChange={(e) => form.setData('quantity', e.target.value)} />
                            </Field>
                            <Field label={t.warranty} error={form.errors.warranty}>
                                <Input value={form.data.warranty} onChange={(e) => form.setData('warranty', e.target.value)} />
                            </Field>
                            <Field label={t.invoice_no} error={form.errors.invoice_no}>
                                <Input value={form.data.invoice_no} onChange={(e) => form.setData('invoice_no', e.target.value)} />
                            </Field>
                            <Field label={t.amount} required error={form.errors.amount}>
                                <Input type="number" step="0.01" value={form.data.amount} onChange={(e) => form.setData('amount', e.target.value)} />
                            </Field>
                            <div className="md:col-span-2">
                                <Field label={t.description} error={form.errors.description}>
                                    <Textarea rows={5} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
                                </Field>
                            </div>
                        </div>
                        <div className="mt-6 flex items-center gap-2 border-t border-border pt-4">
                            <Button type="submit" disabled={form.processing}><Save className="h-4 w-4 me-1" /> {isEdit ? t.update : t.save}</Button>
                            <a href={urls.cancel} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">{t.cancel}</a>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </AdminLayout>
    );
}
