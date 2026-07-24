<?php

namespace App\Http\Controllers;

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

        // Compute total credit and debit for summary cards
        // Branch-wise summary cards: restricted users only ever get their own
        // branch; super-admins get every active branch, narrowed to the
        // filtered one if selected.
        $branchLocations = $isRestricted
            ? Location::where('id', $user->location_id)->get()
            : Location::where('status', 1)->orderBy('name')->get();

        $transactionsByLocation = $transactions->groupBy('location_id');

        $branchSummary = [];
        foreach ($branchLocations as $loc) {
            $locTx = $transactionsByLocation->get($loc->id, collect());

            $branchSummary[$loc->id] = [
                'credit'  => format_price($locTx->where('type', LocationBalanceTransaction::TYPE_CREDIT)->sum('amount')),
                'debit'   => format_price($locTx->where('type', LocationBalanceTransaction::TYPE_DEBIT)->sum('amount')),
                'balance' => format_price($loc->cash_balance),
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

        // Branch-wise summary cards: restricted users only ever get their own
        // branch; super-admins get every active branch, narrowed to the
        // filtered one if selected.
        $branchLocations = $isRestricted
            ? Location::where('id', $user->location_id)->get()
            : Location::where('status', 1)->orderBy('name')->get();

        $transactionsByLocation = $transactions->groupBy('location_id');

        $branchSummary = [];
        foreach ($branchLocations as $loc) {
            $locTx = $transactionsByLocation->get($loc->id, collect());

            $branchSummary[$loc->id] = [
                'credit'  => format_price($locTx->where('type', LocationBalanceTransaction::TYPE_CREDIT)->sum('amount')),
                'debit'   => format_price($locTx->where('type', LocationBalanceTransaction::TYPE_DEBIT)->sum('amount')),
                'balance' => format_price($loc->bank_balance),
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
        $entries = collect();

        foreach ($transactions as $tx) {
            $notes = $tx->notes ?? '';
            
            // 1. Identify the source based on transaction notes
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
            if ($sourceFilter !== 'all' && $sourceFilter !== $detectedSource) {
                continue;
            }

            $isCredit = $tx->type === LocationBalanceTransaction::TYPE_CREDIT;
            
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

        $data = $entries->map(function ($item, $index) {
            $item['index'] = $index + 1;
            return $item;
        });

        // Branch-wise summary cards: restricted users only ever get their own
        // branch; super-admins get every active branch, narrowed to the
        // filtered one if selected.
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

            $rows->push([
                'date'         => $tx->created_at,
                'source'       => $sourceLabels[$detectedSource] ?? $detectedSource,
                'balance_type' => $tx->balance_type === LocationBalanceTransaction::BALANCE_TYPE_BANK ? 'Bank' : 'Cash',
                'location'     => $tx->location->name ?? '-',
                'particulars'  => !empty($notes) ? $notes : 'Manual Balance Adjustment',
                'is_credit'    => $isCredit,
                'amount'       => (float) $tx->amount,
                'done_by'      => $tx->createdBy->name ?? '-',
            ]);
        }

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

    public function branchBalances()
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);
        $this->authorize('manage branch balances');

        $locations = Location::with('balance')->where('status', 1)->orderBy('name')->get();

        return view('accounting.branch-balances', compact('locations'));
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
        $branchBalances = [];
        foreach ($locations as $loc) {
            $branchBalances[$loc->id] = [
                'cash' => format_price($loc->cash_balance),
                'bank' => format_price($loc->bank_balance),
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
}

