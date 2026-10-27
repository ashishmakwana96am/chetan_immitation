<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\PurchaseBillPayment;
use App\Models\SalePayment;
use Illuminate\Console\Command;

class SyncDeletedSalesItemsCommand extends Command
{
    protected $signature = 'sales:sync-deleted-items';

    protected $description = 'Soft delete orphaned order items and payments for all deleted sales and purchase bills';

    public function handle()
    {
        $this->info('Starting synchronization of soft-deleted sales items...');

        $trashedOrderIds = Order::onlyTrashed()->pluck('id');
        $this->line("Found {$trashedOrderIds->count()} soft-deleted sales orders.");

        $deletedOrderItems = OrderItem::whereIn('order_id', $trashedOrderIds)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);

        $deletedSalePayments = SalePayment::whereIn('order_id', $trashedOrderIds)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);

        $deletedOrderPayments = OrderPayment::whereIn('order_id', $trashedOrderIds)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);

        $deletedCancellationRequests = OrderCancellationRequest::whereIn('order_id', $trashedOrderIds)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);

        $this->info("Sales cleanup complete:");
        $this->line(" - Soft-deleted Order Items: {$deletedOrderItems}");
        $this->line(" - Soft-deleted Sale Payments: {$deletedSalePayments}");
        $this->line(" - Soft-deleted Order Payments: {$deletedOrderPayments}");
        $this->line(" - Soft-deleted Cancellation Requests: {$deletedCancellationRequests}");

        $trashedBillIds = PurchaseBill::onlyTrashed()->pluck('id');
        $this->line("Found {$trashedBillIds->count()} soft-deleted purchase bills.");

        $deletedBillItems = PurchaseBillItem::whereIn('purchase_bill_id', $trashedBillIds)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);

        $deletedBillPayments = PurchaseBillPayment::whereIn('purchase_bill_id', $trashedBillIds)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);

        $this->info("Purchase Bills cleanup complete:");
        $this->line(" - Soft-deleted Purchase Bill Items: {$deletedBillItems}");
        $this->line(" - Soft-deleted Purchase Bill Payments: {$deletedBillPayments}");

        $this->info('All deleted sales and purchase bills data synchronized successfully.');

        return Command::SUCCESS;
    }
}
