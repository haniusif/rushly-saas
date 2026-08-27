import * as React from 'react';
import { createPortal } from 'react-dom';
import {
    X, Phone, MapPin, MessageCircle, Edit, Printer, FileText,
    Package, Paperclip, Loader2, AlertCircle, Clock,
    Copy, Truck, Navigation, DollarSign, Scale, MapPinned, ChevronDown, ChevronUp,
} from 'lucide-react';
import { Button } from '@/Components/ui/Button';
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

function COLOR_DOT(color) {
    return ({
        grey:    'bg-slate-400',   yellow:  'bg-amber-500',
        orange:  'bg-orange-500',  blue:    'bg-sky-500',
        purple:  'bg-violet-500',  green:   'bg-emerald-500',
        red:     'bg-rose-500',    teal:    'bg-teal-500',
    })[color] || 'bg-slate-400';
}

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

function Money({ value, currency }) {
    return (
        <span className="tabular-nums">
            {Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
            <span className="text-muted-foreground text-xs ms-1">{currency}</span>
        </span>
    );
}

function Party({ icon: Icon, title, name, address, phone, whatsapp }) {
    return (
        <div className="flex gap-3 p-3 rounded-md border border-border bg-card">
            <div className="grid h-9 w-9 place-items-center rounded-md bg-primary/10 text-primary shrink-0">
                <Icon className="h-4 w-4" />
            </div>
            <div className="min-w-0 flex-1">
                <div className="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">{title}</div>
                <div className="text-sm font-semibold mt-0.5">{name || '—'}</div>
                {address && (
                    <div className="mt-1 text-xs text-muted-foreground flex items-start gap-1">
                        <MapPin className="h-3 w-3 mt-0.5 shrink-0" /> {address}
                    </div>
                )}
                {phone && (
                    <div className="mt-1 text-xs text-muted-foreground flex items-center gap-2">
                        <Phone className="h-3 w-3" /> {phone}
                        {whatsapp && (
                            <a href={whatsapp} target="_blank" rel="noreferrer" className="text-emerald-600 hover:text-emerald-700" title="WhatsApp">
                                <MessageCircle className="h-3.5 w-3.5" />
                            </a>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}

function DetailRow({ label, children }) {
    return (
        <div className="flex items-center justify-between gap-3 py-1.5 border-b border-border last:border-0">
            <span className="text-[11px] uppercase tracking-wide text-muted-foreground">{label}</span>
            <span className="text-sm font-medium text-end">{children}</span>
        </div>
    );
}

/**
 * Compact copy-to-clipboard button used next to the tracking ID in the
 * sticky header. Flash-confirms with a check icon for ~1.2s.
 */
function CopyButton({ value }) {
    const [copied, setCopied] = React.useState(false);
    const onCopy = (e) => {
        e.preventDefault(); e.stopPropagation();
        if (!value) return;
        navigator.clipboard?.writeText(String(value)).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 1200);
        });
    };
    return (
        <button
            type="button"
            onClick={onCopy}
            title={copied ? 'Copied!' : 'Copy'}
            className="p-1 rounded hover:bg-muted text-muted-foreground transition-colors"
        >
            <Copy className={cn('h-3.5 w-3.5', copied && 'text-emerald-600')} />
        </button>
    );
}

/**
 * Icon-only anchor used across the sticky action bar. Tone maps to a soft
 * background tint — rose for print, sky for phone, emerald for WhatsApp,
 * neutral by default. Title attribute doubles as accessible label.
 */
function IconAction({ href, target, icon: Icon, label, tone }) {
    const tones = {
        rose:    'bg-rose-100 text-rose-700 hover:bg-rose-200',
        sky:     'bg-sky-100 text-sky-700 hover:bg-sky-200',
        emerald: 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200',
    };
    return (
        <a
            href={href}
            target={target}
            rel={target === '_blank' ? 'noreferrer' : undefined}
            title={label}
            className={cn(
                'inline-flex h-8 w-8 items-center justify-center rounded-md text-xs font-medium transition-colors shrink-0',
                tones[tone] || 'bg-transparent text-foreground hover:bg-muted',
            )}
        >
            <Icon className="h-3.5 w-3.5" />
        </a>
    );
}

/**
 * Row of compact KPI tiles — one snapshot line each so the operator sees
 * the money + weight + delivery type before scrolling to the detail card.
 */
function KpiRow({ parcel, currency, t }) {
    const items = [
        { label: t.status,        node: <StatusPill label={parcel.status_label} color={parcel.status_color} /> },
        { label: t.cod,           node: <Money value={parcel.cod_amount} currency={currency} />, icon: DollarSign },
        { label: t.price,         node: <Money value={parcel.selling_price} currency={currency} />, icon: DollarSign },
        { label: t.weight,        node: <>{parcel.weight ?? '—'} <span className="text-muted-foreground text-xs">{parcel.weight_unit || ''}</span></>, icon: Scale },
        { label: t.delivery_type, node: <span className="text-sm font-medium">{parcel.delivery_type || '—'}</span>, icon: Navigation },
    ];
    return (
        <div className="grid grid-cols-2 sm:grid-cols-5 gap-2">
            {items.map((it, i) => {
                const Icon = it.icon;
                return (
                    <div key={i} className="rounded-md border border-border bg-card px-2.5 py-2 min-w-0">
                        <div className="flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                            {Icon && <Icon className="h-3 w-3" />}
                            <span className="truncate">{it.label}</span>
                        </div>
                        <div className="mt-1 text-sm">{it.node}</div>
                    </div>
                );
            })}
        </div>
    );
}

/**
 * Timeline wrapper — always shows the most recent date group + the
 * shipment creation event. Older groups collapse behind a "Show N more"
 * toggle so a long-lived shipment (50+ status transitions) doesn't push
 * the rest of the drawer off-screen.
 */
function Timeline({ groups = [], creation, label }) {
    const [showAll, setShowAll] = React.useState(false);
    // Newest-first, keep the first (most recent) always visible.
    const [primary, ...older] = groups;
    const visible = showAll ? groups : (primary ? [primary] : []);
    const hiddenCount = older.reduce((sum, g) => sum + (g.events?.length || 0), 0);

    return (
        <div>
            <div className="mb-3 text-sm font-semibold flex items-center justify-between gap-2">
                <span>{label}</span>
                {hiddenCount > 0 && (
                    <button
                        type="button"
                        onClick={() => setShowAll((v) => !v)}
                        className="inline-flex items-center gap-1 rounded-md border border-input px-2 py-1 text-[11px] font-medium hover:bg-accent"
                    >
                        {showAll
                            ? <>Collapse older <ChevronUp className="h-3 w-3" /></>
                            : <>Show {hiddenCount} older <ChevronDown className="h-3 w-3" /></>
                        }
                    </button>
                )}
            </div>
            <div className="space-y-2">
                {visible.map(({ date, events }) => (
                    <React.Fragment key={date}>
                        <div className="inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-0.5 text-[10px] font-semibold text-muted-foreground">
                            {date}
                        </div>
                        <div>
                            {events.map((ev) => <TimelineEvent key={ev.id} event={ev} />)}
                        </div>
                    </React.Fragment>
                ))}
                {/* Creation event stays visible so the operator always sees when the shipment entered the system. */}
                {creation && (
                    <>
                        <div className="inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-0.5 text-[10px] font-semibold text-muted-foreground">
                            {creation.created_at?.slice(0, 10)}
                        </div>
                        <TimelineEvent event={{ ...creation, color: 'grey' }} isCreation />
                    </>
                )}
            </div>
        </div>
    );
}

function TimelineEvent({ event, isCreation }) {
    const date = event.created_at ? event.created_at.slice(0, 10) : null;
    const time = event.created_at ? event.created_at.slice(11, 19) : null;
    return (
        <div className="flex gap-3">
            <div className="flex flex-col items-end pe-2 shrink-0 w-24">
                {event.actor && <div className="text-xs font-medium truncate w-full text-end">{event.actor}</div>}
                {event.hub && <div className="text-[10px] text-muted-foreground truncate w-full text-end">{event.hub}</div>}
                {time && <div className="text-[10px] text-muted-foreground mt-0.5">{time}</div>}
            </div>
            <div className="relative flex flex-col items-center">
                <span className={cn('h-2.5 w-2.5 rounded-full mt-1.5 shadow-sm', isCreation ? 'bg-slate-400' : COLOR_DOT(event.color))} />
                <span className="flex-1 w-px bg-border my-0.5" />
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

/**
 * `baseUrl` is the tracking-json endpoint WITHOUT the id. It defaults to the
 * admin one so existing callers are unaffected; the merchant list passes its
 * own, which resolves the same payload but refuses another merchant's
 * shipment.
 */
export default function ShipmentDrawer({ parcelId, onClose, baseUrl = '/admin/parcel/tracking-json' }) {
    const open = parcelId != null;
    const [data, setData] = React.useState(null);
    const [loading, setLoading] = React.useState(false);
    const [error, setError] = React.useState(null);
    const trackingUrl = React.useMemo(() => {
        if (!open) return null;
        return `${String(baseUrl).replace(/\/$/, '')}/${parcelId}`;
    }, [parcelId, open, baseUrl]);

    React.useEffect(() => {
        if (!open) {
            setData(null); setError(null);
            return;
        }
        setLoading(true); setError(null);
        fetch(trackingUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(async (r) => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then((d) => setData(d))
            .catch((e) => setError(e.message || 'Failed to load.'))
            .finally(() => setLoading(false));
    }, [open, trackingUrl]);

    React.useEffect(() => {
        if (!open) return;
        const onKey = (e) => { if (e.key === 'Escape') onClose(); };
        document.addEventListener('keydown', onKey);
        document.body.style.overflow = 'hidden';
        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = '';
        };
    }, [open, onClose]);

    // Group timeline by date. Must run on every render (even when closed) to
    // keep React's hooks order consistent across renders.
    const groupedByDate = React.useMemo(() => {
        if (!data?.events) return [];
        const groups = new Map();
        data.events.forEach((ev) => {
            const date = ev.created_at?.slice(0, 10) || '—';
            if (!groups.has(date)) groups.set(date, []);
            groups.get(date).push(ev);
        });
        return Array.from(groups.entries()).map(([date, events]) => ({ date, events }));
    }, [data?.events]);

    if (!open) return null;

    return createPortal(
        <>
            {/* Backdrop */}
            <div
                onClick={onClose}
                className="fixed inset-0 z-[100] bg-black/40 backdrop-blur-sm animate-in fade-in duration-150"
            />

            {/* Drawer */}
            <div
                role="dialog"
                aria-modal="true"
                className="fixed inset-y-0 end-0 z-[100] w-full sm:w-[640px] lg:w-[820px] bg-background shadow-2xl border-s border-border flex flex-col animate-in slide-in-from-right duration-200"
            >
                {/* Header — tracking + status + copy-to-clipboard on the
                    tracking ID. The full-actions bar sits directly below so
                    both stay pinned as the body scrolls. */}
                <div className="flex items-center justify-between gap-3 border-b border-border px-5 py-2.5 bg-background/95 backdrop-blur sticky top-0 z-10">
                    <div className="flex items-center gap-2 min-w-0">
                        <Package className="h-4 w-4 text-primary shrink-0" />
                        <h2 className="text-sm font-semibold truncate">
                            {data?.parcel?.tracking_id ? `#${data.parcel.tracking_id}` : 'Shipment details'}
                        </h2>
                        {data?.parcel?.tracking_id && <CopyButton value={data.parcel.tracking_id} />}
                        {data?.parcel && <StatusPill label={data.parcel.status_label} color={data.parcel.status_color} />}
                    </div>
                    <button onClick={onClose} className="grid h-8 w-8 place-items-center rounded-md hover:bg-muted transition-colors shrink-0">
                        <X className="h-4 w-4" />
                    </button>
                </div>

                {/* Sticky compact action bar — icon-only buttons with tooltip
                    titles. Renders only when the parcel payload has loaded. */}
                {data && !loading && (
                    <div className="border-b border-border px-3 py-1.5 bg-muted/30 sticky top-[46px] z-10 flex items-center gap-1 overflow-x-auto">
                        <IconAction href={data.parcel.urls.edit} icon={Edit} label={data.t.edit} />
                        <IconAction href={data.parcel.urls.print_label} target="_blank" icon={Printer} label={data.t.print} tone="rose" />
                        <IconAction href={data.parcel.urls.print} target="_blank" icon={FileText} label={data.t.print_with_tracking} tone="rose" />
                        <IconAction href={data.parcel.urls.logs} target="_blank" icon={Clock} label={data.t.logs} />
                        {data.recipient?.phone && (
                            <>
                                <div className="h-4 w-px bg-border mx-1" />
                                <IconAction href={`tel:${data.recipient.phone}`} icon={Phone} label="Call recipient" tone="sky" />
                                <IconAction
                                    href={`https://wa.me/${String(data.recipient.phone).replace(/\D/g, '')}`}
                                    target="_blank"
                                    icon={MessageCircle} label="WhatsApp" tone="emerald"
                                />
                            </>
                        )}
                    </div>
                )}

                {/* Body */}
                <div className="flex-1 overflow-y-auto">
                    {loading && (
                        <div className="grid place-items-center py-20">
                            <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                            <div className="mt-3 text-sm text-muted-foreground">Loading…</div>
                        </div>
                    )}
                    {error && !loading && (
                        <div className="m-5 rounded-md border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive flex items-start gap-2">
                            <AlertCircle className="h-4 w-4 mt-0.5 shrink-0" /> {error}
                        </div>
                    )}
                    {data && !loading && (
                        <div className="p-4 space-y-4">
                            {/* Overview KPI row — replaces the long plain
                                action bar (moved into the sticky bar above). */}
                            <KpiRow parcel={data.parcel} currency={data.currency} t={data.t} />

                            <div className="grid gap-4 lg:grid-cols-2">
                                {/* Sender + Recipient */}
                                <div className="space-y-3">
                                    <Party icon={MapPin} title={data.t.sender_info}    {...data.sender} />
                                    <Party icon={MapPin} title={data.t.recipient_info} {...data.recipient} />
                                </div>

                                {/* Detail card */}
                                <div className="rounded-md border border-border bg-card p-4">
                                    <div className="mb-2 flex items-center gap-2">
                                        <Package className="h-4 w-4 text-muted-foreground" />
                                        <span className="text-sm font-mono font-semibold">#{data.parcel.tracking_id}</span>
                                    </div>
                                    <DetailRow label={data.t.booking_date}>{data.parcel.created_at || '—'}</DetailRow>
                                    <DetailRow label={data.t.cod}><Money value={data.parcel.cod_amount} currency={data.currency} /></DetailRow>
                                    <DetailRow label={data.t.price}><Money value={data.parcel.selling_price} currency={data.currency} /></DetailRow>
                                    <DetailRow label={data.t.invoice}>{data.parcel.invoice_no || '—'}</DetailRow>
                                    <DetailRow label={data.t.weight}>{data.parcel.weight} {data.parcel.weight_unit || ''}</DetailRow>
                                    <DetailRow label={data.t.delivery_type}>{data.parcel.delivery_type || '—'}</DetailRow>
                                    <DetailRow label={data.t.city}>{data.parcel.city || '—'}</DetailRow>
                                    <DetailRow label={data.t.area}>{data.parcel.area || '—'}</DetailRow>
                                    <DetailRow label={data.t.status}><StatusPill label={data.parcel.status_label} color={data.parcel.status_color} /></DetailRow>
                                    {data.parcel.note && (
                                        <div className="mt-2 rounded-md bg-muted/40 p-2 text-xs">
                                            <span className="font-semibold">{data.t.note}: </span>
                                            <span className="text-muted-foreground">{data.parcel.note}</span>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Attachments */}
                            <div>
                                <div className="mb-2 flex items-center gap-2 text-sm font-semibold">
                                    <Paperclip className="h-4 w-4" /> {data.t.attachment}
                                </div>
                                {data.attachments.length === 0
                                    ? <div className="text-xs text-muted-foreground">{data.t.no_attachments}</div>
                                    : (
                                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                                            {data.attachments.map((att, i) => (
                                                <a key={i} href={att.url} target="_blank" rel="noreferrer" className="group block rounded-md overflow-hidden border border-border hover:border-primary transition-colors">
                                                    <div className={cn('aspect-square bg-muted/40 grid place-items-center overflow-hidden')}>
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
                            </div>

                            {/* Timeline — most recent groups visible; older
                                ones collapse behind a toggle so a shipment
                                with 50+ events doesn't dominate the drawer. */}
                            <Timeline
                                groups={groupedByDate}
                                creation={data.creation_event}
                                label={data.t.timeline}
                            />
                        </div>
                    )}
                </div>
            </div>
        </>,
        document.body,
    );
}
