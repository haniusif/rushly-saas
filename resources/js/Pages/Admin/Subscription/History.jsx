import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { CreditCard, Filter, Eraser, ChevronLeft, ChevronRight } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Select } from '@/Components/ui/Select';

function Money({ value }) {
    return <span className="tabular-nums">{Number(value || 0).toFixed(2)}</span>;
}

export default function History({ rows = [], pagination = {}, filters = {}, lookups = {}, is_super_admin = false, urls = {}, t = {} }) {
    const [draft, setDraft] = React.useState({ ...filters });
    const goPage = (u) => u && router.get(u, {}, { preserveState: true });
    const submitFilter = (e) => {
        e?.preventDefault?.();
        router.get(urls.index, draft, { preserveState: true, preserveScroll: true, replace: true });
    };
    const clear = () => { setDraft({ company_id: '' }); router.get(urls.index); };
    const showing = (t.showing_results || '').replace(':from', pagination.from ?? 0).replace(':to', pagination.to ?? 0).replace(':total', pagination.total ?? 0);

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title, t.list]}>
            <Head title={`${t.title} · ${t.list}`} />
            {is_super_admin && (
                <Card className="mb-5">
                    <CardContent className="pt-6">
                        <form onSubmit={submitFilter} className="grid gap-3 md:grid-cols-12">
                            <div className="md:col-span-4">
                                <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.company}</label>
                                <Select className="mt-1.5" value={draft.company_id} onChange={(e) => setDraft((d) => ({ ...d, company_id: e.target.value }))}>
                                    <option value="">{t.select} {t.company}</option>
                                    {(lookups.companies || []).map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
                                </Select>
                            </div>
                            <div className="md:col-span-4 flex items-end gap-2">
                                <Button type="submit"><Filter className="h-4 w-4 me-1" /> {t.filter}</Button>
                                <Button type="button" variant="outline" onClick={clear}><Eraser className="h-4 w-4 me-1" /> {t.clear}</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            )}
            <div className="mb-3 flex items-center gap-2 text-sm text-muted-foreground"><CreditCard className="h-4 w-4" /><span>{showing}</span></div>
            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                    <th className="px-4 py-3 text-start">#</th>
                                    <th className="px-4 py-3 text-start">{t.company}</th>
                                    <th className="px-4 py-3 text-start">{t.user}</th>
                                    <th className="px-4 py-3 text-start">{t.plan}</th>
                                    <th className="px-4 py-3 text-end">{t.price}</th>
                                    <th className="px-4 py-3 text-end">{t.parcel_count}</th>
                                    <th className="px-4 py-3 text-end">{t.deliveryman_count}</th>
                                    <th className="px-4 py-3 text-end">{t.days_count}</th>
                                    <th className="px-4 py-3 text-start">{t.start_date}</th>
                                    <th className="px-4 py-3 text-start">{t.expired_date}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 && (
                                    <tr><td colSpan={10} className="px-4 py-10 text-center text-muted-foreground"><div className="flex flex-col items-center gap-2"><CreditCard className="h-10 w-10 text-muted-foreground/40" /><span>{t.no_rows}</span></div></td></tr>
                                )}
                                {rows.map((r, idx) => (
                                    <tr key={r.id} className="border-b border-border last:border-0 hover:bg-muted/20 align-top">
                                        <td className="px-4 py-3 text-muted-foreground">{(pagination.from || 1) + idx}</td>
                                        <td className="px-4 py-3 font-medium">{r.company || '—'}</td>
                                        <td className="px-4 py-3">
                                            <div>{r.user_name || '—'}</div>
                                            <div className="text-[11px] text-muted-foreground">{r.user_mobile}</div>
                                            <div className="text-[11px] text-muted-foreground">{r.user_address}</div>
                                        </td>
                                        <td className="px-4 py-3">{r.plan || '—'}</td>
                                        <td className="px-4 py-3 text-end"><Money value={r.price} /></td>
                                        <td className="px-4 py-3 text-end tabular-nums">{r.parcel_count}</td>
                                        <td className="px-4 py-3 text-end tabular-nums">{r.deliveryman_count}</td>
                                        <td className="px-4 py-3 text-end tabular-nums">{r.days_count}</td>
                                        <td className="px-4 py-3 text-muted-foreground tabular-nums">{r.start_date}</td>
                                        <td className="px-4 py-3 text-muted-foreground tabular-nums">{r.expired_date}</td>
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
