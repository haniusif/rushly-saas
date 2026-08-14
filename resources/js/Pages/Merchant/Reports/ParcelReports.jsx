import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Filter, Eraser, Calendar, Tag, Printer } from 'lucide-react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Input } from '@/Components/ui/Input';

function fmt(n) {
    const v = Number(n) || 0;
    return v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

export default function ParcelReports({ rows = [], totals = {}, currency = '', has_data = false, parcel_ids = '', filters = {}, lookups = {}, urls = {}, t = {} }) {
    const form = useForm({
        parcel_date:   filters.parcel_date   || '',
        parcel_status: filters.parcel_status || [],
    });

    const onSubmit = (e) => {
        e.preventDefault();
        form.get(urls.filter, { preserveScroll: true });
    };

    const toggleStatus = (val) => {
        const current = form.data.parcel_status || [];
        const next = current.includes(val) ? current.filter((v) => v !== val) : [...current, val];
        form.setData('parcel_status', next);
    };

    return (
        <MerchantLayout title={t.title} breadcrumbs={[t.dashboard, t.reports, t.title]}>
            <Head title={t.title} />

            <Card className="mb-3">
                <CardContent className="p-4">
                    <form onSubmit={onSubmit} className="space-y-3">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                            <div className="space-y-1.5">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                                    <Calendar className="h-3 w-3" /> {t.date}
                                </label>
                                <Input value={form.data.parcel_date} onChange={(e) => form.setData('parcel_date', e.target.value)} placeholder={t.date_ph} />
                            </div>
                            <div className="space-y-1.5 md:col-span-2">
                                <label className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                                    <Tag className="h-3 w-3" /> {t.status}
                                </label>
                                <div className="flex flex-wrap gap-1.5 max-h-32 overflow-y-auto rounded-md border border-input bg-background px-2 py-2">
                                    {(lookups.statuses || []).map((s) => {
                                        const selected = (form.data.parcel_status || []).includes(s.value);
                                        return (
                                            <button
                                                key={s.value}
                                                type="button"
                                                onClick={() => toggleStatus(s.value)}
                                                className={`inline-flex items-center px-2.5 py-1 rounded-full text-[11px] border ${
                                                    selected
                                                        ? 'bg-primary text-primary-foreground border-primary'
                                                        : 'bg-muted/30 border-border hover:bg-muted/50'
                                                }`}
                                            >
                                                {s.label}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <button type="submit" className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:opacity-90">
                                <Filter className="h-3.5 w-3.5" /> {t.filter}
                            </button>
                            <a href={urls.reset} className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium rounded-md border border-input bg-background hover:bg-muted/40 no-underline">
                                <Eraser className="h-3.5 w-3.5" /> {t.clear}
                            </a>
                            {urls.print && (
                                <a href={urls.print} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium rounded-md border border-input bg-background hover:bg-muted/40 no-underline ml-auto">
                                    <Printer className="h-3.5 w-3.5" /> {t.print}
                                </a>
                            )}
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardContent className="p-0">
                    <div className="px-5 py-4 border-b border-border">
                        <h2 className="text-base font-semibold m-0">{t.title}</h2>
                    </div>
                    {!has_data || rows.length === 0 ? (
                        <div className="p-8 text-center text-sm text-muted-foreground">{t.empty}</div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/30 text-xs uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th className="text-start font-medium px-4 py-2.5 w-14">{t.serial}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.status}</th>
                                        <th className="text-end   font-medium px-4 py-2.5 w-32">{t.count}</th>
                                        <th className="text-end   font-medium px-4 py-2.5 w-48">{t.cash_collection}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {rows.map((r) => (
                                        <tr key={r.status}>
                                            <td className="px-4 py-2.5 tabular-nums">{r.serial}</td>
                                            <td className="px-4 py-2.5">{r.status_label}</td>
                                            <td className="px-4 py-2.5 text-end tabular-nums">{r.count}</td>
                                            <td className="px-4 py-2.5 text-end tabular-nums">{fmt(r.cash)} <span className="text-xs text-muted-foreground">{currency}</span></td>
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot className="bg-muted/30 border-t border-border">
                                    <tr>
                                        <td colSpan="2" className="px-4 py-2.5 font-semibold">{t.total}</td>
                                        <td className="px-4 py-2.5 text-end tabular-nums font-semibold">{totals.count}</td>
                                        <td className="px-4 py-2.5 text-end tabular-nums font-semibold">{fmt(totals.cash)} <span className="text-xs text-muted-foreground">{currency}</span></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    )}
                </CardContent>
            </Card>
        </MerchantLayout>
    );
}
