<?php

namespace App\Http\Controllers;

use App\Models\Inventory;

class InventoryController extends Controller
{
    public function stock()
    {
        $productId = request('product_id');
        $locationId = request('location_id');
        $variantId = request('variant_id');
        if ($variantId === 'undefined' || $variantId === 'null' || !$variantId) {
            $variantId = null;
        }

        $product = \App\Models\Product::find($productId);
        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found',
            ], 404);
        }

        if ($product->is_variable) {
            $variantStock = $product->getVariantStock();
            
            $totalQuantity = 0;
            if ($variantId === 'parent') {
                foreach ($variantStock as $locData) {
                    $totalQuantity += $locData['parent'];
                }
            } elseif ($variantId) {
                foreach ($variantStock as $locData) {
                    $totalQuantity += ($locData['variants'][$variantId] ?? 0);
                }
            } else {
                $totalQuantity = (int)Inventory::where('product_id', $productId)->sum('quantity');
            }

            if ($locationId) {
                $locData = $variantStock[$locationId] ?? null;
                if ($variantId === 'parent') {
                    $locationQuantity = $locData['parent'] ?? 0;
                } elseif ($variantId) {
                    $locationQuantity = $locData['variants'][$variantId] ?? 0;
                } else {
                    $locationQuantity = (int)Inventory::where('product_id', $productId)
                        ->where('location_id', $locationId)
                        ->value('quantity');
                }
            } else {
                $locationQuantity = $totalQuantity;
            }

            $breakdown = [];
            foreach ($variantStock as $locId => $locData) {
                if ($variantId === 'parent') {
                    $qty = $locData['parent'];
                } elseif ($variantId) {
                    $qty = $locData['variants'][$variantId] ?? 0;
                } else {
                    $qty = $locData['parent'];
                    foreach ($locData['variants'] as $vqty) {
                        $qty += $vqty;
                    }
                }
                if ($qty > 0) {
                    $breakdown[] = [
                        'location_name' => $locData['location_name'],
                        'quantity'      => $qty,
                    ];
                }
            }
        } else {
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
        }

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
