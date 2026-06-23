import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { LifeBuoy, Plus, Eye, Edit, Trash2, MoreVertical, ChevronLeft, ChevronRight } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem } from '@/Components/ui/DropdownMenu';

const STATUS_TINT = {
    0: 'bg-amber-100 text-amber-700 border-amber-200',
    1: 'bg-sky-100 text-sky-700 border-sky-200',
    2: 'bg-emerald-100 text-emerald-700 border-emerald-200',
    3: 'bg-slate-100 text-slate-700 border-slate-200',
};
const PRIORITY_TINT = {
    low:    'bg-slate-100 text-slate-700 border-slate-200',
    medium: 'bg-amber-100 text-amber-700 border-amber-200',
    high:   'bg-rose-100 text-rose-700 border-rose-200',
};

function StatusPill({ status, label }) {
    const k = Number(status);
    return <span className={`inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium ${STATUS_TINT[k] || STATUS_TINT[0]}`}>{label}</span>;
}
function PriorityPill({ p }) {
    return <span className={`inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-medium uppercase ${PRIORITY_TINT[p] || PRIORITY_TINT.medium}`}>{p || '—'}</span>;
}

export default function Index({ rows = [], pagination = {}, permissions = {}, urls = {}, t = {} }) {
    const goPage = (u) => u && router.get(u, {}, { preserveState: true });
    const del = (r) => { if (window.confirm(t.delete_confirm)) router.delete(r.urls.delete, { preserveScroll: true }); };
    const showing = (t.showing_results || '').replace(':from', pagination.from ?? 0).replace(':to', pagination.to ?? 0).replace(':total', pagination.total ?? 0);

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title, t.list]}>
            <Head title={`${t.title} · ${t.list}`} />
            <div className="mb-3 flex items-center justify-between">
                <div className="flex items-center gap-2 text-sm text-muted-foreground"><LifeBuoy className="h-4 w-4" /><span>{showing}</span></div>
                {permissions.create && (
                    <a href={urls.create} className="inline-flex h-9 items-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground hover:bg-primary/90"><Plus className="h-4 w-4 me-1" /> {t.add}</a>
                )}
            </div>
            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                    <th className="px-4 py-3 text-start">#</th>
                                    <th className="px-4 py-3 text-start">{t.user_info}</th>
                                    <th className="px-4 py-3 text-start">{t.subject}</th>
                                    <th className="px-4 py-3 text-start">{t.priority}</th>
                                    <th className="px-4 py-3 text-start">{t.date}</th>
                                    <th className="px-4 py-3 text-start">{t.status}</th>
                                    {permissions.status_update && <th className="px-4 py-3 text-start">{t.status_update}</th>}
                                    <th className="px-4 py-3 text-end">{t.actions}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 && (
                                    <tr><td colSpan={8} className="px-4 py-10 text-center text-muted-foreground"><div className="flex flex-col items-center gap-2"><LifeBuoy className="h-10 w-10 text-muted-foreground/40" /><span>{t.no_rows}</span></div></td></tr>
                                )}
                                {rows.map((r, idx) => {
                                    const num = (pagination.from || 1) + idx;
                                    return (
                                        <tr key={r.id} className="border-b border-border last:border-0 hover:bg-muted/20 align-top">
                                            <td className="px-4 py-3 text-muted-foreground">{num}</td>
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{r.user_name || '—'}</div>
                                                <div className="text-[11px] text-muted-foreground">{r.user_email}</div>
                                                <div className="text-[11px] text-muted-foreground mt-0.5">{r.service_label} · {r.department}</div>
                                            </td>
                                            <td className="px-4 py-3"><a href={r.urls.view} className="text-primary hover:underline">{r.subject}</a></td>
                                            <td className="px-4 py-3"><PriorityPill p={r.priority} /></td>
                                            <td className="px-4 py-3 text-muted-foreground tabular-nums">{r.date || '—'}</td>
                                            <td className="px-4 py-3"><StatusPill status={r.status} label={r.status_label} /></td>
                                            {permissions.status_update && (
                                                <td className="px-4 py-3">
                                                    {r.next_actions?.length > 0 ? (
                                                        <DropdownMenu>
                                                            <DropdownMenuTrigger asChild><Button variant="outline" size="sm">{t.status_update}</Button></DropdownMenuTrigger>
                                                            <DropdownMenuContent align="start" className="w-40">
                                                                {r.next_actions.map((n, i) => <DropdownMenuItem key={i} onClick={() => { window.location.href = n.url; }}>{n.label}</DropdownMenuItem>)}
                                                            </DropdownMenuContent>
                                                        </DropdownMenu>
                                                    ) : <span className="text-muted-foreground text-xs">—</span>}
                                                </td>
                                            )}
                                            <td className="px-4 py-3 text-end">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild><Button variant="ghost" size="icon" className="h-8 w-8"><MoreVertical className="h-4 w-4" /></Button></DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end" className="w-44">
                                                        {permissions.view && <DropdownMenuItem onClick={() => { window.location.href = r.urls.view; }}><Eye className="h-4 w-4 me-2" /> {t.view}</DropdownMenuItem>}
                                                        {permissions.update && <DropdownMenuItem onClick={() => { window.location.href = r.urls.edit; }}><Edit className="h-4 w-4 me-2" /> {t.edit}</DropdownMenuItem>}
                                                        {permissions.delete && <DropdownMenuItem onClick={() => del(r)} className="text-destructive focus:text-destructive"><Trash2 className="h-4 w-4 me-2" /> {t.delete}</DropdownMenuItem>}
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
            {pagination.last_page > 1 && (
                <div className="mt-4 flex items-center justify-between text-sm">
                    <div className="text-muted-foreground">{showing}</div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" disabled={!pagination.prev_url} onClick={() => goPage(pagination.prev_url)}><ChevronLeft className="h-4 w-4 me-1" /> {t.prev}</Button>
                        <span className="text-xs text-muted-foreground">{pagination.current_page} / {pagination.last_page}</span>
                        <Button variant="outline" size="sm" disabled={!pagination.next_url} onClick={() => goPage(pagination.next_url)}>{t.next} <ChevronRight className="h-4 w-4 ms-1" /></Button>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
