import * as React from 'react';
import { useForm, router } from '@inertiajs/react';
import {
    ArrowLeft, Upload, Download, Check, AlertTriangle, FileSpreadsheet,
    X, CheckCircle2, Loader2, Filter, Save,
} from 'lucide-react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Card, CardContent } from '@/Components/ui/Card';

/** Bytes -> a size a person reads, not a number they decode. */
function humanSize(bytes) {
    if (!bytes && bytes !== 0) return '';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB';
    return (bytes / 1024 / 1024).toFixed(1) + ' MB';
}

/**
 * The importer strips the trailing star off every header before it reaches
 * here, so which columns are required travels as its own list.
 */
const makeIsRequired = (required) => {
    const set = new Set((required || []).map((r) => String(r).trim().toLowerCase()));
    return (header) => set.has(String(header || '').trim().toLowerCase());
};

function Steps({ step, t }) {
    const items = [
        { key: 'upload',  n: 1, label: t.choose_file },
        { key: 'preview', n: 2, label: t.preview_title },
    ];
    return (
        <ol className="flex items-center gap-3 m-0 p-0 list-none">
            {items.map((it, i) => {
                const done   = step === 'preview' && it.key === 'upload';
                const active = step === it.key;
                return (
                    <li key={it.key} className="flex items-center gap-3">
                        <span className="flex items-center gap-2">
                            <span
                                className={[
                                    'inline-flex h-6 w-6 items-center justify-center rounded-full text-[11px] font-bold',
                                    done   ? 'bg-emerald-600 text-white'
                                           : active ? 'bg-primary text-primary-foreground'
                                                    : 'bg-muted text-muted-foreground',
                                ].join(' ')}
                            >
                                {done ? <Check className="h-3.5 w-3.5" /> : it.n}
                            </span>
                            <span className={'text-xs font-medium ' + (active || done ? 'text-foreground' : 'text-muted-foreground')}>
                                {it.label}
                            </span>
                        </span>
                        {i < items.length - 1 && <span className="h-px w-8 bg-border" />}
                    </li>
                );
            })}
        </ol>
    );
}

/** One labelled figure. Used for the row/column counts on the preview step. */
function Stat({ value, label, tone = 'default' }) {
    return (
        <div className="rounded-md border border-border bg-card px-3.5 py-2.5">
            <div className={[
                'text-xl font-semibold tabular-nums leading-none',
                tone === 'good' ? 'text-emerald-600' : tone === 'bad' ? 'text-destructive' : 'text-foreground',
            ].join(' ')}>
                {value}
            </div>
            <div className="mt-1 text-[11px] uppercase tracking-wide text-muted-foreground">{label}</div>
        </div>
    );
}

function ErrorPanel({ errors, t }) {
    if (!errors.length) return null;
    return (
        <div className="mt-5 rounded-md border border-destructive/30 bg-destructive/5">
            <div className="flex items-center gap-2 border-b border-destructive/20 px-4 py-2.5 text-sm font-semibold text-destructive">
                <AlertTriangle className="h-4 w-4 shrink-0" />
                {errors.length} {t.errors_count}
            </div>
            {/* Capped height: a sheet with 400 bad rows must not bury the
                upload control under its own error list. */}
            <ul className="m-0 max-h-64 space-y-1 overflow-y-auto px-4 py-3 text-sm text-foreground/80">
                {errors.map((err, i) => (
                    <li key={i} className="list-disc list-inside">{String(err)}</li>
                ))}
            </ul>
            <div className="border-t border-destructive/20 px-4 py-2 text-xs text-muted-foreground">
                {t.fix_and_retry}
            </div>
        </div>
    );
}

