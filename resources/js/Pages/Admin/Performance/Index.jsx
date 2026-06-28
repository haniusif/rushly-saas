import * as React from 'react';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import {
    Package, CheckCircle2, Clock, XCircle, DollarSign, Wallet, TrendingUp,
    Truck, Users, Building2, Network, Timer, ShieldCheck, Smile, BarChart3,
    Crown, AlertTriangle, UserPlus, UserMinus, RotateCcw, Repeat,
    Briefcase, Car, Gauge, Sparkles, TrendingDown, Lightbulb, Target,
    AlertOctagon, Route, Radio, Star,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import KpiTile from './Components/KpiTile';
import FilterBar from './Components/FilterBar';
import ScoreBadge from './Components/ScoreBadge';
import { LineSeries, BarSeries, Donut } from './Components/Charts';

function makeTabs(t) {
    return [
        { id: 'executive', label: t.tab_executive, icon: BarChart3 },
        { id: 'drivers',   label: t.tab_drivers,   icon: Truck },
        { id: 'customers', label: t.tab_customers, icon: Users },
        { id: 'branches',  label: t.tab_branches,  icon: Building2 },
        { id: 'companies', label: t.tab_companies, icon: Network },
        { id: 'insights',  label: t.tab_insights,  icon: Sparkles },
    ];
}

export default function Index({
    filters: initialFilters,
    kpi: initialKpi,
    drivers: initialDrivers,
    customers: initialCustomers,
    hubs: initialHubs,
    companies: initialCompanies,
    insights: initialInsights,
    options, urls, t = {},
}) {
    const [tab, setTab] = React.useState('executive');
    const [filters, setFilters] = React.useState(initialFilters);
    const [kpi, setKpi] = React.useState(initialKpi);
    const [drivers, setDrivers] = React.useState(initialDrivers);
    const [customers, setCustomers] = React.useState(initialCustomers);
    const [hubs, setHubs] = React.useState(initialHubs);
    const [companies, setCompanies] = React.useState(initialCompanies);
    const [insights, setInsights] = React.useState(initialInsights);
    const [autoRefresh, setAutoRefresh] = React.useState(false);
    const [isRefreshing, setIsRefreshing] = React.useState(false);
    const [lastRefresh, setLastRefresh] = React.useState(new Date());

    const refresh = React.useCallback(async () => {
        setIsRefreshing(true);
        try {
            const params = { ...filters };
            const { data } = await axios.get(urls.refresh, { params });
            setFilters(data.filters);
            setKpi(data.kpi);
            setDrivers(data.drivers);
            if (data.customers) setCustomers(data.customers);
            if (data.hubs)      setHubs(data.hubs);
            if (data.companies) setCompanies(data.companies);
            if (data.insights)  setInsights(data.insights);
            setLastRefresh(new Date());
        } catch (e) {
            // Soft-fail: keep the previous state, log to console for ops.
            console.error('Performance refresh failed', e);
        } finally {
            setIsRefreshing(false);
        }
    }, [filters, urls.refresh]);

    // Poll every 60s when auto-refresh is on
    React.useEffect(() => {
        if (!autoRefresh) return;
        const id = setInterval(refresh, 60000);
        return () => clearInterval(id);
    }, [autoRefresh, refresh]);

    return (
        <AdminLayout title={t.title}>
            <Head title={t.title} />

            <div className="space-y-4">
                {/* Subtitle (page heading itself is rendered by AdminLayout) */}
                <p className="-mt-4 text-xs text-muted-foreground">
                    {t.subtitle}
                    <span className="ms-2">{t.last_refresh}: <span className="tabular-nums">{lastRefresh.toLocaleTimeString()}</span></span>
                </p>

                {/* Filters & controls */}
                <FilterBar
                    t={t}
                    filters={filters}
                    options={options}
                    urls={urls}
                    onRefresh={refresh}
                    autoRefresh={autoRefresh}
                    setAutoRefresh={setAutoRefresh}
                    isRefreshing={isRefreshing}
                />

                {/* Tab nav */}
                <div className="flex items-center gap-1 border-b border-border">
                    {makeTabs(t).map(({ id, label, icon: Icon }) => (
                        <button
                            key={id} type="button" onClick={() => setTab(id)}
                            className={`inline-flex items-center gap-2 px-3 py-2 text-sm font-medium border-b-2 -mb-px transition-colors ${
                                tab === id
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            <Icon className="h-4 w-4" /> {label}
                        </button>
                    ))}
                </div>

                {tab === 'executive' && <ExecutiveView kpi={kpi} t={t} />}
                {tab === 'drivers'   && <DriversView drivers={drivers} t={t} />}
                {tab === 'customers' && <CustomersView customers={customers} t={t} />}
                {tab === 'branches'  && <BranchesView hubs={hubs} t={t} />}
                {tab === 'companies' && <CompaniesView companies={companies} t={t} />}
                {tab === 'insights'  && <InsightsView insights={insights} t={t} />}
            </div>
        </AdminLayout>
    );
}

/* ------------------------------- Executive view ------------------------------- */

function ExecutiveView({ kpi, t }) {
    const { orders, financial, activity, service } = kpi;
    return (
        <div className="space-y-4">

            {/* Orders */}
            <SectionTitle title={t.sec_orders} />
            <div className="grid gap-3 grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                <KpiTile label={t.kpi_total_orders} value={orders.total} icon={Package} accent="info"
                         trend={orders.growth_rate} sub={`${t.sub_vs_prev} ${orders.previous_total}`}  t={t} />
                <KpiTile label={t.kpi_completed} value={orders.completed} icon={CheckCircle2} accent="good"
                         sub={`${(orders.completion_rate * 100).toFixed(1)}% ${t.sub_rate_suffix}`}  t={t} />
                <KpiTile label={t.kpi_pending} value={orders.pending} icon={Clock} accent="warn"  t={t} />
                <KpiTile label={t.kpi_cancelled} value={orders.cancelled} icon={XCircle} accent="bad"  t={t} />
                <KpiTile label={t.kpi_growth_rate} value={orders.growth_rate} fmt="pct" icon={TrendingUp}
                         accent={orders.growth_rate >= 0 ? 'good' : 'bad'} t={t}  />
            </div>

            {/* Financial */}
            <SectionTitle title={t.sec_financial} />
            <div className="grid gap-3 grid-cols-2 md:grid-cols-3 lg:grid-cols-3">
                <KpiTile label={t.kpi_revenue} value={financial.revenue} fmt="money" icon={DollarSign} accent="good"
                         sub={financial.currency}  t={t} />
                <KpiTile label={t.kpi_expenses} value={financial.expenses} fmt="money" icon={Wallet} accent="warn"  t={t} />
                <KpiTile label={t.kpi_profit} value={financial.profit} fmt="money" icon={TrendingUp}
                         accent={financial.profit >= 0 ? 'good' : 'bad'} t={t}  />
            </div>

            {/* Activity */}
            <SectionTitle title={t.sec_activity} />
            <div className="grid gap-3 grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                <KpiTile label={t.kpi_active_drivers} value={activity.active_drivers} icon={Truck} accent="info"
                         sub={`${t.sub_of} ${activity.total_drivers}`}  t={t} />
                <KpiTile label={t.kpi_active_customers} value={activity.active_customers} icon={Users} accent="info"
                         sub={`${t.sub_of} ${activity.total_customers}`}  t={t} />
                <KpiTile label={t.kpi_active_companies} value={activity.active_companies} icon={Network} accent="info"
                         sub={t.sub_3pl_operating}  t={t} />
                <KpiTile label={t.kpi_active_branches} value={activity.active_branches} icon={Building2} accent="info"
                         sub={`${t.sub_of} ${activity.total_branches}`}  t={t} />
                <KpiTile label={t.kpi_avg_delivery_time}
                         value={service.avg_delivery_hours != null ? `${service.avg_delivery_hours} h` : '—'}
                         icon={Timer}  t={t} />
            </div>

            <div className="grid gap-3 grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                <KpiTile label={t.kpi_avg_distance}
                         value={service.avg_distance_km != null ? `${service.avg_distance_km} km` : '—'}
                         icon={Route}
                         sub={t.sub_pickup_dropoff}  t={t} />
            </div>

            {/* Service quality */}
            <SectionTitle title={t.sec_service_q} />
            <div className="grid gap-3 grid-cols-1 md:grid-cols-3">
                <Card>
                    <CardContent className="p-4 flex items-center gap-4">
                        <Donut value={service.sla_compliance ?? 0} label={t.donut_sla}  t={t} />
                        <div className="min-w-0">
                            <div className="text-[11px] uppercase tracking-wider font-medium text-muted-foreground">{t.kpi_sla_compliance}</div>
                            <div className="text-2xl font-semibold tabular-nums">
                                {service.sla_compliance != null ? `${(service.sla_compliance * 100).toFixed(1)}%` : '—'}
                            </div>
                            <div className="text-xs text-muted-foreground mt-1">
                                {service.abnormal_open} {t.sub_open_abnormal}
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-4 flex items-center gap-4">
                        <Donut value={service.on_time_rate ?? 0} label={t.donut_on_time} color="#10b981"  t={t} />
                        <div className="min-w-0">
                            <div className="flex items-center gap-2">
                                <div className="text-[11px] uppercase tracking-wider font-medium text-muted-foreground">{t.kpi_on_time_delivery}</div>
                                {service.on_time_is_real
                                    ? <span className="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200" title={service.proxies?.on_time_rate}>{t.real_label}</span>
                                    : <span className="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-amber-50 text-amber-700 border border-amber-200" title={service.proxies?.on_time_rate}>{t.proxy_label}</span>}
                            </div>
                            <div className="text-2xl font-semibold tabular-nums">
                                {service.on_time_rate != null ? `${(service.on_time_rate * 100).toFixed(1)}%` : '—'}
                            </div>
                            <div className="text-xs text-muted-foreground mt-1">
                                {service.on_time_is_real
                                    ? t.sub_delivery_lte_expected
                                    : t.sub_delta_sla_hours}
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-4 flex items-center gap-4">
                        <Donut value={service.satisfaction ?? 0} label={t.donut_sat} color="#6366f1"  t={t} />
                        <div className="min-w-0">
                            <div className="flex items-center gap-2">
                                <div className="text-[11px] uppercase tracking-wider font-medium text-muted-foreground">{t.kpi_customer_satisfaction}</div>
                                {service.satisfaction_is_real
                                    ? <span className="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200" title={service.proxies?.satisfaction}>{t.real_label}</span>
                                    : <span className="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-amber-50 text-amber-700 border border-amber-200" title={service.proxies?.satisfaction}>{t.proxy_label}</span>}
                            </div>
                            <div className="text-2xl font-semibold tabular-nums">
                                {service.satisfaction != null ? `${(service.satisfaction * 100).toFixed(1)}%` : '—'}
                            </div>
                            <div className="text-xs text-muted-foreground mt-1">
                                {service.satisfaction_is_real
                                    ? `${service.ratings_count} ${t.sub_ratings_count}`
                                    : `${service.support_tickets} ${t.sub_tickets_in_range}`}
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}

/* -------------------------------- Drivers view -------------------------------- */

function DriversView({ drivers, t }) {
    const { kpi, ranking, time_series, rating_distribution } = drivers;
    return (
        <div className="space-y-4">

            {/* Driver KPIs */}
            <SectionTitle title={t.sec_driver_kpi} />
            <div className="grid gap-3 grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                <KpiTile label={t.kpi_total_drivers}        value={kpi.total_drivers}         icon={Truck}  t={t} />
                <KpiTile label={t.kpi_active}               value={kpi.active_drivers}        icon={Truck} accent="good"  t={t} />
                <KpiTile label={kpi.online_is_real ? t.kpi_online_now : t.kpi_online_24h}
                         value={kpi.online_drivers} icon={Radio} accent="info"
                         proxy={!kpi.online_is_real}  t={t} />
                <KpiTile label={t.kpi_completed_deliveries} value={kpi.completed_deliveries}  icon={CheckCircle2} accent="good"  t={t} />
                <KpiTile label={t.kpi_cancelled}            value={kpi.cancelled_deliveries}  icon={XCircle} accent="bad"  t={t} />

                <KpiTile label={t.kpi_acceptance_rate}   value={kpi.acceptance_rate}  fmt="pct" icon={ShieldCheck} accent="good"  t={t} />
                <KpiTile label={t.kpi_rejection_rate}    value={kpi.rejection_rate}   fmt="pct" icon={XCircle} accent="warn"  t={t} />
                <KpiTile label={t.kpi_avg_pickup_time}  value={kpi.avg_pickup_hours  != null ? `${kpi.avg_pickup_hours} h`  : '—'} icon={Timer}  t={t} />
                <KpiTile label={t.kpi_avg_delivery_time}value={kpi.avg_delivery_hours!= null ? `${kpi.avg_delivery_hours} h`: '—'} icon={Timer}  t={t} />
                <KpiTile label={t.kpi_distance_covered}  value={kpi.distance_km != null ? `${kpi.distance_km} km` : '—'} icon={Route} accent="info"  t={t} />
                <KpiTile label={t.kpi_revenue_per_driver}  value={kpi.revenue_per_driver} fmt="money" icon={DollarSign} accent="good"  t={t} />

                <KpiTile label={t.kpi_complaints} value={kpi.complaints} icon={AlertTriangle} accent="warn"
                         proxy={!kpi.complaints_is_real}
                         sub={kpi.complaints_is_real ? t.sub_driver_linked_tickets : t.sub_all_tickets}  t={t} />
                <KpiTile label={t.kpi_customer_rating}
                         value={kpi.customer_rating != null ? `${kpi.customer_rating} / 5` : '—'}
                         icon={Star}
                         accent={kpi.customer_rating != null ? (kpi.customer_rating >= 4.5 ? 'good' : kpi.customer_rating >= 3.5 ? 'info' : 'warn') : undefined}
                         sub={`${kpi.customer_rating_count ?? 0} ${t.sub_ratings_count}`} t={t}  />
                <Card>
                    <CardContent className="p-4">
                        <div className="text-[11px] uppercase tracking-wider font-medium text-muted-foreground">{t.kpi_cohort_score}</div>
                        <div className="mt-2">
                            <ScoreBadge score={kpi.cohort_score} band={kpi.cohort_band} size="md"  t={t} />
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Chart row */}
            <div className="grid gap-3 grid-cols-1 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardContent className="p-4">
                        <div className="flex items-center justify-between mb-2">
                            <h3 className="text-sm font-semibold">{t.chart_daily_performance}</h3>
                            <p className="text-xs text-muted-foreground">{t.chart_delivered_vs_assigned}</p>
                        </div>
                        <LineSeries data={time_series}  t={t} />
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-center justify-between mb-2">
                            <h3 className="text-sm font-semibold">{t.chart_rating_distribution}</h3>
                            <span className="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-amber-50 text-amber-700 border border-amber-200">{t.proxy_label}</span>
                        </div>
                        <BarSeries data={rating_distribution}  t={t} />
                        <p className="text-[11px] text-muted-foreground mt-2">{t.chart_rating_buckets_note}</p>
                    </CardContent>
                </Card>
            </div>

            {/* Driver leaderboard */}
            <Card>
                <CardContent className="p-0">
                    <div className="flex items-center justify-between px-4 py-3 border-b border-border">
                        <h3 className="text-sm font-semibold flex items-center gap-2">
                            <Crown className="h-4 w-4 text-amber-500" /> {t.lb_driver_title}
                        </h3>
                        <span className="text-xs text-muted-foreground">{ranking.length} {t.lb_drivers_in_win}</span>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/40">
                                <tr className="text-left text-[11px] uppercase tracking-wider text-muted-foreground">
                                    <th className="px-4 py-2 font-medium w-10">#</th>
                                    <th className="px-4 py-2 font-medium">{t.tbl_driver}</th>
                                    <th className="px-4 py-2 font-medium text-right">{t.tbl_delivered}</th>
                                    <th className="px-4 py-2 font-medium text-right">{t.tbl_handled}</th>
                                    <th className="px-4 py-2 font-medium text-right">{t.tbl_completion}</th>
                                    <th className="px-4 py-2 font-medium text-right">{t.tbl_on_time}</th>
                                    <th className="px-4 py-2 font-medium text-right">{t.tbl_rating}</th>
                                    <th className="px-4 py-2 font-medium text-right">{t.tbl_revenue}</th>
                                    <th className="px-4 py-2 font-medium">{t.tbl_score}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {ranking.length === 0 && (
                                    <tr><td colSpan={9} className="px-4 py-10 text-center text-sm text-muted-foreground">{t.no_driver_activity}</td></tr>
                                )}
                                {ranking.map((row, i) => (
                                    <tr key={row.driver_id} className="hover:bg-muted/30">
                                        <td className="px-4 py-2 text-muted-foreground tabular-nums">{i + 1}</td>
                                        <td className="px-4 py-2 font-medium">{row.name}</td>
                                        <td className="px-4 py-2 text-right tabular-nums">{row.delivered}</td>
                                        <td className="px-4 py-2 text-right tabular-nums text-muted-foreground">{row.handled}</td>
                                        <td className="px-4 py-2 text-right tabular-nums">{(row.completion_rate * 100).toFixed(1)}%</td>
                                        <td className="px-4 py-2 text-right tabular-nums">{row.on_time_rate != null ? `${(row.on_time_rate * 100).toFixed(1)}%` : '—'}</td>
                                        <td className="px-4 py-2 text-right tabular-nums">
                                            {row.customer_rating != null
                                                ? <span className="inline-flex items-center gap-0.5">{row.customer_rating} <Star className="h-3 w-3 text-amber-500" /><span className="text-[10px] text-muted-foreground ms-1">({row.rating_count})</span></span>
                                                : <span className="text-muted-foreground">—</span>}
                                        </td>
                                        <td className="px-4 py-2 text-right tabular-nums">{row.revenue.toLocaleString(undefined, { maximumFractionDigits: 2 })}</td>
                                        <td className="px-4 py-2"><ScoreBadge score={row.score} band={row.band}  t={t} /></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}

function SectionTitle({ title }) {
    return (
        <div className="flex items-center gap-2 pt-1">
            <h2 className="text-xs uppercase tracking-wider font-semibold text-muted-foreground">{title}</h2>
            <div className="flex-1 h-px bg-border" />
        </div>
    );
}

/* ------------------------------- Customers view ------------------------------- */

function CustomersView({ customers, t }) {
    if (!customers) return null;
    const { kpi, top, segments, growth, churn } = customers;
    const growthSeries = (growth ?? []).map((d) => ({
        label: d.label, delivered: d.active, assigned: d.new,
    }));

    return (
        <div className="space-y-4">
            <SectionTitle title={t.sec_customer_kpi} />
            <div className="grid gap-3 grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                <KpiTile label={t.kpi_total_customers}     value={kpi.total_customers}      icon={Users}  t={t} />
                <KpiTile label={t.kpi_active}              value={kpi.active_customers}     icon={Users} accent="good"  t={t} />
                <KpiTile label={t.kpi_new_in_range}      value={kpi.new_customers}        icon={UserPlus} accent="info"  t={t} />
                <KpiTile label={t.kpi_returning}           value={kpi.returning_customers}  icon={Repeat} accent="info"  t={t} />
                <KpiTile label={t.kpi_lost_churn}        value={kpi.lost_customers}       icon={UserMinus} accent="bad"  t={t} />

                <KpiTile label={t.kpi_avg_ltv}  value={kpi.lifetime_value}       fmt="money" icon={DollarSign} accent="good" proxy  t={t} />
                <KpiTile label={t.kpi_avg_order_value}     value={kpi.avg_order_value}      fmt="money" icon={DollarSign}  t={t} />
                <KpiTile label={t.kpi_total_spending}      value={kpi.total_spending}       fmt="money" icon={Wallet}  t={t} />
                <KpiTile label={t.kpi_order_frequency}     value={`${kpi.order_frequency} /d`} icon={Gauge} sub={t.sub_order_freq}  t={t} />
                <KpiTile label={t.kpi_cancellation_rate}   value={kpi.cancellation_rate}    fmt="pct" icon={XCircle} accent="warn"  t={t} />

                <KpiTile label={t.kpi_retention_rate}      value={kpi.retention_rate}       fmt="pct" icon={RotateCcw} accent="info" proxy  t={t} />
                <KpiTile label={t.kpi_satisfaction}        value={kpi.satisfaction}         fmt="pct" icon={Smile} accent="info" proxy  t={t} />
                <Card>
                    <CardContent className="p-4">
                        <div className="text-[11px] uppercase tracking-wider font-medium text-muted-foreground">{t.kpi_cohort_score}</div>
                        <div className="mt-2"><ScoreBadge score={kpi.cohort_score} band={kpi.cohort_band} size="md"  t={t} /></div>
                    </CardContent>
                </Card>
            </div>

            <div className="grid gap-3 grid-cols-1 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardContent className="p-4">
                        <div className="flex items-center justify-between mb-2">
                            <h3 className="text-sm font-semibold">{t.chart_customer_growth}</h3>
                            <p className="text-xs text-muted-foreground">{t.chart_active_vs_new}</p>
                        </div>
                        <LineSeries data={growthSeries}  t={t} />
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-center justify-between mb-2">
                            <h3 className="text-sm font-semibold">{t.chart_customer_segments}</h3>
                            <p className="text-xs text-muted-foreground">{t.chart_by_delivered_spend}</p>
                        </div>
                        <BarSeries data={segments}  t={t} />
                    </CardContent>
                </Card>
            </div>

            <div className="grid gap-3 grid-cols-1 lg:grid-cols-3">
                <Card>
                    <CardContent className="p-4 flex items-center gap-4">
                        <Donut value={churn?.churn_rate ?? 0} label={t.donut_churn} color="#f43f5e"  t={t} />
                        <div className="min-w-0">
                            <div className="text-[11px] uppercase tracking-wider font-medium text-muted-foreground">{t.donut_churn}</div>
                            <div className="text-2xl font-semibold tabular-nums">
                                {churn?.churn_rate != null ? `${(churn.churn_rate * 100).toFixed(1)}%` : '—'}
                            </div>
                            <div className="text-xs text-muted-foreground mt-1">
                                {churn?.churned} {t.sub_of} {churn?.prior_customers} {t.sub_churn_of_prior}
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card className="lg:col-span-2">
                    <CardContent className="p-4">
                        <h3 className="text-sm font-semibold mb-3 flex items-center gap-2">
                            <Crown className="h-4 w-4 text-amber-500" /> {t.lb_top_customers}
                        </h3>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/40">
                                    <tr className="text-left text-[11px] uppercase tracking-wider text-muted-foreground">
                                        <th className="px-3 py-2 font-medium w-8">#</th>
                                        <th className="px-3 py-2 font-medium">{t.tbl_customer}</th>
                                        <th className="px-3 py-2 font-medium text-right">{t.tbl_orders}</th>
                                        <th className="px-3 py-2 font-medium text-right">{t.tbl_revenue}</th>
                                        <th className="px-3 py-2 font-medium text-right">{t.tbl_aov}</th>
                                        <th className="px-3 py-2 font-medium">{t.tbl_score}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {top.length === 0 && (
                                        <tr><td colSpan={6} className="px-3 py-8 text-center text-sm text-muted-foreground">{t.no_customer_activity}</td></tr>
                                    )}
                                    {top.map((row, i) => (
                                        <tr key={row.merchant_id} className="hover:bg-muted/30">
                                            <td className="px-3 py-2 tabular-nums text-muted-foreground">{i + 1}</td>
                                            <td className="px-3 py-2 font-medium">{row.name}</td>
                                            <td className="px-3 py-2 text-right tabular-nums">{row.orders}</td>
                                            <td className="px-3 py-2 text-right tabular-nums">{row.revenue.toLocaleString(undefined, { maximumFractionDigits: 2 })}</td>
                                            <td className="px-3 py-2 text-right tabular-nums">{row.aov.toLocaleString(undefined, { maximumFractionDigits: 2 })}</td>
                                            <td className="px-3 py-2"><ScoreBadge score={row.score} band={row.band}  t={t} /></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}

/* ------------------------------- Companies view ------------------------------- */

function CompaniesView({ companies, t }) {
    if (!companies) return null;
    const { kpi, ranking, compare } = companies;
    const compareSeries = (compare ?? []).map((d) => ({ label: d.label, delivered: d.revenue, assigned: d.orders }));

    return (
        <div className="space-y-4">
            <SectionTitle title={t.sec_company_kpi} />
            <div className="grid gap-3 grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                <KpiTile label={t.kpi_total_companies}   value={kpi.total_companies}  icon={Network}  t={t} />
                <KpiTile label={t.kpi_active}            value={kpi.active_companies} icon={Network} accent="good"  t={t} />
                <KpiTile label={t.kpi_fleet_size}        value={kpi.fleet_size}       icon={Truck} accent="info"  t={t} />
                <KpiTile label={t.kpi_handled}           value={kpi.handled}          icon={Package}  t={t} />
                <KpiTile label={t.kpi_completed}         value={kpi.completed}        icon={CheckCircle2} accent="good"  t={t} />

                <KpiTile label={t.kpi_revenue}           value={kpi.revenue}          fmt="money" icon={DollarSign} accent="good"  t={t} />
                <KpiTile label={t.kpi_expenses}          value={kpi.expenses}         fmt="money" icon={Wallet} accent="warn"  t={t} />
                <KpiTile label={t.kpi_profit}            value={kpi.profit}           fmt="money" icon={TrendingUp}
                         accent={kpi.profit >= 0 ? 'good' : 'bad'} t={t}  />
                <KpiTile label={t.kpi_avg_delivery}      value={kpi.avg_delivery_hours != null ? `${kpi.avg_delivery_hours} h` : '—'} icon={Timer}  t={t} />
                <KpiTile label={t.kpi_fleet_utilization} value={kpi.fleet_utilization} fmt="pct" icon={Gauge} accent="info" proxy  t={t} />

                <KpiTile label={t.kpi_success_rate}      value={kpi.success_rate}    fmt="pct" icon={CheckCircle2} accent="good"  t={t} />
                <KpiTile label={t.kpi_sla_compliance}    value={kpi.sla_compliance}  fmt="pct" icon={ShieldCheck} accent="info" proxy  t={t} />
                <KpiTile label={t.kpi_satisfaction}     value={kpi.satisfaction}    fmt="pct" icon={Smile} accent="info" proxy  t={t} />
                <Card>
                    <CardContent className="p-4">
                        <div className="text-[11px] uppercase tracking-wider font-medium text-muted-foreground">{t.kpi_cohort_score}</div>
                        <div className="mt-2"><ScoreBadge score={kpi.cohort_score} band={kpi.cohort_band} size="md"  t={t} /></div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardContent className="p-4">
                    <div className="flex items-center justify-between mb-2">
                        <h3 className="text-sm font-semibold">{t.chart_revenue_vs_orders}</h3>
                        <p className="text-xs text-muted-foreground">{t.chart_revenue_orders_legend}</p>
                    </div>
                    <LineSeries data={compareSeries}  t={t} />
                </CardContent>
            </Card>

            <Card>
                <CardContent className="p-0">
                    <div className="flex items-center justify-between px-4 py-3 border-b border-border">
                        <h3 className="text-sm font-semibold flex items-center gap-2">
                            <Crown className="h-4 w-4 text-amber-500" /> {t.lb_company_title}
                        </h3>
                        <span className="text-xs text-muted-foreground">{ranking.length} {t.lb_companies_in_win}</span>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/40">
                                <tr className="text-left text-[11px] uppercase tracking-wider text-muted-foreground">
                                    <th className="px-4 py-2 font-medium w-10">#</th>
                                    <th className="px-4 py-2 font-medium">{t.tbl_company}</th>
                                    <th className="px-4 py-2 font-medium text-right">{t.tbl_fleet}</th>
                                    <th className="px-4 py-2 font-medium text-right">{t.tbl_handled}</th>
                                    <th className="px-4 py-2 font-medium text-right">{t.tbl_success}</th>
                                    <th className="px-4 py-2 font-medium text-right">{t.tbl_revenue}</th>
                                    <th className="px-4 py-2 font-medium text-right">{t.tbl_profit}</th>
                                    <th className="px-4 py-2 font-medium text-right">{t.tbl_avg_h}</th>
                                    <th className="px-4 py-2 font-medium">{t.tbl_score}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {ranking.length === 0 && (
                                    <tr><td colSpan={9} className="px-4 py-10 text-center text-sm text-muted-foreground">{t.no_company_activity}</td></tr>
                                )}
                                {ranking.map((r, i) => (
                                    <tr key={r.company_id} className="hover:bg-muted/30">
                                        <td className="px-4 py-2 tabular-nums text-muted-foreground">{i + 1}</td>
                                        <td className="px-4 py-2 font-medium">{r.name}</td>
                                        <td className="px-4 py-2 text-right tabular-nums">{r.fleet_size}</td>
                                        <td className="px-4 py-2 text-right tabular-nums">{r.handled}</td>
                                        <td className="px-4 py-2 text-right tabular-nums">{r.success_rate != null ? `${(r.success_rate * 100).toFixed(1)}%` : '—'}</td>
                                        <td className="px-4 py-2 text-right tabular-nums">{r.revenue.toLocaleString(undefined, { maximumFractionDigits: 2 })}</td>
                                        <td className={`px-4 py-2 text-right tabular-nums ${r.profit >= 0 ? 'text-emerald-600' : 'text-rose-600'}`}>
                                            {r.profit.toLocaleString(undefined, { maximumFractionDigits: 2 })}
                                        </td>
                                        <td className="px-4 py-2 text-right tabular-nums">{r.avg_hours ?? '—'}</td>
                                        <td className="px-4 py-2"><ScoreBadge score={r.score} band={r.band}  t={t} /></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}

/* ------------------------------- Insights view ------------------------------- */

function InsightsView({ insights, t }) {
    if (!insights) return null;
    const { highlights, risks, churn_watch, bottlenecks, forecast, suggestions } = insights;

    const HighlightCard = ({ ic: Icon, color, item }) => {
        if (!item) return null;
        return (
            <Card>
                <CardContent className="p-4">
                    <div className="flex items-center gap-2 text-[11px] uppercase tracking-wider font-medium text-muted-foreground">
                        <Icon className={`h-3.5 w-3.5 ${color}`} /> {item.kind}
                    </div>
                    <div className="mt-2 font-semibold truncate">{item.name}</div>
                    {item.score != null && (
                        <div className="mt-2"><ScoreBadge score={item.score} band={item.band}  t={t} /></div>
                    )}
                </CardContent>
            </Card>
        );
    };

    // Forecast chart: history + dashed forecast
    const allWeeks = [...(forecast?.history ?? []), ...(forecast?.forecast ?? [])];
    const forecastSeries = allWeeks.map((p) => ({
        label: p.label,
        delivered: p.projected ? null : p.revenue,
        assigned:  p.projected ? p.revenue : null,
    }));

    return (
        <div className="space-y-4">

            <SectionTitle title={t.sec_highlights} />
            <div className="grid gap-3 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                <HighlightCard ic={Crown}        color="text-amber-500"  item={highlights.best_driver} />
                <HighlightCard ic={Crown}        color="text-amber-500"  item={highlights.best_customer} />
                <HighlightCard ic={Crown}        color="text-amber-500"  item={highlights.best_branch} />
                <HighlightCard ic={Crown}        color="text-amber-500"  item={highlights.best_company} />
                <HighlightCard ic={TrendingUp}   color="text-emerald-600" item={highlights.highest_revenue_company} />
                <HighlightCard ic={TrendingUp}   color="text-emerald-600" item={highlights.fastest_growing_branch} />
                <HighlightCard ic={TrendingDown} color="text-rose-600"    item={highlights.worst_driver} />
            </div>

            {risks?.length > 0 && (
                <>
                    <SectionTitle title={t.sec_risks} />
                    <div className="space-y-2">
                        {risks.map((r, i) => (
                            <Card key={i} className={
                                r.level === 'high'
                                    ? 'border-rose-200 bg-rose-50/40'
                                    : 'border-amber-200 bg-amber-50/40'
                            }>
                                <CardContent className="p-4 flex items-start gap-3">
                                    <AlertOctagon className={`h-5 w-5 ${r.level === 'high' ? 'text-rose-600' : 'text-amber-600'}`} />
                                    <div className="min-w-0">
                                        <div className="flex items-center gap-2">
                                            <span className={`inline-flex items-center px-2 py-0.5 text-[10px] font-medium rounded-full ${r.level === 'high' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800'}`}>
                                                {r.level === 'high' ? t.risk_high : t.risk_medium}
                                            </span>
                                            <span className="text-xs text-muted-foreground">{r.kind}</span>
                                        </div>
                                        <div className="font-medium mt-1">{r.title}</div>
                                        <div className="text-sm text-muted-foreground">{r.detail}</div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </>
            )}

            <div className="grid gap-3 grid-cols-1 lg:grid-cols-2">
                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-center justify-between mb-2">
                            <h3 className="text-sm font-semibold flex items-center gap-2">
                                <Target className="h-4 w-4 text-primary" /> {t.fc_revenue_forecast}
                            </h3>
                            {forecast?.confidence != null && (
                                <span className="text-[11px] text-muted-foreground">
                                    {t.fc_r2} {forecast.confidence}
                                </span>
                            )}
                        </div>
                        {allWeeks.length >= 2
                            ? <LineSeries data={forecastSeries}  t={t} />
                            : <div className="text-xs text-muted-foreground py-8 text-center">{forecast?.note ?? t.fc_not_enough}</div>}
                        {forecast?.note && allWeeks.length >= 2 && (
                            <div className="text-[11px] text-muted-foreground mt-2">{forecast.note}</div>
                        )}
                        <div className="flex items-center gap-3 text-[11px] text-muted-foreground mt-2">
                            <span className="inline-flex items-center gap-1.5">
                                <span className="h-2 w-2 rounded-full" style={{ background: '#10b981' }} /> {t.fc_history}
                            </span>
                            <span className="inline-flex items-center gap-1.5">
                                <span className="h-2 w-2 rounded-full" style={{ background: '#a21f5c' }} /> {t.fc_projected}
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-4">
                        <h3 className="text-sm font-semibold flex items-center gap-2 mb-3">
                            <UserMinus className="h-4 w-4 text-rose-500" /> {t.churn_watchlist}
                            <span className="text-xs text-muted-foreground font-normal">{t.churn_watchlist_sub}</span>
                        </h3>
                        {churn_watch?.length > 0 ? (
                            <div className="space-y-1">
                                {churn_watch.map((c) => (
                                    <div key={c.merchant_id} className="flex items-center justify-between gap-3 px-3 py-2 rounded-md hover:bg-muted/30">
                                        <span className="truncate font-medium">{c.name}</span>
                                        <span className="text-xs text-muted-foreground tabular-nums">
                                            {c.days_idle != null ? `${c.days_idle}${t.sub_d_idle}` : t.sub_no_orders}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-xs text-muted-foreground py-8 text-center">{t.no_churn_risk}</div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {(bottlenecks?.length > 0 || suggestions?.length > 0) && (
                <div className="grid gap-3 grid-cols-1 lg:grid-cols-2">
                    {bottlenecks?.length > 0 && (
                        <Card>
                            <CardContent className="p-4">
                                <h3 className="text-sm font-semibold flex items-center gap-2 mb-3">
                                    <Timer className="h-4 w-4 text-amber-600" /> {t.bottlenecks}
                                </h3>
                                <div className="space-y-2">
                                    {bottlenecks.map((b, i) => (
                                        <div key={i} className="p-3 rounded-md border border-amber-200 bg-amber-50/30">
                                            <div className="text-xs text-muted-foreground">{b.kind}</div>
                                            <div className="font-medium">{b.title}</div>
                                            <div className="text-sm text-muted-foreground">{b.detail}</div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}
                    {suggestions?.length > 0 && (
                        <Card>
                            <CardContent className="p-4">
                                <h3 className="text-sm font-semibold flex items-center gap-2 mb-3">
                                    <Lightbulb className="h-4 w-4 text-amber-500" /> {t.suggested_improvements}
                                </h3>
                                <ul className="space-y-2">
                                    {suggestions.map((s, i) => (
                                        <li key={i} className="flex items-start gap-2 text-sm">
                                            <CheckCircle2 className="h-4 w-4 text-emerald-600 mt-0.5 shrink-0" />
                                            <span>{s}</span>
                                        </li>
                                    ))}
                                </ul>
                            </CardContent>
                        </Card>
                    )}
                </div>
            )}
        </div>
    );
}

/* ------------------------------- Branches view ------------------------------- */

function BranchesView({ hubs, t }) {
    if (!hubs) return null;
    const { kpi, ranking, trend } = hubs;
    // Repurpose LineSeries: delivered = revenue, assigned = profit
    const trendData = (trend ?? []).map((d) => ({ label: d.label, delivered: d.revenue, assigned: d.profit }));

    return (
        <div className="space-y-4">
            <SectionTitle title={t.sec_branch_kpi} />
            <div className="grid gap-3 grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                <KpiTile label={t.kpi_total_branches}   value={kpi.total_branches}  icon={Building2}  t={t} />
                <KpiTile label={t.kpi_active}           value={kpi.active_branches} icon={Building2} accent="good"  t={t} />
                <KpiTile label={t.kpi_orders}           value={kpi.orders}          icon={Package} accent="info"  t={t} />
                <KpiTile label={t.kpi_employees}        value={kpi.employees}       icon={Briefcase}  t={t} />
                <KpiTile label={t.kpi_vehicles}         value={kpi.vehicles}        icon={Car}  t={t} />

                <KpiTile label={t.kpi_revenue}          value={kpi.revenue}  fmt="money" icon={DollarSign} accent="good"  t={t} />
                <KpiTile label={t.kpi_expenses}         value={kpi.expenses} fmt="money" icon={Wallet} accent="warn"  t={t} />
                <KpiTile label={t.kpi_profit}           value={kpi.profit}   fmt="money" icon={TrendingUp}
                         accent={kpi.profit >= 0 ? 'good' : 'bad'} t={t}  />
                <KpiTile label={t.kpi_success_rate}     value={kpi.success_rate} fmt="pct" icon={CheckCircle2} accent="good"  t={t} />
                <KpiTile label={t.kpi_avg_processing}   value={kpi.avg_processing_hours != null ? `${kpi.avg_processing_hours} h` : '—'}
                         icon={Timer}  t={t} />

                <KpiTile label={t.kpi_sla_compliance}   value={kpi.sla_compliance}  fmt="pct" icon={ShieldCheck} accent="info" proxy  t={t} />
                <KpiTile label={t.kpi_satisfaction}     value={kpi.satisfaction}    fmt="pct" icon={Smile} accent="info" proxy  t={t} />
                <Card>
                    <CardContent className="p-4">
                        <div className="text-[11px] uppercase tracking-wider font-medium text-muted-foreground">{t.kpi_cohort_score}</div>
                        <div className="mt-2"><ScoreBadge score={kpi.cohort_score} band={kpi.cohort_band} size="md"  t={t} /></div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardContent className="p-4">
                    <div className="flex items-center justify-between mb-2">
                        <h3 className="text-sm font-semibold">{t.chart_perf_trend_monthly}</h3>
                        <p className="text-xs text-muted-foreground">{t.chart_revenue_profit_legend}</p>
                    </div>
                    <LineSeries data={trendData}  t={t} />
                </CardContent>
            </Card>

            <Card>
                <CardContent className="p-0">
                    <div className="flex items-center justify-between px-4 py-3 border-b border-border">
                        <h3 className="text-sm font-semibold flex items-center gap-2">
                            <Crown className="h-4 w-4 text-amber-500" /> {t.lb_branch_title}
                        </h3>
                        <span className="text-xs text-muted-foreground">{ranking.length} {t.lb_branches_in_win}</span>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/40">
                                <tr className="text-left text-[11px] uppercase tracking-wider text-muted-foreground">
                                    <th className="px-4 py-2 font-medium w-10">#</th>
                                    <th className="px-4 py-2 font-medium">{t.tbl_branch}</th>
                                    <th className="px-4 py-2 font-medium text-right">{t.tbl_orders}</th>
                                    <th className="px-4 py-2 font-medium text-right">{t.tbl_success}</th>
                                    <th className="px-4 py-2 font-medium text-right">{t.tbl_revenue}</th>
                                    <th className="px-4 py-2 font-medium text-right">{t.tbl_expense}</th>
                                    <th className="px-4 py-2 font-medium text-right">{t.tbl_profit}</th>
                                    <th className="px-4 py-2 font-medium text-right">{t.tbl_avg_h}</th>
                                    <th className="px-4 py-2 font-medium">{t.tbl_score}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {ranking.length === 0 && (
                                    <tr><td colSpan={9} className="px-4 py-10 text-center text-sm text-muted-foreground">{t.no_branch_activity}</td></tr>
                                )}
                                {ranking.map((r, i) => (
                                    <tr key={r.hub_id} className="hover:bg-muted/30">
                                        <td className="px-4 py-2 tabular-nums text-muted-foreground">{i + 1}</td>
                                        <td className="px-4 py-2 font-medium">{r.name}</td>
                                        <td className="px-4 py-2 text-right tabular-nums">{r.orders}</td>
                                        <td className="px-4 py-2 text-right tabular-nums">{r.success_rate != null ? `${(r.success_rate * 100).toFixed(1)}%` : '—'}</td>
                                        <td className="px-4 py-2 text-right tabular-nums">{r.revenue.toLocaleString(undefined, { maximumFractionDigits: 2 })}</td>
                                        <td className="px-4 py-2 text-right tabular-nums text-muted-foreground">{r.expense.toLocaleString(undefined, { maximumFractionDigits: 2 })}</td>
                                        <td className={`px-4 py-2 text-right tabular-nums ${r.profit >= 0 ? 'text-emerald-600' : 'text-rose-600'}`}>
                                            {r.profit.toLocaleString(undefined, { maximumFractionDigits: 2 })}
                                        </td>
                                        <td className="px-4 py-2 text-right tabular-nums">{r.avg_hours ?? '—'}</td>
                                        <td className="px-4 py-2"><ScoreBadge score={r.score} band={r.band}  t={t} /></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
