import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
    Boxes, Truck, RefreshCcw, XCircle, ArrowLeft, Send, Eraser,
    Building2, User as UserIcon, Calendar, StickyNote, Store,
    AlertCircle, Network, FileSpreadsheet, Printer, MessageSquare, PenLine,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';
import { Textarea } from '@/Components/ui/Textarea';
import { cn } from '@/lib/utils';

const COLOR_TO_CLASSES = {
    grey:   'bg-slate-100 text-slate-700 border-slate-200',
    yellow: 'bg-amber-100 text-amber-700 border-amber-200',
    orange: 'bg-orange-100 text-orange-700 border-orange-200',
    blue:   'bg-sky-100 text-sky-700 border-sky-200',
    purple: 'bg-violet-100 text-violet-700 border-violet-200',
    green:  'bg-emerald-100 text-emerald-700 border-emerald-200',
    red:    'bg-rose-100 text-rose-700 border-rose-200',
    teal:   'bg-teal-100 text-teal-700 border-teal-200',
};

// Tailwind JIT discovers classes by string-matching the source. Dynamic
// classes like `bg-${color}-50` only work if the resolved class literal
// appears somewhere — list each pill color combo here so the scanner sees
// them. Order matches the ActionPill palette below.
const _ActionPillClasses = [
    'border-violet-300 bg-violet-50 text-violet-700',
    'border-sky-300 bg-sky-50 text-sky-700',
    'border-rose-300 bg-rose-50 text-rose-700',
    'border-slate-300 bg-slate-50 text-slate-700',
    'border-emerald-300 bg-emerald-50 text-emerald-700',
    'border-amber-300 bg-amber-50 text-amber-700',
    'border-indigo-300 bg-indigo-50 text-indigo-700',
].join(' ');

// Compact horizontal pill — matches the quick-actions row on
// /operations-dashboard (h-9 px-3 rounded-lg border, 3.5x3.5 icon).
// Active state swaps to a tinted variant per action colour so the
// selection is still obvious inside a flex-wrap row.
function ActionPill({ icon: Icon, label, value, current, onClick, color }) {
    const active = current === value;
    return (
        <button
            type="button"
            onClick={() => onClick(value)}
            className={cn(
                'inline-flex items-center gap-1.5 h-9 px-3 rounded-lg border text-sm transition-colors',
                active
                    ? `border-${color}-300 bg-${color}-50 text-${color}-700`
                    : 'border-border bg-background hover:bg-muted',
            )}
        >
            <Icon className="h-3.5 w-3.5" />
            {label}
        </button>
    );
}

function Field({ icon: Icon, label, required, error, children, hint }) {
    return (
        <div className="space-y-1.5">
            <Label className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {Icon && <Icon className="h-3 w-3" />}
                {label}
                {required && <span className="text-destructive">*</span>}
            </Label>
            {children}
            {hint && <p className="text-[11px] text-muted-foreground">{hint}</p>}
            {error && (
                <p className="text-xs text-destructive flex items-center gap-1">
                    <AlertCircle className="h-3 w-3" /> {error}
                </p>
            )}
        </div>
    );
}

// Parse the textarea contents into a deduped list of tracking IDs.
function parseShipmentIds(raw) {
    return Array.from(new Set(
        String(raw || '')
            .split(/[\s,;]+/)
            .map((s) => s.trim())
            .filter(Boolean),
    ));
}

