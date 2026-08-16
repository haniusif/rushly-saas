import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { Plus, Edit, Trash2, ShieldCheck, Users as UsersIcon } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { cn } from '@/lib/utils';

/**
 * Roles list. Replaces backend/role/index.blade.php, which rendered outside
 * the React shell while the rest of admin is Inertia.
 */
export default function Index({ roles = {}, permissions = {}, urls = {}, t = {} }) {
    const rows = roles.data || [];

    const remove = (r) => {
        if (r.users > 0 && !window.confirm(`${t.in_use_warning}\n\n${t.delete_confirm}`)) return;
        if (r.users === 0 && !window.confirm(t.delete_confirm)) return;
        router.delete(r.urls.delete, { preserveScroll: true });
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title_index]}>
            <Head title={t.title} />

            <div className="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h1 className="text-lg font-semibold">{t.title}</h1>
                    <p className="text-sm text-muted-foreground">
                        {roles.total} {String(t.title).toLowerCase()}
                    </p>
                </div>
                {permissions.create && (
                    <a
                        href={urls.create}
                        className="inline-flex h-9 items-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90"
                    >
                        <Plus className="h-4 w-4 me-1" /> {t.add}
                    </a>
                )}
            </div>

            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                    <th className="px-4 py-3 text-start w-12">#</th>
                                    <th className="px-4 py-3 text-start">{t.name}</th>
                                    <th className="px-4 py-3 text-start">{t.slug}</th>
                                    <th className="px-4 py-3 text-end">{t.permission}</th>
                                    <th className="px-4 py-3 text-end">{t.users}</th>
                                    <th className="px-4 py-3 text-start">{t.status}</th>
                                    {(permissions.update || permissions.delete) && (
                                        <th className="px-4 py-3 text-end pe-4">{t.actions}</th>
                                    )}
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 && (
                                    <tr>
                                        <td colSpan={7} className="px-4 py-10 text-center text-muted-foreground">
                                            <ShieldCheck className="mx-auto mb-2 h-6 w-6 opacity-40" />
                                            {t.no_rows}
                                        </td>
                                    </tr>
                                )}

                                {rows.map((r, idx) => (
                                    <tr key={r.id} className="border-b border-border last:border-0 hover:bg-muted/20">
                                        <td className="px-4 py-3 tabular-nums text-muted-foreground">
                                            {(roles.current_page - 1) * 10 + idx + 1}
                                        </td>
                                        <td className="px-4 py-3 font-medium">{r.name}</td>
                                        <td className="px-4 py-3">
                                            <code className="rounded bg-muted px-2 py-0.5 text-xs">{r.slug}</code>
                                        </td>
                                        <td className="px-4 py-3 text-end tabular-nums">{r.permissions}</td>
                                        <td className="px-4 py-3 text-end">
                                            {r.users > 0 ? (
                                                <span className="inline-flex items-center gap-1 tabular-nums">
                                                    <UsersIcon className="h-3.5 w-3.5 text-muted-foreground" />
                                                    {r.users}
                                                </span>
                                            ) : (
                                                <span className="text-muted-foreground">—</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className={cn(
                                                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                                r.status
                                                    ? 'bg-emerald-100 text-emerald-700'
                                                    : 'bg-muted text-muted-foreground',
                                            )}>
                                                {r.status ? t.active : t.inactive}
                                            </span>
                                        </td>
                                        {(permissions.update || permissions.delete) && (
                                            <td className="px-4 py-3 pe-4">
                                                <div className="flex items-center justify-end gap-1">
                                                    {permissions.update && (
                                                        <a
                                                            href={r.urls.edit}
                                                            className="inline-flex h-8 w-8 items-center justify-center rounded-md hover:bg-accent"
                                                            title={t.edit}
                                                        >
                                                            <Edit className="h-4 w-4" />
                                                        </a>
                                                    )}
                                                    {permissions.delete && (
                                                        <button
                                                            type="button"
                                                            onClick={() => remove(r)}
                                                            className="inline-flex h-8 w-8 items-center justify-center rounded-md text-destructive hover:bg-destructive/10"
                                                            title={t.delete}
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        )}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            {roles.last_page > 1 && (
                <div className="mt-4 flex flex-wrap items-center justify-center gap-1">
                    {(roles.links || []).map((l, i) => (
                        <button
                            key={i}
                            type="button"
                            disabled={!l.url}
                            onClick={() => l.url && router.visit(l.url, { preserveScroll: true })}
                            className={cn(
                                'h-8 min-w-8 rounded-md px-2 text-sm',
                                l.active ? 'bg-primary text-primary-foreground' : 'hover:bg-accent',
                                !l.url && 'cursor-not-allowed opacity-40',
                            )}
                            dangerouslySetInnerHTML={{ __html: l.label }}
                        />
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}
