import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Filter, Eraser, Calendar, AlertCircle } from 'lucide-react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Input } from '@/Components/ui/Input';

function money(v, currency, digits = 2) {
    const n = Number(v) || 0;
    return (
        <span className="tabular-nums">
            {currency} {n.toLocaleString(undefined, { minimumFractionDigits: digits, maximumFractionDigits: digits })}
        </span>
    );
}

function Row({ label, value, currency, accent = false, positive = null }) {
    const valueClass = positive === true
        ? 'text-emerald-700'
        : positive === false
            ? 'text-rose-600'
            : '';
    return (
        <li className={`flex items-center justify-between px-5 py-3 ${accent ? 'bg-emerald-50/40' : ''}`}>
            <span className="text-sm text-foreground/80">{label}</span>
            <span className={`text-sm font-semibold ${valueClass}`}>{money(value, currency)}</span>
        </li>
    );
}

function GroupCard({ title, children }) {
    return (
        <div className="bg-card border border-border rounded-xl shadow-sm overflow-hidden">
            <div className="px-5 py-3 border-b border-border">
                <h3 className="text-sm font-semibold m-0">{title}</h3>
            </div>
            <ul className="divide-y divide-border m-0 list-none p-0">{children}</ul>
        </div>
    );
}

export default function TotalSummery({ currency = '', has_data = false, filters = {}, profit = {}, totals = {}, payments = {}, urls = {}, t = {} }) {
    const form = useForm({ date: filters.date || '' });

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.filter, { preserveScroll: true });
    };

    return (
        <MerchantLayout title={t.title} breadcrumbs={[t.dashboard, t.reports, t.title]}>
            <Head title={t.title} />

            <Card className="mb-3">
                <CardContent className="p-4">
                    <form onSubmit={onSubmit} className="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                        <div className="space-y-1.5 md:col-span-2">
                            <label className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                                <Calendar className="h-3 w-3" /> {t.date}
                            </label>
                            <Input value={form.data.date} onChange={(e) => form.setData('date', e.target.value)} placeholder={t.date_ph} />
                        </div>
                        <div className="flex gap-2">
                            <button type="submit" className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:opacity-90">
                                <Filter className="h-3.5 w-3.5" /> {t.filter}
                            </button>
                            <a href={urls.reset} className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium rounded-md border border-input bg-background hover:bg-muted/40 no-underline">
                                <Eraser className="h-3.5 w-3.5" /> {t.clear}
                            </a>
                        </div>
                    </form>
                </CardContent>
            </Card>

            {!has_data && (
                <div className="mb-3 rounded-md border border-amber-200 bg-amber-50 text-amber-900 px-4 py-3 text-sm flex items-center gap-2">
                    <AlertCircle className="h-4 w-4" /> {t.apply_filter_hint}
                </div>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-3">
                <GroupCard title={t.group_profit}>
                    <Row label={t.delivery_charge}  value={profit.delivery_charge} currency={currency} />
                    <Row label={t.cod}              value={profit.cod}             currency={currency} />
                    <Row label={t.vat}              value={profit.vat}             currency={currency} />
                    <Row label={t.liquid_fragile}   value={profit.liquid_fragile}  currency={currency} />
                    <Row label={t.packaging}        value={profit.packaging}       currency={currency} />
                    <Row label={t.net_profit}       value={profit.net_profit}      currency={currency} accent positive={profit.net_profit >= 0} />
                </GroupCard>

                <GroupCard title={t.group_sales}>
                    <Row label={t.cash_collection}  value={totals.cash_collection} currency={currency} />
                    <Row label={t.selling_price}    value={totals.selling_price}   currency={currency} />
                    <Row label={t.payable_amount}   value={totals.payable_amount}  currency={currency} accent />
                </GroupCard>

                <GroupCard title={t.group_payments}>
                    <Row label={t.paid_amount}        value={payments.paid_amount}        currency={currency} />
                    <Row label={t.delivery_charge_vat} value={profit.delivery_charge_vat} currency={currency} />
                    <Row label={t.pending_amount}     value={payments.pending_amount}     currency={currency} />
                </GroupCard>

                <GroupCard title={t.group_accounts}>
                    <Row label={t.bank_opening} value={totals.bank_opening} currency={currency} />
                    <Row label={t.bank_balance} value={totals.bank_balance} currency={currency} />
                </GroupCard>
            </div>
        </MerchantLayout>
    );
}
