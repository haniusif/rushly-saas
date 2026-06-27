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

const TABS = [
    { id: 'executive', label: 'Executive', icon: BarChart3 },
    { id: 'drivers',   label: 'Drivers',   icon: Truck },
    { id: 'customers', label: 'Customers', icon: Users },
    { id: 'branches',  label: 'Branches',  icon: Building2 },
    { id: 'companies', label: 'Companies', icon: Network },
    { id: 'insights',  label: 'AI Insights', icon: Sparkles },
];

export default function Index({
    filters: initialFilters,
    kpi: initialKpi,
    drivers: initialDrivers,
    customers: initialCustomers,
    hubs: initialHubs,
    companies: initialCompanies,
    insights: initialInsights,
    options, urls,
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
        <AdminLayout title="Performance" breadcrumbs={['Performance']}>
            <Head title="Performance Dashboard" />

            <div className="space-y-4">
                {/* Header */}
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">Performance Dashboard</h1>
                        <p className="text-xs text-muted-foreground mt-1">
                            Executive insights · Driver, Customer, Branch &amp; Operating-Company analytics.
                            <span className="ml-2">Last refresh: <span className="tabular-nums">{lastRefresh.toLocaleTimeString()}</span></span>
                        </p>
                    </div>
                </div>

                {/* Filters & controls */}
                <FilterBar
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
                    {TABS.map(({ id, label, icon: Icon }) => (
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

                {tab === 'executive' && <ExecutiveView kpi={kpi} />}
                {tab === 'drivers'   && <DriversView drivers={drivers} />}
                {tab === 'customers' && <CustomersView customers={customers} />}
                {tab === 'branches'  && <BranchesView hubs={hubs} />}
                {tab === 'companies' && <CompaniesView companies={companies} />}
                {tab === 'insights'  && <InsightsView insights={insights} />}
            </div>
        </AdminLayout>
    );
}

/* ------------------------------- Executive view ------------------------------- */

function ExecutiveView({ kpi }) {
    const { orders, financial, activity, service } = kpi;
    return (
        <div className="space-y-4">

            {/* Orders */}
            <SectionTitle title="Orders" />
            <div className="grid gap-3 grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                <KpiTile label="Total Orders" value={orders.total} icon={Package} accent="info"
                         trend={orders.growth_rate} sub={`vs prev ${orders.previous_total}`} />
                <KpiTile label="Completed" value={orders.completed} icon={CheckCircle2} accent="good"
                         sub={`${(orders.completion_rate * 100).toFixed(1)}% rate`} />
                <KpiTile label="Pending" value={orders.pending} icon={Clock} accent="warn" />
                <KpiTile label="Cancelled" value={orders.cancelled} icon={XCircle} accent="bad" />
                <KpiTile label="Growth Rate" value={orders.growth_rate} fmt="pct" icon={TrendingUp}
                         accent={orders.growth_rate >= 0 ? 'good' : 'bad'} />
            </div>

            {/* Financial */}
            <SectionTitle title="Financial" />
            <div className="grid gap-3 grid-cols-2 md:grid-cols-3 lg:grid-cols-3">
                <KpiTile label="Revenue" value={financial.revenue} fmt="money" icon={DollarSign} accent="good"
                         sub={financial.currency} />
                <KpiTile label="Expenses" value={financial.expenses} fmt="money" icon={Wallet} accent="warn" />
                <KpiTile label="Profit" value={financial.profit} fmt="money" icon={TrendingUp}
                         accent={financial.profit >= 0 ? 'good' : 'bad'} />
            </div>

            {/* Activity */}
            <SectionTitle title="Activity" />
            <div className="grid gap-3 grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                <KpiTile label="Active Drivers" value={activity.active_drivers} icon={Truck} accent="info"
                         sub={`of ${activity.total_drivers}`} />
                <KpiTile label="Active Customers" value={activity.active_customers} icon={Users} accent="info"
                         sub={`of ${activity.total_customers}`} />
                <KpiTile label="Active Companies" value={activity.active_companies} icon={Network} accent="info"
                         sub="3PL operating co." />
                <KpiTile label="Active Branches" value={activity.active_branches} icon={Building2} accent="info"
                         sub={`of ${activity.total_branches}`} />
                <KpiTile label="Avg. Delivery Time"
                         value={service.avg_delivery_hours != null ? `${service.avg_delivery_hours} h` : '—'}
                         icon={Timer} />
            </div>

            <div className="grid gap-3 grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                <KpiTile label="Avg. Distance"
                         value={service.avg_distance_km != null ? `${service.avg_distance_km} km` : '—'}
                         icon={Route}
                         sub="straight-line pickup → drop-off" />
            </div>

            {/* Service quality */}
            <SectionTitle title="Service quality" />
            <div className="grid gap-3 grid-cols-1 md:grid-cols-3">
                <Card>
                    <CardContent className="p-4 flex items-center gap-4">
                        <Donut value={service.sla_compliance ?? 0} label="SLA" />
                        <div className="min-w-0">
                            <div className="text-[11px] uppercase tracking-wider font-medium text-muted-foreground">SLA Compliance</div>
                            <div className="text-2xl font-semibold tabular-nums">
                                {service.sla_compliance != null ? `${(service.sla_compliance * 100).toFixed(1)}%` : '—'}
                            </div>
                            <div className="text-xs text-muted-foreground mt-1">
                                {service.abnormal_open} open abnormal
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-4 flex items-center gap-4">
                        <Donut value={service.on_time_rate ?? 0} label="On-time" color="#10b981" />
                        <div className="min-w-0">
                            <div className="flex items-center gap-2">
                                <div className="text-[11px] uppercase tracking-wider font-medium text-muted-foreground">On-Time Delivery</div>
                                {service.on_time_is_real
                                    ? <span className="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200" title={service.proxies?.on_time_rate}>real</span>
                                    : <span className="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-amber-50 text-amber-700 border border-amber-200" title={service.proxies?.on_time_rate}>proxy</span>}
                            </div>
                            <div className="text-2xl font-semibold tabular-nums">
                                {service.on_time_rate != null ? `${(service.on_time_rate * 100).toFixed(1)}%` : '—'}
                            </div>
                            <div className="text-xs text-muted-foreground mt-1">
                                {service.on_time_is_real
                                    ? 'delivery ≤ expected'
                                    : 'Δ vs delivery-type SLA hours'}
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-4 flex items-center gap-4">
                        <Donut value={service.satisfaction ?? 0} label="Sat" color="#6366f1" />
                        <div className="min-w-0">
                            <div className="flex items-center gap-2">
                                <div className="text-[11px] uppercase tracking-wider font-medium text-muted-foreground">Customer Satisfaction</div>
                                {service.satisfaction_is_real
                                    ? <span className="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200" title={service.proxies?.satisfaction}>real</span>
                                    : <span className="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-amber-50 text-amber-700 border border-amber-200" title={service.proxies?.satisfaction}>proxy</span>}
                            </div>
                            <div className="text-2xl font-semibold tabular-nums">
                                {service.satisfaction != null ? `${(service.satisfaction * 100).toFixed(1)}%` : '—'}
                            </div>
                            <div className="text-xs text-muted-foreground mt-1">
                                {service.satisfaction_is_real
                                    ? `${service.ratings_count} ratings`
                                    : `${service.support_tickets} tickets in range`}
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}

/* -------------------------------- Drivers view -------------------------------- */

function DriversView({ drivers }) {
    const { kpi, ranking, time_series, rating_distribution } = drivers;
    return (
        <div className="space-y-4">

            {/* Driver KPIs */}
            <SectionTitle title="Driver KPIs" />
            <div className="grid gap-3 grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                <KpiTile label="Total Drivers"        value={kpi.total_drivers}         icon={Truck} />
                <KpiTile label="Active"               value={kpi.active_drivers}        icon={Truck} accent="good" />
                <KpiTile label={kpi.online_is_real ? 'Online (now)' : 'Online (24h)'}
                         value={kpi.online_drivers} icon={Radio} accent="info"
                         proxy={!kpi.online_is_real} />
                <KpiTile label="Completed Deliveries" value={kpi.completed_deliveries}  icon={CheckCircle2} accent="good" />
                <KpiTile label="Cancelled"            value={kpi.cancelled_deliveries}  icon={XCircle} accent="bad" />

                <KpiTile label="Acceptance Rate"   value={kpi.acceptance_rate}  fmt="pct" icon={ShieldCheck} accent="good" />
                <KpiTile label="Rejection Rate"    value={kpi.rejection_rate}   fmt="pct" icon={XCircle} accent="warn" />
                <KpiTile label="Avg. Pickup Time"  value={kpi.avg_pickup_hours  != null ? `${kpi.avg_pickup_hours} h`  : '—'} icon={Timer} />
                <KpiTile label="Avg. Delivery Time"value={kpi.avg_delivery_hours!= null ? `${kpi.avg_delivery_hours} h`: '—'} icon={Timer} />
                <KpiTile label="Distance Covered"  value={kpi.distance_km != null ? `${kpi.distance_km} km` : '—'} icon={Route} accent="info" />
                <KpiTile label="Revenue / Driver"  value={kpi.revenue_per_driver} fmt="money" icon={DollarSign} accent="good" />

                <KpiTile label="Complaints" value={kpi.complaints} icon={AlertTriangle} accent="warn"
                         proxy={!kpi.complaints_is_real}
                         sub={kpi.complaints_is_real ? 'driver-linked tickets' : 'all tickets (no driver link)'} />
                <KpiTile label="Customer Rating"
                         value={kpi.customer_rating != null ? `${kpi.customer_rating} / 5` : '—'}
                         icon={Star}
                         accent={kpi.customer_rating != null ? (kpi.customer_rating >= 4.5 ? 'good' : kpi.customer_rating >= 3.5 ? 'info' : 'warn') : undefined}
                         sub={`${kpi.customer_rating_count ?? 0} ratings`} />
                <Card>
                    <CardContent className="p-4">
                        <div className="text-[11px] uppercase tracking-wider font-medium text-muted-foreground">Cohort Score</div>
                        <div className="mt-2">
                            <ScoreBadge score={kpi.cohort_score} band={kpi.cohort_band} size="md" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Chart row */}
            <div className="grid gap-3 grid-cols-1 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardContent className="p-4">
                        <div className="flex items-center justify-between mb-2">
                            <h3 className="text-sm font-semibold">Daily performance</h3>
                            <p className="text-xs text-muted-foreground">Delivered vs assigned (in range)</p>
                        </div>
                        <LineSeries data={time_series} />
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-center justify-between mb-2">
                            <h3 className="text-sm font-semibold">Rating distribution</h3>
                            <span className="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-amber-50 text-amber-700 border border-amber-200">proxy</span>
                        </div>
                        <BarSeries data={rating_distribution} />
                        <p className="text-[11px] text-muted-foreground mt-2">Bucketed by completion-rate band (≥0.95=5★, ≥0.85=4★, ≥0.70=3★, ≥0.50=2★).</p>
                    </CardContent>
                </Card>
            </div>

            {/* Driver leaderboard */}
            <Card>
                <CardContent className="p-0">
                    <div className="flex items-center justify-between px-4 py-3 border-b border-border">
                        <h3 className="text-sm font-semibold flex items-center gap-2">
                            <Crown className="h-4 w-4 text-amber-500" /> Driver leaderboard
                        </h3>
                        <span className="text-xs text-muted-foreground">{ranking.length} drivers in window</span>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/40">
                                <tr className="text-left text-[11px] uppercase tracking-wider text-muted-foreground">
                                    <th className="px-4 py-2 font-medium w-10">#</th>
                                    <th className="px-4 py-2 font-medium">Driver</th>
                                    <th className="px-4 py-2 font-medium text-right">Delivered</th>
                                    <th className="px-4 py-2 font-medium text-right">Handled</th>
                                    <th className="px-4 py-2 font-medium text-right">Completion</th>
                                    <th className="px-4 py-2 font-medium text-right">On-time</th>
                                    <th className="px-4 py-2 font-medium text-right">Rating</th>
                                    <th className="px-4 py-2 font-medium text-right">Revenue</th>
                                    <th className="px-4 py-2 font-medium">Score</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {ranking.length === 0 && (
                                    <tr><td colSpan={9} className="px-4 py-10 text-center text-sm text-muted-foreground">No driver activity in this range.</td></tr>
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
                                        <td className="px-4 py-2"><ScoreBadge score={row.score} band={row.band} /></td>
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

function CustomersView({ customers }) {
    if (!customers) return null;
    const { kpi, top, segments, growth, churn } = customers;
    const growthSeries = (growth ?? []).map((d) => ({
        label: d.label, delivered: d.active, assigned: d.new,
    }));

    return (
        <div className="space-y-4">
            <SectionTitle title="Customer KPIs" />
            <div className="grid gap-3 grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                <KpiTile label="Total Customers"     value={kpi.total_customers}      icon={Users} />
                <KpiTile label="Active"              value={kpi.active_customers}     icon={Users} accent="good" />
                <KpiTile label="New (in range)"      value={kpi.new_customers}        icon={UserPlus} accent="info" />
                <KpiTile label="Returning"           value={kpi.returning_customers}  icon={Repeat} accent="info" />
                <KpiTile label="Lost / Churn"        value={kpi.lost_customers}       icon={UserMinus} accent="bad" />

                <KpiTile label="Avg Lifetime Value"  value={kpi.lifetime_value}       fmt="money" icon={DollarSign} accent="good" proxy />
                <KpiTile label="Avg Order Value"     value={kpi.avg_order_value}      fmt="money" icon={DollarSign} />
                <KpiTile label="Total Spending"      value={kpi.total_spending}       fmt="money" icon={Wallet} />
                <KpiTile label="Order Frequency"     value={`${kpi.order_frequency} /d`} icon={Gauge} sub="orders / customer / day" />
                <KpiTile label="Cancellation Rate"   value={kpi.cancellation_rate}    fmt="pct" icon={XCircle} accent="warn" />

                <KpiTile label="Retention Rate"      value={kpi.retention_rate}       fmt="pct" icon={RotateCcw} accent="info" proxy />
                <KpiTile label="Satisfaction"        value={kpi.satisfaction}         fmt="pct" icon={Smile} accent="info" proxy />
                <Card>
                    <CardContent className="p-4">
                        <div className="text-[11px] uppercase tracking-wider font-medium text-muted-foreground">Cohort Score</div>
                        <div className="mt-2"><ScoreBadge score={kpi.cohort_score} band={kpi.cohort_band} size="md" /></div>
                    </CardContent>
                </Card>
            </div>

            <div className="grid gap-3 grid-cols-1 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardContent className="p-4">
                        <div className="flex items-center justify-between mb-2">
                            <h3 className="text-sm font-semibold">Customer growth</h3>
                            <p className="text-xs text-muted-foreground">Active (delivered) vs new signups (assigned)</p>
                        </div>
                        <LineSeries data={growthSeries} />
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-center justify-between mb-2">
                            <h3 className="text-sm font-semibold">Customer segments</h3>
                            <p className="text-xs text-muted-foreground">by delivered spend</p>
                        </div>
                        <BarSeries data={segments} />
                    </CardContent>
                </Card>
            </div>

            <div className="grid gap-3 grid-cols-1 lg:grid-cols-3">
                <Card>
                    <CardContent className="p-4 flex items-center gap-4">
                        <Donut value={churn?.churn_rate ?? 0} label="Churn" color="#f43f5e" />
                        <div className="min-w-0">
                            <div className="text-[11px] uppercase tracking-wider font-medium text-muted-foreground">Churn</div>
                            <div className="text-2xl font-semibold tabular-nums">
                                {churn?.churn_rate != null ? `${(churn.churn_rate * 100).toFixed(1)}%` : '—'}
                            </div>
                            <div className="text-xs text-muted-foreground mt-1">
                                {churn?.churned} of {churn?.prior_customers} prior customers
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card className="lg:col-span-2">
                    <CardContent className="p-4">
                        <h3 className="text-sm font-semibold mb-3 flex items-center gap-2">
                            <Crown className="h-4 w-4 text-amber-500" /> Top customers
                        </h3>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/40">
                                    <tr className="text-left text-[11px] uppercase tracking-wider text-muted-foreground">
                                        <th className="px-3 py-2 font-medium w-8">#</th>
                                        <th className="px-3 py-2 font-medium">Customer</th>
                                        <th className="px-3 py-2 font-medium text-right">Orders</th>
                                        <th className="px-3 py-2 font-medium text-right">Revenue</th>
                                        <th className="px-3 py-2 font-medium text-right">AOV</th>
                                        <th className="px-3 py-2 font-medium">Score</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {top.length === 0 && (
                                        <tr><td colSpan={6} className="px-3 py-8 text-center text-sm text-muted-foreground">No customer activity in this range.</td></tr>
                                    )}
                                    {top.map((row, i) => (
                                        <tr key={row.merchant_id} className="hover:bg-muted/30">
                                            <td className="px-3 py-2 tabular-nums text-muted-foreground">{i + 1}</td>
                                            <td className="px-3 py-2 font-medium">{row.name}</td>
                                            <td className="px-3 py-2 text-right tabular-nums">{row.orders}</td>
                                            <td className="px-3 py-2 text-right tabular-nums">{row.revenue.toLocaleString(undefined, { maximumFractionDigits: 2 })}</td>
                                            <td className="px-3 py-2 text-right tabular-nums">{row.aov.toLocaleString(undefined, { maximumFractionDigits: 2 })}</td>
                                            <td className="px-3 py-2"><ScoreBadge score={row.score} band={row.band} /></td>
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

function CompaniesView({ companies }) {
    if (!companies) return null;
    const { kpi, ranking, compare } = companies;
    const compareSeries = (compare ?? []).map((d) => ({ label: d.label, delivered: d.revenue, assigned: d.orders }));

    return (
        <div className="space-y-4">
            <SectionTitle title="Operating-company KPIs (3PL)" />
            <div className="grid gap-3 grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                <KpiTile label="Total Companies"   value={kpi.total_companies}  icon={Network} />
                <KpiTile label="Active"            value={kpi.active_companies} icon={Network} accent="good" />
                <KpiTile label="Fleet Size"        value={kpi.fleet_size}       icon={Truck} accent="info" />
                <KpiTile label="Handled"           value={kpi.handled}          icon={Package} />
                <KpiTile label="Completed"         value={kpi.completed}        icon={CheckCircle2} accent="good" />

                <KpiTile label="Revenue"           value={kpi.revenue}          fmt="money" icon={DollarSign} accent="good" />
                <KpiTile label="Expenses"          value={kpi.expenses}         fmt="money" icon={Wallet} accent="warn" />
                <KpiTile label="Profit"            value={kpi.profit}           fmt="money" icon={TrendingUp}
                         accent={kpi.profit >= 0 ? 'good' : 'bad'} />
                <KpiTile label="Avg Delivery"      value={kpi.avg_delivery_hours != null ? `${kpi.avg_delivery_hours} h` : '—'} icon={Timer} />
                <KpiTile label="Fleet Utilization" value={kpi.fleet_utilization} fmt="pct" icon={Gauge} accent="info" proxy />

                <KpiTile label="Success Rate"      value={kpi.success_rate}    fmt="pct" icon={CheckCircle2} accent="good" />
                <KpiTile label="SLA Compliance"    value={kpi.sla_compliance}  fmt="pct" icon={ShieldCheck} accent="info" proxy />
                <KpiTile label="Satisfaction"     value={kpi.satisfaction}    fmt="pct" icon={Smile} accent="info" proxy />
                <Card>
                    <CardContent className="p-4">
                        <div className="text-[11px] uppercase tracking-wider font-medium text-muted-foreground">Cohort Score</div>
                        <div className="mt-2"><ScoreBadge score={kpi.cohort_score} band={kpi.cohort_band} size="md" /></div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardContent className="p-4">
                    <div className="flex items-center justify-between mb-2">
                        <h3 className="text-sm font-semibold">Revenue vs orders (weekly)</h3>
                        <p className="text-xs text-muted-foreground">Revenue (green) · Orders (brand)</p>
                    </div>
                    <LineSeries data={compareSeries} />
                </CardContent>
            </Card>

            <Card>
                <CardContent className="p-0">
                    <div className="flex items-center justify-between px-4 py-3 border-b border-border">
                        <h3 className="text-sm font-semibold flex items-center gap-2">
                            <Crown className="h-4 w-4 text-amber-500" /> Operating-company leaderboard
                        </h3>
                        <span className="text-xs text-muted-foreground">{ranking.length} companies in window</span>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/40">
                                <tr className="text-left text-[11px] uppercase tracking-wider text-muted-foreground">
                                    <th className="px-4 py-2 font-medium w-10">#</th>
                                    <th className="px-4 py-2 font-medium">Company</th>
                                    <th className="px-4 py-2 font-medium text-right">Fleet</th>
                                    <th className="px-4 py-2 font-medium text-right">Handled</th>
                                    <th className="px-4 py-2 font-medium text-right">Success</th>
                                    <th className="px-4 py-2 font-medium text-right">Revenue</th>
                                    <th className="px-4 py-2 font-medium text-right">Profit</th>
                                    <th className="px-4 py-2 font-medium text-right">Avg. h</th>
                                    <th className="px-4 py-2 font-medium">Score</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {ranking.length === 0 && (
                                    <tr><td colSpan={9} className="px-4 py-10 text-center text-sm text-muted-foreground">No operating-company activity in this range.</td></tr>
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
                                        <td className="px-4 py-2"><ScoreBadge score={r.score} band={r.band} /></td>
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

function InsightsView({ insights }) {
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
                        <div className="mt-2"><ScoreBadge score={item.score} band={item.band} /></div>
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

            <SectionTitle title="Highlights" />
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
                    <SectionTitle title="Risks" />
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
                                                {r.level.toUpperCase()}
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
                                <Target className="h-4 w-4 text-primary" /> Revenue forecast
                            </h3>
                            {forecast?.confidence != null && (
                                <span className="text-[11px] text-muted-foreground">
                                    R² {forecast.confidence}
                                </span>
                            )}
                        </div>
                        {allWeeks.length >= 2
                            ? <LineSeries data={forecastSeries} />
                            : <div className="text-xs text-muted-foreground py-8 text-center">{forecast?.note ?? 'Not enough data to project.'}</div>}
                        {forecast?.note && allWeeks.length >= 2 && (
                            <div className="text-[11px] text-muted-foreground mt-2">{forecast.note}</div>
                        )}
                        <div className="flex items-center gap-3 text-[11px] text-muted-foreground mt-2">
                            <span className="inline-flex items-center gap-1.5">
                                <span className="h-2 w-2 rounded-full" style={{ background: '#10b981' }} /> History
                            </span>
                            <span className="inline-flex items-center gap-1.5">
                                <span className="h-2 w-2 rounded-full" style={{ background: '#a21f5c' }} /> Projected
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-4">
                        <h3 className="text-sm font-semibold flex items-center gap-2 mb-3">
                            <UserMinus className="h-4 w-4 text-rose-500" /> Churn watchlist
                            <span className="text-xs text-muted-foreground font-normal">(no orders in last 30 days)</span>
                        </h3>
                        {churn_watch?.length > 0 ? (
                            <div className="space-y-1">
                                {churn_watch.map((c) => (
                                    <div key={c.merchant_id} className="flex items-center justify-between gap-3 px-3 py-2 rounded-md hover:bg-muted/30">
                                        <span className="truncate font-medium">{c.name}</span>
                                        <span className="text-xs text-muted-foreground tabular-nums">
                                            {c.days_idle != null ? `${c.days_idle}d idle` : 'no orders'}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-xs text-muted-foreground py-8 text-center">No churn-risk customers.</div>
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
                                    <Timer className="h-4 w-4 text-amber-600" /> Bottlenecks
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
                                    <Lightbulb className="h-4 w-4 text-amber-500" /> Suggested improvements
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

function BranchesView({ hubs }) {
    if (!hubs) return null;
    const { kpi, ranking, trend } = hubs;
    // Repurpose LineSeries: delivered = revenue, assigned = profit
    const trendData = (trend ?? []).map((d) => ({ label: d.label, delivered: d.revenue, assigned: d.profit }));

    return (
        <div className="space-y-4">
            <SectionTitle title="Branch KPIs" />
            <div className="grid gap-3 grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                <KpiTile label="Total Branches"   value={kpi.total_branches}  icon={Building2} />
                <KpiTile label="Active"           value={kpi.active_branches} icon={Building2} accent="good" />
                <KpiTile label="Orders"           value={kpi.orders}          icon={Package} accent="info" />
                <KpiTile label="Employees"        value={kpi.employees}       icon={Briefcase} />
                <KpiTile label="Vehicles"         value={kpi.vehicles}        icon={Car} />

                <KpiTile label="Revenue"          value={kpi.revenue}  fmt="money" icon={DollarSign} accent="good" />
                <KpiTile label="Expenses"         value={kpi.expenses} fmt="money" icon={Wallet} accent="warn" />
                <KpiTile label="Profit"           value={kpi.profit}   fmt="money" icon={TrendingUp}
                         accent={kpi.profit >= 0 ? 'good' : 'bad'} />
                <KpiTile label="Success Rate"     value={kpi.success_rate} fmt="pct" icon={CheckCircle2} accent="good" />
                <KpiTile label="Avg Processing"   value={kpi.avg_processing_hours != null ? `${kpi.avg_processing_hours} h` : '—'}
                         icon={Timer} />

                <KpiTile label="SLA Compliance"   value={kpi.sla_compliance}  fmt="pct" icon={ShieldCheck} accent="info" proxy />
                <KpiTile label="Satisfaction"     value={kpi.satisfaction}    fmt="pct" icon={Smile} accent="info" proxy />
                <Card>
                    <CardContent className="p-4">
                        <div className="text-[11px] uppercase tracking-wider font-medium text-muted-foreground">Cohort Score</div>
                        <div className="mt-2"><ScoreBadge score={kpi.cohort_score} band={kpi.cohort_band} size="md" /></div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardContent className="p-4">
                    <div className="flex items-center justify-between mb-2">
                        <h3 className="text-sm font-semibold">Performance trend (monthly)</h3>
                        <p className="text-xs text-muted-foreground">Revenue (green) · Profit (brand)</p>
                    </div>
                    <LineSeries data={trendData} />
                </CardContent>
            </Card>

            <Card>
                <CardContent className="p-0">
                    <div className="flex items-center justify-between px-4 py-3 border-b border-border">
                        <h3 className="text-sm font-semibold flex items-center gap-2">
                            <Crown className="h-4 w-4 text-amber-500" /> Branch leaderboard
                        </h3>
                        <span className="text-xs text-muted-foreground">{ranking.length} branches in window</span>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/40">
                                <tr className="text-left text-[11px] uppercase tracking-wider text-muted-foreground">
                                    <th className="px-4 py-2 font-medium w-10">#</th>
                                    <th className="px-4 py-2 font-medium">Branch</th>
                                    <th className="px-4 py-2 font-medium text-right">Orders</th>
                                    <th className="px-4 py-2 font-medium text-right">Success</th>
                                    <th className="px-4 py-2 font-medium text-right">Revenue</th>
                                    <th className="px-4 py-2 font-medium text-right">Expense</th>
                                    <th className="px-4 py-2 font-medium text-right">Profit</th>
                                    <th className="px-4 py-2 font-medium text-right">Avg. h</th>
                                    <th className="px-4 py-2 font-medium">Score</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {ranking.length === 0 && (
                                    <tr><td colSpan={9} className="px-4 py-10 text-center text-sm text-muted-foreground">No branch activity in this range.</td></tr>
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
                                        <td className="px-4 py-2"><ScoreBadge score={r.score} band={r.band} /></td>
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
