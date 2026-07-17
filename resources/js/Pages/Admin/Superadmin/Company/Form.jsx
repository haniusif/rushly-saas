import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Save, AlertCircle, Building2, User as UserIcon, UploadCloud, X as XIcon } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';
import { cn } from '@/lib/utils';

function Field({ label, required, error, hint, children, className }) {
    return (
        <div className={cn('space-y-1.5', className)}>
            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {label} {required && <span className="text-destructive">*</span>}
            </Label>
            {children}
            {hint && <p className="text-[11px] text-muted-foreground">{hint}</p>}
            {error && (
                <p className="text-xs text-destructive flex items-center gap-1">
                    <AlertCircle className="h-3 w-3" /> {error}
                </p>
            )}
        </div>
    );
}

/**
 * Tenant create/edit form. On create, all fields are empty and password is
 * required; on edit, fields seed from the User + owned GeneralSettings +
 * tenant/domain, and password is optional (leaving blank keeps the current
 * hash). File inputs (logo, avatar) show the existing image on edit.
 */
export default function CompanyForm({ mode, user, lookups = {}, assets = {}, domain_suffix = '', urls = {}, t = {} }) {
    const isEdit = mode === 'edit';

    const form = useForm({
        id:                isEdit ? user.id : undefined,
        company_name:      user.company_name ?? '',
        domain:            user.domain ?? '',
        domain_id:         user.domain_id ?? '',
        currency:          user.currency ?? '',
        plan_id:           user.plan_id ?? '',
        par_track_prefix:  user.par_track_prefix ?? '',
        invoice_prefix:    user.invoice_prefix ?? '',
        logo:              null,
        name:              user.name ?? '',
        email:             user.email ?? '',
        mobile:            user.mobile ?? '',
        password:          '',
        address:           user.address ?? '',
        nid_number:        user.nid_number ?? '',
        designation_id:    user.designation_id ?? '',
        department_id:     user.department_id ?? '',
        joining_date:      user.joining_date ?? '',
        status:            user.status ?? '',
        image:             null,
        _method:           isEdit ? 'put' : 'post',
    });

    // Local object URLs so the picked-but-not-yet-uploaded logo / avatar
    // previews without needing a second render pass on the server.
    const [logoPreview,   setLogoPreview]   = React.useState(null);
    const [avatarPreview, setAvatarPreview] = React.useState(null);
    React.useEffect(() => () => { if (logoPreview) URL.revokeObjectURL(logoPreview); }, [logoPreview]);
    React.useEffect(() => () => { if (avatarPreview) URL.revokeObjectURL(avatarPreview); }, [avatarPreview]);

    const pickFile = (field, setPreview) => (e) => {
        const f = e.target.files?.[0] || null;
        form.setData(field, f);
        setPreview(f ? URL.createObjectURL(f) : null);
    };

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { forceFormData: true, preserveScroll: true });
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.breadcrumb, t.company_list, t.title]}>
            <Head title={t.title} />
            <form onSubmit={onSubmit} encType="multipart/form-data" className="space-y-4">

                {/* Company */}
                <Card>
                    <CardContent className="p-0">
                        <div className="flex items-center gap-3 px-6 py-5 border-b border-border">
                            <span className="shrink-0 grid h-9 w-9 place-items-center rounded-lg bg-primary/10 text-primary">
                                <Building2 className="h-4 w-4" />
                            </span>
                            <div>
                                <h2 className="text-base font-semibold m-0">{t.company_info}</h2>
                                <p className="text-xs text-muted-foreground mt-0.5">{t.company_hint}</p>
                            </div>
                        </div>
                        <div className="grid gap-5 md:grid-cols-2 p-6">
                            <Field label={t.company_name} required error={form.errors.company_name}>
                                <Input value={form.data.company_name} onChange={(e) => form.setData('company_name', e.target.value)} required />
                            </Field>
                            <Field label={t.domain} required error={form.errors.domain}>
                                <div className="flex items-stretch h-10 bg-background border border-input rounded-md overflow-hidden focus-within:ring-2 focus-within:ring-primary/20">
                                    <span className="inline-flex items-center px-3 text-xs bg-muted text-muted-foreground border-e border-input">https://</span>
                                    <input
                                        value={form.data.domain}
                                        onChange={(e) => form.setData('domain', e.target.value)}
                                        required
                                        autoComplete="off"
                                        className="flex-1 h-full px-3 text-sm bg-transparent outline-none"
                                    />
                                    <span className="inline-flex items-center px-3 text-xs bg-muted text-muted-foreground border-s border-input">{domain_suffix}</span>
                                </div>
                                {isEdit && <input type="hidden" name="domain_id" value={form.data.domain_id} />}
                            </Field>
                            <Field label={t.currency} required error={form.errors.currency}>
                                <Select value={form.data.currency} onChange={(e) => form.setData('currency', e.target.value)} required>
                                    <option value="" disabled>{t.select_currency}</option>
                                    {(lookups.currencies || []).map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
                                </Select>
                            </Field>
                            <Field label={t.plan} required error={form.errors.plan_id}>
                                <Select value={form.data.plan_id} onChange={(e) => form.setData('plan_id', e.target.value)} required>
                                    <option value="" disabled>—</option>
                                    {(lookups.plans || []).map((p) => <option key={p.value} value={p.value}>{p.label}</option>)}
                                </Select>
                            </Field>
                            <Field label={t.par_track_prefix} error={form.errors.par_track_prefix}>
                                <Input value={form.data.par_track_prefix} onChange={(e) => form.setData('par_track_prefix', e.target.value.toUpperCase())} className="uppercase font-mono" />
                            </Field>
                            <Field label={t.invoice_prefix} error={form.errors.invoice_prefix}>
                                <Input value={form.data.invoice_prefix} onChange={(e) => form.setData('invoice_prefix', e.target.value.toUpperCase())} className="uppercase font-mono" />
                            </Field>
                            <div className="md:col-span-2">
                                <Field label={t.logo} error={form.errors.logo}>
                                    <FileTile
                                        currentUrl={assets.logo_url}
                                        objectUrl={logoPreview}
                                        onPick={pickFile('logo', setLogoPreview)}
                                        onClear={() => { form.setData('logo', null); setLogoPreview(null); }}
                                    />
                                </Field>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Owner user */}
                <Card>
                    <CardContent className="p-0">
                        <div className="flex items-center gap-3 px-6 py-5 border-b border-border">
                            <span className="shrink-0 grid h-9 w-9 place-items-center rounded-lg bg-primary/10 text-primary">
                                <UserIcon className="h-4 w-4" />
                            </span>
                            <div>
                                <h2 className="text-base font-semibold m-0">{t.user_info}</h2>
                                <p className="text-xs text-muted-foreground mt-0.5">{t.user_hint}</p>
                            </div>
                        </div>
                        <div className="grid gap-5 md:grid-cols-2 p-6">
                            <Field label={t.name} required error={form.errors.name}>
                                <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required />
                            </Field>
                            <Field label={t.email} required error={form.errors.email}>
                                <Input type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} required />
                            </Field>
                            <Field label={t.phone} required error={form.errors.mobile}>
                                <Input inputMode="numeric" value={form.data.mobile} onChange={(e) => form.setData('mobile', e.target.value)} required />
                            </Field>
                            <Field
                                label={t.password}
                                required={!isEdit}
                                hint={isEdit ? t.password_edit_hint : undefined}
                                error={form.errors.password}
                            >
                                <Input
                                    type="password"
                                    value={form.data.password}
                                    onChange={(e) => form.setData('password', e.target.value)}
                                    required={!isEdit}
                                    autoComplete="new-password"
                                />
                            </Field>
                            <Field label={t.address} required error={form.errors.address} className="md:col-span-2">
                                <Input value={form.data.address} onChange={(e) => form.setData('address', e.target.value)} required />
                            </Field>
                            <Field label={t.nid} error={form.errors.nid_number}>
                                <Input inputMode="numeric" value={form.data.nid_number} onChange={(e) => form.setData('nid_number', e.target.value)} />
                            </Field>
                            <Field label={t.designation} required error={form.errors.designation_id}>
                                <Select value={form.data.designation_id} onChange={(e) => form.setData('designation_id', e.target.value)} required>
                                    <option value="" disabled>—</option>
                                    {(lookups.designations || []).map((d) => <option key={d.value} value={d.value}>{d.label}</option>)}
                                </Select>
                            </Field>
                            <Field label={t.department} required error={form.errors.department_id}>
                                <Select value={form.data.department_id} onChange={(e) => form.setData('department_id', e.target.value)} required>
                                    <option value="" disabled>—</option>
                                    {(lookups.departments || []).map((d) => <option key={d.value} value={d.value}>{d.label}</option>)}
                                </Select>
                            </Field>
                            <Field label={t.opening_date} required error={form.errors.joining_date}>
                                <Input type="date" value={form.data.joining_date} onChange={(e) => form.setData('joining_date', e.target.value)} required />
                            </Field>
                            <Field label={t.status} error={form.errors.status}>
                                <Select value={form.data.status} onChange={(e) => form.setData('status', e.target.value)}>
                                    {(lookups.statuses || []).map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                                </Select>
                            </Field>
                            <div className="md:col-span-2">
                                <Field label={t.image} error={form.errors.image}>
                                    <FileTile
                                        currentUrl={assets.avatar_url}
                                        objectUrl={avatarPreview}
                                        onPick={pickFile('image', setAvatarPreview)}
                                        onClear={() => { form.setData('image', null); setAvatarPreview(null); }}
                                        circle
                                    />
                                </Field>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Footer */}
                <div className="flex items-center justify-end gap-2 bg-background border border-border rounded-xl px-6 py-4">
                    <Button variant="outline" asChild>
                        <a href={urls.index}>{t.cancel}</a>
                    </Button>
                    <Button type="submit" disabled={form.processing}>
                        <Save className="h-4 w-4 me-1" />
                        {t.save}
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}

