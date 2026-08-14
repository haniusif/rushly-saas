import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import {
    Calendar, Filter, Printer, MapPin, Wifi, WifiOff, Users,
    User as UserIcon, AlertCircle, Phone, Truck, Maximize2, Minimize2,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { cn } from '@/lib/utils';

const STAT_GRADIENTS = {
    'New Shipments':     'bg-gradient-to-br from-purple-600 to-indigo-700',
    'Ready for pick-up': 'bg-gradient-to-br from-rose-500 to-orange-500',
    'Picked':            'bg-gradient-to-br from-amber-500 to-yellow-500',
    'OFD':               'bg-gradient-to-br from-emerald-500 to-lime-500',
    'Not Delivered':     'bg-gradient-to-br from-slate-600 to-slate-800',
    'Delivered':         'bg-gradient-to-br from-emerald-600 to-emerald-700',
};

// Lazy Google Maps loader — injects the JS API script once, returns a promise.
function loadGoogleMaps(apiKey) {
    if (typeof window === 'undefined') return Promise.reject(new Error('no window'));
    if (window.google?.maps) return Promise.resolve(window.google.maps);
    if (window.__tmsMapsLoading) return window.__tmsMapsLoading;
    window.__tmsMapsLoading = new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.async = true; s.defer = true;
        s.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}`;
        s.onload  = () => resolve(window.google.maps);
        s.onerror = () => reject(new Error('Failed to load Google Maps'));
        document.head.appendChild(s);
    });
    return window.__tmsMapsLoading;
}

function useGoogleMaps(apiKey) {
    const [state, setState] = React.useState({ status: apiKey ? 'loading' : 'no-key', maps: null });
    React.useEffect(() => {
        if (!apiKey) return;
        let alive = true;
        loadGoogleMaps(apiKey)
            .then((maps) => { if (alive) setState({ status: 'ready', maps }); })
            .catch(() => { if (alive) setState({ status: 'error', maps: null }); });
        return () => { alive = false; };
    }, [apiKey]);
    return state;
}

function MapPanel({ apiKey, locations, hubs, t }) {
    const { status, maps } = useGoogleMaps(apiKey);
    const containerRef = React.useRef(null);
    const mapRef = React.useRef(null);
    const markersRef = React.useRef([]);

    // When any ancestor enters or exits fullscreen, the map's container
    // changes size — tell Google Maps to redraw to the new dimensions.
    React.useEffect(() => {
        const onChange = () => {
            if (mapRef.current && maps) {
                setTimeout(() => maps.event.trigger(mapRef.current, 'resize'), 100);
            }
        };
        document.addEventListener('fullscreenchange', onChange);
        return () => document.removeEventListener('fullscreenchange', onChange);
    }, [maps]);

    // Initialise map once.
    React.useEffect(() => {
        if (status !== 'ready' || !containerRef.current || mapRef.current) return;
        const first = locations.find((l) => l.lat && l.lng) || hubs.find((h) => h.hub_lat && h.hub_long);
        const center = first
            ? { lat: first.lat || first.hub_lat, lng: first.lng || first.hub_long }
            : { lat: 24.7136, lng: 46.6753 }; // Riyadh fallback
        mapRef.current = new maps.Map(containerRef.current, {
            center, zoom: 11,
            mapTypeControl: false, streetViewControl: false,
        });
    }, [status]); // eslint-disable-line react-hooks/exhaustive-deps

    // Render markers whenever data changes.
    React.useEffect(() => {
        if (status !== 'ready' || !mapRef.current) return;
        // Clear old.
        markersRef.current.forEach((m) => m.setMap(null));
        markersRef.current = [];
        const bounds = new maps.LatLngBounds();

        locations.forEach((l) => {
            if (!l.lat || !l.lng) return;
            const marker = new maps.Marker({
                position: { lat: l.lat, lng: l.lng },
                map: mapRef.current,
                title: `${l.name}${l.mobile ? ' · ' + l.mobile : ''}`,
                icon: {
                    path: maps.SymbolPath.CIRCLE,
                    scale: 8,
                    fillColor: Number(l.status) === 1 ? '#10b981' : '#ef4444',
                    fillOpacity: 1,
                    strokeColor: '#fff',
                    strokeWeight: 2,
                },
            });
            markersRef.current.push(marker);
            bounds.extend(marker.getPosition());
        });

        hubs.forEach((h) => {
            if (!h.hub_lat || !h.hub_long) return;
            const marker = new maps.Marker({
                position: { lat: h.hub_lat, lng: h.hub_long },
                map: mapRef.current,
                title: h.name,
                icon: {
                    path: 'M -10 0 L 0 -16 L 10 0 L 0 -4 z',
                    scale: 1.2, fillColor: '#2563eb', fillOpacity: 1,
                    strokeColor: '#fff', strokeWeight: 2, anchor: new maps.Point(0, 0),
                },
            });
            markersRef.current.push(marker);
            bounds.extend(marker.getPosition());
        });

        if (!bounds.isEmpty() && markersRef.current.length > 1) {
            mapRef.current.fitBounds(bounds, 40);
        }
    }, [status, locations, hubs]); // eslint-disable-line react-hooks/exhaustive-deps

    if (status === 'no-key') {
        return (
            <div className="grid h-[calc(100vh-260px)] place-items-center rounded-md border border-amber-200 bg-amber-50 p-6 text-center text-sm text-amber-800">
                <div>
                    <AlertCircle className="mx-auto h-6 w-6 mb-2" />
                    {t.no_map_key}
                </div>
            </div>
        );
    }
    if (status === 'error') {
        return (
            <div className="grid h-[calc(100vh-260px)] place-items-center rounded-md border border-destructive/30 bg-destructive/5 p-6 text-center text-sm text-destructive">
                Failed to load Google Maps.
            </div>
        );
    }
    return (
        <div className="relative h-[calc(100vh-260px)] rounded-md overflow-hidden border border-border bg-card">
            {status === 'loading' && (
                <div className="absolute inset-0 grid place-items-center bg-muted/30 z-10 text-sm text-muted-foreground">
                    Loading map…
                </div>
            )}
            <div ref={containerRef} className="h-full w-full" />
        </div>
    );
}

export default function Index({
    date = '', stats = {}, grouped = [], locations = [], with_shipments = [],
    without_shipments = [], hubs = [], counters = {}, urls = {},
    google_maps_key = '', t = {},
}) {
    const [filterDate, setFilterDate] = React.useState(date || '');
    const [selected, setSelected] = React.useState([]);
    const [tab, setTab] = React.useState('with'); // 'with' | 'without'

    // Page-level fullscreen: wrap the entire TMS view (stats + map + side panels)
    // in a fullscreen-aware container so the user can pop the whole dashboard
    // out of the AdminLayout chrome and into the OS-level fullscreen.
    const fsRef = React.useRef(null);
    const [isFull, setIsFull] = React.useState(false);
    React.useEffect(() => {
        const onChange = () => setIsFull(document.fullscreenElement === fsRef.current);
        document.addEventListener('fullscreenchange', onChange);
        return () => document.removeEventListener('fullscreenchange', onChange);
    }, []);
    const toggleFullscreen = async () => {
        if (!fsRef.current) return;
        try {
            if (document.fullscreenElement) await document.exitFullscreen();
            else await fsRef.current.requestFullscreen();
        } catch { /* user denied or unsupported */ }
    };

    const submitFilter = (e) => {
        e?.preventDefault?.();
        router.get(urls.index, { date: filterDate }, { preserveState: true, replace: true });
    };

    const toggle = (id) => setSelected((s) => s.includes(id) ? s.filter((x) => x !== id) : [...s, id]);
    const toggleAll = () => {
        const allIds = with_shipments.map((d) => d.driver_id);
        setSelected((s) => s.length === allIds.length ? [] : allIds);
    };

    const printBulkSelected = () => {
        if (selected.length === 0) return;
        const params = new URLSearchParams();
        selected.forEach((id) => params.append('drivers[]', id));
        if (filterDate) params.append('date', filterDate);
        window.open(`${urls.runsheet_bulk}?${params}`, '_blank');
    };

    const driverRunsheet = (driverId) => {
        const url = urls.driver_runsheet.replace(/\/0$/, `/${driverId}`);
        const q = filterDate ? `?date=${encodeURIComponent(filterDate)}` : '';
        window.open(`${url}${q}`, '_blank');
    };

    return (
        <AdminLayout title={t.title}>
            <Head title={t.title} />

            <div
                ref={fsRef}
                className={cn(isFull && 'h-screen w-screen overflow-auto bg-background p-4 md:p-6')}
            >
            {/* Filter bar */}
            <Card className="mb-4">
                <CardContent className="pt-6">
                    <form onSubmit={submitFilter} className="flex flex-wrap items-end gap-3">
                        <div className="grow max-w-xs">
                            <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{t.date}</label>
                            <div className="relative mt-1.5">
                                <Calendar className="absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input type="date" value={filterDate} onChange={(e) => setFilterDate(e.target.value)} className="ps-9" />
                            </div>
                        </div>
                        <Button type="submit"><Filter className="h-4 w-4 me-1" /> {t.filter}</Button>
                        <Button type="button" variant="outline" onClick={toggleFullscreen} className="ms-auto">
                            {isFull
                                ? <><Minimize2 className="h-4 w-4 me-1" /> {t.exit_fullscreen || 'Exit fullscreen'}</>
                                : <><Maximize2 className="h-4 w-4 me-1" /> {t.fullscreen || 'Fullscreen'}</>}
                        </Button>
                    </form>
                </CardContent>
            </Card>

            {/* Stat cards */}
            <div className="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                {Object.entries(stats).map(([label, value]) => (
                    <Card key={label}>
                        <CardContent className={cn('p-4 text-white rounded-md', STAT_GRADIENTS[label] || 'bg-gradient-to-br from-slate-500 to-slate-700')}>
                            <div className="text-3xl font-bold tabular-nums leading-none">{value}</div>
                            <div className="mt-1 text-[11px] uppercase tracking-wider opacity-90 font-semibold">{label}</div>
                        </CardContent>
                    </Card>
                ))}
            </div>

            {/* Grid: map + side panels */}
            <div className="grid gap-4 lg:grid-cols-12">
                {/* Map */}
                <div className="lg:col-span-7">
                    <MapPanel apiKey={google_maps_key} locations={locations} hubs={hubs} t={t} />
                </div>

                {/* Drivers panel */}
                <div className="lg:col-span-3">
                    <Card>
                        <CardContent className="p-0 flex flex-col h-[calc(100vh-260px)]">
                            {/* Tabs */}
                            <div className="flex border-b border-border">
                                <button type="button" onClick={() => { setTab('with'); setSelected([]); }}
                                    className={cn('flex-1 px-3 py-2.5 text-xs font-semibold transition-colors',
                                        tab === 'with' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground')}
                                >
                                    {t.with_shipments} ({with_shipments.length})
                                </button>
                                <button type="button" onClick={() => { setTab('without'); setSelected([]); }}
                                    className={cn('flex-1 px-3 py-2.5 text-xs font-semibold transition-colors',
                                        tab === 'without' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground')}
                                >
                                    {t.without_shipments} ({without_shipments.length})
                                </button>
                            </div>

                            {/* Bulk action bar (only when tab=with + selection > 0) */}
                            {tab === 'with' && selected.length > 0 && (
                                <div className="bg-sky-50 border-b border-sky-200 px-3 py-2 flex items-center justify-between text-xs">
                                    <span className="font-semibold text-sky-700">
                                        {(t.selected || ':n selected').replace(':n', selected.length)}
                                    </span>
                                    <Button type="button" size="sm" onClick={printBulkSelected}>
                                        <Printer className="h-3.5 w-3.5 me-1" /> {t.print_bulk}
                                    </Button>
                                </div>
                            )}

                            {/* Select-all (tab=with only) */}
                            {tab === 'with' && with_shipments.length > 0 && (
                                <label className="flex items-center gap-2 border-b border-border bg-muted/30 px-3 py-2 text-xs font-semibold text-muted-foreground cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={selected.length === with_shipments.length && with_shipments.length > 0}
                                        onChange={toggleAll}
                                        className="h-3.5 w-3.5 rounded border-input"
                                    />
                                    {t.select_all}
                                </label>
                            )}

                            <div className="flex-1 overflow-y-auto">
                                {tab === 'with' ? (
                                    with_shipments.length === 0
                                        ? <div className="py-8 text-center text-xs text-muted-foreground">{t.no_drivers}</div>
                                        : with_shipments.map((d) => (
                                            <div key={d.driver_id} className={cn(
                                                'flex items-center justify-between gap-2 border-b border-border px-3 py-2.5 transition-colors',
                                                selected.includes(d.driver_id) && 'bg-sky-50/50',
                                            )}>
                                                <div className="flex min-w-0 items-center gap-2">
                                                    <input type="checkbox" checked={selected.includes(d.driver_id)}
                                                        onChange={() => toggle(d.driver_id)} className="h-3.5 w-3.5 rounded border-input" />
                                                    <UserIcon className="h-5 w-5 text-amber-600 shrink-0" />
                                                    <div className="min-w-0">
                                                        <div className="text-sm font-medium truncate">{d.name}</div>
                                                        <div className="text-[10px] text-muted-foreground truncate">{d.mobile || '—'}</div>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-2 shrink-0">
                                                    <div className="text-end text-xs">
                                                        <div className="font-semibold tabular-nums">{d.shipment_count} {t.shipments}</div>
                                                        <div className="text-[10px] text-muted-foreground tabular-nums">
                                                            {d.total_pending}P · {d.total_delivered}D
                                                        </div>
                                                    </div>
                                                    <Button type="button" variant="ghost" size="icon" onClick={() => driverRunsheet(d.driver_id)} title={t.print_runsheet} className="h-7 w-7">
                                                        <Printer className="h-3.5 w-3.5" />
                                                    </Button>
                                                </div>
                                            </div>
                                        ))
                                ) : (
                                    without_shipments.length === 0
                                        ? <div className="py-8 text-center text-xs text-muted-foreground">{t.no_drivers}</div>
                                        : without_shipments.map((d, i) => (
                                            <div key={i} className="flex items-center gap-2 border-b border-border px-3 py-2.5">
                                                <UserIcon className="h-5 w-5 text-rose-500 shrink-0" />
                                                <div className="min-w-0">
                                                    <div className="text-sm font-medium truncate">{d.name}</div>
                                                    <div className="text-[10px] text-muted-foreground truncate">{d.mobile || '—'}</div>
                                                </div>
                                            </div>
                                        ))
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Status side panel */}
                <div className="lg:col-span-2 space-y-3">
                    <Card>
                        <CardContent className="p-4 space-y-2 text-sm">
                            <div className="flex items-center justify-between border-b border-border pb-2">
                                <span className="inline-flex items-center gap-1.5 text-emerald-700 font-semibold">
                                    <Wifi className="h-3.5 w-3.5" /> {t.online}
                                </span>
                                <span className="font-semibold tabular-nums">{counters.online}</span>
                            </div>
                            <div className="flex items-center justify-between border-b border-border pb-2">
                                <span className="inline-flex items-center gap-1.5 text-rose-700 font-semibold">
                                    <WifiOff className="h-3.5 w-3.5" /> {t.offline}
                                </span>
                                <span className="font-semibold tabular-nums">{counters.offline}</span>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="inline-flex items-center gap-1.5 text-muted-foreground font-semibold">
                                    <Users className="h-3.5 w-3.5" /> {t.total}
                                </span>
                                <span className="font-semibold tabular-nums">{counters.total}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-4">
                            <div className="mb-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">{t.shipment_status}</div>
                            {grouped.length === 0 && <div className="text-xs text-muted-foreground">—</div>}
                            {grouped.map((g) => (
                                <div key={g.name} className="flex items-center justify-between border-b border-border py-1.5 text-sm last:border-0">
                                    <span>{g.name}</span>
                                    <span className="font-semibold text-rose-600 tabular-nums">{g.count}</span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </div>
            </div>
            </div>
        </AdminLayout>
    );
}
