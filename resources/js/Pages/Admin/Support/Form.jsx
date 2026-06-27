import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, AlertCircle, LifeBuoy, FileText, Paperclip, Calendar } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';
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

export default function Form({ mode = 'create', entity = null, lookups = {}, urls = {}, t = {} }) {
    const isEdit = mode === 'edit';
    const form = useForm({
        service: entity?.service ?? '',
        priority: entity?.priority ?? '',
        department_id: entity?.department_id ?? '',
        driver_id: entity?.driver_id ?? '',
        subject: entity?.subject ?? '',
        description: entity?.description ?? '',
        date: entity?.date ?? new Date().toISOString().slice(0, 10),
        attached_file: null,
        ...(isEdit ? { id: entity?.id, _method: 'put' } : {}),
    });

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
                        <div className="mb-5 flex items-center gap-2"><LifeBuoy className="h-5 w-5 text-primary" /><h2 className="text-lg font-semibold">{t.title}</h2></div>
                        <div className="grid gap-4 md:grid-cols-3">
                            <Field label={t.service} required error={form.errors.service}>
                                <Select value={form.data.service} onChange={(e) => form.setData('service', e.target.value)}>
                                    <option value="">—</option>
                                    {(lookups.services || []).map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                                </Select>
                            </Field>
                            <Field label={t.priority} required error={form.errors.priority}>
                                <Select value={form.data.priority} onChange={(e) => form.setData('priority', e.target.value)}>
                                    <option value="">—</option>
                                    {(lookups.priorities || []).map((p) => <option key={p.value} value={p.value}>{p.label}</option>)}
                                </Select>
                            </Field>
                            <Field label={t.department} error={form.errors.department_id}>
                                <Select value={form.data.department_id} onChange={(e) => form.setData('department_id', e.target.value)}>
                                    <option value="">—</option>
                                    {(lookups.departments || []).map((d) => <option key={d.value} value={d.value}>{d.label}</option>)}
                                </Select>
                            </Field>
                            {(lookups.drivers || []).length > 0 && (
                                <Field label="Driver (optional)" error={form.errors.driver_id}>
                                    <Select value={form.data.driver_id} onChange={(e) => form.setData('driver_id', e.target.value)}>
                                        <option value="">—</option>
                                        {(lookups.drivers || []).map((d) => <option key={d.value} value={d.value}>{d.label}</option>)}
                                    </Select>
                                </Field>
                            )}
                        </div>
                        <div className="grid gap-4 mt-4">
                            <Field label={t.subject} required error={form.errors.subject}>
                                <Input value={form.data.subject} onChange={(e) => form.setData('subject', e.target.value)} placeholder={t.placeholder_subject} />
                            </Field>
                            <Field icon={FileText} label={t.description} error={form.errors.description}>
                                <Textarea rows={7} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} placeholder={t.placeholder_desc} />
                            </Field>
                        </div>
                        <div className="grid gap-4 md:grid-cols-2 mt-4">
                            <Field icon={Calendar} label={t.date} required error={form.errors.date}>
                                <Input type="date" value={form.data.date} onChange={(e) => form.setData('date', e.target.value)} />
                            </Field>
                            <Field icon={Paperclip} label={t.attached} error={form.errors.attached_file}>
                                <Input type="file" onChange={(e) => form.setData('attached_file', e.target.files?.[0] || null)} />
                            </Field>
                        </div>
                        <div className="mt-6 flex items-center gap-2 border-t border-border pt-4">
                            <Button type="submit" disabled={form.processing}><Save className="h-4 w-4 me-1" /> {t.save}</Button>
                            <a href={urls.cancel} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">{t.cancel}</a>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </AdminLayout>
    );
}
