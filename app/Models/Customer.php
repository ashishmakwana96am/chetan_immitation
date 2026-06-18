<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    const STATUS_ACTIVE   = 1;
    const STATUS_INACTIVE = 2;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'is_website',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_website' => 'boolean',
            'password'   => 'hashed',
        ];
    }
}
