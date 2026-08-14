import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { AlertOctagon, RefreshCw, Trash2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';

function fmt(iso) { if (!iso) return '—'; try { return new Date(iso).toISOString().replace('T',' ').slice(0,19); } catch { return iso; } }

export default function Index({ rows = [], filters = {}, queues = [], urls = {}, t = {} }) {
    const [queue, setQueue] = React.useState(filters.queue || '');
    const [cls, setCls]     = React.useState(filters.class_like || '');

    const apply = () => router.get(urls.index, { queue, class_like: cls }, { preserveState: true, preserveScroll: true });

    const retry = (id) => {
        if (!confirm('Re-queue this job? Same payload will be dispatched.')) return;
        router.post(`/admin/ops/failed-jobs/${id}/retry`, {}, { preserveScroll: true });
    };
    const forget = (id) => {
        if (!confirm('Delete this failed-job row without retrying? The job will NOT run again.')) return;
        router.delete(`/admin/ops/failed-jobs/${id}`, { preserveScroll: true });
    };

    return (
        <AdminLayout title={t.page_title}>
            <Head title={t.page_title} />

            <Card className="mb-4">
                <CardContent className="p-5 flex items-start gap-3">
                    <AlertOctagon className="h-5 w-5 text-rose-600 mt-0.5" />
                    <div>
                        <h2 className="text-lg font-semibold">{t.page_title}</h2>
                        <p className="text-sm text-muted-foreground">{t.subtitle}</p>
                    </div>
                </CardContent>
            </Card>

            <Card className="mb-4">
                <CardContent className="p-4">
                    <div className="grid gap-3 md:grid-cols-3">
                        <div>
                            <label className="block text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1">Queue</label>
                            <select className="w-full h-9 rounded-md border border-input bg-background px-3 text-sm" value={queue} onChange={(e) => setQueue(e.target.value)}>
                                <option value="">All</option>
                                {queues.map((q) => <option key={q} value={q}>{q}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="block text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1">Class contains</label>
                            <Input value={cls} onChange={(e) => setCls(e.target.value)} placeholder="PushStockJob" />
                        </div>
                        <div className="flex items-end"><Button onClick={apply} className="w-full">Apply</Button></div>
                    </div>
                </CardContent>
            </Card>

            {rows.length === 0 ? (
                <Card><CardContent className="p-8 text-center text-sm text-muted-foreground">{t.no_rows}</CardContent></Card>
            ) : (
                <Card>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/40 text-[11px] uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th className="px-3 py-2 text-left">ID</th>
                                        <th className="px-3 py-2 text-left">Queue</th>
                                        <th className="px-3 py-2 text-left">Job</th>
                                        <th className="px-3 py-2 text-left">Exception</th>
                                        <th className="px-3 py-2 text-left">Failed at</th>
                                        <th className="px-3 py-2 text-right"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map((r) => (
                                        <tr key={r.id} className="border-t border-border">
                                            <td className="px-3 py-2 font-mono text-xs">#{r.id}</td>
                                            <td className="px-3 py-2 text-xs">{r.queue}</td>
                                            <td className="px-3 py-2 font-mono text-xs">{r.job_class}</td>
                                            <td className="px-3 py-2 text-xs text-rose-600 max-w-[36ch] truncate" title={r.exception}>{r.exception}</td>
                                            <td className="px-3 py-2 font-mono text-xs">{fmt(r.failed_at)}</td>
                                            <td className="px-3 py-2 text-right">
                                                <div className="flex justify-end gap-1.5">
                                                    <Button onClick={() => retry(r.id)} className="h-7 px-2 text-xs">
                                                        <RefreshCw className="h-3.5 w-3.5 me-1" /> retry
                                                    </Button>
                                                    <Button onClick={() => forget(r.id)} className="h-7 px-2 text-xs bg-rose-600 hover:bg-rose-700 text-white">
                                                        <Trash2 className="h-3.5 w-3.5 me-1" /> forget
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            )}
        </AdminLayout>
    );
}
