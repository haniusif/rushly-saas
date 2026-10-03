import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
    Save, ArrowLeft, AlertCircle, Package, MapPin, Hash,
    StickyNote, Image as ImageIcon, X, Bug,
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

export default function Create({ lookups = {}, urls = {}, t = {} }) {
    const form = useForm({
        product_id:       '',
        location_id:      '',
        quantity_damaged: '',
        cause:            '',
        notes:            '',
        action_taken:     '',
        photos:           [],
    });

    const [previews, setPreviews] = React.useState([]);

    const onPhotos = (e) => {
        const files = Array.from(e.target.files || []);
        if (files.length === 0) return;
        const next = [...form.data.photos, ...files];
        form.setData('photos', next);
        // Generate previews lazily — only the new files need URLs created.
        const newPreviews = files.map((f) => ({
            name: f.name,
            url:  f.type.startsWith('image/') ? URL.createObjectURL(f) : null,
        }));
        setPreviews((p) => [...p, ...newPreviews]);
        // Clear the input so the same file can be picked again after removal.
        e.target.value = '';
    };

    const removePhoto = (idx) => {
        form.setData('photos', form.data.photos.filter((_, i) => i !== idx));
        setPreviews((p) => p.filter((_, i) => i !== idx));
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
                            <div className="mb-4 text-sm font-semibold tracking-tight">What happened</div>
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
                                <Field icon={Hash} label={t.quantity} required error={form.errors.quantity_damaged}>
                                    <Input type="number" min="1" value={form.data.quantity_damaged}
                                        onChange={(e) => form.setData('quantity_damaged', e.target.value)} />
                                </Field>
                                <Field icon={Bug} label={t.cause} required error={form.errors.cause}>
                                    <Select value={form.data.cause} onChange={(e) => form.setData('cause', e.target.value)}>
                                        <option value="">—</option>
                                        {(lookups.causes || []).map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
                                    </Select>
                                </Field>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="mb-4 text-sm font-semibold tracking-tight">Context</div>
                            <div className="grid gap-4">
                                <Field label={`${t.action_taken} (${t.optional})`} error={form.errors.action_taken} hint={t.action_hint}>
                                    <Select value={form.data.action_taken} onChange={(e) => form.setData('action_taken', e.target.value)}>
                                        <option value="">— Decide later —</option>
                                        {(lookups.actions || []).map((a) => <option key={a.value} value={a.value}>{a.label}</option>)}
                                    </Select>
                                </Field>
                                <Field icon={StickyNote} label={`${t.notes} (${t.optional})`} error={form.errors.notes}>
                                    <Textarea rows={3} value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} />
                                </Field>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="mb-4 text-sm font-semibold tracking-tight">{t.photos}</div>
                            <p className="mb-3 text-xs text-muted-foreground">{t.photos_hint}</p>

                            <label className="flex cursor-pointer items-center justify-center gap-2 rounded-md border border-dashed border-input bg-background/60 px-3 py-6 text-sm font-medium text-muted-foreground hover:bg-accent/40 transition-colors">
                                <ImageIcon className="h-5 w-5" />
                                <span>{t.add_photos}</span>
                                <input type="file" accept="image/*" multiple onChange={onPhotos} className="hidden" />
                            </label>

                            {form.data.photos.length > 0 && (
                                <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                                    {form.data.photos.map((file, idx) => (
                                        <div key={idx} className="relative rounded-md border border-border overflow-hidden bg-muted/30">
                                            <div className="aspect-square">
                                                {previews[idx]?.url
                                                    ? <img src={previews[idx].url} alt="" className="h-full w-full object-cover" />
                                                    : <div className="h-full w-full grid place-items-center text-muted-foreground"><ImageIcon className="h-6 w-6" /></div>}
                                            </div>
                                            <div className="p-1.5 text-[10px] truncate">{file.name}</div>
                                            <button type="button" onClick={() => removePhoto(idx)}
                                                className="absolute top-1 end-1 grid h-6 w-6 place-items-center rounded-full bg-destructive text-destructive-foreground shadow hover:bg-destructive/90">
                                                <X className="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {form.errors['photos.0'] && (
                                <p className="mt-2 text-xs text-destructive flex items-center gap-1">
                                    <AlertCircle className="h-3 w-3" /> {form.errors['photos.0']}
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <div className="lg:col-span-1">
                    <Card className="sticky top-20">
                        <CardContent className="space-y-3 pt-6">
                            <div className="text-sm font-semibold">Summary</div>
                            <div className="text-xs text-muted-foreground">
                                {form.data.photos.length > 0
                                    ? `${form.data.photos.length} photo${form.data.photos.length === 1 ? '' : 's'} attached`
                                    : 'No photos attached yet'}
                            </div>
                            <div className="text-[11px] text-muted-foreground leading-relaxed">
                                A damage report doesn't deduct stock by itself. Use an Adjustment (with reason "Damage") or set the action to "Written off" — the show page wires those follow-ups.
                            </div>
                            <div className="flex flex-col gap-2 pt-2">
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
