import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, AlertCircle, Briefcase, Wallet, UserSearch } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';
import { Textarea } from '@/Components/ui/Textarea';

function Field({ label, required, error, hint, children }) {
    return (
        <div className="space-y-1.5">
            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {label} {required && <span className="text-destructive">*</span>}
            </Label>
            {children}
            {hint && <p className="text-[11px] text-muted-foreground">{hint}</p>}
            {error && <p className="text-xs text-destructive flex items-center gap-1"><AlertCircle className="h-3 w-3" /> {error}</p>}
        </div>
    );
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

function UserPicker({ urlSearch, onChange, placeholder }) {
    const [query, setQuery] = React.useState('');
    const [results, setResults] = React.useState([]);
    const [open, setOpen] = React.useState(false);
    const [selected, setSelected] = React.useState(null);
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

    const pick = (u) => { setSelected(u); setOpen(false); setQuery(''); onChange(String(u.id), u); };

    return (
        <div ref={wrapRef} className="relative">
            {selected ? (
                <div className="flex items-center justify-between gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm">
                    <span className="truncate"><UserSearch className="inline h-3.5 w-3.5 me-1" /> {selected.text || selected.name}</span>
                    <button type="button" className="text-muted-foreground hover:text-foreground text-xs" onClick={() => { setSelected(null); onChange('', null); }}>×</button>
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

export default function Form({ mode = 'create', entity = null, lookups = {}, currency = '', urls = {}, t = {} }) {
    const isEdit = mode === 'edit';
    const todayMonth = new Date().toISOString().slice(0, 7);
    const todayDate  = new Date().toISOString().slice(0, 10);

    const form = useForm({
        month: entity?.month ?? todayMonth,
        user_id: entity?.user_id ?? '',
        date: entity?.date ?? todayDate,
        account_id: entity?.account_id ?? '',
        account_balance: entity?.account_balance ?? 0,
        amount: entity?.amount ?? '',
        note: entity?.note ?? '',
        ...(isEdit ? { id: entity?.id, _method: 'put' } : {}),
    });

    const selectedAcct = React.useMemo(
        () => (lookups.accounts || []).find((a) => String(a.value) === String(form.data.account_id)),
        [lookups.accounts, form.data.account_id],
    );

    React.useEffect(() => {
        form.setData('account_balance', selectedAcct ? selectedAcct.balance : 0);
    }, [form.data.account_id]); // eslint-disable-line react-hooks/exhaustive-deps

    const overbudget = Number(form.data.amount || 0) > Number(form.data.account_balance || 0) && selectedAcct;

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true });
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.list_title, isEdit ? t.edit : t.title]}>
            <Head title={t.title} />
            <div className="mb-4">
                <a href={urls.cancel} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40"><ArrowLeft className="h-4 w-4 me-1" /> {t.back}</a>
            </div>
            <form onSubmit={onSubmit}>
                <Card>
                    <CardContent className="p-6">
                        <div className="mb-5 flex items-center gap-2"><Briefcase className="h-5 w-5 text-primary" /><h2 className="text-lg font-semibold">{t.title}</h2></div>
                        <div className="grid gap-4 md:grid-cols-2">
                            <Field label={t.month} required error={form.errors.month}>
                                <Input type="month" value={form.data.month} onChange={(e) => form.setData('month', e.target.value)} />
                            </Field>
                            <Field label={t.from_account} required error={form.errors.account_id}>
                                <Select value={form.data.account_id} onChange={(e) => form.setData('account_id', e.target.value)}>
                                    <option value="">{t.select_account}</option>
                                    {(lookups.accounts || []).map((a) => <option key={a.value} value={a.value}>{a.label}</option>)}
                                </Select>
                                {selectedAcct && (
                                    <div className="mt-1 inline-flex items-center gap-1 text-[11px] text-muted-foreground">
                                        <Wallet className="h-3 w-3" /> {t.balance_label}: <span className="font-mono">{currency}{Number(selectedAcct.balance || 0).toFixed(2)}</span>
                                    </div>
                                )}
                            </Field>
                            <Field label={t.user} required error={form.errors.user_id}>
                                <UserPicker urlSearch={urls.user_search} onChange={(v) => form.setData('user_id', v)} placeholder={t.select_user} />
                            </Field>
                            <Field label={t.amount} required error={form.errors.amount}>
                                <div className="flex items-center">
                                    <span className="inline-flex h-9 items-center rounded-l-md border border-r-0 border-input bg-muted/40 px-2.5 text-xs font-mono text-muted-foreground">{currency}</span>
                                    <Input className="rounded-l-none" type="number" step="0.01" value={form.data.amount} onChange={(e) => form.setData('amount', e.target.value)} placeholder={t.placeholder_amount} />
                                </div>
                                {overbudget && (
                                    <p className="text-xs text-amber-700 flex items-center gap-1 mt-1"><AlertCircle className="h-3 w-3" /> {t.not_enough_balance}</p>
                                )}
                            </Field>
                            <Field label={t.date} required error={form.errors.date}>
                                <Input type="date" value={form.data.date} onChange={(e) => form.setData('date', e.target.value)} />
                            </Field>
                            <div className="md:col-span-2">
                                <Field label={t.note} error={form.errors.note}>
                                    <Textarea rows={5} value={form.data.note} onChange={(e) => form.setData('note', e.target.value)} placeholder={t.placeholder_desc} />
                                </Field>
                            </div>
                        </div>
                        <div className="mt-6 flex items-center gap-2 border-t border-border pt-4">
                            <Button type="submit" disabled={form.processing || overbudget}><Save className="h-4 w-4 me-1" /> {t.save}</Button>
                            <a href={urls.cancel} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">{t.cancel}</a>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </AdminLayout>
    );
}
