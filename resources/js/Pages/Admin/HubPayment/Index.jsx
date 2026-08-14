import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import {
    Plus, ChevronLeft, ChevronRight, MoreVertical, Download,
    Check, Ban, Edit, Trash2, Banknote, Smartphone, Building2, Wallet,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import {
    DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem,
} from '@/Components/ui/DropdownMenu';
import { cn } from '@/lib/utils';

// ApprovalStatus enum from app/Enums/ApprovalStatus.php
const STATUS_REJECT    = 1;
const STATUS_PENDING   = 3;
const STATUS_PROCESSED = 4;

function Money({ value, currency }) {
    const n = Number(value || 0);
    return (
        <span className="tabular-nums">
            <span className="text-muted-foreground text-xs me-0.5">{currency}</span>
            {n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
        </span>
    );
}

function StatusPill({ status, t }) {
    const map = {
        [STATUS_PENDING]:   ['bg-amber-100 text-amber-800 border-amber-200', t.status_pending],
        [STATUS_PROCESSED]: ['bg-emerald-100 text-emerald-800 border-emerald-200', t.status_processed],
        [STATUS_REJECT]:    ['bg-rose-100 text-rose-800 border-rose-200', t.status_rejected],
    };
    const [klass, label] = map[status] || ['bg-muted text-muted-foreground border-border', '—'];
    return (
        <span className={cn('inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium', klass)}>
            {label}
        </span>
    );
}

function DetailsCell({ details }) {
    if (!details?.kind) return <span className="text-muted-foreground">—</span>;
    if (details.kind === 'Cash') {
        return <span className="inline-flex items-center gap-1 text-sm"><Banknote className="h-3.5 w-3.5 text-emerald-600" /> Cash</span>;
    }
    if (details.kind === 'Bank') {
        return (
            <div className="space-y-0.5">
                <div className="inline-flex items-center gap-1 text-sm font-medium"><Building2 className="h-3.5 w-3.5 text-sky-600" /> {details.account_holder || '—'}</div>
                {details.account_no && <div className="text-xs font-mono text-muted-foreground">{details.account_no}</div>}
                {details.branch && <div className="text-xs text-muted-foreground">{details.branch}</div>}
            </div>
        );
    }
    // Bkash / Rocket / Nagad
    return (
        <div className="space-y-0.5">
            <div className="inline-flex items-center gap-1 text-sm font-medium"><Smartphone className="h-3.5 w-3.5 text-violet-600" /> {details.kind}</div>
            {details.account_holder && <div className="text-xs text-muted-foreground">{details.account_holder}</div>}
            {details.mobile && <div className="text-xs font-mono text-muted-foreground">{details.mobile}</div>}
            {details.account_type && <div className="text-[10px] uppercase tracking-wider text-muted-foreground">{details.account_type}</div>}
        </div>
    );
}

export default function Index({
    rows = [], pagination = {}, permissions = {}, currency = '',
    urls = {}, t = {},
}) {
    const goPage = (url) => url && router.get(url, {}, { preserveState: true });
    const deleteRow = (r) => {
        if (!window.confirm(t.delete_confirm)) return;
        router.delete(r.urls.delete, { preserveScroll: true });
    };

    const showing = (t.showing_results || '')
        .replace(':from', pagination.from ?? 0)
        .replace(':to', pagination.to ?? 0)
        .replace(':total', pagination.total ?? 0);

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title, t.list]}>
            <Head title={`${t.title} · ${t.list}`} />

            <div className="mb-3 flex items-center justify-between">
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Wallet className="h-4 w-4" />
                    <span>{showing}</span>
                </div>
                {permissions.create && (
                    <a href={urls.create} className="inline-flex h-9 items-center justify-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors">
                        <Plus className="h-4 w-4 me-1" /> {t.add}
                    </a>
                )}
            </div>

            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                    <th className="px-4 py-3 text-start">#</th>
                                    <th className="px-4 py-3 text-start">{t.details}</th>
                                    <th className="px-4 py-3 text-start">{t.transaction_id}</th>
                                    <th className="px-4 py-3 text-start">{t.reference}</th>
                                    <th className="px-4 py-3 text-start">{t.description}</th>
                                    <th className="px-4 py-3 text-end">{t.amount}</th>
                                    <th className="px-4 py-3 text-start">{t.status}</th>
                                    <th className="px-4 py-3 text-end pe-4">{t.actions}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 && (
                                    <tr><td colSpan={8} className="px-4 py-10 text-center text-muted-foreground">{t.no_rows}</td></tr>
                                )}
                                {rows.map((r, idx) => {
                                    const num = (pagination.from || 1) + idx;
                                    const isPending   = r.status === STATUS_PENDING;
                                    const isProcessed = r.status === STATUS_PROCESSED;
                                    const isRejected  = r.status === STATUS_REJECT;
                                    return (
                                        <tr key={r.id} className="border-b border-border last:border-0 hover:bg-muted/20 transition-colors">
                                            <td className="px-4 py-3 text-muted-foreground">{num}</td>
                                            <td className="px-4 py-3 align-top"><DetailsCell details={r.details} /></td>
                                            <td className="px-4 py-3 align-top font-mono text-xs">{r.transaction_id || '—'}</td>
                                            <td className="px-4 py-3 align-top">
                                                {r.reference_url
                                                    ? <a href={r.reference_url} download className="inline-flex items-center gap-1 text-xs text-primary hover:underline"><Download className="h-3 w-3" /> {t.download}</a>
                                                    : <span className="text-muted-foreground text-xs">—</span>}
                                            </td>
                                            <td className="px-4 py-3 align-top max-w-[300px] truncate" title={r.description}>{r.description || '—'}</td>
                                            <td className="px-4 py-3 align-top text-end font-semibold"><Money value={r.amount} currency={currency} /></td>
                                            <td className="px-4 py-3 align-top"><StatusPill status={r.status} t={t} /></td>
                                            <td className="px-4 py-3 align-top text-end pe-4">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" size="icon" className="h-8 w-8">
                                                            <MoreVertical className="h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end" className="w-44">
                                                        {/* PENDING: full action set */}
                                                        {isPending && permissions.process && (
                                                            <DropdownMenuItem onClick={() => { window.location.href = r.urls.process; }}>
                                                                <Check className="h-4 w-4 me-2" /> {t.process}
                                                            </DropdownMenuItem>
                                                        )}
                                                        {isPending && permissions.reject && (
                                                            <DropdownMenuItem onClick={() => { window.location.href = r.urls.reject; }}>
                                                                <Ban className="h-4 w-4 me-2" /> {t.reject}
                                                            </DropdownMenuItem>
                                                        )}
                                                        {isPending && permissions.update && (
                                                            <DropdownMenuItem onClick={() => { window.location.href = r.urls.edit; }}>
                                                                <Edit className="h-4 w-4 me-2" /> {t.edit}
                                                            </DropdownMenuItem>
                                                        )}
                                                        {isPending && permissions.delete && (
                                                            <DropdownMenuItem onClick={() => deleteRow(r)} className="text-destructive focus:text-destructive">
                                                                <Trash2 className="h-4 w-4 me-2" /> {t.delete}
                                                            </DropdownMenuItem>
                                                        )}
                                                        {/* PROCESSED → cancel process */}
                                                        {isProcessed && (
                                                            <DropdownMenuItem onClick={() => { window.location.href = r.urls.cancel_process; }}>
                                                                <Ban className="h-4 w-4 me-2" /> {t.cancel_process}
                                                            </DropdownMenuItem>
                                                        )}
                                                        {/* REJECTED → cancel reject */}
                                                        {isRejected && (
                                                            <DropdownMenuItem onClick={() => { window.location.href = r.urls.cancel_reject; }}>
                                                                <Check className="h-4 w-4 me-2" /> {t.cancel_reject}
                                                            </DropdownMenuItem>
                                                        )}
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            {pagination.last_page > 1 && (
                <div className="mt-4 flex items-center justify-between text-sm">
                    <div className="text-muted-foreground">{showing}</div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" disabled={!pagination.prev_url} onClick={() => goPage(pagination.prev_url)}>
                            <ChevronLeft className="h-4 w-4 me-1" /> Prev
                        </Button>
                        <span className="text-xs text-muted-foreground">{pagination.current_page} / {pagination.last_page}</span>
                        <Button variant="outline" size="sm" disabled={!pagination.next_url} onClick={() => goPage(pagination.next_url)}>
                            Next <ChevronRight className="h-4 w-4 ms-1" />
                        </Button>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
