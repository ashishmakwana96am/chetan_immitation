<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModelHasRole extends Model
{
    use SoftDeletes;

    protected $table = 'model_has_roles';

    public $timestamps = false;

    const UPDATED_AT = null;

    protected $fillable = [
        'role_id',
        'model_type',
        'model_id',
    ];
}
