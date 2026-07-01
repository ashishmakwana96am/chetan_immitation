<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'location_id',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
