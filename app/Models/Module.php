<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    use SoftDeletes;

    public static function bootSoftDeletes()
    {
        if (!app()->runningInConsole() || (\Illuminate\Support\Facades\Schema::hasTable('modules') && \Illuminate\Support\Facades\Schema::hasColumn('modules', 'deleted_at'))) {
            static::addGlobalScope(new \Illuminate\Database\Eloquent\SoftDeletingScope);
        }
    }

    protected $fillable = [
        'parent_id',
        'name',
        'icon',
        'route',
        'active_pattern',
        'permission',
        'sort_order',
    ];

    public function parent()
    {
        return $this->belongsTo(Module::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Module::class, 'parent_id')->orderBy('sort_order');
    }
}
