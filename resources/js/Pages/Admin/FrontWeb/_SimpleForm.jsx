import * as React from 'react';
import { Head } from '@inertiajs/react';
import { Save, AlertCircle, UploadCloud, X as XIcon } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';
import { Textarea } from '@/Components/ui/Textarea';
import { cn } from '@/lib/utils';

export function Field({ label, required, error, hint, children, className }) {
    return (
        <div className={cn('space-y-1.5', className)}>
            <Label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {label} {required && <span className="text-destructive">*</span>}
            </Label>
            {children}
            {hint && <p className="text-[11px] text-muted-foreground">{hint}</p>}
            {error && (
                <p className="text-xs text-destructive flex items-center gap-1">
                    <AlertCircle className="h-3 w-3" /> {error}
                </p>
            )}
        </div>
    );
}

/**
 * Compact image picker used by front-web forms. Renders the currently-saved
 * image (if any) or a preview of the just-picked File via ObjectURL. Emits
 * the raw File up to the caller — no direct upload here.
 */
export function ImageTile({ currentUrl, file, onPick, className }) {
    const inputRef = React.useRef(null);
    const [preview, setPreview] = React.useState(null);
    React.useEffect(() => {
        if (!file) { setPreview(null); return; }
        const u = URL.createObjectURL(file);
        setPreview(u);
        return () => URL.revokeObjectURL(u);
    }, [file]);
    const shown = preview || currentUrl;
    return (
        <div className={cn('flex items-center gap-3 rounded-md border border-dashed border-border p-2', preview && 'border-primary/50', className)}>
            <button
                type="button"
                onClick={() => inputRef.current?.click()}
                className="shrink-0 grid place-items-center h-14 w-14 bg-muted text-muted-foreground overflow-hidden rounded-md"
            >
                {shown ? <img src={shown} alt="" className="h-full w-full object-cover" /> : <UploadCloud className="h-5 w-5" />}
            </button>
            <div className="flex-1 min-w-0">
                <button type="button" onClick={() => inputRef.current?.click()}
                        className="text-sm font-medium underline decoration-dotted underline-offset-4 hover:text-primary">
                    {preview ? 'Change file' : (currentUrl ? 'Replace' : 'Choose file')}
                </button>
                <div className="text-[11px] text-muted-foreground truncate">
                    {preview ? 'Ready to upload on save.' : (currentUrl ? 'Current image shown.' : 'No file selected.')}
                </div>
            </div>
            {preview && (
                <button type="button" onClick={() => onPick(null)} className="p-1 rounded hover:bg-muted text-muted-foreground">
                    <XIcon className="h-4 w-4" />
                </button>
            )}
            <input ref={inputRef} type="file" accept="image/*" className="hidden"
                   onChange={(e) => onPick(e.target.files?.[0] || null)} />
        </div>
    );
}

/**
 * Card + submit/cancel scaffold used by every front-web create/edit page.
 * Children render the field grid; this wraps them in an AdminLayout with a
 * consistent header, footer bar, and h1-less body (layout title is the h1).
 */
export default function SimpleForm({
    title, breadcrumbs, sectionLabel, sectionHint, cancelHref,
    saveLabel, cancelLabel, onSubmit, processing, encType = 'multipart/form-data',
    children,
}) {
    return (
        <AdminLayout title={title} breadcrumbs={breadcrumbs}>
            <Head title={title} />
            <form onSubmit={onSubmit} encType={encType} className="space-y-4">
                <Card>
                    <CardContent className="p-0">
                        {(sectionLabel || sectionHint) && (
                            <div className="px-6 py-5 border-b border-border">
                                {sectionLabel && <h2 className="text-base font-semibold m-0">{sectionLabel}</h2>}
                                {sectionHint && <p className="text-xs text-muted-foreground mt-0.5">{sectionHint}</p>}
                            </div>
                        )}
                        <div className="p-6 grid gap-5 md:grid-cols-2">
                            {children}
                        </div>
                    </CardContent>
                </Card>
                <div className="flex items-center justify-end gap-2 bg-background border border-border rounded-xl px-6 py-4">
                    <Button variant="outline" asChild>
                        <a href={cancelHref}>{cancelLabel}</a>
                    </Button>
                    <Button type="submit" disabled={processing}>
                        <Save className="h-4 w-4 me-1" />
                        {saveLabel}
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}

// Re-export common form primitives so module Form.jsx files don't have to
// import Input/Select/Textarea from their own places.
export { Input, Select, Textarea };
