import * as React from 'react';
import { Save, Plus, Trash2, ArrowUp, ArrowDown, Play } from 'lucide-react';
import { Card, CardContent } from '@/Components/ui/Card';
import { Input } from '@/Components/ui/Input';
import { Textarea } from '@/Components/ui/Textarea';
import { Select } from '@/Components/ui/Select';

/**
 * Shared form for Create + Edit. Handles step list, reorder, and EN/AR
 * translation tabs per step.
 */
export default function TourForm({ form, lookups, urls, t, mode = 'create', onPreview }) {
    const setField  = (k, v) => form.setData(k, v);
    const setStep   = (i, key, val) => {
        const steps = [...form.data.steps];
        steps[i] = { ...steps[i], [key]: val };
        setField('steps', steps);
    };
    const setTrans  = (i, locale, key, val) => {
        const steps = [...form.data.steps];
        const tr = { ...(steps[i].translations || {}) };
        tr[locale] = { ...(tr[locale] || {}), [key]: val };
        steps[i] = { ...steps[i], translations: tr };
        setField('steps', steps);
    };
    const addStep = () => {
        setField('steps', [...form.data.steps, {
            target: { type: 'data-tour', value: '' },
            placement: 'auto', spotlight_padding: 8,
            translations: { en: { title: '', body: '' }, ar: { title: '', body: '' } },
        }]);
    };
    const removeStep = (i) => setField('steps', form.data.steps.filter((_, j) => j !== i));
    const move = (i, dir) => {
        const j = i + dir;
        if (j < 0 || j >= form.data.steps.length) return;
        const s = [...form.data.steps];
        [s[i], s[j]] = [s[j], s[i]];
        setField('steps', s);
    };
    const toggleRole = (r) => {
        const list = [...(form.data.role_scope || [])];
        const idx = list.indexOf(r);
        if (idx >= 0) list.splice(idx, 1); else list.push(r);
        setField('role_scope', list);
    };

    return (
        <div className="grid gap-4 lg:grid-cols-3">
            <div className="lg:col-span-2 space-y-4">
                <Card>
                    <CardContent className="p-5 space-y-4">
                        <div className="grid gap-3 md:grid-cols-2">
                            <Field label={`${t.key} *`} error={form.errors.key}>
                                <Input value={form.data.key || ''} onChange={(e) => setField('key', e.target.value)} placeholder="module.page.name" />
                            </Field>
                            <Field label={`Title *`} error={form.errors.title}>
                                <Input value={form.data.title || ''} onChange={(e) => setField('title', e.target.value)} />
                            </Field>
                            <Field label={t.module}>
                                <Input value={form.data.module || ''} onChange={(e) => setField('module', e.target.value)} placeholder="dashboard, parcels, ..." />
                            </Field>
                            <Field label={t.trigger_route}>
                                <Input value={form.data.trigger_route || ''} onChange={(e) => setField('trigger_route', e.target.value)} placeholder="dashboard.index" />
                            </Field>
                            <Field label={t.description} className="md:col-span-2">
                                <Textarea rows={2} value={form.data.description || ''} onChange={(e) => setField('description', e.target.value)} />
                            </Field>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-5 space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-base font-semibold m-0">{t.steps}</h3>
                            <div className="flex items-center gap-2">
                                {onPreview && (
                                    <button type="button" onClick={onPreview} className="inline-flex h-8 items-center gap-1 rounded-md border border-input bg-background px-2 text-xs hover:bg-muted">
                                        <Play className="h-3 w-3" /> {t.preview}
                                    </button>
                                )}
                                <button type="button" onClick={addStep} className="inline-flex h-8 items-center gap-1 rounded-md bg-primary px-2 text-xs text-primary-foreground">
                                    <Plus className="h-3 w-3" /> {t.add_step}
                                </button>
                            </div>
                        </div>
                        <div className="text-[11px] text-muted-foreground">{t.reorder_hint}</div>

                        <div className="space-y-3">
                            {(form.data.steps || []).map((step, i) => (
                                <div key={i} className="rounded-md border border-border p-3">
                                    <div className="flex items-center justify-between gap-2 mb-3">
                                        <div className="text-xs font-semibold text-muted-foreground">Step {i + 1}</div>
                                        <div className="flex items-center gap-1">
                                            <button type="button" onClick={() => move(i, -1)} disabled={i === 0} className="h-7 w-7 grid place-items-center rounded-md border border-input hover:bg-muted disabled:opacity-40">
                                                <ArrowUp className="h-3 w-3" />
                                            </button>
                                            <button type="button" onClick={() => move(i, +1)} disabled={i === form.data.steps.length - 1} className="h-7 w-7 grid place-items-center rounded-md border border-input hover:bg-muted disabled:opacity-40">
                                                <ArrowDown className="h-3 w-3" />
                                            </button>
                                            <button type="button" onClick={() => removeStep(i)} className="h-7 w-7 grid place-items-center rounded-md border border-input text-destructive hover:bg-destructive/10">
                                                <Trash2 className="h-3 w-3" />
                                            </button>
                                        </div>
                                    </div>

                                    <div className="grid gap-3 md:grid-cols-3">
                                        <Field label={t.target_type}>
                                            <Select value={step.target?.type || 'data-tour'} onChange={(e) => setStep(i, 'target', { ...step.target, type: e.target.value })}>
                                                {lookups.target_types.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                                            </Select>
                                        </Field>
                                        <Field label={t.target_value} className="md:col-span-2">
                                            <Input value={step.target?.value || ''} onChange={(e) => setStep(i, 'target', { ...step.target, value: e.target.value })} placeholder="e.g. sidebar-nav_parcels or #dashboard-kpis" />
                                        </Field>
                                        <Field label={t.placement}>
                                            <Select value={step.placement || 'auto'} onChange={(e) => setStep(i, 'placement', e.target.value)}>
                                                {lookups.placements.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                                            </Select>
                                        </Field>
                                        <Field label={t.padding}>
                                            <Input type="number" min={0} max={64} value={step.spotlight_padding ?? 8} onChange={(e) => setStep(i, 'spotlight_padding', parseInt(e.target.value) || 0)} />
                                        </Field>
                                    </div>

                                    <div className="mt-4 grid gap-3 md:grid-cols-2">
                                        <div className="rounded-md border border-border p-3">
                                            <div className="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground mb-2">{t.lang_en}</div>
                                            <div className="space-y-2">
                                                <Input placeholder={t.step_title} value={step.translations?.en?.title || ''} onChange={(e) => setTrans(i, 'en', 'title', e.target.value)} />
                                                <Textarea rows={2} placeholder={t.step_body} value={step.translations?.en?.body || ''} onChange={(e) => setTrans(i, 'en', 'body', e.target.value)} />
                                            </div>
                                        </div>
                                        <div className="rounded-md border border-border p-3" dir="rtl">
                                            <div className="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground mb-2">{t.lang_ar}</div>
                                            <div className="space-y-2">
                                                <Input placeholder={t.step_title} value={step.translations?.ar?.title || ''} onChange={(e) => setTrans(i, 'ar', 'title', e.target.value)} />
                                                <Textarea rows={2} placeholder={t.step_body} value={step.translations?.ar?.body || ''} onChange={(e) => setTrans(i, 'ar', 'body', e.target.value)} />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                            {(form.data.steps || []).length === 0 && (
                                <div className="rounded-md border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                                    No steps yet — click "{t.add_step}" to add one.
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div className="lg:col-span-1 space-y-4">
                <Card>
                    <CardContent className="p-5 space-y-4">
                        <Field label={t.role_scope}>
                            <div className="space-y-1">
                                {lookups.roles.map((r) => (
                                    <label key={r.value} className="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={(form.data.role_scope || []).includes(r.value)}
                                            onChange={() => toggleRole(r.value)}
                                            className="rounded"
                                        />
                                        {r.label}
                                    </label>
                                ))}
                            </div>
                            <p className="mt-1 text-[11px] text-muted-foreground">Empty = all roles.</p>
                        </Field>

                        <Field label={t.version}>
                            <Input type="number" min={1} value={form.data.version ?? 1} onChange={(e) => setField('version', parseInt(e.target.value) || 1)} />
                            <p className="mt-1 text-[11px] text-muted-foreground">Bump to re-show the tour to users who saw the previous version.</p>
                        </Field>

                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={!!form.data.is_active} onChange={(e) => setField('is_active', e.target.checked)} className="rounded" />
                            {t.active}
                        </label>
                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={!!form.data.auto_start} onChange={(e) => setField('auto_start', e.target.checked)} className="rounded" />
                            {t.auto_start}
                        </label>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}

function Field({ label, error, children, className }) {
    return (
        <div className={className || 'space-y-1.5'}>
            <label className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{label}</label>
            {children}
            {error && <p className="text-xs text-destructive">{error}</p>}
        </div>
    );
}
