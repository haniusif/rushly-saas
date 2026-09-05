import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
    Save, ArrowLeft, AlertCircle, Building2, Phone, MapPin, Crosshair,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';
import { cn } from '@/lib/utils';

function Field({ icon: Icon, label, required, error, hint, children, className }) {
    return (
        <div className={cn('space-y-1.5', className)}>
            <Label className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {Icon && <Icon className="h-3 w-3" />}
                {label}
                {required && <span className="text-destructive">*</span>}
            </Label>
            {children}
            {hint && <p className="text-[11px] text-muted-foreground">{hint}</p>}
            {error && (
                <p className="text-xs text-destructive flex items-center gap-1">
                    <AlertCircle className="h-3 w-3" /> {error}
                </p>
            )}
        </div>
    );
}

// Shared Google Maps loader — also requests the `places` library so the
// address input can use Places autocomplete.
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

function HubMap({ apiKey, lat, lng, onPick, addressInputRef, onAddressPick, t }) {
    const containerRef = React.useRef(null);
    const mapRef       = React.useRef(null);
    const markerRef    = React.useRef(null);
    const acRef        = React.useRef(null);
    const [status, setStatus] = React.useState(apiKey ? 'loading' : 'no-key');

    React.useEffect(() => {
        if (!apiKey) return;
        let alive = true;
        loadGoogleMaps(apiKey)
            .then(() => { if (alive) setStatus('ready'); })
            .catch(() => { if (alive) setStatus('error'); });
        return () => { alive = false; };
    }, [apiKey]);

    // Initialise the map + marker + autocomplete once the API is ready.
    React.useEffect(() => {
        if (status !== 'ready' || !containerRef.current || mapRef.current) return;
        const maps = window.google.maps;
        const hasCoords = Number.isFinite(lat) && Number.isFinite(lng);
        const center = hasCoords ? { lat, lng } : { lat: 24.7136, lng: 46.6753 }; // Riyadh fallback

        mapRef.current = new maps.Map(containerRef.current, {
            center, zoom: hasCoords ? 14 : 5,
            mapTypeControl: false, streetViewControl: false,
        });
        markerRef.current = new maps.Marker({
            position: center,
            map: mapRef.current,
            draggable: true,
        });

        markerRef.current.addListener('dragend', (e) => {
            const p = e.latLng;
            onPick({ lat: p.lat(), lng: p.lng() });
        });
        mapRef.current.addListener('click', (e) => {
            const p = e.latLng;
            markerRef.current.setPosition(p);
            onPick({ lat: p.lat(), lng: p.lng() });
        });

        if (addressInputRef?.current) {
            acRef.current = new maps.places.Autocomplete(addressInputRef.current, {
                fields: ['formatted_address', 'geometry'],
            });
            acRef.current.bindTo('bounds', mapRef.current);
            acRef.current.addListener('place_changed', () => {
                const place = acRef.current.getPlace();
                if (!place?.geometry?.location) return;
                const p = place.geometry.location;
                const next = { lat: p.lat(), lng: p.lng() };
                mapRef.current.setCenter(next);
                mapRef.current.setZoom(15);
                markerRef.current.setPosition(next);
                onPick(next);
                onAddressPick?.(place.formatted_address || '');
            });
        }
    }, [status]); // eslint-disable-line react-hooks/exhaustive-deps

    // Sync the marker when the parent updates lat/lng (manual typing or
    // "Use my location"). Skip when values aren't finite numbers.
    React.useEffect(() => {
        if (status !== 'ready' || !mapRef.current || !markerRef.current) return;
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
        const p = { lat, lng };
        markerRef.current.setPosition(p);
        mapRef.current.panTo(p);
    }, [status, lat, lng]);

    if (status === 'no-key') {
        return (
            <div className="rounded-md border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800 flex items-start gap-2">
                <AlertCircle className="h-4 w-4 mt-0.5 shrink-0" />
                <span>{t.no_map_key}</span>
            </div>
        );
    }
    if (status === 'error') {
        return (
            <div className="rounded-md border border-destructive/30 bg-destructive/5 p-4 text-xs text-destructive">
                Failed to load Google Maps.
            </div>
        );
    }
    return (
        <div className="relative h-72 rounded-md overflow-hidden border border-border">
            {status === 'loading' && (
                <div className="absolute inset-0 grid place-items-center bg-muted/30 z-10 text-xs text-muted-foreground">
                    Loading map…
                </div>
            )}
            <div ref={containerRef} className="h-full w-full" />
        </div>
    );
}

