<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Setting extends Model
{
    use SoftDeletes;

    public static function bootSoftDeletes()
    {
        if (!app()->runningInConsole() || (\Illuminate\Support\Facades\Schema::hasTable('settings') && \Illuminate\Support\Facades\Schema::hasColumn('settings', 'deleted_at'))) {
            static::addGlobalScope(new \Illuminate\Database\Eloquent\SoftDeletingScope);
        }
    }

    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();
        if (! $setting) {
            return $default;
        }
        $value = $setting->value;
        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    public static function setValue(string $key, mixed $value): void
    {
        $stored = is_array($value) ? json_encode($value) : $value;
        self::updateOrCreate(['key' => $key], ['value' => $stored]);
    }
}
