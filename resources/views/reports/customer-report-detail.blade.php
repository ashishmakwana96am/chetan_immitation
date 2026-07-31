@extends('layouts.app')

@section('title', 'Customer Details')

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
        .card-title-icon-danger {
            background-color: rgba(234, 84, 85, 0.08);
            color: #ea5455;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-semibold mb-0">Customer Details</h4>
            <small class="text-muted">Full transaction history for <strong>{{ $customer->name }}</strong></small>
        </div>
        <div class="d-flex gap-2">
            @can('manage customer balance')
                <button class="btn btn-primary" data-common-modal="{{ route('admin.accounting.customer-balance.create', ['customer_id' => $customer->id]) }}">
                    <i class="ti ti-plus me-1"></i> Add Credit Balance
                </button>
            @endcan
            <a href="{{ route('admin.reports.customer-report') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Summary/Info Card (Left Column) -->
        <div class="col-lg-4 col-md-5">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon"><i class="ti ti-user"></i></span>
                    <h6 class="mb-0 fw-semibold">Customer Info</h6>
                </div>
                <div class="card-body">
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Customer Name</span>
                        <span class="ledger-info-value text-heading">{{ $customer->name }}</span>
                    </div>
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Phone</span>
                        <span class="ledger-info-value">{{ $customer->phone ?? '-' }}</span>
                    </div>
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Email</span>
                        <span class="ledger-info-value">{{ $customer->email ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon card-title-icon-success"><i class="ti ti-report-money"></i></span>
                    <h6 class="mb-0 fw-semibold">Summary</h6>
                </div>
                <div class="card-body">
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Current Balance</span>
                        <span class="ledger-info-value text-primary">{{ format_price($customer->balance) }}</span>
                    </div>
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Total Credit</span>
                        <span class="ledger-info-value text-success">{{ format_price($totalCredit) }}</span>
                    </div>
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Total Debit</span>
                        <span class="ledger-info-value text-danger">{{ format_price($totalDebit) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions List Card (Right Column) -->
        <div class="col-lg-8 col-md-7">
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon"><i class="ti ti-receipt"></i></span>
                    <h6 class="mb-0 fw-semibold">Transactions</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive text-nowrap">
                        <table class="table border-top table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 5%">#</th>
                                    <th>Date</th>
                                    <th>Source</th>
                                    <th>Type</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Balance After</th>
                                    <th>Notes</th>
                                    <th>Done By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $index => $transaction)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ format_date($transaction->created_at) }}</td>
                                        <td class="text-capitalize">{{ $transaction->source }}</td>
                                        <td>
                                            @if($transaction->type === 'credit')
                                                <span class="badge bg-label-success">Credit</span>
                                            @else
                                                <span class="badge bg-label-danger">Debit</span>
                                            @endif
                                        </td>
                                        <td class="text-end fw-semibold {{ $transaction->type === 'credit' ? 'text-success' : 'text-danger' }}">
                                            {{ format_price($transaction->amount) }}
                                        </td>
                                        <td class="text-end fw-semibold text-heading">{{ format_price($transaction->balance_after) }}</td>
                                        <td>{{ $transaction->notes ?? '-' }}</td>
                                        <td>{{ $transaction->createdBy->name ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            No transactions found.
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
