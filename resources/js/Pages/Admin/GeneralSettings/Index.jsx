import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
    Save, AlertCircle, Tag, Phone, Globe, Palette, Image as ImageIcon,
    UploadCloud, X as XIcon, RotateCcw, LogIn,
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
    { key: 'login',   icon: LogIn,     labelKey: 'nav_login' },
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

/**
 * Radio-tile picker for the sidebar logo widget. Each tile is a miniature of
 * the exact rendering that AdminLayout emits, so what you see here is what
 * the sidebar will look like on save. Clicking a tile writes the value into
 * form.data.logo_style; the empty value ("") means "inherit" — same as every
 * other enum on this page.
 */
function LogoStylePreview({ value, onChange, options, logoUrl, brandName, inheritLabel }) {
    const initial = (brandName || 'R').trim().charAt(0).toUpperCase();
    if (!options?.length) return null;

    const Header = ({ style }) => {
        const showImage = style !== 'text_only';
        const showText  = style !== 'logo_only';
        return (
            <div className="flex h-10 items-center gap-2 rounded-md bg-slate-900 px-3 text-white">
                {showImage && (logoUrl
                    ? <img
                        src={logoUrl}
                        alt=""
                        className={style === 'logo_only'
                            ? 'h-7 w-auto max-w-[130px] object-contain'
                            : 'h-6 w-6 rounded object-contain bg-white/5'}
                    />
                    : <span className="grid h-6 w-6 shrink-0 place-items-center rounded bg-primary text-[10px] font-bold text-primary-foreground">{initial}</span>
                )}
                {showText && <span className="truncate text-[13px] font-semibold">{brandName}</span>}
            </div>
        );
    };

    return (
        <div className="pt-3">
            <div className="mb-2 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                Sidebar header preview
            </div>
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <button
                    type="button"
                    onClick={() => onChange('')}
                    className={cn(
                        'rounded-lg border p-3 text-start transition',
                        value === '' ? 'border-primary ring-2 ring-primary/20' : 'border-border hover:border-primary/60'
                    )}
                >
                    <div className="flex h-10 items-center rounded-md border border-dashed border-muted-foreground/30 px-3 text-[11px] text-muted-foreground">
                        {inheritLabel}
                    </div>
                    <div className="mt-2 text-xs font-medium">{inheritLabel}</div>
                </button>
                {options.map((opt) => (
                    <button
                        key={opt.value}
                        type="button"
                        onClick={() => onChange(opt.value)}
                        className={cn(
                            'rounded-lg border p-3 text-start transition',
                            value === opt.value ? 'border-primary ring-2 ring-primary/20' : 'border-border hover:border-primary/60'
                        )}
                    >
                        <Header style={opt.value} />
                        <div className="mt-2 text-xs font-medium">{opt.label}</div>
                        {opt.hint && <div className="text-[11px] text-muted-foreground mt-0.5">{opt.hint}</div>}
                    </button>
                ))}
            </div>
        </div>
    );
}

/**
 * Miniature, in-browser mock of the pre-auth login page. Reads the current
 * form values (not just what's saved) so admins can see unsaved changes to
 * layout, colors, and background image before clicking Save. Deliberately
 * approximates the real Blade templates rather than iframing them — that
 * way there's no reload, no session, and the preview stays crisp on small
 * cards.
 */
