<?php

namespace App\Http\Controllers\Api\V10;

use App\Http\Controllers\Controller;
use App\Models\Backend\UserTourProgress;
use App\Models\Backend\TourEvent;
use App\Repositories\Tour\TourRepositoryInterface;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * JSON endpoints consumed by the frontend tour engine.
 * All under the tenant-init middleware group.
 */
class TourController extends Controller
{
    use ApiReturnFormatTrait;

    public function __construct(protected TourRepositoryInterface $repo) {}

    /**
     * GET /api/v10/tours/for-me
     * Returns tours applicable to the current user in the current tenant.
     */
    public function forMe(Request $request)
    {
        $user = Auth::user();
        if (! $user) return response()->json(['tours' => []]);

        $tours = $this->repo->forUser($user, $request->query('locale'));

        return response()->json([
            'tours'           => $tours,
            'first_login'     => $user->first_login_at === null,
            'user_type'       => (int) $user->user_type,
            'locale'          => app()->getLocale(),
        ]);
    }

    /**
     * POST /api/v10/tours/{key}/progress
     * Body: { status, current_step, version }
     */
    public function saveProgress(Request $request, string $key)
    {
        $user = Auth::user();
        if (! $user) abort(401);

        $request->validate([
            'status'       => 'required|in:started,completed,skipped,dismissed',
            'current_step' => 'required|integer|min:0',
            'version'      => 'required|integer|min:1',
        ]);

        $this->repo->saveProgress(
            $user,
            $key,
            (int) $request->input('version'),
            $request->input('status'),
            (int) $request->input('current_step'),
        );

        return response()->json(['ok' => true]);
    }

    /**
     * POST /api/v10/tours/{key}/event
     * Body: { event, step_index?, duration_ms?, meta? }
     */
    public function logEvent(Request $request, string $key)
    {
        $user = Auth::user();
        if (! $user) abort(401);

        $request->validate([
            'event'       => 'required|in:started,step_forward,step_back,skipped,completed,dismissed,element_missing',
            'step_index'  => 'nullable|integer|min:0',
            'duration_ms' => 'nullable|integer|min:0',
            'meta'        => 'nullable|array',
        ]);

        $this->repo->logEvent(
            $user,
            $key,
            $request->input('event'),
            $request->filled('step_index')  ? (int) $request->input('step_index')  : null,
            $request->filled('duration_ms') ? (int) $request->input('duration_ms') : null,
            (array) $request->input('meta', []),
        );

        return response()->json(['ok' => true]);
    }
}
