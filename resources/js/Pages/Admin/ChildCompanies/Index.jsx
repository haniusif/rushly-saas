import * as React from 'react';
import { Head } from '@inertiajs/react';
import { Building2, Plus } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';

function StatusPill({ status }) {
    const ok = Number(status) === 1;
    return (
        <span className={`inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium ${ok ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-rose-100 text-rose-700 border-rose-200'}`}>
            {ok ? 'Active' : 'Inactive'}
        </span>
    );
}

export default function Index({ children = [], urls = {}, labels = {} }) {
    return (
        <AdminLayout title={labels.title} breadcrumbs={[labels.title]}>
            <Head title={labels.title} />

            <div className="mb-4 flex items-center justify-between">
                <div>
                    <h1 className="text-lg font-semibold flex items-center gap-2">
                        <Building2 className="h-5 w-5" /> {labels.title}
                    </h1>
                    <p className="text-sm text-muted-foreground">{labels.subtitle}</p>
                </div>
                <a
                    href={urls.create}
                    className="inline-flex h-9 items-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                >
                    <Plus className="h-4 w-4 me-1" /> {labels.create}
                </a>
            </div>

            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                    <th className="px-4 py-3 text-start">#</th>
                                    <th className="px-4 py-3 text-start">{labels.name}</th>
                                    <th className="px-4 py-3 text-start">{labels.email}</th>
                                    <th className="px-4 py-3 text-start">{labels.phone}</th>
                                    <th className="px-4 py-3 text-start">{labels.status}</th>
                                    <th className="px-4 py-3 text-start">{labels.created}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {children.length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-10 text-center text-muted-foreground">
                                            {labels.empty}
                                        </td>
                                    </tr>
                                )}
                                {children.map((c, i) => (
                                    <tr key={c.id} className="border-b border-border/60 hover:bg-muted/20">
                                        <td className="px-4 py-3 text-muted-foreground">{i + 1}</td>
                                        <td className="px-4 py-3 font-medium">{c.name}</td>
                                        <td className="px-4 py-3">{c.email}</td>
                                        <td className="px-4 py-3">{c.phone}</td>
                                        <td className="px-4 py-3"><StatusPill status={c.status} /></td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {c.created_at ? new Date(c.created_at).toLocaleDateString() : ''}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
