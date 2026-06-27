import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import {
    Search, Plus, Edit, ChevronLeft, ChevronRight, MoreVertical,
    Store, Eye, Phone, Mail, Building2, Globe, FileText,
    UserCog, LayoutGrid, List, Link2, Check, Wallet,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import {
    DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem,
} from '@/Components/ui/DropdownMenu';
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
    const t = (name || '?').trim().split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
    return (
        <div className="grid h-10 w-10 place-items-center rounded-full bg-primary/10 text-primary text-xs font-semibold shrink-0">
            {t}
        </div>
    );
}

function StatusBadge({ active, on, off }) {
    return (
        <span className={cn(
            'inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-medium',
            active ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
                   : 'bg-rose-100 text-rose-700 border-rose-200',
        )}>
            {active ? on : off}
        </span>
    );
}

function CoverageCell({ row, t }) {
    return (
        <div className="space-y-1">
            <div className="flex flex-wrap gap-1">
                {row.countries.length === 0 && <span className="text-muted-foreground text-xs">—</span>}
                {row.countries.map((c, i) => (
                    <span key={i} className="rounded bg-muted/60 text-muted-foreground text-[10px] px-1.5 py-0.5">
                        {c.code || c.name}
                    </span>
                ))}
                {row.countries_more > 0 && (
                    <span className="rounded bg-muted/60 text-muted-foreground text-[10px] px-1.5 py-0.5">
                        +{row.countries_more}
                    </span>
                )}
            </div>
            {row.covers_all_cities
                ? <span className="inline-flex items-center gap-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-medium px-1.5 py-0.5"><Globe className="h-2.5 w-2.5" /> {t.covers_all_cities}</span>
                : <span className="text-[10px] text-muted-foreground">{row.city_count} {t.cities_covered}</span>}
        </div>
    );
}

