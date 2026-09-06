import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { createPortal } from 'react-dom';
import {
    ArrowLeft, Edit, Printer, FileText, Phone, MapPin, MessageCircle,
    Package, Paperclip, Clock, Wallet, Receipt, Building2, Truck,
    Copy as CopyIcon, AlertCircle, ExternalLink, Hash, Calendar,
    Flame, X, Loader2,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Select } from '@/Components/ui/Select';
import { Label } from '@/Components/ui/Label';
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
        <button type="button" onClick={copy}
            className={cn(
                'inline-flex items-center gap-1.5 font-mono text-sm font-semibold hover:underline underline-offset-2',
                copied ? 'text-emerald-600' : 'text-foreground',
            )}
            title="Copy tracking ID"
        >
            {value || '—'}
            {copied ? <span className="text-[10px]">✓ Copied</span> : <CopyIcon className="h-3 w-3 text-muted-foreground" />}
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


/**
 * Hand this parcel to a courier company.
 *
 * Lives in a modal off the header rather than inline: handing a parcel over
 * is an occasional action, and this page is read far more often than it is
 * acted on.
 *
 * The endpoint answers JSON rather than an Inertia redirect, so this posts
 * with fetch and reloads on success - which is what refreshes the shipment
 * card and the timeline entry the handover writes.
 */
function AssignThreePlModal({ open, cfg = {}, t = {}, onClose }) {
    const [company, setCompany] = React.useState('');
    const [connId, setConnId]   = React.useState('');
    const [busy, setBusy]       = React.useState(false);
    const [error, setError]     = React.useState(null);

    const isModule = (cfg.module_providers || []).includes(company);
    const conns    = (cfg.connections || {})[company] || [];

    // Default to the carrier's default account, so the common case is one pick.
    React.useEffect(() => {
        const d = conns.find((c) => c.is_default) || conns[0];
        setConnId(d ? String(d.id) : '');
        setError(null);
    }, [company]); // eslint-disable-line react-hooks/exhaustive-deps

    // Reopening starts clean - a stale carrier rejection must not look like
    // the result of the attempt the operator is about to make.
    React.useEffect(() => {
        if (open) { setCompany(''); setError(null); }
    }, [open]);

    React.useEffect(() => {
        if (!open) return undefined;
        const onKey = (e) => { if (e.key === 'Escape' && !busy) onClose?.(); };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [open, busy, onClose]);

    if (!open) return null;

    const submit = async (e) => {
        e?.preventDefault?.();
        if (!company || busy) return;
        setBusy(true);
        setError(null);
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const res = await fetch(cfg.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    company,
                    connection_id: isModule && connId ? Number(connId) : undefined,
                }),
            });
            const body = await res.json().catch(() => ({}));
            if (!res.ok || body.error) {
                setError(body.error || 'The carrier rejected the shipment.');
                return;
            }
            onClose?.();
            router.reload({ preserveScroll: true });
        } catch (err) {
            setError(err.message || 'Request failed.');
        } finally {
            setBusy(false);
        }
    };

    return createPortal(
        <div className="fixed inset-0 z-[100] flex items-center justify-center">
            <div className="absolute inset-0 bg-black/40" onClick={() => !busy && onClose?.()} />

            <div
                role="dialog"
                aria-modal="true"
                className="relative bg-background rounded-lg shadow-xl w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto"
            >
                <div className="flex items-start justify-between border-b border-border px-5 py-3">
                    <div>
                        <div className="text-[10px] uppercase tracking-wider text-muted-foreground font-semibold">
                            {t.assign_3pl_eyebrow || 'Courier handover'}
                        </div>
                        <div className="text-base font-semibold mt-0.5">{t.assign_3pl_title}</div>
                    </div>
                    <button
                        type="button"
                        onClick={() => !busy && onClose?.()}
                        className="p-1 -m-1 rounded-md hover:bg-accent text-muted-foreground"
                        aria-label={t.assign_3pl_close || 'Close'}
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>

                <form onSubmit={submit} className="px-5 py-4 space-y-4">
                    <p className="text-sm text-muted-foreground">{t.assign_3pl_help}</p>

                    <div className="space-y-1.5">
                        <Label htmlFor="a3pl-company">{t.assign_3pl_company}</Label>
                        <Select
                            id="a3pl-company"
                            value={company}
                            onChange={(e) => setCompany(e.target.value)}
                        >
                            <option value="">{t.assign_3pl_pick}</option>
                            {(cfg.companies || []).map((c) => (
                                <option key={c.value} value={c.value}>{c.label}</option>
                            ))}
                        </Select>
                    </div>

                    {/* Only the Shipping-module carriers have per-tenant
                        accounts; the legacy four read theirs from env. */}
                    {isModule && (
                        <div className="space-y-1.5">
                            <Label htmlFor="a3pl-conn">{t.assign_3pl_conn}</Label>
                            {conns.length === 0 ? (
                                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                    {t.assign_3pl_noconn}
                                    <a href={cfg.manage_url} className="text-primary hover:underline">
                                        {t.assign_3pl_manage}
                                    </a>
                                </div>
                            ) : (
                                <Select
                                    id="a3pl-conn"
                                    value={connId}
                                    onChange={(e) => setConnId(e.target.value)}
                                >
                                    {conns.map((c) => (
                                        <option key={c.id} value={c.id}>
                                            {c.name}{c.is_default ? ' •' : ''}
                                        </option>
                                    ))}
                                </Select>
                            )}
                        </div>
                    )}

                    {error && (
                        <div className="flex items-start gap-2 rounded-md border border-destructive/30 bg-destructive/5 p-2 text-xs text-destructive">
                            <AlertCircle className="mt-0.5 h-3.5 w-3.5 shrink-0" />
                            <span>{error}</span>
                        </div>
                    )}

                    <div className="flex justify-end gap-2 border-t border-border pt-4">
                        <Button
                            type="button"
                            onClick={() => !busy && onClose?.()}
                            className="bg-transparent text-foreground border border-input hover:bg-accent"
                        >
                            {t.assign_3pl_cancel || 'Cancel'}
                        </Button>
                        <Button type="submit" disabled={!company || busy || (isModule && conns.length === 0)}>
                            {busy
                                ? <Loader2 className="me-1 h-4 w-4 animate-spin" />
                                : <Truck className="me-1 h-4 w-4" />}
                            {busy ? t.assign_3pl_sending : t.assign_3pl_submit}
                        </Button>
                    </div>
                </form>
            </div>
        </div>,
        document.body,
    );
}

