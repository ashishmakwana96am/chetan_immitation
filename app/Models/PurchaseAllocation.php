<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseAllocation extends Model
{
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
