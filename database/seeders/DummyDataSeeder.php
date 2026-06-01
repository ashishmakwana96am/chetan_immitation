<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;
use App\Models\PurchaseAllocation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Inventory;
use App\Models\User;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Safe Cleanup of Operational Data
        Schema::disableForeignKeyConstraints();
        PurchaseAllocation::truncate();
        PurchaseItem::truncate();
        PurchaseInvoice::truncate();
        OrderItem::truncate();
        Order::truncate();
        Inventory::truncate();
        ProductImage::truncate();
        Product::truncate();
        SubCategory::truncate();
        Category::truncate();
        Supplier::truncate();
        Customer::truncate();
        Location::truncate();
        Schema::enableForeignKeyConstraints();

        $admin = User::first();
        $adminId = $admin ? $admin->id : 1;

        // 2. Generate 15 Good Locations (Gujarat Cities & Hubs)
        $locationNames = [
            'Ahmedabad Main Branch', 'Surat Retail Outlet', 'Rajkot Imitation Hub', 
            'Vadodara Central Store', 'Jamnagar Showroom', 'Bhavnagar Plaza', 
            'Gandhinagar Corporate', 'Anand Town Branch', 'Nadiad Showroom', 
            'Mehsana Retail Hub', 'Morbi Design Center', 'Bhuj Crafts Outlet', 
            'Navsari Bazaar Store', 'Bharuch Trade Hub', 'Valsad Station Road'
        ];

        $locations = [];
        foreach ($locationNames as $index => $name) {
            $locations[] = Location::create([
                'name'       => $name,
                'slug'       => Str::slug($name),
                'address'    => ($index + 10) . ', Commercial Plaza, Main Ring Road, Gujarat',
                'is_default' => $index === 0, // Ahmedabad as default
                'status'     => 'active',
                'created_by' => $adminId,
            ]);
        }

        // Set default location to super admin user
        if ($admin) {
            $admin->update(['location_id' => $locations[0]->id]);
        }

        // 3. Generate 15 Premium Categories
        $categoriesList = [
            'Necklace Sets', 'Bangles & Kada', 'Earrings & Jhumkas', 'Rings', 
            'Anklets (Payal)', 'Pendant Sets', 'Mangalsutras', 'Maang Tikka', 
            'Bridal Combo Sets', 'Hair Accessories', 'Nose Rings (Nath)', 'Bracelets', 
            'Brooches', 'Waist Belts (Kamarbandh)', 'Armlets (Bajubandh)'
        ];

        $categories = [];
        foreach ($categoriesList as $name) {
            $categories[] = Category::create([
                'name'       => $name,
                'slug'       => Str::slug($name),
                'status'     => 'active',
                'created_by' => $adminId,
            ]);
        }

        // 4. Generate 20 Sub Categories mapped to parent categories
        $subCategoriesList = [
            ['category_idx' => 0, 'name' => 'Kundan Necklaces'],
            ['category_idx' => 0, 'name' => 'Antique Choker Sets'],
            ['category_idx' => 0, 'name' => 'Temple Jewellery Sets'],
            ['category_idx' => 1, 'name' => 'Gold Plated Bangles'],
            ['category_idx' => 1, 'name' => 'Silk Thread Bangles'],
            ['category_idx' => 1, 'name' => 'Designer Kada'],
            ['category_idx' => 2, 'name' => 'Traditional Jhumkas'],
            ['category_idx' => 2, 'name' => 'American Diamond Studs'],
            ['category_idx' => 2, 'name' => 'Chandbali Earrings'],
            ['category_idx' => 3, 'name' => 'Adjustable Couple Rings'],
            ['category_idx' => 3, 'name' => 'CZ Engagement Rings'],
            ['category_idx' => 4, 'name' => 'Silver Ghungroo Payal'],
            ['category_idx' => 4, 'name' => 'Beaded Designer Anklets'],
            ['category_idx' => 6, 'name' => 'Short Dailywear Mangalsutra'],
            ['category_idx' => 6, 'name' => 'Traditional Long Mangalsutra'],
            ['category_idx' => 8, 'name' => 'Royal Bridal Choker Combos'],
            ['category_idx' => 11, 'name' => 'Rhodium Plated Bracelets'],
            ['category_idx' => 13, 'name' => 'Kundan Waist Chains'],
            ['category_idx' => 14, 'name' => 'Peacock Plated Bajubandh']
        ];

        $subCategories = [];
        foreach ($subCategoriesList as $subItem) {
            $parentCat = $categories[$subItem['category_idx']];
            $subCategories[] = SubCategory::create([
                'category_id' => $parentCat->id,
                'name'        => $subItem['name'],
                'slug'        => Str::slug($subItem['name']),
                'status'      => 'active',
                'created_by'  => $adminId,
            ]);
        }

        // 5. Generate 20 Premium Imitation Jewellery Products
        $productsData = [
            ['name' => 'Royal Kundan Choker Set', 'cat_idx' => 0, 'sub_idx' => 1, 'sku' => 'NEC-KUN-001', 'purchase' => 1200, 'sale' => 2500],
            ['name' => 'Antique Matte Temple Haran', 'cat_idx' => 0, 'sub_idx' => 2, 'sku' => 'NEC-TEM-002', 'purchase' => 1800, 'sale' => 3800],
            ['name' => 'Gold Plated AD Kada Set', 'cat_idx' => 1, 'sub_idx' => 5, 'sku' => 'BAN-KAD-001', 'purchase' => 450, 'sale' => 950],
            ['name' => 'Handcrafted Silk Thread Bangles', 'cat_idx' => 1, 'sub_idx' => 4, 'sku' => 'BAN-SIL-002', 'purchase' => 180, 'sale' => 399],
            ['name' => 'Meenakari Peacock Jhumkas', 'cat_idx' => 2, 'sub_idx' => 6, 'sku' => 'EAR-JHU-001', 'purchase' => 150, 'sale' => 350],
            ['name' => 'Elegant Solitaire AD Studs', 'cat_idx' => 2, 'sub_idx' => 7, 'sku' => 'EAR-STU-002', 'purchase' => 90, 'sale' => 250],
            ['name' => 'Royal Pearl Chandbalis', 'cat_idx' => 2, 'sub_idx' => 8, 'sku' => 'EAR-CHA-003', 'purchase' => 280, 'sale' => 650],
            ['name' => 'Adjustable Floral Couple Ring', 'cat_idx' => 3, 'sub_idx' => 9, 'sku' => 'RNG-COP-001', 'purchase' => 120, 'sale' => 299],
            ['name' => 'CZ Solitaire Engagement Ring', 'cat_idx' => 3, 'sub_idx' => 10, 'sku' => 'RNG-ENG-002', 'purchase' => 350, 'sale' => 799],
            ['name' => 'Silver Ghungroo Bridal Payal', 'cat_idx' => 4, 'sub_idx' => 11, 'sku' => 'ANK-GHU-001', 'purchase' => 300, 'sale' => 699],
            ['name' => 'Traditional Rajputi Nath', 'cat_idx' => 10, 'sub_idx' => 0, 'sku' => 'NAT-RAJ-001', 'purchase' => 110, 'sale' => 290],
            ['name' => 'Short Diamond Cut Mangalsutra', 'cat_idx' => 6, 'sub_idx' => 13, 'sku' => 'MAN-SHO-001', 'purchase' => 220, 'sale' => 499],
            ['name' => 'Royal Dulhan Kundan Combo Set', 'cat_idx' => 8, 'sub_idx' => 15, 'sku' => 'BRD-KUN-001', 'purchase' => 4500, 'sale' => 9999],
            ['name' => 'CZ Rhodium Plated Bracelet', 'cat_idx' => 11, 'sub_idx' => 16, 'sku' => 'BRC-RHO-001', 'purchase' => 250, 'sale' => 599],
            ['name' => 'Bridal Kundan Kamarbandh', 'cat_idx' => 13, 'sub_idx' => 17, 'sku' => 'WST-KUN-001', 'purchase' => 850, 'sale' => 1899],
            ['name' => 'Antique Plated Bajubandh Set', 'cat_idx' => 14, 'sub_idx' => 18, 'sku' => 'ARM-ANT-001', 'purchase' => 400, 'sale' => 899],
            ['name' => 'Traditional Kempu Pendant Set', 'cat_idx' => 5, 'sub_idx' => 0, 'sku' => 'PEN-KEM-001', 'purchase' => 320, 'sale' => 750],
            ['name' => 'Kundan Dulhan Maang Tikka', 'cat_idx' => 7, 'sub_idx' => 0, 'sku' => 'TIK-KUN-001', 'purchase' => 140, 'sale' => 320],
            ['name' => 'Oxidised Silver Hair Pin Set', 'cat_idx' => 9, 'sub_idx' => 0, 'sku' => 'HAR-PIN-001', 'purchase' => 80, 'sale' => 199],
            ['name' => 'Gold Plated Classic Brooch', 'cat_idx' => 12, 'sub_idx' => 0, 'sku' => 'BRO-GLD-001', 'purchase' => 130, 'sale' => 350]
        ];

        $products = [];
        foreach ($productsData as $prod) {
            $cat = $categories[$prod['cat_idx']];
            $sub = isset($subCategories[$prod['sub_idx']]) ? $subCategories[$prod['sub_idx']]->id : null;
            $product = Product::create([
                'name'            => $prod['name'],
                'slug'            => Str::slug($prod['name']),
                'category_id'     => $cat->id,
                'sub_category_id' => $sub,
                'sku'             => $prod['sku'],
                'description'     => 'This is a premium handcrafted ' . $prod['name'] . ', designed with absolute precision and top-grade electroplating to give a realistic look.',
                'purchase_price'  => $prod['purchase'],
                'sale_price'      => $prod['sale'],
                'status'          => 'active',
                'created_by'      => $adminId,
            ]);
            $products[] = $product;

            // Generate images for this product!
            $sku = $product->sku;
            $dir = public_path('uploads/products');
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            // 1. Primary Image
            $primaryFilename = 'products/' . $sku . '_primary.png';
            $primaryFullPath = public_path('uploads/' . $primaryFilename);
            $bg = [115, 103, 240]; // Purple-blue (#7367f0)
            $fg = [255, 255, 255];
            self::generatePlaceholderImage($primaryFullPath, 600, 600, $product->name, $bg, $fg);

            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $primaryFilename,
                'is_primary' => true,
                'created_by' => $adminId,
            ]);

            // 2. 3 to 5 Additional Images
            $numAdditional = rand(3, 5);
            for ($j = 1; $j <= $numAdditional; $j++) {
                $additionalFilename = 'products/' . $sku . '_detail_' . $j . '.png';
                $additionalFullPath = public_path('uploads/' . $additionalFilename);
                
                // Use a different color palette for details
                $detailBgs = [
                    [40, 199, 111],  // Green
                    [0, 207, 232],   // Cyan
                    [255, 159, 67],  // Orange
                    [234, 84, 85],   // Red
                    [168, 115, 255]  // Light purple
                ];
                $bgIdx = ($j - 1) % count($detailBgs);
                self::generatePlaceholderImage($additionalFullPath, 600, 600, $sku . ' - Detail ' . $j, $detailBgs[$bgIdx], [255, 255, 255]);

                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $additionalFilename,
                    'is_primary' => false,
                    'created_by' => $adminId,
                ]);
            }
        }

        // 6. Generate 15 Premium Suppliers
        $suppliersData = [
            'Radhe Imitation Rajkot', 'Shreeji Jewellery Beads', 'Kundan Craft Jaipur', 
            'Ambika Metal Works', 'Vardhman Gems & Pearls', 'Royal Gold Platers', 
            'Arihant Tools Mumbai', 'Balaji Electroplaters', 'Meera Antique Works', 
            'Jalaram Box & Packaging', 'Mahavir Gift Boxes', 'Ganesh Electroplaters', 
            'Star Platers Ahmedabad', 'Jaipur Craft House', 'Gujarat Metal Syndicate'
        ];

        $suppliers = [];
        foreach ($suppliersData as $index => $name) {
            $suppliers[] = Supplier::create([
                'name'       => $name,
                'phone'      => '+91 98765 ' . str_pad($index + 10000, 5, '0', STR_PAD_LEFT),
                'address'    => ($index + 50) . ', Industrial Estate, Rajkot, Gujarat',
                'status'     => 'active',
                'created_by' => $adminId,
            ]);
        }

        // 7. Generate 15 Premium Customers
        $customersData = [
            ['name' => 'Aarav Patel', 'phone' => '+91 91234 56789', 'email' => 'aarav.patel@gmail.com'],
            ['name' => 'Priya Sharma', 'phone' => '+91 98234 56781', 'email' => 'priya.sharma@yahoo.com'],
            ['name' => 'Neha Mehta', 'phone' => '+91 94234 56782', 'email' => 'neha.mehta@outlook.com'],
            ['name' => 'Anjali Shah', 'phone' => '+91 99234 56783', 'email' => 'anjali.shah@gmail.com'],
            ['name' => 'Rajesh Trivedi', 'phone' => '+91 96234 56784', 'email' => 'rajesh.trivedi@gmail.com'],
            ['name' => 'Hitesh Gadhvi', 'phone' => '+91 93234 56785', 'email' => 'hitesh.gadhvi@gmail.com'],
            ['name' => 'Bhavna Vaghela', 'phone' => '+91 97234 56786', 'email' => 'bhavna.vaghela@yahoo.com'],
            ['name' => 'Sunita Rao', 'phone' => '+91 95234 56787', 'email' => 'sunita.rao@gmail.com'],
            ['name' => 'Kunal Chawla', 'phone' => '+91 91234 56780', 'email' => 'kunal.chawla@gmail.com'],
            ['name' => 'Manoj Kothari', 'phone' => '+91 92234 56789', 'email' => 'manoj.kothari@gmail.com'],
            ['name' => 'Dhara Joshi', 'phone' => '+91 94234 56711', 'email' => 'dhara.joshi@gmail.com'],
            ['name' => 'Kiran Varma', 'phone' => '+91 98234 56722', 'email' => 'kiran.varma@gmail.com'],
            ['name' => 'Sheetal Parmar', 'phone' => '+91 99234 56733', 'email' => 'sheetal.parmar@gmail.com'],
            ['name' => 'Gopal Vyas', 'phone' => '+91 97234 56744', 'email' => 'gopal.vyas@gmail.com'],
            ['name' => 'Amit Panchal', 'phone' => '+91 96234 56755', 'email' => 'amit.panchal@gmail.com']
        ];

        $customers = [];
        foreach ($customersData as $cust) {
            $customers[] = Customer::create([
                'name'       => $cust['name'],
                'phone'      => $cust['phone'],
                'email'      => $cust['email'],
                'status'     => 'active',
            ]);
        }

        // 8. Generate 15 Purchase Invoices (Confirmed) & Allocations to populate Inventory!
        // Confirmed purchases automatically adjust and populate physical stock in locations.
        for ($i = 0; $i < 15; $i++) {
            $supplier = $suppliers[$i];
            $invoiceNo = 'PUR-' . date('Ymd') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
            
            $invoice = PurchaseInvoice::create([
                'supplier_id'    => $supplier->id,
                'invoice_no'     => $invoiceNo,
                'total_amount'   => 0.0,
                'status'         => 'approve',
                'payment_status' => rand(0, 1) ? 'paid' : 'pending',
                'created_by'     => $adminId,
                'created_at'     => now()->subMonths(14 - $i), // Distributed over last 14 months
            ]);

            // Add 2 random items to this purchase
            $totalAmount = 0.0;
            $itemsKeys = array_rand($products, 2);
            
            foreach ($itemsKeys as $k) {
                $product = $products[$k];
                $quantity = rand(40, 100);
                $purchasePrice = $product->purchase_price;
                $itemTotal = $quantity * $purchasePrice;
                $totalAmount += $itemTotal;

                $item = PurchaseItem::create([
                    'purchase_invoice_id' => $invoice->id,
                    'product_id'          => $product->id,
                    'purchase_price'      => $purchasePrice,
                    'quantity'            => $quantity,
                    'total'               => $itemTotal,
                ]);

                // Allocate stock to Locations to build inventory
                // Divide quantity randomly among 3 active locations
                $locKeys = array_rand($locations, 3);
                $remaining = $quantity;
                
                foreach ($locKeys as $idx => $lKey) {
                    $loc = $locations[$lKey];
                    $allocQty = ($idx === 2) ? $remaining : rand(10, floor($remaining / 2));
                    $remaining -= $allocQty;

                    if ($allocQty > 0) {
                        PurchaseAllocation::create([
                            'purchase_item_id' => $item->id,
                            'location_id'      => $loc->id,
                            'quantity'         => $allocQty,
                        ]);

                        // Add to physical inventory table!
                        $inv = Inventory::where('product_id', $product->id)
                            ->where('location_id', $loc->id)
                            ->first();

                        if ($inv) {
                            $inv->increment('quantity', $allocQty);
                        } else {
                            Inventory::create([
                                'product_id'  => $product->id,
                                'location_id' => $loc->id,
                                'quantity'    => $allocQty,
                            ]);
                        }
                    }
                }
            }

            $invoice->update(['total_amount' => $totalAmount]);
        }

        // 9. Generate 15 Sales Orders (Distributed over past months)
        for ($i = 0; $i < 15; $i++) {
            $customer = $customers[$i];
            $location = $locations[$i % count($locations)];
            $orderNo  = 'SAL-' . date('Ymd') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
            
            $order = Order::create([
                'customer_id'    => $customer->id,
                'location_id'    => $location->id,
                'user_id'        => $adminId,
                'order_no'       => $orderNo,
                'order_type'     => 'sale',
                'status'         => 'approve',
                'payment_status' => rand(0, 1) ? 'paid' : 'non_paid',
                'payment_method' => ['cash', 'card', 'upi', 'bank_transfer'][$i % 4],
                'total_amount'   => 0.0,
                'final_amount'   => 0.0,
                'created_at'     => now()->subMonths(14 - $i)->addDays(rand(1, 15)), // Distributed
            ]);

            // Add 2 items to this sale
            $itemsKeys = array_rand($products, 2);
            $orderTotal = 0.0;

            foreach ($itemsKeys as $k) {
                $product = $products[$k];
                
                // Deduct from inventory in this location
                $inv = Inventory::where('product_id', $product->id)
                    ->where('location_id', $location->id)
                    ->first();
                
                $available = $inv ? $inv->quantity : 0;
                $quantity = ($available > 5) ? rand(1, 4) : 1;

                // Adjust inventory stock
                if ($inv) {
                    $inv->decrement('quantity', $quantity);
                } else {
                    Inventory::create([
                        'product_id'  => $product->id,
                        'location_id' => $location->id,
                        'quantity'    => -$quantity, // Negative if sold without stock
                    ]);
                }

                $salePrice = $product->sale_price;
                $itemTotal = $quantity * $salePrice;
                $orderTotal += $itemTotal;

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'quantity'   => $quantity,
                    'price'      => $salePrice,
                    'total'      => $itemTotal,
                ]);
            }

            $order->update([
                'total_amount' => $orderTotal,
                'final_amount' => $orderTotal,
            ]);
        }
    }

    private static function generatePlaceholderImage(string $path, int $width, int $height, string $text, array $bgColor, array $textColor): void
    {
        if (!extension_loaded('gd')) {
            file_put_contents($path, '');
            return;
        }

        $image = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($image, $bgColor[0], $bgColor[1], $bgColor[2]);
        $fg = imagecolorallocate($image, $textColor[0], $textColor[1], $textColor[2]);

        imagefill($image, 0, 0, $bg);

        // Word wrap text to fit in the box
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';
        foreach ($words as $word) {
            if (strlen($currentLine . ' ' . $word) > 15) {
                $lines[] = trim($currentLine);
                $currentLine = $word;
            } else {
                $currentLine .= ' ' . $word;
            }
        }
        $lines[] = trim($currentLine);

        // Center lines vertically
        $font = 5;
        $charWidth = imagefontwidth($font);
        $charHeight = imagefontheight($font);
        $totalHeight = count($lines) * ($charHeight + 6);
        $y = ($height - $totalHeight) / 2;

        foreach ($lines as $line) {
            $lineWidth = strlen($line) * $charWidth;
            $x = ($width - $lineWidth) / 2;
            imagestring($image, $font, (int)$x, (int)$y, $line, $fg);
            $y += $charHeight + 6;
        }

        imagepng($image, $path);
        imagedestroy($image);
    }
}
