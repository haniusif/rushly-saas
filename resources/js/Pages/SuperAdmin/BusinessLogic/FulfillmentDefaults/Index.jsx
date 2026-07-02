import * as React from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { GitBranch, Save, Plus, Trash2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Label } from '@/Components/ui/Label';

function StrategySelect({ value, onChange, strategies, allowNull = true, id }) {
    return (
        <select
            id={id}
            className="w-full h-9 rounded-md border border-input bg-background px-3 text-sm"
            value={value ?? ''}
            onChange={(e) => onChange(e.target.value || null)}
        >
            {allowNull && <option value="">— none —</option>}
            {strategies.map((s) => (
                <option key={s.code} value={s.code}>{s.label}</option>
            ))}
        </select>
    );
}

function DefaultsCard({ title, subtitle, defaults, strategies, tenantName = null, urls, isGlobal, onDelete }) {
    const form = useForm({
        default_strategy:             defaults.default_strategy ?? '',
        service_last_mile_strategy:   defaults.service_last_mile_strategy ?? '',
        service_fulfillment_strategy: defaults.service_fulfillment_strategy ?? '',
        service_storage_strategy:     defaults.service_storage_strategy ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true });
    };

    return (
        <Card className="mb-4">
            <CardContent className="p-5">
                <div className="flex items-start justify-between mb-4">
                    <div>
                        <h3 className="text-base font-semibold">
                            {title}
                            {tenantName && <span className="ms-2 text-sm text-muted-foreground font-normal">· {tenantName}</span>}
                        </h3>
                        {subtitle && <p className="text-xs text-muted-foreground mt-0.5">{subtitle}</p>}
                    </div>
                    {!isGlobal && (
                        <Button type="button" onClick={onDelete} className="h-8 px-2 text-xs bg-rose-600 hover:bg-rose-700 text-white">
                            <Trash2 className="h-3.5 w-3.5 me-1" /> delete
                        </Button>
                    )}
                </div>

                <form onSubmit={submit} className="grid gap-3 md:grid-cols-2">
                    <div>
                        <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1 block">
                            Default strategy <span className="text-muted-foreground normal-case font-normal">(last resort)</span>
                        </Label>
                        <StrategySelect
                            value={form.data.default_strategy}
                            onChange={(v) => form.setData('default_strategy', v)}
                            strategies={strategies}
                        />
                    </div>
                    <div />
                    <div>
                        <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1 block">
                            last_mile →
                        </Label>
                        <StrategySelect
                            value={form.data.service_last_mile_strategy}
                            onChange={(v) => form.setData('service_last_mile_strategy', v)}
                            strategies={strategies}
                        />
                    </div>
                    <div>
                        <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1 block">
                            fulfillment →
                        </Label>
                        <StrategySelect
                            value={form.data.service_fulfillment_strategy}
                            onChange={(v) => form.setData('service_fulfillment_strategy', v)}
                            strategies={strategies}
                        />
                    </div>
                    <div>
                        <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1 block">
                            storage →
                        </Label>
                        <StrategySelect
                            value={form.data.service_storage_strategy}
                            onChange={(v) => form.setData('service_storage_strategy', v)}
                            strategies={strategies}
                        />
                    </div>
                    <div />

                    <div className="md:col-span-2 pt-3 border-t border-border">
                        <Button type="submit" disabled={form.processing}>
                            <Save className="h-4 w-4 me-1" /> Save
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

function NewOverride({ tenants, existingOverrides, strategies, urls }) {
    const [expanded, setExpanded] = React.useState(false);

    const overriddenTenantIds = new Set(existingOverrides.map((o) => o.company_id));
    const availableTenants    = tenants.filter((t) => !overriddenTenantIds.has(t.id));

    const form = useForm({
        company_id: availableTenants[0]?.id ?? '',
        default_strategy:             '',
        service_last_mile_strategy:   '',
        service_fulfillment_strategy: '',
        service_storage_strategy:     '',
    });

    if (!expanded) {
        return (
            <Button type="button" onClick={() => setExpanded(true)} disabled={availableTenants.length === 0}>
                <Plus className="h-4 w-4 me-1" /> Add tenant override
            </Button>
        );
    }

    const submit = (e) => {
        e.preventDefault();
        form.post(urls.submit, {
            preserveScroll: true,
            onSuccess: () => { setExpanded(false); form.reset(); },
        });
    };

    return (
        <Card className="mb-4 border-primary/40">
            <CardContent className="p-5">
                <h3 className="text-base font-semibold mb-3">New tenant override</h3>
                <form onSubmit={submit} className="grid gap-3 md:grid-cols-2">
                    <div>
                        <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1 block">Tenant</Label>
                        <select
                            className="w-full h-9 rounded-md border border-input bg-background px-3 text-sm"
                            value={form.data.company_id}
                            onChange={(e) => form.setData('company_id', e.target.value)}
                        >
                            {availableTenants.map((t) => <option key={t.id} value={t.id}>#{t.id} {t.name}</option>)}
                        </select>
                    </div>
                    <div />
                    <div>
                        <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1 block">Default strategy</Label>
                        <StrategySelect value={form.data.default_strategy} onChange={(v) => form.setData('default_strategy', v)} strategies={strategies} />
                    </div>
                    <div />
                    <div>
                        <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1 block">last_mile →</Label>
                        <StrategySelect value={form.data.service_last_mile_strategy} onChange={(v) => form.setData('service_last_mile_strategy', v)} strategies={strategies} />
                    </div>
                    <div>
                        <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1 block">fulfillment →</Label>
                        <StrategySelect value={form.data.service_fulfillment_strategy} onChange={(v) => form.setData('service_fulfillment_strategy', v)} strategies={strategies} />
                    </div>
                    <div>
                        <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1 block">storage →</Label>
                        <StrategySelect value={form.data.service_storage_strategy} onChange={(v) => form.setData('service_storage_strategy', v)} strategies={strategies} />
                    </div>
                    <div />

                    <div className="md:col-span-2 pt-3 border-t border-border flex gap-2">
                        <Button type="submit" disabled={form.processing}><Save className="h-4 w-4 me-1" /> Save override</Button>
                        <Button type="button" onClick={() => setExpanded(false)} className="bg-transparent text-foreground border border-input hover:bg-muted/40">
                            Cancel
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

export default function Index({ global, overrides = [], tenants = [], strategies = [], urls = {}, t = {} }) {
    const tenantName = (id) => tenants.find((x) => x.id === id)?.name ?? `Tenant #${id}`;

    const deleteOverride = (id) => {
        if (!confirm('Delete this tenant override? The tenant will fall back to the global defaults.')) return;
        const url = (urls.destroy_override_tpl || `/super-admin/business-logic/fulfillment-defaults/overrides/__ID__`).replace('__ID__', id);
        router.delete(url, { preserveScroll: true });
    };

    return (
        <AdminLayout title={t.page_title}>
            <Head title={t.page_title} />

            <Card className="mb-4">
                <CardContent className="p-5 flex items-start gap-3">
                    <GitBranch className="h-5 w-5 text-primary mt-0.5" />
                    <div>
                        <h2 className="text-lg font-semibold">{t.page_title}</h2>
                        <p className="text-sm text-muted-foreground">{t.subtitle}</p>
                    </div>
                </CardContent>
            </Card>

            <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-2">{t.global_h}</h3>
            <DefaultsCard
                title="Platform defaults"
                subtitle="Applied to every tenant that has no override."
                defaults={global}
                strategies={strategies}
                urls={{ submit: urls.update_global }}
                isGlobal
            />

            <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide mt-6 mb-2">{t.overrides_h}</h3>
            {overrides.map((o) => (
                <DefaultsCard
                    key={o.id}
                    title={`Tenant #${o.company_id}`}
                    subtitle="Non-null fields below win over the global defaults. Blank fields fall through."
                    tenantName={tenantName(o.company_id)}
                    defaults={o}
                    strategies={strategies}
                    urls={{ submit: urls.store_override }}
                    onDelete={() => deleteOverride(o.id)}
                />
            ))}
            {overrides.length === 0 && (
                <Card className="mb-4">
                    <CardContent className="p-6 text-sm text-muted-foreground text-center">
                        No per-tenant overrides yet — every tenant uses the global defaults above.
                    </CardContent>
                </Card>
            )}

            <NewOverride
                tenants={tenants}
                existingOverrides={overrides}
                strategies={strategies}
                urls={{ submit: urls.store_override }}
            />
        </AdminLayout>
    );
}
