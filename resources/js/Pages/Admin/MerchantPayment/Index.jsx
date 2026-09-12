import * as React from 'react';
import { router } from '@inertiajs/react';
import {
    MoreVertical, Edit, Trash2, CheckCircle2, Ban, Undo2, Download, Landmark, Smartphone, Banknote, Wallet,
} from 'lucide-react';
import ListPage, { tableHeadClass, tableRowClass, emptyRow, FilterLabel, Pill } from '@/Components/wms/ListPage';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import { Button } from '@/Components/ui/Button';
import {
    DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator,
} from '@/Components/ui/DropdownMenu';

// App\Enums\ApprovalStatus
const STATUS = { REJECT: 1, APPROVED: 2, PENDING: 3, PROCESSED: 4 };
const STATUS_COLORS = { [STATUS.REJECT]: 'rose', [STATUS.PENDING]: 'amber', [STATUS.PROCESSED]: 'emerald', [STATUS.APPROVED]: 'sky' };
const METHOD_ICONS = { bank: Landmark, mobile: Smartphone, cash: Banknote };

function Money({ value, currency }) {
    const n = Number(value || 0);
    return (
        <span className="tabular-nums">
            <span className="text-muted-foreground text-xs me-0.5">{currency}</span>
            {n.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })}
        </span>
    );
}

function DateRange({ value, onChange, t }) {
    // Wire format is "YYYY-MM-DD To YYYY-MM-DD" (the legacy repo parser splits on "To").
    const parts = String(value || '').split(/\s*To\s*/i);
    const from = (parts[0] || '').trim();
    const to   = (parts[1] || parts[0] || '').trim();
    const set = (f, tt) => onChange(f && tt ? `${f} To ${tt}` : '');
    return (
        <div className="grid grid-cols-2 gap-2 mt-1.5">
            <Input type="date" value={from} onChange={(e) => set(e.target.value, to || e.target.value)} aria-label={t.date_from} />
            <Input type="date" value={to}   onChange={(e) => set(from || e.target.value, e.target.value)} aria-label={t.date_to} />
        </div>
    );
}

