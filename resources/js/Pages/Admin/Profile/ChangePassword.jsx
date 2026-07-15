import * as React from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import { Lock, Eye, EyeOff, ArrowLeft, Save, ShieldCheck } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { cn } from '@/lib/utils';

/**
 * Very small heuristic: no rules service call, just a rough visual signal.
 * Returns { level: 0..3, label, tone } — 0 for empty, 1 weak, 2 ok, 3 strong.
 */
function scorePassword(v = '') {
    if (!v) return { level: 0, tone: 'muted' };
    let score = 0;
    if (v.length >= 6)  score++;
    if (v.length >= 12) score++;
    if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
    if (/\d/.test(v))   score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    if (score <= 2) return { level: 1, tone: 'rose' };
    if (score === 3) return { level: 2, tone: 'amber' };
    return { level: 3, tone: 'emerald' };
}

function PasswordField({ label, name, value, onChange, error, autoFocus, autoComplete }) {
    const [visible, setVisible] = React.useState(false);
    return (
        <div className="space-y-1.5">
            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {label}
            </Label>
            <div className="relative">
                <Input
                    type={visible ? 'text' : 'password'}
                    name={name}
                    value={value}
                    onChange={onChange}
                    autoFocus={autoFocus}
                    autoComplete={autoComplete || 'new-password'}
                    required
                    className="pe-10"
                />
                <button
                    type="button"
                    onClick={() => setVisible((v) => !v)}
                    aria-label={visible ? 'Hide password' : 'Show password'}
                    className="absolute end-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground p-1"
                >
                    {visible ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
            </div>
            {error && <p className="text-xs text-rose-600">{error}</p>}
        </div>
    );
}

function StrengthMeter({ value, t = {} }) {
    const { level, tone } = scorePassword(value);
    if (!value) return null;
    const label = level === 1 ? t.strength_weak : level === 2 ? t.strength_ok : t.strength_strong;
    const bars = [1, 2, 3];
    const toneBar = {
        rose:    'bg-rose-500',
        amber:   'bg-amber-500',
        emerald: 'bg-emerald-500',
    }[tone] || 'bg-muted';
    return (
        <div className="mt-2 flex items-center gap-2">
            <div className="flex gap-1 flex-1">
                {bars.map((b) => (
                    <span
                        key={b}
                        className={cn(
                            'h-1.5 flex-1 rounded-full transition-colors',
                            b <= level ? toneBar : 'bg-muted'
                        )}
                    />
                ))}
            </div>
            <span className={cn(
                'text-[11px] font-semibold',
                tone === 'rose' && 'text-rose-600',
                tone === 'amber' && 'text-amber-600',
                tone === 'emerald' && 'text-emerald-600',
            )}>{label}</span>
        </div>
    );
}

export default function ChangePassword({ user = {}, urls = {}, t = {} }) {
    const form = useForm({
        _method: 'put',
        old_password: '',
        new_password: '',
        confirm_password: '',
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(urls.submit, {
            preserveScroll: true,
            onSuccess: () => form.reset('old_password', 'new_password', 'confirm_password'),
        });
    };

    return (
        <AdminLayout title={t.heading} breadcrumbs={[t.heading]}>
            <Head title={t.title} />

            <div className="max-w-3xl">
                <div className="mb-4">
                    <a
                        href={urls.profile}
                        className="inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground transition-colors"
                    >
                        <ArrowLeft className="h-3.5 w-3.5" />
                        {t.cancel}
                    </a>
                </div>

                <Card className="rounded-xl shadow-sm border border-border">
                    <CardContent className="p-6 space-y-5">
                        <div className="flex items-start gap-3">
                            <span className="inline-grid place-items-center h-10 w-10 rounded-lg bg-primary/10 text-primary shrink-0">
                                <Lock className="h-5 w-5" />
                            </span>
                            <div>
                                <h2 className="text-lg font-semibold">{t.heading}</h2>
                                <p className="text-sm text-muted-foreground mt-0.5">{t.intro}</p>
                            </div>
                        </div>

                        <form onSubmit={submit} className="space-y-4">
                            <PasswordField
                                label={t.old_password}
                                name="old_password"
                                value={form.data.old_password}
                                onChange={(e) => form.setData('old_password', e.target.value)}
                                error={form.errors.old_password}
                                autoFocus
                                autoComplete="current-password"
                            />
                            <div>
                                <PasswordField
                                    label={t.new_password}
                                    name="new_password"
                                    value={form.data.new_password}
                                    onChange={(e) => form.setData('new_password', e.target.value)}
                                    error={form.errors.new_password}
                                />
                                <StrengthMeter value={form.data.new_password} t={t} />
                            </div>
                            <PasswordField
                                label={t.confirm_password}
                                name="confirm_password"
                                value={form.data.confirm_password}
                                onChange={(e) => form.setData('confirm_password', e.target.value)}
                                error={form.errors.confirm_password}
                            />

                            <p className="flex items-start gap-2 text-[11px] text-muted-foreground pt-1">
                                <ShieldCheck className="h-3.5 w-3.5 text-emerald-500 shrink-0 mt-0.5" />
                                <span>{t.requirements}</span>
                            </p>

                            <div className="flex items-center justify-end gap-2 pt-2 border-t border-border">
                                <a
                                    href={urls.cancel}
                                    className="inline-flex h-10 items-center rounded-lg border border-input bg-background px-4 text-sm font-medium hover:bg-accent transition-colors"
                                >
                                    {t.cancel}
                                </a>
                                <Button type="submit" disabled={form.processing}>
                                    <Save className="h-4 w-4 me-1.5" />
                                    {form.processing ? '…' : t.save}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
