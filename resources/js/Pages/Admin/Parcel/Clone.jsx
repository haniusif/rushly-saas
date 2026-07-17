import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import ParcelForm from '@/Components/parcel/ParcelForm';

export default function Clone({
    parcel = {},
    merchants = [], cities = [], categories = [], packagings = [], delivery_types = [],
    settings = {}, urls = {}, t = {},
    initial_shops = [],
}) {
    const form = useForm({
        // parcel_id tells duplicateStore() which source parcel this clones from.
        parcel_id:        parcel.id ?? '',
        merchant_id:      String(parcel.merchant_id ?? ''),
        shop_id:          String(parcel.shop_id ?? ''),
        pickup_phone:     parcel.pickup_phone ?? '',
        pickup_address:   parcel.pickup_address ?? '',
        cash_collection:  parcel.cash_collection ?? '',
        selling_price:    parcel.selling_price ?? '',
        invoice_no:       parcel.invoice_no ?? '',
        category_id:      String(parcel.category_id ?? ''),
        weight:           parcel.weight ?? '',
        delivery_type_id: String(parcel.delivery_type_id ?? ''),
        customer_name:    parcel.customer_name ?? '',
        customer_phone:   parcel.customer_phone ?? '',
        city_id:          String(parcel.city_id ?? ''),
        area_id:          String(parcel.area_id ?? ''),
        customer_address: parcel.customer_address ?? '',
        note:             parcel.note ?? '',
        packaging_id:     String(parcel.packaging_id ?? ''),
        priority_id:      String(parcel.priority_type_id ?? '2'),
        fragileLiquid:    !!parcel.liquid_fragile_amount,
        cod_charge:       parcel.cod_charge ?? 0,
        vat_tex:          parcel.vat ?? settings.vat_tax ?? 0,
        chargeDetails:    '',
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(urls.store, { forceFormData: true, preserveScroll: true });
    };

    const title = (t.clone || 'Duplicate parcel') + (parcel.tracking_id ? ` · ${parcel.tracking_id}` : '');

    return (
        <AdminLayout title={title}>
            <Head title={title} />
            <ParcelForm
                form={form}
                mode="create"
                lookups={{ merchants, cities, categories, packagings, delivery_types }}
                settings={settings}
                urls={urls}
                t={t}
                initialShops={initial_shops}
                onSubmit={submit}
            />
        </AdminLayout>
    );
}
