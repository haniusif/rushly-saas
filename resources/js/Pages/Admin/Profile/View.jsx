import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
    Edit, Lock, Mail, Phone, MapPin, Calendar, DollarSign, Shield,
    Building2, Layers, Briefcase, IdCard, Hash, User as UserIcon,
    Wallet, Monitor, Smartphone, ShieldAlert, LogOut, X,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { cn } from '@/lib/utils';

function Money({ value, currency }) {
    const n = Number(value || 0);
    return (
        <span className="tabular-nums">
            <span className="text-muted-foreground text-xs me-0.5">{currency}</span>
            {n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
        </span>
    );
}

function Initials({ name }) {
    const text = (name || '?').trim().split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
    return (
        <div className="grid h-24 w-24 place-items-center rounded-full bg-primary/10 text-primary text-2xl font-semibold shrink-0 shadow-inner">
            {text}
        </div>
    );
}

function InfoRow({ icon: Icon, label, value, mono }) {
    return (
        <div className="flex items-center gap-3 border-b border-border py-2.5 last:border-0">
            <Icon className="h-4 w-4 text-muted-foreground shrink-0" />
            <div className="text-xs uppercase tracking-wide text-muted-foreground font-medium min-w-[100px]">{label}</div>
            <div className={cn('text-sm flex-1', mono && 'font-mono')}>{value || '—'}</div>
        </div>
    );
}

function SectionTitle({ icon: Icon, children }) {
    return (
        <div className="mb-3 flex items-center gap-2 text-sm font-semibold text-foreground">
            <Icon className="h-4 w-4 text-primary" />
            {children}
        </div>
    );
}

function timeAgo(iso) {
    if (!iso) return '';
    const secs = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
    if (secs < 60)    return `${secs}s ago`;
    if (secs < 3600)  return `${Math.floor(secs / 60)}m ago`;
    if (secs < 86400) return `${Math.floor(secs / 3600)}h ago`;
    return `${Math.floor(secs / 86400)}d ago`;
}

/**
 * Browser sessions block rendered inline on the profile page. See
 * BrowserSessionsController for the shape; destroy hits the shared
 * DELETE endpoint with a password-confirmation.
 */
function BrowserSessions({ sessions = [], t = {}, destroyUrl }) {
    const [confirmOpen, setConfirmOpen] = React.useState(false);
    const form = useForm({ password: '' });
    const submit = (e) => {
        e.preventDefault();
        form.delete(destroyUrl, {
            preserveScroll: true,
            onSuccess: () => { setConfirmOpen(false); form.reset(); },
        });
    };
    return (
        <Card className="rounded-xl shadow-sm border border-border">
            <CardContent className="p-6 space-y-5">
                <div>
                    <SectionTitle icon={ShieldAlert}>{t.title}</SectionTitle>
                    <p className="text-sm text-muted-foreground">{t.intro}</p>
                    <p className="mt-2 text-xs text-muted-foreground leading-relaxed">{t.note}</p>
                </div>

                <div className="divide-y divide-border rounded-lg border border-border">
                    {sessions.length === 0 ? (
                        <div className="px-4 py-6 text-sm text-muted-foreground text-center">—</div>
                    ) : sessions.map((s) => {
                        const isMobile = s.platform === 'iOS' || s.platform === 'Android';
                        const Icon = isMobile ? Smartphone : Monitor;
                        return (
                            <div key={s.id} className="flex items-start gap-3 px-4 py-3">
                                <span className="inline-grid place-items-center h-10 w-10 rounded-lg bg-muted text-muted-foreground shrink-0">
                                    <Icon className="h-5 w-5" />
                                </span>
                                <div className="flex-1 min-w-0">
                                    <div className="text-sm font-medium">{s.platform} · {s.browser}</div>
                                    <div className="text-xs text-muted-foreground mt-0.5 flex flex-wrap gap-x-2 gap-y-0.5">
                                        <span className="font-mono">{s.ip}</span>
                                        {s.is_current ? (
                                            <span className="inline-flex items-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">
                                                {t.this_device}
                                            </span>
                                        ) : (
                                            <span>{t.last_active}: {timeAgo(s.last_activity_iso)}</span>
                                        )}
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>

                <div className="flex flex-wrap items-center justify-end gap-2">
                    <Button type="button" onClick={() => setConfirmOpen(true)}>
                        <LogOut className="h-4 w-4 me-1.5" />
                        {t.logout_others}
                    </Button>
                </div>
            </CardContent>

            {confirmOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-xl bg-background border border-border shadow-2xl">
                        <form onSubmit={submit}>
                            <div className="p-6 space-y-4">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 className="text-base font-semibold">{t.logout_others}</h3>
                                        <p className="mt-1.5 text-sm text-muted-foreground">{t.confirm_password}</p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => { setConfirmOpen(false); form.reset(); }}
                                        className="text-muted-foreground hover:text-foreground"
                                    >
                                        <X className="h-4 w-4" />
                                    </button>
                                </div>
                                <div>
                                    <Label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                        {t.password_placeholder}
                                    </Label>
                                    <Input
                                        type="password"
                                        value={form.data.password}
                                        onChange={(e) => form.setData('password', e.target.value)}
                                        autoFocus
                                        required
                                        className="mt-1.5"
                                    />
                                    {form.errors.password && <p className="text-xs text-rose-600 mt-1">{form.errors.password}</p>}
                                </div>
                            </div>
                            <div className="flex items-center justify-end gap-2 px-6 py-4 border-t border-border bg-muted/20 rounded-b-xl">
                                <Button type="button" variant="ghost" onClick={() => { setConfirmOpen(false); form.reset(); }}>
                                    {t.cancel || 'Cancel'}
                                </Button>
                                <Button type="submit" disabled={form.processing}>
                                    {t.confirm}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </Card>
    );
}

export default function View({
    user = {},
    currency = '',
    urls = {},
    t = {},
    browser_sessions = [],
    browser_sessions_t = {},
    browser_sessions_url = '',
}) {
    const isActive = user.status === 1;
    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title]}>
            <Head title={`${t.title} · ${user.name || ''}`} />

            {/* Header strip */}
            <div className="mb-4 flex flex-wrap items-center justify-end gap-2">
                <a href={urls.change_password} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent">
                    <Lock className="h-4 w-4 me-1" /> {t.change_password}
                </a>
                <a href={urls.edit} className="inline-flex h-9 items-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90">
                    <Edit className="h-4 w-4 me-1" /> {t.edit}
                </a>
            </div>

            <div className="grid gap-5 lg:grid-cols-3">
                {/* Left column — identity card */}
                <div className="lg:col-span-1 space-y-5">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex flex-col items-center text-center gap-3">
                                {user.image
                                    ? <img src={user.image} alt="" className="h-24 w-24 rounded-full object-cover ring-4 ring-background shadow-md" />
                                    : <Initials name={user.name} />}
                                <div>
                                    <div className="text-lg font-semibold">{user.name || '—'}</div>
                                    {user.unique_id && (
                                        <div className="mt-0.5 text-xs text-muted-foreground font-mono">#{user.unique_id}</div>
                                    )}
                                    <div className="mt-2">
                                        <span className={cn(
                                            'inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                            isActive ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
                                                     : 'bg-rose-100 text-rose-700 border-rose-200',
                                        )}>
                                            {isActive ? t.active : t.inactive}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div className="mt-5 space-y-1">
                                <div className="flex items-center gap-2 justify-center text-xs text-muted-foreground">
                                    <Phone className="h-3 w-3" />
                                    <span>{user.mobile || '—'}</span>
                                </div>
                                <div className="flex items-center gap-2 justify-center text-xs text-muted-foreground">
                                    <Mail className="h-3 w-3" />
                                    <span className="truncate">{user.email || '—'}</span>
                                </div>
                                {user.address && (
                                    <div className="flex items-start gap-2 justify-center text-xs text-muted-foreground mt-1.5">
                                        <MapPin className="h-3 w-3 mt-0.5 shrink-0" />
                                        <span>{user.address}</span>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <SectionTitle icon={Briefcase}>{t.work}</SectionTitle>
                            <InfoRow icon={Calendar}    label={t.joining_date} value={user.joining_date} />
                            <InfoRow icon={Wallet}      label={t.salary}       value={<Money value={user.salary} currency={currency} />} />
                            <InfoRow icon={Shield}      label={t.role}         value={user.role} />
                        </CardContent>
                    </Card>
                </div>

                {/* Right column — details */}
                <div className="lg:col-span-2 space-y-5">
                    <Card>
                        <CardContent className="pt-6">
                            <SectionTitle icon={UserIcon}>{t.identity}</SectionTitle>
                            <InfoRow icon={UserIcon} label={t.name}      value={user.name} />
                            <InfoRow icon={Mail}     label={t.email}     value={user.email} />
                            <InfoRow icon={Phone}    label={t.phone}     value={user.mobile} mono />
                            <InfoRow icon={IdCard}   label={t.nid}       value={user.nid_number} mono />
                            <InfoRow icon={Hash}     label={t.unique_id} value={user.unique_id} mono />
                            <InfoRow icon={MapPin}   label={t.address}   value={user.address} />
                        </CardContent>
                    </Card>

                    <div className="grid gap-5 md:grid-cols-2">
                        <Card>
                            <CardContent className="pt-6">
                                <SectionTitle icon={Building2}>Organisation</SectionTitle>
                                <InfoRow icon={Building2} label={t.hub}        value={user.hub} />
                                <InfoRow icon={Layers}    label={t.department} value={user.department} />
                                <InfoRow icon={Briefcase} label={t.designation} value={user.designation} />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="pt-6">
                                <SectionTitle icon={Shield}>Access</SectionTitle>
                                <InfoRow icon={Shield}   label={t.role}   value={user.role} />
                                <InfoRow icon={UserIcon} label="User type" value={user.user_type ? `#${user.user_type}` : '—'} mono />
                                <InfoRow icon={Shield}   label={t.status}
                                    value={
                                        <span className={cn(
                                            'inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider',
                                            isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700',
                                        )}>
                                            {isActive ? t.active : t.inactive}
                                        </span>
                                    }
                                />
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <div className="mt-6">
                <BrowserSessions
                    sessions={browser_sessions}
                    t={browser_sessions_t}
                    destroyUrl={browser_sessions_url}
                />
            </div>
        </AdminLayout>
    );
}
