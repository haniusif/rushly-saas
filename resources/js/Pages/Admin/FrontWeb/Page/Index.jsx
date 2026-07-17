import * as React from 'react';
import { FileText } from 'lucide-react';
import SimpleList from '../_SimpleList';

export default function PageIndex({ rows, pagination, permissions, urls, t }) {
    const columns = [
        { label: t.page_title, render: (r) => <span className="font-medium">{r.title}</span> },
        {
            label: t.slug,
            render: (r) => <code className="text-xs bg-muted px-1.5 py-0.5 rounded">{r.slug}</code>,
        },
        {
            label: t.updated,
            render: (r) => <span className="text-xs text-muted-foreground tabular-nums">{r.updated_at}</span>,
        },
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
            emptyIcon={FileText}
            emptyLabel={t.no_data}
            addLabel={t.add}
            editLabel={t.edit}
            deleteLabel={t.delete}
            actionsLabel={t.actions}
            confirmDelete=""
            canUpdate={permissions.update}
            canDelete={false}
        />
    );
}
