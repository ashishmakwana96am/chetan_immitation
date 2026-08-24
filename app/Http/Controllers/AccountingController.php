<?php

namespace App\Http\Controllers;

use App\Models\BranchBalanceTransfer;
use App\Models\BulkPurchasePayment;
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
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Models\SupplierAdvancePayment;
use App\Models\SupplierBalance;
use App\Models\User;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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

        $query = LocationBalanceTransaction::select('id', 'location_id', 'balance_type', 'type', 'amount', 'balance_after', 'notes', 'created_by', 'created_at')
            ->with(['location:id,name', 'createdBy:id,name'])
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

        $transactions = $this->filterActiveTransactions($transactions);

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
                'date_sort'     => $tx->created_at->format('YmdHis'),
                'source_type'   => $detectedSource,
                'location'      => $tx->location->name ?? '-',
                'particulars'   => !empty($tx->notes) ? $tx->notes : 'Manual Balance Adjustment',
                'type'          => $isCredit ? 'credit' : 'debit',
                'type_badge'    => $isCredit ? '<span class="badge bg-label-success">Credit</span>' : '<span class="badge bg-label-danger">Debit</span>',
                'amount'            => format_price($tx->amount),
                'amount_raw'        => (float) $tx->amount,
                'is_credit'         => $isCredit,
                'credit'            => $isCredit ? format_price($tx->amount) : '-',
                'debit'             => !$isCredit ? format_price($tx->amount) : '-',
                'balance_after'     => format_price($tx->balance_after),
                'balance_after_raw' => (float) $tx->balance_after,
                'done_by'           => $tx->createdBy->name ?? '-',
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

        $query = LocationBalanceTransaction::select('id', 'location_id', 'balance_type', 'type', 'amount', 'balance_after', 'notes', 'created_by', 'created_at')
            ->with(['location:id,name', 'createdBy:id,name'])
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

        $transactions = $this->filterActiveTransactions($transactions);

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
                'date_sort'     => $tx->created_at->format('YmdHis'),
                'source_type'   => $detectedSource,
                'location'      => $tx->location->name ?? '-',
                'particulars'   => !empty($tx->notes) ? $tx->notes : 'Manual Balance Adjustment',
                'type'          => $isCredit ? 'credit' : 'debit',
                'type_badge'    => $isCredit ? '<span class="badge bg-label-success">Credit</span>' : '<span class="badge bg-label-danger">Debit</span>',
                'amount'            => format_price($tx->amount),
                'amount_raw'        => (float) $tx->amount,
                'is_credit'         => $isCredit,
                'credit'            => $isCredit ? format_price($tx->amount) : '-',
                'debit'             => !$isCredit ? format_price($tx->amount) : '-',
                'balance_after'     => format_price($tx->balance_after),
                'balance_after_raw' => (float) $tx->balance_after,
                'done_by'           => $tx->createdBy->name ?? '-',
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

        $txQuery = LocationBalanceTransaction::select('id', 'location_id', 'balance_type', 'type', 'amount', 'balance_after', 'notes', 'created_by', 'created_at')
            ->with(['location:id,name', 'createdBy:id,name']);

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

        $transactions = $this->filterActiveTransactions($transactions);

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

        $txQuery = LocationBalanceTransaction::select('id', 'location_id', 'balance_type', 'type', 'amount', 'balance_after', 'notes', 'created_by', 'created_at')
            ->with(['location:id,name', 'createdBy:id,name']);

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

        $transactions = $this->filterActiveTransactions($transactions);

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

        // Group by Supplier (Single line per supplier)
        $grouped = $purchases->groupBy(fn ($purchase) => $purchase->supplier_id ?? 0);

        $rows = collect();
        $totalPurchase = 0.0;
        $totalPayment = 0.0;
        $totalOutstanding = 0.0;

        foreach ($grouped as $supplierId => $items) {
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

            $rows->push([
                'supplier_id'   => $first->supplier_id,
                'supplier_name' => $supplierName,
                'total_amount'  => $sumTotal,
                'paid_amount'   => $sumPaid,
                'due_amount'    => $due,
            ]);
        }

        // Sort by supplier name asc
        $sortedRows = $rows->sortBy('supplier_name')->values();

        $mappedRows = $sortedRows->map(function ($row, $index) {
            return [
                'index'            => $index + 1,
                'supplier_id'      => $row['supplier_id'],
                'supplier'         => e($row['supplier_name']),
                'total_amount'     => format_price($row['total_amount']),
                'raw_total_amount' => (float) $row['total_amount'],
                'paid_amount'      => format_price($row['paid_amount']),
                'raw_paid_amount'  => (float) $row['paid_amount'],
                'due_amount'       => format_price($row['due_amount']),
                'raw_due_amount'   => (float) $row['due_amount'],
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

            $locGrouped = $purchasesByLocation->get($loc->id, collect())->groupBy(fn ($purchase) => $purchase->supplier_id ?? 0);
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

        $allPurchases = $purchasesQuery->orderBy('invoice_no', 'desc')->get();

        $mappedPurchases = $allPurchases->map(function($p) {
            if ($p->payment_status == \App\Models\Purchase::PAYMENT_STATUS_PAID) {
                $p->calculated_paid = (float) $p->total_amount;
            } elseif ($p->payment_status == \App\Models\Purchase::PAYMENT_STATUS_PENDING) {
                $p->calculated_paid = 0.0;
            } else {
                $p->calculated_paid = (float) $p->paid_amount;
            }
            $p->calculated_due = max(0.0, (float) $p->total_amount - $p->calculated_paid);
            return $p;
        });

        $totalPurchase = $mappedPurchases->sum('total_amount');
        $totalPayment = $mappedPurchases->sum('calculated_paid');
        $totalOutstanding = max(0.0, $totalPurchase - $totalPayment);

        $purchases = $mappedPurchases->filter(function($p) {
            return $p->calculated_due > 0;
        })->values();

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
        $user = auth()->user();
        if (!$user->hasRole('super-admin')) {
            $isDefaultBranch = !$user->location_id
                || (int) $user->location_id === 1
                || (bool) optional($user->location)->is_default;

            if (!$isDefaultBranch || !$user->can('create purchase payment')) {
                return response()->json(['status' => 'error', 'message' => 'Make Payment is only available for Default Branch users with purchase payment permissions.'], 403);
            }
        }

        $request->validate([
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'location_id'    => ['nullable', 'integer'],
            'supplier_id'    => ['nullable', 'integer', 'exists:suppliers,id'],
            'payment_method' => ['required', 'string', 'in:cash,online'],
        ]);

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $paymentMethod = $request->input('payment_method', 'cash');

        $purchasesQuery = \App\Models\Purchase::whereIn('payment_status', [
                \App\Models\Purchase::PAYMENT_STATUS_PENDING,
                \App\Models\Purchase::PAYMENT_STATUS_PARTIAL,
            ]);

        if ($request->filled('supplier_id')) {
            $purchasesQuery->where('supplier_id', (int) $request->supplier_id);
        }

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

        if ($purchases->isEmpty() && !$request->filled('supplier_id')) {
            return response()->json([
                'status'  => 'error',
                'message' => 'There are no pending payable balances to process. Select a specific supplier to make an advance payment.',
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

        if (!$request->filled('supplier_id')) {
            if ($totalOutstandingDue <= 0) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'There are no pending payable balances to process.',
                ], 422);
            }
            if ($enteredAmount > $totalOutstandingDue) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Payment amount cannot exceed total outstanding balance due (' . format_price($totalOutstandingDue) . '). Select a specific supplier to make an advance payment.',
                ], 422);
            }
        }

        $remainingPayment = $enteredAmount;
        $totalPaidAllocated = 0.0;
        $billsPaidCount = 0;
        $advanceAmount = 0.0;

        \Illuminate\Support\Facades\DB::transaction(function () use (
            $purchases,
            $enteredAmount,
            $paymentMethod,
            $request,
            $user,
            &$remainingPayment,
            &$totalPaidAllocated,
            &$billsPaidCount,
            &$advanceAmount
        ) {
            $supplierId = $request->filled('supplier_id') ? (int) $request->supplier_id : null;

            $bulkPayRecord = BulkPurchasePayment::create([
                'total_amount'   => $enteredAmount,
                'supplier_id'    => $supplierId,
                'payment_method' => $paymentMethod,
                'created_by'     => auth()->id(),
            ]);

            \App\Services\ActivityLogger::log(
                'Bulk Purchase Payment',
                'create',
                $bulkPayRecord,
                null,
                $bulkPayRecord->toArray(),
                'Created bulk purchase payment of ' . format_price($enteredAmount) . ' via ' . ucfirst($paymentMethod)
            );

            // FIFO: Settle pending purchase bills
            foreach ($purchases as $purchase) {
                if ($remainingPayment <= 0) {
                    break;
                }

                $currentPaid = $purchase->payment_status == Purchase::PAYMENT_STATUS_PAID
                    ? (float) $purchase->total_amount
                    : ($purchase->payment_status == Purchase::PAYMENT_STATUS_PENDING ? 0.0 : (float) $purchase->paid_amount);

                $due = round((float) $purchase->total_amount - $currentPaid, 2);
                if ($due <= 0) {
                    continue;
                }

                $payForThisBill = min($due, $remainingPayment);
                $newPaidAmount = round($currentPaid + $payForThisBill, 2);

                $finalStatus = ($newPaidAmount >= (float) $purchase->total_amount)
                    ? Purchase::PAYMENT_STATUS_PAID
                    : Purchase::PAYMENT_STATUS_PARTIAL;

                $oldStatus = (int) $purchase->payment_status;
                $oldPaid = (float) $purchase->paid_amount;

                PurchasePayment::create([
                    'purchase_id'              => $purchase->id,
                    'bulk_purchase_payment_id' => $bulkPayRecord->id,
                    'amount'                   => $payForThisBill,
                    'created_by'               => auth()->id(),
                ]);

                Purchase::withoutEvents(fn () => Purchase::withoutActivityLogging(fn () => $purchase->update([
                    'payment_status' => $finalStatus,
                    'paid_amount'    => min($newPaidAmount, (float) $purchase->total_amount),
                    'payment_method' => $paymentMethod,
                ])));

                ActivityLogger::log(
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

            // Handle any remaining payment amount as Supplier Advance Payment
            if ($remainingPayment > 0 && $supplierId) {
                $advanceAmount = $remainingPayment;

                SupplierAdvancePayment::create([
                    'supplier_id'              => $supplierId,
                    'bulk_purchase_payment_id' => $bulkPayRecord->id,
                    'total_amount'             => $advanceAmount,
                    'used_amount'              => 0.00,
                    'remaining_amount'         => $advanceAmount,
                    'payment_method'           => $paymentMethod,
                    'notes'                    => 'Supplier Advance Payment',
                    'created_by'               => auth()->id(),
                ]);

                $suppBal = SupplierBalance::firstOrCreate(['supplier_id' => $supplierId]);
                $suppBal->balance = round((float) $suppBal->balance + $advanceAmount, 2);
                if ($paymentMethod === 'cash') {
                    $suppBal->cash_balance = round((float) $suppBal->cash_balance + $advanceAmount, 2);
                } else {
                    $suppBal->bank_balance = round((float) $suppBal->bank_balance + $advanceAmount, 2);
                }
                $suppBal->save();
            }

            // Record Location Cash/Bank debit transactions
            $defaultLoc = Location::where('is_default', true)->first() ?? Location::first();
            $locId = $user->location_id ?: ($defaultLoc ? $defaultLoc->id : 1);
            $balanceType = $paymentMethod === 'cash' ? LocationBalanceTransaction::BALANCE_TYPE_CASH : LocationBalanceTransaction::BALANCE_TYPE_BANK;
            $balCol = $paymentMethod === 'cash' ? 'cash_balance' : 'bank_balance';

            $supplierObj = $supplierId ? Supplier::find($supplierId) : null;
            $suppName = $supplierObj ? $supplierObj->name : 'Supplier';

            // 1. Transaction for Purchase Bill Payment (if any allocated to bills)
            if ($totalPaidAllocated > 0) {
                $locBal = LocationBalance::firstOrCreate(['location_id' => $locId]);
                $newBal = round((float) $locBal->{$balCol} - $totalPaidAllocated, 2);
                $locBal->update([$balCol => $newBal]);

                LocationBalanceTransaction::create([
                    'location_id'   => $locId,
                    'balance_type'  => $balanceType,
                    'type'          => LocationBalanceTransaction::TYPE_DEBIT,
                    'amount'        => $totalPaidAllocated,
                    'balance_after' => $newBal,
                    'notes'         => 'Purchase Payment (' . format_price($totalPaidAllocated) . ') to ' . $suppName,
                    'created_by'    => auth()->id(),
                ]);
            }

            // 2. Transaction for Advance Payment (if any remaining as advance)
            if ($remainingPayment > 0) {
                $locBal = LocationBalance::firstOrCreate(['location_id' => $locId]);
                $newBal = round((float) $locBal->{$balCol} - $remainingPayment, 2);
                $locBal->update([$balCol => $newBal]);

                LocationBalanceTransaction::create([
                    'location_id'   => $locId,
                    'balance_type'  => $balanceType,
                    'type'          => LocationBalanceTransaction::TYPE_DEBIT,
                    'amount'        => $remainingPayment,
                    'balance_after' => $newBal,
                    'notes'         => 'Advance Payment (' . format_price($remainingPayment) . ') to ' . $suppName,
                    'created_by'    => auth()->id(),
                ]);
            }
        });

        $msg = 'Successfully allocated ' . format_price($totalPaidAllocated) . ' across ' . $billsPaidCount . ' bill(s).';
        if ($advanceAmount > 0) {
            $msg = 'Successfully paid ' . format_price($enteredAmount) . ' (' . format_price($totalPaidAllocated) . ' allocated to ' . $billsPaidCount . ' bill(s), ' . format_price($advanceAmount) . ' added as Supplier Advance Credit).';
        }

        return response()->json([
            'status'  => 'success',
            'message' => $msg,
        ]);
    }

    public function payablePaymentHistory(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole('super-admin')) {
            $isDefaultBranch = !$user->location_id
                || (int) $user->location_id === 1
                || (bool) optional($user->location)->is_default;

            if (!$isDefaultBranch || !$user->can('view purchase payments')) {
                abort(403, 'Payment History is only available for Default Branch users with purchase payment permissions.');
            }
        }

        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $canEdit = $user->hasRole('super-admin') || $user->can('edit purchase payment');
        $canDelete = $user->hasRole('super-admin') || $user->can('delete purchase payment');
        $hasActionPermission = $canEdit || $canDelete;

        $locations = Location::where('status', 1)->orderBy('name')->get();

        return view('accounting.payable-payment-history', compact('locations', 'isRestricted', 'hasActionPermission'));
    }

    public function payablePaymentHistoryData(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole('super-admin')) {
            $isDefaultBranch = !$user->location_id
                || (int) $user->location_id === 1
                || (bool) optional($user->location)->is_default;

            if (!$isDefaultBranch || !$user->can('view purchase payments')) {
                return response()->json(['status' => 'error', 'message' => 'Payment History is only available for Default Branch users with purchase payment permissions.'], 403);
            }
        }

        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $paymentsQuery = BulkPurchasePayment::with(['supplier', 'createdBy']);

        if ($request->filled('start_date')) {
            $paymentsQuery->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $paymentsQuery->whereDate('created_at', '<=', $request->end_date);
        }

        $payments = $paymentsQuery->orderBy('created_at', 'desc')->get();

        $canEdit = $user->hasRole('super-admin') || $user->can('edit purchase payment');
        $canDelete = $user->hasRole('super-admin') || $user->can('delete purchase payment');

        $rows = $payments->map(function ($payment, $index) use ($canEdit, $canDelete) {
            $items = '';
            if ($canEdit) {
                $items .= '<a href="javascript:void(0)" class="dropdown-item edit-payable-payment-btn" data-id="' . $payment->id . '" data-amount="' . $payment->total_amount . '" data-method="' . e($payment->payment_method ?? 'cash') . '" data-supplier="' . ($payment->supplier_id ?? '') . '"><i class="ti ti-pencil me-2"></i>Edit</a>';
            }
            if ($canDelete) {
                $items .= '<button type="button" class="dropdown-item text-danger delete-payable-payment-btn" data-id="' . $payment->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
            }

            if (empty($items)) {
                $actions = '-';
            } else {
                $actions = '<div class="dropdown table-action-dropdown">
                    <button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                        <span>Actions</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">' . $items . '</div></div>';
            }

            return [
                'index'          => $index + 1,
                'id'             => $payment->id,
                'date'           => format_date($payment->created_at, 'h:i A'),
                'date_group'     => format_date($payment->created_at, 'd-m-Y'),
                'date_sort'      => $payment->created_at ? $payment->created_at->format('Y-m-d H:i:s') : '',
                'supplier'       => e($payment->supplier->name ?? 'All Suppliers'),
                'amount'         => format_price($payment->total_amount),
                'amount_raw'     => (float) $payment->total_amount,
                'payment_method' => ucfirst($payment->payment_method ?? 'cash'),
                'created_by'     => e($payment->createdBy->name ?? 'System'),
                'actions'        => $actions,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $rows,
        ]);
    }

    public function payablePaymentUpdate(Request $request, BulkPurchasePayment $payment)
    {
        $user = auth()->user();
        $canEdit = $user->hasRole('super-admin') || $user->can('edit purchase payment');

        if (!$canEdit) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized to edit this payment record.',
            ], 403);
        }

        $request->validate([
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'in:cash,online'],
        ]);

        $newAmount = round((float) $request->amount, 2);
        $newPaymentMethod = $request->payment_method;

        try {
            DB::transaction(function () use ($payment, $newAmount, $newPaymentMethod) {
                // 1. Revert previous purchase payments for this bulk payment
                $linkedPayments = PurchasePayment::where('bulk_purchase_payment_id', $payment->id)->get();
                foreach ($linkedPayments as $pPayment) {
                    $purchase = Purchase::find($pPayment->purchase_id);
                    if ($purchase) {
                        $newPaid = max(0, round((float) $purchase->paid_amount - (float) $pPayment->amount, 2));
                        $newStatus = Purchase::PAYMENT_STATUS_PENDING;
                        if ($newPaid >= (float) $purchase->total_amount && (float) $purchase->total_amount > 0) {
                            $newStatus = Purchase::PAYMENT_STATUS_PAID;
                        } elseif ($newPaid > 0) {
                            $newStatus = Purchase::PAYMENT_STATUS_PARTIAL;
                        }
                        Purchase::withoutActivityLogging(fn () => $purchase->update([
                            'paid_amount'    => $newPaid,
                            'payment_status' => $newStatus,
                        ]));
                    }
                    $pPayment->delete();
                }

                // 2. Re-allocate new amount FIFO
                $supplierId = $payment->supplier_id;
                $purchasesQuery = Purchase::whereIn('payment_status', [
                    Purchase::PAYMENT_STATUS_PENDING,
                    Purchase::PAYMENT_STATUS_PARTIAL,
                ]);

                if ($supplierId) {
                    $purchasesQuery->where('supplier_id', $supplierId);
                }

                $purchases = $purchasesQuery->orderBy('created_at', 'asc')->get();

                $remainingPayment = $newAmount;
                foreach ($purchases as $purchase) {
                    if ($remainingPayment <= 0) break;

                    $currentPaid = $purchase->payment_status == Purchase::PAYMENT_STATUS_PAID
                        ? (float) $purchase->total_amount
                        : ($purchase->payment_status == Purchase::PAYMENT_STATUS_PENDING ? 0.0 : (float) $purchase->paid_amount);

                    $due = round((float) $purchase->total_amount - $currentPaid, 2);
                    if ($due <= 0) continue;

                    $payForThisBill = min($due, $remainingPayment);
                    $newPaidAmount = round($currentPaid + $payForThisBill, 2);

                    $finalStatus = ($newPaidAmount >= (float) $purchase->total_amount)
                        ? Purchase::PAYMENT_STATUS_PAID
                        : Purchase::PAYMENT_STATUS_PARTIAL;

                    PurchasePayment::create([
                        'purchase_id'              => $purchase->id,
                        'bulk_purchase_payment_id' => $payment->id,
                        'amount'                   => $payForThisBill,
                        'created_by'               => auth()->id(),
                    ]);

                    Purchase::withoutEvents(fn () => Purchase::withoutActivityLogging(fn () => $purchase->update([
                        'payment_status' => $finalStatus,
                        'paid_amount'    => min($newPaidAmount, (float) $purchase->total_amount),
                        'payment_method' => $newPaymentMethod,
                    ])));

                    $remainingPayment = round($remainingPayment - $payForThisBill, 2);
                }

                $oldData = $payment->toArray();
                $payment->update([
                    'total_amount'   => $newAmount,
                    'payment_method' => $newPaymentMethod,
                ]);

                ActivityLogger::log(
                    'Bulk Purchase Payment',
                    'update',
                    $payment,
                    $oldData,
                    $payment->toArray(),
                    'Updated bulk purchase payment #' . $payment->id
                );
            });
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update payment record: ' . $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Payable payment updated successfully.',
        ]);
    }

    public function payablePaymentDestroy(BulkPurchasePayment $payment)
    {
        $user = auth()->user();
        $canDelete = $user->hasRole('super-admin') || $user->can('delete purchase payment');

        if (!$canDelete) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized to delete this payment record.',
            ], 403);
        }

        try {
            DB::transaction(function () use ($payment) {
                // 1. Revert linked purchase payments & update purchase bills
                $linkedPayments = PurchasePayment::where('bulk_purchase_payment_id', $payment->id)->get();
                foreach ($linkedPayments as $pPayment) {
                    $purchase = Purchase::find($pPayment->purchase_id);
                    if ($purchase) {
                        $newPaid = max(0, round((float) $purchase->paid_amount - (float) $pPayment->amount, 2));
                        $newStatus = Purchase::PAYMENT_STATUS_PENDING;
                        if ($newPaid >= (float) $purchase->total_amount && (float) $purchase->total_amount > 0) {
                            $newStatus = Purchase::PAYMENT_STATUS_PAID;
                        } elseif ($newPaid > 0) {
                            $newStatus = Purchase::PAYMENT_STATUS_PARTIAL;
                        }
                        Purchase::withoutEvents(fn () => Purchase::withoutActivityLogging(fn () => $purchase->update([
                            'paid_amount'    => $newPaid,
                            'payment_status' => $newStatus,
                        ])));
                    }
                    $pPayment->delete();
                }

                // 2. Revert linked supplier advance payments & supplier balances
                $advances = SupplierAdvancePayment::where('bulk_purchase_payment_id', $payment->id)->get();
                foreach ($advances as $adv) {
                    $suppBal = SupplierBalance::where('supplier_id', $adv->supplier_id)->first();
                    if ($suppBal) {
                        $rem = (float) $adv->remaining_amount;
                        $suppBal->balance = max(0.0, round((float) $suppBal->balance - $rem, 2));
                        if ($adv->payment_method === 'cash') {
                            $suppBal->cash_balance = max(0.0, round((float) $suppBal->cash_balance - $rem, 2));
                        } else {
                            $suppBal->bank_balance = max(0.0, round((float) $suppBal->bank_balance - $rem, 2));
                        }
                        $suppBal->save();
                    }
                    $adv->delete();
                }

                // 3. Delete matching LocationBalanceTransaction entry
                $supplierObj = $payment->supplier_id ? Supplier::find($payment->supplier_id) : null;
                $suppName = $supplierObj ? $supplierObj->name : '';

                $matchingTxs = LocationBalanceTransaction::where('type', LocationBalanceTransaction::TYPE_DEBIT)
                    ->where(function ($q) use ($suppName) {
                        $q->where('notes', 'like', '%Purchase Payment%')
                          ->orWhere('notes', 'like', '%Advance Payment%');
                        if ($suppName) {
                            $q->orWhere('notes', 'like', '%' . $suppName . '%');
                        }
                    })
                    ->whereBetween('created_at', [
                        $payment->created_at->copy()->subMinutes(15),
                        $payment->created_at->copy()->addMinutes(15)
                    ])
                    ->get();

                foreach ($matchingTxs as $mTx) {
                    $mTx->delete();
                }

                // Recalculate all location balances & running transaction balances
                Artisan::call('recalculate:location-balances');

                $oldData = $payment->toArray();
                $payment->delete();

                ActivityLogger::log(
                    'Bulk Purchase Payment',
                    'delete',
                    $payment,
                    $oldData,
                    null,
                    'Deleted bulk purchase payment #' . $payment->id
                );
            });
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete payment record: ' . $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Payable payment entry deleted and all related balances reverted successfully.',
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

        $query = LocationBalanceTransaction::select('id', 'location_id', 'balance_type', 'type', 'amount', 'balance_after', 'notes', 'created_by', 'created_at')
            ->with(['location:id,name', 'createdBy:id,name']);

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
                'type'              => $typeBadge,
                'amount'            => $amountSpan,
                'amount_raw'        => (float) $tx->amount,
                'balance_after'     => format_price($tx->balance_after),
                'balance_after_raw' => (float) $tx->balance_after,
                'notes'             => (!empty($tx->notes) && $tx->notes !== 'Manual Account Balance Adjustment') ? e($tx->notes) : 'Opening Balance Added',
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
        $user = auth()->user();
        if (!$user->hasRole('super-admin') && !$user->can('create balance transfer')) {
            abort(403, 'Unauthorized to create balance transfer.');
        }

        $locations = Location::with('balance')->where('status', 1)->orderBy('name')->get();

        return view('accounting.branch-balances-transfer', compact('locations'));
    }

    public function branchBalancesTransferStore(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole('super-admin') && !$user->can('create balance transfer')) {
            return response()->json([
                'status'  => 'error',
                'message' => ['general' => ['Unauthorized to create balance transfer.']],
            ], 403);
        }

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
        $amount         = (float) $request->amount;

        try {
            // Check pending dues between fromLocation and toLocation
            $dueBills = \App\Models\PurchaseBill::with('items.product', 'items.variant')
                ->where('from_location_id', $toLocationId)
                ->where('to_location_id', $fromLocationId)
                ->where('status', \App\Models\PurchaseBill::STATUS_ACCEPTED)
                ->whereIn('payment_status', [
                    \App\Models\PurchaseBill::PAYMENT_STATUS_PENDING,
                    \App\Models\PurchaseBill::PAYMENT_STATUS_PARTIAL
                ])
                ->get();

            $totalPendingDue = 0.0;
            foreach ($dueBills as $pBill) {
                [$billTotal] = $this->purchaseBillTotals($pBill);
                $currentPaid = $pBill->payment_status == \App\Models\PurchaseBill::PAYMENT_STATUS_PAID
                    ? (float) $billTotal
                    : ($pBill->payment_status == \App\Models\PurchaseBill::PAYMENT_STATUS_PENDING ? 0.0 : (float) $pBill->paid_amount);
                $dueAmt = round((float) $billTotal - $currentPaid, 2);
                if ($dueAmt > 0) {
                    $totalPendingDue += $dueAmt;
                }
            }

            if ($totalPendingDue <= 0) {
                return response()->json([
                    'status'  => 'error',
                    'message' => ['from_location_id' => ['Transfer is not allowed because there are no pending purchase bill dues between the selected locations.']],
                ], 422);
            }

            if ($amount > $totalPendingDue) {
                return response()->json([
                    'status'  => 'error',
                    'message' => ['amount' => ['Transfer amount cannot exceed the pending purchase bill dues (' . format_price($totalPendingDue) . ').']],
                ], 422);
            }

            $nextNum = 1;
            do {
                $transferNo = 'BT-' . str_pad($nextNum, 2, '0', STR_PAD_LEFT);
                $activeExists = BranchBalanceTransfer::where('transfer_no', $transferNo)->exists();
                if ($activeExists) {
                    $nextNum++;
                }
            } while ($activeExists);

            $softDeletedConflict = BranchBalanceTransfer::onlyTrashed()->where('transfer_no', $transferNo)->first();
            if ($softDeletedConflict) {
                $softDeletedConflict->update(['transfer_no' => $transferNo . '_del_' . $softDeletedConflict->id]);
            }

            $transfer = BranchBalanceTransfer::create([
                'transfer_no'      => $transferNo,
                'from_location_id' => $fromLocationId,
                'to_location_id'   => $toLocationId,
                'balance_type'     => $request->balance_type,
                'amount'           => $amount,
                'status'           => BranchBalanceTransfer::STATUS_PENDING,
                'notes'            => $request->notes,
                'created_by'       => auth()->id(),
            ]);

            ActivityLogger::log(
                'Balance Transfer',
                'create',
                $transfer,
                null,
                $transfer->toArray(),
                'Created pending balance transfer request ' . $transferNo . ' of ' . format_price($amount)
            );
            $this->clearBalanceTransferCache($fromLocationId, $toLocationId);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => ['amount' => ['Failed to create balance transfer request: ' . $e->getMessage()]],
            ], 422);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Balance transfer request created successfully and is pending approval.',
        ]);
    }

    public function branchBalancesTransferAccept(BranchBalanceTransfer $transfer)
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('super-admin');
        $hasAcceptPermission = $user->can('accept balance transfer');
        $isReceivingBranchAdmin = $user->location_id && (int) $user->location_id === (int) $transfer->to_location_id;

        $canAccept = $isSuperAdmin || ($user->location_id ? $isReceivingBranchAdmin : $hasAcceptPermission);

        if (!$canAccept) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized to accept this balance transfer.',
            ], 403);
        }

        if ($transfer->status !== BranchBalanceTransfer::STATUS_PENDING) {
            return response()->json([
                'status'  => 'error',
                'message' => 'This balance transfer is not in pending status.',
            ], 422);
        }

        $fromLocationId = (int) $transfer->from_location_id;
        $toLocationId   = (int) $transfer->to_location_id;
        $balanceColumn  = $transfer->balance_type === LocationBalanceTransaction::BALANCE_TYPE_BANK ? 'bank_balance' : 'cash_balance';
        $amount         = (float) $transfer->amount;

        try {
            DB::transaction(function () use ($transfer, $fromLocationId, $toLocationId, $balanceColumn, $amount) {
                $fromBalanceRecord = LocationBalance::firstOrCreate(['location_id' => $fromLocationId]);
                $toBalanceRecord   = LocationBalance::firstOrCreate(['location_id' => $toLocationId]);

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
                $userNotes   = !empty($transfer->notes) ? ' (Note: ' . $transfer->notes . ')' : '';

                LocationBalanceTransaction::create([
                    'location_id'   => $fromLocationId,
                    'balance_type'  => $transfer->balance_type,
                    'type'          => LocationBalanceTransaction::TYPE_DEBIT,
                    'amount'        => $amount,
                    'balance_after' => $fromNew,
                    'notes'         => 'Transfer (' . $transfer->transfer_no . ') to ' . $toLocName . $userNotes,
                    'created_by'    => auth()->id(),
                ]);

                LocationBalanceTransaction::create([
                    'location_id'   => $toLocationId,
                    'balance_type'  => $transfer->balance_type,
                    'type'          => LocationBalanceTransaction::TYPE_CREDIT,
                    'amount'        => $amount,
                    'balance_after' => $toNew,
                    'notes'         => 'Transfer (' . $transfer->transfer_no . ') from ' . $fromLocName . $userNotes,
                    'created_by'    => auth()->id(),
                ]);

                // Clear Purchase Bills FIFO
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
                        'purchase_bill_id'           => $pBill->id,
                        'branch_balance_transfer_id' => $transfer->id,
                        'amount'                     => $payAmt,
                        'created_by'                 => auth()->id(),
                    ]);

                    $pBill->update([
                        'payment_status' => $finalStatus,
                        'paid_amount'    => min($newPaid, (float) $billTotal),
                    ]);

                    $remAmt = round($remAmt - $payAmt, 2);
                }

                $transfer->update([
                    'status'      => BranchBalanceTransfer::STATUS_ACCEPTED,
                    'actioned_by' => auth()->id(),
                    'actioned_at' => now(),
                ]);

                ActivityLogger::log(
                    'Balance Transfer',
                    'accept',
                    $transfer,
                    ['from' => $fromCurrent, 'to' => $toCurrent],
                    ['from' => $fromNew, 'to' => $toNew],
                    'Accepted balance transfer ' . $transfer->transfer_no . ' of ' . format_price($amount)
                );
                $this->clearBalanceTransferCache($fromLocationId, $toLocationId);
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'insufficient_balance') {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Insufficient balance in From Location.',
                ], 422);
            }
            throw $e;
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Balance transfer accepted and transactions recorded successfully.',
        ]);
    }

    public function branchBalancesTransferReject(BranchBalanceTransfer $transfer)
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('super-admin');
        $hasRejectPermission = $user->can('reject balance transfer') || $user->can('accept balance transfer');
        $isReceivingBranchAdmin = $user->location_id && (int) $user->location_id === (int) $transfer->to_location_id;

        $canReject = $isSuperAdmin || ($user->location_id ? $isReceivingBranchAdmin : $hasRejectPermission);

        if (!$canReject) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized to reject this balance transfer.',
            ], 403);
        }

        if ($transfer->status !== BranchBalanceTransfer::STATUS_PENDING) {
            return response()->json([
                'status'  => 'error',
                'message' => 'This balance transfer is not in pending status.',
            ], 422);
        }

        $transfer->update([
            'status'      => BranchBalanceTransfer::STATUS_REJECTED,
            'actioned_by' => auth()->id(),
            'actioned_at' => now(),
        ]);

        ActivityLogger::log(
            'Balance Transfer',
            'reject',
            $transfer,
            ['status' => 'pending'],
            ['status' => 'rejected'],
            'Rejected balance transfer ' . $transfer->transfer_no
        );

        $this->clearBalanceTransferCache($transfer->from_location_id, $transfer->to_location_id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Balance transfer rejected.',
        ]);
    }

    public function branchBalancesTransferEdit(BranchBalanceTransfer $transfer)
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('super-admin');
        $hasEditPermission = $user->can('edit balance transfer');
        $isSenderBranchAdmin = $user->location_id && (int) $user->location_id === (int) $transfer->from_location_id;

        $canEdit = $isSuperAdmin || ($user->location_id ? $isSenderBranchAdmin : $hasEditPermission);

        if (!$canEdit) {
            abort(403, 'Unauthorized to edit this balance transfer.');
        }

        $locations = Location::with('balance')->where('status', 1)->orderBy('name')->get();

        return view('accounting.branch-balances-transfer-edit', compact('locations', 'transfer'));
    }

    public function branchBalancesTransferUpdate(Request $request, BranchBalanceTransfer $transfer)
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('super-admin');
        $hasEditPermission = $user->can('edit balance transfer');
        $isSenderBranchAdmin = $user->location_id && (int) $user->location_id === (int) $transfer->from_location_id;

        $canEdit = $isSuperAdmin || ($user->location_id ? $isSenderBranchAdmin : $hasEditPermission);

        if (!$canEdit) {
            return response()->json([
                'status'  => 'error',
                'message' => ['general' => ['Unauthorized to edit this balance transfer.']],
            ], 403);
        }

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

        $newFromId      = (int) $request->from_location_id;
        $newToId        = (int) $request->to_location_id;
        $newBalanceType = $request->balance_type;
        $newAmount      = (float) $request->amount;

        $oldFromId      = (int) $transfer->from_location_id;
        $oldToId        = (int) $transfer->to_location_id;
        $oldBalanceType = $transfer->balance_type;
        $oldAmount      = (float) $transfer->amount;

        $dueBills = \App\Models\PurchaseBill::with('items.product', 'items.variant')
            ->where('from_location_id', $newToId)
            ->where('to_location_id', $newFromId)
            ->where('status', \App\Models\PurchaseBill::STATUS_ACCEPTED)
            ->get();

        $totalPendingDue = 0.0;
        foreach ($dueBills as $pBill) {
            [$billTotal] = $this->purchaseBillTotals($pBill);
            
            $currentPaid = 0.0;
            if ($pBill->payment_status == \App\Models\PurchaseBill::PAYMENT_STATUS_PAID) {
                $currentPaid = (float) $billTotal;
            } elseif ($pBill->payment_status == \App\Models\PurchaseBill::PAYMENT_STATUS_PARTIAL) {
                $currentPaid = (float) $pBill->paid_amount;
            }

            if ((int) $transfer->status === BranchBalanceTransfer::STATUS_ACCEPTED) {
                $linkedPaymentAmt = \App\Models\PurchaseBillPayment::where('branch_balance_transfer_id', $transfer->id)
                    ->where('purchase_bill_id', $pBill->id)
                    ->sum('amount');
                $currentPaid = max(0, round($currentPaid - (float) $linkedPaymentAmt, 2));
            }

            $dueAmt = round((float) $billTotal - $currentPaid, 2);
            if ($dueAmt > 0) {
                $totalPendingDue += $dueAmt;
            }
        }

        if ($totalPendingDue <= 0) {
            return response()->json([
                'status'  => 'error',
                'message' => ['from_location_id' => ['Transfer is not allowed because there are no pending purchase bill dues between the selected locations.']],
            ], 422);
        }

        if ($newAmount > $totalPendingDue) {
            return response()->json([
                'status'  => 'error',
                'message' => ['amount' => ['Transfer amount cannot exceed the pending purchase bill dues (' . format_price($totalPendingDue) . ').']],
            ], 422);
        }

        try {
            DB::transaction(function () use (
                $request,
                $transfer,
                $oldFromId,
                $oldToId,
                $oldBalanceType,
                $oldAmount,
                $newFromId,
                $newToId,
                $newBalanceType,
                $newAmount
            ) {
                // If transfer was accepted, silently adjust balance difference & update purchase bill payments
                if ((int) $transfer->status === BranchBalanceTransfer::STATUS_ACCEPTED) {
                    $oldBalanceCol = $oldBalanceType === LocationBalanceTransaction::BALANCE_TYPE_BANK ? 'bank_balance' : 'cash_balance';
                    $newBalanceCol = $newBalanceType === LocationBalanceTransaction::BALANCE_TYPE_BANK ? 'bank_balance' : 'cash_balance';

                    // 1. Revert Old Balance Effects (without adding audit transaction log rows)
                    $oldFromBal = LocationBalance::firstOrCreate(['location_id' => $oldFromId]);
                    $oldToBal   = LocationBalance::firstOrCreate(['location_id' => $oldToId]);

                    $oldFromBal = LocationBalance::where('id', $oldFromBal->id)->lockForUpdate()->first();
                    $oldToBal   = LocationBalance::where('id', $oldToBal->id)->lockForUpdate()->first();

                    $oldToCurrent = (float) $oldToBal->{$oldBalanceCol};
                    if ($oldToCurrent < $oldAmount) {
                        throw new \RuntimeException('insufficient_balance_revert');
                    }

                    $oldFromNew = (float) $oldFromBal->{$oldBalanceCol} + $oldAmount;
                    $oldToNew   = $oldToCurrent - $oldAmount;

                    $oldFromBal->update([$oldBalanceCol => $oldFromNew]);
                    $oldToBal->update([$oldBalanceCol => $oldToNew]);

                    // Revert Old PurchaseBill payments generated by this transfer
                    $linkedPayments = \App\Models\PurchaseBillPayment::where('branch_balance_transfer_id', $transfer->id)->get();
                    foreach ($linkedPayments as $pPayment) {
                        $pBill = \App\Models\PurchaseBill::find($pPayment->purchase_bill_id);
                        if ($pBill) {
                            $newPaid = max(0, round((float) $pBill->paid_amount - (float) $pPayment->amount, 2));
                            [$billTotal] = $this->purchaseBillTotals($pBill);
                            $newStatus = \App\Models\PurchaseBill::PAYMENT_STATUS_PENDING;
                            if ($newPaid >= (float) $billTotal && (float) $billTotal > 0) {
                                $newStatus = \App\Models\PurchaseBill::PAYMENT_STATUS_PAID;
                            } elseif ($newPaid > 0) {
                                $newStatus = \App\Models\PurchaseBill::PAYMENT_STATUS_PARTIAL;
                            }
                            $pBill->update([
                                'paid_amount'    => $newPaid,
                                'payment_status' => $newStatus,
                            ]);
                        }
                        $pPayment->delete();
                    }

                    // 2. Apply New Balance Effects (without creating duplicate audit transaction rows)
                    $newFromBal = LocationBalance::firstOrCreate(['location_id' => $newFromId]);
                    $newToBal   = LocationBalance::firstOrCreate(['location_id' => $newToId]);

                    $newFromBal = LocationBalance::where('id', $newFromBal->id)->lockForUpdate()->first();
                    $newToBal   = LocationBalance::where('id', $newToBal->id)->lockForUpdate()->first();

                    $newFromCurrent = (float) $newFromBal->{$newBalanceCol};
                    if ($newFromCurrent < $newAmount) {
                        throw new \RuntimeException('insufficient_balance_apply');
                    }

                    $newFromNew = $newFromCurrent - $newAmount;
                    $newToNew   = (float) $newToBal->{$newBalanceCol} + $newAmount;

                    $newFromBal->update([$newBalanceCol => $newFromNew]);
                    $newToBal->update([$newBalanceCol => $newToNew]);

                    // Clear Purchase Bills FIFO for New Branches
                    $fromUnpaidBills = \App\Models\PurchaseBill::with('items.product', 'items.variant')
                        ->where('from_location_id', $newToId)
                        ->where('to_location_id', $newFromId)
                        ->where('status', \App\Models\PurchaseBill::STATUS_ACCEPTED)
                        ->whereIn('payment_status', [
                            \App\Models\PurchaseBill::PAYMENT_STATUS_PENDING,
                            \App\Models\PurchaseBill::PAYMENT_STATUS_PARTIAL
                        ])
                        ->orderBy('created_at', 'asc')
                        ->get();

                    $remAmt = $newAmount;
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
                            'purchase_bill_id'           => $pBill->id,
                            'branch_balance_transfer_id' => $transfer->id,
                            'amount'                     => $payAmt,
                            'created_by'                 => auth()->id(),
                        ]);

                        $pBill->update([
                            'payment_status' => $finalStatus,
                            'paid_amount'    => min($newPaid, (float) $billTotal),
                        ]);

                        $remAmt = round($remAmt - $payAmt, 2);
                    }
                }

                $oldData = $transfer->toArray();
                $transfer->update([
                    'from_location_id' => $newFromId,
                    'to_location_id'   => $newToId,
                    'balance_type'     => $newBalanceType,
                    'amount'           => $newAmount,
                    'notes'            => $request->notes,
                ]);

                ActivityLogger::log(
                    'Balance Transfer',
                    'update',
                    $transfer,
                    $oldData,
                    $transfer->toArray(),
                    'Updated balance transfer ' . $transfer->transfer_no
                );
                $this->clearBalanceTransferCache($oldFromId, $oldToId);
                $this->clearBalanceTransferCache($newFromId, $newToId);
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'insufficient_balance_revert') {
                return response()->json([
                    'status'  => 'error',
                    'message' => ['from_location_id' => ['Cannot edit transfer because target branch has insufficient balance to revert previous transfer.']],
                ], 422);
            }
            if ($e->getMessage() === 'insufficient_balance_apply') {
                return response()->json([
                    'status'  => 'error',
                    'message' => ['amount' => ['Insufficient balance in From Location to transfer.']],
                ], 422);
            }
            throw $e;
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Balance transfer updated successfully.',
        ]);
    }

    public function branchBalancesTransferDestroy(BranchBalanceTransfer $transfer)
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('super-admin');
        $hasDeletePermission = $user->can('delete balance transfer');
        $isSenderBranchAdmin = $user->location_id && (int) $user->location_id === (int) $transfer->from_location_id;

        $canDelete = $isSuperAdmin || ($user->location_id ? $isSenderBranchAdmin : $hasDeletePermission);

        if (!$canDelete) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized to delete this balance transfer.',
            ], 403);
        }

        $fromLocationId = (int) $transfer->from_location_id;
        $toLocationId   = (int) $transfer->to_location_id;
        $balanceColumn  = $transfer->balance_type === LocationBalanceTransaction::BALANCE_TYPE_BANK ? 'bank_balance' : 'cash_balance';
        $amount         = (float) $transfer->amount;

        try {
            DB::transaction(function () use ($transfer, $fromLocationId, $toLocationId, $balanceColumn, $amount) {
                // If transfer was accepted, revert balance change
                if ((int) $transfer->status === BranchBalanceTransfer::STATUS_ACCEPTED) {
                    $fromBalanceRecord = LocationBalance::firstOrCreate(['location_id' => $fromLocationId]);
                    $toBalanceRecord   = LocationBalance::firstOrCreate(['location_id' => $toLocationId]);

                    $fromBalanceRecord = LocationBalance::where('id', $fromBalanceRecord->id)->lockForUpdate()->first();
                    $toBalanceRecord   = LocationBalance::where('id', $toBalanceRecord->id)->lockForUpdate()->first();

                    $toCurrent = (float) $toBalanceRecord->{$balanceColumn};
                    if ($toCurrent < $amount) {
                        throw new \RuntimeException('insufficient_balance_revert');
                    }

                    $fromNew = (float) $fromBalanceRecord->{$balanceColumn} + $amount;
                    $toNew   = $toCurrent - $amount;

                    // Silently revert balances without creating extra reversal transaction entries
                    $fromBalanceRecord->update([$balanceColumn => $fromNew]);
                    $toBalanceRecord->update([$balanceColumn => $toNew]);

                    // Revert PurchaseBill payments generated by this transfer
                    $linkedPayments = \App\Models\PurchaseBillPayment::where('branch_balance_transfer_id', $transfer->id)->get();
                    foreach ($linkedPayments as $pPayment) {
                        $pBill = \App\Models\PurchaseBill::find($pPayment->purchase_bill_id);
                        if ($pBill) {
                            $newPaid = max(0, round((float) $pBill->paid_amount - (float) $pPayment->amount, 2));
                            [$billTotal] = $this->purchaseBillTotals($pBill);
                            $newStatus = \App\Models\PurchaseBill::PAYMENT_STATUS_PENDING;
                            if ($newPaid >= (float) $billTotal && (float) $billTotal > 0) {
                                $newStatus = \App\Models\PurchaseBill::PAYMENT_STATUS_PAID;
                            } elseif ($newPaid > 0) {
                                $newStatus = \App\Models\PurchaseBill::PAYMENT_STATUS_PARTIAL;
                            }
                            $pBill->update([
                                'paid_amount'    => $newPaid,
                                'payment_status' => $newStatus,
                            ]);
                        }
                        $pPayment->delete();
                    }
                }

                $oldData = $transfer->toArray();
                $transfer->update(['transfer_no' => $transfer->transfer_no . '_del_' . $transfer->id]);
                $transfer->delete();

                ActivityLogger::log(
                    'Balance Transfer',
                    'delete',
                    $transfer,
                    $oldData,
                    null,
                    'Deleted balance transfer ' . $transfer->transfer_no
                );
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'insufficient_balance_revert') {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Cannot delete accepted transfer because To Location has insufficient balance to revert.',
                ], 422);
            }
            throw $e;
        }

        $this->clearBalanceTransferCache($fromLocationId, $toLocationId);

        return response()->json([
            'status'  => 'success',
            'message' => 'Balance transfer deleted successfully.',
        ]);
    }

    private function clearBalanceTransferCache($fromLocationId = null, $toLocationId = null)
    {
        try {
            \Illuminate\Support\Facades\Cache::forget('admin_sidebar_pending_bt_count_all');
            if ($fromLocationId) {
                \Illuminate\Support\Facades\Cache::forget('admin_sidebar_pending_bt_count_' . $fromLocationId);
            }
            if ($toLocationId) {
                \Illuminate\Support\Facades\Cache::forget('admin_sidebar_pending_bt_count_' . $toLocationId);
            }
        } catch (\Exception $e) {
        }
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
        $basePrice = (float) (($item->purchase_price > 0) ? $item->purchase_price : ($item->variant->purchase_price ?? $product?->purchase_price ?? 0));

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

    private function filterActiveTransactions($transactions)
    {
        if ($transactions->isEmpty()) {
            return $transactions;
        }

        $purchasesToCheck = [];
        $ordersToCheck = [];

        foreach ($transactions as $tx) {
            $notes = $tx->notes ?? '';
            if (preg_match('/Purchase\s+#([^\s\[\]]+)/i', $notes, $matches)) {
                $purchasesToCheck[$matches[1]] = true;
            } elseif (preg_match('/\[Inv:\s*([^\]]+)\]/i', $notes, $matches)) {
                $purchasesToCheck[trim($matches[1])] = true;
            }
            if (preg_match('/Sale\s+#([^\s\[\]]+)/i', $notes, $matches)) {
                $ordersToCheck[$matches[1]] = true;
            }
        }

        if (empty($purchasesToCheck) && empty($ordersToCheck)) {
            return $transactions;
        }

        $existingPurchases = !empty($purchasesToCheck)
            ? \App\Models\Purchase::whereIn('invoice_no', array_keys($purchasesToCheck))->pluck('invoice_no')->flip()
            : collect();

        $existingOrders = !empty($ordersToCheck)
            ? \App\Models\Order::whereIn('order_no', array_keys($ordersToCheck))->pluck('order_no')->flip()
            : collect();

        return $transactions->filter(function ($tx) use ($existingPurchases, $existingOrders) {
            $notes = $tx->notes ?? '';
            if (preg_match('/Purchase\s+#([^\s\[\]]+)/i', $notes, $matches)) {
                if (!$existingPurchases->has($matches[1])) {
                    return false;
                }
            } elseif (preg_match('/\[Inv:\s*([^\]]+)\]/i', $notes, $matches)) {
                if (!$existingPurchases->has(trim($matches[1]))) {
                    return false;
                }
            }
            if (preg_match('/Sale\s+#([^\s\[\]]+)/i', $notes, $matches)) {
                if (!$existingOrders->has($matches[1])) {
                    return false;
                }
            }
            return true;
        });
    }
}


