import * as React from 'react';
import { Eye, FileCode, Search } from 'lucide-react';
import ListPage, { tableHeadClass, tableRowClass, emptyRow, FilterLabel, Pill } from '@/Components/wms/ListPage';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import { Button } from '@/Components/ui/Button';

export default function Index({ rows = [], pagination = {}, filters = {}, lookups = {}, permissions = {}, urls = {}, t = {} }) {
    return (
        <ListPage
            t={t} urls={urls} pagination={pagination} permissions={permissions}
            filters={filters}
            defaultFilters={{ q: '', merchant_id: '', hub_id: '' }}
            filterContent={({ draft, setDraft }) => (
                <div className="grid gap-3 md:grid-cols-12">
                    <div className="md:col-span-5">
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
                </div>
            )}
            tableContent={
                <>
                    <thead>
                        <tr className={tableHeadClass}>
                            <th className="px-4 py-3 text-start">#</th>
                            <th className="px-4 py-3 text-start">{t.sku}</th>
                            <th className="px-4 py-3 text-start">{t.name}</th>
                            <th className="px-4 py-3 text-start">{t.merchant}</th>
                            <th className="px-4 py-3 text-start">{t.hub}</th>
                            <th className="px-4 py-3 text-start">{t.barcode}</th>
                            <th className="px-4 py-3 text-end">{t.on_hand}</th>
                            <th className="px-4 py-3 text-start">{t.status}</th>
                            <th className="px-4 py-3 text-end pe-4">{t.actions}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 && emptyRow(9, t.no_rows)}
                        {rows.map((r) => (
                            <tr key={r.id} className={tableRowClass}>
                                <td className="px-4 py-3 text-muted-foreground">{r.id}</td>
                                <td className="px-4 py-3 font-mono text-xs">{r.sku || '—'}</td>
                                <td className="px-4 py-3 font-medium">{r.name}</td>
                                <td className="px-4 py-3 text-muted-foreground">{r.merchant || '—'}</td>
                                <td className="px-4 py-3 text-muted-foreground">{r.hub || '—'}</td>
                                <td className="px-4 py-3 font-mono text-xs">{r.barcode || '—'}</td>
                                <td className="px-4 py-3 text-end tabular-nums">
                                    {r.on_hand}
                                    {r.reorder_point > 0 && (
                                        <div className="text-[10px] text-muted-foreground">≥ {r.reorder_point}</div>
                                    )}
                                </td>
                                <td className="px-4 py-3">
                                    <Pill color={r.low ? 'rose' : 'emerald'}>{r.low ? t.low : t.ok}</Pill>
                                </td>
                                <td className="px-4 py-3 text-end pe-4">
                                    <a href={r.urls.view} className="inline-flex h-8 items-center rounded-md border border-input bg-background px-2.5 text-xs font-medium hover:bg-accent me-1">
                                        <Eye className="h-3.5 w-3.5 me-1" /> {t.view}
                                    </a>
                                    <a href={r.urls.barcode} target="_blank" rel="noreferrer" className="inline-flex h-8 items-center rounded-md border border-input bg-background px-2.5 text-xs font-medium hover:bg-accent">
                                        <FileCode className="h-3.5 w-3.5" />
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
