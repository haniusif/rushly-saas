import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    Building2, MapPin, Phone, Edit3, Users, ArrowLeft, ChevronLeft, ChevronRight,
    Package, DollarSign, TrendingUp, Truck, CheckCircle2,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';

const FALLBACK_HEX = '#6c757d';
const isHex = (s) => typeof s === 'string' && /^#[0-9a-fA-F]{6}$/.test(s);
const hexToRgba = (hex, a) => {
    const h = isHex(hex) ? hex : FALLBACK_HEX;
    return `rgba(${parseInt(h.slice(1, 3), 16)}, ${parseInt(h.slice(3, 5), 16)}, ${parseInt(h.slice(5, 7), 16)}, ${a})`;
};

function StatusPill({ label, color }) {
    const hex = isHex(color) ? color : FALLBACK_HEX;
    return (
        <span
            className="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium whitespace-nowrap"
            style={{ backgroundColor: hexToRgba(hex, 0.12), borderColor: hexToRgba(hex, 0.3), color: hex }}
        >
            {label || '—'}
        </span>
    );
}

function Money({ value, currency }) {
    const n = Number(value || 0);
    return (
        <span className="tabular-nums">
            {n.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })}
            <span className="text-muted-foreground text-xs ms-0.5">{currency}</span>
        </span>
    );
}

function Stat({ icon: Icon, label, value, currency, tone = 'default' }) {
    const toneClass = {
        default: 'bg-slate-50 border-slate-200 text-slate-700',
        green:   'bg-emerald-50 border-emerald-200 text-emerald-700',
        amber:   'bg-amber-50 border-amber-200 text-amber-700',
        blue:    'bg-sky-50 border-sky-200 text-sky-700',
        red:     'bg-rose-50 border-rose-200 text-rose-700',
        violet:  'bg-violet-50 border-violet-200 text-violet-700',
    }[tone];
    return (
        <div className={`rounded-md border px-4 py-3 ${toneClass}`}>
            <div className="flex items-center gap-1.5 text-[10px] uppercase tracking-wider font-semibold opacity-80">
                {Icon && <Icon className="h-3 w-3" />} {label}
            </div>
            <div className="mt-1 text-xl font-bold">
                {currency != null ? <Money value={value} currency={currency} /> : value}
            </div>
        </div>
    );
}

function Row({ icon: Icon, label, children }) {
    return (
        <div className="flex items-start gap-3 py-2 border-b border-border/60 last:border-0">
            <div className="flex items-center gap-1.5 text-[11px] uppercase tracking-wider font-semibold text-muted-foreground w-32 shrink-0 pt-0.5">
                {Icon && <Icon className="h-3 w-3" />} {label}
            </div>
            <div className="text-sm flex-1 min-w-0 break-words">{children || <span className="text-muted-foreground">—</span>}</div>
        </div>
    );
}

