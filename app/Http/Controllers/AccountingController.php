<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerBalance;
use App\Models\CustomerBalanceTransaction;
use App\Models\Expense;
use App\Models\Location;
use App\Models\LocationBalance;
use App\Models\LocationBalanceTransaction;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\PurchaseBill;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{
    public function cashBook(Request $request)
    {
        $this->authorize('view cash book');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $locations = $isRestricted
            ? Location::with('balance')->where('id', $user->location_id)->get()
            : Location::with('balance')->where('status', 1)->orderBy('name')->get();

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

        $sourceFilter = $request->filled('source') ? $request->source : 'all';

        $transactions = $query->orderBy('id', 'desc')->get();

        // Exclude any transactions for deleted Purchases / Sales / Expenses / Purchase Bills
        $transactions = $transactions->filter(function ($tx) {
            $notes = $tx->notes ?? '';
            if (preg_match('/Purchase #([A-Z0-9-]+)/i', $notes, $matches)) {
                $invoiceNo = $matches[1];
                $exists = \App\Models\Purchase::where('invoice_no', $invoiceNo)->exists();
                if (!$exists) return false;
            }
            if (preg_match('/Sale #([A-Z0-9-]+)/i', $notes, $matches)) {
                $orderNo = $matches[1];
                $exists = \App\Models\Order::where('order_no', $orderNo)->exists();
                if (!$exists) return false;
            }
            return true;
        });

        $data = $transactions->filter(function ($tx) use ($sourceFilter) {
            if ($sourceFilter === 'all') {
                return true;
            }
            $notes = $tx->notes ?? '';
            $detectedSource = 'cash';
            if ($tx->balance_type === LocationBalanceTransaction::BALANCE_TYPE_BANK) {
                $detectedSource = 'bank';
            }

            if (stripos($notes, 'Opening Balance') !== false || stripos($notes, 'Manual Balance Adjustment') !== false || stripos($notes, 'Manual Account Balance Adjustment') !== false) {
                $detectedSource = 'opening_balance';
            } elseif (stripos($notes, 'Sale #') !== false || stripos($notes, 'Reversal: Sale') !== false) {
                $detectedSource = 'sale';
            } elseif (stripos($notes, 'Purchase Bill') !== false) {
                $detectedSource = 'purchase_bill';
            } elseif (stripos($notes, 'Purchase') !== false || stripos($notes, 'Purchase Payment') !== false) {
                $detectedSource = 'purchase';
            } elseif (stripos($notes, 'Expense') !== false) {
                $detectedSource = 'expense';
            } elseif (stripos($notes, 'Transfer to ') !== false || stripos($notes, 'Transfer from ') !== false) {
                $detectedSource = 'balance_transfer';
            }

            return $detectedSource === $sourceFilter;
        })->values()->map(function ($tx, $index) {
            $notes = $tx->notes ?? '';
            $detectedSource = 'cash';
            if ($tx->balance_type === LocationBalanceTransaction::BALANCE_TYPE_BANK) {
                $detectedSource = 'bank';
            }

            if (stripos($notes, 'Opening Balance') !== false || stripos($notes, 'Manual Balance Adjustment') !== false || stripos($notes, 'Manual Account Balance Adjustment') !== false) {
                $detectedSource = 'opening_balance';
            } elseif (stripos($notes, 'Sale #') !== false || stripos($notes, 'Reversal: Sale') !== false) {
                $detectedSource = 'sale';
            } elseif (stripos($notes, 'Purchase Bill') !== false) {
                $detectedSource = 'purchase_bill';
            } elseif (stripos($notes, 'Purchase') !== false || stripos($notes, 'Purchase Payment') !== false) {
                $detectedSource = 'purchase';
            } elseif (stripos($notes, 'Expense') !== false) {
                $detectedSource = 'expense';
            } elseif (stripos($notes, 'Transfer to ') !== false || stripos($notes, 'Transfer from ') !== false) {
                $detectedSource = 'balance_transfer';
            }

            $isCredit = $tx->type === LocationBalanceTransaction::TYPE_CREDIT;
            return [
                'index'         => $index + 1,
                'date'          => format_date($tx->created_at),
                'date_group'    => $tx->created_at->format('d M Y'),
                'date_sort'     => $tx->created_at->format('Ymd'),
                'source_type'   => $detectedSource,
                'location'      => $tx->location->name ?? '-',
                'particulars'   => !empty($tx->notes) ? $tx->notes : 'Manual Balance Adjustment',
                'type'          => $isCredit ? 'credit' : 'debit',
                'type_badge'    => $isCredit ? '<span class="badge bg-label-success">Credit</span>' : '<span class="badge bg-label-danger">Debit</span>',
                'amount'        => format_price($tx->amount),
                'is_credit'     => $isCredit,
                'credit'        => $isCredit ? format_price($tx->amount) : '-',
                'debit'         => !$isCredit ? format_price($tx->amount) : '-',
                'balance_after' => format_price($tx->balance_after),
                'done_by'       => $tx->createdBy->name ?? '-',
            ];
        });

        $branchLocations = $isRestricted
            ? Location::where('id', $user->location_id)->get()
            : Location::where('status', 1)->orderBy('name')->get();

        $transactionsByLocation = $transactions->groupBy('location_id');

        $customerBalanceByLocation = CustomerBalance::whereHas('customer', function ($q) use ($branchLocations) {
                $q->where('is_credit_customer', true)
                  ->whereIn('location_id', $branchLocations->pluck('id'));
            })
            ->join('customers', 'customer_balances.customer_id', '=', 'customers.id')
            ->selectRaw('customers.location_id, SUM(customer_balances.cash_balance) as total')
            ->groupBy('customers.location_id')
            ->pluck('total', 'location_id');

        $branchSummary = [];
        foreach ($branchLocations as $loc) {
            $locTx = $transactionsByLocation->get($loc->id, collect());

            $branchSummary[$loc->id] = [
                'credit'           => format_price($locTx->where('type', LocationBalanceTransaction::TYPE_CREDIT)->sum('amount')),
                'debit'            => format_price($locTx->where('type', LocationBalanceTransaction::TYPE_DEBIT)->sum('amount')),
                'balance'          => format_price($loc->cash_balance),
                'customer_balance' => format_price($customerBalanceByLocation->get($loc->id, 0)),
            ];
        }

        return response()->json([
            'status'         => 'success',
            'data'           => $data,
            'branch_summary' => $branchSummary,
        ]);
    }

    public function bankBook(Request $request)
    {
        $this->authorize('view bank book');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $locations = $isRestricted
            ? Location::with('balance')->where('id', $user->location_id)->get()
            : Location::with('balance')->where('status', 1)->orderBy('name')->get();

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

        $sourceFilter = $request->filled('source') ? $request->source : 'all';

        $transactions = $query->orderBy('id', 'desc')->get();

        // Exclude any transactions for deleted Purchases / Sales / Expenses / Purchase Bills
        $transactions = $transactions->filter(function ($tx) {
            $notes = $tx->notes ?? '';
            if (preg_match('/Purchase #([A-Z0-9-]+)/i', $notes, $matches)) {
                $invoiceNo = $matches[1];
                $exists = \App\Models\Purchase::where('invoice_no', $invoiceNo)->exists();
                if (!$exists) return false;
            }
            if (preg_match('/Sale #([A-Z0-9-]+)/i', $notes, $matches)) {
                $orderNo = $matches[1];
                $exists = \App\Models\Order::where('order_no', $orderNo)->exists();
                if (!$exists) return false;
            }
            return true;
        });

        $data = $transactions->filter(function ($tx) use ($sourceFilter) {
            if ($sourceFilter === 'all') {
                return true;
            }
            $notes = $tx->notes ?? '';
            $detectedSource = 'bank';
            if (stripos($notes, 'Opening Balance') !== false || stripos($notes, 'Manual Balance Adjustment') !== false || stripos($notes, 'Manual Account Balance Adjustment') !== false) {
                $detectedSource = 'opening_balance';
            } elseif (stripos($notes, 'Sale #') !== false || stripos($notes, 'Reversal: Sale') !== false) {
                $detectedSource = 'sale';
            } elseif (stripos($notes, 'Purchase Bill') !== false) {
                $detectedSource = 'purchase_bill';
            } elseif (stripos($notes, 'Purchase') !== false || stripos($notes, 'Purchase Payment') !== false) {
                $detectedSource = 'purchase';
            } elseif (stripos($notes, 'Expense') !== false) {
                $detectedSource = 'expense';
            } elseif (stripos($notes, 'Transfer to ') !== false || stripos($notes, 'Transfer from ') !== false) {
                $detectedSource = 'balance_transfer';
            }

            return $detectedSource === $sourceFilter;
        })->values()->map(function ($tx, $index) {
            $notes = $tx->notes ?? '';
            $detectedSource = 'bank';
            if (stripos($notes, 'Opening Balance') !== false || stripos($notes, 'Manual Balance Adjustment') !== false || stripos($notes, 'Manual Account Balance Adjustment') !== false) {
                $detectedSource = 'opening_balance';
            } elseif (stripos($notes, 'Sale #') !== false || stripos($notes, 'Reversal: Sale') !== false) {
                $detectedSource = 'sale';
            } elseif (stripos($notes, 'Purchase Bill') !== false) {
                $detectedSource = 'purchase_bill';
            } elseif (stripos($notes, 'Purchase') !== false || stripos($notes, 'Purchase Payment') !== false) {
                $detectedSource = 'purchase';
            } elseif (stripos($notes, 'Expense') !== false) {
                $detectedSource = 'expense';
            } elseif (stripos($notes, 'Transfer to ') !== false || stripos($notes, 'Transfer from ') !== false) {
                $detectedSource = 'balance_transfer';
            }

            $isCredit = $tx->type === LocationBalanceTransaction::TYPE_CREDIT;
            return [
                'index'         => $index + 1,
                'date'          => format_date($tx->created_at),
                'date_group'    => $tx->created_at->format('d M Y'),
                'date_sort'     => $tx->created_at->format('Ymd'),
                'source_type'   => $detectedSource,
                'location'      => $tx->location->name ?? '-',
                'particulars'   => !empty($tx->notes) ? $tx->notes : 'Manual Balance Adjustment',
                'type'          => $isCredit ? 'credit' : 'debit',
                'type_badge'    => $isCredit ? '<span class="badge bg-label-success">Credit</span>' : '<span class="badge bg-label-danger">Debit</span>',
                'amount'        => format_price($tx->amount),
                'is_credit'     => $isCredit,
                'credit'        => $isCredit ? format_price($tx->amount) : '-',
                'debit'         => !$isCredit ? format_price($tx->amount) : '-',
                'balance_after' => format_price($tx->balance_after),
                'done_by'       => $tx->createdBy->name ?? '-',
            ];
        });

        $branchLocations = $isRestricted
            ? Location::where('id', $user->location_id)->get()
            : Location::where('status', 1)->orderBy('name')->get();

        $transactionsByLocation = $transactions->groupBy('location_id');

        $customerBalanceByLocation = CustomerBalance::whereHas('customer', function ($q) use ($branchLocations) {
                $q->where('is_credit_customer', true)
                  ->whereIn('location_id', $branchLocations->pluck('id'));
            })
            ->join('customers', 'customer_balances.customer_id', '=', 'customers.id')
            ->selectRaw('customers.location_id, SUM(customer_balances.bank_balance) as total')
            ->groupBy('customers.location_id')
            ->pluck('total', 'location_id');

        $branchSummary = [];
        foreach ($branchLocations as $loc) {
            $locTx = $transactionsByLocation->get($loc->id, collect());

            $branchSummary[$loc->id] = [
                'credit'           => format_price($locTx->where('type', LocationBalanceTransaction::TYPE_CREDIT)->sum('amount')),
                'debit'            => format_price($locTx->where('type', LocationBalanceTransaction::TYPE_DEBIT)->sum('amount')),
                'balance'          => format_price($loc->bank_balance),
                'customer_balance' => format_price($customerBalanceByLocation->get($loc->id, 0)),
            ];
        }

        return response()->json([
            'status'         => 'success',
            'data'           => $data,
            'branch_summary' => $branchSummary,
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
            ? Location::with('balance')->where('id', $user->location_id)->get()
            : Location::with('balance')->where('status', 1)->orderBy('name')->get();

        return view('accounting.general-ledger', compact('locations', 'isRestricted'));
    }

    public function generalLedgerData(Request $request)
    {
        $this->authorize('view general ledger');

        $user         = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $filterLocationId  = $isRestricted ? $user->location_id : ($request->filled('location_id') ? $request->location_id : null);
        $startDate         = $request->filled('start_date') ? $request->start_date : null;
        $endDate           = $request->filled('end_date') ? $request->end_date : null;
        $sourceFilter      = $request->filled('source') ? $request->source : 'all';
        $balanceTypeFilter = $request->filled('balance_type') ? $request->balance_type : null;

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
        if ($balanceTypeFilter) {
            $txQuery->where('balance_type', $balanceTypeFilter);
        }

        $transactions = $txQuery->orderBy('id', 'desc')->get();

        // Exclude any transactions for deleted Purchases / Sales / Expenses / Purchase Bills
        $transactions = $transactions->filter(function ($tx) {
            $notes = $tx->notes ?? '';
            if (preg_match('/Purchase #([A-Z0-9-]+)/i', $notes, $matches)) {
                $invoiceNo = $matches[1];
                $exists = \App\Models\Purchase::where('invoice_no', $invoiceNo)->exists();
                if (!$exists) return false;
            }
            if (preg_match('/Sale #([A-Z0-9-]+)/i', $notes, $matches)) {
                $orderNo = $matches[1];
                $exists = \App\Models\Order::where('order_no', $orderNo)->exists();
                if (!$exists) return false;
            }
            return true;
        });

        $entries = collect();

        foreach ($transactions as $tx) {
            $notes = $tx->notes ?? '';
            
            $detectedSource = 'general'; // default
            if (stripos($notes, 'Opening Balance') !== false || stripos($notes, 'Manual Balance Adjustment') !== false || stripos($notes, 'Manual Account Balance Adjustment') !== false) {
                $detectedSource = 'opening_balance';
            } elseif (stripos($notes, 'Sale #') !== false || stripos($notes, 'Reversal: Sale') !== false) {
                $detectedSource = 'sale';
            } elseif (stripos($notes, 'Purchase Bill') !== false) {
                $detectedSource = 'purchase_bill';
            } elseif (stripos($notes, 'Purchase') !== false || stripos($notes, 'Purchase Payment') !== false) {
                $detectedSource = 'purchase';
            } elseif (stripos($notes, 'Expense') !== false) {
                $detectedSource = 'expense';
            } elseif (stripos($notes, 'Transfer to ') !== false || stripos($notes, 'Transfer from ') !== false) {
                $detectedSource = 'balance_transfer';
            }

            // 2. Apply Source Filter if selected
            // 2. Apply Source Filter if selected
            if ($sourceFilter !== 'all' && $sourceFilter !== $detectedSource) {
                continue;
            }

            $isCredit = $tx->type === LocationBalanceTransaction::TYPE_CREDIT;
            
            // Map labels for UI display
            // Map labels for UI display
            $sourceLabels = [
                'cash'             => 'Cash',
                'bank'             => 'Bank',
                'opening_balance'  => 'Opening Balance',
                'sale'             => 'Sale',
                'purchase'         => 'Purchase',
                'expense'          => 'Expense',
                'purchase_bill'    => 'Purchase Bill',
                'balance_transfer' => 'Balance Transfer',
            ];

            $balanceTypeBadge = $tx->balance_type === LocationBalanceTransaction::BALANCE_TYPE_BANK
                ? '<span class="badge bg-label-info">Bank</span>'
                : '<span class="badge bg-label-secondary">Cash</span>';

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
                'index'              => 0, // Will be set during map
                'date'               => format_date($tx->created_at),
                'date_group'         => $tx->created_at->format('d M Y'),
                'date_sort'          => $tx->created_at->format('YmdHis'),
                'source'             => $sourceLabels[$detectedSource] ?? $detectedSource,
                'source_type'        => $detectedSource,
                'balance_type_badge' => $balanceTypeBadge,
                'location'           => $tx->location->name ?? '-',
                'location_id'        => $tx->location_id,
                'particulars'        => !empty($notes) ? $notes : 'Manual Balance Adjustment',
                'type'               => $isCredit ? 'credit' : 'debit',
                'type_badge'         => $isCredit ? '<span class="badge bg-label-success">Credit</span>' : '<span class="badge bg-label-danger">Debit</span>',
                'amount'             => format_price($tx->amount),
                'is_credit'          => $isCredit,
                'credit'             => $isCredit ? format_price($tx->amount) : '-',
                'debit'              => !$isCredit ? format_price($tx->amount) : '-',
                'done_by'            => $tx->createdBy->name ?? '-',
                'ref'                => $ref,
                'raw_credit'         => $isCredit ? (float) $tx->amount : 0,
                'raw_debit'          => !$isCredit ? (float) $tx->amount : 0,
            ]);
        }

        $entries = $entries->unique(function ($item) {
            if (in_array($item['source_type'], ['sale', 'purchase', 'purchase_bill']) && !empty($item['ref']) && strpos($item['ref'], 'TXN#') === false) {
                return $item['source_type'] . '_' . $item['location_id'] . '_' . $item['ref'] . '_' . ($item['balance_type_badge']);
            }
            return 'tx_' . rand() . '_' . microtime();
        })->values();

        $data = $entries->map(function ($item, $index) {
            $item['index'] = $index + 1;
            return $item;
        });

        $branchLocations = $isRestricted
            ? Location::where('id', $user->location_id)->get()
            : Location::where('status', 1)->orderBy('name')->get();

        $entriesByLocation = $entries->groupBy('location_id');

        $branchSummary = [];
        foreach ($branchLocations as $loc) {
            $locEntries = $entriesByLocation->get($loc->id, collect());

            $branchSummary[$loc->id] = [
                'credit'       => format_price($locEntries->sum('raw_credit')),
                'debit'        => format_price($locEntries->sum('raw_debit')),
                'cash_balance' => format_price($loc->cash_balance),
                'bank_balance' => format_price($loc->bank_balance),
            ];
        }

        return response()->json([
            'status'         => 'success',
            'data'           => $data,
            'branch_summary' => $branchSummary,
        ]);
    }

    public function exportGeneralLedger(Request $request)
    {
        $this->authorize('view general ledger');

        $user         = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $filterLocationId  = $isRestricted ? $user->location_id : ($request->filled('location_id') ? $request->location_id : null);
        $startDate         = $request->filled('start_date') ? $request->start_date : null;
        $endDate           = $request->filled('end_date') ? $request->end_date : null;
        $sourceFilter      = $request->filled('source') ? $request->source : 'all';
        $balanceTypeFilter = $request->filled('balance_type') ? $request->balance_type : null;

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
        if ($balanceTypeFilter) {
            $txQuery->where('balance_type', $balanceTypeFilter);
        }

        $transactions = $txQuery->orderBy('id', 'desc')->get();

        // Exclude any transactions for deleted Purchases / Sales / Expenses / Purchase Bills
        $transactions = $transactions->filter(function ($tx) {
            $notes = $tx->notes ?? '';
            if (preg_match('/Purchase #([A-Z0-9-]+)/i', $notes, $matches)) {
                $invoiceNo = $matches[1];
                $exists = \App\Models\Purchase::where('invoice_no', $invoiceNo)->exists();
                if (!$exists) return false;
            }
            if (preg_match('/Sale #([A-Z0-9-]+)/i', $notes, $matches)) {
                $orderNo = $matches[1];
                $exists = \App\Models\Order::where('order_no', $orderNo)->exists();
                if (!$exists) return false;
            }
            return true;
        });

        $sourceLabels = [
            'cash'             => 'Cash',
            'bank'             => 'Bank',
            'opening_balance'  => 'Opening Balance',
            'sale'             => 'Sale',
            'purchase'         => 'Purchase',
            'expense'          => 'Expense',
            'purchase_bill'    => 'Purchase Bill',
            'balance_transfer' => 'Balance Transfer',
        ];

        $rows = collect();

        foreach ($transactions as $tx) {
            $notes = $tx->notes ?? '';
            $detectedSource = 'general';
            if (stripos($notes, 'Opening Balance') !== false || stripos($notes, 'Manual Balance Adjustment') !== false || stripos($notes, 'Manual Account Balance Adjustment') !== false) {
                $detectedSource = 'opening_balance';
            } elseif (stripos($notes, 'Sale #') !== false || stripos($notes, 'Reversal: Sale') !== false) {
                $detectedSource = 'sale';
            } elseif (stripos($notes, 'Purchase Bill') !== false) {
                $detectedSource = 'purchase_bill';
            } elseif (stripos($notes, 'Purchase') !== false || stripos($notes, 'Purchase Payment') !== false) {
                $detectedSource = 'purchase';
            } elseif (stripos($notes, 'Expense') !== false) {
                $detectedSource = 'expense';
            } elseif (stripos($notes, 'Transfer to ') !== false || stripos($notes, 'Transfer from ') !== false) {
                $detectedSource = 'balance_transfer';
            }

            if ($sourceFilter !== 'all' && $sourceFilter !== $detectedSource) {
                continue;
            }

            $isCredit = $tx->type === LocationBalanceTransaction::TYPE_CREDIT;

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

            $rows->push([
                'date'         => $tx->created_at,
                'source'       => $sourceLabels[$detectedSource] ?? $detectedSource,
                'source_type'  => $detectedSource,
                'balance_type' => $tx->balance_type === LocationBalanceTransaction::BALANCE_TYPE_BANK ? 'Bank' : 'Cash',
                'location'     => $tx->location->name ?? '-',
                'location_id'  => $tx->location_id,
                'particulars'  => !empty($notes) ? $notes : 'Manual Balance Adjustment',
                'is_credit'    => $isCredit,
                'amount'       => (float) $tx->amount,
                'done_by'      => $tx->createdBy->name ?? '-',
                'ref'          => $ref,
            ]);
        }

        $rows = $rows->unique(function ($item) {
            if (in_array($item['source_type'], ['sale', 'purchase', 'purchase_bill']) && !empty($item['ref']) && strpos($item['ref'], 'TXN#') === false) {
                return $item['source_type'] . '_' . $item['location_id'] . '_' . $item['ref'] . '_' . $item['balance_type'];
            }
            return 'tx_' . rand() . '_' . microtime();
        })->values();

        $totalCredit = $rows->where('is_credit', true)->sum('amount');
        $totalDebit  = $rows->where('is_credit', false)->sum('amount');

        if ($rows->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected filters. Nothing to export.');
        }

        if ($request->boolean('auto_print') && !$request->boolean('stream')) {
            return view('sales.pdf-print-wrapper', [
                'title'  => 'General Ledger',
                'pdfUrl' => route('admin.accounting.general-ledger.export', array_merge($request->all(), ['stream' => 1])),
            ]);
        }

        $pdf = Pdf::loadView('accounting.pdf.general-ledger', compact(
            'rows',
            'startDate',
            'endDate',
            'totalCredit',
            'totalDebit',
            'isRestricted'
        ))->setPaper('a4', 'landscape');

        ActivityLogger::log('Accounting', 'export', null, null, null, 'General ledger exported to PDF');

        return $pdf->stream('general_ledger_' . now()->format('Ymd_His') . '.pdf');
    }

    public function outstandingPayables(Request $request)
    {
        $this->authorize('view outstanding payables');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $locations = $isRestricted
            ? Location::where('id', $user->location_id)->get()
            : Location::where('status', 1)->orderBy('name')->get();

        $suppliers = \App\Models\Supplier::orderBy('name')->get();

        return view('accounting.outstanding-payables', compact('locations', 'isRestricted', 'suppliers'));
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
        $groupByDateSupplier = fn ($items) => $items->groupBy(function ($purchase) {
            $date = $purchase->created_at ? $purchase->created_at->format('Y-m-d') : now()->format('Y-m-d');
            return $date . '_' . ($purchase->supplier_id ?? 0);
        });

        $grouped = $groupByDateSupplier($purchases);

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

        // Branch-wise summary cards: restricted users only ever get their own
        // branch; super-admins get every active branch, narrowed to the
        // filtered one if selected.
        $branchLocations = $isRestricted
            ? Location::where('id', $user->location_id)->get()
            : Location::where('status', 1)->orderBy('name')->get();

        $purchasesByLocation = $purchases->groupBy('location_id');

        $branchSummary = [];
        foreach ($branchLocations as $loc) {
            $locPurchase = 0.0;
            $locPayment = 0.0;
            $locOutstanding = 0.0;

            $locGrouped = $groupByDateSupplier($purchasesByLocation->get($loc->id, collect()));
            foreach ($locGrouped as $items) {
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

                $locPurchase += $sumTotal;
                $locPayment += $sumPaid;
                $locOutstanding += $due;
            }

            $branchSummary[$loc->id] = [
                'purchase'    => format_price($locPurchase),
                'payment'     => format_price($locPayment),
                'outstanding' => format_price($locOutstanding),
            ];
        }

        return response()->json([
            'status'         => 'success',
            'data'           => $mappedRows,
            'branch_summary' => $branchSummary,
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

    public function bulkPaySupplier(Request $request)
    {
        $this->authorize('view outstanding payables');

        $request->validate([
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'location_id'    => ['nullable', 'integer'],
            'payment_method' => ['required', 'string', 'in:cash,online'],
        ]);

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $paymentMethod = $request->input('payment_method', 'cash');

        $purchasesQuery = \App\Models\Purchase::whereIn('payment_status', [
                \App\Models\Purchase::PAYMENT_STATUS_PENDING,
                \App\Models\Purchase::PAYMENT_STATUS_PARTIAL,
            ]);

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

        // FIFO: Oldest purchases first across suppliers (or selected supplier)
        $purchases = $purchasesQuery->orderBy('created_at', 'asc')->get();

        if ($purchases->isEmpty()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No outstanding purchases found.',
            ], 422);
        }

        // Calculate total outstanding balance for validation
        $totalOutstandingDue = 0.0;
        foreach ($purchases as $p) {
            $cPaid = $p->payment_status == \App\Models\Purchase::PAYMENT_STATUS_PAID
                ? (float) $p->total_amount
                : ($p->payment_status == \App\Models\Purchase::PAYMENT_STATUS_PENDING ? 0.0 : (float) $p->paid_amount);
            $totalOutstandingDue += max(0.0, (float) $p->total_amount - $cPaid);
        }
        $totalOutstandingDue = round($totalOutstandingDue, 2);

        $enteredAmount = round((float) $request->amount, 2);
        if ($enteredAmount > $totalOutstandingDue) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Paid amount cannot be greater than the total outstanding balance due (' . format_price($totalOutstandingDue) . ').',
            ], 422);
        }

        $remainingPayment = $enteredAmount;
        $totalPaidAllocated = 0.0;
        $billsPaidCount = 0;

        \Illuminate\Support\Facades\DB::transaction(function () use (
            $purchases,
            $enteredAmount,
            $paymentMethod,
            $request,
            &$remainingPayment,
            &$totalPaidAllocated,
            &$billsPaidCount
        ) {
            // Record single consolidated lump sum payment entry
            \App\Models\BulkPurchasePayment::create([
                'total_amount'   => $enteredAmount,
                'location_id'    => $request->filled('location_id') ? (int)$request->location_id : auth()->user()->location_id,
                'payment_method' => $paymentMethod,
                'created_by'     => auth()->id(),
            ]);

            foreach ($purchases as $purchase) {
                if ($remainingPayment <= 0) {
                    break;
                }

                $currentPaid = $purchase->payment_status == \App\Models\Purchase::PAYMENT_STATUS_PAID
                    ? (float) $purchase->total_amount
                    : ($purchase->payment_status == \App\Models\Purchase::PAYMENT_STATUS_PENDING ? 0.0 : (float) $purchase->paid_amount);

                $due = round((float) $purchase->total_amount - $currentPaid, 2);
                if ($due <= 0) {
                    continue;
                }

                $payForThisBill = min($due, $remainingPayment);
                $newPaidAmount = round($currentPaid + $payForThisBill, 2);

                $finalStatus = ($newPaidAmount >= (float) $purchase->total_amount)
                    ? \App\Models\Purchase::PAYMENT_STATUS_PAID
                    : \App\Models\Purchase::PAYMENT_STATUS_PARTIAL;

                $oldStatus = (int) $purchase->payment_status;
                $oldPaid = (float) $purchase->paid_amount;

                \App\Models\PurchasePayment::create([
                    'purchase_id' => $purchase->id,
                    'amount'      => $payForThisBill,
                    'created_by'  => auth()->id(),
                ]);

                \App\Models\Purchase::withoutActivityLogging(fn () => $purchase->update([
                    'payment_status' => $finalStatus,
                    'paid_amount'    => min($newPaidAmount, (float) $purchase->total_amount),
                    'payment_method' => $paymentMethod,
                ]));

                \App\Services\ActivityLogger::log(
                    'Purchase',
                    'update',
                    $purchase,
                    ['payment_status' => $oldStatus, 'paid_amount' => $oldPaid],
                    ['payment_status' => (int) $purchase->payment_status, 'paid_amount' => (float) $purchase->paid_amount],
                    'Purchase #' . $purchase->invoice_no . ' payment status updated via bulk payment'
                );

                $remainingPayment = round($remainingPayment - $payForThisBill, 2);
                $totalPaidAllocated = round($totalPaidAllocated + $payForThisBill, 2);
                $billsPaidCount++;
            }
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Successfully allocated ' . format_price($totalPaidAllocated) . ' across ' . $billsPaidCount . ' bill(s).',
        ]);
    }

    public function payablePaymentHistory(Request $request)
    {
        $this->authorize('view outstanding payables');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $locations = $isRestricted
            ? Location::where('id', $user->location_id)->get()
            : Location::where('status', 1)->orderBy('name')->get();

        return view('accounting.payable-payment-history', compact('locations', 'isRestricted'));
    }

    public function payablePaymentHistoryData(Request $request)
    {
        $this->authorize('view outstanding payables');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $paymentsQuery = \App\Models\BulkPurchasePayment::with(['location', 'createdBy']);

        if ($isRestricted) {
            $paymentsQuery->where('location_id', $user->location_id);
        } else {
            if ($request->filled('location_id')) {
                $paymentsQuery->where('location_id', (int) $request->location_id);
            }
        }

        if ($request->filled('start_date')) {
            $paymentsQuery->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $paymentsQuery->whereDate('created_at', '<=', $request->end_date);
        }

        $payments = $paymentsQuery->orderBy('created_at', 'desc')->get();

        $rows = $payments->map(function ($payment, $index) {
            return [
                'index'        => $index + 1,
                'id'           => $payment->id,
                'date'         => format_date($payment->created_at, 'd-m-Y H:i A'),
                'date_sort'    => $payment->created_at ? $payment->created_at->format('Y-m-d H:i:s') : '',
                'amount'       => format_price($payment->total_amount),
                'amount_raw'   => (float) $payment->total_amount,
                'location'     => e($payment->location->name ?? 'All Locations'),
                'created_by'   => e($payment->createdBy->name ?? 'System'),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $rows,
        ]);
    }

    public function branchBalances()
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);
        $this->authorize('manage branch balances');

        $locations = Location::with('balance')->where('status', 1)->orderBy('name')->get();

        $customerBalanceByLocation = CustomerBalance::whereHas('customer', function ($q) use ($locations) {
                $q->where('is_credit_customer', true)
                  ->whereIn('location_id', $locations->pluck('id'));
            })
            ->join('customers', 'customer_balances.customer_id', '=', 'customers.id')
            ->selectRaw('customers.location_id, SUM(customer_balances.balance) as total')
            ->groupBy('customers.location_id')
            ->pluck('total', 'location_id');

        return view('accounting.branch-balances', compact('locations', 'customerBalanceByLocation'));
    }

    public function branchBalancesData(Request $request)
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);
        $this->authorize('manage branch balances');

        $query = LocationBalanceTransaction::with(['location', 'createdBy']);

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }
        if ($request->filled('balance_type')) {
            $query->where('balance_type', $request->balance_type);
        }

        $sourceFilter = $request->filled('source') ? $request->source : 'all';

        $transactions = $query->orderBy('id', 'desc')->get();

        $data = $transactions->filter(function ($tx) use ($sourceFilter) {
            if ($sourceFilter === 'all') {
                return true;
            }
            $notes = $tx->notes ?? '';
            $detectedSource = $tx->balance_type === LocationBalanceTransaction::BALANCE_TYPE_BANK ? 'bank' : 'cash';

            if (stripos($notes, 'Opening Balance') !== false || stripos($notes, 'Manual Balance Adjustment') !== false || stripos($notes, 'Manual Account Balance Adjustment') !== false) {
                $detectedSource = 'opening_balance';
            } elseif (stripos($notes, 'Sale #') !== false || stripos($notes, 'Reversal: Sale') !== false) {
                $detectedSource = 'sale';
            } elseif (stripos($notes, 'Purchase Bill') !== false) {
                $detectedSource = 'purchase_bill';
            } elseif (stripos($notes, 'Purchase') !== false || stripos($notes, 'Purchase Payment') !== false) {
                $detectedSource = 'purchase';
            } elseif (stripos($notes, 'Expense') !== false) {
                $detectedSource = 'expense';
            } elseif (stripos($notes, 'Transfer to ') !== false || stripos($notes, 'Transfer from ') !== false) {
                $detectedSource = 'balance_transfer';
            }

            return $detectedSource === $sourceFilter;
        })->values()->map(function ($tx, $index) {
            $notes = $tx->notes ?? '';
            $detectedSource = $tx->balance_type === LocationBalanceTransaction::BALANCE_TYPE_BANK ? 'bank' : 'cash';

            if (stripos($notes, 'Opening Balance') !== false || stripos($notes, 'Manual Balance Adjustment') !== false || stripos($notes, 'Manual Account Balance Adjustment') !== false) {
                $detectedSource = 'opening_balance';
            } elseif (stripos($notes, 'Sale #') !== false || stripos($notes, 'Reversal: Sale') !== false) {
                $detectedSource = 'sale';
            } elseif (stripos($notes, 'Purchase Bill') !== false) {
                $detectedSource = 'purchase_bill';
            } elseif (stripos($notes, 'Purchase') !== false || stripos($notes, 'Purchase Payment') !== false) {
                $detectedSource = 'purchase';
            } elseif (stripos($notes, 'Expense') !== false) {
                $detectedSource = 'expense';
            } elseif (stripos($notes, 'Transfer to ') !== false || stripos($notes, 'Transfer from ') !== false) {
                $detectedSource = 'balance_transfer';
            }

            $isCredit = $tx->type === LocationBalanceTransaction::TYPE_CREDIT;
            $typeBadge = $isCredit
                ? '<span class="badge bg-label-success">Credit</span>'
                : '<span class="badge bg-label-danger">Debit</span>';

            $balanceTypeBadge = $tx->balance_type === LocationBalanceTransaction::BALANCE_TYPE_BANK
                ? '<span class="badge bg-label-info">Bank</span>'
                : '<span class="badge bg-label-secondary">Cash</span>';

            $amountFormatted = ($isCredit ? '+ ' : '- ') . format_price($tx->amount);
            $amountSpan = '<span class="' . ($isCredit ? 'text-success' : 'text-danger') . ' text-nowrap">' . $amountFormatted . '</span>';

            return [
                'index'         => $index + 1,
                'time'          => $tx->created_at->format('h:i A'),
                'branch_name'   => $tx->location->name ?? '-',
                'source_type'   => $detectedSource,
                'balance_type'  => $balanceTypeBadge,
                'type'          => $typeBadge,
                'amount'        => $amountSpan,
                'balance_after' => format_price($tx->balance_after),
                'notes'         => (!empty($tx->notes) && $tx->notes !== 'Manual Account Balance Adjustment') ? e($tx->notes) : 'Opening Balance Added',
                'created_by'    => e($tx->createdBy->name ?? '-'),
                'date_group'    => $tx->created_at->format('d M Y'),
                'date_sort'     => $tx->created_at->format('YmdHis'),
            ];
        });

        $totalCash = LocationBalance::whereHas('location', fn($q) => $q->where('status', 1))->sum('cash_balance');
        $totalBank = LocationBalance::whereHas('location', fn($q) => $q->where('status', 1))->sum('bank_balance');

        $locations = Location::with('balance')->where('status', 1)->orderBy('name')->get();

        $customerBalanceByLocation = CustomerBalance::whereHas('customer', function ($q) use ($locations) {
                $q->where('is_credit_customer', true)
                  ->whereIn('location_id', $locations->pluck('id'));
            })
            ->join('customers', 'customer_balances.customer_id', '=', 'customers.id')
            ->selectRaw('customers.location_id, SUM(customer_balances.balance) as total')
            ->groupBy('customers.location_id')
            ->pluck('total', 'location_id');

        $branchBalances = [];
        foreach ($locations as $loc) {
            $branchBalances[$loc->id] = [
                'cash'             => format_price($loc->cash_balance),
                'bank'             => format_price($loc->bank_balance),
                'customer_balance' => format_price($customerBalanceByLocation->get($loc->id, 0)),
            ];
        }

        return response()->json([
            'status'  => 'success',
            'data'    => $data,
            'summary' => [
                'total_cash' => format_price($totalCash),
                'total_bank' => format_price($totalBank),
            ],
            'branch_balances' => $branchBalances,
        ]);
    }

    public function branchBalancesCreate()
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);
        $this->authorize('manage branch balances');

        $locations = Location::where('status', 1)->orderBy('name')->get();

        return view('accounting.branch-balances-create', compact('locations'));
    }

    public function branchBalancesStore(Request $request)
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);
        $this->authorize('manage branch balances');

        $validator = Validator::make($request->all(), [
            'location_id'  => ['required', 'integer', 'exists:locations,id'],
            'balance_type' => ['required', 'string', 'in:' . LocationBalanceTransaction::BALANCE_TYPE_CASH . ',' . LocationBalanceTransaction::BALANCE_TYPE_BANK],
            'type'         => ['required', 'string', 'in:' . LocationBalanceTransaction::TYPE_CREDIT . ',' . LocationBalanceTransaction::TYPE_DEBIT],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $locationId = (int) $request->location_id;
        $balanceColumn = $request->balance_type === LocationBalanceTransaction::BALANCE_TYPE_BANK
            ? 'bank_balance'
            : 'cash_balance';

        try {
            DB::transaction(function () use ($request, $locationId, $balanceColumn) {
                $balance = LocationBalance::where('location_id', $locationId)->lockForUpdate()->firstOrFail();

                $currentBalance = (float) $balance->{$balanceColumn};
                $amount = (float) $request->amount;

                $newBalance = $request->type === LocationBalanceTransaction::TYPE_CREDIT
                    ? $currentBalance + $amount
                    : $currentBalance - $amount;

                if ($request->type === LocationBalanceTransaction::TYPE_DEBIT && $newBalance < 0) {
                    throw new \RuntimeException('insufficient_balance');
                }

                $balance->update([$balanceColumn => $newBalance]);

                $transaction = LocationBalanceTransaction::create([
                    'location_id'   => $locationId,
                    'balance_type'  => $request->balance_type,
                    'type'          => $request->type,
                    'amount'        => $amount,
                    'balance_after' => $newBalance,
                    'notes'         => !empty($request->notes) ? $request->notes : 'Opening Balance Added',
                    'created_by'    => auth()->id(),
                ]);

                ActivityLogger::log(
                    'Opening Balance',
                    'create',
                    $transaction,
                    [$balanceColumn => $currentBalance],
                    [$balanceColumn => $newBalance],
                    'Opening balance entry recorded for "' . ($transaction->location->name ?? $locationId) . '" (' . $request->balance_type . ', ' . $request->type . ' ' . format_price($amount) . ')'
                );
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'insufficient_balance') {
                return response()->json([
                    'status'  => 'error',
                    'message' => ['amount' => ['Insufficient balance.']],
                ], 422);
            }

            throw $e;
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Balance entry recorded successfully.',
        ]);
    }

    public function branchBalancesTransferCreate()
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);
        $this->authorize('manage branch balances');

        $locations = Location::with('balance')->where('status', 1)->orderBy('name')->get();

        return view('accounting.branch-balances-transfer', compact('locations'));
    }

    public function branchBalancesTransferStore(Request $request)
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);
        $this->authorize('manage branch balances');

        $validator = Validator::make($request->all(), [
            'from_location_id' => ['required', 'integer', 'exists:locations,id'],
            'to_location_id'   => ['required', 'integer', 'exists:locations,id', 'different:from_location_id'],
            'balance_type'     => ['required', 'string', 'in:' . LocationBalanceTransaction::BALANCE_TYPE_CASH . ',' . LocationBalanceTransaction::BALANCE_TYPE_BANK],
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ], [
            'to_location_id.different' => 'To Location must be different from From Location.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $fromLocationId = (int) $request->from_location_id;
        $toLocationId   = (int) $request->to_location_id;
        $balanceColumn  = $request->balance_type === LocationBalanceTransaction::BALANCE_TYPE_BANK ? 'bank_balance' : 'cash_balance';
        $amount         = (float) $request->amount;

        try {
            DB::transaction(function () use ($request, $fromLocationId, $toLocationId, $balanceColumn, $amount) {
                $fromBalanceRecord = LocationBalance::firstOrCreate(['location_id' => $fromLocationId]);
                $toBalanceRecord   = LocationBalance::firstOrCreate(['location_id' => $toLocationId]);

                // Lock records for update
                $fromBalanceRecord = LocationBalance::where('id', $fromBalanceRecord->id)->lockForUpdate()->first();
                $toBalanceRecord   = LocationBalance::where('id', $toBalanceRecord->id)->lockForUpdate()->first();

                $fromCurrent = (float) $fromBalanceRecord->{$balanceColumn};
                if ($fromCurrent < $amount) {
                    throw new \RuntimeException('insufficient_balance');
                }

                $toCurrent = (float) $toBalanceRecord->{$balanceColumn};

                $fromNew = $fromCurrent - $amount;
                $toNew   = $toCurrent + $amount;

                $fromBalanceRecord->update([$balanceColumn => $fromNew]);
                $toBalanceRecord->update([$balanceColumn => $toNew]);

                $fromLocName = Location::find($fromLocationId)->name ?? $fromLocationId;
                $toLocName   = Location::find($toLocationId)->name ?? $toLocationId;

                $userNotes = !empty($request->notes) ? ' (Note: ' . $request->notes . ')' : '';

                // Create debit transaction for sender
                LocationBalanceTransaction::create([
                    'location_id'   => $fromLocationId,
                    'balance_type'  => $request->balance_type,
                    'type'          => LocationBalanceTransaction::TYPE_DEBIT,
                    'amount'        => $amount,
                    'balance_after' => $fromNew,
                    'notes'         => 'Transfer to ' . $toLocName . $userNotes,
                    'created_by'    => auth()->id(),
                ]);

                // Create credit transaction for receiver
                LocationBalanceTransaction::create([
                    'location_id'   => $toLocationId,
                    'balance_type'  => $request->balance_type,
                    'type'          => LocationBalanceTransaction::TYPE_CREDIT,
                    'amount'        => $amount,
                    'balance_after' => $toNew,
                    'notes'         => 'Transfer from ' . $fromLocName . $userNotes,
                    'created_by'    => auth()->id(),
                ]);

                $fromUnpaidBills = \App\Models\PurchaseBill::with('items.product', 'items.variant')
                    ->where('from_location_id', $toLocationId)
                    ->where('to_location_id', $fromLocationId)
                    ->where('status', \App\Models\PurchaseBill::STATUS_ACCEPTED)
                    ->whereIn('payment_status', [
                        \App\Models\PurchaseBill::PAYMENT_STATUS_PENDING,
                        \App\Models\PurchaseBill::PAYMENT_STATUS_PARTIAL
                    ])
                    ->orderBy('created_at', 'asc')
                    ->get();

                $remAmt = $amount;
                foreach ($fromUnpaidBills as $pBill) {
                    if ($remAmt <= 0) break;

                    [$billTotal] = $this->purchaseBillTotals($pBill);
                    if ($billTotal <= 0) {
                        $pBill->update([
                            'payment_status' => \App\Models\PurchaseBill::PAYMENT_STATUS_PAID,
                        ]);
                        continue;
                    }

                    $currentPaid = $pBill->payment_status == \App\Models\PurchaseBill::PAYMENT_STATUS_PAID
                        ? (float) $billTotal
                        : ($pBill->payment_status == \App\Models\PurchaseBill::PAYMENT_STATUS_PENDING ? 0.0 : (float) $pBill->paid_amount);

                    $dueAmt = round((float) $billTotal - $currentPaid, 2);
                    if ($dueAmt <= 0) continue;

                    $payAmt = min($dueAmt, $remAmt);
                    $newPaid = round($currentPaid + $payAmt, 2);

                    $finalStatus = ($newPaid >= (float) $billTotal)
                        ? \App\Models\PurchaseBill::PAYMENT_STATUS_PAID
                        : \App\Models\PurchaseBill::PAYMENT_STATUS_PARTIAL;

                    \App\Models\PurchaseBillPayment::create([
                        'purchase_bill_id' => $pBill->id,
                        'amount'           => $payAmt,
                        'created_by'       => auth()->id(),
                    ]);

                    $pBill->update([
                        'payment_status' => $finalStatus,
                        'paid_amount'    => min($newPaid, (float) $billTotal),
                    ]);

                    $remAmt = round($remAmt - $payAmt, 2);
                }

                ActivityLogger::log(
                    'Balance Transfer',
                    'transfer',
                    null,
                    ['from' => $fromCurrent, 'to' => $toCurrent],
                    ['from' => $fromNew, 'to' => $toNew],
                    'Balance transfer of ' . format_price($amount) . ' (' . $request->balance_type . ') from "' . $fromLocName . '" to "' . $toLocName . '"'
                );
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'insufficient_balance') {
                return response()->json([
                    'status'  => 'error',
                    'message' => ['amount' => ['Insufficient balance in From Location.']],
                ], 422);
            }

            throw $e;
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Balance transferred successfully.',
        ]);
    }

    // ============================================================
    // CUSTOMER BALANCE (accessible from Cash Book / Bank Book)
    // ============================================================

    public function customerBalanceCreate(Request $request)
    {
        $this->authorize('manage customer balance');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        $customers = Customer::where('is_credit_customer', true)
            ->when($isRestricted, fn ($q) => $q->where('location_id', $user->location_id))
            ->orderBy('name')->get();
        $source = in_array($request->source, [CustomerBalanceTransaction::SOURCE_BANK, CustomerBalanceTransaction::SOURCE_CASH])
            ? $request->source
            : CustomerBalanceTransaction::SOURCE_CASH;
        $selectedCustomerId = $request->query('customer_id');
        $lockedCustomer = $selectedCustomerId ? $customers->firstWhere('id', (int) $selectedCustomerId) : null;

        return view('accounting.customer-balance-create', compact('customers', 'source', 'selectedCustomerId', 'lockedCustomer'));
    }

    public function customerBalanceStore(Request $request)
    {
        $this->authorize('manage customer balance');

        $validator = Validator::make($request->all(), [
            'customer_id' => ['required', 'integer', 'exists:customers,id,is_credit_customer,1'],
            'source'      => ['required', 'string', 'in:' . CustomerBalanceTransaction::SOURCE_CASH . ',' . CustomerBalanceTransaction::SOURCE_BANK],
            'type'        => ['required', 'string', 'in:' . CustomerBalanceTransaction::TYPE_CREDIT . ',' . CustomerBalanceTransaction::TYPE_DEBIT],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'notes'       => ['nullable', 'string', 'max:1000'],
        ], [], [
            'customer_id' => 'customer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $customerId = (int) $request->customer_id;

        $viewer = auth()->user();
        if ($viewer->location_id && !$viewer->hasRole('super-admin')) {
            $ownsCustomer = Customer::where('id', $customerId)->where('location_id', $viewer->location_id)->exists();
            if (!$ownsCustomer) {
                return response()->json(['status' => 'error', 'message' => 'You can only manage balances for customers in your own branch.'], 403);
            }
        }

        $createdTransaction = null;

        try {
            DB::transaction(function () use ($request, $customerId, &$createdTransaction) {
                $custBalance = CustomerBalance::firstOrCreate(
                    ['customer_id' => $customerId],
                    ['balance' => 0.00, 'cash_balance' => 0.00, 'bank_balance' => 0.00]
                );

                $currentBalance = (float) $custBalance->balance;
                $amount = (float) $request->amount;

                $isCredit = $request->type === CustomerBalanceTransaction::TYPE_CREDIT;
                $newBalance = $isCredit
                    ? $currentBalance + $amount
                    : $currentBalance - $amount;

                if (!$isCredit && $newBalance < 0) {
                    throw new \RuntimeException('insufficient_balance');
                }

                $transaction = CustomerBalanceTransaction::create([
                    'customer_id'   => $customerId,
                    'source'        => $request->source,
                    'type'          => $request->type,
                    'amount'        => $amount,
                    'balance_after' => $newBalance,
                    'notes'         => !empty($request->notes) ? $request->notes : 'Manual Customer Balance Adjustment',
                    'created_by'    => auth()->id(),
                ]);

                $createdTransaction = $transaction;

                ActivityLogger::log(
                    'Customer Balance',
                    'create',
                    $transaction,
                    ['balance' => $currentBalance],
                    ['balance' => $newBalance],
                    'Customer balance entry recorded for "' . ($customer->name ?? $customerId) . '" (' . $request->source . ', ' . $request->type . ' ' . format_price($amount) . ')'
                );
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'insufficient_balance') {
                return response()->json([
                    'status'  => 'error',
                    'message' => ['amount' => ['Insufficient balance.']],
                ], 422);
            }

            throw $e;
        }

        return response()->json([
            'status'    => 'success',
            'message'   => 'Customer balance entry recorded successfully.',
            'print_url' => route('admin.accounting.customer-balance.thermal', ['transaction' => $createdTransaction->id, 'auto_print' => 1]),
        ]);
    }

    public function customerBalanceEdit(CustomerBalanceTransaction $transaction)
    {
        $this->authorize('edit customer balance');

        if (!empty($transaction->notes) && preg_match('/Sale #/i', $transaction->notes)) {
            abort(403, 'Sales order balance transactions cannot be edited directly.');
        }

        $user = auth()->user();
        if ($user->location_id && !$user->hasRole('super-admin') && $transaction->customer && $transaction->customer->location_id !== $user->location_id) {
            abort(403);
        }

        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        $customers = Customer::where('is_credit_customer', true)
            ->when($isRestricted, fn ($q) => $q->where('location_id', $user->location_id))
            ->orderBy('name')->get();

        return view('accounting.customer-balance-edit', compact('transaction', 'customers'));
    }

    public function customerBalanceUpdate(Request $request, CustomerBalanceTransaction $transaction)
    {
        $this->authorize('edit customer balance');

        if (!empty($transaction->notes) && preg_match('/Sale #/i', $transaction->notes)) {
            return response()->json(['status' => 'error', 'message' => 'Sales order balance transactions cannot be edited directly.'], 403);
        }

        $user = auth()->user();
        if ($user->location_id && !$user->hasRole('super-admin') && $transaction->customer && $transaction->customer->location_id !== $user->location_id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => ['required', 'integer', 'exists:customers,id,is_credit_customer,1'],
            'source'      => ['required', 'string', 'in:' . CustomerBalanceTransaction::SOURCE_CASH . ',' . CustomerBalanceTransaction::SOURCE_BANK],
            'type'        => ['required', 'string', 'in:' . CustomerBalanceTransaction::TYPE_CREDIT . ',' . CustomerBalanceTransaction::TYPE_DEBIT],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'notes'       => ['nullable', 'string', 'max:1000'],
        ], [], [
            'customer_id' => 'customer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $newCustomerId = (int) $request->customer_id;
        if ($user->location_id && !$user->hasRole('super-admin')) {
            $ownsCustomer = Customer::where('id', $newCustomerId)->where('location_id', $user->location_id)->exists();
            if (!$ownsCustomer) {
                return response()->json(['status' => 'error', 'message' => 'You can only manage balances for customers in your own branch.'], 403);
            }
        }

        try {
            DB::transaction(function () use ($request, $transaction, $newCustomerId) {
                $custBalance = CustomerBalance::firstOrCreate(
                    ['customer_id' => $newCustomerId],
                    ['balance' => 0.00, 'cash_balance' => 0.00, 'bank_balance' => 0.00]
                );

                $prevEffect = $transaction->type === CustomerBalanceTransaction::TYPE_CREDIT
                    ? (float) $transaction->amount
                    : -((float) $transaction->amount);

                $newAmount = (float) $request->amount;
                $newEffect = $request->type === CustomerBalanceTransaction::TYPE_CREDIT
                    ? $newAmount
                    : -$newAmount;

                if ((int) $transaction->customer_id === $newCustomerId) {
                    $projectedBalance = (float) $custBalance->balance - $prevEffect + $newEffect;
                } else {
                    $projectedBalance = (float) $custBalance->balance + $newEffect;
                }

                if ($projectedBalance < -0.001) {
                    throw new \RuntimeException('insufficient_balance');
                }

                $oldData = $transaction->toArray();

                $transaction->update([
                    'customer_id' => $newCustomerId,
                    'source'      => $request->source,
                    'type'        => $request->type,
                    'amount'      => $newAmount,
                    'notes'       => !empty($request->notes) ? $request->notes : 'Manual Customer Balance Adjustment',
                ]);

                $transaction->refresh();

                ActivityLogger::log(
                    'Customer Balance',
                    'update',
                    $transaction,
                    $oldData,
                    $transaction->toArray(),
                    'Customer balance entry updated for transaction #' . $transaction->id
                );
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'insufficient_balance') {
                return response()->json([
                    'status'  => 'error',
                    'message' => ['amount' => ['Insufficient customer balance. Debit amount exceeds available balance.']],
                ], 422);
            }

            throw $e;
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer balance entry updated successfully.',
        ]);
    }

    public function customerBalanceDestroy(CustomerBalanceTransaction $transaction)
    {
        $this->authorize('delete customer balance');

        if (!empty($transaction->notes) && preg_match('/Sale #/i', $transaction->notes)) {
            return response()->json(['status' => 'error', 'message' => 'Sales order balance transactions cannot be deleted directly.'], 403);
        }

        $user = auth()->user();
        if ($user->location_id && !$user->hasRole('super-admin') && $transaction->customer && $transaction->customer->location_id !== $user->location_id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        try {
            DB::transaction(function () use ($transaction) {
                $custBalance = CustomerBalance::where('customer_id', $transaction->customer_id)->first();
                if ($custBalance && $transaction->type === CustomerBalanceTransaction::TYPE_CREDIT) {
                    $projectedBalance = (float) $custBalance->balance - (float) $transaction->amount;
                    if ($projectedBalance < -0.001) {
                        throw new \RuntimeException('insufficient_balance_delete');
                    }
                }

                $oldData = $transaction->toArray();
                $transactionId = $transaction->id;

                $transaction->delete();

                ActivityLogger::log(
                    'Customer Balance',
                    'delete',
                    null,
                    $oldData,
                    [],
                    'Customer balance entry #' . $transactionId . ' deleted'
                );
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'insufficient_balance_delete') {
                return response()->json([
                    'status'  => 'error',
                    'message' => ['amount' => ['Cannot delete this credit entry because customer has insufficient remaining balance.']],
                ], 422);
            }

            throw $e;
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer balance entry deleted successfully.',
        ]);
    }

    public function customerBalanceThermal(CustomerBalanceTransaction $transaction)
    {
        $this->authorize('manage customer balance');

        $transaction->load(['customer', 'createdBy']);

        $user = auth()->user();
        if ($user->location_id && !$user->hasRole('super-admin') && $transaction->customer && $transaction->customer->location_id !== $user->location_id) {
            abort(403);
        }

        if (request()->boolean('auto_print') && !request()->boolean('stream')) {
            return view('sales.pdf-print-wrapper', [
                'title'  => 'Customer Balance Receipt #' . $transaction->id,
                'pdfUrl' => route('admin.accounting.customer-balance.thermal', [$transaction, 'stream' => 1]),
            ]);
        }

        $height = $this->measureCustomerBalanceThermalHeight($transaction);

        $pdf = Pdf::loadView('accounting.customer-balance-thermal', ['transaction' => $transaction, 'pdfHeight' => $height])
            ->setPaper([0, 0, 216, $height], 'portrait');

        ActivityLogger::log('Customer Balance', 'export', $transaction, null, null, 'Thermal receipt printed for customer balance entry #' . $transaction->id);

        return $pdf->stream('customer-balance-receipt-' . $transaction->id . '.pdf');
    }

    private function measureCustomerBalanceThermalHeight(CustomerBalanceTransaction $transaction): int
    {
        $low = 150;
        $high = 350;

        $pageCount = function (int $height) use ($transaction) {
            $pdf = Pdf::loadView('accounting.customer-balance-thermal', ['transaction' => $transaction, 'pdfHeight' => $height])
                ->setPaper([0, 0, 216, $height], 'portrait');
            $pdf->render();

            return $pdf->getDomPDF()->getCanvas()->get_page_count();
        };

        while ($pageCount($high) > 1) {
            $high += 100;
        }

        while ($high - $low > 1) {
            $mid = intdiv($low + $high, 2);
            if ($pageCount($mid) > 1) {
                $low = $mid;
            } else {
                $high = $mid;
            }
        }

        return $high + 4;
    }

    private function purchaseBillTotals(\App\Models\PurchaseBill $transfer): array
    {
        $totalAmount = 0.0;
        $totalMrp = 0.0;

        foreach ($transfer->items as $item) {
            $multiplier = $this->stockMultiplierForPurchaseBill($item->product, $item->pair_type, $item->custom_size_value);
            $quantity = (int) $item->quantity;

            $totalAmount += $this->purchasePriceForPurchaseBillItem($item) * $quantity;
            $totalMrp += $this->mrpForPurchaseBillItem($item, $multiplier) * $quantity;
        }

        return [$totalAmount, $totalMrp];
    }

    private function stockMultiplierForPurchaseBill(?\App\Models\Product $product, ?string $pairType, $customSizeValue = null): float
    {
        if ($customSizeValue !== null && $customSizeValue !== '' && (float)$customSizeValue > 0) {
            return (float) $customSizeValue;
        }

        if (!$product || !$product->pair_product) {
            return 1.0;
        }

        $customSizes = $product->custom_sizes;
        if (is_array($customSizes) && count($customSizes) > 0) {
            $sizes = collect($customSizes)->pluck('size')->map(fn($s) => (float) $s)->filter(fn($s) => $s > 0);
            if ($sizes->count() > 0) {
                return (float) $sizes->max();
            }
        }

        return 2.0;
    }

    private function purchasePriceForPurchaseBillItem(\App\Models\PurchaseBillItem $item): float
    {
        $product = $item->product;
        $basePrice = (float) ($item->variant->purchase_price ?? $product?->purchase_price ?? 0);

        if (!$product || !$product->pair_product) {
            return $basePrice;
        }

        $selectedSize = (float) $item->custom_size_value;
        if ($selectedSize <= 0) {
            return $basePrice;
        }

        $sizes = ($item->variant && !empty($item->variant->custom_sizes))
            ? $item->variant->custom_sizes
            : ($product->custom_sizes ?? []);

        $maxSize = collect($sizes)
            ->pluck('size')
            ->map(fn ($size) => (float) $size)
            ->filter(fn ($size) => $size > 0)
            ->max();

        if (!$maxSize || $maxSize <= 0) {
            return $basePrice;
        }

        return (float) ($basePrice * ($selectedSize / (float) $maxSize));
    }

    private function mrpForPurchaseBillItem(\App\Models\PurchaseBillItem $item, float $multiplier): float
    {
        $product = $item->product;
        if (!$product) {
            return 0.0;
        }

        $sizes = ($item->variant && !empty($item->variant->custom_sizes))
            ? $item->variant->custom_sizes
            : ($product->custom_sizes ?? []);

        if (!empty($sizes)) {
            $value = (float) $item->custom_size_value;
            $matched = null;
            foreach ($sizes as $s) {
                if (abs((float) ($s['size'] ?? 0) - $value) < 0.01) {
                    $matched = $s;
                    break;
                }
            }

            if ($matched && !empty($matched['mrp']) && (float) $matched['mrp'] > 0) {
                return (float) $matched['mrp'];
            }
        }

        $variantMrp = (float) ($item->variant->mrp ?? 0);

        return $variantMrp > 0 ? $variantMrp : (float) ($product->mrp ?? 0);
    }
}

