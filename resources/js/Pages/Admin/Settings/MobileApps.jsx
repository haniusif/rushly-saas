import * as React from 'react';
import { Head } from '@inertiajs/react';
import {
    Truck, Store, ShieldCheck, Eye, Warehouse, ArrowDownWideNarrow,
    Navigation, ScanLine, Smartphone,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';

/**
 * Read-only catalog of the Flutter apps that consume /api/v10/*.
 * Replaces backend/settings/mobile-apps.blade.php.
 *
 * Icon and colour live HERE, keyed by the app's `key`, rather than arriving as
 * class strings from the controller: Tailwind only emits classes it can find by
 * scanning the source, so a gradient assembled in PHP at runtime would produce
 * no CSS. Keeping the literals in the JSX is what makes them actually render.
 */
const STYLE = {
    driver:     { Icon: Truck,                ring: 'bg-emerald-500' },
    merchant:   { Icon: Store,                ring: 'bg-indigo-500' },
    admin:      { Icon: ShieldCheck,          ring: 'bg-rose-500' },
    supervisor: { Icon: Eye,                  ring: 'bg-sky-500' },
    warehouse:  { Icon: Warehouse,            ring: 'bg-amber-500' },
    sorting:    { Icon: ArrowDownWideNarrow,  ring: 'bg-violet-500' },
    fleet:      { Icon: Navigation,           ring: 'bg-cyan-500' },
    scanner:    { Icon: ScanLine,             ring: 'bg-blue-500' },
};

const FALLBACK = { Icon: Smartphone, ring: 'bg-muted-foreground' };

export default function MobileApps({ apps = [], t = {} }) {
    return (
        <AdminLayout title={t.title} breadcrumbs={[t.settings, t.title]}>
            <Head title={t.title} />

            <div className="mb-4">
                <h1 className="text-lg font-semibold">{t.title}</h1>
                {t.subtitle && <p className="text-sm text-muted-foreground">{t.subtitle}</p>}
            </div>

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                {apps.map((app) => {
                    const { Icon, ring } = STYLE[app.key] || FALLBACK;
                    return (
                        <Card key={app.key} className="overflow-hidden">
                            <CardContent className="p-5">
                                <div className="flex items-start gap-3">
                                    <div className={`grid h-10 w-10 shrink-0 place-items-center rounded-lg text-white ${ring}`}>
                                        <Icon className="h-5 w-5" />
                                    </div>
                                    <div className="min-w-0">
                                        <div className="font-semibold leading-tight">{app.title}</div>
                                        <div className="mt-0.5 text-xs text-muted-foreground">{app.audience}</div>
                                    </div>
                                </div>

                                {app.description && (
                                    <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                                        {app.description}
                                    </p>
                                )}

                                <div className="mt-4 border-t border-border pt-3">
                                    <div className="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                                        {t.repository}
                                    </div>
                                    <code className="mt-1 inline-block rounded bg-muted px-2 py-0.5 text-xs">
                                        {app.repo}
                                    </code>
                                </div>
                            </CardContent>
                        </Card>
                    );
                })}
            </div>

            {t.footer_note && (
                <p className="mt-5 text-center text-xs text-muted-foreground">{t.footer_note}</p>
            )}
        </AdminLayout>
    );
}
