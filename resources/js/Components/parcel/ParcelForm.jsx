import * as React from 'react';
import {
    User, Store, Phone, MapPin, Banknote, Tag, Package, Truck,
    StickyNote, Box, Flame, Save, ArrowLeft, Calculator, ChevronRight, ChevronDown,
} from 'lucide-react';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';
import { Textarea } from '@/Components/ui/Textarea';
import { cn } from '@/lib/utils';

/* ------------------------------------------------------------------ */
/* Design tokens (spec)                                               */
/*   bg #F8FAFC · cards white · radius 12px · shadow-sm               */
/*   primary #2563EB · success #16A34A · danger #EF4444               */
/*   border #E5E7EB · text #111827 · secondary #6B7280                */
/*                                                                    */
/* We drive most of these through the existing Tailwind theme tokens  */
/* (bg-card, text-foreground, text-muted-foreground, ring-primary…);  */
/* the redesign changes layout + hierarchy, not the color pipeline.   */
/* ------------------------------------------------------------------ */

function Field({ label, required, error, hint, children, className, icon: Icon }) {
    return (
        <div className={cn('space-y-1.5', className)}>
            <Label className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {Icon && <Icon className="h-3 w-3" />}
                {label}
                {required && <span className="text-rose-500" aria-hidden>*</span>}
            </Label>
            {children}
            {hint && <p className="text-[11px] text-muted-foreground">{hint}</p>}
            {error && <p className="text-xs text-rose-600">{error}</p>}
        </div>
    );
}

/**
 * Collapsible section card. Header shows an icon, title, and an optional
 * badge (e.g. "5 fields"), followed by a summary strip when collapsed.
 * Matches the "accordion animation + rounded 12px + subtle shadow" spec.
 */
