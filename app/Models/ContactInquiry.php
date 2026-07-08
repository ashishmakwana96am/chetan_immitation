<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactInquiry extends Model
{
    use SoftDeletes, LogsActivity;

    protected static bool $logCreate = false;

    public function activityModule(): string
    {
        return 'Contact Enquiry';
    }

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
