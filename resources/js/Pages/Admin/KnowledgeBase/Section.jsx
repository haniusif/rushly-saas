import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    BookOpen, LayoutDashboard, Package, Warehouse, Truck, DollarSign,
    UserCog, ListChecks, Receipt, FileText, Layout, History, Settings,
    ChevronLeft, ImageIcon, Upload, Trash2, Loader2, Maximize2, X as XIcon,
    ExternalLink,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { cn } from '@/lib/utils';

const ICONS = {
    LayoutDashboard, Package, Warehouse, Truck, DollarSign, UserCog,
    ListChecks, Receipt, FileText, Layout, History, Settings, BookOpen,
};

const SHOT_DIR = '/images/kb';

function Pill({ children, tone = 'default' }) {
    const tones = {
        default: 'bg-muted text-foreground',
        info:    'bg-sky-100 text-sky-800',
        warn:    'bg-amber-100 text-amber-800',
        ok:      'bg-emerald-100 text-emerald-800',
        bad:     'bg-rose-100 text-rose-800',
        violet:  'bg-violet-100 text-violet-800',
    };
    return (
        <span className={cn('inline-flex items-center rounded px-1.5 py-0.5 text-[11px] font-medium', tones[tone])}>
            {children}
        </span>
    );
}

function FlowArrow() { return <span className="mx-1 text-muted-foreground">→</span>; }

function Lightbox({ src, alt, onClose }) {
    React.useEffect(() => {
        const onKey = (e) => { if (e.key === 'Escape') onClose(); };
        document.addEventListener('keydown', onKey);
        const prev = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => { document.removeEventListener('keydown', onKey); document.body.style.overflow = prev; };
    }, [onClose]);

    return (
        <div role="dialog" aria-modal="true" onClick={onClose}
             className="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm">
            <button type="button" onClick={onClose}
                    className="absolute top-4 end-4 grid h-9 w-9 place-items-center rounded-full bg-white/10 text-white hover:bg-white/20"
                    aria-label="Close">
                <XIcon className="h-5 w-5" />
            </button>
            <img src={src} alt={alt} onClick={(e) => e.stopPropagation()}
                 className="max-h-[90vh] max-w-[95vw] rounded shadow-2xl" />
        </div>
    );
}