export default function BulkAction({
    statuses = [],
    merchants = [],
    deliverymen = [],
    hubs = [],
    companies = [],
    logestechs_connections = [],
    logestechs_manage_url = '#',
    urls = {},
    t = {},
}) {
    // Pre-pick the default connection if any. Falls back to the first active.
    const defaultLogestechsConnection = React.useMemo(() => {
        const def = logestechs_connections.find((c) => c.is_default);
        return def ? def.id : (logestechs_connections[0]?.id || '');
    }, [logestechs_connections]);

    const form = useForm({
        shipment_ids: '',
        action_type: '',
        status: '',
        company: '',
        connection_id: defaultLogestechsConnection,
        driver_id: '',
        schedule_at: '',
        hub_id: '',
        merchant_id: '',
        note: '',
        // Bulk-only inputs for the new actions.
        bulk_note: '',
        sms_message: '',
    });

    const ids = React.useMemo(() => parseShipmentIds(form.data.shipment_ids), [form.data.shipment_ids]);

    // Reset dependent fields when action type or status changes.
    const setActionType = (v) => {
        form.setData('action_type', v);
        form.setData('status', '');
        form.setData('company', '');
        form.setData('driver_id', '');
        form.setData('hub_id', '');
        form.setData('merchant_id', '');
        form.setData('schedule_at', '');
        form.setData('bulk_note', '');
        form.setData('sms_message', '');
    };

    const selectedStatus = React.useMemo(
        () => statuses.find((s) => String(s.id) === String(form.data.status)) || null,
        [statuses, form.data.status],
    );

    // Which sub-fields the current status requires.
    const needsDriver  = selectedStatus?.requires?.includes('delivery_man_id');
    const needsDate    = selectedStatus?.requires?.includes('date');
    const needsHub     = selectedStatus?.requires?.includes('hub_id');
    const needsMerchant= selectedStatus?.requires?.includes('merchant_id');

    const showStatusGroup    = form.data.action_type === 'change_status' && form.data.status;
    const showStatusSelect   = form.data.action_type === 'change_status';
    const showCompanySelect  = form.data.action_type === 'assign_3pl';
    const showLogestechs     = form.data.action_type === 'assign_3pl' && form.data.company === 'logestechs';
    const showCancelHint     = form.data.action_type === 'cancel';
    const showAddNote        = form.data.action_type === 'add_note';
    const showSendSms        = form.data.action_type === 'send_sms';
    const showExport         = form.data.action_type === 'export_excel';
    const showPrint          = form.data.action_type === 'print_awbs';

    const smsCharCount = (form.data.sms_message || '').length;
    const smsSegments  = Math.max(1, Math.ceil(smsCharCount / 160));

    const canSubmit = (
        ids.length > 0 &&
        form.data.action_type &&
        (form.data.action_type !== 'change_status' || !!form.data.status) &&
        (form.data.action_type !== 'assign_3pl'    || (!!form.data.company && (form.data.company !== 'logestechs' || !!form.data.connection_id))) &&
        (form.data.action_type !== 'add_note'      || !!form.data.bulk_note.trim()) &&
        (form.data.action_type !== 'send_sms'      || !!form.data.sms_message.trim()) &&
        (!needsDriver   || !!form.data.driver_id) &&
        (!needsDate     || !!form.data.schedule_at) &&
        (!needsHub      || !!form.data.hub_id) &&
        (!needsMerchant || !!form.data.merchant_id)
    );

    const submit = (e) => {
        e.preventDefault();
        if (!canSubmit) return;

        // Excel returns binary, Print returns an external redirect → both need
        // a real browser form POST (Inertia expects JSON/HTML back). Build a
        // hidden form, submit it, clean up.
        if (form.data.action_type === 'export_excel' || form.data.action_type === 'print_awbs') {
            const f = document.createElement('form');
            f.method = 'POST';
            f.action = urls.apply;
            if (form.data.action_type === 'print_awbs') f.target = '_blank';
            const append = (name, val) => {
                const i = document.createElement('input');
                i.type = 'hidden';
                i.name = name;
                i.value = val ?? '';
                f.appendChild(i);
            };
            append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
            append('shipment_ids', form.data.shipment_ids);
            append('action_type',  form.data.action_type);
            document.body.appendChild(f);
            f.submit();
            document.body.removeChild(f);
            return;
        }

        form.post(urls.apply, { preserveScroll: true });
    };

    const reset = () => form.reset();

    return (
        <AdminLayout>
            <Head title={t.title} />

            <form onSubmit={submit} className="space-y-5">
                {/* 1. Action type — compact pill row at the top so the operator
                    picks the intent first, then the required inputs surface
                    beneath. Style matches the quick-actions row on
                    /operations-dashboard for visual consistency. */}
                <div>
                    <Label className="mb-2 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                        {t.action_type} <span className="text-destructive">*</span>
                    </Label>
                    <div className="flex flex-wrap gap-2">
                        <ActionPill
                            icon={Network} label={t.assign_3pl}
                            value="assign_3pl" current={form.data.action_type}
                            onClick={setActionType} color="violet"
                        />
                        <ActionPill
                            icon={RefreshCcw} label={t.change_status}
                            value="change_status" current={form.data.action_type}
                            onClick={setActionType} color="sky"
                        />
                        <ActionPill
                            icon={XCircle} label={t.cancel_shipments}
                            value="cancel" current={form.data.action_type}
                            onClick={setActionType} color="rose"
                        />
                        <ActionPill
                            icon={Printer} label={t.print_awbs}
                            value="print_awbs" current={form.data.action_type}
                            onClick={setActionType} color="slate"
                        />
                        <ActionPill
                            icon={FileSpreadsheet} label={t.export_excel}
                            value="export_excel" current={form.data.action_type}
                            onClick={setActionType} color="emerald"
                        />
                        <ActionPill
                            icon={PenLine} label={t.add_note}
                            value="add_note" current={form.data.action_type}
                            onClick={setActionType} color="amber"
                        />
                        <ActionPill
                            icon={MessageSquare} label={t.send_sms}
                            value="send_sms" current={form.data.action_type}
                            onClick={setActionType} color="indigo"
                        />
                    </div>
                    {form.errors.action_type && (
                        <p className="mt-2 text-xs text-destructive flex items-center gap-1">
                            <AlertCircle className="h-3 w-3" /> {form.errors.action_type}
                        </p>
                    )}
                    {showCancelHint && (
                        <p className="mt-3 rounded-md border border-amber-200 bg-amber-50 p-2.5 text-xs text-amber-800 flex items-start gap-2">
                            <AlertCircle className="h-3.5 w-3.5 mt-0.5 shrink-0" /> {t.cancel_hint}
                        </p>
                    )}
                </div>

                {/* 2. Shipment IDs */}
                <Card>
                    <CardContent className="pt-6">
                        <Field icon={Boxes} label={t.shipment_ids} required error={form.errors.shipment_ids} hint={t.shipment_ids_hint}>
                            <Textarea
                                rows={5}
                                value={form.data.shipment_ids}
                                onChange={(e) => form.setData('shipment_ids', e.target.value)}
                                placeholder="RL12345678…"
                                className="font-mono text-xs"
                            />
                        </Field>
                        <div className="mt-3 flex items-center gap-3 text-xs text-muted-foreground">
                            <span className="rounded-full bg-primary/10 text-primary px-2 py-0.5 font-medium">
                                {ids.length} shipment{ids.length === 1 ? '' : 's'}
                            </span>
                            <button type="button" onClick={() => form.setData('shipment_ids', '')} className="hover:text-foreground inline-flex items-center gap-1">
                                <Eraser className="h-3 w-3" /> {t.clear}
                            </button>
                        </div>
                    </CardContent>
                </Card>

                {/* 3. Action-specific fields */}
                {(showStatusSelect || showCompanySelect) && (
                    <Card>
                        <CardContent className="pt-6">
                            <div className="grid gap-4 md:grid-cols-2">
                                {showStatusSelect && (
                                    <Field icon={RefreshCcw} label={t.select_status} required error={form.errors.status}>
                                        <Select
                                            value={form.data.status}
                                            onChange={(e) => form.setData('status', e.target.value)}
                                        >
                                            <option value="">—</option>
                                            {statuses.map((s) => (
                                                <option key={s.id} value={s.id}>{s.label}</option>
                                            ))}
                                        </Select>
                                        {selectedStatus && (
                                            <span className={cn(
                                                'mt-2 inline-flex rounded-full border px-2 py-0.5 text-[11px] font-medium',
                                                COLOR_TO_CLASSES.blue,
                                            )}>
                                                {selectedStatus.label}
                                            </span>
                                        )}
                                    </Field>
                                )}

                                {showCompanySelect && (
                                    <Field icon={Network} label={t.select_3pl_company} required error={form.errors.company}>
                                        <Select
                                            value={form.data.company}
                                            onChange={(e) => form.setData('company', e.target.value)}
                                        >
                                            <option value="">{t.select_3pl_company}</option>
                                            {companies.map((c) => (
                                                <option key={c.value} value={c.value}>{c.label}</option>
                                            ))}
                                        </Select>
                                    </Field>
                                )}
                            </div>

                            {showLogestechs && (
                                <div className="mt-4 rounded-md border border-violet-200 bg-violet-50/40 p-4 space-y-2">
                                    {logestechs_connections.length > 0 ? (
                                        <Field
                                            icon={Network}
                                            label={t.logestechs_connection_label}
                                            required
                                            error={form.errors.connection_id}
                                            hint={t.logestechs_connection_hint}
                                        >
                                            <Select
                                                value={form.data.connection_id}
                                                onChange={(e) => form.setData('connection_id', e.target.value)}
                                            >
                                                {logestechs_connections.map((c) => (
                                                    <option key={c.id} value={c.id}>
                                                        {c.connection_name}
                                                        {c.email ? ` — ${c.email}` : ''}
                                                        {c.is_default ? ` ${t.logestechs_default_marker}` : ''}
                                                    </option>
                                                ))}
                                            </Select>
                                        </Field>
                                    ) : (
                                        <div className="text-sm text-rose-700">
                                            {t.logestechs_no_connections}{' '}
                                            <a href={logestechs_manage_url} className="font-medium underline">
                                                {t.logestechs_manage_link}
                                            </a>
                                        </div>
                                    )}
                                </div>
                            )}

                            {showAddNote && (
                                <div className="mt-4 rounded-md border border-amber-200 bg-amber-50/40 p-4">
                                    <Field
                                        icon={PenLine}
                                        label={t.add_note_label}
                                        required
                                        error={form.errors.bulk_note}
                                        hint={t.add_note_hint}
                                    >
                                        <Textarea
                                            value={form.data.bulk_note}
                                            onChange={(e) => form.setData('bulk_note', e.target.value)}
                                            rows={4}
                                            maxLength={2000}
                                            placeholder="Pickup arranged for tomorrow morning…"
                                        />
                                    </Field>
                                </div>
                            )}

                            {showSendSms && (
                                <div className="mt-4 rounded-md border border-indigo-200 bg-indigo-50/40 p-4 space-y-2">
                                    <Field
                                        icon={MessageSquare}
                                        label={t.sms_message_label}
                                        required
                                        error={form.errors.sms_message}
                                        hint={t.sms_message_hint}
                                    >
                                        <Textarea
                                            value={form.data.sms_message}
                                            onChange={(e) => form.setData('sms_message', e.target.value)}
                                            rows={3}
                                            maxLength={480}
                                            placeholder="Hi {customer_name}, your package {tracking_id} is out for delivery."
                                        />
                                    </Field>
                                    <p className="text-[11px] text-muted-foreground tabular-nums">
                                        {smsCharCount}/480 chars · {smsSegments} SMS segment{smsSegments > 1 ? 's' : ''}
                                    </p>
                                </div>
                            )}

                            {showExport && (
                                <p className="mt-3 rounded-md border border-emerald-200 bg-emerald-50 p-2.5 text-xs text-emerald-800 flex items-start gap-2">
                                    <FileSpreadsheet className="h-3.5 w-3.5 mt-0.5 shrink-0" /> {t.export_hint}
                                </p>
                            )}

                            {showPrint && (
                                <p className="mt-3 rounded-md border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-800 flex items-start gap-2">
                                    <Printer className="h-3.5 w-3.5 mt-0.5 shrink-0" /> {t.print_awbs_hint}
                                </p>
                            )}

                            {showStatusGroup && (needsDriver || needsDate || needsHub || needsMerchant) && (
                                <div className="mt-4 grid gap-4 md:grid-cols-2">
                                    {needsDriver && (
                                        <Field icon={UserIcon} label={t.select_driver} required error={form.errors.driver_id}>
                                            <Select
                                                value={form.data.driver_id}
                                                onChange={(e) => form.setData('driver_id', e.target.value)}
                                            >
                                                <option value="">—</option>
                                                {deliverymen.map((d) => (
                                                    <option key={d.id} value={d.id}>{d.name}</option>
                                                ))}
                                            </Select>
                                        </Field>
                                    )}
                                    {needsDate && (
                                        <Field icon={Calendar} label={t.schedule_at} required error={form.errors.schedule_at}>
                                            <Input
                                                type="date"
                                                value={form.data.schedule_at}
                                                onChange={(e) => form.setData('schedule_at', e.target.value)}
                                            />
                                        </Field>
                                    )}
                                    {needsHub && (
                                        <Field icon={Building2} label={t.select_hub} required error={form.errors.hub_id}>
                                            <Select
                                                value={form.data.hub_id}
                                                onChange={(e) => form.setData('hub_id', e.target.value)}
                                            >
                                                <option value="">—</option>
                                                {hubs.map((h) => (
                                                    <option key={h.id} value={h.id}>{h.name}</option>
                                                ))}
                                            </Select>
                                        </Field>
                                    )}
                                    {needsMerchant && (
                                        <Field icon={Store} label={t.select_merchant} required error={form.errors.merchant_id}>
                                            <Select
                                                value={form.data.merchant_id}
                                                onChange={(e) => form.setData('merchant_id', e.target.value)}
                                            >
                                                <option value="">—</option>
                                                {merchants.map((m) => (
                                                    <option key={m.id} value={m.id}>{m.name}</option>
                                                ))}
                                            </Select>
                                        </Field>
                                    )}
                                </div>
                            )}

                            {showStatusGroup && (
                                <div className="mt-4">
                                    <Field icon={StickyNote} label={`${t.note} (${t.optional})`} error={form.errors.note}>
                                        <Textarea
                                            rows={2}
                                            value={form.data.note}
                                            onChange={(e) => form.setData('note', e.target.value)}
                                            placeholder={t.note_placeholder}
                                        />
                                    </Field>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}

                {/* 4. Apply */}
                <div className="flex items-center justify-between gap-3 rounded-xl border border-border bg-card p-4 shadow-sm">
                    <a href={urls.index} className="inline-flex h-10 items-center rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent">
                        <ArrowLeft className="h-4 w-4 me-1" /> {t.back}
                    </a>
                    <div className="flex items-center gap-3">
                        {!canSubmit && (
                            <small className="text-xs text-muted-foreground hidden sm:inline">{t.apply_hint}</small>
                        )}
                        <Button type="button" variant="outline" onClick={reset}>
                            <Eraser className="h-4 w-4 me-1" /> {t.clear}
                        </Button>
                        <Button type="submit" disabled={!canSubmit || form.processing}>
                            <Send className="h-4 w-4 me-1" /> {form.processing ? '…' : t.apply}
                        </Button>
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}
