import * as React from 'react';
import { createPortal } from 'react-dom';
import { X, Loader2 } from 'lucide-react';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import { Label } from '@/Components/ui/Label';

// Parcel status IDs (mirrors app/Enums/ParcelStatus.php)
const S = {
    PICKUP_ASSIGN: 2,
    PICKUP_RE_SCHEDULE: 3,
    RECEIVED_WAREHOUSE: 5,
    TRANSFER_TO_HUB: 6,
    DELIVERY_MAN_ASSIGN: 7,
    DELIVERY_RE_SCHEDULE: 8,
    DELIVERED: 9,
    PICKUP_ASSIGN_CANCEL: 14,
    RECEIVED_WAREHOUSE_CANCEL: 16,
    DELIVERY_MAN_ASSIGN_CANCEL: 17,
    DELIVERY_RE_SCHEDULE_CANCEL: 18,
    RECEIVED_BY_HUB: 19,
    TRANSFER_TO_HUB_CANCEL: 20,
    RECEIVED_BY_HUB_CANCEL: 21,
    PICKUP_RE_SCHEDULE_CANCEL: 23,
    RETURN_TO_COURIER: 24,
    RETURN_TO_COURIER_CANCEL: 25,
    PARTIAL_DELIVERED: 32,
};

// target status -> { urlKey (matches urls.status[key]), fields[] }
const PLAN = {
    [S.PICKUP_ASSIGN]:                { urlKey: 'pickup_assign',               fields: ['deliveryman'] },
    [S.PICKUP_ASSIGN_CANCEL]:         { urlKey: 'pickup_assign_cancel',        fields: [] },
    [S.PICKUP_RE_SCHEDULE]:           { urlKey: 'pickup_re_schedule',          fields: ['deliveryman', 'date'] },
    [S.PICKUP_RE_SCHEDULE_CANCEL]:    { urlKey: 'pickup_re_schedule_cancel',   fields: [] },
    [S.RECEIVED_WAREHOUSE]:           { urlKey: 'received_warehouse',          fields: ['hub'] },
    [S.RECEIVED_WAREHOUSE_CANCEL]:    { urlKey: 'received_warehouse_cancel',   fields: [] },
    [S.TRANSFER_TO_HUB]:              { urlKey: 'transfer_to_hub',             fields: ['hub'] },
    [S.TRANSFER_TO_HUB_CANCEL]:       { urlKey: 'transfer_to_hub_cancel',      fields: [] },
    [S.RECEIVED_BY_HUB]:              { urlKey: 'received_by_hub',             fields: [] },
    [S.RECEIVED_BY_HUB_CANCEL]:       { urlKey: 'received_by_hub_cancel',      fields: [] },
    [S.DELIVERY_MAN_ASSIGN]:          { urlKey: 'delivery_man_assign',         fields: ['deliveryman'] },
    [S.DELIVERY_MAN_ASSIGN_CANCEL]:   { urlKey: 'delivery_man_assign_cancel',  fields: [] },
    [S.DELIVERY_RE_SCHEDULE]:         { urlKey: 'delivery_re_schedule',        fields: ['deliveryman', 'date'] },
    [S.DELIVERY_RE_SCHEDULE_CANCEL]:  { urlKey: 'delivery_re_schedule_cancel', fields: [] },
    [S.DELIVERED]:                    { urlKey: 'delivered',                   fields: [] },
    [S.PARTIAL_DELIVERED]:            { urlKey: 'partial_delivered',           fields: ['cash_collection'] },
    [S.RETURN_TO_COURIER]:            { urlKey: 'return_to_courier',           fields: [] },
    [S.RETURN_TO_COURIER_CANCEL]:     { urlKey: 'return_to_courier_cancel',    fields: [] },
};

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

