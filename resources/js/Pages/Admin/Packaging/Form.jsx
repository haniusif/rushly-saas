import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, AlertCircle, Package, Image as ImageIcon, X } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';

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

export default function Form({ mode = 'create', entity = null, lookups = {}, urls = {}, t = {} }) {
    const isEdit = mode === 'edit';
    const fileInputRef = React.useRef(null);
    const [preview, setPreview] = React.useState(null);

    const form = useForm({
        name: entity?.name ?? '',
        price: entity?.price ?? '',
        status: String(entity?.status ?? '1'),
        position: entity?.position ?? '',
        image: null,
        ...(isEdit ? { id: entity?.id, _method: 'put' } : {}),
    });

    const onSubmit = (e) => {
        e.preventDefault();
        // File upload requires forceFormData so Inertia serializes with FormData.
        form.post(urls.submit, { preserveScroll: true, forceFormData: true });
    };

    const onFile = (e) => {
        const f = e.target.files?.[0] || null;
        form.setData('image', f);
        if (preview) URL.revokeObjectURL(preview);
        setPreview(f ? URL.createObjectURL(f) : null);
    };

    const clearFile = () => {
        form.setData('image', null);
        if (preview) URL.revokeObjectURL(preview);
        setPreview(null);
        if (fileInputRef.current) fileInputRef.current.value = '';
    };

    // Fall back to existing image on the edit page when no new upload is
    // selected. That mirrors the old Blade which only wrote a new photo
    // when $request->image was present.
    const shownImage = preview || (isEdit ? entity?.image : null);

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.list_title, isEdit ? t.edit : t.add]}>
            <Head title={t.title} />
            <div className="mb-4">
                <a href={urls.cancel} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.back}
                </a>
            </div>
            <form onSubmit={onSubmit} className="max-w-3xl">
                <Card>
                    <CardContent className="p-6 space-y-4">
                        <div className="flex items-center gap-2 mb-2">
                            <Package className="h-5 w-5 text-primary" />
                            <h2 className="text-lg font-semibold">{t.title}</h2>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <Field label={t.name} required error={form.errors.name}>
                                <Input
                                    value={form.data.name}
                                    onChange={(e) => form.setData('name', e.target.value)}
                                    placeholder={t.placeholder_name}
                                    autoFocus
                                />
                            </Field>
                            <Field label={t.price} required error={form.errors.price}>
                                <Input
                                    type="number"
                                    step="0.01"
                                    value={form.data.price}
                                    onChange={(e) => form.setData('price', e.target.value)}
                                    placeholder={t.placeholder_price}
                                />
                            </Field>
                            <Field label={t.status} required error={form.errors.status}>
                                <Select value={form.data.status} onChange={(e) => form.setData('status', e.target.value)}>
                                    {(lookups.statuses || []).map((s) => (
                                        <option key={s.value} value={s.value}>{s.label}</option>
                                    ))}
                                </Select>
                            </Field>
                            <Field label={t.position} error={form.errors.position}>
                                <Input
                                    type="number"
                                    value={form.data.position}
                                    onChange={(e) => form.setData('position', e.target.value)}
                                    placeholder={t.placeholder_pos}
                                />
                            </Field>
                        </div>

                        <Field label={t.image} error={form.errors.image} hint={t.image_hint}>
                            <div className="flex items-start gap-3">
                                <div className="h-20 w-20 rounded-lg border border-border bg-muted grid place-items-center overflow-hidden shrink-0">
                                    {shownImage ? (
                                        <img src={shownImage} alt="" className="h-full w-full object-cover" />
                                    ) : (
                                        <ImageIcon className="h-5 w-5 text-muted-foreground/60" />
                                    )}
                                </div>
                                <div className="flex-1 space-y-2">
                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp"
                                        onChange={onFile}
                                        className="block w-full text-sm text-muted-foreground file:me-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-foreground hover:file:bg-muted/70"
                                    />
                                    {form.data.image && (
                                        <button
                                            type="button"
                                            onClick={clearFile}
                                            className="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-destructive"
                                        >
                                            <X className="h-3 w-3" /> Clear
                                        </button>
                                    )}
                                </div>
                            </div>
                        </Field>

                        <div className="flex items-center gap-2 border-t border-border pt-4">
                            <Button type="submit" disabled={form.processing}>
                                <Save className="h-4 w-4 me-1" /> {isEdit ? t.update : t.save}
                            </Button>
                            <a href={urls.cancel} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">
                                {t.cancel}
                            </a>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </AdminLayout>
    );
}
