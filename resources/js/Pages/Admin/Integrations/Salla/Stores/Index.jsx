import * as React from 'react';
import { Head } from '@inertiajs/react';
import { ArrowLeft, Store, Settings as SettingsIcon, CheckCircle2, AlertCircle, Info } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';

function StatusPill({ kind, label }) {
    const tints = {
        ok:    'bg-emerald-100 text-emerald-700 border-emerald-200',
        warn:  'bg-amber-100   text-amber-700   border-amber-200',
        muted: 'bg-slate-100   text-slate-700   border-slate-200',
        bad:   'bg-rose-100    text-rose-700    border-rose-200',
    };
    return (
        <span className={`inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium ${tints[kind]}`}>
            {label}
        </span>
    );
}

function storeStatusPill(s, t) {
    if (! s.installed) return <StatusPill kind="muted" label={t.uninstalled} />;
    if (s.token_expired) return <StatusPill kind="bad"  label={t.token_expired} />;
    if (s.rushly_merchant_id == null) return <StatusPill kind="warn" label={t.not_linked} />;
    if (s.belongs_to_company === false) return <StatusPill kind="muted" label={t.other_company} />;
    return <StatusPill kind="ok" label={t.installed} />;
}

export default function Index({ stores = [], permissions = {}, urls = {}, t = {} }) {
    return (
        <AdminLayout title={t.title} breadcrumbs={[t.breadcrumb_settings, t.breadcrumb_integrations, t.title]}>
            <Head title={t.title} />

            <div className="mb-4">
                <a href={urls.integrations} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.breadcrumb_integrations}
                </a>
            </div>

            <Card className="mb-4">
                <CardContent className="p-5">
                    <div className="flex items-center gap-2 mb-1">
                        <Store className="h-5 w-5 text-primary" />
                        <h2 className="text-lg font-semibold">{t.title}</h2>
                    </div>
                    <p className="text-sm text-muted-foreground">{t.help}</p>
                </CardContent>
            </Card>

            {stores.length === 0 ? (
                <Card>
                    <CardContent className="p-10 text-center">
                        <Info className="h-6 w-6 mx-auto mb-3 text-muted-foreground" />
                        <p className="text-sm text-muted-foreground">{t.empty}</p>
                    </CardContent>
                </Card>
            ) : (
                <Card>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/40">
                                    <tr className="text-[11px] uppercase tracking-wide text-muted-foreground">
                                        <th className="text-start px-4 py-2.5">{t.store}</th>
                                        <th className="text-start px-4 py-2.5">{t.salla_merchant_id}</th>
                                        <th className="text-start px-4 py-2.5">{t.rushly_merchant}</th>
                                        <th className="text-start px-4 py-2.5">{t.status}</th>
                                        <th className="text-end px-4 py-2.5">{t.auto_create}</th>
                                        <th className="text-end px-4 py-2.5">{t.orders}</th>
                                        <th className="text-end px-4 py-2.5">{t.shipments}</th>
                                        <th className="text-end px-4 py-2.5"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {stores.map((s) => (
                                        <tr key={s.id} className="border-t border-border">
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{s.store_name || '—'}</div>
                                                {s.store_domain && <div className="text-[11px] text-muted-foreground break-all">{s.store_domain}</div>}
                                            </td>
                                            <td className="px-4 py-3"><code className="text-[11px] font-mono">{s.salla_merchant_id}</code></td>
                                            <td className="px-4 py-3">
                                                {s.rushly_merchant
                                                    ? <span className="text-sm">{s.rushly_merchant.name} <span className="text-[11px] text-muted-foreground">#{s.rushly_merchant.id}</span></span>
                                                    : <span className="text-muted-foreground text-sm">—</span>}
                                            </td>
                                            <td className="px-4 py-3">{storeStatusPill(s, t)}</td>
                                            <td className="px-4 py-3 text-end">
                                                {s.auto_create_parcel
                                                    ? <CheckCircle2 className="inline h-4 w-4 text-emerald-600" />
                                                    : <span className="text-muted-foreground text-xs">off</span>}
                                            </td>
                                            <td className="px-4 py-3 text-end tabular-nums">{Number(s.orders_count || 0).toLocaleString()}</td>
                                            <td className="px-4 py-3 text-end tabular-nums">{Number(s.shipments_count || 0).toLocaleString()}</td>
                                            <td className="px-4 py-3 text-end">
                                                {permissions.update && (
                                                    <a href={s.urls.edit} className="inline-flex h-8 items-center rounded-md bg-primary px-3 text-xs font-medium text-primary-foreground hover:bg-primary/90">
                                                        <SettingsIcon className="h-3.5 w-3.5 me-1" /> {t.configure}
                                                    </a>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            )}
        </AdminLayout>
    );
}
