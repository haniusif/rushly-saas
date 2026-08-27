import * as React from 'react';
import { router } from '@inertiajs/react';
import {
    Plus, Edit, Trash2, MoreVertical, ChevronLeft, ChevronRight,
    Search, Info, AlertCircle, Inbox,
} from 'lucide-react';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import { Label } from '@/Components/ui/Label';
import {
    DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem,
} from '@/Components/ui/DropdownMenu';
import { cn } from '@/lib/utils';

/**
 * Shared building blocks for the countries / cities / areas screens. The three
 * modules differ only in their columns and form fields, so everything else —
 * the shared-data notice, the search bar, the status pill, pagination, the
 * form field wrapper — lives here rather than being written out three times.
 */

/**
 * These tables carry no company_id, so every tenant reads the same rows and an
 * edit here is visible to all of them. Saying so on the page is the only thing
 * standing between an admin and an edit they think is local to their tenant.
 */
export function SharedDataNotice({ children }) {
    return (
        <div className="mb-4 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200">
            <Info className="mt-0.5 h-4 w-4 shrink-0" />
            <span>{children}</span>
        </div>
    );
}

export function StatusPill({ active, activeLabel, inactiveLabel }) {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-[11px] font-medium',
                active
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300'
                    : 'border-border bg-muted/40 text-muted-foreground',
            )}
        >
            <span className={cn('h-1.5 w-1.5 rounded-full', active ? 'bg-emerald-500' : 'bg-muted-foreground/50')} />
            {active ? activeLabel : inactiveLabel}
        </span>
    );
}

/**
 * Search + status (+ optional extra select) filter bar. Submits with a GET so
 * the filter state lives in the URL and stays shareable and back-button safe.
 */
export function FilterBar({ indexUrl, filters, t, extra = null, onExtraReset }) {
    const [search, setSearch] = React.useState(filters.search || '');
    const [status, setStatus] = React.useState(filters.status ?? '');

    const submit = (e) => {
        e.preventDefault();
        const params = { ...(filters.__extra || {}) };
        if (search) params.search = search;
        if (status !== '') params.status = status;
        if (extra?.value) params[extra.name] = extra.value;
        router.get(indexUrl, params, { preserveState: true, preserveScroll: true });
    };

    const clear = () => {
        setSearch('');
        setStatus('');
        onExtraReset?.();
        router.get(indexUrl, {}, { preserveState: true, preserveScroll: true });
    };

    return (
        <Card className="mb-4">
            <CardContent className="p-4">
                <form onSubmit={submit} className="flex flex-wrap items-end gap-3">
                    <div className="min-w-[220px] flex-1 space-y-1.5">
                        <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                            {t.search}
                        </Label>
                        <div className="relative">
                            <Search className="absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder={t.search_ph}
                                className="ps-9"
                            />
                        </div>
                    </div>

                    {extra && (
                        <div className="min-w-[180px] space-y-1.5">
                            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                {extra.label}
                            </Label>
                            <Select value={extra.value} onChange={(e) => extra.onChange(e.target.value)}>
                                <option value="">{t.all}</option>
                                {extra.options.map((o) => (
                                    <option key={o.id} value={o.id}>{o.name}</option>
                                ))}
                            </Select>
                        </div>
                    )}

                    <div className="min-w-[150px] space-y-1.5">
                        <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                            {t.status}
                        </Label>
                        <Select value={status} onChange={(e) => setStatus(e.target.value)}>
                            <option value="">{t.all}</option>
                            <option value="1">{t.active}</option>
                            <option value="0">{t.inactive}</option>
                        </Select>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button type="submit">
                            <Search className="me-1 h-4 w-4" /> {t.search}
                        </Button>
                        <Button type="button" variant="outline" onClick={clear}>
                            {t.cancel}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

export function RowActions({ row, permissions, t, onDelete }) {
    if (!permissions.update && !permissions.delete) return null;
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="h-8 w-8">
                    <MoreVertical className="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-44">
                {permissions.update && (
                    <DropdownMenuItem onClick={() => { window.location.href = row.urls.edit; }}>
                        <Edit className="me-2 h-4 w-4" /> {t.edit}
                    </DropdownMenuItem>
                )}
                {permissions.delete && (
                    <DropdownMenuItem
                        onClick={() => onDelete(row)}
                        className="text-destructive focus:text-destructive"
                    >
                        <Trash2 className="me-2 h-4 w-4" /> {t.delete}
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

export function EmptyRow({ colSpan, label }) {
    return (
        <tr>
            <td colSpan={colSpan} className="px-4 py-12 text-center text-muted-foreground">
                <div className="flex flex-col items-center gap-2">
                    <Inbox className="h-10 w-10 text-muted-foreground/40" />
                    <span>{label}</span>
                </div>
            </td>
        </tr>
    );
}

export function Pager({ pagination, t }) {
    if (!pagination || pagination.last_page <= 1) return null;
    const go = (u) => u && router.get(u, {}, { preserveState: true, preserveScroll: true });
    const showing = (t.showing_results || '')
        .replace(':from', pagination.from ?? 0)
        .replace(':to', pagination.to ?? 0)
        .replace(':total', pagination.total ?? 0);
    return (
        <div className="mt-4 flex items-center justify-between text-sm">
            <div className="text-muted-foreground">{showing}</div>
            <div className="flex items-center gap-2">
                <Button variant="outline" size="sm" disabled={!pagination.prev_url} onClick={() => go(pagination.prev_url)}>
                    <ChevronLeft className="me-1 h-4 w-4" /> {t.prev}
                </Button>
                <span className="text-xs text-muted-foreground">
                    {pagination.current_page} / {pagination.last_page}
                </span>
                <Button variant="outline" size="sm" disabled={!pagination.next_url} onClick={() => go(pagination.next_url)}>
                    {t.next} <ChevronRight className="ms-1 h-4 w-4" />
                </Button>
            </div>
        </div>
    );
}

export function ListHeader({ showing, createUrl, canCreate, addLabel }) {
    return (
        <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div className="text-sm text-muted-foreground">{showing}</div>
            {canCreate && createUrl && (
                <a
                    href={createUrl}
                    className="inline-flex h-9 items-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground no-underline hover:opacity-90"
                >
                    <Plus className="me-1 h-4 w-4" /> {addLabel}
                </a>
            )}
        </div>
    );
}

export function Field({ label, required, error, hint, children }) {
    return (
        <div className="space-y-1.5">
            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {label} {required && <span className="text-destructive">*</span>}
            </Label>
            {children}
            {hint && !error && <p className="text-xs text-muted-foreground">{hint}</p>}
            {error && (
                <p className="flex items-center gap-1 text-xs text-destructive">
                    <AlertCircle className="h-3 w-3" /> {error}
                </p>
            )}
        </div>
    );
}

/** Checkbox-backed active toggle. There is no Switch component in the kit. */
export function ActiveToggle({ checked, onChange, label }) {
    return (
        <label className="inline-flex cursor-pointer items-center gap-2 text-sm">
            <input
                type="checkbox"
                checked={!!checked}
                onChange={(e) => onChange(e.target.checked)}
                className="h-4 w-4 rounded border-input accent-[var(--primary)]"
            />
            <span>{label}</span>
        </label>
    );
}
