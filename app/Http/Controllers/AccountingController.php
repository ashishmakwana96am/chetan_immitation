<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Location;
use App\Models\LocationBalanceTransaction;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\PurchaseBill;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function cashBook(Request $request)
    {
        $this->authorize('view cash book');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $locations = $isRestricted
            ? Location::where('id', $user->location_id)->get()
            : Location::where('status', 1)->orderBy('name')->get();

        return view('accounting.cashbook', compact('locations', 'isRestricted'));
    }

    public function cashBookData(Request $request)
    {
        $this->authorize('view cash book');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $query = LocationBalanceTransaction::with(['location', 'createdBy'])
            ->where('balance_type', LocationBalanceTransaction::BALANCE_TYPE_CASH);

        if ($isRestricted) {
            $query->where('location_id', $user->location_id);
        } elseif ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $transactions = $query->orderBy('id', 'desc')->get();

        $data = $transactions->map(function ($tx, $index) {
            return [
                'index'         => $index + 1,
                'date'          => format_date($tx->created_at),
                'date_group'    => $tx->created_at->format('d M Y'),
                'date_sort'     => $tx->created_at->format('Ymd'),
                'location'      => $tx->location->name ?? '-',
                'particulars'   => !empty($tx->notes) ? $tx->notes : 'Manual Balance Adjustment',
                'credit'        => $tx->type === LocationBalanceTransaction::TYPE_CREDIT ? format_price($tx->amount) : '-',
                'debit'         => $tx->type === LocationBalanceTransaction::TYPE_DEBIT ? format_price($tx->amount) : '-',
                'balance_after' => format_price($tx->balance_after),
                'done_by'       => $tx->createdBy->name ?? '-',
            ];
        });

        // Compute total credit and debit for summary cards
        $totalCredit = $transactions->where('type', LocationBalanceTransaction::TYPE_CREDIT)->sum('amount');
        $totalDebit  = $transactions->where('type', LocationBalanceTransaction::TYPE_DEBIT)->sum('amount');

        // Compute current cash balance based on filters/role
        if ($isRestricted) {
            $currentBalance = \App\Models\Location::where('id', $user->location_id)->value('cash_balance') ?? 0;
        } elseif ($request->filled('location_id')) {
            $currentBalance = \App\Models\Location::where('id', $request->location_id)->value('cash_balance') ?? 0;
        } else {
            $currentBalance = \App\Models\Location::where('status', 1)->sum('cash_balance');
        }

        return response()->json([
            'status'  => 'success',
            'data'    => $data,
            'summary' => [
                'total_credit'    => format_price($totalCredit),
                'total_debit'     => format_price($totalDebit),
                'current_balance' => format_price($currentBalance),
            ]
        ]);
    }

    public function bankBook(Request $request)
    {
        $this->authorize('view bank book');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $locations = $isRestricted
            ? Location::where('id', $user->location_id)->get()
            : Location::where('status', 1)->orderBy('name')->get();

        return view('accounting.bankbook', compact('locations', 'isRestricted'));
    }

    public function bankBookData(Request $request)
    {
        $this->authorize('view bank book');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $query = LocationBalanceTransaction::with(['location', 'createdBy'])
            ->where('balance_type', LocationBalanceTransaction::BALANCE_TYPE_BANK);

        if ($isRestricted) {
            $query->where('location_id', $user->location_id);
        } elseif ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $transactions = $query->orderBy('id', 'desc')->get();

        $data = $transactions->map(function ($tx, $index) {
            return [
                'index'         => $index + 1,
                'date'          => format_date($tx->created_at),
                'date_group'    => $tx->created_at->format('d M Y'),
                'date_sort'     => $tx->created_at->format('Ymd'),
                'location'      => $tx->location->name ?? '-',
                'particulars'   => !empty($tx->notes) ? $tx->notes : 'Manual Balance Adjustment',
                'credit'        => $tx->type === LocationBalanceTransaction::TYPE_CREDIT ? format_price($tx->amount) : '-',
                'debit'         => $tx->type === LocationBalanceTransaction::TYPE_DEBIT ? format_price($tx->amount) : '-',
                'balance_after' => format_price($tx->balance_after),
                'done_by'       => $tx->createdBy->name ?? '-',
            ];
        });

        $totalCredit = $transactions->where('type', LocationBalanceTransaction::TYPE_CREDIT)->sum('amount');
        $totalDebit  = $transactions->where('type', LocationBalanceTransaction::TYPE_DEBIT)->sum('amount');

        if ($isRestricted) {
            $currentBalance = \App\Models\Location::where('id', $user->location_id)->value('bank_balance') ?? 0;
        } elseif ($request->filled('location_id')) {
            $currentBalance = \App\Models\Location::where('id', $request->location_id)->value('bank_balance') ?? 0;
        } else {
            $currentBalance = \App\Models\Location::where('status', 1)->sum('bank_balance');
        }

        return response()->json([
            'status'  => 'success',
            'data'    => $data,
            'summary' => [
                'total_credit'    => format_price($totalCredit),
                'total_debit'     => format_price($totalDebit),
                'current_balance' => format_price($currentBalance),
            ]
        ]);
    }

    // ============================================================
    // GENERAL LEDGER
    // ============================================================

    public function generalLedger(Request $request)
    {
        $this->authorize('view general ledger');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $locations = $isRestricted
            ? Location::where('id', $user->location_id)->get()
            : Location::where('status', 1)->orderBy('name')->get();

        return view('accounting.general-ledger', compact('locations', 'isRestricted'));
    }

    public function generalLedgerData(Request $request)
    {
        $this->authorize('view general ledger');

        $user         = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $filterLocationId = $isRestricted ? $user->location_id : ($request->filled('location_id') ? $request->location_id : null);
        $startDate        = $request->filled('start_date') ? $request->start_date : null;
        $endDate          = $request->filled('end_date') ? $request->end_date : null;
        $sourceFilter     = $request->filled('source') ? $request->source : 'all'; // all, cash, bank, expense, sale, purchase, purchase_bill

        // We fetch ALL transactions from LocationBalanceTransaction
        // Since every entry (sale, purchase, expense, transfer, direct cash/bank) creates a transaction,
        // this guarantees no double counting while capturing everything.
        $txQuery = LocationBalanceTransaction::with(['location', 'createdBy']);

        if ($filterLocationId) {
            $txQuery->where('location_id', $filterLocationId);
        }
        if ($startDate) {
            $txQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $txQuery->whereDate('created_at', '<=', $endDate);
        }

        $transactions = $txQuery->orderBy('id', 'desc')->get();
        $entries = collect();

        foreach ($transactions as $tx) {
            $notes = $tx->notes ?? '';
            
            // 1. Identify the source based on transaction notes
            $detectedSource = 'cash'; // default
            if ($tx->balance_type === LocationBalanceTransaction::BALANCE_TYPE_BANK) {
                $detectedSource = 'bank';
            }

            if (stripos($notes, 'Sale #') !== false || stripos($notes, 'Reversal: Sale') !== false) {
                $detectedSource = 'sale';
            } elseif (stripos($notes, 'Purchase') !== false || stripos($notes, 'Purchase Payment') !== false) {
                $detectedSource = 'purchase';
            } elseif (stripos($notes, 'Expense') !== false) {
                $detectedSource = 'expense';
            } elseif (stripos($notes, 'Purchase Bill') !== false || stripos($notes, 'Transfer') !== false) {
                $detectedSource = 'purchase_bill';
            }

            // 2. Apply Source Filter if selected
            if ($sourceFilter !== 'all' && $sourceFilter !== $detectedSource) {
                continue;
            }

            $isCredit = $tx->type === LocationBalanceTransaction::TYPE_CREDIT;
            
            // Map labels for UI display
            $sourceLabels = [
                'cash' => 'Cash',
                'bank' => 'Bank',
                'sale' => 'Sale',
                'purchase' => 'Purchase',
                'expense' => 'Expense',
                'purchase_bill' => 'Purchase Bill'
            ];

            $ref = 'TXN#' . $tx->id;
            if ($detectedSource === 'sale') {
                if (preg_match('/Sale\s+#([^\s\[\]]+)/i', $notes, $matches)) {
                    $ref = $matches[1];
                }
            } elseif ($detectedSource === 'purchase') {
                if (preg_match('/Purchase\s+#([^\s\[\]]+)/i', $notes, $matches)) {
                    $ref = $matches[1];
                } elseif (preg_match('/\[Inv:\s*([^\]]+)\]/i', $notes, $matches)) {
                    $ref = trim($matches[1]);
                }
            } elseif ($detectedSource === 'purchase_bill') {
                if (preg_match('/#([^\s\|\]]+)/i', $notes, $matches)) {
                    $ref = $matches[1];
                }
            }

            $entries->push([
                'index'       => 0, // Will be set during map
                'date'        => format_date($tx->created_at),
                'date_group'  => $tx->created_at->format('d M Y'),
                'date_sort'   => $tx->created_at->format('YmdHis'),
                'source'      => $sourceLabels[$detectedSource] ?? $detectedSource,
                'source_type' => $detectedSource,
                'location'    => $tx->location->name ?? '-',
                'particulars' => $notes,
                'credit'      => $isCredit ? format_price($tx->amount) : '-',
                'debit'       => !$isCredit ? format_price($tx->amount) : '-',
                'done_by'     => $tx->createdBy->name ?? '-',
                'ref'         => $ref,
                'raw_credit'  => $isCredit ? (float) $tx->amount : 0,
                'raw_debit'   => !$isCredit ? (float) $tx->amount : 0,
            ]);
        }

        $data = $entries->map(function ($item, $index) {
            $item['index'] = $index + 1;
            return $item;
        });

        $totalCredit = $entries->sum('raw_credit');
        $totalDebit  = $entries->sum('raw_debit');

        if ($filterLocationId) {
            $loc         = Location::find($filterLocationId);
            $cashBalance = $loc ? (float) $loc->cash_balance : 0;
            $bankBalance = $loc ? (float) $loc->bank_balance : 0;
        } else {
            $cashBalance = Location::where('status', 1)->sum('cash_balance');
            $bankBalance = Location::where('status', 1)->sum('bank_balance');
        }

        return response()->json([
            'status' => 'success',
            'data'   => $data,
            'summary' => [
                'total_credit' => format_price($totalCredit),
                'total_debit'  => format_price($totalDebit),
                'cash_balance' => format_price($cashBalance),
                'bank_balance' => format_price($bankBalance),
                'net'          => format_price($totalCredit - $totalDebit),
            ],
        ]);
    }

    public function outstandingPayables(Request $request)
    {
        $this->authorize('view outstanding payables');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $locations = $isRestricted
            ? Location::where('id', $user->location_id)->get()
            : Location::where('status', 1)->orderBy('name')->get();

        return view('accounting.outstanding-payables', compact('locations', 'isRestricted'));
    }

    public function outstandingPayablesData(Request $request)
    {
        $this->authorize('view outstanding payables');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $purchasesQuery = \App\Models\Purchase::with('supplier');

        if ($isRestricted) {
            $locationId = $user->location_id;
            $purchasesQuery->whereHas('items.allocations', function ($aq) use ($locationId) {
                $aq->where('location_id', $locationId);
            });
        } else {
            if ($request->filled('location_id')) {
                $locationId = (int) $request->location_id;
                $purchasesQuery->whereHas('items.allocations', function ($aq) use ($locationId) {
                    $aq->where('location_id', $locationId);
                });
            }
        }

        if ($request->filled('start_date')) {
            $purchasesQuery->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $purchasesQuery->whereDate('created_at', '<=', $request->end_date);
        }

        $purchases = $purchasesQuery->get();

        // Group by Date and Supplier
        $grouped = $purchases->groupBy(function ($purchase) {
            $date = $purchase->created_at ? $purchase->created_at->format('Y-m-d') : now()->format('Y-m-d');
            return $date . '_' . ($purchase->supplier_id ?? 0);
        });

        $rows = collect();
        $totalPurchase = 0.0;
        $totalPayment = 0.0;
        $totalOutstanding = 0.0;

        foreach ($grouped as $key => $items) {
            $first = $items->first();
            $supplierName = $first->supplier->name ?? 'Unknown';
            
            $sumTotal = 0.0;
            $sumPaid = 0.0;

            foreach ($items as $purchase) {
                $sumTotal += (float) $purchase->total_amount;
                if ($purchase->payment_status == \App\Models\Purchase::PAYMENT_STATUS_PAID) {
                    $sumPaid += (float) $purchase->total_amount;
                } elseif ($purchase->payment_status == \App\Models\Purchase::PAYMENT_STATUS_PENDING) {
                    $sumPaid += 0.0;
                } else {
                    $sumPaid += (float) $purchase->paid_amount;
                }
            }

            $due = max(0.0, $sumTotal - $sumPaid);

            if ($due <= 0) {
                continue;
            }

            $totalPurchase += $sumTotal;
            $totalPayment += $sumPaid;
            $totalOutstanding += $due;

            $dateObj = $first->created_at ?? now();

            $rows->push([
                'supplier_id'    => $first->supplier_id,
                'supplier_name'  => $supplierName,
                'date_raw'       => $dateObj->format('Y-m-d'),
                'date_formatted' => format_date($dateObj),
                'total_amount'   => $sumTotal,
                'paid_amount'    => $sumPaid,
                'due_amount'     => $due,
            ]);
        }

        // Sort by date desc, then supplier name asc
        $sortedRows = $rows->sort(function ($a, $b) {
            if ($a['date_raw'] !== $b['date_raw']) {
                return strcmp($b['date_raw'], $a['date_raw']);
            }
            return strcmp($a['supplier_name'], $b['supplier_name']);
        })->values();

        $mappedRows = $sortedRows->map(function ($row, $index) {
            $dateObj = \Carbon\Carbon::parse($row['date_raw']);
            return [
                'index'          => $index + 1,
                'supplier_id'    => $row['supplier_id'],
                'supplier'       => e($row['supplier_name']),
                'date'           => $row['date_formatted'],
                'total_amount'   => format_price($row['total_amount']),
                'paid_amount'    => format_price($row['paid_amount']),
                'due_amount'     => format_price($row['due_amount']),
                'date_group'     => format_date($dateObj, 'd M Y'),
                'date_sort'      => $row['date_raw'],
            ];
        });

        return response()->json([
            'status'  => 'success',
            'data'    => $mappedRows,
            'summary' => [
                'purchase'    => format_price($totalPurchase),
                'payment'     => format_price($totalPayment),
                'outstanding' => format_price($totalOutstanding),
            ],
        ]);
    }

    public function outstandingPayablesDetail(Request $request)
    {
        $this->authorize('view outstanding payables');

        $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'date'        => ['nullable', 'date_format:Y-m-d'],
        ]);

        $supplier = \App\Models\Supplier::findOrFail($request->supplier_id);

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $purchasesQuery = \App\Models\Purchase::where('supplier_id', $request->supplier_id);

        if ($isRestricted) {
            $locationId = $user->location_id;
            $purchasesQuery->whereHas('items.allocations', function ($aq) use ($locationId) {
                $aq->where('location_id', $locationId);
            });
        } else {
            if ($request->filled('location_id')) {
                $locationId = (int) $request->location_id;
                $purchasesQuery->whereHas('items.allocations', function ($aq) use ($locationId) {
                    $aq->where('location_id', $locationId);
                });
            }
        }

        if ($request->filled('date')) {
            $purchasesQuery->whereDate('created_at', $request->date);
            $date = \Carbon\Carbon::parse($request->date);
        } else {
            if ($request->filled('start_date')) {
                $purchasesQuery->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $purchasesQuery->whereDate('created_at', '<=', $request->end_date);
            }
            $date = null;
        }

        $allPurchases = $purchasesQuery->get();

        $purchases = $allPurchases->map(function($p) {
            if ($p->payment_status == \App\Models\Purchase::PAYMENT_STATUS_PAID) {
                $p->calculated_paid = (float) $p->total_amount;
            } elseif ($p->payment_status == \App\Models\Purchase::PAYMENT_STATUS_PENDING) {
                $p->calculated_paid = 0.0;
            } else {
                $p->calculated_paid = (float) $p->paid_amount;
            }
            $p->calculated_due = max(0.0, (float) $p->total_amount - $p->calculated_paid);
            return $p;
        })->filter(function($p) {
            return $p->calculated_due > 0;
        })->values();

        $totalPurchase = $purchases->sum('total_amount');
        $totalPayment = $purchases->sum('calculated_paid');
        $totalOutstanding = max(0.0, $totalPurchase - $totalPayment);

        return view('accounting.outstanding-payables-detail', compact(
            'supplier',
            'purchases',
            'totalPurchase',
            'totalPayment',
            'totalOutstanding',
            'date'
        ));
    }
}
