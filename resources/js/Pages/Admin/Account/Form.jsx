import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, AlertCircle, DollarSign } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';

function Field({ label, required, error, hint, children }) {
    return (
        <div className="space-y-1.5">
            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {label} {required && <span className="text-destructive">*</span>}
            </Label>
            {children}
            {hint && <p className="text-[11px] text-muted-foreground">{hint}</p>}
            {error && <p className="text-xs text-destructive flex items-center gap-1"><AlertCircle className="h-3 w-3" /> {error}</p>}
        </div>
    );
}

// Gateway-conditional field visibility — mirrors public/backend/js/account/account.js:
//   1 (Cash)         → balance only
//   2 (Bank)         → holder + account_no + bank + branch + opening_balance
//   3/4/5 (mWallet)  → holder + mobile + account_type + opening_balance
function gatewayFields(g) {
    const gw = String(g || '');
    if (gw === '1') return { balance: true };
    if (gw === '2') return { account_holder_name: true, account_no: true, bank: true, branch_name: true, opening_balance: true };
    if (['3', '4', '5'].includes(gw)) return { account_holder_name: true, mobile: true, account_type: true, opening_balance: true };
    return {};
}

export default function Form({ mode = 'create', entity = null, lookups = {}, urls = {}, t = {} }) {
    const isEdit = mode === 'edit';
    const form = useForm({
        type: entity?.type ?? '',
        user: entity?.user ?? '',
        gateway: String(entity?.gateway ?? ''),
        balance: entity?.balance ?? '',
        account_holder_name: entity?.account_holder_name ?? '',
        account_no: entity?.account_no ?? '',
        bank: entity?.bank ?? '',
        branch_name: entity?.branch_name ?? '',
        opening_balance: entity?.opening_balance ?? '',
        mobile: entity?.mobile ?? '',
        account_type: entity?.account_type ?? '',
        status: String(entity?.status ?? '1'),
        ...(isEdit ? { id: entity?.id, _method: 'put' } : {}),
    });

    const show = gatewayFields(form.data.gateway);
    const gwPicked = !!form.data.gateway;

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true });
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.list_title, isEdit ? t.edit : t.title]}>
            <Head title={t.title} />
            <div className="mb-4">
                <a href={urls.cancel} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40"><ArrowLeft className="h-4 w-4 me-1" /> {t.back}</a>
            </div>
            <form onSubmit={onSubmit}>
                <Card>
                    <CardContent className="p-6">
                        <div className="mb-5 flex items-center gap-2"><DollarSign className="h-5 w-5 text-primary" /><h2 className="text-lg font-semibold">{t.title}</h2></div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <Field label={t.type} error={form.errors.type}>
                                <Select value={form.data.type} onChange={(e) => form.setData('type', e.target.value)}>
                                    <option value="">{t.select} {t.type}</option>
                                    {(lookups.types || []).map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                                </Select>
                            </Field>
                            <Field label={t.user} error={form.errors.user}>
                                <Select value={form.data.user} onChange={(e) => form.setData('user', e.target.value)}>
                                    <option value="">{t.select} {t.user}</option>
                                    {(lookups.users || []).map((u) => <option key={u.value} value={u.value}>{u.label}</option>)}
                                </Select>
                            </Field>
                            <Field label={t.gateway} required error={form.errors.gateway} hint={t.gateway_help}>
                                <Select value={form.data.gateway} onChange={(e) => form.setData('gateway', e.target.value)}>
                                    <option value="">{t.select} {t.gateway}</option>
                                    {(lookups.gateways || []).map((g) => <option key={g.value} value={g.value}>{g.label}</option>)}
                                </Select>
                            </Field>

                            {show.balance && (
                                <Field label={t.opening_balance} required error={form.errors.balance}>
                                    <Input type="number" step="0.01" value={form.data.balance} onChange={(e) => form.setData('balance', e.target.value)} placeholder={t.placeholder_balance} />
                                </Field>
                            )}

                            {show.account_holder_name && (
                                <Field label={t.account_holder_name} required error={form.errors.account_holder_name}>
                                    <Input value={form.data.account_holder_name} onChange={(e) => form.setData('account_holder_name', e.target.value)} placeholder={t.placeholder_holder} />
                                </Field>
                            )}

                            {show.account_no && (
                                <Field label={t.account_no} required error={form.errors.account_no}>
                                    <Input type="number" value={form.data.account_no} onChange={(e) => form.setData('account_no', e.target.value)} placeholder={t.placeholder_account_no} />
                                </Field>
                            )}

                            {show.bank && (
                                <Field label={t.bank} required error={form.errors.bank}>
                                    <Select value={form.data.bank} onChange={(e) => form.setData('bank', e.target.value)}>
                                        <option value="">{t.select} {t.bank}</option>
                                        {(lookups.banks || []).map((b) => <option key={b.value} value={b.value}>{b.label}</option>)}
                                    </Select>
                                </Field>
                            )}

                            {show.branch_name && (
                                <Field label={t.branch_name} required error={form.errors.branch_name}>
                                    <Input value={form.data.branch_name} onChange={(e) => form.setData('branch_name', e.target.value)} placeholder={t.placeholder_branch} />
                                </Field>
                            )}

                            {show.mobile && (
                                <Field label={t.mobile} required error={form.errors.mobile}>
                                    <Input type="number" value={form.data.mobile} onChange={(e) => form.setData('mobile', e.target.value)} placeholder={t.placeholder_mobile} />
                                </Field>
                            )}

                            {show.account_type && (
                                <Field label={t.account_type} required error={form.errors.account_type}>
                                    <Select value={form.data.account_type} onChange={(e) => form.setData('account_type', e.target.value)}>
                                        <option value="">{t.select} {t.account_type}</option>
                                        {(lookups.account_types || []).map((a) => <option key={a.value} value={a.value}>{a.label}</option>)}
                                    </Select>
                                </Field>
                            )}

                            {show.opening_balance && (
                                <Field label={t.opening_balance} required error={form.errors.opening_balance}>
                                    <Input type="number" step="0.01" value={form.data.opening_balance} onChange={(e) => form.setData('opening_balance', e.target.value)} placeholder={t.placeholder_opening} />
                                </Field>
                            )}

                            {gwPicked && (
                                <Field label={t.status} required error={form.errors.status}>
                                    <Select value={form.data.status} onChange={(e) => form.setData('status', e.target.value)}>
                                        {(lookups.statuses || []).map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                                    </Select>
                                </Field>
                            )}
                        </div>

                        <div className="mt-6 flex items-center gap-2 border-t border-border pt-4">
                            <Button type="submit" disabled={form.processing || !gwPicked}><Save className="h-4 w-4 me-1" /> {t.save}</Button>
                            <a href={urls.cancel} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">{t.cancel}</a>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </AdminLayout>
    );
}
