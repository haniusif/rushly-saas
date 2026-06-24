import * as React from 'react';
import { Head } from '@inertiajs/react';
import { BookOpen, ExternalLink } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';

const REDOC_CDN = 'https://cdn.jsdelivr.net/npm/redoc@2.1.5/bundles/redoc.standalone.js';

function useRedoc(specUrl, containerRef) {
    React.useEffect(() => {
        if (!specUrl || !containerRef.current) return;
        let cancelled = false;
        const init = () => {
            if (cancelled || !window.Redoc || !containerRef.current) return;
            containerRef.current.innerHTML = '';
            window.Redoc.init(specUrl, {
                expandResponses: '200',
                hideDownloadButton: false,
                pathInMiddlePanel: true,
                hideLoading: false,
                theme: {
                    colors: { primary: { main: '#a21f5c' } },
                    typography: {
                        fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif',
                        headings: { fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif' },
                        code: { fontFamily: 'ui-monospace, SFMono-Regular, Menlo, monospace' },
                    },
                    sidebar: { width: '260px' },
                },
            }, containerRef.current);
        };

        if (window.Redoc) {
            init();
            return () => { cancelled = true; };
        }

        let script = document.querySelector(`script[src="${REDOC_CDN}"]`);
        if (!script) {
            script = document.createElement('script');
            script.src = REDOC_CDN;
            script.async = true;
            document.body.appendChild(script);
        }
        script.addEventListener('load', init);
        return () => {
            cancelled = true;
            script.removeEventListener('load', init);
        };
    }, [specUrl, containerRef]);
}

function PublicShell({ title, specUrl, children }) {
    return (
        <div className="min-h-screen bg-background text-foreground">
            <header className="border-b border-border bg-card">
                <div className="max-w-7xl mx-auto px-5 py-3 flex items-center gap-3">
                    <BookOpen className="h-5 w-5 text-primary" />
                    <h1 className="text-sm font-semibold flex-1">{title}</h1>
                    <a
                        href={specUrl}
                        target="_blank"
                        rel="noreferrer"
                        className="inline-flex h-8 items-center rounded-md border border-input bg-background px-3 text-xs font-medium hover:bg-muted/40"
                    >
                        <ExternalLink className="h-3 w-3 me-1" /> Download OpenAPI
                    </a>
                </div>
            </header>
            {children}
        </div>
    );
}

export default function Merchant({ is_public = false, api_base = '', spec_url = '', t = {} }) {
    const containerRef = React.useRef(null);
    useRedoc(spec_url, containerRef);

    const body = (
        <>
            <Head title={t.title} />
            <div
                ref={containerRef}
                className="redoc-host min-h-[80vh]"
                style={{ background: '#fff' }}
            >
                <div className="p-10 text-center text-sm text-muted-foreground">
                    {t.loading}
                </div>
            </div>
        </>
    );

    if (is_public) {
        return (
            <PublicShell title={t.title} specUrl={spec_url}>
                {body}
            </PublicShell>
        );
    }

    return (
        <AdminLayout title={t.title} breadcrumbs={[t.breadcrumb_settings, t.title]}>
            <div className="mb-3 flex items-center justify-between">
                <p className="text-xs text-muted-foreground">
                    Base URL: <code className="font-mono">{api_base}</code>
                </p>
                <a
                    href={spec_url}
                    target="_blank"
                    rel="noreferrer"
                    className="inline-flex h-8 items-center rounded-md border border-input bg-background px-3 text-xs font-medium hover:bg-muted/40"
                >
                    <ExternalLink className="h-3 w-3 me-1" /> Download OpenAPI
                </a>
            </div>
            <div className="rounded-md border border-border overflow-hidden bg-white">
                {body}
            </div>
        </AdminLayout>
    );
}
