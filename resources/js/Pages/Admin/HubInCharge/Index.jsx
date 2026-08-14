import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    Building2, Users, ArrowLeft, Plus, Edit3, Trash2, Mail, Phone, Hash,
    CheckCircle2, Circle, UserCheck,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';

function StatusBadge({ active, t }) {
    return active ? (
        <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 text-emerald-700 px-2 py-0.5 text-[10px] font-medium">
            <CheckCircle2 className="h-3 w-3" /> {t.active}
        </span>
    ) : (
        <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 text-slate-700 px-2 py-0.5 text-[10px] font-medium">
            <Circle className="h-3 w-3" /> {t.inactive}
        </span>
    );
}

function Avatar({ name, image }) {
    const initials = String(name || '?').split(/\s+/).map((p) => p[0]).filter(Boolean).slice(0, 2).join('').toUpperCase();
    if (image) {
        return <img src={image} alt={name} className="h-10 w-10 rounded-full object-cover border border-border" />;
    }
    return (
        <div className="h-10 w-10 rounded-full bg-primary/10 text-primary grid place-items-center font-semibold text-xs">
            {initials || '—'}
        </div>
    );
}

export default function Index({ hub = {}, rows = [], permissions = {}, urls = {}, t = {} }) {
    const onDelete = (row) => {
        if (!window.confirm(t.delete_confirm)) return;
        router.delete(row.urls.destroy, { preserveScroll: true });
    };
    const onAssignActive = (row) => {
        router.get(row.urls.assigned, {}, { preserveScroll: true });
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.hubs, hub.name || '—', t.title]}>
            <Head title={`${t.title} · ${hub.name || ''}`} />

            {/* Toolbar */}
            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <Link href={urls.hub_view} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.back_to_hub}
                </Link>
                {permissions.create && (
                    <Link href={urls.create} className="inline-flex h-9 items-center rounded-md bg-primary text-primary-foreground px-3 text-sm font-medium hover:bg-primary/90">
                        <Plus className="h-4 w-4 me-1" /> {t.add}
                    </Link>
                )}
            </div>

            {/* Hub identity strip */}
            <Card className="mb-5">
                <CardContent className="p-5 flex items-center gap-4">
                    <div className="grid h-12 w-12 place-items-center rounded-md bg-primary/10 text-primary shrink-0">
                        <Building2 className="h-6 w-6" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <div className="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground">{t.hub}</div>
                        <div className="text-lg font-bold leading-tight truncate">{hub.name || '—'}</div>
                        <div className="mt-0.5 text-xs text-muted-foreground flex flex-wrap items-center gap-3">
                            {hub.phone && <span className="font-mono">{hub.phone}</span>}
                            {hub.address && <span>· {hub.address}</span>}
                        </div>
                    </div>
                    <div className="text-end">
                        <div className="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground">{t.title}</div>
                        <div className="text-2xl font-bold tabular-nums text-primary">{rows.length}</div>
                    </div>
                </CardContent>
            </Card>

            {/* In-charges list */}
            {rows.length === 0 ? (
                <Card>
                    <CardContent className="p-12 text-center">
                        <Users className="h-10 w-10 text-muted-foreground/40 mx-auto mb-3" />
                        <p className="text-sm text-muted-foreground mb-3">{t.no_rows}</p>
                        {permissions.create && (
                            <Link href={urls.create} className="inline-flex h-9 items-center rounded-md bg-primary text-primary-foreground px-3 text-sm font-medium hover:bg-primary/90">
                                <Plus className="h-4 w-4 me-1" /> {t.add}
                            </Link>
                        )}
                    </CardContent>
                </Card>
            ) : (
                <Card>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/40 text-[10px] uppercase tracking-wider text-muted-foreground border-b border-border">
                                    <tr>
                                        <th className="px-4 py-3 text-start font-semibold">{t.name}</th>
                                        <th className="px-4 py-3 text-start font-semibold">{t.email}</th>
                                        <th className="px-4 py-3 text-start font-semibold">{t.phone}</th>
                                        <th className="px-4 py-3 text-start font-semibold">{t.unique_id}</th>
                                        <th className="px-4 py-3 text-start font-semibold">{t.status}</th>
                                        <th className="px-4 py-3 text-end font-semibold">{t.actions}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map((r) => (
                                        <tr key={r.id} className="border-b border-border last:border-0 hover:bg-muted/30">
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-3">
                                                    <Avatar name={r.name} image={r.image} />
                                                    <div className="min-w-0">
                                                        <div className="font-medium truncate">{r.name || '—'}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-xs">
                                                {r.email ? (
                                                    <span className="inline-flex items-center gap-1.5">
                                                        <Mail className="h-3 w-3 text-muted-foreground" /> {r.email}
                                                    </span>
                                                ) : <span className="text-muted-foreground">—</span>}
                                            </td>
                                            <td className="px-4 py-3 text-xs">
                                                {r.mobile ? (
                                                    <span className="inline-flex items-center gap-1.5 font-mono">
                                                        <Phone className="h-3 w-3 text-muted-foreground" /> {r.mobile}
                                                    </span>
                                                ) : <span className="text-muted-foreground">—</span>}
                                            </td>
                                            <td className="px-4 py-3 text-xs">
                                                {r.unique_id ? (
                                                    <span className="inline-flex items-center gap-1 font-mono">
                                                        <Hash className="h-3 w-3 text-muted-foreground" /> {r.unique_id}
                                                    </span>
                                                ) : <span className="text-muted-foreground">—</span>}
                                            </td>
                                            <td className="px-4 py-3"><StatusBadge active={r.is_active} t={t} /></td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center justify-end gap-1">
                                                    {permissions.assigned && !r.is_active && (
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => onAssignActive(r)}
                                                            title={t.assign_active}
                                                            className="text-emerald-700 border-emerald-200 hover:bg-emerald-50"
                                                        >
                                                            <UserCheck className="h-3.5 w-3.5" />
                                                        </Button>
                                                    )}
                                                    {permissions.update && (
                                                        <Link
                                                            href={r.urls.edit}
                                                            title={t.edit}
                                                            className="inline-flex h-8 w-8 items-center justify-center rounded-md border border-input bg-background hover:bg-accent"
                                                        >
                                                            <Edit3 className="h-3.5 w-3.5" />
                                                        </Link>
                                                    )}
                                                    {permissions.delete && (
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => onDelete(r)}
                                                            title={t.delete}
                                                            className="text-rose-600 border-rose-200 hover:bg-rose-50"
                                                        >
                                                            <Trash2 className="h-3.5 w-3.5" />
                                                        </Button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            )}
        </AdminLayout>
    );
}
