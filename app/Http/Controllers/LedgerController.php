<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Location;
use App\Models\LocationBalanceTransaction;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\PurchaseBill;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    // -----------------------------------------------------------------
    // Supplier Ledger (company-wide for super-admin, own location only
    // for location-restricted users)
    // -----------------------------------------------------------------

    public function supplierLedger()
    {
        $this->authorizeLedger('view supplier ledger');

        [, $isRestricted] = $this->resolveLocations();
        $locationId = auth()->user()->location_id;

        $suppliers = Supplier::where('status', Supplier::STATUS_ACTIVE)
            ->when($isRestricted, function ($q) use ($locationId) {
                $supplierIds = Purchase::whereHas('items.allocations', function ($aq) use ($locationId) {
                    $aq->where('location_id', $locationId);
                })->pluck('supplier_id')->unique();

                $q->whereIn('id', $supplierIds);
            })
            ->orderBy('name')
            ->get();

        return view('ledgers.supplier', compact('suppliers'));
    }

    public function supplierLedgerData(Request $request)
    {
        $this->authorizeLedger('view supplier ledger');

        [$startDate, $endDate] = $this->resolveDateRange($request);
        [, $isRestricted] = $this->resolveLocations();
        $locationId = auth()->user()->location_id;

        $purchases = Purchase::with('supplier')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->when($isRestricted, function ($q) use ($locationId) {
                $q->whereHas('items.allocations', function ($aq) use ($locationId) {
                    $aq->where('location_id', $locationId);
                });
            })
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

            $dateObj = $first->created_at ?? now();

            $rows->push([
                'supplier_id'    => $first->supplier_id,
                'supplier_name'  => $first->supplier->name ?? '-',
                'date_raw'       => $dateObj->format('Y-m-d'),
                'date_group'     => $dateObj->format('d M Y'),
                'date_formatted' => format_date($dateObj),
                'total_amount'   => $totalAmount,
                'paid_amount'    => $paidAmount,
                'due_amount'     => $dueAmount,
            ]);
        }

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

            return [
                'index'        => $index + 1,
                'supplier'     => e($row['supplier_name']),
                'date'         => $row['date_formatted'],
                'date_raw'     => $row['date_raw'],
                'date_group'   => $row['date_group'],
                'total_amount' => format_price($row['total_amount']),
                'paid_amount'  => format_price($row['paid_amount']),
                'due_amount'   => format_price($row['due_amount']),
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

    /**
     * Full CA-standard transaction ledger for one supplier: Opening Balance
     * (outstanding carried forward from before the start date) + every
     * Purchase (Debit) / Purchase Payment (Credit) inside the date range,
     * sorted chronologically with a running balance.
     */
    public function supplierLedgerDetail(Request $request)
    {
        $this->authorizeLedger('view supplier ledger');

        $request->validate(['supplier_id' => ['required', 'integer', 'exists:suppliers,id']]);

        $supplier = Supplier::findOrFail($request->supplier_id);
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $ledger = $this->buildSupplierLedger($supplier, $startDate, $endDate);

        $mappedRows = collect($ledger['rows'])->values()->map(function ($row, $index) {
            return [
                'index'      => $index + 1,
                'date'       => format_date($row['date']),
                'date_raw'   => $row['date']->format('Y-m-d'),
                'voucher_no' => e($row['voucher_no']),
                'particular' => e($row['particular']),
                'debit'      => $row['debit'] > 0 ? format_price($row['debit']) : '-',
                'credit'     => $row['credit'] > 0 ? format_price($row['credit']) : '-',
                'balance'    => format_price(abs($row['balance'])) . ' ' . ($row['balance'] < 0 ? 'CR' : 'DR'),
                'items'      => $row['type'] === 'purchase' ? $this->purchaseItemRows($row['purchase']) : [],
            ];
        });

        return response()->json([
            'status'  => 'success',
            'data'    => $mappedRows,
            'summary' => [
                'opening'     => format_price(abs($ledger['opening'])) . ' ' . ($ledger['opening'] < 0 ? 'CR' : 'DR'),
                'purchase'    => format_price($ledger['total_purchase']),
                'payment'     => format_price($ledger['total_payment']),
                'closing'     => format_price(abs($ledger['closing'])) . ' ' . ($ledger['closing'] < 0 ? 'CR' : 'DR'),
            ],
        ]);
    }

    /**
     * Builds the opening balance + chronological Debit/Credit transaction
     * rows + running balance + totals for one supplier, reusing existing
     * Purchase/PurchasePayment data only (no new tables).
     *
     * Accounting rule: Purchase = Debit, Purchase Payment = Credit,
     * Opening Balance = Debit when positive (amount owed to supplier).
     * A negative running/closing balance means an advance/credit position.
     */
    private function buildSupplierLedger(Supplier $supplier, string $startDate, string $endDate): array
    {
        [, $isRestricted] = $this->resolveLocations();
        $locationId = auth()->user()->location_id;

        $locationFilter = function ($q) use ($isRestricted, $locationId) {
            if ($isRestricted) {
                $q->whereHas('items.allocations', function ($aq) use ($locationId) {
                    $aq->where('location_id', $locationId);
                });
            }
        };

        // Opening Balance = outstanding carried forward from before the start date.
        $openingPurchaseTotal = (float) Purchase::where('supplier_id', $supplier->id)
            ->whereDate('created_at', '<', $startDate)
            ->tap($locationFilter)
            ->sum('total_amount');

        $openingPaidTotal = (float) PurchasePayment::whereDate('created_at', '<', $startDate)
            ->whereHas('purchase', function ($q) use ($supplier, $locationFilter) {
                $q->where('supplier_id', $supplier->id);
                $locationFilter($q);
            })
            ->sum('amount');

        $opening = round($openingPurchaseTotal - $openingPaidTotal, 2);

        // In-range transactions.
        $purchasesInRange = Purchase::with(['items.product', 'items.variant.attributeValue'])
            ->where('supplier_id', $supplier->id)
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->tap($locationFilter)
            ->get();

        $paymentsInRange = PurchasePayment::with('purchase')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->whereHas('purchase', function ($q) use ($supplier, $locationFilter) {
                $q->where('supplier_id', $supplier->id);
                $locationFilter($q);
            })
            ->get();

        $rows = collect();

        foreach ($purchasesInRange as $purchase) {
            $rows->push([
                'date'       => $purchase->created_at,
                'voucher_no' => $purchase->invoice_no,
                'particular' => 'Purchase',
                'debit'      => (float) $purchase->total_amount,
                'credit'     => 0.0,
                'type'       => 'purchase',
                'purchase'   => $purchase,
            ]);
        }

        foreach ($paymentsInRange as $payment) {
            $rows->push([
                'date'       => $payment->created_at,
                'voucher_no' => 'Payment against ' . ($payment->purchase->invoice_no ?? '-'),
                'particular' => 'Purchase Payment',
                'debit'      => 0.0,
                'credit'     => (float) $payment->amount,
                'type'       => 'payment',
                'purchase'   => null,
            ]);
        }

        // Sort by Date, then Voucher No — chronological order.
        $sortedRows = $rows->sort(function ($a, $b) {
            $dateCompare = $a['date']->timestamp <=> $b['date']->timestamp;
            if ($dateCompare !== 0) {
                return $dateCompare;
            }
            return strcmp($a['voucher_no'], $b['voucher_no']);
        })->values();

        $running = $opening;
        $totalPurchase = 0.0;
        $totalPayment = 0.0;

        $finalRows = $sortedRows->map(function ($row) use (&$running, &$totalPurchase, &$totalPayment) {
            $running += $row['debit'] - $row['credit'];
            $totalPurchase += $row['debit'];
            $totalPayment += $row['credit'];
            $row['balance'] = round($running, 2);

            return $row;
        });

        return [
            'opening'        => $opening,
            'rows'           => $finalRows,
            'total_purchase' => round($totalPurchase, 2),
            'total_payment'  => round($totalPayment, 2),
            'closing'        => round($running, 2),
        ];
    }

    /**
     * Line-item detail for one Purchase (Products, Variants, Qty, Rate,
     * GST, Amount) — used by the supplier ledger's expandable Purchase rows.
     * Reuses the same item/variant/GST data already loaded for the purchase.
     */
    private function purchaseItemRows(Purchase $purchase): array
    {
        $gstRate = (float) \App\Models\Setting::getValue('purchase_gst_rate', 3);

        return $purchase->items->map(function ($item) use ($purchase, $gstRate) {
            $gstAmount = $purchase->is_gst ? round(((float) $item->total) * $gstRate / 100, 2) : 0.0;

            return [
                'product' => $item->product->name ?? '-',
                'variant' => $item->variant?->attributeValue?->value ?? '-',
                'quantity' => (int) $item->quantity,
                'rate'     => format_price($item->purchase_price),
                'gst'      => $purchase->is_gst ? format_price($gstAmount) : '-',
                'amount'   => format_price($item->total),
            ];
        })->values()->toArray();
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
