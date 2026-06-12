<?php

namespace App\Http\Resources\v10\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Minimal user payload for admin / hub / incharge / super_admin sessions.
 * Avoids the merchant-only fields exposed by v10\UserResource (parcel totals,
 * cash amount, etc.).
 */
class AdminUserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'phone'       => (string) $this->mobile,
            'user_type'   => (int) $this->user_type,
            'role'        => $this->roleName(),
            'hub_id'      => $this->hub_id,
            'hub'         => $this->hub,
            'image'       => (string) $this->image,
            'address'     => $this->address,
            'status'      => (int) $this->status,
            'statusName'  => trans('status.' . $this->status),
            'created_at'  => optional($this->created_at)->format('d M Y, h:i A'),
        ];
    }

    private function roleName(): string
    {
        return match ((int) $this->user_type) {
            1 => 'admin',
            4 => 'incharge',
            5 => 'hub',
            6 => 'super_admin',
            default => 'unknown',
        };
    }
}
