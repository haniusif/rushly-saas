import * as React from 'react';
import { Head } from '@inertiajs/react';
import {
    Plug, Settings, ExternalLink, Truck, Info, Store, Calculator, Network,
    ShoppingBag, ShoppingCart, PackageOpen, Package2, Plane, Rocket, Boxes, Package,
    Receipt, BookOpen, LayoutGrid,
} from 'lucide-react';

// Brand key → Lucide icon. Used as final fallback when no real logo is available.
const BRAND_ICONS = {
    salla:       ShoppingBag,
    zid:         Store,
    shopify:     ShoppingCart,
    woocommerce: ShoppingCart,
    panda:       PackageOpen,
    zajel:       Package2,
    aramex:      Plane,
    jet:         Rocket,
    logestechs:  Boxes,
    imile:       Package,
    qoyod:       Receipt,
    daftra:      BookOpen,
    odoo:        LayoutGrid,
};

// Brands available via the simple-icons CDN (CC0). Verified at build time.
const SIMPLE_ICONS_SLUGS = new Set(['salla', 'shopify', 'woocommerce', 'odoo', 'jet']);

function iconFor(key, fallback) {
    return BRAND_ICONS[String(key || '').toLowerCase()] || fallback;
}

function brandLogoUrl(key) {
    const k = String(key || '').toLowerCase();
    if (! k) return null;
    // 1. User-supplied logo in /public/integrations/{key}.{svg,png}
    //    (LogoBox tries these via <img onError> chain below.)
    return `/integrations/${k}.svg`;
}

function brandCdnUrl(key) {
    const k = String(key || '').toLowerCase();
    return SIMPLE_ICONS_SLUGS.has(k) ? `https://cdn.simpleicons.org/${k}` : null;
}
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';

function StatusPill({ kind, label }) {
    const tints = {
        ok:    'bg-emerald-100 text-emerald-700 border-emerald-200',
        warn:  'bg-amber-100   text-amber-700   border-amber-200',
        muted: 'bg-slate-100   text-slate-700   border-slate-200',
    };
    return (
        <span className={`inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium ${tints[kind]}`}>
            {label}
        </span>
    );
}

function LogoBox({ src, name, brandKey, Icon }) {
    // Build an ordered fallback chain. <img onError> walks down it; once
    // exhausted, we render the Lucide icon (or a letter monogram).
    const chain = React.useMemo(() => {
        const urls = [];
        if (src) urls.push(src);
        const local = brandLogoUrl(brandKey);
        if (local) urls.push(local);
        const cdn = brandCdnUrl(brandKey);
        if (cdn) urls.push(cdn);
        return urls;
    }, [src, brandKey]);

    const [idx, setIdx] = React.useState(0);
    React.useEffect(() => { setIdx(0); }, [chain.length, chain[0]]);

    const current = chain[idx];

    return (
        <div className="flex h-14 w-14 items-center justify-center rounded-xl bg-muted/40 shrink-0 overflow-hidden">
            {current
                ? <img
                    src={current}
                    alt={name}
                    className="max-h-12 max-w-12 object-contain"
                    onError={() => setIdx((i) => i + 1)}
                  />
                : Icon
                    ? <Icon className="h-7 w-7 text-primary" />
                    : <span className="text-xl font-bold text-foreground">{(name || '?').slice(0, 1).toUpperCase()}</span>}
        </div>
    );
}

function MonoVal({ children, className = '' }) {
    return <code className={`text-[11px] font-mono break-all ${className}`}>{children}</code>;
}

function DLRow({ label, children }) {
    return (
        <>
            <dt className="text-[11px] uppercase tracking-wide text-muted-foreground">{label}</dt>
            <dd className="text-sm">{children}</dd>
        </>
    );
}

