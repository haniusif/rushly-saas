import * as React from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import {
    Plus, Filter, Eraser, Eye, Upload, Download, ChevronDown,
    Search, Package, Rows3, LayoutGrid, MoreVertical, Copy, Edit, Trash2,
    User, Phone, Map, MapPin, Store, History, Check, FileText, Printer,
    Route as RouteIcon, Image as ImageIcon, RefreshCcw, Ban,
} from 'lucide-react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import { Button } from '@/Components/ui/Button';
import Pagination from '@/Components/merchant/Pagination';
import {
    DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem,
    DropdownMenuSeparator,
} from '@/Components/ui/DropdownMenu';
import ShipmentDrawer from '@/Components/parcel/ShipmentDrawer';
import { cn } from '@/lib/utils';

/**
 * Merchant parcel list. Built on the same visual language as the admin list
 * (Admin/Parcel/Index): KPI chip strip, collapsed filter panel, list/card view
 * toggle, tinted status pills.
 *
 * Column-for-column with the admin list where a merchant has the data and the
 * route: actions menu, tracking id with copy, recipient block (name / phone /
 * city+area / address), shop, the COD + charge breakdown, status with the
 * updated-on stamp, attempts and 3PL courier.
 *
 * NOT ported, each for a concrete reason rather than taste:
 *   - row selection + bulk assign (deliveryman / hub / pickup date): no
 *     merchant route exists for any of them.
 *   - priority toggle: same, courier-side only.
 *   - print label: the admin route is parcel.print-label; the merchant panel
 *     has no equivalent, so the column would be a dead link.
 *   - change-status control: a merchant route DOES exist, but
 *     MerchantParcelRepository::statusUpdate() does a bare Parcel::find($id)
 *     with no ownership check and no whitelist of allowed statuses. Putting a
 *     control on it would advertise that. Flagged separately.
 *   - the CLIENT column: on a merchant's own list it is always themselves.
 *     Replaced with the shop the shipment was booked from.
 *   - the invoice column: the admin reads $p->admin_parcel_invoice, which is
 *     not a relation on Parcel and resolves to null for every row, so that
 *     column is dead there too.
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

/**
 * Tracking id with a copy button, as on the admin list. Falls back silently
 * when the clipboard API is unavailable (non-secure context, or denied).
 */
function TrackingCell({ value, href }) {
    const [copied, setCopied] = React.useState(false);
    const copy = async (e) => {
        e.preventDefault();
        e.stopPropagation();
        try {
            await navigator.clipboard.writeText(value ?? '');
            setCopied(true);
            setTimeout(() => setCopied(false), 1500);
        } catch (_) { /* clipboard unavailable — the id is still selectable */ }
    };
    return (
        <div className="space-y-0.5">
            <a href={href} className="font-mono text-xs text-primary hover:underline break-all">
                {value || '—'}
            </a>
            {value && (
                <button
                    type="button"
                    onClick={copy}
                    className="flex items-center gap-1 text-[10px] text-muted-foreground hover:text-foreground"
                >
                    {copied
                        ? <><Check className="h-3 w-3" /> Copied</>
                        : <><Copy className="h-3 w-3" /> Copy</>}
                </button>
            )}
        </div>
    );
}

