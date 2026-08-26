<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModelHasPermission extends Model
{
    use SoftDeletes;

    protected $table = 'model_has_permissions';

    public $timestamps = false;

    const UPDATED_AT = null;

    protected $fillable = [
        'permission_id',
        'model_type',
        'model_id',
    ];
}
