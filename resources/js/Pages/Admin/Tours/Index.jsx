import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { Plus, Edit, Trash2, BarChart3, PlayCircle, ShieldCheck, Building2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';

const ROLE_LABEL = { 1:'Admin', 2:'Merchant', 3:'Driver', 4:'Incharge', 5:'Hub', 6:'Super admin' };

export default function Index({ tours = [], urls = {}, t = {} }) {
    const toggle = async (id) => {
        try {
            await fetch(`/admin/tours/${id}/toggle`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                },
            });
            router.reload({ only: ['tours'] });
        } catch { /* ignore */ }
    };

    const remove = (id) => {
        if (!window.confirm('Delete this tour?')) return;
        router.delete(`/admin/tours/${id}`);
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title]}>
            <Head title={t.title} />

            <div className="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold m-0">{t.title}</h1>
                    <p className="mt-1 text-sm text-muted-foreground m-0">Onboarding tours shown to users based on role + module.</p>
                </div>
                <div className="flex items-center gap-2">
                    <a href={urls.analytics} className="inline-flex h-9 items-center gap-1.5 rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent no-underline">
                        <BarChart3 className="h-4 w-4" /> {t.analytics}
                    </a>
                    <a href={urls.create} className="inline-flex h-9 items-center gap-1.5 rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground hover:opacity-90 no-underline">
                        <Plus className="h-4 w-4" /> {t.add}
                    </a>
                </div>
            </div>

            <Card>
                <CardContent className="p-0">
                    {tours.length === 0 ? (
                        <div className="p-8 text-center text-sm text-muted-foreground">{t.no_data}</div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/30 text-xs uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th className="text-start font-medium px-4 py-2.5">{t.key}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.module}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.role_scope}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.steps}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.version}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.active}</th>
                                        <th className="text-end   font-medium px-4 py-2.5">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {tours.map((tr) => (
                                        <tr key={tr.id} className="hover:bg-muted/20">
                                            <td className="px-4 py-2.5 align-top">
                                                <div className="flex items-center gap-2">
                                                    {tr.is_system
                                                        ? <ShieldCheck className="h-3.5 w-3.5 text-sky-600" title={t.system} />
                                                        : <Building2 className="h-3.5 w-3.5 text-emerald-600" title={t.tenant} />}
                                                    <div>
                                                        <div className="font-medium">{tr.title}</div>
                                                        <div className="text-[11px] text-muted-foreground font-mono">{tr.key}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-2.5 align-top text-xs">{tr.module || '—'}</td>
                                            <td className="px-4 py-2.5 align-top text-xs">
                                                {(tr.role_scope || []).length === 0
                                                    ? <span className="text-muted-foreground">All</span>
                                                    : tr.role_scope.map((r) => (
                                                        <span key={r} className="me-1 inline-block rounded-full bg-muted px-2 py-0.5 text-[10px]">{ROLE_LABEL[r] || r}</span>
                                                    ))}
                                            </td>
                                            <td className="px-4 py-2.5 align-top tabular-nums">{tr.step_count}</td>
                                            <td className="px-4 py-2.5 align-top tabular-nums">v{tr.version}</td>
                                            <td className="px-4 py-2.5 align-top">
                                                <label className="inline-flex cursor-pointer items-center gap-2">
                                                    <input type="checkbox" checked={tr.is_active} onChange={() => toggle(tr.id)} className="rounded" />
                                                    <span className="text-xs">{tr.is_active ? t.active : t.inactive}</span>
                                                </label>
                                            </td>
                                            <td className="px-4 py-2.5 align-top text-end">
                                                <div className="inline-flex gap-1">
                                                    <a href={`/admin/tours/${tr.id}/preview`} className="inline-flex h-7 items-center gap-1 rounded-md border border-input bg-background px-2 text-xs hover:bg-muted no-underline">
                                                        <PlayCircle className="h-3 w-3" /> {t.preview}
                                                    </a>
                                                    <a href={`/admin/tours/${tr.id}/edit`} className="inline-flex h-7 items-center gap-1 rounded-md border border-input bg-background px-2 text-xs hover:bg-muted no-underline">
                                                        <Edit className="h-3 w-3" /> {t.edit}
                                                    </a>
                                                    <button type="button" onClick={() => remove(tr.id)} className="inline-flex h-7 items-center gap-1 rounded-md border border-input bg-background px-2 text-xs text-destructive hover:bg-destructive/10">
                                                        <Trash2 className="h-3 w-3" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
