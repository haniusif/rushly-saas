import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import {
    Search, Plus, Filter, Eraser, Edit, ChevronLeft, ChevronRight,
    MoreVertical, Truck,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import {
    DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem,
} from '@/Components/ui/DropdownMenu';
import { cn } from '@/lib/utils';

const STATUS_LABELS = { 1: 'Active', 2: 'Suspended', 3: 'On leave', 4: 'Ended' };
const STATUS_STYLES = {
    1: 'bg-emerald-100 text-emerald-700 border-emerald-200',
    2: 'bg-amber-100 text-amber-700 border-amber-200',
    3: 'bg-sky-100 text-sky-700 border-sky-200',
    4: 'bg-rose-100 text-rose-700 border-rose-200',
};

function StatusPill({ status, t }) {
    const labels = {
        1: t.status_active || STATUS_LABELS[1],
        2: t.status_suspended || STATUS_LABELS[2],
        3: t.status_leave || STATUS_LABELS[3],
        4: t.status_terminated || STATUS_LABELS[4],
    };
    return (
        <span className={cn(
            'inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium',
            STATUS_STYLES[status] || 'bg-muted text-muted-foreground border-border',
        )}>
            {labels[status] || '—'}
        </span>
    );
}

function Initials({ name }) {
    const text = (name || '?').trim().split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
    return (
        <div className="grid h-9 w-9 place-items-center rounded-full bg-primary/10 text-primary text-xs font-semibold shrink-0">
            {text}
        </div>
    );
}

function Money({ value, currency }) {
    const n = Number(value || 0);
    return (
        <span className="tabular-nums">
            <span className="text-muted-foreground text-xs">{currency}</span>
            {n.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })}
        </span>
    );
}

