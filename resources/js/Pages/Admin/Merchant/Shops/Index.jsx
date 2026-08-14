import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { Plus, Edit, Trash2, Store, Star, Phone, MapPin } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { cn } from '@/lib/utils';
import MerchantSubHeader from '@/Components/merchant/MerchantSubHeader';

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
                        <Store className="mx-auto h-8 w-8 mb-2 opacity-40" />
                        {t.no_rows}
                    </CardContent>
                </Card>
            ) : (
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {rows.map((r) => (
                        <Card key={r.id} className={cn('overflow-hidden', r.is_default && 'ring-2 ring-amber-300')}>
                            <CardContent className="p-4">
                                <div className="flex items-start justify-between gap-2 mb-2">
                                    <div className="font-semibold truncate">{r.name || `#${r.id}`}</div>
                                    {r.is_default && (
                                        <span className="inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-700 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 shrink-0">
                                            <Star className="h-3 w-3 fill-current" /> {t.default}
                                        </span>
                                    )}
                                </div>
                                <div className="space-y-1 text-xs text-muted-foreground">
                                    {r.contact_no && (
                                        <div className="flex items-center gap-1.5"><Phone className="h-3 w-3" /> {r.contact_no}</div>
                                    )}
                                    {r.address && (
                                        <div className="flex items-start gap-1.5"><MapPin className="h-3 w-3 mt-0.5 shrink-0" /> <span>{r.address}</span></div>
                                    )}
                                </div>
                                <div className="mt-3 flex items-center justify-between gap-2">
                                    <span className={cn(
                                        'inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-medium',
                                        r.status === 1 ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-rose-100 text-rose-700 border-rose-200',
                                    )}>
                                        {r.status === 1 ? t.active : t.inactive}
                                    </span>
                                    <div className="flex items-center gap-1">
                                        {!r.is_default && (
                                            <a href={r.urls.default} className="inline-flex h-7 items-center rounded-md border border-input bg-background px-2 text-[10px] font-medium hover:bg-accent" title={t.set_default}>
                                                <Star className="h-3 w-3 me-1" /> {t.set_default}
                                            </a>
                                        )}
                                        {permissions.update && (
                                            <a href={r.urls.edit} className="inline-flex h-7 w-7 items-center justify-center rounded-md border border-input bg-background hover:bg-accent" title={t.edit}>
                                                <Edit className="h-3.5 w-3.5" />
                                            </a>
                                        )}
                                        {permissions.delete && (
                                            <button type="button" onClick={() => deleteRow(r)} className="inline-flex h-7 w-7 items-center justify-center rounded-md border border-input bg-background text-destructive hover:bg-destructive/5" title={t.delete}>
                                                <Trash2 className="h-3.5 w-3.5" />
                                            </button>
                                        )}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}
