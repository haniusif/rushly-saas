import * as React from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    Key,
    Plus,
    Copy,
    RefreshCw,
    Trash2,
    Power,
    ShieldCheck,
    AlertTriangle,
    Code2,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Textarea } from '@/Components/ui/Textarea';

function formatRelative(iso) {
    if (! iso) return '—';
    try {
        const d = new Date(iso);
        return d.toLocaleString();
    } catch {
        return iso;
    }
}

function CopyButton({ value, label = 'Copy' }) {
    const [copied, setCopied] = React.useState(false);
    return (
        <button
            type="button"
            onClick={async () => {
                try {
                    await navigator.clipboard.writeText(value);
                    setCopied(true);
                    setTimeout(() => setCopied(false), 1500);
                } catch { /* clipboard blocked */ }
            }}
            className="inline-flex items-center gap-1 rounded border border-slate-300 bg-white px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
        >
            <Copy className="h-3 w-3" />
            {copied ? 'Copied!' : label}
        </button>
    );
}

function NewKeyBanner({ plaintext, onDismiss }) {
    if (! plaintext) return null;
    return (
        <div className="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4">
            <div className="flex items-start gap-3">
                <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
                <div className="flex-1">
                    <div className="text-sm font-semibold text-amber-900">
                        Copy your new API key now
                    </div>
                    <p className="mt-1 text-xs text-amber-800">
                        For security, this is the only time the full key will be shown. Store it somewhere safe — you can rotate it later, but you cannot view it again.
                    </p>
                    <div className="mt-3 flex items-center gap-2">
                        <code className="flex-1 truncate rounded border border-amber-300 bg-white px-3 py-2 font-mono text-sm text-slate-800">
                            {plaintext}
                        </code>
                        <CopyButton value={plaintext} label="Copy key" />
                    </div>
                </div>
                <button
                    type="button"
                    onClick={onDismiss}
                    className="text-xs text-amber-800 underline hover:text-amber-900"
                >
                    Dismiss
                </button>
            </div>
        </div>
    );
}

