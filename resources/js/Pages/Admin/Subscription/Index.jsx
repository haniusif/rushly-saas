import * as React from 'react';
import { Head } from '@inertiajs/react';
import { Check, X, Crown, ChevronDown } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';

function Money({ value, currency }) {
    return <span className="tabular-nums">{currency ? `${currency} ` : ''}{Number(value || 0).toFixed(2)}</span>;
}

function PlanCard({ plan: p, stripe_on, currency, urls, t, onContact }) {
    const [open, setOpen] = React.useState(false);
    const includedCount = p.modules.filter((m) => m.included).length;

    return (
        <Card className="flex flex-col">
            <CardContent className="p-6 flex flex-col h-full">
                <div className="flex items-center justify-center mb-3">
                    <Crown className="h-8 w-8 text-amber-500" />
                </div>
                <h3 className="text-center text-xl font-semibold">{p.name}</h3>
                <p className="text-center text-sm text-muted-foreground mt-2">{p.description}</p>
                <div className="flex justify-center items-baseline gap-2 my-6">
                    <span className="text-3xl font-bold"><Money value={p.price} currency={currency} /></span>
                    <span className="text-sm text-muted-foreground">/ {p.intval_name}</span>
                </div>
                <p className="text-center text-xs text-muted-foreground -mt-4 mb-4">{t.when_billed}</p>

                <ul className="space-y-2 flex-1">
                    <li className="flex items-center gap-2 text-sm">
                        <Check className="h-4 w-4 text-emerald-600 shrink-0" />
                        <span>{t.parcel_count} {p.parcel_count}</span>
                    </li>
                </ul>

                <button
                    type="button"
                    onClick={() => setOpen((v) => !v)}
                    aria-expanded={open}
                    className="mt-3 inline-flex items-center justify-between gap-2 w-full rounded-md border border-border bg-muted/40 hover:bg-muted px-3 py-2 text-sm font-medium text-foreground transition-colors"
                >
                    <span>
                        {includedCount} / {p.modules.length} {t.modules || 'modules'}
                    </span>
                    <ChevronDown className={`h-4 w-4 transition-transform ${open ? 'rotate-180' : ''}`} />
                </button>
                <ul
                    className={`space-y-2 overflow-hidden transition-all ${open ? 'mt-3 max-h-[1000px] opacity-100' : 'mt-0 max-h-0 opacity-0'}`}
                    aria-hidden={!open}
                >
                    {p.modules.map((m) => (
                        <li key={m.key} className="flex items-center gap-2 text-sm">
                            {m.included
                                ? <Check className="h-4 w-4 text-emerald-600 shrink-0" />
                                : <X className="h-4 w-4 text-rose-500 shrink-0" />}
                            <span className={m.included ? '' : 'text-muted-foreground line-through'}>{m.label}</span>
                        </li>
                    ))}
                </ul>

                <div className="mt-6 pt-4 border-t border-border text-center">
                    {p.is_current && p.remaining_days && (
                        <div className="mb-2">
                            <span className="text-emerald-600 font-medium">{t.active}</span><br />
                            <span className="text-xs text-muted-foreground">{t.remaining} {p.remaining_days} {t.days}</span>
                        </div>
                    )}
                    {p.is_current && !p.remaining_days && (
                        <div className="mb-2">
                            <span className="inline-flex items-center rounded-full border border-rose-200 bg-rose-100 text-rose-700 px-2 py-0.5 text-xs font-medium">{t.expired}</span>
                        </div>
                    )}
                    {stripe_on ? (
                        <a href={`${urls.pay_base}?plan_id=${p.id}`} className="inline-flex h-10 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90 w-full">{t.subscribe}</a>
                    ) : (
                        <Button className="w-full" onClick={onContact}>{t.subscribe}</Button>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

export default function Index({ plans = [], stripe_on = false, admin = {}, currency = '', urls = {}, t = {} }) {
    const [contactOpen, setContactOpen] = React.useState(false);

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title]}>
            <Head title={t.title} />
            <h2 className="text-center text-2xl font-bold mt-4 mb-6">{t.pick}</h2>
            <div className="grid gap-5 lg:grid-cols-3">
                {plans.map((p) => (
                    <PlanCard
                        key={p.id}
                        plan={p}
                        stripe_on={stripe_on}
                        currency={currency}
                        urls={urls}
                        t={t}
                        onContact={() => setContactOpen(true)}
                    />
                ))}
            </div>

            {contactOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" onClick={() => setContactOpen(false)}>
                    <Card className="w-full max-w-md" onClick={(e) => e.stopPropagation()}>
                        <CardContent className="p-6">
                            <h3 className="text-lg font-semibold mb-4">{t.contact_title}</h3>
                            <p className="text-sm mb-2">{t.contact_name}: {admin.name}</p>
                            <p className="text-sm mb-2">{t.contact_email}: {admin.email}</p>
                            <p className="text-sm mb-4">{t.contact_phone}: {admin.phone}</p>
                            <Button variant="outline" className="w-full" onClick={() => setContactOpen(false)}>Close</Button>
                        </CardContent>
                    </Card>
                </div>
            )}
        </AdminLayout>
    );
}
