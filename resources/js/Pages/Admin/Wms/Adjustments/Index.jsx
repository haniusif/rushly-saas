import * as React from 'react';
import { Eye } from 'lucide-react';
import ListPage, { tableHeadClass, tableRowClass, emptyRow, FilterLabel, Pill, ucwords } from '@/Components/wms/ListPage';
import { Select } from '@/Components/ui/Select';
import { cn } from '@/lib/utils';

const AP_COLORS = { approved: 'emerald', pending_approval: 'amber', rejected: 'rose' };

export default function Index({ rows = [], pagination = {}, filters = {}, lookups = {}, permissions = {}, urls = {}, t = {} }) {
    return (
        <ListPage
            t={t} urls={urls} pagination={pagination} permissions={permissions}
            filters={filters}
            defaultFilters={{ reason: '', status: '' }}
            filterContent={({ draft, setDraft }) => (
                <div className="grid gap-3 md:grid-cols-12">
                    <div className="md:col-span-6">
                        <FilterLabel>{t.reason}</FilterLabel>
                        <Select value={draft.reason || ''} onChange={(e) => setDraft((d) => ({ ...d, reason: e.target.value }))} className="mt-1.5">
                            <option value="">{t.all}</option>
                            {(lookups.reasons || []).map((r) => <option key={r.value} value={r.value}>{r.label}</option>)}
                        </Select>
                    </div>
                    <div className="md:col-span-6">
                        <FilterLabel>{t.approval}</FilterLabel>
                        <Select value={draft.status || ''} onChange={(e) => setDraft((d) => ({ ...d, status: e.target.value }))} className="mt-1.5">
                            <option value="">{t.all}</option>
                            {(lookups.statuses || []).map((s) => <option key={s} value={s}>{ucwords(s)}</option>)}
                        </Select>
                    </div>
                </div>
            )}
            tableContent={
                <>
                    <thead>
                        <tr className={tableHeadClass}>
                            <th className="px-3 py-3 text-start">#</th>
                            <th className="px-3 py-3 text-start">{t.sku}</th>
                            <th className="px-3 py-3 text-start">{t.product}</th>
                            <th className="px-3 py-3 text-start">{t.location}</th>
                            <th className="px-3 py-3 text-end">{t.before}</th>
                            <th className="px-3 py-3 text-end">{t.change}</th>
                            <th className="px-3 py-3 text-end">{t.after}</th>
                            <th className="px-3 py-3 text-start">{t.reason}</th>
                            <th className="px-3 py-3 text-start">{t.approval}</th>
                            <th className="px-3 py-3 text-start">{t.by}</th>
                            <th className="px-3 py-3 text-start">{t.when}</th>
                            <th className="px-3 py-3 text-end pe-4">{t.actions}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 && emptyRow(12, t.no_rows)}
                        {rows.map((r) => (
                            <tr key={r.id} className={tableRowClass}>
                                <td className="px-3 py-3 text-muted-foreground">{r.id}</td>
                                <td className="px-3 py-3 font-mono text-xs">{r.sku || '—'}</td>
                                <td className="px-3 py-3">{r.product_name || '—'}</td>
                                <td className="px-3 py-3 font-mono text-xs">{r.location_code || '—'}</td>
                                <td className="px-3 py-3 text-end tabular-nums">{r.before}</td>
                                <td className={cn('px-3 py-3 text-end tabular-nums font-semibold',
                                    r.change > 0 ? 'text-emerald-700' : r.change < 0 ? 'text-rose-700' : 'text-muted-foreground')}>
                                    {r.change > 0 ? '+' : ''}{r.change}
                                </td>
                                <td className="px-3 py-3 text-end tabular-nums">{r.after}</td>
                                <td className="px-3 py-3"><Pill color="violet">{r.reason_label}</Pill></td>
                                <td className="px-3 py-3"><Pill color={AP_COLORS[r.approval_status] || 'grey'}>{ucwords(r.approval_status)}</Pill></td>
                                <td className="px-3 py-3 text-muted-foreground">{r.created_by || '—'}</td>
                                <td className="px-3 py-3 text-xs text-muted-foreground">{r.created_at || '—'}</td>
                                <td className="px-3 py-3 text-end pe-4">
                                    <a href={r.url} className="inline-flex h-8 items-center rounded-md border border-input bg-background px-2.5 text-xs font-medium hover:bg-accent">
                                        <Eye className="h-3.5 w-3.5 me-1" /> {t.view}
                                    </a>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </>
            }
        />
    );
}
