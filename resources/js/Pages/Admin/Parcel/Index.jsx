import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import {
    Search, Plus, Filter, Eraser, Edit, ChevronLeft, ChevronRight, ChevronDown,
    MoreVertical, Package, Eye, History, Copy, Printer, Trash2, User,
    Phone, MapPin, Truck, Flame, Map, Download, Upload, FileText,
    Receipt, RefreshCcw, Check, Route as RouteIcon, LayoutGrid, List,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import {
    DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator,
} from '@/Components/ui/DropdownMenu';
import ShipmentDrawer from '@/Components/parcel/ShipmentDrawer';
import ChangeStatusModal from '@/Components/parcel/ChangeStatusModal';
import { cn } from '@/lib/utils';

function Money({ value, currency }) {
    const n = Number(value || 0);
    return (
        <span className="tabular-nums">
            <span className="text-muted-foreground text-xs me-0.5">{currency}</span>
            {n.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })}
        </span>
    );
}

// Backend (ParcelStatusHelper::color) sends a curated hex per status:
// e.g. PENDING #6c757d, DELIVERED #16a34a, NDR_CREATED #ef4444, *_CANCEL #475569.
// We render a soft tint pill: 12% alpha fill + 30% alpha border + solid text.
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

function TrackingCell({ value, parcelId, onTrack }) {
    const [copied, setCopied] = React.useState(false);
    const copy = (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (!value) return;
        navigator.clipboard?.writeText(value).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 1200);
        });
    };
    return (
        <div className="space-y-1">
            <button
                type="button"
                onClick={() => onTrack(parcelId)}
                className="font-mono text-xs text-primary hover:underline underline-offset-2 text-start"
                title="Open shipment drawer"
            >
                {value || '—'}
            </button>
            <button
                type="button"
                onClick={copy}
                className={cn(
                    'block text-[10px] text-muted-foreground hover:text-foreground transition-colors',
                    copied && 'text-emerald-600',
                )}
                title="Copy tracking ID"
            >
                {copied ? 'Copied ✓' : (
                    <span className="inline-flex items-center gap-1"><Copy className="h-2.5 w-2.5" /> Copy</span>
                )}
            </button>
        </div>
    );
}

function PriorityToggle({ id, initial, url }) {
    const [on, setOn] = React.useState(initial === 1);
    const [busy, setBusy] = React.useState(false);
    const toggle = async () => {
        if (busy) return;
        // Server reads `priority` (NOT `value`) and FLIPS it: if it
        // receives 1, it stores 2; if anything else, stores 1.
        // So we send the CURRENT value before the flip, and update local
        // state optimistically to the flipped value.
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
            className={cn(
                'relative inline-flex h-5 w-9 items-center rounded-full transition-colors',
                on ? 'bg-rose-500' : 'bg-muted-foreground/30',
                busy && 'opacity-60',
            )}
            title={on ? 'High priority' : 'Normal'}
        >
            <span className={cn(
                'inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform',
                on ? 'translate-x-4' : 'translate-x-0.5',
            )} />
        </button>
    );
}

