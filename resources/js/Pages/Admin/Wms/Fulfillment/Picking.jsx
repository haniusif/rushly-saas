import * as React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, MapPin, Check, CheckCircle2, AlertCircle } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';

// One item at a time, in location-code order, big and thumb-friendly —
// this page is used on the warehouse floor.
export default function Picking({ fulfillment: f = {}, next = null, progress = {}, urls = {}, t = {} }) {
    const form = useForm({
        item_id:    next?.id ?? '',
        picked_qty: next?.quantity_required ?? 0,
        _method:    'put',
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(urls.pick, { preserveScroll: true });
    };

    const pct = progress.total ? Math.round((progress.done / progress.total) * 100) : 0;
    const isShort = next && Number(form.data.picked_qty) < next.quantity_required;

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.list, f.fulfillment_number || `#${f.id}`, t.title]}>
            <Head title={`${t.title} · ${f.fulfillment_number || ''}`} />

            <div className="mx-auto max-w-xl">
                <div className="mb-4 flex items-center justify-between gap-2">
                    <Link href={urls.show} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent">
                        <ArrowLeft className="h-4 w-4 me-1" /> {t.back_to_fulfillment}
                    </Link>
                    <span className="font-mono text-xs uppercase tracking-wider text-muted-foreground">{f.fulfillment_number}</span>
                </div>

                <Card>
                    <CardContent className="p-6">
                        {/* Progress */}
                        <div className="h-2 w-full rounded-full bg-muted overflow-hidden">
                            <div className="h-full bg-emerald-500 transition-all" style={{ width: `${pct}%` }} />
                        </div>
                        <div className="mt-2 text-center text-xs text-muted-foreground tabular-nums">
                            {(t.progress || ':done of :total items picked')
                                .replace(':done', progress.done ?? 0)
                                .replace(':total', progress.total ?? 0)}
                        </div>

                        {next ? (
                            <form onSubmit={submit} className="mt-6">
                                <div className="text-center">
                                    <div className="text-[11px] uppercase tracking-wider font-semibold text-muted-foreground">{t.walk_to}</div>
                                    <div className="mt-3 inline-flex items-center gap-3 rounded-xl bg-slate-900 text-white px-6 py-4 font-mono text-4xl font-bold tracking-widest">
                                        <MapPin className="h-7 w-7 text-amber-400 shrink-0" />
                                        {next.location || '?'}
                                    </div>
                                </div>

                                <div className="mt-5 text-center">
                                    <div className="text-xl font-semibold leading-tight">{next.product || '—'}</div>
                                    <div className="mt-1 font-mono text-sm text-muted-foreground">SKU: {next.sku || '—'}</div>
                                    {next.status === 'short' && (
                                        <div className="mt-2 inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-800 px-2 py-0.5 text-[11px] font-medium">
                                            <AlertCircle className="h-3 w-3" /> {t.previously_short}
                                        </div>
                                    )}
                                </div>

                                <div className="mt-5 rounded-lg bg-muted/40 border border-border px-4 py-4 text-center">
                                    <div className="text-sm text-muted-foreground">{t.pick_this_many}</div>
                                    <div className="mt-1 text-5xl font-extrabold tabular-nums leading-none">{next.quantity_required}</div>
                                </div>

                                <div className="mt-5">
                                    <label className="text-[11px] uppercase tracking-wider font-semibold text-muted-foreground">{t.picked_qty}</label>
                                    <input
                                        type="number"
                                        min="0"
                                        max={next.quantity_required}
                                        value={form.data.picked_qty}
                                        onChange={(e) => form.setData('picked_qty', e.target.value)}
                                        required
                                        className="mt-1.5 w-full rounded-md border border-input bg-background px-4 py-3 text-center text-3xl font-bold tabular-nums focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    />
                                    {form.errors.picked_qty && (
                                        <p className="mt-1 text-xs text-destructive">{form.errors.picked_qty}</p>
                                    )}
                                    {isShort && (
                                        <p className="mt-1.5 text-xs text-amber-700 flex items-center gap-1">
                                            <AlertCircle className="h-3 w-3" /> {t.short_hint}
                                        </p>
                                    )}
                                </div>

                                <Button type="submit" disabled={form.processing} className="mt-5 w-full h-12 text-base font-semibold bg-amber-500 text-white hover:bg-amber-600">
                                    <Check className="h-5 w-5 me-1.5" /> {t.confirm_pick}
                                </Button>

                                {progress.remaining > 1 && (
                                    <div className="mt-3 text-center text-xs text-muted-foreground">
                                        {(t.more_after || ':n more item(s) after this one').replace(':n', progress.remaining - 1)}
                                    </div>
                                )}
                            </form>
                        ) : (
                            <div className="py-10 text-center">
                                <CheckCircle2 className="mx-auto h-16 w-16 text-emerald-500" />
                                <h3 className="mt-4 text-lg font-semibold">{t.all_done_title}</h3>
                                <p className="mt-1 text-sm text-muted-foreground">{t.all_done_body}</p>
                                <Link href={urls.show} className="mt-5 inline-flex h-10 items-center rounded-md bg-primary text-primary-foreground px-4 text-sm font-medium hover:bg-primary/90">
                                    {t.continue}
                                </Link>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
