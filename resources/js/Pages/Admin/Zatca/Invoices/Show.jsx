import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Download, QrCode, RefreshCw, FileText } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { cn } from '@/lib/utils';

const STATUS_STYLES = {
    pending:    'bg-amber-100 text-amber-800',
    generated:  'bg-emerald-100 text-emerald-800',
    failed:     'bg-rose-100 text-rose-800',
    regenerated:'bg-sky-100 text-sky-800',
};

function Row({ label, children }) {
    return (
        <div className="grid grid-cols-3 gap-2 border-b border-slate-100 py-2 last:border-0">
            <div className="text-xs uppercase tracking-wide text-slate-500">{label}</div>
            <div className="col-span-2 text-sm text-slate-800">{children}</div>
        </div>
    );
}

function fmt(n) {
    return new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0);
}

export default function Show({ invoice = {}, urls = {}, t = {} }) {
    const regenerate = () => {
        if (!confirm('Regenerate this ZATCA invoice?')) return;
        router.post(urls.regenerate);
    };

    return (
        <AdminLayout title={invoice.invoice_number} breadcrumbs={[t.title || 'ZATCA', invoice.invoice_number]}>
            <Head title={invoice.invoice_number} />

            <div className="mb-4 flex items-center justify-between">
                <Link href={urls.index} className="inline-flex items-center gap-1 text-sm text-slate-600 hover:text-slate-900">
                    <ArrowLeft className="h-4 w-4" /> Back to list
                </Link>
                <div className="flex gap-2">
                    <a href={urls.pdf} className="inline-flex h-9 items-center gap-1 rounded-md border px-3 text-sm hover:bg-slate-50">
                        <Download className="h-4 w-4" /> {t.download_pdf}
                    </a>
                    <a href={urls.qr} target="_blank" rel="noreferrer" className="inline-flex h-9 items-center gap-1 rounded-md border px-3 text-sm hover:bg-slate-50">
                        <QrCode className="h-4 w-4" /> {t.download_qr}
                    </a>
                    <Button variant="outline" onClick={regenerate}>
                        <RefreshCw className="mr-2 h-4 w-4" /> {t.regenerate}
                    </Button>
                </div>
            </div>

            <div className="grid gap-4 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardContent className="p-5">
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="text-lg font-semibold flex items-center gap-2">
                                <FileText className="h-5 w-5 text-indigo-600" />
                                {invoice.invoice_number}
                            </h2>
                            <span className={cn('rounded-full px-3 py-1 text-xs font-medium', STATUS_STYLES[invoice.status] || 'bg-slate-100 text-slate-700')}>
                                {invoice.status_label}
                            </span>
                        </div>

                        <Row label="UUID"><code className="text-xs">{invoice.uuid}</code></Row>
                        <Row label={t.type}>{invoice.type_label}</Row>
                        <Row label={t.issued_at}>{(invoice.issued_at || '').replace('T', ' ').slice(0, 19)} UTC</Row>
                        <Row label={t.buyer}>{invoice.buyer_name || '—'}</Row>
                        {invoice.buyer_vat_number && <Row label="Buyer VAT">{invoice.buyer_vat_number}</Row>}
                        <Row label={t.subtotal}>{fmt(invoice.subtotal)} {invoice.currency}</Row>
                        <Row label={`${t.vat_amount} (${invoice.vat_rate}%)`}>{fmt(invoice.vat_amount)} {invoice.currency}</Row>
                        <Row label={t.total_inclusive}><strong>{fmt(invoice.total_inclusive)} {invoice.currency}</strong></Row>
                        <Row label="Hash"><code className="break-all text-xs">{invoice.hash}</code></Row>
                        {invoice.previous_hash && <Row label="Previous Hash"><code className="break-all text-xs">{invoice.previous_hash}</code></Row>}
                        {invoice.error_message && (
                            <Row label="Error"><span className="text-rose-600">{invoice.error_message}</span></Row>
                        )}

                        <div className="mt-4">
                            <div className="mb-1 text-xs uppercase tracking-wide text-slate-500">{t.tlv_payload}</div>
                            <textarea readOnly value={invoice.qr_payload || ''} className="h-24 w-full rounded-md border bg-slate-50 p-2 font-mono text-xs" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-5 text-center">
                        <div className="mb-2 text-xs uppercase tracking-wide text-slate-500">{t.qr_preview}</div>
                        {invoice.qr_image_url ? (
                            <img src={invoice.qr_image_url} alt="ZATCA QR" className="mx-auto w-full max-w-[240px]" />
                        ) : (
                            <a href={urls.qr} target="_blank" rel="noreferrer">
                                <QrCode className="mx-auto h-32 w-32 text-slate-300" />
                            </a>
                        )}
                        <p className="mt-2 text-[11px] text-slate-500">Scan with the Fatoora app to verify</p>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
