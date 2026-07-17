import * as React from 'react';
import { useForm } from '@inertiajs/react';
import SimpleForm, { Field, ImageTile, Input, Select, Textarea } from '../_SimpleForm';

export default function BlogForm({ mode, row, lookups = {}, assets = {}, urls = {}, t = {} }) {
    const isEdit = mode === 'edit';
    const form = useForm({
        id: isEdit ? row.id : undefined,
        title:       row.title       ?? '',
        description: row.description ?? '',
        position:    row.position    ?? '',
        status:      row.status      ?? '',
        image:       null,
        _method:     isEdit ? 'put' : 'post',
    });
    const onSubmit = (e) => { e.preventDefault(); form.post(urls.submit, { forceFormData: true, preserveScroll: true }); };
    return (
        <SimpleForm
            title={t.title}
            breadcrumbs={[t.front_web, t.blogs, isEdit ? row.title : t.title]}
            sectionLabel={t.section}
            cancelHref={urls.index}
            saveLabel={t.save}
            cancelLabel={t.cancel}
            onSubmit={onSubmit}
            processing={form.processing}
        >
            <Field label={t.blog_title} required error={form.errors.title} className="md:col-span-2">
                <Input value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} required />
            </Field>
            <Field label={t.description} error={form.errors.description} className="md:col-span-2">
                <Textarea rows={8} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
            </Field>
            <Field label={t.image} error={form.errors.image} className="md:col-span-2">
                <ImageTile currentUrl={assets.image_url} file={form.data.image} onPick={(f) => form.setData('image', f)} />
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
