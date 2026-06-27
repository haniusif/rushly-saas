import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import {
    BookOpen, LayoutDashboard, Boxes, MapPin, ClipboardList, Inbox,
    ArrowRightLeft, CheckSquare, Bug, Package, Send, Workflow,
    Timer, ShieldCheck, ExternalLink, ImageIcon, X as XIcon, Maximize2,
    Upload, Trash2, Loader2,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { cn } from '@/lib/utils';
import { useT } from '@/lib/i18n';

const SECTIONS = [
    { id: 'overview',    icon: BookOpen,        labelKey: 'wms_kb_sec_overview' },
    { id: 'flow',        icon: Workflow,        labelKey: 'wms_kb_sec_flow' },
    { id: 'dashboard',   icon: LayoutDashboard, labelKey: 'wms_kb_sec_dashboard',   url: 'dashboard',    shot: 'dashboard' },
    { id: 'products',    icon: Boxes,           labelKey: 'wms_kb_sec_products',    url: 'products',     shot: 'products' },
    { id: 'locations',   icon: MapPin,          labelKey: 'wms_kb_sec_locations',   url: 'locations',    shot: 'locations' },
    { id: 'stock',       icon: ClipboardList,   labelKey: 'wms_kb_sec_stock',       url: 'stock',        shot: 'stock' },
    { id: 'grn',         icon: Inbox,           labelKey: 'wms_kb_sec_grn',         url: 'grn',          shot: 'grn' },
    { id: 'adjustments', icon: ArrowRightLeft,  labelKey: 'wms_kb_sec_adjustments', url: 'adjustments',  shot: 'adjustments' },
    { id: 'cycle',       icon: CheckSquare,     labelKey: 'wms_kb_sec_cycle',       url: 'cycle_counts', shot: 'cycle-counts' },
    { id: 'damage',      icon: Bug,             labelKey: 'wms_kb_sec_damage',      url: 'damage',       shot: 'damage' },
    { id: 'fulfillment', icon: Package,         labelKey: 'wms_kb_sec_fulfillment', url: 'fulfillment',  shot: 'fulfillment' },
    { id: 'outbound',    icon: Send,            labelKey: 'wms_kb_sec_outbound',    url: 'outbound',     shot: 'outbound' },
    { id: 'jobs',        icon: Timer,           labelKey: 'wms_kb_sec_jobs' },
    { id: 'access',      icon: ShieldCheck,     labelKey: 'wms_kb_sec_access' },
];

const SHOT_DIR = '/images/wms-kb';

function Toc({ urls, t }) {
    return (
        <Card className="sticky top-4">
            <CardContent className="p-3">
                <div className="px-2 py-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    {t('wms_kb_toc')}
                </div>
                <nav className="mt-1 space-y-0.5">
                    {SECTIONS.map((s) => {
                        const Icon = s.icon;
                        return (
                            <a
                                key={s.id}
                                href={`#${s.id}`}
                                className="group flex items-center gap-2 rounded px-2 py-1.5 text-sm text-muted-foreground hover:bg-muted/60 hover:text-foreground transition-colors"
                            >
                                <Icon className="h-3.5 w-3.5" />
                                <span className="flex-1 truncate">{t(s.labelKey)}</span>
                                {s.url && urls?.[s.url] ? (
                                    <a
                                        href={urls[s.url]}
                                        onClick={(e) => e.stopPropagation()}
                                        title={t('wms_kb_open_module')}
                                        className="opacity-0 group-hover:opacity-100 text-muted-foreground hover:text-primary"
                                    >
                                        <ExternalLink className="h-3 w-3" />
                                    </a>
                                ) : null}
                            </a>
                        );
                    })}
                </nav>
            </CardContent>
        </Card>
    );
}

function Lightbox({ src, alt, onClose }) {
    React.useEffect(() => {
        const onKey = (e) => { if (e.key === 'Escape') onClose(); };
        document.addEventListener('keydown', onKey);
        const prev = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = prev;
        };
    }, [onClose]);

    return (
        <div
            role="dialog"
            aria-modal="true"
            onClick={onClose}
            className="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
        >
            <button
                type="button"
                onClick={onClose}
                className="absolute top-4 end-4 grid h-9 w-9 place-items-center rounded-full bg-white/10 text-white hover:bg-white/20"
                aria-label="Close"
            >
                <XIcon className="h-5 w-5" />
            </button>
            <img
                src={src}
                alt={alt}
                onClick={(e) => e.stopPropagation()}
                className="max-h-[90vh] max-w-[95vw] rounded shadow-2xl"
            />
        </div>
    );
}

