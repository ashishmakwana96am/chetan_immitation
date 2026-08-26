<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Expense;
use App\Models\Location;
use App\Models\LocationBalance;
use App\Models\LocationBalanceTransaction;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // 1. Specifically repair Bardoli 07 Aug 2026 corrupted records (ID 290 and ID 292)
            $exp59 = Expense::find(59); // coffee, 20.00
            if ($exp59) {
                LocationBalanceTransaction::where('id', 290)->update([
                    'expense_id' => 59,
                    'notes'      => 'Expense: coffee',
                    'amount'     => 20.00,
                ]);
            }

            $exp61 = Expense::find(61); // coffee & nasta, 275.00
            if ($exp61) {
                LocationBalanceTransaction::where('id', 292)->update([
                    'expense_id' => 61,
                    'notes'      => 'Expense: coffee & nasta',
                    'amount'     => 275.00,
                ]);
            }

            // 2. Link all unlinked Expenses to their corresponding LocationBalanceTransaction
            $allExpenses = Expense::orderBy('id', 'asc')->get();

            foreach ($allExpenses as $expense) {
                // Check if already linked
                $alreadyLinked = LocationBalanceTransaction::where('expense_id', $expense->id)->exists();
                if ($alreadyLinked) {
                    continue;
                }

                $titleNote = 'Expense: ' . ($expense->title ?: $expense->category);

                // Find candidate transaction by note, location and null expense_id
                $candidate = LocationBalanceTransaction::whereNull('expense_id')
                    ->where('location_id', $expense->location_id)
                    ->where('notes', $titleNote)
                    ->orderByRaw('ABS(TIMESTAMPDIFF(SECOND, created_at, ?)) ASC', [$expense->created_at])
                    ->first();

                if ($candidate) {
                    $candidate->update([
                        'expense_id' => $expense->id,
                        'amount'     => $expense->amount,
                        'notes'      => $titleNote,
                    ]);
                } else {
                    // Try searching candidate without exact note match (by timestamp proximity within 5 minutes)
                    $approxCandidate = LocationBalanceTransaction::whereNull('expense_id')
                        ->where('location_id', $expense->location_id)
                        ->where('notes', 'LIKE', 'Expense:%')
                        ->orderByRaw('ABS(TIMESTAMPDIFF(SECOND, created_at, ?)) ASC', [$expense->created_at])
                        ->first();

                    if ($approxCandidate && abs(strtotime($approxCandidate->created_at) - strtotime($expense->created_at)) < 300) {
                        $approxCandidate->update([
                            'expense_id' => $expense->id,
                            'amount'     => $expense->amount,
                            'notes'      => $titleNote,
                        ]);
                    } else {
                        // Create missing transaction if expense is valid
                        if ($expense->amount > 0 && $expense->location_id) {
                            $online = ['online', 'upi', 'bank transfer', 'bank_transfer', 'card'];
                            $balanceType = in_array(strtolower($expense->payment_method ?? ''), $online, true)
                                ? LocationBalanceTransaction::BALANCE_TYPE_BANK
                                : LocationBalanceTransaction::BALANCE_TYPE_CASH;

                            LocationBalanceTransaction::create([
                                'location_id'  => $expense->location_id,
                                'expense_id'   => $expense->id,
                                'balance_type' => $balanceType,
                                'type'         => LocationBalanceTransaction::TYPE_DEBIT,
                                'amount'       => $expense->amount,
                                'balance_after'=> 0.00,
                                'notes'        => $titleNote,
                                'created_by'   => LocationBalanceTransaction::getFallbackUserId($expense->created_by),
                                'created_at'   => $expense->created_at,
                                'updated_at'   => $expense->created_at,
                            ]);
                        }
                    }
                }
            }

            // 3. Recalculate running balance_after for all locations sequentially
            $locations = Location::all();
            foreach ($locations as $loc) {
                foreach ([LocationBalanceTransaction::BALANCE_TYPE_CASH, LocationBalanceTransaction::BALANCE_TYPE_BANK] as $bType) {
                    $runningBalance = 0.00;
                    $txs = LocationBalanceTransaction::where('location_id', $loc->id)
                        ->where('balance_type', $bType)
                        ->orderBy('created_at', 'asc')
                        ->orderBy('id', 'asc')
                        ->get();

                    foreach ($txs as $tx) {
                        $amt = (float) $tx->amount;
                        if ($tx->type === LocationBalanceTransaction::TYPE_CREDIT) {
                            $runningBalance += $amt;
                        } else {
                            $runningBalance -= $amt;
                        }

                        $tx->update(['balance_after' => round($runningBalance, 2)]);
                    }

                    // Update LocationBalance table
                    $balCol = $bType === LocationBalanceTransaction::BALANCE_TYPE_BANK ? 'bank_balance' : 'cash_balance';
                    LocationBalance::updateOrCreate(
                        ['location_id' => $loc->id],
                        [$balCol => round($runningBalance, 2)]
                    );
                }
            }
        });
    }

    public function down(): void
    {
        // No action needed for down repair
    }
};
