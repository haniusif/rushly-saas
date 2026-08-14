import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, MessageCircle, Calendar, Paperclip } from 'lucide-react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import { Textarea } from '@/Components/ui/Textarea';

function Field({ label, required, error, children, className }) {
    return (
        <div className={className || 'space-y-1.5'}>
            <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {label}
                {required && <span className="text-destructive">&nbsp;*</span>}
            </label>
            {children}
            {error && <p className="text-xs text-destructive">{error}</p>}
        </div>
    );
}

export default function Create({ lookups = {}, urls = {}, t = {} }) {
    const today = new Date().toISOString().slice(0, 10);
    const form = useForm({
        department_id: '',
        service: '',
        priority: '',
        subject: '',
        date: today,
        description: '',
        attached_file: null,
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(urls.store, { forceFormData: true, preserveScroll: true });
    };

    return (
        <MerchantLayout title={t.add} breadcrumbs={[t.title_index, t.add]}>
            <Head title={`${t.add}`} />

            <div className="mb-4 flex items-center gap-3">
                <a href={urls.cancel} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent no-underline">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.cancel}
                </a>
                <div className="flex items-center gap-2">
                    <MessageCircle className="h-5 w-5 text-primary" />
                    <h1 className="text-xl font-semibold m-0">{t.add}</h1>
                </div>
            </div>

            <form onSubmit={submit}>
                <Card>
                    <CardContent className="p-5">
                        <div className="grid gap-4 md:grid-cols-3">
                            <Field label={t.department} required error={form.errors.department_id}>
                                <Select
                                    value={form.data.department_id}
                                    onChange={(e) => form.setData('department_id', e.target.value)}
                                >
                                    <option value="">{t.department_ph}</option>
                                    {(lookups.departments || []).map((d) => (
                                        <option key={d.id} value={d.id}>{d.name}</option>
                                    ))}
                                </Select>
                            </Field>

                            <Field label={t.service} required error={form.errors.service}>
                                <Select
                                    value={form.data.service}
                                    onChange={(e) => form.setData('service', e.target.value)}
                                >
                                    <option value="">{t.service_ph}</option>
                                    {(lookups.services || []).map((s) => (
                                        <option key={s.value} value={s.value}>{s.label}</option>
                                    ))}
                                </Select>
                            </Field>

                            <Field label={t.priority} required error={form.errors.priority}>
                                <Select
                                    value={form.data.priority}
                                    onChange={(e) => form.setData('priority', e.target.value)}
                                >
                                    <option value="">{t.priority_ph}</option>
                                    {(lookups.priorities || []).map((p) => (
                                        <option key={p.value} value={p.value}>{p.label}</option>
                                    ))}
                                </Select>
                            </Field>

                            <Field label={t.subject} required error={form.errors.subject} className="md:col-span-2 space-y-1.5">
                                <Input
                                    value={form.data.subject}
                                    onChange={(e) => form.setData('subject', e.target.value)}
                                    placeholder={t.subject_ph}
                                />
                            </Field>

                            <Field label={t.date} error={form.errors.date}>
                                <div className="relative">
                                    <Calendar className="absolute top-1/2 start-3 -translate-y-1/2 text-muted-foreground h-3.5 w-3.5" />
                                    <Input
                                        type="date"
                                        value={form.data.date}
                                        onChange={(e) => form.setData('date', e.target.value)}
                                        className="ps-9"
                                    />
                                </div>
                            </Field>

                            <Field label={t.description} error={form.errors.description} className="md:col-span-3 space-y-1.5">
                                <Textarea
                                    rows={5}
                                    value={form.data.description}
                                    onChange={(e) => form.setData('description', e.target.value)}
                                />
                            </Field>

                            <Field label={t.attached_file} error={form.errors.attached_file} className="md:col-span-3 space-y-1.5">
                                <div className="flex items-center gap-2">
                                    <Paperclip className="h-4 w-4 text-muted-foreground" />
                                    <input
                                        type="file"
                                        onChange={(e) => form.setData('attached_file', e.target.files?.[0] || null)}
                                        className="block text-sm file:me-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-muted file:text-foreground hover:file:bg-muted/80 cursor-pointer"
                                    />
                                </div>
                            </Field>
                        </div>

                        <div className="mt-6 flex items-center gap-2 border-t border-border pt-4">
                            <button
                                type="submit"
                                disabled={form.processing}
                                className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:opacity-90 disabled:opacity-50"
                            >
                                <Save className="h-4 w-4" /> {form.processing ? '…' : t.save}
                            </button>
                            <a
                                href={urls.cancel}
                                className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium rounded-md border border-input bg-background hover:bg-muted/40 no-underline"
                            >
                                {t.cancel}
                            </a>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </MerchantLayout>
    );
}