function Screenshot({ slug, label, icon: Icon, t, version, canUpdate = true }) {
    const src = version ? `${SHOT_DIR}/${slug}.png?v=${version}` : `${SHOT_DIR}/${slug}.png`;
    const exists = !!version;

    const [open, setOpen] = React.useState(false);
    const [uploading, setUploading] = React.useState(false);
    const [error, setError] = React.useState(null);
    const fileInputRef = React.useRef(null);

    const triggerPicker = () => fileInputRef.current?.click();

    const handleFile = (e) => {
        const file = e.target.files?.[0];
        if (!file) return;
        setError(null);
        setUploading(true);
        router.post(
            route('wms.knowledge-base.screenshot.upload', { slug }),
            { screenshot: file },
            {
                forceFormData: true,
                preserveScroll: true,
                onError: (errors) => {
                    setError(errors.screenshot || t('wms_kb_screenshot_upload_failed'));
                },
                onFinish: () => {
                    setUploading(false);
                    if (fileInputRef.current) fileInputRef.current.value = '';
                },
            }
        );
    };

    const handleDelete = () => {
        if (!window.confirm(t('wms_kb_screenshot_delete_confirm'))) return;
        setError(null);
        setUploading(true);
        router.delete(route('wms.knowledge-base.screenshot.delete', { slug }), {
            preserveScroll: true,
            onError: () => setError(t('wms_kb_screenshot_delete_failed')),
            onFinish: () => setUploading(false),
        });
    };

    // Hidden file input — shared between Upload and Replace buttons.
    const hiddenInput = (
        <input
            ref={fileInputRef}
            type="file"
            accept="image/png,image/jpeg,image/webp"
            onChange={handleFile}
            className="sr-only"
            tabIndex={-1}
            aria-hidden="true"
        />
    );

    if (exists) {
        return (
            <div className="mt-3">
                <div className="group relative overflow-hidden rounded-lg border border-border bg-muted/30">
                    <button
                        type="button"
                        onClick={() => setOpen(true)}
                        className="block w-full"
                        title={t('wms_kb_screenshot_zoom')}
                    >
                        <img src={src} alt={label} className="block max-h-[420px] w-full object-cover" />
                    </button>

                    {/* Hover overlay — zoom hint + action buttons */}
                    <div className="pointer-events-none absolute inset-0 flex items-start justify-between p-2 opacity-0 transition-opacity group-hover:opacity-100">
                        <span className="pointer-events-auto inline-flex items-center gap-1 rounded bg-black/60 px-2 py-1 text-[11px] font-medium text-white">
                            <Maximize2 className="h-3 w-3" /> {t('wms_kb_screenshot_zoom')}
                        </span>
                        {canUpdate ? (
                            <div className="pointer-events-auto flex items-center gap-1">
                                <button
                                    type="button"
                                    onClick={triggerPicker}
                                    disabled={uploading}
                                    className="inline-flex items-center gap-1 rounded bg-white/90 px-2 py-1 text-[11px] font-medium text-foreground hover:bg-white disabled:opacity-60"
                                >
                                    {uploading
                                        ? <Loader2 className="h-3 w-3 animate-spin" />
                                        : <Upload className="h-3 w-3" />}
                                    {t('wms_kb_screenshot_replace')}
                                </button>
                                <button
                                    type="button"
                                    onClick={handleDelete}
                                    disabled={uploading}
                                    className="inline-flex items-center gap-1 rounded bg-rose-600/90 px-2 py-1 text-[11px] font-medium text-white hover:bg-rose-600 disabled:opacity-60"
                                >
                                    <Trash2 className="h-3 w-3" />
                                    {t('wms_kb_screenshot_delete')}
                                </button>
                            </div>
                        ) : null}
                    </div>
                </div>

                {error ? (
                    <div className="mt-2 rounded border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-800">
                        {error}
                    </div>
                ) : null}

                {canUpdate ? hiddenInput : null}
                {open ? <Lightbox src={src} alt={label} onClose={() => setOpen(false)} /> : null}
            </div>
        );
    }

    // Placeholder — also the upload affordance when no screenshot exists yet.
    return (
        <div className="mt-3">
            <div className="flex flex-col gap-3 rounded-lg border border-dashed border-border bg-muted/20 p-4 sm:flex-row sm:items-center">
                <div className="grid h-14 w-14 shrink-0 place-items-center rounded-lg bg-muted text-muted-foreground">
                    {Icon ? <Icon className="h-6 w-6" /> : <ImageIcon className="h-6 w-6" />}
                </div>
                <div className="min-w-0 flex-1">
                    <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        {t('wms_kb_screenshot_label')} — {label}
                    </div>
                    <div className="mt-1 text-xs text-muted-foreground">
                        {canUpdate ? (
                            <>
                                {t('wms_kb_screenshot_placeholder_hint')}{' '}
                                <code className="rounded bg-background px-1.5 py-0.5 font-mono text-[11px] border border-border">
                                    {SHOT_DIR}/{slug}.png
                                </code>
                            </>
                        ) : (
                            <>{t('wms_kb_screenshot_no_image') || 'No screenshot uploaded yet.'}</>
                        )}
                    </div>
                </div>
                {canUpdate ? (
                    <button
                        type="button"
                        onClick={triggerPicker}
                        disabled={uploading}
                        className="inline-flex shrink-0 items-center gap-1.5 rounded-md border border-input bg-background px-3 py-1.5 text-xs font-medium hover:bg-muted/60 disabled:opacity-60"
                    >
                        {uploading
                            ? <Loader2 className="h-3.5 w-3.5 animate-spin" />
                            : <Upload className="h-3.5 w-3.5" />}
                        {uploading ? t('wms_kb_screenshot_uploading') : t('wms_kb_screenshot_upload')}
                    </button>
                ) : null}
            </div>

            {error ? (
                <div className="mt-2 rounded border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-800">
                    {error}
                </div>
            ) : null}

            {canUpdate ? hiddenInput : null}
        </div>
    );
}

