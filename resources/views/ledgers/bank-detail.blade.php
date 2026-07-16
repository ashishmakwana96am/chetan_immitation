@extends('layouts.app')

@section('title', 'Bank Ledger Details')

@section('page-css')
    <style>
        .ledger-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px dashed #e9e9e9;
        }
        .ledger-info-row:last-child {
            border-bottom: none;
        }
        .ledger-info-label {
            color: #8592a3;
            font-weight: 500;
        }
        .ledger-info-value {
            font-weight: 600;
            color: #566a7f;
        }
        .card-title-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 6px;
            background-color: rgba(115, 103, 240, 0.08);
            color: #7367f0;
            font-size: 1.15rem;
        }
        .card-title-icon-success {
            background-color: rgba(40, 199, 111, 0.08);
            color: #28c76f;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-semibold mb-0">Bank Ledger Details</h4>
            <small class="text-muted">Transactions for <strong>{{ $location->name ?? 'All Locations' }}</strong> on <strong>{{ format_date($date) }}</strong></small>
        </div>
        <div>
            <a href="{{ route('admin.ledgers.bank') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back to Ledger
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Summary/Info Card (Left Column) -->
        <div class="col-lg-4 col-md-5">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon"><i class="ti ti-building-store"></i></span>
                    <h6 class="mb-0 fw-semibold">Location Info</h6>
                </div>
                <div class="card-body">
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Location</span>
                        <span class="ledger-info-value text-heading">{{ $location->name ?? 'All Locations' }}</span>
                    </div>
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Date</span>
                        <span class="ledger-info-value">{{ format_date($date) }}</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon card-title-icon-success"><i class="ti ti-calculator"></i></span>
                    <h6 class="mb-0 fw-semibold">Summary</h6>
                </div>
                <div class="card-body">
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Opening Balance</span>
                        <span class="ledger-info-value {{ $openingBalance < 0 ? 'text-danger fw-bold' : '' }}">{{ format_price($openingBalance) }}</span>
                    </div>
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Total Credit</span>
                        <span class="ledger-info-value text-success">{{ format_price($totalIn) }}</span>
                    </div>
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Total Debit</span>
                        <span class="ledger-info-value text-danger">{{ format_price($totalOut) }}</span>
                    </div>
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Closing Balance</span>
                        <span class="ledger-info-value {{ $closingBalance < 0 ? 'text-danger fw-bold' : 'text-primary' }}">{{ format_price($closingBalance) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions List Card (Right Column) -->
        <div class="col-lg-8 col-md-7">
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon"><i class="ti ti-receipt"></i></span>
                    <h6 class="mb-0 fw-semibold">Credit / Debit Transactions</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive text-nowrap">
                        <table class="table border-top table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 5%">#</th>
                                    <th>Time</th>
                                    @if(!$location)
                                        <th>Location</th>
                                    @endif
                                    <th>Description</th>
                                    <th>Type</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Balance After</th>
                                    <th>By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $index => $transaction)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $transaction->created_at->format('h:i A') }}</td>
                                        @if(!$location)
                                            <td>{{ $transaction->location->name ?? '-' }}</td>
                                        @endif
                                        <td>{{ $transaction->notes ?? '-' }}</td>
                                        <td>
                                            @if($transaction->type === \App\Models\LocationBalanceTransaction::TYPE_CREDIT)
                                                <span class="badge bg-label-success">Credit</span>
                                            @else
                                                <span class="badge bg-label-danger">Debit</span>
                                            @endif
                                        </td>
                                        <td class="text-end fw-semibold {{ $transaction->type === \App\Models\LocationBalanceTransaction::TYPE_CREDIT ? 'text-success' : 'text-danger' }}">
                                            {{ $transaction->type === \App\Models\LocationBalanceTransaction::TYPE_CREDIT ? '+' : '-' }} {{ format_price($transaction->amount) }}
                                        </td>
                                        <td class="text-end {{ $transaction->balance_after < 0 ? 'text-danger fw-bold' : 'text-heading' }}">{{ format_price($transaction->balance_after) }}</td>
                                        <td>{{ $transaction->createdBy->name ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $location ? 7 : 8 }}" class="text-center py-4 text-muted">
                                            No transactions found for this date.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
