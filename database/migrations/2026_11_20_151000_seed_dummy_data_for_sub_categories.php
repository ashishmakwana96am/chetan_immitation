<?php

use App\Models\SubCategory;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $subCategories = SubCategory::all();

        foreach ($subCategories as $subCategory) {
            $name = trim($subCategory->name);

            $description = '<p>Exquisite ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ' crafted with premium quality materials and superior finish. Lightweight, skin-friendly, and perfect for everyday wear as well as weddings, parties, and festive occasions. Designed to add elegance and timeless beauty to your attire.</p>';

            $info = '<ul><li>Premium Quality Craftsmanship</li><li>Skin Friendly &amp; Hypoallergenic</li><li>Comfortable for Daily &amp; Party Wear</li><li>Tarnish Resistant Protective Finish</li><li>Long-lasting Polish &amp; Radiant Shine</li><li>Perfect Gift for Special Occasions</li></ul>';

            $highlights = '<p>✓ Premium Finish</p><p>✓ Elegant &amp; Trendy Design</p><p>✓ Lightweight &amp; Comfortable</p><p>✓ Skin Friendly Material</p><p>✓ Anti-Tarnish Coating</p><p>✓ Luxury Look at Affordable Value</p><p>✓ Perfect for Weddings &amp; Festive Wear</p>';

            $subCategory->update([
                'description'            => $description,
                'additional_information' => $info,
                'product_highlights'     => $highlights,
            ]);
        }
    }

    public function down(): void
    {
    }
};