function UploadStep({ urls, limits, t, errors }) {
    const form = useForm({ file: null });
    const inputRef = React.useRef(null);
    const [dragging, setDragging] = React.useState(false);

    const file = form.data.file;
    const flashErrors = Object.values(errors || {}).flat();

    const pick = (f) => {
        if (!f) return;
        form.setData('file', f);
        form.clearErrors();
    };

    const onDrop = (e) => {
        e.preventDefault();
        setDragging(false);
        pick(e.dataTransfer?.files?.[0]);
    };

    const submit = (e) => {
        e.preventDefault();
        if (!file || form.processing) return;
        form.post(urls.upload, { forceFormData: true });
    };

    return (
        <div className="grid gap-5 lg:grid-cols-3">
            <div className="lg:col-span-2">
                <Card>
                    <CardContent className="p-6">
                        <form onSubmit={submit}>
                            {/* The drop zone doubles as the file preview once
                                something is chosen - one place to look, rather
                                than a bare input plus a filename somewhere else. */}
                            {!file ? (
                                <div
                                    onDragOver={(e) => { e.preventDefault(); setDragging(true); }}
                                    onDragLeave={() => setDragging(false)}
                                    onDrop={onDrop}
                                    onClick={() => inputRef.current?.click()}
                                    role="button"
                                    tabIndex={0}
                                    onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') inputRef.current?.click(); }}
                                    className={[
                                        'flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed px-6 py-12 text-center transition-colors',
                                        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                                        dragging ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/50 hover:bg-muted/30',
                                    ].join(' ')}
                                >
                                    <Upload className="h-7 w-7 text-muted-foreground" />
                                    <div className="text-sm font-medium">{t.drop_here}</div>
                                    <div className="text-xs text-muted-foreground">
                                        {(limits.accepted || []).join(' · ')} — {t.req_size}: {limits.max_mb} MB
                                    </div>
                                </div>
                            ) : (
                                <div className="flex items-center gap-3 rounded-lg border border-emerald-300/60 bg-emerald-50 px-4 py-3 dark:bg-emerald-950/20">
                                    <FileSpreadsheet className="h-8 w-8 shrink-0 text-emerald-600" />
                                    <div className="min-w-0 flex-1">
                                        <div className="truncate text-sm font-medium">{file.name}</div>
                                        <div className="text-xs text-muted-foreground">
                                            {humanSize(file.size)} · {t.file_selected}
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => { form.setData('file', null); if (inputRef.current) inputRef.current.value = ''; }}
                                        className="grid h-8 w-8 place-items-center rounded-md text-muted-foreground hover:bg-muted"
                                        aria-label={t.remove_file}
                                    >
                                        <X className="h-4 w-4" />
                                    </button>
                                </div>
                            )}

                            <input
                                ref={inputRef}
                                type="file"
                                accept=".xlsx,.xls,.csv"
                                onChange={(e) => pick(e.target.files?.[0])}
                                className="hidden"
                            />

                            {form.errors.file && (
                                <p className="mt-2 text-xs text-destructive">{form.errors.file}</p>
                            )}

                            <div className="mt-5 flex flex-wrap items-center gap-2">
                                <button
                                    type="submit"
                                    disabled={form.processing || !file}
                                    className="inline-flex h-10 items-center gap-1.5 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:opacity-90 disabled:opacity-50"
                                >
                                    {form.processing
                                        ? <Loader2 className="h-4 w-4 animate-spin" />
                                        : <Upload className="h-4 w-4" />}
                                    {t.import}
                                </button>
                                <a
                                    href={urls.parcel_index}
                                    className="inline-flex h-10 items-center gap-1.5 rounded-md border border-input bg-background px-4 text-sm font-medium no-underline hover:bg-muted/40"
                                >
                                    {t.back}
                                </a>
                            </div>
                        </form>

                        <ErrorPanel errors={flashErrors} t={t} />
                    </CardContent>
                </Card>
            </div>

            {/* Everything the merchant needs to know before choosing a file,
                stated as facts rather than a paragraph of prose. */}
            <aside className="space-y-4">
                <Card>
                    <CardContent className="p-5">
                        <a
                            href={urls.sample}
                            download
                            className="inline-flex h-10 w-full items-center justify-center gap-1.5 rounded-md bg-emerald-600 px-4 text-sm font-medium text-white no-underline hover:bg-emerald-700"
                        >
                            <Download className="h-4 w-4" /> {t.sample}
                        </a>
                        <p className="mt-2 mb-0 text-xs text-muted-foreground">{t.note}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-5">
                        <div className="mb-3 text-sm font-semibold">{t.requirements}</div>
                        <dl className="m-0 space-y-2.5 text-sm">
                            <div className="flex items-baseline justify-between gap-3">
                                <dt className="text-muted-foreground">{t.req_format}</dt>
                                <dd className="m-0 font-mono text-xs">{(limits.accepted || []).join(' ')}</dd>
                            </div>
                            <div className="flex items-baseline justify-between gap-3">
                                <dt className="text-muted-foreground">{t.req_size}</dt>
                                <dd className="m-0 font-medium tabular-nums">{limits.max_mb} MB</dd>
                            </div>
                            <div className="flex items-baseline justify-between gap-3">
                                <dt className="text-muted-foreground">{t.req_rows}</dt>
                                <dd className="m-0 font-medium tabular-nums">{Number(limits.max_rows || 0).toLocaleString()}</dd>
                            </div>
                        </dl>
                        <p className="mt-3 mb-0 border-t border-border pt-3 text-xs text-muted-foreground">
                            {t.req_required_hint}
                        </p>
                    </CardContent>
                </Card>
            </aside>
        </div>
    );
}

