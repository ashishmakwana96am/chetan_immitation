<?php

namespace App\Http\Controllers;

use App\Models\Inventory;

class InventoryController extends Controller
{
    public function stock()
    {
        $inventory = Inventory::where('product_id', request('product_id'))
            ->where('location_id', request('location_id'))
            ->first();

        return response()->json([
            'status' => 'success',
            'data'   => ['quantity' => $inventory ? $inventory->quantity : 0],
        ]);
    }
}
