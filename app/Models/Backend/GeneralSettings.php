<?php

namespace App\Models\Backend;

use App\Models\Backend\Superadmin\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class GeneralSettings extends Model
{
    use HasFactory,LogsActivity;


    protected $fillable = [
        'phone',
        'name',
        'tracking_id',
        'details',
        'prefix',
        'purchase_code',
        'timezone',
    ];

    public function getActivitylogOptions(): LogOptions
    {

        $logAttributes = [

            'phone',
            'name',
            'tracking_id',
            'details',
            'prefix'
        ];
        return LogOptions::defaults()
        ->useLogName('General Settings')
        ->logOnly($logAttributes)
            ->setDescriptionForEvent(fn(string $eventName) => "{$eventName}");
    }

    // Get single row in Upload table.
    public function rxlogo()
    {
        return $this->belongsTo(Upload::class, 'logo', 'id');
    }
    public function lightlogo()
    {
        return $this->belongsTo(Upload::class, 'light_logo', 'id');
    }
    public function rxfavicon()
    {
        return $this->belongsTo(Upload::class, 'favicon', 'id');
    }

    /**
     * The upload row's `original` column is a plain varchar (relative path);
     * historically the accessors did `$this->rxlogo->original['original']`,
     * which returned the first character in PHP 8.2 and throws TypeError on
     * PHP 8.3+. We now read the string directly.
     */
    public function getLogoImageAttribute()
    {
        $path = optional($this->rxlogo)->original;
        if (!empty($path) && file_exists(public_path($path))) {
            return static_asset($path);
        }
        return static_asset('images/default/logo.png');
    }

    public function getPLogoImageAttribute()
    {
        $path = optional($this->rxlogo)->original;
        if (!empty($path) && file_exists(public_path($path))) {
            return public_path($path);
        }
        return public_path('images/default/logo.png');
    }

    public function getLightLogoImageAttribute()
    {
        $path = optional($this->lightlogo)->original;
        if (!empty($path) && file_exists(public_path($path))) {
            return static_asset($path);
        }
        return static_asset('images/default/light-logo.png');
    }

    public function getFaviconImageAttribute()
    {
        $path = optional($this->rxfavicon)->original;
        if (!empty($path) && file_exists(public_path($path))) {
            return static_asset($path);
        }
        return static_asset('images/default/favicon.png');
    }

    public function createdBy(){
        return $this->belongsTo(User::class,'created_by','id');
    }

    public function excenseRate(){
        return $this->belongsTo(Currency::class,'currency','symbol');
    }

    public function plan(){
        return $this->belongsTo(Plan::class,'plan_id','id');
    }

    public function subscription(){
        return $this->belongsTo(Subscription::class,'subscription_id','id');
    }
 
}
