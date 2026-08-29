<?php

namespace App\Http\Controllers;

use App\Models\BranchBalanceTransfer;
use App\Models\BulkPurchasePayment;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\LocationBalance;
use App\Models\LocationBalanceTransaction;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Models\SupplierAdvancePayment;
use App\Models\SupplierBalance;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LedgerController extends Controller
{
    // -----------------------------------------------------------------
    // Supplier Ledger (company-wide, super-admin only)
    // -----------------------------------------------------------------

    public function supplierLedger()
    {
        $this->authorizeLedger('view supplier ledger');

        [$locations, $isRestricted] = $this->resolveLocations();
        $user = auth()->user();
        $canManageAdvance = $user->hasRole('super-admin') || !$user->location_id || (int) $user->location_id === 1;

        return view('ledgers.supplier', compact('locations', 'isRestricted', 'canManageAdvance'));
    }

    public function supplierLedgerData(Request $request)
    {
        $this->authorizeLedger('view supplier ledger');

        $asOnDate = $this->resolveAsOnDate($request);

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        $canManageAdvance = $user->hasRole('super-admin') || !$user->location_id || (int) $user->location_id === 1;

        $purchasesQuery = Purchase::with('supplier');

        if ($request->filled('start_date')) {
            $purchasesQuery->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $purchasesQuery->whereDate('created_at', '<=', $request->end_date);
        } else {
            $purchasesQuery->whereDate('created_at', '<=', $asOnDate);
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

        $mappedRows = $sortedRows->map(function ($row, $index) use ($canManageAdvance) {
            $suppObj = Supplier::find($row['supplier_id']);
            $advBal = ($suppObj && $canManageAdvance) ? (float) $suppObj->advance_balance : 0.0;

            return [
                'index'               => $index + 1,
                'supplier_id'         => $row['supplier_id'],
                'supplier'            => e($row['supplier_name']),
                'total_amount'        => format_price($row['total_amount']),
                'paid_amount'         => format_price($row['paid_amount']),
                'due_amount'          => format_price($row['due_amount']),
                'advance_balance'     => format_price($advBal),
                'raw_advance_balance' => $advBal,
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
            'status'             => 'success',
            'data'               => $mappedRows,
            'branch_summary'     => $branchSummary,
            'can_manage_advance' => $canManageAdvance,
        ]);
    }

    public function supplierLedgerDetail(Request $request)
    {
        $this->authorizeLedger('view supplier ledger');

        $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'as_on_date'  => ['required', 'date_format:Y-m-d'],
        ]);

        $supplier = Supplier::findOrFail($request->supplier_id);
        $asOnDate = \Carbon\Carbon::parse($request->as_on_date);

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        $canManageAdvance = $user->hasRole('super-admin') || !$user->location_id || (int) $user->location_id === 1;

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

        $advancePayments = $canManageAdvance
            ? SupplierAdvancePayment::where('supplier_id', $supplier->id)->orderByDesc('created_at')->get()
            : collect();

        return view('ledgers.supplier-detail', compact(
            'supplier',
            'asOnDate',
            'purchases',
            'totalPurchase',
            'totalPayment',
            'totalOutstanding',
            'advancePayments',
            'canManageAdvance'
        ));
    }

    public function supplierAdvanceHistory(Request $request)
    {
        $this->authorizeLedger('view supplier ledger');

        $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
        ]);

        $supplier = Supplier::findOrFail($request->supplier_id);
        $user = auth()->user();
        $canManageAdvance = $user->hasRole('super-admin') || !$user->location_id || (int) $user->location_id === 1;

        if (!$canManageAdvance) {
            abort(403, 'Unauthorized access to supplier advance history.');
        }

        $advancePayments = SupplierAdvancePayment::where('supplier_id', $supplier->id)
            ->orderByDesc('created_at')
            ->get();

        $totalAdvancePaid = $advancePayments->sum('total_amount');
        $totalAdvanceUsed = $advancePayments->sum('used_amount');
        $totalRemainingAdvance = $advancePayments->sum('remaining_amount');

        return view('ledgers.supplier-advance-history', compact(
            'supplier',
            'advancePayments',
            'totalAdvancePaid',
            'totalAdvanceUsed',
            'totalRemainingAdvance'
        ));
    }

    public function paySupplierAdvance(Request $request)
    {
        $user = auth()->user();
        $isMainBranchUser = $user->hasRole('super-admin') || !$user->location_id || (int) $user->location_id === 1;

        if (!$isMainBranchUser) {
            return response()->json(['status' => 'error', 'message' => 'Advance payments are only accessible for Main Branch users or Super Admin.'], 403);
        }

        $request->validate([
            'supplier_id'    => ['required', 'integer', 'exists:suppliers,id'],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'in:cash,online'],
            'notes'          => ['nullable', 'string'],
        ]);

        $supplierId = (int) $request->supplier_id;
        $enteredAmount = round((float) $request->amount, 2);
        $paymentMethod = $request->payment_method;
        $supplier = Supplier::findOrFail($supplierId);

        \Illuminate\Support\Facades\DB::transaction(function () use ($supplier, $supplierId, $enteredAmount, $paymentMethod, $request, $user) {
            $purchases = Purchase::where('supplier_id', $supplierId)
                ->whereIn('payment_status', [Purchase::PAYMENT_STATUS_PENDING, Purchase::PAYMENT_STATUS_PARTIAL])
                ->orderBy('created_at', 'asc')
                ->get();

            $bulkPayRecord = BulkPurchasePayment::create([
                'total_amount'   => $enteredAmount,
                'supplier_id'    => $supplierId,
                'payment_method' => $paymentMethod,
                'created_by'     => auth()->id(),
            ]);

            $remaining = $enteredAmount;
            $totalPaidAllocated = 0.0;

            foreach ($purchases as $purchase) {
                if ($remaining <= 0) break;

                $currentPaid = $purchase->payment_status == Purchase::PAYMENT_STATUS_PAID
                    ? (float) $purchase->total_amount
                    : ($purchase->payment_status == Purchase::PAYMENT_STATUS_PENDING ? 0.0 : (float) $purchase->paid_amount);

                $due = round((float) $purchase->total_amount - $currentPaid, 2);
                if ($due <= 0) continue;

                $payForThisBill = min($due, $remaining);
                $newPaidAmount = round($currentPaid + $payForThisBill, 2);
                $finalStatus = ($newPaidAmount >= (float) $purchase->total_amount)
                    ? Purchase::PAYMENT_STATUS_PAID
                    : Purchase::PAYMENT_STATUS_PARTIAL;

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

                $remaining = round($remaining - $payForThisBill, 2);
                $totalPaidAllocated = round($totalPaidAllocated + $payForThisBill, 2);
            }

            if ($remaining > 0) {
                SupplierAdvancePayment::create([
                    'supplier_id'              => $supplierId,
                    'bulk_purchase_payment_id' => $bulkPayRecord->id,
                    'total_amount'             => $remaining,
                    'used_amount'              => 0.00,
                    'remaining_amount'         => $remaining,
                    'payment_method'           => $paymentMethod,
                    'notes'                    => $request->notes ?? 'Supplier Advance Payment',
                    'created_by'               => auth()->id(),
                ]);

                $suppBal = SupplierBalance::firstOrCreate(['supplier_id' => $supplierId]);
                $suppBal->balance = round((float) $suppBal->balance + $remaining, 2);
                if ($paymentMethod === 'cash') {
                    $suppBal->cash_balance = round((float) $suppBal->cash_balance + $remaining, 2);
                } else {
                    $suppBal->bank_balance = round((float) $suppBal->bank_balance + $remaining, 2);
                }
                $suppBal->save();
            }

            $defaultLoc = Location::where('is_default', true)->first() ?? Location::first();
            $locId = $user->location_id ?: ($defaultLoc ? $defaultLoc->id : 1);
            $balanceType = $paymentMethod === 'cash' ? LocationBalanceTransaction::BALANCE_TYPE_CASH : LocationBalanceTransaction::BALANCE_TYPE_BANK;
            $balCol = $paymentMethod === 'cash' ? 'cash_balance' : 'bank_balance';

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
                    'notes'         => 'Purchase Payment (' . format_price($totalPaidAllocated) . ') to ' . $supplier->name,
                    'created_by'    => auth()->id(),
                ]);
            }

            // 2. Transaction for Advance Payment (if any remaining as advance)
            if ($remaining > 0) {
                $locBal = LocationBalance::firstOrCreate(['location_id' => $locId]);
                $newBal = round((float) $locBal->{$balCol} - $remaining, 2);
                $locBal->update([$balCol => $newBal]);

                LocationBalanceTransaction::create([
                    'location_id'   => $locId,
                    'balance_type'  => $balanceType,
                    'type'          => LocationBalanceTransaction::TYPE_DEBIT,
                    'amount'        => $remaining,
                    'balance_after' => $newBal,
                    'notes'         => 'Advance Payment (' . format_price($remaining) . ') to ' . $supplier->name,
                    'created_by'    => auth()->id(),
                ]);
            }
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Advance payment of ' . format_price($enteredAmount) . ' processed successfully for ' . $supplier->name . '.',
        ]);
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

        $canManageAdvance = $user->hasRole('super-admin') || !$user->location_id || (int) $user->location_id === 1;

        $rows = collect();
        foreach ($grouped as $items) {
            $first = $items->first();
            $totalAmount = (float) $items->sum('total_amount');
            $paidAmount  = (float) $items->sum('paid_amount');
            $dueAmount   = max(0.0, $totalAmount - $paidAmount);

            $suppObj = Supplier::find($first->supplier_id);
            $advBal = ($suppObj && $canManageAdvance) ? (float) $suppObj->advance_balance : 0.0;

            $rows->push([
                'supplier_name' => $first->supplier->name ?? '-',
                'total_amount'  => $totalAmount,
                'paid_amount'   => $paidAmount,
                'due_amount'    => $dueAmount,
                'balance'       => $advBal,
            ]);
        }

        $rows = $rows->sort(function ($a, $b) {
            if ($a['due_amount'] !== $b['due_amount']) {
                return $b['due_amount'] <=> $a['due_amount'];
            }
            return strcmp($a['supplier_name'], $b['supplier_name']);
        })->values();

        $totalAmount  = $rows->sum('total_amount');
        $totalPaid    = $rows->sum('paid_amount');
        $totalDue     = $rows->sum('due_amount');
        $totalBalance = $rows->sum('balance');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Supplier Ledger');

        $titleStyle = [
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '111827']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F2F4F7'],
            ],
        ];

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '1D2939'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAECF0']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D5DD'],
                ],
            ],
        ];

        $headers = ['#', 'Supplier', 'Total Amount', 'Paid Amount', 'Due Amount'];
        $cols = ['A', 'B', 'C', 'D', 'E'];
        if ($canManageAdvance) {
            $headers[] = 'Balance';
            $cols[] = 'F';
        }

        $lastCol = end($cols);
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', 'Supplier Ledger Data (' . $locationName . ')');
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(30);

        foreach ($headers as $cIdx => $hText) {
            $sheet->setCellValue($cols[$cIdx] . '2', $hText);
        }
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray($headerStyle);
        $sheet->getRowDimension(2)->setRowHeight(26);

        $r = 3;
        foreach ($rows as $idx => $row) {
            $sheet->setCellValue('A' . $r, $idx + 1);
            $sheet->setCellValue('B' . $r, $row['supplier_name']);
            $sheet->setCellValue('C' . $r, '₹' . number_format($row['total_amount'], 2));
            $sheet->setCellValue('D' . $r, '₹' . number_format($row['paid_amount'], 2));
            $sheet->setCellValue('E' . $r, '₹' . number_format($row['due_amount'], 2));
            if ($canManageAdvance) {
                $sheet->setCellValue('F' . $r, '₹' . number_format($row['balance'], 2));
            }
            $sheet->getRowDimension($r)->setRowHeight(20);
            $r++;
        }

        $sheet->setCellValue('A' . $r, 'Total');
        $sheet->setCellValue('C' . $r, '₹' . number_format($totalAmount, 2));
        $sheet->setCellValue('D' . $r, '₹' . number_format($totalPaid, 2));
        $sheet->setCellValue('E' . $r, '₹' . number_format($totalDue, 2));
        if ($canManageAdvance) {
            $sheet->setCellValue('F' . $r, '₹' . number_format($totalBalance, 2));
        }
        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFont()->setBold(true);
        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EAECF0');
        $sheet->getStyle("A2:{$lastCol}{$r}")->applyFromArray($borderStyle);
        $sheet->getStyle("C2:{$lastCol}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        foreach ($cols as $colLetter) {
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        ActivityLogger::log('Ledgers', 'export', null, null, null, 'Supplier ledger exported to Excel');

        $writer = new Xlsx($spreadsheet);
        $filename = 'supplier_ledger_' . $asOnDate . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
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

            $filteredNodes = $locChain;
            if ($request->filled('start_date')) {
                $filteredNodes = $filteredNodes->where('date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $filteredNodes = $filteredNodes->where('date', '<=', $request->end_date);
            }
            $filteredNodes = $filteredNodes->values();

            if ($filteredNodes->isNotEmpty()) {
                $opening = $filteredNodes->first()['opening'];
                $sale    = $filteredNodes->sum('in');
                $expense = $filteredNodes->sum('out');
                $closing = $filteredNodes->last()['closing'];
            } else {
                $checkDate = $request->start_date ?: $today;
                $opening = $this->openingBalance($loc->id, LocationBalanceTransaction::BALANCE_TYPE_CASH, $checkDate);
                $sale    = 0.0;
                $expense = 0.0;
                $closing = $opening;
            }

            $branchSummary[$loc->id] = [
                'opening' => format_price($opening),
                'sale'    => format_price($sale),
                'expense' => format_price($expense),
                'closing' => format_price($closing),
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

        $locationName = is_int($actionLocationId) ? (Location::find($actionLocationId)->name ?? 'All Locations') : 'All Locations';

        $totalIn  = $rows->sum('in');
        $totalOut = $rows->sum('out');

        if ($rows->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected filters. Nothing to export.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cash Ledger');

        $titleStyle = [
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '111827']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F2F4F7'],
            ],
        ];

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '1D2939'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAECF0']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D5DD'],
                ],
            ],
        ];

        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'Cash Ledger Data (' . $locationName . ')');
        $sheet->getStyle('A1:F1')->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $headers = ['#', 'Date', 'Opening Balance', 'Receipt (Cash In)', 'Payment (Cash Out)', 'Closing Balance'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F'];

        foreach ($headers as $cIdx => $hText) {
            $sheet->setCellValue($cols[$cIdx] . '2', $hText);
        }
        $sheet->getStyle('A2:F2')->applyFromArray($headerStyle);
        $sheet->getRowDimension(2)->setRowHeight(26);

        $r = 3;
        foreach ($rows as $idx => $row) {
            $sheet->setCellValue('A' . $r, $idx + 1);
            $sheet->setCellValue('B' . $r, format_date($row['date']));
            $sheet->setCellValue('C' . $r, '₹' . number_format($row['opening'], 2));
            $sheet->setCellValue('D' . $r, '₹' . number_format($row['in'], 2));
            $sheet->setCellValue('E' . $r, '₹' . number_format($row['out'], 2));
            $sheet->setCellValue('F' . $r, '₹' . number_format($row['closing'], 2));
            $sheet->getRowDimension($r)->setRowHeight(20);
            $r++;
        }

        $sheet->setCellValue('A' . $r, 'Total');
        $sheet->setCellValue('D' . $r, '₹' . number_format($totalIn, 2));
        $sheet->setCellValue('E' . $r, '₹' . number_format($totalOut, 2));
        $sheet->getStyle("A{$r}:F{$r}")->getFont()->setBold(true);
        $sheet->getStyle("A{$r}:F{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EAECF0');
        $sheet->getStyle("A2:F{$r}")->applyFromArray($borderStyle);
        $sheet->getStyle("A2:B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C2:F{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        foreach ($cols as $colLetter) {
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        ActivityLogger::log('Ledgers', 'export', null, null, null, 'Cash ledger exported to Excel');

        $writer = new Xlsx($spreadsheet);
        $filename = 'cash_ledger_' . date('Y_m_d_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
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

            $filteredNodes = $locChain;
            if ($request->filled('start_date')) {
                $filteredNodes = $filteredNodes->where('date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $filteredNodes = $filteredNodes->where('date', '<=', $request->end_date);
            }
            $filteredNodes = $filteredNodes->values();

            if ($filteredNodes->isNotEmpty()) {
                $opening = $filteredNodes->first()['opening'];
                $receipt = $filteredNodes->sum('in');
                $payment = $filteredNodes->sum('out');
                $closing = $filteredNodes->last()['closing'];
            } else {
                $checkDate = $request->start_date ?: $today;
                $opening = $this->openingBalance($loc->id, LocationBalanceTransaction::BALANCE_TYPE_BANK, $checkDate);
                $receipt = 0.0;
                $payment = 0.0;
                $closing = $opening;
            }

            $branchSummary[$loc->id] = [
                'opening' => format_price($opening),
                'receipt' => format_price($receipt),
                'payment' => format_price($payment),
                'closing' => format_price($closing),
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

        $locationName = is_int($actionLocationId) ? (Location::find($actionLocationId)->name ?? 'All Locations') : 'All Locations';

        $totalIn  = $rows->sum('in');
        $totalOut = $rows->sum('out');

        if ($rows->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected filters. Nothing to export.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bank Ledger');

        $titleStyle = [
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '111827']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F2F4F7'],
            ],
        ];

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '1D2939'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAECF0']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D5DD'],
                ],
            ],
        ];

        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'Bank Ledger Data (' . $locationName . ')');
        $sheet->getStyle('A1:F1')->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $headers = ['#', 'Date', 'Opening Balance', 'Receipt (Bank In)', 'Payment (Bank Out)', 'Closing Balance'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F'];

        foreach ($headers as $cIdx => $hText) {
            $sheet->setCellValue($cols[$cIdx] . '2', $hText);
        }
        $sheet->getStyle('A2:F2')->applyFromArray($headerStyle);
        $sheet->getRowDimension(2)->setRowHeight(26);

        $r = 3;
        foreach ($rows as $idx => $row) {
            $sheet->setCellValue('A' . $r, $idx + 1);
            $sheet->setCellValue('B' . $r, format_date($row['date']));
            $sheet->setCellValue('C' . $r, '₹' . number_format($row['opening'], 2));
            $sheet->setCellValue('D' . $r, '₹' . number_format($row['in'], 2));
            $sheet->setCellValue('E' . $r, '₹' . number_format($row['out'], 2));
            $sheet->setCellValue('F' . $r, '₹' . number_format($row['closing'], 2));
            $sheet->getRowDimension($r)->setRowHeight(20);
            $r++;
        }

        $sheet->setCellValue('A' . $r, 'Total');
        $sheet->setCellValue('D' . $r, '₹' . number_format($totalIn, 2));
        $sheet->setCellValue('E' . $r, '₹' . number_format($totalOut, 2));
        $sheet->getStyle("A{$r}:F{$r}")->getFont()->setBold(true);
        $sheet->getStyle("A{$r}:F{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EAECF0');
        $sheet->getStyle("A2:F{$r}")->applyFromArray($borderStyle);
        $sheet->getStyle("A2:B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C2:F{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        foreach ($cols as $colLetter) {
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        ActivityLogger::log('Ledgers', 'export', null, null, null, 'Bank ledger exported to Excel');

        $writer = new Xlsx($spreadsheet);
        $filename = 'bank_ledger_' . date('Y_m_d_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
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

    public function branchLedgerPendingCount()
    {
        $user = auth()->user();

        $count = BranchBalanceTransfer::where('status', BranchBalanceTransfer::STATUS_PENDING)
            ->when($user->location_id && !$user->hasRole('super-admin'), function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->where('from_location_id', $user->location_id)
                        ->orWhere('to_location_id', $user->location_id);
                });
            })
            ->count();

        return response()->json(['status' => 'success', 'count' => $count]);
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

                $paid = (float) ($bill->paid_amount ?? 0);
                $dueAmount = max(0.0, $amount - $paid);

                $bill->computed_amount = $dueAmount;
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

        // One row per bill (not per in/out side) — a transfer between two of the
        // filtered locations only needs listing once, naming both branches.
        // Only unpaid/partial bills belong here; once marked Paid it drops off, same as
        // the Pending Payments Between Branches summary below.
        $stockTransfersQuery = PurchaseBill::with(['items.product', 'items.variant', 'fromLocation', 'toLocation'])
            ->where(function ($q) use ($locationIds) {
                $q->whereIn('from_location_id', $locationIds)
                    ->orWhereIn('to_location_id', $locationIds);
            })
            ->where('status', PurchaseBill::STATUS_ACCEPTED)
            ->whereIn('payment_status', [PurchaseBill::PAYMENT_STATUS_PENDING, PurchaseBill::PAYMENT_STATUS_PARTIAL]);

        if ($request->filled('start_date')) {
            $stockTransfersQuery->whereDate('accepted_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $stockTransfersQuery->whereDate('accepted_at', '<=', $request->end_date);
        }
        $stockTransfers = $stockTransfersQuery->get();

        $stockRows = $stockTransfers->map(function ($transfer) {
            $billTotal = 0.0;
            foreach ($transfer->items as $item) {
                $billTotal += $this->purchasePriceForLedgerItem($item) * $item->quantity;
            }
            $paid = (float) ($transfer->paid_amount ?? 0);
            $amount = max(0.0, $billTotal - $paid);
            $transfer->computed_due_amount = $amount;

            $date = $transfer->accepted_at->format('Y-m-d');
            $rawDate = $transfer->accepted_at->format('Y-m-d H:i:s');

            $branchLabel = e($transfer->fromLocation->name ?? '-')
                . ' <i class="ti ti-arrow-right mx-1 text-muted"></i> '
                . e($transfer->toLocation->name ?? '-');

            $transferNo = str_starts_with($transfer->transfer_no, 'ST-') ? $transfer->transfer_no : 'ST-' . $transfer->transfer_no;

            $statusBadge = match ((int) $transfer->status) {
                PurchaseBill::STATUS_ACCEPTED => '<span class="badge bg-label-success">Accepted</span>',
                PurchaseBill::STATUS_REJECTED => '<span class="badge bg-label-danger">Rejected</span>',
                default                       => '<span class="badge bg-label-warning">Pending</span>',
            };

            return [
                'raw_date'    => $rawDate,
                'date'        => format_date($date),
                'date_sort'   => $date,
                'date_group'  => format_date($date),
                'transfer_no' => '<code>' . e($transferNo) . '</code>',
                'status'      => $statusBadge,
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
        });

        // Balance transfers from BranchBalanceTransfer table
        $btTransfersQuery = BranchBalanceTransfer::with(['fromLocation', 'toLocation'])
            ->where(function ($q) use ($locationIds) {
                $q->whereIn('from_location_id', $locationIds)
                    ->orWhereIn('to_location_id', $locationIds);
            });

        if ($request->filled('start_date')) {
            $btTransfersQuery->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $btTransfersQuery->whereDate('created_at', '<=', $request->end_date);
        }
        $btTransfers = $btTransfersQuery->get();

        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('super-admin');
        $hasAcceptPermission = $user->can('accept balance transfer');
        $hasRejectPermission = $user->can('reject balance transfer') || $hasAcceptPermission;
        $hasEditPermission   = $user->can('edit balance transfer');
        $hasDeletePermission = $user->can('delete balance transfer');

        $btRows = $btTransfers->map(function ($bt) use ($user, $isSuperAdmin, $hasAcceptPermission, $hasRejectPermission, $hasEditPermission, $hasDeletePermission) {
            $date = $bt->created_at->format('Y-m-d');
            $rawDate = $bt->created_at->format('Y-m-d H:i:s');

            $branchLabel = e($bt->fromLocation->name ?? '-')
                . ' <i class="ti ti-arrow-right mx-1 text-muted"></i> '
                . e($bt->toLocation->name ?? '-');

            $statusBadge = match ((int) $bt->status) {
                BranchBalanceTransfer::STATUS_ACCEPTED => '<span class="badge bg-label-success">Accepted</span>',
                BranchBalanceTransfer::STATUS_REJECTED => '<span class="badge bg-label-danger">Rejected</span>',
                default                                => '<span class="badge bg-label-warning">Pending</span>',
            };

            $isUserRestricted       = (bool) $user->location_id;
            $isReceivingBranchAdmin = $isUserRestricted && (int) $user->location_id === (int) $bt->to_location_id;
            $isSenderBranchAdmin    = $isUserRestricted && (int) $user->location_id === (int) $bt->from_location_id;

            $canAccept = $isSuperAdmin || ($isUserRestricted ? $isReceivingBranchAdmin : $hasAcceptPermission);
            $canReject = $isSuperAdmin || ($isUserRestricted ? $isReceivingBranchAdmin : ($hasRejectPermission || $hasAcceptPermission));
            $canEdit   = $isSuperAdmin || ($isUserRestricted ? $isSenderBranchAdmin : $hasEditPermission);
            $canDelete = $isSuperAdmin || ($isUserRestricted ? $isSenderBranchAdmin : $hasDeletePermission);

            $items = '';
            if ((int) $bt->status === BranchBalanceTransfer::STATUS_PENDING) {
                if ($canAccept) {
                    $items .= '<a href="javascript:void(0)" class="dropdown-item text-success accept-bt-btn" data-id="' . $bt->id . '"><i class="ti ti-check me-2"></i>Accept</a>';
                }
                if ($canReject) {
                    $items .= '<a href="javascript:void(0)" class="dropdown-item text-warning reject-bt-btn" data-id="' . $bt->id . '"><i class="ti ti-x me-2"></i>Reject</a>';
                }
            }

            if ($canEdit) {
                $items .= '<a href="javascript:void(0)" class="dropdown-item edit-bt-btn" data-id="' . $bt->id . '" data-amount="' . $bt->amount . '" data-notes="' . e($bt->notes) . '"><i class="ti ti-pencil me-2"></i>Edit</a>';
            }
            if ($canDelete) {
                $items .= '<button class="dropdown-item text-danger delete-bt-btn" data-id="' . $bt->id . '"><i class="ti ti-trash me-2"></i>Delete</button>';
            }

            if (empty($items)) {
                $actionBtns = '-';
            } else {
                $actionBtns = '<div class="dropdown table-action-dropdown">
                    <button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                        <span>Actions</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">' . $items . '</div></div>';
            }

            return [
                'raw_date'    => $rawDate,
                'date'        => format_date($date),
                'date_sort'   => $date,
                'date_group'  => format_date($date),
                'transfer_no' => '<code>' . e($bt->transfer_no) . '</code>',
                'status'      => $statusBadge,
                'branch'      => $branchLabel,
                'amount'      => '<span class="fw-semibold text-primary">' . format_price($bt->amount) . '</span>',
                'actions'     => $actionBtns,
            ];
        });

        $allMerged = $stockRows->concat($btRows)->sortByDesc('raw_date')->values();

        $rows = $allMerged->map(function ($r, $idx) {
            $r['index'] = $idx + 1;
            return $r;
        });

        $branchDues = $stockTransfers
            ->groupBy(fn ($bill) => $bill->from_location_id . ':' . $bill->to_location_id)
            ->map(function ($bills) {
                $first = $bills->first();
                $amountRaw = (float) $bills->sum('computed_due_amount');

                return [
                    'from_location_id'  => $first->from_location_id,
                    'to_location_id'    => $first->to_location_id,
                    'payable_branch'    => e($first->toLocation->name ?? '-'),
                    'receivable_branch' => e($first->fromLocation->name ?? '-'),
                    'amount_raw'        => $amountRaw,
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

        [$locationIds, $actionLocationId] = $this->resolveLocationIds($request);
        [$startDate, $endDate] = $this->resolveDateRange($request);

        abort_if(empty($locationIds), 404);

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

        // Stock Transfers (PurchaseBills)
        $stockTransfersQuery = PurchaseBill::with(['items.product', 'items.variant', 'fromLocation', 'toLocation'])
            ->where(function ($q) use ($locationIds) {
                $q->whereIn('from_location_id', $locationIds)
                    ->orWhereIn('to_location_id', $locationIds);
            })
            ->where('status', PurchaseBill::STATUS_ACCEPTED)
            ->whereIn('payment_status', [PurchaseBill::PAYMENT_STATUS_PENDING, PurchaseBill::PAYMENT_STATUS_PARTIAL]);

        if ($request->filled('start_date')) {
            $stockTransfersQuery->whereDate('accepted_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $stockTransfersQuery->whereDate('accepted_at', '<=', $request->end_date);
        }
        $stockTransfers = $stockTransfersQuery->get();

        $stockRows = $stockTransfers->map(function ($transfer) use ($getTransferAmount) {
            $amount = $getTransferAmount([$transfer]);
            $date = $transfer->accepted_at ? $transfer->accepted_at->format('d-m-Y h:i A') : '-';
            $rawDate = $transfer->accepted_at ? $transfer->accepted_at->format('Y-m-d H:i:s') : '';
            $transferNo = str_starts_with($transfer->transfer_no, 'ST-') ? $transfer->transfer_no : 'ST-' . $transfer->transfer_no;

            $statusStr = match ((int) $transfer->status) {
                PurchaseBill::STATUS_ACCEPTED => 'Accepted',
                PurchaseBill::STATUS_REJECTED => 'Rejected',
                default                       => 'Pending',
            };

            return [
                'raw_date'    => $rawDate,
                'date'        => $date,
                'transfer_no' => $transferNo,
                'from_branch' => $transfer->fromLocation->name ?? '-',
                'to_branch'   => $transfer->toLocation->name ?? '-',
                'status'      => $statusStr,
                'amount'      => $amount,
            ];
        });

        // Balance Transfers (BranchBalanceTransfer)
        $btTransfersQuery = BranchBalanceTransfer::with(['fromLocation', 'toLocation'])
            ->where(function ($q) use ($locationIds) {
                $q->whereIn('from_location_id', $locationIds)
                    ->orWhereIn('to_location_id', $locationIds);
            });

        if ($request->filled('start_date')) {
            $btTransfersQuery->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $btTransfersQuery->whereDate('created_at', '<=', $request->end_date);
        }
        $btTransfers = $btTransfersQuery->get();

        $btRows = $btTransfers->map(function ($bt) {
            $date = $bt->created_at ? $bt->created_at->format('d-m-Y h:i A') : '-';
            $rawDate = $bt->created_at ? $bt->created_at->format('Y-m-d H:i:s') : '';

            $statusStr = match ((int) $bt->status) {
                BranchBalanceTransfer::STATUS_ACCEPTED => 'Accepted',
                BranchBalanceTransfer::STATUS_REJECTED => 'Rejected',
                default                                => 'Pending',
            };

            return [
                'raw_date'    => $rawDate,
                'date'        => $date,
                'transfer_no' => $bt->transfer_no,
                'from_branch' => $bt->fromLocation->name ?? '-',
                'to_branch'   => $bt->toLocation->name ?? '-',
                'status'      => $statusStr,
                'amount'      => (float) $bt->amount,
            ];
        });

        $allMerged = $stockRows->concat($btRows)->sortByDesc('raw_date')->values();

        if ($allMerged->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected filters. Nothing to export.');
        }

        $locationName = is_int($actionLocationId) ? (Location::find($actionLocationId)->name ?? 'All Locations') : 'All Locations';

        $spreadsheet = new Spreadsheet();

        $titleStyle = [
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '111827']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F2F4F7'],
            ],
        ];

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '1D2939'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAECF0']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D5DD'],
                ],
            ],
        ];

        // Sheet 1: Branch Ledger / Transfers
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Branch Ledger');

        $sheet1->mergeCells('A1:G1');
        $sheet1->setCellValue('A1', 'Branch Ledger Data (' . $locationName . ')');
        $sheet1->getStyle('A1:G1')->applyFromArray($titleStyle);
        $sheet1->getRowDimension(1)->setRowHeight(30);

        $headers1 = ['#', 'Date', 'Bill / Transfer No', 'From Branch', 'To Branch', 'Status', 'Amount'];
        $cols1 = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

        foreach ($headers1 as $cIdx => $hText) {
            $sheet1->setCellValue($cols1[$cIdx] . '2', $hText);
        }
        $sheet1->getStyle('A2:G2')->applyFromArray($headerStyle);
        $sheet1->getRowDimension(2)->setRowHeight(26);

        $r = 3;
        $totalAmt = 0.0;
        foreach ($allMerged as $idx => $row) {
            $amt = (float) $row['amount'];
            $totalAmt += $amt;

            $sheet1->setCellValue('A' . $r, $idx + 1);
            $sheet1->setCellValue('B' . $r, $row['date']);
            $sheet1->setCellValue('C' . $r, $row['transfer_no']);
            $sheet1->setCellValue('D' . $r, $row['from_branch']);
            $sheet1->setCellValue('E' . $r, $row['to_branch']);
            $sheet1->setCellValue('F' . $r, $row['status']);
            $sheet1->setCellValue('G' . $r, '₹' . number_format($amt, 2));
            $sheet1->getRowDimension($r)->setRowHeight(20);
            $r++;
        }

        $sheet1->setCellValue('A' . $r, 'Total');
        $sheet1->setCellValue('G' . $r, '₹' . number_format($totalAmt, 2));
        $sheet1->getStyle("A{$r}:G{$r}")->getFont()->setBold(true);
        $sheet1->getStyle("A{$r}:G{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EAECF0');
        $sheet1->getStyle("A2:G{$r}")->applyFromArray($borderStyle);
        $sheet1->getStyle("A2:C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle("F2:F{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle("G2:G{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        foreach ($cols1 as $colLetter) {
            $sheet1->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Sheet 2: Pending Branch Dues
        $pendingBillsQuery = PurchaseBill::with(['items.product', 'items.variant', 'fromLocation', 'toLocation'])
            ->where(function ($q) use ($locationIds) {
                $q->whereIn('from_location_id', $locationIds)
                    ->orWhereIn('to_location_id', $locationIds);
            })
            ->where('status', PurchaseBill::STATUS_ACCEPTED)
            ->whereIn('payment_status', [PurchaseBill::PAYMENT_STATUS_PENDING, PurchaseBill::PAYMENT_STATUS_PARTIAL]);

        if ($request->filled('start_date')) {
            $pendingBillsQuery->whereDate('accepted_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $pendingBillsQuery->whereDate('accepted_at', '<=', $request->end_date);
        }
        $pendingBills = $pendingBillsQuery->get();

        $branchDues = $pendingBills
            ->groupBy(fn ($bill) => $bill->from_location_id . ':' . $bill->to_location_id)
            ->map(function ($bills) use ($getTransferAmount) {
                $first = $bills->first();
                return [
                    'payable_branch'    => $first->toLocation->name ?? '-',
                    'receivable_branch' => $first->fromLocation->name ?? '-',
                    'amount'            => $getTransferAmount($bills),
                    'bills_count'       => $bills->count(),
                ];
            })
            ->filter(fn ($due) => $due['amount'] > 0)
            ->sortByDesc('amount')
            ->values();

        if ($branchDues->isNotEmpty()) {
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Pending Branch Dues');

            $sheet2->mergeCells('A1:E1');
            $sheet2->setCellValue('A1', 'Pending Payments Between Branches (' . $locationName . ')');
            $sheet2->getStyle('A1:E1')->applyFromArray($titleStyle);
            $sheet2->getRowDimension(1)->setRowHeight(30);

            $headers2 = ['#', 'Branch That Owes Payment', 'Branch To Be Paid', 'Pending Amount', 'Bills Count'];
            $cols2 = ['A', 'B', 'C', 'D', 'E'];

            foreach ($headers2 as $cIdx => $hText) {
                $sheet2->setCellValue($cols2[$cIdx] . '2', $hText);
            }
            $sheet2->getStyle('A2:E2')->applyFromArray($headerStyle);
            $sheet2->getRowDimension(2)->setRowHeight(26);

            $r2 = 3;
            $sumDues = 0.0;
            foreach ($branchDues as $idx => $due) {
                $amt = (float) $due['amount'];
                $sumDues += $amt;

                $sheet2->setCellValue('A' . $r2, $idx + 1);
                $sheet2->setCellValue('B' . $r2, $due['payable_branch']);
                $sheet2->setCellValue('C' . $r2, $due['receivable_branch']);
                $sheet2->setCellValue('D' . $r2, '₹' . number_format($amt, 2));
                $sheet2->setCellValue('E' . $r2, $due['bills_count']);
                $sheet2->getRowDimension($r2)->setRowHeight(20);
                $r2++;
            }

            $sheet2->setCellValue('A' . $r2, 'Total Pending Dues');
            $sheet2->setCellValue('D' . $r2, '₹' . number_format($sumDues, 2));
            $sheet2->getStyle("A{$r2}:E{$r2}")->getFont()->setBold(true);
            $sheet2->getStyle("A{$r2}:E{$r2}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EAECF0');
            $sheet2->getStyle("A2:E{$r2}")->applyFromArray($borderStyle);
            $sheet2->getStyle("A2:A{$r2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle("D2:E{$r2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            foreach ($cols2 as $colLetter) {
                $sheet2->getColumnDimension($colLetter)->setAutoSize(true);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        ActivityLogger::log('Ledgers', 'export', null, null, null, 'Branch ledger exported to Excel');

        $writer = new Xlsx($spreadsheet);
        $filename = 'branch_ledger_' . date('Y_m_d_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
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
        return (float) (($item->purchase_price > 0) ? $item->purchase_price : ($item->variant->purchase_price ?? $product?->purchase_price ?? 0));
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

        $ordersQuery = \App\Models\Order::with('customer');

        if ($request->filled('start_date')) {
            $ordersQuery->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $ordersQuery->whereDate('created_at', '<=', $request->end_date);
        }

        if ($isRestricted) {
            $ordersQuery->where('location_id', $user->location_id);
        } else {
            if ($request->filled('location_id')) {
                $ordersQuery->where('location_id', (int) $request->location_id);
            }
        }

        $orders = $ordersQuery->get();

        // Group by Customer ID only — cumulative total per customer
        $grouped = $orders->groupBy(fn ($order) => $order->customer_id ?? 0);

        $rows = collect();
        foreach ($grouped as $customerId => $items) {
            $first = $items->first();
            $totalAmount = 0.0;
            $paidAmount  = 0.0;

            foreach ($items as $order) {
                $totalAmount += (float) $order->final_amount;
                if ($order->payment_status == \App\Models\Order::PAYMENT_STATUS_PAID) {
                    $paidAmount += (float) $order->final_amount;
                } elseif ($order->payment_status == \App\Models\Order::PAYMENT_STATUS_PARTIAL) {
                    $paidAmt = (float) $order->paid_cash_amount + (float) $order->paid_online_amount;
                    if ($first->customer && $first->customer->is_credit_customer) {
                        $walletUsed = (float) \App\Models\CustomerBalanceTransaction::where('customer_id', $first->customer_id)
                            ->where('notes', 'LIKE', '%Sale #' . $order->order_number . '%')
                            ->sum('amount');
                        $paidAmt += $walletUsed;
                    }
                    $paidAmount += min((float) $order->final_amount, $paidAmt);
                } else {
                    $paidAmount += (float) $order->payments()->where('status', \App\Models\OrderPayment::STATUS_CAPTURED)->sum('amount');
                }
            }

            $dueAmount = max(0.0, $totalAmount - $paidAmount);

            // Hide 0 balance customers: Only show customers with outstanding payment due
            if ($dueAmount <= 0) {
                continue;
            }

            // Determine aggregate status
            if ($paidAmount <= 0) {
                $status = \App\Models\Order::PAYMENT_STATUS_PENDING;
            } else {
                $status = 3; // Partial
            }

            $lastOrderDate = $items->max('created_at') ?? now();

            $rows->push([
                'customer_id'    => $first->customer_id ?? 0,
                'customer_name'  => $first->customer->name ?? 'Walk-in',
                'date_raw'       => $lastOrderDate->format('Y-m-d'),
                'date_formatted' => format_date($lastOrderDate),
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

        // Branch-wise summary cards
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

        $ordersQuery = \App\Models\Order::with('customer');

        if ($request->filled('start_date')) {
            $ordersQuery->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $ordersQuery->whereDate('created_at', '<=', $request->end_date);
        }

        $locationName = 'All Locations';
        if ($isRestricted) {
            $ordersQuery->where('location_id', $user->location_id);
            $locationName = Location::find($user->location_id)->name ?? 'All Locations';
        } elseif ($request->filled('location_id')) {
            $ordersQuery->where('location_id', (int) $request->location_id);
            $locationName = Location::find((int) $request->location_id)->name ?? 'All Locations';
        }

        $orders = $ordersQuery->get();

        $grouped = $orders->groupBy(fn ($order) => $order->customer_id ?? 0);

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

            // Hide 0 balance customers
            if ($dueAmount <= 0) {
                continue;
            }

            $lastOrderDate = $items->max('created_at') ?? now();

            $rows->push([
                'customer_name' => $first->customer->name ?? 'Walk-in',
                'date'          => $lastOrderDate,
                'total_amount'  => $totalAmount,
                'paid_amount'   => $paidAmount,
                'due_amount'    => $dueAmount,
            ]);
        }

        $rows = $rows->sort(function ($a, $b) {
            if ($a['due_amount'] !== $b['due_amount']) {
                return $b['due_amount'] <=> $a['due_amount'];
            }
            return strcmp($a['customer_name'], $b['customer_name']);
        })->values();

        $totalAmount = $rows->sum('total_amount');
        $totalPaid   = $rows->sum('paid_amount');
        $totalDue    = $rows->sum('due_amount');

        if ($rows->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected filters. Nothing to export.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Customer Ledger');

        $titleStyle = [
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '111827']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F2F4F7'],
            ],
        ];

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '1D2939'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAECF0']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D5DD'],
                ],
            ],
        ];

        $headers = ['#', 'Customer Name', 'Last Order Date', 'Total Amount', 'Paid Amount', 'Due Amount'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F'];
        $lastCol = 'F';

        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', 'Customer Ledger Data (' . $locationName . ')');
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(30);

        foreach ($headers as $cIdx => $hText) {
            $sheet->setCellValue($cols[$cIdx] . '2', $hText);
        }
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray($headerStyle);
        $sheet->getRowDimension(2)->setRowHeight(26);

        $r = 3;
        foreach ($rows as $idx => $row) {
            $sheet->setCellValue('A' . $r, $idx + 1);
            $sheet->setCellValue('B' . $r, $row['customer_name']);
            $sheet->setCellValue('C' . $r, $row['date'] ? \Carbon\Carbon::parse($row['date'])->format('d-m-Y') : '-');
            $sheet->setCellValue('D' . $r, '₹' . number_format($row['total_amount'], 2));
            $sheet->setCellValue('E' . $r, '₹' . number_format($row['paid_amount'], 2));
            $sheet->setCellValue('F' . $r, '₹' . number_format($row['due_amount'], 2));
            $sheet->getRowDimension($r)->setRowHeight(20);
            $r++;
        }

        $sheet->setCellValue('A' . $r, 'Total');
        $sheet->setCellValue('D' . $r, '₹' . number_format($totalAmount, 2));
        $sheet->setCellValue('E' . $r, '₹' . number_format($totalPaid, 2));
        $sheet->setCellValue('F' . $r, '₹' . number_format($totalDue, 2));

        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFont()->setBold(true);
        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EAECF0');
        $sheet->getStyle("A2:{$lastCol}{$r}")->applyFromArray($borderStyle);
        $sheet->getStyle("A2:A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C2:C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D2:{$lastCol}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        foreach ($cols as $colLetter) {
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        ActivityLogger::log('Ledgers', 'export', null, null, null, 'Customer ledger exported to Excel');

        $writer = new Xlsx($spreadsheet);
        $filename = 'customer_ledger_' . date('Y_m_d_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function customerLedgerDetail(Request $request)
    {
        $this->authorizeLedger('view customer ledger');

        $request->validate([
            'customer_id' => ['required', 'integer'],
            'date'        => ['nullable', 'date_format:Y-m-d'],
        ]);

        $customerId = (int) $request->customer_id;
        $date = $request->filled('date') ? \Carbon\Carbon::parse($request->date) : null;

        if ($customerId === 0) {
            $customer = new \App\Models\Customer();
            $customer->name = 'Walk-in';
            $customer->phone = '-';
        } else {
            $customer = \App\Models\Customer::findOrFail($customerId);
        }

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $ordersQuery = \App\Models\Order::query();

        if ($customerId === 0) {
            $ordersQuery->whereNull('customer_id');
        } else {
            $ordersQuery->where('customer_id', $customerId);
        }

        if ($request->filled('date')) {
            $ordersQuery->whereDate('created_at', $request->date);
        } else {
            if ($request->filled('start_date')) {
                $ordersQuery->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $ordersQuery->whereDate('created_at', '<=', $request->end_date);
            }
        }

        if ($isRestricted) {
            $ordersQuery->where('location_id', $user->location_id);
        } else {
            if ($request->filled('location_id')) {
                $ordersQuery->where('location_id', (int) $request->location_id);
            }
        }

        $orders = $ordersQuery->orderByDesc('created_at')->get();

        $totalSales = 0.0;
        $totalPayment = 0.0;

        foreach ($orders as $order) {
            $totalSales += (float) $order->final_amount;
            if ($order->payment_status == \App\Models\Order::PAYMENT_STATUS_PAID) {
                $totalPayment += (float) $order->final_amount;
            } elseif ($order->payment_status == \App\Models\Order::PAYMENT_STATUS_PARTIAL) {
                $paidAmt = (float) $order->paid_cash_amount + (float) $order->paid_online_amount;
                if ($customer && $customer->is_credit_customer) {
                    $walletUsed = (float) \App\Models\CustomerBalanceTransaction::where('customer_id', $customer->id)
                        ->where('notes', 'LIKE', '%Sale #' . $order->order_number . '%')
                        ->sum('amount');
                    $paidAmt += $walletUsed;
                }
                $totalPayment += min((float) $order->final_amount, $paidAmt);
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
