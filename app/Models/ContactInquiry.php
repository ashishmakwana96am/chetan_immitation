<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactInquiry extends Model
{
    use SoftDeletes;
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
