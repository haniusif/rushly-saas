import * as React from 'react';
import { Newspaper } from 'lucide-react';
import SimpleList, { StatusCell } from '../_SimpleList';

export default function BlogIndex({ rows, pagination, permissions, urls, t }) {
    const columns = [
        {
            label: t.blog_title,
            render: (r) => (
                <div className="flex items-center gap-3 min-w-0">
                    {r.image ? (
                        <img src={r.image} alt="" className="w-12 h-12 rounded-lg object-cover bg-muted shrink-0" />
                    ) : (
                        <span className="grid w-12 h-12 place-items-center rounded-lg bg-muted text-muted-foreground text-xs shrink-0">
                            {(r.title || '·').charAt(0).toUpperCase()}
                        </span>
                    )}
                    <div className="min-w-0">
                        <div className="font-medium line-clamp-1">{r.title}</div>
                        <div className="text-xs text-muted-foreground mt-0.5">{t.position}: {r.position}</div>
                    </div>
                </div>
            ),
        },
        {
            label: t.description,
            render: (r) => (
                <div className="text-xs text-muted-foreground max-w-md line-clamp-2">{r.description_plain}</div>
            ),
        },
        { label: t.created_by, render: (r) => r.author ?? '—' },
        { label: t.date, render: (r) => <span className="text-xs text-muted-foreground tabular-nums">{r.created_at}</span> },
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
            emptyIcon={Newspaper}
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
