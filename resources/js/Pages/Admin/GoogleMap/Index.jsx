import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { MapPin, Save, AlertCircle, KeyRound, ExternalLink, CheckCircle2, ArrowLeft } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';

export default function Index({ map_key = '', urls = {}, t = {} }) {
    const form = useForm({ map_key, _method: 'put' });

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { preserveScroll: true });
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.breadcrumb_settings, t.title]}>
            <Head title={t.title} />

            <div className="mb-4">
                <a href={urls.integrations} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.back}
                </a>
            </div>

            <div className="grid gap-5 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <form onSubmit={onSubmit}>
                        <Card>
                            <CardContent className="p-6">
                                <div className="mb-5 flex items-center gap-2">
                                    <MapPin className="h-5 w-5 text-primary" />
                                    <h2 className="text-lg font-semibold">{t.title}</h2>
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                        <KeyRound className="h-3 w-3" /> {t.map_key} <span className="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        type="text"
                                        value={form.data.map_key}
                                        onChange={(e) => form.setData('map_key', e.target.value)}
                                        placeholder="AIzaSy..."
                                        className="font-mono text-sm"
                                        autoComplete="off"
                                    />
                                    <p className="text-[11px] text-muted-foreground">{t.map_help}</p>
                                    {form.errors.map_key && (
                                        <p className="text-xs text-destructive flex items-center gap-1">
                                            <AlertCircle className="h-3 w-3" /> {form.errors.map_key}
                                        </p>
                                    )}
                                </div>

                                <div className="mt-6 flex items-center gap-2 border-t border-border pt-4">
                                    <Button type="submit" disabled={form.processing}>
                                        <Save className="h-4 w-4 me-1" /> {t.save}
                                    </Button>
                                    <a
                                        href={urls.console}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40"
                                    >
                                        <ExternalLink className="h-4 w-4 me-1" /> {t.open_console}
                                    </a>
                                </div>
                            </CardContent>
                        </Card>
                    </form>
                </div>

                <aside>
                    <Card>
                        <CardContent className="p-5">
                            <div className="flex items-center gap-2 mb-3">
                                <CheckCircle2 className="h-4 w-4 text-primary" />
                                <h3 className="text-sm font-semibold">{t.enabled_apis}</h3>
                            </div>
                            <ul className="space-y-2 text-[12px]">
                                {t.apis_list.split(' · ').map((api) => (
                                    <li key={api} className="flex items-start gap-2">
                                        <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600 mt-0.5 shrink-0" />
                                        <span>{api}</span>
                                    </li>
                                ))}
                            </ul>
                            <p className="text-[11px] text-muted-foreground mt-3">{t.map_help}</p>
                        </CardContent>
                    </Card>
                </aside>
            </div>
        </AdminLayout>
    );
}
