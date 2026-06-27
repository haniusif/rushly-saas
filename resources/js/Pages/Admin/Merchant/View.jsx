import * as React from 'react';
import { Head } from '@inertiajs/react';
import {
    Edit, ExternalLink, Mail, Phone, MapPin, Building2, Globe,
    Wallet, FileText, Store, Receipt, UserCog, Truck, ArrowLeft,
    Check, CreditCard,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { cn } from '@/lib/utils';

function Money({ value, currency }) {
    const n = Number(value || 0);
    return (
        <span className="tabular-nums">
            <span className="text-muted-foreground text-xs me-0.5">{currency}</span>
            {n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
        </span>
    );
}

function Initials({ name }) {
    const text = (name || '?').trim().split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
    return (
        <div className="grid h-20 w-20 place-items-center rounded-full bg-primary/10 text-primary text-2xl font-semibold shrink-0">
            {text}
        </div>
    );
}

function Row({ label, value, children }) {
    return (
        <div className="flex items-start justify-between gap-3 border-b border-border py-2.5 text-sm last:border-0">
            <span className="text-muted-foreground text-xs uppercase tracking-wide font-medium pt-0.5">{label}</span>
            <span className="text-end font-medium">{children ?? value ?? '—'}</span>
        </div>
    );
}

function LinkTile({ href, icon: Icon, label }) {
    return (
        <a href={href} className="flex items-center gap-3 rounded-md border border-border bg-card p-3 text-sm font-medium hover:bg-accent transition-colors">
            <Icon className="h-4 w-4 text-primary" />
            <span>{label}</span>
            <ExternalLink className="ms-auto h-3.5 w-3.5 text-muted-foreground" />
        </a>
    );
}

export default function View({ merchant = {}, shops = [], currency = '', permissions = {}, urls = {}, t = {} }) {
    const m = merchant;
    const user = m.user || {};
    const isActive = m.status === 1;

    const impersonate = () => {
        if (!window.confirm(t.impersonate_confirm)) return;
        const form = document.createElement('form');
        form.action = urls.impersonate; form.method = 'POST';
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = '_token'; inp.value = csrf;
        form.appendChild(inp);
        document.body.appendChild(form); form.submit();
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title_index, m.business_name || '—']}>
            <Head title={`${t.title} · ${m.business_name || ''}`} />

            <div className="mb-4 flex items-center justify-between gap-2">
                <a href={urls.index} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.title_index}
                </a>
                <div className="flex items-center gap-2">
                    {permissions.impersonate && (
                        <Button type="button" variant="outline" onClick={impersonate}>
                            <UserCog className="h-4 w-4 me-1 text-amber-600" /> {t.impersonate}
                        </Button>
                    )}
                    {permissions.edit && (
                        <a href={urls.edit} className="inline-flex h-9 items-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90">
                            <Edit className="h-4 w-4 me-1" /> {t.edit}
                        </a>
                    )}
                </div>
            </div>

            <div className="grid gap-5 lg:grid-cols-3">
                {/* Identity */}
                <Card className="lg:col-span-1">
                    <CardContent className="pt-6">
                        <div className="flex flex-col items-center text-center gap-3">
                            {user.image
                                ? <img src={user.image} alt="" className="h-20 w-20 rounded-full object-cover ring-2 ring-background shadow" />
                                : <Initials name={user.name} />}
                            <div>
                                <div className="font-semibold text-lg">{user.name || '—'}</div>
                                <div className="text-sm text-muted-foreground font-mono">#{m.unique_id || '—'}</div>
                                <span className={cn(
                                    'mt-2 inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                    isActive ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-rose-100 text-rose-700 border-rose-200',
                                )}>
                                    {isActive ? t.active : t.inactive}
                                </span>
                            </div>
                        </div>

                        <div className="mt-5">
                            <Row label={t.business_name} value={m.business_name} />
                            <Row label={t.email || 'Email'} value={
                                user.email && <span className="inline-flex items-center gap-1"><Mail className="h-3 w-3" /> {user.email}</span>
                            } />
                            <Row label={t.mobile || 'Mobile'} value={
                                user.mobile && <span className="inline-flex items-center gap-1"><Phone className="h-3 w-3" /> {user.mobile}</span>
                            } />
                            <Row label={t.hub} value={user.hub} />
                            <Row label={t.address} value={user.address} />
                        </div>
                    </CardContent>
                </Card>

                <div className="lg:col-span-2 space-y-5">
                    {/* Finance + coverage */}
                    <div className="grid gap-5 md:grid-cols-2">
                        <Card>
                            <CardContent className="pt-6">
                                <div className="mb-3 flex items-center gap-2 text-sm font-semibold">
                                    <Wallet className="h-4 w-4 text-emerald-600" /> {t.finance || 'Finance'}
                                </div>
                                <Row label={t.opening_balance}><Money value={m.opening_balance} currency={currency} /></Row>
                                <Row label={t.current_balance}><Money value={m.current_balance} currency={currency} /></Row>
                                <Row label={t.computed_balance}>
                                    <span className="font-semibold"><Money value={m.computed_balance} currency={currency} /></span>
                                </Row>
                                <Row label={t.vat} value={`${m.vat}%`} />
                                <Row label={t.cod_charges} value={m.cod_charges} />
                                <Row label={t.payment_period} value={m.payment_period} />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="pt-6">
                                <div className="mb-3 flex items-center gap-2 text-sm font-semibold">
                                    <Globe className="h-4 w-4 text-sky-600" /> {t.coverage}
                                </div>
                                <div className="flex flex-wrap gap-1.5 mb-3">
                                    {m.countries?.length
                                        ? m.countries.map((c, i) => (
                                            <span key={i} className="rounded bg-muted/60 text-muted-foreground text-[11px] px-2 py-0.5 font-medium">
                                                {c.code || c.name}
                                            </span>
                                        ))
                                        : <span className="text-xs text-muted-foreground">—</span>}
                                </div>
                                {m.covers_all_cities
                                    ? <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 text-emerald-700 text-[11px] font-medium px-2 py-0.5">
                                        <Check className="h-3 w-3" /> {t.covers_all_cities}
                                    </span>
                                    : <span className="text-sm text-muted-foreground">{m.city_count} {t.cities_covered}</span>}

                                <div className="mt-4 mb-2 text-xs uppercase tracking-wide text-muted-foreground font-medium">{t.services}</div>
                                {m.services?.length
                                    ? <div className="flex flex-wrap gap-1.5">
                                        {m.services.map((s) => (
                                            <span key={s} className="rounded-full bg-violet-100 text-violet-700 text-[11px] font-medium px-2 py-0.5">{(t.service_labels && t.service_labels[s]) || s}</span>
                                        ))}
                                    </div>
                                    : <span className="text-xs text-muted-foreground">—</span>}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Sub-page links */}
                    <Card>
                        <CardContent className="pt-6">
                            <div className="mb-3 text-sm font-semibold">{t.manage || 'Manage'}</div>
                            <div className="grid gap-2 sm:grid-cols-2">
                                <LinkTile href={urls.shops}    icon={Store}      label={t.shops} />
                                <LinkTile href={urls.payments} icon={CreditCard} label={t.payments} />
                                <LinkTile href={urls.invoices} icon={Receipt}    label={t.invoices} />
                                <LinkTile href={urls.delivery} icon={Truck}      label={t.delivery} />
                            </div>
                        </CardContent>
                    </Card>

                    {/* KYC docs */}
                    {(m.nid_url || m.trade_url) && (
                        <Card>
                            <CardContent className="pt-6">
                                <div className="mb-3 flex items-center gap-2 text-sm font-semibold">
                                    <FileText className="h-4 w-4 text-muted-foreground" /> {t.kyc_documents || 'KYC documents'}
                                </div>
                                <div className="grid gap-3 sm:grid-cols-2">
                                    {m.nid_url && (
                                        <a href={m.nid_url} target="_blank" rel="noreferrer" className="block rounded-md border border-border overflow-hidden hover:border-primary transition-colors">
                                            <img src={m.nid_url} alt={t.nid} className="aspect-video w-full object-cover bg-muted/40" />
                                            <div className="p-2 text-xs font-medium">{t.nid}</div>
                                        </a>
                                    )}
                                    {m.trade_url && (
                                        <a href={m.trade_url} target="_blank" rel="noreferrer" className="block rounded-md border border-border overflow-hidden hover:border-primary transition-colors">
                                            <img src={m.trade_url} alt={t.trade} className="aspect-video w-full object-cover bg-muted/40" />
                                            <div className="p-2 text-xs font-medium">{t.trade}</div>
                                        </a>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Shops snapshot */}
                    <Card>
                        <CardContent className="pt-6">
                            <div className="mb-3 flex items-center justify-between">
                                <div className="flex items-center gap-2 text-sm font-semibold">
                                    <Store className="h-4 w-4" /> {t.shops}
                                </div>
                                <a href={urls.shops} className="text-xs text-primary hover:underline inline-flex items-center gap-1">
                                    {t.manage || 'Manage'} <ExternalLink className="h-3 w-3" />
                                </a>
                            </div>
                            {shops.length === 0
                                ? <div className="text-xs text-muted-foreground py-2">{t.no_shops}</div>
                                : (
                                    <div className="divide-y divide-border">
                                        {shops.map((s) => (
                                            <div key={s.id} className="flex items-center justify-between py-2 text-sm">
                                                <div className="min-w-0">
                                                    <div className="font-medium truncate">{s.name || `#${s.id}`}</div>
                                                    {s.address && <div className="text-xs text-muted-foreground truncate">{s.address}</div>}
                                                </div>
                                                {s.is_default && (
                                                    <span className="rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5">
                                                        {t.default}
                                                    </span>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
