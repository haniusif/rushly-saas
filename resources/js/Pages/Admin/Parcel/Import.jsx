import * as React from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import { Upload, Download, FileSpreadsheet, ArrowLeft, AlertTriangle, CheckCircle2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Label } from '@/Components/ui/Label';

export default function Import({
    categories = [],
    delivery_types = [],
    import_errors = [],
    urls = {},
    t = {},
}) {
    const { data, setData, post, processing, errors, reset } = useForm({ file: null });
    const fileRef = React.useRef(null);
    const [localError, setLocalError] = React.useState('');

    const onSubmit = (e) => {
        e.preventDefault();
        setLocalError('');
        if (!data.file) {
            setLocalError(t.file_required || 'Choose an Excel file before importing.');
            return;
        }
        post(urls.submit, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => { reset('file'); if (fileRef.current) fileRef.current.value = ''; },
        });
    };

    return (
        <AdminLayout title={t.import}>
            <Head title={`${t.title} · ${t.import}`} />

            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <Link href={urls.index} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.title}
                </Link>
                <a
                    href={urls.sample_file}
                    download
                    className="inline-flex h-9 items-center rounded-md bg-emerald-600 text-white px-3 text-sm font-medium hover:bg-emerald-700 transition-colors"
                >
                    <Download className="h-4 w-4 me-1" /> {t.sample}
                </a>
            </div>

            <div className="grid gap-5 lg:grid-cols-3">
                {/* Upload card — AdminLayout H1 already says "Import", so
                    the card leads straight into the file input without a
                    redundant subheading. Emerald-tinted glyph carries the
                    section identity. */}
                <Card className="lg:col-span-2">
                    <CardContent className="p-6 space-y-5">
                        <form onSubmit={onSubmit} className="space-y-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="file">{t.select_file}</Label>
                                <input
                                    ref={fileRef}
                                    id="file"
                                    type="file"
                                    accept=".xlsx,.xls,.csv"
                                    onChange={(e) => setData('file', e.target.files?.[0] || null)}
                                    className="block w-full text-sm file:me-3 file:rounded-md file:border-0 file:bg-primary file:text-primary-foreground file:px-4 file:py-2 file:font-medium hover:file:bg-primary/90 cursor-pointer border border-input rounded-md p-1.5"
                                />
                                {(localError || errors.file) && (
                                    <div className="text-xs text-rose-600 mt-1">{localError || errors.file}</div>
                                )}
                            </div>

                            <div className="flex justify-end pt-2 border-t border-border">
                                <Button type="submit" disabled={processing}>
                                    <Upload className="h-4 w-4 me-1" />
                                    {processing ? '…' : t.submit}
                                </Button>
                            </div>
                        </form>

                        {/* Reference info */}
                        <div className="space-y-4 pt-2 border-t border-border">
                            <p className="text-sm">{t.instructions}</p>
                            <ul className="space-y-2 text-sm">
                                <li className="flex items-start gap-2">
                                    <CheckCircle2 className="h-4 w-4 mt-0.5 text-emerald-600 shrink-0" />
                                    <span>{t.instruction_2}</span>
                                </li>
                                <li className="flex items-start gap-2">
                                    <CheckCircle2 className="h-4 w-4 mt-0.5 text-emerald-600 shrink-0" />
                                    <span>{t.instruction_3}</span>
                                </li>
                                <li className="flex items-start gap-2">
                                    <CheckCircle2 className="h-4 w-4 mt-0.5 text-emerald-600 shrink-0" />
                                    <span>
                                        <strong>{t.categories}:</strong>{' '}
                                        <span className="text-xs font-mono text-muted-foreground">
                                            {categories.map((c) => `${c.id}=${c.title}`).join(', ') || '—'}
                                        </span>
                                    </span>
                                </li>
                                <li className="flex items-start gap-2">
                                    <CheckCircle2 className="h-4 w-4 mt-0.5 text-emerald-600 shrink-0" />
                                    <span>
                                        <strong>{t.delivery_types}:</strong>{' '}
                                        <span className="text-xs font-mono text-muted-foreground">
                                            {delivery_types.map((d) => `${d.id}=${d.label}`).join(', ') || '—'}
                                        </span>
                                    </span>
                                </li>
                                <li className="flex items-start gap-2">
                                    <CheckCircle2 className="h-4 w-4 mt-0.5 text-emerald-600 shrink-0" />
                                    <span>
                                        <strong>{t.cash_collection} = 0.00</strong>, <strong>{t.selling_price} = 0.00</strong> {t.instruction_4}
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </CardContent>
                </Card>

                {/* Validation log */}
                <Card>
                    <CardContent className="p-6">
                        <div className="flex items-center gap-2 pb-3 border-b border-border">
                            <AlertTriangle className={`h-5 w-5 ${import_errors.length ? 'text-rose-600' : 'text-muted-foreground'}`} />
                            <h2 className="text-lg font-semibold">{t.validation_log}</h2>
                            {import_errors.length > 0 && (
                                <span className="ms-auto inline-flex items-center rounded-full bg-rose-100 text-rose-700 px-2 py-0.5 text-xs font-medium">
                                    {import_errors.length}
                                </span>
                            )}
                        </div>

                        {import_errors.length === 0 ? (
                            <p className="text-sm text-muted-foreground mt-4">{t.no_errors || 'No errors from the last import.'}</p>
                        ) : (
                            <div className="mt-4 space-y-3 max-h-[600px] overflow-y-auto pe-1">
                                {import_errors.map((entry) => (
                                    <div key={entry.row} className="rounded-md border border-rose-200 bg-rose-50 px-3 py-2">
                                        <div className="text-xs font-semibold text-rose-700 mb-1">
                                            {t.row_number} {entry.row}
                                        </div>
                                        <ul className="text-xs text-rose-700 space-y-0.5 ps-3 list-disc">
                                            {entry.errors.map((err, i) => <li key={i}>{err}</li>)}
                                        </ul>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
