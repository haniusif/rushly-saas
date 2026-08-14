import * as React from 'react';
import { Eye } from 'lucide-react';
import ListPage, { tableHeadClass, tableRowClass, emptyRow, Pill, ucwords } from '@/Components/wms/ListPage';

export default function Index({ rows = [], pagination = {}, permissions = {}, urls = {}, t = {} }) {
    return (
        <ListPage
            t={t} urls={urls} pagination={pagination} permissions={permissions}
            tableContent={
                <>
                    <thead>
                        <tr className={tableHeadClass}>
                            <th className="px-4 py-3 text-start">#</th>
                            <th className="px-4 py-3 text-start">{t.sku}</th>
                            <th className="px-4 py-3 text-start">{t.product}</th>
                            <th className="px-4 py-3 text-start">{t.location}</th>
                            <th className="px-4 py-3 text-end">{t.qty}</th>
                            <th className="px-4 py-3 text-start">{t.cause}</th>
                            <th className="px-4 py-3 text-start">{t.action}</th>
                            <th className="px-4 py-3 text-start">{t.reported_by}</th>
                            <th className="px-4 py-3 text-start">{t.when}</th>
                            <th className="px-4 py-3 text-end pe-4">{t.actions}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 && emptyRow(10, t.no_rows)}
                        {rows.map((r) => (
                            <tr key={r.id} className={tableRowClass}>
                                <td className="px-4 py-3 text-muted-foreground">{r.id}</td>
                                <td className="px-4 py-3 font-mono text-xs">{r.sku || '—'}</td>
                                <td className="px-4 py-3">{r.product_name || '—'}</td>
                                <td className="px-4 py-3 font-mono text-xs">{r.location_code || '—'}</td>
                                <td className="px-4 py-3 text-end tabular-nums">{r.quantity}</td>
                                <td className="px-4 py-3"><Pill color="rose">{ucwords(r.cause)}</Pill></td>
                                <td className="px-4 py-3">
                                    {r.action_taken ? <Pill color="sky">{ucwords(r.action_taken)}</Pill> : <span className="text-muted-foreground">—</span>}
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">{r.reported_by || '—'}</td>
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
