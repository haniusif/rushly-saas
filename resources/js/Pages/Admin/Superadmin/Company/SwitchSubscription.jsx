import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Save, CreditCard, ArrowRight } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Label } from '@/Components/ui/Label';
import { cn } from '@/lib/utils';

/**
 * Small page for switching a tenant's subscription plan. Renders each plan
 * as a radio tile with price + duration so the operator can compare
 * side-by-side, and posts back to company.subscription.switch.store with
 * user_id + plan_id.
 */
export default function SwitchSubscription({ user_id, company_name, current_plan, plans = [], currency = '$', urls = {}, t = {} }) {
    const form = useForm({
        user_id,
        plan_id: current_plan ? String(current_plan.id) : (plans[0]?.value ?? ''),
    });

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true });
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.breadcrumb, t.company_list, t.title]}>
            <Head title={t.title} />
            <form onSubmit={onSubmit}>
                <Card>
                    <CardContent className="p-0">
                        <div className="flex items-center gap-3 px-6 py-5 border-b border-border">
                            <span className="shrink-0 grid h-9 w-9 place-items-center rounded-lg bg-primary/10 text-primary">
                                <CreditCard className="h-4 w-4" />
                            </span>
                            <div className="min-w-0">
                                <h2 className="text-base font-semibold m-0">{t.title}</h2>
                                <p className="text-xs text-muted-foreground mt-0.5 truncate">
                                    {t.switching_for} <span className="font-medium text-foreground">{company_name}</span>
                                </p>
                            </div>
                        </div>

                        <div className="p-6 space-y-4">
                            {current_plan && (
                                <div className="flex items-center gap-3 px-4 py-3 bg-primary/5 text-foreground rounded-lg">
                                    <span className="text-xs uppercase tracking-wide text-muted-foreground">{t.current_plan}</span>
                                    <span className="font-semibold">{current_plan.name}</span>
                                    <ArrowRight className="h-4 w-4 text-muted-foreground ms-auto" />
                                </div>
                            )}

                            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                {t.plan}
                            </Label>
                            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                {plans.map((p) => {
                                    const active = form.data.plan_id === p.value;
                                    return (
                                        <label
                                            key={p.value}
                                            className={cn(
                                                'flex items-start gap-3 px-4 py-3 border rounded-lg cursor-pointer transition-colors',
                                                active ? 'border-primary bg-primary/5 ring-2 ring-primary/20' : 'border-border hover:border-primary/60'
                                            )}
                                        >
                                            <input
                                                type="radio"
                                                name="plan_id"
                                                value={p.value}
                                                checked={active}
                                                onChange={() => form.setData('plan_id', p.value)}
                                                className="mt-1 accent-primary"
                                            />
                                            <div className="min-w-0">
                                                <div className="font-medium truncate">{p.label}</div>
                                                <div className="text-[11px] text-muted-foreground mt-0.5">
                                                    <span className="me-1">{currency}</span>
                                                    <span className="font-medium text-foreground tabular-nums">
                                                        {Number(p.price ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                    </span>
                                                    <span className="mx-1.5">·</span>
                                                    {p.days} days
                                                </div>
                                            </div>
                                        </label>
                                    );
                                })}
                            </div>
                            {form.errors.plan_id && (
                                <p className="text-xs text-destructive">{form.errors.plan_id}</p>
                            )}
                        </div>
                    </CardContent>
                </Card>

                <div className="flex items-center justify-end gap-2 mt-4 bg-background border border-border rounded-xl px-6 py-4">
                    <Button variant="outline" asChild>
                        <a href={urls.index}>{t.cancel}</a>
                    </Button>
                    <Button type="submit" disabled={form.processing}>
                        <Save className="h-4 w-4 me-1" />
                        {t.save}
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
