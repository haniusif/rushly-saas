import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
    Plus, Trash2, Save, ArrowLeft, AlertCircle, Store, Building2,
    Send,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';
import { cn } from '@/lib/utils';

function Field({ icon: Icon, label, required, error, children, className }) {
    return (
        <div className={cn('space-y-1.5', className)}>
            <Label className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {Icon && <Icon className="h-3 w-3" />}
                {label}
                {required && <span className="text-destructive">*</span>}
            </Label>
            {children}
            {error && (
                <p className="text-xs text-destructive flex items-center gap-1">
                    <AlertCircle className="h-3 w-3" /> {error}
                </p>
            )}
        </div>
    );
}

function ucwords(s) {
    return String(s || '').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

const newItem = () => ({ product_id: '', location_id: '', quantity: '', batch_number: '' });

export default function Create({ lookups = {}, next_number = '', urls = {}, t = {} }) {
    const form = useForm({
        merchant_id: '', hub_id: '', type: '',
        items: [newItem()],
    });

    const addRow = () => form.setData('items', [...form.data.items, newItem()]);
    const removeRow = (idx) => {
        if (form.data.items.length <= 1) return;
        form.setData('items', form.data.items.filter((_, i) => i !== idx));
    };
    const updateItem = (idx, key, value) => {
        form.setData('items', form.data.items.map((it, i) => i === idx ? { ...it, [key]: value } : it));
    };

    const filteredProducts = React.useMemo(() => {
        const merch = String(form.data.merchant_id);
        const hub   = String(form.data.hub_id);
        return (lookups.products || []).filter((p) =>
            (!merch || String(p.merchant_id) === merch) &&
            (!hub   || String(p.hub_id)      === hub),
        );
    }, [lookups.products, form.data.merchant_id, form.data.hub_id]);

    const filteredLocations = React.useMemo(() => {
        const hub = String(form.data.hub_id);
        return (lookups.locations || []).filter((l) => !hub || String(l.hub_id) === hub);
    }, [lookups.locations, form.data.hub_id]);

    React.useEffect(() => {
        const validProductIds = new Set(filteredProducts.map((p) => String(p.id)));
        const validLocationIds = new Set(filteredLocations.map((l) => String(l.id)));
        const next = form.data.items.map((it) => ({
            ...it,
            product_id:  validProductIds.has(String(it.product_id))   ? it.product_id  : '',
            location_id: validLocationIds.has(String(it.location_id)) ? it.location_id : '',
        }));
        form.setData('items', next);
    }, [form.data.merchant_id, form.data.hub_id]); // eslint-disable-line react-hooks/exhaustive-deps

    const submit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true });
    };

    const merchantPicked = !!form.data.merchant_id;
    const hubPicked = !!form.data.hub_id;
    const typePicked = !!form.data.type;
    const itemsBlocker = !merchantPicked ? t.select_merchant_first
                       : !hubPicked      ? t.select_hub_first
                       : !typePicked     ? t.select_type_first
                       : null;
    const headerError = form.errors.items;

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title_index, t.title]}>
            <Head title={t.title} />

            <form onSubmit={submit} className="space-y-5">
                {/* Header */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="text-sm font-semibold tracking-tight">Header</div>
                            {next_number && (
                                <div className="text-xs text-muted-foreground">
                                    {t.outbound_number}: <span className="font-mono font-semibold text-foreground">{next_number}</span>
                                </div>
                            )}
                        </div>
                        <div className="grid gap-4 md:grid-cols-3">
                            <Field icon={Store} label={t.merchant} required error={form.errors.merchant_id}>
                                <Select value={form.data.merchant_id} onChange={(e) => form.setData('merchant_id', e.target.value)}>
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
                            <Field icon={Send} label={t.type} required error={form.errors.type}>
                                <Select value={form.data.type} onChange={(e) => form.setData('type', e.target.value)}>
                                    <option value="">—</option>
                                    {(lookups.types || []).map((tp) => <option key={tp} value={tp}>{ucwords(tp)}</option>)}
                                </Select>
                            </Field>
                        </div>
                    </CardContent>
                </Card>

                {/* Items */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="text-sm font-semibold tracking-tight">{t.items}</div>
                            <Button type="button" variant="outline" size="sm" onClick={addRow} disabled={!!itemsBlocker}>
                                <Plus className="h-4 w-4 me-1" /> {t.add_item}
                            </Button>
                        </div>

                        {itemsBlocker && (
                            <p className="mb-3 rounded-md border border-amber-200 bg-amber-50 p-2.5 text-xs text-amber-800 flex items-start gap-2">
                                <AlertCircle className="h-3.5 w-3.5 mt-0.5 shrink-0" /> {itemsBlocker}
                            </p>
                        )}

                        {headerError && (
                            <p className="mb-3 text-xs text-destructive flex items-center gap-1">
                                <AlertCircle className="h-3 w-3" /> {headerError}
                            </p>
                        )}

                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                        <th className="px-3 py-2 text-start min-w-[220px]">{t.product} <span className="text-destructive">*</span></th>
                                        <th className="px-3 py-2 text-start min-w-[160px]">{t.location} <span className="text-destructive">*</span></th>
                                        <th className="px-3 py-2 text-end min-w-[100px]">{t.quantity} <span className="text-destructive">*</span></th>
                                        <th className="px-3 py-2 text-start min-w-[140px]">{t.batch}</th>
                                        <th className="px-2 py-2" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {form.data.items.length === 0 && (
                                        <tr>
                                            <td colSpan={5} className="px-3 py-6 text-center text-muted-foreground text-xs">{t.no_items}</td>
                                        </tr>
                                    )}
                                    {form.data.items.map((it, idx) => (
                                        <tr key={idx} className="border-b border-border last:border-0">
                                            <td className="px-3 py-2">
                                                <Select value={it.product_id} onChange={(e) => updateItem(idx, 'product_id', e.target.value)} className="h-9">
                                                    <option value="">—</option>
                                                    {filteredProducts.map((p) => <option key={p.id} value={p.id}>{p.sku} · {p.name}</option>)}
                                                </Select>
                                                {form.errors[`items.${idx}.product_id`] && (
                                                    <p className="text-[11px] text-destructive mt-1">{form.errors[`items.${idx}.product_id`]}</p>
                                                )}
                                            </td>
                                            <td className="px-3 py-2">
                                                <Select value={it.location_id} onChange={(e) => updateItem(idx, 'location_id', e.target.value)} className="h-9">
                                                    <option value="">—</option>
                                                    {filteredLocations.map((l) => <option key={l.id} value={l.id}>{l.code}</option>)}
                                                </Select>
                                                {form.errors[`items.${idx}.location_id`] && (
                                                    <p className="text-[11px] text-destructive mt-1">{form.errors[`items.${idx}.location_id`]}</p>
                                                )}
                                            </td>
                                            <td className="px-3 py-2">
                                                <Input type="number" min="1" value={it.quantity} onChange={(e) => updateItem(idx, 'quantity', e.target.value)} className="h-9 text-end" />
                                                {form.errors[`items.${idx}.quantity`] && (
                                                    <p className="text-[11px] text-destructive mt-1">{form.errors[`items.${idx}.quantity`]}</p>
                                                )}
                                            </td>
                                            <td className="px-3 py-2">
                                                <Input value={it.batch_number} onChange={(e) => updateItem(idx, 'batch_number', e.target.value)} className="h-9 font-mono text-xs" />
                                            </td>
                                            <td className="px-2 py-2">
                                                <Button type="button" variant="ghost" size="icon" className="h-8 w-8 text-destructive" onClick={() => removeRow(idx)} disabled={form.data.items.length === 1} title={t.remove}>
                                                    <Trash2 className="h-3.5 w-3.5" />
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex items-center justify-end gap-2 rounded-xl border border-border bg-card p-4 shadow-sm">
                    <a href={urls.cancel} className="inline-flex h-10 items-center rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent">
                        <ArrowLeft className="h-4 w-4 me-1" /> {t.cancel}
                    </a>
                    <Button type="submit" disabled={form.processing}>
                        <Save className="h-4 w-4 me-1" /> {form.processing ? '…' : t.save}
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
