<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Banner extends Model
{
    use SoftDeletes, LogsActivity;

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 2;

    protected $fillable = [
        'image',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public function getImageUrlAttribute()
    {
        return $this->image ? asset('uploads/' . $this->image) : null;
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
