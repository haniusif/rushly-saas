import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
    Package, Tag, Hash, Building2, Store, Layers, Scale, Box,
    Ruler, AlertCircle, Save, ArrowLeft, FileText,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';
import { Textarea } from '@/Components/ui/Textarea';
import { cn } from '@/lib/utils';

function Field({ icon: Icon, label, required, error, hint, children, className }) {
    return (
        <div className={cn('space-y-1.5', className)}>
            <Label className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {Icon && <Icon className="h-3 w-3" />}
                {label}
                {required && <span className="text-destructive">*</span>}
            </Label>
            {children}
            {hint && <p className="text-[11px] text-muted-foreground">{hint}</p>}
            {error && (
                <p className="text-xs text-destructive flex items-center gap-1">
                    <AlertCircle className="h-3 w-3" /> {error}
                </p>
            )}
        </div>
    );
}

function Section({ title, children }) {
    return (
        <Card>
            <CardContent className="pt-6">
                <div className="mb-4 text-sm font-semibold tracking-tight">{title}</div>
                {children}
            </CardContent>
        </Card>
    );
}

function CheckTile({ checked, label, onToggle, hint }) {
    return (
        <label
            onClick={onToggle}
            className={cn(
                'flex cursor-pointer items-start gap-3 rounded-md border p-3 transition-colors',
                checked ? 'border-primary bg-primary/5' : 'border-input bg-card hover:bg-muted/40',
            )}
        >
            <input type="checkbox" checked={checked} onChange={() => {}} className="mt-0.5 h-4 w-4 rounded border-input" />
            <div className="min-w-0">
                <div className="text-sm font-medium">{label}</div>
                {hint && <div className="text-[11px] text-muted-foreground">{hint}</div>}
            </div>
        </label>
    );
}

