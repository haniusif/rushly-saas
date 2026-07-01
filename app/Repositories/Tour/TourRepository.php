<?php

namespace App\Repositories\Tour;

use App\Models\Backend\Tour;
use App\Models\Backend\TourEvent;
use App\Models\Backend\UserTourProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TourRepository implements TourRepositoryInterface
{
    /**
     * Per-request memoization. The query is cheap (indexed lookups on
     * three small tables) so we don't reach for a persistent cache —
     * stancl's tenancy CacheManager wraps Cache::* with tagged stores,
     * which fails against the file driver.
     */
    protected array $memo = [];

    public function forUser(User $user, ?string $locale = null): array
    {
        $locale = $locale ?: app()->getLocale();
        $key    = $this->memoKey($user, $locale);
        if (isset($this->memo[$key])) return $this->memo[$key];

        $result = (function () use ($user, $locale) {
            $companyId = settings()->id;
            $userType  = (int) $user->user_type;

            // Pull both system (company_id=null) and tenant tours in one query,
            // filter by role_scope + active, then dedupe: tenant row wins over
            // system row with the same key.
            $rows = Tour::with('steps')
                ->where('is_active', true)
                ->where(function ($q) use ($companyId) {
                    $q->whereNull('company_id')->orWhere('company_id', $companyId);
                })
                ->where(function ($q) use ($userType) {
                    $q->whereNull('role_scope')
                      ->orWhereRaw('JSON_CONTAINS(role_scope, ?)', [json_encode($userType)]);
                })
                ->orderByRaw('company_id IS NULL')  // tenant rows first
                ->get();

            $byKey = [];
            foreach ($rows as $tour) {
                if (isset($byKey[$tour->key])) continue; // tenant already claimed this key
                $byKey[$tour->key] = $tour;
            }

            $progressByKey = UserTourProgress::where('user_id', $user->id)
                ->whereIn('tour_key', array_keys($byKey))
                ->get()
                ->keyBy('tour_key');

            return array_values(array_map(function (\App\Models\Backend\Tour $tour) use ($locale, $progressByKey) {
                $progress = $progressByKey->get($tour->key);
                return [
                    'id'           => $tour->id,
                    'key'          => $tour->key,
                    'module'       => $tour->module,
                    'title'        => $tour->title,
                    'description'  => $tour->description,
                    'version'      => (int) $tour->version,
                    'auto_start'   => (bool) $tour->auto_start,
                    'trigger_route'=> $tour->trigger_route,
                    'meta'         => $tour->meta ?? [],
                    'progress'     => $progress ? [
                        'status'       => $progress->status,
                        'current_step' => (int) $progress->current_step,
                        'tour_version' => (int) $progress->tour_version,
                    ] : null,
                    'steps'        => $tour->steps->map(function ($step) use ($locale) {
                        $content = $step->localizedContent($locale);
                        return [
                            'id'                => $step->id,
                            'sort_order'        => (int) $step->sort_order,
                            'target'            => $step->target,
                            'placement'         => $step->placement,
                            'spotlight_padding' => (int) $step->spotlight_padding,
                            'title'             => $content['title'] ?? '',
                            'body'              => $content['body']  ?? '',
                            'action'            => $step->action,
                        ];
                    })->values()->all(),
                ];
            }, $byKey));
        })();

        $this->memo[$key] = $result;
        return $result;
    }

    public function findByKey(string $key): ?Tour
    {
        $companyId = settings()->id;
        return Tour::with('steps')
            ->where('key', $key)
            ->where('is_active', true)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->orderByRaw('company_id IS NULL')
            ->first();
    }

    public function saveProgress(User $user, string $key, int $version, string $status, int $currentStep): void
    {
        $companyId = settings()->id;

        $existing = UserTourProgress::where('user_id', $user->id)
            ->where('tour_key', $key)
            ->where('tour_version', $version)
            ->first();

        $now = now();
        if ($existing) {
            $existing->status        = $status;
            $existing->current_step  = $currentStep;
            if ($status === UserTourProgress::STATUS_COMPLETED && !$existing->completed_at) {
                $existing->completed_at = $now;
            }
            $existing->save();
        } else {
            UserTourProgress::create([
                'user_id'      => $user->id,
                'company_id'   => $companyId,
                'tour_key'     => $key,
                'tour_version' => $version,
                'status'       => $status,
                'current_step' => $currentStep,
                'started_at'   => $now,
                'completed_at' => $status === UserTourProgress::STATUS_COMPLETED ? $now : null,
            ]);
        }

        // Reset per-request memo so a subsequent forUser() reflects the new progress.
        $this->memo = [];
    }

    public function logEvent(User $user, string $key, string $event, ?int $stepIndex = null, ?int $durationMs = null, array $meta = []): void
    {
        TourEvent::create([
            'user_id'     => $user->id,
            'company_id'  => settings()->id,
            'tour_key'    => $key,
            'event'       => $event,
            'step_index'  => $stepIndex,
            'duration_ms' => $durationMs,
            'meta'        => $meta,
            'created_at'  => now(),
        ]);
    }

    protected function memoKey(User $user, string $locale): string
    {
        $permHash = substr(md5(is_array($user->permissions) ? implode(',', $user->permissions) : (string) $user->permissions), 0, 8);
        return sprintf('%d.%d.%s.%s', settings()->id, $user->id, $locale, $permHash);
    }
}
