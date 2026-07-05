import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';

function FormField({ label, error, required, children }) {
    return (
        <div>
            <Label className="text-[12px] font-medium">
                {label}{required && <span className="text-rose-500"> *</span>}
            </Label>
            <div className="mt-1.5">{children}</div>
            {error && <p className="mt-1 text-[11px] text-rose-600">{error}</p>}
        </div>
    );
}

export default function Create({ plans = [], currencies = [], defaultCurrency = '', appHost = '', urls = {}, labels = {} }) {
    const { data, setData, post, processing, errors } = useForm({
        company_name: '',
        domain: '',
        currency: defaultCurrency || '',
        plan_id: plans[0]?.id ?? '',
        address: '',
        name: '',
        email: '',
        password: '',
        mobile: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(urls.submit);
    };

    const selectedPlan = plans.find((p) => Number(p.id) === Number(data.plan_id));

    return (
        <AdminLayout title={labels.title} breadcrumbs={[labels.title]}>
            <Head title={labels.title} />

            <form onSubmit={submit} className="space-y-5">
                {/* Company card */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="mb-4 flex items-center gap-2">
                            <Building2 className="h-5 w-5" />
                            <h2 className="text-base font-semibold">{labels.title}</h2>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <FormField label={labels.company_name} error={errors.company_name} required>
                                <Input value={data.company_name} onChange={(e) => setData('company_name', e.target.value)} />
                            </FormField>

                            <FormField label={labels.domain} error={errors.domain} required>
                                <div className="flex items-center gap-2">
                                    <Input value={data.domain} onChange={(e) => setData('domain', e.target.value)} placeholder="acme" />
                                    <span className="text-xs text-muted-foreground whitespace-nowrap">.{appHost}</span>
                                </div>
                            </FormField>

                            <FormField label={labels.currency} error={errors.currency} required>
                                <select
                                    value={data.currency}
                                    onChange={(e) => setData('currency', e.target.value)}
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                                >
                                    <option value="">—</option>
                                    {currencies.map((c) => (
                                        <option key={c.id ?? c.code} value={c.code || c.symbol || c.name}>
                                            {c.name} {c.code ? `(${c.code})` : ''}
                                        </option>
                                    ))}
                                </select>
                            </FormField>

                            <FormField label={labels.plan} error={errors.plan_id} required>
                                <select
                                    value={data.plan_id}
                                    onChange={(e) => setData('plan_id', e.target.value)}
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                                >
                                    {plans.map((p) => (
                                        <option key={p.id} value={p.id}>{p.name}</option>
                                    ))}
                                </select>
                                {selectedPlan && (
                                    <p className="mt-1 text-[11px] text-muted-foreground">
                                        {selectedPlan.user_count ? `${selectedPlan.user_count} users · ` : ''}
                                        {selectedPlan.deliveryman_count ? `${selectedPlan.deliveryman_count} drivers · ` : ''}
                                        {selectedPlan.parcel_count ? `${selectedPlan.parcel_count} parcels · ` : ''}
                                        {selectedPlan.days_count} days
                                    </p>
                                )}
                            </FormField>

                            <div className="md:col-span-2">
                                <FormField label={labels.address} error={errors.address}>
                                    <Input value={data.address} onChange={(e) => setData('address', e.target.value)} />
                                </FormField>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Owner card */}
                <Card>
                    <CardContent className="pt-6">
                        <h2 className="mb-4 text-base font-semibold">{labels.owner}</h2>
                        <div className="grid gap-4 md:grid-cols-2">
                            <FormField label={labels.name} error={errors.name} required>
                                <Input value={data.name} onChange={(e) => setData('name', e.target.value)} />
                            </FormField>
                            <FormField label={labels.email} error={errors.email} required>
                                <Input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                            </FormField>
                            <FormField label={labels.password} error={errors.password} required>
                                <Input type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} />
                            </FormField>
                            <FormField label={labels.mobile} error={errors.mobile} required>
                                <Input value={data.mobile} onChange={(e) => setData('mobile', e.target.value)} />
                            </FormField>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex items-center justify-end gap-2">
                    <a href={urls.index} className="inline-flex h-9 items-center rounded-md border border-input px-3 text-sm hover:bg-muted">
                        {labels.cancel}
                    </a>
                    <Button type="submit" disabled={processing}>
                        {labels.submit}
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
