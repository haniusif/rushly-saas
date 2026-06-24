import * as React from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { Droplets, Edit, Save, ArrowLeft, MoreVertical } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem } from '@/Components/ui/DropdownMenu';
import { cn } from '@/lib/utils';

function csrfToken() {
    if (typeof document === 'undefined') return '';
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function ToggleSwitch({ active, disabled, onClick }) {
    return (
        <button
            type="button"
            disabled={disabled}
            onClick={onClick}
            role="switch"
            aria-checked={active}
            className={cn(
                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors shrink-0',
                disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
                active ? 'bg-primary' : 'bg-muted-foreground/30'
            )}
        >
            <span className={cn(
                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow',
                active ? 'translate-x-6' : 'translate-x-1'
            )} />
        </button>
    );
}

export default function Index({ mode = 'view', charge = 0, active: initialActive = false, currency = '', permissions = {}, urls = {}, t = {} }) {
    const isEdit = mode === 'edit';
    const [active, setActive] = React.useState(initialActive);
    const [busy, setBusy]     = React.useState(false);

    React.useEffect(() => setActive(initialActive), [initialActive]);

    const toggle = async () => {
        if (!permissions.status_change || busy) return;
        setBusy(true);
        setActive((v) => !v);
        const fd = new FormData();
        fd.append('_token', csrfToken());
        try {
            const res = await fetch(urls.status, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
        } catch {
            setActive((v) => !v);
        } finally {
            setBusy(false);
        }
    };

    const form = useForm({ charge, _method: 'put' });
    const onSubmit = (e) => { e.preventDefault(); form.post(urls.update, { preserveScroll: true }); };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title, isEdit ? t.edit : t.view]}>
            <Head title={t.title} />
            {isEdit && (
                <div className="mb-4">
                    <a href={urls.index} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40"><ArrowLeft className="h-4 w-4 me-1" /> {t.back}</a>
                </div>
            )}
            <Card>
                <CardContent className="p-0">
                    <div className="px-5 py-4 border-b border-border flex items-center gap-2">
                        <Droplets className="h-5 w-5 text-primary" />
                        <h2 className="text-base font-semibold">{isEdit ? `${t.update} ${t.title}` : t.title}</h2>
                    </div>

                    {isEdit ? (
                        <form onSubmit={onSubmit}>
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                        <th className="px-4 py-3 text-start">{t.name}</th>
                                        <th className="px-4 py-3 text-start w-64">{t.charge}</th>
                                        <th className="px-4 py-3 text-end w-32">{t.actions}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td className="px-4 py-3 font-medium">{t.name_value}</td>
                                        <td className="px-4 py-3">
                                            <div className="flex items-center">
                                                <span className="inline-flex h-9 items-center rounded-l-md border border-r-0 border-input bg-muted/40 px-2.5 text-xs font-mono text-muted-foreground">{currency}</span>
                                                <Input className="rounded-l-none" type="number" step="0.01" value={form.data.charge} onChange={(e) => form.setData('charge', e.target.value)} autoFocus />
                                            </div>
                                            {form.errors.charge && <p className="text-xs text-destructive mt-1">{form.errors.charge}</p>}
                                        </td>
                                        <td className="px-4 py-3 text-end">
                                            <Button type="submit" size="sm" disabled={form.processing}><Save className="h-3.5 w-3.5 me-1" /> {t.update}</Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </form>
                    ) : (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                    <th className="px-4 py-3 text-start">{t.name}</th>
                                    {permissions.status_change && <th className="px-4 py-3 text-start w-32">{t.status}</th>}
                                    <th className="px-4 py-3 text-start w-40">{t.charge}</th>
                                    {permissions.update && <th className="px-4 py-3 text-end w-32">{t.actions}</th>}
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td className="px-4 py-3 font-medium">{t.name_value}</td>
                                    {permissions.status_change && (
                                        <td className="px-4 py-3">
                                            <ToggleSwitch active={active} disabled={busy} onClick={toggle} />
                                        </td>
                                    )}
                                    <td className="px-4 py-3 tabular-nums">{currency}{Number(charge || 0).toFixed(2)}</td>
                                    {permissions.update && (
                                        <td className="px-4 py-3 text-end">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild><Button variant="ghost" size="icon" className="h-8 w-8"><MoreVertical className="h-4 w-4" /></Button></DropdownMenuTrigger>
                                                <DropdownMenuContent align="end" className="w-32">
                                                    <DropdownMenuItem onClick={() => router.get(urls.edit)}><Edit className="h-4 w-4 me-2" /> {t.edit}</DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </td>
                                    )}
                                </tr>
                            </tbody>
                        </table>
                    )}
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