/** The handover that already exists, from either the module or the legacy path. */
function ThreePlCard({ info, t }) {
    return (
        <Card>
            <CardContent className="p-4">
                <div className="mb-2 flex items-center gap-2">
                    <Truck className="h-4 w-4 text-primary" />
                    <div className="text-sm font-semibold">{t.three_pl_title}</div>
                </div>
                <div className="grid gap-2 text-sm sm:grid-cols-4">
                    <div>
                        <div className="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">{t.three_pl_carrier}</div>
                        <div className="font-semibold">{info.carrier || '—'}</div>
                    </div>
                    <div>
                        <div className="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">{t.awb}</div>
                        <div className="font-mono font-semibold">{info.awb || '—'}</div>
                    </div>
                    <div>
                        <div className="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">{t.three_pl_created}</div>
                        <div className="font-mono text-xs">{info.created_at || '—'}</div>
                    </div>
                    <div>
                        <div className="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">{t.three_pl_label}</div>
                        {info.label_url
                            ? <a href={info.label_url} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 font-medium text-primary hover:underline">
                                {t.three_pl_open} <ExternalLink className="h-3 w-3" />
                              </a>
                            : <span className="text-muted-foreground">—</span>}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
export default function Details({
    parcel = {}, sender = {}, recipient = {}, attachments = [], events = [],
    panda_3pl = null, three_pl = null, assign_3pl = null, currency = '', permissions = {}, urls = {}, t = {},
}) {
    const [assignOpen, setAssignOpen] = React.useState(false);

    // Group timeline by date.
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
        <AdminLayout title={t.title}>
            <Head title={`${t.title} · ${parcel.tracking_id || ''}`} />

            {/* Sticky header strip — pins the primary CTAs while the operator
                scrolls through the timeline. `-mx-4 md:-mx-8` cancels the
                AdminLayout's main padding so the strip spans edge-to-edge;
                inner padding restores it. Backdrop-blur keeps the content
                below legible on light + dark themes. */}
            <div className="sticky top-0 z-20 mb-5 -mx-4 md:-mx-8 px-4 md:px-8 py-3 bg-background/95 backdrop-blur border-b border-border">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-3 min-w-0">
                        <a href={urls.index} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent shrink-0">
                            <ArrowLeft className="h-4 w-4 me-1" /> {t.back_to_list}
                        </a>
                        <div className="inline-flex items-center gap-2 min-w-0">
                            <Package className="h-5 w-5 text-primary shrink-0" />
                            <CopyableTracking value={parcel.tracking_id} />
                            <StatusPill label={parcel.status_label} color={parcel.status_color} />
                            {parcel.priority === 1 && (
                                <span className="inline-flex items-center gap-1 rounded-full bg-rose-100 text-rose-700 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 shrink-0">
                                    <Flame className="h-3 w-3" /> High
                                </span>
                            )}
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        {assign_3pl?.can && (
                            <button
                                type="button"
                                onClick={() => setAssignOpen(true)}
                                className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent"
                            >
                                <Truck className="h-4 w-4 me-1" /> {t.assign_3pl_button}
                            </button>
                        )}
                        <a href={urls.logs} target="_blank" rel="noreferrer" className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent">
                            <Clock className="h-4 w-4 me-1" /> {t.logs}
                        </a>
                        <a href={urls.print_label} target="_blank" rel="noreferrer" className="inline-flex h-9 items-center rounded-md border border-rose-200 bg-rose-50 text-rose-700 px-3 text-sm font-medium hover:bg-rose-100">
                            <Printer className="h-4 w-4 me-1" /> {t.print}
                        </a>
                        <a href={urls.print} target="_blank" rel="noreferrer" className="inline-flex h-9 items-center rounded-md border border-rose-200 bg-rose-50 text-rose-700 px-3 text-sm font-medium hover:bg-rose-100">
                            <Printer className="h-4 w-4 me-1" /> {t.print_with_tracking}
                        </a>
                        {permissions.edit && (
                            <a href={urls.edit} className="inline-flex h-9 items-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90">
                                <Edit className="h-4 w-4 me-1" /> {t.edit}
                            </a>
                        )}
                    </div>
                </div>
            </div>

            <div className="grid gap-5 lg:grid-cols-3">
                {/* Left column — parties + attachments + timeline */}
                <div className="lg:col-span-2 space-y-5">
                    {/* Parties */}
                    <div className="grid gap-4 md:grid-cols-2">
                        <Party icon={Building2} title={t.sender_info}    {...sender} />
                        <Party icon={MapPin}    title={t.recipient_info} {...recipient} />
                    </div>


                    {/* The handover that exists, then the control to make one.
                        Both sit above attachments so an operator sees carrier
                        state before scrolling into the timeline. */}
                    {three_pl && <ThreePlCard info={three_pl} t={t} />}
                    {/* Panda 3PL block (only when present) */}
                    {panda_3pl && (
                        <Card>
                            <CardContent className="p-4">
                                <div className="mb-2 flex items-center gap-2">
                                    <Truck className="h-4 w-4 text-rose-600" />
                                    <div className="text-sm font-semibold">{t.panda_tracking}</div>
                                </div>
                                <div className="grid gap-2 sm:grid-cols-3 text-sm">
                                    <div>
                                        <div className="text-[10px] uppercase tracking-wide text-muted-foreground font-medium">{t.awb}</div>
                                        <div className="font-mono font-semibold">{panda_3pl.awb || '—'}</div>
                                    </div>
                                    <div>
                                        <div className="text-[10px] uppercase tracking-wide text-muted-foreground font-medium">{t.current_status}</div>
                                        <div className="font-semibold">{panda_3pl.status || '—'}</div>
                                    </div>
                                    <div>
                                        <div className="text-[10px] uppercase tracking-wide text-muted-foreground font-medium">{t.last_update}</div>
                                        <div className="font-mono text-xs">{panda_3pl.datetime || '—'}</div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    )}

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
                                            <a key={i} href={att.url} target="_blank" rel="noreferrer" className="group block rounded-md overflow-hidden border border-border hover:border-primary transition-colors">
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
                                {/* Creation event pinned at the bottom */}
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
                                        created_at: parcel.created_at + ':00',
                                    }}
                                />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Right column — detail card + finance */}
                <div className="lg:col-span-1 space-y-5">
                    <Card>
                        <CardContent className="p-4">
                            <div className="mb-2 flex items-center gap-2 text-sm font-semibold">
                                <Hash className="h-4 w-4 text-muted-foreground" /> {t.tracking_id}
                            </div>
                            {/* Scannable tracking id. The white plate is not
                                decoration - a QR inverted by dark mode fails
                                on a lot of scanners, so the code keeps its
                                own light ground in both themes. */}
                            {parcel.qr && (
                                <div className="mb-3 flex flex-col items-center gap-1.5">
                                    <div className="rounded-md border border-border bg-white p-2">
                                        <img
                                            src={parcel.qr}
                                            alt={`${t.tracking_id}: ${parcel.tracking_id}`}
                                            className="block h-24 w-24"
                                            style={{ imageRendering: 'pixelated' }}
                                        />
                                    </div>
                                    <span className="font-mono text-[11px] font-semibold">{parcel.tracking_id}</span>
                                </div>
                            )}
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
                            <DetailRow label="Delivery"><Money value={parcel.total_delivery_amount} currency={currency} /></DetailRow>
                            <DetailRow label="VAT"><Money value={parcel.vat_amount} currency={currency} /></DetailRow>
                            <DetailRow label="Net payable">
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
            {assign_3pl?.can && (
                <AssignThreePlModal
                    open={assignOpen}
                    cfg={assign_3pl}
                    t={t}
                    onClose={() => setAssignOpen(false)}
                />
            )}
        </AdminLayout>
    );
}