function Screenshot({ section, sub, label, icon: Icon, version, canUpdate = true }) {
    const src = version ? `${SHOT_DIR}/${section}/${sub}.png?v=${version}` : `${SHOT_DIR}/${section}/${sub}.png`;
    const exists = !!version;
    const [open, setOpen] = React.useState(false);
    const [uploading, setUploading] = React.useState(false);
    const [error, setError] = React.useState(null);
    const fileRef = React.useRef(null);

    const trigger = () => fileRef.current?.click();

    const handleFile = (e) => {
        const file = e.target.files?.[0];
        if (!file) return;
        setError(null); setUploading(true);
        router.post(
            route('admin.kb.screenshot.upload', { section, sub }),
            { screenshot: file },
            {
                forceFormData: true, preserveScroll: true,
                onError: (errs) => setError(errs.screenshot || 'Upload failed'),
                onFinish: () => { setUploading(false); if (fileRef.current) fileRef.current.value = ''; },
            },
        );
    };

    const handleDelete = () => {
        if (!window.confirm('Delete this screenshot?')) return;
        setError(null); setUploading(true);
        router.delete(route('admin.kb.screenshot.delete', { section, sub }), {
            preserveScroll: true,
            onError: () => setError('Delete failed'),
            onFinish: () => setUploading(false),
        });
    };

    const hiddenInput = (
        <input ref={fileRef} type="file" accept="image/png,image/jpeg,image/webp"
               onChange={handleFile} className="sr-only" tabIndex={-1} aria-hidden="true" />
    );

    if (exists) {
        return (
            <div className="mt-3">
                <div className="group relative overflow-hidden rounded-lg border border-border bg-muted/30">
                    <button type="button" onClick={() => setOpen(true)} className="block w-full">
                        <img src={src} alt={label} className="block max-h-[420px] w-full object-cover" />
                    </button>
                    <div className="pointer-events-none absolute inset-0 flex items-start justify-between p-2 opacity-0 transition-opacity group-hover:opacity-100">
                        <span className="pointer-events-auto inline-flex items-center gap-1 rounded bg-black/60 px-2 py-1 text-[11px] font-medium text-white">
                            <Maximize2 className="h-3 w-3" /> Zoom
                        </span>
                        {canUpdate ? (
                            <div className="pointer-events-auto flex items-center gap-1">
                                <button type="button" onClick={trigger} disabled={uploading}
                                        className="inline-flex items-center gap-1 rounded bg-white/90 px-2 py-1 text-[11px] font-medium text-foreground hover:bg-white disabled:opacity-60">
                                    {uploading ? <Loader2 className="h-3 w-3 animate-spin" /> : <Upload className="h-3 w-3" />}
                                    Replace
                                </button>
                                <button type="button" onClick={handleDelete} disabled={uploading}
                                        className="inline-flex items-center gap-1 rounded bg-rose-600/90 px-2 py-1 text-[11px] font-medium text-white hover:bg-rose-600 disabled:opacity-60">
                                    <Trash2 className="h-3 w-3" /> Delete
                                </button>
                            </div>
                        ) : null}
                    </div>
                </div>
                {error ? <div className="mt-2 rounded border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-800">{error}</div> : null}
                {canUpdate ? hiddenInput : null}
                {open ? <Lightbox src={src} alt={label} onClose={() => setOpen(false)} /> : null}
            </div>
        );
    }

    return (
        <div className="mt-3">
            <div className="flex flex-col gap-3 rounded-lg border border-dashed border-border bg-muted/20 p-4 sm:flex-row sm:items-center">
                <div className="grid h-14 w-14 shrink-0 place-items-center rounded-lg bg-muted text-muted-foreground">
                    {Icon ? <Icon className="h-6 w-6" /> : <ImageIcon className="h-6 w-6" />}
                </div>
                <div className="min-w-0 flex-1">
                    <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Screenshot — {label}</div>
                    <div className="mt-1 text-xs text-muted-foreground">
                        {canUpdate
                            ? <>Drop image at <code className="rounded bg-background px-1.5 py-0.5 font-mono text-[11px] border border-border">{SHOT_DIR}/{section}/{sub}.png</code></>
                            : <>No screenshot uploaded yet.</>}
                    </div>
                </div>
                {canUpdate ? (
                    <button type="button" onClick={trigger} disabled={uploading}
                            className="inline-flex shrink-0 items-center gap-1.5 rounded-md border border-input bg-background px-3 py-1.5 text-xs font-medium hover:bg-muted/60 disabled:opacity-60">
                        {uploading ? <Loader2 className="h-3.5 w-3.5 animate-spin" /> : <Upload className="h-3.5 w-3.5" />}
                        {uploading ? 'Uploading…' : 'Upload screenshot'}
                    </button>
                ) : null}
            </div>
            {error ? <div className="mt-2 rounded border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-800">{error}</div> : null}
            {canUpdate ? hiddenInput : null}
        </div>
    );
}

function StatusFlow({ steps }) {
    if (!steps || !steps.length) return null;
    return (
        <div className="mt-2 flex flex-wrap items-center gap-y-1 text-xs">
            {steps.map((s, i) => (
                <React.Fragment key={s.label + i}>
                    <Pill tone={s.tone || 'default'}>{s.label}</Pill>
                    {i < steps.length - 1 ? <FlowArrow /> : null}
                </React.Fragment>
            ))}
        </div>
    );
}

function FieldList({ items, label }) {
    if (!items || !items.length) return null;
    return (
        <div className="mt-2 rounded border border-border bg-muted/30 p-3">
            <div className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{label}</div>
            <div className="mt-2 flex flex-wrap gap-1.5">
                {items.map((f) => (
                    <code key={f} className="rounded bg-background px-1.5 py-0.5 text-[11px] font-mono border border-border">{f}</code>
                ))}
            </div>
        </div>
    );
}

function PageList({ items, label }) {
    if (!items || !items.length) return null;
    return (
        <div className="mt-3">
            <div className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1">{label}</div>
            <ul className="space-y-1.5 list-disc ps-5">
                {items.map((p, i) => (
                    <li key={i}>
                        <code className="text-[12px] font-mono text-primary">{p.path}</code>
                        <span className="text-muted-foreground"> — {p.desc}</span>
                    </li>
                ))}
            </ul>
        </div>
    );
}

