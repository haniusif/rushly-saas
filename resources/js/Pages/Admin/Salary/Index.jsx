import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { Briefcase, Plus, Edit, Trash2, FileText, MoreVertical, ChevronLeft, ChevronRight, Filter, Eraser, UserSearch } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem } from '@/Components/ui/DropdownMenu';

function Money({ value, currency }) {
    return <span className="tabular-nums font-medium">{currency}{Number(value || 0).toFixed(2)}</span>;
}

function csrfToken() {
    if (typeof document === 'undefined') return '';
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function useDebounced(value, delay = 300) {
    const [v, setV] = React.useState(value);
    React.useEffect(() => { const id = setTimeout(() => setV(value), delay); return () => clearTimeout(id); }, [value, delay]);
    return v;
}

function UserPicker({ urlSearch, value, onChange, placeholder }) {
    const [query, setQuery] = React.useState('');
    const [results, setResults] = React.useState([]);
    const [open, setOpen] = React.useState(false);
    const [selected, setSelected] = React.useState(value ? { id: value, text: `User #${value}` } : null);
    const debounced = useDebounced(query, 300);
    const wrapRef = React.useRef(null);

    React.useEffect(() => {
        const onDoc = (e) => { if (!wrapRef.current?.contains(e.target)) setOpen(false); };
        document.addEventListener('mousedown', onDoc);
        return () => document.removeEventListener('mousedown', onDoc);
    }, []);

    React.useEffect(() => {
        let alive = true;
        if (!open) return;
        const fd = new FormData();
        fd.append('search', debounced);
        fd.append('_token', csrfToken());
        fetch(urlSearch, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } })
            .then((r) => r.ok ? r.json() : [])
            .then((d) => { if (alive) setResults(Array.isArray(d) ? d : (d?.data || [])); })
            .catch(() => { if (alive) setResults([]); });
        return () => { alive = false; };
    }, [debounced, open, urlSearch]);

    const pick = (u) => { setSelected(u); setOpen(false); setQuery(''); onChange(String(u.id)); };

    return (
        <div ref={wrapRef} className="relative">
            {selected ? (
                <div className="flex items-center justify-between gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm">
                    <span className="truncate"><UserSearch className="inline h-3.5 w-3.5 me-1" /> {selected.text}</span>
                    <button type="button" className="text-muted-foreground hover:text-foreground text-xs" onClick={() => { setSelected(null); onChange(''); }}>×</button>
                </div>
            ) : (
                <Input value={query} onChange={(e) => { setQuery(e.target.value); setOpen(true); }} onFocus={() => setOpen(true)} placeholder={placeholder} />
            )}
            {open && !selected && (
                <div className="absolute z-10 mt-1 w-full rounded-md border border-border bg-popover shadow-md max-h-56 overflow-auto">
                    {results.length === 0 && <div className="px-3 py-2 text-xs text-muted-foreground">…</div>}
                    {results.map((u) => (
                        <button key={u.id} type="button" className="w-full px-3 py-2 text-start text-sm hover:bg-muted/50" onMouseDown={(e) => { e.preventDefault(); pick(u); }}>{u.text || u.name}</button>
                    ))}
                </div>
            )}
        </div>
    );
}

function Avatar({ name, src }) {
    if (src) return <img src={src} alt="" className="h-9 w-9 rounded-full border border-border object-cover" />;
    return <div className="h-9 w-9 rounded-full bg-muted/40 flex items-center justify-center text-xs font-semibold">{(name || '?').slice(0, 1).toUpperCase()}</div>;
}

