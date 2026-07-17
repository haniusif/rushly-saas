import * as React from 'react';
import { Head } from '@inertiajs/react';
import { Package, Pencil } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';

/**
 * Read-only view of the modules included in a plan. Kept alive so
 * bookmarks/direct links to /super-admin/plan/modules/{id} still work,
 * but the primary surface for browsing modules is now the inline
 * popover on the Plan/Index page.
 */
export default function PlanModules({ plan, modules = [], urls = {}, t = {} }) {
    return (
        <AdminLayout title={t.title} breadcrumbs={[t.breadcrumb, t.plans, plan.name]}>
            <Head title={t.title} />
            <Card>
                <CardContent className="p-0">
                    <div className="flex items-center gap-3 px-6 py-5 border-b border-border">
                        <span className="shrink-0 grid h-9 w-9 place-items-center rounded-lg bg-primary/10 text-primary">
                            <Package className="h-4 w-4" />
                        </span>
                        <div className="flex-1 min-w-0">
                            <h2 className="text-base font-semibold m-0 truncate">{plan.name}</h2>
                            <p className="text-xs text-muted-foreground mt-0.5">{modules.length} {t.modules}</p>
                        </div>
                        <Button variant="outline" size="sm" asChild>
                            <a href={urls.edit}>
                                <Pencil className="h-3.5 w-3.5 me-1" />
                                {t.edit}
                            </a>
                        </Button>
                    </div>
                    <div className="p-6">
                        {modules.length === 0 ? (
                            <div className="text-sm text-muted-foreground text-center py-8">{t.no_modules}</div>
                        ) : (
                            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                                {modules.map((m) => (
                                    <div key={m.key} className="flex items-center gap-2 px-3 py-2 border border-border rounded-lg bg-background">
                                        <Package className="h-3.5 w-3.5 text-primary shrink-0" />
                                        <span className="text-sm truncate">{m.label}</span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </CardContent>
            </Card>
            <div className="mt-4 flex justify-end">
                <Button variant="outline" asChild>
                    <a href={urls.index}>{t.back}</a>
                </Button>
            </div>
        </AdminLayout>
    );
}
