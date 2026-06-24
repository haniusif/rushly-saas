import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, AlertCircle, Tags } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';

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
        title: entity?.title ?? '',
        status: String(entity?.status ?? '1'),
        position: entity?.position ?? '',
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
            <form onSubmit={onSubmit} className="max-w-2xl">
                <Card>
                    <CardContent className="p-6 space-y-4">
                        <div className="flex items-center gap-2 mb-2"><Tags className="h-5 w-5 text-primary" /><h2 className="text-lg font-semibold">{t.title}</h2></div>
                        <Field label={t.name} required error={form.errors.title}>
                            <Input value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} placeholder={t.placeholder_title} />
                        </Field>
                        <Field label={t.status} error={form.errors.status}>
                            <Select value={form.data.status} onChange={(e) => form.setData('status', e.target.value)}>
                                {(lookups.statuses || []).map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                            </Select>
                        </Field>
                        <Field label={t.position} error={form.errors.position}>
                            <Input type="number" value={form.data.position} onChange={(e) => form.setData('position', e.target.value)} placeholder={t.placeholder_pos} />
                        </Field>
                        <div className="flex items-center gap-2 border-t border-border pt-4">
                            <Button type="submit" disabled={form.processing}><Save className="h-4 w-4 me-1" /> {isEdit ? t.update : t.save}</Button>
                            <a href={urls.cancel} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">{t.cancel}</a>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </AdminLayout>
    );
}
