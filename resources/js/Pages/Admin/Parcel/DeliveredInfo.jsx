import * as React from 'react';
import { Head } from '@inertiajs/react';
import { ArrowLeft, Printer, CheckCircle2, Calendar, Image as ImageIcon, Camera, PenTool } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { cn } from '@/lib/utils';

const COLOR_TO_CLASSES = {
    grey:   'bg-slate-100 text-slate-700 border-slate-200',
    yellow: 'bg-amber-100 text-amber-700 border-amber-200',
    orange: 'bg-orange-100 text-orange-700 border-orange-200',
    blue:   'bg-sky-100 text-sky-700 border-sky-200',
    purple: 'bg-violet-100 text-violet-700 border-violet-200',
    green:  'bg-emerald-100 text-emerald-700 border-emerald-200',
    red:    'bg-rose-100 text-rose-700 border-rose-200',
    teal:   'bg-teal-100 text-teal-700 border-teal-200',
};

function StatusPill({ label, color }) {
    return (
        <span className={cn(
            'inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium',
            COLOR_TO_CLASSES[color] || COLOR_TO_CLASSES.grey,
        )}>
            {label || '—'}
        </span>
    );
}

/**
 * One POD event card. Contains the delivered_image + signature side by
 * side (2 cols on ≥sm; stacks on mobile) with the note + timestamp.
 * Renders even when both images are null so an event without proof
 * still surfaces its note.
 */
function PodEventCard({ event, t }) {
    const hasImages = event.delivered_image || event.signature_image;
    return (
        <Card className="rounded-xl border border-border">
            <CardContent className="p-4 space-y-3">
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                        <div className="flex items-center gap-2 text-sm font-semibold">
                            <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                            <span>{event.status_label}</span>
                        </div>
                        {event.note && (
                            <div className="mt-1.5 text-xs text-muted-foreground">
                                <span className="font-medium">{t.note}:</span> {event.note}
                            </div>
                        )}
                    </div>
                    <div className="text-end shrink-0">
                        <div className="text-xs font-medium">{event.created_at_date}</div>
                        <div className="text-[10px] text-muted-foreground tabular-nums">{event.created_at_time}</div>
                    </div>
                </div>
                {hasImages && (
                    <div className="grid gap-3 sm:grid-cols-2">
                        {event.delivered_image && (
                            <div>
                                <div className="mb-1 text-[10px] uppercase tracking-wider font-semibold text-muted-foreground inline-flex items-center gap-1">
                                    <Camera className="h-3 w-3" /> {t.delivered_photo}
                                </div>
                                <a href={event.delivered_image} target="_blank" rel="noreferrer" className="block rounded-md overflow-hidden border border-border hover:border-primary transition-colors">
                                    <img src={event.delivered_image} alt="" className="w-full h-64 object-cover bg-muted" />
                                </a>
                            </div>
                        )}
                        {event.signature_image && (
                            <div>
                                <div className="mb-1 text-[10px] uppercase tracking-wider font-semibold text-muted-foreground inline-flex items-center gap-1">
                                    <PenTool className="h-3 w-3" /> {t.signature}
                                </div>
                                <a href={event.signature_image} target="_blank" rel="noreferrer" className="block rounded-md overflow-hidden border border-border hover:border-primary transition-colors bg-white">
                                    <img src={event.signature_image} alt="" className="w-full h-64 object-contain p-2" />
                                </a>
                            </div>
                        )}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

export default function DeliveredInfo({ parcel = {}, events = [], parcel_images = [], urls = {}, t = {} }) {
    return (
        <AdminLayout title={t.title}>
            <Head title={`${t.title} · ${parcel.tracking_id || ''}`} />

            {/* Sticky header — back button + tracking id + status + print */}
            <div className="sticky top-0 z-20 mb-5 -mx-4 md:-mx-8 px-4 md:px-8 py-3 bg-background/95 backdrop-blur border-b border-border">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-3 min-w-0">
                        <a href={urls.parcel_details} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent shrink-0">
                            <ArrowLeft className="h-4 w-4 me-1" /> {t.back_to_details}
                        </a>
                        <div className="inline-flex items-center gap-2 min-w-0">
                            <CheckCircle2 className="h-5 w-5 text-emerald-600 shrink-0" />
                            <span className="font-mono text-sm font-semibold truncate">
                                {parcel.tracking_id || `#${parcel.id}`}
                            </span>
                            <StatusPill label={parcel.status_label} color={parcel.status_color} />
                        </div>
                    </div>
                    <a href={urls.print_label} target="_blank" rel="noreferrer"
                       className="inline-flex h-9 items-center rounded-md border border-rose-200 bg-rose-50 text-rose-700 px-3 text-sm font-medium hover:bg-rose-100">
                        <Printer className="h-4 w-4 me-1" /> {t.print_label}
                    </a>
                </div>
            </div>

            {/* Timeline of delivery events */}
            {events.length === 0 ? (
                <Card>
                    <CardContent className="py-16 text-center">
                        <div className="flex justify-center mb-3 text-muted-foreground/40">
                            <Camera className="h-10 w-10" />
                        </div>
                        <p className="text-sm text-muted-foreground m-0">{t.no_pod}</p>
                    </CardContent>
                </Card>
            ) : (
                <div className="space-y-4">
                    {events.map((e) => <PodEventCard key={e.id} event={e} t={t} />)}
                </div>
            )}

            {/* Parcel-level image gallery — merchant-uploaded photos separate
                from POD photos (packaging, damage, etc.). Only renders when
                the parcel has at least one image. */}
            {parcel_images.length > 0 && (
                <Card className="mt-5">
                    <CardContent className="p-4">
                        <div className="mb-3 flex items-center gap-2 text-sm font-semibold">
                            <ImageIcon className="h-4 w-4 text-primary" />
                            {t.parcel_images}
                            <span className="ms-auto text-[10px] text-muted-foreground font-normal">
                                {parcel_images.length}
                            </span>
                        </div>
                        <div className="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-3">
                            {parcel_images.map((img) => (
                                <a key={img.id} href={img.url} target="_blank" rel="noreferrer"
                                   className="group block rounded-md overflow-hidden border border-border hover:border-primary transition-colors">
                                    <div className="aspect-square bg-muted/40 overflow-hidden">
                                        <img src={img.url} alt="" className="w-full h-full object-cover" />
                                    </div>
                                    {img.type && (
                                        <div className="p-1 text-center text-[10px] text-muted-foreground truncate">
                                            {img.type.charAt(0).toUpperCase() + img.type.slice(1)}
                                        </div>
                                    )}
                                </a>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            )}
        </AdminLayout>
    );
}
