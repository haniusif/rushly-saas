import * as React from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import {
    ChevronLeft, ChevronRight, Check, AlertCircle, Upload, User as UserIcon,
    Badge, Home, Briefcase, FileText, Banknote, Files,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';
import { cn } from '@/lib/utils';

function safeRoute(name, params) {
    try { return window.route(name, params); } catch (_) { return '#'; }
}

const STEPS = [
    { id: 1, key: 'section_basic',      icon: UserIcon },
    { id: 2, key: 'section_id',         icon: Badge },
    { id: 3, key: 'section_address',    icon: Home },
    { id: 4, key: 'section_employment', icon: Briefcase },
    { id: 5, key: 'section_license',    icon: FileText },
    { id: 6, key: 'section_bank',       icon: Banknote, showFor: ['freelancer'] },
    { id: 7, key: 'section_documents',  icon: Files },
];

function Field({ label, error, required, hint, children, className }) {
    return (
        <div className={cn('space-y-1.5', className)}>
            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {label} {required && <span className="text-destructive ms-0.5">*</span>}
            </Label>
            {children}
            {hint && <p className="text-[11px] text-muted-foreground">{hint}</p>}
            {error && (
                <p className="text-xs text-destructive flex items-center gap-1">
                    <AlertCircle className="h-3 w-3 shrink-0" /> {error}
                </p>
            )}
        </div>
    );
}

function SectionHeader({ num, title }) {
    return (
        <div className="mb-5 flex items-center gap-3">
            <span className="grid h-8 w-8 place-items-center rounded-full bg-primary/10 text-primary text-sm font-semibold">
                {num}
            </span>
            <h2 className="text-base font-semibold tracking-tight">{title}</h2>
        </div>
    );
}

function FileInput({ name, label, accept = 'image/*', onChange, helper, error }) {
    const [preview, setPreview] = React.useState(null);
    const handle = (e) => {
        const file = e.target.files?.[0] || null;
        onChange(file);
        if (file && file.type.startsWith('image/')) {
            const url = URL.createObjectURL(file);
            setPreview(url);
        } else {
            setPreview(null);
        }
    };
    return (
        <div className="space-y-1.5">
            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{label}</Label>
            <label className="flex cursor-pointer items-center gap-3 rounded-md border border-dashed border-input bg-background/60 px-3 py-2.5 text-sm hover:bg-accent/40 transition-colors">
                {preview ? (
                    <img src={preview} alt="" className="h-10 w-10 rounded object-cover" />
                ) : (
                    <span className="grid h-10 w-10 place-items-center rounded bg-muted text-muted-foreground">
                        <Upload className="h-4 w-4" />
                    </span>
                )}
                <span className="flex-1 truncate text-muted-foreground">
                    {preview ? 'Replace…' : (helper || 'JPEG / PNG, max 5 MB')}
                </span>
                <input
                    type="file"
                    name={name}
                    accept={accept}
                    onChange={handle}
                    className="hidden"
                />
            </label>
            {error && (
                <p className="text-xs text-destructive flex items-center gap-1">
                    <AlertCircle className="h-3 w-3 shrink-0" /> {error}
                </p>
            )}
        </div>
    );
}

function Stepper({ steps, current, visited, errors, onJump }) {
    return (
        <div className="mb-6 rounded-xl border border-border bg-card p-4 shadow-sm">
            <div className="mb-3 h-1.5 overflow-hidden rounded-full bg-muted">
                <div
                    className="h-full rounded-full bg-gradient-to-r from-primary to-primary/60 transition-all duration-300"
                    style={{ width: `${(steps.findIndex((s) => s.id === current) + 1) / steps.length * 100}%` }}
                />
            </div>
            <div className="flex flex-wrap gap-1.5">
                {steps.map((s, idx) => {
                    const isActive = s.id === current;
                    const isDone = visited.includes(s.id) && !isActive;
                    const hasError = errors.includes(s.id);
                    const Icon = s.icon;
                    return (
                        <button
                            key={s.id}
                            type="button"
                            onClick={() => onJump(s.id)}
                            className={cn(
                                'inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-medium transition-all',
                                isActive && 'border-transparent bg-primary text-primary-foreground shadow-md scale-[1.02]',
                                isDone && !isActive && 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100',
                                !isActive && !isDone && !hasError && 'border-border bg-muted/40 text-muted-foreground hover:bg-muted',
                                hasError && 'border-destructive bg-destructive/10 text-destructive',
                            )}
                        >
                            <span className={cn(
                                'grid h-5 w-5 place-items-center rounded-full text-[10px] font-bold',
                                isActive && 'bg-white/20',
                                isDone && 'bg-emerald-200',
                                !isActive && !isDone && !hasError && 'bg-muted-foreground/15',
                                hasError && 'bg-destructive/20',
                            )}>
                                {isDone ? <Check className="h-3 w-3" /> : (idx + 1)}
                            </span>
                            <Icon className="h-3.5 w-3.5" />
                            <span className="hidden sm:inline">{s.label}</span>
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

function Summary({ t, data, hubs, statusLabel, typeLabel }) {
    const initials = (data.name || '?').trim().split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
    const hub = hubs.find((h) => String(h.id) === String(data.hub_id));
    const contractWarn = (() => {
        if (!data.contract_end_date) return false;
        const d = new Date(data.contract_end_date + 'T00:00:00');
        if (isNaN(d)) return false;
        const days = Math.round((d - new Date()) / 86400000);
        return days >= 0 && days <= 30;
    })();
    return (
        <Card className="sticky top-20">
            <CardContent className="pt-6">
                <div className="mb-4 flex flex-col items-center text-center">
                    <div className="grid h-16 w-16 place-items-center rounded-full bg-primary/10 text-primary text-xl font-semibold">
                        {initials}
                    </div>
                    <div className="mt-3 font-semibold">{data.name || '—'}</div>
                    <div className="text-xs text-muted-foreground">{t.create_deliveryman}</div>
                </div>
                <Row label={t.mobile}      value={data.mobile} />
                <Row label={t.email}       value={data.email} />
                <Row label={t.nationality} value={data.nationality} />
                <hr className="my-3 border-border" />
                <Row label={t.driver_type} value={typeLabel} badge="bg-blue-100 text-blue-700" />
                <Row label={t.hub}         value={hub?.name} />
                <Row label={t.status}      value={statusLabel} badge={
                    String(data.status) === '1' ? 'bg-emerald-100 text-emerald-700'
                    : String(data.status) === '2' ? 'bg-amber-100 text-amber-700'
                    : String(data.status) === '3' ? 'bg-sky-100 text-sky-700'
                    : 'bg-rose-100 text-rose-700'
                } />
                <Row label={t.joining_date} value={data.joining_date} />
                {contractWarn && (
                    <div className="mt-3 rounded-md border border-amber-200 bg-amber-50 p-2 text-xs text-amber-800 flex gap-1.5 items-start">
                        <AlertCircle className="h-3.5 w-3.5 mt-0.5 shrink-0" /> {t.contract_expiry_hint}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function Row({ label, value, badge }) {
    return (
        <div className="flex items-center justify-between py-1.5 text-sm">
            <span className="text-xs uppercase tracking-wide text-muted-foreground">{label}</span>
            {badge && value
                ? <span className={cn('rounded-full px-2 py-0.5 text-[11px] font-medium', badge)}>{value}</span>
                : <span className="font-medium text-foreground truncate max-w-[55%] text-end">{value || '—'}</span>}
        </div>
    );
}

export default function Create({
    t,
    hubs = [],
    supplierCompanies = [],
    operationalAreas = [],
    managers = [],
    nationalities = [],
    mode = 'create',
    deliveryman = null,
}) {
    const { url } = usePage();
    const isEdit = mode === 'edit';
    const dm = deliveryman || {};
    const form = useForm({
        name:                    dm.name        ?? '',
        name_en:                 dm.name_en     ?? '',
        mobile:                  dm.mobile      ?? '',
        alt_mobile:              dm.alt_mobile  ?? '',
        email:                   dm.email       ?? '',
        password:                '', // Always blank; backend keeps current if blank
        gender:                  dm.gender      ?? '',
        dob:                     dm.dob         ?? '',
        nationality:             dm.nationality ?? '',
        id_type:                 dm.id_type     ?? '',
        id_number:               dm.id_number   ?? '',
        id_expiry:               dm.id_expiry   ?? '',
        id_image_id:             null,
        address:                 dm.address     ?? '',
        district:                dm.district    ?? '',
        short_national_address:  dm.short_national_address ?? '',
        driver_type:             dm.driver_type ?? 'company_courier',
        employee_number:         dm.employee_number ?? '',
        supplier_company_id:     dm.supplier_company_id ? String(dm.supplier_company_id) : '',
        joining_date:            dm.joining_date ?? '',
        contract_end_date:       dm.contract_end_date ?? '',
        status:                  dm.status != null ? String(dm.status) : '1',
        hub_id:                  dm.hub_id ? String(dm.hub_id) : '',
        direct_manager_id:       dm.direct_manager_id ? String(dm.direct_manager_id) : '',
        operational_area_id:     dm.operational_area_id ? String(dm.operational_area_id) : '',
        salary:                  dm.salary          ?? '',
        delivery_charge:         dm.delivery_charge ?? '',
        pickup_charge:           dm.pickup_charge   ?? '',
        return_charge:           dm.return_charge   ?? '',
        opening_balance:         dm.opening_balance ?? '',
        license_number:          dm.license_number  ?? '',
        license_expiry:          dm.license_expiry  ?? '',
        iqama_expiry:            dm.iqama_expiry    ?? '',
        bank_account_no:         dm.bank_account_no ?? '',
        iban:                    dm.iban            ?? '',
        image_id:                null,
        driving_license_image_id: null,
        iqama_image_id:          null,
        contract_image_id:       null,
        promissory_note_image_id: null,
        ...(isEdit ? { id: dm.id, _method: 'put' } : {}),
    });

    const [step, setStep] = React.useState(1);
    const [visited, setVisited] = React.useState([]);

    const visibleSteps = STEPS
        .filter((s) => !s.showFor || s.showFor.includes(form.data.driver_type))
        .map((s) => ({ ...s, label: t[s.key] }));

    React.useEffect(() => {
        if (!visibleSteps.find((s) => s.id === step)) {
            const prev = visibleSteps.filter((s) => s.id < step).pop();
            setStep(prev ? prev.id : visibleSteps[0]?.id || 1);
        }
    }, [form.data.driver_type]); // eslint-disable-line react-hooks/exhaustive-deps

    const stepsWithErrors = React.useMemo(() => {
        const out = [];
        const map = {
            1: ['name', 'name_en', 'mobile', 'alt_mobile', 'email', 'password', 'gender', 'dob', 'nationality'],
            2: ['id_type', 'id_number', 'id_expiry', 'id_image_id'],
            3: ['address', 'district', 'short_national_address'],
            4: ['driver_type', 'employee_number', 'supplier_company_id', 'joining_date', 'contract_end_date',
                'status', 'hub_id', 'direct_manager_id', 'operational_area_id',
                'salary', 'delivery_charge', 'pickup_charge', 'return_charge', 'opening_balance'],
            5: ['license_number', 'license_expiry', 'iqama_expiry'],
            6: ['bank_account_no', 'iban'],
            7: ['image_id', 'driving_license_image_id', 'iqama_image_id', 'contract_image_id', 'promissory_note_image_id'],
        };
        Object.entries(map).forEach(([id, fields]) => {
            if (fields.some((f) => form.errors[f])) out.push(Number(id));
        });
        return out;
    }, [form.errors]);

    const validateStep = (id) => {
        const required = {
            1: ['name', 'mobile', 'email', ...(isEdit ? [] : ['password'])],
            3: ['address'],
            4: ['driver_type', 'status', 'hub_id', ...(form.data.driver_type === 'company_courier' ? ['employee_number'] : []),
                ...(form.data.driver_type === 'outsourced' ? ['supplier_company_id'] : [])],
        }[id] || [];
        const missing = required.filter((f) => !String(form.data[f] ?? '').trim());
        return { ok: missing.length === 0, missing };
    };

    const goTo = (target) => {
        if (target <= step) { setStep(target); window.scrollTo({ top: 0, behavior: 'smooth' }); return; }
        const from = visibleSteps.findIndex((s) => s.id === step);
        const to   = visibleSteps.findIndex((s) => s.id === target);
        for (let i = from; i < to; i++) {
            const sid = visibleSteps[i].id;
            const res = validateStep(sid);
            if (!res.ok) {
                setStep(sid);
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            setVisited((v) => v.includes(sid) ? v : [...v, sid]);
        }
        setStep(target);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const next = () => {
        const idx = visibleSteps.findIndex((s) => s.id === step);
        if (idx < visibleSteps.length - 1) goTo(visibleSteps[idx + 1].id);
    };
    const prev = () => {
        const idx = visibleSteps.findIndex((s) => s.id === step);
        if (idx > 0) goTo(visibleSteps[idx - 1].id);
    };

    const submit = (e) => {
        e.preventDefault();
        for (const s of visibleSteps) {
            const res = validateStep(s.id);
            if (!res.ok) { setStep(s.id); window.scrollTo({ top: 0, behavior: 'smooth' }); return; }
        }
        // Both endpoints accept multipart; edit goes through _method=put spoof.
        const target = isEdit ? safeRoute('deliveryman.update') : safeRoute('deliveryman.store');
        form.post(target, { forceFormData: true });
    };

    const isAr = url?.startsWith?.('/ar') || (typeof document !== 'undefined' && document.documentElement.dir === 'rtl');

    const typeLabels = {
        freelancer:      t.driver_type_freelancer,
        outsourced:      t.driver_type_outsourced,
        company_courier: t.driver_type_company_courier,
    };
    const statusLabels = {
        '1': t.status_active, '2': t.status_suspended, '3': t.status_leave, '4': t.status_terminated,
    };

    const isLast = visibleSteps.findIndex((s) => s.id === step) === visibleSteps.length - 1;
    const positionLabel = (t.wizard_step_of || 'Step :current of :total')
        .replace(':current', visibleSteps.findIndex((s) => s.id === step) + 1)
        .replace(':total', visibleSteps.length);

    return (
        <AdminLayout title={isEdit ? t.edit_deliveryman : t.create_deliveryman} breadcrumbs={[t.title, isEdit ? t.edit_deliveryman : t.create_deliveryman]}>
            <Head title={isEdit ? t.edit_deliveryman : t.create_deliveryman} />

            <form onSubmit={submit} encType="multipart/form-data" noValidate>
                <Stepper
                    steps={visibleSteps}
                    current={step}
                    visited={visited}
                    errors={stepsWithErrors}
                    onJump={goTo}
                />

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2 space-y-6">
                        {step === 1 && (
                            <Card>
                                <CardContent className="pt-6">
                                    <SectionHeader num={1} title={t.section_basic} />
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <Field label={t.full_name} required error={form.errors.name}>
                                            <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                                        </Field>
                                        <Field label={t.name_en} error={form.errors.name_en}>
                                            <Input value={form.data.name_en} onChange={(e) => form.setData('name_en', e.target.value)} />
                                        </Field>
                                        <Field label={t.mobile} required error={form.errors.mobile}>
                                            <Input value={form.data.mobile} onChange={(e) => form.setData('mobile', e.target.value)} inputMode="tel" />
                                        </Field>
                                        <Field label={t.alt_mobile} error={form.errors.alt_mobile}>
                                            <Input value={form.data.alt_mobile} onChange={(e) => form.setData('alt_mobile', e.target.value)} inputMode="tel" />
                                        </Field>
                                        <Field label={t.email} required error={form.errors.email}>
                                            <Input type="email" autoComplete="off" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} />
                                        </Field>
                                        <Field label={t.password} required={!isEdit} error={form.errors.password} hint={isEdit ? (t.password_keep_hint || 'Leave blank to keep the current password.') : undefined}>
                                            <Input type="password" autoComplete="new-password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} />
                                        </Field>
                                        <Field label={t.gender} error={form.errors.gender}>
                                            <Select value={form.data.gender} onChange={(e) => form.setData('gender', e.target.value)}>
                                                <option value="">—</option>
                                                <option value="male">{t.gender_male}</option>
                                                <option value="female">{t.gender_female}</option>
                                            </Select>
                                        </Field>
                                        <Field label={t.dob} error={form.errors.dob}>
                                            <Input type="date" value={form.data.dob} onChange={(e) => form.setData('dob', e.target.value)} />
                                        </Field>
                                        <Field label={t.nationality} error={form.errors.nationality} className="md:col-span-2">
                                            <Select value={form.data.nationality} onChange={(e) => form.setData('nationality', e.target.value)}>
                                                <option value="">—</option>
                                                {nationalities.map((c) => {
                                                    const label = isAr ? (c.name || c.en_name) : (c.en_name || c.name);
                                                    return <option key={c.id} value={label}>{label}</option>;
                                                })}
                                            </Select>
                                            {!nationalities.length && <p className="text-xs text-muted-foreground">{t.nationality_empty}</p>}
                                        </Field>
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {step === 2 && (
                            <Card>
                                <CardContent className="pt-6">
                                    <SectionHeader num={2} title={t.section_id} />
                                    <div className="grid gap-4 md:grid-cols-3">
                                        <Field label={t.id_type} error={form.errors.id_type}>
                                            <Select value={form.data.id_type} onChange={(e) => form.setData('id_type', e.target.value)}>
                                                <option value="">—</option>
                                                <option value="national_id">{t.id_type_national}</option>
                                                <option value="iqama">{t.id_type_iqama}</option>
                                            </Select>
                                        </Field>
                                        <Field label={t.id_number} error={form.errors.id_number}>
                                            <Input value={form.data.id_number} onChange={(e) => form.setData('id_number', e.target.value)} />
                                        </Field>
                                        <Field label={t.id_expiry} error={form.errors.id_expiry}>
                                            <Input type="date" value={form.data.id_expiry} onChange={(e) => form.setData('id_expiry', e.target.value)} />
                                        </Field>
                                        <FileInput
                                            name="id_image_id"
                                            label={t.id_image}
                                            helper={t.file_help}
                                            error={form.errors.id_image_id}
                                            onChange={(f) => form.setData('id_image_id', f)}
                                        />
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {step === 3 && (
                            <Card>
                                <CardContent className="pt-6">
                                    <SectionHeader num={3} title={t.section_address} />
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <Field label={t.address} required error={form.errors.address} className="md:col-span-2">
                                            <Input value={form.data.address} onChange={(e) => form.setData('address', e.target.value)} />
                                        </Field>
                                        <Field label={t.district} error={form.errors.district}>
                                            <Input value={form.data.district} onChange={(e) => form.setData('district', e.target.value)} />
                                        </Field>
                                        <Field label={t.short_national_address} error={form.errors.short_national_address}>
                                            <Input value={form.data.short_national_address} onChange={(e) => form.setData('short_national_address', e.target.value)} placeholder={t.short_national_address_placeholder} />
                                        </Field>
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {step === 4 && (
                            <Card>
                                <CardContent className="pt-6">
                                    <SectionHeader num={4} title={t.section_employment} />
                                    <div className="mb-5">
                                        <Label className="mb-2 block text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                            {t.driver_type} <span className="text-destructive">*</span>
                                        </Label>
                                        <div className="inline-flex rounded-md border border-input p-0.5">
                                            {['freelancer', 'outsourced', 'company_courier'].map((v) => (
                                                <button
                                                    key={v}
                                                    type="button"
                                                    onClick={() => form.setData('driver_type', v)}
                                                    className={cn(
                                                        'rounded-[5px] px-4 py-1.5 text-xs font-semibold transition-colors',
                                                        form.data.driver_type === v
                                                            ? 'bg-primary text-primary-foreground shadow-sm'
                                                            : 'text-muted-foreground hover:bg-muted',
                                                    )}
                                                >
                                                    {typeLabels[v]}
                                                </button>
                                            ))}
                                        </div>
                                        {form.errors.driver_type && (
                                            <p className="mt-1 text-xs text-destructive flex items-center gap-1">
                                                <AlertCircle className="h-3 w-3" /> {form.errors.driver_type}
                                            </p>
                                        )}
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        {form.data.driver_type === 'company_courier' && (
                                            <Field label={t.employee_number} required error={form.errors.employee_number}>
                                                <Input value={form.data.employee_number} onChange={(e) => form.setData('employee_number', e.target.value)} />
                                            </Field>
                                        )}
                                        {form.data.driver_type === 'outsourced' && (
                                            <Field label={t.supplier_company} required error={form.errors.supplier_company_id}>
                                                <Select value={form.data.supplier_company_id} onChange={(e) => form.setData('supplier_company_id', e.target.value)}>
                                                    <option value="">—</option>
                                                    {supplierCompanies.map((sc) => <option key={sc.id} value={sc.id}>{sc.name}</option>)}
                                                </Select>
                                                {!supplierCompanies.length && <p className="text-xs text-muted-foreground">{t.supplier_company_empty}</p>}
                                            </Field>
                                        )}
                                        <Field label={t.joining_date} error={form.errors.joining_date}>
                                            <Input type="date" value={form.data.joining_date} onChange={(e) => form.setData('joining_date', e.target.value)} />
                                        </Field>
                                        <Field label={t.contract_end_date} error={form.errors.contract_end_date}>
                                            <Input type="date" value={form.data.contract_end_date} onChange={(e) => form.setData('contract_end_date', e.target.value)} />
                                        </Field>
                                        <Field label={t.status} required error={form.errors.status}>
                                            <Select value={form.data.status} onChange={(e) => form.setData('status', e.target.value)}>
                                                <option value="1">{t.status_active}</option>
                                                <option value="2">{t.status_suspended}</option>
                                                <option value="3">{t.status_leave}</option>
                                                <option value="4">{t.status_terminated}</option>
                                            </Select>
                                        </Field>
                                        <Field label={t.hub} required error={form.errors.hub_id}>
                                            <Select value={form.data.hub_id} onChange={(e) => form.setData('hub_id', e.target.value)}>
                                                <option value="">—</option>
                                                {hubs.map((h) => <option key={h.id} value={h.id}>{h.name}</option>)}
                                            </Select>
                                        </Field>
                                        <Field label={t.direct_manager} error={form.errors.direct_manager_id}>
                                            <Select value={form.data.direct_manager_id} onChange={(e) => form.setData('direct_manager_id', e.target.value)}>
                                                <option value="">—</option>
                                                {managers.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                                            </Select>
                                        </Field>
                                        <Field label={t.operational_area} error={form.errors.operational_area_id}>
                                            <Select value={form.data.operational_area_id} onChange={(e) => form.setData('operational_area_id', e.target.value)}>
                                                <option value="">—</option>
                                                {operationalAreas.map((oa) => <option key={oa.id} value={oa.id}>{oa.name}</option>)}
                                            </Select>
                                        </Field>
                                        <Field label={t.salary} error={form.errors.salary}>
                                            <Input type="number" step="any" value={form.data.salary} onChange={(e) => form.setData('salary', e.target.value)} />
                                        </Field>
                                        <Field label={t.delivery_charge} error={form.errors.delivery_charge}>
                                            <Input type="number" step="any" value={form.data.delivery_charge} onChange={(e) => form.setData('delivery_charge', e.target.value)} />
                                        </Field>
                                        <Field label={t.pickup_charge} error={form.errors.pickup_charge}>
                                            <Input type="number" step="any" value={form.data.pickup_charge} onChange={(e) => form.setData('pickup_charge', e.target.value)} />
                                        </Field>
                                        <Field label={t.return_charge} error={form.errors.return_charge}>
                                            <Input type="number" step="any" value={form.data.return_charge} onChange={(e) => form.setData('return_charge', e.target.value)} />
                                        </Field>
                                        <Field label={t.opening_balance} error={form.errors.opening_balance}>
                                            <Input type="number" step="any" value={form.data.opening_balance} onChange={(e) => form.setData('opening_balance', e.target.value)} />
                                        </Field>
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {step === 5 && (
                            <Card>
                                <CardContent className="pt-6">
                                    <SectionHeader num={5} title={t.section_license} />
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <Field label={t.license_number} error={form.errors.license_number}>
                                            <Input value={form.data.license_number} onChange={(e) => form.setData('license_number', e.target.value)} />
                                        </Field>
                                        <Field label={t.license_expiry} error={form.errors.license_expiry}>
                                            <Input type="date" value={form.data.license_expiry} onChange={(e) => form.setData('license_expiry', e.target.value)} />
                                        </Field>
                                        <Field label={t.iqama_expiry} error={form.errors.iqama_expiry}>
                                            <Input type="date" value={form.data.iqama_expiry} onChange={(e) => form.setData('iqama_expiry', e.target.value)} />
                                        </Field>
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {step === 6 && (
                            <Card>
                                <CardContent className="pt-6">
                                    <SectionHeader num={6} title={t.section_bank} />
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <Field label={t.bank_account_no} error={form.errors.bank_account_no}>
                                            <Input value={form.data.bank_account_no} onChange={(e) => form.setData('bank_account_no', e.target.value)} />
                                        </Field>
                                        <Field label={t.iban} error={form.errors.iban}>
                                            <Input value={form.data.iban} onChange={(e) => form.setData('iban', e.target.value)} placeholder={t.iban_placeholder} />
                                        </Field>
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {step === 7 && (
                            <Card>
                                <CardContent className="pt-6">
                                    <SectionHeader num={7} title={t.section_documents} />
                                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-2">
                                        <FileInput name="image_id" label={t.personal_photo} helper={t.file_help} error={form.errors.image_id} onChange={(f) => form.setData('image_id', f)} />
                                        <FileInput name="driving_license_image_id" label={t.license_photo} helper={t.file_help} error={form.errors.driving_license_image_id} onChange={(f) => form.setData('driving_license_image_id', f)} />
                                        {form.data.driver_type === 'freelancer' && (
                                            <>
                                                <FileInput name="iqama_image_id" label={t.iqama_photo} helper={t.file_help} error={form.errors.iqama_image_id} onChange={(f) => form.setData('iqama_image_id', f)} />
                                                <FileInput name="contract_image_id" label={t.contract_photo} helper={t.file_help} error={form.errors.contract_image_id} onChange={(f) => form.setData('contract_image_id', f)} />
                                                <FileInput name="promissory_note_image_id" label={t.promissory_note_photo} helper={t.file_help} error={form.errors.promissory_note_image_id} onChange={(f) => form.setData('promissory_note_image_id', f)} />
                                            </>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {/* Nav */}
                        <div className="flex items-center justify-between gap-3 rounded-xl border border-border bg-card p-4 shadow-sm">
                            <Button type="button" variant="outline" onClick={prev} disabled={visibleSteps.findIndex((s) => s.id === step) === 0}>
                                <ChevronLeft className="h-4 w-4 me-1" /> {t.wizard_prev}
                            </Button>
                            <div className="text-xs text-muted-foreground font-medium">{positionLabel}</div>
                            <div className="flex gap-2">
                                <a href={safeRoute('deliveryman.index')} className="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent">
                                    {t.cancel || 'Cancel'}
                                </a>
                                {isLast ? (
                                    <Button type="submit" disabled={form.processing}>
                                        <Check className="h-4 w-4 me-1" /> {form.processing ? '…' : t.wizard_submit}
                                    </Button>
                                ) : (
                                    <Button type="button" onClick={next}>
                                        {t.wizard_next} <ChevronRight className="h-4 w-4 ms-1" />
                                    </Button>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="lg:col-span-1">
                        <Summary
                            t={t}
                            data={form.data}
                            hubs={hubs}
                            statusLabel={statusLabels[String(form.data.status)]}
                            typeLabel={typeLabels[form.data.driver_type]}
                        />
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}
