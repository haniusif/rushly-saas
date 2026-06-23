import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
    Save, AlertCircle, Tag, Phone, Globe, Palette, Image as ImageIcon,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';
import { Textarea } from '@/Components/ui/Textarea';
import { cn } from '@/lib/utils';

const SECTIONS = [
    { key: 'brand',   icon: Tag,     labelKey: 'nav_brand' },
    { key: 'contact', icon: Phone,   labelKey: 'nav_contact' },
    { key: 'locale',  icon: Globe,   labelKey: 'nav_locale' },
    { key: 'theme',   icon: Palette, labelKey: 'nav_theme' },
    { key: 'logos',   icon: ImageIcon, labelKey: 'nav_logos' },
];

function Field({ label, required, error, hint, children }) {
    return (
        <div className="space-y-1.5">
            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {label} {required && <span className="text-destructive">*</span>}
            </Label>
            {children}
            {hint && <p className="text-[11px] text-muted-foreground">{hint}</p>}
            {error && <p className="text-xs text-destructive flex items-center gap-1"><AlertCircle className="h-3 w-3" /> {error}</p>}
        </div>
    );
}

function ColorField({ label, value, onChange, error }) {
    const isHex = /^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/.test(value || '');
    return (
        <Field label={label} error={error}>
            <div className="flex items-center gap-2">
                <input
                    type="color"
                    value={isHex ? value : '#000000'}
                    onChange={(e) => onChange(e.target.value)}
                    className="h-10 w-12 cursor-pointer rounded-md border border-input p-1"
                />
                <Input
                    value={value || ''}
                    onChange={(e) => onChange(e.target.value)}
                    placeholder="#rrggbb"
                    maxLength={7}
                    className="font-mono text-sm"
                />
            </div>
        </Field>
    );
}

function ToggleField({ label, description, value, onChange }) {
    return (
        <div className="flex items-center justify-between gap-4 rounded-md border border-border bg-muted/20 p-3">
            <div>
                <div className="text-sm font-medium">{label}</div>
                {description && <div className="text-[11px] text-muted-foreground mt-0.5">{description}</div>}
            </div>
            <button
                type="button"
                onClick={() => onChange(!value)}
                className={cn(
                    'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                    value ? 'bg-primary' : 'bg-muted-foreground/30'
                )}
            >
                <span className={cn(
                    'inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow',
                    value ? 'translate-x-6' : 'translate-x-1'
                )} />
            </button>
        </div>
    );
}

function FilePreviewField({ label, currentUrl, error, onPick, dark = false, smallPreview = false }) {
    const [preview, setPreview] = React.useState(null);
    React.useEffect(() => () => { if (preview) URL.revokeObjectURL(preview); }, [preview]);
    const handle = (e) => {
        const f = e.target.files?.[0] || null;
        onPick(f);
        if (preview) URL.revokeObjectURL(preview);
        setPreview(f ? URL.createObjectURL(f) : null);
    };
    const src = preview || currentUrl;
    return (
        <Field label={label} error={error}>
            <div className={cn(
                'flex items-center gap-3 rounded-md border border-dashed border-border p-3',
                dark && 'bg-slate-900'
            )}>
                {src && (
                    <img
                        src={src}
                        alt=""
                        className={cn(
                            'object-contain rounded',
                            smallPreview ? 'max-h-8 max-w-8' : 'max-h-12 max-w-32'
                        )}
                    />
                )}
                <Input type="file" accept="image/*" onChange={handle} className="flex-1" />
            </div>
        </Field>
    );
}

