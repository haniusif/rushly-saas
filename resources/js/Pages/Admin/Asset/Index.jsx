import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { Boxes, Plus, Edit, Trash2, MoreVertical, ChevronLeft, ChevronRight, Eye } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem } from '@/Components/ui/DropdownMenu';

function Money({ value, currency }) {
    return <span className="tabular-nums">{currency ? `${currency} ` : ''}{Number(value || 0).toFixed(2)}</span>;
}

export default function Index({ rows = [], pagination = {}, permissions = {}, currency = '', urls = {}, t = {} }) {
    const goPage = (u) => u && router.get(u, {}, { preserveState: true });
    const del = (r) => { if (window.confirm(t.delete_confirm)) router.delete(r.urls.delete, { preserveScroll: true }); };
    const showing = (t.showing_results || '').replace(':from', pagination.from ?? 0).replace(':to', pagination.to ?? 0).replace(':total', pagination.total ?? 0);

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title, t.list]}>
            <Head title={`${t.title} · ${t.list}`} />
            <div className="mb-3 flex items-center justify-between">
                <div className="flex items-center gap-2 text-sm text-muted-foreground"><Boxes className="h-4 w-4" /><span>{showing}</span></div>
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
                                    <th className="px-4 py-3 text-start">{t.name}</th>
                                    <th className="px-4 py-3 text-start">{t.category}</th>
                                    <th className="px-4 py-3 text-start">{t.hub}</th>
                                    <th className="px-4 py-3 text-start">{t.supplier}</th>
                                    <th className="px-4 py-3 text-end">{t.quantity}</th>
                                    <th className="px-4 py-3 text-start">{t.warranty}</th>
                                    <th className="px-4 py-3 text-start">{t.invoice_no}</th>
                                    <th className="px-4 py-3 text-end">{t.amount}</th>
                                    {(permissions.update || permissions.delete) && <th className="px-4 py-3 text-end">{t.actions}</th>}
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 && (
                                    <tr><td colSpan={10} className="px-4 py-10 text-center text-muted-foreground"><div className="flex flex-col items-center gap-2"><Boxes className="h-10 w-10 text-muted-foreground/40" /><span>{t.no_rows}</span></div></td></tr>
                                )}
                                {rows.map((r, idx) => {
                                    const num = (pagination.from || 1) + idx;
                                    return (
                                        <tr key={r.id} className="border-b border-border last:border-0 hover:bg-muted/20 align-top">
                                            <td className="px-4 py-3 text-muted-foreground">{num}</td>
                                            <td className="px-4 py-3 font-medium">{r.name}</td>
                                            <td className="px-4 py-3 text-muted-foreground">{r.category || '—'}</td>
                                            <td className="px-4 py-3 text-muted-foreground">{r.hub || '—'}</td>
                                            <td className="px-4 py-3 text-muted-foreground">{r.supplier_name || '—'}</td>
                                            <td className="px-4 py-3 text-end tabular-nums">{r.quantity ?? '—'}</td>
                                            <td className="px-4 py-3 text-muted-foreground">{r.warranty || '—'}</td>
                                            <td className="px-4 py-3 font-mono text-xs text-muted-foreground">{r.invoice_no || '—'}</td>
                                            <td className="px-4 py-3 text-end"><Money value={r.amount} currency={currency} /></td>
                                            {(permissions.update || permissions.delete) && (
                                                <td className="px-4 py-3 text-end">
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild><Button variant="ghost" size="icon" className="h-8 w-8"><MoreVertical className="h-4 w-4" /></Button></DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end" className="w-44">
                                                            {permissions.view && r.urls.view && <DropdownMenuItem onClick={() => { window.location.href = r.urls.view; }}><Eye className="h-4 w-4 me-2" /> View</DropdownMenuItem>}
                                                            {permissions.update && <DropdownMenuItem onClick={() => { window.location.href = r.urls.edit; }}><Edit className="h-4 w-4 me-2" /> {t.edit}</DropdownMenuItem>}
                                                            {permissions.delete && <DropdownMenuItem onClick={() => del(r)} className="text-destructive focus:text-destructive"><Trash2 className="h-4 w-4 me-2" /> {t.delete}</DropdownMenuItem>}
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </td>
                                            )}
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
