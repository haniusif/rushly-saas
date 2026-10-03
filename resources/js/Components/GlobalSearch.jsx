import * as React from 'react';
import {
    Search, Loader2, Package, Truck, Store, Box, MessageSquare,
} from 'lucide-react';
import { Input } from '@/Components/ui/Input';
import { cn } from '@/lib/utils';

const GROUP_ICONS = {
    parcel:  Package,
    driver:  Truck,
    client:  Store,
    product: Box,
    ticket:  MessageSquare,
};
const GROUP_ACCENTS = {
    parcel:  'text-sky-600',
    driver:  'text-amber-600',
    client:  'text-emerald-600',
    product: 'text-violet-600',
    ticket:  'text-rose-600',
};

export default function GlobalSearch({ placeholder = 'Search…' }) {
    const [q, setQ]               = React.useState('');
    const [groups, setGroups]     = React.useState([]);
    const [open, setOpen]         = React.useState(false);
    const [loading, setLoading]   = React.useState(false);
    const [highlightIdx, setHighlightIdx] = React.useState(-1);
    const wrapperRef = React.useRef(null);

    // Flat list of rows in render order, so arrow keys can step through them.
    const flatRows = React.useMemo(() => groups.flatMap((g) => g.rows.map((r) => ({ ...r, _group: g.label }))), [groups]);

    // Debounced search.
    React.useEffect(() => {
        if (q.trim().length < 2) {
            setGroups([]);
            return;
        }
        let alive = true;
        setLoading(true);
        const timer = setTimeout(() => {
            const params = new URLSearchParams({ q: q.trim() });
            fetch(`/admin/global-search?${params}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            })
                .then((r) => r.ok ? r.json() : { groups: [] })
                .then((d) => { if (alive) { setGroups(d.groups || []); setHighlightIdx(-1); } })
                .catch(() => { if (alive) setGroups([]); })
                .finally(() => { if (alive) setLoading(false); });
        }, 200);
        return () => { alive = false; clearTimeout(timer); };
    }, [q]);

    // Click-outside + ESC to close.
    React.useEffect(() => {
        if (!open) return;
        const onDocClick = (e) => {
            if (wrapperRef.current && !wrapperRef.current.contains(e.target)) setOpen(false);
        };
        const onKey = (e) => { if (e.key === 'Escape') setOpen(false); };
        document.addEventListener('mousedown', onDocClick);
        document.addEventListener('keydown', onKey);
        return () => {
            document.removeEventListener('mousedown', onDocClick);
            document.removeEventListener('keydown', onKey);
        };
    }, [open]);

    const onKeyDown = (e) => {
        if (!open) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setHighlightIdx((i) => Math.min(flatRows.length - 1, i + 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setHighlightIdx((i) => Math.max(0, i - 1));
        } else if (e.key === 'Enter') {
            if (highlightIdx >= 0 && flatRows[highlightIdx]) {
                e.preventDefault();
                window.location.href = flatRows[highlightIdx].url;
            }
        }
    };

    const hasResults = groups.length > 0;
    const showDropdown = open && q.trim().length >= 2;

    let rowOffset = 0;

    return (
        <div ref={wrapperRef} className="relative w-full">
            <Search className="absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground pointer-events-none" />
            <Input
                value={q}
                onChange={(e) => setQ(e.target.value)}
                onFocus={() => setOpen(true)}
                onKeyDown={onKeyDown}
                placeholder={placeholder}
                className="border-0 bg-muted/40 ps-9 h-9 pe-8"
            />
            {loading && (
                <Loader2 className="absolute end-2.5 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-muted-foreground" />
            )}

            {showDropdown && (
                <div className="absolute start-0 end-0 mt-1.5 max-h-[480px] overflow-y-auto rounded-md border border-border bg-popover shadow-lg z-50">
                    {!loading && !hasResults && (
                        <div className="px-3 py-6 text-center text-xs text-muted-foreground">
                            No results for “{q}”
                        </div>
                    )}
                    {groups.map((g, gi) => {
                        const Icon = GROUP_ICONS[Object.keys(GROUP_ICONS).find((k) =>
                            (g.label || '').toLowerCase().includes(k)
                        )] || Package;
                        const accent = GROUP_ACCENTS[Object.keys(GROUP_ACCENTS).find((k) =>
                            (g.label || '').toLowerCase().includes(k)
                        )] || 'text-muted-foreground';
                        const start = rowOffset;
                        rowOffset += g.rows.length;
                        return (
                            <div key={gi} className={cn(gi > 0 && 'border-t border-border')}>
                                <div className="flex items-center gap-1.5 px-3 py-1.5 bg-muted/30 text-[10px] uppercase tracking-wider font-semibold text-muted-foreground">
                                    <Icon className={cn('h-3 w-3', accent)} />
                                    {g.label}
                                </div>
                                {g.rows.map((row, ri) => {
                                    const idx = start + ri;
                                    return (
                                        <a
                                            key={row.id}
                                            href={row.url}
                                            onMouseEnter={() => setHighlightIdx(idx)}
                                            className={cn(
                                                'flex items-start justify-between gap-2 px-3 py-2 text-sm transition-colors',
                                                highlightIdx === idx ? 'bg-accent' : 'hover:bg-accent/60',
                                            )}
                                        >
                                            <div className="min-w-0 flex-1">
                                                <div className="font-medium truncate">{row.title}</div>
                                                {row.subtitle && (
                                                    <div className="text-xs text-muted-foreground truncate">{row.subtitle}</div>
                                                )}
                                            </div>
                                            {row.meta && (
                                                <span className="font-mono text-[10px] text-muted-foreground shrink-0 mt-0.5">{row.meta}</span>
                                            )}
                                        </a>
                                    );
                                })}
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
