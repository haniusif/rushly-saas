import * as React from 'react';
import { Head, usePage } from '@inertiajs/react';
import { Truck } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
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

export default function Index({ rows: initial = [], permissions = {}, urls = {}, t = {} }) {
    const [rows, setRows] = React.useState(initial);
    const [busy, setBusy] = React.useState({});

    React.useEffect(() => setRows(initial), [initial]);

    const toggle = async (key) => {
        if (!permissions.status_change) return;
        setBusy((b) => ({ ...b, [key]: true }));
        // Optimistic flip.
        setRows((rs) => rs.map((r) => (r.key === key ? { ...r, active: !r.active } : r)));
        const fd = new FormData();
        fd.append('key', key);
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
            // Revert on failure.
            setRows((rs) => rs.map((r) => (r.key === key ? { ...r, active: !r.active } : r)));
        } finally {
            setBusy((b) => ({ ...b, [key]: false }));
        }
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title, t.list]}>
            <Head title={`${t.title} · ${t.list}`} />
            <Card>
                <CardContent className="p-0">
                    <div className="px-5 py-4 border-b border-border flex items-center gap-2">
                        <Truck className="h-5 w-5 text-primary" />
                        <h2 className="text-base font-semibold">{t.title}</h2>
                    </div>
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                <th className="px-4 py-3 text-start w-16">#</th>
                                <th className="px-4 py-3 text-start">{t.name}</th>
                                {permissions.status_change && (
                                    <th className="px-4 py-3 text-end w-32">{t.status}</th>
                                )}
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((r, idx) => (
                                <tr key={r.key} className="border-b border-border last:border-0 hover:bg-muted/20">
                                    <td className="px-4 py-3 text-muted-foreground">{idx + 1}</td>
                                    <td className="px-4 py-3 font-medium capitalize">{r.label}</td>
                                    {permissions.status_change && (
                                        <td className="px-4 py-3">
                                            <div className="flex justify-end">
                                                <ToggleSwitch
                                                    active={r.active}
                                                    disabled={!!busy[r.key]}
                                                    onClick={() => toggle(r.key)}
                                                />
                                            </div>
                                        </td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
