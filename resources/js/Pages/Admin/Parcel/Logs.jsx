import * as React from 'react';
import { Head } from '@inertiajs/react';
import {
    ArrowLeft, ExternalLink, Printer, Hourglass, TruckIcon, Warehouse,
    Truck, Handshake, Undo2, Phone, MapPin, FileText, Check,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { cn } from '@/lib/utils';

const COLOR_TO_CLASSES = {
    grey:    'bg-slate-100 text-slate-700 border-slate-200',
    yellow:  'bg-amber-100 text-amber-700 border-amber-200',
    orange:  'bg-orange-100 text-orange-700 border-orange-200',
    blue:    'bg-sky-100 text-sky-700 border-sky-200',
    purple:  'bg-violet-100 text-violet-700 border-violet-200',
    green:   'bg-emerald-100 text-emerald-700 border-emerald-200',
    red:     'bg-rose-100 text-rose-700 border-rose-200',
    teal:    'bg-teal-100 text-teal-700 border-teal-200',
};
const COLOR_DOT = {
    grey:   'bg-slate-400',  yellow: 'bg-amber-500', orange: 'bg-orange-500',
    blue:   'bg-sky-500',    purple: 'bg-violet-500', green: 'bg-emerald-500',
    red:    'bg-rose-500',   teal:   'bg-teal-500',
};
const STAGE_ICONS = {
    pending:   Hourglass,
    pickup:    TruckIcon,
    warehouse: Warehouse,
    dispatch:  Truck,
    delivered: Handshake,
    returned:  Undo2,
};

function StatusPill({ label, color }) {
    return (
        <span className={cn(
            'inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium',
            COLOR_TO_CLASSES[color] || COLOR_TO_CLASSES.grey,
        )}>
            {label}
        </span>
    );
}

function StagePill({ stage, idx, isLast }) {
    const Icon = STAGE_ICONS[stage.key] || Hourglass;
    const active = stage.active;
    return (
        <div className="flex items-center flex-1 min-w-0">
            <div className="flex flex-col items-center text-center">
                <div className={cn(
                    'relative grid h-12 w-12 place-items-center rounded-full border-2 transition-colors shrink-0',
                    active
                        ? 'border-emerald-500 bg-emerald-500 text-white shadow-md shadow-emerald-500/30'
                        : 'border-border bg-card text-muted-foreground',
                )}>
                    {active ? <Check className="h-5 w-5" strokeWidth={3} /> : <Icon className="h-5 w-5" />}
                </div>
                <div className={cn(
                    'mt-2 text-[10px] uppercase tracking-wider font-semibold max-w-[100px] truncate',
                    active ? 'text-foreground' : 'text-muted-foreground',
                )}>
                    {stage.label}
                </div>
            </div>
            {!isLast && (
                <div className={cn(
                    'h-0.5 flex-1 mx-2 -translate-y-3 rounded-full transition-colors',
                    active ? 'bg-emerald-500' : 'bg-border',
                )} />
            )}
        </div>
    );
}

function EventRow({ event }) {
    return (
        <div className="flex gap-3">
            <div className="flex flex-col items-center pt-1">
                <span className={cn('h-3 w-3 rounded-full shadow-sm shrink-0', COLOR_DOT[event.color] || COLOR_DOT.grey)} />
                <span className="my-0.5 w-px flex-1 bg-border" />
            </div>
            <div className="flex-1 pb-5">
                <div className="rounded-md border border-border bg-card p-3 text-sm">
                    <div className="flex items-center justify-between gap-2">
                        <div className="font-semibold">{event.label}</div>
                        <span className="text-[10px] text-muted-foreground font-mono">{event.created_at}</span>
                    </div>
                    <div className="mt-2 space-y-0.5 text-xs">
                        {event.pickupman && (
                            <div className="text-muted-foreground">
                                Pickup: <span className="text-foreground font-medium">{event.pickupman.name}</span>
                                {event.pickupman.mobile && <span className="ms-1.5 font-mono">· {event.pickupman.mobile}</span>}
                            </div>
                        )}
                        {event.deliveryman && (
                            <div className="text-muted-foreground">
                                Courier: <span className="text-foreground font-medium">{event.deliveryman.name}</span>
                                {event.deliveryman.mobile && <span className="ms-1.5 font-mono">· {event.deliveryman.mobile}</span>}
                            </div>
                        )}
                        {event.hub && (
                            <div className="text-muted-foreground">
                                Hub: <span className="text-foreground font-medium">{event.hub.name}</span>
                                {event.hub.phone && <span className="ms-1.5 font-mono">· {event.hub.phone}</span>}
                            </div>
                        )}
                        {event.note && (
                            <div className="mt-1.5 pt-1.5 border-t border-border text-muted-foreground italic">
                                {event.note}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}

export default function Logs({ parcel = {}, stages = [], events = [], urls = {}, t = {} }) {
    return (
        <AdminLayout title={t.title}>
            <Head title={`${t.title} · ${parcel.tracking_id || ''}`} />

            <div className="mb-5 flex flex-wrap items-center justify-between gap-2">
                <a href={urls.details} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.back}
                </a>
                <div className="flex items-center gap-2">
                    <span className="text-sm text-muted-foreground font-mono">{parcel.tracking_id}</span>
                    <StatusPill label={parcel.status_label} color={parcel.status_color} />
                    <a href={urls.print_label} target="_blank" rel="noreferrer" className="inline-flex h-9 items-center rounded-md border border-rose-200 bg-rose-50 text-rose-700 px-3 text-sm font-medium hover:bg-rose-100">
                        <Printer className="h-4 w-4 me-1" /> Print
                    </a>
                </div>
            </div>

            {/* Workflow pipeline */}
            <Card className="mb-5">
                <CardContent className="pt-6">
                    <div className="mb-4 text-sm font-semibold">{t.pipeline}</div>
                    <div className="flex items-start">
                        {stages.map((s, i) => (
                            <StagePill key={s.key} stage={s} idx={i} isLast={i === stages.length - 1} />
                        ))}
                    </div>
                </CardContent>
            </Card>

            {/* Timeline */}
            <Card>
                <CardContent className="pt-6">
                    <div className="mb-4 text-sm font-semibold">{t.timeline}</div>
                    {events.length === 0
                        ? <div className="text-xs text-muted-foreground py-6 text-center">{t.no_events}</div>
                        : (
                            <div className="space-y-0">
                                {events.map((ev) => <EventRow key={ev.id} event={ev} />)}
                            </div>
                        )}
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
