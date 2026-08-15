import * as React from 'react';
import { createPortal } from 'react-dom';
import { X, Loader2, PackagePlus, Check } from 'lucide-react';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import { Label } from '@/Components/ui/Label';
import { Textarea } from '@/Components/ui/Textarea';

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

const EMPTY = {
    merchant_id: '',
    pickup_phone: '',
    pickup_address: '',
    customer_name: '',
    customer_phone: '',
    customer_address: '',
    city_id: '',
    cash_collection: '',
    note: '',
};

/**
 * Navbar quick-create shipment modal.
 *
 * Collects only pickup / receiver / COD / notes. Delivery category, service
 * type and all pricing are resolved server-side by ParcelController@quickStore
 * — the browser never computes charges here (unlike the full ParcelForm), so
 * there is nothing for a caller to tamper with and no chargeDetails to keep in
 * sync. Everything the modal omits stays editable on the normal edit screen.
 *
 * Lookups are fetched lazily on first open: the Topbar renders on every admin
 * page and has no page props to read merchants/cities from.
 */
export default function QuickCreateShipmentModal({
    open,
    onClose,
    lookupsUrl,
    storeUrl,
    parcelIndexUrl,
    t = (k) => k,
}) {
    const [form, setForm] = React.useState(EMPTY);
    const [lookups, setLookups] = React.useState(null);
    const [loading, setLoading] = React.useState(false);
    const [submitting, setSubmitting] = React.useState(false);
    const [error, setError] = React.useState(null);
    const [fieldErrors, setFieldErrors] = React.useState({});
    const [done, setDone] = React.useState(null);

    const set = (k) => (e) => setForm((f) => ({ ...f, [k]: e.target.value }));

    // Reset each time the modal is opened so a previous success/error never
    // bleeds into the next shipment.
    React.useEffect(() => {
        if (!open) return;
        setForm(EMPTY);
        setError(null);
        setFieldErrors({});
        setDone(null);
    }, [open]);

    React.useEffect(() => {
        if (!open || lookups || loading) return;
        setLoading(true);
        fetch(lookupsUrl, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then((r) => (r.ok ? r.json() : Promise.reject(new Error('HTTP ' + r.status))))
            .then(setLookups)
            .catch(() => setError(t('quick_ship_lookup_failed')))
            .finally(() => setLoading(false));
    }, [open, lookups, loading, lookupsUrl, t]);

    React.useEffect(() => {
        if (!open) return;
        const onKey = (e) => { if (e.key === 'Escape' && !submitting) onClose?.(); };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [open, onClose, submitting]);

    // Picking a merchant prefills its pickup details, but never overwrites
    // something already typed by hand.
    React.useEffect(() => {
        if (!form.merchant_id || !lookups) return;
        const m = (lookups.merchants || []).find((x) => String(x.id) === String(form.merchant_id));
        if (!m) return;
        setForm((f) => ({
            ...f,
            pickup_phone:   f.pickup_phone   || m.pickup_phone   || '',
            pickup_address: f.pickup_address || m.pickup_address || '',
        }));
    }, [form.merchant_id, lookups]);

    if (!open) return null;

    const submit = async (e) => {
        e?.preventDefault?.();
        setError(null);
        setFieldErrors({});
        setSubmitting(true);
        try {
            const res = await fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf(),
                },
                credentials: 'same-origin',
                body: JSON.stringify({ ...form, _token: csrf() }),
            });
            const payload = await res.json().catch(() => ({}));
            if (!res.ok) {
                // 422 from validate() carries per-field messages; anything else
                // is a single message (quota, subscription, save failure).
                if (payload.errors) setFieldErrors(payload.errors);
                throw new Error(payload.message || 'HTTP ' + res.status);
            }
            setDone(payload);
        } catch (err) {
            setError(err?.message || t('quick_ship_failed'));
        } finally {
            setSubmitting(false);
        }
    };

    const fieldError = (k) => fieldErrors[k]?.[0];

    const field = (k, label, extra = {}) => (
        <div className="space-y-1.5">
            <Label htmlFor={`qs-${k}`}>{label}</Label>
            <Input id={`qs-${k}`} value={form[k]} onChange={set(k)} {...extra} />
            {fieldError(k) && <p className="text-xs text-rose-600">{fieldError(k)}</p>}
        </div>
    );

    const sectionTitle = (text) => (
        <div className="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
            {text}
        </div>
    );

    return createPortal(
        <div className="fixed inset-0 z-[100] flex items-center justify-center">
            <div
                className="absolute inset-0 bg-black/40"
                onClick={() => !submitting && onClose?.()}
            />

            <div className="relative mx-4 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-background shadow-xl">
                <div className="flex items-start justify-between border-b border-border px-5 py-3">
                    <div className="flex items-center gap-2">
                        <PackagePlus className="h-5 w-5 text-primary" />
                        <div>
                            <div className="text-base font-semibold">{t('quick_ship_title')}</div>
                            <div className="mt-0.5 text-xs text-muted-foreground">
                                {t('quick_ship_subtitle')}
                            </div>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={() => !submitting && onClose?.()}
                        className="-m-1 rounded-md p-1 text-muted-foreground hover:bg-accent"
                        aria-label={t('close')}
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>

                {done ? (
                    <div className="space-y-4 px-5 py-6 text-center">
                        <div className="mx-auto grid h-12 w-12 place-items-center rounded-full bg-emerald-100 text-emerald-600">
                            <Check className="h-6 w-6" />
                        </div>
                        <div>
                            <div className="font-semibold">{t('quick_ship_created')}</div>
                            {done.tracking_id && (
                                <div className="mt-1 font-mono text-sm text-muted-foreground">
                                    #{done.tracking_id}
                                </div>
                            )}
                        </div>
                        <div className="flex items-center justify-center gap-2 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => { setForm(EMPTY); setDone(null); }}
                            >
                                {t('quick_ship_another')}
                            </Button>
                            <Button type="button" onClick={() => { window.location.href = parcelIndexUrl; }}>
                                {t('quick_ship_view_all')}
                            </Button>
                        </div>
                    </div>
                ) : loading && !lookups ? (
                    <div className="flex items-center justify-center gap-2 px-5 py-10 text-sm text-muted-foreground">
                        <Loader2 className="h-4 w-4 animate-spin" /> {t('loading')}
                    </div>
                ) : (
                    <form onSubmit={submit} className="space-y-5 px-5 py-4">
                        {/* ---------- Pickup ---------- */}
                        <div className="space-y-3">
                            {sectionTitle(t('quick_ship_pickup'))}

                            <div className="space-y-1.5">
                                <Label htmlFor="qs-merchant">{t('quick_ship_merchant')}</Label>
                                <Select id="qs-merchant" value={form.merchant_id} onChange={set('merchant_id')}>
                                    <option value="">— {t('quick_ship_merchant')} —</option>
                                    {(lookups?.merchants || []).map((m) => (
                                        <option key={m.id} value={m.id}>{m.name}</option>
                                    ))}
                                </Select>
                                {fieldError('merchant_id') && (
                                    <p className="text-xs text-rose-600">{fieldError('merchant_id')}</p>
                                )}
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2">
                                {field('pickup_phone', t('quick_ship_pickup_phone'), { inputMode: 'tel' })}
                                {field('pickup_address', t('quick_ship_pickup_address'))}
                            </div>
                        </div>

                        {/* ---------- Receiver ---------- */}
                        <div className="space-y-3 border-t border-border pt-4">
                            {sectionTitle(t('quick_ship_receiver'))}

                            <div className="grid gap-3 sm:grid-cols-2">
                                {field('customer_name', t('quick_ship_receiver_name'))}
                                {field('customer_phone', t('quick_ship_receiver_phone'), { inputMode: 'tel' })}
                            </div>

                            {field('customer_address', t('quick_ship_receiver_address'))}

                            <div className="space-y-1.5">
                                <Label htmlFor="qs-city">{t('quick_ship_city')}</Label>
                                <Select id="qs-city" value={form.city_id} onChange={set('city_id')}>
                                    <option value="">— {t('quick_ship_city')} —</option>
                                    {(lookups?.cities || []).map((c) => (
                                        <option key={c.id} value={c.id}>{c.name}</option>
                                    ))}
                                </Select>
                                {fieldError('city_id') && (
                                    <p className="text-xs text-rose-600">{fieldError('city_id')}</p>
                                )}
                            </div>
                        </div>

                        {/* ---------- COD + notes ---------- */}
                        <div className="space-y-3 border-t border-border pt-4">
                            {sectionTitle(t('quick_ship_payment'))}

                            <div className="space-y-1.5">
                                <Label htmlFor="qs-cash_collection">
                                    {t('quick_ship_cod')}
                                    {lookups?.currency ? ` (${lookups.currency})` : ''}
                                </Label>
                                <Input
                                    id="qs-cash_collection"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0"
                                    value={form.cash_collection}
                                    onChange={set('cash_collection')}
                                />
                                <p className="text-xs text-muted-foreground">{t('quick_ship_cod_hint')}</p>
                                {fieldError('cash_collection') && (
                                    <p className="text-xs text-rose-600">{fieldError('cash_collection')}</p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="qs-note">{t('quick_ship_notes')}</Label>
                                <Textarea id="qs-note" rows={3} value={form.note} onChange={set('note')} />
                                {fieldError('note') && (
                                    <p className="text-xs text-rose-600">{fieldError('note')}</p>
                                )}
                            </div>
                        </div>

                        {error && (
                            <div className="rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-600">
                                {error}
                            </div>
                        )}

                        <div className="flex items-center justify-end gap-2 border-t border-border pt-3">
                            <Button type="button" variant="outline" onClick={onClose} disabled={submitting}>
                                {t('cancel')}
                            </Button>
                            <Button type="submit" disabled={submitting || !lookups}>
                                {submitting ? (
                                    <><Loader2 className="h-4 w-4 me-1 animate-spin" /> {t('quick_ship_creating')}</>
                                ) : (
                                    t('quick_ship_create')
                                )}
                            </Button>
                        </div>
                    </form>
                )}
            </div>
        </div>,
        document.body,
    );
}