/**
 * Compact file picker. Shows the existing image URL (from `currentUrl`) or
 * the just-picked File preview (`objectUrl`) with an X to clear the newly
 * picked file. Doesn't try to delete the server-side existing image; use
 * the parent form's clear semantics if needed.
 */
function FileTile({ currentUrl, objectUrl, onPick, onClear, circle = false }) {
    const inputRef = React.useRef(null);
    const shown = objectUrl || currentUrl;
    return (
        <div className={cn(
            'flex items-center gap-3 rounded-md border border-dashed border-border p-2',
            objectUrl && 'border-primary/50'
        )}>
            <button
                type="button"
                onClick={() => inputRef.current?.click()}
                className={cn(
                    'shrink-0 grid place-items-center h-14 w-14 bg-muted text-muted-foreground overflow-hidden',
                    circle ? 'rounded-full' : 'rounded-md'
                )}
            >
                {shown ? (
                    <img src={shown} alt="" className="h-full w-full object-cover" />
                ) : (
                    <UploadCloud className="h-5 w-5" />
                )}
            </button>
            <div className="flex-1 min-w-0">
                <button
                    type="button"
                    onClick={() => inputRef.current?.click()}
                    className="text-sm font-medium underline decoration-dotted underline-offset-4 hover:text-primary"
                >
                    {objectUrl ? 'Change file' : (currentUrl ? 'Replace' : 'Choose file')}
                </button>
                <div className="text-[11px] text-muted-foreground truncate">
                    {objectUrl ? 'Ready to upload on save.' : (currentUrl ? 'Current image shown.' : 'No file selected.')}
                </div>
            </div>
            {objectUrl && (
                <button type="button" onClick={onClear} className="p-1 rounded hover:bg-muted text-muted-foreground">
                    <XIcon className="h-4 w-4" />
                </button>
            )}
            <input ref={inputRef} type="file" accept="image/*" className="hidden" onChange={onPick} />
        </div>
    );
}
