import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    Plus, Building2, Link as LinkIcon, Package, MoreHorizontal,
    Pencil, Trash2, RefreshCw, ExternalLink, LogIn, List, LayoutGrid,
    Search, X,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { cn } from '@/lib/utils';

const VIEW_KEY = 'superadmin.company.view';

/**
 * The per-row actions dropdown, shared by the list (table) and card views.
 * Only one menu is open at a time (keyed by row.id), so a single menuRef for
 * click-outside is enough across both views.
 */
function RowActions({ row, open, setOpenMenu, menuRef, permissions, t, onImpersonate, onDelete, align = 'end' }) {
    return (
        <div className="relative inline-block">
            <button
                type="button"
                onClick={() => setOpenMenu(open ? null : row.id)}
                className="inline-flex items-center justify-center w-9 h-9 rounded-lg hover:bg-muted text-muted-foreground border-0 bg-transparent"
                aria-label="actions"
            >
                <MoreHorizontal className="h-4 w-4" />
            </button>
            {open && (
                <div
                    ref={menuRef}
                    className={cn(
                        'absolute top-11 z-30 min-w-[10rem] rounded-md border border-border bg-popover shadow-md overflow-hidden text-sm',
                        align === 'end' ? 'end-0' : 'start-0'
                    )}
                >
                    {permissions.impersonate && (
                        <button
                            type="button"
                            onClick={() => { setOpenMenu(null); onImpersonate(row); }}
                            className="flex w-full items-center gap-2 px-3 py-2 text-primary hover:bg-muted"
                        >
                            <LogIn className="h-3.5 w-3.5" /> {t.login_as}
                        </button>
                    )}
                    {permissions.update && (
                        <a href={row.urls.edit} className="flex items-center gap-2 px-3 py-2 hover:bg-muted">
                            <Pencil className="h-3.5 w-3.5" /> {t.edit}
                        </a>
                    )}
                    {permissions.delete && row.company?.id !== 1 && (
                        <button
                            type="button"
                            onClick={() => { setOpenMenu(null); onDelete(row); }}
                            className="flex w-full items-center gap-2 px-3 py-2 text-rose-600 hover:bg-muted"
                        >
                            <Trash2 className="h-3.5 w-3.5" /> {t.delete}
                        </button>
                    )}
                </div>
            )}
        </div>
    );
}

/**
 * Company (tenants) list for the super-admin. Port of the old
 * backend/super-admin/company/index.blade.php table into AdminLayout.
 * Props are flattened by CompanyController::index so this component only
 * consumes primitives.
 */
