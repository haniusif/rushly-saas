import * as React from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import {
    FileText, Save, Eye, Check, ChevronLeft, ChevronRight, Ruler,
    Store, RotateCcw,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Select } from '@/Components/ui/Select';
import { cn } from '@/lib/utils';

function TemplateTile({ tpl, selected, onSelect, t }) {
    const [w, h] = tpl.format || [0, 0];
    return (
        <label
            className={cn(
                'group relative flex flex-col rounded-xl border bg-card p-4 cursor-pointer transition-all',
                selected
                    ? 'border-primary ring-2 ring-primary/25 shadow-sm'
                    : 'border-border hover:border-primary/40 hover:shadow-sm'
            )}
        >
            <input
                type="radio"
                name="default_label_template"
                value={tpl.value}
                checked={selected}
                onChange={() => onSelect(tpl.value)}
                className="sr-only"
            />

            {selected && (
                <span className="absolute top-3 end-3 inline-grid place-items-center h-5 w-5 rounded-full bg-primary text-primary-foreground">
                    <Check className="h-3 w-3" />
                </span>
            )}

            <div className="flex items-start gap-2.5">
                <span className="inline-grid place-items-center h-9 w-9 rounded-lg bg-primary/10 text-primary shrink-0">
                    <FileText className="h-4 w-4" />
                </span>
                <div className="flex-1 min-w-0">
                    <div className="text-sm font-semibold truncate">{tpl.label}</div>
                    <div className="mt-0.5 flex items-center gap-1 text-[11px] text-muted-foreground">
                        <Ruler className="h-3 w-3" />
                        {w}×{h} mm
                    </div>
                </div>
            </div>

            <p className="mt-3 text-xs text-muted-foreground leading-relaxed min-h-[3.5em]">
                {tpl.description}
            </p>

            <a
                href={tpl.preview_url}
                target="_blank"
                rel="noopener noreferrer"
                onClick={(e) => e.stopPropagation()}
                className="mt-3 inline-flex items-center gap-1.5 self-start rounded-md border border-input bg-background px-2.5 py-1 text-[11px] font-medium hover:bg-accent transition-colors"
            >
                <Eye className="h-3 w-3" />
                {t.preview}
            </a>
        </label>
    );
}

function MerchantRow({ m, templates, t }) {
    const form = useForm({
        _method: 'put',
        label_template: m.label_template || '',
    });
    const submit = (e) => {
        e.preventDefault();
        form.post(m.submit_url, { preserveScroll: true });
    };
    const isOverridden = !!form.data.label_template;
    return (
        <tr className="border-b border-border last:border-0 hover:bg-muted/30 transition-colors">
            <td className="px-4 py-3">
                <div className="flex items-center gap-2 min-w-0">
                    <span className="inline-grid place-items-center h-7 w-7 rounded-md bg-primary/10 text-primary shrink-0">
                        <Store className="h-3.5 w-3.5" />
                    </span>
                    <span className="text-sm font-medium truncate">{m.business_name}</span>
                </div>
            </td>
            <td className="px-4 py-3">
                <div className="flex items-center gap-2">
                    <Select
                        value={form.data.label_template}
                        onChange={(e) => form.setData('label_template', e.target.value)}
                        className="min-w-[180px]"
                    >
                        <option value="">— {t.use_default} —</option>
                        {templates.map((tpl) => (
                            <option key={tpl.value} value={tpl.value}>{tpl.label}</option>
                        ))}
                    </Select>
                    {isOverridden && (
                        <button
                            type="button"
                            onClick={() => { form.setData('label_template', ''); }}
                            title={t.use_default}
                            className="p-1 text-muted-foreground hover:text-foreground"
                        >
                            <RotateCcw className="h-3.5 w-3.5" />
                        </button>
                    )}
                </div>
            </td>
            <td className="px-4 py-3 text-end">
                <Button type="button" size="sm" onClick={submit} disabled={form.processing}>
                    <Save className="h-3.5 w-3.5 me-1" />
                    {t.save}
                </Button>
            </td>
        </tr>
    );
}

