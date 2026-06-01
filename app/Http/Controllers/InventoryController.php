<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;

class InventoryController extends Controller
{
    public function index()
    {
        $this->authorize('view inventory');

        $user = auth()->user();
        if ($user->location_id && $user->type !== 'super-admin') {
            $locations = Location::where('id', $user->location_id)->get();
        } else {
            $locations = Location::where('status', 'active')->orderBy('name')->get();
        }
        $categories = Category::where('status', 'active')->orderBy('name')->get();

        // Get all products with their inventory per location
        $products = Product::with(['category', 'inventories' => function($q) use ($user) {
                $q->when($user->location_id && $user->type !== 'super-admin', fn($sub) => $sub->where('location_id', $user->location_id));
            }, 'inventories.location'])
            ->orderBy('name')
            ->get()
            ->map(function ($product) use ($locations) {
                $stock = [];
                foreach ($locations as $location) {
                    $inventory = $product->inventories->firstWhere('location_id', $location->id);
                    $stock[$location->id] = $inventory ? $inventory->quantity : 0;
                }
                return [
                    'id'          => $product->id,
                    'name'        => $product->name,
                    'sku'         => $product->sku,
                    'category'    => $product->category->name ?? '-',
                    'category_id' => $product->category_id,
                    'stock'       => $stock,
                    'total'       => array_sum($stock),
                ];
            });

        return view('inventory.index', compact('products', 'locations', 'categories'));
    }

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
