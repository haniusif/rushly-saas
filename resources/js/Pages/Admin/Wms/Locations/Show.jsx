import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft, Edit3, Trash2, MapPin, Building2, Layers, Grid3x3, Box, Gauge, Clock, Boxes, Map,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Pill, tableHeadClass, tableRowClass, ucwords } from '@/Components/wms/ListPage';

const TYPE_COLORS = { standard: 'blue', bulk: 'violet', cold: 'sky', hazmat: 'rose' };

function StatCard({ icon: Icon, label, value, tone = 'default' }) {
    const toneClass = {
        default: 'bg-slate-50 border-slate-200',
        green:   'bg-emerald-50 border-emerald-200',
        amber:   'bg-amber-50 border-amber-200',
        red:     'bg-rose-50 border-rose-200',
    }[tone];
    return (
        <div className={`rounded-md border px-4 py-3 ${toneClass}`}>
            <div className="flex items-center gap-1.5 text-[10px] uppercase tracking-wider font-semibold text-muted-foreground">
                {Icon && <Icon className="h-3 w-3" />} {label}
            </div>
            <div className="mt-1 text-2xl font-bold tabular-nums">{value}</div>
        </div>
    );
}

function Row({ icon: Icon, label, children, mono = false }) {
    return (
        <div className="flex items-start gap-3 py-2 border-b border-border/60 last:border-0">
            <div className="flex items-center gap-1.5 text-[11px] uppercase tracking-wider font-semibold text-muted-foreground w-28 shrink-0 pt-0.5">
                {Icon && <Icon className="h-3 w-3" />} {label}
            </div>
            <div className={`text-sm flex-1 min-w-0 break-words ${mono ? 'font-mono' : ''}`}>
                {children ?? <span className="text-muted-foreground">—</span>}
            </div>
        </div>
    );
}

// Zone › Aisle › Rack › Shelf › Bin as a breadcrumb-style path.
function Hierarchy({ location, t }) {
    const parts = [
        [t.zone, location.zone], [t.aisle, location.aisle], [t.rack, location.rack],
        [t.shelf, location.shelf], [t.bin, location.bin],
    ];
    return (
        <div className="flex flex-wrap items-center gap-1.5">
            {parts.map(([label, value], i) => (
                <React.Fragment key={label}>
                    {i > 0 && <span className="text-muted-foreground/60 text-xs">›</span>}
                    <div className={`rounded-md border px-2 py-1 text-center min-w-[52px] ${value ? 'border-border bg-card' : 'border-dashed border-border/70 bg-muted/20'}`}>
                        <div className="text-[9px] uppercase tracking-wider text-muted-foreground">{label}</div>
                        <div className={`text-sm font-mono font-semibold ${value ? '' : 'text-muted-foreground/60'}`}>{value || '—'}</div>
                    </div>
                </React.Fragment>
            ))}
        </div>
    );
}

