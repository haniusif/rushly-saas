import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Filter, Eraser, Calendar, Tag, Hash } from 'lucide-react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import Pagination from '@/Components/merchant/Pagination';

export default function Index({ rows = [], currency = '', filters = {}, lookups = {}, pagination = null, urls = {}, t = {} }) {
    const form = useForm({
        date: filters.date || '',
        type: filters.type || '',
        parcel_tracking_id: filters.parcel_tracking_id || '',
    });

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.filter, { preserveScroll: true });
    };

    return (
        <MerchantLayout title={t.title} breadcrumbs={[t.dashboard, t.title, t.list]}>
            <Head title={t.title} />

            <Card className="mb-3">
                <CardContent className="p-4">
                    <form onSubmit={onSubmit} className="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                        <div className="space-y-1.5">
                            <label className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                                <Calendar className="h-3 w-3" /> {t.date_filter}
                            </label>
                            <Input
                                value={form.data.date}
                                onChange={(e) => form.setData('date', e.target.value)}
                                placeholder={t.date_ph}
                            />
                        </div>
                        <div className="space-y-1.5">
                            <label className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                                <Tag className="h-3 w-3" /> {t.type}
                            </label>
                            <Select value={form.data.type} onChange={(e) => form.setData('type', e.target.value)}>
                                <option value="">{t.type_ph}</option>
                                {(lookups.types || []).map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                            </Select>
                        </div>
                        <div className="space-y-1.5">
                            <label className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                                <Hash className="h-3 w-3" /> {t.tracking_id}
                            </label>
                            <Input
                                value={form.data.parcel_tracking_id}
                                onChange={(e) => form.setData('parcel_tracking_id', e.target.value)}
                                placeholder={t.tracking_ph}
                            />
                        </div>
                        <div className="flex gap-2">
                            <button
                                type="submit"
                                className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:opacity-90"
                            >
                                <Filter className="h-3.5 w-3.5" /> {t.filter}
                            </button>
                            <a
                                href={urls.reset}
                                className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium rounded-md border border-input bg-background hover:bg-muted/40 no-underline"
                            >
                                <Eraser className="h-3.5 w-3.5" /> {t.clear}
                            </a>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardContent className="p-0">
                    <div className="px-5 py-4 border-b border-border">
                        <h2 className="text-base font-semibold m-0">{t.title}</h2>
                    </div>
                    {rows.length === 0 ? (
                        <div className="p-8 text-center text-sm text-muted-foreground">{t.empty}</div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/30 text-xs uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th className="text-start font-medium px-4 py-2.5 w-16">{t.id}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.details}</th>
                                        <th className="text-start font-medium px-4 py-2.5 w-40">{t.date_label}</th>
                                        <th className="text-start font-medium px-4 py-2.5 w-32">{t.type}</th>
                                        <th className="text-end   font-medium px-4 py-2.5 w-40">{t.amount}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {rows.map((r) => (
                                        <tr key={r.id}>
                                            <td className="px-4 py-2.5 tabular-nums">{r.serial}</td>
                                            <td className="px-4 py-2.5">{r.note}</td>
                                            <td className="px-4 py-2.5 text-xs text-muted-foreground">{r.date}</td>
                                            <td className="px-4 py-2.5">
                                                <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border ${
                                                    r.is_income
                                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                        : 'bg-rose-50 text-rose-700 border-rose-200'
                                                }`}>{r.type_label}</span>
                                            </td>
                                            <td className="px-4 py-2.5 text-end tabular-nums font-medium">
                                                {r.amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} <span className="text-xs text-muted-foreground">{currency}</span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    <Pagination pagination={pagination} />
                </CardContent>
            </Card>
        </MerchantLayout>
    );
}