function RowActions({ row, permissions, t, onDelete, onTrack }) {
    // Delivered (9) and Partial delivered (10) are terminal — the admin list
    // hides edit/delete on them and so does this one.
    const terminal = row.status === 9 || row.status === 10;
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="h-8 w-8">
                    <MoreVertical className="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" className="w-44">
                {onTrack && (
                    <>
                        <DropdownMenuItem onClick={() => onTrack(row.id)}>
                            <RouteIcon className="h-4 w-4 me-2" /> {t.track}
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                    </>
                )}
                <DropdownMenuItem onClick={() => { window.location.href = row.urls.view; }}>
                    <Eye className="h-4 w-4 me-2" /> {t.view}
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => { window.location.href = row.urls.logs; }}>
                    <History className="h-4 w-4 me-2" /> {t.logs}
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => { window.location.href = row.urls.clone; }}>
                    <Copy className="h-4 w-4 me-2" /> {t.clone}
                </DropdownMenuItem>
                {row.urls.print && (
                    <DropdownMenuItem onClick={() => { window.open(row.urls.print, '_blank'); }}>
                        <Printer className="h-4 w-4 me-2" /> {t.print}
                    </DropdownMenuItem>
                )}
                {row.urls.print_label && (
                    <DropdownMenuItem onClick={() => { window.open(row.urls.print_label, '_blank'); }}>
                        <Printer className="h-4 w-4 me-2" /> {t.print_label}
                    </DropdownMenuItem>
                )}
                {!terminal && (permissions.update || permissions.delete) && <DropdownMenuSeparator />}
                {!terminal && permissions.update && (
                    <DropdownMenuItem onClick={() => { window.location.href = row.urls.edit; }}>
                        <Edit className="h-4 w-4 me-2" /> {t.edit}
                    </DropdownMenuItem>
                )}
                {!terminal && permissions.delete && (
                    <DropdownMenuItem onClick={() => onDelete(row)} className="text-destructive focus:text-destructive">
                        <Trash2 className="h-4 w-4 me-2" /> {t.delete}
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

/**
 * Priority flag. Same inverted contract as the admin toggle: the server reads
 * `priority` and FLIPS it, so the CURRENT value is what gets posted.
 */
function PriorityToggle({ id, initial, url }) {
    const [on, setOn] = React.useState(initial === 1);
    const [busy, setBusy] = React.useState(false);
    const toggle = async () => {
        if (busy || !url) return;
        const current = on ? 1 : 2;
        const next = !on;
        setOn(next);
        setBusy(true);
        try {
            const fd = new FormData();
            fd.append('id', String(id));
            fd.append('priority', String(current));
            fd.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
            const r = await fetch(url, {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
        } catch {
            setOn(on);   // revert on failure
        } finally {
            setBusy(false);
        }
    };
    return (
        <button
            type="button"
            onClick={toggle}
            disabled={busy}
            aria-pressed={on}
            className={cn(
                'relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors',
                on ? 'bg-primary' : 'bg-muted-foreground/30',
                busy && 'opacity-60',
            )}
        >
            <span className={cn(
                'inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform',
                on ? 'translate-x-[1.15rem]' : 'translate-x-[0.15rem]',
            )} />
        </button>
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
function ParcelCard({ row, currency, t, permissions, onDelete, onTrack }) {
    return (
        <Card className="h-full">
            <CardContent className="p-4 flex flex-col gap-3 h-full">
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                        <TrackingCell value={row.tracking_id} href={row.urls?.view || row.details_url} />
                        {row.courier_name && (
                            <div className="mt-1 text-[10px] font-medium text-rose-600">3PL: {row.courier_name}</div>
                        )}
                    </div>
                    <div className="flex items-start gap-1">
                        <StatusPill label={row.status_label} color={row.status_color} />
                        <RowActions row={row} permissions={permissions} t={t} onDelete={onDelete} onTrack={onTrack} />
                    </div>
                </div>

                <div className="min-w-0 space-y-0.5">
                    {row.customer_name && (
                        <div className="flex items-center gap-1 text-sm font-medium">
                            <User className="h-3 w-3 text-muted-foreground" /> {row.customer_name}
                        </div>
                    )}
                    {row.customer_phone && (
                        <div className="flex items-center gap-1 text-[11px] text-muted-foreground">
                            <Phone className="h-3 w-3" /> {row.customer_phone}
                        </div>
                    )}
                    {(row.city || row.area) && (
                        <div className="flex items-center gap-1 text-[11px] text-muted-foreground">
                            <Map className="h-3 w-3" /> {[row.city, row.area].filter(Boolean).join(', ')}
                        </div>
                    )}
                    {row.customer_address && (
                        <div className="flex items-start gap-1 text-[11px] text-muted-foreground">
                            <MapPin className="h-3 w-3 mt-0.5 shrink-0" />
                            <span className="truncate">{row.customer_address}</span>
                        </div>
                    )}
                    {row.shop_name && (
                        <div className="flex items-center gap-1 text-[11px] text-muted-foreground">
                            <Store className="h-3 w-3" /> {row.shop_name}
                        </div>
                    )}
                </div>

                <div className="mt-auto space-y-1 border-t border-border pt-2 text-[11px]">
                    <div className="flex items-baseline justify-between gap-2">
                        <span className="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">{t.cod}</span>
                        <span className="text-sm font-bold"><Money value={row.amount} currency={currency} /></span>
                    </div>
                    <div className="flex items-baseline justify-between gap-2">
                        <span className="text-muted-foreground">{t.total_charge}</span>
                        <span className="tabular-nums"><Money value={row.total_delivery_amount} currency={currency} /></span>
                    </div>
                    <div className="flex items-baseline justify-between gap-2">
                        <span className="font-semibold">{t.current_payable}</span>
                        <span className="font-semibold tabular-nums"><Money value={row.current_payable} currency={currency} /></span>
                    </div>
                    {row.updated_at && (
                        <div className="pt-0.5 text-[10px] text-muted-foreground">
                            {t.updated_on}: {row.updated_at}
                        </div>
                    )}
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
    permissions = {},
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

    // Tracking drawer, same component the admin list opens.
    const [trackingId, setTrackingId] = React.useState(null);
    const openTracking = (id) => setTrackingId(id);

    // Row selection for the bulk bar.
    const [selected, setSelected] = React.useState([]);
    const toggleRow = (id) =>
        setSelected((v) => (v.includes(id) ? v.filter((x) => x !== id) : [...v, id]));
    const allOnPage = rows.map((r) => r.id);
    const allSelected = allOnPage.length > 0 && allOnPage.every((id) => selected.includes(id));
    const toggleAll = () => setSelected(allSelected ? [] : allOnPage);
    React.useEffect(() => {
        // Drop ids that are no longer on the page after a filter or page change.
        setSelected((v) => v.filter((id) => allOnPage.includes(id)));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [pagination?.current_page, rows.length]);

    /**
     * The one transition a merchant may make. Posts to the guarded
     * status-update endpoint, which independently re-checks ownership and that
     * the shipment is still Pending.
     */
    const changeStatus = (row) => {
        if (!row.can_cancel) return;
        if (!window.confirm(t.cancel_confirm || 'Cancel this shipment?')) return;
        router.post(`/merchant/parcel/status-update/${row.id}/41`, {}, { preserveScroll: true });
    };

    /**
     * Bulk actions post a real form rather than going through Inertia: the
     * label endpoint streams a PDF, which Inertia cannot follow.
     */
    const submitBulk = (action, confirmText) => {
        if (!selected.length) return;
        if (confirmText && !window.confirm(confirmText)) return;
        const url = action === 'labels' ? urls.bulk_print_labels : urls.bulk_cancel;
        if (!url) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        if (action === 'labels') form.target = '_blank';
        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = document.querySelector('meta[name="csrf-token"]')?.content || '';
        form.appendChild(token);
        selected.forEach((id) => {
            const i = document.createElement('input');
            i.type = 'hidden';
            i.name = 'ids[]';
            i.value = String(id);
            form.appendChild(i);
        });
        document.body.appendChild(form);
        form.submit();
    };

    const del = (row) => {
        if (!row?.urls?.delete) return;
        if (window.confirm(t.delete_confirm || 'Delete this shipment?')) {
            router.delete(row.urls.delete, { preserveScroll: true });
        }
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

            {selected.length > 0 && (
                <Card className="mb-3 border-primary/40 bg-primary/5">
                    <CardContent className="flex flex-wrap items-center gap-2 p-3">
                        <span className="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">
                            {selected.length} {t.selected}
                        </span>
                        <div className="flex flex-wrap items-center gap-2 ms-auto">
                            <Button variant="outline" size="sm" onClick={() => submitBulk('labels')}>
                                <Printer className="h-3.5 w-3.5 me-1" /> {t.bulk_print_labels}
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                className="text-destructive"
                                onClick={() => submitBulk('cancel', t.cancel_confirm)}
                            >
                                <Ban className="h-3.5 w-3.5 me-1" /> {t.bulk_cancel}
                            </Button>
                            <Button variant="ghost" size="sm" onClick={() => setSelected([])}>
                                {t.cancel}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            )}

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
                        <ParcelCard key={r.id} row={r} currency={currency} t={t} permissions={permissions} onDelete={del} onTrack={openTracking} />
                    ))}
                </div>
            ) : (
                <Card>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/30 text-[11px] uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th className="px-2.5 py-2 text-start font-medium">
                                            <input
                                                type="checkbox"
                                                checked={allSelected}
                                                onChange={toggleAll}
                                                aria-label="Select all"
                                                className="h-4 w-4 rounded border-input"
                                            />
                                        </th>
                                        <th className="px-2.5 py-2 text-start font-medium">{t.actions}</th>
                                        <th className="px-2.5 py-2 text-start font-medium">{t.tracking_id}</th>
                                        <th className="px-2.5 py-2 text-start font-medium">{t.print_label}</th>
                                        <th className="px-2.5 py-2 text-start font-medium">{t.recipient_info}</th>
                                        <th className="px-2.5 py-2 text-start font-medium">{t.shop}</th>
                                        <th className="px-2.5 py-2 text-end   font-medium">{t.amount}</th>
                                        <th className="px-2.5 py-2 text-center font-medium">{t.priority}</th>
                                        <th className="px-2.5 py-2 text-start font-medium">{t.status}</th>
                                        <th className="px-2.5 py-2 text-start font-medium">{t.status_update}</th>
                                        <th className="px-2.5 py-2 text-start font-medium">{t.payment}</th>
                                        <th className="px-2.5 py-2 text-center font-medium">{t.attempts}</th>
                                        <th className="px-2.5 py-2 text-start font-medium">{t.pod}</th>
                                        <th className="px-2.5 py-2 text-start font-medium">{t.courier_name}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {rows.map((r) => (
                                        <tr key={r.id} className={cn(
                                            'hover:bg-muted/20 transition-colors',
                                            selected.includes(r.id) && 'bg-primary/5',
                                        )}>
                                            <td className="px-2.5 py-2 align-top">
                                                <input
                                                    type="checkbox"
                                                    checked={selected.includes(r.id)}
                                                    onChange={() => toggleRow(r.id)}
                                                    className="h-4 w-4 rounded border-input"
                                                />
                                            </td>
                                            <td className="px-2.5 py-2 align-top">
                                                <RowActions row={r} permissions={permissions} t={t} onDelete={del} onTrack={openTracking} />
                                            </td>

                                            <td className="px-2.5 py-2 align-top">
                                                <TrackingCell value={r.tracking_id} href={r.urls?.view || r.details_url} />
                                                {r.invoice_id && (
                                                    <div className="text-[10px] text-muted-foreground mt-1">
                                                        {t.invoice_id}: {r.invoice_id}
                                                    </div>
                                                )}
                                                {r.courier_name && (
                                                    <div className="mt-1 text-[10px] font-medium text-rose-600">
                                                        3PL: {r.courier_name}
                                                    </div>
                                                )}
                                            </td>

                                            <td className="px-2.5 py-2 align-top">
                                                {r.urls?.print_label ? (
                                                    <a href={r.urls.print_label} target="_blank" rel="noreferrer"
                                                        title={t.print_label} className="text-rose-600">
                                                        <FileText className="h-5 w-5" />
                                                    </a>
                                                ) : <span className="text-xs text-muted-foreground">—</span>}
                                            </td>

                                            <td className="px-2.5 py-2 align-top">
                                                <div className="max-w-[220px] space-y-0.5">
                                                    {r.customer_name && (
                                                        <div className="flex items-center gap-1 text-sm font-medium">
                                                            <User className="h-3 w-3 text-muted-foreground" /> {r.customer_name}
                                                        </div>
                                                    )}
                                                    {r.customer_phone && (
                                                        <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                                            <Phone className="h-3 w-3" /> {r.customer_phone}
                                                        </div>
                                                    )}
                                                    {(r.city || r.area) && (
                                                        <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                                            <Map className="h-3 w-3" /> {[r.city, r.area].filter(Boolean).join(', ')}
                                                        </div>
                                                    )}
                                                    {r.customer_address && (
                                                        <div className="flex items-start gap-1 text-xs text-muted-foreground">
                                                            <MapPin className="h-3 w-3 mt-0.5 shrink-0" />
                                                            <span className="truncate">{r.customer_address}</span>
                                                        </div>
                                                    )}
                                                </div>
                                            </td>

                                            <td className="px-2.5 py-2 align-top">
                                                {r.shop_name ? (
                                                    <div className="flex items-center gap-1 text-xs">
                                                        <Store className="h-3 w-3 text-muted-foreground" /> {r.shop_name}
                                                    </div>
                                                ) : <span className="text-xs text-muted-foreground">—</span>}
                                            </td>

                                            <td className="px-2.5 py-2 align-top">
                                                <div className="min-w-[150px]">
                                                    <div className="flex items-baseline justify-between gap-2">
                                                        <span className="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">{t.cod}</span>
                                                        <span className="text-sm font-bold tabular-nums">
                                                            <Money value={r.amount} currency={currency} />
                                                        </span>
                                                    </div>
                                                    <div className="mt-1.5 space-y-0.5 border-t border-border/60 pt-1.5 text-[11px]">
                                                        <div className="flex items-baseline justify-between gap-2">
                                                            <span className="text-muted-foreground">{t.total_charge}</span>
                                                            <span className="tabular-nums"><Money value={r.total_delivery_amount} currency={currency} /></span>
                                                        </div>
                                                        <div className="flex items-baseline justify-between gap-2">
                                                            <span className="text-muted-foreground">{t.vat}</span>
                                                            <span className="tabular-nums"><Money value={r.vat_amount} currency={currency} /></span>
                                                        </div>
                                                        <div className="flex items-baseline justify-between gap-2 pt-0.5">
                                                            <span className="font-semibold text-foreground">{t.current_payable}</span>
                                                            <span className="font-semibold tabular-nums"><Money value={r.current_payable} currency={currency} /></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <td className="px-2.5 py-2 align-top text-center">
                                                <PriorityToggle id={r.id} initial={r.priority} url={urls.priority_status} />
                                            </td>

                                            <td className="px-2.5 py-2 align-top">
                                                <div className="space-y-1">
                                                    <StatusPill label={r.status_label} color={r.status_color} />
                                                    {r.partial_delivered && r.status !== 10 && (
                                                        <div><StatusPill label={r.partial_delivered_label} color="#16a34a" /></div>
                                                    )}
                                                    {r.updated_at && (
                                                        <div className="text-[10px] text-muted-foreground">
                                                            {t.updated_on}: {r.updated_at}
                                                        </div>
                                                    )}
                                                </div>
                                            </td>

                                            <td className="px-2.5 py-2 align-top">
                                                {r.can_cancel ? (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        className="h-8"
                                                        onClick={() => changeStatus(r)}
                                                    >
                                                        <RefreshCcw className="h-3.5 w-3.5 me-1" /> {t.change_status}
                                                    </Button>
                                                ) : (
                                                    <span className="text-xs text-muted-foreground">…</span>
                                                )}
                                            </td>

                                            <td className="px-2.5 py-2 align-top text-xs text-muted-foreground">
                                                {r.payment_label || <span>—</span>}
                                            </td>

                                            <td className="px-2.5 py-2 align-top text-center font-medium tabular-nums">
                                                {r.attempts ?? 0}
                                            </td>

                                            <td className="px-2.5 py-2 align-top">
                                                {r.urls?.delivered_info ? (
                                                    <a href={r.urls.delivered_info} target="_blank" rel="noreferrer"
                                                        className="inline-flex items-center gap-1 text-xs text-primary hover:underline">
                                                        <ImageIcon className="h-3.5 w-3.5" /> {t.pod}
                                                    </a>
                                                ) : <span className="text-xs text-muted-foreground">—</span>}
                                            </td>

                                            <td className="px-2.5 py-2 align-top text-xs">
                                                {r.courier_name || <span className="text-muted-foreground">—</span>}
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

            <ShipmentDrawer
                parcelId={trackingId}
                onClose={() => setTrackingId(null)}
                baseUrl={urls.tracking_json_base}
            />
        </MerchantLayout>
    );
}
