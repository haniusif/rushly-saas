import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
    Plus, Filter, Eraser, Eye, ListTree, Upload, Download, ChevronDown,
    Search, Package, Rows3, LayoutGrid,
} from 'lucide-react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import { Button } from '@/Components/ui/Button';
import Pagination from '@/Components/merchant/Pagination';
import { cn } from '@/lib/utils';

/**
 * Merchant parcel list. Built on the same visual language as the admin list
 * (Admin/Parcel/Index): KPI chip strip, collapsed filter panel, list/card view
 * toggle, tinted status pills.
 *
 * Deliberately NOT ported from the admin page: row selection and bulk actions
 * (assign deliveryman / hub / pickup date), the priority toggle and the inline
 * status-change modal. Those are courier-side operations — a merchant has no
 * route to perform them, so surfacing the controls would only produce dead UI.
 */

/**
 * Append the current filter state to an export URL so the download respects
 * whatever the user is currently filtering on (date, status, customer, etc.).
 * Empty values are dropped.
 */
function withFilters(url, filters) {
    if (!url) return url;
    const params = new URLSearchParams();
    Object.entries(filters || {}).forEach(([k, v]) => {
        if (v !== '' && v !== null && v !== undefined) params.set(k, String(v));
    });
    const qs = params.toString();
    if (!qs) return url;
    return url + (url.includes('?') ? '&' : '?') + qs;
}

function ExportMenu({ urls, filters, t }) {
    const [open, setOpen] = React.useState(false);
    const ref = React.useRef(null);
    React.useEffect(() => {
        const handler = (e) => { if (ref.current && !ref.current.contains(e.target)) setOpen(false); };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, []);
    return (
        <div className="relative" ref={ref}>
            <button
                type="button"
                onClick={() => setOpen((o) => !o)}
                className="inline-flex items-center gap-1.5 h-9 px-3 text-sm font-medium rounded-md border border-input bg-background hover:bg-muted/40"
            >
                <Download className="h-4 w-4" /> {t.export}
                <ChevronDown className="h-3 w-3 opacity-60" />
            </button>
            {open && (
                <div className="absolute end-0 mt-1 w-40 rounded-md border border-border bg-card shadow-md z-20 py-1">
                    <a
                        href={withFilters(urls.export_xlsx, filters)}
                        className="block px-3 py-2 text-sm hover:bg-muted/40 no-underline"
                        onClick={() => setOpen(false)}
                    >
                        {t.export_xlsx}
                    </a>
                    <a
                        href={withFilters(urls.export_csv, filters)}
                        className="block px-3 py-2 text-sm hover:bg-muted/40 no-underline"
                        onClick={() => setOpen(false)}
                    >
                        {t.export_csv}
                    </a>
                </div>
            )}
        </div>
    );
}

function Money({ value, currency }) {
    const n = Number(value) || 0;
    return (
        <span className="tabular-nums">
            <span className="text-muted-foreground text-xs me-0.5">{currency}</span>
            {n.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })}
        </span>
    );
}

// Backend (ParcelStatusHelper::color) sends a curated hex per status, the same
// source the admin list uses — so a given status looks identical on both pages.
// Rendered as a soft tint: 12% alpha fill + 30% alpha border + solid text.
const FALLBACK_HEX = '#6c757d';
const isHex = (s) => typeof s === 'string' && /^#[0-9a-fA-F]{6}$/.test(s);
const hexToRgba = (hex, alpha) => {
    const h = isHex(hex) ? hex : FALLBACK_HEX;
    const r = parseInt(h.slice(1, 3), 16);
    const g = parseInt(h.slice(3, 5), 16);
    const b = parseInt(h.slice(5, 7), 16);
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
};

function StatusPill({ label, color }) {
    const hex = isHex(color) ? color : FALLBACK_HEX;
    return (
        <span
            className="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium whitespace-nowrap"
            style={{
                backgroundColor: hexToRgba(hex, 0.12),
                borderColor: hexToRgba(hex, 0.30),
                color: hex,
            }}
        >
            {label || '—'}
        </span>
    );
}

function FilterField({ label, className, children }) {
    return (
        <div className={className}>
            <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{label}</label>
            <div className="mt-1.5">{children}</div>
        </div>
    );
}

