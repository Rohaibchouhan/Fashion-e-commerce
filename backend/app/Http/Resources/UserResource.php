<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $addresses = $this->relationLoaded('addresses')
            ? $this->addresses
            : $this->addresses()->get();

        $roles = $this->relationLoaded('roles')
            ? $this->roles
            : $this->roles()->get();

        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'status' => $this->status,
            'role' => $roles->first()?->name ?? 'Customer',
            'roleSlug' => $roles->first()?->slug ?? 'customer',
            'roles' => $roles->pluck('slug')->toArray(),
            'createdAt' => $this->created_at?->toISOString(),
            'emailVerifiedAt' => $this->email_verified_at?->toISOString(),
            'ordersCount' => (int) ($this->orders_count ?? ($this->relationLoaded('orders') ? $this->orders->count() : 0)),
            'ordersTotal' => (float) ($this->orders_sum_total ?? 0),
            'addresses' => $addresses->map(fn ($addr) => [
                'id' => (string) $addr->id,
                'firstName' => $addr->first_name,
                'lastName' => $addr->last_name,
                'street' => $addr->street,
                'apartment' => $addr->apartment,
                'city' => $addr->city,
                'state' => $addr->state,
                'zipCode' => $addr->zip_code,
                'country' => $addr->country,
                'phone' => $addr->phone,
                'isDefaultShipping' => (bool) $addr->is_default_shipping,
                'isDefaultBilling' => (bool) $addr->is_default_billing,
            ])->toArray(),
        ];
    }
}
