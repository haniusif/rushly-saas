import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Plus, Eye, Pencil, Trash2 } from 'lucide-react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import Pagination from '@/Components/merchant/Pagination';

function StatusPill({ active, label }) {
    return (
        <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border ${
            active
                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                : 'bg-muted/40 text-muted-foreground border-border'
        }`}>
            {label}
        </span>
    );
}

function DeleteButton({ url, label }) {
    const form = useForm({});
    const submit = (e) => {
        e.preventDefault();
        if (!window.confirm('Delete this support ticket?')) return;
        form.delete(url, { preserveScroll: true });
    };
    return (
        <form onSubmit={submit} className="inline">
            <button
                type="submit"
                className="inline-flex items-center gap-1.5 h-7 px-2.5 text-xs rounded-md border border-input hover:bg-rose-50 hover:text-rose-700 hover:border-rose-200"
            >
                <Trash2 className="h-3 w-3" /> {label}
            </button>
        </form>
    );
}

export default function Index({ rows = [], pagination = null, urls = {}, t = {} }) {
    return (
        <MerchantLayout title={`${t.title} ${t.list}`} breadcrumbs={[t.dashboard, t.title, t.list]}>
            <Head title={t.title} />
            <Card>
                <CardContent className="p-0">
                    <div className="flex items-center justify-between px-5 py-4 border-b border-border">
                        <h2 className="text-base font-semibold m-0">{t.title}</h2>
                        <a
                            href={urls.create}
                            className="inline-flex items-center gap-1.5 h-9 px-3 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:opacity-90 no-underline"
                        >
                            <Plus className="h-4 w-4" /> {t.add}
                        </a>
                    </div>
                    {rows.length === 0 ? (
                        <div className="p-8 text-center text-sm text-muted-foreground">{t.empty}</div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/30 text-xs uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th className="text-start font-medium px-4 py-2.5 w-12">{t.sl}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.user_info}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.subject}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.date}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.status}</th>
                                        <th className="text-end   font-medium px-4 py-2.5 w-40">{t.action}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {rows.map((r) => (
                                        <tr key={r.id}>
                                            <td className="px-4 py-2.5 tabular-nums align-top">{r.serial}</td>
                                            <td className="px-4 py-2.5 align-top">
                                                <div className="text-sm font-medium">{r.user_name}</div>
                                                <div className="text-[11px] text-muted-foreground">{r.user_email}</div>
                                                {r.department && <div className="text-[11px] text-muted-foreground">{t.department}: {r.department}</div>}
                                                {r.service && <div className="text-[11px] text-muted-foreground">{t.service}: {r.service}</div>}
                                            </td>
                                            <td className="px-4 py-2.5 align-top max-w-[280px]">
                                                <a href={r.view_url} className="text-primary hover:underline">{r.subject}</a>
                                            </td>
                                            <td className="px-4 py-2.5 align-top text-xs text-muted-foreground">{r.date}</td>
                                            <td className="px-4 py-2.5 align-top">
                                                <StatusPill active={r.status_active} label={r.status_label || (r.status_active ? 'Open' : 'Closed')} />
                                            </td>
                                            <td className="px-4 py-2.5 align-top text-end">
                                                <div className="inline-flex gap-1.5">
                                                    <a
                                                        href={r.view_url}
                                                        className="inline-flex items-center gap-1.5 h-7 px-2.5 text-xs rounded-md border border-input hover:bg-muted/40 no-underline"
                                                    >
                                                        <Eye className="h-3 w-3" /> {t.view}
                                                    </a>
                                                    {r.status === 1 && (
                                                        <>
                                                            <a
                                                                href={r.edit_url}
                                                                className="inline-flex items-center gap-1.5 h-7 px-2.5 text-xs rounded-md border border-input hover:bg-muted/40 no-underline"
                                                            >
                                                                <Pencil className="h-3 w-3" /> {t.edit}
                                                            </a>
                                                            <DeleteButton url={r.delete_url} label={t.delete} />
                                                        </>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    <Pagination pagination={pagination} />
                </CardContent>
            </Card>
        </MerchantLayout>
    );
}
