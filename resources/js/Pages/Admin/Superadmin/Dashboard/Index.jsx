import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import {
    Building2, Layers, HandCoins, DollarSign, Calendar, Filter, ArrowRight,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { cn } from '@/lib/utils';

/**
 * Super-admin dashboard (user_type=SUPER_ADMIN branch of /dashboard).
 * Rendered by DashbordController::index — mirrors what the old
 * backend/super-admin/dashboard.blade.php showed: 4 KPI tiles, then two
 * side-by-side "recent" tables (companies + subscriptions). Filter form
 * is a normal GET; the controller reads $request->filter_date.
 */
export default function SuperadminDashboard({ kpis, currency = '$', recent_companies = [], recent_subscriptions = [], filter = {}, urls = {}, t = {} }) {
    const [fromDate, setFromDate] = React.useState(filter.from_date ?? '');

    const applyFilter = (e) => {
        e.preventDefault();
        router.get(urls.submit, { filter_date: fromDate, days: 'custom' }, { preserveScroll: true });
    };

    const kpiTiles = [
        { key: 'company', label: t.total_company,           value: kpis.total_company,         icon: Building2, tone: 'primary', href: urls.companies_index },
        { key: 'plans',   label: t.total_plans,             value: kpis.total_plans,           icon: Layers,    tone: 'info',    href: urls.plans_index     },
        { key: 'subs',    label: t.total_subscription,      value: kpis.total_subscription,    icon: HandCoins, tone: 'success' },
        { key: 'amount',  label: t.total_subscription_price, value: kpis.total_subscription_amount, icon: DollarSign, tone: 'warning', money: true },
    ];

    return (
        // Layout renders its own H1 from `title` + breadcrumb strip; passing
        // undefined breadcrumbs here so the dashboard (a top-level surface)
        // doesn't get a redundant "Dashboard / Dashboard" trail.
        <AdminLayout title={t.title}>
            <Head title={t.title} />

            {/* Filter — right-aligned, no duplicate H1 (layout already shows the title). */}
            <div className="flex items-center justify-end mb-6">
                <form onSubmit={applyFilter} className="flex items-center gap-2">
                    <div className="relative">
                        <Calendar className="absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground h-4 w-4" />
                        <Input
                            value={fromDate}
                            onChange={(e) => setFromDate(e.target.value)}
                            placeholder={t.date_ph}
                            className="pl-9 w-64"
                        />
                    </div>
                    <Button type="submit">
                        <Filter className="h-4 w-4 me-1" />
                        {t.filter}
                    </Button>
                </form>
            </div>

            {/* KPI tiles */}
            <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                {kpiTiles.map((k) => <KpiTile key={k.key} {...k} currency={currency} />)}
            </div>

            {/* Recent tables */}
            <div className="grid grid-cols-1 xl:grid-cols-2 gap-4">

                <Card>
                    <CardContent className="p-0">
                        <div className="flex items-center justify-between px-5 py-4 border-b border-border">
                            <h2 className="text-base font-semibold m-0">{t.recent_company}</h2>
                            <a href={urls.companies_index} className="text-xs font-medium text-primary hover:underline inline-flex items-center gap-1">
                                {t.view_all} <ArrowRight className="h-3 w-3" />
                            </a>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/40">
                                    <tr className="text-start text-xs uppercase tracking-wider text-muted-foreground">
                                        <th className="px-5 py-3 font-medium">{t.name}</th>
                                        <th className="px-5 py-3 font-medium">{t.user_details}</th>
                                        <th className="px-5 py-3 font-medium">{t.modules}</th>
                                        <th className="px-5 py-3 font-medium">{t.status}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {recent_companies.length === 0 ? (
                                        <tr>
                                            <td colSpan={4} className="px-5 py-10 text-center text-sm text-muted-foreground">{t.no_data}</td>
                                        </tr>
                                    ) : recent_companies.map((c) => (
                                        <tr key={c.id} className="hover:bg-muted/30 transition-colors">
                                            <td className="px-5 py-3">
                                                <div className="flex items-center gap-3">
                                                    {c.company?.logo ? (
                                                        <img src={c.company.logo} alt="" className="w-9 h-9 rounded-lg object-cover bg-muted" />
                                                    ) : (
                                                        <span className="grid w-9 h-9 place-items-center rounded-lg bg-muted text-muted-foreground text-xs font-semibold">
                                                            {(c.company?.name || '·').charAt(0).toUpperCase()}
                                                        </span>
                                                    )}
                                                    <span className="font-medium truncate">{c.company?.name ?? '—'}</span>
                                                </div>
                                            </td>
                                            <td className="px-5 py-3">
                                                <div className="flex items-center gap-3">
                                                    {c.avatar ? (
                                                        <img src={c.avatar} alt="" className="w-9 h-9 rounded-full object-cover bg-muted" />
                                                    ) : (
                                                        <span className="grid w-9 h-9 place-items-center rounded-full bg-muted text-muted-foreground text-xs font-semibold">
                                                            {(c.name || '·').charAt(0).toUpperCase()}
                                                        </span>
                                                    )}
                                                    <div className="min-w-0">
                                                        <div className="font-medium truncate">{c.name}</div>
                                                        <div className="text-xs text-muted-foreground truncate">{c.email}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-5 py-3">
                                                {c.plan ? (
                                                    <span className="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-primary/10 text-primary">
                                                        {c.plan.module_count}
                                                    </span>
                                                ) : (
                                                    <span className="text-xs text-muted-foreground/60">—</span>
                                                )}
                                            </td>
                                            <td
                                                className="px-5 py-3"
                                                dangerouslySetInnerHTML={{ __html: c.status_html || '' }}
                                            />
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        <div className="flex items-center justify-between px-5 py-4 border-b border-border">
                            <h2 className="text-base font-semibold m-0">{t.recent_subscriptions}</h2>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/40">
                                    <tr className="text-start text-xs uppercase tracking-wider text-muted-foreground">
                                        <th className="px-5 py-3 font-medium">{t.company}</th>
                                        <th className="px-5 py-3 font-medium">{t.plan}</th>
                                        <th className="px-5 py-3 font-medium text-end">{t.price}</th>
                                        <th className="px-5 py-3 font-medium">{t.expired_date}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {recent_subscriptions.length === 0 ? (
                                        <tr>
                                            <td colSpan={4} className="px-5 py-10 text-center text-sm text-muted-foreground">{t.no_data}</td>
                                        </tr>
                                    ) : recent_subscriptions.map((s) => (
                                        <tr key={s.id} className="hover:bg-muted/30 transition-colors">
                                            <td className="px-5 py-3 font-medium">{s.company_name || '—'}</td>
                                            <td className="px-5 py-3 text-muted-foreground">{s.plan_name || '—'}</td>
                                            <td className="px-5 py-3 text-end tabular-nums font-medium">
                                                <span className="text-muted-foreground me-1">{currency}</span>
                                                {Number(s.price).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                            </td>
                                            <td className="px-5 py-3 text-xs text-muted-foreground tabular-nums">{s.expired_date}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

            </div>
        </AdminLayout>
    );
}

function KpiTile({ label, value, icon: Icon, tone = 'primary', href, money = false, currency = '$' }) {
    const tones = {
        primary: 'from-primary/10 to-primary/5 text-primary',
        info:    'from-sky-100 to-sky-50 text-sky-700 dark:from-sky-950/40 dark:to-sky-950/10 dark:text-sky-300',
        success: 'from-emerald-100 to-emerald-50 text-emerald-700 dark:from-emerald-950/40 dark:to-emerald-950/10 dark:text-emerald-300',
        warning: 'from-amber-100 to-amber-50 text-amber-700 dark:from-amber-950/40 dark:to-amber-950/10 dark:text-amber-200',
    };
    const shown = money
        ? <><span className="text-lg text-muted-foreground me-1 font-medium">{currency}</span>{Number(value ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</>
        : Number(value ?? 0).toLocaleString();
    const inner = (
        <div className="flex items-start justify-between gap-3">
            <div className="min-w-0">
                <div className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{label}</div>
                <div className="mt-1.5 text-3xl font-bold tabular-nums tracking-tight">{shown}</div>
            </div>
            <span className={cn('inline-grid place-items-center h-10 w-10 rounded-xl bg-gradient-to-br shrink-0', tones[tone])}>
                <Icon className="h-5 w-5" />
            </span>
        </div>
    );
    return (
        <Card className={cn('rounded-xl shadow-sm border border-border', href && 'hover:shadow-md hover:-translate-y-0.5 transition-all')}>
            <CardContent className="p-5">
                {href ? <a href={href} className="block text-inherit no-underline">{inner}</a> : inner}
            </CardContent>
        </Card>
    );
}
