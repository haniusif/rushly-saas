import * as React from 'react';
import { Head } from '@inertiajs/react';
import { ArrowLeft, Eye, History, Calendar } from 'lucide-react';
import MerchantLayout from '@/Layouts/MerchantLayout';
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

export default function Logs({ parcel = {}, events = [], urls = {}, t = {} }) {
    return (
        <MerchantLayout title={t.title} breadcrumbs={[t.title_index, parcel.tracking_id || '#' + parcel.id]}>
            <Head title={`${t.title} · ${parcel.tracking_id || ''}`} />

            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-center gap-3">
                    <a href={urls.index} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent no-underline">
                        <ArrowLeft className="h-4 w-4 me-1" /> {t.back_to_list}
                    </a>
                    <div className="inline-flex items-center gap-2">
                        <History className="h-5 w-5 text-primary" />
                        <span className="font-mono text-sm font-semibold">{parcel.tracking_id}</span>
                        <StatusPill label={parcel.status_label} color={parcel.status_color} />
                    </div>
                </div>
                <a href={urls.details} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent no-underline">
                    <Eye className="h-4 w-4 me-1" /> {t.view_details}
                </a>
            </div>

            <Card>
                <CardContent className="p-0">
                    {events.length === 0 ? (
                        <div className="p-8 text-center text-sm text-muted-foreground">{t.no_events}</div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/30 text-xs uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th className="text-start font-medium px-4 py-2.5 w-44">{t.when}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.status}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.actor}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.hub}</th>
                                        <th className="text-start font-medium px-4 py-2.5">{t.note}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {events.map((ev) => (
                                        <tr key={ev.id} className="hover:bg-muted/20">
                                            <td className="px-4 py-2.5 align-top">
                                                <div className="font-mono text-xs flex items-center gap-1.5">
                                                    <Calendar className="h-3 w-3 text-muted-foreground" />
                                                    {ev.created_at || '—'}
                                                </div>
                                            </td>
                                            <td className="px-4 py-2.5 align-top">
                                                <StatusPill label={ev.label} color={ev.color} />
                                            </td>
                                            <td className="px-4 py-2.5 align-top text-xs">{ev.actor || '—'}</td>
                                            <td className="px-4 py-2.5 align-top text-xs">{ev.hub || '—'}</td>
                                            <td className="px-4 py-2.5 align-top text-xs text-muted-foreground italic">
                                                {ev.note || <span className="text-muted-foreground">—</span>}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardContent>
            </Card>
        </MerchantLayout>
    );
}
