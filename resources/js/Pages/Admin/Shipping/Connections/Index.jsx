import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Plus, Truck, CheckCircle2, XCircle, Star, Edit, Trash2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';

function StatusPill({ status }) {
    const map = {
        active:  ['bg-emerald-100 text-emerald-700 border-emerald-200', 'active'],
        paused:  ['bg-amber-100 text-amber-700 border-amber-200', 'paused'],
        invalid: ['bg-rose-100 text-rose-700 border-rose-200', 'invalid'],
    };
    const [cls, label] = map[status] || ['bg-slate-100 text-slate-700 border-slate-200', status || '—'];
    return <span className={`inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium ${cls}`}>{label}</span>;
}

export default function Index({ connections = [], providers = [], permissions = {}, urls = {}, t = {} }) {
    const [showProviderPicker, setShowProviderPicker] = React.useState(false);

    return (
        <AdminLayout title={t.page_title} breadcrumbs={[t.breadcrumb_settings, t.breadcrumb_integrations, t.breadcrumb_shipping]}>
            <Head title={t.page_title} />

            <div className="mb-4 flex items-center justify-between gap-2">
                <a href={urls.integrations} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.breadcrumb_integrations}
                </a>
                {permissions.update && (
                    <Button type="button" onClick={() => setShowProviderPicker((v) => !v)}>
                        <Plus className="h-4 w-4 me-1" /> {t.add_integration}
                    </Button>
                )}
            </div>

            <Card className="mb-4">
                <CardContent className="p-5 flex items-start gap-3">
                    <Truck className="h-5 w-5 text-primary mt-0.5" />
                    <div>
                        <h2 className="text-lg font-semibold">{t.page_title}</h2>
                        <p className="text-sm text-muted-foreground">{t.help}</p>
                    </div>
                </CardContent>
            </Card>

            {showProviderPicker && (
                <Card className="mb-4 border-primary/40">
                    <CardContent className="p-5">
                        <h3 className="text-sm font-semibold mb-3">{t.pick_provider}</h3>
                        <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            {providers.map((p) => (
                                <Link
                                    key={p.code}
                                    href={`${urls.create.split('?')[0]}?provider=${encodeURIComponent(p.code)}`}
                                    className="flex items-center gap-3 rounded-md border border-input p-3 hover:bg-muted/40"
                                >
                                    {p.logo_url
                                        ? <img src={p.logo_url} alt={p.name} className="h-8 w-8 rounded object-contain" />
                                        : <Truck className="h-6 w-6 text-muted-foreground" />}
                                    <span className="text-sm font-medium">{p.name}</span>
                                </Link>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            )}

            {connections.length === 0 ? (
                <Card>
                    <CardContent className="p-8 text-center text-sm text-muted-foreground">
                        {t.no_connections}
                    </CardContent>
                </Card>
            ) : (
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {connections.map((c) => (
                        <Card key={c.id} className="flex flex-col h-full">
                            <CardContent className="p-5 flex flex-col h-full">
                                <div className="flex items-center gap-3 mb-3">
                                    <div className="flex-1 min-w-0">
                                        <h3 className="text-base font-semibold truncate">{c.connection_name}</h3>
                                        <p className="text-xs text-muted-foreground">{c.provider.name}</p>
                                    </div>
                                    <StatusPill status={c.status} />
                                </div>

                                <dl className="text-xs space-y-1.5 mb-4">
                                    {c.domain && <div className="flex gap-2"><dt className="text-muted-foreground w-20">domain</dt><dd className="font-mono truncate flex-1">{c.domain}</dd></div>}
                                    {c.remote_company_id && <div className="flex gap-2"><dt className="text-muted-foreground w-20">company</dt><dd className="font-mono">{c.remote_company_id}</dd></div>}
                                    {c.email && <div className="flex gap-2"><dt className="text-muted-foreground w-20">email</dt><dd className="truncate flex-1">{c.email}</dd></div>}
                                    <div className="flex gap-2">
                                        <dt className="text-muted-foreground w-20">readiness</dt>
                                        <dd className="flex items-center gap-1">
                                            {c.is_ready
                                                ? <><CheckCircle2 className="h-3.5 w-3.5 text-emerald-600" /> {t.ready}</>
                                                : <><XCircle className="h-3.5 w-3.5 text-amber-600" /> {t.needs_config}</>}
                                        </dd>
                                    </div>
                                </dl>

                                <div className="mt-auto pt-3 border-t border-border flex items-center justify-between gap-2">
                                    <div className="flex items-center gap-2">
                                        {c.is_default && (
                                            <span className="inline-flex items-center gap-1 text-[11px] font-medium text-amber-600">
                                                <Star className="h-3.5 w-3.5 fill-current" /> default
                                            </span>
                                        )}
                                    </div>
                                    <div className="flex gap-1.5">
                                        {!c.is_default && permissions.update && (
                                            <Button
                                                type="button"
                                                onClick={() => router.post(`/admin/shipping/connections/${c.id}/default`, {}, { preserveScroll: true })}
                                                className="h-8 px-2 text-xs bg-transparent text-foreground border border-input hover:bg-muted/40"
                                            >
                                                {t.set_default}
                                            </Button>
                                        )}
                                        <Link
                                            href={`/admin/shipping/connections/${c.id}/edit`}
                                            className="inline-flex h-8 items-center rounded-md bg-primary px-3 text-xs font-medium text-primary-foreground hover:bg-primary/90"
                                        >
                                            <Edit className="h-3.5 w-3.5 me-1" /> edit
                                        </Link>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}