export default function ChangeStatusModal({
    open,
    parcel,
    transition,
    statusUrls = {},
    lookups = {},
    t = {},
    onClose,
    onSuccess,
}) {
    const [form, setForm] = React.useState({ delivery_man_id: '', hub_id: '', date: '', cash_collection: '' });
    const [submitting, setSubmitting] = React.useState(false);
    const [error, setError] = React.useState(null);

    const plan = transition ? PLAN[transition.value] : null;
    const url  = plan ? statusUrls[plan.urlKey] : null;

    React.useEffect(() => {
        if (!open) return;
        setForm({
            delivery_man_id: '',
            hub_id: '',
            date: new Date().toISOString().slice(0, 10),
            cash_collection: parcel?.cash_collection ?? '',
        });
        setError(null);
    }, [open, parcel?.id, transition?.value]);

    React.useEffect(() => {
        if (!open) return;
        const onKey = (e) => { if (e.key === 'Escape') onClose?.(); };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [open, onClose]);

    if (!open || !plan || !url) return null;

    const wants = (f) => plan.fields.includes(f);

    const submit = async (e) => {
        e?.preventDefault?.();
        setError(null);

        // Client-side required-field guard
        if (wants('deliveryman') && !form.delivery_man_id) return setError(t.required || 'Required');
        if (wants('hub')         && !form.hub_id)         return setError(t.required || 'Required');
        if (wants('date')        && !form.date)           return setError(t.required || 'Required');
        if (wants('cash_collection') && form.cash_collection === '') return setError(t.required || 'Required');

        const body = new FormData();
        body.append('_token', csrf());
        body.append('parcel_id', parcel.id);
        if (wants('deliveryman'))     body.append('delivery_man_id', form.delivery_man_id);
        if (wants('hub'))             body.append('hub_id', form.hub_id);
        if (wants('date'))            body.append('date', form.date);
        if (wants('cash_collection')) body.append('cash_collection', form.cash_collection);

        setSubmitting(true);
        try {
            const res = await fetch(url, {
                method: 'POST',
                body,
                headers: {
                    'Accept': 'application/json, text/html;q=0.9, */*;q=0.5',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf(),
                },
                credentials: 'same-origin',
                redirect: 'follow',
            });
            if (!res.ok && res.status !== 302) {
                throw new Error('HTTP ' + res.status);
            }
            onSuccess?.();
        } catch (err) {
            setError(err?.message || 'Failed to update status');
        } finally {
            setSubmitting(false);
        }
    };

    return createPortal(
        <div className="fixed inset-0 z-[100] flex items-center justify-center">
            {/* backdrop */}
            <div className="absolute inset-0 bg-black/40" onClick={() => !submitting && onClose?.()} />

            {/* dialog */}
            <div className="relative bg-background rounded-lg shadow-xl w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto">
                <div className="flex items-start justify-between border-b border-border px-5 py-3">
                    <div>
                        <div className="text-[10px] uppercase tracking-wider text-muted-foreground font-semibold">
                            {t.status_change_to || 'Change status to'}
                        </div>
                        <div className="text-base font-semibold mt-0.5">{transition.label}</div>
                        <div className="text-xs text-muted-foreground mt-0.5">
                            #{parcel.tracking_id || parcel.id}
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={() => !submitting && onClose?.()}
                        className="p-1 -m-1 rounded-md hover:bg-accent text-muted-foreground"
                        aria-label="Close"
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>

                <form onSubmit={submit} className="px-5 py-4 space-y-4">
                    {plan.fields.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            {t.confirm_change || 'Confirm this status change.'}
                        </p>
                    )}

                    {wants('deliveryman') && (
                        <div className="space-y-1.5">
                            <Label htmlFor="cs-deliveryman">{t.select_deliveryman || 'Select courier'}</Label>
                            <Select
                                id="cs-deliveryman"
                                value={form.delivery_man_id}
                                onChange={(e) => setForm((f) => ({ ...f, delivery_man_id: e.target.value }))}
                            >
                                <option value="">— {t.select_deliveryman || 'Select courier'} —</option>
                                {(lookups.deliverymen || []).map((d) => (
                                    <option key={d.id} value={d.id}>{d.name}</option>
                                ))}
                            </Select>
                        </div>
                    )}

                    {wants('hub') && (
                        <div className="space-y-1.5">
                            <Label htmlFor="cs-hub">{t.select_hub || 'Select hub'}</Label>
                            <Select
                                id="cs-hub"
                                value={form.hub_id}
                                onChange={(e) => setForm((f) => ({ ...f, hub_id: e.target.value }))}
                            >
                                <option value="">— {t.select_hub || 'Select hub'} —</option>
                                {(lookups.hubs || []).map((h) => (
                                    <option key={h.id} value={h.id}>{h.name}</option>
                                ))}
                            </Select>
                        </div>
                    )}

                    {wants('date') && (
                        <div className="space-y-1.5">
                            <Label htmlFor="cs-date">{t.date || 'Date'}</Label>
                            <Input
                                id="cs-date"
                                type="date"
                                value={form.date}
                                onChange={(e) => setForm((f) => ({ ...f, date: e.target.value }))}
                            />
                        </div>
                    )}

                    {wants('cash_collection') && (
                        <div className="space-y-1.5">
                            <Label htmlFor="cs-cash">{t.cash_collection || 'Cash collection'}</Label>
                            <Input
                                id="cs-cash"
                                type="number"
                                step="0.01"
                                min="0"
                                value={form.cash_collection}
                                onChange={(e) => setForm((f) => ({ ...f, cash_collection: e.target.value }))}
                            />
                        </div>
                    )}

                    {error && (
                        <div className="text-sm text-rose-600 bg-rose-50 border border-rose-200 rounded-md px-3 py-2">
                            {error}
                        </div>
                    )}

                    <div className="flex items-center justify-end gap-2 pt-2 border-t border-border">
                        <Button type="button" variant="outline" onClick={onClose} disabled={submitting}>
                            {t.cancel || 'Cancel'}
                        </Button>
                        <Button type="submit" disabled={submitting}>
                            {submitting ? (
                                <><Loader2 className="h-4 w-4 me-1 animate-spin" /> {t.updating || 'Updating…'}</>
                            ) : (
                                t.confirm || 'Confirm'
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </div>,
        document.body,
    );
}
