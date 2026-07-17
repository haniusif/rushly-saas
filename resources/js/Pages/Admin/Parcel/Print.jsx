import * as React from 'react';
import { Head } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';

function Money({ value, currency }) {
    const n = Number(value || 0);
    return (
        <span className="tabular-nums">
            {n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
            <span className="text-muted-foreground text-xs ms-0.5">{currency}</span>
        </span>
    );
}

export default function Print({ parcel = {}, merchant = {}, company = {}, urls = {}, t = {} }) {
    const print = () => window.print();
    const currency = company.currency || '';

    return (
        /* No AdminLayout title/breadcrumb — this is a print-optimized invoice
           and the layout's H1 + crumb strip would leak above the merchant/
           customer info when the operator hits Cmd/Ctrl+P. The toolbar
           (back button + Print CTA) already gives on-screen context. */
        <AdminLayout>
            <Head title={`${t.invoice} ${parcel.invoice_no || parcel.tracking_id || ''}`} />

            {/* Toolbar (hidden in print) */}
            <div className="mb-4 flex flex-wrap items-center justify-between gap-2 print:hidden">
                <a href={urls.details} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.back}
                </a>
                <Button type="button" onClick={print}>
                    <Printer className="h-4 w-4 me-1" /> {t.print}
                </Button>
            </div>

            <div className="mx-auto max-w-3xl">
                <Card className="print:shadow-none print:border-0">
                    <CardContent className="p-8 print:p-6">
                        {/* Invoice header */}
                        <div className="flex items-start justify-between gap-4 border-b border-border pb-5 mb-5">
                            <div>
                                <div className="flex items-center gap-3 mb-1">
                                    {company.logo && (
                                        <img src={company.logo} alt="" className="h-10 w-10 rounded object-contain" />
                                    )}
                                    <div className="text-lg font-bold">{company.name || '—'}</div>
                                </div>
                                <div className="text-xs text-muted-foreground">{t.invoice}</div>
                                {parcel.invoice_no && (
                                    <div className="mt-1 text-2xl font-bold font-mono">#{parcel.invoice_no}</div>
                                )}
                            </div>
                            <div className="text-end">
                                <div className="text-[10px] uppercase tracking-wider text-muted-foreground font-semibold">{t.date}</div>
                                <div className="text-sm font-medium">{parcel.created_at}</div>
                                {parcel.tracking_id && (
                                    <>
                                        <div className="mt-2 text-[10px] uppercase tracking-wider text-muted-foreground font-semibold">{t.tracking_id}</div>
                                        <div className="text-sm font-mono font-semibold">{parcel.tracking_id}</div>
                                    </>
                                )}
                            </div>
                        </div>

                        {/* Parties */}
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
                            <div>
                                <div className="text-[10px] uppercase tracking-wider text-muted-foreground font-semibold mb-2">{t.from}</div>
                                <div className="text-sm font-semibold">{merchant.business_name || '—'}</div>
                                {merchant.unique_id && (
                                    <div className="text-xs text-muted-foreground font-mono">#{merchant.unique_id}</div>
                                )}
                                {merchant.mobile && (
                                    <div className="text-xs mt-1.5">{t.phone}: <span className="font-mono">{merchant.mobile}</span></div>
                                )}
                                {merchant.email && (
                                    <div className="text-xs">{t.email}: {merchant.email}</div>
                                )}
                            </div>
                            <div>
                                <div className="text-[10px] uppercase tracking-wider text-muted-foreground font-semibold mb-2">{t.to}</div>
                                <div className="text-sm font-semibold">{parcel.customer_name || '—'}</div>
                                {parcel.customer_phone && (
                                    <div className="text-xs mt-1.5">{t.phone}: <span className="font-mono">{parcel.customer_phone}</span></div>
                                )}
                                {parcel.customer_address && (
                                    <div className="text-xs mt-0.5">{t.address}: {parcel.customer_address}</div>
                                )}
                            </div>
                            <div className="text-xs space-y-1">
                                {parcel.delivery_type && (
                                    <div className="flex justify-between gap-2"><span className="text-muted-foreground">{t.delivery_type}:</span><span className="font-medium">{parcel.delivery_type}</span></div>
                                )}
                                {parcel.pickup_date && (
                                    <div className="flex justify-between gap-2"><span className="text-muted-foreground">{t.pickup_date}:</span><span className="font-mono">{parcel.pickup_date}</span></div>
                                )}
                                {parcel.delivery_date && (
                                    <div className="flex justify-between gap-2"><span className="text-muted-foreground">{t.delivery_date}:</span><span className="font-mono">{parcel.delivery_date}</span></div>
                                )}
                            </div>
                        </div>

                        {/* Line items */}
                        <table className="w-full text-sm border-collapse mb-5">
                            <thead>
                                <tr className="border-b-2 border-border bg-muted/30 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                                    <th className="px-3 py-2 text-start w-12">#</th>
                                    <th className="px-3 py-2 text-start">{t.category}</th>
                                    <th className="px-3 py-2 text-end">{t.weight}</th>
                                    <th className="px-3 py-2 text-end">{t.qty}</th>
                                    <th className="px-3 py-2 text-end">{t.total}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr className="border-b border-border">
                                    <td className="px-3 py-2.5 text-muted-foreground">1</td>
                                    <td className="px-3 py-2.5 font-medium">{parcel.category || '—'}</td>
                                    <td className="px-3 py-2.5 text-end tabular-nums">{parcel.weight || '—'}</td>
                                    <td className="px-3 py-2.5 text-end tabular-nums">1</td>
                                    <td className="px-3 py-2.5 text-end"><Money value={parcel.cash_collection} currency={currency} /></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr className="text-sm">
                                    <td colSpan={4} className="px-3 py-2 text-end text-muted-foreground">{t.delivery_amount}</td>
                                    <td className="px-3 py-2 text-end font-semibold"><Money value={parcel.total_delivery_amount} currency={currency} /></td>
                                </tr>
                                <tr className="text-sm">
                                    <td colSpan={4} className="px-3 py-2 text-end text-muted-foreground">{t.cash_collection}</td>
                                    <td className="px-3 py-2 text-end"><Money value={parcel.cash_collection} currency={currency} /></td>
                                </tr>
                                <tr className="border-t-2 border-border">
                                    <td colSpan={4} className="px-3 py-3 text-end font-bold">{t.current_payable}</td>
                                    <td className="px-3 py-3 text-end font-bold text-lg"><Money value={parcel.current_payable} currency={currency} /></td>
                                </tr>
                            </tfoot>
                        </table>
                    </CardContent>
                </Card>
            </div>

            {/* Print stylesheet — hide AdminLayout chrome at print time */}
            <style>{`
                @media print {
                    @page { margin: 1cm; }
                    body { background: #fff !important; }
                    aside, header, [role="navigation"], nav { display: none !important; }
                    main { padding: 0 !important; }
                    .print\\:hidden { display: none !important; }
                    .print\\:shadow-none { box-shadow: none !important; }
                    .print\\:border-0 { border: 0 !important; }
                    .print\\:p-6 { padding: 1.5rem !important; }
                }
            `}</style>
        </AdminLayout>
    );
}
