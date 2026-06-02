<?php

namespace App\Http\Controllers;

use App\Models\Inventory;

class InventoryController extends Controller
{
    public function stock()
    {
        $productId = request('product_id');
        $locationId = request('location_id');

        $totalQuantity = (int)Inventory::where('product_id', $productId)->sum('quantity');

        if ($locationId) {
            $locationQuantity = (int)Inventory::where('product_id', $productId)
                ->where('location_id', $locationId)
                ->value('quantity');
        } else {
            $locationQuantity = $totalQuantity;
        }

        $breakdown = Inventory::where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->with('location')
            ->get()
            ->map(function ($inv) {
                return [
                    'location_name' => $inv->location->name ?? 'Unknown',
                    'quantity'      => $inv->quantity,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data'   => [
                'quantity'       => $locationQuantity,
                'total_quantity' => $totalQuantity,
                'breakdown'      => $breakdown,
            ],
        ]);
    }
}
