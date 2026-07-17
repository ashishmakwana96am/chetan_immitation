<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\PurchaseBill;
use App\Models\Setting;

class RegenerateInvoiceNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:regenerate-invoice-numbers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate all previous invoice and order numbers according to current settings prefix and hyphen separator';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting invoice and order numbers regeneration...');

        // Step 1: Temporarily rename numbers to prevent unique constraint collisions
        $this->info('Applying temporary names to avoid unique collisions...');
        
        Order::where('source', 'ONLINE')->each(function ($order) {
            $order->update(['order_no' => 'temp_on_' . $order->id]);
        });
        
        Order::where('source', 'POS')->each(function ($sale) {
            $sale->update(['order_no' => 'temp_pos_' . $sale->id]);
        });
        
        Purchase::where('is_gst', true)->each(function ($purchase) {
            $purchase->update(['invoice_no' => 'temp_gst_' . $purchase->id]);
        });
        
        Purchase::where('is_gst', false)->each(function ($purchase) {
            $purchase->update(['invoice_no' => 'temp_ngst_' . $purchase->id]);
        });
        
        PurchaseBill::each(function ($transfer) {
            $transfer->update(['transfer_no' => 'temp_pb_' . $transfer->id]);
        });

        // Step 2: Set final sequence numbers
        $this->info('Setting new formatted sequence numbers...');

        // 1. Online Orders (OR-xx)
        $onlinePrefix = strtoupper(Setting::getValue('prefix_online_order', 'OR'));
        $onlineOrders = Order::where('source', 'ONLINE')->orderBy('id', 'asc')->get();
        $this->info("Found {$onlineOrders->count()} Online Orders to update with prefix: {$onlinePrefix}");
        foreach ($onlineOrders as $index => $order) {
            $newNo = $onlinePrefix . '-' . str_pad($index + 1, 2, '0', STR_PAD_LEFT);
            $order->update(['order_no' => $newNo]);
        }

        // 2. Offline Sales (SA-xx)
        $offlinePrefix = strtoupper(Setting::getValue('prefix_offline_sale', 'SA'));
        $offlineSales = Order::where('source', 'POS')->orderBy('id', 'asc')->get();
        $this->info("Found {$offlineSales->count()} Offline Sales to update with prefix: {$offlinePrefix}");
        foreach ($offlineSales as $index => $sale) {
            $newNo = $offlinePrefix . '-' . str_pad($index + 1, 2, '0', STR_PAD_LEFT);
            $sale->update(['order_no' => $newNo]);
        }

        // 3. GST Purchases (GP-xx)
        $gstPurchasePrefix = strtoupper(Setting::getValue('prefix_supplier_purchase_gst', 'GP'));
        $gstPurchases = Purchase::where('is_gst', true)->orderBy('id', 'asc')->get();
        $this->info("Found {$gstPurchases->count()} GST Purchases to update with prefix: {$gstPurchasePrefix}");
        foreach ($gstPurchases as $index => $purchase) {
            $newNo = $gstPurchasePrefix . '-' . str_pad($index + 1, 2, '0', STR_PAD_LEFT);
            $purchase->update(['invoice_no' => $newNo]);
        }

        // 4. Non-GST Purchases (PS-xx)
        $nonGstPurchasePrefix = strtoupper(Setting::getValue('prefix_supplier_purchase', 'PS'));
        $nonGstPurchases = Purchase::where('is_gst', false)->orderBy('id', 'asc')->get();
        $this->info("Found {$nonGstPurchases->count()} Non-GST Purchases to update with prefix: {$nonGstPurchasePrefix}");
        foreach ($nonGstPurchases as $index => $purchase) {
            $newNo = $nonGstPurchasePrefix . '-' . str_pad($index + 1, 2, '0', STR_PAD_LEFT);
            $purchase->update(['invoice_no' => $newNo]);
        }

        // 5. Purchase Bills (ST-xx)
        $transferPrefix = strtoupper(Setting::getValue('prefix_stock_transfer', 'ST'));
        $transfers = PurchaseBill::orderBy('id', 'asc')->get();
        $this->info("Found {$transfers->count()} Purchase Bills to update with prefix: {$transferPrefix}");
        foreach ($transfers as $index => $transfer) {
            $newNo = $transferPrefix . '-' . str_pad($index + 1, 2, '0', STR_PAD_LEFT);
            $transfer->update(['transfer_no' => $newNo]);
        }

        $this->info('Regeneration completed successfully!');
    }
}
