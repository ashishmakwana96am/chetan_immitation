<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $reports = \App\Models\UtilityReport::where(function($q) {
            $q->where('description', 'like', '%Stock issued/moved out for purchase bill #%')
              ->orWhere('description', 'like', '%Stock received/moved in for purchase bill #%');
        })->get();

        foreach ($reports as $report) {
            if (preg_match('/#([A-Za-z0-9-]+)/i', $report->description, $matches)) {
                $trNo = $matches[1];
                $pb = \App\Models\PurchaseBill::with(['fromLocation', 'toLocation'])->where('transfer_no', $trNo)->first();
                if ($pb) {
                    $fromName = $pb->fromLocation?->name ?? 'Source Branch';
                    $toName = $pb->toLocation?->name ?? 'Destination Branch';
                    if (str_contains($report->description, 'issued/moved out') || str_contains($report->description, 'moved out')) {
                        $newDesc = 'Stock moved out from ' . $fromName . ' to ' . $toName . ' for purchase bill #' . $trNo;
                    } else {
                        $newDesc = 'Stock moved in to ' . $toName . ' from ' . $fromName . ' for purchase bill #' . $trNo;
                    }
                    $report->update(['description' => $newDesc]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal required
    }
};
