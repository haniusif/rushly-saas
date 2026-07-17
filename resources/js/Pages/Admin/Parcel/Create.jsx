import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import ParcelForm from '@/Components/parcel/ParcelForm';

export default function Create({
    merchants = [], cities = [], categories = [], packagings = [], delivery_types = [],
    settings = {}, urls = {}, t = {},
}) {
    const form = useForm({
        merchant_id: '', shop_id: '', pickup_phone: '', pickup_address: '',
        pickup_lat: '', pickup_long: '',
        cash_collection: '', selling_price: '', invoice_no: '',
        category_id: '', weight: '', delivery_type_id: '',
        customer_name: '', customer_phone: '',
        city_id: '', area_id: '', customer_address: '',
        lat: '', long: '',
        note: '', packaging_id: '', priority_id: '2', fragileLiquid: false,
        cod_charge: 0, vat_tex: settings.vat_tax || 0, chargeDetails: '',
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(urls.store, { forceFormData: true, preserveScroll: true });
    };

    return (
        <AdminLayout title={t.create}>
            <Head title={t.create} />
            <ParcelForm
                form={form}
                mode="create"
                lookups={{ merchants, cities, categories, packagings, delivery_types }}
                settings={settings}
                urls={urls}
                t={t}
                onSubmit={submit}
            />
        </AdminLayout>
    );
}
