<?php

namespace Database\Seeders;

use App\Models\Backend\Tour;
use App\Models\Backend\TourStep;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Idempotently seed system tours from JSON files in database/seeders/tours/.
 * Rows are marked as system (company_id=null); tenant overrides can be
 * created via the admin manager UI without touching these.
 *
 * Run standalone:
 *   php artisan db:seed --class=Database\\Seeders\\TourSeeder
 */
class TourSeeder extends Seeder
{
    public function run(): void
    {
        $dir = database_path('seeders/tours');
        if (! is_dir($dir)) return;

        foreach (File::glob($dir . '/*.json') as $path) {
            $doc = json_decode(File::get($path), true);
            if (! is_array($doc) || empty($doc['key'])) {
                $this->command->warn("Skipping malformed tour JSON: {$path}");
                continue;
            }

            $tour = Tour::updateOrCreate(
                ['company_id' => null, 'key' => $doc['key']],
                [
                    'module'        => $doc['module']        ?? null,
                    'title'         => $doc['title']         ?? $doc['key'],
                    'description'   => $doc['description']   ?? null,
                    'role_scope'    => $doc['role_scope']    ?? null,
                    'meta'          => $doc['meta']          ?? [],
                    'version'       => (int) ($doc['version'] ?? 1),
                    'is_active'     => (bool) ($doc['is_active'] ?? true),
                    'auto_start'    => (bool) ($doc['auto_start'] ?? false),
                    'trigger_route' => $doc['trigger_route'] ?? null,
                ]
            );

            $tour->steps()->delete();
            foreach (array_values($doc['steps'] ?? []) as $i => $step) {
                TourStep::create([
                    'tour_id'           => $tour->id,
                    'sort_order'        => $i,
                    'target'            => (array) ($step['target'] ?? []),
                    'placement'         => $step['placement'] ?? 'auto',
                    'spotlight_padding' => (int) ($step['spotlight_padding'] ?? 8),
                    'translations'      => (array) ($step['translations'] ?? []),
                    'action'            => $step['action'] ?? null,
                ]);
            }

            $this->command->info("✓ Seeded tour: {$doc['key']} (" . count($doc['steps'] ?? []) . " steps)");
        }
    }
}
