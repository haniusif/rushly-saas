import * as React from 'react';
import { Eye } from 'lucide-react';
import ListPage, { tableHeadClass, tableRowClass, emptyRow, FilterLabel, Pill, ucwords } from '@/Components/wms/ListPage';
import { Select } from '@/Components/ui/Select';

const GRN_COLORS = { draft: 'grey', in_progress: 'sky', completed: 'emerald', discrepancy: 'rose' };

export default function Index({ rows = [], pagination = {}, filters = {}, lookups = {}, permissions = {}, urls = {}, t = {} }) {
    return (
        <ListPage
            t={t} urls={urls} pagination={pagination} permissions={permissions}
            filters={filters}
            defaultFilters={{ status: '', merchant_id: '', hub_id: '' }}
            filterContent={({ draft, setDraft }) => (
                <div className="grid gap-3 md:grid-cols-12">
                    <div className="md:col-span-4">
                        <FilterLabel>{t.status}</FilterLabel>
                        <Select value={draft.status || ''} onChange={(e) => setDraft((d) => ({ ...d, status: e.target.value }))} className="mt-1.5">
                            <option value="">{t.all}</option>
                            {(lookups.statuses || []).map((s) => <option key={s} value={s}>{ucwords(s)}</option>)}
                        </Select>
                    </div>
                    <div className="md:col-span-4">
                        <FilterLabel>{t.merchant}</FilterLabel>
                        <Select value={draft.merchant_id || ''} onChange={(e) => setDraft((d) => ({ ...d, merchant_id: e.target.value }))} className="mt-1.5">
                            <option value="">{t.all}</option>
                            {(lookups.merchants || []).map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                        </Select>
                    </div>
                    <div className="md:col-span-4">
                        <FilterLabel>{t.hub}</FilterLabel>
                        <Select value={draft.hub_id || ''} onChange={(e) => setDraft((d) => ({ ...d, hub_id: e.target.value }))} className="mt-1.5">
                            <option value="">{t.all}</option>
                            {(lookups.hubs || []).map((h) => <option key={h.id} value={h.id}>{h.name}</option>)}
                        </Select>
                    </div>
                </div>
            )}
            tableContent={
                <>
                    <thead>
                        <tr className={tableHeadClass}>
                            <th className="px-4 py-3 text-start">#</th>
                            <th className="px-4 py-3 text-start">{t.grn_number}</th>
                            <th className="px-4 py-3 text-start">{t.merchant}</th>
                            <th className="px-4 py-3 text-start">{t.hub}</th>
                            <th className="px-4 py-3 text-start">{t.received_by}</th>
                            <th className="px-4 py-3 text-center">{t.items}</th>
                            <th className="px-4 py-3 text-start">{t.status}</th>
                            <th className="px-4 py-3 text-start">{t.created}</th>
                            <th className="px-4 py-3 text-end pe-4">{t.actions}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 && emptyRow(9, t.no_rows)}
                        {rows.map((r) => (
                            <tr key={r.id} className={tableRowClass}>
                                <td className="px-4 py-3 text-muted-foreground">{r.id}</td>
                                <td className="px-4 py-3 font-mono font-semibold text-xs">{r.grn_number}</td>
                                <td className="px-4 py-3">{r.merchant || '—'}</td>
                                <td className="px-4 py-3 text-muted-foreground">{r.hub || '—'}</td>
                                <td className="px-4 py-3 text-muted-foreground">{r.received_by || '—'}</td>
                                <td className="px-4 py-3 text-center tabular-nums">{r.items_count}</td>
                                <td className="px-4 py-3"><Pill color={GRN_COLORS[r.status] || 'grey'}>{r.status_label}</Pill></td>
                                <td className="px-4 py-3 text-xs text-muted-foreground">{r.created_at || '—'}</td>
                                <td className="px-4 py-3 text-end pe-4">
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
