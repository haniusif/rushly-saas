import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Save, AlertCircle, Bell, Eye, EyeOff } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';

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

export default function NotificationSettingsIndex({
    settings = {}, permissions = {}, urls = {}, t = {},
}) {
    const [showSecret, setShowSecret] = React.useState(false);
    const form = useForm({
        fcm_secret_key: settings.fcm_secret_key ?? '',
        fcm_topic:      settings.fcm_topic ?? '',
        _method:        'put',
    });
    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true });
    };
    return (
        <AdminLayout title={t.title}>
            <Head title={t.title} />
            <form onSubmit={onSubmit} className="space-y-4">
                <Card>
                    <CardContent className="p-0">
                        <div className="flex items-center gap-3 px-6 py-5 border-b border-border">
                            <span className="shrink-0 grid h-9 w-9 place-items-center rounded-lg bg-primary/10 text-primary">
                                <Bell className="h-4 w-4" />
                            </span>
                            <div className="min-w-0">
                                <h2 className="text-base font-semibold m-0">Firebase Cloud Messaging</h2>
                                <p className="text-xs text-muted-foreground mt-0.5">Server key + topic used to broadcast to subscribed devices.</p>
                            </div>
                        </div>
                        <div className="grid gap-5 md:grid-cols-2 p-6">
                            <Field label={t.fcm_secret_key} required hint={t.fcm_secret_hint} error={form.errors.fcm_secret_key}>
                                {/* Password-style masked input with an eye toggle so the
                                    operator can peek without pasting the secret elsewhere. */}
                                <div className="relative">
                                    <Input
                                        type={showSecret ? 'text' : 'password'}
                                        value={form.data.fcm_secret_key}
                                        onChange={(e) => form.setData('fcm_secret_key', e.target.value)}
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
                            <Field label={t.fcm_topic} required hint={t.fcm_topic_hint} error={form.errors.fcm_topic}>
                                <Input
                                    value={form.data.fcm_topic}
                                    onChange={(e) => form.setData('fcm_topic', e.target.value)}
                                    className="font-mono text-xs"
                                    autoComplete="off"
                                    spellCheck={false}
                                />
                            </Field>
                        </div>
                    </CardContent>
                </Card>

                {permissions.update && (
                    <div className="flex items-center justify-end gap-2 bg-background border border-border rounded-xl px-6 py-4">
                        <Button type="submit" disabled={form.processing}>
                            <Save className="h-4 w-4 me-1" />
                            {form.processing ? '…' : t.save}
                        </Button>
                    </div>
                )}
            </form>
        </AdminLayout>
    );
}