function SectionCard({
    icon: Icon, title, subtitle, defaultOpen = true, badge, children,
    // when collapsed, this string appears next to the chevron
    collapsedSummary,
}) {
    const [open, setOpen] = React.useState(defaultOpen);
    return (
        <Card className="overflow-hidden rounded-xl shadow-sm border border-border">
            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                className="w-full flex items-start gap-3 px-5 pt-5 pb-4 text-start hover:bg-muted/30 transition-colors"
                aria-expanded={open}
            >
                {Icon && (
                    <span className="mt-0.5 inline-grid place-items-center h-9 w-9 rounded-lg bg-primary/10 text-primary shrink-0">
                        <Icon className="h-4 w-4" />
                    </span>
                )}
                <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                        <div className="text-sm font-semibold tracking-tight">{title}</div>
                        {badge && (
                            <span className="text-[10px] font-semibold uppercase tracking-wide rounded-full bg-muted px-2 py-0.5 text-muted-foreground">
                                {badge}
                            </span>
                        )}
                    </div>
                    {subtitle && <div className="mt-0.5 text-xs text-muted-foreground">{subtitle}</div>}
                    {!open && collapsedSummary && (
                        <div className="mt-1 text-xs text-muted-foreground truncate">{collapsedSummary}</div>
                    )}
                </div>
                <span className="mt-1 text-muted-foreground shrink-0">
                    {open ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                </span>
            </button>
            <div
                className={cn(
                    'grid transition-[grid-template-rows] duration-200 ease-out',
                    open ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'
                )}
            >
                <div className="overflow-hidden">
                    <div className="px-5 pb-5 pt-1">
                        {children}
                    </div>
                </div>
            </div>
        </Card>
    );
}

function Badge({ tone = 'muted', icon: Icon, children }) {
    const tones = {
        muted:   'bg-muted text-muted-foreground',
        primary: 'bg-primary/10 text-primary',
        success: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
        warning: 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-200',
    };
    return (
        <span className={cn(
            'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold',
            tones[tone]
        )}>
            {Icon && <Icon className="h-3 w-3" />}
            {children}
        </span>
    );
}

function Money({ value, currency }) {
    return (
        <span className="tabular-nums">
            <span className="text-muted-foreground text-xs me-0.5">{currency}</span>
            {Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
        </span>
    );
}

/* ------------------------------------------------------------------ */
/* Google Maps loader — unchanged from prior version.                 */
/* ------------------------------------------------------------------ */
let gmapsPromise = null;
let gmapsKey = null;
function loadGoogleMaps(apiKey) {
    if (typeof window === 'undefined') return Promise.resolve(null);
    if (window.google && window.google.maps) return Promise.resolve(window.google.maps);
    if (gmapsPromise) {
        if (apiKey && gmapsKey && apiKey !== gmapsKey) {
            // eslint-disable-next-line no-console
            console.warn('Google Maps loader already initialized with a different key; reusing existing load.');
        }
        return gmapsPromise;
    }
    gmapsKey = apiKey;
    gmapsPromise = new Promise((resolve, reject) => {
        const cb = `__gmaps_ready_${Math.random().toString(36).slice(2)}`;
        window[cb] = () => { resolve(window.google.maps); try { delete window[cb]; } catch (e) {} };
        const script = document.createElement('script');
        const params = new URLSearchParams({
            key: apiKey || '',
            callback: cb,
            libraries: 'places',
            loading: 'async',
        });
        script.src = `https://maps.googleapis.com/maps/api/js?${params.toString()}`;
        script.async = true;
        script.defer = true;
        script.onerror = () => reject(new Error('Failed to load Google Maps JS API'));
        document.head.appendChild(script);
    });
    return gmapsPromise;
}

/**
 * Larger drop-off picker (spec: "map should have a larger height").
 * Clicking the map places the marker; dragging updates the coord fields.
 */
function LocationPicker({ form, defaultCenter, labels, apiKey }) {
    const mapEl = React.useRef(null);
    const mapObj = React.useRef(null);
    const dropoffMarker = React.useRef(null);
    const [status, setStatus] = React.useState(apiKey ? 'loading' : 'no-key');

    const dropLat  = parseFloat(form.data.lat);
    const dropLong = parseFloat(form.data.long);

    React.useEffect(() => {
        if (!apiKey) return;
        let cancelled = false;
        loadGoogleMaps(apiKey).then((maps) => {
            if (cancelled || !maps || !mapEl.current || mapObj.current) return;
            const initial = Number.isFinite(dropLat) && Number.isFinite(dropLong)
                ? { lat: dropLat, lng: dropLong }
                : { lat: defaultCenter[0], lng: defaultCenter[1] };
            const map = new maps.Map(mapEl.current, {
                center: initial,
                zoom: 12,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false,
            });
            mapObj.current = map;

            const dropIcon = {
                path: maps.SymbolPath.CIRCLE,
                fillColor: '#dc2626', fillOpacity: 1,
                strokeColor: '#ffffff', strokeWeight: 2, scale: 8,
            };

            const placeDropoff = (lat, lng) => {
                if (dropoffMarker.current) {
                    dropoffMarker.current.setPosition({ lat, lng });
                } else {
                    dropoffMarker.current = new maps.Marker({
                        position: { lat, lng }, map, icon: dropIcon,
                        draggable: true, title: labels.dropoff_pin || 'Drop-off',
                    });
                    dropoffMarker.current.addListener('dragend', (e) => {
                        const p = e.latLng;
                        form.setData((d) => ({ ...d, lat: p.lat().toFixed(6), long: p.lng().toFixed(6) }));
                    });
                }
            };
            if (Number.isFinite(dropLat) && Number.isFinite(dropLong)) placeDropoff(dropLat, dropLong);

            map.addListener('click', (ev) => {
                const lat = ev.latLng.lat();
                const lng = ev.latLng.lng();
                placeDropoff(lat, lng);
                form.setData((d) => ({ ...d, lat: lat.toFixed(6), long: lng.toFixed(6) }));
            });

            setStatus('ready');
        }).catch(() => setStatus('error'));

        return () => {
            cancelled = true;
            mapObj.current = null;
            dropoffMarker.current = null;
        };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [apiKey]);

    return (
        <div className="space-y-2">
            <div className="flex items-center gap-2 text-xs">
                <span className="inline-flex items-center gap-1.5 rounded-md border border-rose-200 bg-rose-50 dark:bg-rose-950/40 dark:border-rose-900 px-2.5 py-1.5 font-medium text-rose-700 dark:text-rose-200">
                    <span className="inline-block h-2 w-2 rounded-full bg-rose-500" />
                    {labels.dropoff_pin || 'Drop-off'}
                </span>
                <span className="ms-auto text-[11px] text-muted-foreground">
                    {labels.map_hint || 'Click on the map to place the drop-off pin, or drag it to adjust.'}
                </span>
            </div>
            {status === 'no-key' ? (
                <div className="rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-800 p-3 text-xs text-amber-800 dark:text-amber-200">
                    {labels.no_key || 'Google Maps API key not configured for this workspace. Set it under Settings → Google Map to enable the map. Coordinates below are still editable.'}
                </div>
            ) : status === 'error' ? (
                <div className="rounded-xl border border-rose-300 bg-rose-50 dark:bg-rose-950/30 dark:border-rose-800 p-3 text-xs text-rose-800 dark:text-rose-200">
                    {labels.load_error || 'Google Maps failed to load. Check the API key + billing status.'}
                </div>
            ) : (
                <div
                    ref={mapEl}
                    className="h-80 lg:h-96 w-full rounded-xl border border-border bg-muted overflow-hidden"
                    style={{ minHeight: '320px' }}
                />
            )}
            <div className="text-[11px] text-muted-foreground">
                <span className="font-semibold text-foreground">{labels.dropoff_pin || 'Drop-off'}:</span>{' '}
                {form.data.lat && form.data.long ? `${form.data.lat}, ${form.data.long}` : '—'}
            </div>
        </div>
    );
}

function ChargeRow({ label, value, currency, bold, tone }) {
    return (
        <div className={cn(
            'flex items-center justify-between border-b border-border py-2 last:border-0 text-sm',
            bold && 'font-semibold',
        )}>
            <span className={cn('text-muted-foreground', bold && 'text-foreground')}>{label}</span>
            <span className={cn(
                tone === 'positive' && 'text-emerald-600',
                tone === 'negative' && 'text-rose-600',
            )}>
                <Money value={value} currency={currency} />
            </span>
        </div>
    );
}

export default function ParcelForm({
    form,
    mode = 'create',
    lookups = {},
    settings = {},
    urls = {},
    t = {},
    initialShops = [],
    onSubmit,
}) {
    const {
        merchants = [], cities = [], categories = [], packagings = [], delivery_types = [],
    } = lookups;
    const currency = settings.currency || '';
    const [shops, setShops] = React.useState(initialShops);

    // -------- side effects (unchanged) -------------------------------
    const onMerchantChange = (id) => {
        form.setData('merchant_id', id);
        form.setData('shop_id', '');
        const m = merchants.find((x) => String(x.id) === String(id));
        if (m) {
            if (!form.data.pickup_phone)   form.setData('pickup_phone',   m.pickup_phone   || '');
            if (!form.data.pickup_address) form.setData('pickup_address', m.pickup_address || '');
            form.setData('vat_tex', m.vat || settings.vat_tax || 0);
        }
        if (!id) { setShops([]); return; }
        const fd = new FormData();
        fd.append('merchant_id', id);
        fd.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
        fetch(urls.merchant_shops, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then((r) => r.ok ? r.json() : [])
            .then((data) => setShops(Array.isArray(data) ? data : (data?.shops || [])))
            .catch(() => setShops([]));
    };

    React.useEffect(() => {
        if (!form.data.shop_id) return;
        const shop = shops.find((s) => String(s.id) === String(form.data.shop_id));
        if (shop) {
            form.setData((d) => ({
                ...d,
                pickup_phone:   shop.phone   || d.pickup_phone,
                pickup_address: shop.address || d.pickup_address,
                pickup_lat:     shop.lat  ? String(shop.lat)  : '',
                pickup_long:    shop.long ? String(shop.long) : '',
            }));
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [form.data.shop_id, shops]);

    const areas = React.useMemo(() => {
        const c = cities.find((x) => String(x.id) === String(form.data.city_id));
        return c?.areas || [];
    }, [form.data.city_id, cities]);

    React.useEffect(() => {
        if (form.data.area_id && !areas.find((a) => String(a.id) === String(form.data.area_id))) {
            form.setData('area_id', '');
        }
    }, [form.data.city_id]); // eslint-disable-line react-hooks/exhaustive-deps

    // -------- live charge computation (unchanged) --------------------
    const merchant = merchants.find((m) => String(m.id) === String(form.data.merchant_id));
    const packaging = packagings.find((p) => String(p.id) === String(form.data.packaging_id));
    const cashCollection = Number(form.data.cash_collection || 0);
    const codPct = merchant?.cod_charges?.inside_city || 0;
    const codCharge = cashCollection * (codPct / 100);
    const liquidCharge = form.data.fragileLiquid ? Number(settings.fragile_liquid_charge || 0) : 0;
    const packagingCharge = Number(packaging?.price || 0);
    const deliveryCharge = 0;
    const totalCharge = codCharge + liquidCharge + packagingCharge + deliveryCharge;
    const vatAmount   = totalCharge * ((Number(form.data.vat_tex) || 0) / 100);
    const netPayable  = cashCollection - totalCharge - vatAmount;
    const currentPayable = netPayable;

    React.useEffect(() => {
        form.setData('chargeDetails', JSON.stringify({
            totalCashCollection: cashCollection,
            codChargeAmount: codCharge,
            liquidFragileAmount: liquidCharge,
            packagingAmount: packagingCharge,
            totalDeliveryChargeAmount: totalCharge,
            VatAmount: vatAmount,
            netPayable,
            currentPayable,
        }));
    }, [cashCollection, codCharge, liquidCharge, packagingCharge, totalCharge, vatAmount]); // eslint-disable-line react-hooks/exhaustive-deps

    // -------- derived UI state --------------------------------------
    const selectedCategory   = categories.find((c) => String(c.id) === String(form.data.category_id));
    const selectedDeliveryTy = delivery_types.find((d) => String(d.id) === String(form.data.delivery_type_id));
    const selectedShop       = shops.find((s) => String(s.id) === String(form.data.shop_id));
    const priorityLabel      = String(form.data.priority_id) === '1' ? (t.high || 'High') : (t.normal || 'Normal');
    const priorityTone       = String(form.data.priority_id) === '1' ? 'warning' : 'muted';

    const headerBadges = (
        <div className="mt-2 flex flex-wrap items-center gap-2">
            {merchant && <Badge tone="primary" icon={Store}>{merchant.name}</Badge>}
            {selectedShop && <Badge icon={Store}>{selectedShop.name || selectedShop.title}</Badge>}
            {selectedDeliveryTy && <Badge icon={Truck}>{selectedDeliveryTy.name}</Badge>}
            <Badge tone={priorityTone}>{priorityLabel}</Badge>
            {form.data.fragileLiquid && <Badge tone="warning" icon={Flame}>Fragile</Badge>}
        </div>
    );

    return (
        <form onSubmit={onSubmit} encType="multipart/form-data" noValidate className="pb-24 lg:pb-0">
            {/* Compact context strip — AdminLayout already renders the title
                as the top-level H1, so we skip the redundant heading and
                surface only the identity badges + primary CTAs here. */}
            <div className="mb-5 flex flex-wrap items-start justify-between gap-3">
                <div className="min-w-0">{headerBadges}</div>
                <div className="hidden lg:flex items-center gap-2">
                    <a href={urls.cancel} className="inline-flex h-10 items-center rounded-lg border border-input bg-background px-4 text-sm font-medium hover:bg-accent transition-colors">
                        <ArrowLeft className="h-4 w-4 me-1.5" /> {t.cancel}
                    </a>
                    <Button type="submit" disabled={form.processing} className="rounded-lg">
                        <Save className="h-4 w-4 me-1.5" />
                        {form.processing ? '…' : (mode === 'edit' ? (t.update || t.save) : t.save)}
                    </Button>
                </div>
            </div>

            {/* ── Two-column layout: 75% form / 25% sticky sidebar ────── */}
            <div className="grid gap-6 lg:grid-cols-4">
                {/* Left: form */}
                <div className="lg:col-span-3 space-y-5">
                    <SectionCard
                        icon={Store}
                        title={t.pickup_section_title || 'Pickup'}
                        subtitle={t.pickup_section_hint || 'Merchant, shop, and pickup contact'}
                        collapsedSummary={merchant?.name}
                    >
                        <div className="grid gap-4 md:grid-cols-2">
                            <Field label={t.merchant} required error={form.errors.merchant_id} icon={Store}>
                                <Select
                                    value={form.data.merchant_id}
                                    onChange={(e) => onMerchantChange(e.target.value)}
                                    disabled={mode === 'edit'}
                                >
                                    <option value="">{t.select_merchant}</option>
                                    {merchants.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                                </Select>
                            </Field>
                            <Field label={t.shop} error={form.errors.shop_id} icon={Store}>
                                <Select value={form.data.shop_id} onChange={(e) => form.setData('shop_id', e.target.value)} disabled={!form.data.merchant_id}>
                                    <option value="">—</option>
                                    {shops.map((s) => <option key={s.id} value={s.id}>{s.name || s.title}</option>)}
                                </Select>
                            </Field>
                            <Field label={t.pickup_phone} required error={form.errors.pickup_phone} icon={Phone}>
                                <Input value={form.data.pickup_phone} onChange={(e) => form.setData('pickup_phone', e.target.value)} inputMode="tel" />
                            </Field>
                            <Field label={t.pickup_address} required error={form.errors.pickup_address} icon={MapPin}>
                                <Input value={form.data.pickup_address} onChange={(e) => form.setData('pickup_address', e.target.value)} />
                            </Field>
                        </div>
                    </SectionCard>

                    <SectionCard
                        icon={User}
                        title={t.receiver_section_title || 'Receiver'}
                        subtitle={t.receiver_section_hint || 'Customer contact + drop-off location'}
                        collapsedSummary={form.data.customer_name || undefined}
                    >
                        <div className="grid gap-4 md:grid-cols-2">
                            <Field label={t.customer_name} required error={form.errors.customer_name} icon={User}>
                                <Input value={form.data.customer_name} onChange={(e) => form.setData('customer_name', e.target.value)} />
                            </Field>
                            <Field label={t.customer_phone} required error={form.errors.customer_phone} icon={Phone}>
                                <Input value={form.data.customer_phone} onChange={(e) => form.setData('customer_phone', e.target.value)} inputMode="tel" />
                            </Field>
                            <Field label={t.city} required error={form.errors.city_id} icon={MapPin}>
                                <Select value={form.data.city_id} onChange={(e) => form.setData('city_id', e.target.value)}>
                                    <option value="">—</option>
                                    {cities.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                                </Select>
                            </Field>
                            <Field label={t.area} error={form.errors.area_id} icon={MapPin}>
                                <Select value={form.data.area_id} onChange={(e) => form.setData('area_id', e.target.value)} disabled={!form.data.city_id}>
                                    <option value="">{form.data.city_id ? '—' : t.select_city_first}</option>
                                    {areas.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
                                </Select>
                            </Field>
                            <Field label={t.customer_address} required error={form.errors.customer_address} className="md:col-span-2" icon={MapPin}>
                                <Input value={form.data.customer_address} onChange={(e) => form.setData('customer_address', e.target.value)} />
                            </Field>
                            <div className="md:col-span-2">
                                <Label className="mb-2 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                    <MapPin className="h-3 w-3" /> {t.map_title || 'Drop-off location'}
                                </Label>
                                <LocationPicker
                                    form={form}
                                    defaultCenter={[24.7136, 46.6753]}
                                    apiKey={settings.google_maps_key || ''}
                                    labels={{
                                        dropoff_pin: t.dropoff_pin || 'Drop-off',
                                        map_hint:    t.map_hint    || 'Click on the map to place the drop-off pin, or drag it to adjust.',
                                        no_key:      t.map_no_key,
                                        load_error:  t.map_load_error,
                                    }}
                                />
                            </div>
                        </div>
                    </SectionCard>

                    <SectionCard
                        icon={Truck}
                        title={t.shipping_section_title || 'Shipping'}
                        subtitle={t.shipping_section_hint || 'Delivery type, priority, packaging, and item category'}
                        collapsedSummary={[selectedDeliveryTy?.name, selectedCategory?.name, form.data.weight && `${form.data.weight} kg`].filter(Boolean).join(' · ') || undefined}
                    >
                        <div className="grid gap-4 md:grid-cols-3">
                            <Field label={t.delivery_type} required error={form.errors.delivery_type_id} icon={Truck}>
                                <Select value={form.data.delivery_type_id} onChange={(e) => form.setData('delivery_type_id', e.target.value)}>
                                    <option value="">—</option>
                                    {delivery_types.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                                </Select>
                            </Field>
                            <Field label={t.priority} error={form.errors.priority_id}>
                                <Select value={form.data.priority_id} onChange={(e) => form.setData('priority_id', e.target.value)}>
                                    <option value="2">{t.normal}</option>
                                    <option value="1">{t.high}</option>
                                </Select>
                            </Field>
                            <Field label={t.packaging} error={form.errors.packaging_id} icon={Box}>
                                <Select value={form.data.packaging_id} onChange={(e) => form.setData('packaging_id', e.target.value)}>
                                    <option value="">—</option>
                                    {packagings.map((p) => (
                                        <option key={p.id} value={p.id}>
                                            {p.name} ({Number(p.price).toFixed(2)} {currency})
                                        </option>
                                    ))}
                                </Select>
                            </Field>
                            <Field label={t.category} required error={form.errors.category_id} icon={Tag}>
                                <Select value={form.data.category_id} onChange={(e) => form.setData('category_id', e.target.value)}>
                                    <option value="">—</option>
                                    {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                                </Select>
                            </Field>
                            <Field label={t.weight} error={form.errors.weight} icon={Package}>
                                <Select value={form.data.weight} onChange={(e) => form.setData('weight', e.target.value)}>
                                    <option value="">—</option>
                                    {[0.5, 1, 2, 3, 5, 10, 15, 20].map((w) => <option key={w} value={w}>{w} kg</option>)}
                                </Select>
                            </Field>
                            <label className="md:col-span-1 flex items-center gap-2 rounded-xl border border-input bg-background px-3 py-2.5 cursor-pointer hover:bg-accent/30 transition-colors">
                                <input
                                    type="checkbox"
                                    checked={form.data.fragileLiquid}
                                    onChange={(e) => form.setData('fragileLiquid', e.target.checked)}
                                    className="h-4 w-4 rounded border-input"
                                />
                                <span className="text-sm font-medium flex items-center gap-1.5">
                                    <Flame className="h-4 w-4 text-rose-500" /> {t.liquid_fragile}
                                    <span className="text-xs text-muted-foreground ms-2">+{Number(settings.fragile_liquid_charge || 0).toFixed(2)} {currency}</span>
                                </span>
                            </label>
                        </div>
                    </SectionCard>

                    <SectionCard
                        icon={Banknote}
                        title={t.amounts_section_title || 'Amounts'}
                        subtitle={t.amounts_section_hint || 'Cash collection and invoice reference'}
                        collapsedSummary={cashCollection ? `${currency} ${cashCollection.toFixed(2)}` : undefined}
                    >
                        <div className="grid gap-4 md:grid-cols-3">
                            <Field label={t.cash_collection} required error={form.errors.cash_collection} icon={Banknote}>
                                <Input type="number" step="any" value={form.data.cash_collection} onChange={(e) => form.setData('cash_collection', e.target.value)} />
                            </Field>
                            <Field label={t.selling_price} error={form.errors.selling_price} icon={Banknote}>
                                <Input type="number" step="any" value={form.data.selling_price} onChange={(e) => form.setData('selling_price', e.target.value)} />
                            </Field>
                            <Field label={t.invoice_no} error={form.errors.invoice_no}>
                                <Input value={form.data.invoice_no} onChange={(e) => form.setData('invoice_no', e.target.value)} />
                            </Field>
                        </div>
                    </SectionCard>

                    <SectionCard
                        icon={StickyNote}
                        title={t.notes_section_title || 'Notes'}
                        subtitle={t.notes_section_hint || 'Handling instructions or internal notes'}
                        collapsedSummary={form.data.note ? `${(form.data.note || '').slice(0, 60)}${(form.data.note || '').length > 60 ? '…' : ''}` : undefined}
                        defaultOpen={false}
                    >
                        <Field label={t.note} error={form.errors.note} icon={StickyNote}>
                            <Textarea rows={4} value={form.data.note} onChange={(e) => form.setData('note', e.target.value)} />
                        </Field>
                    </SectionCard>
                </div>

                {/* Right: sticky sidebar summary + primary action */}
                <div className="lg:col-span-1">
                    <div className="lg:sticky lg:top-20 space-y-4">
                        <Card className="rounded-xl shadow-sm border border-border">
                            <CardContent className="pt-6">
                                <div className="mb-4 flex items-center gap-2">
                                    <Calculator className="h-4 w-4 text-primary" />
                                    <div className="text-sm font-semibold">{t.summary || t.charge_details || 'Shipment summary'}</div>
                                </div>
                                <div>
                                    <ChargeRow label={t.cash_collection} value={cashCollection} currency={currency} />
                                    <ChargeRow label={t.delivery_charge} value={deliveryCharge} currency={currency} />
                                    <ChargeRow label={t.cod_charge}      value={codCharge}      currency={currency} />
                                    {form.data.fragileLiquid && (
                                        <ChargeRow label={t.liquid_charge} value={liquidCharge} currency={currency} />
                                    )}
                                    {form.data.packaging_id && (
                                        <ChargeRow label={t.packaging_charge} value={packagingCharge} currency={currency} />
                                    )}
                                    <ChargeRow label={t.total_charge}   value={totalCharge}   currency={currency} bold />
                                    <ChargeRow label={t.vat}             value={vatAmount}     currency={currency} />
                                    <ChargeRow label={t.net_payable}     value={netPayable}    currency={currency} />
                                    <ChargeRow label={t.current_payable} value={currentPayable} currency={currency} bold tone={currentPayable >= 0 ? 'positive' : 'negative'} />
                                </div>
                                {deliveryCharge === 0 && form.data.merchant_id && form.data.city_id && (
                                    <p className="mt-3 text-[11px] text-muted-foreground leading-relaxed">
                                        {t.delivery_charge_calc_hint || 'Delivery charge depends on merchant pricing tier + city zone and is calculated on submit.'}
                                    </p>
                                )}

                                {/* Primary action inside the sidebar (desktop) */}
                                <div className="mt-5 hidden lg:block">
                                    <Button type="submit" disabled={form.processing} className="w-full h-11 rounded-lg text-sm font-semibold">
                                        <Save className="h-4 w-4 me-1.5" />
                                        {form.processing ? '…' : (mode === 'edit' ? (t.update || t.save) : (t.create_shipment || t.save))}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            {/* Sticky bottom action bar (mobile / tablet) */}
            <div className="fixed inset-x-0 bottom-0 z-30 border-t border-border bg-background/95 backdrop-blur px-4 py-3 shadow-lg lg:hidden">
                <div className="mx-auto flex max-w-3xl items-center gap-2">
                    <a href={urls.cancel} className="inline-flex h-10 items-center rounded-lg border border-input bg-background px-4 text-sm font-medium">
                        <ArrowLeft className="h-4 w-4 me-1.5" /> {t.cancel}
                    </a>
                    <Button type="submit" disabled={form.processing} className="flex-1 h-10 rounded-lg">
                        <Save className="h-4 w-4 me-1.5" />
                        {form.processing ? '…' : (mode === 'edit' ? (t.update || t.save) : (t.create_shipment || t.save))}
                    </Button>
                </div>
            </div>
        </form>
    );
}
