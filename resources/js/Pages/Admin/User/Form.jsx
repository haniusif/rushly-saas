import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, AlertCircle, Users } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';

function Field({ label, required, error, children }) {
    return (
        <div className="space-y-1.5">
            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {label} {required && <span className="text-destructive">*</span>}
            </Label>
            {children}
            {error && <p className="text-xs text-destructive flex items-center gap-1"><AlertCircle className="h-3 w-3" /> {error}</p>}
        </div>
    );
}

export default function Form({ mode = 'create', entity = null, lookups = {}, flags = {}, urls = {}, t = {} }) {
    const isEdit = mode === 'edit';
    const [imgPreview, setImgPreview] = React.useState(null);

    const form = useForm({
        name: entity?.name ?? '',
        mobile: entity?.mobile ?? '',
        address: entity?.address ?? '',
        designation_id: entity?.designation_id ?? '',
        department_id: entity?.department_id ?? '',
        role_id: entity?.role_id ?? '',
        status: String(entity?.status ?? '1'),
        email: entity?.email ?? '',
        password: '',
        nid_number: entity?.nid_number ?? '',
        joining_date: entity?.joining_date ?? new Date().toISOString().slice(0, 10),
        hub_id: entity?.hub_id ?? '',
        salary: entity?.salary ?? '',
        image: null,
        ...(isEdit ? { id: entity?.id, _method: 'put' } : {}),
    });

    const onImage = (e) => {
        const f = e.target.files?.[0] || null;
        form.setData('image', f);
        if (imgPreview) URL.revokeObjectURL(imgPreview);
        setImgPreview(f ? URL.createObjectURL(f) : null);
    };
    React.useEffect(() => () => { if (imgPreview) URL.revokeObjectURL(imgPreview); }, [imgPreview]);

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { forceFormData: true, preserveScroll: true });
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.list_title, isEdit ? t.edit : t.title]}>
            <Head title={t.title} />
            <div className="mb-4">
                <a href={urls.cancel} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40"><ArrowLeft className="h-4 w-4 me-1" /> {t.back}</a>
            </div>
            <form onSubmit={onSubmit} encType="multipart/form-data">
                <Card>
                    <CardContent className="p-6">
                        <div className="mb-5 flex items-center gap-2"><Users className="h-5 w-5 text-primary" /><h2 className="text-lg font-semibold">{t.title}</h2></div>
                        <div className="grid gap-4 md:grid-cols-2">
                            <Field label={t.name} required error={form.errors.name}>
                                <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} placeholder={t.placeholder_name} />
                            </Field>
                            <Field label={t.email} required error={form.errors.email}>
                                <Input type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} placeholder={t.placeholder_email} />
                            </Field>
                            <Field label={t.phone} required error={form.errors.mobile}>
                                <Input type="number" value={form.data.mobile} onChange={(e) => form.setData('mobile', e.target.value)} placeholder={t.placeholder_mobile} inputMode="tel" />
                            </Field>
                            <Field label={t.password} required={!isEdit} error={form.errors.password}>
                                <Input type="password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} placeholder={t.placeholder_password} autoComplete="new-password" />
                            </Field>
                            <Field label={t.address} required error={form.errors.address}>
                                <Input value={form.data.address} onChange={(e) => form.setData('address', e.target.value)} placeholder={t.placeholder_address} />
                            </Field>
                            <Field label={t.nid} error={form.errors.nid_number}>
                                <Input type="number" value={form.data.nid_number} onChange={(e) => form.setData('nid_number', e.target.value)} placeholder={t.placeholder_nid} />
                            </Field>
                            <Field label={t.designation} required error={form.errors.designation_id}>
                                <Select value={form.data.designation_id} onChange={(e) => form.setData('designation_id', e.target.value)}>
                                    <option value="">—</option>
                                    {(lookups.designations || []).map((d) => <option key={d.value} value={d.value}>{d.label}</option>)}
                                </Select>
                            </Field>
                            <Field label={t.joining_date} required error={form.errors.joining_date}>
                                <Input type="date" value={form.data.joining_date} onChange={(e) => form.setData('joining_date', e.target.value)} />
                            </Field>
                            <Field label={t.department} required error={form.errors.department_id}>
                                <Select value={form.data.department_id} onChange={(e) => form.setData('department_id', e.target.value)}>
                                    <option value="">—</option>
                                    {(lookups.departments || []).map((d) => <option key={d.value} value={d.value}>{d.label}</option>)}
                                </Select>
                            </Field>
                            {!flags.is_super_admin && (
                                <Field label={t.hub} error={form.errors.hub_id}>
                                    <Select value={form.data.hub_id} onChange={(e) => form.setData('hub_id', e.target.value)}>
                                        <option value="">{t.none}</option>
                                        {(lookups.hubs || []).map((h) => <option key={h.value} value={h.value}>{h.label}</option>)}
                                    </Select>
                                </Field>
                            )}
                            <Field label={t.role} required error={form.errors.role_id}>
                                <Select value={form.data.role_id} onChange={(e) => form.setData('role_id', e.target.value)}>
                                    <option value="">—</option>
                                    {(lookups.roles || []).map((r) => <option key={r.value} value={r.value}>{r.label}</option>)}
                                </Select>
                            </Field>
                            <Field label={t.salary} error={form.errors.salary}>
                                <Input value={form.data.salary} onChange={(e) => form.setData('salary', e.target.value)} placeholder={t.placeholder_salary} />
                            </Field>
                            <Field label={t.status} error={form.errors.status}>
                                <Select value={form.data.status} onChange={(e) => form.setData('status', e.target.value)}>
                                    {(lookups.statuses || []).map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                                </Select>
                            </Field>
                            <Field label={t.image} error={form.errors.image}>
                                <div className="flex items-center gap-3">
                                    {(imgPreview || entity?.image) && (
                                        <img src={imgPreview || entity.image} alt="" className="h-12 w-12 rounded-full object-cover border border-border" />
                                    )}
                                    <Input type="file" accept="image/*" onChange={onImage} className="flex-1" />
                                </div>
                            </Field>
                        </div>
                        <div className="mt-6 flex items-center gap-2 border-t border-border pt-4">
                            <Button type="submit" disabled={form.processing}><Save className="h-4 w-4 me-1" /> {t.save}</Button>
                            <a href={urls.cancel} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">{t.cancel}</a>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </AdminLayout>
    );
}
