import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, Building2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import { SharedDataNotice, Field, ActiveToggle } from '@/Components/admin/ReferenceData';

export default function Form({ mode = 'create', entity = null, lookups = {}, urls = {}, t = {} }) {
    const isEdit = mode === 'edit';
    const form = useForm({
        country_id: entity?.country_id ?? '',
        name:       entity?.name ?? '',
        en_name:    entity?.en_name ?? '',
        city_code:  entity?.city_code ?? '',
        sorting:    entity?.sorting ?? 0,
        is_active:  entity?.is_active ?? true,
        ...(isEdit ? { _method: 'put' } : {}),
    });

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true });
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.list_title, isEdit ? t.edit : t.add]}>
            <Head title={t.title} />

            <div className="mb-4">
                <a href={urls.cancel} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium no-underline hover:bg-muted/40">
                    <ArrowLeft className="me-1 h-4 w-4" /> {t.back}
                </a>
            </div>

            <div className="max-w-2xl">
                <SharedDataNotice>{t.shared_notice}</SharedDataNotice>

                <form onSubmit={onSubmit}>
                    <Card>
                        <CardContent className="space-y-4 p-6">
                            <div className="mb-2 flex items-center gap-2">
                                <Building2 className="h-5 w-5 text-primary" />
                                <h2 className="m-0 text-lg font-semibold">{t.title}</h2>
                            </div>

                            <Field label={t.country} error={form.errors.country_id}>
                                <Select value={form.data.country_id ?? ''} onChange={(e) => form.setData('country_id', e.target.value)}>
                                    <option value="">{t.all}</option>
                                    {(lookups.countries || []).map((c) => (
                                        <option key={c.id} value={c.id}>{c.name}</option>
                                    ))}
                                </Select>
                            </Field>

                            <Field label={t.name} required error={form.errors.name}>
                                <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} autoFocus />
                            </Field>

                            <Field label={t.en_name} error={form.errors.en_name}>
                                <Input value={form.data.en_name} onChange={(e) => form.setData('en_name', e.target.value)} />
                            </Field>

                            <Field label={t.code} error={form.errors.city_code}>
                                <Input value={form.data.city_code} onChange={(e) => form.setData('city_code', e.target.value)} className="font-mono" />
                            </Field>

                            <Field label={t.position} error={form.errors.sorting} hint={t.sorting_hint}>
                                <Input type="number" min="0" value={form.data.sorting}
                                    onChange={(e) => form.setData('sorting', e.target.value)} />
                            </Field>

                            <Field label={t.status} error={form.errors.is_active}>
                                <ActiveToggle checked={form.data.is_active} onChange={(v) => form.setData('is_active', v)} label={t.active} />
                            </Field>

                            <div className="flex items-center gap-2 border-t border-border pt-4">
                                <Button type="submit" disabled={form.processing}>
                                    <Save className="me-1 h-4 w-4" /> {isEdit ? t.update : t.save}
                                </Button>
                                <a href={urls.cancel} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium no-underline hover:bg-muted/40">
                                    {t.cancel}
                                </a>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </AdminLayout>
    );
}