function Section({ id, icon: Icon, title, subtitle, openUrl, shot, t, canUpdate, children }) {
    return (
        <section id={id} className="scroll-mt-20">
            <Card>
                <CardContent className="p-6">
                    <div className="mb-3 flex items-start justify-between gap-4">
                        <div>
                            <h2 className="flex items-center gap-2 text-lg font-semibold">
                                {Icon ? <Icon className="h-5 w-5 text-primary" /> : null}
                                {title}
                            </h2>
                            {subtitle ? (
                                <p className="mt-1 text-sm text-muted-foreground">{subtitle}</p>
                            ) : null}
                        </div>
                        {openUrl ? (
                            <a
                                href={openUrl}
                                className="inline-flex items-center gap-1 rounded-md border border-input bg-background px-2.5 py-1.5 text-xs font-medium hover:bg-muted/60 transition-colors"
                            >
                                {t('wms_kb_open_module')} <ExternalLink className="h-3 w-3" />
                            </a>
                        ) : null}
                    </div>

                    {shot ? (
                        <Screenshot
                            slug={shot.slug}
                            label={shot.label}
                            icon={Icon}
                            version={shot.version}
                            canUpdate={canUpdate}
                            t={t}
                        />
                    ) : null}

                    <div className="prose-sm max-w-none text-sm leading-relaxed mt-3">{children}</div>
                </CardContent>
            </Card>
        </section>
    );
}

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

function FlowArrow() {
    return <span className="mx-1 text-muted-foreground">→</span>;
}

function FieldList({ items, t }) {
    return (
        <div className="mt-2 rounded border border-border bg-muted/30 p-3">
            <div className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {t('wms_kb_key_fields')}
            </div>
            <div className="mt-2 flex flex-wrap gap-1.5">
                {items.map((f) => (
                    <code key={f} className="rounded bg-background px-1.5 py-0.5 text-[11px] font-mono border border-border">
                        {f}
                    </code>
                ))}
            </div>
        </div>
    );
}

