<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\PurchaseItem;
use App\Models\PurchaseBillItem;

class ClearProductTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:clear-transactions {barcode : The barcode of the product to clear data for} {--force : Force execution without confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hard deletes all sales, purchases, transfers, and inventory records for a specific product barcode';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $barcode = trim($this->argument('barcode'));

        $product = Product::withTrashed()->where('barcode', $barcode)->first();

        if (!$product) {
            $this->error("Product with barcode '{$barcode}' was not found.");
            return 1;
        }

        $this->info("Found Product: {$product->name} (ID: {$product->id}, Barcode: {$product->barcode})");

        if (!$this->option('force') && !$this->confirm("Are you sure you want to HARD DELETE all sales, purchases, transfers, and inventory records for '{$product->name}' ({$barcode})? This action cannot be undone!")) {
            $this->info('Action cancelled.');
            return 0;
        }

        $productId = $product->id;

        DB::transaction(function () use ($productId, $barcode) {
            // 1. Delete Order Items & Orphaned Orders
            $orderIds = OrderItem::where('product_id', $productId)->pluck('order_id')->unique()->filter()->toArray();
            $deletedOrderItems = DB::table('order_items')->where('product_id', $productId)->delete();
            $this->info("Deleted {$deletedOrderItems} order item(s).");

            foreach ($orderIds as $orderId) {
                if (DB::table('order_items')->where('order_id', $orderId)->count() === 0) {
                    DB::table('sale_payments')->where('order_id', $orderId)->delete();
                    DB::table('order_payments')->where('order_id', $orderId)->delete();
                    DB::table('order_cancellation_requests')->where('order_id', $orderId)->delete();
                    DB::table('orders')->where('id', $orderId)->delete();
                    $this->info("Deleted empty Order #{$orderId}.");
                }
            }

            // 2. Delete Purchase Items, Allocations & Orphaned Purchases
            $purchaseItemIds = PurchaseItem::where('product_id', $productId)->pluck('id')->filter()->toArray();
            $purchaseIds = PurchaseItem::where('product_id', $productId)->pluck('purchase_id')->unique()->filter()->toArray();

            if (!empty($purchaseItemIds)) {
                DB::table('purchase_allocations')->whereIn('purchase_item_id', $purchaseItemIds)->delete();
            }
            $deletedPurchaseItems = DB::table('purchase_items')->where('product_id', $productId)->delete();
            $this->info("Deleted {$deletedPurchaseItems} purchase item(s).");

            foreach ($purchaseIds as $purchaseId) {
                if (DB::table('purchase_items')->where('purchase_id', $purchaseId)->count() === 0) {
                    DB::table('purchase_payments')->where('purchase_id', $purchaseId)->delete();
                    DB::table('purchases')->where('id', $purchaseId)->delete();
                    $this->info("Deleted empty Purchase #{$purchaseId}.");
                }
            }

            // 3. Delete Purchase Bill Items & Orphaned Purchase Bills (Transfers)
            $transferIds = PurchaseBillItem::where('product_id', $productId)->pluck('purchase_bill_id')->unique()->filter()->toArray();
            $deletedTransferItems = DB::table('purchase_bill_items')->where('product_id', $productId)->delete();
            $this->info("Deleted {$deletedTransferItems} transfer item(s).");

            foreach ($transferIds as $billId) {
                if (DB::table('purchase_bill_items')->where('purchase_bill_id', $billId)->count() === 0) {
                    DB::table('purchase_bill_payments')->where('purchase_bill_id', $billId)->delete();
                    DB::table('purchase_bills')->where('id', $billId)->delete();
                    $this->info("Deleted empty Purchase Bill #{$billId}.");
                }
            }

            // 4. Delete Inventories
            $inventoryIds = DB::table('inventories')->where('product_id', $productId)->pluck('id')->toArray();
            $deletedInventories = DB::table('inventories')->where('product_id', $productId)->delete();
            $this->info("Deleted {$deletedInventories} inventory record(s).");

            // 5. Delete Cart Items & Wishlists
            DB::table('cart_items')->where('product_id', $productId)->delete();
            DB::table('wishlists')->where('product_id', $productId)->delete();

            // 6. Delete Utility Reports / Activity Logs for this product
            $deletedLogs = DB::table('utility_reports')
                ->where(function ($q) use ($productId, $barcode, $inventoryIds) {
                    if (!empty($inventoryIds)) {
                        $q->orWhere(function ($sub) use ($inventoryIds) {
                            $sub->where('subject_type', 'App\\Models\\Inventory')
                                ->whereIn('subject_id', $inventoryIds);
                        });
                    }
                    $q->orWhere(function ($sub) use ($productId) {
                        $sub->where('subject_type', 'App\\Models\\Product')
                            ->where('subject_id', $productId);
                    })
                    ->orWhere('description', 'like', "%{$barcode}%")
                    ->orWhere('old_values', 'like', "%\"product_id\":{$productId}%")
                    ->orWhere('new_values', 'like', "%\"product_id\":{$productId}%");
                })
                ->delete();

            $this->info("Deleted {$deletedLogs} activity log(s).");

            // 7. Clear Caches
            Product::clearMappedCaches();
        });

        $this->info("Successfully hard deleted all sales, purchases, transfers, and inventory for barcode '{$barcode}'.");
        return 0;
    }
}