export default function Show({ location = {}, stock = {}, permissions = {}, urls = {}, t = {} }) {
    const onDelete = () => {
        if (!window.confirm(t.delete_confirm)) return;
        router.delete(urls.destroy);
    };

    const rows = stock.rows || [];
    const capacity = location.capacity;
    const fill = capacity ? Math.min(100, Math.round(((stock.on_hand ?? 0) / capacity) * 100)) : null;
    const fillTone = fill === null ? 'default' : fill >= 100 ? 'red' : fill >= 80 ? 'amber' : 'green';

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.list, location.code || `#${location.id}`]}>
            <Head title={location.code || t.title} />

            {/* Toolbar */}
            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <Link href={urls.index} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.back_to_list}
                </Link>
                <div className="flex flex-wrap items-center gap-2">
                    <Link href={urls.map} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent">
                        <Map className="h-4 w-4 me-1" /> {t.map_view}
                    </Link>
                    {permissions.update && (
                        <Link href={urls.edit} className="inline-flex h-9 items-center rounded-md bg-primary text-primary-foreground px-3 text-sm font-medium hover:bg-primary/90">
                            <Edit3 className="h-4 w-4 me-1" /> {t.edit}
                        </Link>
                    )}
                    {permissions.delete && (
                        <Button type="button" variant="outline" onClick={onDelete} className="text-rose-600 border-rose-200 hover:bg-rose-50">
                            <Trash2 className="h-4 w-4 me-1" /> {t.delete}
                        </Button>
                    )}
                </div>
            </div>

            <div className="grid gap-5 lg:grid-cols-3">
                {/* Identity */}
                <Card>
                    <CardContent className="p-6">
                        <div className="flex items-start gap-3 pb-4 border-b border-border">
                            <div className="grid h-14 w-14 place-items-center rounded-md bg-primary/10 text-primary shrink-0">
                                <MapPin className="h-7 w-7" />
                            </div>
                            <div className="min-w-0 flex-1">
                                <div className="text-lg font-bold leading-tight font-mono truncate">{location.code || '—'}</div>
                                <div className="text-xs text-muted-foreground mt-0.5">{location.hub || '—'}</div>
                                <div className="mt-2 flex items-center gap-1.5 flex-wrap">
                                    <Pill color={TYPE_COLORS[location.type] || 'blue'}>{ucwords(location.type || '')}</Pill>
                                    <Pill color={location.is_active ? 'emerald' : 'grey'}>{location.is_active ? t.active : t.inactive}</Pill>
                                </div>
                            </div>
                        </div>

                        <div className="mt-4">
                            <div className="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground mb-2">{t.hierarchy}</div>
                            <Hierarchy location={location} t={t} />
                        </div>

                        <div className="mt-4 pt-4 border-t border-border space-y-0.5">
                            <Row icon={Building2} label={t.hub}>{location.hub}</Row>
                            <Row icon={Layers} label={t.type}>{ucwords(location.type || '')}</Row>
                            <Row icon={Gauge} label={t.capacity}>{capacity ?? null}</Row>
                            <Row icon={Clock} label={t.created_at}>{location.created_at}</Row>
                            <Row icon={Clock} label={t.updated_at}>{location.updated_at}</Row>
                        </div>
                    </CardContent>
                </Card>

                {/* Stock */}
                <div className="lg:col-span-2 space-y-5">
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <StatCard icon={Boxes} label={t.products} value={stock.products ?? 0} />
                        <StatCard label={t.on_hand} value={stock.on_hand ?? 0} tone="green" />
                        <StatCard label={t.reserved} value={stock.reserved ?? 0} tone="amber" />
                        <StatCard icon={Gauge} label={t.fill} value={fill === null ? '—' : `${fill}%`} tone={fillTone} />
                    </div>

                    <Card>
                        <CardContent className="p-0">
                            <div className="flex items-center justify-between px-5 py-4 border-b border-border">
                                <div className="text-sm font-semibold tracking-tight">{t.stocked_products}</div>
                                <div className="text-xs text-muted-foreground">{t.stock_hint}</div>
                            </div>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className={tableHeadClass}>
                                            <th className="px-4 py-3 text-start">{t.product}</th>
                                            <th className="px-4 py-3 text-end">{t.on_hand}</th>
                                            <th className="px-4 py-3 text-end">{t.reserved}</th>
                                            <th className="px-4 py-3 text-end">{t.available}</th>
                                            <th className="px-4 py-3 text-start">{t.batch}</th>
                                            <th className="px-4 py-3 text-start">{t.expiry}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {rows.length === 0 && (
                                            <tr>
                                                <td colSpan={6} className="px-4 py-10 text-center text-sm text-muted-foreground">
                                                    <Box className="mx-auto mb-2 h-10 w-10 text-muted-foreground/40" />
                                                    {t.empty_location}
                                                </td>
                                            </tr>
                                        )}
                                        {rows.map((s) => (
                                            <tr key={s.id} className={`${tableRowClass} ${s.expiring ? 'bg-amber-50/60 hover:bg-amber-50' : ''}`}>
                                                <td className="px-4 py-3 align-top">
                                                    <a href={s.product_url} className="font-medium leading-tight hover:underline">{s.product || '—'}</a>
                                                    <div className="mt-0.5 font-mono text-[11px] text-muted-foreground">{s.sku || '—'}</div>
                                                </td>
                                                <td className="px-4 py-3 align-top text-end tabular-nums font-medium">{s.quantity}</td>
                                                <td className="px-4 py-3 align-top text-end tabular-nums text-muted-foreground">{s.reserved}</td>
                                                <td className="px-4 py-3 align-top text-end tabular-nums">{s.available}</td>
                                                <td className="px-4 py-3 align-top font-mono text-xs">{s.batch_number || '—'}</td>
                                                <td className="px-4 py-3 align-top text-xs tabular-nums">
                                                    {s.expiry_date || '—'}
                                                    {s.expiring && <Pill color="amber" className="ms-2">{t.expiring}</Pill>}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
