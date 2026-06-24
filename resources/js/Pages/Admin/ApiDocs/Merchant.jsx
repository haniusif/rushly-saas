import * as React from 'react';
import { Head } from '@inertiajs/react';
import { BookOpen, Key, Copy, Check, Terminal, ExternalLink, Lock, Globe } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Input } from '@/Components/ui/Input';
import { cn } from '@/lib/utils';

const METHOD_TINT = {
    GET:    'bg-emerald-100 text-emerald-700 border-emerald-200',
    POST:   'bg-sky-100     text-sky-700     border-sky-200',
    PUT:    'bg-amber-100   text-amber-700   border-amber-200',
    PATCH:  'bg-amber-100   text-amber-700   border-amber-200',
    DELETE: 'bg-rose-100    text-rose-700    border-rose-200',
};

function MethodBadge({ method }) {
    return (
        <span className={cn(
            'inline-flex items-center rounded border px-1.5 py-0.5 text-[10px] font-mono font-bold tracking-tight uppercase shrink-0 w-16 justify-center',
            METHOD_TINT[method] || 'bg-slate-100 text-slate-700 border-slate-200'
        )}>
            {method}
        </span>
    );
}

function CopyButton({ text }) {
    const [copied, setCopied] = React.useState(false);
    const onClick = async () => {
        try { await navigator.clipboard.writeText(text); setCopied(true); setTimeout(() => setCopied(false), 1200); } catch {}
    };
    return (
        <button
            type="button"
            onClick={onClick}
            className="ms-2 inline-flex h-6 items-center rounded border border-input bg-background px-1.5 text-[10px] font-medium text-muted-foreground hover:bg-muted/40 transition-colors"
            title="Copy"
        >
            {copied ? <Check className="h-3 w-3 me-1 text-emerald-600" /> : <Copy className="h-3 w-3 me-1" />}
            {copied ? 'Copied' : 'Copy'}
        </button>
    );
}

function EndpointRow({ ep, apiBase }) {
    const fullPath = apiBase + ep.path;
    return (
        <div className="flex items-start gap-3 py-2 border-b border-border last:border-0">
            <MethodBadge method={ep.method} />
            <div className="flex-1 min-w-0">
                <div className="flex items-center flex-wrap">
                    <code className="text-xs font-mono break-all">{ep.path}</code>
                    <CopyButton text={fullPath} />
                    {ep.is_public
                        ? <span className="ms-2 inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-1.5 py-0.5 text-[10px] font-medium text-emerald-700">
                            <Globe className="h-2.5 w-2.5" /> public
                          </span>
                        : <span className="ms-2 inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-medium text-slate-600">
                            <Lock className="h-2.5 w-2.5" /> auth
                          </span>}
                </div>
                <p className="text-[11px] text-muted-foreground mt-0.5">{ep.purpose}</p>
            </div>
        </div>
    );
}

function Section({ section, apiBase, filter }) {
    const filtered = React.useMemo(() => {
        if (!filter) return section.endpoints;
        const f = filter.toLowerCase();
        return section.endpoints.filter((e) =>
            e.path.toLowerCase().includes(f) ||
            (e.purpose || '').toLowerCase().includes(f) ||
            e.method.toLowerCase().includes(f)
        );
    }, [section.endpoints, filter]);
    if (filtered.length === 0) return null;
    return (
        <Card className="mb-4">
            <CardContent className="p-5">
                <div className="mb-2">
                    <h3 className="text-base font-semibold">{section.title}</h3>
                    {section.help && <p className="text-[12px] text-muted-foreground mt-0.5">{section.help}</p>}
                </div>
                <div className="-mb-2">
                    {filtered.map((ep, i) => <EndpointRow key={`${ep.method}${ep.path}${i}`} ep={ep} apiBase={apiBase} />)}
                </div>
            </CardContent>
        </Card>
    );
}

function CodeBlock({ children }) {
    const [copied, setCopied] = React.useState(false);
    const text = typeof children === 'string' ? children : (children?.props?.children ?? '');
    const onCopy = async () => {
        try { await navigator.clipboard.writeText(text); setCopied(true); setTimeout(() => setCopied(false), 1200); } catch {}
    };
    return (
        <div className="relative rounded-md border border-border bg-slate-950 text-slate-50 font-mono text-[12px] leading-relaxed">
            <button
                type="button"
                onClick={onCopy}
                className="absolute right-2 top-2 inline-flex h-7 items-center rounded border border-slate-700 bg-slate-900/80 px-2 text-[10px] font-medium text-slate-200 hover:bg-slate-800"
            >
                {copied ? <><Check className="h-3 w-3 me-1 text-emerald-400" /> Copied</> : <><Copy className="h-3 w-3 me-1" /> Copy</>}
            </button>
            <pre className="p-4 overflow-x-auto whitespace-pre"><code>{children}</code></pre>
        </div>
    );
}

