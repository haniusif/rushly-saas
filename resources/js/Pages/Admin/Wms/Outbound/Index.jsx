import * as React from 'react';
import { ExternalLink } from 'lucide-react';
import ListPage, { tableHeadClass, tableRowClass, emptyRow, FilterLabel, Pill, ucwords } from '@/Components/wms/ListPage';
import { Select } from '@/Components/ui/Select';

const OB_COLORS = { pending: 'amber', processing: 'sky', completed: 'emerald', cancelled: 'rose' };
const TYPE_COLORS = { fulfillment: 'sky', manual: 'grey', transfer: 'violet', return_to_merchant: 'amber' };

export default function Index({ rows = [], pagination = {}, filters = {}, lookups = {}, permissions = {}, urls = {}, t = {} }) {
    return (
        <ListPage
            t={t} urls={urls} pagination={pagination} permissions={permissions}
            filters={filters}
            defaultFilters={{ type: '', status: '', merchant_id: '' }}
            filterContent={({ draft, setDraft }) => (
                <div className="grid gap-3 md:grid-cols-12">
                    <div className="md:col-span-4">
                        <FilterLabel>{t.type}</FilterLabel>
                        <Select value={draft.type || ''} onChange={(e) => setDraft((d) => ({ ...d, type: e.target.value }))} className="mt-1.5">
                            <option value="">{t.all}</option>
                            {(lookups.types || []).map((tp) => <option key={tp} value={tp}>{ucwords(tp)}</option>)}
                        </Select>
                    </div>
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
                </div>
            )}
            tableContent={
                <>
                    <thead>
                        <tr className={tableHeadClass}>
                            <th className="px-4 py-3 text-start">#</th>
                            <th className="px-4 py-3 text-start">{t.outbound_number}</th>
                            <th className="px-4 py-3 text-start">{t.type}</th>
                            <th className="px-4 py-3 text-start">{t.merchant}</th>
                            <th className="px-4 py-3 text-start">{t.hub}</th>
                            <th className="px-4 py-3 text-start">{t.processed_by}</th>
                            <th className="px-4 py-3 text-start">{t.status}</th>
                            <th className="px-4 py-3 text-start">{t.completed}</th>
                            <th className="px-4 py-3 text-end pe-4">{t.actions}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 && emptyRow(9, t.no_rows)}
                        {rows.map((r) => (
                            <tr key={r.id} className={tableRowClass}>
                                <td className="px-4 py-3 text-muted-foreground">{r.id}</td>
                                <td className="px-4 py-3 font-mono text-xs font-semibold">{r.outbound_number}</td>
                                <td className="px-4 py-3"><Pill color={TYPE_COLORS[r.type] || 'grey'}>{ucwords(r.type)}</Pill></td>
                                <td className="px-4 py-3">{r.merchant || '—'}</td>
                                <td className="px-4 py-3 text-muted-foreground">{r.hub || '—'}</td>
                                <td className="px-4 py-3 text-muted-foreground">{r.processed_by || '—'}</td>
                                <td className="px-4 py-3"><Pill color={OB_COLORS[r.status] || 'grey'}>{r.status_label}</Pill></td>
                                <td className="px-4 py-3 text-xs text-muted-foreground">{r.completed_at || '—'}</td>
                                <td className="px-4 py-3 text-end pe-4">
                                    <a href={r.url} className="inline-flex h-8 items-center rounded-md border border-input bg-background px-2.5 text-xs font-medium hover:bg-accent">
                                        <ExternalLink className="h-3.5 w-3.5 me-1" /> {t.open}
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
