import * as React from 'react';
import { useForm } from '@inertiajs/react';
import SimpleForm, { Field, Input, Select, Textarea } from '../_SimpleForm';

/**
 * Front-web static page editor. Only edit is exposed - the set of pages
 * (About, Privacy, Terms, …) is fixed and seeded, so no create surface.
 */
export default function PageForm({ mode, row, lookups = {}, urls = {}, t = {} }) {
    const form = useForm({
        id: row.id,
        title:       row.title       ?? '',
        description: row.description ?? '',
        status:      row.status      ?? '',
        _method:     'put',
    });
    const onSubmit = (e) => { e.preventDefault(); form.post(urls.submit, { preserveScroll: true }); };
    return (
        <SimpleForm
            title={t.title}
            breadcrumbs={[t.front_web, t.pages, row.title]}
            sectionLabel={t.section}
            cancelHref={urls.index}
            saveLabel={t.save}
            cancelLabel={t.cancel}
            onSubmit={onSubmit}
            processing={form.processing}
        >
            <Field label={t.page_title} required error={form.errors.title} className="md:col-span-2">
                <Input value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} required />
            </Field>
            <Field label={t.description} error={form.errors.description} className="md:col-span-2">
                <Textarea rows={12} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
            </Field>
            <Field label={t.status} error={form.errors.status}>
                <Select value={form.data.status} onChange={(e) => form.setData('status', e.target.value)}>
                    {(lookups.statuses || []).map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                </Select>
            </Field>
        </SimpleForm>
    );
}