export default function Index({
    rows = [],
    kpi_counts = {},
    pagination = {},
    filters = {},
    lookups = {},
    permissions = {},
    currency = '',
    urls = {},
    t = {},
}) {
    const [draft, setDraft] = React.useState({ ...filters });
    const [search, setSearch] = React.useState(filters.search || '');

    // Date filter is wire-format "YYYY-MM-DD To YYYY-MM-DD" (matches legacy repo parser).
    // We split it into two date inputs in the UI and rejoin on submit/change.
    const parseDateRange = (raw) => {
        if (!raw) return { from: '', to: '' };
        const parts = String(raw).split(/\s*To\s*/i);
        return { from: (parts[0] || '').trim(), to: (parts[1] || parts[0] || '').trim() };
    };
    const initialRange = parseDateRange(filters.parcel_date);
    const [dateFrom, setDateFrom] = React.useState(initialRange.from);
    const [dateTo,   setDateTo]   = React.useState(initialRange.to);
    React.useEffect(() => {
        const combined = (dateFrom && dateTo) ? `${dateFrom} To ${dateTo}` : '';
        setDraft((d) => ({ ...d, parcel_date: combined }));
    }, [dateFrom, dateTo]);
    const [submitting, setSubmitting] = React.useState(false);
    const [selected, setSelected] = React.useState([]);
    const [bulkType, setBulkType] = React.useState('');

    // Persist the operator's chosen view mode across visits. Default to
    // list on desktop (dense; 14 columns is only readable at scale) and
    // fall back to the localStorage pick when present. Cards preferred on
    // narrower viewports since the row would horizontally scroll.
    const [viewMode, setViewMode] = React.useState(() => {
        if (typeof window === 'undefined') return 'list';
        return window.localStorage.getItem('parcel.view_mode') || 'list';
    });
    const switchViewMode = (v) => {
        setViewMode(v);
        if (typeof window !== 'undefined') window.localStorage.setItem('parcel.view_mode', v);
    };

    // Filters are collapsed by default so the KPI chip strip + table sit
    // above the fold. If the operator has a non-empty filter set (arrived
    // via a filter link from /summary, for example) auto-open so the
    // active filters are visible without an extra click. The user's own
    // expand choice sticks via localStorage after the first toggle.
    const hasActiveFilters = React.useMemo(() => (
        Object.entries(filters).some(([k, v]) => k !== 'search' && String(v ?? '').trim() !== '')
    ), [filters]);
    const [filtersOpen, setFiltersOpen] = React.useState(() => {
        if (typeof window === 'undefined') return false;
        const stored = window.localStorage.getItem('parcel.filters_open');
        if (stored !== null) return stored === '1';
        return hasActiveFilters;
    });
    const toggleFilters = () => {
        setFiltersOpen((v) => {
            const next = !v;
            if (typeof window !== 'undefined') window.localStorage.setItem('parcel.filters_open', next ? '1' : '0');
            return next;
        });
    };
    const activeFilterCount = React.useMemo(() => (
        Object.entries(filters).filter(([k, v]) => k !== 'search' && String(v ?? '').trim() !== '').length
    ), [filters]);

    const [bulkInputs, setBulkInputs] = React.useState({ pickup_date: '', deliveryman_id: '', hub_id: '' });
    const [trackingId, setTrackingId] = React.useState(null);
    const openTracking = (id) => setTrackingId(id);
    const closeTracking = () => setTrackingId(null);

    // Inline status-change modal: { parcel, transition } | null
    const [statusChange, setStatusChange] = React.useState(null);
    const openStatusChange = (parcel, transition) => setStatusChange({ parcel, transition });
    const closeStatusChange = () => setStatusChange(null);
    const onStatusChanged = () => {
        closeStatusChange();
        router.reload({ preserveScroll: true });
    };

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

    const submitSearch = (e) => {
        e?.preventDefault?.();
        router.get(urls.specific_search, { search }, { preserveState: true });
    };

    const clear = () => {
        setDraft({
            parcel_date: '', parcel_status: '', parcel_merchant_id: '',
            parcel_deliveryman_id: '', parcel_pickupman_id: '', invoice_id: '',
            has_3pl: '', search: '',
        });
        setSearch('');
        setDateFrom('');
        setDateTo('');
        router.get(urls.index, {}, { preserveState: false });
    };

    const goPage = (url) => url && router.get(url, {}, { preserveState: true, preserveScroll: false });
    const changePerPage = (n) => router.get(urls.index, { per_page: n }, { preserveState: false });

    const toggle = (id) => setSelected((s) => s.includes(id) ? s.filter((x) => x !== id) : [...s, id]);
    const toggleAll = () => setSelected((s) => s.length === rows.length ? [] : rows.map((r) => r.id));

    const deleteRow = (row) => {
        if (!window.confirm(t.delete_confirm || 'Delete this parcel?')) return;
        router.delete(row.urls.delete, { preserveScroll: true });
    };

    const printSelectedLabels = () => {
        if (!selected.length) return;
        const form = document.createElement('form');
        form.action = urls.multiple_print_label;
        form.method = 'GET';
        form.target = '_blank';
        selected.forEach((id) => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'parcels[][]';
            inp.value = id;
            form.appendChild(inp);
        });
        document.body.appendChild(form);
        form.submit();
        form.remove();
    };

    const applyBulk = () => {
        if (!selected.length) { window.alert(t.bulk_select_first || 'Select at least one parcel first.'); return; }
        const parcels = selected.map((id) => ({ [id]: id }));
        const data = { parcels };
        // Modern change_status flow: shipment_ids is a plain string of numeric
        // parcel IDs joined by commas. The apply endpoint's splitIds() picks
        // those up via the numeric-id branch — no tracking-id lookup needed.
        const shipmentIdsString = selected.join(',');

        if (bulkType === 'received_by_hub_multiple_parcel') {
            if (!bulkInputs.hub_id) { window.alert(t.bulk_pick_hub || 'Pick a hub.'); return; }
            router.post(urls.bulk_action_apply, {
                shipment_ids: shipmentIdsString,
                action_type:  'change_status',
                status:       19, // ParcelStatus::RECEIVED_BY_HUB
                hub_id:       bulkInputs.hub_id,
            }, {
                preserveScroll: false,
                onSuccess: () => { setSelected([]); setBulkType(''); },
            });
            return;
        }
        if (bulkType === 'delivery_man_assign_multiple_parcel') {
            if (!bulkInputs.deliveryman_id) { window.alert(t.bulk_pick_courier || 'Pick a courier.'); return; }
            router.post(urls.bulk_deliveryman_assign, { ...data, deliveryman_id: bulkInputs.deliveryman_id }, {
                preserveScroll: false,
                onSuccess: () => { setSelected([]); setBulkType(''); },
            });
            return;
        }
        if (bulkType === 'transfer_to_hub_multiple_parcel') {
            if (!bulkInputs.hub_id) { window.alert(t.bulk_pick_hub || 'Pick a hub.'); return; }
            router.post(urls.bulk_transfer_to_hub, { ...data, hub_id: bulkInputs.hub_id }, {
                preserveScroll: false,
                onSuccess: () => { setSelected([]); setBulkType(''); },
            });
            return;
        }
        if (bulkType === 'assignpickupbulk') {
            if (!bulkInputs.deliveryman_id || !bulkInputs.pickup_date) {
                window.alert(t.bulk_pick_date || 'Pick courier + date.'); return;
            }
            router.post(urls.bulk_pickup_assign, {
                ...data,
                pickup_deliveryman_id: bulkInputs.deliveryman_id,
                pickup_date: bulkInputs.pickup_date,
            }, {
                preserveScroll: false,
                onSuccess: () => { setSelected([]); setBulkType(''); },
            });
            return;
        }
        if (bulkType === 'assign_return_merchant') {
            // Return-flow parcels need a courier + date, same as pickup.
            if (!bulkInputs.deliveryman_id || !bulkInputs.pickup_date) {
                window.alert(t.bulk_pick_date || 'Pick courier + date.'); return;
            }
            router.post(urls.bulk_action_apply, {
                shipment_ids:    shipmentIdsString,
                action_type:     'change_status',
                status:          26, // ParcelStatus::RETURN_ASSIGN_TO_MERCHANT
                delivery_man_id: bulkInputs.deliveryman_id,
                date:            bulkInputs.pickup_date,
            }, {
                preserveScroll: false,
                onSuccess: () => { setSelected([]); setBulkType(''); },
            });
            return;
        }
    };

    const showing = (t.showing_results || 'Showing :from – :to of :total')
        .replace(':from', pagination.from ?? 0)
        .replace(':to', pagination.to ?? 0)
        .replace(':total', pagination.total ?? 0);

    const exportLabel = (t.export_label || 'Export :TOTAL Shipments').replace(':TOTAL', pagination.total ?? 0);

    return (
        <AdminLayout title={t.title}>
            <Head title={`${t.title} · ${t.list}`} />

            {/* Filters — collapsed by default so the KPI strip + table sit
                above the fold. Header row stays visible with an active-filter
                count chip so the operator sees at a glance whether the list
                is filtered. */}
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
                                {activeFilterCount} active
                            </span>
                        )}
                    </div>
                    <ChevronDown className={cn('h-4 w-4 text-muted-foreground transition-transform', filtersOpen && 'rotate-180')} />
                </button>
                {filtersOpen && (
                <CardContent className="pt-2 pb-5 border-t border-border">
                    <form onSubmit={submitFilter} className="grid gap-3 md:grid-cols-12">
                        <FilterField className="md:col-span-3" label={t.date_label}>
                            <div className="flex items-center gap-1.5">
                                <Input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="flex-1" />
                                <span className="text-xs text-muted-foreground">→</span>
                                <Input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} min={dateFrom || undefined} className="flex-1" />
                            </div>
                        </FilterField>
                        <FilterField className="md:col-span-2" label={t.status_label}>
                            <Select value={draft.parcel_status || ''} onChange={(e) => setDraft((d) => ({ ...d, parcel_status: e.target.value }))}>
                                <option value="">{t.all}</option>
                                {(lookups.statuses || []).map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                            </Select>
                        </FilterField>
                        <FilterField className="md:col-span-2" label={t.merchant_label}>
                            <Select value={draft.parcel_merchant_id || ''} onChange={(e) => setDraft((d) => ({ ...d, parcel_merchant_id: e.target.value }))}>
                                <option value="">{t.all}</option>
                                {(lookups.merchants || []).map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                            </Select>
                        </FilterField>
                        <FilterField className="md:col-span-2" label={t.deliveryman_label}>
                            <Select value={draft.parcel_deliveryman_id || ''} onChange={(e) => setDraft((d) => ({ ...d, parcel_deliveryman_id: e.target.value }))}>
                                <option value="">{t.all}</option>
                                {(lookups.deliverymen || []).map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                            </Select>
                        </FilterField>
                        <FilterField className="md:col-span-2" label={t.pickup_label}>
                            <Select value={draft.parcel_pickupman_id || ''} onChange={(e) => setDraft((d) => ({ ...d, parcel_pickupman_id: e.target.value }))}>
                                <option value="">{t.all}</option>
                                {(lookups.deliverymen || []).map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                            </Select>
                        </FilterField>
                        <FilterField className="md:col-span-2" label={t.invoice_id}>
                            <div className="relative">
                                <Search className="absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input value={draft.invoice_id || ''} placeholder={t.awb_invoice}
                                    onChange={(e) => setDraft((d) => ({ ...d, invoice_id: e.target.value }))} className="ps-9" />
                            </div>
                        </FilterField>
                        <FilterField className="md:col-span-2" label={t.three_pl}>
                            <Select value={draft.has_3pl || ''} onChange={(e) => setDraft((d) => ({ ...d, has_3pl: e.target.value }))}>
                                <option value="">{t.all}</option>
                                <option value="panda">{t.panda}</option>
                            </Select>
                        </FilterField>

                        <div className="md:col-span-12 flex items-center justify-end gap-2 pt-1">
                            <Button type="button" variant="outline" onClick={clear}>
                                <Eraser className="h-4 w-4 me-1" /> {t.clear}
                            </Button>
                            <Button type="submit" disabled={submitting}>
                                <Filter className="h-4 w-4 me-1" /> {t.filter}
                            </Button>
                        </div>
                    </form>
                </CardContent>
                )}
            </Card>

            {/* Header strip */}
            <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div className="flex items-center gap-3 text-sm text-muted-foreground">
                    <Package className="h-4 w-4" />
                    <span>{showing}</span>
                    {selected.length > 0 && (
                        <span className="rounded-full bg-primary/10 text-primary px-2 py-0.5 text-xs font-medium">
                            {selected.length} selected
                        </span>
                    )}
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    {/* View mode toggle — list (default) vs card grid.
                        Choice persists per-user via localStorage. */}
                    <div className="inline-flex rounded-md border border-input overflow-hidden">
                        <button
                            type="button"
                            onClick={() => switchViewMode('list')}
                            className={cn(
                                'inline-flex h-9 items-center px-2.5 text-xs font-medium transition-colors',
                                viewMode === 'list'
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-background text-muted-foreground hover:bg-accent'
                            )}
                            title="List view"
                            aria-pressed={viewMode === 'list'}
                        >
                            <List className="h-3.5 w-3.5" />
                        </button>
                        <button
                            type="button"
                            onClick={() => switchViewMode('card')}
                            className={cn(
                                'inline-flex h-9 items-center px-2.5 text-xs font-medium transition-colors border-s border-input',
                                viewMode === 'card'
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-background text-muted-foreground hover:bg-accent'
                            )}
                            title="Card view"
                            aria-pressed={viewMode === 'card'}
                        >
                            <LayoutGrid className="h-3.5 w-3.5" />
                        </button>
                    </div>
                    {selected.length > 0 && (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={printSelectedLabels}
                            className="border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100"
                            title={`Print AWBs for ${selected.length} selected`}
                        >
                            <Printer className="h-4 w-4 me-1" />
                            {t.print_awbs || 'Print AWBs'} ({selected.length})
                        </Button>
                    )}
                    <a href={urls.parcel_map} target="_blank" rel="noreferrer" className="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent transition-colors">
                        <Map className="h-4 w-4 me-1" /> {t.map_label}
                    </a>
                    <a href={urls.export} className="inline-flex h-9 items-center justify-center rounded-md border border-sky-200 bg-sky-50 text-sky-700 px-3 text-sm font-medium hover:bg-sky-100 transition-colors">
                        <Download className="h-4 w-4 me-1" /> {exportLabel}
                    </a>
                    <a href={urls.import} className="inline-flex h-9 items-center justify-center rounded-md border border-emerald-200 bg-emerald-50 text-emerald-700 px-3 text-sm font-medium hover:bg-emerald-100 transition-colors">
                        <Upload className="h-4 w-4 me-1" /> {t.import_label}
                    </a>
                    <label className="text-xs text-muted-foreground ms-2">{t.per_page}</label>
                    <Select value={pagination.per_page || 10} onChange={(e) => changePerPage(e.target.value)} className="h-9 w-20">
                        {[10, 20, 50, 100, 500, 1000].map((n) => <option key={n} value={n}>{n}</option>)}
                    </Select>
                    {permissions.create && (
                        <a href={urls.create} className="inline-flex h-9 items-center justify-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors">
                            <Plus className="h-4 w-4 me-1" /> {t.add}
                        </a>
                    )}
                </div>
            </div>

            {/* KPI chip strip — clickable status filters. Each chip re-runs
                parcel.filter with the corresponding parcel_status value. */}
            <KpiChips
                counts={kpi_counts}
                filterUrl={urls.filter}
                activeStatus={filters.parcel_status}
                t={t}
            />

            {/* Bulk action panel — hidden until at least one row is selected,
                so it doesn't eat vertical real estate on every visit. */}
            {permissions.status_update && selected.length > 0 && (
                <Card className="mb-4">
                    <CardContent className="pt-4 pb-4">
                        <div className="flex flex-wrap items-end gap-3">
                            <div className="grow min-w-[200px]">
                                <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.bulk_actions}</label>
                                <Select value={bulkType} onChange={(e) => setBulkType(e.target.value)} className="mt-1.5">
                                    <option value="">{t.bulk_actions}</option>
                                    <option value="assignpickupbulk">{t.bulk_pickup}</option>
                                    <option value="transfer_to_hub_multiple_parcel">{t.bulk_hub_transfer}</option>
                                    <option value="received_by_hub_multiple_parcel">{t.bulk_hub_received}</option>
                                    <option value="delivery_man_assign_multiple_parcel">{t.bulk_dman_assign}</option>
                                    <option value="assign_return_merchant">{t.bulk_return_merch}</option>
                                </Select>
                            </div>

                            {bulkType === 'assignpickupbulk' && (
                                <>
                                    <BulkInput label={t.deliveryman_label}>
                                        <Select value={bulkInputs.deliveryman_id} onChange={(e) => setBulkInputs((b) => ({ ...b, deliveryman_id: e.target.value }))}>
                                            <option value="">—</option>
                                            {(lookups.deliverymen || []).map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                                        </Select>
                                    </BulkInput>
                                    <BulkInput label={t.date_label}>
                                        <Input type="date" value={bulkInputs.pickup_date} onChange={(e) => setBulkInputs((b) => ({ ...b, pickup_date: e.target.value }))} />
                                    </BulkInput>
                                </>
                            )}
                            {bulkType === 'delivery_man_assign_multiple_parcel' && (
                                <BulkInput label={t.deliveryman_label}>
                                    <Select value={bulkInputs.deliveryman_id} onChange={(e) => setBulkInputs((b) => ({ ...b, deliveryman_id: e.target.value }))}>
                                        <option value="">—</option>
                                        {(lookups.deliverymen || []).map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                                    </Select>
                                </BulkInput>
                            )}
                            {(bulkType === 'transfer_to_hub_multiple_parcel' || bulkType === 'received_by_hub_multiple_parcel') && (
                                <BulkInput label={t.hub}>
                                    <Select value={bulkInputs.hub_id} onChange={(e) => setBulkInputs((b) => ({ ...b, hub_id: e.target.value }))}>
                                        <option value="">—</option>
                                        {(lookups.hubs || []).map((h) => <option key={h.id} value={h.id}>{h.name}</option>)}
                                    </Select>
                                </BulkInput>
                            )}
                            {bulkType === 'assign_return_merchant' && (
                                <>
                                    <BulkInput label={t.deliveryman_label}>
                                        <Select value={bulkInputs.deliveryman_id} onChange={(e) => setBulkInputs((b) => ({ ...b, deliveryman_id: e.target.value }))}>
                                            <option value="">—</option>
                                            {(lookups.deliverymen || []).map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                                        </Select>
                                    </BulkInput>
                                    <BulkInput label={t.date_label}>
                                        <Input type="date" value={bulkInputs.pickup_date} onChange={(e) => setBulkInputs((b) => ({ ...b, pickup_date: e.target.value }))} />
                                    </BulkInput>
                                </>
                            )}

                            <Button type="button" onClick={applyBulk} disabled={!bulkType || !selected.length}>
                                <Check className="h-4 w-4 me-1" /> {t.apply}
                            </Button>
                            {!selected.length && bulkType && (
                                <div className="text-xs text-muted-foreground">Select rows first.</div>
                            )}
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Body — list or card view depending on the toggle. Both feed
                the same rows / selection state / handlers so nothing in
                either mode is out of sync with the other. */}
            {viewMode === 'card' ? (
                rows.length === 0 ? (
                    <Card>
                        <CardContent className="py-16 text-center">
                            <div className="flex justify-center mb-3 text-muted-foreground/40">
                                <Package className="h-10 w-10" />
                            </div>
                            <p className="text-sm text-muted-foreground m-0">{t.no_rows}</p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {rows.map((r) => (
                            <ParcelCard
                                key={r.id}
                                r={r}
                                selected={selected}
                                toggle={toggle}
                                currency={currency}
                                permissions={permissions}
                                t={t}
                                urls={urls}
                                onTrack={openTracking}
                                onStatusChange={openStatusChange}
                                deleteRow={deleteRow}
                            />
                        ))}
                    </div>
                )
            ) : (
            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                    <th className="px-2.5 py-2 text-start">
                                        <input
                                            type="checkbox"
                                            checked={selected.length > 0 && selected.length === rows.length}
                                            onChange={toggleAll}
                                            className="h-4 w-4 rounded border-input"
                                        />
                                    </th>
                                    <th className="px-2.5 py-2 text-start">{t.actions}</th>
                                    <th className="px-2.5 py-2 text-start">{t.tracking_id}</th>
                                    <th className="px-2.5 py-2 text-start">{t.print_label}</th>
                                    <th className="px-2.5 py-2 text-start">{t.recipient_info}</th>
                                    <th className="px-2.5 py-2 text-start">{t.merchant}</th>
                                    <th className="px-2.5 py-2 text-end">{t.amount}</th>
                                    <th className="px-2.5 py-2 text-center">{t.priority}</th>
                                    <th className="px-2.5 py-2 text-start">{t.status}</th>
                                    {permissions.status_update && <th className="px-3 py-3 text-start">{t.status_update}</th>}
                                    <th className="px-2.5 py-2 text-start">{t.invoice}</th>
                                    <th className="px-2.5 py-2 text-center">{t.attempts}</th>
                                    <th className="px-2.5 py-2 text-start">{t.pod}</th>
                                    <th className="px-2.5 py-2 text-start">{t.courier_name}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 && (
                                    <tr>
                                        <td colSpan={14} className="px-4 py-10 text-center text-muted-foreground">
                                            {t.no_rows}
                                        </td>
                                    </tr>
                                )}
                                {rows.map((r) => (
                                    <tr key={r.id} className={cn(
                                        'border-b border-border last:border-0 hover:bg-muted/20 transition-colors',
                                        selected.includes(r.id) && 'bg-primary/5',
                                    )}>
                                        <td className="px-2.5 py-2 align-top">
                                            <input type="checkbox" checked={selected.includes(r.id)} onChange={() => toggle(r.id)} className="h-4 w-4 rounded border-input" />
                                        </td>
                                        <td className="px-2.5 py-2 align-top">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="icon" className="h-8 w-8">
                                                        <MoreVertical className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="start" className="w-44">
                                                    <DropdownMenuItem onClick={() => openTracking(r.id)}><RouteIcon className="h-4 w-4 me-2" /> Track shipment</DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem onClick={() => { window.location.href = r.urls.view; }}><Eye className="h-4 w-4 me-2" /> {t.view}</DropdownMenuItem>
                                                    <DropdownMenuItem onClick={() => { window.location.href = r.urls.logs; }}><History className="h-4 w-4 me-2" /> {t.logs}</DropdownMenuItem>
                                                    <DropdownMenuItem onClick={() => { window.location.href = r.urls.clone; }}><Copy className="h-4 w-4 me-2" /> {t.clone}</DropdownMenuItem>
                                                    <DropdownMenuItem onClick={() => { window.location.href = r.urls.print; }}><Printer className="h-4 w-4 me-2" /> {t.print}</DropdownMenuItem>
                                                    <DropdownMenuItem onClick={() => { window.open(r.urls.print_label, '_blank'); }}><Printer className="h-4 w-4 me-2" /> {t.print_label}</DropdownMenuItem>
                                                    {(permissions.update || permissions.delete) && r.status !== 9 && r.status !== 10 && <DropdownMenuSeparator />}
                                                    {permissions.update && r.status !== 9 && r.status !== 10 && (
                                                        <DropdownMenuItem onClick={() => { window.location.href = r.urls.edit; }}><Edit className="h-4 w-4 me-2" /> {t.edit}</DropdownMenuItem>
                                                    )}
                                                    {permissions.delete && r.status !== 9 && r.status !== 10 && (
                                                        <DropdownMenuItem onClick={() => deleteRow(r)} className="text-destructive focus:text-destructive">
                                                            <Trash2 className="h-4 w-4 me-2" /> {t.delete}
                                                        </DropdownMenuItem>
                                                    )}
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </td>
                                        <td className="px-2.5 py-2 align-top">
                                            <TrackingCell value={r.tracking_id} parcelId={r.id} onTrack={openTracking} />
                                            {r.code && <div className="text-xs text-muted-foreground mt-1">{t.awb}: {r.code}</div>}
                                            {r.courier_name && (
                                                <div className="mt-1 text-[10px] text-rose-600 font-medium">3PL: {r.courier_name}</div>
                                            )}
                                        </td>
                                        <td className="px-2.5 py-2 align-top">
                                            <a href={r.urls.print_label} title={t.print_label} className="text-rose-600">
                                                <FileText className="h-5 w-5" />
                                            </a>
                                        </td>
                                        <td className="px-2.5 py-2 align-top">
                                            <div className="space-y-0.5 max-w-[220px]">
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
                                                        <MapPin className="h-3 w-3 mt-0.5 shrink-0" /> <span className="truncate">{r.customer_address}</span>
                                                    </div>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-2.5 py-2 align-top">
                                            <div className="max-w-[180px] space-y-0.5">
                                                <div className="font-medium">{r.merchant_name || '—'}</div>
                                                {r.merchant_mobile && <div className="text-xs text-muted-foreground">{r.merchant_mobile}</div>}
                                                {r.merchant_address && <div className="text-xs text-muted-foreground truncate">{r.merchant_address}</div>}
                                            </div>
                                        </td>
                                        <td className="px-2.5 py-2 align-top">
                                            <div className="min-w-[150px]">
                                                {/* Headline: COD */}
                                                <div className="flex items-baseline justify-between gap-2">
                                                    <span className="text-[10px] uppercase tracking-wide text-muted-foreground font-medium">{t.cod}</span>
                                                    <span className="text-sm font-bold tabular-nums">
                                                        <Money value={r.cash_collection} currency={currency} />
                                                    </span>
                                                </div>
                                                {/* Finance breakdown (permission-gated) */}
                                                {permissions.finance_update && (
                                                    <div className="mt-1.5 pt-1.5 border-t border-border/60 space-y-0.5 text-[11px]">
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
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-2.5 py-2 align-top text-center">
                                            <PriorityToggle id={r.id} initial={r.priority} url={urls.priority_status} />
                                        </td>
                                        <td className="px-2.5 py-2 align-top">
                                            <div className="space-y-1">
                                                <StatusPill label={r.status_label} color={r.status_color} />
                                                {r.partial_delivered && r.status !== 10 && (
                                                    <div><StatusPill label={r.partial_delivered_label} color="green" /></div>
                                                )}
                                                <div className="text-[10px] text-muted-foreground">
                                                    {t.updated_on}: {r.updated_at}
                                                </div>
                                            </div>
                                        </td>
                                        {permissions.status_update && (
                                            <td className="px-2.5 py-2 align-top">
                                                {r.allowed_transitions?.length ? (
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="outline" size="sm" className="h-8">
                                                                <RefreshCcw className="h-3.5 w-3.5 me-1" /> {t.change_status}
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="start" className="w-56">
                                                            <DropdownMenuLabel className="text-[10px] uppercase">{t.change_status}</DropdownMenuLabel>
                                                            <DropdownMenuSeparator />
                                                            {r.allowed_transitions.map((tr) => (
                                                                <DropdownMenuItem
                                                                    key={tr.value}
                                                                    onClick={() => openStatusChange(r, tr)}
                                                                >
                                                                    <span
                                                                        className="inline-block h-2 w-2 rounded-full me-2"
                                                                        style={{ backgroundColor: isHex(tr.color) ? tr.color : FALLBACK_HEX }}
                                                                    />
                                                                    {tr.label}
                                                                </DropdownMenuItem>
                                                            ))}
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                ) : (
                                                    <span className="text-muted-foreground text-xs">…</span>
                                                )}
                                            </td>
                                        )}
                                        <td className="px-2.5 py-2 align-top">
                                            {r.invoice ? (
                                                <div className="text-xs space-y-0.5">
                                                    <div className="font-medium">{r.invoice.status_label}</div>
                                                    <div className="font-mono text-[10px] text-muted-foreground">{r.invoice.id}</div>
                                                    {r.invoice.paid_at && (
                                                        <div className="text-emerald-700 text-[10px]">{t.paid_at}: {r.invoice.paid_at}</div>
                                                    )}
                                                </div>
                                            ) : (
                                                <span className="text-muted-foreground text-xs">N/A</span>
                                            )}
                                        </td>
                                        <td className="px-2.5 py-2 align-top text-center font-medium tabular-nums">{r.attempts ?? 0}</td>
                                        <td className="px-2.5 py-2 align-top">
                                            {r.urls.delivered_info ? (
                                                <a href={r.urls.delivered_info} className="inline-flex items-center gap-1 rounded-md bg-amber-100 text-amber-800 px-2 py-1 text-xs font-medium hover:bg-amber-200 transition-colors">
                                                    <Eye className="h-3 w-3" /> {t.view}
                                                </a>
                                            ) : (
                                                <span className="text-muted-foreground text-xs">—</span>
                                            )}
                                        </td>
                                        <td className="px-2.5 py-2 align-top">
                                            <div className="space-y-1 text-xs">
                                                {r.courier_name && (
                                                    <div className="text-rose-600 font-medium">{r.courier_name}</div>
                                                )}
                                                {r.assigned_deliveryman && (
                                                    <span className="inline-flex items-center gap-1 rounded-full bg-sky-100 text-sky-700 px-2 py-0.5 text-[11px] font-medium">
                                                        <Truck className="h-3 w-3" /> {r.assigned_deliveryman}
                                                    </span>
                                                )}
                                                {!r.courier_name && !r.assigned_deliveryman && (
                                                    <span className="text-muted-foreground">—</span>
                                                )}
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

            {/* Tracking drawer (mounted once) */}
            <ShipmentDrawer parcelId={trackingId} onClose={closeTracking} />

            {/* Inline status-change modal */}
            <ChangeStatusModal
                open={!!statusChange}
                parcel={statusChange?.parcel ?? {}}
                transition={statusChange?.transition ?? null}
                statusUrls={urls.status || {}}
                lookups={lookups}
                t={t}
                onClose={closeStatusChange}
                onSuccess={onStatusChanged}
            />

            {/* Pagination */}
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

function FilterField({ label, className, children }) {
    return (
        <div className={className}>
            <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{label}</label>
            <div className="mt-1.5">{children}</div>
        </div>
    );
}

function BulkInput({ label, children }) {
    return (
        <div className="min-w-[160px]">
            <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{label}</label>
            <div className="mt-1.5">{children}</div>
        </div>
    );
}

/**
 * Status filter chips above the table. Each chip carries a per-status
 * count that comes prebaked from the controller (one grouped query, not
 * eleven). Clicking a chip navigates to parcel.filter with the status
 * value; "Total" clears the status filter and re-fetches the full list.
 *
 * The "active" tint sticks so the operator can see at a glance which
 * status they're looking at when the toolbar sits below the chip strip.
 */
function KpiChips({ counts = {}, filterUrl, activeStatus, t = {} }) {
    // Status codes align with App\Enums\ParcelStatus. Kept inline (rather
    // than shared as a constant) so the color assignments live next to the
    // labels they belong to.
    const chips = [
        { key: 'total',     label: t.chip_total     ?? 'Total',       tone: 'primary', status: '' },
        { key: 'pending',   label: t.chip_pending   ?? 'Pending',     tone: 'slate',   status: 1  },
        { key: 'assigned',  label: t.chip_assigned  ?? 'Assigned',    tone: 'sky',     status: 2  },
        { key: 'picked_up', label: t.chip_picked_up ?? 'Picked up',   tone: 'indigo',  status: 5  },
        { key: 'ofd',       label: t.chip_ofd       ?? 'OFD',         tone: 'amber',   status: 7  },
        { key: 'delivered', label: t.chip_delivered ?? 'Delivered',   tone: 'emerald', status: 9  },
        { key: 'returned',  label: t.chip_returned  ?? 'Returned',    tone: 'orange',  status: 30 },
        { key: 'cancelled', label: t.chip_cancelled ?? 'Cancelled',   tone: 'rose',    status: 41 },
        { key: 'failed',    label: t.chip_failed    ?? 'Failed',      tone: 'red',     status: 8  },
        { key: 'ndr',       label: t.chip_ndr       ?? 'NDR',         tone: 'red',     status: 35 },
    ];
    const tones = {
        primary: { pill: 'bg-primary/10 text-primary border-primary/30', dot: 'bg-primary' },
        slate:   { pill: 'bg-slate-100 text-slate-700 border-slate-200', dot: 'bg-slate-400' },
        sky:     { pill: 'bg-sky-100 text-sky-700 border-sky-200',       dot: 'bg-sky-500' },
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
                                'inline-flex items-center gap-2 rounded-full border px-3 h-8 text-xs font-medium whitespace-nowrap transition-all',
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
 * Compact card for a single shipment. Same primary actions as the table
 * row but reflowed for a grid layout: tracking + status pill at top,
 * recipient / merchant middle, COD + priority + change-status bottom,
 * actions dropdown in the top-right. Whole card highlights when the
 * row is selected so bulk actions stay obvious in grid view.
 */
function ParcelCard({
    r, selected, toggle, currency, permissions, t, urls,
    onTrack, onStatusChange, deleteRow,
}) {
    const isSelected = selected.includes(r.id);
    const canEdit    = permissions.update && r.status !== 9 && r.status !== 10;
    const canDelete  = permissions.delete && r.status !== 9 && r.status !== 10;
    return (
        <Card className={cn(
            'rounded-xl border overflow-hidden transition-all',
            isSelected ? 'border-primary shadow-md ring-2 ring-primary/20 bg-primary/5' : 'border-border hover:shadow-md',
        )}>
            <CardContent className="p-0">
                {/* Header — selection + tracking + status + actions */}
                <div className="flex items-start gap-2 px-4 pt-4 pb-3 border-b border-border">
                    <input
                        type="checkbox"
                        checked={isSelected}
                        onChange={() => toggle(r.id)}
                        className="h-4 w-4 rounded border-input mt-1"
                        aria-label="select shipment"
                    />
                    <div className="flex-1 min-w-0">
                        <div className="flex items-start justify-between gap-2">
                            <TrackingCell value={r.tracking_id} parcelId={r.id} onTrack={onTrack} />
                            <PriorityToggle id={r.id} initial={r.priority} url={urls.priority_status} />
                        </div>
                        {r.code && <div className="text-[10px] text-muted-foreground mt-0.5">{t.awb}: {r.code}</div>}
                        <div className="mt-2 flex items-center gap-2 flex-wrap">
                            <StatusPill label={r.status_label} color={r.status_color} />
                            {r.partial_delivered && r.status !== 10 && (
                                <StatusPill label={r.partial_delivered_label} color="green" />
                            )}
                            <span className="text-[10px] text-muted-foreground">
                                {t.updated_on}: {r.updated_at}
                            </span>
                        </div>
                    </div>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon" className="h-8 w-8 shrink-0">
                                <MoreVertical className="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-44">
                            <DropdownMenuItem onClick={() => onTrack(r.id)}><RouteIcon className="h-4 w-4 me-2" /> Track shipment</DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem onClick={() => { window.location.href = r.urls.view; }}><Eye className="h-4 w-4 me-2" /> {t.view}</DropdownMenuItem>
                            <DropdownMenuItem onClick={() => { window.location.href = r.urls.logs; }}><History className="h-4 w-4 me-2" /> {t.logs}</DropdownMenuItem>
                            <DropdownMenuItem onClick={() => { window.location.href = r.urls.clone; }}><Copy className="h-4 w-4 me-2" /> {t.clone}</DropdownMenuItem>
                            <DropdownMenuItem onClick={() => { window.location.href = r.urls.print; }}><Printer className="h-4 w-4 me-2" /> {t.print}</DropdownMenuItem>
                            <DropdownMenuItem onClick={() => { window.open(r.urls.print_label, '_blank'); }}><FileText className="h-4 w-4 me-2" /> {t.print_label}</DropdownMenuItem>
                            {(canEdit || canDelete) && <DropdownMenuSeparator />}
                            {canEdit && (
                                <DropdownMenuItem onClick={() => { window.location.href = r.urls.edit; }}><Edit className="h-4 w-4 me-2" /> {t.edit}</DropdownMenuItem>
                            )}
                            {canDelete && (
                                <DropdownMenuItem onClick={() => deleteRow(r)} className="text-destructive focus:text-destructive">
                                    <Trash2 className="h-4 w-4 me-2" /> {t.delete}
                                </DropdownMenuItem>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                {/* Body — recipient + merchant + optional 3PL/courier */}
                <div className="px-4 py-3 space-y-2.5">
                    <div>
                        <div className="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground mb-0.5">{t.recipient_info}</div>
                        {r.customer_name && (
                            <div className="flex items-center gap-1.5 text-sm font-medium">
                                <User className="h-3.5 w-3.5 text-muted-foreground shrink-0" />
                                <span className="truncate">{r.customer_name}</span>
                            </div>
                        )}
                        {r.customer_phone && (
                            <div className="flex items-center gap-1.5 text-xs text-muted-foreground mt-0.5">
                                <Phone className="h-3 w-3 shrink-0" />
                                <span className="tabular-nums truncate">{r.customer_phone}</span>
                            </div>
                        )}
                        {(r.city || r.area || r.customer_address) && (
                            <div className="flex items-start gap-1.5 text-xs text-muted-foreground mt-0.5">
                                <MapPin className="h-3 w-3 mt-0.5 shrink-0" />
                                <span className="line-clamp-2 leading-relaxed">
                                    {[r.city, r.area].filter(Boolean).join(', ')}
                                    {r.customer_address && <span className="text-muted-foreground/80"> · {r.customer_address}</span>}
                                </span>
                            </div>
                        )}
                    </div>
                    <div className="pt-2 border-t border-border">
                        <div className="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground mb-0.5">{t.merchant}</div>
                        <div className="text-sm font-medium truncate">{r.merchant_name || '—'}</div>
                    </div>
                    {(r.courier_name || r.assigned_deliveryman) && (
                        <div className="pt-2 border-t border-border flex items-center gap-2 flex-wrap">
                            {r.courier_name && (
                                <span className="inline-flex items-center gap-1 text-[11px] font-medium text-rose-600">
                                    3PL: {r.courier_name}
                                </span>
                            )}
                            {r.assigned_deliveryman && (
                                <span className="inline-flex items-center gap-1 rounded-full bg-sky-100 text-sky-700 px-2 py-0.5 text-[11px] font-medium">
                                    <Truck className="h-3 w-3" /> {r.assigned_deliveryman}
                                </span>
                            )}
                        </div>
                    )}
                </div>

                {/* Footer — COD + attempts + change status */}
                <div className="px-4 py-3 border-t border-border bg-muted/20 flex items-center justify-between gap-3">
                    <div className="min-w-0">
                        <div className="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">{t.cod}</div>
                        <div className="text-lg font-bold tabular-nums">
                            <Money value={r.cash_collection} currency={currency} />
                        </div>
                        <div className="text-[10px] text-muted-foreground">
                            {t.attempts}: <span className="font-medium tabular-nums">{r.attempts ?? 0}</span>
                        </div>
                    </div>
                    {permissions.status_update && r.allowed_transitions?.length ? (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline" size="sm" className="h-8 shrink-0">
                                    <RefreshCcw className="h-3.5 w-3.5 me-1" /> {t.change_status}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-56">
                                <DropdownMenuLabel className="text-[10px] uppercase">{t.change_status}</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                {r.allowed_transitions.map((tr) => (
                                    <DropdownMenuItem key={tr.value} onClick={() => onStatusChange(r, tr)}>
                                        <span
                                            className="inline-block h-2 w-2 rounded-full me-2"
                                            style={{ backgroundColor: isHex(tr.color) ? tr.color : FALLBACK_HEX }}
                                        />
                                        {tr.label}
                                    </DropdownMenuItem>
                                ))}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    ) : null}
                </div>
            </CardContent>
        </Card>
    );
}