export default function Index({ settings = {}, lookups = {}, theme_fallbacks = {}, permissions = {}, urls = {}, t = {} }) {
    const [active, setActive] = React.useState('brand');

    const form = useForm({
        name: settings.name ?? '',
        copyright: settings.copyright ?? '',
        show_landing_page: settings.show_landing_page ? '1' : '0',
        phone: settings.phone ?? '',
        email: settings.email ?? '',
        address: settings.address ?? '',
        currency: settings.currency ?? '',
        par_track_prefix: settings.par_track_prefix ?? '',
        invoice_prefix: settings.invoice_prefix ?? '',
        primary_color: settings.primary_color ?? '#000000',
        text_color: settings.text_color ?? '#000000',
        login_layout: settings.login_layout ?? 'split',
        sidebar_color: settings.sidebar_color ?? '',
        sidebar_text_color: settings.sidebar_text_color ?? '',
        topbar_color: settings.topbar_color ?? '',
        topbar_text_color: settings.topbar_text_color ?? '',
        accent_color: settings.accent_color ?? '',
        sidebar_style: settings.sidebar_style ?? '',
        font_family: settings.font_family ?? '',
        border_radius: settings.border_radius ?? '',
        density: settings.density ?? '',
        logo: null,
        light_logo: null,
        favicon: null,
        _method: 'put',
    });

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { forceFormData: true, preserveScroll: true });
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.breadcrumb_settings, t.title]}>
            <Head title={t.title} />

            <form onSubmit={onSubmit} encType="multipart/form-data">
                <div className="flex flex-col gap-5 lg:flex-row">
                    {/* Left nav */}
                    <aside className="lg:w-60 lg:shrink-0">
                        <Card>
                            <CardContent className="p-2">
                                <div className="flex flex-col gap-1">
                                    {SECTIONS.map(({ key, icon: Icon, labelKey }) => {
                                        const isActive = active === key;
                                        return (
                                            <button
                                                key={key}
                                                type="button"
                                                onClick={() => setActive(key)}
                                                className={cn(
                                                    'flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-start transition-colors',
                                                    isActive
                                                        ? 'bg-primary/10 text-primary'
                                                        : 'text-muted-foreground hover:bg-muted/40 hover:text-foreground'
                                                )}
                                            >
                                                <Icon className="h-4 w-4" />
                                                {t[labelKey]}
                                            </button>
                                        );
                                    })}
                                </div>
                            </CardContent>
                        </Card>
                    </aside>

                    {/* Right pane */}
                    <div className="flex-1 min-w-0 space-y-4">
                        {active === 'brand' && (
                            <Card>
                                <CardContent className="p-6 space-y-4">
                                    <div>
                                        <h2 className="text-base font-semibold">{t.nav_brand}</h2>
                                        <p className="text-xs text-muted-foreground mt-0.5">{t.title}</p>
                                    </div>
                                    <Field label={t.application_name} required error={form.errors.name}>
                                        <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                                    </Field>
                                    <Field label={t.copyright} error={form.errors.copyright}>
                                        <Input value={form.data.copyright} onChange={(e) => form.setData('copyright', e.target.value)} />
                                    </Field>
                                    <ToggleField
                                        label={t.show_landing}
                                        description={t.show_landing_help}
                                        value={form.data.show_landing_page === '1'}
                                        onChange={(v) => form.setData('show_landing_page', v ? '1' : '0')}
                                    />
                                </CardContent>
                            </Card>
                        )}

                        {active === 'contact' && (
                            <Card>
                                <CardContent className="p-6 space-y-4">
                                    <div>
                                        <h2 className="text-base font-semibold">{t.nav_contact}</h2>
                                        <p className="text-xs text-muted-foreground mt-0.5">{t.address}</p>
                                    </div>
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <Field label={t.phone} required error={form.errors.phone}>
                                            <Input value={form.data.phone} onChange={(e) => form.setData('phone', e.target.value)} inputMode="tel" />
                                        </Field>
                                        <Field label={t.email} required error={form.errors.email}>
                                            <Input type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} />
                                        </Field>
                                    </div>
                                    <Field label={t.address} required error={form.errors.address}>
                                        <Textarea rows={3} value={form.data.address} onChange={(e) => form.setData('address', e.target.value)} />
                                    </Field>
                                </CardContent>
                            </Card>
                        )}

                        {active === 'locale' && (
                            <Card>
                                <CardContent className="p-6 space-y-4">
                                    <div>
                                        <h2 className="text-base font-semibold">{t.nav_locale}</h2>
                                    </div>
                                    <Field label={t.currency} required error={form.errors.currency}>
                                        <Select value={form.data.currency} onChange={(e) => form.setData('currency', e.target.value)}>
                                            <option value="">—</option>
                                            {(lookups.currencies || []).map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
                                        </Select>
                                    </Field>
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <Field label={t.par_track_prefix} error={form.errors.par_track_prefix}>
                                            <Input className="uppercase font-mono" value={form.data.par_track_prefix} onChange={(e) => form.setData('par_track_prefix', e.target.value.toUpperCase())} />
                                        </Field>
                                        <Field label={t.invoice_prefix} error={form.errors.invoice_prefix}>
                                            <Input className="uppercase font-mono" value={form.data.invoice_prefix} onChange={(e) => form.setData('invoice_prefix', e.target.value.toUpperCase())} />
                                        </Field>
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {active === 'theme' && (
                            <>
                                <Card>
                                    <CardContent className="p-6 space-y-4">
                                        <div>
                                            <h2 className="text-base font-semibold">{t.nav_theme}</h2>
                                        </div>
                                        <div className="grid gap-4 md:grid-cols-2">
                                            <ColorField label={t.primary_color} value={form.data.primary_color} onChange={(v) => form.setData('primary_color', v)} error={form.errors.primary_color} />
                                            <ColorField label={t.text_color} value={form.data.text_color} onChange={(v) => form.setData('text_color', v)} error={form.errors.text_color} />
                                        </div>
                                        <Field label={t.login_layout} hint={t.login_layout_help} error={form.errors.login_layout}>
                                            <Select value={form.data.login_layout} onChange={(e) => form.setData('login_layout', e.target.value)}>
                                                {(lookups.login_layouts || []).map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                                            </Select>
                                        </Field>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardContent className="p-6 space-y-4">
                                        <div>
                                            <h2 className="text-base font-semibold">{t.theme_section_colors}</h2>
                                            <p className="text-xs text-muted-foreground mt-0.5">{t.theme_inherit}</p>
                                        </div>
                                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                            {['sidebar_color','sidebar_text_color','topbar_color','topbar_text_color','accent_color'].map((field) => (
                                                <ColorField
                                                    key={field}
                                                    label={t[field]}
                                                    value={form.data[field] || ''}
                                                    onChange={(v) => form.setData(field, v)}
                                                    error={form.errors[field]}
                                                />
                                            ))}
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardContent className="p-6 space-y-4">
                                        <div>
                                            <h2 className="text-base font-semibold">{t.theme_section_layout}</h2>
                                        </div>
                                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                                            <Field label={t.sidebar_style} error={form.errors.sidebar_style}>
                                                <Select value={form.data.sidebar_style} onChange={(e) => form.setData('sidebar_style', e.target.value)}>
                                                    <option value="">{t.theme_inherit}</option>
                                                    {(lookups.sidebar_styles || []).map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                                                </Select>
                                            </Field>
                                            <Field label={t.font_family} error={form.errors.font_family}>
                                                <Select value={form.data.font_family} onChange={(e) => form.setData('font_family', e.target.value)}>
                                                    <option value="">{t.theme_inherit}</option>
                                                    {(lookups.fonts || []).map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                                                </Select>
                                            </Field>
                                            <Field label={t.border_radius} error={form.errors.border_radius}>
                                                <Select value={form.data.border_radius} onChange={(e) => form.setData('border_radius', e.target.value)}>
                                                    <option value="">{t.theme_inherit}</option>
                                                    {(lookups.border_radii || []).map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                                                </Select>
                                            </Field>
                                            <Field label={t.density} error={form.errors.density}>
                                                <Select value={form.data.density} onChange={(e) => form.setData('density', e.target.value)}>
                                                    <option value="">{t.theme_inherit}</option>
                                                    {(lookups.densities || []).map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                                                </Select>
                                            </Field>
                                        </div>
                                    </CardContent>
                                </Card>
                            </>
                        )}

                        {active === 'logos' && (
                            <Card>
                                <CardContent className="p-6 space-y-4">
                                    <div>
                                        <h2 className="text-base font-semibold">{t.nav_logos}</h2>
                                    </div>
                                    <FilePreviewField
                                        label={t.logo}
                                        currentUrl={settings.logo_image}
                                        error={form.errors.logo}
                                        onPick={(f) => form.setData('logo', f)}
                                    />
                                    <FilePreviewField
                                        label={t.light_logo}
                                        currentUrl={settings.light_logo_image}
                                        error={form.errors.light_logo}
                                        onPick={(f) => form.setData('light_logo', f)}
                                        dark
                                    />
                                    <FilePreviewField
                                        label={t.favicon}
                                        currentUrl={settings.favicon_image}
                                        error={form.errors.favicon}
                                        onPick={(f) => form.setData('favicon', f)}
                                        smallPreview
                                    />
                                </CardContent>
                            </Card>
                        )}

                        {permissions.update && (
                            <div className="flex justify-end pt-2">
                                <Button type="submit" disabled={form.processing}>
                                    <Save className="h-4 w-4 me-1" /> {t.save}
                                </Button>
                            </div>
                        )}
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}