export default function Create({ urls = {}, lookups = {}, google_maps_key = '', mode = 'create', hub = null, t = {} }) {
    const isEdit = mode === 'edit';
    const form = useForm({
        name:    hub?.name    ?? '',
        phone:   hub?.phone   ?? '',
        address: hub?.address ?? '',
        city_id: hub?.city_id ?? '',
        lat:     hub?.lat     ?? '',
        long:    hub?.long    ?? '',
        status:  hub?.status  ?? 1,
        ...(isEdit ? { id: hub?.id, _method: 'put' } : {}),
    });

    const addressInputRef = React.useRef(null);
    const [locating, setLocating] = React.useState(false);

    const useCurrentLocation = () => {
        if (!navigator.geolocation) return;
        setLocating(true);
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                form.setData('lat',  pos.coords.latitude.toFixed(6));
                form.setData('long', pos.coords.longitude.toFixed(6));
                setLocating(false);
            },
            () => setLocating(false),
            { enableHighAccuracy: true, timeout: 10000 },
        );
    };

    const onMapPick = ({ lat, lng }) => {
        form.setData('lat',  lat.toFixed(6));
        form.setData('long', lng.toFixed(6));
    };
    const onAddressPick = (formatted) => {
        if (formatted) form.setData('address', formatted);
    };

    const submit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true });
    };

    const latNum  = parseFloat(form.data.lat);
    const longNum = parseFloat(form.data.long);

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title_index, t.title]}>
            <Head title={t.title} />

            <form onSubmit={submit} className="space-y-5">
                <div className="grid gap-5 lg:grid-cols-2">
                    {/* Left: form fields */}
                    <div className="space-y-5">
                        <Card>
                            <CardContent className="pt-6">
                                <div className="mb-4 text-sm font-semibold tracking-tight">Hub details</div>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field icon={Building2} label={t.name} required error={form.errors.name}>
                                        <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} maxLength={191} />
                                    </Field>
                                    <Field icon={Phone} label={t.phone} required error={form.errors.phone}>
                                        <Input value={form.data.phone} onChange={(e) => form.setData('phone', e.target.value)} inputMode="tel" />
                                    </Field>
                                    <Field icon={MapPin} label={t.address} required error={form.errors.address} hint={t.address_hint} className="md:col-span-2">
                                        <Input ref={addressInputRef} value={form.data.address}
                                            onChange={(e) => form.setData('address', e.target.value)} maxLength={191} />
                                    </Field>
                                    {/* The hub's city is the pickup origin a 3PL
                                        carrier is given. Picked from the list
                                        rather than typed, so it maps to a real
                                        carrier region instead of being matched
                                        on a free-text name. */}
                                    <Field icon={Building2} label={t.city} error={form.errors.city_id} hint={t.city_hint} className="md:col-span-2">
                                        <Select value={form.data.city_id ?? ''}
                                            onChange={(e) => form.setData('city_id', e.target.value)}>
                                            <option value="">—</option>
                                            {(lookups.cities || []).map((c) => (
                                                <option key={c.id} value={c.id}>{c.label}</option>
                                            ))}
                                        </Select>
                                    </Field>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="pt-6">
                                <div className="mb-4 flex items-center justify-between">
                                    <div className="text-sm font-semibold tracking-tight">Coordinates</div>
                                    <Button type="button" variant="outline" size="sm" onClick={useCurrentLocation} disabled={locating}>
                                        <Crosshair className="h-4 w-4 me-1" /> {locating ? '…' : 'Use my location'}
                                    </Button>
                                </div>
                                <p className="mb-3 text-[11px] text-muted-foreground">{t.coord_hint}</p>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field label={t.lat} error={form.errors.lat}>
                                        <Input value={form.data.lat} onChange={(e) => form.setData('lat', e.target.value)} className="font-mono" placeholder="24.7136" />
                                    </Field>
                                    <Field label={t.long} error={form.errors.long}>
                                        <Input value={form.data.long} onChange={(e) => form.setData('long', e.target.value)} className="font-mono" placeholder="46.6753" />
                                    </Field>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="pt-6">
                                <Field label={t.status} required error={form.errors.status}>
                                    <Select value={form.data.status} onChange={(e) => form.setData('status', e.target.value)}>
                                        {(lookups.statuses || []).map((s) => (
                                            <option key={s.value} value={s.value}>{s.label}</option>
                                        ))}
                                    </Select>
                                </Field>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right: sticky map */}
                    <div className="lg:sticky lg:top-20 self-start">
                        <Card>
                            <CardContent className="pt-6 space-y-3">
                                <div className="text-sm font-semibold">Map preview</div>
                                <HubMap
                                    apiKey={google_maps_key}
                                    lat={latNum}
                                    lng={longNum}
                                    onPick={onMapPick}
                                    addressInputRef={addressInputRef}
                                    onAddressPick={onAddressPick}
                                    t={t}
                                />
                                <p className="text-[11px] text-muted-foreground">
                                    Click anywhere on the map to place the marker, or drag it. The lat / long fields stay in sync.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <div className="flex items-center justify-end gap-2 rounded-xl border border-border bg-card p-4 shadow-sm">
                    <a href={urls.cancel} className="inline-flex h-10 items-center rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent">
                        <ArrowLeft className="h-4 w-4 me-1" /> {t.cancel}
                    </a>
                    <Button type="submit" disabled={form.processing}>
                        <Save className="h-4 w-4 me-1" /> {form.processing ? '…' : t.save}
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