export default function Form({ title, mode = 'create', product = null, lookups = {}, urls = {}, t = {} }) {
    const isEdit = mode === 'edit';
    const form = useForm({
        merchant_id:   String(product?.merchant_id ?? ''),
        hub_id:        String(product?.hub_id ?? ''),
        name:          product?.name ?? '',
        sku:           product?.sku ?? '',
        barcode:       product?.barcode ?? '',
        description:   product?.description ?? '',
        category:      product?.category ?? '',
        weight:        product?.weight ?? '',
        unit:          product?.unit ?? '',
        reorder_point: product?.reorder_point ?? 0,
        dim_l:         product?.dimensions?.l ?? '',
        dim_w:         product?.dimensions?.w ?? '',
        dim_h:         product?.dimensions?.h ?? '',
        track_expiry:  !!product?.track_expiry,
        is_active:     product == null ? true : !!product.is_active,
        ...(isEdit ? { _method: 'put' } : {}),
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true });
    };

    return (
        <AdminLayout title={title} breadcrumbs={[t.title_index, title]}>
            <Head title={title} />

            <form onSubmit={submit} className="grid gap-5 lg:grid-cols-3">
                <div className="lg:col-span-2 space-y-5">
                    <Section title={t.identity}>
                        <div className="grid gap-4 md:grid-cols-2">
                            <Field icon={Package} label={t.name} required error={form.errors.name}>
                                <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} maxLength={191} />
                            </Field>
                            <Field icon={Hash} label={t.sku} required error={form.errors.sku}>
                                <Input value={form.data.sku} onChange={(e) => form.setData('sku', e.target.value)} maxLength={191} className="font-mono" />
                            </Field>
                            <Field icon={Tag} label={t.barcode} error={form.errors.barcode} hint={t.barcode_hint} className="md:col-span-2">
                                <Input value={form.data.barcode} onChange={(e) => form.setData('barcode', e.target.value)} maxLength={191} className="font-mono" />
                            </Field>
                        </div>
                    </Section>

                    <Section title={t.classification}>
                        <div className="grid gap-4 md:grid-cols-2">
                            <Field icon={Store} label={t.merchant} required error={form.errors.merchant_id}>
                                <Select value={form.data.merchant_id} onChange={(e) => form.setData('merchant_id', e.target.value)} disabled={isEdit}>
                                    <option value="">—</option>
                                    {(lookups.merchants || []).map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                                </Select>
                            </Field>
                            <Field icon={Building2} label={t.hub} required error={form.errors.hub_id}>
                                <Select value={form.data.hub_id} onChange={(e) => form.setData('hub_id', e.target.value)}>
                                    <option value="">—</option>
                                    {(lookups.hubs || []).map((h) => <option key={h.id} value={h.id}>{h.name}</option>)}
                                </Select>
                            </Field>
                            <Field icon={Layers} label={t.category} error={form.errors.category}>
                                <Input value={form.data.category} onChange={(e) => form.setData('category', e.target.value)} maxLength={191} />
                            </Field>
                            <Field icon={Box} label={t.unit} required error={form.errors.unit}>
                                <Select value={form.data.unit} onChange={(e) => form.setData('unit', e.target.value)}>
                                    <option value="">—</option>
                                    {(lookups.units || []).map((u) => <option key={u} value={u}>{u}</option>)}
                                </Select>
                            </Field>
                        </div>
                    </Section>

                    <Section title={t.metrics}>
                        <div className="grid gap-4 md:grid-cols-3">
                            <Field icon={Scale} label={t.weight} error={form.errors.weight}>
                                <Input type="number" step="0.001" value={form.data.weight} onChange={(e) => form.setData('weight', e.target.value)} />
                            </Field>
                            <Field icon={Hash} label={t.reorder_point} error={form.errors.reorder_point}>
                                <Input type="number" min="0" value={form.data.reorder_point} onChange={(e) => form.setData('reorder_point', e.target.value)} />
                            </Field>
                            <div />
                            <Field icon={Ruler} label={t.dim_l} error={form.errors.dim_l}>
                                <Input type="number" step="0.01" value={form.data.dim_l} onChange={(e) => form.setData('dim_l', e.target.value)} />
                            </Field>
                            <Field icon={Ruler} label={t.dim_w} error={form.errors.dim_w}>
                                <Input type="number" step="0.01" value={form.data.dim_w} onChange={(e) => form.setData('dim_w', e.target.value)} />
                            </Field>
                            <Field icon={Ruler} label={t.dim_h} error={form.errors.dim_h}>
                                <Input type="number" step="0.01" value={form.data.dim_h} onChange={(e) => form.setData('dim_h', e.target.value)} />
                            </Field>
                        </div>
                    </Section>

                    <Section title={t.description}>
                        <Field icon={FileText} label={t.description} error={form.errors.description}>
                            <Textarea rows={4} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
                        </Field>
                    </Section>
                </div>

                <div className="lg:col-span-1 space-y-5">
                    <Section title={t.flags}>
                        <div className="space-y-3">
                            <CheckTile
                                checked={form.data.track_expiry}
                                onToggle={() => form.setData('track_expiry', !form.data.track_expiry)}
                                label={t.track_expiry}
                                hint="Stock rows must record an expiry date"
                            />
                            <CheckTile
                                checked={form.data.is_active}
                                onToggle={() => form.setData('is_active', !form.data.is_active)}
                                label={t.is_active}
                                hint="Inactive products are hidden from pickers"
                            />
                        </div>
                    </Section>

                    <Card className="sticky top-20">
                        <CardContent className="space-y-3 pt-6">
                            <div className="text-sm font-semibold">{title}</div>
                            <div className="text-xs text-muted-foreground">
                                {isEdit
                                    ? 'Changes to merchant are locked once a product is in use.'
                                    : 'A unique SKU is required. Barcode auto-generates from SKU when blank.'}
                            </div>
                            <div className="flex flex-col gap-2">
                                <Button type="submit" disabled={form.processing}>
                                    <Save className="h-4 w-4 me-1" /> {form.processing ? '…' : (isEdit ? t.update : t.save)}
                                </Button>
                                <a href={urls.cancel} className="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent">
                                    <ArrowLeft className="h-4 w-4 me-1" /> {t.cancel}
                                </a>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </form>
        </AdminLayout>
    );
}
