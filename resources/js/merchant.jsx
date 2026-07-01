import '../css/merchant.css';

import * as React from 'react';
import { createRoot, hydrateRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { route as ziggyRoute } from 'ziggy-js';
import TourProvider from './Tour/TourProvider';

// The blade emits `@routes`, which sets window.Ziggy. Expose ziggy-js's route()
// as window.route() — the layout calls it via window.route(name, params).
if (typeof window !== 'undefined') {
    window.route = (name, params, absolute) => ziggyRoute(name, params, absolute, window.Ziggy);
}

const pages = import.meta.glob('./Pages/**/*.jsx');

createInertiaApp({
    resolve: (name) => {
        const path = `./Pages/${name}.jsx`;
        const loader = pages[path];
        if (!loader) {
            throw new Error(`Inertia page not found: ${name} (looked for ${path})`);
        }
        return loader().then((m) => m.default);
    },
    setup({ el, App, props }) {
        // Read locale from the initial page's shared props — this is the same
        // data that useT()/useLocale() read after Inertia mounts. Doing it here
        // (rather than via usePage() inside a wrapper) is required because
        // usePage's context is only available under Inertia's own <App>.
        const locale = props?.initialPage?.props?.app?.locale || 'en';
        const isRTL  = locale === 'ar';

        const node = React.createElement(
            TourProvider,
            { locale, isRTL },
            React.createElement(App, props),
        );

        if (el.hasChildNodes()) {
            hydrateRoot(el, node);
        } else {
            createRoot(el).render(node);
        }
    },
    progress: { color: '#a21f5c' },
});