function PreviewStep({ headers, required, rows, row_errors, error_count, total_rows, saved, urls, t }) {
    const isRequired = makeIsRequired(required);

    // The rows are edited here and posted back whole; the server re-validates
    // and returns fresh errors, so this state is never the source of truth
    // about what is valid - it only holds what the merchant typed.
    const [draft, setDraft]   = React.useState(() => rows.map((r) => ({ ...r })));
    const [dirty, setDirty]   = React.useState(false);
    const [busy, setBusy]     = React.useState(false);
    const [onlyBad, setOnlyBad] = React.useState(error_count > 0);

    // A save or a fresh upload replaces the rows underneath us.
    React.useEffect(() => {
        setDraft(rows.map((r) => ({ ...r })));
        setDirty(false);
        setOnlyBad(error_count > 0);
    }, [rows, error_count]);

    const errorsFor = (i) => row_errors?.[i] || row_errors?.[String(i)] || {};
    const rowHasError = (i) => Object.keys(errorsFor(i)).length > 0;

    const visible = React.useMemo(
        () => draft.map((r, i) => i).filter((i) => !onlyBad || rowHasError(i)),
        [draft, onlyBad, row_errors],
    );

    const setCell = (i, col, value) => {
        setDraft((d) => {
            const next = d.slice();
            next[i] = { ...next[i], [col]: value };
            return next;
        });
        setDirty(true);
    };

    const save = () => {
        if (busy) return;
        setBusy(true);
        router.post(urls.update, { rows: draft }, {
            preserveScroll: true,
            onFinish: () => setBusy(false),
        });
    };

    const confirm = () => {
        if (busy) return;
        setBusy(true);
        router.post(urls.confirm, {}, { onFinish: () => setBusy(false) });
    };

    const blocked = error_count > 0 || dirty;

    return (
        <Card>
            <CardContent className="p-0">
                <div className="sticky top-0 z-10 flex flex-wrap items-center justify-between gap-3 border-b border-border bg-background/95 px-5 py-3 backdrop-blur">
                    <div className="flex items-center gap-2 text-sm font-semibold">
                        {error_count > 0
                            ? <><AlertTriangle className="h-4 w-4 text-destructive" /> {error_count} {t.errors_count}</>
                            : <><CheckCircle2 className="h-4 w-4 text-emerald-600" /> {t.no_problems}</>}
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        {error_count > 0 && (
                            <button
                                type="button"
                                onClick={() => setOnlyBad((v) => !v)}
                                className="inline-flex h-9 items-center gap-1.5 rounded-md border border-input bg-background px-3 text-xs font-medium hover:bg-muted/40"
                            >
                                <Filter className="h-3.5 w-3.5" />
                                {onlyBad ? t.all_rows : t.only_problems}
                            </button>
                        )}
                        <a href={urls.cancel} className="inline-flex h-9 items-center gap-1.5 rounded-md border border-input bg-background px-3 text-sm font-medium no-underline hover:bg-muted/40">
                            <ArrowLeft className="h-4 w-4" /> {t.back}
                        </a>
                        <button
                            type="button"
                            onClick={save}
                            disabled={busy || !dirty}
                            className="inline-flex h-9 items-center gap-1.5 rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40 disabled:opacity-50"
                        >
                            {busy ? <Loader2 className="h-4 w-4 animate-spin" /> : <Save className="h-4 w-4" />}
                            {busy ? t.saving : t.save_changes}
                        </button>
                        <button
                            type="button"
                            onClick={confirm}
                            disabled={busy || blocked}
                            title={blocked ? (dirty ? t.unsaved : t.problems_remain) : undefined}
                            className="inline-flex h-9 items-center gap-1.5 rounded-md bg-emerald-600 px-4 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                        >
                            <Check className="h-4 w-4" /> {t.confirm_import}
                        </button>
                    </div>
                </div>

                <div className="p-5">
                    <div className="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <Stat value={Number(total_rows || 0).toLocaleString()} label={t.ready_to_import} tone={error_count ? 'default' : 'good'} />
                        <Stat value={Number(error_count || 0).toLocaleString()} label={t.errors_count} tone={error_count ? 'bad' : 'default'} />
                        <Stat value={headers.length} label={t.columns} />
                    </div>

                    {saved && !dirty && (
                        <div className="mb-3 flex items-center gap-2 rounded-md border border-emerald-300/50 bg-emerald-50 px-3 py-2 text-xs text-emerald-800 dark:bg-emerald-950/20 dark:text-emerald-200">
                            <CheckCircle2 className="h-3.5 w-3.5" /> {t.saved_ok}
                        </div>
                    )}
                    {dirty && (
                        <div className="mb-3 flex items-center gap-2 rounded-md border border-amber-300/60 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-950/20 dark:text-amber-200">
                            <AlertTriangle className="h-3.5 w-3.5" /> {t.unsaved}
                        </div>
                    )}

                    <p className="mb-3 text-xs text-muted-foreground">{t.edit_hint}</p>

                    <div className="overflow-x-auto rounded-md border border-border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                                <tr>
                                    <th className="w-14 px-3 py-2 text-start font-medium">{t.row}</th>
                                    {headers.map((h, i) => (
                                        <th key={i} className="whitespace-nowrap px-3 py-2 text-start font-medium">
                                            {h}
                                            {isRequired(h) && <span className="ms-1 text-destructive" title={t.required_col}>*</span>}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {visible.map((i) => {
                                    const errs = errorsFor(i);
                                    return (
                                        <tr key={i} className={rowHasError(i) ? 'bg-destructive/5' : undefined}>
                                            <td className="px-3 py-1.5 tabular-nums text-muted-foreground">{i + 2}</td>
                                            {headers.map((h) => {
                                                const msg = errs[h];
                                                return (
                                                    <td key={h} className="px-1.5 py-1.5 align-top">
                                                        <input
                                                            value={draft[i]?.[h] ?? ''}
                                                            onChange={(e) => setCell(i, h, e.target.value)}
                                                            aria-invalid={msg ? 'true' : undefined}
                                                            aria-label={h + ' ' + t.row + ' ' + (i + 2)}
                                                            className={[
                                                                'h-8 w-full min-w-[8rem] rounded-md border bg-background px-2 text-sm',
                                                                'focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
                                                                msg ? 'border-destructive' : 'border-transparent hover:border-input',
                                                            ].join(' ')}
                                                        />
                                                        {msg && <div className="mt-0.5 text-[10px] font-medium text-destructive">{msg}</div>}
                                                    </td>
                                                );
                                            })}
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>

                    {visible.length === 0 && (
                        <div className="rounded-md border border-dashed border-border p-6 text-center text-xs text-muted-foreground">
                            {t.no_problems}
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

export default function Import({
    step = 'upload',
    headers = [], required = [], rows = [], row_errors = {}, error_count = 0, total_rows = 0, saved = false,
    urls = {}, limits = {}, t = {}, errors = {},
}) {
    const isPreview = step === 'preview';

    return (
        <MerchantLayout title={t.title} breadcrumbs={[t.parcels, t.title]}>
            {/* No heading and no <Head> here on purpose: MerchantLayout already
                renders the breadcrumb trail AND an h1 from its title prop, so
                repeating it printed "Import shipments" three times down the
                page. The layout owns the title; this row owns the progress. */}
            <div className="mb-5 flex justify-end">
                <Steps step={isPreview ? 'preview' : 'upload'} t={t} />
            </div>

            {isPreview ? (
                <PreviewStep
                    headers={headers}
                    required={required}
                    rows={rows}
                    row_errors={row_errors}
                    error_count={error_count}
                    total_rows={total_rows}
                    saved={saved}
                    urls={urls}
                    t={t}
                />
            ) : (
                <UploadStep urls={urls} limits={limits} t={t} errors={errors} />
            )}
        </MerchantLayout>
    );
}
