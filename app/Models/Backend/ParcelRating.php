<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Customer rating for a delivered parcel.
 *
 * Captured via the public signed-URL endpoint (mobile-friendly Blade page),
 * but the schema accepts admin/merchant entries too — disambiguated by the
 * `source` column.
 */
class ParcelRating extends Model
{
    use HasFactory;

    protected $table = 'parcel_ratings';

    protected $fillable = [
        'company_id', 'parcel_id', 'deliveryman_id', 'merchant_id',
        'customer_phone', 'rating', 'comment', 'source',
    ];

    protected $casts = [
        'rating'         => 'integer',
        'deliveryman_id' => 'integer',
        'merchant_id'    => 'integer',
        'parcel_id'      => 'integer',
        'company_id'     => 'integer',
    ];

    public function scopeCompanywise($query)
    {
        return $query->where('company_id', settings()->id);
    }

    public function parcel()
    {
        return $this->belongsTo(Parcel::class, 'parcel_id');
    }

    public function deliveryman()
    {
        return $this->belongsTo(DeliveryMan::class, 'deliveryman_id');
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }
}
