import * as React from 'react';
import { useForm } from '@inertiajs/react';
import SimpleForm, { Field, ImageTile, Input, Select } from '../_SimpleForm';

export default function PartnerForm({ mode, row, lookups = {}, assets = {}, urls = {}, t = {} }) {
    const isEdit = mode === 'edit';
    const form = useForm({
        id: isEdit ? row.id : undefined,
        name:     row.name     ?? '',
        link:     row.link     ?? '',
        position: row.position ?? '',
        status:   row.status   ?? '',
        image:    null,
        _method:  isEdit ? 'put' : 'post',
    });
    const onSubmit = (e) => { e.preventDefault(); form.post(urls.submit, { forceFormData: true, preserveScroll: true }); };
    return (
        <SimpleForm
            title={t.title}
            breadcrumbs={[t.front_web, t.partners, isEdit ? row.name : t.title]}
            sectionLabel={t.section}
            cancelHref={urls.index}
            saveLabel={t.save}
            cancelLabel={t.cancel}
            onSubmit={onSubmit}
            processing={form.processing}
        >
            <Field label={t.name} required error={form.errors.name}>
                <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required />
            </Field>
            <Field label={t.link} error={form.errors.link}>
                <Input type="url" value={form.data.link} onChange={(e) => form.setData('link', e.target.value)} placeholder="https://..." />
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
