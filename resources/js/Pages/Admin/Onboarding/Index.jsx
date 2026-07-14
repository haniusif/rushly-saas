import * as React from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { Check, ChevronRight, ExternalLink, SkipForward } from 'lucide-react';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';
import { Textarea } from '@/Components/ui/Textarea';
import { cn } from '@/lib/utils';

// Ordering + descriptors must mirror OnboardingWizardController::STEPS.
const STEPS = ['basics', 'delivery_category', 'delivery_charge', 'delivery_type', 'sms', 'google_maps'];

function StepBadge({ done, active, i }) {
    return (
        <span className={cn(
            'inline-grid place-items-center h-6 w-6 rounded-full text-xs font-semibold',
            done ? 'bg-primary/15 text-primary' : active ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'
        )}>
            {done ? <Check className="h-3.5 w-3.5" /> : i + 1}
        </span>
    );
}

function Field({ label, required, error, hint, children }) {
    return (
        <div className="space-y-1.5">
            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {label} {required && <span className="text-destructive">*</span>}
            </Label>
            {children}
            {hint && <p className="text-[11px] text-muted-foreground">{hint}</p>}
            {error && <p className="text-xs text-destructive">{error}</p>}
        </div>
    );
}

function LinkOutStep({ hint, url, label }) {
    return (
        <div className="rounded-lg border border-border bg-muted/30 p-4">
            <p className="text-sm text-muted-foreground mb-3">{hint}</p>
            <a
                href={url}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-2 rounded-md border border-input bg-background px-4 py-2 text-sm font-medium hover:bg-muted transition-colors"
            >
                <ExternalLink className="h-4 w-4" />
                {label}
            </a>
        </div>
    );
}