export default function Index({
    templates = [],
    current = '',
    merchants = [],
    pagination = {},
    urls = {},
    t = {},
}) {
    // Default-template picker
    const form = useForm({
        _method: 'put',
        default_label_template: current,
    });
    const currentTpl = templates.find((x) => x.value === current);

    const submit = (e) => {
        e.preventDefault();
        form.post(urls.update_default, { preserveScroll: true });
    };

    const goPage = (url) => { if (url) router.get(url, {}, { preserveScroll: true }); };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title]}>
            <Head title={t.title} />

            <div className="max-w-6xl space-y-6">
                {/* Header + save row */}
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div className="flex items-center gap-2 text-lg font-semibold">
                            <FileText className="h-5 w-5 text-primary" />
                            {t.title}
                        </div>
                        <p className="mt-1 text-sm text-muted-foreground max-w-2xl">{t.subtitle}</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <span className="inline-flex items-center gap-1.5 rounded-full bg-primary/10 text-primary px-3 py-1 text-xs font-semibold">
                            {t.current}: <span className="font-bold">{currentTpl?.label ?? current}</span>
                        </span>
                    </div>
                </div>

                {/* Template grid */}
                <Card className="rounded-xl shadow-sm border border-border">
                    <CardContent className="p-6">
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                            {templates.map((tpl) => (
                                <TemplateTile
                                    key={tpl.value}
                                    tpl={tpl}
                                    selected={form.data.default_label_template === tpl.value}
                                    onSelect={(v) => form.setData('default_label_template', v)}
                                    t={t}
                                />
                            ))}
                        </div>

                        <div className="mt-6 flex items-center justify-end gap-2 pt-4 border-t border-border">
                            <Button type="button" onClick={submit} disabled={form.processing}>
                                <Save className="h-4 w-4 me-1.5" />
                                {form.processing ? '…' : t.save_default}
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Merchant overrides */}
                <Card className="rounded-xl shadow-sm border border-border">
                    <CardContent className="p-0">
                        <div className="px-6 pt-6 pb-3">
                            <div className="text-sm font-semibold">{t.merchant_overrides}</div>
                            <p className="mt-1 text-xs text-muted-foreground max-w-2xl">{t.merchant_overrides_hint}</p>
                        </div>

                        {merchants.length === 0 ? (
                            <div className="px-6 pb-6 text-sm text-muted-foreground">{t.no_merchants}</div>
                        ) : (
                            <>
                                <div className="overflow-x-auto">
                                    <table className="w-full">
                                        <thead>
                                            <tr className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground border-y border-border bg-muted/20">
                                                <th className="px-4 py-2 text-start">{t.merchant}</th>
                                                <th className="px-4 py-2 text-start">{t.override}</th>
                                                <th className="px-4 py-2 text-end w-32">{t.actions}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {merchants.map((m) => (
                                                <MerchantRow key={m.id} m={m} templates={templates} t={t} />
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                {(pagination.last_page ?? 1) > 1 && (
                                    <div className="flex items-center justify-between gap-3 px-6 py-3 border-t border-border">
                                        <div className="text-xs text-muted-foreground">
                                            {t.showing_results
                                                ?.replace(':from', pagination.from ?? 0)
                                                .replace(':to', pagination.to ?? 0)
                                                .replace(':total', pagination.total ?? 0)}
                                        </div>
                                        <div className="flex items-center gap-1">
                                            <button
                                                type="button"
                                                onClick={() => goPage(pagination.prev_url)}
                                                disabled={!pagination.prev_url}
                                                className="inline-flex items-center h-8 rounded-md border border-input bg-background px-2.5 text-xs font-medium hover:bg-accent disabled:opacity-40 disabled:cursor-not-allowed"
                                            >
                                                <ChevronLeft className="h-3.5 w-3.5 me-1" /> {t.prev}
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => goPage(pagination.next_url)}
                                                disabled={!pagination.next_url}
                                                className="inline-flex items-center h-8 rounded-md border border-input bg-background px-2.5 text-xs font-medium hover:bg-accent disabled:opacity-40 disabled:cursor-not-allowed"
                                            >
                                                {t.next} <ChevronRight className="h-3.5 w-3.5 ms-1" />
                                            </button>
                                        </div>
                                    </div>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
