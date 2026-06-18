<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactInquiry extends Model
{
    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'subject',
        'message',
        'emailed_at',
    ];

    protected function casts(): array
    {
        return [
            'emailed_at' => 'datetime',
        ];
    }
}
