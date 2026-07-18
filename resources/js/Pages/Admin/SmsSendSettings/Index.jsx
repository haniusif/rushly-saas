import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { MessageSquare, Info, ChevronLeft, ChevronRight } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { cn } from '@/lib/utils';

function Switch({ checked, disabled, onChange, ariaLabel }) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={checked}
            aria-label={ariaLabel}
            disabled={disabled}
            onClick={() => onChange(!checked)}
            className={cn(
                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors shrink-0',
                checked ? 'bg-primary' : 'bg-muted-foreground/30',
                disabled && 'opacity-50 cursor-not-allowed',
            )}
        >
            <span className={cn(
                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow',
                checked ? 'translate-x-6' : 'translate-x-1',
            )} />
        </button>
    );
}

export default function Index({ rows = [], pagination = {}, permissions = {}, urls = {}, t = {} }) {
    // Optimistic local state — the server round-trip is instant, but a
    // checkbox that visually snaps back on server refresh feels laggy.
    const [pending, setPending] = React.useState(new Set());

    const toggle = (row) => {
        if (!permissions.toggle) return;
        setPending((s) => new Set(s).add(row.id));
        router.post(urls.toggle, { id: row.id }, {
            preserveScroll: true,
            onFinish: () => setPending((s) => { const n = new Set(s); n.delete(row.id); return n; }),
        });
    };

    const goPage = (u) => u && router.get(u, {}, { preserveState: true });
    const showing = (t.showing_results || '')
        .replace(':from', pagination.from ?? 0)
        .replace(':to', pagination.to ?? 0)
        .replace(':total', pagination.total ?? 0);

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title, t.list]}>
            <Head title={`${t.title} · ${t.list}`} />
            <div className="mb-3 flex items-center justify-between">
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <MessageSquare className="h-4 w-4" /><span>{showing}</span>
                </div>
            </div>

            <div className="mb-4 flex items-start gap-2 rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-xs text-sky-800">
                <Info className="h-4 w-4 shrink-0 mt-0.5" />
                <p className="m-0">{t.hint}</p>
            </div>

            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                    <th className="px-4 py-3 text-start w-16">{t.id}</th>
                                    <th className="px-4 py-3 text-start">{t.event}</th>
                                    {permissions.toggle && <th className="px-4 py-3 text-end w-32">{t.status}</th>}
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 && (
                                    <tr>
                                        <td colSpan={permissions.toggle ? 3 : 2} className="px-4 py-10 text-center text-muted-foreground">
                                            <div className="flex flex-col items-center gap-2">
                                                <MessageSquare className="h-10 w-10 text-muted-foreground/40" />
                                                <span>{t.no_rows}</span>
                                            </div>
                                        </td>
                                    </tr>
                                )}
                                {rows.map((r) => (
                                    <tr key={r.id} className="border-b border-border last:border-0 hover:bg-muted/20">
                                        <td className="px-4 py-3 text-muted-foreground tabular-nums">{r.id}</td>
                                        <td className="px-4 py-3 font-medium">
                                            <div>{r.event_label}</div>
                                            <div className="text-[11px] text-muted-foreground mt-0.5">
                                                {r.is_active ? t.active : t.inactive}
                                            </div>
                                        </td>
                                        {permissions.toggle && (
                                            <td className="px-4 py-3">
                                                <div className="flex justify-end">
                                                    <Switch
                                                        checked={r.is_active}
                                                        disabled={pending.has(r.id)}
                                                        onChange={() => toggle(r)}
                                                        ariaLabel={r.event_label}
                                                    />
                                                </div>
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
                        <Button variant="outline" size="sm" disabled={!pagination.prev_url} onClick={() => goPage(pagination.prev_url)}>
                            <ChevronLeft className="h-4 w-4 me-1" /> {t.prev}
                        </Button>
                        <span className="text-xs text-muted-foreground">{pagination.current_page} / {pagination.last_page}</span>
                        <Button variant="outline" size="sm" disabled={!pagination.next_url} onClick={() => goPage(pagination.next_url)}>
                            {t.next} <ChevronRight className="h-4 w-4 ms-1" />
                        </Button>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
