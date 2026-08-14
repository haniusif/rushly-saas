import * as React from 'react';
import { Search, Download } from 'lucide-react';
import ListPage, { tableHeadClass, tableRowClass, emptyRow, FilterLabel, Pill } from '@/Components/wms/ListPage';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import { cn } from '@/lib/utils';

export default function Index({ rows = [], pagination = {}, filters = {}, lookups = {}, urls = {}, t = {} }) {
    return (
        <ListPage
            t={t} urls={urls} pagination={pagination}
            filters={filters}
            defaultFilters={{ q: '', merchant_id: '', hub_id: '', low_only: false }}
            filterContent={({ draft, setDraft }) => (
                <div className="grid gap-3 md:grid-cols-12">
                    <div className="md:col-span-4">
                        <FilterLabel>{t.search}</FilterLabel>
                        <div className="relative mt-1.5">
                            <Search className="absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input value={draft.q || ''} onChange={(e) => setDraft((d) => ({ ...d, q: e.target.value }))} className="ps-9" />
                        </div>
                    </div>
                    <div className="md:col-span-3">
                        <FilterLabel>{t.merchant}</FilterLabel>
                        <Select value={draft.merchant_id || ''} onChange={(e) => setDraft((d) => ({ ...d, merchant_id: e.target.value }))} className="mt-1.5">
                            <option value="">{t.all}</option>
                            {(lookups.merchants || []).map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                        </Select>
                    </div>
                    <div className="md:col-span-3">
                        <FilterLabel>{t.hub}</FilterLabel>
                        <Select value={draft.hub_id || ''} onChange={(e) => setDraft((d) => ({ ...d, hub_id: e.target.value }))} className="mt-1.5">
                            <option value="">{t.all}</option>
                            {(lookups.hubs || []).map((h) => <option key={h.id} value={h.id}>{h.name}</option>)}
                        </Select>
                    </div>
                    <div className="md:col-span-2 flex items-end">
                        <label className="flex items-center gap-2 text-sm font-medium pb-2">
                            <input type="checkbox" checked={!!draft.low_only} onChange={(e) => setDraft((d) => ({ ...d, low_only: e.target.checked }))} className="h-4 w-4 rounded border-input" />
                            {t.low_only}
                        </label>
                    </div>
                </div>
            )}
            headerExtras={
                <a href={urls.export} className="inline-flex h-9 items-center rounded-md border border-emerald-200 bg-emerald-50 text-emerald-700 px-3 text-sm font-medium hover:bg-emerald-100">
                    <Download className="h-4 w-4 me-1" /> {t.export}
                </a>
            }
            tableContent={
                <>
                    <thead>
                        <tr className={tableHeadClass}>
                            <th className="px-4 py-3 text-start">#</th>
                            <th className="px-4 py-3 text-start">{t.sku}</th>
                            <th className="px-4 py-3 text-start">{t.product}</th>
                            <th className="px-4 py-3 text-start">{t.location}</th>
                            <th className="px-4 py-3 text-end">{t.qty}</th>
                            <th className="px-4 py-3 text-end">{t.reserved}</th>
                            <th className="px-4 py-3 text-end">{t.available}</th>
                            <th className="px-4 py-3 text-start">{t.batch}</th>
                            <th className="px-4 py-3 text-start">{t.expiry}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 && emptyRow(9, t.no_rows)}
                        {rows.filter((r) => !filters.low_only || r.low).map((r) => (
                            <tr key={r.id} className={cn(tableRowClass, r.low && 'bg-amber-50/40')}>
                                <td className="px-4 py-3 text-muted-foreground">{r.id}</td>
                                <td className="px-4 py-3 font-mono text-xs">{r.sku || '—'}</td>
                                <td className="px-4 py-3">
                                    <div className="font-medium">{r.product_name || '—'}</div>
                                    {r.merchant && <div className="text-xs text-muted-foreground">{r.merchant}</div>}
                                </td>
                                <td className="px-4 py-3 font-mono text-xs">{r.location_code || '—'}</td>
                                <td className="px-4 py-3 text-end tabular-nums">{r.quantity}</td>
                                <td className="px-4 py-3 text-end tabular-nums text-muted-foreground">{r.reserved}</td>
                                <td className="px-4 py-3 text-end tabular-nums font-semibold">
                                    {r.available}
                                    {r.low && <Pill color="amber" className="ms-1">LOW</Pill>}
                                </td>
                                <td className="px-4 py-3 font-mono text-[11px] text-muted-foreground">{r.batch || '—'}</td>
                                <td className="px-4 py-3 text-xs text-muted-foreground">{r.expiry || '—'}</td>
                            </tr>
                        ))}
                    </tbody>
                </>
            }
        />
    );
}
