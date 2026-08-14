import * as React from 'react';

/*
 * Tiny paginator that renders Laravel's linkCollection (mapped) and forwards
 * navigation to the parent via Inertia's <a href>. Used across the merchant
 * panel list pages so each port doesn't roll its own.
 */
export default function Pagination({ pagination }) {
    if (!pagination) return null;
    const { from, to, total, links = [] } = pagination;
    if (!total) return null;

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-xs text-muted-foreground border-t border-border">
            <div>
                Showing <span className="font-medium text-foreground">{from || 0}</span>
                {' '}to <span className="font-medium text-foreground">{to || 0}</span>
                {' '}of <span className="font-medium text-foreground">{total}</span>
            </div>
            <ul className="flex gap-1 items-center list-none p-0 m-0">
                {links.map((l, i) => (
                    <li key={i}>
                        {l.url ? (
                            <a
                                href={l.url}
                                className={`px-2.5 h-7 inline-flex items-center rounded-md border text-xs no-underline ${
                                    l.active
                                        ? 'bg-primary text-primary-foreground border-primary'
                                        : 'border-input hover:bg-muted/40'
                                }`}
                                dangerouslySetInnerHTML={{ __html: l.label }}
                            />
                        ) : (
                            <span
                                className="px-2.5 h-7 inline-flex items-center rounded-md border border-input/40 text-xs text-muted-foreground"
                                dangerouslySetInnerHTML={{ __html: l.label }}
                            />
                        )}
                    </li>
                ))}
            </ul>
        </div>
    );
}
