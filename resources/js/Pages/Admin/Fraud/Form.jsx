import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, AlertCircle, ShieldAlert, Phone, User, Hash, FileText } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Textarea } from '@/Components/ui/Textarea';

function Field({ icon: Icon, label, required, error, children }) {
    return (
        <div className="space-y-1.5">
            <Label className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {Icon && <Icon className="h-3 w-3" />} {label}
                {required && <span className="text-destructive">*</span>}
            </Label>
            {children}
            {error && <p className="text-xs text-destructive flex items-center gap-1"><AlertCircle className="h-3 w-3" /> {error}</p>}
        </div>
    );
}

export default function Form({ mode = 'create', entity = null, urls = {}, t = {} }) {
    const isEdit = mode === 'edit';
    const form = useForm({
        phone: entity?.phone ?? '',
        name: entity?.name ?? '',
        tracking_id: entity?.tracking_id ?? '',
        details: entity?.details ?? '',
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
                        <div className="mb-5 flex items-center gap-2"><ShieldAlert className="h-5 w-5 text-primary" /><h2 className="text-lg font-semibold">{t.title}</h2></div>
                        <div className="grid gap-4 md:grid-cols-3">
                            <Field icon={Phone} label={t.phone} required error={form.errors.phone}>
                                <Input value={form.data.phone} onChange={(e) => form.setData('phone', e.target.value)} placeholder={t.placeholder_phone} inputMode="tel" />
                            </Field>
                            <Field icon={User} label={t.name} required error={form.errors.name}>
                                <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} placeholder={t.placeholder_name} />
                            </Field>
                            <Field icon={Hash} label={t.tracking_id} error={form.errors.tracking_id}>
                                <Input value={form.data.tracking_id} onChange={(e) => form.setData('tracking_id', e.target.value)} placeholder={t.placeholder_tracking} />
                            </Field>
                            <div className="md:col-span-3">
                                <Field icon={FileText} label={t.details} required error={form.errors.details}>
                                    <Textarea rows={6} value={form.data.details} onChange={(e) => form.setData('details', e.target.value)} placeholder={t.placeholder_details} />
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
