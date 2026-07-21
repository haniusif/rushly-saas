import * as React from 'react';
import { Head } from '@inertiajs/react';
import { Building2, Plus, ExternalLink, Copy, Check } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';

function StatusPill({ status, labels }) {
    const ok = Number(status) === 1;
    return (
        <span className={`inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium ${ok ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-rose-100 text-rose-700 border-rose-200'}`}>
            {ok ? labels.active : labels.inactive}
        </span>
    );
}

function PortalCell({ url, domain, labels }) {
    const [copied, setCopied] = React.useState(false);

    if (!url) return <span className="text-xs text-muted-foreground">—</span>;

    const copy = async () => {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(url);
            } else {
                // Legacy fallback for non-HTTPS dev environments.
                const ta = document.createElement('textarea');
                ta.value = url;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
            }
            setCopied(true);
            setTimeout(() => setCopied(false), 1500);
        } catch (e) {
            // best-effort; don't crash the row
        }
    };

    return (
        <div className="flex items-center gap-2">
            <a
                href={url}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-1 text-sky-600 hover:text-sky-700 hover:underline"
                title={labels.open}
            >
                <span className="truncate max-w-[220px]">{domain}</span>
                <ExternalLink className="h-3.5 w-3.5 shrink-0" />
            </a>
            <button
                type="button"
                onClick={copy}
                className="inline-flex items-center rounded-md border border-input p-1 text-muted-foreground hover:bg-muted hover:text-foreground"
                title={copied ? labels.copied : labels.copy}
                aria-label={copied ? labels.copied : labels.copy}
            >
                {copied ? <Check className="h-3.5 w-3.5 text-emerald-600" /> : <Copy className="h-3.5 w-3.5" />}
            </button>
        </div>
    );
}

export default function Index({ children = [], urls = {}, labels = {} }) {
    return (
        <AdminLayout title={labels.title}>
            <Head title={labels.title} />

            {/* Layout already renders the "Sub-accounts" h1 via `title`; the
                inline h1 that used to live here plus the breadcrumb of the
                same word made "Sub-accounts" show three times in a row. */}
            <div className="mb-4 flex items-center justify-between gap-3">
                <p className="text-sm text-muted-foreground m-0 inline-flex items-center gap-2">
                    <Building2 className="h-4 w-4" /> {labels.subtitle}
                </p>
                <a
                    href={urls.create}
                    className="inline-flex h-9 items-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground hover:bg-primary/90 shrink-0"
                >
                    <Plus className="h-4 w-4 me-1" /> {labels.create}
                </a>
            </div>

            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border bg-muted/30 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                    <th className="px-4 py-3 text-start">#</th>
                                    <th className="px-4 py-3 text-start">{labels.name}</th>
                                    <th className="px-4 py-3 text-start">{labels.portal}</th>
                                    <th className="px-4 py-3 text-start">{labels.email}</th>
                                    <th className="px-4 py-3 text-start">{labels.phone}</th>
                                    <th className="px-4 py-3 text-start">{labels.status}</th>
                                    <th className="px-4 py-3 text-start">{labels.created}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {children.length === 0 && (
                                    <tr>
                                        <td colSpan={7} className="px-4 py-10 text-center text-muted-foreground">
                                            {labels.empty}
                                        </td>
                                    </tr>
                                )}
                                {children.map((c, i) => (
                                    <tr key={c.id} className="border-b border-border/60 hover:bg-muted/20">
                                        <td className="px-4 py-3 text-muted-foreground">{i + 1}</td>
                                        <td className="px-4 py-3 font-medium">{c.name}</td>
                                        <td className="px-4 py-3">
                                            <PortalCell url={c.portal_url} domain={c.domain} labels={labels} />
                                        </td>
                                        <td className="px-4 py-3">{c.email}</td>
                                        <td className="px-4 py-3">{c.phone}</td>
                                        <td className="px-4 py-3"><StatusPill status={c.status} labels={labels} /></td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {c.created_at ? new Date(c.created_at).toLocaleDateString() : ''}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