function MerchantCard({ row, currency, permissions, t, onImpersonate }) {
    return (
        <Card className="overflow-hidden">
            <CardContent className="p-0">
                <div className="flex items-center gap-3 p-4 border-b border-border">
                    {row.image
                        ? <img src={row.image} alt="" className="h-10 w-10 rounded-full object-cover" />
                        : <Initials name={row.name} />}
                    <div className="min-w-0 flex-1">
                        <div className="font-medium truncate">{row.name || '—'}</div>
                        <div className="text-xs text-muted-foreground truncate">
                            #{row.unique_id || '—'} · {row.business_name || '—'}
                        </div>
                    </div>
                    {(permissions.view || permissions.update) && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="icon" className="h-8 w-8">
                                    <MoreVertical className="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-48">
                                <DropdownMenuItem onClick={() => { window.location.href = row.urls.invoice; }}>
                                    <FileText className="h-4 w-4 me-2" /> {t.invoice_generate}
                                </DropdownMenuItem>
                                {permissions.view && (
                                    <DropdownMenuItem onClick={() => { window.location.href = row.urls.view; }}>
                                        <Eye className="h-4 w-4 me-2" /> {t.view}
                                    </DropdownMenuItem>
                                )}
                                {permissions.update && (
                                    <>
                                        <DropdownMenuItem onClick={() => { window.location.href = row.urls.edit; }}>
                                            <Edit className="h-4 w-4 me-2" /> {t.edit}
                                        </DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => onImpersonate(row)} className="text-amber-600 focus:text-amber-700">
                                            <UserCog className="h-4 w-4 me-2" /> {t.impersonate}
                                        </DropdownMenuItem>
                                    </>
                                )}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                </div>
                <div className="space-y-1.5 p-4 text-sm">
                    <div className="flex items-center gap-2 text-muted-foreground">
                        <Phone className="h-3.5 w-3.5" /> {row.mobile || '—'}
                    </div>
                    <div className="flex items-center gap-2 text-muted-foreground">
                        <Mail className="h-3.5 w-3.5" /> {row.email || '—'}
                    </div>
                    <div className="flex items-center gap-2 text-muted-foreground">
                        <Building2 className="h-3.5 w-3.5" /> {row.hub_name || '—'}
                    </div>
                    <div className="pt-1"><CoverageCell row={row} t={t} /></div>
                </div>
                <div className="flex items-center justify-between bg-muted/30 px-4 py-3 border-t border-border">
                    <div className="flex flex-col gap-1">
                        <StatusBadge active={row.status === 1} on={t.status_active} off={t.status_inactive} />
                        <StatusBadge active={row.wallet_active} on={t.wallet_on} off={t.wallet_off} />
                    </div>
                    <div className="text-end">
                        <div className="text-[10px] uppercase tracking-wide text-muted-foreground font-medium">{t.current_balance}</div>
                        <div className="text-sm font-semibold"><Money value={row.computed_balance} currency={currency} /></div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

export default function Index({
    rows = [], pagination = {}, permissions = {},
    currency = '', urls = {}, t = {},
}) {
    const [view, setView] = React.useState('card');   // 'card' | 'list'
    const [search, setSearch] = React.useState('');
    const [copied, setCopied] = React.useState(false);

    const filtered = React.useMemo(() => {
        if (!search.trim()) return rows;
        const q = search.toLowerCase();
        return rows.filter((r) =>
            [r.name, r.email, r.business_name, r.mobile, r.unique_id, r.hub_name]
                .some((v) => String(v ?? '').toLowerCase().includes(q)),
        );
    }, [rows, search]);

    const goPage = (url) => url && router.get(url, {}, { preserveState: true });
    const onImpersonate = (row) => {
        const confirmMsg = (t.impersonate_confirm || 'Continue as :name?').replace(':name', row.impersonate_name || '');
        if (!window.confirm(confirmMsg)) return;
        const form = document.createElement('form');
        form.action = row.urls.impersonate;
        form.method = 'POST';
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = '_token'; inp.value = csrf;
        form.appendChild(inp);
        document.body.appendChild(form); form.submit();
    };

    const copyApplyLink = () => {
        navigator.clipboard?.writeText(urls.apply).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 1500);
        });
    };

    const showing = (t.showing_results || '')
        .replace(':from', pagination.from ?? 0)
        .replace(':to', pagination.to ?? 0)
        .replace(':total', pagination.total ?? 0);

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title, t.list]}>
            <Head title={`${t.title} · ${t.list}`} />

            <Card className="mb-5">
                <CardContent className="pt-6">
                    <div className="flex flex-wrap items-center gap-3">
                        <div className="text-lg font-semibold">{t.title}</div>
                        <div className="relative flex-1 min-w-[220px] max-w-md">
                            <Search className="absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder={t.search} className="ps-9" />
                        </div>
                        <div className="ms-auto flex items-center gap-2">
                            <div className="inline-flex rounded-md border border-input">
                                <button
                                    type="button"
                                    onClick={() => setView('card')}
                                    title={t.card_view}
                                    className={cn('px-3 py-1.5 text-xs font-medium transition-colors',
                                        view === 'card' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted')}
                                >
                                    <LayoutGrid className="h-4 w-4" />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setView('list')}
                                    title={t.list_view}
                                    className={cn('px-3 py-1.5 text-xs font-medium transition-colors',
                                        view === 'list' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted')}
                                >
                                    <List className="h-4 w-4" />
                                </button>
                            </div>
                            {permissions.create && (
                                <>
                                    <Button type="button" variant="outline" size="sm" onClick={copyApplyLink} title={t.copy_apply_link}>
                                        {copied ? <Check className="h-4 w-4 text-emerald-600" /> : <Link2 className="h-4 w-4" />}
                                    </Button>
                                    <a href={urls.create} className="inline-flex h-9 items-center justify-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors">
                                        <Plus className="h-4 w-4 me-1" /> {t.add}
                                    </a>
                                </>
                            )}
                        </div>
                    </div>
                    <div className="mt-3 text-xs text-muted-foreground flex items-center gap-2">
                        <Store className="h-3.5 w-3.5" /> {showing}
                    </div>
                </CardContent>
            </Card>

            {filtered.length === 0 ? (
                <Card>
                    <CardContent className="py-16 text-center text-muted-foreground">{t.no_rows}</CardContent>
                </Card>
            ) : view === 'card' ? (
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {filtered.map((r) => (
                        <MerchantCard key={r.id} row={r} currency={currency} permissions={permissions} t={t} onImpersonate={onImpersonate} />
                    ))}
                </div>
            ) : (
                <Card>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                        <th className="px-3 py-3 text-start">#</th>
                                        <th className="px-3 py-3 text-start">{t.unique_id}</th>
                                        <th className="px-3 py-3 text-start">{t.title}</th>
                                        <th className="px-3 py-3 text-start">{t.hub}</th>
                                        <th className="px-3 py-3 text-start">{t.business_name}</th>
                                        <th className="px-3 py-3 text-start">{t.geography}</th>
                                        <th className="px-3 py-3 text-start">{t.phone}</th>
                                        <th className="px-3 py-3 text-start">{t.status}</th>
                                        <th className="px-3 py-3 text-end">{t.current_balance}</th>
                                        {(permissions.view || permissions.update) && <th className="px-3 py-3 text-end">{t.actions}</th>}
                                    </tr>
                                </thead>
                                <tbody>
                                    {filtered.map((r, idx) => (
                                        <tr key={r.id} className="border-b border-border last:border-0 hover:bg-muted/20 transition-colors">
                                            <td className="px-3 py-3 text-muted-foreground">{(pagination.from || 1) + idx}</td>
                                            <td className="px-3 py-3 font-mono text-xs">{r.unique_id || '—'}</td>
                                            <td className="px-3 py-3">
                                                <div className="flex items-center gap-3">
                                                    {r.image
                                                        ? <img src={r.image} alt="" className="h-9 w-9 rounded-full object-cover" />
                                                        : <Initials name={r.name} />}
                                                    <div className="min-w-0">
                                                        <div className="font-medium truncate">{r.name || '—'}</div>
                                                        <div className="text-xs text-muted-foreground truncate">{r.email || '—'}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-3 py-3">{r.hub_name || '—'}</td>
                                            <td className="px-3 py-3">{r.business_name || '—'}</td>
                                            <td className="px-3 py-3"><CoverageCell row={r} t={t} /></td>
                                            <td className="px-3 py-3 text-muted-foreground">{r.mobile || '—'}</td>
                                            <td className="px-3 py-3">
                                                <div className="flex flex-col gap-1">
                                                    <StatusBadge active={r.status === 1} on={t.status_active} off={t.status_inactive} />
                                                    <StatusBadge active={r.wallet_active} on={t.wallet_on} off={t.wallet_off} />
                                                </div>
                                            </td>
                                            <td className="px-3 py-3 text-end font-medium"><Money value={r.computed_balance} currency={currency} /></td>
                                            {(permissions.view || permissions.update) && (
                                                <td className="px-3 py-3 text-end">
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" size="icon" className="h-8 w-8">
                                                                <MoreVertical className="h-4 w-4" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end" className="w-48">
                                                            <DropdownMenuItem onClick={() => { window.location.href = r.urls.invoice; }}>
                                                                <FileText className="h-4 w-4 me-2" /> {t.invoice_generate}
                                                            </DropdownMenuItem>
                                                            {permissions.view && (
                                                                <DropdownMenuItem onClick={() => { window.location.href = r.urls.view; }}>
                                                                    <Eye className="h-4 w-4 me-2" /> {t.view}
                                                                </DropdownMenuItem>
                                                            )}
                                                            {permissions.update && (
                                                                <>
                                                                    <DropdownMenuItem onClick={() => { window.location.href = r.urls.edit; }}>
                                                                        <Edit className="h-4 w-4 me-2" /> {t.edit}
                                                                    </DropdownMenuItem>
                                                                    <DropdownMenuItem onClick={() => onImpersonate(r)} className="text-amber-600 focus:text-amber-700">
                                                                        <UserCog className="h-4 w-4 me-2" /> {t.impersonate}
                                                                    </DropdownMenuItem>
                                                                </>
                                                            )}
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            )}

            {pagination.last_page > 1 && (
                <div className="mt-4 flex items-center justify-between text-sm">
                    <div className="text-muted-foreground">{showing}</div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" disabled={!pagination.prev_url} onClick={() => goPage(pagination.prev_url)}>
                            <ChevronLeft className="h-4 w-4 me-1" /> {t.prev || 'Prev'}
                        </Button>
                        <span className="text-xs text-muted-foreground">{pagination.current_page} / {pagination.last_page}</span>
                        <Button variant="outline" size="sm" disabled={!pagination.next_url} onClick={() => goPage(pagination.next_url)}>
                            {t.next || 'Next'} <ChevronRight className="h-4 w-4 ms-1" />
                        </Button>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