export default function Merchant({ sections = [], api_base = '', api_key_hint = '', t = {} }) {
    const [filter, setFilter] = React.useState('');
    const visibleCount = React.useMemo(() => {
        if (!filter) return sections.reduce((s, sec) => s + sec.endpoints.length, 0);
        const f = filter.toLowerCase();
        return sections.reduce((s, sec) =>
            s + sec.endpoints.filter((e) =>
                e.path.toLowerCase().includes(f) ||
                (e.purpose || '').toLowerCase().includes(f) ||
                e.method.toLowerCase().includes(f)
            ).length, 0);
    }, [sections, filter]);

    const mintSnippet = `php artisan tinker --execute='
tenancy()->initialize(App\\Models\\Tenant::find("YOUR-TENANT-ID"));
$u = App\\Models\\Backend\\Merchant::find(MERCHANT_ID)->user;
echo $u->createToken("merchant-app")->plainTextToken . PHP_EOL;
'`;

    const curlSnippet = `curl '${api_base}/dashboard' \\
  -H 'apiKey: YOUR_RUSHLY_API_KEY' \\
  -H 'Authorization: Bearer {token}' \\
  -H 'Accept: application/json'`;

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.breadcrumb_settings, t.title]}>
            <Head title={t.title} />

            <Card className="mb-4">
                <CardContent className="p-5">
                    <div className="flex items-start gap-3">
                        <BookOpen className="h-6 w-6 text-primary mt-0.5" />
                        <div>
                            <h2 className="text-lg font-semibold">{t.title}</h2>
                            <p className="text-sm text-muted-foreground">{t.subtitle}</p>
                            <div className="mt-2 text-[12px]">
                                <span className="text-muted-foreground">Base URL: </span>
                                <code className="font-mono">{api_base}</code>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Auth */}
            <Card className="mb-4">
                <CardContent className="p-5">
                    <div className="flex items-center gap-2 mb-2">
                        <Key className="h-4 w-4 text-primary" />
                        <h3 className="text-sm font-semibold">{t.auth_section}</h3>
                    </div>
                    <p className="text-[12px] text-muted-foreground mb-3">{t.auth_lead}</p>
                    <CodeBlock>{`apiKey: YOUR_RUSHLY_API_KEY
Authorization: Bearer {token}`}</CodeBlock>
                    <ul className="text-[12px] text-muted-foreground mt-3 space-y-1">
                        <li><strong>apiKey</strong> — {api_key_hint}</li>
                        <li><strong>Bearer</strong> — minted via Sanctum for the merchant user. See below for how to mint one.</li>
                    </ul>
                </CardContent>
            </Card>

            <Card className="mb-4">
                <CardContent className="p-5">
                    <div className="flex items-center gap-2 mb-2">
                        <Terminal className="h-4 w-4 text-primary" />
                        <h3 className="text-sm font-semibold">{t.mint_section}</h3>
                    </div>
                    <p className="text-[12px] text-muted-foreground mb-3">{t.mint_help}</p>
                    <CodeBlock>{mintSnippet}</CodeBlock>
                </CardContent>
            </Card>

            <Card className="mb-6">
                <CardContent className="p-5">
                    <div className="flex items-center gap-2 mb-2">
                        <Terminal className="h-4 w-4 text-primary" />
                        <h3 className="text-sm font-semibold">{t.try_section}</h3>
                    </div>
                    <p className="text-[12px] text-muted-foreground mb-3">{t.try_help}</p>
                    <CodeBlock>{curlSnippet}</CodeBlock>
                </CardContent>
            </Card>

            {/* Filter */}
            <Card className="mb-4">
                <CardContent className="pt-5 pb-4">
                    <div className="flex items-center gap-3">
                        <Input
                            value={filter}
                            onChange={(e) => setFilter(e.target.value)}
                            placeholder={t.search_placeholder}
                            className="flex-1"
                        />
                        <span className="text-[11px] text-muted-foreground tabular-nums shrink-0">
                            {visibleCount} endpoints
                        </span>
                    </div>
                </CardContent>
            </Card>

            {/* Sections */}
            {visibleCount === 0
                ? (
                    <Card>
                        <CardContent className="p-10 text-center text-muted-foreground">
                            <ExternalLink className="h-10 w-10 mx-auto mb-2 text-muted-foreground/40" />
                            <p>{t.no_results}</p>
                        </CardContent>
                    </Card>
                )
                : sections.map((s) => <Section key={s.key} section={s} apiBase={api_base} filter={filter} />)}
        </AdminLayout>
    );
}
