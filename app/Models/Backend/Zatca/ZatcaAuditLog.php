<?php

namespace App\Models\Backend\Zatca;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ZatcaAuditLog extends Model
{
    protected $table = 'zatca_audit_logs';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function scopeCompanywise(Builder $q): Builder
    {
        return $q->where('company_id', settings('company_id'));
    }
}