export default function Index({
    rows = [],
    pagination = {},
    filters = {},
    permissions = {},
    currency = '',
    urls = {},
    t = {},
}) {
    const [draft, setDraft] = React.useState({
        name: filters.name || '',
        email: filters.email || '',
        phone: filters.phone || '',
    });
    const [submitting, setSubmitting] = React.useState(false);

    const submitFilter = (e) => {
        e?.preventDefault?.();
        setSubmitting(true);
        router.get(urls.filter, draft, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => setSubmitting(false),
        });
    };

    const clear = () => {
        setDraft({ name: '', email: '', phone: '' });
        router.get(urls.index, {}, { preserveState: false });
    };

    const goPage = (url) => {
        if (!url) return;
        router.get(url, {}, { preserveState: true, preserveScroll: false });
    };

    const showingTpl = t.showing_results || 'Showing :from – :to of :total';
    const showing = showingTpl
        .replace(':from', pagination.from ?? 0)
        .replace(':to', pagination.to ?? 0)
        .replace(':total', pagination.total ?? 0);

    return (
        <AdminLayout
            title={t.title || 'Couriers'}
            breadcrumbs={[t.title || 'Couriers', t.list || 'List']}
        >
            <Head title={`${t.title || 'Couriers'} · ${t.list || 'List'}`} />

            {/* Filters */}
            <Card className="mb-5">
                <CardContent className="pt-6">
                    <form onSubmit={submitFilter} className="grid gap-3 md:grid-cols-12">
                        <div className="md:col-span-3">
                            <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.name_label || 'Name'}</label>
                            <Input
                                value={draft.name}
                                onChange={(e) => setDraft((d) => ({ ...d, name: e.target.value }))}
                                placeholder={t.name_label || 'Name'}
                                className="mt-1.5"
                            />
                        </div>
                        <div className="md:col-span-3">
                            <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.email_label || 'Email'}</label>
                            <Input
                                value={draft.email}
                                onChange={(e) => setDraft((d) => ({ ...d, email: e.target.value }))}
                                placeholder={t.email_label || 'Email'}
                                className="mt-1.5"
                            />
                        </div>
                        <div className="md:col-span-3">
                            <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.phone_label || 'Phone'}</label>
                            <Input
                                value={draft.phone}
                                onChange={(e) => setDraft((d) => ({ ...d, phone: e.target.value }))}
                                placeholder={t.phone_label || 'Phone'}
                                className="mt-1.5"
                                inputMode="tel"
                            />
                        </div>
                        <div className="md:col-span-3 flex items-end gap-2">
                            <Button type="submit" disabled={submitting}>
                                <Filter className="h-4 w-4 me-1" /> {t.filter || 'Filter'}
                            </Button>
                            <Button type="button" variant="outline" onClick={clear}>
                                <Eraser className="h-4 w-4 me-1" /> {t.clear || 'Clear'}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            {/* Header strip + Add CTA */}
            <div className="mb-3 flex items-center justify-between">
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Truck className="h-4 w-4" />
                    <span>{showing}</span>
                </div>
                {permissions.create && (
                    <a href={urls.create} className="inline-flex h-9 items-center justify-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors">
                        <Plus className="h-4 w-4 me-1" /> {t.add || 'Add'}
                    </a>
                )}
            </div>

            {/* Table */}
            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                    <th className="px-4 py-3 text-start">#</th>
                                    <th className="px-4 py-3 text-start">{t.unique_id || 'ID'}</th>
                                    <th className="px-4 py-3 text-start">{t.user_label || 'User'}</th>
                                    <th className="px-4 py-3 text-start">{t.hub || 'Hub'}</th>
                                    <th className="px-4 py-3 text-end">{t.delivery_charge || 'Delivery'}</th>
                                    <th className="px-4 py-3 text-end">{t.pickup_charge || 'Pickup'}</th>
                                    <th className="px-4 py-3 text-end">{t.return_charge || 'Return'}</th>
                                    <th className="px-4 py-3 text-end">{t.current_balance || 'Balance'}</th>
                                    <th className="px-4 py-3 text-end">{t.opening_balance || 'Opening'}</th>
                                    <th className="px-4 py-3 text-start">{t.status || 'Status'}</th>
                                    {(permissions.update || permissions.delete) && (
                                        <th className="px-4 py-3 text-end">{t.actions || 'Actions'}</th>
                                    )}
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 && (
                                    <tr>
                                        <td colSpan={11} className="px-4 py-10 text-center text-muted-foreground">
                                            {t.no_rows || 'No couriers found'}
                                        </td>
                                    </tr>
                                )}
                                {rows.map((r, idx) => {
                                    const num = (pagination.from || 1) + idx;
                                    return (
                                        <tr key={r.id} className="border-b border-border last:border-0 hover:bg-muted/20 transition-colors">
                                            <td className="px-4 py-3 text-muted-foreground">{num}</td>
                                            <td className="px-4 py-3 font-mono text-xs text-muted-foreground">{r.unique_id || '—'}</td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-3">
                                                    {r.image
                                                        ? <img src={r.image} alt="" className="h-9 w-9 rounded-full object-cover" />
                                                        : <Initials name={r.name} />
                                                    }
                                                    <div className="min-w-0">
                                                        <div className="font-medium truncate">{r.name || '—'}</div>
                                                        <div className="text-xs text-muted-foreground truncate">{r.email || '—'}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">{r.hub_name || '—'}</td>
                                            <td className="px-4 py-3 text-end"><Money value={r.delivery_charge} currency={currency} /></td>
                                            <td className="px-4 py-3 text-end"><Money value={r.pickup_charge}   currency={currency} /></td>
                                            <td className="px-4 py-3 text-end"><Money value={r.return_charge}   currency={currency} /></td>
                                            <td className="px-4 py-3 text-end font-medium"><Money value={r.current_balance} currency={currency} /></td>
                                            <td className="px-4 py-3 text-end"><Money value={r.opening_balance} currency={currency} /></td>
                                            <td className="px-4 py-3"><StatusPill status={r.status} t={t} /></td>
                                            {(permissions.update || permissions.delete) && (
                                                <td className="px-4 py-3 text-end">
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" size="icon" className="h-8 w-8">
                                                                <MoreVertical className="h-4 w-4" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end" className="w-40">
                                                            {permissions.update && (
                                                                <DropdownMenuItem onClick={() => { window.location.href = r.edit_url; }}>
                                                                    <Edit className="h-4 w-4 me-2" /> {t.edit || 'Edit'}
                                                                </DropdownMenuItem>
                                                            )}
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </td>
                                            )}
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            {/* Pagination */}
            {pagination.last_page > 1 && (
                <div className="mt-4 flex items-center justify-between text-sm">
                    <div className="text-muted-foreground">{showing}</div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" disabled={!pagination.prev_url} onClick={() => goPage(pagination.prev_url)}>
                            <ChevronLeft className="h-4 w-4 me-1" /> Prev
                        </Button>
                        <span className="text-xs text-muted-foreground">
                            {pagination.current_page} / {pagination.last_page}
                        </span>
                        <Button variant="outline" size="sm" disabled={!pagination.next_url} onClick={() => goPage(pagination.next_url)}>
                            Next <ChevronRight className="h-4 w-4 ms-1" />
                        </Button>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
