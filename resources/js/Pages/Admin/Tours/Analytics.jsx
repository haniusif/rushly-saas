import * as React from 'react';
import { Head } from '@inertiajs/react';
import { ArrowLeft, TrendingUp, Users, XCircle, Clock } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';

function fmtDuration(ms) {
    if (!ms) return '—';
    if (ms < 1000) return `${ms} ms`;
    const s = Math.round(ms / 1000);
    if (s < 60) return `${s}s`;
    const m = Math.floor(s / 60);
    const r = s % 60;
    return `${m}m ${r}s`;
}

function ProgressBar({ value }) {
    return (
        <div className="h-1.5 w-full rounded-full bg-muted overflow-hidden">
            <div className="h-full bg-emerald-500 transition-all" style={{ width: `${Math.min(100, Math.max(0, value))}%` }} />
        </div>
    );
}

export default function Analytics({ rows = [], urls = {}, t = {} }) {
    const totals = rows.reduce((acc, r) => ({
        starts:    acc.starts + r.starts,
        completes: acc.completes + r.completes,
        skips:     acc.skips + r.skips,
    }), { starts: 0, completes: 0, skips: 0 });
    const overallRate = totals.starts > 0 ? (totals.completes / totals.starts) * 100 : 0;

    return (
        <AdminLayout title={t.analytics} breadcrumbs={[t.title_index, t.analytics]}>
            <Head title={t.analytics} />

            <div className="mb-4 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <a href={urls.index} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent no-underline">
                        <ArrowLeft className="h-4 w-4 me-1" /> {t.back}
                    </a>
                    <h1 className="text-xl font-semibold m-0">{t.analytics}</h1>
                </div>
            </div>

            <div className="mb-4 grid gap-3 grid-cols-2 md:grid-cols-4">
                <Kpi icon={Users}     label={t.starts}          value={totals.starts.toLocaleString()} tone="bg-primary/10 text-primary" />
                <Kpi icon={TrendingUp} label={t.completes}      value={totals.completes.toLocaleString()} tone="bg-emerald-50 text-emerald-700" />
                <Kpi icon={XCircle}   label={t.skips}           value={totals.skips.toLocaleString()} tone="bg-amber-50 text-amber-700" />
                <Kpi icon={TrendingUp} label={t.completion_rate} value={`${overallRate.toFixed(1)}%`} tone="bg-sky-50 text-sky-700" />
            </div>

            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/30 text-xs uppercase tracking-wide text-muted-foreground">
                                <tr>
                                    <th className="text-start font-medium px-4 py-2.5">Tour</th>
                                    <th className="text-start font-medium px-4 py-2.5">{t.module}</th>
                                    <th className="text-end   font-medium px-4 py-2.5">{t.starts}</th>
                                    <th className="text-end   font-medium px-4 py-2.5">{t.completes}</th>
                                    <th className="text-end   font-medium px-4 py-2.5">{t.skips}</th>
                                    <th className="text-start font-medium px-4 py-2.5 w-56">{t.completion_rate}</th>
                                    <th className="text-end   font-medium px-4 py-2.5">{t.dropoff}</th>
                                    <th className="text-end   font-medium px-4 py-2.5">{t.avg_step}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {rows.length === 0 && (
                                    <tr><td colSpan={8} className="px-4 py-10 text-center text-sm text-muted-foreground">No events recorded yet.</td></tr>
                                )}
                                {rows.map((r) => (
                                    <tr key={r.tour_key} className="hover:bg-muted/20">
                                        <td className="px-4 py-2.5 align-top">
                                            <div className="font-medium">{r.title}</div>
                                            <div className="text-[11px] text-muted-foreground font-mono">{r.tour_key}</div>
                                        </td>
                                        <td className="px-4 py-2.5 align-top text-xs">{r.module || '—'}</td>
                                        <td className="px-4 py-2.5 align-top text-end tabular-nums">{r.starts}</td>
                                        <td className="px-4 py-2.5 align-top text-end tabular-nums">{r.completes}</td>
                                        <td className="px-4 py-2.5 align-top text-end tabular-nums">{r.skips}</td>
                                        <td className="px-4 py-2.5 align-top">
                                            <div className="flex items-center gap-2">
                                                <span className="tabular-nums text-xs w-12 text-end">{r.completion_rate}%</span>
                                                <div className="flex-1"><ProgressBar value={r.completion_rate} /></div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-2.5 align-top text-end text-xs">
                                            {r.dropoff_step == null ? '—' : `Step ${r.dropoff_step + 1}`}
                                        </td>
                                        <td className="px-4 py-2.5 align-top text-end text-xs">
                                            <Clock className="h-3 w-3 inline me-1 opacity-60" />
                                            {fmtDuration(r.avg_step_ms)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}

function Kpi({ icon: Icon, label, value, tone }) {
    return (
        <Card>
            <CardContent className="p-4">
                <div className="flex items-center gap-3">
                    <span className={`grid h-10 w-10 place-items-center rounded-lg ${tone}`}>
                        <Icon className="h-5 w-5" />
                    </span>
                    <div>
                        <div className="text-[11px] uppercase tracking-wider font-medium text-muted-foreground">{label}</div>
                        <div className="text-xl font-semibold tabular-nums">{value}</div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