/**
 * Status filter chips above the table. Counts come prebaked from the
 * controller as one grouped query, scoped to this merchant. Clicking a chip
 * re-runs the filter with that parcel_status; "Total" clears it.
 */
function KpiChips({ counts = {}, filterUrl, activeStatus, t = {} }) {
    // Status codes align with App\Enums\ParcelStatus.
    const chips = [
        { key: 'total',     label: t.chip_total     ?? 'Total',     tone: 'primary', status: '' },
        { key: 'pending',   label: t.chip_pending   ?? 'Pending',   tone: 'slate',   status: 1  },
        { key: 'assigned',  label: t.chip_assigned  ?? 'Assigned',  tone: 'sky',     status: 2  },
        { key: 'picked_up', label: t.chip_picked_up ?? 'Picked up', tone: 'indigo',  status: 5  },
        { key: 'ofd',       label: t.chip_ofd       ?? 'OFD',       tone: 'amber',   status: 7  },
        { key: 'delivered', label: t.chip_delivered ?? 'Delivered', tone: 'emerald', status: 9  },
        { key: 'returned',  label: t.chip_returned  ?? 'Returned',  tone: 'orange',  status: 30 },
        { key: 'cancelled', label: t.chip_cancelled ?? 'Cancelled', tone: 'rose',    status: 41 },
        { key: 'failed',    label: t.chip_failed    ?? 'Failed',    tone: 'red',     status: 8  },
    ];
    const tones = {
        primary: { pill: 'bg-primary/10 text-primary border-primary/30', dot: 'bg-primary' },
        slate:   { pill: 'bg-slate-100 text-slate-700 border-slate-200', dot: 'bg-slate-400' },
        sky:     { pill: 'bg-sky-100 text-sky-700 border-sky-200', dot: 'bg-sky-500' },
        indigo:  { pill: 'bg-indigo-100 text-indigo-700 border-indigo-200', dot: 'bg-indigo-500' },
        amber:   { pill: 'bg-amber-100 text-amber-700 border-amber-200', dot: 'bg-amber-500' },
        emerald: { pill: 'bg-emerald-100 text-emerald-700 border-emerald-200', dot: 'bg-emerald-500' },
        orange:  { pill: 'bg-orange-100 text-orange-700 border-orange-200', dot: 'bg-orange-500' },
        rose:    { pill: 'bg-rose-100 text-rose-700 border-rose-200', dot: 'bg-rose-500' },
        red:     { pill: 'bg-red-100 text-red-700 border-red-200', dot: 'bg-red-500' },
    };

    return (
        <div className="mb-4 -mx-1 overflow-x-auto">
            <div className="flex items-center gap-1.5 px-1 min-w-max">
                {chips.map((c) => {
                    const isActive = String(activeStatus ?? '') === String(c.status);
                    const tone = tones[c.tone] || tones.slate;
                    const href = c.status === ''
                        ? filterUrl
                        : `${filterUrl}${filterUrl.includes('?') ? '&' : '?'}parcel_status=${c.status}`;
                    return (
                        <a
                            key={c.key}
                            href={href}
                            className={cn(
                                'inline-flex items-center gap-2 rounded-full border px-3 h-8 text-xs font-medium whitespace-nowrap transition-all no-underline',
                                tone.pill,
                                isActive ? 'ring-2 ring-offset-1 ring-offset-background' : 'opacity-80 hover:opacity-100',
                            )}
                            style={isActive ? { '--tw-ring-color': 'var(--primary)' } : undefined}
                        >
                            <span className={cn('h-1.5 w-1.5 rounded-full', tone.dot)} />
                            <span>{c.label}</span>
                            <span className="tabular-nums font-semibold">
                                {Number(counts[c.key] ?? 0).toLocaleString()}
                            </span>
                        </a>
                    );
                })}
            </div>
        </div>
    );
}

/**
 * Compact card for one shipment — the same information as a table row,
 * reflowed for a grid. Used when the merchant picks the card view, and the
 * better default on narrow screens where a 7-column row would scroll.
 */
