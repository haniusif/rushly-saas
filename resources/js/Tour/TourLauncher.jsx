import * as React from 'react';
import { HelpCircle, PlayCircle, X } from 'lucide-react';
import { useTourEngine } from './TourProvider';

/**
 * Topbar button that opens a small menu of tours applicable to the
 * current user. Scoped by current route when possible; otherwise all.
 */
export default function TourLauncher({ label = 'Take a tour' }) {
    const { tours, start } = useTourEngine();
    const [open, setOpen] = React.useState(false);
    const ref = React.useRef(null);

    React.useEffect(() => {
        const onDoc = (e) => { if (ref.current && !ref.current.contains(e.target)) setOpen(false); };
        document.addEventListener('mousedown', onDoc);
        return () => document.removeEventListener('mousedown', onDoc);
    }, []);

    // Nothing to launch → hide the button entirely (no dead affordance).
    if (!tours || tours.length === 0) return null;

    const current = typeof window !== 'undefined' ? window.location.pathname : '';
    // Prioritize tours whose trigger_route matches the current URL path prefix.
    const sorted = [...tours].sort((a, b) => {
        const aMatch = a.trigger_route && current.includes(a.trigger_route.replace(/^merchant-panel\./, '').replace(/\./g, '/')) ? 1 : 0;
        const bMatch = b.trigger_route && current.includes(b.trigger_route.replace(/^merchant-panel\./, '').replace(/\./g, '/')) ? 1 : 0;
        return bMatch - aMatch;
    });

    return (
        <div className="relative" ref={ref}>
            <button
                type="button"
                onClick={() => setOpen((o) => !o)}
                className="inline-flex h-9 items-center gap-1.5 rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent"
                aria-haspopup="menu"
                aria-expanded={open}
                title={label}
            >
                <HelpCircle className="h-4 w-4" />
                <span className="hidden sm:inline">{label}</span>
            </button>
            {open && (
                <div className="absolute end-0 z-50 mt-1 w-72 rounded-md border border-border bg-card shadow-lg" role="menu">
                    <div className="border-b border-border px-3 py-2 text-[11px] uppercase tracking-wider font-semibold text-muted-foreground">
                        {label}
                    </div>
                    <ul className="max-h-80 overflow-y-auto p-1">
                        {sorted.map((t) => {
                            const done = t.progress?.status === 'completed';
                            return (
                                <li key={t.key}>
                                    <button
                                        type="button"
                                        onClick={() => { setOpen(false); start(t.key); }}
                                        className="flex w-full items-center gap-2 rounded-md px-2 py-2 text-start text-sm hover:bg-muted/50"
                                    >
                                        <PlayCircle className="h-4 w-4 text-primary shrink-0" />
                                        <div className="min-w-0">
                                            <div className="truncate font-medium">{t.title}</div>
                                            {t.description && <div className="truncate text-[11px] text-muted-foreground">{t.description}</div>}
                                        </div>
                                        {done && <span className="ms-auto rounded-full bg-emerald-100 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700">✓</span>}
                                    </button>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            )}
        </div>
    );
}
