import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { MessageCircle, Save, AlertCircle } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { cn } from '@/lib/utils';

function Field({ label, required, error, children }) {
    return (
        <div className="space-y-1.5">
            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {label} {required && <span className="text-destructive">*</span>}
            </Label>
            {children}
            {error && <p className="text-xs text-destructive flex items-center gap-1"><AlertCircle className="h-3 w-3" /> {error}</p>}
        </div>
    );
}

function ToggleSwitch({ active, onChange }) {
    return (
        <button
            type="button"
            onClick={() => onChange(!active)}
            role="switch"
            aria-checked={active}
            className={cn(
                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors shrink-0 cursor-pointer',
                active ? 'bg-primary' : 'bg-muted-foreground/30'
            )}
        >
            <span className={cn(
                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow',
                active ? 'translate-x-6' : 'translate-x-1'
            )} />
        </button>
    );
}

function ProviderCard({ name, provider, submitUrl, statusKey, canEdit, t, fields, help }) {
    // Build form data: provider fields + the status toggle field + smsMethod marker.
    // Status field uses the legacy HTML-checkbox convention ('on' = checked,
    // anything else = unchecked) because SmsSettingRepository::update() does
    // `$request->{x}_status == 'on' ? Status::ACTIVE : Status::INACTIVE`.
    const initial = { ...provider.fields, [statusKey]: provider.active ? 'on' : 'off', smsMethod: provider.method, _method: 'put' };
    const form = useForm(initial);

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(submitUrl, { preserveScroll: true });
    };

    return (
        <Card>
            <CardContent className="p-6">
                <div className="mb-4 flex items-center gap-2">
                    <MessageCircle className="h-5 w-5 text-primary" />
                    <h3 className="text-base font-semibold">{name}</h3>
                </div>
                {help && <p className="text-[11px] text-muted-foreground -mt-2 mb-3">{help}</p>}
                <form onSubmit={onSubmit} className="space-y-4">
                    {fields.map((f) => (
                        <Field key={f.name} label={t[f.labelKey]} required={f.required} error={form.errors[f.name]}>
                            <Input
                                type={f.type || 'text'}
                                value={form.data[f.name] || ''}
                                onChange={(e) => form.setData(f.name, e.target.value)}
                                placeholder={f.placeholderKey ? t[f.placeholderKey] : t[f.labelKey]}
                                autoComplete="off"
                            />
                        </Field>
                    ))}
                    <div className="flex items-center justify-between border-t border-border pt-3">
                        <div className="flex items-center gap-3">
                            <span className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.status}</span>
                            <ToggleSwitch
                                active={form.data[statusKey] === 'on'}
                                onChange={(v) => form.setData(statusKey, v ? 'on' : 'off')}
                            />
                        </div>
                        {canEdit && (
                            <Button type="submit" disabled={form.processing}><Save className="h-4 w-4 me-1" /> {t.save}</Button>
                        )}
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

export default function Index({ providers = {}, permissions = {}, urls = {}, t = {} }) {
    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title, t.list]}>
            <Head title={t.title} />
            <div className="grid gap-5 md:grid-cols-2">
                <ProviderCard
                    name={providers.reve?.name}
                    provider={providers.reve}
                    submitUrl={urls.submit_reve}
                    statusKey="reve_status"
                    canEdit={permissions.update}
                    t={t}
                    fields={[
                        { name: 'reve_api_key',       labelKey: 'api_key',       placeholderKey: 'ph_api_key',       required: true },
                        { name: 'reve_secret_key',    labelKey: 'secret_key',    placeholderKey: 'ph_secret_key',    required: true },
                        { name: 'reve_api_url',       labelKey: 'api_url',       placeholderKey: 'ph_api_url',       required: true },
                        { name: 'reve_username',      labelKey: 'username',      placeholderKey: 'ph_username' },
                        { name: 'reve_user_password', labelKey: 'user_password', placeholderKey: 'ph_user_password' },
                    ]}
                />
                <ProviderCard
                    name={providers.twilio?.name}
                    provider={providers.twilio}
                    submitUrl={urls.submit_twilio}
                    statusKey="twilio_status"
                    canEdit={permissions.update}
                    t={t}
                    fields={[
                        { name: 'twilio_sid',   labelKey: 'twilio_sid',   required: true },
                        { name: 'twilio_token', labelKey: 'twilio_token', required: true },
                        { name: 'twilio_from',  labelKey: 'twilio_from',  required: true },
                    ]}
                />
                <ProviderCard
                    name={providers.nexmo?.name}
                    provider={providers.nexmo}
                    submitUrl={urls.submit_nexmo}
                    statusKey="nexmo_status"
                    canEdit={permissions.update}
                    t={t}
                    fields={[
                        { name: 'nexmo_key',        labelKey: 'nexmo_key',        required: true },
                        { name: 'nexmo_secret_key', labelKey: 'nexmo_secret_key', required: true },
                    ]}
                />
                <ProviderCard
                    name={providers.msegat?.name}
                    provider={providers.msegat}
                    submitUrl={urls.submit_msegat}
                    statusKey="msegat_status"
                    canEdit={permissions.update}
                    t={t}
                    help={t.msegat_help}
                    fields={[
                        { name: 'msegat_user_name', labelKey: 'msegat_user_name', placeholderKey: 'ph_msegat_user_name', required: true },
                        { name: 'msegat_api_key',   labelKey: 'msegat_api_key',   placeholderKey: 'ph_msegat_api_key',   required: true },
                        { name: 'msegat_sender',    labelKey: 'msegat_sender',    placeholderKey: 'ph_msegat_sender',    required: true },
                    ]}
                />
                <ProviderCard
                    name={providers.taqnyat?.name}
                    provider={providers.taqnyat}
                    submitUrl={urls.submit_taqnyat}
                    statusKey="taqnyat_status"
                    canEdit={permissions.update}
                    t={t}
                    help={t.taqnyat_help}
                    fields={[
                        { name: 'taqnyat_token',  labelKey: 'taqnyat_token',  placeholderKey: 'ph_taqnyat_token',  required: true },
                        { name: 'taqnyat_sender', labelKey: 'taqnyat_sender', placeholderKey: 'ph_taqnyat_sender', required: true },
                    ]}
                />
            </div>
        </AdminLayout>
    );
}
