import * as React from 'react';
import { ExternalLink, Clock, AlertTriangle, Package } from 'lucide-react';
import ListPage, { tableHeadClass, tableRowClass, emptyRow, FilterLabel, Pill, ucwords } from '@/Components/wms/ListPage';
import { Card, CardContent } from '@/Components/ui/Card';
import { Select } from '@/Components/ui/Select';
import { cn } from '@/lib/utils';

const FUL_COLORS = {
    pending: 'grey', picking: 'amber', packing: 'sky',
    ready: 'emerald', dispatched: 'emerald', cancelled: 'rose',
};

function StatCard({ label, value, accent }) {
    return (
        <Card>
            <CardContent className="p-4">
                <div className={cn('text-3xl font-bold tabular-nums leading-none', accent)}>{value}</div>
                <div className="mt-1 text-[10px] uppercase tracking-wider text-muted-foreground font-semibold">{label}</div>
            </CardContent>
        </Card>
    );
}

export default function Index({ rows = [], pagination = {}, filters = {}, summary = {}, lookups = {}, urls = {}, t = {} }) {
    return (
        <ListPage
            t={t} urls={urls} pagination={pagination}
            filters={filters}
            defaultFilters={{ status: '', hub_id: '', sla_breached: false }}
            statsCards={
                <div className="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    <StatCard label="Pending" value={summary.pending} />
                    <StatCard label="Picking" value={summary.picking} accent="text-amber-700" />
                    <StatCard label="Packing" value={summary.packing} accent="text-sky-700" />
                    <StatCard label="Ready" value={summary.ready} accent="text-emerald-700" />
                    <StatCard label="Dispatched today" value={summary.dispatched_today} accent="text-emerald-700" />
                    <StatCard label={t.sla_breached} value={summary.sla_breached} accent="text-rose-700" />
                </div>
            }
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
                        <FilterLabel>{t.hub}</FilterLabel>
                        <Select value={draft.hub_id || ''} onChange={(e) => setDraft((d) => ({ ...d, hub_id: e.target.value }))} className="mt-1.5">
                            <option value="">{t.all}</option>
                            {(lookups.hubs || []).map((h) => <option key={h.id} value={h.id}>{h.name}</option>)}
                        </Select>
                    </div>
                    <div className="md:col-span-4 flex items-end">
                        <label className="flex items-center gap-2 text-sm font-medium pb-2">
                            <input type="checkbox" checked={!!draft.sla_breached} onChange={(e) => setDraft((d) => ({ ...d, sla_breached: e.target.checked }))} className="h-4 w-4 rounded border-input" />
                            {t.sla_breached}
                        </label>
                    </div>
                </div>
            )}
            tableContent={
                <>
                    <thead>
                        <tr className={tableHeadClass}>
                            <th className="px-4 py-3 text-start">#</th>
                            <th className="px-4 py-3 text-start">{t.fulfillment_number}</th>
                            <th className="px-4 py-3 text-start">{t.parcel}</th>
                            <th className="px-4 py-3 text-start">{t.merchant}</th>
                            <th className="px-4 py-3 text-start">{t.hub}</th>
                            <th className="px-4 py-3 text-start">{t.picker}</th>
                            <th className="px-4 py-3 text-start">{t.status}</th>
                            <th className="px-4 py-3 text-start">{t.sla}</th>
                            <th className="px-4 py-3 text-end pe-4">{t.actions}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 && emptyRow(9, t.no_rows)}
                        {rows.map((r) => (
                            <tr key={r.id} className={cn(tableRowClass, r.sla_overdue && 'bg-rose-50/30')}>
                                <td className="px-4 py-3 text-muted-foreground">{r.id}</td>
                                <td className="px-4 py-3 font-mono text-xs font-semibold">{r.fulfillment_no}</td>
                                <td className="px-4 py-3 font-mono text-xs">{r.parcel_label}</td>
                                <td className="px-4 py-3">{r.merchant || '—'}</td>
                                <td className="px-4 py-3 text-muted-foreground">{r.hub || '—'}</td>
                                <td className="px-4 py-3 text-muted-foreground">{r.picker || '—'}</td>
                                <td className="px-4 py-3"><Pill color={FUL_COLORS[r.status] || 'grey'}>{r.status_label}</Pill></td>
                                <td className="px-4 py-3 text-xs">
                                    {r.sla_overdue
                                        ? <span className="inline-flex items-center gap-1 text-rose-700"><AlertTriangle className="h-3 w-3" /> {t.overdue}</span>
                                        : <span className="inline-flex items-center gap-1 text-muted-foreground"><Clock className="h-3 w-3" /> {r.sla_deadline || '—'}</span>}
                                </td>
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
