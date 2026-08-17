<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Location;
use App\Models\LocationBalance;
use App\Models\LocationBalanceTransaction;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    // -----------------------------------------------------------------
    // Supplier Ledger (company-wide, super-admin only)
    // -----------------------------------------------------------------

    public function supplierLedger()
    {
        $this->authorizeLedger('view supplier ledger');

        [$locations, $isRestricted] = $this->resolveLocations();

        return view('ledgers.supplier', compact('locations', 'isRestricted'));
    }

    public function supplierLedgerData(Request $request)
    {
        $this->authorizeLedger('view supplier ledger');

        $asOnDate = $this->resolveAsOnDate($request);

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $purchasesQuery = Purchase::with('supplier')
            ->whereDate('created_at', '<=', $asOnDate);

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

        $purchases = $purchasesQuery->get();

        // Group by Supplier ID only — cumulative totals as on the selected date
        $grouped = $purchases->groupBy(fn ($purchase) => $purchase->supplier_id ?? 0);

        $rows = collect();
        foreach ($grouped as $items) {
            $first = $items->first();
            $totalAmount = (float) $items->sum('total_amount');
            $paidAmount  = (float) $items->sum('paid_amount');
            $dueAmount   = max(0.0, $totalAmount - $paidAmount);

            // Determine aggregate status
            if ($dueAmount <= 0) {
                $status = Purchase::PAYMENT_STATUS_PAID;
            } elseif ($paidAmount <= 0) {
                $status = Purchase::PAYMENT_STATUS_PENDING;
            } else {
                $status = Purchase::PAYMENT_STATUS_PARTIAL;
            }

            $rows->push([
                'supplier_id'    => $first->supplier_id,
                'supplier_name'  => $first->supplier->name ?? '-',
                'total_amount'   => $totalAmount,
                'paid_amount'    => $paidAmount,
                'due_amount'     => $dueAmount,
                'payment_status' => $status,
            ]);
        }

        $sortedRows = $rows->sort(function ($a, $b) {
            if ($a['due_amount'] !== $b['due_amount']) {
                return $b['due_amount'] <=> $a['due_amount'];
            }
            return strcmp($a['supplier_name'], $b['supplier_name']);
        })->values();

        $mappedRows = $sortedRows->map(function ($row, $index) {
            return [
                'index'          => $index + 1,
                'supplier_id'    => $row['supplier_id'],
                'supplier'       => e($row['supplier_name']),
                'total_amount'   => format_price($row['total_amount']),
                'paid_amount'    => format_price($row['paid_amount']),
                'due_amount'     => format_price($row['due_amount']),
            ];
        });

        $branchLocations = $isRestricted
            ? Location::where('id', $user->location_id)->get()
            : Location::where('status', 1)->orderBy('name')->get();

        $purchasesByLocation = $purchases->groupBy('location_id');

        $branchSummary = [];
        foreach ($branchLocations as $loc) {
            $locPurchases = $purchasesByLocation->get($loc->id, collect());
            $locTotal = (float) $locPurchases->sum('total_amount');
            $locPaid  = (float) $locPurchases->sum('paid_amount');

            $branchSummary[$loc->id] = [
                'purchase'    => format_price($locTotal),
                'payment'     => format_price($locPaid),
                'outstanding' => format_price(max(0.0, $locTotal - $locPaid)),
            ];
        }

        return response()->json([
            'status'         => 'success',
            'data'           => $mappedRows,
            'branch_summary' => $branchSummary,
        ]);
    }

    public function supplierLedgerDetail(Request $request)
    {
        $this->authorizeLedger('view supplier ledger');

        $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'as_on_date'  => ['required', 'date_format:Y-m-d'],
        ]);

        $supplier = \App\Models\Supplier::findOrFail($request->supplier_id);
        $asOnDate = \Carbon\Carbon::parse($request->as_on_date);

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $purchasesQuery = Purchase::where('supplier_id', $request->supplier_id)
            ->whereDate('created_at', '<=', $request->as_on_date)
            ->orderByDesc('created_at');

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

        $purchases = $purchasesQuery->get();

        $totalPurchase = $purchases->sum('total_amount');
        $totalPayment = $purchases->sum('paid_amount');
        $totalOutstanding = max(0.0, $totalPurchase - $totalPayment);

        return view('ledgers.supplier-detail', compact(
            'supplier',
            'asOnDate',
            'purchases',
            'totalPurchase',
            'totalPayment',
            'totalOutstanding'
        ));
    }

    public function exportSupplierLedger(Request $request)
    {
        $this->authorizeLedger('view supplier ledger');

        $asOnDate = $this->resolveAsOnDate($request);

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $purchasesQuery = Purchase::with('supplier')
            ->whereDate('created_at', '<=', $asOnDate);

        $locationName = 'All Locations';
        if ($isRestricted) {
            $locationId = $user->location_id;
            $purchasesQuery->whereHas('items.allocations', function ($aq) use ($locationId) {
                $aq->where('location_id', $locationId);
            });
            $locationName = Location::find($locationId)->name ?? 'All Locations';
        } elseif ($request->filled('location_id')) {
            $locationId = (int) $request->location_id;
            $purchasesQuery->whereHas('items.allocations', function ($aq) use ($locationId) {
                $aq->where('location_id', $locationId);
            });
            $locationName = Location::find($locationId)->name ?? 'All Locations';
        }

        $purchases = $purchasesQuery->get();

        $grouped = $purchases->groupBy(fn ($purchase) => $purchase->supplier_id ?? 0);

        $rows = collect();
        foreach ($grouped as $items) {
            $first = $items->first();
            $totalAmount = (float) $items->sum('total_amount');
            $paidAmount  = (float) $items->sum('paid_amount');
            $dueAmount   = max(0.0, $totalAmount - $paidAmount);

            $rows->push([
                'supplier_name' => $first->supplier->name ?? '-',
                'total_amount'  => $totalAmount,
                'paid_amount'   => $paidAmount,
                'due_amount'    => $dueAmount,
            ]);
        }

        $rows = $rows->sort(function ($a, $b) {
            if ($a['due_amount'] !== $b['due_amount']) {
                return $b['due_amount'] <=> $a['due_amount'];
            }
            return strcmp($a['supplier_name'], $b['supplier_name']);
        })->values();

        $totalAmount = $rows->sum('total_amount');
        $totalPaid   = $rows->sum('paid_amount');
        $totalDue    = $rows->sum('due_amount');

        if ($rows->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected filters. Nothing to export.');
        }

        if ($request->boolean('auto_print') && !$request->boolean('stream')) {
            return view('sales.pdf-print-wrapper', [
                'title'  => 'Supplier Ledger',
                'pdfUrl' => route('admin.ledgers.supplier.export', array_merge($request->all(), ['stream' => 1])),
            ]);
        }

        $pdf = Pdf::loadView('ledgers.pdf.supplier', compact(
            'rows',
            'asOnDate',
            'locationName',
            'totalAmount',
            'totalPaid',
            'totalDue'
        ))->setPaper('a4', 'landscape');

        ActivityLogger::log('Ledgers', 'export', null, null, null, 'Supplier ledger exported to PDF');

        return $pdf->stream('supplier_ledger_' . now()->format('Ymd_His') . '.pdf');
    }

    public function cashLedgerDetail(Request $request)
    {
        $this->authorizeLedger('view cash ledger');

        $request->validate([
            'location_id' => ['required'],
            'date'        => ['required', 'date_format:Y-m-d'],
        ]);

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        if ($isRestricted) {
            $locationIds = [$user->location_id];
        } elseif ($request->location_id === 'all') {
            $locationIds = Location::where('status', 1)->pluck('id')->all();
        } else {
            $request->validate(['location_id' => ['integer', 'exists:locations,id']]);
            $locationIds = [(int) $request->location_id];
        }

        abort_if(empty($locationIds), 404);

        $location = count($locationIds) === 1 ? Location::find($locationIds[0]) : null;
        $date = \Carbon\Carbon::parse($request->date);

        $transactions = LocationBalanceTransaction::with('createdBy', 'location')
            ->whereIn('location_id', $locationIds)
            ->where('balance_type', LocationBalanceTransaction::BALANCE_TYPE_CASH)
            ->whereDate('created_at', $request->date)
            ->orderBy('id', 'desc')
            ->get();

        $openingBalance = collect($locationIds)->sum(
            fn ($locId) => $this->openingBalance($locId, LocationBalanceTransaction::BALANCE_TYPE_CASH, $request->date)
        );

        $totalIn = $transactions->where('type', LocationBalanceTransaction::TYPE_CREDIT)->sum('amount');
        $totalOut = $transactions->where('type', LocationBalanceTransaction::TYPE_DEBIT)->sum('amount');
        $closingBalance = $openingBalance + $totalIn - $totalOut;

        return view('ledgers.cash-detail', compact(
            'location',
            'date',
            'transactions',
            'openingBalance',
            'totalIn',
            'totalOut',
            'closingBalance'
        ));
    }

    // -----------------------------------------------------------------
    // Cash Ledger (per branch)
    // -----------------------------------------------------------------

    public function cashLedger()
    {
        $this->authorizeLedger('view cash ledger');

        [$locations, $isRestricted] = $this->resolveLocations();

        return view('ledgers.cash', compact('locations', 'isRestricted'));
    }

    public function cashLedgerData(Request $request)
    {
        $this->authorizeLedger('view cash ledger');

        [$locationIds, $actionLocationId] = $this->resolveLocationIds($request);

        if (empty($locationIds)) {
            return response()->json(['status' => 'error', 'message' => 'No location available.'], 422);
        }

        $today = now()->format('Y-m-d');

        $chain = $this->buildLedgerChain($locationIds, LocationBalanceTransaction::BALANCE_TYPE_CASH, $today);

        $todayNode = $chain->firstWhere('date', $today);

        $rows = $chain
            ->filter(fn ($node) => $node['in'] > 0 || $node['out'] > 0 || $node['date'] === $today)
            ->when($request->filled('start_date'), fn ($c) => $c->where('date', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn ($c) => $c->where('date', '<=', $request->end_date))
            ->sortByDesc('date')
            ->values()
            ->map(fn ($node, $index) => [
                'index'      => $index + 1,
                'date'       => format_date($node['date']),
                'date_sort'  => $node['date'],
                'date_group' => format_date($node['date']),
                'opening'    => format_price($node['opening']),
                'sale'       => format_price($node['in']),
                'expense'    => format_price($node['out']),
                'closing'    => format_price($node['closing']),
                'actions'    => '
                    <div class="dropdown table-action-dropdown">
                        <button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                            <span>Actions</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">
                            <a href="' . route('admin.ledgers.cash.detail') . '?location_id=' . $actionLocationId . '&date=' . $node['date'] . '" class="dropdown-item">
                                <i class="ti ti-eye me-2"></i>View
                            </a>
                        </div>
                    </div>'
            ]);

        $currentBalance = LocationBalance::whereIn('location_id', $locationIds)->sum('cash_balance');

        // Branch-wise summary cards: restricted users only ever get their own
        // branch; super-admins get every active branch, narrowed to the
        // filtered one if selected.
        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        $branchLocations = $isRestricted
            ? Location::where('id', $user->location_id)->get()
            : Location::where('status', 1)->orderBy('name')->get();

        $branchSummary = [];
        foreach ($branchLocations as $loc) {
            $locChain = $this->buildLedgerChain([$loc->id], LocationBalanceTransaction::BALANCE_TYPE_CASH, $today);
            $locTodayNode = $locChain->firstWhere('date', $today);

            $branchSummary[$loc->id] = [
                'opening' => format_price($locTodayNode['opening']),
                'sale'    => format_price($locTodayNode['in']),
                'expense' => format_price($locTodayNode['out']),
                'closing' => format_price($locTodayNode['closing']),
            ];
        }

        return response()->json([
            'status'  => 'success',
            'data'    => $rows,
            'current_balance' => format_price($currentBalance),
            'branch_summary' => $branchSummary,
        ]);
    }

    public function exportCashLedger(Request $request)
    {
        $this->authorizeLedger('view cash ledger');

        [$locationIds, $actionLocationId] = $this->resolveLocationIds($request);

        abort_if(empty($locationIds), 404);

        $today = now()->format('Y-m-d');
        $chain = $this->buildLedgerChain($locationIds, LocationBalanceTransaction::BALANCE_TYPE_CASH, $today);

        $startDate = $request->filled('start_date') ? $request->start_date : null;
        $endDate   = $request->filled('end_date') ? $request->end_date : null;

        $rows = $chain
            ->filter(fn ($node) => $node['in'] > 0 || $node['out'] > 0 || $node['date'] === $today)
            ->when($startDate, fn ($c) => $c->where('date', '>=', $startDate))
            ->when($endDate, fn ($c) => $c->where('date', '<=', $endDate))
            ->sortByDesc('date')
            ->values();

        $currentBalance = LocationBalance::whereIn('location_id', $locationIds)->sum('cash_balance');
        $locationName = is_int($actionLocationId) ? (Location::find($actionLocationId)->name ?? 'All Locations') : 'All Locations';

        $totalIn  = $rows->sum('in');
        $totalOut = $rows->sum('out');

        if ($rows->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected filters. Nothing to export.');
        }

        if ($request->boolean('auto_print') && !$request->boolean('stream')) {
            return view('sales.pdf-print-wrapper', [
                'title'  => 'Cash Ledger',
                'pdfUrl' => route('admin.ledgers.cash.export', array_merge($request->all(), ['stream' => 1])),
            ]);
        }

        $pdf = Pdf::loadView('ledgers.pdf.cash', compact(
            'rows',
            'startDate',
            'endDate',
            'locationName',
            'currentBalance',
            'totalIn',
            'totalOut'
        ))->setPaper('a4', 'landscape');

        ActivityLogger::log('Ledgers', 'export', null, null, null, 'Cash ledger exported to PDF');

        return $pdf->stream('cash_ledger_' . now()->format('Ymd_His') . '.pdf');
    }

    // -----------------------------------------------------------------
    // Bank Ledger (per branch)
    // -----------------------------------------------------------------

    public function bankLedger()
    {
        $this->authorizeLedger('view bank ledger');

        [$locations, $isRestricted] = $this->resolveLocations();

        return view('ledgers.bank', compact('locations', 'isRestricted'));
    }

    public function bankLedgerData(Request $request)
    {
        $this->authorizeLedger('view bank ledger');

        [$locationIds, $actionLocationId] = $this->resolveLocationIds($request);

        if (empty($locationIds)) {
            return response()->json(['status' => 'error', 'message' => 'No location available.'], 422);
        }

        $today = now()->format('Y-m-d');

        $chain = $this->buildLedgerChain($locationIds, LocationBalanceTransaction::BALANCE_TYPE_BANK, $today);

        $todayNode = $chain->firstWhere('date', $today);

        $rows = $chain
            ->filter(fn ($node) => $node['in'] > 0 || $node['out'] > 0 || $node['date'] === $today)
            ->when($request->filled('start_date'), fn ($c) => $c->where('date', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn ($c) => $c->where('date', '<=', $request->end_date))
            ->sortByDesc('date')
            ->values()
            ->map(fn ($node, $index) => [
                'index'      => $index + 1,
                'date'       => format_date($node['date']),
                'date_sort'  => $node['date'],
                'date_group' => format_date($node['date']),
                'opening'    => format_price($node['opening']),
                'receipt'    => format_price($node['in']),
                'payment'    => format_price($node['out']),
                'closing'    => format_price($node['closing']),
                'actions'    => '
                    <div class="dropdown table-action-dropdown">
                        <button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                            <span>Actions</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">
                            <a href="' . route('admin.ledgers.bank.detail') . '?location_id=' . $actionLocationId . '&date=' . $node['date'] . '" class="dropdown-item">
                                <i class="ti ti-eye me-2"></i>View
                            </a>
                        </div>
                    </div>'
            ]);

        $currentBalance = LocationBalance::whereIn('location_id', $locationIds)->sum('bank_balance');

        // Branch-wise summary cards: restricted users only ever get their own
        // branch; super-admins get every active branch, narrowed to the
        // filtered one if selected.
        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        $branchLocations = $isRestricted
            ? Location::where('id', $user->location_id)->get()
            : Location::where('status', 1)->orderBy('name')->get();

        $branchSummary = [];
        foreach ($branchLocations as $loc) {
            $locChain = $this->buildLedgerChain([$loc->id], LocationBalanceTransaction::BALANCE_TYPE_BANK, $today);
            $locTodayNode = $locChain->firstWhere('date', $today);

            $branchSummary[$loc->id] = [
                'opening' => format_price($locTodayNode['opening']),
                'receipt' => format_price($locTodayNode['in']),
                'payment' => format_price($locTodayNode['out']),
                'closing' => format_price($locTodayNode['closing']),
            ];
        }

        return response()->json([
            'status'  => 'success',
            'data'    => $rows,
            'current_balance' => format_price($currentBalance),
            'branch_summary' => $branchSummary,
        ]);
    }

    public function exportBankLedger(Request $request)
    {
        $this->authorizeLedger('view bank ledger');

        [$locationIds, $actionLocationId] = $this->resolveLocationIds($request);

        abort_if(empty($locationIds), 404);

        $today = now()->format('Y-m-d');
        $chain = $this->buildLedgerChain($locationIds, LocationBalanceTransaction::BALANCE_TYPE_BANK, $today);

        $startDate = $request->filled('start_date') ? $request->start_date : null;
        $endDate   = $request->filled('end_date') ? $request->end_date : null;

        $rows = $chain
            ->filter(fn ($node) => $node['in'] > 0 || $node['out'] > 0 || $node['date'] === $today)
            ->when($startDate, fn ($c) => $c->where('date', '>=', $startDate))
            ->when($endDate, fn ($c) => $c->where('date', '<=', $endDate))
            ->sortByDesc('date')
            ->values();

        $currentBalance = LocationBalance::whereIn('location_id', $locationIds)->sum('bank_balance');
        $locationName = is_int($actionLocationId) ? (Location::find($actionLocationId)->name ?? 'All Locations') : 'All Locations';

        $totalIn  = $rows->sum('in');
        $totalOut = $rows->sum('out');

        if ($rows->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected filters. Nothing to export.');
        }

        if ($request->boolean('auto_print') && !$request->boolean('stream')) {
            return view('sales.pdf-print-wrapper', [
                'title'  => 'Bank Ledger',
                'pdfUrl' => route('admin.ledgers.bank.export', array_merge($request->all(), ['stream' => 1])),
            ]);
        }

        $pdf = Pdf::loadView('ledgers.pdf.bank', compact(
            'rows',
            'startDate',
            'endDate',
            'locationName',
            'currentBalance',
            'totalIn',
            'totalOut'
        ))->setPaper('a4', 'landscape');

        ActivityLogger::log('Ledgers', 'export', null, null, null, 'Bank ledger exported to PDF');

        return $pdf->stream('bank_ledger_' . now()->format('Ymd_His') . '.pdf');
    }

    public function bankLedgerDetail(Request $request)
    {
        $this->authorizeLedger('view bank ledger');

        $request->validate([
            'location_id' => ['required'],
            'date'        => ['required', 'date_format:Y-m-d'],
        ]);

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        if ($isRestricted) {
            $locationIds = [$user->location_id];
        } elseif ($request->location_id === 'all') {
            $locationIds = Location::where('status', 1)->pluck('id')->all();
        } else {
            $request->validate(['location_id' => ['integer', 'exists:locations,id']]);
            $locationIds = [(int) $request->location_id];
        }

        abort_if(empty($locationIds), 404);

        $location = count($locationIds) === 1 ? Location::find($locationIds[0]) : null;
        $date = \Carbon\Carbon::parse($request->date);

        $transactions = LocationBalanceTransaction::with('createdBy', 'location')
            ->whereIn('location_id', $locationIds)
            ->where('balance_type', LocationBalanceTransaction::BALANCE_TYPE_BANK)
            ->whereDate('created_at', $request->date)
            ->orderBy('id', 'desc')
            ->get();

        $openingBalance = collect($locationIds)->sum(
            fn ($locId) => $this->openingBalance($locId, LocationBalanceTransaction::BALANCE_TYPE_BANK, $request->date)
        );

        $totalIn = $transactions->where('type', LocationBalanceTransaction::TYPE_CREDIT)->sum('amount');
        $totalOut = $transactions->where('type', LocationBalanceTransaction::TYPE_DEBIT)->sum('amount');
        $closingBalance = $openingBalance + $totalIn - $totalOut;

        return view('ledgers.bank-detail', compact(
            'location',
            'date',
            'transactions',
            'openingBalance',
            'totalIn',
            'totalOut',
            'closingBalance'
        ));
    }

    // -----------------------------------------------------------------
    // Branch Ledger (per branch)
    // -----------------------------------------------------------------

    public function branchLedger()
    {
        $this->authorizeLedger('view branch ledger');

        [$locations, $isRestricted] = $this->resolveLocations();

        return view('ledgers.branch', compact('locations', 'isRestricted'));
    }

    public function branchDuesBills(Request $request)
    {
        $this->authorizeLedger('view branch ledger');

        $request->validate([
            'from_location_id' => ['required', 'integer', 'exists:locations,id'],
            'to_location_id'   => ['required', 'integer', 'exists:locations,id'],
        ]);

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        if ($isRestricted
            && (int) $user->location_id !== (int) $request->from_location_id
            && (int) $user->location_id !== (int) $request->to_location_id) {
            abort(403);
        }

        $fromLocation = Location::findOrFail($request->from_location_id);
        $toLocation = Location::findOrFail($request->to_location_id);

        $stockMultiplierFor = function ($item) {
            if ($item->custom_size_value !== null && (float) $item->custom_size_value > 0) {
                return (float) $item->custom_size_value;
            }
            if ($item->product && !$item->product->pair_product) {
                return 1.0;
            }
            return ($item->pair_type === 'pair') ? 2.0 : 1.0;
        };

        $formatStockQtyText = function ($pairsCount, $pcsCount) {
            $parts = [];
            if ($pairsCount > 0) {
                $parts[] = number_format($pairsCount) . ' pair' . ($pairsCount > 1 ? 's' : '');
            }
            if ($pcsCount > 0 || empty($parts)) {
                $parts[] = number_format($pcsCount) . ' pcs';
            }
            return implode(', ', $parts);
        };

        $bills = PurchaseBill::with(['items.product', 'items.variant'])
            ->where('from_location_id', $request->from_location_id)
            ->where('to_location_id', $request->to_location_id)
            ->where('status', PurchaseBill::STATUS_ACCEPTED)
            ->whereIn('payment_status', [PurchaseBill::PAYMENT_STATUS_PENDING, PurchaseBill::PAYMENT_STATUS_PARTIAL])
            ->orderByDesc('accepted_at')
            ->get()
            ->map(function ($bill) use ($stockMultiplierFor, $formatStockQtyText) {
                $pairs = 0;
                $pcs = 0;
                $amount = 0.0;

                foreach ($bill->items as $item) {
                    $multiplier = $stockMultiplierFor($item);
                    if ($item->product && $item->product->pair_product) {
                        $pairs += (int) $item->quantity;
                    } else {
                        $pcs += (int) round($item->quantity * $multiplier);
                    }
                    $amount += $this->purchasePriceForLedgerItem($item) * $item->quantity;
                }

                $bill->computed_amount = $amount;
                $bill->computed_qty_text = $formatStockQtyText($pairs, $pcs);

                return $bill;
            });

        $total = $bills->sum('computed_amount');

        return view('ledgers.branch-dues-bills', compact('fromLocation', 'toLocation', 'bills', 'total'));
    }

    public function branchLedgerData(Request $request)
    {
        $this->authorizeLedger('view branch ledger');

        [$locationIds] = $this->resolveLocationIds($request);
        [$startDate, $endDate] = $this->resolveDateRange($request);

        if (empty($locationIds)) {
            return response()->json(['status' => 'error', 'message' => 'No location available.'], 422);
        }

        $getTransferAmount = function ($transferCollection) {
            $total = 0.0;
            foreach ($transferCollection as $transfer) {
                $billTotal = 0.0;
                foreach ($transfer->items as $item) {
                    $billTotal += $this->purchasePriceForLedgerItem($item) * $item->quantity;
                }
                $paid = (float) ($transfer->paid_amount ?? 0);
                $total += max(0.0, $billTotal - $paid);
            }
            return (float) $total;
        };

        // One row per bill (not per in/out side) — a transfer between two of the
        // filtered locations only needs listing once, naming both branches.
        // Only unpaid/partial bills belong here; once marked Paid it drops off, same as
        // the Pending Payments Between Branches summary below.
        $transfers = PurchaseBill::with(['items.product', 'items.variant', 'fromLocation', 'toLocation'])
            ->where(function ($q) use ($locationIds) {
                $q->whereIn('from_location_id', $locationIds)
                    ->orWhereIn('to_location_id', $locationIds);
            })
            ->where('status', PurchaseBill::STATUS_ACCEPTED)
            ->whereIn('payment_status', [PurchaseBill::PAYMENT_STATUS_PENDING, PurchaseBill::PAYMENT_STATUS_PARTIAL])
            ->whereDate('accepted_at', '>=', $startDate)
            ->whereDate('accepted_at', '<=', $endDate)
            ->orderByDesc('accepted_at')
            ->get();

        $rows = $transfers->values()->map(function ($transfer, $index) use ($getTransferAmount) {
            $amount = $getTransferAmount([$transfer]);
            $date = $transfer->accepted_at->format('Y-m-d');

            $branchLabel = e($transfer->fromLocation->name ?? '-')
                . ' <i class="ti ti-arrow-right mx-1 text-muted"></i> '
                . e($transfer->toLocation->name ?? '-');

            return [
                'index'       => $index + 1,
                'date'        => format_date($date),
                'date_sort'   => $date,
                'date_group'  => format_date($date),
                'transfer_no' => '<code>' . e($transfer->transfer_no) . '</code>',
                'branch'      => $branchLabel,
                'amount'      => '<span class="fw-semibold">' . format_price($amount) . '</span>',
                'actions'     => '
                    <div class="dropdown table-action-dropdown">
                        <button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                            <span>Actions</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">
                            <a href="' . route('admin.purchase-bills.show', $transfer->id) . '" class="dropdown-item">
                                <i class="ti ti-eye me-2"></i>View
                            </a>
                        </div>
                    </div>',
            ];
        })->values();

        // Pending Payments Between Branches — same date-range/location filters
        // as the table above, but scoped to accepted + unpaid/partial bills only.
        $pendingBills = PurchaseBill::with(['items.product', 'items.variant', 'fromLocation', 'toLocation'])
            ->where(function ($q) use ($locationIds) {
                $q->whereIn('from_location_id', $locationIds)
                    ->orWhereIn('to_location_id', $locationIds);
            })
            ->where('status', PurchaseBill::STATUS_ACCEPTED)
            ->whereIn('payment_status', [PurchaseBill::PAYMENT_STATUS_PENDING, PurchaseBill::PAYMENT_STATUS_PARTIAL])
            ->whereDate('accepted_at', '>=', $startDate)
            ->whereDate('accepted_at', '<=', $endDate)
            ->get();

        $branchDues = $pendingBills
            ->groupBy(fn ($bill) => $bill->from_location_id . ':' . $bill->to_location_id)
            ->map(function ($bills) use ($getTransferAmount) {
                $first = $bills->first();

                return [
                    'from_location_id'  => $first->from_location_id,
                    'to_location_id'    => $first->to_location_id,
                    'payable_branch'    => e($first->toLocation->name ?? '-'),
                    'receivable_branch' => e($first->fromLocation->name ?? '-'),
                    'amount_raw'        => $getTransferAmount($bills),
                    'bills_count'       => $bills->count(),
                ];
            })
            ->filter(fn ($due) => $due['amount_raw'] > 0)
            ->sortByDesc('amount_raw')
            ->values()
            ->map(function ($due) {
                $due['amount'] = format_price($due['amount_raw']);
                unset($due['amount_raw']);

                return $due;
            });

        return response()->json([
            'status'      => 'success',
            'data'        => $rows,
            'branch_dues' => $branchDues,
        ]);
    }

    public function exportBranchLedger(Request $request)
    {
        $this->authorizeLedger('view branch ledger');

        [$locationIds] = $this->resolveLocationIds($request);
        [$startDate, $endDate] = $this->resolveDateRange($request);

        abort_if(empty($locationIds), 404);

        $getTransferAmount = function ($transferCollection) {
            $total = 0.0;
            foreach ($transferCollection as $transfer) {
                foreach ($transfer->items as $item) {
                    $total += $this->purchasePriceForLedgerItem($item) * $item->quantity;
                }
            }
            return (float) $total;
        };

        $transfers = PurchaseBill::with(['items.product', 'items.variant', 'fromLocation', 'toLocation'])
            ->where(function ($q) use ($locationIds) {
                $q->whereIn('from_location_id', $locationIds)
                    ->orWhereIn('to_location_id', $locationIds);
            })
            ->where('status', PurchaseBill::STATUS_ACCEPTED)
            ->where('payment_status', PurchaseBill::PAYMENT_STATUS_PENDING)
            ->whereDate('accepted_at', '>=', $startDate)
            ->whereDate('accepted_at', '<=', $endDate)
            ->orderByDesc('accepted_at')
            ->get();

        $rows = $transfers->map(function ($transfer) use ($getTransferAmount) {
            return [
                'date'        => $transfer->accepted_at,
                'transfer_no' => $transfer->transfer_no,
                'from'        => $transfer->fromLocation->name ?? '-',
                'to'          => $transfer->toLocation->name ?? '-',
                'amount'      => $getTransferAmount([$transfer]),
            ];
        })->values();

        $totalAmount = $rows->sum('amount');

        if ($rows->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected filters. Nothing to export.');
        }

        if ($request->boolean('auto_print') && !$request->boolean('stream')) {
            return view('sales.pdf-print-wrapper', [
                'title'  => 'Branch Ledger',
                'pdfUrl' => route('admin.ledgers.branch.export', array_merge($request->all(), ['stream' => 1])),
            ]);
        }

        $pdf = Pdf::loadView('ledgers.pdf.branch', compact('rows', 'startDate', 'endDate', 'totalAmount'))
            ->setPaper('a4', 'landscape');

        ActivityLogger::log('Ledgers', 'export', null, null, null, 'Branch ledger exported to PDF');

        return $pdf->stream('branch_ledger_' . now()->format('Ymd_His') . '.pdf');
    }

    public function branchLedgerDetail(Request $request)
    {
        $this->authorizeLedger('view branch ledger');

        $request->validate([
            'location_id' => ['required'],
            'date'        => ['required', 'date_format:Y-m-d'],
        ]);

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        if ($isRestricted) {
            $locationIds = [$user->location_id];
        } elseif ($request->location_id === 'all') {
            $locationIds = Location::where('status', 1)->pluck('id')->all();
        } else {
            $request->validate(['location_id' => ['integer', 'exists:locations,id']]);
            $locationIds = [(int) $request->location_id];
        }

        abort_if(empty($locationIds), 404);

        $location = count($locationIds) === 1 ? Location::find($locationIds[0]) : null;
        $date = \Carbon\Carbon::parse($request->date);

        $transferIn = PurchaseBill::with('items.product', 'items.variant', 'fromLocation', 'toLocation')
            ->whereIn('to_location_id', $locationIds)
            ->where('status', PurchaseBill::STATUS_ACCEPTED)
            ->whereDate('accepted_at', $request->date)
            ->orderBy('accepted_at')
            ->get();

        $transferOut = PurchaseBill::with('items.product', 'items.variant', 'fromLocation', 'toLocation')
            ->whereIn('from_location_id', $locationIds)
            ->where('status', PurchaseBill::STATUS_ACCEPTED)
            ->whereDate('accepted_at', $request->date)
            ->orderBy('accepted_at')
            ->get();

        $stockMultiplierFor = function ($item) {
            if ($item->custom_size_value !== null && (float)$item->custom_size_value > 0) {
                return (float) $item->custom_size_value;
            }
            if ($item->product && !$item->product->pair_product) {
                return 1.0;
            }
            return ($item->pair_type === 'pair') ? 2.0 : 1.0;
        };

        $formatStockQtyText = function ($pairsCount, $pcsCount) {
            $parts = [];
            if ($pairsCount > 0) {
                $parts[] = number_format($pairsCount) . ' pair' . ($pairsCount > 1 ? 's' : '');
            }
            if ($pcsCount > 0 || empty($parts)) {
                $parts[] = number_format($pcsCount) . ' pcs';
            }
            return implode(', ', $parts);
        };

        $getTransferQtyBreakdown = function ($transferCollection) use ($stockMultiplierFor) {
            $totalPairs = 0;
            $totalPcs = 0;

            foreach ($transferCollection as $transfer) {
                foreach ($transfer->items as $item) {
                    $multiplier = $stockMultiplierFor($item);
                    $totalPieces = (int) round($item->quantity * $multiplier);
                    if ($item->product && $item->product->pair_product) {
                        $totalPairs += (int) $item->quantity;
                    } else {
                        $totalPcs += $totalPieces;
                    }
                }
            }
            return [$totalPairs, $totalPcs];
        };

        $singleTransferQtyText = function ($transfer) use ($getTransferQtyBreakdown, $formatStockQtyText) {
            [$pairs, $pcs] = $getTransferQtyBreakdown([$transfer]);
            return $formatStockQtyText($pairs, $pcs);
        };

        $transferAmount = function ($transfer) {
            return (float) $transfer->items->sum(function ($item) {
                return $this->purchasePriceForLedgerItem($item) * $item->quantity;
            });
        };

        [$inPairs, $inPcs]   = $getTransferQtyBreakdown($transferIn);
        $totalInQtyText     = $formatStockQtyText($inPairs, $inPcs);
        $totalInAmount      = $transferIn->sum($transferAmount);

        [$outPairs, $outPcs] = $getTransferQtyBreakdown($transferOut);
        $totalOutQtyText    = $formatStockQtyText($outPairs, $outPcs);
        $totalOutAmount     = $transferOut->sum($transferAmount);
        
        $stockData          = $this->getAllStockData($locationIds);
        $outstandingQtyText = $formatStockQtyText($stockData['total_pairs'], $stockData['total_loose_pcs']);
        $outstandingValue   = $stockData['total_value'];

        return view('ledgers.branch-detail', compact(
            'location',
            'date',
            'transferIn',
            'transferOut',
            'totalInQtyText',
            'totalInAmount',
            'totalOutQtyText',
            'totalOutAmount',
            'singleTransferQtyText',
            'transferAmount',
            'outstandingQtyText',
            'outstandingValue'
        ));
    }

    // -----------------------------------------------------------------
    // Shared helpers
    // -----------------------------------------------------------------

    private function guardSuperAdmin(string $permission): void
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);
        $this->authorize($permission);
    }

    private function authorizeLedger(string $permission): void
    {
        $this->authorize($permission);
    }

    private function purchasePriceForLedgerItem(PurchaseBillItem $item): float
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

        return round($basePrice * ($selectedSize / (float) $maxSize));
    }

    private function resolveLocations(): array
    {
        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $locations = $isRestricted
            ? Location::with('balance')->where('id', $user->location_id)->get()
            : Location::with('balance')->where('status', 1)->orderBy('name')->get();

        return [$locations, $isRestricted];
    }

    private function resolveLocationId(Request $request): ?int
    {
        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        if ($isRestricted) {
            return $user->location_id;
        }

        return $request->filled('location_id') ? (int) $request->location_id : (int) (Location::where('status', 1)->orderBy('name')->value('id'));
    }

    /**
     * Resolve the location IDs to scope a ledger query to, plus the value to use
     * for the "view" action link (a specific location id, or 'all' for the
     * super-admin aggregate-all-locations view).
     *
     * @return array{0: array<int>, 1: int|string}
     */
    private function resolveLocationIds(Request $request): array
    {
        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        if ($isRestricted) {
            return [[$user->location_id], $user->location_id];
        }

        if ($request->filled('location_id')) {
            $locationId = (int) $request->location_id;

            return [[$locationId], $locationId];
        }

        $locationIds = Location::where('status', 1)->pluck('id')->all();

        return [$locationIds, 'all'];
    }

    private function resolveDateRange(Request $request): array
    {
        $startDate = $request->start_date ?: now()->subDays(30)->format('Y-m-d');
        $endDate   = $request->end_date ?: now()->format('Y-m-d');

        return [$startDate, $endDate];
    }

    private function resolveAsOnDate(Request $request): string
    {
        $asOnDate = $request->as_on_date ?: now()->format('Y-m-d');

        return $asOnDate > now()->format('Y-m-d') ? now()->format('Y-m-d') : $asOnDate;
    }

    private function openingBalance(int $locationId, string $balanceType, string $beforeDate): float
    {
        $lastEntry = LocationBalanceTransaction::where('location_id', $locationId)
            ->where('balance_type', $balanceType)
            ->whereDate('created_at', '<', $beforeDate)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        return $lastEntry ? (float) $lastEntry->balance_after : 0.0;
    }

    /**
     * Build a day-by-day opening/in/out/closing chain for a set of locations,
     * sourced directly from LocationBalanceTransaction so every kind of movement
     * (sales, expenses, purchases, accepted purchase bills, manual adjustments)
     * is reflected — not just sales/expenses. Aggregates across locations when
     * more than one is given.
     *
     * @param  array<int>  $locationIds
     * @return \Illuminate\Support\Collection<int, array{date: string, opening: float, in: float, out: float, closing: float}>
     */
    private function buildLedgerChain(array $locationIds, string $balanceType, string $upToDate): \Illuminate\Support\Collection
    {
        $transactions = LocationBalanceTransaction::whereIn('location_id', $locationIds)
            ->where('balance_type', $balanceType)
            ->whereDate('created_at', '<=', $upToDate)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($tx) => $tx->created_at->format('Y-m-d'));

        $days = $transactions->keys()->push($upToDate)->unique()->sort()->values();

        $runningByLocation = [];
        foreach ($locationIds as $locId) {
            $runningByLocation[$locId] = $this->openingBalance($locId, $balanceType, $days->first());
        }

        return $days->map(function ($day) use (&$runningByLocation, $transactions, $locationIds) {
            $dayTx = $transactions->get($day, collect());
            $dayOpening = array_sum($runningByLocation);

            $inTotal = 0.0;
            $outTotal = 0.0;

            foreach ($locationIds as $locId) {
                $locTx = $dayTx->where('location_id', $locId)->values();

                if ($locTx->isEmpty()) {
                    continue;
                }

                $inTotal  += (float) $locTx->where('type', LocationBalanceTransaction::TYPE_CREDIT)->sum('amount');
                $outTotal += (float) $locTx->where('type', LocationBalanceTransaction::TYPE_DEBIT)->sum('amount');
                $runningByLocation[$locId] = (float) $locTx->last()->balance_after;
            }

            $dayClosing = array_sum($runningByLocation);

            return ['date' => $day, 'opening' => $dayOpening, 'in' => $inTotal, 'out' => $outTotal, 'closing' => $dayClosing];
        });
    }

    /**
     * Efficiently batch-calculate stock quantity and monetary value (using purchase price)
     * across specified location IDs for both simple and variable products in a single pass.
     *
     * @param array<int> $locationIds
     * @return array{by_location: array<int, array{qty: int, value: float}>, total_qty: int, total_value: float}
     */
    private function getAllStockData(array $locationIds): array
    {
        $locationIds = array_map('intval', array_values(array_unique($locationIds)));

        $byLocation = [];
        foreach ($locationIds as $locId) {
            $byLocation[$locId] = ['qty' => 0, 'value' => 0.0, 'pairs' => 0, 'loose_pcs' => 0];
        }

        if (empty($locationIds)) {
            return ['by_location' => $byLocation, 'total_qty' => 0, 'total_value' => 0.0, 'total_pairs' => 0, 'total_loose_pcs' => 0];
        }

        $products = Product::with('variants')->get();
        $variableProducts = $products->where('type', 'variable');
        $variableIds = $variableProducts->pluck('id')->all();

        // 1. Simple products: read from materialized Inventory table
        $simpleInventory = Inventory::whereIn('location_id', $locationIds)
            ->selectRaw('location_id, product_id, sum(quantity) as qty')
            ->groupBy('location_id', 'product_id')
            ->get()
            ->groupBy('location_id');

        // 2. Batch fetch for variable products across all locations in single queries
        $purchasedByLocVar = [];
        $soldByLocVar      = [];
        $transInByLocVar   = [];
        $transOutByLocVar  = [];

        if (!empty($variableIds)) {
            $allocations = \App\Models\PurchaseAllocation::whereIn('location_id', $locationIds)
                ->whereHas('purchaseItem', function ($q) use ($variableIds) {
                    $q->whereIn('product_id', $variableIds)
                      ->whereHas('invoice', fn ($sub) => $sub->where('status', 2));
                })
                ->with('purchaseItem')
                ->get();

            foreach ($allocations as $alloc) {
                $locId = $alloc->location_id;
                $pId   = $alloc->purchaseItem->product_id;
                $vId   = $alloc->purchaseItem->product_variant_id;
                $qty   = (int) $alloc->quantity;

                if ($vId) {
                    $purchasedByLocVar[$locId][$pId][$vId] = ($purchasedByLocVar[$locId][$pId][$vId] ?? 0) + $qty;
                }
            }

            $orderItems = \App\Models\OrderItem::whereHas('order', function ($q) use ($locationIds) {
                    $q->whereIn('location_id', $locationIds)
                      ->where('status', \App\Models\Order::STATUS_APPROVE);
                })
                ->whereIn('product_id', $variableIds)
                ->with('order')
                ->get();

            foreach ($orderItems as $item) {
                $locId = $item->order->location_id;
                $pId   = $item->product_id;
                $vId   = $item->product_variant_id;
                $qty   = (int) $item->quantity;

                if ($vId) {
                    $soldByLocVar[$locId][$pId][$vId] = ($soldByLocVar[$locId][$pId][$vId] ?? 0) + $qty;
                }
            }

            $transferItems = PurchaseBillItem::whereIn('product_id', $variableIds)
                ->whereHas('transfer', function ($q) {
                    $q->where('status', PurchaseBill::STATUS_ACCEPTED);
                })
                ->with('transfer')
                ->get();

            foreach ($transferItems as $item) {
                $pId       = $item->product_id;
                $vId       = $item->product_variant_id;
                $fromLocId = $item->transfer->from_location_id;
                $toLocId   = $item->transfer->to_location_id;
                $qty       = (int) $item->quantity;

                if (in_array($fromLocId, $locationIds, true) && $vId) {
                    $transOutByLocVar[$fromLocId][$pId][$vId] = ($transOutByLocVar[$fromLocId][$pId][$vId] ?? 0) + $qty;
                }

                if (in_array($toLocId, $locationIds, true) && $vId) {
                    $transInByLocVar[$toLocId][$pId][$vId] = ($transInByLocVar[$toLocId][$pId][$vId] ?? 0) + $qty;
                }
            }
        }

        foreach ($products as $product) {
            $purchasePrice = (float) $product->purchase_price;
            $isPair = (bool) $product->pair_product;

            if ($product->type === 'variable') {
                foreach ($product->variants as $variant) {
                    $variantPrice = (float) ($variant->purchase_price ?? $purchasePrice);

                    foreach ($locationIds as $locId) {
                        $pQty  = $purchasedByLocVar[$locId][$product->id][$variant->id] ?? 0;
                        $sQty  = $soldByLocVar[$locId][$product->id][$variant->id] ?? 0;
                        $tiQty = $transInByLocVar[$locId][$product->id][$variant->id] ?? 0;
                        $toQty = $transOutByLocVar[$locId][$product->id][$variant->id] ?? 0;

                        $vStock = $pQty - $sQty + $tiQty - $toQty;
                        if ($vStock > 0) {
                            $byLocation[$locId]['qty']   += $vStock;
                            $byLocation[$locId]['value'] += $vStock * $variantPrice;
                            if ($isPair) {
                                $byLocation[$locId]['pairs'] += (int) floor($vStock / 2);
                                $byLocation[$locId]['loose_pcs'] += (int) ($vStock % 2);
                            } else {
                                $byLocation[$locId]['loose_pcs'] += $vStock;
                            }
                        }
                    }
                }
            } else {
                foreach ($locationIds as $locId) {
                    $locInvData = $simpleInventory->get($locId);
                    $qty = (int) ($locInvData?->firstWhere('product_id', $product->id)?->qty ?? 0);
                    if ($qty > 0) {
                        $byLocation[$locId]['qty']   += $qty;
                        $byLocation[$locId]['value'] += $qty * $purchasePrice;
                        if ($isPair) {
                            $byLocation[$locId]['pairs'] += (int) floor($qty / 2);
                            $byLocation[$locId]['loose_pcs'] += (int) ($qty % 2);
                        } else {
                            $byLocation[$locId]['loose_pcs'] += $qty;
                        }
                    }
                }
            }
        }

        $totalQty = array_sum(array_column($byLocation, 'qty'));
        $totalValue = array_sum(array_column($byLocation, 'value'));
        $totalPairs = array_sum(array_column($byLocation, 'pairs'));
        $totalLoosePcs = array_sum(array_column($byLocation, 'loose_pcs'));

        return [
            'by_location'     => $byLocation,
            'total_qty'       => $totalQty,
            'total_value'     => $totalValue,
            'total_pairs'     => $totalPairs,
            'total_loose_pcs' => $totalLoosePcs,
        ];
    }

    private function locationStockValue(array $locationIds): float
    {
        return $this->getAllStockData($locationIds)['total_value'];
    }

    private function locationStockQty(array $locationIds): int
    {
        return $this->getAllStockData($locationIds)['total_qty'];
    }

    // -----------------------------------------------------------------
    // Customer Ledger
    // -----------------------------------------------------------------

    public function customerLedger()
    {
        $this->authorizeLedger('view customer ledger');

        [$locations, $isRestricted] = $this->resolveLocations();

        return view('ledgers.customer', compact('locations', 'isRestricted'));
    }

    public function customerLedgerData(Request $request)
    {
        $this->authorizeLedger('view customer ledger');

        [$startDate, $endDate] = $this->resolveDateRange($request);

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $ordersQuery = \App\Models\Order::with('customer')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        if ($isRestricted) {
            $ordersQuery->where('location_id', $user->location_id);
        } else {
            if ($request->filled('location_id')) {
                $ordersQuery->where('location_id', (int) $request->location_id);
            }
        }

        $orders = $ordersQuery->get();

        // Group by Date portion and Customer ID
        $grouped = $orders->groupBy(function ($order) {
            $date = $order->created_at ? $order->created_at->format('Y-m-d') : now()->format('Y-m-d');
            return $date . '_' . ($order->customer_id ?? 0);
        });

        $rows = collect();
        foreach ($grouped as $key => $items) {
            $first = $items->first();
            $totalAmount = 0.0;
            $paidAmount  = 0.0;

            foreach ($items as $order) {
                $totalAmount += (float) $order->final_amount;
                if ($order->payment_status == \App\Models\Order::PAYMENT_STATUS_PAID) {
                    $paidAmount += (float) $order->final_amount;
                } elseif ($order->payment_status == \App\Models\Order::PAYMENT_STATUS_PARTIAL) {
                    $paidAmount += (float) $order->paid_cash_amount + (float) $order->paid_online_amount;
                } else {
                    $paidAmount += (float) $order->payments()->where('status', \App\Models\OrderPayment::STATUS_CAPTURED)->sum('amount');
                }
            }

            $dueAmount = max(0.0, $totalAmount - $paidAmount);

            // Determine aggregate status
            if ($dueAmount <= 0) {
                $status = \App\Models\Order::PAYMENT_STATUS_PAID;
            } elseif ($paidAmount <= 0) {
                $status = \App\Models\Order::PAYMENT_STATUS_PENDING;
            } else {
                $status = 3; // Partial
            }

            $dateObj = $first->created_at ?? now();

            $rows->push([
                'customer_id'    => $first->customer_id ?? 0,
                'customer_name'  => $first->customer->name ?? 'Walk-in',
                'date_raw'       => $dateObj->format('Y-m-d'),
                'date_formatted' => format_date($dateObj),
                'total_amount'   => $totalAmount,
                'paid_amount'    => $paidAmount,
                'due_amount'     => $dueAmount,
                'payment_status' => $status,
            ]);
        }

        $sortedRows = $rows->sort(function ($a, $b) {
            if ($a['date_raw'] !== $b['date_raw']) {
                return strcmp($b['date_raw'], $a['date_raw']);
            }
            return strcmp($a['customer_name'], $b['customer_name']);
        })->values();

        $mappedRows = $sortedRows->map(function ($row, $index) {
            $dateObj = \Carbon\Carbon::parse($row['date_raw']);

            return [
                'index'          => $index + 1,
                'customer_id'    => $row['customer_id'],
                'customer'       => e($row['customer_name']),
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

        $ordersByLocation = $orders->groupBy('location_id');

        $branchSummary = [];
        foreach ($branchLocations as $loc) {
            $locOrders = $ordersByLocation->get($loc->id, collect());

            $locSales = 0.0;
            $locPaid  = 0.0;
            foreach ($locOrders as $order) {
                $locSales += (float) $order->final_amount;
                if ($order->payment_status == \App\Models\Order::PAYMENT_STATUS_PAID) {
                    $locPaid += (float) $order->final_amount;
                } elseif ($order->payment_status == \App\Models\Order::PAYMENT_STATUS_PARTIAL) {
                    $locPaid += (float) $order->paid_cash_amount + (float) $order->paid_online_amount;
                } else {
                    $locPaid += (float) $order->payments()->where('status', \App\Models\OrderPayment::STATUS_CAPTURED)->sum('amount');
                }
            }

            $branchSummary[$loc->id] = [
                'sales'       => format_price($locSales),
                'payment'     => format_price($locPaid),
                'outstanding' => format_price(max(0.0, $locSales - $locPaid)),
            ];
        }

        return response()->json([
            'status'         => 'success',
            'data'           => $mappedRows,
            'branch_summary' => $branchSummary,
        ]);
    }

    public function exportCustomerLedger(Request $request)
    {
        $this->authorizeLedger('view customer ledger');

        [$startDate, $endDate] = $this->resolveDateRange($request);

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $ordersQuery = \App\Models\Order::with('customer')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        $locationName = 'All Locations';
        if ($isRestricted) {
            $ordersQuery->where('location_id', $user->location_id);
            $locationName = Location::find($user->location_id)->name ?? 'All Locations';
        } elseif ($request->filled('location_id')) {
            $ordersQuery->where('location_id', (int) $request->location_id);
            $locationName = Location::find((int) $request->location_id)->name ?? 'All Locations';
        }

        $orders = $ordersQuery->get();

        $grouped = $orders->groupBy(function ($order) {
            $date = $order->created_at ? $order->created_at->format('Y-m-d') : now()->format('Y-m-d');
            return $date . '_' . ($order->customer_id ?? 0);
        });

        $rows = collect();
        foreach ($grouped as $items) {
            $first = $items->first();
            $totalAmount = 0.0;
            $paidAmount  = 0.0;

            foreach ($items as $order) {
                $totalAmount += (float) $order->final_amount;
                if ($order->payment_status == \App\Models\Order::PAYMENT_STATUS_PAID) {
                    $paidAmount += (float) $order->final_amount;
                } elseif ($order->payment_status == \App\Models\Order::PAYMENT_STATUS_PARTIAL) {
                    $paidAmount += (float) $order->paid_cash_amount + (float) $order->paid_online_amount;
                } else {
                    $paidAmount += (float) $order->payments()->where('status', \App\Models\OrderPayment::STATUS_CAPTURED)->sum('amount');
                }
            }

            $dueAmount = max(0.0, $totalAmount - $paidAmount);

            $rows->push([
                'customer_name' => $first->customer->name ?? 'Walk-in',
                'date'          => $first->created_at ?? now(),
                'total_amount'  => $totalAmount,
                'paid_amount'   => $paidAmount,
                'due_amount'    => $dueAmount,
            ]);
        }

        $rows = $rows->sort(function ($a, $b) {
            if ($a['date']->format('Y-m-d') !== $b['date']->format('Y-m-d')) {
                return $b['date'] <=> $a['date'];
            }
            return strcmp($a['customer_name'], $b['customer_name']);
        })->values();

        $totalAmount = $rows->sum('total_amount');
        $totalPaid   = $rows->sum('paid_amount');
        $totalDue    = $rows->sum('due_amount');

        if ($rows->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected filters. Nothing to export.');
        }

        if ($request->boolean('auto_print') && !$request->boolean('stream')) {
            return view('sales.pdf-print-wrapper', [
                'title'  => 'Customer Ledger',
                'pdfUrl' => route('admin.ledgers.customer.export', array_merge($request->all(), ['stream' => 1])),
            ]);
        }

        $pdf = Pdf::loadView('ledgers.pdf.customer', compact(
            'rows',
            'startDate',
            'endDate',
            'locationName',
            'totalAmount',
            'totalPaid',
            'totalDue'
        ))->setPaper('a4', 'landscape');

        ActivityLogger::log('Ledgers', 'export', null, null, null, 'Customer ledger exported to PDF');

        return $pdf->stream('customer_ledger_' . now()->format('Ymd_His') . '.pdf');
    }

    public function customerLedgerDetail(Request $request)
    {
        $this->authorizeLedger('view customer ledger');

        $request->validate([
            'customer_id' => ['required', 'integer'],
            'date'        => ['required', 'date_format:Y-m-d'],
        ]);

        $customerId = (int) $request->customer_id;
        $date = \Carbon\Carbon::parse($request->date);

        if ($customerId === 0) {
            $customer = new \App\Models\Customer();
            $customer->name = 'Walk-in';
            $customer->phone = '-';
        } else {
            $customer = \App\Models\Customer::findOrFail($customerId);
        }

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $ordersQuery = \App\Models\Order::whereDate('created_at', $request->date);

        if ($customerId === 0) {
            $ordersQuery->whereNull('customer_id');
        } else {
            $ordersQuery->where('customer_id', $customerId);
        }

        if ($isRestricted) {
            $ordersQuery->where('location_id', $user->location_id);
        } else {
            if ($request->filled('location_id')) {
                $ordersQuery->where('location_id', (int) $request->location_id);
            }
        }

        $orders = $ordersQuery->get();

        $totalSales = 0.0;
        $totalPayment = 0.0;

        foreach ($orders as $order) {
            $totalSales += (float) $order->final_amount;
            if ($order->payment_status == \App\Models\Order::PAYMENT_STATUS_PAID) {
                $totalPayment += (float) $order->final_amount;
            } elseif ($order->payment_status == \App\Models\Order::PAYMENT_STATUS_PARTIAL) {
                $totalPayment += (float) $order->paid_cash_amount + (float) $order->paid_online_amount;
            } else {
                $totalPayment += (float) $order->payments()->where('status', \App\Models\OrderPayment::STATUS_CAPTURED)->sum('amount');
            }
        }

        $totalOutstanding = max(0.0, $totalSales - $totalPayment);

        return view('ledgers.customer-detail', compact(
            'customer',
            'date',
            'orders',
            'totalSales',
            'totalPayment',
            'totalOutstanding'
        ));
    }
}
