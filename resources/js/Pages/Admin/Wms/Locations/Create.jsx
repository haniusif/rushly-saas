import * as React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    MapPin, Building2, Layers, Grid3x3, Box, Hash, Save, ArrowLeft, AlertCircle,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';
import { cn } from '@/lib/utils';

function Field({ icon: Icon, label, required, error, hint, children, className }) {
    return (
        <div className={cn('space-y-1.5', className)}>
            <Label className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {Icon && <Icon className="h-3 w-3" />}
                {label}
                {required && <span className="text-destructive">*</span>}
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

function Section({ title, children }) {
    return (
        <Card>
            <CardContent className="pt-6">
                <div className="mb-4 text-sm font-semibold tracking-tight">{title}</div>
                {children}
            </CardContent>
        </Card>
    );
}

export default function Create({ lookups = {}, urls = {}, t = {} }) {
    const form = useForm({
        hub_id:   '',
        zone:     '',
        aisle:    '',
        rack:     '',
        shelf:    '',
        bin:      '',
        type:     (lookups.types && lookups.types[0]) || '',
        capacity: '',
        code:     '',
        is_active: true,
    });

    // Auto-suggested code preview (rack-shelf-bin) — the backend will fill it
    // in if we leave the field blank, so this is purely a UX hint.
    const previewCode = React.useMemo(() => {
        const parts = [form.data.rack, form.data.shelf, form.data.bin].filter(Boolean);
        return parts.length ? parts.join('-').toUpperCase() : '';
    }, [form.data.rack, form.data.shelf, form.data.bin]);

    const submit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true });
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.list, t.title]}>
            <Head title={t.title} />

            <div className="mb-4">
                <Link href={urls.cancel} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.list}
                </Link>
            </div>

            <form onSubmit={submit} className="grid gap-5 lg:grid-cols-3">
                <div className="lg:col-span-2 space-y-5">
                    <Section title={t.identity}>
                        <div className="grid gap-4 md:grid-cols-2">
                            <Field icon={Building2} label={t.hub} required error={form.errors.hub_id}>
                                <Select value={form.data.hub_id} onChange={(e) => form.setData('hub_id', e.target.value)}>
                                    <option value="">—</option>
                                    {(lookups.hubs || []).map((h) => (
                                        <option key={h.id} value={h.id}>{h.name}</option>
                                    ))}
                                </Select>
                            </Field>
                            <Field icon={Layers} label={t.type} required error={form.errors.type}>
                                <Select value={form.data.type} onChange={(e) => form.setData('type', e.target.value)}>
                                    {(lookups.types || []).map((tp) => (
                                        <option key={tp} value={tp}>{tp}</option>
                                    ))}
                                </Select>
                            </Field>
                            <Field icon={Hash} label={t.code} error={form.errors.code} hint={t.code_hint} className="md:col-span-2">
                                <Input
                                    value={form.data.code}
                                    onChange={(e) => form.setData('code', e.target.value)}
                                    placeholder={previewCode || '—'}
                                    className="font-mono"
                                    maxLength={191}
                                />
                            </Field>
                        </div>
                    </Section>

                    <Section title={t.address}>
                        <div className="grid gap-4 md:grid-cols-3">
                            <Field icon={Grid3x3} label={t.zone} error={form.errors.zone} hint={t.zone_hint}>
                                <Input value={form.data.zone} onChange={(e) => form.setData('zone', e.target.value)} maxLength={191} />
                            </Field>
                            <Field icon={Grid3x3} label={t.aisle} error={form.errors.aisle}>
                                <Input value={form.data.aisle} onChange={(e) => form.setData('aisle', e.target.value)} maxLength={191} />
                            </Field>
                            <Field icon={Grid3x3} label={t.rack} required error={form.errors.rack}>
                                <Input value={form.data.rack} onChange={(e) => form.setData('rack', e.target.value)} maxLength={191} />
                            </Field>
                            <Field icon={Grid3x3} label={t.shelf} required error={form.errors.shelf}>
                                <Input value={form.data.shelf} onChange={(e) => form.setData('shelf', e.target.value)} maxLength={191} />
                            </Field>
                            <Field icon={Box} label={t.bin} error={form.errors.bin}>
                                <Input value={form.data.bin} onChange={(e) => form.setData('bin', e.target.value)} maxLength={191} />
                            </Field>
                            <Field icon={Box} label={t.capacity} error={form.errors.capacity}>
                                <Input
                                    type="number"
                                    min="0"
                                    value={form.data.capacity}
                                    onChange={(e) => form.setData('capacity', e.target.value)}
                                />
                            </Field>
                        </div>
                    </Section>
                </div>

                {/* Sidebar */}
                <div className="space-y-5">
                    <Section title={t.options}>
                        <label
                            onClick={() => form.setData('is_active', !form.data.is_active)}
                            className={cn(
                                'flex cursor-pointer items-start gap-3 rounded-md border p-3 transition-colors',
                                form.data.is_active ? 'border-primary bg-primary/5' : 'border-input bg-card hover:bg-muted/40',
                            )}
                        >
                            <input type="checkbox" checked={form.data.is_active} onChange={() => {}} className="mt-0.5 h-4 w-4 rounded border-input" />
                            <div className="min-w-0">
                                <div className="text-sm font-medium">{t.is_active}</div>
                                <div className="text-[11px] text-muted-foreground">Inactive locations are hidden from picking/putaway suggestions.</div>
                            </div>
                        </label>
                    </Section>

                    <Card>
                        <CardContent className="pt-6 space-y-3">
                            <div className="flex items-center gap-2 text-sm">
                                <MapPin className="h-4 w-4 text-primary" />
                                <span className="font-semibold">{t.code}</span>
                            </div>
                            <div className="font-mono text-lg tabular-nums bg-muted/40 rounded-md px-3 py-2 border border-border">
                                {form.data.code || previewCode || '—'}
                            </div>
                            <div className="flex flex-col gap-2 pt-2 border-t border-border">
                                <Button type="submit" disabled={form.processing}>
                                    <Save className="h-4 w-4 me-1" /> {t.save}
                                </Button>
                                <Link href={urls.cancel} className="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent">
                                    {t.cancel}
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </form>
        </AdminLayout>
    );
}
