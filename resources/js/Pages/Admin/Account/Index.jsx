import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { DollarSign, Plus, Edit, Trash2, Eye, MoreVertical, ChevronLeft, ChevronRight, Filter, Eraser } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem } from '@/Components/ui/DropdownMenu';

function Money({ value, currency }) {
    return <span className="tabular-nums">{currency}{Number(value || 0).toFixed(2)}</span>;
}

export default function Index({ rows = [], pagination = {}, filters = {}, currency = '', permissions = {}, urls = {}, t = {} }) {
    const [draft, setDraft] = React.useState({ ...filters });
    const submitFilter = (e) => { e?.preventDefault?.(); router.get(urls.filter, draft, { preserveState: true, preserveScroll: true, replace: true }); };
    const clear = () => { setDraft({ holder_name: '', account_no: '', bank: '' }); router.get(urls.index); };
    const goPage = (u) => u && router.get(u, {}, { preserveState: true });
    const del = (r) => { if (window.confirm(t.delete_confirm)) router.delete(r.urls.delete, { preserveScroll: true }); };
    const showing = (t.showing_results || '').replace(':from', pagination.from ?? 0).replace(':to', pagination.to ?? 0).replace(':total', pagination.total ?? 0);

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title, t.list]}>
            <Head title={`${t.title} · ${t.list}`} />
            <Card className="mb-5">
                <CardContent className="pt-6">
                    <form onSubmit={submitFilter} className="grid gap-3 md:grid-cols-12">
                        <div className="md:col-span-3">
                            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.holder}</Label>
                            <Input className="mt-1.5" value={draft.holder_name} onChange={(e) => setDraft((d) => ({ ...d, holder_name: e.target.value }))} placeholder={t.placeholder_holder} />
                        </div>
                        <div className="md:col-span-3">
                            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.account_no}</Label>
                            <Input className="mt-1.5" value={draft.account_no} onChange={(e) => setDraft((d) => ({ ...d, account_no: e.target.value }))} placeholder={t.placeholder_acct} />
                        </div>
                        <div className="md:col-span-3">
                            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.bank}</Label>
                            <Input className="mt-1.5" value={draft.bank} onChange={(e) => setDraft((d) => ({ ...d, bank: e.target.value }))} placeholder={t.placeholder_bank} />
                        </div>
                        <div className="md:col-span-3 flex items-end gap-2">
                            <Button type="submit"><Filter className="h-4 w-4 me-1" /> {t.filter}</Button>
                            <Button type="button" variant="outline" onClick={clear}><Eraser className="h-4 w-4 me-1" /> {t.clear}</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
            <div className="mb-3 flex items-center justify-between">
                <div className="flex items-center gap-2 text-sm text-muted-foreground"><DollarSign className="h-4 w-4" /><span>{showing}</span></div>
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
                                    <th className="px-4 py-3 text-start">{t.holder}</th>
                                    <th className="px-4 py-3 text-start">{t.bank}</th>
                                    <th className="px-4 py-3 text-start">{t.account_no}</th>
                                    <th className="px-4 py-3 text-end">{t.balance}</th>
                                    <th className="px-4 py-3 text-end">{t.opening_balance}</th>
                                    <th className="px-4 py-3 text-end">{t.actions}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 && (
                                    <tr><td colSpan={7} className="px-4 py-10 text-center text-muted-foreground"><div className="flex flex-col items-center gap-2"><DollarSign className="h-10 w-10 text-muted-foreground/40" /><span>{t.no_rows}</span></div></td></tr>
                                )}
                                {rows.map((r, idx) => (
                                    <tr key={r.id} className="border-b border-border last:border-0 hover:bg-muted/20 align-top">
                                        <td className="px-4 py-3 text-muted-foreground">{(pagination.from || 1) + idx}</td>
                                        <td className="px-4 py-3 font-medium">{r.account_holder_name || '—'}</td>
                                        <td className="px-4 py-3"><div>{r.bank || '—'}</div><div className="text-[11px] text-muted-foreground">{r.branch_name}</div></td>
                                        <td className="px-4 py-3 font-mono text-xs">{r.account_no || '—'}</td>
                                        <td className="px-4 py-3 text-end font-medium"><Money value={r.balance} currency={currency} /></td>
                                        <td className="px-4 py-3 text-end text-muted-foreground"><Money value={r.opening_balance} currency={currency} /></td>
                                        <td className="px-4 py-3 text-end">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild><Button variant="ghost" size="icon" className="h-8 w-8"><MoreVertical className="h-4 w-4" /></Button></DropdownMenuTrigger>
                                                <DropdownMenuContent align="end" className="w-44">
                                                    <DropdownMenuItem onClick={() => { window.location.href = r.urls.view; }}><Eye className="h-4 w-4 me-2" /> {t.view}</DropdownMenuItem>
                                                    {permissions.update && <DropdownMenuItem onClick={() => { window.location.href = r.urls.edit; }}><Edit className="h-4 w-4 me-2" /> {t.edit}</DropdownMenuItem>}
                                                    {permissions.delete && <DropdownMenuItem onClick={() => del(r)} className="text-destructive focus:text-destructive"><Trash2 className="h-4 w-4 me-2" /> {t.delete}</DropdownMenuItem>}
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </td>
                                    </tr>
                                ))}
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
