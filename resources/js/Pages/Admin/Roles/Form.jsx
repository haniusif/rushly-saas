import * as React from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Save, Loader2, Search, ShieldCheck } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import { Label } from '@/Components/ui/Label';
import { cn } from '@/lib/utils';

/**
 * Create / edit a role. Replaces backend/role/{create,edit}.blade.php.
 *
 * The Blade picker looped raw `permissions` rows, and production has 188 rows
 * across only ~70 distinct modules, so the same module rendered many times over.
 * The controller now merges them per attribute and this form renders one card
 * per module.
 */
export default function Form({ mode = 'create', role = null, groups = [], urls = {}, t = {} }) {
    const isEdit = mode === 'edit';

    const form = useForm({
        id:          role?.id ?? null,
        name:        role?.name ?? '',
        status:      role?.status ?? 1,
        permissions: role?.permissions ?? [],
    });

    const [q, setQ] = React.useState('');

    const selected = React.useMemo(() => new Set(form.data.permissions), [form.data.permissions]);
    const allValues = React.useMemo(
        () => groups.flatMap((g) => g.items.map((i) => i.value)),
        [groups],
    );

    const visible = React.useMemo(() => {
        const needle = q.trim().toLowerCase();
        if (!needle) return groups;
        return groups.filter(
            (g) =>
                g.label.toLowerCase().includes(needle) ||
                g.key.toLowerCase().includes(needle) ||
                g.items.some((i) => i.label.toLowerCase().includes(needle)),
        );
    }, [groups, q]);

    const setPerms = (next) => form.setData('permissions', Array.from(next));

    const toggle = (value) => {
        const next = new Set(selected);
        next.has(value) ? next.delete(value) : next.add(value);
        setPerms(next);
    };

    const toggleGroup = (g, on) => {
        const next = new Set(selected);
        g.items.forEach((i) => (on ? next.add(i.value) : next.delete(i.value)));
        setPerms(next);
    };

    const submit = (e) => {
        e.preventDefault();
        if (isEdit) form.put(urls.submit);
        else form.post(urls.submit);
    };

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.title_index, t.title]}>
            <Head title={t.title} />

            <form onSubmit={submit} className="space-y-4">
                {/* ---------- identity ---------- */}
                <Card>
                    <CardContent className="grid gap-4 p-5 sm:grid-cols-3">
                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="name">
                                {t.name} <span className="text-rose-500">*</span>
                            </Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                autoFocus
                            />
                            {form.errors.name && <p className="text-xs text-rose-600">{form.errors.name}</p>}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="status">{t.status}</Label>
                            <Select
                                id="status"
                                value={form.data.status}
                                onChange={(e) => form.setData('status', e.target.value)}
                            >
                                <option value={1}>{t.active}</option>
                                <option value={0}>{t.inactive}</option>
                            </Select>
                            {form.errors.status && <p className="text-xs text-rose-600">{form.errors.status}</p>}
                        </div>
                    </CardContent>
                </Card>

                {/* ---------- permission matrix ---------- */}
                <Card>
                    <CardContent className="p-5">
                        <div className="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-border pb-3">
                            <div className="flex items-center gap-2">
                                <ShieldCheck className="h-4 w-4 text-primary" />
                                <span className="text-sm font-semibold">{t.permissions}</span>
                                <span className="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                                    {form.data.permissions.length} {t.selected}
                                </span>
                            </div>

                            <div className="flex flex-wrap items-center gap-2">
                                <div className="relative">
                                    <Search className="pointer-events-none absolute inset-y-0 start-2 my-auto h-4 w-4 text-muted-foreground" />
                                    <Input
                                        value={q}
                                        onChange={(e) => setQ(e.target.value)}
                                        placeholder={t.search}
                                        className="h-9 w-48 ps-8"
                                    />
                                </div>
                                <Button type="button" variant="outline" size="sm" onClick={() => setPerms(new Set(allValues))}>
                                    {t.select_all}
                                </Button>
                                <Button type="button" variant="outline" size="sm" onClick={() => setPerms(new Set())}>
                                    {t.clear_all}
                                </Button>
                            </div>
                        </div>

                        {form.errors.permissions && (
                            <p className="mb-3 text-xs text-rose-600">{form.errors.permissions}</p>
                        )}

                        <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            {visible.map((g) => {
                                const on = g.items.filter((i) => selected.has(i.value)).length;
                                const all = on === g.items.length && on > 0;
                                return (
                                    <div key={g.key} className="rounded-lg border border-border">
                                        <div className="flex items-center justify-between gap-2 border-b border-border bg-muted/30 px-3 py-2">
                                            <label className="flex min-w-0 items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    checked={all}
                                                    ref={(el) => el && (el.indeterminate = on > 0 && !all)}
                                                    onChange={(e) => toggleGroup(g, e.target.checked)}
                                                    className="h-4 w-4 shrink-0"
                                                />
                                                <span className="truncate text-sm font-medium">{g.label}</span>
                                            </label>
                                            <span className="shrink-0 text-[11px] tabular-nums text-muted-foreground">
                                                {on}/{g.items.length}
                                            </span>
                                        </div>

                                        <div className="space-y-1.5 px-3 py-2">
                                            {g.items.map((i) => (
                                                <label key={i.value} className="flex items-start gap-2 text-sm">
                                                    <input
                                                        type="checkbox"
                                                        checked={selected.has(i.value)}
                                                        onChange={() => toggle(i.value)}
                                                        className="mt-0.5 h-4 w-4 shrink-0"
                                                    />
                                                    <span className={cn('leading-snug', !selected.has(i.value) && 'text-muted-foreground')}>
                                                        {i.label}
                                                    </span>
                                                </label>
                                            ))}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>

                        {visible.length === 0 && (
                            <p className="py-8 text-center text-sm text-muted-foreground">{t.no_rows}</p>
                        )}
                    </CardContent>
                </Card>

                {/* ---------- actions ---------- */}
                <div className="flex items-center justify-end gap-2">
                    <Button type="button" variant="outline" onClick={() => router.visit(urls.index)} disabled={form.processing}>
                        {t.cancel}
                    </Button>
                    <Button type="submit" disabled={form.processing}>
                        {form.processing
                            ? <><Loader2 className="h-4 w-4 me-1 animate-spin" /> {t.saving}</>
                            : <><Save className="h-4 w-4 me-1" /> {t.save}</>}
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
