<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourStep extends Model
{
    use HasFactory;

    protected $table = 'tour_steps';

    protected $fillable = [
        'tour_id', 'sort_order', 'target', 'placement',
        'spotlight_padding', 'translations', 'action',
    ];

    protected $casts = [
        'target'            => 'array',
        'translations'      => 'array',
        'action'            => 'array',
        'sort_order'        => 'integer',
        'spotlight_padding' => 'integer',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    /**
     * Pick the right locale bundle for this step, falling back to en.
     */
    public function localizedContent(?string $locale = null): array
    {
        $locale = $locale ?: app()->getLocale();
        $bundle = $this->translations ?? [];
        return $bundle[$locale] ?? $bundle['en'] ?? ['title' => '', 'body' => ''];
    }
}
