import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Save, AlertCircle, UserCog, Building2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';
import { cn } from '@/lib/utils';

function Field({ label, required, error, hint, children, className }) {
    return (
        <div className={cn('space-y-1.5', className)}>
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

/**
 * Shared create + edit form for /admin/hub/incharge/{hubID}/*. mode drives
 * submit URL/method; the users lookup is a flat "value/label" pool so both
 * modes just pick from a select. Whole page sits inside AdminLayout so the
 * shell stays consistent with the ported HubInCharge/Index.
 */
export default function HubInChargeForm({ mode, hub, row, lookups = {}, urls = {}, t = {} }) {
    const isEdit = mode === 'edit';
    const form = useForm({
        id:      isEdit ? row.id : undefined,
        user_id: row.user_id ?? '',
        status:  row.status  ?? '',
        _method: isEdit ? 'put' : 'post',
    });
    const onSubmit = (e) => { e.preventDefault(); form.post(urls.submit, { preserveScroll: true }); };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.hubs, hub.name, t.incharge, t.section]}>
            <Head title={t.title} />
            <form onSubmit={onSubmit} className="space-y-4">
                <Card>
                    <CardContent className="p-0">
                        <div className="flex items-center gap-3 px-6 py-5 border-b border-border">
                            <span className="shrink-0 grid h-9 w-9 place-items-center rounded-lg bg-primary/10 text-primary">
                                <UserCog className="h-4 w-4" />
                            </span>
                            <div className="min-w-0">
                                <h2 className="text-base font-semibold m-0">{t.section}</h2>
                                <p className="text-xs text-muted-foreground mt-0.5 truncate inline-flex items-center gap-1">
                                    <Building2 className="h-3 w-3" />
                                    {hub.name}
                                </p>
                            </div>
                        </div>
                        <div className="grid gap-5 md:grid-cols-2 p-6">
                            <Field label={t.user} required error={form.errors.user_id}>
                                <Select value={form.data.user_id} onChange={(e) => form.setData('user_id', e.target.value)} required>
                                    <option value="" disabled>{t.select_user}</option>
                                    {(lookups.users || []).map((u) => (
                                        <option key={u.value} value={u.value}>{u.label}</option>
                                    ))}
                                </Select>
                            </Field>
                            <Field label={t.status} error={form.errors.status}>
                                <Select value={form.data.status} onChange={(e) => form.setData('status', e.target.value)}>
                                    {(lookups.statuses || []).map((s) => (
                                        <option key={s.value} value={s.value}>{s.label}</option>
                                    ))}
                                </Select>
                            </Field>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex items-center justify-end gap-2 bg-background border border-border rounded-xl px-6 py-4">
                    <Button variant="outline" asChild>
                        <a href={urls.index}>{t.cancel}</a>
                    </Button>
                    <Button type="submit" disabled={form.processing}>
                        <Save className="h-4 w-4 me-1" />
                        {t.save}
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
