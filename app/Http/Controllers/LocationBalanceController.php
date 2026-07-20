<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\LocationBalance;
use App\Models\LocationBalanceTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LocationBalanceController extends Controller
{
    public function show(Location $location)
    {
        $this->guardSuperAdmin();

        return view('locations.balance.show', compact('location'));
    }

    public function create(Location $location)
    {
        $this->guardSuperAdmin();

        return view('locations.balance.create', compact('location'));
    }

    public function data(Request $request, Location $location)
    {
        $this->guardSuperAdmin();

        $transactions = LocationBalanceTransaction::with('createdBy')
            ->where('location_id', $location->id)
            ->orderByDesc('id')
            ->get();

        $data = $transactions->map(function ($transaction, $index) {
            $balanceTypeBadge = $transaction->balance_type === LocationBalanceTransaction::BALANCE_TYPE_BANK
                ? '<span class="badge bg-label-info">Bank</span>'
                : '<span class="badge bg-label-secondary">Cash</span>';

            $isCredit = $transaction->type === LocationBalanceTransaction::TYPE_CREDIT;
            $typeBadge = $isCredit
                ? '<span class="badge bg-label-success">Credit</span>'
                : '<span class="badge bg-label-danger">Debit</span>';

            $amount = format_price($transaction->amount);

            return [
                'index'         => $index + 1,
                'created_at'    => format_date($transaction->created_at),
                'balance_type'  => $balanceTypeBadge,
                'type'          => $typeBadge,
                'amount'        => '<span class="' . ($isCredit ? 'text-success' : 'text-danger') . '">' . $amount . '</span>',
                'balance_after' => format_price($transaction->balance_after),
                'notes'         => e($transaction->notes) ?: '-',
                'created_by'    => e($transaction->createdBy->name ?? '-'),
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function store(Request $request, Location $location)
    {
        $this->guardSuperAdmin();

        $validator = Validator::make($request->all(), [
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

        $balanceColumn = $request->balance_type === LocationBalanceTransaction::BALANCE_TYPE_BANK
            ? 'bank_balance'
            : 'cash_balance';

        try {
            $newBalance = DB::transaction(function () use ($request, $location, $balanceColumn) {
                $lockedBalance = LocationBalance::where('location_id', $location->id)->lockForUpdate()->firstOrFail();

                $currentBalance = (float) $lockedBalance->{$balanceColumn};
                $amount = (float) $request->amount;

                $newBalance = $request->type === LocationBalanceTransaction::TYPE_CREDIT
                    ? $currentBalance + $amount
                    : $currentBalance - $amount;

                if ($request->type === LocationBalanceTransaction::TYPE_DEBIT && $newBalance < 0) {
                    throw new \RuntimeException('insufficient_balance');
                }

                $lockedBalance->update([$balanceColumn => $newBalance]);

                LocationBalanceTransaction::create([
                    'location_id'   => $location->id,
                    'balance_type'  => $request->balance_type,
                    'type'          => $request->type,
                    'amount'        => $amount,
                    'balance_after' => $newBalance,
                    'notes'         => !empty($request->notes) ? $request->notes : 'Opening Balance Added',
                    'created_by'    => auth()->id(),
                ]);

                return $newBalance;
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

        $location->refresh();

        return response()->json([
            'status'       => 'success',
            'message'      => 'Balance entry recorded successfully.',
            'cash_balance' => format_price($location->cash_balance),
            'bank_balance' => format_price($location->bank_balance),
        ]);
    }

    private function guardSuperAdmin(): void
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);
        $this->authorize('manage branch balances');
    }
}
