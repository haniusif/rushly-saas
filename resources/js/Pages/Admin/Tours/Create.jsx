import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import TourForm from './TourForm';

export default function Create({ lookups = {}, urls = {}, t = {} }) {
    const form = useForm({
        key: '', module: '', title: '', description: '',
        role_scope: [], version: 1, is_active: true, auto_start: false, trigger_route: '',
        steps: [{
            target: { type: 'data-tour', value: '' },
            placement: 'auto', spotlight_padding: 8,
            translations: { en: { title: '', body: '' }, ar: { title: '', body: '' } },
        }],
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(urls.store, { preserveScroll: true });
    };

    return (
        <AdminLayout title={t.add} breadcrumbs={[t.title_index, t.add]}>
            <Head title={t.add} />

            <form onSubmit={submit}>
                <div className="mb-4 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <a href={urls.cancel} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent no-underline">
                            <ArrowLeft className="h-4 w-4 me-1" /> {t.cancel}
                        </a>
                        <h1 className="text-xl font-semibold m-0">{t.add}</h1>
                    </div>
                    <button type="submit" disabled={form.processing} className="inline-flex h-9 items-center gap-1.5 rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground hover:opacity-90 disabled:opacity-50">
                        <Save className="h-4 w-4" /> {form.processing ? '…' : t.save}
                    </button>
                </div>

                <TourForm form={form} lookups={lookups} urls={urls} t={t} mode="create" />
            </form>
        </AdminLayout>
    );
}