function LoginPreview({ layout, primaryColor, textColor, bgUrl, logoUrl, brandName }) {
    const primary = primaryColor || '#a21f5c';
    const accent  = '#29245a';
    const gradient = `linear-gradient(135deg, ${primary} 0%, ${accent} 100%)`;
    const brandInitial = (brandName || 'R').trim().charAt(0).toUpperCase();

    const Logo = ({ className = 'h-6', tint = 'primary' }) => (
        logoUrl
            ? <img src={logoUrl} alt="" className={`${className} w-auto`} />
            : <span
                className={`inline-grid place-items-center h-8 w-8 rounded-lg text-white font-bold text-sm`}
                style={{ background: tint === 'primary' ? primary : 'rgba(255,255,255,0.15)' }}
            >{brandInitial}</span>
    );

    if (layout === 'centered') {
        return (
            <div className="relative aspect-[16/9] w-full overflow-hidden rounded-lg border border-border">
                <div className="absolute inset-0"
                     style={{ background: bgUrl
                         ? `url('${bgUrl}') center/cover no-repeat`
                         : `linear-gradient(135deg, ${primary}1a, ${accent}14)` }} />
                {bgUrl && <div className="absolute inset-0 bg-black/25" />}
                <div className="relative z-10 flex h-full w-full items-center justify-center p-6">
                    <div className="w-full max-w-[220px] rounded-2xl bg-white p-5 shadow-2xl">
                        <div className="mb-3 flex flex-col items-center">
                            <Logo />
                            <div className="mt-2 text-[11px] font-semibold" style={{ color: textColor || '#0f172a' }}>Welcome back</div>
                        </div>
                        <div className="space-y-2">
                            <div className="h-6 rounded-md bg-gray-100" />
                            <div className="h-6 rounded-md bg-gray-100" />
                            <div className="h-6 rounded-md text-[9px] font-semibold text-white flex items-center justify-center"
                                 style={{ background: gradient }}>Sign in</div>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    if (layout === 'fullbleed') {
        return (
            <div className="relative aspect-[16/9] w-full overflow-hidden rounded-lg border border-border">
                <div className="absolute inset-0"
                     style={{ background: bgUrl
                         ? `url('${bgUrl}') center/cover no-repeat`
                         : gradient }} />
                <div className="absolute inset-0" style={{ background: 'rgba(0,0,0,0.35)' }} />
                <div className="relative z-10 flex h-full w-full items-center justify-center p-6">
                    <div className="w-full max-w-[220px] rounded-2xl border border-white/20 bg-white/10 p-5 backdrop-blur-md text-white">
                        <div className="mb-3 flex flex-col items-center">
                            <Logo tint="light" />
                            <div className="mt-2 text-[11px] font-semibold">Welcome back</div>
                        </div>
                        <div className="space-y-2">
                            <div className="h-6 rounded-md bg-white/15" />
                            <div className="h-6 rounded-md bg-white/15" />
                            <div className="h-6 rounded-md text-[9px] font-semibold flex items-center justify-center bg-white" style={{ color: primary }}>Sign in</div>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    // split (default)
    return (
        <div className="relative aspect-[16/9] w-full overflow-hidden rounded-lg border border-border">
            <div className="grid h-full grid-cols-2">
                <div className="flex flex-col items-center justify-center bg-white p-4">
                    <Logo className="h-5" />
                    <div className="mt-2 text-[10px] font-semibold" style={{ color: textColor || '#0f172a' }}>Welcome back</div>
                    <div className="mt-3 w-full max-w-[140px] space-y-1.5">
                        <div className="h-5 rounded bg-gray-100" />
                        <div className="h-5 rounded bg-gray-100" />
                        <div className="h-5 rounded text-[8px] font-semibold text-white flex items-center justify-center"
                             style={{ background: gradient }}>Sign in</div>
                    </div>
                </div>
                <div className="relative overflow-hidden text-white"
                     style={{ background: bgUrl ? `url('${bgUrl}') center/cover no-repeat` : gradient }}>
                    {bgUrl && <div className="absolute inset-0" style={{ background: 'rgba(0,0,0,0.35)' }} />}
                    <div className="relative z-10 flex h-full flex-col items-start justify-center p-4">
                        <div className="rounded-full bg-white/15 px-2 py-0.5 text-[8px] font-semibold uppercase tracking-wide">
                            Smart logistics
                        </div>
                        <div className="mt-2 text-[11px] font-bold leading-tight">{brandName || 'Your brand'}</div>
                        <div className="mt-1 space-y-1 text-[8px] opacity-90">
                            <div>• Real-time tracking</div>
                            <div>• Fleet visibility</div>
                            <div>• Automated billing</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default function Index({ settings = {}, lookups = {}, theme_fallbacks = {}, permissions = {}, urls = {}, t = {} }) {
    const [active, setActive] = React.useState('brand');
    // Preview URL for the picked-but-not-yet-uploaded login_bg File.
    const [loginBgObjectUrl, setLoginBgObjectUrl] = React.useState(null);
    React.useEffect(() => () => { if (loginBgObjectUrl) URL.revokeObjectURL(loginBgObjectUrl); }, [loginBgObjectUrl]);

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
        logo_style: settings.logo_style ?? '',
        font_family: settings.font_family ?? '',
        border_radius: settings.border_radius ?? '',
        density: settings.density ?? '',
        timezone: settings.timezone ?? '',
        logo: null,
        light_logo: null,
        favicon: null,
        login_bg: null,
        login_bg_clear: '0',
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
                                    <Field label={t.timezone} hint={t.timezone_help} error={form.errors.timezone}>
                                        <Select value={form.data.timezone} onChange={(e) => form.setData('timezone', e.target.value)}>
                                            <option value="">{t.timezone_default_option}</option>
                                            {(lookups.timezones || []).map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
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
                                            <Field
                                                label={t.logo_style}
                                                hint={
                                                    (lookups.logo_styles || []).find(o => o.value === form.data.logo_style)?.hint
                                                    || t.logo_style_help
                                                }
                                                error={form.errors.logo_style}
                                            >
                                                <Select value={form.data.logo_style} onChange={(e) => form.setData('logo_style', e.target.value)}>
                                                    <option value="">{t.theme_inherit}</option>
                                                    {(lookups.logo_styles || []).map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
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

                                        {/* Visual picker for logo_style — mirrors the AdminLayout sidebar
                                            header so tenants can see what they're choosing before saving. */}
                                        <LogoStylePreview
                                            value={form.data.logo_style}
                                            onChange={(v) => form.setData('logo_style', v)}
                                            options={lookups.logo_styles || []}
                                            logoUrl={settings.logo_image}
                                            brandName={form.data.name || settings.name || 'Your brand'}
                                            inheritLabel={t.theme_inherit}
                                        />
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

                        {active === 'login' && (
                            <Card>
                                <CardContent className="p-6 space-y-5">
                                    <div>
                                        <h2 className="text-base font-semibold">{t.nav_login}</h2>
                                        <p className="text-xs text-muted-foreground mt-1">{t.login_section_intro}</p>
                                    </div>

                                    {/* Live preview — reflects unsaved edits to layout, colors and bg. */}
                                    <div className="rounded-lg border border-border bg-muted/20 p-3">
                                        <div className="mb-2 flex items-center justify-between">
                                            <span className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                                {t.live_preview || 'Live preview'}
                                            </span>
                                            <span className="text-[10px] text-muted-foreground">
                                                {(lookups.login_layouts || []).find(o => o.value === form.data.login_layout)?.label || form.data.login_layout}
                                            </span>
                                        </div>
                                        <LoginPreview
                                            layout={form.data.login_layout}
                                            primaryColor={form.data.primary_color}
                                            textColor={form.data.text_color}
                                            bgUrl={
                                                // Prefer the just-picked File (via ObjectURL) so the preview updates
                                                // instantly; when the user cleared, use nothing; otherwise fall
                                                // back to whatever is already saved on the row.
                                                form.data.login_bg_clear === '1'
                                                    ? null
                                                    : (loginBgObjectUrl || settings.login_bg_image || null)
                                            }
                                            logoUrl={settings.logo_image}
                                            brandName={form.data.name || settings.name || 'Your brand'}
                                        />
                                    </div>

                                    <Field label={t.login_layout} hint={t.login_layout_help} error={form.errors.login_layout}>
                                        <Select value={form.data.login_layout} onChange={(e) => form.setData('login_layout', e.target.value)}>
                                            {(lookups.login_layouts || []).map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                                        </Select>
                                    </Field>
                                    <LogoUploadCard
                                        label={t.login_bg}
                                        recommended="1920×1080 px (or larger)"
                                        hint={t.login_bg_help}
                                        currentUrl={settings.login_bg_image}
                                        error={form.errors.login_bg}
                                        onPick={(f) => {
                                            form.setData('login_bg', f);
                                            form.setData('login_bg_clear', f ? '0' : '1');
                                            // Update the object URL for the preview.
                                            if (loginBgObjectUrl) URL.revokeObjectURL(loginBgObjectUrl);
                                            setLoginBgObjectUrl(f ? URL.createObjectURL(f) : null);
                                        }}
                                        dark
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
