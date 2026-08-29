<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttributeValue extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'attribute_id',
        'value',
    ];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    public function getValueAttribute($value)
    {
        return html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
