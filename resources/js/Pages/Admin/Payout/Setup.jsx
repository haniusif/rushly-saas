import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { CreditCard, Wallet, Smartphone, ShieldCheck, Save, Eye, EyeOff } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { cn } from '@/lib/utils';

const ICON_MAP = {
    paypal:     Wallet,
    stripe:     CreditCard,
    razorpay:   CreditCard,
    skrill:     Wallet,
    sslcommerz: ShieldCheck,
    aamarpay:   Wallet,
    bkash:      Smartphone,
};

function Toggle({ checked, onChange, label, disabled }) {
    return (
        <label className="inline-flex items-center gap-2 cursor-pointer">
            <button
                type="button"
                role="switch"
                aria-checked={checked}
                aria-label={label}
                disabled={disabled}
                onClick={() => onChange(!checked)}
                className={cn(
                    'relative inline-flex h-5 w-9 items-center rounded-full transition-colors shrink-0',
                    checked ? 'bg-primary' : 'bg-muted-foreground/30',
                    disabled && 'opacity-50 cursor-not-allowed',
                )}
            >
                <span className={cn(
                    'inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow',
                    checked ? 'translate-x-4' : 'translate-x-0.5',
                )} />
            </button>
            <span className="text-sm text-foreground/80">{label}</span>
        </label>
    );
}

/**
 * One gateway card. Each carries its own useForm so gateways submit
 * independently — the original Blade rendered N separate <form> tags
 * pointed at N separate PUT endpoints keyed by the gateway's numeric
 * enum. Preserving that shape avoids one-toggle changes triggering
 * bulk writes.
 */
function GatewayCard({ gateway, permissions, t }) {
    const Icon = ICON_MAP[gateway.icon] || CreditCard;
    const [revealed, setRevealed] = React.useState({});

    // Seed initial form state from the server-provided field.value +
    // switch.checked. Booleans become 1/0 to match what the repo expects.
    const initial = React.useMemo(() => {
        const d = { _method: 'put' };
        gateway.fields.forEach((f) => { d[f.key] = f.value ?? ''; });
        gateway.switches.forEach((s) => { d[s.key] = s.checked ? 1 : 0; });
        return d;
    }, [gateway]);

    const form = useForm(initial);

    // If the server props change (e.g. after a save reloads the page),
    // resync the local form state so revisiting shows the persisted values.
    React.useEffect(() => { form.setData(initial); /* eslint-disable-next-line */ }, [gateway]);

    const statusKey = gateway.switches[gateway.switches.length - 1]?.key;
    const showActive = !!form.data[statusKey];

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(gateway.submit, { preserveScroll: true });
    };

    return (
        <Card>
            <CardContent className="p-0">
                <div className="flex items-center justify-between gap-3 px-5 py-4 border-b border-border">
                    <div className="flex items-center gap-3 min-w-0">
                        <span className={cn('shrink-0 grid h-10 w-10 place-items-center rounded-lg', gateway.tint)}>
                            <Icon className="h-5 w-5" />
                        </span>
                        <div className="min-w-0">
                            <h2 className="text-base font-semibold m-0">{gateway.name}</h2>
                            <p className="text-xs text-muted-foreground mt-0.5 m-0">
                                {gateway.fields.length} {t.fields}
                            </p>
                        </div>
                    </div>
                    <span className={cn(
                        'inline-flex items-center gap-1.5 px-2 py-0.5 text-[10px] font-medium rounded-full shrink-0',
                        showActive
                            ? 'bg-emerald-50 text-emerald-700'
                            : 'bg-slate-100 text-slate-500',
                    )}>
                        <span className={cn('w-1.5 h-1.5 rounded-full', showActive ? 'bg-emerald-500' : 'bg-slate-400')} />
                        {showActive ? t.active : t.inactive}
                    </span>
                </div>

                <form onSubmit={onSubmit}>
                    <div className="p-5 space-y-4">
                        {gateway.fields.map((f) => {
                            const isSecret = f.type === 'password';
                            const shown    = revealed[f.key];
                            return (
                                <div key={f.key} className="space-y-1.5">
                                    <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                        {f.label} <span className="text-destructive">*</span>
                                    </Label>
                                    <div className="relative">
                                        <Input
                                            type={isSecret && !shown ? 'password' : f.type === 'email' ? 'email' : 'text'}
                                            value={form.data[f.key] ?? ''}
                                            onChange={(e) => form.setData(f.key, e.target.value)}
                                            placeholder={f.label}
                                            readOnly={!permissions.update}
                                            className={cn(isSecret && 'pe-9 font-mono text-xs')}
                                            autoComplete="off"
                                            spellCheck={false}
                                        />
                                        {isSecret && (
                                            <button
                                                type="button"
                                                onClick={() => setRevealed((r) => ({ ...r, [f.key]: !r[f.key] }))}
                                                title={shown ? 'Hide' : 'Show'}
                                                className="absolute end-1 top-1/2 -translate-y-1/2 p-1.5 rounded hover:bg-muted text-muted-foreground"
                                                tabIndex={-1}
                                            >
                                                {shown ? <EyeOff className="h-3.5 w-3.5" /> : <Eye className="h-3.5 w-3.5" />}
                                            </button>
                                        )}
                                    </div>
                                    {form.errors[f.key] && <p className="text-xs text-destructive">{form.errors[f.key]}</p>}
                                </div>
                            );
                        })}

                        <div className="flex flex-wrap gap-x-6 gap-y-3 pt-2 border-t border-border">
                            {gateway.switches.map((s) => (
                                <Toggle
                                    key={s.key}
                                    label={s.label}
                                    checked={!!form.data[s.key]}
                                    disabled={!permissions.update}
                                    onChange={(v) => form.setData(s.key, v ? 1 : 0)}
                                />
                            ))}
                        </div>
                    </div>

                    {permissions.update && (
                        <div className="flex items-center justify-end px-5 py-3 border-t border-border bg-muted/20">
                            <Button type="submit" disabled={form.processing}>
                                <Save className="h-4 w-4 me-1" />
                                {form.processing ? '…' : t.save}
                            </Button>
                        </div>
                    )}
                </form>
            </CardContent>
        </Card>
    );
}

export default function Setup({ gateways = [], permissions = {}, t = {} }) {
    return (
        <AdminLayout title={t.title} breadcrumbs={[t.settings, t.pay_out]}>
            <Head title={t.title} />
            <div className="mb-5">
                <h1 className="text-xl font-semibold m-0">{t.title}</h1>
                <p className="text-sm text-muted-foreground mt-1 m-0">{t.subtitle}</p>
            </div>
            <div className="grid gap-4 lg:grid-cols-2">
                {gateways.map((g) => (
                    <GatewayCard
                        key={g.key}
                        gateway={g}
                        permissions={permissions}
                        t={t}
                    />
                ))}
            </div>
        </AdminLayout>
    );
}
