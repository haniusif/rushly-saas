import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    Plus, Building2, Link as LinkIcon, Package, MoreHorizontal,
    Pencil, Trash2, RefreshCw, ExternalLink,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { cn } from '@/lib/utils';

/**
 * Company (tenants) list for the super-admin. Port of the old
 * backend/super-admin/company/index.blade.php table into AdminLayout.
 * Props are flattened by CompanyController::index so this component only
 * consumes primitives.
 */
export default function CompanyIndex({ rows = [], pagination = {}, permissions = {}, urls = {}, t = {} }) {
    const [openMenu, setOpenMenu] = React.useState(null);
    const menuRef = React.useRef(null);

    React.useEffect(() => {
        const onDoc = (e) => { if (menuRef.current && !menuRef.current.contains(e.target)) setOpenMenu(null); };
        document.addEventListener('mousedown', onDoc);
        return () => document.removeEventListener('mousedown', onDoc);
    }, []);

    const onDelete = (row) => {
        if (typeof window !== 'undefined' && !window.confirm(t.confirm_delete)) return;
        router.delete(row.urls.delete, { preserveScroll: true });
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.breadcrumb, t.title]}>
            <Head title={t.title} />

            <Card>
                <CardContent className="p-0">
                    {/* Header */}
                    <div className="flex items-center justify-between gap-3 px-5 py-4 border-b border-border">
                        <div>
                            <h1 className="text-lg font-semibold text-foreground m-0 flex items-center gap-2">
                                <Building2 className="h-4 w-4 text-primary" />
                                {t.title}
                            </h1>
                            <p className="text-xs text-muted-foreground mt-0.5">
                                {pagination.total ?? 0} {t.count_suffix}
                            </p>
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

                    {/* Table */}
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
                                    {(permissions.update || permissions.delete) && (
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
                                            {(permissions.update || permissions.delete) && (
                                                <td className="px-5 py-3 text-end relative">
                                                    <button
                                                        type="button"
                                                        onClick={() => setOpenMenu(menuOpen ? null : row.id)}
                                                        className="inline-flex items-center justify-center w-9 h-9 rounded-lg hover:bg-muted text-muted-foreground border-0 bg-transparent"
                                                        aria-label="actions"
                                                    >
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </button>
                                                    {menuOpen && (
                                                        <div
                                                            ref={menuRef}
                                                            className="absolute end-5 top-11 z-30 min-w-[10rem] rounded-md border border-border bg-popover shadow-md overflow-hidden text-sm"
                                                        >
                                                            {permissions.update && (
                                                                <a
                                                                    href={row.urls.edit}
                                                                    className="flex items-center gap-2 px-3 py-2 hover:bg-muted"
                                                                >
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
                                                </td>
                                            )}
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>

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
