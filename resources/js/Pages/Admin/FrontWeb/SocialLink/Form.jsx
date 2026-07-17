import * as React from 'react';
import { useForm } from '@inertiajs/react';
import SimpleForm, { Field, Input, Select } from '../_SimpleForm';

export default function SocialLinkForm({ mode, row, lookups = {}, urls = {}, t = {} }) {
    const isEdit = mode === 'edit';
    const form = useForm({
        id: isEdit ? row.id : undefined,
        name:     row.name     ?? '',
        icon:     row.icon     ?? '',
        link:     row.link     ?? '',
        position: row.position ?? '',
        status:   row.status   ?? '',
        _method:  isEdit ? 'put' : 'post',
    });
    const onSubmit = (e) => { e.preventDefault(); form.post(urls.submit, { preserveScroll: true }); };
    return (
        <SimpleForm
            title={t.title}
            breadcrumbs={[t.front_web, t.social_link, isEdit ? row.name : t.title]}
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
            <Field label={t.icon} error={form.errors.icon} hint="Font Awesome class, e.g. fab fa-facebook">
                <Input value={form.data.icon} onChange={(e) => form.setData('icon', e.target.value)} className="font-mono" />
            </Field>
            <Field label={t.link} required error={form.errors.link} className="md:col-span-2">
                <Input type="url" value={form.data.link} onChange={(e) => form.setData('link', e.target.value)} required placeholder="https://..." />
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
