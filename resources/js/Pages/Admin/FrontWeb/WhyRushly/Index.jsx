import * as React from 'react';
import { Sparkles } from 'lucide-react';
import SimpleList, { StatusCell } from '../_SimpleList';

export default function WhyRushlyIndex({ rows, pagination, permissions, urls, t }) {
    const columns = [
        {
            label: t.title,
            render: (r) => (
                <div className="flex items-center gap-3 min-w-0">
                    {r.image ? (
                        <img src={r.image} alt="" className="w-10 h-10 rounded-md object-cover bg-muted shrink-0" />
                    ) : (
                        <span className="grid w-10 h-10 place-items-center rounded-md bg-muted text-muted-foreground text-xs shrink-0">
                            {(r.title || '·').charAt(0).toUpperCase()}
                        </span>
                    )}
                    <span className="font-medium truncate">{r.title}</span>
                </div>
            ),
        },
        { label: t.position, align: 'end', render: (r) => r.position },
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
            emptyIcon={Sparkles}
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
