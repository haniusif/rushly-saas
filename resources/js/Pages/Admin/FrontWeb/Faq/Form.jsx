import * as React from 'react';
import { useForm } from '@inertiajs/react';
import SimpleForm, { Field, Input, Select, Textarea } from '../_SimpleForm';

export default function FaqForm({ mode, row, lookups = {}, urls = {}, t = {} }) {
    const isEdit = mode === 'edit';
    const form = useForm({
        id: isEdit ? row.id : undefined,
        question: row.question ?? '',
        answer:   row.answer   ?? '',
        position: row.position ?? '',
        status:   row.status   ?? '',
        _method:  isEdit ? 'put' : 'post',
    });
    const onSubmit = (e) => { e.preventDefault(); form.post(urls.submit, { preserveScroll: true }); };
    return (
        <SimpleForm
            title={t.title}
            breadcrumbs={[t.front_web, t.faq, isEdit ? row.question : t.title]}
            sectionLabel={t.section}
            cancelHref={urls.index}
            saveLabel={t.save}
            cancelLabel={t.cancel}
            onSubmit={onSubmit}
            processing={form.processing}
        >
            <Field label={t.question} required error={form.errors.question} className="md:col-span-2">
                <Input value={form.data.question} onChange={(e) => form.setData('question', e.target.value)} required />
            </Field>
            <Field label={t.answer} required error={form.errors.answer} className="md:col-span-2">
                <Textarea rows={6} value={form.data.answer} onChange={(e) => form.setData('answer', e.target.value)} required />
            </Field>
            <Field label={t.position} error={form.errors.position}>
                <Input inputMode="numeric" value={form.data.position} onChange={(e) => form.setData('position', e.target.value)} />
            </Field>
            <Field label={t.status} error={form.errors.status}>
                <Select value={form.data.status} onChange={(e) => form.setData('status', e.target.value)}>
                    {(lookups.statuses || []).map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                </Select>
            </Field>
        </SimpleForm>
    );
}
