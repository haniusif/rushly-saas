import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { Plus, Edit, Trash2, CreditCard, Building2, Smartphone, Banknote } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { cn } from '@/lib/utils';
import MerchantSubHeader from '@/Components/merchant/MerchantSubHeader';

function methodIcon(method) {
    const m = String(method || '').toLowerCase();
    if (m === 'cash') return Banknote;
    if (m === 'bank') return Building2;
    return Smartphone;
}

export default function Index({ merchant = {}, rows = [], permissions = {}, urls = {}, t = {} }) {
    const deleteRow = (r) => {
        if (!window.confirm(t.delete_confirm)) return;
        router.delete(r.urls.delete, { preserveScroll: true });
    };
    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title_index, merchant.business_name, t.title]}>
            <Head title={`${t.title} · ${merchant.business_name || ''}`} />
            <MerchantSubHeader
                merchant={merchant}
                title={t.title}
                backUrl={urls.view}
                backLabel={t.back_to_view}
                actions={permissions.create && (
                    <a href={urls.create} className="inline-flex h-9 items-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90">
                        <Plus className="h-4 w-4 me-1" /> {t.add}
                    </a>
                )}
            />

            {rows.length === 0 ? (
                <Card>
                    <CardContent className="py-16 text-center text-muted-foreground">
                        <CreditCard className="mx-auto h-8 w-8 mb-2 opacity-40" />
                        {t.no_rows}
                    </CardContent>
                </Card>
            ) : (
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {rows.map((r) => {
                        const Icon = methodIcon(r.method);
                        return (
                            <Card key={r.id}>
                                <CardContent className="p-4">
                                    <div className="flex items-start gap-3 mb-3">
                                        <div className="grid h-10 w-10 place-items-center rounded-md bg-primary/10 text-primary shrink-0">
                                            <Icon className="h-4 w-4" />
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <div className="text-xs text-muted-foreground uppercase tracking-wide font-medium">{t.method}</div>
                                            <div className="font-semibold capitalize truncate">{r.method_label}</div>
                                        </div>
                                        <span className={cn(
                                            'rounded-full border px-2 py-0.5 text-[10px] font-medium shrink-0',
                                            r.status === 1 ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-rose-100 text-rose-700 border-rose-200',
                                        )}>
                                            {r.status === 1 ? t.active : t.inactive}
                                        </span>
                                    </div>
                                    <div className="space-y-0.5 text-xs">
                                        {r.bank_name      && <div><span className="text-muted-foreground">Bank: </span><span className="font-medium">{r.bank_name}</span></div>}
                                        {r.holder_name    && <div><span className="text-muted-foreground">Holder: </span><span className="font-medium">{r.holder_name}</span></div>}
                                        {r.account_no     && <div><span className="text-muted-foreground">Account #: </span><span className="font-mono font-medium">{r.account_no}</span></div>}
                                        {r.branch_name    && <div><span className="text-muted-foreground">Branch: </span><span className="font-medium">{r.branch_name}</span></div>}
                                        {r.routing_no     && <div><span className="text-muted-foreground">Routing #: </span><span className="font-mono">{r.routing_no}</span></div>}
                                        {r.mobile_company && <div><span className="text-muted-foreground">Provider: </span><span className="font-medium">{r.mobile_company}</span></div>}
                                        {r.mobile_no      && <div><span className="text-muted-foreground">Mobile: </span><span className="font-mono">{r.mobile_no}</span></div>}
                                        {r.account_type   && <div><span className="text-muted-foreground">Account type: </span><span className="font-medium">{r.account_type}</span></div>}
                                    </div>
                                    {(permissions.update || permissions.delete) && (
                                        <div className="mt-3 flex items-center justify-end gap-1">
                                            {permissions.update && (
                                                <a href={r.urls.edit} className="inline-flex h-7 items-center rounded-md border border-input bg-background px-2 text-xs font-medium hover:bg-accent" title={t.edit}>
                                                    <Edit className="h-3 w-3 me-1" /> {t.edit}
                                                </a>
                                            )}
                                            {permissions.delete && (
                                                <button type="button" onClick={() => deleteRow(r)} className="inline-flex h-7 w-7 items-center justify-center rounded-md border border-input bg-background text-destructive hover:bg-destructive/5" title={t.delete}>
                                                    <Trash2 className="h-3.5 w-3.5" />
                                                </button>
                                            )}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>
            )}
        </AdminLayout>
    );
}