export default function Index({ rows = [], pagination = {}, filters = {}, currency = '', permissions = {}, urls = {}, t = {} }) {
    const [draft, setDraft] = React.useState({ ...filters });
    const submitFilter = (e) => { e?.preventDefault?.(); router.get(urls.filter, draft, { preserveState: true, preserveScroll: true, replace: true }); };
    const clear = () => { setDraft({ user_id: '', month: '' }); router.get(urls.index); };
    const goPage = (u) => u && router.get(u, {}, { preserveState: true });
    const del = (r) => { if (window.confirm(t.delete_confirm)) router.delete(r.urls.delete, { preserveScroll: true }); };
    const showing = (t.showing_results || '').replace(':from', pagination.from ?? 0).replace(':to', pagination.to ?? 0).replace(':total', pagination.total ?? 0);

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title, t.list]}>
            <Head title={`${t.title} · ${t.list}`} />
            <Card className="mb-5">
                <CardContent className="pt-6">
                    <form onSubmit={submitFilter} className="grid gap-3 md:grid-cols-12">
                        <div className="md:col-span-5">
                            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.user}</Label>
                            <div className="mt-1.5">
                                <UserPicker urlSearch={urls.user_search} value={draft.user_id} onChange={(v) => setDraft((d) => ({ ...d, user_id: v }))} placeholder={t.select_user} />
                            </div>
                        </div>
                        <div className="md:col-span-4">
                            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.month}</Label>
                            <Input className="mt-1.5" type="month" value={draft.month} onChange={(e) => setDraft((d) => ({ ...d, month: e.target.value }))} />
                        </div>
                        <div className="md:col-span-3 flex items-end gap-2">
                            <Button type="submit"><Filter className="h-4 w-4 me-1" /> {t.filter}</Button>
                            <Button type="button" variant="outline" onClick={clear}><Eraser className="h-4 w-4 me-1" /> {t.clear}</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
            <div className="mb-3 flex items-center justify-between">
                <div className="flex items-center gap-2 text-sm text-muted-foreground"><Briefcase className="h-4 w-4" /><span>{showing}</span></div>
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
                                    <th className="px-4 py-3 text-start">{t.user}</th>
                                    <th className="px-4 py-3 text-start">{t.from_account}</th>
                                    <th className="px-4 py-3 text-start">{t.month}</th>
                                    <th className="px-4 py-3 text-start">{t.date}</th>
                                    <th className="px-4 py-3 text-start">{t.note}</th>
                                    <th className="px-4 py-3 text-end">{t.amount}</th>
                                    {(permissions.update || permissions.delete) && <th className="px-4 py-3 text-end">{t.actions}</th>}
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 && (
                                    <tr><td colSpan={8} className="px-4 py-10 text-center text-muted-foreground"><div className="flex flex-col items-center gap-2"><Briefcase className="h-10 w-10 text-muted-foreground/40" /><span>{t.no_rows}</span></div></td></tr>
                                )}
                                {rows.map((r, idx) => (
                                    <tr key={r.id} className="border-b border-border last:border-0 hover:bg-muted/20 align-top">
                                        <td className="px-4 py-3 text-muted-foreground">{(pagination.from || 1) + idx}</td>
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-2">
                                                <Avatar name={r.user_name} src={r.user_image} />
                                                <div className="min-w-0">
                                                    <div className="font-medium">{r.user_name || '—'}</div>
                                                    <div className="text-[11px] text-muted-foreground">{r.user_email}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">{r.from_account || '—'}</td>
                                        <td className="px-4 py-3 font-mono text-xs">{r.month || '—'}</td>
                                        <td className="px-4 py-3 text-muted-foreground tabular-nums">{r.date || '—'}</td>
                                        <td className="px-4 py-3 text-muted-foreground max-w-xs">{r.note || '—'}</td>
                                        <td className="px-4 py-3 text-end"><Money value={r.amount} currency={currency} /></td>
                                        {(permissions.update || permissions.delete) && (
                                            <td className="px-4 py-3 text-end">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild><Button variant="ghost" size="icon" className="h-8 w-8"><MoreVertical className="h-4 w-4" /></Button></DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end" className="w-44">
                                                        <DropdownMenuItem onClick={() => window.open(r.urls.pay_slip, '_blank')}><FileText className="h-4 w-4 me-2" /> {t.pay_slip}</DropdownMenuItem>
                                                        {permissions.update && <DropdownMenuItem onClick={() => { window.location.href = r.urls.edit; }}><Edit className="h-4 w-4 me-2" /> {t.edit}</DropdownMenuItem>}
                                                        {permissions.delete && <DropdownMenuItem onClick={() => del(r)} className="text-destructive focus:text-destructive"><Trash2 className="h-4 w-4 me-2" /> {t.delete}</DropdownMenuItem>}
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
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
        </AdminLayout>
    );
}
