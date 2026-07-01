import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import {
    Package, Truck, RotateCcw, Box, ShoppingBag, Coins, Percent, CreditCard,
    Wallet, Hourglass, Database, Home, Layers, History, Calendar, Filter,
} from 'lucide-react';
import MerchantLayout from '@/Layouts/MerchantLayout';

function fmt(n, digits = 2) {
    const v = Number(n) || 0;
    return v.toLocaleString(undefined, { minimumFractionDigits: digits, maximumFractionDigits: digits });
}
function fmtInt(n) {
    return (Number(n) || 0).toLocaleString();
}

function KpiTile({ icon: Icon, label, value, tone, href }) {
    const inner = (
        <div className="group block bg-card border border-border rounded-xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
            <div className="flex items-center gap-4">
                <span className={`shrink-0 w-12 h-12 rounded-xl flex items-center justify-center ${tone}`}>
                    <Icon className="h-5 w-5" />
                </span>
                <div className="min-w-0">
                    <div className="text-[11px] uppercase tracking-wider font-medium text-muted-foreground mb-1">{label}</div>
                    <div className="text-2xl font-semibold tabular-nums">{fmtInt(value)}</div>
                </div>
            </div>
        </div>
    );
    return href ? <a href={href} className="no-underline">{inner}</a> : inner;
}

function AmountRow({ label, value, currency, highlight = false, positive = null }) {
    const valueClass = positive === true
        ? 'text-emerald-700'
        : positive === false
            ? 'text-rose-600'
            : 'text-foreground';
    return (
        <li className={`flex items-center justify-between px-5 py-3 ${highlight ? 'bg-emerald-50/40' : ''}`}>
            <span className="text-sm text-foreground/80">{label}</span>
            <span className={`text-sm font-semibold tabular-nums ${valueClass}`}>
                {fmt(value)} <span className="text-xs text-muted-foreground font-normal ms-0.5">{currency}</span>
            </span>
        </li>
    );
}

function AmountCard({ title, children }) {
    return (
        <div className="bg-card border border-border rounded-xl shadow-sm overflow-hidden">
            <div className="px-5 py-3 border-b border-border">
                <h3 className="text-sm font-semibold m-0">{title}</h3>
            </div>
            <ul className="divide-y divide-border m-0 list-none p-0">{children}</ul>
        </div>
    );
}

function ReportTile({ icon: Icon, label, value, unit, tone }) {
    return (
        <div className="bg-card border border-border rounded-xl shadow-sm p-4 hover:shadow-md transition-shadow">
            <div className="flex items-center gap-3">
                <span className={`shrink-0 w-10 h-10 rounded-lg flex items-center justify-center ${tone}`}>
                    <Icon className="h-4 w-4" />
                </span>
                <div className="min-w-0">
                    <div className="text-[11px] uppercase tracking-wider font-medium text-muted-foreground line-clamp-1">{label}</div>
                    <div className="text-base font-semibold tabular-nums truncate">
                        {value}
                        {unit && <span className="text-xs text-muted-foreground font-normal ms-0.5">{unit}</span>}
                    </div>
                </div>
            </div>
        </div>
    );
}

/*
 * Inline SVG line chart — one polyline per series. Matches the pattern from
 * Admin/Dashboard/Index.jsx so we don't drag in a chart library.
 */
function LineChart({ series = [], height = 220, dates = [] }) {
    const W = 600;
    const H = height;
    const padX = 32;
    const padY = 24;
    const all = series.flatMap((s) => s.data);
    const max = Math.max(1, ...all);
    const xs = dates.length ? dates.length : (series[0]?.data?.length || 1);
    const step = (W - padX * 2) / Math.max(1, xs - 1);
    const yScale = (v) => H - padY - (v / max) * (H - padY * 2);

    return (
        <svg viewBox={`0 0 ${W} ${H}`} className="w-full" preserveAspectRatio="none">
            {/* horizontal gridlines */}
            {[0, 1, 2, 3, 4].map((i) => {
                const y = padY + ((H - padY * 2) * i) / 4;
                return <line key={i} x1={padX} x2={W - padX} y1={y} y2={y} stroke="currentColor" className="text-border" strokeWidth="0.5" />;
            })}
            {series.map((s) => {
                const pts = s.data.map((v, i) => `${padX + i * step},${yScale(v)}`).join(' ');
                return (
                    <g key={s.name}>
                        <polyline
                            fill="none"
                            stroke={s.color}
                            strokeWidth="2"
                            strokeLinejoin="round"
                            strokeLinecap="round"
                            points={pts}
                        />
                        {s.data.map((v, i) => (
                            <circle key={i} cx={padX + i * step} cy={yScale(v)} r="2.5" fill={s.color} />
                        ))}
                    </g>
                );
            })}
            {/* x-axis date ticks (every other) */}
            {dates.map((d, i) => {
                if (i % 2 !== 0 && i !== dates.length - 1) return null;
                return (
                    <text
                        key={i}
                        x={padX + i * step}
                        y={H - 6}
                        fontSize="9"
                        textAnchor="middle"
                        className="fill-muted-foreground"
                    >
                        {d.slice(5)}
                    </text>
                );
            })}
        </svg>
    );
}

