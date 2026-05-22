<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;

class ParcelStatusMapping extends Model
{
    protected $table = 'parcel_statuses_mapping';

    protected $fillable = [
        'company_name_en','company_name_ar',
        'external_status_code','external_status_en','external_status_ar',
        'parcel_status_id','notes_en','notes_ar'
    ];

    protected static function norm(?string $s): string
    {
        $s = (string) $s;
        $s = trim(mb_strtolower($s));
        $s = preg_replace('/\s+/u', ' ', $s);             // normalize spaces
        $s = preg_replace('/[^\p{L}\p{N}\s]/u', '', $s);  // strip punctuation
        return $s ?? '';
    }

    /**
     * ابحث عن سطر خريطة يربط حالة خارجية (كود/نص) بحالة داخلية.
     */
    public static function mapExternalToInternal(
        ?string $company,
        ?string $extCode,
        ?string $extTextEn,
        ?string $extTextAr = null
    ): ?self {
        if (!$company) return null;

        $companyNorm = self::norm($company);
        $baseQ = self::query()->where(function($q) use ($companyNorm) {
            $q->whereRaw('LOWER(REPLACE(company_name_en," ","")) = ?', [str_replace(' ', '', $companyNorm)])
              ->orWhereRaw('LOWER(REPLACE(company_name_ar," ","")) = ?', [str_replace(' ', '', $companyNorm)]);
        });

        // 1) المطابقة بالكود (الأقوى)
        if ($extCode) {
            $m = (clone $baseQ)->where('external_status_code', $extCode)->first();
            if ($m) return $m;
        }

        // 2) المطابقة بالنص الإنجليزي (exact ثم like)
        if ($extTextEn) {
            $needle = mb_strtolower(trim($extTextEn));
            $m = (clone $baseQ)->whereRaw('LOWER(external_status_en) = ?', [$needle])->first();
            if ($m) return $m;

            $m = (clone $baseQ)->whereRaw('LOWER(external_status_en) LIKE ?', ['%'.$needle.'%'])->first();
            if ($m) return $m;
        }

        // 3) المطابقة بالنص العربي (exact ثم like)
        if ($extTextAr) {
            $needle = mb_strtolower(trim($extTextAr));
            $m = (clone $baseQ)->whereRaw('LOWER(external_status_ar) = ?', [$needle])->first();
            if ($m) return $m;

            $m = (clone $baseQ)->whereRaw('LOWER(external_status_ar) LIKE ?', ['%'.$needle.'%'])->first();
            if ($m) return $m;
        }

        return null;
    }
}
