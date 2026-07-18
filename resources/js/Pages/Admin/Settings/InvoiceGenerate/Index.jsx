import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { FileText, Info, Zap, ExternalLink } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';

export default function Index({ urls = {}, t = {} }) {
    const [running, setRunning] = React.useState(false);

    const generate = () => {
        // The endpoint is a GET that runs artisan invoice:generate and
        // redirects back. Confirm before firing since it's not idempotent
        // by intent (creates invoice rows) even if it's safe to re-run.
        if (!window.confirm(t.generate + '?')) return;
        setRunning(true);
        router.get(urls.generate, {}, {
            preserveScroll: true,
            onFinish: () => setRunning(false),
        });
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.settings, t.title]}>
            <Head title={t.title} />

            <div className="grid gap-5 md:grid-cols-3">
                <div className="md:col-span-2 space-y-5">
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-start gap-3">
                                <div className="grid h-10 w-10 place-items-center rounded-lg bg-emerald-100 text-emerald-700 shrink-0">
                                    <FileText className="h-5 w-5" />
                                </div>
                                <div className="min-w-0">
                                    <h2 className="text-lg font-semibold m-0">{t.title}</h2>
                                    <p className="text-sm text-muted-foreground mt-1 mb-0">{t.description}</p>
                                </div>
                            </div>

                            <div className="mt-6 flex flex-wrap items-center gap-2">
                                <Button onClick={generate} disabled={running} className="bg-emerald-600 hover:bg-emerald-700">
                                    <Zap className="h-4 w-4 me-1" />
                                    {running ? '…' : t.generate}
                                </Button>
                                <a
                                    href={urls.invoices}
                                    className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40"
                                >
                                    {t.invoices} <ExternalLink className="h-3.5 w-3.5 ms-1" />
                                </a>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-5">
                    <Card>
                        <CardContent className="p-5">
                            <div className="flex items-center gap-2 mb-2">
                                <Info className="h-4 w-4 text-sky-600" />
                                <h3 className="text-sm font-semibold m-0">{t.note_title}</h3>
                            </div>
                            <p className="text-xs text-muted-foreground m-0 leading-relaxed">
                                {t.note_body}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
