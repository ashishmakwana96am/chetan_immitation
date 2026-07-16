<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Location;
use App\Models\LocationBalanceTransaction;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseBill;
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

        [$startDate, $endDate] = $this->resolveDateRange($request);

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $purchasesQuery = Purchase::with('supplier')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

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

        // Group by Date portion and Supplier ID
        $grouped = $purchases->groupBy(function ($purchase) {
            $date = $purchase->created_at ? $purchase->created_at->format('Y-m-d') : now()->format('Y-m-d');
            return $date . '_' . ($purchase->supplier_id ?? 0);
        });

        $rows = collect();
        foreach ($grouped as $key => $items) {
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

            $dateObj = $first->created_at ?? now();

            $rows->push([
                'supplier_id'    => $first->supplier_id,
                'supplier_name'  => $first->supplier->name ?? '-',
                'date_raw'       => $dateObj->format('Y-m-d'),
                'date_formatted' => format_date($dateObj),
                'total_amount'   => $totalAmount,
                'paid_amount'    => $paidAmount,
                'due_amount'     => $dueAmount,
                'payment_status' => $status,
            ]);
        }

        // Sort by date desc, then supplier name asc
        $sortedRows = $rows->sort(function ($a, $b) {
            if ($a['date_raw'] !== $b['date_raw']) {
                return strcmp($b['date_raw'], $a['date_raw']);
            }
            return strcmp($a['supplier_name'], $b['supplier_name']);
        })->values();

        $totalPurchase = 0.0;
        $totalPayment  = 0.0;
        $totalOutstanding = 0.0;

        $mappedRows = $sortedRows->map(function ($row, $index) use (&$totalPurchase, &$totalPayment, &$totalOutstanding) {
            $totalPurchase += $row['total_amount'];
            $totalPayment  += $row['paid_amount'];
            $totalOutstanding += $row['due_amount'];

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

    public function supplierLedgerDetail(Request $request)
    {
        $this->authorizeLedger('view supplier ledger');

        $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'date'        => ['required', 'date_format:Y-m-d'],
        ]);

        $supplier = \App\Models\Supplier::findOrFail($request->supplier_id);
        $date = \Carbon\Carbon::parse($request->date);

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $purchasesQuery = Purchase::where('supplier_id', $request->supplier_id)
            ->whereDate('created_at', $request->date);

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
            'date',
            'purchases',
            'totalPurchase',
            'totalPayment',
            'totalOutstanding'
        ));
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
            ->orderBy('id', 'asc')
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

        $currentBalance = Location::whereIn('id', $locationIds)->sum('cash_balance');

        return response()->json([
            'status'  => 'success',
            'data'    => $rows,
            'current_balance' => format_price($currentBalance),
            'summary' => [
                'opening' => format_price($todayNode['opening']),
                'sale'    => format_price($todayNode['in']),
                'expense' => format_price($todayNode['out']),
                'closing' => format_price($todayNode['closing']),
            ],
        ]);
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

        $currentBalance = Location::whereIn('id', $locationIds)->sum('bank_balance');

        return response()->json([
            'status'  => 'success',
            'data'    => $rows,
            'current_balance' => format_price($currentBalance),
            'summary' => [
                'opening' => format_price($todayNode['opening']),
                'receipt' => format_price($todayNode['in']),
                'payment' => format_price($todayNode['out']),
                'closing' => format_price($todayNode['closing']),
            ],
        ]);
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
            ->orderBy('id', 'asc')
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

    public function branchLedgerData(Request $request)
    {
        $this->authorizeLedger('view branch ledger');

        [$locationIds, $actionLocationId] = $this->resolveLocationIds($request);
        [$startDate, $endDate] = $this->resolveDateRange($request);

        if (empty($locationIds)) {
            return response()->json(['status' => 'error', 'message' => 'No location available.'], 422);
        }

        $transferQty = fn ($transfer) => $transfer->items->sum(
            fn ($item) => ($item->pair_type === 'pair') ? $item->quantity * 2 : $item->quantity
        );

        $transferIn = PurchaseBill::with('items')
            ->whereIn('to_location_id', $locationIds)
            ->where('status', PurchaseBill::STATUS_ACCEPTED)
            ->whereDate('accepted_at', '>=', $startDate)
            ->whereDate('accepted_at', '<=', $endDate)
            ->get()
            ->groupBy(fn ($transfer) => $transfer->accepted_at->format('Y-m-d'));

        $transferOut = PurchaseBill::with('items')
            ->whereIn('from_location_id', $locationIds)
            ->where('status', PurchaseBill::STATUS_ACCEPTED)
            ->whereDate('accepted_at', '>=', $startDate)
            ->whereDate('accepted_at', '<=', $endDate)
            ->get()
            ->groupBy(fn ($transfer) => $transfer->accepted_at->format('Y-m-d'));

        $days = $transferIn->keys()->concat($transferOut->keys())->unique()->sort()->values();

        $totalIn  = 0;
        $totalOut = 0;

        $dayNodes = $days
            ->map(function ($day) use (&$totalIn, &$totalOut, $transferIn, $transferOut, $transferQty) {
                $dayIn  = (int) ($transferIn->get($day)?->sum($transferQty) ?? 0);
                $dayOut = (int) ($transferOut->get($day)?->sum($transferQty) ?? 0);
                $totalIn  += $dayIn;
                $totalOut += $dayOut;

                return ['date' => $day, 'in' => $dayIn, 'out' => $dayOut];
            })
            ->sortByDesc('date')
            ->values()
            ->all();

        $currentStock = $this->locationStockQty($locationIds);
        $runningStock = $currentStock;

        foreach ($dayNodes as $key => $node) {
            $dayNodes[$key]['outstanding'] = $runningStock;
            $runningStock = $runningStock - $node['in'] + $node['out'];
        }

        $rows = collect($dayNodes)->map(fn ($node, $index) => [
            'index'        => $index + 1,
            'date'         => format_date($node['date']),
            'date_sort'    => $node['date'],
            'date_group'   => format_date($node['date']),
            'transfer_in'  => number_format($node['in']),
            'transfer_out' => number_format($node['out']),
            'outstanding'  => number_format($node['outstanding']),
            'actions'      => '
                <div class="dropdown table-action-dropdown">
                    <button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                        <span>Actions</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">
                        <a href="' . route('admin.ledgers.branch.detail') . '?location_id=' . $actionLocationId . '&date=' . $node['date'] . '" class="dropdown-item">
                            <i class="ti ti-eye me-2"></i>View
                        </a>
                    </div>
                </div>'
        ]);

        return response()->json([
            'status'  => 'success',
            'data'    => $rows,
            'summary' => [
                'transfer_in'  => number_format($totalIn),
                'transfer_out' => number_format($totalOut),
                'outstanding'  => number_format($currentStock),
            ],
        ]);
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

        $transferValue = fn ($transfer) => $transfer->items->sum(
            fn ($item) => ($item->variant->purchase_price ?? $item->product->purchase_price ?? 0) * $item->quantity
        );

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

        $transferQty = fn ($transfer) => $transfer->items->sum(
            fn ($item) => ($item->pair_type === 'pair') ? $item->quantity * 2 : $item->quantity
        );
        $totalIn = $transferIn->sum($transferQty);
        $totalOut = $transferOut->sum($transferQty);
        $outstanding = $this->locationStockQty($locationIds);

        return view('ledgers.branch-detail', compact(
            'location',
            'date',
            'transferIn',
            'transferOut',
            'totalIn',
            'totalOut',
            'transferQty',
            'outstanding'
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

    private function resolveLocations(): array
    {
        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $locations = $isRestricted
            ? Location::where('id', $user->location_id)->get()
            : Location::where('status', 1)->orderBy('name')->get();

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
     * (sales, expenses, purchases, accepted stock transfers, manual adjustments)
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
     * Current stock value held across the given locations right now: for simple
     * products from the materialized Inventory table, for variable products
     * from the live purchased/sold/transferred ledger (Product::getVariantStock())
     * — mirrors the calculation used by ReportController::stockInventory.
     *
     * @param  array<int>  $locationIds
     */
    private function locationStockValue(array $locationIds): float
    {
        $value = 0.0;

        $products = Product::with('variants')->get();

        $simpleQuantities = Inventory::whereIn('location_id', $locationIds)
            ->selectRaw('product_id, sum(quantity) as qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        foreach ($products as $product) {
            $purchasePrice = (float) $product->purchase_price;

            if ($product->is_variable) {
                $stockByLocation = $product->getVariantStock();

                foreach ($product->variants as $variant) {
                    $price = (float) ($variant->purchase_price ?? $purchasePrice);

                    foreach ($locationIds as $locId) {
                        $qty = $stockByLocation[$locId]['variants'][$variant->id] ?? 0;
                        $value += $qty * $price;
                    }
                }
            } else {
                $qty = (int) ($simpleQuantities[$product->id] ?? 0);
                $value += $qty * $purchasePrice;
            }
        }

        return $value;
    }

    private function locationStockQty(array $locationIds): int
    {
        $totalQty = 0;

        $products = Product::with('variants')->get();

        $simpleQuantities = Inventory::whereIn('location_id', $locationIds)
            ->selectRaw('product_id, sum(quantity) as qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        foreach ($products as $product) {
            if ($product->is_variable) {
                $stockByLocation = $product->getVariantStock();

                foreach ($product->variants as $variant) {
                    foreach ($locationIds as $locId) {
                        $qty = (int) ($stockByLocation[$locId]['variants'][$variant->id] ?? 0);
                        $totalQty += $qty;
                    }
                }
            } else {
                $qty = (int) ($simpleQuantities[$product->id] ?? 0);
                $totalQty += $qty;
            }
        }

        return $totalQty;
    }
}
