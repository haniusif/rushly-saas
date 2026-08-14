import * as React from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, MapPin, Maximize2, Minimize2, AlertCircle, Truck } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';

// Shared loader (idempotent): same as Hub/Create + Tms/Index
function loadGoogleMaps(apiKey) {
    if (typeof window === 'undefined') return Promise.reject(new Error('no window'));
    if (window.google?.maps?.places) return Promise.resolve(window.google.maps);
    if (window.__rlMapsLoading) return window.__rlMapsLoading;
    window.__rlMapsLoading = new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.async = true; s.defer = true;
        s.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=places`;
        s.onload  = () => resolve(window.google.maps);
        s.onerror = () => reject(new Error('Failed to load Google Maps'));
        document.head.appendChild(s);
    });
    return window.__rlMapsLoading;
}

function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function buildInfoHtml(p, currency, t) {
    const money = Number(p.current_payable || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2, maximumFractionDigits: 2,
    });
    return `
        <div style="font-size:12px;line-height:1.4;min-width:240px;max-width:300px;padding:4px 2px">
            <div style="font-weight:600;font-size:13px;color:#0f172a;margin-bottom:4px">
                ${escapeHtml(p.tracking_id || '—')}
            </div>
            <div style="display:flex;justify-content:space-between;gap:6px;margin-bottom:6px">
                <span style="color:#64748b;font-size:11px">${escapeHtml(t.amount || 'Amount')}</span>
                <span style="font-weight:600;color:#16a34a">${escapeHtml(currency || '')} ${money}</span>
            </div>
            <div style="border-top:1px solid #e2e8f0;padding-top:6px;color:#334155">
                <div><strong>${escapeHtml(t.customer || 'Customer')}:</strong> ${escapeHtml(p.customer_name || '—')}</div>
                ${p.customer_phone ? `<div><strong>${escapeHtml(t.phone || 'Phone')}:</strong> ${escapeHtml(p.customer_phone)}</div>` : ''}
                ${p.customer_address ? `<div style="margin-top:2px"><strong>${escapeHtml(t.address || 'Address')}:</strong> ${escapeHtml(p.customer_address)}</div>` : ''}
                ${p.merchant ? `<div style="margin-top:4px;color:#64748b;font-size:11px"><strong>${escapeHtml(t.merchant || 'Merchant')}:</strong> ${escapeHtml(p.merchant)}</div>` : ''}
            </div>
            ${p.url ? `<div style="margin-top:6px"><a href="${escapeHtml(p.url)}" style="color:#2563eb;font-weight:500">${escapeHtml(t.open_logs || 'Open logs')} →</a></div>` : ''}
        </div>
    `;
}

function MapView({ apiKey, points, currency, t }) {
    const containerRef = React.useRef(null);
    const wrapRef      = React.useRef(null);
    const mapRef       = React.useRef(null);
    const infoRef      = React.useRef(null);
    const [status, setStatus] = React.useState(apiKey ? 'loading' : 'no-key');
    const [isFs, setIsFs]     = React.useState(false);

    React.useEffect(() => {
        if (!apiKey) return;
        let alive = true;
        loadGoogleMaps(apiKey)
            .then(() => { if (alive) setStatus('ready'); })
            .catch(() => { if (alive) setStatus('error'); });
        return () => { alive = false; };
    }, [apiKey]);

    // Initialise map + markers once API is ready.
    React.useEffect(() => {
        if (status !== 'ready' || !containerRef.current || mapRef.current) return;
        const maps = window.google.maps;

        const haveAny = points.length > 0;
        const center = haveAny ? { lat: points[0].lat, lng: points[0].lng } : { lat: 24.7136, lng: 46.6753 };

        mapRef.current = new maps.Map(containerRef.current, {
            center, zoom: haveAny ? 10 : 5,
            mapTypeControl: false, streetViewControl: false, fullscreenControl: false,
        });
        infoRef.current = new maps.InfoWindow();

        const bounds = new maps.LatLngBounds();
        points.forEach((p) => {
            const marker = new maps.Marker({
                position: { lat: p.lat, lng: p.lng },
                map: mapRef.current,
                title: p.tracking_id || '',
            });
            marker.addListener('click', () => {
                infoRef.current.setContent(buildInfoHtml(p, currency, t));
                infoRef.current.open({ map: mapRef.current, anchor: marker });
            });
            bounds.extend({ lat: p.lat, lng: p.lng });
        });

        if (points.length > 1) {
            mapRef.current.fitBounds(bounds, 40);
        }
    }, [status, points, currency, t]);

    // Fullscreen toggle via the native Fullscreen API on the wrapper element.
    React.useEffect(() => {
        const onFsChange = () => setIsFs(document.fullscreenElement === wrapRef.current);
        document.addEventListener('fullscreenchange', onFsChange);
        return () => document.removeEventListener('fullscreenchange', onFsChange);
    }, []);

    const toggleFs = () => {
        if (!wrapRef.current) return;
        if (document.fullscreenElement) {
            document.exitFullscreen?.();
        } else {
            wrapRef.current.requestFullscreen?.();
        }
    };

    if (status === 'no-key') {
        return (
            <div className="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 flex items-start gap-2">
                <AlertCircle className="h-4 w-4 mt-0.5 shrink-0" />
                <span>{t.no_map_key}</span>
            </div>
        );
    }
    if (status === 'error') {
        return (
            <div className="rounded-md border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive">
                {t.map_load_failed || 'Failed to load Google Maps.'}
            </div>
        );
    }

    return (
        <div ref={wrapRef} className="relative rounded-md overflow-hidden border border-border bg-background" style={{ height: isFs ? '100vh' : '70vh' }}>
            {status === 'loading' && (
                <div className="absolute inset-0 grid place-items-center bg-muted/30 z-10 text-sm text-muted-foreground">
                    {t.loading_map || 'Loading map…'}
                </div>
            )}
            <div ref={containerRef} className="h-full w-full" />
            <button
                type="button"
                onClick={toggleFs}
                className="absolute top-3 end-3 z-20 inline-flex items-center gap-1 rounded-md bg-background/95 backdrop-blur-sm border border-border px-2.5 py-1.5 text-xs font-medium shadow-sm hover:bg-accent transition-colors"
                title={isFs ? t.exit_fullscreen : t.fullscreen}
            >
                {isFs ? <Minimize2 className="h-3.5 w-3.5" /> : <Maximize2 className="h-3.5 w-3.5" />}
                {isFs ? t.exit_fullscreen : t.fullscreen}
            </button>

            {points.length === 0 && (
                <div className="absolute bottom-4 start-4 end-4 mx-auto max-w-md rounded-md bg-background/95 backdrop-blur-sm border border-border px-4 py-3 text-sm text-muted-foreground shadow-sm flex items-start gap-2">
                    <MapPin className="h-4 w-4 mt-0.5 shrink-0" />
                    <span>{t.no_points}</span>
                </div>
            )}
        </div>
    );
}

export default function Map({ points = [], total = 0, plotted = 0, google_maps_key = '', currency = '', urls = {}, t = {} }) {
    const missing = Math.max(total - plotted, 0);

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title, t.map]}>
            <Head title={`${t.title} · ${t.map}`} />

            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <Link href={urls.index} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.title}
                </Link>
                <div className="flex items-center gap-2 text-sm">
                    <span className="inline-flex items-center gap-1 rounded-full bg-sky-100 text-sky-700 px-3 py-1 text-xs font-medium">
                        <Truck className="h-3.5 w-3.5" /> {t.plotted}: <span className="font-mono">{plotted}</span>
                    </span>
                    {missing > 0 && (
                        <span className="inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-700 px-3 py-1 text-xs font-medium">
                            <AlertCircle className="h-3.5 w-3.5" /> {t.no_coords}: <span className="font-mono">{missing}</span>
                        </span>
                    )}
                </div>
            </div>

            <Card>
                <CardContent className="p-3">
                    <MapView apiKey={google_maps_key} points={points} currency={currency} t={t} />
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
