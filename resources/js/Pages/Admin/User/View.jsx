import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import {
    Edit, Lock, Mail, Phone, MapPin, Calendar, DollarSign, Shield,
    Building2, Layers, Briefcase, IdCard, Hash, User as UserIcon,
    ArrowLeft, Send,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { cn } from '@/lib/utils';

function Money({ value, currency }) {
    return (
        <span className="tabular-nums">
            <span className="text-muted-foreground text-xs me-0.5">{currency}</span>
            {Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
        </span>
    );
}

function Initials({ name, image }) {
    if (image) {
        return <img src={image} alt="" className="h-24 w-24 rounded-full object-cover shrink-0 border border-border" />;
    }
    const text = (name || '?').trim().split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
    return (
        <div className="grid h-24 w-24 place-items-center rounded-full bg-primary/10 text-primary text-2xl font-semibold shrink-0">
            {text || '?'}
        </div>
    );
}

function InfoRow({ icon: Icon, label, value, mono }) {
    return (
        <div className="flex items-start gap-3 py-2 first:pt-0 last:pb-0">
            <Icon className="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
            <div className="flex-1 min-w-0">
                <div className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{label}</div>
                <div className={cn('text-sm mt-0.5', mono && 'font-mono')}>{value ?? '—'}</div>
            </div>
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

export default function View({ user = {}, currency = '', permissions = {}, urls = {}, t = {} }) {
    const isActive = user.status === 1;
    const [sendingCreds, setSendingCreds] = React.useState(false);

    const sendCredentials = () => {
        if (! user.email) return;
        if (! window.confirm(`Send login info to ${user.email}?`)) return;
        setSendingCreds(true);
        router.post(urls.send_credentials, {}, {
            preserveScroll: true,
            onFinish: () => setSendingCreds(false),
        });
    };

    return (
        <AdminLayout title={user.name || t.title} breadcrumbs={[t.title, user.name || '']}>
            <Head title={`${t.title} · ${user.name || ''}`} />

            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <a
                    href={urls.back}
                    className="inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground transition-colors"
                >
                    <ArrowLeft className="h-3.5 w-3.5" />
                    {t.back}
                </a>
                <div className="flex flex-wrap items-center gap-2">
                    {permissions.change_password && (
                        <>
                            <button
                                type="button"
                                onClick={sendCredentials}
                                disabled={sendingCreds || ! user.email}
                                title={user.email ? undefined : t.no_email_hint}
                                className="inline-flex h-9 items-center gap-1.5 rounded-lg border border-input bg-background px-3 text-sm font-medium hover:bg-accent transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <Send className="h-4 w-4" />
                                {t.send_credentials}
                            </button>
                            <a
                                href={urls.change_password}
                                className="inline-flex h-9 items-center gap-1.5 rounded-lg border border-input bg-background px-3 text-sm font-medium hover:bg-accent transition-colors"
                            >
                                <Lock className="h-4 w-4" />
                                {t.change_password}
                            </a>
                        </>
                    )}
                    {permissions.update && (
                        <a
                            href={urls.edit}
                            className="inline-flex h-9 items-center gap-1.5 rounded-lg bg-primary px-3 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90"
                        >
                            <Edit className="h-4 w-4" />
                            {t.edit}
                        </a>
                    )}
                </div>
            </div>

            <div className="grid gap-5 lg:grid-cols-3">
                {/* Left: identity card */}
                <div className="lg:col-span-1">
                    <Card className="rounded-xl shadow-sm border border-border">
                        <CardContent className="p-6">
                            <div className="flex flex-col items-center text-center">
                                <Initials name={user.name} image={user.image} />
                                <div className="mt-4 text-lg font-semibold">{user.name}</div>
                                {user.email && (
                                    <a href={`mailto:${user.email}`} className="text-sm text-primary hover:underline mt-0.5 break-all">
                                        {user.email}
                                    </a>
                                )}
                                {user.mobile && (
                                    <a href={`tel:${user.mobile}`} className="text-xs text-muted-foreground mt-0.5">
                                        {user.mobile}
                                    </a>
                                )}
                                <span className={cn(
                                    'mt-3 inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider',
                                    isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700',
                                )}>
                                    {isActive ? t.active : t.inactive}
                                </span>
                            </div>

                            <div className="mt-6 border-t border-border pt-4 space-y-1">
                                <InfoRow icon={IdCard} label={t.unique_id} value={user.unique_id} mono />
                                <InfoRow icon={Hash}   label={t.nid}       value={user.nid_number} mono />
                                <InfoRow icon={MapPin} label={t.address}   value={user.address} />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Right: work + contact */}
                <div className="lg:col-span-2 space-y-5">
                    <Card className="rounded-xl shadow-sm border border-border">
                        <CardContent className="p-6">
                            <SectionTitle icon={Briefcase}>{t.work}</SectionTitle>
                            <div className="grid gap-1 sm:grid-cols-2">
                                <InfoRow icon={Building2} label={t.hub}         value={user.hub} />
                                <InfoRow icon={Layers}    label={t.department}  value={user.department} />
                                <InfoRow icon={Briefcase} label={t.designation} value={user.designation} />
                                <InfoRow icon={Shield}    label={t.role}        value={user.role} />
                                <InfoRow icon={Calendar}  label={t.joining_date} value={user.joining_date} />
                                <InfoRow icon={DollarSign} label={t.salary}     value={<Money value={user.salary} currency={currency} />} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="rounded-xl shadow-sm border border-border">
                        <CardContent className="p-6">
                            <SectionTitle icon={Mail}>{t.contact}</SectionTitle>
                            <div className="grid gap-1 sm:grid-cols-2">
                                <InfoRow icon={Mail}     label={t.email}     value={user.email} />
                                <InfoRow icon={Phone}    label={t.phone}     value={user.mobile} mono />
                                <InfoRow icon={Shield}   label="User type"   value={user.user_type ? `#${user.user_type}` : '—'} mono />
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
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
