import * as React from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Wallet, Filter, Eraser, Plus, ChevronLeft, ChevronRight, MoreVertical, Check, X, Trash2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem } from '@/Components/ui/DropdownMenu';

const STATUS_TINT = {
    1: 'bg-amber-100 text-amber-700 border-amber-200',
    2: 'bg-emerald-100 text-emerald-700 border-emerald-200',
    3: 'bg-rose-100 text-rose-700 border-rose-200',
};
function StatusPill({ status, label }) {
    return <span className={`inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium ${STATUS_TINT[status] || STATUS_TINT[1]}`}>{label}</span>;
}
function Money({ value, currency, type }) {
    const cls = type === 1 ? 'text-emerald-700' : type === 2 ? 'text-rose-700' : '';
    const sign = type === 1 ? '+' : type === 2 ? '−' : '';
    return <span className={`font-medium tabular-nums ${cls}`}>{sign} {currency}{Number(value || 0).toFixed(2)}</span>;
}

function SummaryCard({ label, value, sub, valueClass = '' }) {
    return (
        <Card>
            <CardContent className="p-4">
                <div className="text-xs text-muted-foreground">{label}</div>
                <div className={`text-2xl font-bold tabular-nums mt-1 ${valueClass}`}>{value}</div>
                {sub && <div className="text-xs text-muted-foreground mt-1">{sub}</div>}
            </CardContent>
        </Card>
    );
}

function csrfToken() {
    if (typeof document === 'undefined') return '';
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function RechargeModal({ open, onClose, urls, t }) {
    const form = useForm({ merchant_id: '', transaction_id: '', amount: '' });
    const setQuick = (v) => form.setData('amount', String(v));
    const submit = (e) => {
        e.preventDefault();
        form.post(urls.recharge, { preserveScroll: true, onSuccess: onClose });
    };
    if (!open) return null;
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" onClick={onClose}>
            <Card className="w-full max-w-2xl" onClick={(e) => e.stopPropagation()}>
                <CardContent className="p-6">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-lg font-semibold">{t.recharge_wallet}</h3>
                        <Button variant="ghost" size="icon" onClick={onClose}><X className="h-4 w-4" /></Button>
                    </div>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.merchant}</Label>
                            <Input className="mt-1.5" value={form.data.merchant_id} onChange={(e) => form.setData('merchant_id', e.target.value)} placeholder={t.select_merchant} />
                            {form.errors.merchant_id && <p className="text-xs text-destructive mt-1">{form.errors.merchant_id}</p>}
                        </div>
                        <div>
                            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.transaction_id}</Label>
                            <Input className="mt-1.5" value={form.data.transaction_id} onChange={(e) => form.setData('transaction_id', e.target.value)} />
                        </div>
                        <div>
                            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.amount}</Label>
                            <Input type="number" step="0.01" className="mt-1.5" value={form.data.amount} onChange={(e) => form.setData('amount', e.target.value)} />
                            {form.errors.amount && <p className="text-xs text-destructive mt-1">{form.errors.amount}</p>}
                        </div>
                        <div>
                            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.quick_add}</Label>
                            <p className="text-xs text-muted-foreground">{t.quick_hint}</p>
                            <div className="grid grid-cols-4 gap-2 mt-2">
                                {[500, 1000, 2000, 3000, 5000, 10000, 15000, 20000].map((v) => (
                                    <button type="button" key={v} onClick={() => setQuick(v)} className="rounded-md border border-input bg-background px-3 py-2 text-sm font-medium hover:bg-muted/40 tabular-nums">{v}</button>
                                ))}
                            </div>
                        </div>
                        <div className="flex justify-end gap-2 pt-2 border-t border-border">
                            <Button type="button" variant="outline" onClick={onClose}>{t.cancel || 'Cancel'}</Button>
                            <Button type="submit" disabled={form.processing}>{t.add_to_wallet}</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}

