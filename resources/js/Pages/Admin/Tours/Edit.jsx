import * as React from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { ArrowLeft, Save, Play } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import TourForm from './TourForm';

export default function Edit({ tour = {}, lookups = {}, urls = {}, t = {} }) {
    const form = useForm({
        key:           tour.key ?? '',
        module:        tour.module ?? '',
        title:         tour.title ?? '',
        description:   tour.description ?? '',
        role_scope:    tour.role_scope ?? [],
        version:       tour.version ?? 1,
        is_active:     !!tour.is_active,
        auto_start:    !!tour.auto_start,
        trigger_route: tour.trigger_route ?? '',
        steps:         Array.isArray(tour.steps) ? tour.steps : [],
        _method: 'put',
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(urls.update, { preserveScroll: true });
    };

    const openPreview = () => {
        window.open(urls.preview, '_blank');
    };

    return (
        <AdminLayout title={`${t.edit} · ${tour.title || ''}`} breadcrumbs={[t.title_index, t.edit]}>
            <Head title={`${t.edit} · ${tour.title || ''}`} />

            <form onSubmit={submit}>
                <div className="mb-4 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <a href={urls.cancel} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent no-underline">
                            <ArrowLeft className="h-4 w-4 me-1" /> {t.back}
                        </a>
                        <h1 className="text-xl font-semibold m-0">{tour.title}</h1>
                    </div>
                    <button type="submit" disabled={form.processing} className="inline-flex h-9 items-center gap-1.5 rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground hover:opacity-90 disabled:opacity-50">
                        <Save className="h-4 w-4" /> {form.processing ? '…' : t.save}
                    </button>
                </div>

                <TourForm form={form} lookups={lookups} urls={urls} t={t} mode="edit" onPreview={openPreview} />
            </form>
        </AdminLayout>
    );
}
