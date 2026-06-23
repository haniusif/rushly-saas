import * as React from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { ListChecks, Plus, Edit, Trash2, MoreVertical, ChevronLeft, ChevronRight, Save, X, Play, Check } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';
import { Textarea } from '@/Components/ui/Textarea';
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem } from '@/Components/ui/DropdownMenu';

const STATUS_TINT = {
    1: 'bg-amber-100 text-amber-700 border-amber-200',
    2: 'bg-sky-100 text-sky-700 border-sky-200',
    3: 'bg-emerald-100 text-emerald-700 border-emerald-200',
};

function StatusPill({ status, label }) {
    return <span className={`inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium ${STATUS_TINT[status] || STATUS_TINT[1]}`}>{label}</span>;
}

function FormModal({ open, onClose, mode, todo, lookups, urls, t }) {
    const isEdit = mode === 'edit';
    const form = useForm({
        title: todo?.title ?? '',
        description: todo?.description_full ?? '',
        user_id: String(todo?.user_id ?? ''),
        date: todo?.date_raw ?? new Date().toISOString().slice(0, 10),
        ...(isEdit ? { id: todo?.id, _method: 'put' } : {}),
    });
    React.useEffect(() => {
        if (open) form.clearErrors();
    }, [open]);
    if (!open) return null;

    const submit = (e) => {
        e.preventDefault();
        const url = isEdit ? urls.update : urls.store;
        form.post(url, { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" onClick={onClose}>
            <Card className="w-full max-w-lg" onClick={(e) => e.stopPropagation()}>
                <CardContent className="p-6">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-lg font-semibold">{isEdit ? t.edit : t.add}</h3>
                        <Button variant="ghost" size="icon" onClick={onClose}><X className="h-4 w-4" /></Button>
                    </div>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.title_field}</Label>
                            <Input className="mt-1.5" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} />
                            {form.errors.title && <p className="text-xs text-destructive mt-1">{form.errors.title}</p>}
                        </div>
                        <div>
                            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.description}</Label>
                            <Textarea rows={4} className="mt-1.5" value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.assign}</Label>
                                <Select className="mt-1.5" value={form.data.user_id} onChange={(e) => form.setData('user_id', e.target.value)}>
                                    <option value="">{t.select_user}</option>
                                    {(lookups.users || []).map((u) => <option key={u.value} value={u.value}>{u.label}</option>)}
                                </Select>
                            </div>
                            <div>
                                <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.date}</Label>
                                <Input type="date" className="mt-1.5" value={form.data.date} onChange={(e) => form.setData('date', e.target.value)} />
                            </div>
                        </div>
                        <div className="flex justify-end gap-2 pt-2 border-t border-border">
                            <Button type="button" variant="outline" onClick={onClose}>{t.cancel}</Button>
                            <Button type="submit" disabled={form.processing}><Save className="h-4 w-4 me-1" /> {t.save}</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}

function NoteModal({ open, onClose, action, todo, urls, t }) {
    const [note, setNote] = React.useState('');
    React.useEffect(() => { if (open) setNote(''); }, [open]);
    if (!open) return null;
    const submit = (e) => {
        e.preventDefault();
        const url = action === 'processing' ? urls.processing : urls.completed;
        router.post(url, { todo_id: todo.id, note }, { preserveScroll: true, onSuccess: onClose });
    };
    const title = action === 'processing' ? t.mark_processing : t.mark_completed;
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" onClick={onClose}>
            <Card className="w-full max-w-md" onClick={(e) => e.stopPropagation()}>
                <CardContent className="p-6">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-lg font-semibold">{title}</h3>
                        <Button variant="ghost" size="icon" onClick={onClose}><X className="h-4 w-4" /></Button>
                    </div>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.note}</Label>
                            <Textarea rows={4} className="mt-1.5" value={note} onChange={(e) => setNote(e.target.value)} />
                        </div>
                        <div className="flex justify-end gap-2">
                            <Button type="button" variant="outline" onClick={onClose}>{t.cancel}</Button>
                            <Button type="submit"><Save className="h-4 w-4 me-1" /> {t.save}</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}

