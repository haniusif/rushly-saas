import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import {
    SharedDataNotice, StatusPill, FilterBar, RowActions, EmptyRow, Pager, ListHeader,
} from '@/Components/admin/ReferenceData';

export default function Index({ rows = [], pagination = {}, filters = {}, lookups = {}, permissions = {}, urls = {}, t = {} }) {
    const [countryId, setCountryId] = React.useState(filters.country_id || '');
    const del = (r) => {
        if (window.confirm(t.delete_confirm)) router.delete(r.urls.delete, { preserveScroll: true });
    };
    const showing = (t.showing_results || '')
        .replace(':from', pagination.from ?? 0)
        .replace(':to', pagination.to ?? 0)
        .replace(':total', pagination.total ?? 0);
    const cols = permissions.update || permissions.delete ? 8 : 7;

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title, t.list]}>
            <Head title={`${t.title} · ${t.list}`} />

            <SharedDataNotice>{t.shared_notice}</SharedDataNotice>

            <FilterBar
                indexUrl={urls.index}
                filters={filters}
                t={t}
                extra={{
                    name: 'country_id',
                    label: t.country,
                    value: countryId,
                    onChange: setCountryId,
                    options: lookups.countries || [],
                }}
                onExtraReset={() => setCountryId('')}
            />

            <ListHeader showing={showing} createUrl={urls.create} canCreate={permissions.create} addLabel={t.add} />

            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                    <th className="px-4 py-3 text-start">#</th>
                                    <th className="px-4 py-3 text-start">{t.name}</th>
                                    <th className="px-4 py-3 text-start">{t.en_name}</th>
                                    <th className="px-4 py-3 text-start">{t.country}</th>
                                    <th className="px-4 py-3 text-start">{t.code}</th>
                                    <th className="px-4 py-3 text-end">{t.areas}</th>
                                    <th className="px-4 py-3 text-start">{t.status}</th>
                                    {(permissions.update || permissions.delete) && <th className="px-4 py-3 text-end">{t.actions}</th>}
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 && <EmptyRow colSpan={cols} label={t.no_rows} />}
                                {rows.map((r, idx) => (
                                    <tr key={r.id} className="border-b border-border align-top last:border-0 hover:bg-muted/20">
                                        <td className="px-4 py-3 text-muted-foreground tabular-nums">{(pagination.from || 1) + idx}</td>
                                        <td className="px-4 py-3 font-medium">{r.name || '—'}</td>
                                        <td className="px-4 py-3 text-muted-foreground">{r.en_name || '—'}</td>
                                        <td className="px-4 py-3">{r.country || '—'}</td>
                                        <td className="px-4 py-3"><span className="font-mono text-xs">{r.city_code || '—'}</span></td>
                                        <td className="px-4 py-3 text-end tabular-nums">{r.areas_count}</td>
                                        <td className="px-4 py-3">
                                            <StatusPill active={r.is_active} activeLabel={t.active} inactiveLabel={t.inactive} />
                                        </td>
                                        {(permissions.update || permissions.delete) && (
                                            <td className="px-4 py-3 text-end">
                                                <RowActions row={r} permissions={permissions} t={t} onDelete={del} />
                                            </td>
                                        )}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <Pager pagination={pagination} t={t} />
        </AdminLayout>
    );
}
