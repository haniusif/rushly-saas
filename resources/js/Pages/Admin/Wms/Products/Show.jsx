import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    Package, ArrowLeft, Edit3, Trash2, Barcode, Tag, Hash, Building2,
    Store, Ruler, Scale, Layers, MapPin, AlertTriangle, CheckCircle2,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';

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

function Row({ icon: Icon, label, children }) {
    return (
        <div className="flex items-start gap-3 py-2 border-b border-border/60 last:border-0">
            <div className="flex items-center gap-1.5 text-[11px] uppercase tracking-wider font-semibold text-muted-foreground w-40 shrink-0 pt-0.5">
                {Icon && <Icon className="h-3 w-3" />} {label}
            </div>
            <div className="text-sm flex-1 min-w-0 break-words">{children || <span className="text-muted-foreground">—</span>}</div>
        </div>
    );
}

export default function Show({ product = {}, stock = {}, permissions = {}, urls = {}, t = {} }) {
    const dims = product.dimensions;
    const dimStr = dims && (dims.l || dims.w || dims.h)
        ? `${dims.l || '—'} × ${dims.w || '—'} × ${dims.h || '—'}`
        : null;

    const onDelete = () => {
        if (!window.confirm(t.delete_confirm)) return;
        router.delete(urls.destroy);
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.list, product.sku || product.name || `#${product.id}`]}>
            <Head title={`${product.name || ''} · ${product.sku || ''}`} />

            {/* Toolbar */}
            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <Link href={urls.index} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.back_to_list}
                </Link>
                <div className="flex flex-wrap items-center gap-2">
                    <a
                        href={urls.barcode}
                        target="_blank"
                        rel="noreferrer"
                        className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent"
                    >
                        <Barcode className="h-4 w-4 me-1" /> {t.print_barcode}
                    </a>
                    {permissions.update && (
                        <Link
                            href={urls.edit}
                            className="inline-flex h-9 items-center rounded-md bg-primary text-primary-foreground px-3 text-sm font-medium hover:bg-primary/90"
                        >
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
                {/* Identity card */}
                <Card>
                    <CardContent className="p-6">
                        <div className="flex items-start gap-3 pb-4 border-b border-border">
                            <div className="grid h-14 w-14 place-items-center rounded-md bg-primary/10 text-primary shrink-0">
                                <Package className="h-7 w-7" />
                            </div>
                            <div className="min-w-0 flex-1">
                                <div className="text-lg font-bold leading-tight truncate">{product.name || '—'}</div>
                                <div className="text-xs text-muted-foreground font-mono mt-0.5">{product.sku}</div>
                                <div className="mt-2 flex items-center gap-1.5 flex-wrap">
                                    {product.is_active ? (
                                        <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 text-emerald-700 px-2 py-0.5 text-[10px] font-medium">
                                            <CheckCircle2 className="h-3 w-3" /> {t.is_active}
                                        </span>
                                    ) : (
                                        <span className="inline-flex items-center rounded-full bg-slate-100 text-slate-700 px-2 py-0.5 text-[10px] font-medium">
                                            {t.is_active}: {t.no}
                                        </span>
                                    )}
                                    {product.track_expiry && (
                                        <span className="inline-flex items-center rounded-full bg-amber-100 text-amber-700 px-2 py-0.5 text-[10px] font-medium">
                                            {t.track_expiry}
                                        </span>
                                    )}
                                    {stock.low && (
                                        <span className="inline-flex items-center gap-1 rounded-full bg-rose-100 text-rose-700 px-2 py-0.5 text-[10px] font-medium">
                                            <AlertTriangle className="h-3 w-3" /> {t.low}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="mt-3 space-y-0.5">
                            <Row icon={Hash} label={t.sku}><span className="font-mono">{product.sku}</span></Row>
                            <Row icon={Tag} label={t.barcode}><span className="font-mono">{product.barcode}</span></Row>
                            <Row icon={Layers} label={t.category}>{product.category}</Row>
                            <Row icon={Store} label={t.merchant}>{product.merchant}</Row>
                            <Row icon={Building2} label={t.hub}>{product.hub}</Row>
                        </div>
                    </CardContent>
                </Card>

                {/* Specifications + dates */}
                <Card>
                    <CardContent className="p-6">
                        <div className="mb-3 text-sm font-semibold tracking-tight">{t.specifications}</div>
                        <div className="space-y-0.5">
                            <Row icon={Package} label={t.unit}>{product.unit}</Row>
                            <Row icon={Scale} label={t.weight}>{product.weight}</Row>
                            <Row icon={Ruler} label={t.dimensions}>{dimStr}</Row>
                            <Row label={t.reorder_point}>{product.reorder_point}</Row>
                            <Row label={t.track_expiry}>{product.track_expiry ? t.yes : t.no}</Row>
                        </div>

                        {product.description && (
                            <div className="mt-4 pt-4 border-t border-border">
                                <div className="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground mb-1.5">{t.description}</div>
                                <p className="text-sm whitespace-pre-line text-foreground/90">{product.description}</p>
                            </div>
                        )}

                        <div className="mt-4 pt-4 border-t border-border space-y-0.5 text-xs">
                            <Row label={t.created_at}>{product.created_at}</Row>
                            <Row label={t.updated_at}>{product.updated_at}</Row>
                        </div>
                    </CardContent>
                </Card>

                {/* Stock summary card */}
                <Card>
                    <CardContent className="p-6">
                        <div className="mb-3 text-sm font-semibold tracking-tight">{t.stock_summary}</div>
                        <div className="grid grid-cols-3 gap-2">
                            <StatCard label={t.on_hand} value={stock.on_hand ?? 0} tone={stock.low ? 'red' : 'green'} />
                            <StatCard label={t.reserved} value={stock.reserved ?? 0} tone="amber" />
                            <StatCard label={t.available} value={stock.available ?? 0} />
                        </div>

                        <div className="mt-4">
                            <div className="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground mb-2">{t.stock_per_location}</div>
                            {stock.rows && stock.rows.length > 0 ? (
                                <div className="rounded-md border border-border overflow-hidden">
                                    <table className="w-full text-xs">
                                        <thead className="bg-muted/40 text-[10px] uppercase tracking-wider text-muted-foreground">
                                            <tr>
                                                <th className="px-3 py-2 text-start font-semibold">{t.location}</th>
                                                <th className="px-3 py-2 text-end font-semibold">{t.on_hand}</th>
                                                <th className="px-3 py-2 text-end font-semibold">{t.reserved}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {stock.rows.map((r, i) => (
                                                <tr key={i} className="border-t border-border">
                                                    <td className="px-3 py-2 font-mono"><MapPin className="h-3 w-3 inline me-1 text-muted-foreground" />{r.location || '—'}</td>
                                                    <td className="px-3 py-2 text-end tabular-nums font-medium">{r.quantity}</td>
                                                    <td className="px-3 py-2 text-end tabular-nums text-muted-foreground">{r.reserved}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="rounded-md border border-dashed border-border px-4 py-6 text-center text-xs text-muted-foreground">
                                    {t.no_stock}
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
