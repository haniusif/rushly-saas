import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { Filter, Eraser, Plus, ChevronLeft, ChevronRight, Boxes } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { cn } from '@/lib/utils';

/**
 * Shared chrome used by every WMS index page: AdminLayout wrapper, optional
 * stats row, optional filter form, header strip (showing + Add CTA + extras),
 * the table card, and Prev/Next pagination.
 *
 * The page passes filterContent (the form fields), tableContent (thead + tbody),
 * and optional statsCards / headerExtras. The shared bits live here.
 */
export default function ListPage({
    title, breadcrumbs, t = {}, urls = {}, pagination = {}, filters = {}, defaultFilters,
    permissions = {}, statsCards, filterContent, headerExtras, tableContent,
    addLabelKey = 'add',
}) {
    const [draft, setDraft] = React.useState({ ...filters });
    const [submitting, setSubmitting] = React.useState(false);

    React.useEffect(() => { setDraft({ ...filters }); }, [JSON.stringify(filters)]); // eslint-disable-line

    const submitFilter = (e) => {
        e?.preventDefault?.();
        setSubmitting(true);
        router.get(urls.index, draft, {
            preserveState: true, preserveScroll: true, replace: true,
            onFinish: () => setSubmitting(false),
        });
    };
    const clear = () => {
        const empty = defaultFilters || Object.keys(filters).reduce((a, k) => ({ ...a, [k]: '' }), {});
        setDraft(empty);
        router.get(urls.index, {}, { preserveState: false });
    };
    const goPage = (url) => url && router.get(url, {}, { preserveState: true });

    const showing = (t.showing_results || 'Showing :from – :to of :total')
        .replace(':from', pagination.from ?? 0)
        .replace(':to', pagination.to ?? 0)
        .replace(':total', pagination.total ?? 0);

    return (
        <AdminLayout title={title || t.title} breadcrumbs={breadcrumbs || [t.title, t.list]}>
            <Head title={`${t.title} · ${t.list}`} />

            {statsCards && <div className="mb-5">{statsCards}</div>}

            {filterContent && (
                <Card className="mb-5">
                    <CardContent className="pt-6">
                        <form onSubmit={submitFilter}>
                            {typeof filterContent === 'function' ? filterContent({ draft, setDraft }) : filterContent}
                            <div className="mt-3 flex items-center justify-end gap-2">
                                <Button type="button" variant="outline" onClick={clear}>
                                    <Eraser className="h-4 w-4 me-1" /> {t.clear}
                                </Button>
                                <Button type="submit" disabled={submitting}>
                                    <Filter className="h-4 w-4 me-1" /> {t.filter}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            )}

            <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Boxes className="h-4 w-4" />
                    <span>{showing}</span>
                </div>
                <div className="flex items-center gap-2">
                    {headerExtras}
                    {permissions.create && urls.create && (
                        <a href={urls.create} className="inline-flex h-9 items-center justify-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors">
                            <Plus className="h-4 w-4 me-1" /> {t[addLabelKey] || t.add}
                        </a>
                    )}
                </div>
            </div>

            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">{tableContent}</table>
                    </div>
                </CardContent>
            </Card>

            {pagination.last_page > 1 && (
                <div className="mt-4 flex items-center justify-between text-sm">
                    <div className="text-muted-foreground">{showing}</div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" disabled={!pagination.prev_url} onClick={() => goPage(pagination.prev_url)}>
                            <ChevronLeft className="h-4 w-4 me-1" /> Prev
                        </Button>
                        <span className="text-xs text-muted-foreground">{pagination.current_page} / {pagination.last_page}</span>
                        <Button variant="outline" size="sm" disabled={!pagination.next_url} onClick={() => goPage(pagination.next_url)}>
                            Next <ChevronRight className="h-4 w-4 ms-1" />
                        </Button>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}

export const tableHeadClass = 'border-b border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground';
export const tableRowClass = 'border-b border-border last:border-0 hover:bg-muted/20 transition-colors';
export const emptyRow = (cols, label) => (
    <tr><td colSpan={cols} className="px-4 py-10 text-center text-muted-foreground">{label}</td></tr>
);

export function ucwords(s) {
    return String(s || '').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

export function FilterLabel({ children }) {
    return <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{children}</label>;
}

export function Pill({ children, color = 'grey', className }) {
    const styles = {
        grey:   'bg-slate-100 text-slate-700 border-slate-200',
        amber:  'bg-amber-100 text-amber-800 border-amber-200',
        sky:    'bg-sky-100 text-sky-800 border-sky-200',
        emerald:'bg-emerald-100 text-emerald-800 border-emerald-200',
        rose:   'bg-rose-100 text-rose-800 border-rose-200',
        violet: 'bg-violet-100 text-violet-800 border-violet-200',
        blue:   'bg-blue-100 text-blue-800 border-blue-200',
    };
    return (
        <span className={cn(
            'inline-flex items-center rounded-md border px-2 py-0.5 text-[11px] font-medium',
            styles[color] || styles.grey, className,
        )}>
            {children}
        </span>
    );
}
