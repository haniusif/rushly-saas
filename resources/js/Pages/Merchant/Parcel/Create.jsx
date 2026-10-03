import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Save, X, AlertCircle, MapPin, Phone, User, FileText, Hash, Tag, Boxes, Truck, Building2 } from 'lucide-react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import { Textarea } from '@/Components/ui/Textarea';
import { Label } from '@/Components/ui/Label';

function Field({ label, required, error, hint, icon: Icon, children }) {
    return (
        <div className="space-y-1.5">
            <Label className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                {Icon && <Icon className="h-3 w-3" />}
                {label} {required && <span className="text-destructive">*</span>}
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

function fmt(n) {
    const v = Number(n) || 0;
    return v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Map delivery_type → which COD-charge column applies (matches the
// hidden inputs from the legacy Blade form).
const DT_TO_COD_KEY = {
    1: 'inside_city',  // same_day
    2: 'inside_city',  // next_day → same as inside city in legacy mapping
    3: 'sub_city',     // sub_city
    4: 'outside_city', // outside_City
};

async function postJson(url, payload, csrf) {
    const body = new FormData();
    Object.entries(payload).forEach(([k, v]) => body.append(k, v ?? ''));
    body.append('_token', csrf);
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json, text/html' },
        body,
        credentials: 'same-origin',
    });
    return res;
}

export default function Create({
    merchant = {},
    cod_charges = {},
    fragile_liquid = { active: false, charge: 0 },
    default_shop = null,
    shops = [],
    delivery_types = [],
    categories = [],
    packagings = [],
    cities = [],
    currency = '',
    urls = {},
    t = {},
    // Edit-mode props: when `parcel` is provided, the form is pre-filled and
    // submits to urls.update via PUT (multipart-spoofed) instead of POST.
    parcel = null,
    mode = 'create',
}) {
    const isEdit = mode === 'edit' && parcel;
    const csrf = React.useMemo(
        () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        [],
    );

    const form = useForm({
        merchant_id:        merchant.id || '',
        shop_id:            String(parcel?.shop_id ?? default_shop?.id ?? ''),
        pickup_phone:       parcel?.pickup_phone   ?? default_shop?.phone   ?? '',
        pickup_address:     parcel?.pickup_address ?? default_shop?.address ?? '',
        pickup_lat:         parcel?.pickup_lat     ?? default_shop?.lat     ?? '',
        pickup_long:        parcel?.pickup_long    ?? default_shop?.long    ?? '',
        cash_collection:    parcel?.cash_collection ?? '',
        reference_number:   parcel?.invoice_no ?? '',
        category_id:        String(parcel?.category_id ?? ''),
        weight:             parcel?.weight ?? '',
        extra_weight:       parcel?.extra_weight ?? 0,
        delivery_type_id:   String(parcel?.delivery_type_id ?? ''),
        customer_name:      parcel?.customer_name ?? '',
        customer_phone:     parcel?.customer_phone ?? '',
        city_id:            String(parcel?.city_id ?? ''),
        area_id:            String(parcel?.area_id ?? ''),
        customer_address:   parcel?.customer_address ?? '',
        lat:                parcel?.customer_lat ?? '',
        long:               parcel?.customer_long ?? '',
        note:               parcel?.note ?? '',
        fragileLiquid:      !!parcel?.liquid_fragile_amount,
        packaging_id:       String(parcel?.packaging_id ?? ''),
        parcel_bank:        !!parcel?.parcel_bank,
        vat_tex:            parcel?.vat ?? 0,
        cod_charge:         parcel?.cod_charge ?? 0,
        chargeDetails:      '',
        ...(isEdit ? { _method: 'put' } : {}),
    });

    const [weights, setWeights] = React.useState([]);
    const [areas, setAreas]     = React.useState([]);
    const [deliveryCharge, setDeliveryCharge] = React.useState(0);
    const [packagingPrice, setPackagingPrice] = React.useState(0);

    // shop change → pull phone/address/lat/long from the picked shop
    React.useEffect(() => {
        if (!form.data.shop_id) return;
        const shop = shops.find((s) => String(s.id) === String(form.data.shop_id));
        if (shop) {
            form.setData((d) => ({
                ...d,
                pickup_phone:   shop.phone || '',
                pickup_address: shop.address || '',
                pickup_lat:     shop.lat || '',
                pickup_long:    shop.long || '',
            }));
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [form.data.shop_id]);

    // category change → fetch weight options
    React.useEffect(() => {
        if (!form.data.category_id) {
            setWeights([]);
            return;
        }
        let cancelled = false;
        (async () => {
            const res = await postJson(urls.weight_lookup, { category_id: form.data.category_id }, csrf);
            const html = await res.text();
            if (cancelled) return;
            // server returns <option> snippets — parse them out
            const opts = [...html.matchAll(/<option\s+value="([^"]*)"[^>]*>([^<]+)<\/option>/g)]
                .map((m) => ({ value: m[1], label: m[2].trim() }))
                .filter((o) => o.value);
            setWeights(opts);
        })();
        return () => { cancelled = true; };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [form.data.category_id]);

    // city change → fetch areas
    React.useEffect(() => {
        if (!form.data.city_id) {
            setAreas([]);
            return;
        }
        let cancelled = false;
        (async () => {
            const res = await fetch(`${urls.areas_by_city}?city_id=${form.data.city_id}`, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (cancelled) return;
            setAreas(Array.isArray(data) ? data : []);
        })();
        return () => { cancelled = true; };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [form.data.city_id]);

    // category + weight + delivery_type → recompute server-side delivery charge
    React.useEffect(() => {
        const { category_id, weight, delivery_type_id } = form.data;
        if (!category_id || !weight || !delivery_type_id) {
            setDeliveryCharge(0);
            return;
        }
        let cancelled = false;
        (async () => {
            const res = await postJson(urls.delivery_charge, {
                merchant_id:     form.data.merchant_id,
                category_id,
                weight,
                delivery_type_id,
            }, csrf);
            const txt = (await res.text()).trim();
            if (cancelled) return;
            const n = parseFloat(txt);
            setDeliveryCharge(Number.isFinite(n) ? n : 0);
        })();
        return () => { cancelled = true; };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [form.data.category_id, form.data.weight, form.data.delivery_type_id]);

    // packaging change → store price client-side
    React.useEffect(() => {
        const p = packagings.find((p) => String(p.id) === String(form.data.packaging_id));
        setPackagingPrice(p ? Number(p.price) || 0 : 0);
    }, [form.data.packaging_id, packagings]);

    // ── live charge calculation
    const cash         = Number(form.data.cash_collection) || 0;
    const extraWeight  = Number(form.data.extra_weight) || 0;
    const codKey       = DT_TO_COD_KEY[form.data.delivery_type_id] || 'inside_city';
    const codPct       = Number(cod_charges[codKey]) || 0;
    const codCharge    = cash * (codPct / 100);
    const liquidCharge = form.data.fragileLiquid && fragile_liquid.active ? Number(fragile_liquid.charge) || 0 : 0;
    const totalCharge  = deliveryCharge + codCharge + liquidCharge + packagingPrice + extraWeight;
    const vatPct       = Number(merchant.vat) || 0;
    const vat          = totalCharge * (vatPct / 100);
    const netPayable   = totalCharge + vat;
    const currentPayable = Math.max(0, cash - netPayable);

    // sync calculated fields back into the form payload before submit
    React.useEffect(() => {
        const details = JSON.stringify({
            cash,
            deliveryCharge,
            codCharge,
            liquidCharge,
            packagingPrice,
            totalDeliveryChargeAmount: totalCharge,
            vat,
            netPayable,
            currentPayable,
        });
        form.setData((d) => ({ ...d, chargeDetails: details, cod_charge: codCharge, vat_tex: vat }));
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [cash, deliveryCharge, codCharge, liquidCharge, packagingPrice, totalCharge, vat, netPayable, currentPayable]);

    const onSubmit = (e) => {
        e.preventDefault();
        const target = isEdit ? urls.update : urls.store;
        // Use POST + _method:put for multipart-spoofed PUT in edit mode.
        form.post(target, { forceFormData: true, preserveScroll: true });
    };

    const pageTitle = isEdit ? (t.edit_title || t.title) : t.title;
    const crumbLast = isEdit ? (t.edit || 'Edit') : (t.create || 'Create');

    return (
        <MerchantLayout title={pageTitle} breadcrumbs={[t.dashboard, t.shipments, crumbLast]}>
            <Head title={pageTitle} />
            <form onSubmit={onSubmit}>
                <div className="grid gap-3 lg:grid-cols-3">
                    {/* ─── main form ──────────────────────────────────────────── */}
                    <div className="lg:col-span-2 space-y-3">
                        <Card>
                            <CardContent className="p-5">
                                <h2 className="text-lg font-semibold mb-4">{t.title}</h2>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field label={t.pickup_point} icon={Building2} error={form.errors.shop_id}>
                                        <Select value={form.data.shop_id} onChange={(e) => form.setData('shop_id', e.target.value)}>
                                            <option value="">{t.pickup_point_ph}</option>
                                            {shops.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                                        </Select>
                                    </Field>
                                    <Field label={t.pickup_phone} icon={Phone} error={form.errors.pickup_phone} required>
                                        <Input value={form.data.pickup_phone} onChange={(e) => form.setData('pickup_phone', e.target.value)} placeholder={t.pickup_phone} />
                                    </Field>
                                    <Field label={t.pickup_address} icon={MapPin} error={form.errors.pickup_address} required>
                                        <Input value={form.data.pickup_address} onChange={(e) => form.setData('pickup_address', e.target.value)} placeholder={t.pickup_address} />
                                    </Field>
                                    <Field label={t.cod} icon={Hash} error={form.errors.cash_collection} required>
                                        <Input
                                            type="number" step="0.01" min="0"
                                            value={form.data.cash_collection}
                                            onChange={(e) => form.setData('cash_collection', e.target.value)}
                                            placeholder={t.cod_ph}
                                        />
                                    </Field>
                                    <Field label={t.reference_number} icon={FileText} error={form.errors.reference_number}>
                                        <Input value={form.data.reference_number} onChange={(e) => form.setData('reference_number', e.target.value)} placeholder={t.reference_ph} />
                                    </Field>
                                    <Field label={t.category} icon={Tag} error={form.errors.category_id} required>
                                        <Select value={form.data.category_id} onChange={(e) => form.setData('category_id', e.target.value)}>
                                            <option value="">{t.select}</option>
                                            {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                                        </Select>
                                    </Field>
                                    <Field label={t.weight} error={form.errors.weight} required>
                                        <Select value={form.data.weight} onChange={(e) => form.setData('weight', e.target.value)} disabled={!form.data.category_id}>
                                            <option value="">{t.select} {t.weight}</option>
                                            {weights.map((w) => <option key={w.value} value={w.value}>{w.label}</option>)}
                                        </Select>
                                    </Field>
                                    <Field label={t.extra_weight} error={form.errors.extra_weight}>
                                        <Input
                                            type="number" step="0.1" min="0"
                                            value={form.data.extra_weight}
                                            onChange={(e) => form.setData('extra_weight', e.target.value)}
                                            placeholder={t.extra_weight}
                                        />
                                    </Field>
                                    <Field label={t.delivery_type} icon={Truck} error={form.errors.delivery_type_id} required>
                                        <Select value={form.data.delivery_type_id} onChange={(e) => form.setData('delivery_type_id', e.target.value)}>
                                            <option value="">{t.select} {t.delivery_type}</option>
                                            {delivery_types.map((dt) => <option key={dt.id} value={dt.id}>{dt.name}</option>)}
                                        </Select>
                                    </Field>
                                    <Field label={t.customer_name} icon={User} error={form.errors.customer_name} required>
                                        <Input value={form.data.customer_name} onChange={(e) => form.setData('customer_name', e.target.value)} placeholder={t.customer_name} />
                                    </Field>
                                    <Field label={t.customer_phone} icon={Phone} error={form.errors.customer_phone} required>
                                        <Input value={form.data.customer_phone} onChange={(e) => form.setData('customer_phone', e.target.value)} placeholder={t.customer_phone} />
                                    </Field>
                                    <Field label={t.city} error={form.errors.city_id}>
                                        <Select value={form.data.city_id} onChange={(e) => form.setData('city_id', e.target.value)}>
                                            <option value="">{t.city_ph}</option>
                                            {cities.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                                        </Select>
                                    </Field>
                                    <Field label={t.area} error={form.errors.area_id}>
                                        <Select value={form.data.area_id} onChange={(e) => form.setData('area_id', e.target.value)} disabled={!form.data.city_id}>
                                            <option value="">{t.area_ph}</option>
                                            {areas.map((a) => <option key={a.id} value={a.id}>{a.name || a.title}</option>)}
                                        </Select>
                                    </Field>
                                </div>

                                <div className="mt-4 space-y-4">
                                    <Field label={t.customer_address} icon={MapPin} error={form.errors.customer_address} required>
                                        <Input value={form.data.customer_address} onChange={(e) => form.setData('customer_address', e.target.value)} placeholder={t.customer_address} />
                                    </Field>
                                    <Field label={t.note} error={form.errors.note}>
                                        <Textarea
                                            rows={4}
                                            value={form.data.note}
                                            onChange={(e) => form.setData('note', e.target.value)}
                                        />
                                    </Field>

                                    <div className="grid md:grid-cols-2 gap-4">
                                        {fragile_liquid.active && (
                                            <label className="flex items-start gap-2 rounded-md border border-input p-3 cursor-pointer hover:bg-muted/30">
                                                <input
                                                    type="checkbox"
                                                    checked={form.data.fragileLiquid}
                                                    onChange={(e) => form.setData('fragileLiquid', e.target.checked)}
                                                    className="mt-0.5"
                                                />
                                                <div>
                                                    <div className="text-sm font-medium">{t.liquid_check_label}</div>
                                                    <div className="text-[11px] text-muted-foreground">
                                                        {t.liquid_fragile} (+{fmt(fragile_liquid.charge)} {currency})
                                                    </div>
                                                </div>
                                            </label>
                                        )}
                                        <Field label={t.packaging} icon={Boxes} error={form.errors.packaging_id}>
                                            <Select value={form.data.packaging_id} onChange={(e) => form.setData('packaging_id', e.target.value)}>
                                                <option value="">{t.select} {t.packaging}</option>
                                                {packagings.map((p) => (
                                                    <option key={p.id} value={p.id}>
                                                        {p.name} ({fmt(p.price)} {currency})
                                                    </option>
                                                ))}
                                            </Select>
                                        </Field>
                                    </div>

                                    <label className="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={form.data.parcel_bank}
                                            onChange={(e) => form.setData('parcel_bank', e.target.checked)}
                                        />
                                        {t.parcel_bank}
                                    </label>
                                </div>

                                <div className="mt-6 flex items-center gap-2 border-t border-border pt-4">
                                    <button
                                        type="submit"
                                        disabled={form.processing}
                                        className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:opacity-90 disabled:opacity-50"
                                    >
                                        <Save className="h-4 w-4" /> {t.save}
                                    </button>
                                    <a href={urls.cancel} className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium rounded-md border border-input bg-background hover:bg-muted/40 no-underline">
                                        <X className="h-4 w-4" /> {t.cancel}
                                    </a>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* ─── charge summary ─────────────────────────────────────── */}
                    <aside className="lg:col-span-1">
                        <Card className="sticky top-4">
                            <CardContent className="p-0">
                                <div className="px-5 py-3 border-b border-border">
                                    <h3 className="text-sm font-semibold m-0">{t.charge_details}</h3>
                                </div>
                                <ul className="divide-y divide-border list-none m-0 p-0 text-sm">
                                    <li className="flex items-center justify-between px-5 py-2.5">
                                        <span className="text-muted-foreground">{t.cash_collection}</span>
                                        <span className="tabular-nums font-medium">{fmt(cash)} <span className="text-xs text-muted-foreground">{currency}</span></span>
                                    </li>
                                    <li className="flex items-center justify-between px-5 py-2.5">
                                        <span className="text-muted-foreground">{t.delivery_charge}</span>
                                        <span className="tabular-nums">{fmt(deliveryCharge)} <span className="text-xs text-muted-foreground">{currency}</span></span>
                                    </li>
                                    <li className="flex items-center justify-between px-5 py-2.5">
                                        <span className="text-muted-foreground">{t.cod_charge}</span>
                                        <span className="tabular-nums">{fmt(codCharge)} <span className="text-xs text-muted-foreground">{currency}</span></span>
                                    </li>
                                    {form.data.fragileLiquid && fragile_liquid.active && (
                                        <li className="flex items-center justify-between px-5 py-2.5">
                                            <span className="text-muted-foreground">{t.liquid_charge}</span>
                                            <span className="tabular-nums">{fmt(liquidCharge)} <span className="text-xs text-muted-foreground">{currency}</span></span>
                                        </li>
                                    )}
                                    {packagingPrice > 0 && (
                                        <li className="flex items-center justify-between px-5 py-2.5">
                                            <span className="text-muted-foreground">{t.packaging_charge}</span>
                                            <span className="tabular-nums">{fmt(packagingPrice)} <span className="text-xs text-muted-foreground">{currency}</span></span>
                                        </li>
                                    )}
                                    <li className="flex items-center justify-between px-5 py-2.5 bg-muted/20">
                                        <span className="font-semibold">{t.total_charge}</span>
                                        <span className="tabular-nums font-semibold">{fmt(totalCharge)} <span className="text-xs text-muted-foreground">{currency}</span></span>
                                    </li>
                                    <li className="flex items-center justify-between px-5 py-2.5">
                                        <span className="text-muted-foreground">{t.vat}</span>
                                        <span className="tabular-nums">{fmt(vat)} <span className="text-xs text-muted-foreground">{currency}</span></span>
                                    </li>
                                    <li className="flex items-center justify-between px-5 py-2.5">
                                        <span className="text-muted-foreground">{t.net_payable}</span>
                                        <span className="tabular-nums">{fmt(netPayable)} <span className="text-xs text-muted-foreground">{currency}</span></span>
                                    </li>
                                    <li className="flex items-center justify-between px-5 py-3 bg-emerald-50/40">
                                        <span className="font-semibold text-emerald-800">{t.current_payable}</span>
                                        <span className={`tabular-nums font-semibold ${currentPayable > 0 ? 'text-emerald-700' : 'text-foreground'}`}>
                                            {fmt(currentPayable)} <span className="text-xs text-muted-foreground">{currency}</span>
                                        </span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                    </aside>
                </div>
            </form>
        </MerchantLayout>
    );
}
