import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
    Save, ArrowLeft, AlertCircle, User as UserIcon, Building2, MapPin,
    Phone, Mail, Lock, Image as ImageIcon, FileText, Percent, Wallet,
    Calendar, RefreshCcw, Upload,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';
import { Textarea } from '@/Components/ui/Textarea';
import { cn } from '@/lib/utils';

function Field({ icon: Icon, label, required, error, hint, children, className }) {
    return (
        <div className={cn('space-y-1.5', className)}>
            <Label className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {Icon && <Icon className="h-3 w-3" />}
                {label}
                {required && <span className="text-destructive">*</span>}
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

function FileTile({ label, value, onChange, accept = 'image/*,application/pdf', required, error, hint, t = {} }) {
    const [preview, setPreview] = React.useState(null);
    const handle = (e) => {
        const file = e.target.files?.[0] || null;
        onChange(file);
        if (file && file.type?.startsWith?.('image/')) {
            setPreview(URL.createObjectURL(file));
        } else {
            setPreview(null);
        }
    };
    return (
        <Field icon={FileText} label={label} required={required} error={error} hint={hint}>
            <label className="flex cursor-pointer items-center gap-3 rounded-md border border-dashed border-input bg-background/60 px-3 py-2.5 text-sm hover:bg-accent/40 transition-colors">
                {preview
                    ? <img src={preview} alt="" className="h-12 w-12 rounded object-cover" />
                    : <span className="grid h-12 w-12 place-items-center rounded bg-muted text-muted-foreground"><Upload className="h-4 w-4" /></span>}
                <span className="flex-1 truncate text-muted-foreground">
                    {value ? value.name || (t.replace_file || 'Replace…') : (t.choose_file || 'Choose file…')}
                </span>
                <input type="file" accept={accept} onChange={handle} className="hidden" />
            </label>
        </Field>
    );
}

export default function Create({ lookups = {}, urls = {}, t = {}, mode = 'create', merchant = null }) {
    const isEdit = mode === 'edit';
    const codAreas = lookups.cod_areas || [];
    const initialCharges = Object.fromEntries(
        codAreas.map((a) => [a.key, Number(merchant?.cod_charges?.[a.key] ?? 0)]),
    );

    const form = useForm({
        // Account
        name:     merchant?.name     ?? '',
        mobile:   merchant?.mobile   ?? '',
        email:    merchant?.email    ?? '',
        password: '',
        // Business
        business_name: merchant?.business_name ?? '',
        address:       merchant?.address       ?? '',
        hub:           merchant?.hub != null ? String(merchant.hub) : '',
        status:        merchant?.status != null ? String(merchant.status) : 1,
        // Money
        opening_balance: merchant?.opening_balance ?? 0,
        vat:             merchant?.vat ?? 0,
        payment_period:  merchant?.payment_period ?? 2,
        return_charges:  merchant?.return_charges ?? 100,
        wallet_use_activation: merchant?.wallet_use_activation != null ? String(merchant.wallet_use_activation) : 0,
        reference_name:  merchant?.reference_name ?? '',
        reference_phone: merchant?.reference_phone ?? '',
        // COD charges
        area:   codAreas.map((a) => a.key),
        charge: initialCharges,
        // Services
        services: merchant?.services ?? [],
        // Geography
        country_ids:       (merchant?.country_ids ?? []).map((id) => Number(id)),
        city_ids:          (merchant?.city_ids ?? []).map((id) => Number(id)),
        covers_all_cities: !!(merchant?.covers_all_cities),
        // Docs (only sent if user picks a new file)
        image_id: null, nid: null, trade_license: null,
        ...(isEdit ? { id: merchant?.id, _method: 'put' } : {}),
    });

    const toggleService = (s) => {
        const has = form.data.services.includes(s);
        form.setData('services', has ? form.data.services.filter((x) => x !== s) : [...form.data.services, s]);
    };

    const toggleCountry = (id) => {
        const n = Number(id);
        const has = form.data.country_ids.includes(n);
        const next = has ? form.data.country_ids.filter((x) => x !== n) : [...form.data.country_ids, n];
        form.setData('country_ids', next);
        // Drop any selected cities that no longer belong to a selected country.
        const cityCountryIds = new Set(
            (lookups.cities || []).filter((c) => next.includes(Number(c.country_id))).map((c) => c.id),
        );
        form.setData('city_ids', form.data.city_ids.filter((id) => cityCountryIds.has(id)));
    };

    const toggleCity = (id) => {
        const n = Number(id);
        const has = form.data.city_ids.includes(n);
        form.setData('city_ids', has ? form.data.city_ids.filter((x) => x !== n) : [...form.data.city_ids, n]);
    };

    const submit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { forceFormData: true, preserveScroll: true });
    };

    // Cities filtered by selected countries (only relevant when not "covers all").
    const visibleCities = React.useMemo(() => {
        const sel = new Set(form.data.country_ids.map(Number));
        return (lookups.cities || []).filter((c) => sel.has(Number(c.country_id)));
    }, [form.data.country_ids, lookups.cities]);

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title_index, t.title]}>
            <Head title={t.title} />

            <form onSubmit={submit} encType="multipart/form-data" className="space-y-5">
                <div className="grid gap-5 lg:grid-cols-3">
                    {/* Left: account + business + cod + extras */}
                    <div className="lg:col-span-2 space-y-5">
                        <Card>
                            <CardContent className="pt-6">
                                <div className="mb-4 text-sm font-semibold tracking-tight">{t.account}</div>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field icon={UserIcon} label={t.name} required error={form.errors.name}>
                                        <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} autoComplete="name" />
                                    </Field>
                                    <Field icon={Phone} label={t.mobile} required error={form.errors.mobile}>
                                        <Input value={form.data.mobile} onChange={(e) => form.setData('mobile', e.target.value)} inputMode="tel" autoComplete="tel" />
                                    </Field>
                                    <Field icon={Mail} label={`${t.email} (${t.optional})`} error={form.errors.email}>
                                        <Input type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} autoComplete="email" />
                                    </Field>
                                    <Field icon={Lock} label={t.password} required={!isEdit} error={form.errors.password} hint={isEdit ? t.password_keep_hint : undefined}>
                                        <Input type="password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} minLength={6} autoComplete="new-password" />
                                    </Field>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="pt-6">
                                <div className="mb-4 text-sm font-semibold tracking-tight">{t.business}</div>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field icon={Building2} label={t.business_name} required error={form.errors.business_name} className="md:col-span-2">
                                        <Input value={form.data.business_name} onChange={(e) => form.setData('business_name', e.target.value)} autoComplete="organization" />
                                    </Field>
                                    <Field icon={MapPin} label={t.address} required error={form.errors.address} className="md:col-span-2">
                                        <Textarea rows={2} value={form.data.address} onChange={(e) => form.setData('address', e.target.value)} />
                                    </Field>
                                    <Field icon={Building2} label={t.hub} required error={form.errors.hub}>
                                        <Select value={form.data.hub} onChange={(e) => form.setData('hub', e.target.value)}>
                                            <option value="">—</option>
                                            {(lookups.hubs || []).map((h) => <option key={h.id} value={h.id}>{h.name}</option>)}
                                        </Select>
                                    </Field>
                                    <Field label={t.status} required error={form.errors.status}>
                                        <Select value={form.data.status} onChange={(e) => form.setData('status', e.target.value)}>
                                            {(lookups.statuses || []).map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                                        </Select>
                                    </Field>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="pt-6">
                                <div className="mb-4 text-sm font-semibold tracking-tight">{t.cod_charges}</div>
                                <div className="grid gap-4 sm:grid-cols-3">
                                    {codAreas.map((a) => (
                                        <Field key={a.key} icon={Percent} label={a.label} error={form.errors[`charge.${a.key}`]}>
                                            <Input
                                                type="number" step="0.01" min="0" max="100"
                                                value={form.data.charge[a.key] ?? 0}
                                                onChange={(e) => form.setData('charge', { ...form.data.charge, [a.key]: e.target.value })}
                                            />
                                        </Field>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Geography coverage — required for edit, optional helper for create */}
                        <Card>
                            <CardContent className="pt-6">
                                <div className="mb-4 text-sm font-semibold tracking-tight">{t.geography || 'Geography coverage'}</div>
                                <Field icon={MapPin} label={t.countries || 'Countries'} required error={form.errors.country_ids || form.errors['country_ids.0']}>
                                    <div className="flex flex-wrap gap-2">
                                        {(lookups.countries || []).map((c) => {
                                            const on = form.data.country_ids.includes(Number(c.id));
                                            return (
                                                <button
                                                    key={c.id} type="button" onClick={() => toggleCountry(c.id)}
                                                    className={cn(
                                                        'rounded-full border px-3 py-1.5 text-xs font-medium transition-colors',
                                                        on ? 'bg-primary text-primary-foreground border-primary'
                                                           : 'bg-muted text-muted-foreground border-border hover:bg-accent',
                                                    )}
                                                >
                                                    {c.code ? <span className="me-1 opacity-70 font-mono">{c.code}</span> : null}
                                                    {c.name}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </Field>

                                <label
                                    onClick={() => form.setData('covers_all_cities', !form.data.covers_all_cities)}
                                    className={cn(
                                        'mt-4 flex cursor-pointer items-start gap-3 rounded-md border p-3 transition-colors',
                                        form.data.covers_all_cities ? 'border-primary bg-primary/5' : 'border-input bg-card hover:bg-muted/40',
                                    )}
                                >
                                    <input type="checkbox" checked={form.data.covers_all_cities} onChange={() => {}} className="mt-0.5 h-4 w-4 rounded border-input" />
                                    <div className="min-w-0">
                                        <div className="text-sm font-medium">{t.covers_all_cities || 'Covers all cities in selected countries'}</div>
                                    </div>
                                </label>

                                {!form.data.covers_all_cities && (
                                    <div className="mt-4">
                                        <Field label={t.cities || 'Cities'} error={form.errors.city_ids}>
                                            {visibleCities.length === 0 ? (
                                                <p className="text-xs text-muted-foreground">{t.select_at_least_country}</p>
                                            ) : (
                                                <div className="flex flex-wrap gap-1.5 max-h-48 overflow-y-auto p-1.5 border border-input rounded-md">
                                                    {visibleCities.map((c) => {
                                                        const on = form.data.city_ids.includes(Number(c.id));
                                                        return (
                                                            <button
                                                                key={c.id} type="button" onClick={() => toggleCity(c.id)}
                                                                className={cn(
                                                                    'rounded-md border px-2 py-1 text-[11px] font-medium transition-colors',
                                                                    on ? 'bg-primary text-primary-foreground border-primary'
                                                                       : 'bg-muted text-muted-foreground border-border hover:bg-accent',
                                                                )}
                                                            >
                                                                {c.name}
                                                            </button>
                                                        );
                                                    })}
                                                </div>
                                            )}
                                        </Field>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="pt-6">
                                <div className="mb-4 text-sm font-semibold tracking-tight">{t.extras}</div>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field icon={Wallet} label={t.opening_balance} error={form.errors.opening_balance}>
                                        <Input type="number" step="0.01" min="0" value={form.data.opening_balance} onChange={(e) => form.setData('opening_balance', e.target.value)} />
                                    </Field>
                                    <Field icon={Percent} label={t.vat} error={form.errors.vat}>
                                        <Input type="number" step="0.01" min="0" max="100" value={form.data.vat} onChange={(e) => form.setData('vat', e.target.value)} />
                                    </Field>
                                    <Field icon={Calendar} label={t.payment_period} error={form.errors.payment_period}>
                                        <Input type="number" min="0" value={form.data.payment_period} onChange={(e) => form.setData('payment_period', e.target.value)} />
                                    </Field>
                                    <Field icon={RefreshCcw} label={t.return_charges} error={form.errors.return_charges}>
                                        <Input type="number" min="0" max="100" value={form.data.return_charges} onChange={(e) => form.setData('return_charges', e.target.value)} />
                                    </Field>
                                    <Field label={t.wallet_use}>
                                        <Select value={form.data.wallet_use_activation} onChange={(e) => form.setData('wallet_use_activation', e.target.value)}>
                                            <option value="0">{t.wallet_off || 'Off'}</option>
                                            <option value="1">{t.wallet_on || 'On'}</option>
                                        </Select>
                                    </Field>
                                    <Field label={t.reference_name}>
                                        <Input value={form.data.reference_name} onChange={(e) => form.setData('reference_name', e.target.value)} />
                                    </Field>
                                    <Field label={t.reference_phone}>
                                        <Input value={form.data.reference_phone} onChange={(e) => form.setData('reference_phone', e.target.value)} inputMode="tel" />
                                    </Field>
                                </div>

                                <div className="mt-5">
                                    <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground block mb-2">{t.services}</Label>
                                    <div className="flex flex-wrap gap-2">
                                        {(lookups.services || []).map((s) => {
                                            const on = form.data.services.includes(s);
                                            return (
                                                <button
                                                    key={s} type="button" onClick={() => toggleService(s)}
                                                    className={cn(
                                                        'rounded-full border px-3 py-1.5 text-xs font-medium capitalize transition-colors',
                                                        on ? 'bg-primary text-primary-foreground border-primary'
                                                           : 'bg-muted text-muted-foreground border-border hover:bg-accent',
                                                    )}
                                                >
                                                    {(t.service_labels && t.service_labels[s]) || s.replace('_', ' ')}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right: documents + submit */}
                    <div className="lg:col-span-1 space-y-5">
                        <Card>
                            <CardContent className="pt-6 space-y-4">
                                <div className="text-sm font-semibold tracking-tight">{t.documents}</div>
                                <FileTile
                                    label={`${t.avatar} (${t.optional})`}
                                    value={form.data.image_id}
                                    onChange={(f) => form.setData('image_id', f)}
                                    accept="image/*"
                                    error={form.errors.image_id}
                                    t={t}
                                />
                                <FileTile
                                    label={isEdit ? `${t.nid} (${t.optional})` : t.nid}
                                    value={form.data.nid}
                                    onChange={(f) => form.setData('nid', f)}
                                    required={!isEdit}
                                    error={form.errors.nid}
                                    hint={isEdit ? (t.file_hint_replace || 'Upload to replace existing') : (t.file_hint_types || 'JPEG / PNG / PDF')}
                                    t={t}
                                />
                                <FileTile
                                    label={isEdit ? `${t.trade} (${t.optional})` : t.trade}
                                    value={form.data.trade_license}
                                    onChange={(f) => form.setData('trade_license', f)}
                                    required={!isEdit}
                                    error={form.errors.trade_license}
                                    hint={isEdit ? (t.file_hint_replace || 'Upload to replace existing') : (t.file_hint_types || 'JPEG / PNG / PDF')}
                                    t={t}
                                />
                            </CardContent>
                        </Card>

                        <Card className="lg:sticky lg:top-20">
                            <CardContent className="pt-6 space-y-3">
                                <div className="text-sm font-semibold">{t.title}</div>
                                <p className="text-[11px] text-muted-foreground leading-relaxed">
                                    {t.footer_caption || 'Owner account, business profile, and documents are required. Brand theme + per-area delivery charges can be set after save from the merchant page.'}
                                </p>
                                <div className="flex flex-col gap-2 pt-2">
                                    <Button type="submit" disabled={form.processing}>
                                        <Save className="h-4 w-4 me-1" /> {form.processing ? '…' : t.save}
                                    </Button>
                                    <a href={urls.cancel} className="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent">
                                        <ArrowLeft className="h-4 w-4 me-1" /> {t.cancel}
                                    </a>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}