function CreateForm({ storeUrl }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        allowed_origins: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(storeUrl, {
            preserveScroll: true,
            onSuccess: () => reset('name', 'allowed_origins'),
        });
    };

    return (
        <Card>
            <CardContent className="p-5">
                <div className="mb-4 flex items-center gap-2 text-sm font-semibold text-indigo-700">
                    <Plus className="h-4 w-4" />
                    Create a new API key
                </div>
                <form onSubmit={submit} className="space-y-3">
                    <div>
                        <label className="text-xs font-medium uppercase tracking-wide text-slate-600">
                            Name <span className="text-rose-500">*</span>
                        </label>
                        <Input
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="e.g. Storefront tracking widget"
                            className="mt-1"
                        />
                        {errors.name && <div className="mt-1 text-xs text-rose-600">{errors.name}</div>}
                    </div>
                    <div>
                        <label className="text-xs font-medium uppercase tracking-wide text-slate-600">
                            Allowed origins (optional)
                        </label>
                        <Textarea
                            value={data.allowed_origins}
                            onChange={(e) => setData('allowed_origins', e.target.value)}
                            placeholder="https://mystore.com, https://checkout.mystore.com"
                            rows={2}
                            className="mt-1"
                        />
                        <p className="mt-1 text-[11px] text-slate-500">
                            Comma or space separated. Leave blank to allow the key to be used from any origin (server-to-server).
                        </p>
                        {errors.allowed_origins && <div className="mt-1 text-xs text-rose-600">{errors.allowed_origins}</div>}
                    </div>
                    <div className="pt-1">
                        <Button type="submit" disabled={processing}>
                            <Plus className="h-4 w-4" /> Create key
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

function KeyRow({ item, urls }) {
    const fill = (tpl) => (tpl || '').replace('__ID__', item.id);
    const regenerate = () => {
        if (! confirm('Rotate this key? The current key will stop working immediately.')) return;
        router.post(fill(urls.regenerate_tpl), {}, { preserveScroll: true });
    };
    const toggle = () => {
        router.post(fill(urls.toggle_tpl), {}, { preserveScroll: true });
    };
    const destroy = () => {
        if (! confirm('Delete this key? This cannot be undone.')) return;
        router.delete(fill(urls.destroy_tpl), { preserveScroll: true });
    };

    return (
        <tr className="border-b border-slate-100 last:border-0">
            <td className="px-4 py-3">
                <div className="font-medium text-slate-800">{item.name}</div>
                <div className="mt-1 font-mono text-xs text-slate-500">{item.key_prefix}…</div>
            </td>
            <td className="px-4 py-3 text-xs text-slate-600">
                {item.allowed_origins.length === 0
                    ? <span className="text-slate-400">any</span>
                    : (
                        <div className="flex flex-wrap gap-1">
                            {item.allowed_origins.map((o) => (
                                <span key={o} className="rounded bg-slate-100 px-1.5 py-0.5 font-mono">{o}</span>
                            ))}
                        </div>
                    )
                }
            </td>
            <td className="px-4 py-3 text-center">
                {item.is_active
                    ? <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800"><ShieldCheck className="h-3 w-3" />active</span>
                    : <span className="inline-flex items-center gap-1 rounded-full bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-600">disabled</span>
                }
            </td>
            <td className="px-4 py-3 text-right text-xs text-slate-600">
                {item.request_count.toLocaleString()}
            </td>
            <td className="px-4 py-3 text-xs text-slate-600">{formatRelative(item.last_used_at)}</td>
            <td className="px-4 py-3">
                <div className="flex items-center justify-end gap-1">
                    <button
                        type="button"
                        onClick={regenerate}
                        title="Rotate key"
                        className="inline-flex items-center rounded border border-slate-300 bg-white p-1.5 text-slate-700 hover:bg-slate-50"
                    >
                        <RefreshCw className="h-3.5 w-3.5" />
                    </button>
                    <button
                        type="button"
                        onClick={toggle}
                        title={item.is_active ? 'Disable' : 'Enable'}
                        className="inline-flex items-center rounded border border-slate-300 bg-white p-1.5 text-slate-700 hover:bg-slate-50"
                    >
                        <Power className="h-3.5 w-3.5" />
                    </button>
                    <button
                        type="button"
                        onClick={destroy}
                        title="Delete key"
                        className="inline-flex items-center rounded border border-rose-300 bg-white p-1.5 text-rose-600 hover:bg-rose-50"
                    >
                        <Trash2 className="h-3.5 w-3.5" />
                    </button>
                </div>
            </td>
        </tr>
    );
}

function UsageExample({ endpoint, sampleTracking }) {
    const url = endpoint.replace('{tracking_id}', sampleTracking);
    const curl = `curl -H "X-API-Key: rxk_your_key_here" \\\n  "${url}"`;
    const js = `const res = await fetch("${url}", {
  headers: { "X-API-Key": "rxk_your_key_here" },
});
const { data } = await res.json();
console.log(data.status_label, data.events);`;
    return (
        <Card>
            <CardContent className="p-5">
                <div className="mb-3 flex items-center gap-2 text-sm font-semibold text-indigo-700">
                    <Code2 className="h-4 w-4" />
                    How to use
                </div>
                <div className="space-y-4">
                    <div>
                        <div className="mb-1 text-xs font-medium text-slate-600">Endpoint</div>
                        <div className="flex items-center gap-2">
                            <code className="flex-1 truncate rounded border border-slate-200 bg-slate-50 px-3 py-2 font-mono text-xs text-slate-800">
                                GET {endpoint}
                            </code>
                            <CopyButton value={endpoint} />
                        </div>
                    </div>
                    <div>
                        <div className="mb-1 text-xs font-medium text-slate-600">cURL</div>
                        <pre className="overflow-x-auto rounded border border-slate-200 bg-slate-900 p-3 font-mono text-xs leading-relaxed text-slate-100"><code>{curl}</code></pre>
                    </div>
                    <div>
                        <div className="mb-1 text-xs font-medium text-slate-600">JavaScript (browser)</div>
                        <pre className="overflow-x-auto rounded border border-slate-200 bg-slate-900 p-3 font-mono text-xs leading-relaxed text-slate-100"><code>{js}</code></pre>
                    </div>
                    <div className="rounded border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                        Send the key in the <code className="rounded bg-white px-1 py-0.5 font-mono">X-API-Key</code> header, or as a <code className="rounded bg-white px-1 py-0.5 font-mono">?api_key=</code> query parameter. Keys are scoped to your tenant — they can only look up parcels created under this account.
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

export default function Index({ keys = [], endpoint = '', sampleTracking = '', flash_key = null, urls = {} }) {
    const { props } = usePage();
    const [banner, setBanner] = React.useState(flash_key || null);

    // React to new flashed key when a create/regenerate lands.
    React.useEffect(() => {
        if (flash_key) setBanner(flash_key);
    }, [flash_key]);

    return (
        <AdminLayout>
            <Head title="Public tracking API keys" />

            <div className="mx-auto max-w-6xl space-y-6 p-4 lg:p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="flex items-center gap-2 text-lg font-semibold text-slate-800">
                            <Key className="h-5 w-5 text-indigo-600" />
                            Public tracking API keys
                        </h1>
                        <p className="mt-1 text-sm text-slate-600">
                            Issue keys so other websites (e.g. merchant storefronts) can look up parcel tracking status through your API.
                        </p>
                    </div>
                </div>

                <NewKeyBanner plaintext={banner} onDismiss={() => setBanner(null)} />

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <Card>
                            <CardContent className="p-0">
                                <div className="border-b border-slate-100 p-4 text-sm font-semibold text-slate-700">
                                    Active keys ({keys.length})
                                </div>
                                {keys.length === 0
                                    ? (
                                        <div className="p-8 text-center text-sm text-slate-500">
                                            No API keys yet. Create one to get started.
                                        </div>
                                    )
                                    : (
                                        <div className="overflow-x-auto">
                                            <table className="w-full text-sm">
                                                <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                                    <tr>
                                                        <th className="px-4 py-3">Name / Prefix</th>
                                                        <th className="px-4 py-3">Allowed origins</th>
                                                        <th className="px-4 py-3 text-center">Status</th>
                                                        <th className="px-4 py-3 text-right">Requests</th>
                                                        <th className="px-4 py-3">Last used</th>
                                                        <th className="px-4 py-3"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {keys.map((k) => (
                                                        <KeyRow key={k.id} item={k} urls={urls} />
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    )
                                }
                            </CardContent>
                        </Card>
                    </div>
                    <div className="space-y-6">
                        <CreateForm storeUrl={urls.store} />
                        <UsageExample endpoint={endpoint} sampleTracking={sampleTracking} />
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
