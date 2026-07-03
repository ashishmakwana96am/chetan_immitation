<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseAllocation extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'purchase_item_id',
        'location_id',
        'quantity',
    ];

    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
