<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;

class ReportController extends Controller
{
    public function products()
    {
        $this->authorize('view reports');

        $categories = Category::where('status', 'active')->orderBy('name')->get();

        $products = Product::with(['category', 'primaryImage', 'inventories'])
            ->orderBy('name')
            ->get()
            ->map(function ($product) {
                return [
                    'id'             => $product->id,
                    'name'           => $product->name,
                    'sku'            => $product->sku,
                    'category'       => $product->category->name ?? '-',
                    'category_id'    => $product->category_id,
                    'purchase_price' => $product->purchase_price,
                    'sale_price'     => $product->sale_price,
                    'total_stock'    => $product->inventories->sum('quantity'),
                    'status'         => $product->status,
                ];
            });

        return view('reports.products', compact('products', 'categories'));
    }

    public function stockInventory()
    {
        $this->authorize('view reports');

        $locations  = Location::where('status', 'active')->orderBy('name')->get();
        $categories = Category::where('status', 'active')->orderBy('name')->get();

        $products = Product::with(['category', 'inventories.location'])
            ->orderBy('name')
            ->get()
            ->map(function ($product) use ($locations) {
                $stock = [];
                foreach ($locations as $location) {
                    $inventory            = $product->inventories->firstWhere('location_id', $location->id);
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
                    'status'      => $product->status,
                ];
            });

        return view('reports.stock-inventory', compact('products', 'locations', 'categories'));
    }
}
