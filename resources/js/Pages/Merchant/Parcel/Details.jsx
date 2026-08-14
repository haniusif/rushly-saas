import * as React from 'react';
import { Head } from '@inertiajs/react';
import {
    ArrowLeft, Edit, FileText, Phone, MapPin, MessageCircle,
    Package, Paperclip, Wallet, Building2, Copy as CopyIcon,
    Hash, Calendar, Flame,
} from 'lucide-react';
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
const COLOR_DOT = {
    grey:    'bg-slate-400',
    yellow:  'bg-amber-500',
    orange:  'bg-orange-500',
    blue:    'bg-sky-500',
    purple:  'bg-violet-500',
    green:   'bg-emerald-500',
    red:     'bg-rose-500',
    teal:    'bg-teal-500',
};

function Money({ value, currency }) {
    const n = Number(value || 0);
    return (
        <span className="tabular-nums">
            {n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
            <span className="text-muted-foreground text-xs ms-1">{currency}</span>
        </span>
    );
}

function StatusPill({ label, color, className }) {
    return (
        <span className={cn(
            'inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium',
            COLOR_TO_CLASSES[color] || COLOR_TO_CLASSES.grey,
            className,
        )}>
            {label || '—'}
        </span>
    );
}

function Party({ icon: Icon, title, name, address, phone, whatsapp }) {
    return (
        <Card>
            <CardContent className="p-4">
                <div className="mb-2 flex items-center gap-2">
                    <Icon className="h-4 w-4 text-primary" />
                    <div className="text-xs uppercase tracking-wide text-muted-foreground font-semibold">{title}</div>
                </div>
                <div className="text-sm font-semibold">{name || '—'}</div>
                {address && (
                    <div className="mt-1 text-xs text-muted-foreground flex items-start gap-1">
                        <MapPin className="h-3 w-3 mt-0.5 shrink-0" /> {address}
                    </div>
                )}
                {phone && (
                    <div className="mt-1.5 text-xs text-muted-foreground flex items-center gap-2">
                        <Phone className="h-3 w-3" />
                        <span>{phone}</span>
                        {whatsapp && (
                            <a href={whatsapp} target="_blank" rel="noreferrer" className="text-emerald-600 hover:text-emerald-700" title="WhatsApp">
                                <MessageCircle className="h-3.5 w-3.5" />
                            </a>
                        )}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function DetailRow({ label, children }) {
    return (
        <div className="flex items-baseline justify-between gap-3 border-b border-border py-2 last:border-0">
            <span className="text-[11px] uppercase tracking-wide text-muted-foreground">{label}</span>
            <span className="text-sm font-medium text-end">{children}</span>
        </div>
    );
}

function CopyableTracking({ value }) {
    const [copied, setCopied] = React.useState(false);
    const copy = () => {
        if (!value) return;
        navigator.clipboard?.writeText(value).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 1200);
        });
    };
    return (
        <button
            type="button"
            onClick={copy}
            className={cn(
                'inline-flex items-center gap-1.5 font-mono text-sm font-semibold hover:underline underline-offset-2',
                copied ? 'text-emerald-600' : 'text-foreground',
            )}
            title="Copy"
        >
            {value || '—'}
            {copied ? <span className="text-[10px]">✓</span> : <CopyIcon className="h-3 w-3 text-muted-foreground" />}
        </button>
    );
}

function TimelineEvent({ event, isCreation }) {
    const time = event.created_at ? event.created_at.slice(11, 19) : null;
    return (
        <div className="flex gap-3">
            <div className="flex w-28 flex-col items-end pe-2 shrink-0">
                {event.actor && <div className="text-xs font-medium truncate w-full text-end">{event.actor}</div>}
                {event.hub && <div className="text-[10px] text-muted-foreground truncate w-full text-end">{event.hub}</div>}
                {time && <div className="mt-0.5 text-[10px] text-muted-foreground">{time}</div>}
            </div>
            <div className="relative flex flex-col items-center">
                <span className={cn(
                    'mt-1.5 h-2.5 w-2.5 rounded-full shadow-sm',
                    isCreation ? 'bg-slate-400' : (COLOR_DOT[event.color] || COLOR_DOT.grey),
                )} />
                <span className="my-0.5 w-px flex-1 bg-border" />
            </div>
            <div className="flex-1 pb-4">
                <div className="rounded-md border border-border bg-card px-3 py-2 text-sm">
                    {event.label}
                    {event.note && <div className="mt-1 text-xs text-muted-foreground italic">{event.note}</div>}
                </div>
                <div className="mt-1.5"><StatusPill label={event.label} color={event.color} /></div>
            </div>
        </div>
    );
}

export default function Details({
    parcel = {}, sender = {}, recipient = {}, attachments = [], events = [],
    currency = '', permissions = {}, urls = {}, t = {},
}) {
    const groupedByDate = React.useMemo(() => {
        const groups = new Map();
        events.forEach((ev) => {
            const date = ev.created_at?.slice(0, 10) || '—';
            if (!groups.has(date)) groups.set(date, []);
            groups.get(date).push(ev);
        });
        return Array.from(groups.entries()).map(([date, items]) => ({ date, items }));
    }, [events]);

    const creationDate = parcel.created_at?.slice(0, 10);

    return (
        <MerchantLayout title={t.title} breadcrumbs={[t.title_index, parcel.tracking_id || ('#' + parcel.id)]}>
            <Head title={`${t.title} · ${parcel.tracking_id || ''}`} />

            {/* Header */}
            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-center gap-3">
                    <a href={urls.index} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent no-underline">
                        <ArrowLeft className="h-4 w-4 me-1" /> {t.back_to_list}
                    </a>
                    <div className="inline-flex items-center gap-2">
                        <Package className="h-5 w-5 text-primary" />
                        <CopyableTracking value={parcel.tracking_id} />
                        <StatusPill label={parcel.status_label} color={parcel.status_color} />
                        {parcel.priority === 1 && (
                            <span className="inline-flex items-center gap-1 rounded-full bg-rose-100 text-rose-700 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5">
                                <Flame className="h-3 w-3" /> {t.priority_high}
                            </span>
                        )}
                    </div>
                </div>
                <div className="flex items-center gap-2">
                    {permissions.edit && (
                        <a href={urls.edit} className="inline-flex h-9 items-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90 no-underline">
                            <Edit className="h-4 w-4 me-1" /> {t.edit}
                        </a>
                    )}
                </div>
            </div>

            <div className="grid gap-5 lg:grid-cols-3">
                <div className="lg:col-span-2 space-y-5">
                    {/* Parties */}
                    <div className="grid gap-4 md:grid-cols-2">
                        <Party icon={Building2} title={t.sender_info}    {...sender} />
                        <Party icon={MapPin}    title={t.recipient_info} {...recipient} />
                    </div>

                    {/* Attachments */}
                    <Card>
                        <CardContent className="p-4">
                            <div className="mb-3 flex items-center gap-2 text-sm font-semibold">
                                <Paperclip className="h-4 w-4" /> {t.attachment}
                            </div>
                            {attachments.length === 0
                                ? <div className="text-xs text-muted-foreground">{t.no_attachments}</div>
                                : (
                                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                                        {attachments.map((att, i) => (
                                            <a key={i} href={att.url} target="_blank" rel="noreferrer" className="group block rounded-md overflow-hidden border border-border hover:border-primary transition-colors no-underline">
                                                <div className="aspect-square bg-muted/40 grid place-items-center overflow-hidden">
                                                    <img src={att.url} alt={att.label} className={cn('h-full w-full', att.contain ? 'object-contain p-2' : 'object-cover')} />
                                                </div>
                                                <div className="p-1.5 text-[10px] text-center">
                                                    <div className="font-medium truncate">{att.label}</div>
                                                    {att.date && <div className="text-muted-foreground">{att.date}</div>}
                                                </div>
                                            </a>
                                        ))}
                                    </div>
                                )}
                        </CardContent>
                    </Card>

                    {/* Timeline */}
                    <Card>
                        <CardContent className="p-4">
                            <div className="mb-3 text-sm font-semibold">{t.timeline}</div>
                            <div className="space-y-2">
                                {groupedByDate.map(({ date, items }) => (
                                    <React.Fragment key={date}>
                                        <div className="inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-0.5 text-[10px] font-semibold text-muted-foreground">
                                            <Calendar className="h-3 w-3" /> {date}
                                        </div>
                                        <div>
                                            {items.map((ev) => <TimelineEvent key={ev.id} event={ev} />)}
                                        </div>
                                    </React.Fragment>
                                ))}
                                {creationDate && (
                                    <div className="inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-0.5 text-[10px] font-semibold text-muted-foreground">
                                        <Calendar className="h-3 w-3" /> {creationDate}
                                    </div>
                                )}
                                <TimelineEvent
                                    isCreation
                                    event={{
                                        actor: sender.name,
                                        label: t.shipment_creation,
                                        color: 'grey',
                                        created_at: (parcel.created_at || '') + ':00',
                                    }}
                                />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Right column */}
                <div className="lg:col-span-1 space-y-5">
                    <Card>
                        <CardContent className="p-4">
                            <div className="mb-2 flex items-center gap-2 text-sm font-semibold">
                                <Hash className="h-4 w-4 text-muted-foreground" /> {t.tracking_id}
                            </div>
                            <DetailRow label={t.booking_date}><span className="font-mono text-xs">{parcel.created_at || '—'}</span></DetailRow>
                            <DetailRow label={t.invoice}>{parcel.invoice_no || '—'}</DetailRow>
                            <DetailRow label={t.weight}>{parcel.weight} {parcel.weight_unit || ''}</DetailRow>
                            <DetailRow label={t.delivery_type}>{parcel.delivery_type || '—'}</DetailRow>
                            <DetailRow label={t.city}>{parcel.city || '—'}</DetailRow>
                            <DetailRow label={t.area}>{parcel.area || '—'}</DetailRow>
                            <DetailRow label={t.hub}>{parcel.hub || '—'}</DetailRow>
                            <DetailRow label={t.attempts}>{parcel.attempts}</DetailRow>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-4">
                            <div className="mb-2 flex items-center gap-2 text-sm font-semibold">
                                <Wallet className="h-4 w-4 text-emerald-600" /> {t.finance}
                            </div>
                            <DetailRow label={t.cash_collection}><Money value={parcel.cash_collection} currency={currency} /></DetailRow>
                            <DetailRow label={t.cod}><Money value={parcel.cod_amount} currency={currency} /></DetailRow>
                            <DetailRow label={t.price}><Money value={parcel.selling_price} currency={currency} /></DetailRow>
                            <DetailRow label={t.delivery}><Money value={parcel.total_delivery_amount} currency={currency} /></DetailRow>
                            <DetailRow label={t.vat}><Money value={parcel.vat_amount} currency={currency} /></DetailRow>
                            <DetailRow label={t.net_payable}>
                                <span className="font-bold"><Money value={parcel.current_payable} currency={currency} /></span>
                            </DetailRow>
                        </CardContent>
                    </Card>

                    {parcel.note && (
                        <Card>
                            <CardContent className="p-4">
                                <div className="mb-2 flex items-center gap-2 text-sm font-semibold">
                                    <FileText className="h-4 w-4 text-muted-foreground" /> {t.note}
                                </div>
                                <div className="rounded-md bg-muted/40 p-2.5 text-xs whitespace-pre-wrap">{parcel.note}</div>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </MerchantLayout>
    );
}
