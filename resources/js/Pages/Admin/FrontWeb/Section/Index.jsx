import * as React from 'react';
import { LayoutGrid } from 'lucide-react';
import SimpleList from '../_SimpleList';

export default function SectionIndex({ rows, pagination, permissions, urls, t }) {
    const columns = [
        { label: t.type, render: (r) => <span className="font-medium">{r.my_type || r.type}</span> },
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
            emptyIcon={LayoutGrid}
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