export default function View({
    hub = {}, stats = {}, status_groups = [], rows = [], pagination = {}, filters = {},
    currency = '', urls = {}, t = {},
}) {
    const [dateRange, setDateRange] = React.useState(filters.parcel_date || '');
    const showing = (t.showing_results || 'Showing :from – :to of :total')
        .replace(':from', pagination.from ?? 0)
        .replace(':to', pagination.to ?? 0)
        .replace(':total', pagination.total ?? 0);

    const submitFilter = (e) => {
        e?.preventDefault?.();
        router.get(urls.self, { parcel_date: dateRange }, { preserveState: true, replace: true });
    };
    const clearFilter = () => {
        setDateRange('');
        router.get(urls.self, {}, { preserveState: false });
    };
    const goPage = (url) => url && router.get(url, {}, { preserveState: true });

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title, hub.name || '—']}>
            <Head title={`${t.title} · ${hub.name || ''}`} />

            {/* Toolbar */}
            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <Link href={urls.index} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.back}
                </Link>
                <div className="flex flex-wrap items-center gap-2">
                    <Link href={urls.incharges} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent">
                        <Users className="h-4 w-4 me-1" /> {t.incharges}
                    </Link>
                    <Link href={urls.edit} className="inline-flex h-9 items-center rounded-md bg-primary text-primary-foreground px-3 text-sm font-medium hover:bg-primary/90">
                        <Edit3 className="h-4 w-4 me-1" /> {t.edit}
                    </Link>
                </div>
            </div>

            {/* Top: identity + status breakdown */}
            <div className="grid gap-5 lg:grid-cols-3 mb-5">
                <Card>
                    <CardContent className="p-6">
                        <div className="flex items-start gap-3 pb-4 border-b border-border">
                            <div className="grid h-14 w-14 place-items-center rounded-md bg-primary/10 text-primary shrink-0">
                                <Building2 className="h-7 w-7" />
                            </div>
                            <div className="min-w-0 flex-1">
                                <div className="text-lg font-bold leading-tight truncate">{hub.name || '—'}</div>
                                {hub.status === 1 ? (
                                    <span className="mt-1 inline-flex items-center gap-1 rounded-full bg-emerald-100 text-emerald-700 px-2 py-0.5 text-[10px] font-medium">
                                        <CheckCircle2 className="h-3 w-3" /> {t.active}
                                    </span>
                                ) : (
                                    <span className="mt-1 inline-flex items-center rounded-full bg-slate-100 text-slate-700 px-2 py-0.5 text-[10px] font-medium">
                                        {t.inactive}
                                    </span>
                                )}
                            </div>
                        </div>
                        <div className="mt-3 space-y-0.5">
                            <Row icon={Phone} label={t.phone}><span className="font-mono">{hub.phone}</span></Row>
                            <Row icon={MapPin} label={t.address}>{hub.address}</Row>
                            <Row label={t.coordinates}>
                                {hub.lat || hub.long ? (
                                    <span className="font-mono text-xs">{hub.lat ?? '—'}, {hub.long ?? '—'}</span>
                                ) : null}
                            </Row>
                        </div>
                    </CardContent>
                </Card>

                <Card className="lg:col-span-2">
                    <CardContent className="p-6">
                        <div className="mb-3 text-sm font-semibold tracking-tight">{t.overview}</div>
                        <div className="grid gap-2 sm:grid-cols-3">
                            <Stat icon={Package}   label={t.total_parcels}    value={stats.total_parcels ?? 0} />
                            <Stat icon={DollarSign} label={t.cash_total}      value={stats.total_cash}        currency={currency} tone="blue" />
                            <Stat icon={CheckCircle2} label={t.cash_delivered} value={stats.delivered_cash}   currency={currency} tone="green" />
                            <Stat icon={Truck}     label={t.cash_in_transit}  value={stats.in_transit_cash}  currency={currency} tone="amber" />
                            <Stat icon={TrendingUp} label={t.cash_partial}    value={stats.partial_delivered_cash} currency={currency} tone="violet" />
                            <Stat icon={DollarSign} label={t.delivery_charges} value={stats.delivery_charges} currency={currency} />
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Status breakdown */}
            {status_groups.length > 0 && (
                <Card className="mb-5">
                    <CardContent className="p-5">
                        <div className="mb-3 text-sm font-semibold tracking-tight">{t.status_breakdown}</div>
                        <div className="flex flex-wrap gap-2">
                            {status_groups.map((g) => (
                                <div
                                    key={g.id}
                                    className="rounded-md border px-3 py-1.5 text-xs flex items-center gap-2"
                                    style={{ backgroundColor: hexToRgba(g.color, 0.08), borderColor: hexToRgba(g.color, 0.25) }}
                                >
                                    <StatusPill label={g.label} color={g.color} />
                                    <span className="font-semibold tabular-nums">{g.count}</span>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Recent parcels */}
            <Card>
                <CardContent className="p-5">
                    <div className="flex items-center justify-between gap-2 pb-3 border-b border-border">
                        <div className="text-sm font-semibold tracking-tight">{t.recent_parcels}</div>
                        <form onSubmit={submitFilter} className="flex items-center gap-2">
                            <Input
                                type="text"
                                placeholder="YYYY-MM-DD To YYYY-MM-DD"
                                value={dateRange}
                                onChange={(e) => setDateRange(e.target.value)}
                                className="h-8 w-60 text-xs"
                            />
                            <Button type="submit" size="sm">{t.filter}</Button>
                            {filters.parcel_date && (
                                <Button type="button" size="sm" variant="outline" onClick={clearFilter}>{t.clear}</Button>
                            )}
                        </form>
                    </div>

                    {rows.length === 0 ? (
                        <div className="py-12 text-center text-sm text-muted-foreground">
                            <Package className="h-10 w-10 text-muted-foreground/40 mx-auto mb-3" />
                            {t.no_parcels}
                        </div>
                    ) : (
                        <div className="overflow-x-auto mt-3">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/40 text-[10px] uppercase tracking-wider text-muted-foreground border-b border-border">
                                    <tr>
                                        <th className="px-3 py-2 text-start font-semibold">{t.tracking_id}</th>
                                        <th className="px-3 py-2 text-start font-semibold">{t.customer}</th>
                                        <th className="px-3 py-2 text-end font-semibold">{t.cod}</th>
                                        <th className="px-3 py-2 text-start font-semibold">{t.status}</th>
                                        <th className="px-3 py-2 text-end font-semibold">{t.updated_at}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map((r) => (
                                        <tr key={r.id} className="border-b border-border hover:bg-muted/30">
                                            <td className="px-3 py-2">
                                                <a href={r.url} className="font-mono text-xs text-primary hover:underline">
                                                    {r.tracking_id || `#${r.id}`}
                                                </a>
                                            </td>
                                            <td className="px-3 py-2">
                                                <div className="text-xs font-medium">{r.customer_name || '—'}</div>
                                                {r.customer_phone && <div className="text-[10px] text-muted-foreground font-mono">{r.customer_phone}</div>}
                                            </td>
                                            <td className="px-3 py-2 text-end font-semibold"><Money value={r.cash_collection} currency={currency} /></td>
                                            <td className="px-3 py-2"><StatusPill label={r.status_label} color={r.status_color} /></td>
                                            <td className="px-3 py-2 text-end text-[11px] text-muted-foreground font-mono">{r.updated_at}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {pagination.last_page > 1 && (
                        <div className="mt-3 flex items-center justify-between text-sm">
                            <div className="text-xs text-muted-foreground">{showing}</div>
                            <div className="flex items-center gap-2">
                                <Button variant="outline" size="sm" disabled={!pagination.prev_url} onClick={() => goPage(pagination.prev_url)}>
                                    <ChevronLeft className="h-4 w-4 me-1" /> {t.prev}
                                </Button>
                                <span className="text-xs text-muted-foreground tabular-nums">
                                    {pagination.current_page} / {pagination.last_page}
                                </span>
                                <Button variant="outline" size="sm" disabled={!pagination.next_url} onClick={() => goPage(pagination.next_url)}>
                                    {t.next} <ChevronRight className="h-4 w-4 ms-1" />
                                </Button>
                            </div>
                        </div>
                    )}
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
