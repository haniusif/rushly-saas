import * as React from 'react';
import { Head } from '@inertiajs/react';
import { Plug, Settings, ExternalLink, Truck, Info, Store } from 'lucide-react';
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

function LogoBox({ src, name }) {
    return (
        <div className="flex h-14 w-14 items-center justify-center rounded-xl bg-muted/40 shrink-0">
            {src
                ? <img src={src} alt={name} className="max-h-12 max-w-12 object-contain" />
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
                    <LogoBox src={i.logo_url} name={i.name} />
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
                    <LogoBox name={p.name} />
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

export default function Index({ integrations = [], three_pls = [], permissions = {}, t = {} }) {
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
        </AdminLayout>
    );
}
