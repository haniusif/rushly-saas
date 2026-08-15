import * as React from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Truck, Save, Loader2, Info, Plus } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import { Label } from '@/Components/ui/Label';
import MerchantSubHeader from '@/Components/merchant/MerchantSubHeader';

const RATE_FIELDS = ['same_day', 'next_day', 'sub_city', 'outside_city'];

/**
 * Create / edit a merchant's delivery-charge rule.
 *
 * Replaces backend/merchant/delivery-charge/{create,edit}.blade.php. The list
 * screen was already Inertia, so opening this form used to drop the admin out
 * of the React shell into the old Bootstrap layout.
 *
 * The Blade version fetched the selected rate card's defaults over AJAX
 * (deliveryChargeInfo -> Blade partial) and injected raw HTML into the form.
 * All rate cards arrive as props here instead, so picking one fills the inputs
 * instantly with no round trip.
 */
export default function Form({
    mode = 'create',
    merchant = {},
    charges = [],
    row = null,
    currency = '',
    urls = {},
    t = {},
}) {
    const isEdit = mode === 'edit';

    const form = useForm({
        delivery_charge_id: row?.delivery_charge_id ?? charges[0]?.id ?? '',
        extra_weight_price: row?.extra_weight_price ?? charges[0]?.extra_weight_price ?? 0,
        same_day:     row?.same_day     ?? charges[0]?.same_day     ?? 0,
        next_day:     row?.next_day     ?? charges[0]?.next_day     ?? 0,
        sub_city:     row?.sub_city     ?? charges[0]?.sub_city     ?? 0,
        outside_city: row?.outside_city ?? charges[0]?.outside_city ?? 0,
        status:       row?.status ?? 1,
    });

    const selected = charges.find((c) => String(c.id) === String(form.data.delivery_charge_id));

    // Picking a different rate card reloads its defaults. On edit the merchant's
    // own saved rates must survive the initial render, so this only fires on an
    // actual change — not on mount.
    const mounted = React.useRef(false);
    React.useEffect(() => {
        if (!mounted.current) { mounted.current = true; return; }
        const c = charges.find((x) => String(x.id) === String(form.data.delivery_charge_id));
        if (!c) return;
        form.setData((d) => ({
            ...d,
            extra_weight_price: c.extra_weight_price ?? 0,
            same_day:     c.same_day     ?? 0,
            next_day:     c.next_day     ?? 0,
            sub_city:     c.sub_city     ?? 0,
            outside_city: c.outside_city ?? 0,
        }));
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [form.data.delivery_charge_id]);

    const submit = (e) => {
        e.preventDefault();
        if (isEdit) form.put(urls.submit);
        else form.post(urls.submit);
    };

    const breadcrumbs = [t.title_index, merchant.business_name, t.title_parent, t.title];

    // No tenant-level rate cards exist, so the select would render empty. Say
    // why and link to where it gets fixed, rather than showing a dead form.
    if (!charges.length) {
        return (
            <AdminLayout title={t.title} breadcrumbs={breadcrumbs}>
                <Head title={`${t.title} · ${merchant.business_name || ''}`} />
                <MerchantSubHeader
                    merchant={merchant}
                    title={t.title}
                    backUrl={urls.index}
                    backLabel={t.back_to_list}
                />
                <Card>
                    <CardContent className="flex flex-col items-center gap-3 py-12 text-center">
                        <div className="grid h-12 w-12 place-items-center rounded-full bg-amber-100 text-amber-600">
                            <Info className="h-6 w-6" />
                        </div>
                        <div className="font-semibold">{t.empty_title}</div>
                        <p className="max-w-md text-sm text-muted-foreground">{t.empty_body}</p>
                        <div className="mt-2 flex flex-wrap items-center justify-center gap-2">
                            <a
                                href={urls.charge_create}
                                className="inline-flex h-9 items-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90"
                            >
                                <Plus className="h-4 w-4 me-1" /> {t.empty_cta}
                            </a>
                            <a
                                href={urls.charge_index}
                                className="inline-flex h-9 items-center rounded-md border border-border px-3 text-sm font-medium hover:bg-accent"
                            >
                                {t.empty_view_all}
                            </a>
                            <a href={urls.index} className="inline-flex h-9 items-center px-3 text-sm text-muted-foreground hover:underline">
                                {t.cancel}
                            </a>
                        </div>
                    </CardContent>
                </Card>
            </AdminLayout>
        );
    }

    return (
        <AdminLayout title={t.title} breadcrumbs={breadcrumbs}>
            <Head title={`${t.title} · ${merchant.business_name || ''}`} />

            <MerchantSubHeader
                merchant={merchant}
                title={t.title}
                backUrl={urls.index}
                backLabel={t.back_to_list}
            />

            <form onSubmit={submit}>
                <Card>
                    <CardContent className="space-y-6 p-5">
                        {/* ---------- rate card ---------- */}
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="delivery_charge_id">
                                    {t.category} <span className="text-rose-500">*</span>
                                </Label>
                                <Select
                                    id="delivery_charge_id"
                                    value={form.data.delivery_charge_id}
                                    onChange={(e) => form.setData('delivery_charge_id', e.target.value)}
                                >
                                    {charges.map((c) => (
                                        <option key={c.id} value={c.id}>
                                            {c.category}{c.weight ? ` (${c.weight})` : ''}
                                        </option>
                                    ))}
                                </Select>
                                {form.errors.delivery_charge_id && (
                                    <p className="text-xs text-rose-600">{form.errors.delivery_charge_id}</p>
                                )}
                                <p className="text-xs text-muted-foreground">{t.category_hint}</p>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="weight">{t.weight}</Label>
                                <Input
                                    id="weight"
                                    type="number"
                                    value={selected?.weight ?? row?.weight ?? 0}
                                    disabled
                                    readOnly
                                />
                                <p className="text-xs text-muted-foreground">{t.weight_hint}</p>
                            </div>
                        </div>

                        {/* ---------- rates ---------- */}
                        <div className="space-y-3 border-t border-border pt-5">
                            <div className="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                <Truck className="h-3.5 w-3.5" /> {t.rates_section}
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {RATE_FIELDS.map((f) => (
                                    <div key={f} className="space-y-1.5">
                                        <Label htmlFor={f}>
                                            {t[f]} <span className="text-rose-500">*</span>
                                        </Label>
                                        <div className="relative">
                                            <Input
                                                id={f}
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                className="pe-12"
                                                value={form.data[f]}
                                                onChange={(e) => form.setData(f, e.target.value)}
                                            />
                                            <span className="pointer-events-none absolute inset-y-0 end-3 flex items-center text-xs text-muted-foreground">
                                                {currency}
                                            </span>
                                        </div>
                                        {form.errors[f] && <p className="text-xs text-rose-600">{form.errors[f]}</p>}
                                    </div>
                                ))}

                                <div className="space-y-1.5">
                                    <Label htmlFor="extra_weight_price">{t.extra_weight}</Label>
                                    <div className="relative">
                                        <Input
                                            id="extra_weight_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            className="pe-12"
                                            value={form.data.extra_weight_price}
                                            onChange={(e) => form.setData('extra_weight_price', e.target.value)}
                                        />
                                        <span className="pointer-events-none absolute inset-y-0 end-3 flex items-center text-xs text-muted-foreground">
                                            {currency}
                                        </span>
                                    </div>
                                    {form.errors.extra_weight_price && (
                                        <p className="text-xs text-rose-600">{form.errors.extra_weight_price}</p>
                                    )}
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="status">
                                        {t.status} <span className="text-rose-500">*</span>
                                    </Label>
                                    <Select
                                        id="status"
                                        value={form.data.status}
                                        onChange={(e) => form.setData('status', e.target.value)}
                                    >
                                        <option value={1}>{t.active}</option>
                                        <option value={0}>{t.inactive}</option>
                                    </Select>
                                    {form.errors.status && <p className="text-xs text-rose-600">{form.errors.status}</p>}
                                </div>
                            </div>
                        </div>

                        {/* ---------- actions ---------- */}
                        <div className="flex items-center justify-end gap-2 border-t border-border pt-4">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => router.visit(urls.index)}
                                disabled={form.processing}
                            >
                                {t.cancel}
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {form.processing
                                    ? <><Loader2 className="h-4 w-4 me-1 animate-spin" /> {t.saving}</>
                                    : <><Save className="h-4 w-4 me-1" /> {t.save}</>}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </AdminLayout>
    );
}
