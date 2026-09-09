import * as React from 'react';

/**
 * Google Maps pin picker, shared by the shipment form and the merchant
 * pickup-point form.
 *
 * Writes to form.data.lat / form.data.long, which is what both callers
 * already store - the shops repository maps those onto merchant_lat and
 * merchant_long. Degrades to a notice when the tenant has no Maps key,
 * leaving the coordinates editable by hand.
 */
function loadGoogleMaps(apiKey) {
    if (typeof window === 'undefined') return Promise.resolve(null);
    if (window.google && window.google.maps) return Promise.resolve(window.google.maps);
    if (gmapsPromise) {
        if (apiKey && gmapsKey && apiKey !== gmapsKey) {
            // eslint-disable-next-line no-console
            console.warn('Google Maps loader already initialized with a different key; reusing existing load.');
        }
        return gmapsPromise;
    }
    gmapsKey = apiKey;
    gmapsPromise = new Promise((resolve, reject) => {
        const cb = `__gmaps_ready_${Math.random().toString(36).slice(2)}`;
        window[cb] = () => { resolve(window.google.maps); try { delete window[cb]; } catch (e) {} };
        const script = document.createElement('script');
        const params = new URLSearchParams({
            key: apiKey || '',
            callback: cb,
            libraries: 'places',
            loading: 'async',
        });
        script.src = `https://maps.googleapis.com/maps/api/js?${params.toString()}`;
        script.async = true;
        script.defer = true;
        script.onerror = () => reject(new Error('Failed to load Google Maps JS API'));
        document.head.appendChild(script);
    });
    return gmapsPromise;
}

/**
 * Larger drop-off picker (spec: "map should have a larger height").
 * Clicking the map places the marker; dragging updates the coord fields.
 */
export default function LocationPicker({ form, defaultCenter, labels, apiKey }) {
    const mapEl = React.useRef(null);
    const mapObj = React.useRef(null);
    const dropoffMarker = React.useRef(null);
    const [status, setStatus] = React.useState(apiKey ? 'loading' : 'no-key');

    const dropLat  = parseFloat(form.data.lat);
    const dropLong = parseFloat(form.data.long);

    React.useEffect(() => {
        if (!apiKey) return;
        let cancelled = false;
        loadGoogleMaps(apiKey).then((maps) => {
            if (cancelled || !maps || !mapEl.current || mapObj.current) return;
            const initial = Number.isFinite(dropLat) && Number.isFinite(dropLong)
                ? { lat: dropLat, lng: dropLong }
                : { lat: defaultCenter[0], lng: defaultCenter[1] };
            const map = new maps.Map(mapEl.current, {
                center: initial,
                zoom: 12,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false,
            });
            mapObj.current = map;

            const dropIcon = {
                path: maps.SymbolPath.CIRCLE,
                fillColor: '#dc2626', fillOpacity: 1,
                strokeColor: '#ffffff', strokeWeight: 2, scale: 8,
            };

            const placeDropoff = (lat, lng) => {
                if (dropoffMarker.current) {
                    dropoffMarker.current.setPosition({ lat, lng });
                } else {
                    dropoffMarker.current = new maps.Marker({
                        position: { lat, lng }, map, icon: dropIcon,
                        draggable: true, title: labels.dropoff_pin || 'Drop-off',
                    });
                    dropoffMarker.current.addListener('dragend', (e) => {
                        const p = e.latLng;
                        form.setData((d) => ({ ...d, lat: p.lat().toFixed(6), long: p.lng().toFixed(6) }));
                    });
                }
            };
            if (Number.isFinite(dropLat) && Number.isFinite(dropLong)) placeDropoff(dropLat, dropLong);

            map.addListener('click', (ev) => {
                const lat = ev.latLng.lat();
                const lng = ev.latLng.lng();
                placeDropoff(lat, lng);
                form.setData((d) => ({ ...d, lat: lat.toFixed(6), long: lng.toFixed(6) }));
            });

            setStatus('ready');
        }).catch(() => setStatus('error'));

        return () => {
            cancelled = true;
            mapObj.current = null;
            dropoffMarker.current = null;
        };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [apiKey]);

    return (
        <div className="space-y-2">
            <div className="flex items-center gap-2 text-xs">
                <span className="inline-flex items-center gap-1.5 rounded-md border border-rose-200 bg-rose-50 dark:bg-rose-950/40 dark:border-rose-900 px-2.5 py-1.5 font-medium text-rose-700 dark:text-rose-200">
                    <span className="inline-block h-2 w-2 rounded-full bg-rose-500" />
                    {labels.dropoff_pin || 'Drop-off'}
                </span>
                <span className="ms-auto text-[11px] text-muted-foreground">
                    {labels.map_hint || 'Click on the map to place the drop-off pin, or drag it to adjust.'}
                </span>
            </div>
            {status === 'no-key' ? (
                <div className="rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-800 p-3 text-xs text-amber-800 dark:text-amber-200">
                    {labels.no_key || 'Google Maps API key not configured for this workspace. Set it under Settings → Google Map to enable the map. Coordinates below are still editable.'}
                </div>
            ) : status === 'error' ? (
                <div className="rounded-xl border border-rose-300 bg-rose-50 dark:bg-rose-950/30 dark:border-rose-800 p-3 text-xs text-rose-800 dark:text-rose-200">
                    {labels.load_error || 'Google Maps failed to load. Check the API key + billing status.'}
                </div>
            ) : (
                <div
                    ref={mapEl}
                    className="h-80 lg:h-96 w-full rounded-xl border border-border bg-muted overflow-hidden"
                    style={{ minHeight: '320px' }}
                />
            )}
            <div className="text-[11px] text-muted-foreground">
                <span className="font-semibold text-foreground">{labels.dropoff_pin || 'Drop-off'}:</span>{' '}
                {form.data.lat && form.data.long ? `${form.data.lat}, ${form.data.long}` : '—'}
            </div>
        </div>
    );
}