/*
 * Donut chart for the parcel status breakdown. Pure SVG arcs, no library.
 */
function DonutChart({ slices = [], size = 220, thickness = 30 }) {
    const total = slices.reduce((sum, s) => sum + (s.value || 0), 0);
    const cx = size / 2;
    const cy = size / 2;
    const r = (size - thickness) / 2;
    let acc = -Math.PI / 2;

    if (total === 0) {
        return (
            <div className="flex flex-col items-center justify-center h-[220px] text-sm text-muted-foreground">
                — no data —
            </div>
        );
    }

    return (
        <div className="flex flex-col md:flex-row items-center gap-6 justify-center">
            <svg viewBox={`0 0 ${size} ${size}`} className="w-[180px] h-[180px]">
                <circle cx={cx} cy={cy} r={r} fill="none" stroke="currentColor" className="text-border" strokeWidth={thickness} />
                {slices.map((s) => {
                    const frac = (s.value || 0) / total;
                    if (frac === 0) return null;
                    const angle = frac * 2 * Math.PI;
                    const x1 = cx + Math.cos(acc) * r;
                    const y1 = cy + Math.sin(acc) * r;
                    const x2 = cx + Math.cos(acc + angle) * r;
                    const y2 = cy + Math.sin(acc + angle) * r;
                    const large = angle > Math.PI ? 1 : 0;
                    const d = `M ${x1} ${y1} A ${r} ${r} 0 ${large} 1 ${x2} ${y2}`;
                    acc += angle;
                    return <path key={s.name} d={d} fill="none" stroke={s.color} strokeWidth={thickness} strokeLinecap="butt" />;
                })}
                <text x={cx} y={cy - 4} textAnchor="middle" className="fill-foreground" fontSize="20" fontWeight="600">{total}</text>
                <text x={cx} y={cy + 14} textAnchor="middle" className="fill-muted-foreground" fontSize="10">total</text>
            </svg>
            <ul className="space-y-1.5 list-none p-0 m-0 text-xs">
                {slices.map((s) => {
                    const pct = total ? ((s.value / total) * 100).toFixed(0) : 0;
                    return (
                        <li key={s.name} className="flex items-center gap-2">
                            <span className="inline-block w-2.5 h-2.5 rounded-full" style={{ background: s.color }} />
                            <span className="text-foreground/80 me-2 min-w-[90px]">{s.name}</span>
                            <span className="tabular-nums font-medium">{s.value}</span>
                            <span className="text-muted-foreground">({pct}%)</span>
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}

const SERVICE_TONE = {
    last_mile:   'bg-sky-50 text-sky-700 border-sky-200',
    fulfillment: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    storage:     'bg-amber-50 text-amber-700 border-amber-200',
};

export default function Index({
    currency = '',
    merchant = null,
    services = [],
    request_date = null,
    parcel_kpis = {},
    active_amounts = {},
    fees_amounts = {},
    delivery_amounts = {},
    reports = {},
    series = {},
    pie = {},
    urls = {},
    t = {},
}) {
    const [date, setDate] = React.useState(request_date || '');
    const onFilter = (e) => {
        e.preventDefault();
        router.post(urls.filter, { date }, { preserveScroll: true });
    };

    const reportTiles = [
        { icon: ShoppingBag, label: t.total_sales_amount,       value: fmt(reports.total_sales),        unit: currency, tone: 'bg-emerald-50 text-emerald-600' },
        { icon: Truck,       label: t.total_delivery_fees_paid, value: fmt(reports.total_delivery_fees),unit: currency, tone: 'bg-amber-50 text-amber-600' },
        { icon: Percent,     label: t.total_vat,                value: fmt(reports.total_vat),          unit: currency, tone: 'bg-purple-50 text-purple-600' },
        { icon: Coins,       label: t.net_profit,               value: fmt(reports.net_profit),         unit: currency, tone: reports.net_profit >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' },
        { icon: CreditCard,  label: t.current_balance,          value: fmt(reports.current_balance),    unit: currency, tone: 'bg-primary/10 text-primary' },
        { icon: Wallet,      label: t.opening_balance,          value: fmt(reports.opening_balance),    unit: currency, tone: 'bg-sky-50 text-sky-600' },
        { icon: Percent,     label: t.vat,                      value: fmt(reports.merchant_vat),       unit: currency, tone: 'bg-purple-50 text-purple-600' },
        { icon: Hourglass,   label: t.payment_processing,       value: fmt(reports.payment_processing), unit: currency, tone: 'bg-amber-50 text-amber-600' },
        { icon: Database,    label: t.paid_amount,              value: fmt(reports.paid_amount),        unit: currency, tone: 'bg-emerald-50 text-emerald-600' },
        { icon: Home,        label: t.total_shop,               value: fmtInt(reports.total_shop),      unit: '',       tone: 'bg-indigo-50 text-indigo-600' },
        { icon: Layers,      label: t.total_parcel_bank_items,  value: fmtInt(reports.total_parcel_bank),unit: '',      tone: 'bg-primary/10 text-primary' },
        { icon: History,     label: t.total_payment_request,    value: fmtInt(reports.total_payment_req),unit: '',      tone: 'bg-sky-50 text-sky-600' },
    ];

    return (
        <MerchantLayout title={t.merchant_dashboard} breadcrumbs={[t.dashboard, t.merchant_dashboard]}>
            <Head title={t.merchant_dashboard} />

            {/* Header + filter */}
            <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5">
                <div>
                    <h1 className="text-2xl font-semibold mb-1">{t.merchant_dashboard}</h1>
                    <p className="text-sm text-muted-foreground m-0">{t.dashboard}</p>
                </div>
                <form onSubmit={onFilter} className="flex items-center gap-2">
                    <div className="relative">
                        <Calendar className="absolute top-1/2 start-3 -translate-y-1/2 text-muted-foreground h-3.5 w-3.5" />
                        <input
                            type="text"
                            value={date}
                            onChange={(e) => setDate(e.target.value)}
                            placeholder={t.date_ph}
                            className="h-10 ps-9 pe-3 text-sm bg-background border border-input rounded-lg w-56"
                        />
                    </div>
                    <button
                        type="submit"
                        className="inline-flex items-center gap-2 h-10 px-4 text-sm font-medium text-primary-foreground bg-primary hover:opacity-90 rounded-lg"
                    >
                        <Filter className="h-3.5 w-3.5" />
                        {t.filter}
                    </button>
                </form>
            </div>

            {/* Services badges */}
            {services.length > 0 && (
                <div className="mb-5 flex items-center flex-wrap gap-2">
                    <span className="text-xs uppercase tracking-wider font-medium text-muted-foreground me-1">{t.services}</span>
                    {services.map((svc) => (
                        <span
                            key={svc}
                            className={`inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full border ${SERVICE_TONE[svc] || 'bg-muted/40 text-foreground border-border'}`}
                        >
                            {t['service_' + svc] || svc}
                        </span>
                    ))}
                </div>
            )}

            {/* Parcel KPI tiles */}
            <div data-tour="dashboard-kpis" className="grid gap-3 grid-cols-2 lg:grid-cols-4 mb-5">
                <KpiTile icon={Package} label={t.total_parcel}    value={parcel_kpis.total}     tone="bg-primary/10 text-primary" href={urls.parcels} />
                <KpiTile icon={Truck}   label={t.total_delivered} value={parcel_kpis.delivered} tone="bg-emerald-50 text-emerald-600" href={urls.parcels_delivered} />
                <KpiTile icon={RotateCcw} label={t.total_return}  value={parcel_kpis.returned}  tone="bg-amber-50 text-amber-600" href={urls.parcels_returned} />
                <KpiTile icon={Box}     label={t.total_transit}   value={parcel_kpis.in_transit} tone="bg-indigo-50 text-indigo-600" href={urls.parcels} />
            </div>

            {/* Three grouped amount cards */}
            <div data-tour="dashboard-amounts" className="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-5">
                <AmountCard title={t.active_amounts_title}>
                    <AmountRow label={t.active_shipments_amount} value={active_amounts.cash_collection} currency={currency} />
                    <AmountRow label={t.total_selling_price}     value={active_amounts.selling_price}   currency={currency} />
                    <AmountRow
                        label={t.net_profit_amount}
                        value={active_amounts.net_profit}
                        currency={currency}
                        highlight
                        positive={active_amounts.net_profit >= 0}
                    />
                </AmountCard>

                <AmountCard title={t.liquid_amounts_title}>
                    <AmountRow label={t.total_liquid_fragile_amount} value={fees_amounts.liquid_fragile} currency={currency} />
                    <AmountRow label={t.total_packaging_amount}      value={fees_amounts.packaging}      currency={currency} />
                    <AmountRow label={t.total_vat_amount}             value={fees_amounts.vat}            currency={currency} />
                </AmountCard>

                <AmountCard title={t.delivery_amounts_title}>
                    <AmountRow label={t.total_delivery_charge}        value={delivery_amounts.delivery_charge} currency={currency} />
                    <AmountRow label={t.total_cod_amount}             value={delivery_amounts.cod}             currency={currency} />
                    <AmountRow label={t.total_total_delivery_amount}  value={delivery_amounts.delivery_total}  currency={currency} />
                </AmountCard>
            </div>

            {/* Charts */}
            <div data-tour="dashboard-charts" className="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-5">
                <div className="bg-card border border-border rounded-xl shadow-sm">
                    <div className="px-5 py-3 border-b border-border">
                        <h3 className="text-sm font-semibold m-0">{t.parcels_chart}</h3>
                    </div>
                    <div className="p-4">
                        <LineChart
                            height={220}
                            dates={series.dates || []}
                            series={[
                                { name: t.series_total,     data: series.totals || [],       color: '#a21f5c' },
                                { name: t.series_delivered, data: series.delivers || [],     color: '#10b981' },
                                { name: t.series_partial,   data: series.par_delivers || [], color: '#f59e0b' },
                                { name: t.series_returned,  data: series.returns || [],      color: '#ef4444' },
                            ]}
                        />
                        <div className="mt-2 flex flex-wrap gap-3 text-[11px] text-muted-foreground">
                            {[
                                { name: t.series_total,     color: '#a21f5c' },
                                { name: t.series_delivered, color: '#10b981' },
                                { name: t.series_partial,   color: '#f59e0b' },
                                { name: t.series_returned,  color: '#ef4444' },
                            ].map((s) => (
                                <span key={s.name} className="inline-flex items-center gap-1.5">
                                    <span className="inline-block w-2.5 h-2.5 rounded-full" style={{ background: s.color }} />
                                    {s.name}
                                </span>
                            ))}
                        </div>
                    </div>
                </div>

                <div className="bg-card border border-border rounded-xl shadow-sm">
                    <div className="px-5 py-3 border-b border-border">
                        <h3 className="text-sm font-semibold m-0">{t.parcels_breakdown}</h3>
                    </div>
                    <div className="p-4">
                        <DonutChart
                            slices={[
                                { name: t.series_pending,   value: pie.pending || 0,           color: '#f59e0b' },
                                { name: t.series_delivered, value: pie.delivered || 0,         color: '#10b981' },
                                { name: t.series_partial,   value: pie.partial_delivered || 0, color: '#a21f5c' },
                                { name: t.series_returned,  value: pie.returned || 0,          color: '#ef4444' },
                            ]}
                        />
                    </div>
                </div>
            </div>

            {/* All reports */}
            <h2 className="text-base font-semibold mb-3">{t.all_reports}</h2>
            <div data-tour="dashboard-reports" className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                {reportTiles.map((r, i) => (
                    <ReportTile key={i} {...r} />
                ))}
            </div>
        </MerchantLayout>
    );
}
