import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import {
    Search, Plus, Filter, Eraser, Edit, ChevronLeft, ChevronRight,
    MoreVertical, Building2, Eye, Trash2, UserCog, Phone, MapPin,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import {
    DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem,
} from '@/Components/ui/DropdownMenu';
import { cn } from '@/lib/utils';

function StatusPill({ status, t }) {
    const active = status === 1;
    return (
        <span className={cn(
            'inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium',
            active ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
                   : 'bg-rose-100 text-rose-700 border-rose-200',
        )}>
            {active ? t.status_active : t.status_inactive}
        </span>
    );
}

/**
 * One hub tile. Header shows the identity (building glyph + name + status
 * pill + action dropdown). Body is a compact identity block: phone, then
 * address. Footer is the primary "View" CTA when it's available, else
 * "Edit". Whole card is clickable to the primary destination so the
 * common path is a single click, not menu → item.
 */
function HubCard({ row, permissions, t, onDelete }) {
    const primaryHref = row.urls.view || row.urls.edit || '#';
    const primaryLabel = row.urls.view ? t.view : (permissions.update ? t.edit : '');
    const goPrimary = (e) => {
        // Don't hijack clicks that landed on a nested control (menu, links).
        if (e.target.closest('[data-stop]')) return;
        if (primaryHref !== '#') window.location.href = primaryHref;
    };
    return (
        <Card
            className={cn(
                'rounded-xl border border-border overflow-hidden transition-all',
                'hover:shadow-md hover:-translate-y-0.5',
                primaryHref !== '#' && 'cursor-pointer',
            )}
            onClick={goPrimary}
        >
            <CardContent className="p-0">
                <div className="flex items-start gap-3 px-5 pt-5 pb-3">
                    <span className="inline-grid place-items-center h-11 w-11 rounded-xl bg-primary/10 text-primary shrink-0">
                        <Building2 className="h-5 w-5" />
                    </span>
                    <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2 min-w-0">
                            <div className="text-base font-semibold truncate">{row.name || '—'}</div>
                            <StatusPill status={row.status} t={t} />
                        </div>
                    </div>
                    {(permissions.update || permissions.delete || permissions.view || permissions.incharge_read) && (
                        <div data-stop>
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="ghost" size="icon" className="h-8 w-8">
                                        <MoreVertical className="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="w-44">
                                    {permissions.view && row.urls.view && (
                                        <DropdownMenuItem onClick={() => { window.location.href = row.urls.view; }}>
                                            <Eye className="h-4 w-4 me-2" /> {t.view}
                                        </DropdownMenuItem>
                                    )}
                                    {permissions.update && (
                                        <DropdownMenuItem onClick={() => { window.location.href = row.urls.edit; }}>
                                            <Edit className="h-4 w-4 me-2" /> {t.edit}
                                        </DropdownMenuItem>
                                    )}
                                    {permissions.incharge_read && row.urls.incharge && (
                                        <DropdownMenuItem onClick={() => { window.location.href = row.urls.incharge; }}>
                                            <UserCog className="h-4 w-4 me-2" /> {t.incharge}
                                        </DropdownMenuItem>
                                    )}
                                    {permissions.delete && (
                                        <DropdownMenuItem onClick={() => onDelete(row)} className="text-destructive focus:text-destructive">
                                            <Trash2 className="h-4 w-4 me-2" /> {t.delete}
                                        </DropdownMenuItem>
                                    )}
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    )}
                </div>
                <div className="px-5 pb-4 space-y-1.5 text-sm">
                    <div className="flex items-center gap-2 text-muted-foreground">
                        <Phone className="h-3.5 w-3.5 shrink-0" />
                        <span className="truncate tabular-nums">{row.phone || '—'}</span>
                    </div>
                    <div className="flex items-start gap-2 text-muted-foreground">
                        <MapPin className="h-3.5 w-3.5 mt-0.5 shrink-0" />
                        <span className="line-clamp-2 leading-relaxed">{row.address || '—'}</span>
                    </div>
                </div>
                {primaryLabel && (
                    <div className="px-5 py-3 border-t border-border bg-muted/20 flex items-center justify-between" data-stop>
                        <a
                            href={primaryHref}
                            className="text-xs font-medium text-primary hover:underline inline-flex items-center gap-1"
                        >
                            {primaryLabel}
                            <ChevronRight className="h-3 w-3" />
                        </a>
                        {permissions.incharge_read && row.urls.incharge && (
                            <a href={row.urls.incharge} className="text-[11px] text-muted-foreground hover:text-foreground inline-flex items-center gap-1">
                                <UserCog className="h-3 w-3" />
                                {t.incharge}
                            </a>
                        )}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

export default function Index({
    rows = [], pagination = {}, filters = {}, permissions = {},
    urls = {}, t = {},
}) {
    const [draft, setDraft] = React.useState({ ...filters });
    const [submitting, setSubmitting] = React.useState(false);

    const submitFilter = (e) => {
        e?.preventDefault?.();
        setSubmitting(true);
        router.get(urls.filter, draft, {
            preserveState: true, preserveScroll: true, replace: true,
            onFinish: () => setSubmitting(false),
        });
    };
    const clear = () => {
        setDraft({ name: '', phone: '' });
        router.get(urls.index, {}, { preserveState: false });
    };
    const goPage = (url) => url && router.get(url, {}, { preserveState: true });
    const deleteRow = (r) => {
        if (!window.confirm(t.delete_confirm)) return;
        router.delete(r.urls.delete, { preserveScroll: true });
    };

    const showing = (t.showing_results || '')
        .replace(':from', pagination.from ?? 0)
        .replace(':to', pagination.to ?? 0)
        .replace(':total', pagination.total ?? 0);

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title, t.list]}>
            <Head title={`${t.title} · ${t.list}`} />

            {/* Filter bar (unchanged) */}
            <Card className="mb-5">
                <CardContent className="pt-6">
                    <form onSubmit={submitFilter} className="grid gap-3 md:grid-cols-12">
                        <div className="md:col-span-5">
                            <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.name}</label>
                            <Input value={draft.name} onChange={(e) => setDraft((d) => ({ ...d, name: e.target.value }))} placeholder={t.name} className="mt-1.5" />
                        </div>
                        <div className="md:col-span-5">
                            <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.phone}</label>
                            <Input value={draft.phone} onChange={(e) => setDraft((d) => ({ ...d, phone: e.target.value }))} placeholder={t.phone} className="mt-1.5" inputMode="tel" />
                        </div>
                        <div className="md:col-span-2 flex items-end gap-2">
                            <Button type="submit" disabled={submitting}>
                                <Filter className="h-4 w-4 me-1" /> {t.filter}
                            </Button>
                            <Button type="button" variant="outline" onClick={clear}>
                                <Eraser className="h-4 w-4 me-1" /> {t.clear}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            {/* Meta line + add button */}
            <div className="mb-3 flex items-center justify-between">
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Building2 className="h-4 w-4" />
                    <span>{showing}</span>
                </div>
                {permissions.create && (
                    <a href={urls.create} className="inline-flex h-9 items-center justify-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors">
                        <Plus className="h-4 w-4 me-1" /> {t.add}
                    </a>
                )}
            </div>

            {/* Card grid */}
            {rows.length === 0 ? (
                <Card>
                    <CardContent className="py-16 text-center">
                        <div className="flex justify-center mb-3 text-muted-foreground/40">
                            <Building2 className="h-10 w-10" />
                        </div>
                        <p className="text-sm text-muted-foreground m-0">{t.no_rows}</p>
                        {permissions.create && (
                            <a href={urls.create} className="inline-flex mt-4 items-center gap-1 text-sm text-primary hover:underline">
                                <Plus className="h-3.5 w-3.5" /> {t.add}
                            </a>
                        )}
                    </CardContent>
                </Card>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {rows.map((r) => (
                        <HubCard key={r.id} row={r} permissions={permissions} t={t} onDelete={deleteRow} />
                    ))}
                </div>
            )}

            {pagination.last_page > 1 && (
                <div className="mt-6 flex items-center justify-between text-sm">
                    <div className="text-muted-foreground">{showing}</div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" disabled={!pagination.prev_url} onClick={() => goPage(pagination.prev_url)}>
                            <ChevronLeft className="h-4 w-4 me-1" /> Prev
                        </Button>
                        <span className="text-xs text-muted-foreground">{pagination.current_page} / {pagination.last_page}</span>
                        <Button variant="outline" size="sm" disabled={!pagination.next_url} onClick={() => goPage(pagination.next_url)}>
                            Next <ChevronRight className="h-4 w-4 ms-1" />
                        </Button>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
