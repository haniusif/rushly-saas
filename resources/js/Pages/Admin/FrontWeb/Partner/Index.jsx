import * as React from 'react';
import { Handshake } from 'lucide-react';
import SimpleList, { StatusCell } from '../_SimpleList';

export default function PartnerIndex({ rows, pagination, permissions, urls, t }) {
    const columns = [
        {
            label: t.name,
            render: (r) => (
                <div className="flex items-center gap-3 min-w-0">
                    {r.image ? (
                        <img src={r.image} alt="" className="w-12 h-8 object-contain bg-muted rounded shrink-0" />
                    ) : (
                        <span className="grid w-12 h-8 place-items-center rounded bg-muted text-muted-foreground text-xs shrink-0">·</span>
                    )}
                    <span className="font-medium truncate">{r.name}</span>
                </div>
            ),
        },
        {
            label: t.link,
            render: (r) => r.link ? (
                <a href={r.link} target="_blank" rel="noreferrer"
                   className="text-xs text-primary hover:underline truncate max-w-xs inline-block">
                    {r.link}
                </a>
            ) : <span className="text-xs text-muted-foreground/60">—</span>,
        },
        { label: t.status, render: (r) => <StatusCell html={r.status_html} /> },
    ];
    return (
        <SimpleList
            title={t.title}
            breadcrumbs={[t.front_web, t.title]}
            rows={rows}
            columns={columns}
            pagination={pagination}
            permissions={permissions}
            urls={urls}
            countLabel={t.count_suffix}
            emptyIcon={Handshake}
            emptyLabel={t.no_data}
            addLabel={t.add}
            editLabel={t.edit}
            deleteLabel={t.delete}
            actionsLabel={t.actions}
            confirmDelete={t.confirm_delete}
            canUpdate={permissions.update}
            canDelete={permissions.delete}
        />
    );
}
