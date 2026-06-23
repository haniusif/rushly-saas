import * as React from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import {
    Save, ArrowLeft, AlertCircle, Bell, Users, UserSearch, ImagePlus, Type, FileText,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { Select } from '@/Components/ui/Select';
import { Textarea } from '@/Components/ui/Textarea';
import { cn } from '@/lib/utils';

function Field({ icon: Icon, label, required, error, hint, children, className }) {
    return (
        <div className={cn('space-y-1.5', className)}>
            <Label className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                {Icon && <Icon className="h-3 w-3" />}
                {label}
                {required && <span className="text-destructive">*</span>}
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

function csrfToken() {
    if (typeof document === 'undefined') return '';
    const el = document.querySelector('meta[name="csrf-token"]');
    return el?.content || '';
}

function useDebounced(value, delay = 300) {
    const [v, setV] = React.useState(value);
    React.useEffect(() => {
        const id = setTimeout(() => setV(value), delay);
        return () => clearTimeout(id);
    }, [value, delay]);
    return v;
}

function UserPicker({ urlSearch, roleId, value, onChange, t, error }) {
    const [query, setQuery] = React.useState('');
    const [results, setResults] = React.useState([]);
    const [loading, setLoading] = React.useState(false);
    const [open, setOpen] = React.useState(false);
    const [selected, setSelected] = React.useState(null);
    const debounced = useDebounced(query, 300);
    const wrapRef = React.useRef(null);

    React.useEffect(() => {
        // Close dropdown on outside click.
        const onDoc = (e) => {
            if (!wrapRef.current?.contains(e.target)) setOpen(false);
        };
        document.addEventListener('mousedown', onDoc);
        return () => document.removeEventListener('mousedown', onDoc);
    }, []);

    React.useEffect(() => {
        let alive = true;
        if (debounced.length === 0 && !open) return;
        setLoading(true);
        const fd = new FormData();
        fd.append('search', debounced);
        fd.append('userType', roleId || 'all');
        fd.append('_token', csrfToken());
        fetch(urlSearch, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        })
            .then((r) => r.ok ? r.json() : [])
            .then((data) => { if (alive) setResults(Array.isArray(data) ? data : []); })
            .catch(() => { if (alive) setResults([]); })
            .finally(() => { if (alive) setLoading(false); });
        return () => { alive = false; };
    }, [debounced, roleId, open, urlSearch]);

    // If the role changes away from a specific one, clear the picked user.
    React.useEffect(() => {
        if (roleId === 'all') {
            setSelected(null);
            onChange(null);
        }
    }, [roleId]);

    const pick = (u) => {
        setSelected(u);
        setOpen(false);
        setQuery('');
        onChange(u.id);
    };

    const disabled = !roleId || roleId === 'all';

    return (
        <Field icon={UserSearch} label={t.user} error={error} hint={t.user_optional_hint}>
            <div ref={wrapRef} className={cn('relative', disabled && 'opacity-60')}>
                {selected ? (
                    <div className="flex items-center justify-between gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm">
                        <span className="truncate">{selected.text}</span>
                        <button type="button" className="text-muted-foreground hover:text-foreground text-xs" onClick={() => { setSelected(null); onChange(null); }}>×</button>
                    </div>
                ) : (
                    <Input
                        type="text"
                        disabled={disabled}
                        value={query}
                        onChange={(e) => { setQuery(e.target.value); setOpen(true); }}
                        onFocus={() => !disabled && setOpen(true)}
                        placeholder={t.placeholder_user}
                    />
                )}
                {open && !selected && !disabled && (
                    <div className="absolute z-10 mt-1 w-full rounded-md border border-border bg-popover shadow-md max-h-56 overflow-auto">
                        {loading && (
                            <div className="px-3 py-2 text-xs text-muted-foreground">…</div>
                        )}
                        {!loading && results.length === 0 && (
                            <div className="px-3 py-2 text-xs text-muted-foreground">{t.no_rows || 'No results.'}</div>
                        )}
                        {!loading && results.map((u) => (
                            <button
                                key={u.id}
                                type="button"
                                className="w-full px-3 py-2 text-start text-sm hover:bg-muted/50 focus:bg-muted/50"
                                onMouseDown={(e) => { e.preventDefault(); pick(u); }}
                            >
                                {u.text}
                            </button>
                        ))}
                    </div>
                )}
            </div>
        </Field>
    );
}

export default function Create({ lookups = {}, urls = {}, t = {} }) {
    const [imagePreview, setImagePreview] = React.useState(null);
    const form = useForm({
        title: '',
        role_id: 'all',
        user_id: '',
        merchant_id: '',
        image: null,
        description: '',
    });

    const onImage = (e) => {
        const f = e.target.files?.[0] || null;
        form.setData('image', f);
        if (imagePreview) URL.revokeObjectURL(imagePreview);
        setImagePreview(f ? URL.createObjectURL(f) : null);
    };

    React.useEffect(() => () => {
        if (imagePreview) URL.revokeObjectURL(imagePreview);
    }, [imagePreview]);

    const onSubmit = (e) => {
        e.preventDefault();
        form.post(urls.store, { forceFormData: true, preserveScroll: true });
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.list_title, t.add]}>
            <Head title={t.title} />

            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <a
                    href={urls.cancel}
                    className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40 transition-colors"
                >
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.back || t.cancel}
                </a>
            </div>

            <form onSubmit={onSubmit} encType="multipart/form-data">
                <Card>
                    <CardContent className="p-6">
                        <div className="mb-5 flex items-center gap-2">
                            <Bell className="h-5 w-5 text-primary" />
                            <h2 className="text-lg font-semibold">{t.title}</h2>
                        </div>

                        <div className="grid gap-6 lg:grid-cols-2">
                            {/* Left column */}
                            <div className="space-y-4">
                                <Field icon={Type} label={t.title} required error={form.errors.title}>
                                    <Input
                                        type="text"
                                        value={form.data.title}
                                        onChange={(e) => form.setData('title', e.target.value)}
                                        placeholder={t.placeholder_title}
                                        autoComplete="off"
                                    />
                                </Field>

                                <Field icon={Users} label={t.role} required error={form.errors.role_id}>
                                    <Select
                                        value={form.data.role_id}
                                        onChange={(e) => { form.setData('role_id', e.target.value); form.setData('user_id', ''); }}
                                    >
                                        {(lookups.roles || []).map((r) => (
                                            <option key={r.value} value={r.value}>{r.label}</option>
                                        ))}
                                    </Select>
                                </Field>

                                <UserPicker
                                    urlSearch={urls.user_search}
                                    roleId={form.data.role_id}
                                    value={form.data.user_id}
                                    onChange={(id) => form.setData('user_id', id ?? '')}
                                    t={t}
                                    error={form.errors.user_id}
                                />

                                <Field icon={ImagePlus} label={t.image} error={form.errors.image} hint={t.file_help}>
                                    <Input
                                        type="file"
                                        accept="image/png"
                                        onChange={onImage}
                                    />
                                    {imagePreview && (
                                        <img
                                            src={imagePreview}
                                            alt=""
                                            className="mt-2 h-20 w-20 rounded-md border border-border object-cover"
                                        />
                                    )}
                                </Field>
                            </div>

                            {/* Right column — description */}
                            <Field icon={FileText} label={t.description} error={form.errors.description}>
                                <Textarea
                                    rows={14}
                                    value={form.data.description}
                                    onChange={(e) => form.setData('description', e.target.value)}
                                    placeholder={t.placeholder_desc}
                                />
                            </Field>
                        </div>

                        <div className="mt-6 flex items-center gap-2 border-t border-border pt-4">
                            <Button type="submit" disabled={form.processing}>
                                <Save className="h-4 w-4 me-1" /> {t.save}
                            </Button>
                            <a
                                href={urls.cancel}
                                className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-muted/40 transition-colors"
                            >
                                {t.cancel}
                            </a>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </AdminLayout>
    );
}
