<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'merchant.app';

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
                    'id'    => $user->id,
                    'name'  => $user->name ?? null,
                    'email' => $user->email ?? null,
                    'image' => $user->image ?? null,
                ] : null,
            ],

            'brand' => fn () => $this->brand(),

            // Set when an admin is currently signed in as this user via the
            // impersonation feature. Drives the "you're viewing as X" banner.
            'impersonator' => fn () => $this->impersonator($request),

            'app' => [
                'name'   => config('app.name'),
                'locale' => app()->getLocale(),
            ],

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
                'message' => fn () => $request->session()->get('message'),
            ],

            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
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