function SubSection({ section, sub, t, canUpdate }) {
    const SubIcon = ICONS[sub.icon] || BookOpen;
    return (
        <section id={sub.slug} className="scroll-mt-20">
            <Card>
                <CardContent className="p-6">
                    <div className="flex items-start gap-3 mb-2">
                        <div className="grid h-9 w-9 shrink-0 place-items-center rounded-md bg-primary/10 text-primary">
                            <SubIcon className="h-4.5 w-4.5" />
                        </div>
                        <div className="min-w-0">
                            <h2 className="text-lg font-semibold">{sub.label || sub.slug}</h2>
                            {sub.purpose ? <p className="mt-1 text-sm text-muted-foreground">{sub.purpose}</p> : null}
                        </div>
                    </div>

                    <Screenshot
                        section={section.slug}
                        sub={sub.slug}
                        label={sub.label || sub.slug}
                        icon={SubIcon}
                        version={sub.version}
                        canUpdate={canUpdate}
                    />

                    {!sub.purpose && !sub.pages && !sub.fields && !sub.status_flow ? (
                        <div className="mt-3 text-xs text-muted-foreground italic">{t.pending}</div>
                    ) : null}

                    <PageList items={sub.pages} label={t.pages_label} />

                    {sub.status_flow && sub.status_flow.length ? (
                        <div className="mt-3">
                            <div className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.status_flow}</div>
                            <StatusFlow steps={sub.status_flow} />
                        </div>
                    ) : null}

                    <FieldList items={sub.fields} label={t.key_fields} />

                    {sub.cross_links ? (
                        <div className="mt-3 rounded border border-border bg-muted/20 p-3">
                            <div className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.cross_links}</div>
                            <p className="mt-1 text-sm leading-relaxed">{sub.cross_links}</p>
                        </div>
                    ) : null}

                    {sub.notes ? (
                        <div className="mt-3 rounded border border-dashed border-border bg-amber-50/50 p-3">
                            <div className="text-[11px] font-semibold uppercase tracking-wide text-amber-700">{t.notes}</div>
                            <p className="mt-1 text-sm leading-relaxed text-amber-900">{sub.notes}</p>
                        </div>
                    ) : null}
                </CardContent>
            </Card>
        </section>
    );
}

function Toc({ section, t }) {
    return (
        <Card className="sticky top-4">
            <CardContent className="p-3">
                <Link href={route('admin.kb.index')}
                      className="flex items-center gap-1.5 rounded px-2 py-1.5 text-xs font-medium text-muted-foreground hover:bg-muted/60 hover:text-foreground transition-colors">
                    <ChevronLeft className="h-3.5 w-3.5" /> {t.back_to_hub}
                </Link>
                <div className="mt-2 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    {section.label}
                </div>
                <nav className="mt-1 space-y-0.5">
                    {section.subs.map((s) => {
                        const SubIcon = ICONS[s.icon] || BookOpen;
                        return (
                            <a key={s.slug} href={`#${s.slug}`}
                               className="flex items-center gap-2 rounded px-2 py-1.5 text-sm text-muted-foreground hover:bg-muted/60 hover:text-foreground transition-colors">
                                <SubIcon className="h-3.5 w-3.5" />
                                <span className="truncate">{s.label || s.slug}</span>
                            </a>
                        );
                    })}
                </nav>
            </CardContent>
        </Card>
    );
}

export default function SectionPage({ section, screenshots = {}, urls = {}, t = {}, can_update = false }) {
    const Icon = ICONS[section.icon] || BookOpen;
    const subs = (section.subs || []).map((s) => ({ ...s, version: screenshots[s.slug] || null }));

    return (
        <AdminLayout>
            <Head title={`${section.label} — KB`} />

            <div className="mb-6 flex items-start gap-3">
                <div className="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-primary/10 text-primary">
                    <Icon className="h-6 w-6" />
                </div>
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">{section.label}</h1>
                    {section.overview ? <p className="mt-1 text-sm text-muted-foreground max-w-3xl">{section.overview}</p> : null}
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-[240px_minmax(0,1fr)]">
                <aside className="hidden lg:block">
                    <Toc section={{ ...section, subs }} t={t} />
                </aside>
                <div className="space-y-6">
                    {subs.length === 0 ? (
                        <Card>
                            <CardContent className="p-6 text-sm text-muted-foreground italic">{t.pending}</CardContent>
                        </Card>
                    ) : subs.map((sub) => (
                        <SubSection key={sub.slug} section={section} sub={sub} t={t} canUpdate={can_update} />
                    ))}
                </div>
            </div>
        </AdminLayout>
    );
}
