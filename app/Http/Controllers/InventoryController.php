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
                foreach ($variantStock as $locData) {
                    $totalQuantity += array_sum($locData['variants']);
                }
            }

            if ($locationId) {
                $locData = $variantStock[$locationId] ?? null;
                if ($variantId === 'parent') {
                    $locationQuantity = $locData['parent'] ?? 0;
                } elseif ($variantId) {
                    $locationQuantity = $locData['variants'][$variantId] ?? 0;
                } else {
                    $locationQuantity = array_sum($locData['variants'] ?? []);
                }
            } else {
                $locationQuantity = $totalQuantity;
            }

            $breakdown = [];
            foreach ($variantStock as $locId => $locData) {
                // If filtering by location, only show that location's breakdown
                if ($locationId && (int)$locId !== (int)$locationId) {
                    continue;
                }
                if ($variantId === 'parent') {
                    $qty = $locData['parent'];
                } elseif ($variantId) {
                    $qty = $locData['variants'][$variantId] ?? 0;
                } else {
                    $qty = array_sum($locData['variants'] ?? []);
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

            $inventoryQuery = Inventory::where('product_id', $productId)
                ->where('quantity', '>', 0)
                ->with('location');

            // If filtering by location, only show that location's breakdown
            if ($locationId) {
                $inventoryQuery->where('location_id', $locationId);
            }

            $breakdown = $inventoryQuery->get()->map(function ($inv) {
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
