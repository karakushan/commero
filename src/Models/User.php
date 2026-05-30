<?php

namespace Commero\Models;

use Commero\Support\Phone;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'first_name',
    'last_name',
    'phone',
    'email',
    'gender',
    'birthday',
    'password',
    'delivery_shipping_method_id',
    'delivery_city_ref',
    'delivery_city_name',
    'delivery_warehouse_ref',
    'delivery_warehouse_name',
    'delivery_street',
    'delivery_house',
    'delivery_apartment',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected string $guard_name = 'web';

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            $fullName = trim(implode(' ', array_filter([
                $user->first_name,
                $user->last_name,
            ])));

            if ($fullName !== '') {
                $user->name = $fullName;
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birthday' => 'date',
            'password' => 'hashed',
        ];
    }

    protected function phone(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => Phone::normalize($value),
        );
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'admin') {
            return false;
        }

        return $this->hasAnyRole(['admin', 'manager', 'editor']);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function wishlistProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'wishlist_items')
            ->withTimestamps()
            ->orderByPivot('created_at', 'desc');
    }

    public function productWaitlistSubscriptions(): HasMany
    {
        return $this->hasMany(ProductWaitlistSubscription::class)->orderByDesc('created_at');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->orderByDesc('created_at');
    }
}
