<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Location;
use App\Models\LocationBalanceTransaction;
use App\Models\Order;
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
        $this->guardSuperAdmin('view supplier ledger');

        return view('ledgers.supplier');
    }

    public function supplierLedgerData(Request $request)
    {
        $this->guardSuperAdmin('view supplier ledger');

        [$startDate, $endDate] = $this->resolveDateRange($request);

        $purchases = Purchase::with('supplier')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->get();

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

        $statusLabels = [
            Purchase::PAYMENT_STATUS_PENDING => '<span class="badge bg-label-danger">Pending</span>',
            Purchase::PAYMENT_STATUS_PAID    => '<span class="badge bg-label-success">Paid</span>',
            Purchase::PAYMENT_STATUS_PARTIAL => '<span class="badge bg-label-warning">Partially Paid</span>',
        ];

        $totalPurchase = 0.0;
        $totalPayment  = 0.0;
        $totalOutstanding = 0.0;

        $mappedRows = $sortedRows->map(function ($row, $index) use ($statusLabels, &$totalPurchase, &$totalPayment, &$totalOutstanding) {
            $totalPurchase += $row['total_amount'];
            $totalPayment  += $row['paid_amount'];
            $totalOutstanding += $row['due_amount'];

            return [
                'index'          => $index + 1,
                'supplier'       => e($row['supplier_name']),
                'date'           => $row['date_formatted'],
                'date_raw'       => $row['date_raw'],
                'total_amount'   => format_price($row['total_amount']),
                'paid_amount'    => format_price($row['paid_amount']),
                'due_amount'     => format_price($row['due_amount']),
                'payment_status' => $statusLabels[$row['payment_status']] ?? '-',
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

        $locationId = $this->resolveLocationId($request);

        if (!$locationId) {
            return response()->json(['status' => 'error', 'message' => 'No location available.'], 422);
        }

        $today = now()->format('Y-m-d');

        $sales = Order::where('location_id', $locationId)
            ->where('order_type', 'sale')
            ->where('payment_method', 'cash')
            ->whereDate('created_at', '<=', $today)
            ->get()
            ->groupBy(fn ($sale) => $sale->created_at->format('Y-m-d'));

        $expenses = Expense::where('location_id', $locationId)
            ->where('payment_method', 'Cash')
            ->whereDate('expense_date', '<=', $today)
            ->get()
            ->groupBy(fn ($expense) => $expense->expense_date->format('Y-m-d'));

        $days = $sales->keys()->concat($expenses->keys())->push($today)->unique()->sort()->values();

        $opening = $this->openingBalance($locationId, LocationBalanceTransaction::BALANCE_TYPE_CASH, $days->first());

        $running = $opening;
        $chain = $days->map(function ($day) use (&$running, $sales, $expenses) {
            $daySale    = (float) ($sales->get($day)?->sum('final_amount') ?? 0);
            $dayExpense = (float) ($expenses->get($day)?->sum('amount') ?? 0);
            $dayOpening = $running;
            $dayClosing = $dayOpening + $daySale - $dayExpense;
            $running    = $dayClosing;

            return ['date' => $day, 'opening' => $dayOpening, 'in' => $daySale, 'out' => $dayExpense, 'closing' => $dayClosing];
        });

        $todayNode = $chain->firstWhere('date', $today);

        $rows = $chain
            ->filter(fn ($node) => $node['in'] > 0 || $node['out'] > 0)
            ->when($request->filled('start_date'), fn ($c) => $c->where('date', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn ($c) => $c->where('date', '<=', $request->end_date))
            ->values()
            ->map(fn ($node, $index) => [
                'index'   => $index + 1,
                'date'    => format_date($node['date']),
                'opening' => format_price($node['opening']),
                'sale'    => format_price($node['in']),
                'expense' => format_price($node['out']),
                'closing' => format_price($node['closing']),
            ]);

        return response()->json([
            'status'  => 'success',
            'data'    => $rows,
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

        $locationId = $this->resolveLocationId($request);

        if (!$locationId) {
            return response()->json(['status' => 'error', 'message' => 'No location available.'], 422);
        }

        $today = now()->format('Y-m-d');

        $receipts = Order::where('location_id', $locationId)
            ->where('order_type', 'sale')
            ->where(function ($q) {
                $q->where('payment_method', 'online')
                    ->orWhere(function ($q2) {
                        $q2->where('payment_method', 'cod')->where('payment_status', Order::PAYMENT_STATUS_PAID);
                    });
            })
            ->whereDate('created_at', '<=', $today)
            ->get()
            ->groupBy(fn ($order) => $order->created_at->format('Y-m-d'));

        $payments = Expense::where('location_id', $locationId)
            ->whereIn('payment_method', ['Bank Transfer', 'UPI', 'Card'])
            ->whereDate('expense_date', '<=', $today)
            ->get()
            ->groupBy(fn ($expense) => $expense->expense_date->format('Y-m-d'));

        $days = $receipts->keys()->concat($payments->keys())->push($today)->unique()->sort()->values();

        $opening = $this->openingBalance($locationId, LocationBalanceTransaction::BALANCE_TYPE_BANK, $days->first());

        $running = $opening;
        $chain = $days->map(function ($day) use (&$running, $receipts, $payments) {
            $dayReceipt = (float) ($receipts->get($day)?->sum('final_amount') ?? 0);
            $dayPayment = (float) ($payments->get($day)?->sum('amount') ?? 0);
            $dayOpening = $running;
            $dayClosing = $dayOpening + $dayReceipt - $dayPayment;
            $running    = $dayClosing;

            return ['date' => $day, 'opening' => $dayOpening, 'in' => $dayReceipt, 'out' => $dayPayment, 'closing' => $dayClosing];
        });

        $todayNode = $chain->firstWhere('date', $today);

        $rows = $chain
            ->filter(fn ($node) => $node['in'] > 0 || $node['out'] > 0)
            ->when($request->filled('start_date'), fn ($c) => $c->where('date', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn ($c) => $c->where('date', '<=', $request->end_date))
            ->values()
            ->map(fn ($node, $index) => [
                'index'   => $index + 1,
                'date'    => format_date($node['date']),
                'opening' => format_price($node['opening']),
                'receipt' => format_price($node['in']),
                'payment' => format_price($node['out']),
                'closing' => format_price($node['closing']),
            ]);

        return response()->json([
            'status'  => 'success',
            'data'    => $rows,
            'summary' => [
                'opening' => format_price($todayNode['opening']),
                'receipt' => format_price($todayNode['in']),
                'payment' => format_price($todayNode['out']),
                'closing' => format_price($todayNode['closing']),
            ],
        ]);
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

        $locationId = $this->resolveLocationId($request);
        [$startDate, $endDate] = $this->resolveDateRange($request);

        if (!$locationId) {
            return response()->json(['status' => 'error', 'message' => 'No location available.'], 422);
        }

        $transferIn = PurchaseBill::with('items.product', 'items.variant', 'fromLocation')
            ->where('to_location_id', $locationId)
            ->where('status', PurchaseBill::STATUS_ACCEPTED)
            ->whereDate('accepted_at', '>=', $startDate)
            ->whereDate('accepted_at', '<=', $endDate)
            ->orderBy('accepted_at')
            ->get();

        $transferOut = PurchaseBill::with('items.product', 'items.variant', 'toLocation')
            ->where('from_location_id', $locationId)
            ->where('status', PurchaseBill::STATUS_ACCEPTED)
            ->whereDate('accepted_at', '>=', $startDate)
            ->whereDate('accepted_at', '<=', $endDate)
            ->orderBy('accepted_at')
            ->get();

        $pending = PurchaseBill::with('items.product', 'items.variant', 'fromLocation')
            ->where('to_location_id', $locationId)
            ->where('status', PurchaseBill::STATUS_PENDING)
            ->get();

        $transferValue = fn ($transfer) => $transfer->items->sum(
            fn ($item) => ($item->variant->purchase_price ?? $item->product->purchase_price ?? 0) * $item->quantity
        );

        $totalIn      = $transferIn->sum($transferValue);
        $totalOut     = $transferOut->sum($transferValue);
        $totalPending = $pending->sum($transferValue);

        $rows = collect()
            ->concat($transferIn->map(fn ($transfer) => [
                'date'      => $transfer->accepted_at,
                'type'      => '<span class="badge bg-label-success">Transfer In</span>',
                'reference' => e($transfer->transfer_no) . ' (from ' . e($transfer->fromLocation->name ?? '-') . ')',
                'amount'    => '<span class="text-success">+ ' . format_price($transferValue($transfer)) . '</span>',
            ]))
            ->concat($transferOut->map(fn ($transfer) => [
                'date'      => $transfer->accepted_at,
                'type'      => '<span class="badge bg-label-danger">Transfer Out</span>',
                'reference' => e($transfer->transfer_no) . ' (to ' . e($transfer->toLocation->name ?? '-') . ')',
                'amount'    => '<span class="text-danger">- ' . format_price($transferValue($transfer)) . '</span>',
            ]))
            ->sortBy('date')
            ->values()
            ->map(function ($row, $index) {
                $row['index'] = $index + 1;
                $row['date']  = format_date($row['date']);

                return $row;
            });

        return response()->json([
            'status'  => 'success',
            'data'    => $rows,
            'summary' => [
                'transfer_in'  => format_price($totalIn),
                'transfer_out' => format_price($totalOut),
                'outstanding'  => format_price($totalPending),
            ],
        ]);
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
}
