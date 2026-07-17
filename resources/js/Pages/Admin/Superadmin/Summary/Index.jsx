import * as React from 'react';
import { Head } from '@inertiajs/react';
import {
    Building2, Users, ShieldCheck, Bike, Store, TicketCheck,
    CreditCard, DollarSign, Clock, TrendingUp, Layers, MessageCircle,
    AlertCircle, ArrowUpRight,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { cn } from '@/lib/utils';

function KpiCard({ icon: Icon, label, value, tone = 'primary', hint, format = 'number', currency }) {
    const tones = {
        primary: 'from-primary/10 to-primary/5 text-primary',
        info:    'from-sky-100 to-sky-50 text-sky-700 dark:from-sky-950/40 dark:to-sky-950/10 dark:text-sky-300',
        success: 'from-emerald-100 to-emerald-50 text-emerald-700 dark:from-emerald-950/40 dark:to-emerald-950/10 dark:text-emerald-300',
        warning: 'from-amber-100 to-amber-50 text-amber-700 dark:from-amber-950/40 dark:to-amber-950/10 dark:text-amber-200',
        rose:    'from-rose-100 to-rose-50 text-rose-700 dark:from-rose-950/40 dark:to-rose-950/10 dark:text-rose-300',
    };
    const shown = format === 'money'
        ? <>
            <span className="text-lg text-muted-foreground me-1 font-medium">{currency}</span>
            {Number(value ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
          </>
        : Number(value ?? 0).toLocaleString();
    return (
        <Card className="rounded-xl shadow-sm border border-border overflow-hidden">
            <CardContent className="p-5">
                <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                        <div className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{label}</div>
                        <div className="mt-1.5 text-3xl font-bold tabular-nums tracking-tight text-foreground">
                            {shown}
                        </div>
                        {hint && <div className="mt-1 text-xs text-muted-foreground">{hint}</div>}
                    </div>
                    <span className={cn('inline-grid place-items-center h-10 w-10 rounded-xl bg-gradient-to-br shrink-0', tones[tone])}>
                        <Icon className="h-5 w-5" />
                    </span>
                </div>
            </CardContent>
        </Card>
    );
}

function SignupSparkline({ data = [], emptyLabel }) {
    if (!data.length || data.every(d => d.count === 0)) {
        return <div className="text-sm text-muted-foreground text-center py-8">{emptyLabel}</div>;
    }
    const max = Math.max(1, ...data.map(d => d.count));
    return (
        <div className="space-y-2">
            <div className="flex items-end gap-[3px] h-28">
                {data.map((d) => {
                    const h = Math.max(2, Math.round((d.count / max) * 100));
                    return (
                        <div
                            key={d.iso}
                            title={`${d.iso}: ${d.count} tenant(s)`}
                            className={cn(
                                'flex-1 rounded-t transition-colors',
                                d.count > 0 ? 'bg-primary/70 hover:bg-primary' : 'bg-muted'
                            )}
                            style={{ height: `${h}%` }}
                        />
                    );
                })}
            </div>
            <div className="flex items-center justify-between text-[10px] text-muted-foreground">
                <span>{data[0]?.iso}</span>
                <span>Today</span>
            </div>
        </div>
    );
}

function hue(s = '') { let h = 0; for (let i = 0; i < s.length; i++) h = ((h << 5) - h + s.charCodeAt(i)) | 0; return Math.abs(h) % 360; }
function Initial({ name = '', shape = 'square', size = 'sm' }) {
    const cls = {
        sm: 'h-8 w-8 text-xs',
        md: 'h-10 w-10 text-sm',
    }[size];
    return (
        <span
            className={cn(
                'inline-grid place-items-center shrink-0 font-semibold text-white',
                shape === 'circle' ? 'rounded-full' : 'rounded-md',
                cls
            )}
            style={{ backgroundColor: `hsl(${hue(name)}, 62%, 48%)` }}
            aria-hidden
        >
            {(name || '?').trim().charAt(0).toUpperCase()}
        </span>
    );
}

/** Priority tone → pill classes. Priority is a free-text field in this repo. */
function PriorityPill({ priority }) {
    const p = String(priority || '').toLowerCase();
    const tone =
        p.includes('high') || p.includes('urgent') ? 'bg-rose-100 text-rose-700' :
        p.includes('medium') ? 'bg-amber-100 text-amber-800' :
        p.includes('low') ? 'bg-sky-100 text-sky-700' :
        'bg-muted text-muted-foreground';
    if (!priority) return null;
    return <span className={cn('inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide', tone)}>{priority}</span>;
}

function TicketStatusPill({ status, t }) {
    const st = Number(status);
    const map = {
        1: { label: t.ticket_open,    tone: 'bg-emerald-100 text-emerald-700' },
        2: { label: t.ticket_closed,  tone: 'bg-muted text-muted-foreground' },
    };
    const shown = map[st] || { label: t.ticket_pending, tone: 'bg-amber-100 text-amber-800' };
    return <span className={cn('inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium', shown.tone)}>{shown.label}</span>;
}

export default function Index({
    currency = '',
    saas = {},
    users = {},
    sub_status = {},
    signup_trend = [],
    plan_dist = [],
    recent_tenants = [],
    recent_tickets = [],
    t = {},
}) {
    const topPlanValue = Math.max(1, ...plan_dist.map(p => p.active_tenants));

    return (
        <AdminLayout title={t.title} breadcrumbs={[]}>
            <Head title={t.title} />

            {/* Subtitle only — AdminLayout already renders {t.title} as the H1. */}
            {t.subtitle && (
                <p className="text-sm text-muted-foreground mb-5 -mt-2">{t.subtitle}</p>
            )}

            {/* SaaS KPI row */}
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-4">
                <KpiCard icon={Building2}  tone="primary" label={t.kpi_tenants}      value={saas.tenants}
                    hint={`+${saas.new_this_month ?? 0} ${t.kpi_new_month?.toLowerCase() ?? ''}`} />
                <KpiCard icon={CreditCard} tone="info"    label={t.kpi_active_subs}  value={saas.active_subs} />
                <KpiCard icon={DollarSign} tone="success" label={t.kpi_mrr}          value={saas.mrr} format="money" currency={currency} />
                <KpiCard icon={TicketCheck} tone={saas.open_tickets > 0 ? 'rose' : 'warning'}
                    label={t.kpi_open_tickets} value={saas.open_tickets}
                    hint={`${saas.total_tickets ?? 0} ${t.kpi_total_tickets?.toLowerCase() ?? ''}`} />
            </div>

            {/* Users breakdown */}
            <div className="mb-6">
                <Card className="rounded-xl shadow-sm border border-border">
                    <CardContent className="p-5">
                        <div className="mb-3 flex items-center gap-2">
                            <Users className="h-4 w-4 text-primary" />
                            <div className="text-sm font-semibold">{t.users_title}</div>
                        </div>
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            {[
                                { icon: Users,        label: t.users_total,     value: users.total,       tone: 'text-foreground' },
                                { icon: ShieldCheck,  label: t.users_admins,    value: users.admins,      tone: 'text-primary' },
                                { icon: Bike,         label: t.users_delivery,  value: users.deliverymen, tone: 'text-amber-600' },
                                { icon: Store,        label: t.users_merchants, value: users.merchants,   tone: 'text-emerald-600' },
                            ].map(({ icon: I, label, value, tone }) => (
                                <div key={label} className="flex items-start gap-3">
                                    <I className={cn('h-4 w-4 mt-1', tone)} />
                                    <div>
                                        <div className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{label}</div>
                                        <div className="text-2xl font-bold tabular-nums mt-0.5">
                                            {Number(value ?? 0).toLocaleString()}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div className="grid gap-6 lg:grid-cols-2 mb-6">
                {/* Signup trend */}
                <Card className="rounded-xl shadow-sm border border-border">
                    <CardContent className="p-5">
                        <div className="mb-4 flex items-center gap-2">
                            <TrendingUp className="h-4 w-4 text-primary" />
                            <div className="text-sm font-semibold">{t.signup_trend_title}</div>
                        </div>
                        <SignupSparkline data={signup_trend} emptyLabel={t.signup_trend_empty} />
                    </CardContent>
                </Card>

                {/* Subscription status */}
                <Card className="rounded-xl shadow-sm border border-border">
                    <CardContent className="p-5">
                        <div className="mb-4 flex items-center gap-2">
                            <CreditCard className="h-4 w-4 text-primary" />
                            <div className="text-sm font-semibold">{t.sub_status_title}</div>
                        </div>
                        <div className="space-y-3">
                            {[
                                { label: t.sub_active,        value: sub_status.active,        tone: 'bg-emerald-500' },
                                { label: t.sub_expiring_soon, value: sub_status.expiring_soon, tone: 'bg-amber-500' },
                                { label: t.sub_expired,       value: sub_status.expired,       tone: 'bg-rose-500' },
                            ].map((row) => {
                                const total = Math.max(1, (sub_status.active || 0) + (sub_status.expired || 0));
                                const pct = Math.round(((row.value || 0) / total) * 100);
                                return (
                                    <div key={row.label}>
                                        <div className="flex items-center justify-between text-xs mb-1">
                                            <span className="font-medium">{row.label}</span>
                                            <span className="text-muted-foreground tabular-nums">{row.value ?? 0}</span>
                                        </div>
                                        <div className="h-2 rounded-full bg-muted overflow-hidden">
                                            <div className={cn('h-full transition-all', row.tone)} style={{ width: `${pct}%` }} />
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div className="grid gap-6 lg:grid-cols-2 mb-6">
                {/* Plan distribution */}
                <Card className="rounded-xl shadow-sm border border-border">
                    <CardContent className="p-0">
                        <div className="px-5 pt-5 pb-3 flex items-center gap-2">
                            <Layers className="h-4 w-4 text-primary" />
                            <div className="text-sm font-semibold">{t.plan_dist_title}</div>
                        </div>
                        {plan_dist.length === 0 ? (
                            <div className="px-5 pb-6 text-sm text-muted-foreground">{t.plan_dist_empty}</div>
                        ) : (
                            <div className="divide-y divide-border">
                                {plan_dist.map((p) => {
                                    const pct = Math.max(2, Math.round((p.active_tenants / topPlanValue) * 100));
                                    return (
                                        <div key={p.id} className="grid grid-cols-[1fr_auto_auto] items-center gap-4 px-5 py-3 hover:bg-muted/30 transition-colors">
                                            <div className="min-w-0">
                                                <div className="text-sm font-medium truncate">{p.name}</div>
                                                <div className="mt-1 h-1.5 w-full rounded-full bg-muted overflow-hidden">
                                                    <div className="h-full bg-primary/70" style={{ width: `${pct}%` }} />
                                                </div>
                                            </div>
                                            <div className="text-xs text-muted-foreground tabular-nums">
                                                <span className="me-1">{currency}</span>
                                                {Number(p.price).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                                            </div>
                                            <div className="text-sm font-semibold tabular-nums w-10 text-end">{p.active_tenants}</div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Recent tenants */}
                <Card className="rounded-xl shadow-sm border border-border">
                    <CardContent className="p-0">
                        <div className="px-5 pt-5 pb-3 flex items-center gap-2">
                            <Building2 className="h-4 w-4 text-primary" />
                            <div className="text-sm font-semibold">{t.recent_tenants_title}</div>
                        </div>
                        {recent_tenants.length === 0 ? (
                            <div className="px-5 pb-6 text-sm text-muted-foreground">{t.recent_tenants_empty}</div>
                        ) : (
                            <div className="divide-y divide-border">
                                {recent_tenants.map((row) => (
                                    <div key={row.id} className="flex items-center gap-3 px-5 py-3 hover:bg-muted/30 transition-colors">
                                        <Initial name={row.name} size="md" />
                                        <div className="flex-1 min-w-0">
                                            <div className="text-sm font-medium truncate">{row.name}</div>
                                            <div className="text-[11px] text-muted-foreground truncate">
                                                {row.plan && <>{row.plan} · </>}{row.created_at} · {row.ago}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Recent tickets — full width */}
            <Card className="rounded-xl shadow-sm border border-border">
                <CardContent className="p-0">
                    <div className="px-5 pt-5 pb-3 flex items-center gap-2">
                        <MessageCircle className="h-4 w-4 text-primary" />
                        <div className="text-sm font-semibold">{t.recent_tickets_title}</div>
                    </div>
                    {recent_tickets.length === 0 ? (
                        <div className="px-5 pb-6 text-sm text-muted-foreground">{t.recent_tickets_empty}</div>
                    ) : (
                        <div className="divide-y divide-border">
                            {recent_tickets.map((r) => (
                                <div key={r.id} className="flex items-start gap-3 px-5 py-3 hover:bg-muted/30 transition-colors">
                                    <span className="inline-grid place-items-center h-8 w-8 rounded-lg bg-primary/10 text-primary shrink-0">
                                        <TicketCheck className="h-4 w-4" />
                                    </span>
                                    <div className="flex-1 min-w-0">
                                        <div className="text-sm font-medium truncate">{r.subject}</div>
                                        <div className="text-[11px] text-muted-foreground truncate mt-0.5">
                                            {r.user && <>{r.user} · </>}{r.ago}
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-1.5 shrink-0">
                                        <PriorityPill priority={r.priority} />
                                        <TicketStatusPill status={r.status} t={t} />
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
