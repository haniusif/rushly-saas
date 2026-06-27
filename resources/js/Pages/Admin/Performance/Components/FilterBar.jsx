import * as React from 'react';
import { router } from '@inertiajs/react';
import { Filter, RefreshCw, Download, Printer, FileText } from 'lucide-react';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';

const PRESETS = [
    { id: 'today',  label: 'Today',   days: 0 },
    { id: '7d',     label: '7 days',  days: 6 },
    { id: '30d',    label: '30 days', days: 29 },
    { id: '90d',    label: '90 days', days: 89 },
    { id: 'ytd',    label: 'YTD',     days: 'ytd' },
];

function isoDaysAgo(n) {
    const d = new Date(); d.setDate(d.getDate() - n); return d.toISOString().slice(0, 10);
}
function todayIso() { return new Date().toISOString().slice(0, 10); }

export default function FilterBar({
    filters, options, urls, onRefresh, autoRefresh, setAutoRefresh, isRefreshing,
}) {
    const [from, setFrom] = React.useState(filters.from);
    const [to, setTo]     = React.useState(filters.to);
    const [driverId, setDriverId]   = React.useState(filters.driver_id ?? '');
    const [hubId, setHubId]         = React.useState(filters.hub_id ?? '');
    const [merchantId, setMerchantId] = React.useState(filters.merchant_id ?? '');
    const [supplierId, setSupplierId] = React.useState(filters.supplier_company_id ?? '');
    const [deliveryTypeId, setDeliveryTypeId] = React.useState(filters.delivery_type_id ?? '');

    const apply = () => {
        router.get(window.location.pathname, {
            from, to,
            driver_id: driverId || undefined,
            hub_id: hubId || undefined,
            merchant_id: merchantId || undefined,
            supplier_company_id: supplierId || undefined,
            delivery_type_id: deliveryTypeId || undefined,
        }, { preserveState: true, preserveScroll: true });
    };

    const applyPreset = (preset) => {
        if (preset.days === 'ytd') {
            const start = new Date(); start.setMonth(0, 1);
            setFrom(start.toISOString().slice(0, 10));
        } else {
            setFrom(isoDaysAgo(preset.days));
        }
        setTo(todayIso());
    };

    const exportUrl = (kind) => {
        const u = new URL(urls[kind], window.location.origin);
        const q = { from, to, driver_id: driverId, hub_id: hubId, merchant_id: merchantId, supplier_company_id: supplierId, delivery_type_id: deliveryTypeId };
        Object.entries(q).forEach(([k, v]) => v && u.searchParams.set(k, v));
        return u.toString();
    };

    return (
        <Card>
            <CardContent className="p-4 space-y-4">
                <div className="flex items-center justify-between gap-3 flex-wrap">
                    <div className="flex items-center gap-2 text-sm font-medium">
                        <Filter className="h-4 w-4 text-primary" />
                        Filters
                    </div>
                    <div className="flex items-center gap-2 flex-wrap">
                        <label className="flex items-center gap-2 text-xs text-muted-foreground">
                            <input
                                type="checkbox" className="h-3.5 w-3.5 accent-primary"
                                checked={autoRefresh}
                                onChange={(e) => setAutoRefresh(e.target.checked)}
                            />
                            Auto-refresh (60s)
                        </label>
                        <Button variant="outline" size="sm" onClick={onRefresh} disabled={isRefreshing}>
                            <RefreshCw className={`h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`} /> Refresh
                        </Button>
                        <a href={exportUrl('export_excel')} target="_blank" rel="noopener">
                            <Button variant="outline" size="sm" type="button">
                                <Download className="h-4 w-4" /> Excel
                            </Button>
                        </a>
                        <a href={exportUrl('export_pdf')} target="_blank" rel="noopener">
                            <Button variant="outline" size="sm" type="button">
                                <FileText className="h-4 w-4" /> PDF
                            </Button>
                        </a>
                        <Button variant="outline" size="sm" type="button" onClick={() => window.print()}>
                            <Printer className="h-4 w-4" /> Print
                        </Button>
                    </div>
                </div>

                {/* Presets */}
                <div className="flex items-center gap-1.5 flex-wrap">
                    {PRESETS.map((p) => (
                        <button
                            key={p.id} type="button" onClick={() => applyPreset(p)}
                            className="px-2.5 py-1 text-xs font-medium rounded-md border border-border bg-muted/30 hover:bg-muted text-muted-foreground transition-colors"
                        >
                            {p.label}
                        </button>
                    ))}
                </div>

                {/* Inputs */}
                <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
                    <div>
                        <label className="text-[11px] uppercase tracking-wide font-medium text-muted-foreground block mb-1">From</label>
                        <Input type="date" value={from} onChange={(e) => setFrom(e.target.value)} />
                    </div>
                    <div>
                        <label className="text-[11px] uppercase tracking-wide font-medium text-muted-foreground block mb-1">To</label>
                        <Input type="date" value={to} onChange={(e) => setTo(e.target.value)} />
                    </div>
                    <div>
                        <label className="text-[11px] uppercase tracking-wide font-medium text-muted-foreground block mb-1">Driver</label>
                        <Select value={driverId} onChange={(e) => setDriverId(e.target.value)}>
                            <option value="">All</option>
                            {options.drivers.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                        </Select>
                    </div>
                    <div>
                        <label className="text-[11px] uppercase tracking-wide font-medium text-muted-foreground block mb-1">Branch (Hub)</label>
                        <Select value={hubId} onChange={(e) => setHubId(e.target.value)}>
                            <option value="">All</option>
                            {options.hubs.map((h) => <option key={h.id} value={h.id}>{h.name}</option>)}
                        </Select>
                    </div>
                    <div>
                        <label className="text-[11px] uppercase tracking-wide font-medium text-muted-foreground block mb-1">Customer</label>
                        <Select value={merchantId} onChange={(e) => setMerchantId(e.target.value)}>
                            <option value="">All</option>
                            {options.merchants.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                        </Select>
                    </div>
                    <div>
                        <label className="text-[11px] uppercase tracking-wide font-medium text-muted-foreground block mb-1">Operating Co.</label>
                        <Select value={supplierId} onChange={(e) => setSupplierId(e.target.value)}>
                            <option value="">All</option>
                            {options.supplier_companies.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                        </Select>
                    </div>
                    <div>
                        <label className="text-[11px] uppercase tracking-wide font-medium text-muted-foreground block mb-1">Service Type</label>
                        <Select value={deliveryTypeId} onChange={(e) => setDeliveryTypeId(e.target.value)}>
                            <option value="">All</option>
                            {options.delivery_types.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                        </Select>
                    </div>
                </div>

                <div className="flex justify-end">
                    <Button size="sm" onClick={apply}>Apply</Button>
                </div>
            </CardContent>
        </Card>
    );
}