export default function Index({ rows = [], pagination = {}, lookups = {}, permissions = {}, urls = {}, t = {} }) {
    const [formState, setFormState] = React.useState({ open: false, mode: 'create', todo: null });
    const [noteState, setNoteState] = React.useState({ open: false, action: 'processing', todo: null });

    const goPage = (u) => u && router.get(u, {}, { preserveState: true });
    const del = (r) => { if (window.confirm(t.delete_confirm)) router.delete(r.urls.delete, { preserveScroll: true }); };
    const showing = (t.showing_results || '').replace(':from', pagination.from ?? 0).replace(':to', pagination.to ?? 0).replace(':total', pagination.total ?? 0);

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title, t.list]}>
            <Head title={`${t.title} · ${t.list}`} />
            <div className="mb-3 flex items-center justify-between">
                <div className="flex items-center gap-2 text-sm text-muted-foreground"><ListChecks className="h-4 w-4" /><span>{showing}</span></div>
                {permissions.create && (
                    <Button onClick={() => setFormState({ open: true, mode: 'create', todo: null })}><Plus className="h-4 w-4 me-1" /> {t.add}</Button>
                )}
            </div>
            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                    <th className="px-4 py-3 text-start">{t.sl}</th>
                                    <th className="px-4 py-3 text-start">{t.date}</th>
                                    <th className="px-4 py-3 text-start">{t.title_field}</th>
                                    <th className="px-4 py-3 text-start">{t.description}</th>
                                    <th className="px-4 py-3 text-start">{t.assign}</th>
                                    <th className="px-4 py-3 text-start">{t.note}</th>
                                    <th className="px-4 py-3 text-start">{t.status}</th>
                                    <th className="px-4 py-3 text-start">{t.status_update}</th>
                                    <th className="px-4 py-3 text-end">{t.actions}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 && (
                                    <tr><td colSpan={9} className="px-4 py-10 text-center text-muted-foreground"><div className="flex flex-col items-center gap-2"><ListChecks className="h-10 w-10 text-muted-foreground/40" /><span>{t.no_rows}</span></div></td></tr>
                                )}
                                {rows.map((r, idx) => (
                                    <tr key={r.id} className="border-b border-border last:border-0 hover:bg-muted/20 align-top">
                                        <td className="px-4 py-3 text-muted-foreground">{(pagination.from || 1) + idx}</td>
                                        <td className="px-4 py-3 text-muted-foreground tabular-nums">{r.date || '—'}</td>
                                        <td className="px-4 py-3 font-medium">{r.title}</td>
                                        <td className="px-4 py-3 text-muted-foreground max-w-md">{r.description}</td>
                                        <td className="px-4 py-3">{r.user_name || '—'}</td>
                                        <td className="px-4 py-3 text-muted-foreground">{r.note || '—'}</td>
                                        <td className="px-4 py-3"><StatusPill status={r.status} label={r.status_label} /></td>
                                        <td className="px-4 py-3">
                                            {permissions.update && r.status !== 3 ? (
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild><Button variant="outline" size="sm">{t.status_update}</Button></DropdownMenuTrigger>
                                                    <DropdownMenuContent align="start" className="w-44">
                                                        {r.status === 1 && <DropdownMenuItem onClick={() => setNoteState({ open: true, action: 'processing', todo: r })}><Play className="h-4 w-4 me-2" /> {t.mark_processing}</DropdownMenuItem>}
                                                        {(r.status === 1 || r.status === 2) && <DropdownMenuItem onClick={() => setNoteState({ open: true, action: 'completed', todo: r })}><Check className="h-4 w-4 me-2" /> {t.mark_completed}</DropdownMenuItem>}
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            ) : <span className="text-muted-foreground text-xs">—</span>}
                                        </td>
                                        <td className="px-4 py-3 text-end">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild><Button variant="ghost" size="icon" className="h-8 w-8"><MoreVertical className="h-4 w-4" /></Button></DropdownMenuTrigger>
                                                <DropdownMenuContent align="end" className="w-40">
                                                    {permissions.update && <DropdownMenuItem onClick={() => setFormState({ open: true, mode: 'edit', todo: r })}><Edit className="h-4 w-4 me-2" /> {t.edit}</DropdownMenuItem>}
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

            <FormModal open={formState.open} onClose={() => setFormState({ ...formState, open: false })}
                mode={formState.mode} todo={formState.todo} lookups={lookups} urls={urls} t={t} />
            <NoteModal open={noteState.open} onClose={() => setNoteState({ ...noteState, open: false })}
                action={noteState.action} todo={noteState.todo} urls={urls} t={t} />
        </AdminLayout>
    );
}
