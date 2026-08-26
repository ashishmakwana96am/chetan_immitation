<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoleHasPermission extends Model
{
    use SoftDeletes;

    protected $table = 'role_has_permissions';

    public $timestamps = false;

    const UPDATED_AT = null;

    protected $fillable = [
        'permission_id',
        'role_id',
    ];
}
