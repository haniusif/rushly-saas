import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, AlertCircle, Megaphone, Type, FileText, Calendar, ImagePlus } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';
import { Textarea } from '@/Components/ui/Textarea';
import { cn } from '@/lib/utils';

function Field({ icon: Icon, label, required, error, hint, children }) {
    return (
        <div className="space-y-1.5">
            <Label className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {Icon && <Icon className="h-3 w-3" />} {label}
                {required && <span className="text-destructive">*</span>}
            </Label>
            {children}
            {hint && <p className="text-[11px] text-muted-foreground">{hint}</p>}
            {error && <p className="text-xs text-destructive flex items-center gap-1"><AlertCircle className="h-3 w-3" /> {error}</p>}
        </div>
    );
}

export default function Form({ mode = 'create', entity = null, lookups = {}, urls = {}, t = {} }) {
    const isEdit = mode === 'edit';
    const [imgPreview, setImgPreview] = React.useState(null);

    const form = useForm({
        title: entity?.title ?? '',
        status: String(entity?.status ?? '1'),
        date: entity?.date ?? new Date().toISOString().slice(0, 10),
        description: entity?.description ?? '',
        file: null,
        ...(isEdit ? { _method: 'put' } : {}),
    });

    const onFile = (e) => {
        const f = e.target.files?.[0] || null;
        form.setData('file', f);
        if (imgPreview) URL.revokeObjectURL(imgPreview);
        setImgPreview(f ? URL.createObjectURL(f) : null);
    };
    React.useEffect(() => () => { if (imgPreview) URL.revokeObjectURL(imgPreview); }, [imgPreview]);

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { forceFormData: true, preserveScroll: true });
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.list_title, isEdit ? t.edit : t.add]}>
            <Head title={t.title} />
            <div className="mb-4">
                <a href={urls.cancel} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40"><ArrowLeft className="h-4 w-4 me-1" /> {t.back}</a>
            </div>
            <form onSubmit={onSubmit} encType="multipart/form-data">
                <Card>
                    <CardContent className="p-6">
                        <div className="mb-5 flex items-center gap-2"><Megaphone className="h-5 w-5 text-primary" /><h2 className="text-lg font-semibold">{t.title}</h2></div>
                        <div className="grid gap-6 lg:grid-cols-2">
                            <div className="space-y-4">
                                <Field icon={Type} label={t.name_field} required error={form.errors.title}>
                                    <Input value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} placeholder={t.placeholder_title} />
                                </Field>
                                <Field label={t.status} required error={form.errors.status}>
                                    <Select value={form.data.status} onChange={(e) => form.setData('status', e.target.value)}>
                                        {(lookups.statuses || []).map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                                    </Select>
                                </Field>
                                <Field icon={Calendar} label={t.date} required error={form.errors.date}>
                                    <Input type="date" value={form.data.date} onChange={(e) => form.setData('date', e.target.value)} />
                                </Field>
                                <Field icon={ImagePlus} label={t.image} error={form.errors.file} hint={t.file_help}>
                                    <Input type="file" accept="image/*" onChange={onFile} />
                                    {(imgPreview || entity?.image_url) && (
                                        <img src={imgPreview || entity.image_url} alt="" className="mt-2 h-20 w-20 rounded-md border border-border object-cover" />
                                    )}
                                </Field>
                            </div>
                            <Field icon={FileText} label={t.description} error={form.errors.description}>
                                <Textarea rows={14} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} placeholder={t.placeholder_desc} />
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
