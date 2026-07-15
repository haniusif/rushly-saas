import * as React from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
    User as UserIcon, MapPin, Mail, Phone, ImagePlus, Save, ArrowLeft,
    Lock, X as XIcon, UploadCloud,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Textarea } from '@/Components/ui/Textarea';
import { cn } from '@/lib/utils';

function Field({ label, required, error, hint, children, icon: Icon }) {
    return (
        <div className="space-y-1.5">
            <Label className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {Icon && <Icon className="h-3 w-3" />}
                {label}
                {required && <span className="text-rose-500">*</span>}
            </Label>
            {children}
            {hint && <p className="text-[11px] text-muted-foreground">{hint}</p>}
            {error && <p className="text-xs text-rose-600">{error}</p>}
        </div>
    );
}

/**
 * Drag-and-drop / click-to-pick photo tile with a live preview. Falls back
 * to the user's currently-saved image when nothing is picked.
 */
function PhotoPicker({ currentUrl, onPick, error, t = {} }) {
    const [file, setFile] = React.useState(null);
    const [preview, setPreview] = React.useState(null);
    const [dragging, setDragging] = React.useState(false);
    const inputRef = React.useRef(null);

    React.useEffect(() => () => { if (preview) URL.revokeObjectURL(preview); }, [preview]);

    const apply = (f) => {
        if (!f || !f.type?.startsWith('image/')) return;
        onPick(f);
        setFile(f);
        if (preview) URL.revokeObjectURL(preview);
        setPreview(URL.createObjectURL(f));
    };
    const clear = (e) => {
        e?.stopPropagation();
        onPick(null);
        setFile(null);
        if (preview) URL.revokeObjectURL(preview);
        setPreview(null);
        if (inputRef.current) inputRef.current.value = '';
    };

    const shown = preview || currentUrl;

    return (
        <div>
            <div
                onClick={() => inputRef.current?.click()}
                onDragOver={(e) => { e.preventDefault(); setDragging(true); }}
                onDragLeave={() => setDragging(false)}
                onDrop={(e) => {
                    e.preventDefault(); setDragging(false);
                    apply(e.dataTransfer.files?.[0] || null);
                }}
                className={cn(
                    'relative flex items-center gap-4 rounded-xl border-2 border-dashed p-4 cursor-pointer transition-colors',
                    dragging ? 'border-primary bg-primary/5' : 'border-border hover:bg-muted/30'
                )}
            >
                {shown ? (
                    <img src={shown} alt="" className="h-20 w-20 rounded-lg object-cover shrink-0 border border-border" />
                ) : (
                    <span className="inline-grid place-items-center h-20 w-20 rounded-lg bg-muted text-muted-foreground shrink-0">
                        <ImagePlus className="h-6 w-6" />
                    </span>
                )}
                <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                        <UploadCloud className="h-4 w-4 text-muted-foreground" />
                        <span className="text-sm font-medium">{file ? file.name : t.change_photo}</span>
                    </div>
                    <p className="text-[11px] text-muted-foreground mt-1">{t.image_hint}</p>
                </div>
                {file && (
                    <button
                        type="button"
                        onClick={clear}
                        className="absolute top-2 end-2 inline-grid place-items-center h-6 w-6 rounded-full bg-background/90 border border-border text-muted-foreground hover:text-rose-600 hover:border-rose-300"
                        aria-label={t.remove_photo}
                    >
                        <XIcon className="h-3.5 w-3.5" />
                    </button>
                )}
                <input
                    ref={inputRef}
                    type="file"
                    accept="image/*"
                    onChange={(e) => apply(e.target.files?.[0] || null)}
                    className="hidden"
                />
            </div>
            {error && <p className="text-xs text-rose-600 mt-1">{error}</p>}
        </div>
    );
}

export default function Edit({ user = {}, urls = {}, t = {} }) {
    const form = useForm({
        _method: 'put',
        name: user.name ?? '',
        address: user.address ?? '',
        image: null,
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(urls.submit, { forceFormData: true, preserveScroll: true });
    };

    return (
        <AdminLayout title={t.heading} breadcrumbs={[t.heading]}>
            <Head title={t.title} />

            <div className="max-w-3xl">
                <div className="mb-4 flex items-center justify-between gap-3">
                    <a
                        href={urls.cancel}
                        className="inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground transition-colors"
                    >
                        <ArrowLeft className="h-3.5 w-3.5" />
                        {t.cancel}
                    </a>
                    <a
                        href={urls.change_password}
                        className="inline-flex h-8 items-center gap-1.5 rounded-lg border border-input bg-background px-3 text-xs font-medium hover:bg-accent transition-colors"
                    >
                        <Lock className="h-3.5 w-3.5" />
                        {t.change_password}
                    </a>
                </div>

                <Card className="rounded-xl shadow-sm border border-border">
                    <CardContent className="p-6 space-y-5">
                        <div className="flex items-start gap-3">
                            <span className="inline-grid place-items-center h-10 w-10 rounded-lg bg-primary/10 text-primary shrink-0">
                                <UserIcon className="h-5 w-5" />
                            </span>
                            <div>
                                <h2 className="text-lg font-semibold">{t.heading}</h2>
                                <p className="text-sm text-muted-foreground mt-0.5">{t.intro}</p>
                            </div>
                        </div>

                        <form onSubmit={submit} className="space-y-5" encType="multipart/form-data">
                            <div className="grid gap-4 md:grid-cols-2">
                                <Field label={t.name} required error={form.errors.name} icon={UserIcon}>
                                    <Input
                                        value={form.data.name}
                                        onChange={(e) => form.setData('name', e.target.value)}
                                        autoFocus
                                    />
                                </Field>
                                <Field label={t.email} icon={Mail}>
                                    <Input value={user.email} readOnly disabled className="bg-muted/40" />
                                </Field>
                                <Field label={t.phone} icon={Phone}>
                                    <Input value={user.mobile} readOnly disabled className="bg-muted/40" />
                                </Field>
                                <div />
                                <Field label={t.address} required error={form.errors.address} icon={MapPin} className="md:col-span-2">
                                    <Textarea
                                        rows={2}
                                        value={form.data.address}
                                        onChange={(e) => form.setData('address', e.target.value)}
                                    />
                                </Field>
                            </div>

                            <div>
                                <Label className="mb-2 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                    <ImagePlus className="h-3 w-3" />
                                    {t.image}
                                </Label>
                                <PhotoPicker
                                    currentUrl={user.image}
                                    onPick={(f) => form.setData('image', f)}
                                    error={form.errors.image}
                                    t={t}
                                />
                            </div>

                            <div className="flex items-center justify-end gap-2 pt-4 border-t border-border">
                                <a
                                    href={urls.cancel}
                                    className="inline-flex h-10 items-center rounded-lg border border-input bg-background px-4 text-sm font-medium hover:bg-accent transition-colors"
                                >
                                    {t.cancel}
                                </a>
                                <Button type="submit" disabled={form.processing}>
                                    <Save className="h-4 w-4 me-1.5" />
                                    {form.processing ? '…' : t.save}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
