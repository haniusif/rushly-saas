import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Monitor, Smartphone, ShieldAlert, X, LogOut } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { cn } from '@/lib/utils';

function DeviceIcon({ platform, browser }) {
    const isMobile = platform === 'iOS' || platform === 'Android';
    const Icon = isMobile ? Smartphone : Monitor;
    return (
        <span className="inline-grid place-items-center h-10 w-10 rounded-lg bg-muted text-muted-foreground shrink-0">
            <Icon className="h-5 w-5" />
        </span>
    );
}

function timeAgo(iso) {
    if (!iso) return '';
    const then = new Date(iso).getTime();
    const secs = Math.floor((Date.now() - then) / 1000);
    if (secs < 60)  return `${secs}s ago`;
    if (secs < 3600) return `${Math.floor(secs / 60)}m ago`;
    if (secs < 86400) return `${Math.floor(secs / 3600)}h ago`;
    return `${Math.floor(secs / 86400)}d ago`;
}

export default function Index({ sessions = [], t = {} }) {
    const [confirmOpen, setConfirmOpen] = React.useState(false);
    const form = useForm({ password: '' });

    const submit = (e) => {
        e.preventDefault();
        form.delete(route('browser-sessions.destroy'), {
            preserveScroll: true,
            onSuccess: () => { setConfirmOpen(false); form.reset(); },
        });
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title]}>
            <Head title={t.title} />

            <div className="max-w-3xl">
                <Card className="rounded-xl shadow-sm border border-border">
                    <CardContent className="p-6 space-y-5">
                        <div>
                            <h2 className="text-lg font-semibold flex items-center gap-2">
                                <ShieldAlert className="h-5 w-5 text-primary" />
                                {t.title}
                            </h2>
                            <p className="mt-2 text-sm text-muted-foreground">
                                {t.intro}
                            </p>
                            <p className="mt-2 text-xs text-muted-foreground leading-relaxed">
                                {t.note}
                            </p>
                        </div>

                        <div className="divide-y divide-border rounded-lg border border-border">
                            {sessions.length === 0 ? (
                                <div className="px-4 py-6 text-sm text-muted-foreground text-center">
                                    (no sessions to display)
                                </div>
                            ) : sessions.map((s) => (
                                <div key={s.id} className="flex items-start gap-3 px-4 py-3">
                                    <DeviceIcon platform={s.platform} browser={s.browser} />
                                    <div className="flex-1 min-w-0">
                                        <div className="text-sm font-medium">
                                            {s.platform} · {s.browser}
                                        </div>
                                        <div className="text-xs text-muted-foreground mt-0.5 flex flex-wrap gap-x-2 gap-y-0.5">
                                            <span className="font-mono">{s.ip}</span>
                                            {s.is_current ? (
                                                <span className="inline-flex items-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">
                                                    {t.this_device}
                                                </span>
                                            ) : (
                                                <span>{t.last_active}: {timeAgo(s.last_activity_iso)}</span>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="flex flex-wrap items-center justify-end gap-2 pt-2">
                            <Button type="button" onClick={() => setConfirmOpen(true)}>
                                <LogOut className="h-4 w-4 me-1.5" />
                                {t.logout_others}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Password confirm modal */}
            {confirmOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-xl bg-background border border-border shadow-2xl">
                        <form onSubmit={submit}>
                            <div className="p-6 space-y-4">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 className="text-base font-semibold">{t.logout_others}</h3>
                                        <p className="mt-1.5 text-sm text-muted-foreground">{t.confirm_password}</p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => { setConfirmOpen(false); form.reset(); }}
                                        className="text-muted-foreground hover:text-foreground"
                                    >
                                        <X className="h-4 w-4" />
                                    </button>
                                </div>
                                <div>
                                    <Label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                        {t.password_placeholder}
                                    </Label>
                                    <Input
                                        type="password"
                                        value={form.data.password}
                                        onChange={(e) => form.setData('password', e.target.value)}
                                        autoFocus
                                        required
                                        className="mt-1.5"
                                    />
                                    {form.errors.password && (
                                        <p className="text-xs text-rose-600 mt-1">{form.errors.password}</p>
                                    )}
                                </div>
                            </div>
                            <div className="flex items-center justify-end gap-2 px-6 py-4 border-t border-border bg-muted/20 rounded-b-xl">
                                <Button type="button" variant="ghost" onClick={() => { setConfirmOpen(false); form.reset(); }}>
                                    {t.cancel}
                                </Button>
                                <Button type="submit" disabled={form.processing}>
                                    {t.confirm}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
