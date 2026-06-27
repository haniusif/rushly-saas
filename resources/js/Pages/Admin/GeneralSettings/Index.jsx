import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
    Save, AlertCircle, Tag, Phone, Globe, Palette, Image as ImageIcon,
    UploadCloud, X as XIcon, RotateCcw,
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
    { key: 'brand',   icon: Tag,       labelKey: 'nav_brand' },
    { key: 'contact', icon: Phone,     labelKey: 'nav_contact' },
    { key: 'locale',  icon: Globe,     labelKey: 'nav_locale' },
    { key: 'logos',   icon: ImageIcon, labelKey: 'nav_logos' },
    { key: 'theme',   icon: Palette,   labelKey: 'nav_theme' },
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

function formatBytes(bytes) {
    if (!bytes && bytes !== 0) return '';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / 1024 / 1024).toFixed(2)} MB`;
}

function LogoUploadCard({ label, hint, recommended, currentUrl, error, onPick, dark = false, favicon = false }) {
    const [file, setFile] = React.useState(null);
    const [preview, setPreview] = React.useState(null);
    const [dragging, setDragging] = React.useState(false);
    const inputRef = React.useRef(null);

    React.useEffect(() => () => { if (preview) URL.revokeObjectURL(preview); }, [preview]);

    const apply = (f) => {
        if (!f || !f.type?.startsWith('image/')) return;
        onPick(f);
        setFile(f);
        if (preview) URL.revokeObjectURL(preview);
        setPreview(URL.createObjectURL(f));
    };

    const clear = (e) => {
        e?.stopPropagation();
        onPick(null);
        setFile(null);
        if (preview) URL.revokeObjectURL(preview);
        setPreview(null);
        if (inputRef.current) inputRef.current.value = '';
    };

    const onDrop = (e) => {
        e.preventDefault(); e.stopPropagation();
        setDragging(false);
        apply(e.dataTransfer.files?.[0] || null);
    };

    const previewSrc = preview || currentUrl;
    const hasNew = !!file;

    return (
        <Field label={label} hint={hint} error={error}>
            <div
                role="button"
                tabIndex={0}
                onClick={() => inputRef.current?.click()}
                onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); inputRef.current?.click(); } }}
                onDragOver={(e) => { e.preventDefault(); setDragging(true); }}
                onDragLeave={(e) => { e.preventDefault(); setDragging(false); }}
                onDrop={onDrop}
                className={cn(
                    'group relative flex flex-col sm:flex-row items-stretch gap-4 rounded-lg border-2 border-dashed p-4 cursor-pointer transition-all',
                    dragging
                        ? 'border-primary bg-primary/5'
                        : 'border-border hover:border-primary/60 hover:bg-muted/30',
                )}
            >
                {/* Preview tile */}
                <div className={cn(
                    'shrink-0 flex items-center justify-center rounded-md border border-border overflow-hidden',
                    favicon ? 'h-24 w-24' : 'h-24 w-40 sm:w-48',
                    dark ? 'bg-slate-900' : 'bg-muted/40',
                )}>
                    {previewSrc ? (
                        <img
                            src={previewSrc}
                            alt=""
                            className={cn(
                                'object-contain',
                                favicon ? 'max-h-12 max-w-12' : 'max-h-20 max-w-full p-2',
                            )}
                        />
                    ) : (
                        <ImageIcon className={cn('text-muted-foreground/40', favicon ? 'h-8 w-8' : 'h-10 w-10')} />
                    )}
                </div>

                {/* Right column: text + actions */}
                <div className="flex-1 min-w-0 flex flex-col justify-between gap-2">
                    <div>
                        <div className="flex items-center gap-2 text-sm font-medium text-foreground">
                            <UploadCloud className="h-4 w-4 text-primary" />
                            <span>Drop image here, or <span className="text-primary">browse</span></span>
                        </div>
                        <div className="text-[11px] text-muted-foreground mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                            {recommended && <span>Recommended: <span className="font-medium text-foreground/80">{recommended}</span></span>}
                            <span>PNG · JPG · SVG · WebP</span>
                        </div>
                    </div>

                    {hasNew && (
                        <div className="flex items-center gap-2">
                            <span className="inline-flex items-center gap-1.5 rounded-full bg-primary/10 text-primary px-2 py-0.5 text-[11px] font-medium">
                                <span className="h-1.5 w-1.5 rounded-full bg-primary" />
                                New
                            </span>
                            <span className="text-xs text-muted-foreground truncate">
                                {file.name} <span className="text-muted-foreground/70">· {formatBytes(file.size)}</span>
                            </span>
                            <button
                                type="button"
                                onClick={clear}
                                className="ms-auto inline-flex items-center gap-1 rounded-md border border-border bg-background px-2 py-1 text-[11px] text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
                                aria-label="Revert"
                            >
                                <RotateCcw className="h-3 w-3" /> Revert
                            </button>
                        </div>
                    )}

                    {!hasNew && currentUrl && (
                        <div className="text-[11px] text-muted-foreground">
                            <span className="inline-flex items-center gap-1.5 rounded-full bg-muted text-muted-foreground px-2 py-0.5 font-medium">
                                <span className="h-1.5 w-1.5 rounded-full bg-muted-foreground/50" />
                                Current
                            </span>
                        </div>
                    )}
                </div>

                {/* Floating clear button when there's a new file (covers preview tile corner) */}
                {hasNew && (
                    <button
                        type="button"
                        onClick={clear}
                        className="absolute top-2 end-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-background/90 backdrop-blur border border-border text-muted-foreground hover:text-destructive hover:border-destructive transition-colors"
                        aria-label="Remove selected"
                    >
                        <XIcon className="h-3.5 w-3.5" />
                    </button>
                )}

                <input
                    ref={inputRef}
                    type="file"
                    accept="image/*"
                    onChange={(e) => apply(e.target.files?.[0] || null)}
                    className="hidden"
                />
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
                                        <LogoUploadCard
                                            label={t.logo}
                                            recommended="240×60 px"
                                            hint="Shown alongside your brand colors. Manage all logos in the Logos tab."
                                            currentUrl={settings.logo_image}
                                            error={form.errors.logo}
                                            onPick={(f) => form.setData('logo', f)}
                                        />
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
                                <CardContent className="p-6 space-y-5">
                                    <div>
                                        <h2 className="text-base font-semibold">{t.nav_logos}</h2>
                                        <p className="text-xs text-muted-foreground mt-1">Drag and drop, or click any tile to upload.</p>
                                    </div>
                                    <LogoUploadCard
                                        label={t.logo}
                                        recommended="240×60 px"
                                        hint="Primary logo shown on light backgrounds (header, login, invoices)."
                                        currentUrl={settings.logo_image}
                                        error={form.errors.logo}
                                        onPick={(f) => form.setData('logo', f)}
                                    />
                                    <LogoUploadCard
                                        label={t.light_logo}
                                        recommended="240×60 px"
                                        hint="Variant for dark backgrounds (dark sidebar, dark theme)."
                                        currentUrl={settings.light_logo_image}
                                        error={form.errors.light_logo}
                                        onPick={(f) => form.setData('light_logo', f)}
                                        dark
                                    />
                                    <LogoUploadCard
                                        label={t.favicon}
                                        recommended="32×32 px (square)"
                                        hint="Browser tab icon. Square PNG or ICO."
                                        currentUrl={settings.favicon_image}
                                        error={form.errors.favicon}
                                        onPick={(f) => form.setData('favicon', f)}
                                        favicon
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
