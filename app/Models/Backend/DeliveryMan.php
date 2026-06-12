<?php

namespace App\Models\Backend;

use App\Enums\Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class DeliveryMan extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'delivery_man';
    protected $fillable = [
        'company_id', 'user_id', 'status',
        'delivery_charge', 'pickup_charge', 'return_charge',
        'opening_balance', 'current_balance',
        'driver_type', 'employee_number', 'joining_date', 'contract_end_date',
        'direct_manager_id', 'license_number', 'license_expiry', 'iqama_expiry',
        'bank_account_no', 'iban',
        'supplier_company_id', 'operational_area_id',
        'iqama_image_id', 'contract_image_id', 'promissory_note_image_id',
    ];

    protected $casts = [
        'joining_date'      => 'date',
        'contract_end_date' => 'date',
        'license_expiry'    => 'date',
        'iqama_expiry'      => 'date',
    ];

    public function supplierCompany()
    {
        return $this->belongsTo(SupplierCompany::class, 'supplier_company_id');
    }

    public function operationalArea()
    {
        return $this->belongsTo(OperationalArea::class, 'operational_area_id');
    }

    public function directManager()
    {
        return $this->belongsTo(User::class, 'direct_manager_id');
    }

    public function iqamaImage()
    {
        return $this->belongsTo(Upload::class, 'iqama_image_id');
    }

    public function contractImage()
    {
        return $this->belongsTo(Upload::class, 'contract_image_id');
    }

    public function promissoryNoteImage()
    {
        return $this->belongsTo(Upload::class, 'promissory_note_image_id');
    }

    /** True iff contract ends within the given number of days (default 30). */
    public function isContractExpiringSoon(int $days = 30): bool
    {
        if (!$this->contract_end_date) return false;
        return now()->lte($this->contract_end_date)
            && now()->diffInDays($this->contract_end_date, false) <= $days;
    }


    public function scopeOrderByDesc($query, $data)
    {
        $query->orderBy($data, 'desc');
    }
    /**
    * Activity Log
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('DeliveryMan')
        ->logOnly(['user.name', 'current_balance',])
        ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }

    // Get active row this model.
    public function scopeActive($query)
    {
        $query->where('status', Status::ACTIVE);
    }

    public function getMyStatusAttribute()
    {
        if($this->status == Status::ACTIVE){
            $status = '<span class="badge badge-pill badge-success">'.trans("status." . $this->status).'</span>';
        }else {
            $status = '<span class="badge badge-pill badge-danger">'.trans("status." . $this->status).'</span>';
        }
        return $status;
    }

    public function getDrivingLicenseImageAttribute()
    {
        if (!empty($this->uploadLicense->original['original']) && file_exists(public_path($this->uploadLicense->original['original']))) {
            return static_asset($this->uploadLicense->original['original']);
        }
        return static_asset('images/default/user.png');
    }

    public function uploadLicense()
    {
        return $this->belongsTo(Upload::class, 'driving_license_image_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hub()
    {
        return $this->belongsTo(Hub::class, 'hub_id', 'id');
    }

    
    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }
    
    
    public function deliveries()
{
    return $this->hasMany(ParcelEvent::class, 'delivery_man_id');
}

public function pickups()
{
    return $this->hasMany(ParcelEvent::class, 'pickup_man_id');
}




/**
 * All assigned shipments (ParcelEvents linked to this delivery man)
 */
public function assignedShipments()
{
    return $this->hasMany(ParcelEvent::class, 'delivery_man_id', 'id')
        ->with('parcel');
}

/**
 * Delivered shipments (where related Parcel.status = 9)
 */
public function deliveredShipments()
{
    return $this->hasMany(ParcelEvent::class, 'delivery_man_id', 'id')
        ->whereHas('parcel', function ($query) {
            $query->where('status', 9);
        })
        ->with('parcel');
}

/**
 * Pending shipments (where related Parcel.status != 9)
 */
public function pendingShipments()
{
    return $this->hasMany(ParcelEvent::class, 'delivery_man_id', 'id')
        ->whereHas('parcel', function ($query) {
            $query->where('status', '!=', 9);
        })
        ->with('parcel');
}




    
}
