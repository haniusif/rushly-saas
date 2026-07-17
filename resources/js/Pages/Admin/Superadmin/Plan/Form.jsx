import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Save, AlertCircle, Layers, Package } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';
import { Textarea } from '@/Components/ui/Textarea';
import { cn } from '@/lib/utils';

function Field({ label, required, error, hint, children }) {
    return (
        <div className="space-y-1.5">
            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {label} {required && <span className="text-destructive">*</span>}
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

/**
 * Shared create + edit form for /super-admin/plan. `mode` selects the
 * submit URL/method; `plan` seeds the initial values (empty strings on
 * create). The module grid supports select-all + per-item toggling with a
 * live selected-count badge, and posts back as `modules[]`.
 */
export default function PlanForm({ mode, plan, lookups = {}, currency = '$', urls = {}, t = {} }) {
    const isEdit = mode === 'edit';

    const form = useForm({
        id:                 isEdit ? plan.id : undefined,
        name:                plan.name ?? '',
        price:               plan.price ?? '',
        parcel_count:        plan.parcel_count ?? '',
        deliveryman_count:   plan.deliveryman_count ?? '',
        user_count:          plan.user_count ?? '',
        days_count:          plan.days_count ?? '',
        position:            plan.position ?? '',
        status:              plan.status ?? '',
        description:         plan.description ?? '',
        modules:             plan.modules ?? [],
        _method:             isEdit ? 'put' : 'post',
    });

    const allModuleKeys = (lookups.modules ?? []).map((m) => m.value);
    const selectedSet   = React.useMemo(() => new Set(form.data.modules), [form.data.modules]);
    const allChecked    = allModuleKeys.length > 0 && allModuleKeys.every((k) => selectedSet.has(k));
    const someChecked   = selectedSet.size > 0 && !allChecked;

    const toggleAll = (v) => form.setData('modules', v ? allModuleKeys : []);
    const toggleOne = (key) => {
        const next = new Set(form.data.modules);
        if (next.has(key)) next.delete(key); else next.add(key);
        form.setData('modules', Array.from(next));
    };

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true });
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.breadcrumb, t.plans, t.title]}>
            <Head title={t.title} />
            <form onSubmit={onSubmit} className="space-y-4">

                {/* Plan fields */}
                <Card>
                    <CardContent className="p-0">
                        <div className="flex items-center gap-3 px-6 py-5 border-b border-border">
                            <span className="shrink-0 grid h-9 w-9 place-items-center rounded-lg bg-primary/10 text-primary">
                                <Layers className="h-4 w-4" />
                            </span>
                            <div>
                                <h2 className="text-base font-semibold m-0">{t.plan_section}</h2>
                                <p className="text-xs text-muted-foreground mt-0.5">{t.plan_section_hint}</p>
                            </div>
                        </div>
                        <div className="grid gap-5 md:grid-cols-2 p-6">
                            <Field label={t.name} required error={form.errors.name}>
                                <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                            </Field>
                            <Field label={t.price} required hint={t.price_hint} error={form.errors.price}>
                                <div className="flex items-center gap-2">
                                    <span className="text-sm text-muted-foreground font-medium">{currency}</span>
                                    <Input inputMode="decimal" value={form.data.price} onChange={(e) => form.setData('price', e.target.value)} />
                                </div>
                            </Field>
                            <Field label={t.parcel_count} required error={form.errors.parcel_count}>
                                <Input inputMode="numeric" value={form.data.parcel_count} onChange={(e) => form.setData('parcel_count', e.target.value)} />
                            </Field>
                            <Field label={t.max_deliveryman} required error={form.errors.deliveryman_count}>
                                <Input inputMode="numeric" value={form.data.deliveryman_count} onChange={(e) => form.setData('deliveryman_count', e.target.value)} />
                            </Field>
                            <Field label={t.user_count} error={form.errors.user_count}>
                                <Input inputMode="numeric" min={1} type="number" value={form.data.user_count} onChange={(e) => form.setData('user_count', e.target.value)} />
                            </Field>
                            <Field label={t.days_count} required error={form.errors.days_count}>
                                <Input inputMode="numeric" value={form.data.days_count} onChange={(e) => form.setData('days_count', e.target.value)} />
                            </Field>
                            <Field label={t.position} required error={form.errors.position}>
                                <Input inputMode="numeric" value={form.data.position} onChange={(e) => form.setData('position', e.target.value)} />
                            </Field>
                            <Field label={t.status} error={form.errors.status}>
                                <Select value={form.data.status} onChange={(e) => form.setData('status', e.target.value)}>
                                    {(lookups.statuses || []).map((s) => (
                                        <option key={s.value} value={s.value}>{s.label}</option>
                                    ))}
                                </Select>
                            </Field>
                            <div className="md:col-span-2">
                                <Field label={t.description} error={form.errors.description}>
                                    <Textarea rows={3} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
                                </Field>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Modules */}
                <Card>
                    <CardContent className="p-0">
                        <div className="flex items-center gap-3 px-6 py-5 border-b border-border">
                            <span className="shrink-0 grid h-9 w-9 place-items-center rounded-lg bg-primary/10 text-primary">
                                <Package className="h-4 w-4" />
                            </span>
                            <div className="flex-1">
                                <h2 className="text-base font-semibold m-0">{t.modules}</h2>
                                <p className="text-xs text-muted-foreground mt-0.5">
                                    {t.modules_hint} · <span className="font-medium text-foreground">{selectedSet.size}</span> / {allModuleKeys.length}
                                </p>
                            </div>
                        </div>
                        <div className="p-6">
                            <label className="flex items-center gap-3 px-4 py-3 mb-4 bg-muted/40 border border-border rounded-lg cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={allChecked}
                                    ref={(el) => { if (el) el.indeterminate = someChecked; }}
                                    onChange={(e) => toggleAll(e.target.checked)}
                                    className="w-4 h-4 accent-primary"
                                />
                                <span className="text-sm font-medium">{t.select_all}</span>
                            </label>
                            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                                {(lookups.modules || []).map((m) => {
                                    const checked = selectedSet.has(m.value);
                                    return (
                                        <label
                                            key={m.value}
                                            className={cn(
                                                'flex items-center gap-2 px-3 py-2 border rounded-lg cursor-pointer transition-colors',
                                                checked
                                                    ? 'bg-primary/5 border-primary/40'
                                                    : 'bg-background border-border hover:bg-muted/40'
                                            )}
                                        >
                                            <input
                                                type="checkbox"
                                                checked={checked}
                                                onChange={() => toggleOne(m.value)}
                                                className="w-4 h-4 accent-primary"
                                            />
                                            <span className="text-sm truncate">{m.label}</span>
                                        </label>
                                    );
                                })}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Actions */}
                <div className="flex items-center justify-end gap-2 bg-background border border-border rounded-xl px-6 py-4">
                    <Button variant="outline" asChild>
                        <a href={urls.index}>{t.cancel}</a>
                    </Button>
                    <Button type="submit" disabled={form.processing}>
                        <Save className="h-4 w-4 me-1" />
                        {t.save}
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