export default function Index({ settings = {}, lookups = {}, urls = {}, t = {} }) {
    const [active, setActive] = React.useState(0);
    const [seen, setSeen] = React.useState(() => new Set());

    // Single form drives every step; each step submits only the fields it touched.
    const form = useForm({
        step: STEPS[0],
        name: settings.name ?? '',
        phone: settings.phone ?? '',
        email: settings.email ?? '',
        address: settings.address ?? '',
        currency: settings.currency ?? '',
        category_title: settings.category_title ?? '',
        map_key: settings.map_key ?? '',
    });

    const stepKey = STEPS[active];

    const goto = (idx) => {
        if (idx < 0 || idx >= STEPS.length) return;
        setSeen(s => new Set(s).add(STEPS[active]));
        setActive(idx);
        form.setData('step', STEPS[idx]);
    };

    const save = () => {
        form.setData('step', stepKey);
        form.post(urls.save_step, {
            preserveScroll: true,
            onSuccess: () => {
                setSeen(s => new Set(s).add(stepKey));
                if (active < STEPS.length - 1) setActive(active + 1);
                else complete();
            },
        });
    };

    const skip = () => {
        setSeen(s => new Set(s).add(stepKey));
        if (active < STEPS.length - 1) setActive(active + 1);
        else complete();
    };

    const complete = () => router.post(urls.complete, {}, { preserveScroll: true });

    const progressPct = Math.round(((seen.size) / STEPS.length) * 100);

    return (
        <>
            <Head title={t.title} />

            <div className="min-h-screen bg-muted/30 flex items-center justify-center p-4 sm:p-6 lg:p-8">
                <div className="w-full max-w-5xl">
                    <div className="mb-6 text-center">
                        <h1 className="text-2xl font-bold tracking-tight">{t.title}</h1>
                        <p className="text-sm text-muted-foreground mt-1.5">{t.subtitle}</p>
                    </div>

                    {/* Progress bar */}
                    <div className="mb-4">
                        <div className="flex items-center justify-between text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1">
                            <span>{t.progress}</span>
                            <span>{seen.size} / {STEPS.length}</span>
                        </div>
                        <div className="h-1.5 w-full bg-muted rounded-full overflow-hidden">
                            <div className="h-full bg-primary transition-all duration-300" style={{ width: `${progressPct}%` }} />
                        </div>
                    </div>

                    <div className="grid gap-6 lg:grid-cols-[240px_1fr]">
                        {/* Steps sidebar */}
                        <Card>
                            <CardContent className="p-2">
                                <div className="flex flex-col gap-1">
                                    {STEPS.map((key, i) => (
                                        <button
                                            key={key}
                                            type="button"
                                            onClick={() => goto(i)}
                                            className={cn(
                                                'flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-start transition-colors',
                                                i === active
                                                    ? 'bg-primary/10 text-primary'
                                                    : 'text-muted-foreground hover:bg-muted/40 hover:text-foreground'
                                            )}
                                        >
                                            <StepBadge done={seen.has(key)} active={i === active} i={i} />
                                            <span className="flex-1 truncate">{t.steps?.[key] ?? key}</span>
                                            {i === active && <ChevronRight className="h-4 w-4" />}
                                        </button>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Step body */}
                        <div className="space-y-4">
                            <Card>
                                <CardContent className="p-6 space-y-5">
                                    <div>
                                        <h2 className="text-base font-semibold">{t.steps?.[stepKey] ?? stepKey}</h2>
                                        <p className="text-xs text-muted-foreground mt-0.5">
                                            {stepKey === 'basics'            && t.basics_hint}
                                            {stepKey === 'delivery_category' && t.category_hint}
                                            {stepKey === 'google_maps'       && t.google_hint}
                                            {(stepKey === 'delivery_charge' || stepKey === 'delivery_type' || stepKey === 'sms') && t.linkout_hint}
                                        </p>
                                    </div>

                                    {stepKey === 'basics' && (
                                        <div className="grid gap-4 md:grid-cols-2">
                                            <Field label={t.name} error={form.errors.name}>
                                                <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                                            </Field>
                                            <Field label={t.phone} error={form.errors.phone}>
                                                <Input value={form.data.phone} onChange={(e) => form.setData('phone', e.target.value)} inputMode="tel" />
                                            </Field>
                                            <Field label={t.email} error={form.errors.email}>
                                                <Input type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} />
                                            </Field>
                                            <Field label={t.currency} error={form.errors.currency}>
                                                <Select value={form.data.currency} onChange={(e) => form.setData('currency', e.target.value)}>
                                                    <option value="">—</option>
                                                    {(lookups.currencies || []).map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
                                                </Select>
                                            </Field>
                                            <div className="md:col-span-2">
                                                <Field label={t.address} error={form.errors.address}>
                                                    <Textarea rows={3} value={form.data.address} onChange={(e) => form.setData('address', e.target.value)} />
                                                </Field>
                                            </div>
                                        </div>
                                    )}

                                    {stepKey === 'delivery_category' && (
                                        <Field label={t.steps.delivery_category} error={form.errors.category_title}>
                                            <Input
                                                value={form.data.category_title}
                                                onChange={(e) => form.setData('category_title', e.target.value)}
                                                placeholder={t.category_placeholder}
                                            />
                                        </Field>
                                    )}

                                    {stepKey === 'delivery_charge' && (
                                        <LinkOutStep hint={t.linkout_hint} url={urls.delivery_charge} label={t.open_full_page} />
                                    )}

                                    {stepKey === 'delivery_type' && (
                                        <LinkOutStep hint={t.linkout_hint} url={urls.delivery_type} label={t.open_full_page} />
                                    )}

                                    {stepKey === 'sms' && (
                                        <LinkOutStep hint={t.linkout_hint} url={urls.sms_settings} label={t.open_full_page} />
                                    )}

                                    {stepKey === 'google_maps' && (
                                        <Field label={t.map_key} error={form.errors.map_key}>
                                            <Input
                                                value={form.data.map_key}
                                                onChange={(e) => form.setData('map_key', e.target.value)}
                                                placeholder="AIza…"
                                                className="font-mono"
                                            />
                                        </Field>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Actions */}
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <button
                                    type="button"
                                    onClick={complete}
                                    className="inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground transition-colors"
                                >
                                    <SkipForward className="h-3.5 w-3.5" />
                                    {t.skip_all}
                                </button>
                                <div className="flex items-center gap-2">
                                    <Button type="button" variant="ghost" onClick={skip}>
                                        {t.skip_step}
                                    </Button>
                                    <Button type="button" onClick={save} disabled={form.processing}>
                                        {stepKey === 'delivery_charge' || stepKey === 'delivery_type' || stepKey === 'sms'
                                            ? t.mark_done
                                            : t.save_continue}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
