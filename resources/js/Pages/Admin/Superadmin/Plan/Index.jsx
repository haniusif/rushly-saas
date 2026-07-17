import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import {
    Plus, Layers, Package, MoreHorizontal, Pencil, Trash2, Info,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { cn } from '@/lib/utils';

/**
 * Subscription plans list for the super-admin. Port of the old
 * backend/super-admin/plan/index.blade.php table into AdminLayout.
 * The row shape is flattened server-side; JSX only handles primitives.
 */
export default function PlanIndex({ rows = [], pagination = {}, permissions = {}, urls = {}, currency = '$', t = {} }) {
    const [openMenu, setOpenMenu] = React.useState(null);
    const [modulesFor, setModulesFor] = React.useState(null); // row.id whose modules popover is open
    const menuRef = React.useRef(null);
    const modulesRef = React.useRef(null);

    React.useEffect(() => {
        const onDoc = (e) => {
            if (menuRef.current && !menuRef.current.contains(e.target)) setOpenMenu(null);
            if (modulesRef.current && !modulesRef.current.contains(e.target)) setModulesFor(null);
        };
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
                    {/* Header — AdminLayout already renders the H1 title,
                        so we only show the row-count meta + primary CTA here. */}
                    <div className="flex items-center justify-between gap-3 px-5 py-4 border-b border-border">
                        <p className="text-xs text-muted-foreground m-0 inline-flex items-center gap-1.5">
                            <Layers className="h-3.5 w-3.5" />
                            {pagination.total ?? 0} {t.count_suffix}
                        </p>
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
                                    <th className="px-5 py-3 font-medium text-end">{t.price}</th>
                                    <th className="px-5 py-3 font-medium text-end">{t.parcel_count}</th>
                                    <th className="px-5 py-3 font-medium text-end">{t.max_deliveryman}</th>
                                    <th className="px-5 py-3 font-medium text-end">{t.days_count}</th>
                                    <th className="px-5 py-3 font-medium">{t.modules}</th>
                                    <th className="px-5 py-3 font-medium">{t.status}</th>
                                    {(permissions.update || permissions.delete) && (
                                        <th className="px-5 py-3 font-medium text-end">{t.actions}</th>
                                    )}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {rows.length === 0 ? (
                                    <tr>
                                        <td colSpan={9} className="px-5 py-16 text-center">
                                            <div className="text-muted-foreground/40 mb-2 flex justify-center">
                                                <Layers className="h-10 w-10" />
                                            </div>
                                            <p className="text-sm text-muted-foreground m-0">{t.no_data}</p>
                                        </td>
                                    </tr>
                                ) : rows.map((row, i) => {
                                    const menuOpen = openMenu === row.id;
                                    const modOpen = modulesFor === row.id;
                                    const rowNumber = (pagination.from ?? 1) + i;
                                    return (
                                        <tr key={row.id} className="hover:bg-muted/30 transition-colors">
                                            <td className="px-5 py-3 text-muted-foreground tabular-nums">{rowNumber}</td>

                                            {/* Name + description */}
                                            <td className="px-5 py-3">
                                                <div className="font-medium text-foreground">{row.name}</div>
                                                {row.description && (
                                                    <div className="text-xs text-muted-foreground mt-0.5 line-clamp-1">{row.description}</div>
                                                )}
                                            </td>

                                            <td className="px-5 py-3 text-end tabular-nums font-semibold text-foreground">
                                                <span className="text-muted-foreground me-1 font-medium">{currency}</span>
                                                {Number(row.price ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                            </td>
                                            <td className="px-5 py-3 text-end tabular-nums text-foreground">{Number(row.parcel_count).toLocaleString()}</td>
                                            <td className="px-5 py-3 text-end tabular-nums text-foreground">{Number(row.deliveryman_count).toLocaleString()}</td>
                                            <td className="px-5 py-3 text-end tabular-nums text-foreground">{Number(row.days_count).toLocaleString()}</td>

                                            {/* Modules — click opens a popover listing the first N; falls back to "no modules" tag */}
                                            <td className="px-5 py-3 relative">
                                                <button
                                                    type="button"
                                                    onClick={() => setModulesFor(modOpen ? null : row.id)}
                                                    className={cn(
                                                        'inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full border-0',
                                                        row.module_count > 0
                                                            ? 'text-primary bg-primary/10 hover:bg-primary/15'
                                                            : 'text-muted-foreground bg-muted'
                                                    )}
                                                >
                                                    <Package className="h-3 w-3" />
                                                    {row.module_count}
                                                </button>
                                                {modOpen && row.module_count > 0 && (
                                                    <div
                                                        ref={modulesRef}
                                                        className="absolute start-5 top-11 z-30 min-w-[16rem] max-w-xs rounded-md border border-border bg-popover shadow-md overflow-hidden text-xs"
                                                    >
                                                        <div className="px-3 py-2 border-b border-border bg-muted/40 flex items-center gap-2 font-medium">
                                                            <Info className="h-3.5 w-3.5 text-muted-foreground" />
                                                            {row.name} · {row.module_count} {t.modules}
                                                        </div>
                                                        <ul className="p-2 max-h-64 overflow-y-auto space-y-0.5">
                                                            {(row.modules_preview || []).map((m) => (
                                                                <li key={m.key} className="px-2 py-1 rounded hover:bg-muted truncate">{m.label}</li>
                                                            ))}
                                                            {row.module_count > (row.modules_preview?.length ?? 0) && (
                                                                <li className="px-2 py-1 text-muted-foreground italic">
                                                                    +{row.module_count - (row.modules_preview?.length ?? 0)} {t.more_modules}
                                                                </li>
                                                            )}
                                                        </ul>
                                                    </div>
                                                )}
                                            </td>

                                            {/* Status */}
                                            <td
                                                className="px-5 py-3"
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
                                                            {permissions.delete && (
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
