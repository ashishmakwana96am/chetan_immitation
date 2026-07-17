@extends('layouts.app')

@section('title', 'Customer Ledger Details')

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
            <h4 class="fw-semibold mb-0">Customer Ledger Details</h4>
            <small class="text-muted">Breakdown of sales for <strong>{{ $customer->name }}</strong> on <strong>{{ format_date($date) }}</strong></small>
        </div>
        <div>
            <a href="{{ route('admin.ledgers.customer') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back to Ledger
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
                        <span class="ledger-info-label">Total Sales</span>
                        <span class="ledger-info-value text-primary">{{ format_price($totalSales) }}</span>
                    </div>
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Total Received</span>
                        <span class="ledger-info-value text-success">{{ format_price($totalPayment) }}</span>
                    </div>
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Total Outstanding</span>
                        <span class="ledger-info-value text-danger">{{ format_price($totalOutstanding) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoices List Card (Right Column) -->
        <div class="col-lg-8 col-md-7">
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon"><i class="ti ti-receipt"></i></span>
                    <h6 class="mb-0 fw-semibold">Orders & Sales</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive text-nowrap">
                        <table class="table border-top table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 5%">#</th>
                                    <th>Order No</th>
                                    <th class="text-end">Total Amount</th>
                                    <th class="text-end">Paid Amount</th>
                                    <th class="text-end">Remaining Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $index => $order)
                                    @php
                                        if ($order->payment_status == \App\Models\Order::PAYMENT_STATUS_PAID) {
                                            $paid = (float) $order->final_amount;
                                        } else {
                                            $paid = (float) $order->payments()->where('status', \App\Models\OrderPayment::STATUS_CAPTURED)->sum('amount');
                                        }
                                        $remaining = max(0.0, $order->final_amount - $paid);
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <a href="{{ route('admin.sales.show', $order->id) }}" class="fw-bold">
                                                {{ $order->order_no }}
                                            </a>
                                        </td>
                                        <td class="text-end fw-semibold text-heading">{{ format_price($order->final_amount) }}</td>
                                        <td class="text-end text-success fw-semibold">{{ format_price($paid) }}</td>
                                        <td class="text-end text-danger fw-semibold">{{ format_price($remaining) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            No sales found for this date.
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
