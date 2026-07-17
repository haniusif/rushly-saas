import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { Plus, MoreHorizontal, Pencil, Trash2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { cn } from '@/lib/utils';

/**
 * Shared list-page shell for the /admin/front-web/* modules. Each caller
 * hands over its own column spec + row shape + urls; the shell handles
 * the layout, header row, pagination, action dropdown, and confirm-delete.
 *
 * columns: [{ label, render(row) → JSX, className?, align?: 'end' }]
 * rows:    each row must expose { id, urls: { edit?, delete? } }
 */
export default function SimpleList({
    title,
    breadcrumbs,
    rows = [],
    columns = [],
    pagination = {},
    permissions = {},
    urls = {},
    countLabel,
    emptyIcon: EmptyIcon,
    emptyLabel,
    addLabel,
    editLabel,
    deleteLabel,
    actionsLabel,
    confirmDelete,
    canUpdate,
    canDelete,
}) {
    const [openMenu, setOpenMenu] = React.useState(null);
    const menuRef = React.useRef(null);

    React.useEffect(() => {
        const onDoc = (e) => { if (menuRef.current && !menuRef.current.contains(e.target)) setOpenMenu(null); };
        document.addEventListener('mousedown', onDoc);
        return () => document.removeEventListener('mousedown', onDoc);
    }, []);

    const onDelete = (row) => {
        if (typeof window !== 'undefined' && !window.confirm(confirmDelete)) return;
        router.delete(row.urls.delete, { preserveScroll: true });
    };

    const hasActions = canUpdate || canDelete;

    return (
        <AdminLayout title={title} breadcrumbs={breadcrumbs}>
            <Head title={title} />
            <Card>
                <CardContent className="p-0">
                    {/* Header */}
                    <div className="flex items-center justify-between gap-3 px-5 py-4 border-b border-border">
                        <p className="text-xs text-muted-foreground m-0">
                            {pagination.total ?? rows.length} {countLabel}
                        </p>
                        {permissions.create && (
                            <Button asChild size="sm">
                                <a href={urls.create}>
                                    <Plus className="h-4 w-4 me-1" />
                                    {addLabel}
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
                                    {columns.map((c, i) => (
                                        <th key={i} className={cn('px-5 py-3 font-medium', c.align === 'end' && 'text-end', c.className)}>
                                            {c.label}
                                        </th>
                                    ))}
                                    {hasActions && (
                                        <th className="px-5 py-3 font-medium text-end">{actionsLabel}</th>
                                    )}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {rows.length === 0 ? (
                                    <tr>
                                        <td colSpan={columns.length + 1 + (hasActions ? 1 : 0)} className="px-5 py-16 text-center">
                                            {EmptyIcon && (
                                                <div className="text-muted-foreground/40 mb-2 flex justify-center">
                                                    <EmptyIcon className="h-10 w-10" />
                                                </div>
                                            )}
                                            <p className="text-sm text-muted-foreground m-0">{emptyLabel}</p>
                                        </td>
                                    </tr>
                                ) : rows.map((row, i) => {
                                    const menuOpen = openMenu === row.id;
                                    const rowNumber = (pagination.from ?? 1) + i;
                                    return (
                                        <tr key={row.id} className="hover:bg-muted/30 transition-colors">
                                            <td className="px-5 py-3 text-muted-foreground tabular-nums align-top">{rowNumber}</td>
                                            {columns.map((c, ci) => (
                                                <td key={ci} className={cn('px-5 py-3 align-top', c.align === 'end' && 'text-end tabular-nums', c.className)}>
                                                    {c.render(row)}
                                                </td>
                                            ))}
                                            {hasActions && (
                                                <td className="px-5 py-3 text-end relative align-top">
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
                                                            {canUpdate && row.urls?.edit && (
                                                                <a href={row.urls.edit} className="flex items-center gap-2 px-3 py-2 hover:bg-muted">
                                                                    <Pencil className="h-3.5 w-3.5" /> {editLabel}
                                                                </a>
                                                            )}
                                                            {canDelete && row.urls?.delete && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => { setOpenMenu(null); onDelete(row); }}
                                                                    className="flex w-full items-center gap-2 px-3 py-2 text-rose-600 hover:bg-muted"
                                                                >
                                                                    <Trash2 className="h-3.5 w-3.5" /> {deleteLabel}
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

/**
 * Small helper for status cells — accepts server-rendered HTML badge markup.
 */
export function StatusCell({ html }) {
    return <span dangerouslySetInnerHTML={{ __html: html || '' }} />;
}
