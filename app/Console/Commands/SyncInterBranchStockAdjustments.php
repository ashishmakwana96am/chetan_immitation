<?php

namespace App\Console\Commands;

use App\Services\InterBranchStockAdjustmentService;
use Illuminate\Console\Command;

class SyncInterBranchStockAdjustments extends Command
{
    protected $signature = 'interbranch:sync-adjustments';
    protected $description = 'Reconcile and auto-adjust historical inter-branch stock returns against outstanding balances.';

    public function handle(): int
    {
        $this->info('Starting Inter-Branch Stock Return Auto Balance Adjustment sync...');

        try {
            $result = InterBranchStockAdjustmentService::syncAllHistoricalData();
            $this->info("Successfully synchronized inter-branch adjustments!");
            $this->info("Total adjustments made: {$result['adjustments_count']}");
            $this->info("Total amount adjusted: ₹" . number_format($result['total_amount'], 2));
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Error during sync: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
