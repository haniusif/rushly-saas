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
    shop_id: '',
    pickup_phone: '',
    pickup_address: '',
    customer_name: '',
    customer_phone: '',
    customer_address: '',
    city_id: '',
    area_id: '',
    cash_collection: '',
    note: '',
};

/**
 * A select you can type into.
 *
 * 28 cities would survive a plain dropdown; 518 areas would not, and the two
 * should behave the same way or the form teaches one habit and then breaks it.
 * Deliberately dependency-free: filter, arrow keys, Enter, Escape.
 */
function SearchSelect({ id, value, onChange, options, placeholder, emptyText, disabled }) {
    const [open, setOpen]   = React.useState(false);
    const [query, setQuery] = React.useState('');
    const [hi, setHi]       = React.useState(0);
    const boxRef = React.useRef(null);

    const selected = options.find((o) => String(o.id) === String(value));

    const shown = React.useMemo(() => {
        const q = query.trim().toLowerCase();
        if (!q) return options.slice(0, 50);
        return options.filter((o) => String(o.name).toLowerCase().includes(q)).slice(0, 50);
    }, [query, options]);

    React.useEffect(() => {
        const onDoc = (e) => { if (boxRef.current && !boxRef.current.contains(e.target)) setOpen(false); };
        document.addEventListener('mousedown', onDoc);
        return () => document.removeEventListener('mousedown', onDoc);
    }, []);

    const pick = (o) => { onChange(String(o.id)); setOpen(false); setQuery(''); };

    const onKey = (e) => {
        if (e.key === 'ArrowDown') { e.preventDefault(); setOpen(true); setHi((i) => Math.min(i + 1, shown.length - 1)); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); setHi((i) => Math.max(i - 1, 0)); }
        else if (e.key === 'Enter' && open && shown[hi]) { e.preventDefault(); pick(shown[hi]); }
        else if (e.key === 'Escape') { setOpen(false); }
    };

    return (
        <div className="relative" ref={boxRef}>
            <Input
                id={id}
                autoComplete="off"
                disabled={disabled}
                value={open ? query : (selected ? selected.name : '')}
                placeholder={disabled ? emptyText : placeholder}
                onFocus={() => { if (!disabled) { setOpen(true); setQuery(''); setHi(0); } }}
                onChange={(e) => { setQuery(e.target.value); setOpen(true); setHi(0); }}
                onKeyDown={onKey}
            />
            {open && !disabled && (
                <div className="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-border bg-card shadow-lg">
                    {shown.length === 0 ? (
                        <div className="px-3 py-2 text-xs text-muted-foreground">{emptyText}</div>
                    ) : shown.map((o, i) => (
                        <button
                            key={o.id}
                            type="button"
                            onMouseEnter={() => setHi(i)}
                            onClick={() => pick(o)}
                            className={[
                                'block w-full px-3 py-1.5 text-start text-sm',
                                i === hi ? 'bg-accent' : 'hover:bg-accent/60',
                                String(o.id) === String(value) ? 'font-semibold' : '',
                            ].join(' ')}
                        >
                            {o.name}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}

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

    // 'merchant' is the signed-in merchant, so there is no merchant to pick
    // and the server ignores any that is sent. 'admin' files for anyone.
    audience = 'admin',
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
    const shops = lookups?.shops || [];

    // Areas arrive grouped by city id, so narrowing is a lookup, not a
    // fetch - the whole point of a modal that is supposed to be quick.
    const areasForCity = React.useMemo(
        () => (form.city_id ? (lookups?.areas?.[String(form.city_id)] || []) : []),
        [lookups, form.city_id],
    );

    // A lone pickup point is not a question. Fill it in and say which one was
    // used, rather than showing a select with one option or two inputs the
    // merchant would only retype.
    React.useEffect(() => {
        if (!lookups || shops.length !== 1) return;
        setForm((f) => ({
            ...f,
            shop_id:        String(shops[0].id),
            pickup_phone:   f.pickup_phone   || shops[0].phone   || '',
            pickup_address: f.pickup_address || shops[0].address || '',
        }));
    }, [lookups]); // eslint-disable-line react-hooks/exhaustive-deps

    // Several pickup points: choosing one fills the contact details from it.
    React.useEffect(() => {
        if (!form.shop_id || shops.length < 2) return;
        const sh = shops.find((x) => String(x.id) === String(form.shop_id));
        if (!sh) return;
        setForm((f) => ({
            ...f,
            pickup_phone:   sh.phone   || '',
            pickup_address: sh.address || '',
        }));
    }, [form.shop_id]); // eslint-disable-line react-hooks/exhaustive-deps

    // One merchant means there is nothing to choose. The merchant panel
    // always sends exactly one; an admin tenant with a single merchant gets
    // the same courtesy.
    React.useEffect(() => {
        if (!lookups || form.merchant_id) return;
        const list = lookups.merchants || [];
        if (list.length === 1) setForm((f) => ({ ...f, merchant_id: String(list[0].id) }));
    }, [lookups]); // eslint-disable-line react-hooks/exhaustive-deps

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

                            {audience === 'admin' && (
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
                            )}

                            {shops.length === 1 ? (
                                // Nothing to choose: state what will be used.
                                <div className="rounded-md border border-border bg-muted/30 px-3 py-2 text-sm">
                                    <div className="font-medium">{shops[0].name}</div>
                                    <div className="text-xs text-muted-foreground">
                                        {[shops[0].phone, shops[0].address].filter(Boolean).join(' - ')}
                                    </div>
                                </div>
                            ) : shops.length > 1 ? (
                                <div className="space-y-1.5">
                                    <Label htmlFor="qs-shop">{t('quick_ship_pickup')}</Label>
                                    <Select id="qs-shop" value={form.shop_id} onChange={set('shop_id')}>
                                        <option value="">-</option>
                                        {shops.map((sh) => (
                                            <option key={sh.id} value={sh.id}>{sh.name}</option>
                                        ))}
                                    </Select>
                                </div>
                            ) : (
                                <div className="grid gap-3 sm:grid-cols-2">
                                    {field('pickup_phone', t('quick_ship_pickup_phone'), { inputMode: 'tel' })}
                                    {field('pickup_address', t('quick_ship_pickup_address'))}
                                </div>
                            )}
                        </div>

                        {/* ---------- Receiver ---------- */}
                        <div className="space-y-3 border-t border-border pt-4">
                            {sectionTitle(t('quick_ship_receiver'))}

                            {/* Order follows how the shipment is actually
                                described out loud: who, how to reach them,
                                where - narrowing city, then area, then the
                                street address. Money and notes come last. */}
                            <div className="grid gap-3 sm:grid-cols-2">
                                {field('customer_name', t('quick_ship_receiver_name'))}
                                {field('customer_phone', t('quick_ship_receiver_phone'), { inputMode: 'tel' })}
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="qs-city">{t('quick_ship_city')}</Label>
                                    <SearchSelect
                                        id="qs-city"
                                        value={form.city_id}
                                        onChange={(v) => setForm((f) => ({ ...f, city_id: v, area_id: '' }))}
                                        options={lookups?.cities || []}
                                        placeholder={t('quick_ship_city')}
                                        emptyText={t('quick_ship_no_match')}
                                    />
                                    {fieldError('city_id') && (
                                        <p className="text-xs text-rose-600">{fieldError('city_id')}</p>
                                    )}
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="qs-area">{t('quick_ship_area')}</Label>
                                    <SearchSelect
                                        id="qs-area"
                                        value={form.area_id}
                                        onChange={(v) => setForm((f) => ({ ...f, area_id: v }))}
                                        options={areasForCity}
                                        placeholder={t('quick_ship_area')}
                                        emptyText={form.city_id ? t('quick_ship_no_match') : t('quick_ship_pick_city_first')}
                                        disabled={!form.city_id}
                                    />
                                    {fieldError('area_id') && (
                                        <p className="text-xs text-rose-600">{fieldError('area_id')}</p>
                                    )}
                                </div>
                            </div>

                            {field('customer_address', t('quick_ship_receiver_address'))}
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
