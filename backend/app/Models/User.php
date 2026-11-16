<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'password',
        'status',
        'activation_token',
        'email_verified_at',
        'deletion_requested_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'activation_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'deletion_requested_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    public function wishlist(): HasOne
    {
        return $this->hasOne(Wishlist::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function getFirstNameAttribute(): string
    {
        $parts = explode(' ', (string) $this->name, 2);
        return $parts[0] ?? '';
    }

    public function getLastNameAttribute(): string
    {
        $parts = explode(' ', (string) $this->name, 2);
        return $parts[1] ?? '';
    }

    public function getRoleAttribute(): ?string
    {
        return $this->roles->first()?->name ?? 'Customer';
    }

    public function getRoleSlugAttribute(): ?string
    {
        return $this->roles->first()?->slug ?? 'customer';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCustomer(): bool
    {
        return $this->roles()->where('slug', UserRole::CUSTOMER->value)->exists();
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : func_get_args();
        return $this->roles()->whereIn('slug', $roles)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', fn ($q) => $q->where('slug', $permission))
            ->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->roles()->where('slug', UserRole::SUPER_ADMIN->value)->exists();
    }

    public function isAdminStaff(): bool
    {
        return $this->roles()
            ->whereIn('slug', [
                UserRole::SUPER_ADMIN->value,
                UserRole::ADMIN->value,
                UserRole::STORE_MANAGER->value,
                UserRole::PRODUCT_MANAGER->value,
                UserRole::ORDER_MANAGER->value,
                UserRole::CUSTOMER_SUPPORT->value,
                UserRole::INVENTORY_MANAGER->value,
            ])
            ->exists();
    }

    public function defaultShippingAddress()
    {
        return $this->addresses()->where('is_default_shipping', true)->first()
            ?? $this->addresses()->first();
    }

    public function defaultBillingAddress()
    {
        return $this->addresses()->where('is_default_billing', true)->first()
            ?? $this->addresses()->first();
    }
}