export default function Index({
    rows = [], pagination = {}, filters = {}, lookups = {}, permissions = {}, urls = {}, t = {},
    currency = '', total_amount = 0,
}) {
    const canAct = permissions.update || permissions.delete || permissions.reject || permissions.process;

    const go = (url) => { if (url) window.location.href = url; };
    const confirmGo = (url) => { if (url && window.confirm(t.confirm_action)) window.location.href = url; };
    const onDelete = (r) => {
        if (!r.urls.destroy || !window.confirm(t.delete_confirm)) return;
        router.delete(r.urls.destroy, { preserveScroll: true });
    };

    return (
        <ListPage
            t={t} urls={urls} pagination={pagination} permissions={permissions}
            breadcrumbs={[t.section, t.title]}
            filters={filters}
            defaultFilters={{ date: '', merchant_id: '', merchant_account: '', from_account: '' }}
            filterContent={({ draft, setDraft }) => {
                const accountsForMerchant = (lookups.merchant_accounts || []).filter(
                    (a) => String(a.merchant_id) === String(draft.merchant_id || ''),
                );
                return (
                    <div className="grid gap-3 md:grid-cols-12">
                        <div className="md:col-span-4">
                            <FilterLabel>{t.date}</FilterLabel>
                            <DateRange value={draft.date} onChange={(v) => setDraft((d) => ({ ...d, date: v }))} t={t} />
                        </div>
                        <div className="md:col-span-3">
                            <FilterLabel>{t.merchant}</FilterLabel>
                            <Select
                                value={draft.merchant_id || ''}
                                onChange={(e) => setDraft((d) => ({ ...d, merchant_id: e.target.value, merchant_account: '' }))}
                                className="mt-1.5"
                            >
                                <option value="">{t.all}</option>
                                {(lookups.merchants || []).map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                            </Select>
                        </div>
                        <div className="md:col-span-5 lg:col-span-2">
                            <FilterLabel>{t.merchant_account}</FilterLabel>
                            <Select
                                value={draft.merchant_account || ''}
                                onChange={(e) => setDraft((d) => ({ ...d, merchant_account: e.target.value }))}
                                className="mt-1.5"
                                disabled={!draft.merchant_id}
                                title={!draft.merchant_id ? t.pick_merchant_first : undefined}
                            >
                                <option value="">{draft.merchant_id ? t.all : t.pick_merchant_first}</option>
                                {accountsForMerchant.map((a) => <option key={a.id} value={a.id}>{a.label}</option>)}
                            </Select>
                        </div>
                        <div className="md:col-span-12 lg:col-span-3">
                            <FilterLabel>{t.from_account}</FilterLabel>
                            <Select value={draft.from_account || ''} onChange={(e) => setDraft((d) => ({ ...d, from_account: e.target.value }))} className="mt-1.5">
                                <option value="">{t.all}</option>
                                {(lookups.from_accounts || []).map((a) => <option key={a.id} value={a.id}>{a.label}</option>)}
                            </Select>
                        </div>
                    </div>
                );
            }}
            tableContent={
                <>
                    <thead>
                        <tr className={tableHeadClass}>
                            <th className="px-4 py-3 text-start w-10">#</th>
                            <th className="px-4 py-3 text-start">{t.merchant_details}</th>
                            <th className="px-4 py-3 text-start">{t.payout_account}</th>
                            <th className="px-4 py-3 text-start">{t.transaction_id}</th>
                            <th className="px-4 py-3 text-start">{t.from_account}</th>
                            <th className="px-4 py-3 text-start">{t.description}</th>
                            <th className="px-4 py-3 text-start">{t.status}</th>
                            <th className="px-4 py-3 text-end">{t.amount}</th>
                            {canAct && <th className="px-4 py-3 text-end pe-4">{t.actions}</th>}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 && emptyRow(canAct ? 9 : 8, t.no_rows)}
                        {rows.map((r, i) => {
                            const pa = r.payout_account;
                            const MethodIcon = (pa && METHOD_ICONS[pa.method]) || Wallet;
                            const fa = r.from_account;
                            const finalised = r.status === STATUS.PROCESSED || r.status === STATUS.REJECT;
                            return (
                                <tr key={r.id} className={tableRowClass}>
                                    <td className="px-4 py-3 align-top text-muted-foreground tabular-nums">{(pagination.from ?? 1) + i}</td>

                                    {/* Merchant */}
                                    <td className="px-4 py-3 align-top">
                                        <div className="flex items-start gap-3 min-w-[200px]">
                                            {r.merchant.image
                                                ? <img src={r.merchant.image} alt="" className="h-9 w-9 rounded-md object-cover shrink-0 border border-border" />
                                                : <div className="h-9 w-9 rounded-md bg-muted shrink-0" />}
                                            <div className="min-w-0">
                                                <div className="font-medium leading-tight truncate">{r.merchant.business || r.merchant.name || '—'}</div>
                                                {r.merchant.name && r.merchant.business && (
                                                    <div className="text-xs text-muted-foreground truncate">{r.merchant.name}</div>
                                                )}
                                                {r.merchant.email && <div className="text-xs text-muted-foreground truncate">{r.merchant.email}</div>}
                                            </div>
                                        </div>
                                    </td>

                                    {/* Payout account */}
                                    <td className="px-4 py-3 align-top">
                                        {pa ? (
                                            <div className="min-w-[160px]">
                                                <div className="flex items-center gap-1.5 text-xs font-semibold">
                                                    <MethodIcon className="h-3.5 w-3.5 text-muted-foreground" /> {pa.method_label}
                                                </div>
                                                {pa.lines.map((l, k) => (
                                                    <div key={k} className={`text-xs ${k === 0 ? 'text-foreground/90 mt-0.5' : 'text-muted-foreground'} ${k === 2 ? 'font-mono' : ''}`}>{l}</div>
                                                ))}
                                            </div>
                                        ) : <span className="text-muted-foreground">—</span>}
                                    </td>

                                    {/* Transaction + date */}
                                    <td className="px-4 py-3 align-top">
                                        <div className="font-mono text-xs">{r.transaction_id || '—'}</div>
                                        <div className="text-xs text-muted-foreground mt-0.5">{r.created_at}</div>
                                        {r.reference_url && (
                                            <a href={r.reference_url} download className="mt-1 inline-flex items-center gap-1 text-xs text-primary hover:underline">
                                                <Download className="h-3 w-3" /> {t.reference}
                                            </a>
                                        )}
                                    </td>

                                    {/* From account */}
                                    <td className="px-4 py-3 align-top">
                                        {fa ? (
                                            <div className="min-w-[140px]">
                                                <div className="text-xs font-medium">{fa.title || '—'}</div>
                                                {fa.lines.map((l, k) => <div key={k} className="text-xs text-muted-foreground">{l}</div>)}
                                            </div>
                                        ) : <span className="text-muted-foreground">—</span>}
                                    </td>

                                    <td className="px-4 py-3 align-top text-xs text-muted-foreground max-w-[220px]">
                                        <div className="line-clamp-3" title={r.description || ''}>{r.description || '—'}</div>
                                    </td>

                                    <td className="px-4 py-3 align-top">
                                        <Pill color={STATUS_COLORS[r.status] || 'grey'}>{r.status_label}</Pill>
                                    </td>

                                    <td className="px-4 py-3 align-top text-end font-semibold">
                                        <Money value={r.amount} currency={currency} />
                                    </td>

                                    {canAct && (
                                        <td className="px-4 py-3 align-top text-end pe-4">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="icon" className="h-8 w-8">
                                                        <MoreVertical className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end" className="w-48">
                                                    {finalised ? (
                                                        <>
                                                            {r.status === STATUS.PROCESSED && r.urls.cancel_process && (
                                                                <DropdownMenuItem onClick={() => confirmGo(r.urls.cancel_process)}>
                                                                    <Undo2 className="h-4 w-4 me-2" /> {t.cancel_process}
                                                                </DropdownMenuItem>
                                                            )}
                                                            {r.status === STATUS.REJECT && r.urls.cancel_reject && (
                                                                <DropdownMenuItem onClick={() => confirmGo(r.urls.cancel_reject)}>
                                                                    <Undo2 className="h-4 w-4 me-2" /> {t.cancel_reject}
                                                                </DropdownMenuItem>
                                                            )}
                                                        </>
                                                    ) : (
                                                        <>
                                                            {r.status === STATUS.PENDING && r.urls.process && (
                                                                <DropdownMenuItem onClick={() => go(r.urls.process)}>
                                                                    <CheckCircle2 className="h-4 w-4 me-2 text-emerald-600" /> {t.process}
                                                                </DropdownMenuItem>
                                                            )}
                                                            {r.status === STATUS.PENDING && r.urls.reject && (
                                                                <DropdownMenuItem onClick={() => confirmGo(r.urls.reject)}>
                                                                    <Ban className="h-4 w-4 me-2 text-rose-600" /> {t.reject}
                                                                </DropdownMenuItem>
                                                            )}
                                                            {(r.urls.edit || r.urls.destroy) && r.status === STATUS.PENDING && <DropdownMenuSeparator />}
                                                            {r.urls.edit && (
                                                                <DropdownMenuItem onClick={() => go(r.urls.edit)}>
                                                                    <Edit className="h-4 w-4 me-2" /> {t.edit}
                                                                </DropdownMenuItem>
                                                            )}
                                                            {r.urls.destroy && (
                                                                <DropdownMenuItem onClick={() => onDelete(r)} className="text-destructive focus:text-destructive">
                                                                    <Trash2 className="h-4 w-4 me-2" /> {t.delete}
                                                                </DropdownMenuItem>
                                                            )}
                                                        </>
                                                    )}
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </td>
                                    )}
                                </tr>
                            );
                        })}
                    </tbody>
                    {rows.length > 0 && (
                        <tfoot>
                            <tr className="border-t border-border bg-muted/30">
                                <td colSpan={7} className="px-4 py-3 text-end text-xs font-semibold uppercase tracking-wide text-muted-foreground">{t.page_total}</td>
                                <td className="px-4 py-3 text-end font-bold"><Money value={total_amount} currency={currency} /></td>
                                {canAct && <td />}
                            </tr>
                        </tfoot>
                    )}
                </>
            }
        />
    );
}
