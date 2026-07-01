import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import { ArrowLeft, Play } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { useTourEngine } from '@/Tour/TourProvider';

/**
 * Preview: renders a landing card with a "Play" button. The tour engine
 * is already mounted globally, so we just call start() with a synthetic
 * tour object built from the loaded definition.
 */
export default function Preview({ tour = {}, urls = {}, t = {} }) {
    const { start } = useTourEngine();

    const play = () => {
        // The engine can start any tour object — inject minimally.
        const synthetic = {
            key:     tour.key,
            title:   tour.title,
            version: tour.version || 1,
            steps:   (tour.steps || []).map((s, i) => {
                const content = (s.translations?.en || Object.values(s.translations || {})[0] || {});
                return {
                    id:                s.id ?? i,
                    sort_order:        s.sort_order ?? i,
                    target:            s.target,
                    placement:         s.placement || 'auto',
                    spotlight_padding: s.spotlight_padding ?? 8,
                    title:             content.title || '',
                    body:              content.body || '',
                    action:            s.action,
                };
            }),
        };
        start(synthetic);
    };

    return (
        <AdminLayout title={`Preview · ${tour.title}`} breadcrumbs={[t.title_index, t.preview]}>
            <Head title={`Preview · ${tour.title}`} />

            <div className="mb-4 flex items-center gap-3">
                <a href={urls.back} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent no-underline">
                    <ArrowLeft className="h-4 w-4 me-1" /> {t.back}
                </a>
                <h1 className="text-xl font-semibold m-0">{tour.title}</h1>
            </div>

            <Card>
                <CardContent className="p-8 text-center">
                    <p className="text-sm text-muted-foreground">
                        Preview will run the tour against the CURRENT page — you'll want to open the tour on the page
                        it was designed for. Steps whose target selectors aren't present will emit an
                        "element_missing" event and be skipped in the popover.
                    </p>
                    <button
                        type="button"
                        onClick={play}
                        className="mt-4 inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
                    >
                        <Play className="h-4 w-4" /> Play preview
                    </button>
                    <div className="mt-6 text-xs text-muted-foreground">
                        Tour: <span className="font-mono">{tour.key}</span> · {tour.steps?.length || 0} steps · v{tour.version || 1}
                    </div>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
