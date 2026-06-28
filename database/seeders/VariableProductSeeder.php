<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\SubCategory;

class VariableProductSeeder extends Seeder
{
    public function run(): void
    {
        // Find categories
        $category = Category::where('name', 'Bangles & Kada')->first() ?? Category::first();
        $subCategory = SubCategory::where('name', 'Designer Kada')->first() ?? SubCategory::first();

        $earringsCategory = Category::where('name', 'Earrings & Jhumkas')->first() ?? Category::first();
        $earringsSub = SubCategory::where('name', 'Traditional Jhumkas')->first() ?? SubCategory::first();

        // Find attribute values
        $colorGold = AttributeValue::where('value', 'Gold')->first();
        $colorSilver = AttributeValue::where('value', 'Silver')->first();
        $colorGreen = AttributeValue::where('value', 'Green')->first();

        $sizeSmall = AttributeValue::where('value', 'Small')->first();
        $sizeMedium = AttributeValue::where('value', 'Medium')->first();
        $sizeLarge = AttributeValue::where('value', 'Large')->first();

        if ($colorGold && $colorSilver) {
            // 1. Premium Designer Kada
            $kada = Product::create([
                'name'            => 'Premium Designer Kada',
                'slug'            => 'premium-designer-kada-' . time(),
                'category_id'     => $category->id,
                'sub_category_id' => $subCategory->id,
                'sku'             => 'BAN-KAD-VAR',
                'description'     => 'This is a premium designer kada available in gold and silver finishes.',
                'purchase_price'  => 400,
                'sale_price'      => 900,
                'type'            => 'variable',
                'status'          => 1,
                'created_by'      => 1,
            ]);

            ProductVariant::create([
                'product_id' => $kada->id,
                'attribute_value_id' => $colorGold->id,
                'purchase_price' => 400,
                'sale_price' => 900,
                'status' => 1,
            ]);

            ProductVariant::create([
                'product_id' => $kada->id,
                'attribute_value_id' => $colorSilver->id,
                'purchase_price' => 380,
                'sale_price' => 850,
                'status' => 1,
            ]);

            if ($colorGreen) {
                ProductVariant::create([
                    'product_id' => $kada->id,
                    'attribute_value_id' => $colorGreen->id,
                    'purchase_price' => 420,
                    'sale_price' => 950,
                    'status' => 1,
                ]);
            }
        }

        if ($sizeSmall && $sizeMedium && $sizeLarge) {
            // 2. Traditional Kundan Earring
            $earring = Product::create([
                'name'            => 'Traditional Kundan Earring',
                'slug'            => 'traditional-kundan-earring-' . time(),
                'category_id'     => $earringsCategory->id,
                'sub_category_id' => $earringsSub->id,
                'sku'             => 'EAR-KUN-VAR',
                'description'     => 'Traditional royal Kundan earrings available in small, medium and large sizes.',
                'purchase_price'  => 200,
                'sale_price'      => 450,
                'type'            => 'variable',
                'status'          => 1,
                'created_by'      => 1,
            ]);

            ProductVariant::create([
                'product_id' => $earring->id,
                'attribute_value_id' => $sizeSmall->id,
                'purchase_price' => 150,
                'sale_price' => 350,
                'status' => 1,
            ]);

            ProductVariant::create([
                'product_id' => $earring->id,
                'attribute_value_id' => $sizeMedium->id,
                'purchase_price' => 200,
                'sale_price' => 450,
                'status' => 1,
            ]);

            ProductVariant::create([
                'product_id' => $earring->id,
                'attribute_value_id' => $sizeLarge->id,
                'purchase_price' => 250,
                'sale_price' => 550,
                'status' => 1,
            ]);
        }
    }
}
