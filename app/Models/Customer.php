<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    use SoftDeletes, LogsActivity;

    protected static array $activityHidden = ['otp'];

    const STATUS_ACTIVE = 1;

    const STATUS_INACTIVE = 2;

    protected $fillable = [
        'location_id',
        'name',
        'email',
        'gst_no',
        'state',
        'address',
        'password',
        'avatar',
        'is_website',
        'status',
        'is_credit_customer',
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
            'is_credit_customer' => 'boolean',
            'password' => 'hashed',
            'otp_expires_at' => 'datetime',
        ];
    }

    public function customerBalance()
    {
        return $this->hasOne(CustomerBalance::class);
    }

    public function getBalanceAttribute()
    {
        $bal = $this->customerBalance()->first();
        return $bal ? (float) $bal->balance : 0.00;
    }

    public function getCashBalanceAttribute()
    {
        $bal = $this->customerBalance()->first();
        return $bal ? (float) $bal->cash_balance : 0.00;
    }

    public function getBankBalanceAttribute()
    {
        $bal = $this->customerBalance()->first();
        return $bal ? (float) $bal->bank_balance : 0.00;
    }

    public function setBalanceAttribute($value)
    {
        $bal = $this->customerBalance()->firstOrCreate([], [
            'balance' => 0.00,
            'cash_balance' => 0.00,
            'bank_balance' => 0.00,
        ]);
        $bal->update(['balance' => $value]);
    }

    public function setCashBalanceAttribute($value)
    {
        $bal = $this->customerBalance()->firstOrCreate([], [
            'balance' => 0.00,
            'cash_balance' => 0.00,
            'bank_balance' => 0.00,
        ]);
        $bal->update(['cash_balance' => $value]);
    }

    public function setBankBalanceAttribute($value)
    {
        $bal = $this->customerBalance()->firstOrCreate([], [
            'balance' => 0.00,
            'cash_balance' => 0.00,
            'bank_balance' => 0.00,
        ]);
        $bal->update(['bank_balance' => $value]);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function balanceTransactions()
    {
        return $this->hasMany(CustomerBalanceTransaction::class);
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
        static::created(function (Customer $customer) {
            $customer->customerBalance()->firstOrCreate([], [
                'balance'      => 0.00,
                'cash_balance' => 0.00,
                'bank_balance' => 0.00,
            ]);
        });

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
