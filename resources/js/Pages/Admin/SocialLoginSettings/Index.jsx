import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Save, AlertCircle, Facebook, Chrome, Eye, EyeOff } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { cn } from '@/lib/utils';

function Field({ label, required, error, hint, children }) {
    return (
        <div className="space-y-1.5">
            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
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

function Toggle({ checked, onChange, label, subtle }) {
    return (
        <div className="flex items-center justify-between gap-4 rounded-md border border-border bg-muted/20 px-3 py-2.5">
            <div className="text-sm">
                <div className="font-medium">{label}</div>
                {subtle && <div className="text-[11px] text-muted-foreground">{subtle}</div>}
            </div>
            <button
                type="button"
                onClick={() => onChange(!checked)}
                className={cn(
                    'relative inline-flex h-6 w-11 items-center rounded-full transition-colors shrink-0',
                    checked ? 'bg-primary' : 'bg-muted-foreground/30'
                )}
                aria-pressed={checked}
            >
                <span className={cn(
                    'inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow',
                    checked ? 'translate-x-6' : 'translate-x-1'
                )} />
            </button>
        </div>
    );
}

/**
 * Provider config card — one is rendered per social provider. Each carries
 * its own useForm instance so the Facebook and Google cards can be saved
 * independently (matching the two separate PUT endpoints).
 */
function ProviderCard({
    provider,          // 'facebook' | 'google'
    icon: Icon,
    iconTone,          // Tailwind bg-* text-* pair
    title,
    initial,           // { client_id, client_secret, status }
    submitUrl,
    permissions,
    t,
    idLabel,
    secretLabel,
    hint,
}) {
    const [showSecret, setShowSecret] = React.useState(false);
    const form = useForm({
        [`${provider}_client_id`]:     initial.client_id ?? '',
        [`${provider}_client_secret`]: initial.client_secret ?? '',
        [`${provider}_status`]:        initial.status ? 1 : 0,
        _method: 'put',
    });
    const idKey     = `${provider}_client_id`;
    const secretKey = `${provider}_client_secret`;
    const statusKey = `${provider}_status`;

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(submitUrl, { preserveScroll: true });
    };

    return (
        <Card>
            <CardContent className="p-0">
                <div className="flex items-center gap-3 px-5 py-4 border-b border-border">
                    <span className={cn('shrink-0 grid h-10 w-10 place-items-center rounded-lg', iconTone)}>
                        <Icon className="h-5 w-5" />
                    </span>
                    <div className="min-w-0">
                        <h2 className="text-base font-semibold m-0">{title}</h2>
                        <p className="text-xs text-muted-foreground mt-0.5">{hint}</p>
                    </div>
                    <span className={cn(
                        'ms-auto inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium shrink-0',
                        form.data[statusKey]
                            ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
                            : 'bg-slate-100 text-slate-600 border-slate-200'
                    )}>
                        {form.data[statusKey] ? t.enabled : t.disabled}
                    </span>
                </div>
                <form onSubmit={onSubmit} className="p-5 space-y-4">
                    <Field label={idLabel} required error={form.errors[idKey]}>
                        <Input
                            value={form.data[idKey]}
                            onChange={(e) => form.setData(idKey, e.target.value)}
                            className="font-mono text-xs"
                            autoComplete="off"
                            spellCheck={false}
                        />
                    </Field>
                    <Field label={secretLabel} required error={form.errors[secretKey]}>
                        <div className="relative">
                            <Input
                                type={showSecret ? 'text' : 'password'}
                                value={form.data[secretKey]}
                                onChange={(e) => form.setData(secretKey, e.target.value)}
                                className="pe-9 font-mono text-xs"
                                autoComplete="off"
                                spellCheck={false}
                            />
                            <button
                                type="button"
                                onClick={() => setShowSecret((v) => !v)}
                                title={showSecret ? 'Hide' : 'Show'}
                                className="absolute end-1 top-1/2 -translate-y-1/2 p-1.5 rounded hover:bg-muted text-muted-foreground"
                            >
                                {showSecret ? <EyeOff className="h-3.5 w-3.5" /> : <Eye className="h-3.5 w-3.5" />}
                            </button>
                        </div>
                    </Field>
                    <Toggle
                        label={t.status}
                        subtle={form.data[statusKey] ? 'Sign-in button shown on the login page.' : 'Sign-in button hidden from the login page.'}
                        checked={!!form.data[statusKey]}
                        onChange={(v) => form.setData(statusKey, v ? 1 : 0)}
                    />
                    {permissions.update && (
                        <div className="flex justify-end pt-2 border-t border-border">
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

export default function SocialLoginSettingsIndex({
    facebook = {}, google = {}, permissions = {}, urls = {}, t = {},
}) {
    return (
        <AdminLayout title={t.title}>
            <Head title={t.title} />
            <div className="grid gap-5 lg:grid-cols-2">
                <ProviderCard
                    provider="facebook"
                    icon={Facebook}
                    iconTone="bg-blue-100 text-blue-700"
                    title={t.facebook}
                    hint={t.fb_hint}
                    initial={facebook}
                    submitUrl={urls.submit_facebook}
                    permissions={permissions}
                    t={t}
                    idLabel={t.app_id}
                    secretLabel={t.app_secret}
                />
                <ProviderCard
                    provider="google"
                    icon={Chrome}
                    iconTone="bg-rose-100 text-rose-700"
                    title={t.google}
                    hint={t.google_hint}
                    initial={google}
                    submitUrl={urls.submit_google}
                    permissions={permissions}
                    t={t}
                    idLabel={t.client_id}
                    secretLabel={t.client_secret}
                />
            </div>
        </AdminLayout>
    );
}