function EcommerceCard({ i, permissions, t }) {
    let pill;
    if (i.bridge_ready) pill = <StatusPill kind="ok"    label={t.connected} />;
    else if (i.is_enabled) pill = <StatusPill kind="warn"  label={t.needs_config} />;
    else                pill = <StatusPill kind="muted" label={t.disabled} />;

    return (
        <Card className="flex flex-col h-full">
            <CardContent className="p-5 flex flex-col h-full">
                <div className="flex items-center gap-3 mb-4">
                    <LogoBox src={i.logo_url} name={i.name} brandKey={i.platform} Icon={iconFor(i.platform, ShoppingBag)} />
                    <div className="flex-1 min-w-0">
                        <h3 className="text-base font-semibold">{i.name}</h3>
                        <p className="text-xs text-muted-foreground">{i.host}</p>
                    </div>
                    {pill}
                </div>

                <dl className="grid grid-cols-[max-content_1fr] gap-x-3 gap-y-2 mb-4">
                    <DLRow label={t.bridge_url}>
                        {i.app_url
                            ? <a href={i.app_url} target="_blank" rel="noreferrer" className="text-primary hover:underline break-all">{i.app_url}</a>
                            : <span className="text-muted-foreground">—</span>}
                    </DLRow>
                    <DLRow label={t.api_base}>
                        {i.api_base ? <MonoVal>{i.api_base}</MonoVal> : <span className="text-muted-foreground">—</span>}
                    </DLRow>
                    <DLRow label={t.parcels_created}>
                        <strong className="tabular-nums">{Number(i.parcels || 0).toLocaleString()}</strong>
                    </DLRow>
                    <DLRow label={t.writeback_token}>
                        {i.writeback_set
                            ? <MonoVal>••••{i.writeback_tail}</MonoVal>
                            : <span className="text-destructive">{t.not_set}</span>}
                    </DLRow>
                    {typeof i.stores_count !== 'undefined' && (
                        <DLRow label={t.stores}>
                            <strong className="tabular-nums">{Number(i.stores_count || 0).toLocaleString()}</strong>
                            <span className="text-muted-foreground"> · {Number(i.stores_linked_count || 0).toLocaleString()} {t.linked}</span>
                        </DLRow>
                    )}
                </dl>

                <div className="mt-auto flex flex-wrap gap-2 pt-3 border-t border-border">
                    {permissions.update && (
                        <a href={i.urls.edit} className="inline-flex h-8 items-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                            <Settings className="h-3.5 w-3.5 me-1" /> {t.configure}
                        </a>
                    )}
                    {i.urls.stores && (
                        <a href={i.urls.stores} className="inline-flex h-8 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">
                            <Store className="h-3.5 w-3.5 me-1" /> {t.manage_stores}
                        </a>
                    )}
                    {i.app_url && (
                        <a href={i.app_url} target="_blank" rel="noreferrer" className="inline-flex h-8 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">
                            <ExternalLink className="h-3.5 w-3.5 me-1" /> {t.open_bridge}
                        </a>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

function ThreePlCard({ p, t }) {
    return (
        <Card className="flex flex-col h-full">
            <CardContent className="p-5 flex flex-col h-full">
                <div className="flex items-center gap-3 mb-4">
                    <LogoBox src={p.logo_url} name={p.name} brandKey={p.key} Icon={iconFor(p.key, Truck)} />
                    <div className="flex-1 min-w-0">
                        <h3 className="text-base font-semibold">{p.name}</h3>
                        <p className="text-xs text-muted-foreground">{p.host}</p>
                    </div>
                    {p.key_set
                        ? <StatusPill kind="ok"   label={t.connected} />
                        : <StatusPill kind="warn" label={t.needs_config} />}
                </div>

                <dl className="grid grid-cols-[max-content_1fr] gap-x-3 gap-y-2 mb-4">
                    <DLRow label={t.api_base}>
                        {p.base_url ? <MonoVal>{p.base_url}</MonoVal> : <span className="text-muted-foreground">—</span>}
                    </DLRow>
                    <DLRow label={t.api_key}>
                        {p.key_set
                            ? <MonoVal>••••{p.key_tail}</MonoVal>
                            : <span className="text-destructive">{t.not_set}</span>}
                    </DLRow>
                    <DLRow label={t.parcels_assigned}>
                        <strong className="tabular-nums">{Number(p.parcels || 0).toLocaleString()}</strong>
                    </DLRow>
                    {Object.entries(p.extras || {}).map(([label, value]) =>
                        value ? <DLRow key={label} label={label}><MonoVal>{value}</MonoVal></DLRow> : null
                    )}
                    <DLRow label={t.config_source}>
                        <MonoVal>.env / config/services.php</MonoVal>
                    </DLRow>
                </dl>

                <div className="mt-auto pt-3 border-t border-border flex items-start gap-2 text-[11px] text-muted-foreground">
                    <Info className="h-3.5 w-3.5 mt-0.5 shrink-0" />
                    <span>{t.three_pl_note}</span>
                </div>
            </CardContent>
        </Card>
    );
}

function PaymentsCard({ p, t }) {
    let pill;
    if (p.ready)        pill = <StatusPill kind="ok"    label={t.connected} />;
    else if (p.enabled) pill = <StatusPill kind="warn"  label={t.needs_config} />;
    else                pill = <StatusPill kind="muted" label={t.needs_config} />;
    return (
        <Card className="flex flex-col h-full">
            <CardContent className="p-5 flex flex-col h-full">
                <div className="flex items-center gap-3 mb-4">
                    <LogoBox name={p.name} brandKey={p.key} Icon={iconFor(p.key, Receipt)} />
                    <div className="flex-1 min-w-0">
                        <h3 className="text-base font-semibold">{p.name}</h3>
                        <p className="text-xs text-muted-foreground">{p.host}</p>
                    </div>
                    {pill}
                </div>
                <div className="flex flex-wrap items-center gap-1.5 mb-3">
                    <span className="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-2 py-0.5 text-[10px] font-medium text-sky-700">
                        {p.region}
                    </span>
                    {(p.methods || []).map((m) => (
                        <span key={m} className="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-medium text-slate-700">
                            {m}
                        </span>
                    ))}
                </div>
                {p.note && (
                    <p className="text-[11px] text-muted-foreground italic mb-3">{p.note}</p>
                )}
                <div className="mt-auto flex flex-wrap gap-2 pt-3 border-t border-border">
                    <a href={p.urls.settings} className="inline-flex h-8 items-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                        <Settings className="h-3.5 w-3.5 me-1" /> {t.configure}
                    </a>
                    <a href={p.urls.docs} target="_blank" rel="noreferrer" className="inline-flex h-8 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">
                        <ExternalLink className="h-3.5 w-3.5 me-1" /> {t.api_docs}
                    </a>
                </div>
            </CardContent>
        </Card>
    );
}

function AccountingCard({ a, t, fallbackIcon = Calculator }) {
    let pill;
    if (a.ready)         pill = <StatusPill kind="ok"    label={t.connected} />;
    else if (a.enabled)  pill = <StatusPill kind="warn"  label={t.needs_config} />;
    else                 pill = <StatusPill kind="muted" label={t.disabled} />;
    return (
        <Card className="flex flex-col h-full">
            <CardContent className="p-5 flex flex-col h-full">
                <div className="flex items-center gap-3 mb-4">
                    <LogoBox name={a.name} brandKey={a.key} Icon={iconFor(a.key, fallbackIcon)} />
                    <div className="flex-1 min-w-0">
                        <h3 className="text-base font-semibold">{a.name}</h3>
                        <p className="text-xs text-muted-foreground">{a.host}</p>
                    </div>
                    {pill}
                </div>
                <div className="mt-auto flex flex-wrap gap-2 pt-3 border-t border-border">
                    <a href={a.urls.settings} className="inline-flex h-8 items-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                        <Settings className="h-3.5 w-3.5 me-1" /> {t.configure}
                    </a>
                    <a href={a.urls.docs} target="_blank" rel="noreferrer" className="inline-flex h-8 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">
                        <ExternalLink className="h-3.5 w-3.5 me-1" /> API docs
                    </a>
                </div>
            </CardContent>
        </Card>
    );
}

export default function Index({ integrations = [], three_pls = [], accounting = [], erp = [], payments = [], permissions = {}, t = {} }) {
    return (
        <AdminLayout title={t.title} breadcrumbs={[t.breadcrumb_settings, t.title]}>
            <Head title={t.title} />

            <Card className="mb-4">
                <CardContent className="p-5">
                    <div className="flex items-center gap-2 mb-1">
                        <Plug className="h-5 w-5 text-primary" />
                        <h2 className="text-lg font-semibold">{t.ecommerce_title}</h2>
                    </div>
                    <p className="text-sm text-muted-foreground">{t.ecommerce_help}</p>
                </CardContent>
            </Card>

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3 mb-6">
                {integrations.map((i) => (
                    <EcommerceCard key={i.platform} i={i} permissions={permissions} t={t} />
                ))}
            </div>

            <Card className="mb-4">
                <CardContent className="p-5">
                    <div className="flex items-center gap-2 mb-1">
                        <Truck className="h-5 w-5 text-primary" />
                        <h2 className="text-lg font-semibold">{t.three_pl_title}</h2>
                    </div>
                    <p className="text-sm text-muted-foreground">{t.three_pl_help}</p>
                </CardContent>
            </Card>

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {three_pls.map((p) => (
                    <ThreePlCard key={p.key} p={p} t={t} />
                ))}
            </div>

            {accounting.length > 0 && (
                <>
                    <Card className="mb-4 mt-6">
                        <CardContent className="p-5">
                            <div className="flex items-center gap-2 mb-1">
                                <Calculator className="h-5 w-5 text-primary" />
                                <h2 className="text-lg font-semibold">{t.accounting_title}</h2>
                            </div>
                            <p className="text-sm text-muted-foreground">{t.accounting_help}</p>
                        </CardContent>
                    </Card>
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {accounting.map((a) => <AccountingCard key={a.key} a={a} t={t} />)}
                    </div>
                </>
            )}

            {erp.length > 0 && (
                <>
                    <Card className="mb-4 mt-6">
                        <CardContent className="p-5">
                            <div className="flex items-center gap-2 mb-1">
                                <Network className="h-5 w-5 text-primary" />
                                <h2 className="text-lg font-semibold">{t.erp_title}</h2>
                            </div>
                            <p className="text-sm text-muted-foreground">{t.erp_help}</p>
                        </CardContent>
                    </Card>
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {erp.map((e) => <AccountingCard key={e.key} a={e} t={t} fallbackIcon={Network} />)}
                    </div>
                </>
            )}

            {payments.length > 0 && (
                <>
                    <Card className="mb-4 mt-6">
                        <CardContent className="p-5">
                            <div className="flex items-center gap-2 mb-1">
                                <Receipt className="h-5 w-5 text-primary" />
                                <h2 className="text-lg font-semibold">{t.payments_title}</h2>
                            </div>
                            <p className="text-sm text-muted-foreground">{t.payments_help}</p>
                        </CardContent>
                    </Card>
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {payments.map((p) => <PaymentsCard key={p.key} p={p} t={t} />)}
                    </div>
                </>
            )}
        </AdminLayout>
    );
}
