import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Building2, ChevronDown, X } from 'lucide-react';
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

// Lightweight typeahead — currency lists can be long (100+ rows) and a
// native <select> forces a linear scroll. Filters options as the user
// types across name / code / symbol. No extra deps.
function SearchableCurrencySelect({ currencies, value, onChange, invalid }) {
    const [query, setQuery] = React.useState('');
    const [open, setOpen] = React.useState(false);
    const wrapRef = React.useRef(null);

    const optionValue = (c) => c.code || c.symbol || c.name;
    const optionLabel = (c) => `${c.name}${c.code ? ` (${c.code})` : ''}`;

    const selected = React.useMemo(
        () => currencies.find((c) => optionValue(c) === value),
        [currencies, value],
    );

    const filtered = React.useMemo(() => {
        const q = query.trim().toLowerCase();
        if (!q) return currencies;
        return currencies.filter((c) => {
            const hay = `${c.name || ''} ${c.code || ''} ${c.symbol || ''}`.toLowerCase();
            return hay.includes(q);
        });
    }, [currencies, query]);

    React.useEffect(() => {
        const handler = (e) => { if (wrapRef.current && !wrapRef.current.contains(e.target)) setOpen(false); };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, []);

    const pick = (c) => { onChange(optionValue(c)); setQuery(''); setOpen(false); };
    const clear = (e) => { e.stopPropagation(); onChange(''); setQuery(''); };

    return (
        <div ref={wrapRef} className="relative">
            <div
                onClick={() => setOpen(true)}
                className={`flex h-9 items-center gap-1 rounded-md border px-3 text-sm cursor-text ${invalid ? 'border-rose-300' : 'border-input'} bg-background`}
            >
                {open ? (
                    <input
                        autoFocus
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder={selected ? optionLabel(selected) : 'Search currency…'}
                        className="flex-1 bg-transparent outline-none"
                    />
                ) : (
                    <span className={`flex-1 truncate ${selected ? '' : 'text-muted-foreground'}`}>
                        {selected ? optionLabel(selected) : '—'}
                    </span>
                )}
                {selected && (
                    <button type="button" onClick={clear} className="p-0.5 text-muted-foreground hover:text-foreground" title="Clear">
                        <X className="h-3.5 w-3.5" />
                    </button>
                )}
                <ChevronDown className="h-4 w-4 text-muted-foreground" />
            </div>
            {open && (
                <div className="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-md border border-input bg-popover shadow-md">
                    {filtered.length === 0 && (
                        <div className="px-3 py-2 text-xs text-muted-foreground">No matches</div>
                    )}
                    {filtered.map((c) => {
                        const v = optionValue(c);
                        const isSel = v === value;
                        return (
                            <button
                                type="button"
                                key={c.id ?? v}
                                onClick={() => pick(c)}
                                className={`block w-full truncate px-3 py-1.5 text-start text-sm hover:bg-muted ${isSel ? 'bg-muted/70 font-medium' : ''}`}
                            >
                                {optionLabel(c)}
                            </button>
                        );
                    })}
                </div>
            )}
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
                                <SearchableCurrencySelect
                                    currencies={currencies}
                                    value={data.currency}
                                    onChange={(v) => setData('currency', v)}
                                    invalid={!!errors.currency}
                                />
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
