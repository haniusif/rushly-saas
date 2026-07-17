import * as React from 'react';
import { HelpCircle } from 'lucide-react';
import SimpleList, { StatusCell } from '../_SimpleList';

export default function FaqIndex({ rows, pagination, permissions, urls, t }) {
    const columns = [
        { label: t.title, render: (r) => <div className="font-medium max-w-md line-clamp-2">{r.question}</div> },
        {
            label: t.description ?? 'Answer',
            render: (r) => (
                <div className="text-xs text-muted-foreground max-w-md line-clamp-2"
                     dangerouslySetInnerHTML={{ __html: r.answer_html }} />
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
            emptyIcon={HelpCircle}
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
