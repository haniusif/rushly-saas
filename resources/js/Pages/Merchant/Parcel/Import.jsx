import * as React from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { ArrowLeft, Upload, Download, Check, AlertTriangle, FileSpreadsheet, Info } from 'lucide-react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Card, CardContent } from '@/Components/ui/Card';

function StepBadge({ active, n, label }) {
    return (
        <div className={`flex items-center gap-2 ${active ? 'text-foreground' : 'text-muted-foreground'}`}>
            <span className={`inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-semibold ${active ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'}`}>
                {n}
            </span>
            <span className="text-sm font-medium">{label}</span>
        </div>
    );
}

function UploadStep({ urls, t, errors }) {
    const form = useForm({ file: null });

    const submit = (e) => {
        e.preventDefault();
        form.post(urls.upload, { forceFormData: true });
    };

    const flashErrors = Object.values(errors || {}).flat();

    return (
        <Card>
            <CardContent className="p-6">
                <div className="flex items-start justify-between flex-wrap gap-4 mb-6">
                    <div>
                        <h2 className="text-lg font-semibold m-0">{t.parcel_import}</h2>
                        <p className="text-sm text-muted-foreground mt-1 mb-0">{t.note}</p>
                    </div>
                    <a
                        href={urls.sample}
                        download
                        className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium rounded-md bg-emerald-600 text-white hover:bg-emerald-700 no-underline"
                    >
                        <Download className="h-4 w-4" /> {t.sample}
                    </a>
                </div>

                <div className="rounded-md border border-border bg-muted/30 p-4 mb-6 flex items-start gap-3">
                    <Info className="h-4 w-4 text-sky-600 mt-0.5 shrink-0" />
                    <div className="text-sm text-foreground/80">{t.tip_01}</div>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="block text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1.5">
                            {t.choose_file}
                        </label>
                        <input
                            type="file"
                            accept=".xlsx,.xls,.csv"
                            onChange={(e) => form.setData('file', e.target.files?.[0] || null)}
                            className="block w-full text-sm file:me-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary file:text-primary-foreground hover:file:opacity-90 cursor-pointer"
                        />
                        {form.errors.file && (
                            <p className="text-xs text-destructive mt-1">{form.errors.file}</p>
                        )}
                    </div>

                    <div className="flex items-center gap-2">
                        <button
                            type="submit"
                            disabled={form.processing || !form.data.file}
                            className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:opacity-90 disabled:opacity-50"
                        >
                            <Upload className="h-4 w-4" /> {form.processing ? '…' : t.import}
                        </button>
                        <a
                            href={urls.parcel_index}
                            className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium rounded-md border border-input bg-background hover:bg-muted/40 no-underline"
                        >
                            {t.back}
                        </a>
                    </div>
                </form>

                {flashErrors.length > 0 && (
                    <div className="mt-6 rounded-md border border-destructive/30 bg-destructive/5 p-4">
                        <div className="flex items-center gap-2 text-destructive font-medium mb-2">
                            <AlertTriangle className="h-4 w-4" /> {t.validation_errors}
                        </div>
                        <ul className="text-sm text-foreground/80 space-y-1 list-disc list-inside m-0">
                            {flashErrors.map((err, i) => <li key={i}>{String(err)}</li>)}
                        </ul>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function PreviewStep({ headers, preview_rows, total_rows, preview_count, expected, urls, t }) {
    const [submitting, setSubmitting] = React.useState(false);

    const confirm = () => {
        if (submitting) return;
        setSubmitting(true);
        router.post(urls.confirm, {}, {
            onFinish: () => setSubmitting(false),
        });
    };

    return (
        <Card>
            <CardContent className="p-6">
                <div className="flex items-start justify-between flex-wrap gap-4 mb-4">
                    <div>
                        <h2 className="text-lg font-semibold m-0">{t.preview_title}</h2>
                        <p className="text-sm text-muted-foreground mt-1 mb-0">
                            {t.total_rows}: <span className="font-medium text-foreground">{Number(total_rows || 0).toLocaleString()}</span>
                            {total_rows > preview_count && (
                                <span> — {t.showing_first} {preview_count} {t.rows_only}</span>
                            )}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <a
                            href={urls.cancel}
                            className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium rounded-md border border-input bg-background hover:bg-muted/40 no-underline"
                        >
                            <ArrowLeft className="h-4 w-4" /> {t.back}
                        </a>
                        <button
                            type="button"
                            onClick={confirm}
                            disabled={submitting}
                            className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium rounded-md bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50"
                        >
                            <Check className="h-4 w-4" /> {submitting ? '…' : t.confirm_import}
                        </button>
                    </div>
                </div>

                {expected && expected.length > 0 && (
                    <div className="rounded-md border border-sky-200 bg-sky-50 text-sky-900 p-3 mb-4">
                        <div className="text-xs font-semibold mb-1">{t.expected_columns}</div>
                        <code className="text-xs">{expected.join(', ')}</code>
                    </div>
                )}

                <div className="overflow-x-auto rounded-md border border-border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                            <tr>
                                <th className="text-start font-medium px-3 py-2 w-12">#</th>
                                {headers.map((h, i) => (
                                    <th key={i} className="text-start font-medium px-3 py-2 whitespace-nowrap">{h}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {preview_rows.map((row, ri) => (
                                <tr key={ri} className="hover:bg-muted/20">
                                    <td className="px-3 py-2 tabular-nums text-muted-foreground">{ri + 1}</td>
                                    {row.map((cell, ci) => (
                                        <td key={ci} className="px-3 py-2 whitespace-nowrap">
                                            {cell == null || cell === '' ? <span className="text-muted-foreground">—</span> : String(cell)}
                                        </td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    );
}

export default function Import({
    step = 'upload',
    headers = [], preview_rows = [], total_rows = 0, preview_count = 0, expected = [],
    urls = {}, t = {}, errors = {},
}) {
    const isPreview = step === 'preview';

    return (
        <MerchantLayout title={t.title} breadcrumbs={[t.parcels, t.title]}>
            <Head title={t.title} />

            <div className="mb-5 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold mb-1 flex items-center gap-2">
                        <FileSpreadsheet className="h-6 w-6 text-primary" />
                        {t.title}
                    </h1>
                </div>
                <div className="flex items-center gap-4 text-xs">
                    <StepBadge active={!isPreview} n={1} label={t.import} />
                    <span className="text-muted-foreground">→</span>
                    <StepBadge active={isPreview} n={2} label={t.preview_title} />
                </div>
            </div>

            {isPreview ? (
                <PreviewStep
                    headers={headers}
                    preview_rows={preview_rows}
                    total_rows={total_rows}
                    preview_count={preview_count}
                    expected={expected}
                    urls={urls}
                    t={t}
                />
            ) : (
                <UploadStep urls={urls} t={t} errors={errors} />
            )}
        </MerchantLayout>
    );
}
