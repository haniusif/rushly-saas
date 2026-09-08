import * as React from 'react';
import { useForm } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import ParcelForm from '@/Components/parcel/ParcelForm';
import { Label } from '@/Components/ui/Label';

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
    google_maps_key = '',
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
            {/* Same component the admin create screen uses. Everything that
                depends on the merchant's own rate card - weight bands, areas,
                the delivery charge and the whole charge summary - is still
                resolved HERE and handed over, because those numbers are what
                the merchant is billed on and the shared component's own
                approximation is not the same calculation. */}
            <ParcelForm
                form={form}
                mode={isEdit ? 'edit' : 'create'}
                audience={'merchant'}
                lookups={{
                    merchants: merchant?.id ? [{ id: merchant.id, name: merchant.name || '', vat: merchant.vat || 0 }] : [],
                    cities,
                    categories,
                    packagings,
                    delivery_types,
                }}
                initialShops={shops}
                weightOptions={weights.length ? weights : null}
                areaOptions={areas}
                deliveryCharge={deliveryCharge}
                charges={{
                    cash,
                    deliveryCharge,
                    codCharge,
                    liquidCharge,
                    packagingCharge: packagingPrice,
                    totalCharge,
                    vat,
                    netPayable,
                    currentPayable,
                }}
                showReference
                showExtraWeight
                showParcelBank
                settings={{
                    currency,
                    vat_tax: merchant?.vat || 0,
                    fragile_liquid_charge: fragile_liquid?.charge || 0,
                    google_maps_key: google_maps_key || '',
                }}
                urls={urls}
                t={t}
                onSubmit={onSubmit}
            />
        </MerchantLayout>
    );
}
