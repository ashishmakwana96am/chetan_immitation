<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    use SoftDeletes;

    const STATUS_ACTIVE = 1;

    const STATUS_INACTIVE = 2;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'is_website',
        'status',
        'otp',
        'otp_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_website' => 'boolean',
            'password' => 'hashed',
            'otp_expires_at' => 'datetime',
        ];
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function phones()
    {
        return $this->hasMany(CustomerPhone::class);
    }

    /**
     * Virtual "phone" attribute — reads/writes the customer's primary
     * (first) number in the customer_phones table so existing code that
     * referred to the old customers.phone column keeps working.
     */
    protected function phone(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->relationLoaded('phones')
                ? $this->phones->sortBy('id')->first()?->phone
                : $this->phones()->oldest('id')->value('phone'),
        );
    }

    public function syncPrimaryPhone(?string $phone): void
    {
        if (!$phone) {
            return;
        }

        $first = $this->phones()->oldest('id')->first();
        if ($first) {
            $first->update(['phone' => $phone]);
        } else {
            $this->phones()->create(['phone' => $phone]);
        }
    }

    protected static function booted(): void
    {
        static::deleting(function (Customer $customer) {
            if (!$customer->isForceDeleting()) {
                $customer->phones()->delete();
            }
        });

        static::restoring(function (Customer $customer) {
            $customer->phones()->onlyTrashed()->restore();
        });
    }
}
