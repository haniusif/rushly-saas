import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, Store, User, Phone, MapPin } from 'lucide-react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';

function Field({ label, required, error, icon: Icon, children }) {
    return (
        <div className="space-y-1.5">
            <label className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {Icon && <Icon className="h-3 w-3" />}
                {label}
                {required && <span className="text-destructive">*</span>}
            </label>
            {children}
            {error && <p className="text-xs text-destructive">{error}</p>}
        </div>
    );
}

export default function Create({ urls = {}, t = {} }) {
    const form = useForm({
        name: '',
        contact_no: '',
        address: '',
        status: '1',
        lat: '',
        long: '',
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(urls.store, { preserveScroll: true });
    };

    return (
        <MerchantLayout title={t.title} breadcrumbs={[t.title_index, t.add]}>
            <Head title={`${t.add} · ${t.title}`} />

            <div className="mb-4 flex items-center gap-3">
                <a href={urls.cancel} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent no-underline">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.cancel}
                </a>
                <div className="flex items-center gap-2">
                    <Store className="h-5 w-5 text-primary" />
                    <h1 className="text-xl font-semibold m-0">{t.add} — {t.title}</h1>
                </div>
            </div>

            <form onSubmit={submit}>
                <div className="grid gap-5 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <Card>
                            <CardContent className="p-5 space-y-4">
                                <Field label={t.name} required icon={User} error={form.errors.name}>
                                    <Input
                                        value={form.data.name}
                                        onChange={(e) => form.setData('name', e.target.value)}
                                        placeholder={t.name_ph}
                                    />
                                </Field>

                                <Field label={t.contact} required icon={Phone} error={form.errors.contact_no}>
                                    <Input
                                        value={form.data.contact_no}
                                        onChange={(e) => form.setData('contact_no', e.target.value)}
                                        placeholder={t.contact_ph}
                                        inputMode="tel"
                                    />
                                </Field>

                                <Field label={t.address} required icon={MapPin} error={form.errors.address}>
                                    <Input
                                        value={form.data.address}
                                        onChange={(e) => form.setData('address', e.target.value)}
                                        placeholder={t.address_ph}
                                    />
                                </Field>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="lg:col-span-1">
                        <Card>
                            <CardContent className="p-5 space-y-4">
                                <Field label={t.status} required error={form.errors.status}>
                                    <Select
                                        value={form.data.status}
                                        onChange={(e) => form.setData('status', e.target.value)}
                                    >
                                        <option value="1">{t.active}</option>
                                        <option value="0">{t.inactive}</option>
                                    </Select>
                                </Field>

                                <div className="flex items-center gap-2 pt-2">
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
                    </div>
                </div>
            </form>
        </MerchantLayout>
    );
}
