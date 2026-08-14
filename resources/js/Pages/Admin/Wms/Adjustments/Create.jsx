import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
    Save, ArrowLeft, AlertCircle, Package, MapPin, Hash, FileText,
    StickyNote, Image as ImageIcon, ArrowRight,
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

function ucwords(s) {
    return String(s || '').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

export default function Create({ lookups = {}, pre = {}, urls = {}, t = {} }) {
    const form = useForm({
        product_id:     pre.product_id ? String(pre.product_id) : '',
        location_id:    pre.location_id ? String(pre.location_id) : '',
        quantity_after: '',
        reason:         '',
        reference:      '',
        notes:          '',
        photo:          null,
    });

    const [currentQty, setCurrentQty] = React.useState(pre.current_qty);
    const [loadingQty, setLoadingQty] = React.useState(false);
    const [photoPreview, setPhotoPreview] = React.useState(null);

    // Fetch the current stock qty when both pickers are set.
    React.useEffect(() => {
        if (!form.data.product_id || !form.data.location_id) {
            setCurrentQty(null);
            return;
        }
        setLoadingQty(true);
        const params = new URLSearchParams({
            product_id: form.data.product_id,
            location_id: form.data.location_id,
        });
        fetch(`${urls.lookup_qty}?${params}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            credentials: 'same-origin',
        })
            .then((r) => r.ok ? r.json() : { quantity: null })
            .then((d) => setCurrentQty(d.quantity))
            .catch(() => setCurrentQty(null))
            .finally(() => setLoadingQty(false));
    }, [form.data.product_id, form.data.location_id, urls.lookup_qty]);

    const after = form.data.quantity_after === '' ? null : Number(form.data.quantity_after);
    const before = currentQty == null ? null : Number(currentQty);
    const change = (after != null && before != null) ? after - before : null;
    const changePct = (change != null && before > 0) ? (change / before) * 100 : null;
    const willRequireApproval = changePct != null && Math.abs(changePct) >= 20;

    const handlePhoto = (e) => {
        const file = e.target.files?.[0] || null;
        form.setData('photo', file);
        if (file && file.type.startsWith('image/')) {
            setPhotoPreview(URL.createObjectURL(file));
        } else {
            setPhotoPreview(null);
        }
    };

    const submit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { forceFormData: true, preserveScroll: true });
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title_index, t.title]}>
            <Head title={t.title} />

            <form onSubmit={submit} encType="multipart/form-data" className="grid gap-5 lg:grid-cols-3">
                <div className="lg:col-span-2 space-y-5">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="mb-4 text-sm font-semibold tracking-tight">Target stock</div>
                            <div className="grid gap-4 md:grid-cols-2">
                                <Field icon={Package} label={t.product} required error={form.errors.product_id}>
                                    <Select value={form.data.product_id} onChange={(e) => form.setData('product_id', e.target.value)}>
                                        <option value="">—</option>
                                        {(lookups.products || []).map((p) => <option key={p.id} value={p.id}>{p.sku} · {p.name}</option>)}
                                    </Select>
                                </Field>
                                <Field icon={MapPin} label={t.location} required error={form.errors.location_id}>
                                    <Select value={form.data.location_id} onChange={(e) => form.setData('location_id', e.target.value)}>
                                        <option value="">—</option>
                                        {(lookups.locations || []).map((l) => <option key={l.id} value={l.id}>{l.code}</option>)}
                                    </Select>
                                </Field>
                                <Field icon={Hash} label={t.quantity_after} required error={form.errors.quantity_after}>
                                    <Input type="number" min="0" value={form.data.quantity_after}
                                        onChange={(e) => form.setData('quantity_after', e.target.value)} />
                                </Field>
                                <Field icon={ArrowRight} label={t.reason} required error={form.errors.reason}>
                                    <Select value={form.data.reason} onChange={(e) => form.setData('reason', e.target.value)}>
                                        <option value="">—</option>
                                        {(lookups.reasons || []).map((r) => <option key={r} value={r}>{ucwords(r)}</option>)}
                                    </Select>
                                </Field>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="mb-4 text-sm font-semibold tracking-tight">Context</div>
                            <div className="grid gap-4 md:grid-cols-2">
                                <Field icon={FileText} label={`${t.reference} (${t.optional})`} error={form.errors.reference} hint={t.reference_hint}>
                                    <Input value={form.data.reference} onChange={(e) => form.setData('reference', e.target.value)} maxLength={191} />
                                </Field>
                                <Field icon={StickyNote} label={`${t.notes} (${t.optional})`} error={form.errors.notes}>
                                    <Textarea rows={2} value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} />
                                </Field>
                                <Field icon={ImageIcon} label={`${t.photo} (${t.optional})`} error={form.errors.photo} hint={t.photo_hint} className="md:col-span-2">
                                    <label className="flex cursor-pointer items-center gap-3 rounded-md border border-dashed border-input bg-background/60 px-3 py-2.5 text-sm hover:bg-accent/40 transition-colors">
                                        {photoPreview
                                            ? <img src={photoPreview} alt="" className="h-12 w-12 rounded object-cover" />
                                            : <span className="grid h-12 w-12 place-items-center rounded bg-muted text-muted-foreground"><ImageIcon className="h-5 w-5" /></span>}
                                        <span className="flex-1 truncate text-muted-foreground">
                                            {photoPreview ? 'Replace…' : t.photo_hint}
                                        </span>
                                        <input type="file" accept="image/*" onChange={handlePhoto} className="hidden" />
                                    </label>
                                </Field>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Sticky summary */}
                <div className="lg:col-span-1 space-y-5">
                    <Card className="sticky top-20">
                        <CardContent className="pt-6">
                            <div className="mb-3 text-sm font-semibold">Stock change preview</div>
                            {!form.data.product_id || !form.data.location_id ? (
                                <div className="text-xs text-muted-foreground">{t.select_pair}</div>
                            ) : (
                                <div className="space-y-3 text-sm">
                                    <div className="flex items-center justify-between border-b border-border py-2">
                                        <span className="text-muted-foreground">{t.current_qty}</span>
                                        <span className="font-semibold tabular-nums">{loadingQty ? '…' : (before ?? '—')}</span>
                                    </div>
                                    <div className="flex items-center justify-between border-b border-border py-2">
                                        <span className="text-muted-foreground">{t.quantity_after}</span>
                                        <span className="font-semibold tabular-nums">{after ?? '—'}</span>
                                    </div>
                                    <div className="flex items-center justify-between py-2">
                                        <span className="text-muted-foreground">{t.change}</span>
                                        <span className={cn('font-bold tabular-nums',
                                            change == null ? 'text-muted-foreground'
                                            : change > 0 ? 'text-emerald-700'
                                            : change < 0 ? 'text-rose-700'
                                            : 'text-foreground',
                                        )}>
                                            {change == null ? '—' : (change > 0 ? '+' : '') + change}
                                            {changePct != null && (
                                                <span className="ms-1 text-xs text-muted-foreground">
                                                    ({changePct > 0 ? '+' : ''}{changePct.toFixed(0)}%)
                                                </span>
                                            )}
                                        </span>
                                    </div>
                                    {willRequireApproval && (
                                        <p className="rounded-md border border-amber-200 bg-amber-50 p-2.5 text-[11px] text-amber-800 flex items-start gap-2">
                                            <AlertCircle className="h-3.5 w-3.5 mt-0.5 shrink-0" />
                                            {t.approval_hint}
                                        </p>
                                    )}
                                </div>
                            )}

                            <div className="mt-4 flex flex-col gap-2">
                                <Button type="submit" disabled={form.processing}>
                                    <Save className="h-4 w-4 me-1" /> {form.processing ? '…' : t.save}
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