function PageList({ items }) {
    return (
        <ul className="mt-2 space-y-1.5 list-disc ps-5">
            {items.map((p, i) => (
                <li key={p.path + i}>
                    <code className="text-[12px] font-mono text-primary">{p.path}</code>
                    <span className="text-muted-foreground"> — {p.desc}</span>
                </li>
            ))}
        </ul>
    );
}

function StatusFlow({ steps }) {
    return (
        <div className="mt-2 flex flex-wrap items-center gap-y-1 text-xs">
            {steps.map((s, i) => (
                <React.Fragment key={s.label + i}>
                    <Pill tone={s.tone}>{s.label}</Pill>
                    {i < steps.length - 1 ? <FlowArrow /> : null}
                </React.Fragment>
            ))}
        </div>
    );
}

export default function Index({ urls = {}, screenshots = {}, can_update = false }) {
    const t = useT();
    const shotFor = (slug, label) => ({ slug, label, version: screenshots[slug] || null });

    return (
        <AdminLayout title={t('wms_kb_title')}>
            <Head title={t('wms_kb_title')} />

            <div className="mb-6">
                <h1 className="text-2xl font-semibold tracking-tight">{t('wms_kb_title')}</h1>
                <p className="mt-1 text-sm text-muted-foreground">{t('wms_kb_subtitle')}</p>
            </div>

            <div className="grid gap-6 lg:grid-cols-[240px_minmax(0,1fr)]">
                <aside className="hidden lg:block">
                    <Toc urls={urls} t={t} />
                </aside>

                <div className="space-y-6">
                    {/* OVERVIEW */}
                    <Section id="overview" icon={BookOpen} t={t} canUpdate={can_update}
                        title={t('wms_kb_sec_overview')}
                        subtitle={t('wms_kb_sub_overview')}>
                        <p>{t('wms_kb_overview_body')}</p>
                        <ul className="mt-3 list-disc ps-5 space-y-1">
                            <li>{t('wms_kb_overview_b1')}</li>
                            <li>{t('wms_kb_overview_b2')}</li>
                            <li>{t('wms_kb_overview_b3')}</li>
                            <li>{t('wms_kb_overview_b4')}</li>
                            <li>{t('wms_kb_overview_b5')}</li>
                        </ul>
                    </Section>

                    {/* FLOW */}
                    <Section id="flow" icon={Workflow} t={t} canUpdate={can_update}
                        title={t('wms_kb_sec_flow')}>
                        <div className="space-y-3">
                            <div>
                                <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-1">
                                    {t('wms_kb_flow_inbound')}
                                </div>
                                <div className="flex flex-wrap items-center gap-1 text-sm">
                                    <Pill tone="info">{t('wms_kb_flow_supplier')}</Pill>
                                    <FlowArrow />
                                    <Pill tone="info">{t('wms_kb_flow_grn_draft')}</Pill>
                                    <FlowArrow />
                                    <Pill tone="info">{t('wms_kb_flow_receive')}</Pill>
                                    <FlowArrow />
                                    <Pill tone="ok">{t('wms_kb_flow_complete')}</Pill>
                                    <FlowArrow />
                                    <Pill tone="violet">{t('wms_kb_flow_stock_created')}</Pill>
                                    <span className="text-muted-foreground mx-1">·</span>
                                    <Pill tone="bad">{t('wms_kb_flow_damaged')}</Pill>
                                </div>
                            </div>
                            <div>
                                <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-1">
                                    {t('wms_kb_flow_outbound')}
                                </div>
                                <div className="flex flex-wrap items-center gap-1 text-sm">
                                    <Pill tone="info">{t('wms_kb_flow_parcel')}</Pill>
                                    <FlowArrow />
                                    <Pill tone="info">{t('wms_kb_flow_ful_pending')}</Pill>
                                    <FlowArrow />
                                    <Pill tone="warn">{t('wms_kb_flow_picking')}</Pill>
                                    <FlowArrow />
                                    <Pill tone="warn">{t('wms_kb_flow_packing')}</Pill>
                                    <FlowArrow />
                                    <Pill tone="ok">{t('wms_kb_flow_dispatched')}</Pill>
                                    <FlowArrow />
                                    <Pill tone="violet">{t('wms_kb_flow_outbound_3pl')}</Pill>
                                </div>
                            </div>
                            <div>
                                <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-1">
                                    {t('wms_kb_flow_corrections')}
                                </div>
                                <div className="flex flex-wrap items-center gap-1 text-sm">
                                    <Pill tone="info">{t('wms_kb_flow_cc_dmg')}</Pill>
                                    <FlowArrow />
                                    <Pill tone="warn">{t('wms_kb_flow_adj_pending')}</Pill>
                                    <FlowArrow />
                                    <Pill tone="ok">{t('wms_kb_flow_approved')}</Pill>
                                    <FlowArrow />
                                    <Pill tone="violet">{t('wms_kb_flow_stock_updated')}</Pill>
                                </div>
                            </div>
                        </div>
                    </Section>

                    {/* DASHBOARD */}
                    <Section id="dashboard" icon={LayoutDashboard} t={t} canUpdate={can_update}
                        title={t('wms_kb_sec_dashboard')} openUrl={urls.dashboard}
                        subtitle={t('wms_kb_sub_dashboard')}
                        shot={shotFor('dashboard', t('wms_kb_sec_dashboard'))}>
                        <p>{t('wms_kb_dashboard_body')}</p>
                        <PageList items={[
                            { path: 'GET /admin/wms',           desc: t('wms_kb_dashboard_page_main') },
                            { path: 'GET /admin/wms/dashboard', desc: t('wms_kb_dashboard_page_alias') },
                        ]} />
                    </Section>

                    {/* PRODUCTS */}
                    <Section id="products" icon={Boxes} t={t} canUpdate={can_update}
                        title={t('wms_kb_sec_products')} openUrl={urls.products}
                        subtitle={t('wms_kb_sub_products')}
                        shot={shotFor('products', t('wms_kb_sec_products'))}>
                        <p>{t('wms_kb_products_body')}</p>
                        <PageList items={[
                            { path: 'Index',         desc: t('wms_kb_products_page_index') },
                            { path: 'Create / Edit', desc: t('wms_kb_products_page_form') },
                            { path: 'Show',          desc: t('wms_kb_products_page_show') },
                        ]} />
                        <FieldList t={t} items={['sku','barcode','name','category','weight','dimensions','unit','reorder_point','track_expiry','is_active','merchant_id','hub_id']} />
                    </Section>

                    {/* LOCATIONS */}
                    <Section id="locations" icon={MapPin} t={t} canUpdate={can_update}
                        title={t('wms_kb_sec_locations')} openUrl={urls.locations}
                        subtitle={t('wms_kb_sub_locations')}
                        shot={shotFor('locations', t('wms_kb_sec_locations'))}>
                        <p>{t('wms_kb_locations_body')}</p>
                        <PageList items={[
                            { path: 'Index',         desc: t('wms_kb_locations_page_index') },
                            { path: 'Map',           desc: t('wms_kb_locations_page_map') },
                            { path: 'Create / Edit', desc: t('wms_kb_locations_page_form') },
                        ]} />
                        <FieldList t={t} items={['code','hub_id','zone','aisle','rack','shelf','bin','type','capacity','is_active']} />
                    </Section>

                    {/* STOCK */}
                    <Section id="stock" icon={ClipboardList} t={t} canUpdate={can_update}
                        title={t('wms_kb_sec_stock')} openUrl={urls.stock}
                        subtitle={t('wms_kb_sub_stock')}
                        shot={shotFor('stock', t('wms_kb_sec_stock'))}>
                        <p>{t('wms_kb_stock_body')}</p>
                        <PageList items={[
                            { path: 'Index',  desc: t('wms_kb_stock_page_index') },
                            { path: 'Export', desc: t('wms_kb_stock_page_export') },
                        ]} />
                        <FieldList t={t} items={['product_id','location_id','quantity','reserved_qty','batch_number','lot_number','expiry_date']} />
                    </Section>

                    {/* GRN */}
                    <Section id="grn" icon={Inbox} t={t} canUpdate={can_update}
                        title={t('wms_kb_sec_grn')} openUrl={urls.grn}
                        subtitle={t('wms_kb_sub_grn')}
                        shot={shotFor('grn', t('wms_kb_sec_grn'))}>
                        <p>{t('wms_kb_grn_body')}</p>
                        <div className="mt-2">
                            <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{t('wms_kb_status_flow')}</div>
                            <StatusFlow steps={[
                                { label: 'draft',       tone: 'default' },
                                { label: 'in_progress', tone: 'info' },
                                { label: 'completed',   tone: 'ok' },
                            ]} />
                            <div className="mt-1 text-xs text-muted-foreground">{t('wms_kb_grn_discrepancy_note')}</div>
                        </div>
                        <PageList items={[
                            { path: 'Index / Create / Show',  desc: t('wms_kb_grn_page_crud') },
                            { path: 'PUT /grn/{id}/complete', desc: t('wms_kb_grn_page_complete') },
                        ]} />
                        <FieldList t={t} items={['grn_number','merchant_id','hub_id','reference_number','received_by','received_at','status']} />
                    </Section>

                    {/* ADJUSTMENTS */}
                    <Section id="adjustments" icon={ArrowRightLeft} t={t} canUpdate={can_update}
                        title={t('wms_kb_sec_adjustments')} openUrl={urls.adjustments}
                        subtitle={t('wms_kb_sub_adjustments')}
                        shot={shotFor('adjustments', t('wms_kb_sec_adjustments'))}>
                        <p>{t('wms_kb_adjustments_body')}</p>
                        <div className="mt-2">
                            <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{t('wms_kb_status_flow')}</div>
                            <StatusFlow steps={[
                                { label: 'pending_approval', tone: 'warn' },
                                { label: 'approved',         tone: 'ok' },
                            ]} />
                            <div className="mt-1 text-xs text-muted-foreground">{t('wms_kb_adjustments_rejected_note')}</div>
                        </div>
                        <PageList items={[
                            { path: 'Index / Create / Show',         desc: t('wms_kb_adjustments_page_crud') },
                            { path: 'PUT /adjustments/{id}/approve', desc: t('wms_kb_adjustments_page_approve') },
                            { path: 'PUT /adjustments/{id}/reject',  desc: t('wms_kb_adjustments_page_reject') },
                            { path: 'GET /adjustments/lookup-qty',   desc: t('wms_kb_adjustments_page_lookup') },
                        ]} />
                        <FieldList t={t} items={['product_id','location_id','reason','quantity_before','quantity_after','quantity_change','approval_status','approved_by','approved_at']} />
                    </Section>

                    {/* CYCLE COUNTS */}
                    <Section id="cycle" icon={CheckSquare} t={t} canUpdate={can_update}
                        title={t('wms_kb_sec_cycle')} openUrl={urls.cycle_counts}
                        subtitle={t('wms_kb_sub_cycle')}
                        shot={shotFor('cycle-counts', t('wms_kb_sec_cycle'))}>
                        <p>{t('wms_kb_cycle_body')}</p>
                        <div className="mt-2">
                            <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{t('wms_kb_status_flow')}</div>
                            <StatusFlow steps={[
                                { label: 'pending',     tone: 'default' },
                                { label: 'in_progress', tone: 'info' },
                                { label: 'completed',   tone: 'ok' },
                            ]} />
                        </div>
                        <PageList items={[
                            { path: 'Index / Create / Show', desc: t('wms_kb_cycle_page_crud') },
                        ]} />
                        <FieldList t={t} items={['count_number','hub_id','scope','zone','assigned_to','status','started_at','completed_at']} />
                    </Section>

                    {/* DAMAGE */}
                    <Section id="damage" icon={Bug} t={t} canUpdate={can_update}
                        title={t('wms_kb_sec_damage')} openUrl={urls.damage}
                        subtitle={t('wms_kb_sub_damage')}
                        shot={shotFor('damage', t('wms_kb_sec_damage'))}>
                        <p>{t('wms_kb_damage_body')}</p>
                        <PageList items={[
                            { path: 'Index / Create / Show', desc: t('wms_kb_damage_page_crud') },
                        ]} />
                        <FieldList t={t} items={['product_id','location_id','quantity_damaged','cause','photos','action_taken','reported_by','grn_id?']} />
                    </Section>

                    {/* FULFILLMENT */}
                    <Section id="fulfillment" icon={Package} t={t} canUpdate={can_update}
                        title={t('wms_kb_sec_fulfillment')} openUrl={urls.fulfillment}
                        subtitle={t('wms_kb_sub_fulfillment')}
                        shot={shotFor('fulfillment', t('wms_kb_sec_fulfillment'))}>
                        <p>{t('wms_kb_fulfillment_body')}</p>
                        <div className="mt-2">
                            <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{t('wms_kb_status_flow')}</div>
                            <StatusFlow steps={[
                                { label: 'pending',    tone: 'default' },
                                { label: 'picking',    tone: 'info' },
                                { label: 'packing',    tone: 'info' },
                                { label: 'ready',      tone: 'warn' },
                                { label: 'dispatched', tone: 'ok' },
                            ]} />
                            <div className="mt-1 text-xs text-muted-foreground">{t('wms_kb_fulfillment_parcel_note')}</div>
                        </div>
                        <PageList items={[
                            { path: 'Index / Show',                    desc: t('wms_kb_fulfillment_page_crud') },
                            { path: 'GET  /fulfillment/{id}/picking',  desc: t('wms_kb_fulfillment_page_picking') },
                            { path: 'PUT  /fulfillment/{id}/pick',     desc: t('wms_kb_fulfillment_page_pick') },
                            { path: 'PUT  /fulfillment/{id}/pack',     desc: t('wms_kb_fulfillment_page_pack') },
                            { path: 'PUT  /fulfillment/{id}/dispatch', desc: t('wms_kb_fulfillment_page_dispatch') },
                        ]} />
                        <FieldList t={t} items={['fulfillment_number','parcel_id','status','picker_id','packer_id','picked_at','packed_at','dispatched_at','sla_deadline']} />
                    </Section>

                    {/* OUTBOUND */}
                    <Section id="outbound" icon={Send} t={t} canUpdate={can_update}
                        title={t('wms_kb_sec_outbound')} openUrl={urls.outbound}
                        subtitle={t('wms_kb_sub_outbound')}
                        shot={shotFor('outbound', t('wms_kb_sec_outbound'))}>
                        <p>{t('wms_kb_outbound_body')}</p>
                        <div className="mt-2">
                            <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{t('wms_kb_types')}</div>
                            <div className="mt-1 flex flex-wrap gap-1">
                                <Pill>fulfillment</Pill>
                                <Pill>manual</Pill>
                                <Pill>transfer</Pill>
                                <Pill>return_to_merchant</Pill>
                            </div>
                        </div>
                        <div className="mt-2">
                            <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{t('wms_kb_status_flow')}</div>
                            <StatusFlow steps={[
                                { label: 'pending',    tone: 'default' },
                                { label: 'processing', tone: 'info' },
                                { label: 'completed',  tone: 'ok' },
                            ]} />
                        </div>
                        <PageList items={[
                            { path: 'Index / Create / Show',       desc: t('wms_kb_outbound_page_crud') },
                            { path: 'PUT /outbound/{id}/complete', desc: t('wms_kb_outbound_page_complete') },
                        ]} />
                        <FieldList t={t} items={['outbound_number','type','merchant_id','hub_id','fulfillment_id?','processed_by','status','completed_at']} />
                    </Section>

                    {/* JOBS */}
                    <Section id="jobs" icon={Timer} t={t} canUpdate={can_update}
                        title={t('wms_kb_sec_jobs')}
                        subtitle={t('wms_kb_sub_jobs')}>
                        <PageList items={[
                            { path: 'wms:auto-fulfillment',      desc: t('wms_kb_jobs_auto_ful') },
                            { path: 'wms:min-stock-check',       desc: t('wms_kb_jobs_min_stock') },
                            { path: 'wms:expiry-alert',          desc: t('wms_kb_jobs_expiry') },
                            { path: 'wms:fulfillment-sla-check', desc: t('wms_kb_jobs_sla') },
                        ]} />
                    </Section>

                    {/* ACCESS */}
                    <Section id="access" icon={ShieldCheck} t={t} canUpdate={can_update}
                        title={t('wms_kb_sec_access')}>
                        <ul className="list-disc ps-5 space-y-1">
                            <li>{t('wms_kb_access_b1')}</li>
                            <li>{t('wms_kb_access_b2')}</li>
                            <li>{t('wms_kb_access_b3')}</li>
                            <li>{t('wms_kb_access_b4')}</li>
                        </ul>
                    </Section>
                </div>
            </div>
        </AdminLayout>
    );
}
