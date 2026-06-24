import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, AlertCircle, DollarSign } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';

function Field({ label, required, error, prefix, children }) {
    return (
        <div className="space-y-1.5">
            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {label} {required && <span className="text-destructive">*</span>}
            </Label>
            {prefix
                ? (
                    <div className="flex items-center">
                        <span className="inline-flex h-9 items-center rounded-l-md border border-r-0 border-input bg-muted/40 px-2.5 text-xs font-mono text-muted-foreground">{prefix}</span>
                        <div className="flex-1">{children}</div>
                    </div>
                )
                : children}
            {error && <p className="text-xs text-destructive flex items-center gap-1"><AlertCircle className="h-3 w-3" /> {error}</p>}
        </div>
    );
}

export default function Form({ mode = 'create', entity = null, lookups = {}, currency = '', urls = {}, t = {} }) {
    const isEdit = mode === 'edit';
    const form = useForm({
        category: entity?.category ?? '',
        weight: entity?.weight ?? '',
        extra_weight_price: entity?.extra_weight_price ?? '',
        same_day: entity?.same_day ?? '',
        next_day: entity?.next_day ?? '',
        sub_city: entity?.sub_city ?? '',
        outside_city: entity?.outside_city ?? '',
        position: entity?.position ?? '',
        status: String(entity?.status ?? '1'),
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
                        <div className="flex items-center gap-2 mb-5"><DollarSign className="h-5 w-5 text-primary" /><h2 className="text-lg font-semibold">{t.title}</h2></div>
                        <div className="grid gap-4 md:grid-cols-2">
                            <Field label={t.category} required error={form.errors.category}>
                                <Select value={form.data.category} onChange={(e) => form.setData('category', e.target.value)}>
                                    <option value="">{t.select}</option>
                                    {(lookups.categories || []).map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
                                </Select>
                            </Field>
                            <Field label={t.status} required error={form.errors.status}>
                                <Select value={form.data.status} onChange={(e) => form.setData('status', e.target.value)}>
                                    {(lookups.statuses || []).map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                                </Select>
                            </Field>
                            <Field label={t.weight} required error={form.errors.weight}>
                                <Input type="number" step="0.01" value={form.data.weight} onChange={(e) => form.setData('weight', e.target.value)} placeholder={t.placeholder_weight} />
                            </Field>
                            <Field label={t.extra_weight_price} required error={form.errors.extra_weight_price}>
                                <Input type="number" step="0.01" min="0" value={form.data.extra_weight_price} onChange={(e) => form.setData('extra_weight_price', e.target.value)} placeholder={t.extra_weight_price} />
                            </Field>
                            <Field label={t.same_day} required error={form.errors.same_day} prefix={currency}>
                                <Input className="rounded-l-none" type="number" step="0.01" value={form.data.same_day} onChange={(e) => form.setData('same_day', e.target.value)} placeholder={t.placeholder_same_day} />
                            </Field>
                            <Field label={t.next_day} required error={form.errors.next_day} prefix={currency}>
                                <Input className="rounded-l-none" type="number" step="0.01" value={form.data.next_day} onChange={(e) => form.setData('next_day', e.target.value)} placeholder={t.placeholder_next_day} />
                            </Field>
                            <Field label={t.sub_city} required error={form.errors.sub_city} prefix={currency}>
                                <Input className="rounded-l-none" type="number" step="0.01" value={form.data.sub_city} onChange={(e) => form.setData('sub_city', e.target.value)} placeholder={t.placeholder_sub_city} />
                            </Field>
                            <Field label={t.outside_city} required error={form.errors.outside_city} prefix={currency}>
                                <Input className="rounded-l-none" type="number" step="0.01" value={form.data.outside_city} onChange={(e) => form.setData('outside_city', e.target.value)} placeholder={t.placeholder_outside_city} />
                            </Field>
                            <Field label={t.position} required error={form.errors.position}>
                                <Input type="number" value={form.data.position} onChange={(e) => form.setData('position', e.target.value)} placeholder={t.placeholder_position} />
                            </Field>
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