export default function CompanyIndex({ rows = [], pagination = {}, permissions = {}, urls = {}, t = {}, filters = {}, planOptions = [] }) {
    const [openMenu, setOpenMenu] = React.useState(null);
    const menuRef = React.useRef(null);

    // Search + filters. Inputs are controlled locally; we push a partial reload
    // to the server (search is debounced). preserveState keeps focus/typing.
    const [q, setQ] = React.useState(filters.q || '');
    const [plan, setPlan] = React.useState(filters.plan ? String(filters.plan) : '');
    const [status, setStatus] = React.useState(filters.status !== '' && filters.status != null ? String(filters.status) : '');
    const searchTimer = React.useRef(null);

    const applyFilters = (override = {}) => {
        const params = { q, plan, status, ...override };
        Object.keys(params).forEach((k) => { if (params[k] === '' || params[k] == null) delete params[k]; });
        router.get(urls.index, params, { preserveState: true, preserveScroll: true, replace: true });
    };
    const onSearchChange = (v) => {
        setQ(v);
        if (searchTimer.current) clearTimeout(searchTimer.current);
        searchTimer.current = setTimeout(() => applyFilters({ q: v }), 350);
    };
    const onPlanChange = (v) => { setPlan(v); applyFilters({ plan: v }); };
    const onStatusChange = (v) => { setStatus(v); applyFilters({ status: v }); };
    const clearFilters = () => {
        setQ(''); setPlan(''); setStatus('');
        router.get(urls.index, {}, { preserveState: true, preserveScroll: true, replace: true });
    };
    const hasFilters = !!(q || plan || status);

    // View mode (list | cards) — a per-viewer convenience, remembered locally.
    const [view, setView] = React.useState('list');
    React.useEffect(() => {
        try { const v = localStorage.getItem(VIEW_KEY); if (v === 'cards' || v === 'list') setView(v); } catch (e) { /* ignore */ }
    }, []);
    const chooseView = (v) => {
        setView(v);
        setOpenMenu(null);
        try { localStorage.setItem(VIEW_KEY, v); } catch (e) { /* ignore */ }
    };

    React.useEffect(() => {
        const onDoc = (e) => { if (menuRef.current && !menuRef.current.contains(e.target)) setOpenMenu(null); };
        document.addEventListener('mousedown', onDoc);
        return () => document.removeEventListener('mousedown', onDoc);
    }, []);

    const onDelete = (row) => {
        if (typeof window !== 'undefined' && !window.confirm(t.confirm_delete)) return;
        router.delete(row.urls.delete, { preserveScroll: true });
    };

    const onImpersonate = (row) => {
        if (typeof window !== 'undefined' && !window.confirm(t.impersonate_confirm)) return;
        // Native form POST (not Inertia): impersonate() redirects across hosts to
        // the tenant subdomain, which the browser must follow directly.
        const form = document.createElement('form');
        form.action = row.urls.impersonate;
        form.method = 'POST';
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = '_token'; inp.value = csrf;
        form.appendChild(inp);
        document.body.appendChild(form);
        form.submit();
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.breadcrumb, t.title]}>
            <Head title={t.title} />

            <Card>
                <CardContent className="p-0">
                    {/* Header — AdminLayout already renders the H1 title,
                        so we only show the row-count meta + primary CTA here. */}
                    <div className="flex items-center justify-between gap-3 px-5 py-4 border-b border-border">
                        <p className="text-xs text-muted-foreground m-0 inline-flex items-center gap-1.5">
                            <Building2 className="h-3.5 w-3.5" />
                            {pagination.total ?? 0} {t.count_suffix}
                        </p>
                        <div className="flex items-center gap-2">
                            {/* View toggle: list / cards */}
                            <div className="inline-flex items-center rounded-lg border border-border p-0.5" role="group" aria-label={t.view ?? 'View'}>
                                <button
                                    type="button"
                                    onClick={() => chooseView('list')}
                                    aria-pressed={view === 'list'}
                                    title={t.list_view ?? 'List view'}
                                    className={cn(
                                        'inline-flex items-center justify-center h-7 w-7 rounded-md border-0 bg-transparent',
                                        view === 'list' ? 'bg-muted text-foreground' : 'text-muted-foreground hover:text-foreground'
                                    )}
                                >
                                    <List className="h-4 w-4" />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => chooseView('cards')}
                                    aria-pressed={view === 'cards'}
                                    title={t.card_view ?? 'Card view'}
                                    className={cn(
                                        'inline-flex items-center justify-center h-7 w-7 rounded-md border-0 bg-transparent',
                                        view === 'cards' ? 'bg-muted text-foreground' : 'text-muted-foreground hover:text-foreground'
                                    )}
                                >
                                    <LayoutGrid className="h-4 w-4" />
                                </button>
                            </div>
                            {permissions.create && (
                                <Button asChild size="sm">
                                    <a href={urls.create}>
                                        <Plus className="h-4 w-4 me-1" />
                                        {t.add}
                                    </a>
                                </Button>
                            )}
                        </div>
                    </div>

                    {/* Search + filters */}
                    <div className="flex flex-col sm:flex-row sm:items-center gap-2 px-5 py-3 border-b border-border">
                        <div className="relative flex-1 min-w-0">
                            <Search className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                            <input
                                type="text"
                                value={q}
                                onChange={(e) => onSearchChange(e.target.value)}
                                placeholder={t.search}
                                className="w-full h-9 ps-9 pe-3 text-sm rounded-md border border-border bg-background text-foreground placeholder:text-muted-foreground/60 focus:outline-none focus:ring-2 focus:ring-primary/30"
                            />
                        </div>
                        <select
                            value={plan}
                            onChange={(e) => onPlanChange(e.target.value)}
                            className="h-9 px-3 text-sm rounded-md border border-border bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30"
                        >
                            <option value="">{t.all_plans}</option>
                            {planOptions.map((o) => (
                                <option key={o.value} value={o.value}>{o.label}</option>
                            ))}
                        </select>
                        <select
                            value={status}
                            onChange={(e) => onStatusChange(e.target.value)}
                            className="h-9 px-3 text-sm rounded-md border border-border bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30"
                        >
                            <option value="">{t.all_statuses}</option>
                            <option value="1">{t.active}</option>
                            <option value="0">{t.inactive}</option>
                        </select>
                        {hasFilters && (
                            <button
                                type="button"
                                onClick={clearFilters}
                                className="inline-flex items-center gap-1 h-9 px-3 text-sm rounded-md border border-border text-muted-foreground hover:text-foreground hover:bg-muted"
                            >
                                <X className="h-3.5 w-3.5" /> {t.clear}
                            </button>
                        )}
                    </div>

                    {/* List (table) view */}
                    {view === 'list' && (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/40">
                                <tr className="text-start text-[11px] uppercase tracking-wider text-muted-foreground">
                                    <th className="px-5 py-3 font-medium w-12">#</th>
                                    <th className="px-5 py-3 font-medium">{t.name}</th>
                                    <th className="px-5 py-3 font-medium">{t.domain}</th>
                                    <th className="px-5 py-3 font-medium">{t.owner}</th>
                                    <th className="px-5 py-3 font-medium">{t.plan}</th>
                                    <th className="px-5 py-3 font-medium">{t.subscription}</th>
                                    <th className="px-5 py-3 font-medium">{t.status}</th>
                                    {(permissions.update || permissions.delete || permissions.impersonate) && (
                                        <th className="px-5 py-3 font-medium text-end">{t.actions}</th>
                                    )}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {rows.length === 0 ? (
                                    <tr>
                                        <td colSpan={8} className="px-5 py-16 text-center">
                                            <div className="text-muted-foreground/40 mb-2 flex justify-center">
                                                <Building2 className="h-10 w-10" />
                                            </div>
                                            <p className="text-sm text-muted-foreground m-0">{t.no_data}</p>
                                        </td>
                                    </tr>
                                ) : rows.map((row, i) => {
                                    const menuOpen = openMenu === row.id;
                                    const rowNumber = (pagination.from ?? 1) + i;
                                    return (
                                        <tr key={row.id} className="hover:bg-muted/30 transition-colors">
                                            <td className="px-5 py-3 text-muted-foreground tabular-nums">{rowNumber}</td>

                                            {/* Company */}
                                            <td className="px-5 py-3">
                                                <div className="flex items-center gap-3">
                                                    {row.company?.logo ? (
                                                        <img src={row.company.logo} alt="" className="w-9 h-9 rounded-lg object-cover bg-muted" />
                                                    ) : (
                                                        <span className="grid w-9 h-9 place-items-center rounded-lg bg-muted text-muted-foreground text-xs font-semibold">
                                                            {(row.company?.name || '·').charAt(0).toUpperCase()}
                                                        </span>
                                                    )}
                                                    <div className="min-w-0">
                                                        <div className="font-medium text-foreground truncate">
                                                            {row.company?.name ?? '—'}
                                                        </div>
                                                        {row.plan && (
                                                            <div className="text-xs text-muted-foreground flex items-center gap-1">
                                                                <Package className="h-3 w-3" />
                                                                {row.plan.module_count} {t.modules}
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </td>

                                            {/* Domains */}
                                            <td className="px-5 py-3 text-xs">
                                                {row.domains.length === 0 ? (
                                                    <span className="text-muted-foreground/60">—</span>
                                                ) : (
                                                    <div className="flex flex-col gap-0.5">
                                                        {row.domains.map((d) => (
                                                            <a
                                                                key={d.id ?? d.name}
                                                                href={d.url}
                                                                target="_blank"
                                                                rel="noreferrer"
                                                                className="inline-flex items-center gap-1 text-primary hover:underline"
                                                            >
                                                                <ExternalLink className="h-3 w-3" />
                                                                {d.name}
                                                            </a>
                                                        ))}
                                                    </div>
                                                )}
                                            </td>

                                            {/* Owner */}
                                            <td className="px-5 py-3">
                                                <div className="flex items-center gap-3">
                                                    {row.avatar ? (
                                                        <img src={row.avatar} alt="" className="w-9 h-9 rounded-full object-cover bg-muted" />
                                                    ) : (
                                                        <span className="grid w-9 h-9 place-items-center rounded-full bg-muted text-muted-foreground text-xs font-semibold">
                                                            {(row.name || '·').charAt(0).toUpperCase()}
                                                        </span>
                                                    )}
                                                    <div className="min-w-0">
                                                        <div className="font-medium text-foreground truncate">{row.name}</div>
                                                        <div className="text-xs text-muted-foreground truncate">{row.email}</div>
                                                        {row.mobile && (
                                                            <div className="text-xs text-muted-foreground/70 tabular-nums truncate">{row.mobile}</div>
                                                        )}
                                                    </div>
                                                </div>
                                            </td>

                                            {/* Plan */}
                                            <td className="px-5 py-3">
                                                {row.plan ? (
                                                    <span className="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-primary/10 text-primary">
                                                        {row.plan.name}
                                                    </span>
                                                ) : (
                                                    <span className="text-xs text-muted-foreground/60">—</span>
                                                )}
                                            </td>

                                            {/* Subscription */}
                                            <td className="px-5 py-3">
                                                <div className="flex flex-col gap-1.5">
                                                    {row.subscription.active ? (
                                                        <span className="inline-flex items-center gap-1.5 text-xs text-emerald-700 dark:text-emerald-400">
                                                            <span className="w-1.5 h-1.5 rounded-full bg-emerald-500" />
                                                            {t.remaining} {row.subscription.remaining_days} {t.days}
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300 w-fit">
                                                            {t.expired}
                                                        </span>
                                                    )}
                                                    {permissions.subscribe && (
                                                        <a
                                                            href={row.urls.subscribe}
                                                            className="inline-flex items-center justify-center h-7 px-2.5 text-xs font-medium text-primary bg-primary/10 hover:bg-primary/15 rounded-md w-fit gap-1"
                                                        >
                                                            <RefreshCw className="h-3 w-3" />
                                                            {t.subscribe_now}
                                                        </a>
                                                    )}
                                                </div>
                                            </td>

                                            {/* Status */}
                                            <td
                                                className="px-5 py-3"
                                                // status_html is server-rendered Blade output — trusted admin surface.
                                                dangerouslySetInnerHTML={{ __html: row.status_html || '' }}
                                            />

                                            {/* Actions */}
                                            {(permissions.update || permissions.delete || permissions.impersonate) && (
                                                <td className="px-5 py-3 text-end">
                                                    <RowActions
                                                        row={row}
                                                        open={menuOpen}
                                                        setOpenMenu={setOpenMenu}
                                                        menuRef={menuRef}
                                                        permissions={permissions}
                                                        t={t}
                                                        onImpersonate={onImpersonate}
                                                        onDelete={onDelete}
                                                    />
                                                </td>
                                            )}
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                    )}

                    {/* Card view */}
                    {view === 'cards' && (
                        rows.length === 0 ? (
                            <div className="px-5 py-16 text-center">
                                <div className="text-muted-foreground/40 mb-2 flex justify-center">
                                    <Building2 className="h-10 w-10" />
                                </div>
                                <p className="text-sm text-muted-foreground m-0">{t.no_data}</p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 p-5">
                                {rows.map((row) => {
                                    const menuOpen = openMenu === row.id;
                                    return (
                                        <div key={row.id} className="relative flex flex-col gap-3 rounded-xl border border-border bg-card p-4 hover:shadow-sm transition-shadow">
                                            {/* Header: company + actions */}
                                            <div className="flex items-start justify-between gap-2">
                                                <div className="flex items-center gap-3 min-w-0">
                                                    {row.company?.logo ? (
                                                        <img src={row.company.logo} alt="" className="w-11 h-11 rounded-lg object-cover bg-muted" />
                                                    ) : (
                                                        <span className="grid w-11 h-11 place-items-center rounded-lg bg-muted text-muted-foreground text-sm font-semibold">
                                                            {(row.company?.name || '·').charAt(0).toUpperCase()}
                                                        </span>
                                                    )}
                                                    <div className="min-w-0">
                                                        <div className="font-semibold text-foreground truncate">{row.company?.name ?? '—'}</div>
                                                        {row.plan ? (
                                                            <span className="mt-0.5 inline-flex items-center px-2 py-0.5 text-[11px] font-medium rounded-full bg-primary/10 text-primary">
                                                                {row.plan.name}
                                                            </span>
                                                        ) : (
                                                            <span className="text-xs text-muted-foreground/60">—</span>
                                                        )}
                                                    </div>
                                                </div>
                                                {(permissions.update || permissions.delete || permissions.impersonate) && (
                                                    <RowActions
                                                        row={row}
                                                        open={menuOpen}
                                                        setOpenMenu={setOpenMenu}
                                                        menuRef={menuRef}
                                                        permissions={permissions}
                                                        t={t}
                                                        onImpersonate={onImpersonate}
                                                        onDelete={onDelete}
                                                    />
                                                )}
                                            </div>

                                            {/* Owner */}
                                            <div className="flex items-center gap-2.5 border-t border-border pt-3">
                                                {row.avatar ? (
                                                    <img src={row.avatar} alt="" className="w-8 h-8 rounded-full object-cover bg-muted" />
                                                ) : (
                                                    <span className="grid w-8 h-8 place-items-center rounded-full bg-muted text-muted-foreground text-xs font-semibold">
                                                        {(row.name || '·').charAt(0).toUpperCase()}
                                                    </span>
                                                )}
                                                <div className="min-w-0">
                                                    <div className="text-sm font-medium text-foreground truncate">{row.name}</div>
                                                    <div className="text-xs text-muted-foreground truncate">{row.email}</div>
                                                </div>
                                            </div>

                                            {/* Domains */}
                                            <div className="text-xs">
                                                <div className="text-[11px] uppercase tracking-wider text-muted-foreground/70 mb-1">{t.domain}</div>
                                                {row.domains.length === 0 ? (
                                                    <span className="text-muted-foreground/60">—</span>
                                                ) : (
                                                    <div className="flex flex-col gap-0.5">
                                                        {row.domains.map((d) => (
                                                            <a
                                                                key={d.id ?? d.name}
                                                                href={d.url}
                                                                target="_blank"
                                                                rel="noreferrer"
                                                                className="inline-flex items-center gap-1 text-primary hover:underline truncate"
                                                            >
                                                                <ExternalLink className="h-3 w-3 shrink-0" />
                                                                <span className="truncate">{d.name}</span>
                                                            </a>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>

                                            {/* Subscription + status */}
                                            <div className="flex items-center justify-between gap-2 border-t border-border pt-3 mt-auto">
                                                {row.subscription.active ? (
                                                    <span className="inline-flex items-center gap-1.5 text-xs text-emerald-700 dark:text-emerald-400">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-500" />
                                                        {t.remaining} {row.subscription.remaining_days} {t.days}
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300">
                                                        {t.expired}
                                                    </span>
                                                )}
                                                {permissions.subscribe && !row.subscription.active && (
                                                    <a
                                                        href={row.urls.subscribe}
                                                        className="inline-flex items-center justify-center h-7 px-2.5 text-xs font-medium text-primary bg-primary/10 hover:bg-primary/15 rounded-md gap-1"
                                                    >
                                                        <RefreshCw className="h-3 w-3" />
                                                        {t.subscribe_now}
                                                    </a>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )
                    )}

                    {/* Pagination */}
                    {pagination.last_page > 1 && (
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-5 py-4 border-t border-border">
                            <p className="text-xs text-muted-foreground m-0">
                                {pagination.from}–{pagination.to} / {pagination.total}
                            </p>
                            <div className="flex items-center gap-1">
                                {(pagination.links || []).map((l, i) => (
                                    <a
                                        key={i}
                                        href={l.url || '#'}
                                        onClick={(e) => { if (!l.url) e.preventDefault(); }}
                                        className={cn(
                                            'inline-flex items-center justify-center h-8 min-w-8 px-2 text-xs rounded-md border border-border',
                                            l.active
                                                ? 'bg-primary text-primary-foreground border-primary'
                                                : l.url
                                                    ? 'text-foreground hover:bg-muted'
                                                    : 'text-muted-foreground/40 pointer-events-none'
                                        )}
                                        dangerouslySetInnerHTML={{ __html: l.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
