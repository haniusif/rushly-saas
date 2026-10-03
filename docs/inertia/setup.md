# Inertia + React + Tailwind stack — setup

What was installed and wired up to make the admin Inertia pages work.
This is the one-time infrastructure; new pages don't need any of these
steps repeated.

## Packages

### composer

```bash
composer require inertiajs/inertia-laravel
composer require tightenco/ziggy
```

`tightenco/ziggy` is loaded server-side so `HandleInertiaRequests::share`
can call `(new Ziggy)->toArray()`. The blade root view also emits the
`@routes` directive (Ziggy) so `window.Ziggy` is available client-side
before the React app boots.

### npm (in `package.json`)

```jsonc
"dependencies": {
  "@inertiajs/react": "^2.0.0",
  "@radix-ui/react-dropdown-menu": "^2.1.2",
  "@radix-ui/react-label": "^2.1.0",
  "@radix-ui/react-slot": "^1.1.0",
  "class-variance-authority": "^0.7.0",
  "clsx": "^2.1.1",
  "lucide-react": "^0.460.0",
  "tailwind-merge": "^2.5.4",
  "tailwindcss-animate": "^1.0.7",
  "ziggy-js": "^2.5.0"
},
"devDependencies": {
  "@vitejs/plugin-react": "^4.3.4",
  "autoprefixer": "^10.4.20",
  "postcss": "^8.4.49",
  "react": "^18.3.1",
  "react-dom": "^18.3.1",
  "tailwindcss": "^3.4.15",
  "vite": "^5.4.11",
  "laravel-vite-plugin": "^1.0.6"
}
```

`ziggy-js` exposes the `route()` function client-side; the entry point
binds it to `window.route` (see below).

## Vite config — `vite.config.js`

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/merchant.css',
                'resources/js/merchant.jsx',
            ],
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
            ziggy: path.resolve(__dirname, 'vendor/tightenco/ziggy'),
        },
    },
});
```

The legacy `resources/sass/app.scss` and `resources/js/app.js` inputs were
dropped — `app.scss` referenced `~bootstrap/scss/bootstrap` which Vite
can't resolve, and neither was referenced by any blade.

## Tailwind — `tailwind.config.js`

- `darkMode: ['class']` — dark mode toggled via a `dark` class on `<html>`
- `content` scoped to `resources/views/merchant/**/*.blade.php` and
  `resources/js/**/*.{js,jsx,ts,tsx}` — the admin blade root also gets
  picked up by the JSX scan
- shadcn-style colour tokens: `background`, `foreground`, `primary`,
  `secondary`, `muted`, `accent`, `destructive`, `border`, `input`, `ring`,
  `popover`, `card`, `sidebar` (+ `sidebar-border` / `sidebar-accent`)
- `tailwindcss-animate` plugin for Radix transitions

## PostCSS — `postcss.config.js`

```js
export default {
    plugins: { tailwindcss: {}, autoprefixer: {} },
};
```

## CSS entry — `resources/css/merchant.css`

`@tailwind base/components/utilities`, plus `:root` and `.dark` blocks
defining every CSS variable Tailwind references.

## JS entry — `resources/js/merchant.jsx`

```jsx
import '../css/merchant.css';
import { createRoot, hydrateRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { route as ziggyRoute } from 'ziggy-js';

if (typeof window !== 'undefined') {
    window.route = (name, params, absolute) =>
        ziggyRoute(name, params, absolute, window.Ziggy);
}

const pages = import.meta.glob('./Pages/**/*.jsx');

createInertiaApp({
    resolve: (name) => pages[`./Pages/${name}.jsx`]().then((m) => m.default),
    setup: ({ el, App, props }) => {
        if (el.hasChildNodes()) hydrateRoot(el, <App {...props} />);
        else createRoot(el).render(<App {...props} />);
    },
    progress: { color: '#a21f5c' },
});
```

A single `merchant.jsx` bundle serves both admin and merchant Inertia
pages — the blade root just sets `$rootView` to `'admin.app'` or
`'merchant.app'` depending on URL prefix (see next section).

## Kernel middleware — `app/Http/Kernel.php`

```php
'web' => [
    \App\Http\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \App\Http\Middleware\VerifyCsrfToken::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
    \App\Http\Middleware\LanguageManager::class,
    \App\Http\Middleware\HandleInertiaRequests::class,  // <-- added
],
```

## `HandleInertiaRequests` — dynamic root view

```php
protected $rootView = 'merchant.app';

public function rootView(Request $request): string
{
    return str_starts_with(ltrim($request->path(), '/'), 'admin')
        ? 'admin.app'
        : $this->rootView;
}
```

Anything served at a URL starting with `admin/...` uses
`resources/views/admin/app.blade.php`; everything else uses
`resources/views/merchant/app.blade.php`. Both blades load the same JS
bundle but expose different `merchantBrand()` chrome.

`HandleInertiaRequests::share()` exposes:

- `auth.user` (id, name, email, image)
- `brand` (merchant overrides; null on the admin side)
- `impersonator` (when admin is signed in as a merchant user)
- `app.name`, `app.locale`
- `flash.success`, `flash.error`, `flash.message`
- `ziggy` (route table from `tightenco/ziggy`)

## Blade root views

Both root views (`merchant/app.blade.php` and `admin/app.blade.php` — the
latter cloned from the former) read the Vite manifest manually:

```php
$__viteManifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
$__cssEntry = $__viteManifest['resources/css/merchant.css']['file'] ?? null;
$__jsEntry  = $__viteManifest['resources/js/merchant.jsx']['file'] ?? null;
```

Reason: Stancl Tenancy's `asset_helper_tenancy` rewrites `@vite()` URLs to
`/tenancy/assets/...` which 404 on the tenant subdomain. Reading the
manifest manually and wrapping the path with `global_asset()` bypasses
that rewriter.

## Build commands

```bash
npm install
npm run build      # production
npm run dev        # HMR (rarely used here)
```

Builds drop into `public/build/`. After every build, `chown -R
www-data:www-data public/build` so php-fpm can read it.
