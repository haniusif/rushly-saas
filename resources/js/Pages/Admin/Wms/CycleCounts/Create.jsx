import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
    Save, ArrowLeft, AlertCircle, Building2, Layers, MapPin,
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

function ScopeTile({ scope, current, onClick }) {
    const active = scope.value === current;
    return (
        <button
            type="button"
            onClick={() => onClick(scope.value)}
            className={cn(
                'flex flex-col items-start gap-1 rounded-md border p-3 text-start transition-all',
                active
                    ? 'border-primary bg-primary/5 shadow-sm'
                    : 'border-input bg-card hover:bg-muted/40',
            )}
        >
            <div className="flex items-center gap-2 text-sm font-medium">
                <Layers className={cn('h-4 w-4', active ? 'text-primary' : 'text-muted-foreground')} />
                {scope.label}
            </div>
            <div className="text-[11px] text-muted-foreground">{scope.hint}</div>
        </button>
    );
}

export default function Create({ lookups = {}, next_number = '', urls = {}, t = {} }) {
    const form = useForm({
        hub_id: '',
        scope: '',
        zone: '',
    });

    const needsZone = form.data.scope === 'zone';

    // Clear the zone field when the scope changes away from "zone".
    React.useEffect(() => {
        if (form.data.scope !== 'zone' && form.data.zone !== '') {
            form.setData('zone', '');
        }
    }, [form.data.scope]); // eslint-disable-line react-hooks/exhaustive-deps

    const submit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true });
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title_index, t.title]}>
            <Head title={t.title} />

            <form onSubmit={submit} className="max-w-2xl space-y-5">
                <Card>
                    <CardContent className="pt-6">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="text-sm font-semibold tracking-tight">Header</div>
                            {next_number && (
                                <div className="text-xs text-muted-foreground">
                                    {t.count_number}: <span className="font-mono font-semibold text-foreground">{next_number}</span>
                                </div>
                            )}
                        </div>

                        <Field icon={Building2} label={t.hub} required error={form.errors.hub_id}>
                            <Select value={form.data.hub_id} onChange={(e) => form.setData('hub_id', e.target.value)}>
                                <option value="">—</option>
                                {(lookups.hubs || []).map((h) => <option key={h.id} value={h.id}>{h.name}</option>)}
                            </Select>
                        </Field>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <Field label={t.scope} required error={form.errors.scope}>
                            <div className="grid gap-2 sm:grid-cols-3">
                                {(lookups.scopes || []).map((s) => (
                                    <ScopeTile key={s.value} scope={s} current={form.data.scope}
                                        onClick={(v) => form.setData('scope', v)} />
                                ))}
                            </div>
                        </Field>

                        {needsZone && (
                            <div className="mt-4">
                                <Field icon={MapPin} label={t.zone} required={needsZone} error={form.errors.zone} hint={t.zone_hint}>
                                    <Input value={form.data.zone} onChange={(e) => form.setData('zone', e.target.value)} maxLength={191} />
                                </Field>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <div className="flex items-center justify-end gap-2 rounded-xl border border-border bg-card p-4 shadow-sm">
                    <a href={urls.cancel} className="inline-flex h-10 items-center rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent">
                        <ArrowLeft className="h-4 w-4 me-1" /> {t.cancel}
                    </a>
                    <Button type="submit" disabled={form.processing}>
                        <Save className="h-4 w-4 me-1" /> {form.processing ? '…' : t.save}
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