function TransactionsTable({ rows, pagination, permissions, urls, currency, t }) {
    const action = (verb, id) => {
        const map = { approve: urls.approve_base, reject: urls.reject_base, delete: urls.delete_base };
        const confirmMap = { approve: t.approve_confirm, reject: t.reject_confirm, delete: t.delete_confirm };
        if (!window.confirm(confirmMap[verb])) return;
        const url = `${map[verb]}/${id}`;
        if (verb === 'delete') router.delete(url, { preserveScroll: true });
        else router.put(url, {}, { preserveScroll: true });
    };
    const goPage = (u) => u && router.get(u, {}, { preserveState: true });
    const showing = (t.showing_results || '').replace(':from', pagination.from ?? 0).replace(':to', pagination.to ?? 0).replace(':total', pagination.total ?? 0);

    return (
        <>
            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                    <th className="px-4 py-3 text-start">#</th>
                                    <th className="px-4 py-3 text-start">{t.merchant}</th>
                                    <th className="px-4 py-3 text-start">{t.date}</th>
                                    <th className="px-4 py-3 text-start">{t.transaction_id}</th>
                                    <th className="px-4 py-3 text-start">{t.payment_method}</th>
                                    <th className="px-4 py-3 text-end">{t.amount}</th>
                                    <th className="px-4 py-3 text-start">{t.status}</th>
                                    {(permissions.approve || permissions.reject || permissions.delete) && <th className="px-4 py-3 text-end">{t.actions}</th>}
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 && (
                                    <tr><td colSpan={8} className="px-4 py-10 text-center text-muted-foreground"><div className="flex flex-col items-center gap-2"><Wallet className="h-10 w-10 text-muted-foreground/40" /><span>{t.no_rows}</span></div></td></tr>
                                )}
                                {rows.map((r, idx) => (
                                    <tr key={r.id} className="border-b border-border last:border-0 hover:bg-muted/20 align-top">
                                        <td className="px-4 py-3 text-muted-foreground">{(pagination.from || 1) + idx}</td>
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-2">
                                                {r.user_image ? <img src={r.user_image} className="h-9 w-9 rounded-full border border-border object-cover" alt="" /> : <div className="h-9 w-9 rounded-full bg-muted" />}
                                                <div>
                                                    <div className="font-medium">{r.merchant_name || '—'}</div>
                                                    <div className="text-[11px] text-muted-foreground">{r.merchant_phone}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground tabular-nums">{r.created_at || '—'}</td>
                                        <td className="px-4 py-3 font-mono text-xs text-muted-foreground">{r.transaction_id || '—'}</td>
                                        <td className="px-4 py-3 text-muted-foreground">{r.payment_method_label}</td>
                                        <td className="px-4 py-3 text-end"><Money value={r.amount} currency={currency} type={r.type} /></td>
                                        <td className="px-4 py-3">{r.type === 1 ? <StatusPill status={r.status} label={r.status_label} /> : <span className="text-muted-foreground text-xs">—</span>}</td>
                                        {(permissions.approve || permissions.reject || permissions.delete) && (
                                            <td className="px-4 py-3 text-end">
                                                {r.type === 1 && (
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild><Button variant="ghost" size="icon" className="h-8 w-8"><MoreVertical className="h-4 w-4" /></Button></DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end" className="w-44">
                                                            {permissions.approve && r.status === 1 && <DropdownMenuItem onClick={() => action('approve', r.id)}><Check className="h-4 w-4 me-2" /> {t.approve}</DropdownMenuItem>}
                                                            {permissions.reject && r.status === 1 && <DropdownMenuItem onClick={() => action('reject', r.id)}><X className="h-4 w-4 me-2" /> {t.reject}</DropdownMenuItem>}
                                                            {permissions.delete && <DropdownMenuItem onClick={() => action('delete', r.id)} className="text-destructive focus:text-destructive"><Trash2 className="h-4 w-4 me-2" /> {t.delete}</DropdownMenuItem>}
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                )}
                                            </td>
                                        )}
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
        </>
    );
}

export default function Index({
    rows_all = [], rows_recharge = [], pagination_all = {}, pagination_recharge = {},
    summary = {}, filters = {}, lookups = {}, permissions = {}, currency = '', urls = {}, t = {},
}) {
    const [tab, setTab] = React.useState(filters.recharge_page ? 'recharges' : 'all');
    const [draft, setDraft] = React.useState({ date: filters.date, status: filters.status, merchant_id: filters.merchant_id, search: filters.search });
    const [rechargeOpen, setRechargeOpen] = React.useState(false);

    const submitFilter = (e) => {
        e?.preventDefault?.();
        router.get(urls.index, { ...draft, recharge_page: tab === 'recharges' ? 1 : 0 }, { preserveState: true, preserveScroll: true, replace: true });
    };
    const clear = () => { setDraft({ date: '', status: '', merchant_id: '', search: '' }); router.get(urls.index); };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title, t.list]}>
            <Head title={`${t.title} · ${t.list}`} />

            <Card className="mb-5">
                <CardContent className="pt-6">
                    <form onSubmit={submitFilter} className="grid gap-3 md:grid-cols-12">
                        <div className="md:col-span-3">
                            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.date}</Label>
                            <Input className="mt-1.5" placeholder="YYYY-MM-DD to YYYY-MM-DD" value={draft.date} onChange={(e) => setDraft((d) => ({ ...d, date: e.target.value }))} />
                        </div>
                        <div className="md:col-span-2">
                            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.status}</Label>
                            <Select className="mt-1.5" value={draft.status} onChange={(e) => setDraft((d) => ({ ...d, status: e.target.value }))}>
                                <option value="">—</option>
                                {(lookups.statuses || []).map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                            </Select>
                        </div>
                        <div className="md:col-span-3">
                            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.merchant}</Label>
                            <Input className="mt-1.5" placeholder="Merchant ID" value={draft.merchant_id} onChange={(e) => setDraft((d) => ({ ...d, merchant_id: e.target.value }))} />
                        </div>
                        <div className="md:col-span-2">
                            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.search}</Label>
                            <Input className="mt-1.5" value={draft.search} onChange={(e) => setDraft((d) => ({ ...d, search: e.target.value }))} />
                        </div>
                        <div className="md:col-span-2 flex items-end gap-2">
                            <Button type="submit"><Filter className="h-4 w-4 me-1" /> {t.filter}</Button>
                            <Button type="button" variant="outline" onClick={clear}><Eraser className="h-4 w-4 me-1" /> {t.clear}</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <div className="grid gap-4 md:grid-cols-4 mb-5">
                <SummaryCard label={t.total_recharge} value={`${currency}${Number(summary.total_recharge || 0).toFixed(2)}`} valueClass="text-emerald-700" />
                <SummaryCard label={t.total_deductions} value={`${currency}${Number(summary.total_deductions || 0).toFixed(2)}`} valueClass="text-rose-700" />
                <Card>
                    <CardContent className="p-4">
                        <div className="grid grid-cols-3 gap-3">
                            <div><div className="text-xs text-muted-foreground">{t.pending}</div><div className="text-xl font-bold tabular-nums">{summary.count_pending}</div></div>
                            <div><div className="text-xs text-muted-foreground">{t.confirm}</div><div className="text-xl font-bold tabular-nums text-emerald-700">{summary.count_approved}</div></div>
                            <div><div className="text-xs text-muted-foreground">{t.rejected}</div><div className="text-xl font-bold tabular-nums text-rose-700">{summary.count_rejected}</div></div>
                        </div>
                    </CardContent>
                </Card>
                {permissions.create && (
                    <button onClick={() => setRechargeOpen(true)} className="rounded-lg border border-dashed border-primary/50 bg-primary/5 hover:bg-primary/10 transition-colors p-4 text-center cursor-pointer">
                        <div className="text-xs text-muted-foreground">{t.recharge_wallet}</div>
                        <Plus className="h-6 w-6 mt-2 mx-auto text-primary" />
                    </button>
                )}
            </div>

            <Card className="mb-3">
                <CardContent className="p-2">
                    <div className="flex gap-1 border-b border-border">
                        <button onClick={() => setTab('all')} className={`px-4 py-2 text-sm font-medium border-b-2 ${tab === 'all' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'}`}>{t.all_transactions}</button>
                        <button onClick={() => setTab('recharges')} className={`px-4 py-2 text-sm font-medium border-b-2 ${tab === 'recharges' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'}`}>{t.recharges}</button>
                    </div>
                </CardContent>
            </Card>

            {tab === 'all'
                ? <TransactionsTable rows={rows_all} pagination={pagination_all} permissions={permissions} urls={urls} currency={currency} t={t} />
                : <TransactionsTable rows={rows_recharge} pagination={pagination_recharge} permissions={permissions} urls={urls} currency={currency} t={t} />}

            <RechargeModal open={rechargeOpen} onClose={() => setRechargeOpen(false)} urls={urls} t={t} />
        </AdminLayout>
    );
}
