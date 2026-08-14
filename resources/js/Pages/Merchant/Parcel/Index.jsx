import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Plus, Filter, Eraser, Eye, ListTree, Calendar, Tag, User, Phone, Hash, Upload, Download, ChevronDown } from 'lucide-react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import Pagination from '@/Components/merchant/Pagination';

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
                className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium rounded-md border border-input bg-background hover:bg-muted/40"
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
            {n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
            <span className="text-xs text-muted-foreground ms-0.5">{currency}</span>
        </span>
    );
}

export default function Index({ rows = [], currency = '', filters = {}, lookups = {}, pagination = null, urls = {}, t = {} }) {
    const form = useForm({
        parcel_date:           filters.parcel_date           || '',
        parcel_status:         filters.parcel_status         || '',
        parcel_customer:       filters.parcel_customer       || '',
        parcel_customer_phone: filters.parcel_customer_phone || '',
        invoice_id:            filters.invoice_id            || '',
    });

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.filter, { preserveScroll: true });
    };

    return (
        <MerchantLayout title={t.title} breadcrumbs={[t.dashboard, t.title]}>
            <Head title={t.title} />

            <Card className="mb-3">
                <CardContent className="p-4">
                    <form onSubmit={onSubmit} className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-3 items-end">
                        <div className="space-y-1.5">
                            <label className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                                <Calendar className="h-3 w-3" /> {t.date}
                            </label>
                            <Input value={form.data.parcel_date} onChange={(e) => form.setData('parcel_date', e.target.value)} placeholder={t.date_ph} />
                        </div>
                        <div className="space-y-1.5">
                            <label className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                                <Tag className="h-3 w-3" /> {t.status}
                            </label>
                            <Select value={form.data.parcel_status} onChange={(e) => form.setData('parcel_status', e.target.value)}>
                                <option value="">{t.status_ph}</option>
                                {(lookups.statuses || []).map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                            </Select>
                        </div>
                        <div className="space-y-1.5">
                            <label className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                                <User className="h-3 w-3" /> {t.customer}
                            </label>
                            <Input value={form.data.parcel_customer} onChange={(e) => form.setData('parcel_customer', e.target.value)} placeholder={t.customer_ph} />
                        </div>
                        <div className="space-y-1.5">
                            <label className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                                <Phone className="h-3 w-3" /> {t.customer_phone}
                            </label>
                            <Input value={form.data.parcel_customer_phone} onChange={(e) => form.setData('parcel_customer_phone', e.target.value)} placeholder={t.phone_ph} />
                        </div>
                        <div className="space-y-1.5">
                            <label className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                                <Hash className="h-3 w-3" /> {t.invoice_id}
                            </label>
                            <Input value={form.data.invoice_id} onChange={(e) => form.setData('invoice_id', e.target.value)} placeholder={t.invoice_ph} />
                        </div>
                        <div className="md:col-span-3 lg:col-span-5 flex flex-wrap gap-2">
                            <button type="submit" className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:opacity-90">
                                <Filter className="h-3.5 w-3.5" /> {t.filter}
                            </button>
                            <a href={urls.reset} className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium rounded-md border border-input bg-background hover:bg-muted/40 no-underline">
                                <Eraser className="h-3.5 w-3.5" /> {t.clear}
                            </a>
                            <div className="flex flex-wrap gap-2 ms-auto">
                                {urls.import && (
                                    <a
                                        href={urls.import}
                                        className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium rounded-md border border-input bg-background hover:bg-muted/40 no-underline"
                                    >
                                        <Upload className="h-4 w-4" /> {t.import}
                                    </a>
                                )}
                                {urls.export_xlsx && (
                                    <ExportMenu urls={urls} filters={form.data} t={t} />
                                )}
                                {urls.create && (
                                    <a href={urls.create} className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:opacity-90 no-underline">
                                        <Plus className="h-4 w-4" /> {t.add}
                                    </a>
                                )}
                            </div>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardContent className="p-0">
                    <div className="flex items-center justify-between px-5 py-4 border-b border-border">
                        <h2 className="text-base font-semibold m-0">{t.title}</h2>
                        {pagination?.total != null && (
                            <span className="text-xs text-muted-foreground">{pagination.total} total</span>
                        )}
                    </div>
                    {rows.length === 0 ? (
                        <div className="p-8 text-center text-sm text-muted-foreground">{t.empty}</div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/30 text-xs uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th className="text-start font-medium px-4 py-2.5 w-12">#</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.tracking_id}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.recipient_info}</th>
                                        <th className="text-end   font-medium px-4 py-2.5">{t.amount}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.status}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.payment}</th>
                                        <th className="text-end   font-medium px-4 py-2.5 w-32">{/* actions */}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {rows.map((r) => (
                                        <tr key={r.id}>
                                            <td className="px-4 py-2.5 tabular-nums align-top">{r.serial}</td>
                                            <td className="px-4 py-2.5 align-top">
                                                <a href={r.details_url} className="text-primary hover:underline font-mono text-xs">{r.tracking_id}</a>
                                                {r.invoice_id && <div className="text-[11px] text-muted-foreground">{t.invoice_id}: {r.invoice_id}</div>}
                                            </td>
                                            <td className="px-4 py-2.5 align-top">
                                                <div className="font-medium">{r.customer_name}</div>
                                                {r.customer_phone && <div className="text-[11px] text-muted-foreground">{r.customer_phone}</div>}
                                            </td>
                                            <td className="px-4 py-2.5 text-end align-top"><Money value={r.amount} currency={currency} /></td>
                                            <td className="px-4 py-2.5 align-top">
                                                <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border bg-muted/40 border-border">
                                                    {r.status_label}
                                                </span>
                                            </td>
                                            <td className="px-4 py-2.5 align-top text-xs text-muted-foreground">{r.payment_label || <span>—</span>}</td>
                                            <td className="px-4 py-2.5 align-top text-end">
                                                <div className="inline-flex gap-1.5">
                                                    <a href={r.details_url} className="inline-flex items-center gap-1.5 h-7 px-2.5 text-xs rounded-md border border-input hover:bg-muted/40 no-underline">
                                                        <Eye className="h-3 w-3" /> {t.view}
                                                    </a>
                                                    <a href={r.logs_url} className="inline-flex items-center gap-1.5 h-7 px-2.5 text-xs rounded-md border border-input hover:bg-muted/40 no-underline">
                                                        <ListTree className="h-3 w-3" /> {t.logs}
                                                    </a>
                                                </div>
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
