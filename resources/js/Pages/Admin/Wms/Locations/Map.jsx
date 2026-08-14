import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    MapPin, Building2, Grid3x3, Box, ListOrdered, Plus, Edit3, ChevronDown, ChevronRight,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Select } from '@/Components/ui/Select';

function TypeBadge({ type }) {
    // Single-tone soft pill; the type names come from the LocationType enum
    // (RESERVE, PICKING, BULK, etc.). Keep neutral — color-coding belongs
    // in a future enhancement once the team picks a palette.
    return (
        <span className="inline-flex items-center rounded-full bg-slate-100 text-slate-700 px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide">
            {type || '—'}
        </span>
    );
}

function LocationCard({ loc, editLabel }) {
    return (
        <div className="group rounded-md border border-border bg-card hover:border-primary/40 hover:bg-primary/5 transition-colors px-3 py-2.5">
            <div className="flex items-start justify-between gap-2">
                <div className="min-w-0">
                    <div className="font-mono text-xs font-semibold truncate">{loc.code}</div>
                    <div className="mt-0.5 flex items-center gap-1.5 flex-wrap">
                        <TypeBadge type={loc.type} />
                        {loc.capacity != null && (
                            <span className="inline-flex items-center gap-0.5 text-[10px] text-muted-foreground">
                                <Box className="h-2.5 w-2.5" /> {loc.capacity}
                            </span>
                        )}
                    </div>
                </div>
                <a
                    href={loc.url}
                    title={editLabel}
                    className="opacity-0 group-hover:opacity-100 transition-opacity text-muted-foreground hover:text-primary"
                >
                    <Edit3 className="h-3.5 w-3.5" />
                </a>
            </div>
            <div className="mt-2 grid grid-cols-3 gap-1 text-[10px] text-muted-foreground">
                <div className="truncate" title="Rack">R: <span className="font-medium text-foreground/90">{loc.rack || '—'}</span></div>
                <div className="truncate" title="Shelf">S: <span className="font-medium text-foreground/90">{loc.shelf || '—'}</span></div>
                <div className="truncate" title="Bin">B: <span className="font-medium text-foreground/90">{loc.bin || '—'}</span></div>
            </div>
        </div>
    );
}

function AisleBlock({ aisle, editLabel, defaultOpen = true }) {
    const [open, setOpen] = React.useState(defaultOpen);
    return (
        <div className="rounded-md border border-border bg-background">
            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                className="w-full flex items-center gap-2 px-3 py-2 border-b border-border/60 text-sm font-medium hover:bg-muted/40 transition-colors"
            >
                {open ? <ChevronDown className="h-3.5 w-3.5 text-muted-foreground" /> : <ChevronRight className="h-3.5 w-3.5 text-muted-foreground" />}
                <Grid3x3 className="h-3.5 w-3.5 text-muted-foreground" />
                <span>{aisle.name}</span>
                <span className="ms-auto inline-flex items-center rounded-full bg-muted text-muted-foreground px-2 py-0.5 text-[10px] font-medium tabular-nums">
                    {aisle.locations.length}
                </span>
            </button>
            {open && (
                <div className="grid gap-2 p-3 sm:grid-cols-2 md:grid-cols-3">
                    {aisle.locations.map((l) => <LocationCard key={l.id} loc={l} editLabel={editLabel} />)}
                </div>
            )}
        </div>
    );
}

function ZoneSection({ zone, editLabel }) {
    const total = zone.aisles.reduce((sum, a) => sum + a.locations.length, 0);
    return (
        <Card>
            <CardContent className="p-5 space-y-3">
                <div className="flex items-center gap-2 pb-3 border-b border-border">
                    <MapPin className="h-4 w-4 text-primary" />
                    <h3 className="text-sm font-semibold tracking-tight">{zone.name}</h3>
                    <span className="ms-auto inline-flex items-center rounded-full bg-primary/10 text-primary px-2 py-0.5 text-[10px] font-medium tabular-nums">
                        {total}
                    </span>
                </div>
                <div className="space-y-2.5">
                    {zone.aisles.map((a) => (
                        <AisleBlock key={a.name} aisle={a} editLabel={editLabel} defaultOpen={zone.aisles.length === 1} />
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}

export default function Map({
    zones = [],
    filters = {},
    lookups = {},
    permissions = {},
    urls = {},
    t = {},
}) {
    const [hubId, setHubId] = React.useState(filters.hub_id || '');

    const applyHub = (v) => {
        setHubId(v);
        router.get(urls.map, v ? { hub_id: v } : {}, { preserveState: false, replace: true });
    };

    const totalLocations = zones.reduce(
        (sum, z) => sum + z.aisles.reduce((s2, a) => s2 + a.locations.length, 0),
        0,
    );

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title]}>
            <Head title={t.title} />

            {/* Toolbar */}
            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div className="flex items-center gap-2">
                    <Link
                        href={urls.index}
                        className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent"
                    >
                        <ListOrdered className="h-4 w-4 me-1" /> {t.list_view}
                    </Link>
                    <div className="inline-flex h-9 items-center rounded-md bg-primary text-primary-foreground px-3 text-sm font-medium">
                        <MapPin className="h-4 w-4 me-1" /> {t.map_view}
                    </div>
                </div>
                <div className="flex items-center gap-2">
                    <div className="flex items-center gap-1.5 text-sm">
                        <Building2 className="h-4 w-4 text-muted-foreground" />
                        <Select
                            value={hubId}
                            onChange={(e) => applyHub(e.target.value)}
                            className="h-9 w-44"
                        >
                            <option value="">{t.all_hubs}</option>
                            {(lookups.hubs || []).map((h) => (
                                <option key={h.id} value={h.id}>{h.name}</option>
                            ))}
                        </Select>
                    </div>
                    {permissions.create && (
                        <Link
                            href={urls.create}
                            className="inline-flex h-9 items-center rounded-md bg-primary text-primary-foreground px-3 text-sm font-medium hover:bg-primary/90"
                        >
                            <Plus className="h-4 w-4 me-1" /> {t.add}
                        </Link>
                    )}
                </div>
            </div>

            {/* Summary strip */}
            <div className="mb-4 flex items-center gap-3 text-sm">
                <span className="inline-flex items-center gap-1 rounded-full bg-sky-100 text-sky-700 px-3 py-1 text-xs font-medium">
                    <MapPin className="h-3.5 w-3.5" /> {totalLocations}
                </span>
                <span className="inline-flex items-center gap-1 rounded-full bg-violet-100 text-violet-700 px-3 py-1 text-xs font-medium">
                    <Grid3x3 className="h-3.5 w-3.5" /> {zones.length} {t.zone}
                </span>
            </div>

            {zones.length === 0 ? (
                <Card>
                    <CardContent className="p-12 text-center">
                        <MapPin className="h-10 w-10 text-muted-foreground/40 mx-auto mb-3" />
                        <p className="text-sm text-muted-foreground">{t.no_locations}</p>
                        {permissions.create && (
                            <Link
                                href={urls.create}
                                className="mt-4 inline-flex h-9 items-center rounded-md bg-primary text-primary-foreground px-3 text-sm font-medium hover:bg-primary/90"
                            >
                                <Plus className="h-4 w-4 me-1" /> {t.add}
                            </Link>
                        )}
                    </CardContent>
                </Card>
            ) : (
                <div className="grid gap-4">
                    {zones.map((z) => <ZoneSection key={z.name} zone={z} editLabel={t.edit} />)}
                </div>
            )}
        </AdminLayout>
    );
}
