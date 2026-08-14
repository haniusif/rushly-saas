import * as React from 'react';
import { ExternalLink } from 'lucide-react';
import ListPage, { tableHeadClass, tableRowClass, emptyRow, Pill, ucwords } from '@/Components/wms/ListPage';

const CC_COLORS = { open: 'amber', in_progress: 'sky', completed: 'emerald' };

export default function Index({ rows = [], pagination = {}, permissions = {}, urls = {}, t = {} }) {
    return (
        <ListPage
            t={t} urls={urls} pagination={pagination} permissions={permissions}
            tableContent={
                <>
                    <thead>
                        <tr className={tableHeadClass}>
                            <th className="px-4 py-3 text-start">#</th>
                            <th className="px-4 py-3 text-start">{t.count_number}</th>
                            <th className="px-4 py-3 text-start">{t.hub}</th>
                            <th className="px-4 py-3 text-start">{t.scope}</th>
                            <th className="px-4 py-3 text-start">{t.assigned}</th>
                            <th className="px-4 py-3 text-start">{t.status}</th>
                            <th className="px-4 py-3 text-start">{t.started}</th>
                            <th className="px-4 py-3 text-start">{t.completed}</th>
                            <th className="px-4 py-3 text-end pe-4">{t.actions}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 && emptyRow(9, t.no_rows)}
                        {rows.map((r) => (
                            <tr key={r.id} className={tableRowClass}>
                                <td className="px-4 py-3 text-muted-foreground">{r.id}</td>
                                <td className="px-4 py-3 font-mono text-xs font-semibold">{r.count_number}</td>
                                <td className="px-4 py-3">{r.hub || '—'}</td>
                                <td className="px-4 py-3">
                                    {r.scope || '—'}
                                    {r.zone && <span className="ms-2 text-xs text-muted-foreground">· {r.zone}</span>}
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">{r.assigned_to || '—'}</td>
                                <td className="px-4 py-3"><Pill color={CC_COLORS[r.status] || 'grey'}>{r.status_label}</Pill></td>
                                <td className="px-4 py-3 text-xs text-muted-foreground">{r.started_at || '—'}</td>
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