function ParcelCard({ row, currency, t }) {
    return (
        <Card className="h-full">
            <CardContent className="p-4 flex flex-col gap-3 h-full">
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                        <a href={row.details_url} className="text-primary hover:underline font-mono text-xs break-all">
                            {row.tracking_id}
                        </a>
                        {row.invoice_id && (
                            <div className="text-[11px] text-muted-foreground mt-0.5">
                                {t.invoice_id}: {row.invoice_id}
                            </div>
                        )}
                    </div>
                    <StatusPill label={row.status_label} color={row.status_color} />
                </div>

                <div className="min-w-0">
                    <div className="font-medium truncate">{row.customer_name}</div>
                    {row.customer_phone && (
                        <div className="text-[11px] text-muted-foreground">{row.customer_phone}</div>
                    )}
                </div>

                <div className="flex items-center justify-between gap-2 mt-auto pt-2 border-t border-border">
                    <Money value={row.amount} currency={currency} />
                    <div className="inline-flex gap-1.5">
                        <a href={row.details_url} className="inline-flex items-center gap-1.5 h-7 px-2.5 text-xs rounded-md border border-input hover:bg-muted/40 no-underline">
                            <Eye className="h-3 w-3" /> {t.view}
                        </a>
                        <a href={row.logs_url} className="inline-flex items-center gap-1.5 h-7 px-2.5 text-xs rounded-md border border-input hover:bg-muted/40 no-underline">
                            <ListTree className="h-3 w-3" /> {t.logs}
                        </a>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

export default function Index({
    rows = [],
    kpi_counts = {},
    currency = '',
    filters = {},
    lookups = {},
    pagination = null,
    urls = {},
    t = {},
}) {
    const form = useForm({
        parcel_date:           filters.parcel_date           || '',
        parcel_status:         filters.parcel_status         || '',
        parcel_customer:       filters.parcel_customer       || '',
        parcel_customer_phone: filters.parcel_customer_phone || '',
        invoice_id:            filters.invoice_id            || '',
    });

    // The date filter travels as one wire string, "YYYY-MM-DD To YYYY-MM-DD"
    // (MerchantParcelRepository::filter splits on "To"). Two date inputs are
    // easier to use than one free-text box, so split on load and rejoin on
    // change — matching how the admin list handles the same field.
    const parseDateRange = (raw) => {
        if (!raw) return { from: '', to: '' };
        const parts = String(raw).split(/\s*To\s*/i);
        return { from: (parts[0] || '').trim(), to: (parts[1] || parts[0] || '').trim() };
    };
    const initialRange = parseDateRange(filters.parcel_date);
    const [dateFrom, setDateFrom] = React.useState(initialRange.from);
    const [dateTo,   setDateTo]   = React.useState(initialRange.to);
    React.useEffect(() => {
        form.setData('parcel_date', (dateFrom && dateTo) ? `${dateFrom} To ${dateTo}` : '');
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [dateFrom, dateTo]);

    // View mode sticks per merchant. Cards are friendlier on narrow screens,
    // but the list is denser, so the list stays the default.
    const [viewMode, setViewMode] = React.useState(() => {
        if (typeof window === 'undefined') return 'list';
        try {
            return window.localStorage.getItem('merchant.parcel.view_mode') || 'list';
        } catch (_) {
            return 'list';
        }
    });
    const switchViewMode = (v) => {
        setViewMode(v);
        try {
            if (typeof window !== 'undefined') window.localStorage.setItem('merchant.parcel.view_mode', v);
        } catch (_) { /* private mode / blocked storage — the choice just won't persist */ }
    };

    // Filters collapse by default so the chip strip and the list sit above the
    // fold. Auto-open when arriving with filters already applied (a chip link,
    // for example) so what is being filtered on stays visible.
    const activeFilterCount = React.useMemo(() => (
        Object.values(filters).filter((v) => String(v ?? '').trim() !== '').length
    ), [filters]);
    const [filtersOpen, setFiltersOpen] = React.useState(() => {
        if (typeof window === 'undefined') return false;
        try {
            const stored = window.localStorage.getItem('merchant.parcel.filters_open');
            if (stored !== null) return stored === '1';
        } catch (_) { /* fall through to the active-filter default */ }
        return activeFilterCount > 0;
    });
    const toggleFilters = () => {
        setFiltersOpen((v) => {
            const next = !v;
            try {
                if (typeof window !== 'undefined') {
                    window.localStorage.setItem('merchant.parcel.filters_open', next ? '1' : '0');
                }
            } catch (_) { /* not persisting is fine */ }
            return next;
        });
    };

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.filter, { preserveScroll: true });
    };

    const showing = pagination?.total != null
        ? `${t.showing || 'Showing'} ${pagination.from ?? 0}–${pagination.to ?? 0} ${t.of || 'of'} ${pagination.total}`
        : null;

    return (
        <MerchantLayout title={t.title}>
            <Head title={`${t.title} · ${t.list}`} />

            {/* Filters — collapsed by default, with an active-count chip so the
                merchant can see at a glance whether the list is filtered. */}
            <Card className="mb-4">
                <button
                    type="button"
                    onClick={toggleFilters}
                    className="w-full flex items-center justify-between gap-3 px-5 py-3 text-start hover:bg-muted/30 transition-colors"
                    aria-expanded={filtersOpen}
                >
                    <div className="flex items-center gap-2">
                        <Filter className="h-4 w-4 text-primary" />
                        <span className="text-sm font-semibold">{t.filter}</span>
                        {activeFilterCount > 0 && (
                            <span className="inline-flex items-center rounded-full bg-primary/10 text-primary px-2 py-0.5 text-[11px] font-medium">
                                {activeFilterCount} {t.active || 'active'}
                            </span>
                        )}
                    </div>
                    <ChevronDown className={cn('h-4 w-4 text-muted-foreground transition-transform', filtersOpen && 'rotate-180')} />
                </button>
                {filtersOpen && (
                    <CardContent className="pt-2 pb-5 border-t border-border">
                        <form onSubmit={onSubmit} className="grid gap-3 md:grid-cols-12">
                            <FilterField className="md:col-span-4" label={t.date_label || t.date}>
                                <div className="flex items-center gap-1.5">
                                    <Input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="flex-1" />
                                    <span className="text-xs text-muted-foreground">→</span>
                                    <Input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} min={dateFrom || undefined} className="flex-1" />
                                </div>
                            </FilterField>
                            <FilterField className="md:col-span-3" label={t.status_label || t.status}>
                                <Select value={form.data.parcel_status} onChange={(e) => form.setData('parcel_status', e.target.value)}>
                                    <option value="">{t.status_ph || t.all}</option>
                                    {(lookups.statuses || []).map((s) => (
                                        <option key={s.value} value={s.value}>{s.label}</option>
                                    ))}
                                </Select>
                            </FilterField>
                            <FilterField className="md:col-span-2" label={t.customer}>
                                <Input value={form.data.parcel_customer} onChange={(e) => form.setData('parcel_customer', e.target.value)} placeholder={t.customer_ph} />
                            </FilterField>
                            <FilterField className="md:col-span-3" label={t.customer_phone}>
                                <Input value={form.data.parcel_customer_phone} onChange={(e) => form.setData('parcel_customer_phone', e.target.value)} placeholder={t.phone_ph} />
                            </FilterField>
                            <FilterField className="md:col-span-4" label={t.invoice_id}>
                                <div className="relative">
                                    <Search className="absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input value={form.data.invoice_id} placeholder={t.invoice_ph}
                                        onChange={(e) => form.setData('invoice_id', e.target.value)} className="ps-9" />
                                </div>
                            </FilterField>

                            <div className="md:col-span-12 flex items-center justify-end gap-2 pt-1">
                                <a href={urls.reset} className="inline-flex items-center gap-1.5 h-9 px-3 text-sm font-medium rounded-md border border-input bg-background hover:bg-muted/40 no-underline">
                                    <Eraser className="h-4 w-4" /> {t.clear}
                                </a>
                                <Button type="submit" disabled={form.processing}>
                                    <Filter className="h-4 w-4 me-1" /> {t.filter}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                )}
            </Card>

            {/* KPI chip strip — clickable status filters, counts scoped to this merchant. */}
            <KpiChips
                counts={kpi_counts}
                filterUrl={urls.filter}
                activeStatus={filters.parcel_status}
                t={t}
            />

            {/* Header strip: result count on the left, view toggle + actions right. */}
            <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Package className="h-4 w-4" />
                    {showing && <span>{showing}</span>}
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    <div className="inline-flex rounded-md border border-input overflow-hidden">
                        <button
                            type="button"
                            onClick={() => switchViewMode('list')}
                            aria-pressed={viewMode === 'list'}
                            title={t.view_list}
                            className={cn(
                                'inline-flex items-center gap-1.5 h-9 px-3 text-sm',
                                viewMode === 'list' ? 'bg-primary text-primary-foreground' : 'bg-background hover:bg-muted/40',
                            )}
                        >
                            <Rows3 className="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            onClick={() => switchViewMode('cards')}
                            aria-pressed={viewMode === 'cards'}
                            title={t.view_cards}
                            className={cn(
                                'inline-flex items-center gap-1.5 h-9 px-3 text-sm border-s border-input',
                                viewMode === 'cards' ? 'bg-primary text-primary-foreground' : 'bg-background hover:bg-muted/40',
                            )}
                        >
                            <LayoutGrid className="h-4 w-4" />
                        </button>
                    </div>

                    {urls.import && (
                        <a href={urls.import} className="inline-flex items-center gap-1.5 h-9 px-3 text-sm font-medium rounded-md border border-input bg-background hover:bg-muted/40 no-underline">
                            <Upload className="h-4 w-4" /> {t.import}
                        </a>
                    )}
                    {urls.export_xlsx && <ExportMenu urls={urls} filters={form.data} t={t} />}
                    {urls.create && (
                        <a href={urls.create} className="inline-flex items-center gap-1.5 h-9 px-3 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:opacity-90 no-underline">
                            <Plus className="h-4 w-4" /> {t.add}
                        </a>
                    )}
                </div>
            </div>

            {rows.length === 0 ? (
                <Card>
                    <CardContent className="p-10 text-center text-sm text-muted-foreground">
                        {t.empty}
                    </CardContent>
                </Card>
            ) : viewMode === 'cards' ? (
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    {rows.map((r) => (
                        <ParcelCard key={r.id} row={r} currency={currency} t={t} />
                    ))}
                </div>
            ) : (
                <Card>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/30 text-xs uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th className="text-start font-medium px-4 py-2.5 w-12">#</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.tracking_id}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.recipient_info}</th>
                                        <th className="text-end   font-medium px-4 py-2.5">{t.amount}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.status}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.payment}</th>
                                        <th className="text-end   font-medium px-4 py-2.5 w-32">{/* actions */}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {rows.map((r) => (
                                        <tr key={r.id} className="hover:bg-muted/20 transition-colors">
                                            <td className="px-4 py-2.5 tabular-nums align-top text-muted-foreground">{r.serial}</td>
                                            <td className="px-4 py-2.5 align-top">
                                                <a href={r.details_url} className="text-primary hover:underline font-mono text-xs">{r.tracking_id}</a>
                                                {r.invoice_id && <div className="text-[11px] text-muted-foreground">{t.invoice_id}: {r.invoice_id}</div>}
                                            </td>
                                            <td className="px-4 py-2.5 align-top">
                                                <div className="font-medium">{r.customer_name}</div>
                                                {r.customer_phone && <div className="text-[11px] text-muted-foreground">{r.customer_phone}</div>}
                                            </td>
                                            <td className="px-4 py-2.5 text-end align-top"><Money value={r.amount} currency={currency} /></td>
                                            <td className="px-4 py-2.5 align-top">
                                                <StatusPill label={r.status_label} color={r.status_color} />
                                            </td>
                                            <td className="px-4 py-2.5 align-top text-xs text-muted-foreground">{r.payment_label || <span>—</span>}</td>
                                            <td className="px-4 py-2.5 align-top text-end">
                                                <div className="inline-flex gap-1.5">
                                                    <a href={r.details_url} className="inline-flex items-center gap-1.5 h-7 px-2.5 text-xs rounded-md border border-input hover:bg-muted/40 no-underline">
                                                        <Eye className="h-3 w-3" /> {t.view}
                                                    </a>
                                                    <a href={r.logs_url} className="inline-flex items-center gap-1.5 h-7 px-2.5 text-xs rounded-md border border-input hover:bg-muted/40 no-underline">
                                                        <ListTree className="h-3 w-3" /> {t.logs}
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            )}

            <div className={cn(viewMode === 'cards' && 'mt-3')}>
                <Pagination pagination={pagination} />
            </div>
        </MerchantLayout>
    );
}
