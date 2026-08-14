import * as React from 'react';
import { Edit, Map } from 'lucide-react';
import ListPage, { tableHeadClass, tableRowClass, emptyRow, FilterLabel, Pill } from '@/Components/wms/ListPage';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';

export default function Index({ rows = [], pagination = {}, filters = {}, lookups = {}, permissions = {}, urls = {}, t = {} }) {
    return (
        <ListPage
            t={t} urls={urls} pagination={pagination} permissions={permissions}
            filters={filters}
            defaultFilters={{ hub_id: '', zone: '', aisle: '', type: '' }}
            filterContent={({ draft, setDraft }) => (
                <div className="grid gap-3 md:grid-cols-12">
                    <div className="md:col-span-3">
                        <FilterLabel>{t.hub}</FilterLabel>
                        <Select value={draft.hub_id || ''} onChange={(e) => setDraft((d) => ({ ...d, hub_id: e.target.value }))} className="mt-1.5">
                            <option value="">{t.all}</option>
                            {(lookups.hubs || []).map((h) => <option key={h.id} value={h.id}>{h.name}</option>)}
                        </Select>
                    </div>
                    <div className="md:col-span-3">
                        <FilterLabel>{t.zone}</FilterLabel>
                        <Input value={draft.zone || ''} onChange={(e) => setDraft((d) => ({ ...d, zone: e.target.value }))} className="mt-1.5" />
                    </div>
                    <div className="md:col-span-3">
                        <FilterLabel>{t.aisle}</FilterLabel>
                        <Input value={draft.aisle || ''} onChange={(e) => setDraft((d) => ({ ...d, aisle: e.target.value }))} className="mt-1.5" />
                    </div>
                    <div className="md:col-span-3">
                        <FilterLabel>{t.type}</FilterLabel>
                        <Select value={draft.type || ''} onChange={(e) => setDraft((d) => ({ ...d, type: e.target.value }))} className="mt-1.5">
                            <option value="">{t.all}</option>
                            {(lookups.types || []).map((tp) => <option key={tp.value} value={tp.value}>{tp.label}</option>)}
                        </Select>
                    </div>
                </div>
            )}
            headerExtras={
                <a href={urls.map} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent">
                    <Map className="h-4 w-4 me-1" /> {t.map_view}
                </a>
            }
            tableContent={
                <>
                    <thead>
                        <tr className={tableHeadClass}>
                            <th className="px-4 py-3 text-start">#</th>
                            <th className="px-4 py-3 text-start">{t.code}</th>
                            <th className="px-4 py-3 text-start">{t.hub}</th>
                            <th className="px-4 py-3 text-start">{t.zone}</th>
                            <th className="px-4 py-3 text-start">{t.aisle}</th>
                            <th className="px-4 py-3 text-start">{t.rack}</th>
                            <th className="px-4 py-3 text-start">{t.shelf}</th>
                            <th className="px-4 py-3 text-start">{t.bin}</th>
                            <th className="px-4 py-3 text-start">{t.type}</th>
                            <th className="px-4 py-3 text-end">{t.capacity}</th>
                            <th className="px-4 py-3 text-end pe-4">{t.actions}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 && emptyRow(11, t.no_rows)}
                        {rows.map((r) => (
                            <tr key={r.id} className={tableRowClass}>
                                <td className="px-4 py-3 text-muted-foreground">{r.id}</td>
                                <td className="px-4 py-3 font-mono text-xs font-semibold">{r.code}</td>
                                <td className="px-4 py-3">{r.hub || '—'}</td>
                                <td className="px-4 py-3 text-muted-foreground">{r.zone || '—'}</td>
                                <td className="px-4 py-3 text-muted-foreground">{r.aisle || '—'}</td>
                                <td className="px-4 py-3 text-muted-foreground">{r.rack || '—'}</td>
                                <td className="px-4 py-3 text-muted-foreground">{r.shelf || '—'}</td>
                                <td className="px-4 py-3 text-muted-foreground">{r.bin || '—'}</td>
                                <td className="px-4 py-3"><Pill color="blue">{r.type}</Pill></td>
                                <td className="px-4 py-3 text-end tabular-nums">{r.capacity ?? '—'}</td>
                                <td className="px-4 py-3 text-end pe-4">
                                    <a href={r.url} className="inline-flex h-8 items-center rounded-md border border-input bg-background px-2.5 text-xs font-medium hover:bg-accent">
                                        <Edit className="h-3.5 w-3.5 me-1" /> {t.edit}
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
