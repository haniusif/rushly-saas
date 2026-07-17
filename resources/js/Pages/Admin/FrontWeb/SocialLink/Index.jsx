import * as React from 'react';
import { Link as LinkIcon } from 'lucide-react';
import SimpleList, { StatusCell } from '../_SimpleList';

export default function SocialLinkIndex({ rows, pagination, permissions, urls, t }) {
    const columns = [
        {
            label: t.name,
            render: (r) => (
                <div className="flex items-center gap-2 font-medium">
                    {r.icon && <i className={`${r.icon} text-muted-foreground`} />}
                    <span>{r.name}</span>
                </div>
            ),
        },
        {
            label: t.icon,
            render: (r) => <code className="text-xs bg-muted px-1.5 py-0.5 rounded">{r.icon || '—'}</code>,
        },
        {
            label: t.link,
            render: (r) => (
                <a href={r.link} target="_blank" rel="noreferrer"
                   className="text-xs text-primary hover:underline truncate max-w-xs inline-block">
                    {r.link}
                </a>
            ),
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
            emptyIcon={LinkIcon}
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
