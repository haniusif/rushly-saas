<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'merchant.app';

    public function rootView(Request $request): string
    {
        return str_starts_with(ltrim($request->path(), '/'), 'admin')
            ? 'admin.app'
            : $this->rootView;
    }

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $user ? [
                    'id'        => $user->id,
                    'name'      => $user->name ?? null,
                    'email'     => $user->email ?? null,
                    'image'     => $user->image ?? null,
                    'user_type' => (int) ($user->user_type ?? 0),
                ] : null,
                // Flat permission array — same source the server-side
                // hasPermission() helper reads (users.permissions JSON).
                // Sidebar/UI can filter menu entries by perm without
                // having to hit an endpoint. Server-side middleware
                // remains authoritative; this is UX-only.
                'permissions' => $user && is_array($user->permissions) ? array_values($user->permissions) : [],
            ],

            'brand' => fn () => $this->brand(),

            // Set when an admin is currently signed in as this user via the
            // impersonation feature. Drives the "you're viewing as X" banner.
            'impersonator' => fn () => $this->impersonator($request),

            'app' => [
                'name'   => config('app.name'),
                'locale' => app()->getLocale(),
            ],

            // Current tenant currency, shared so any page can render the mark
            // (SVG when present, e.g. the new SAR/AED symbols) via <CurrencySymbol/>.
            'currency' => fn () => $this->currencyMeta(),

            'flash' => [
                'success'     => fn () => $request->session()->get('success'),
                'error'       => fn () => $request->session()->get('error'),
                'warning'     => fn () => $request->session()->get('warning'),
                'message'     => fn () => $request->session()->get('message'),
                'errors_list' => fn () => $request->session()->get('errors_list'),
            ],

            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }

    /**
     * Current tenant currency as { code, symbol, svg }. code is what's stored
     * on general_settings; symbol/svg come from the matching currencies row so
     * the UI can render the real mark and fall back to the unicode symbol.
     */
    protected function currencyMeta(): array
    {
        $code = (string) optional(settings())->currency;
        $meta = ['code' => $code, 'symbol' => $code, 'svg' => null];

        if ($code !== '') {
            $row = \App\Models\Backend\Currency::where('code', $code)
                ->orWhere('symbol', $code)
                ->first();
            if ($row) {
                $meta['symbol'] = (string) $row->symbol;
                $meta['svg']    = $row->symbol_svg;
            }
        }

        return $meta;
    }

    protected function brand(): ?array
    {
        return merchantBrand();
    }

    protected function impersonator(Request $request): ?array
    {
        $id = $request->session()->get('impersonator_id');
        if (! $id) return null;
        $admin = \App\Models\User::find($id);
        if (! $admin) return null;
        return [
            'id'    => $admin->id,
            'name'  => $admin->name,
            'email' => $admin->email,
        ];
    }
}
