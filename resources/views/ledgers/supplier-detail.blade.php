@extends('layouts.app')

@section('title', 'Supplier Ledger Details')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
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
        .card-datatable .dataTables_wrapper .row:first-child {
            padding: 1.25rem 1.5rem 0.75rem;
            margin: 0;
        }
        .card-datatable .dataTables_wrapper .row:last-child {
            padding: 0.75rem 1.5rem 1.25rem;
            margin: 0;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-semibold mb-0">Supplier Ledger Details</h4>
            <small class="text-muted">Breakdown of purchases for <strong>{{ $supplier->name }}</strong> as on <strong>{{ format_date($asOnDate) }}</strong></small>
        </div>
        <div>
            <a href="{{ route('admin.ledgers.supplier') }}" class="btn btn-label-secondary">
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
                    <h6 class="mb-0 fw-semibold">Supplier Info</h6>
                </div>
                <div class="card-body">
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Supplier Name</span>
                        <span class="ledger-info-value text-heading">{{ $supplier->name }}</span>
                    </div>
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Mobile</span>
                        <span class="ledger-info-value">{{ $supplier->mobile ?? '-' }}</span>
                    </div>
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">As on Date</span>
                        <span class="ledger-info-value">{{ format_date($asOnDate) }}</span>
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
                        <span class="ledger-info-label">Total Purchases</span>
                        <span class="ledger-info-value text-primary">{{ format_price($totalPurchase) }}</span>
                    </div>
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Total Paid</span>
                        <span class="ledger-info-value text-success">{{ format_price($totalPayment) }}</span>
                    </div>
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Total Remaining</span>
                        <span class="ledger-info-value text-danger">{{ format_price($totalOutstanding) }}</span>
                    </div>
                    @if($canManageAdvance)
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Advance Payment</span>
                        <span class="ledger-info-value text-success fw-bold fs-6">{{ format_price($supplier->advance_balance) }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Invoices List Card (Right Column) -->
        <div class="col-lg-8 col-md-7">
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon"><i class="ti ti-receipt"></i></span>
                    <h6 class="mb-0 fw-semibold">Purchases & Invoices</h6>
                </div>
                <div class="card-datatable table-responsive">
                    <table class="table border-top table-hover mb-0" id="purchasesInvoicesTable">
                        <thead>
                            <tr>
                                <th style="width: 5%">#</th>
                                <th>Invoice No</th>
                                <th>Date</th>
                                <th class="text-end">Total Amount</th>
                                <th class="text-end">Paid Amount</th>
                                <th class="text-end">Remaining Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchases as $index => $purchase)
                                @php
                                    $remaining = max(0.0, $purchase->total_amount - $purchase->paid_amount);
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="fw-bold">
                                            {{ $purchase->invoice_no }}
                                        </a>
                                    </td>
                                    <td>{{ format_date($purchase->created_at) }}</td>
                                    <td class="text-end fw-semibold text-heading">{{ format_price($purchase->total_amount) }}</td>
                                    <td class="text-end text-success fw-semibold">{{ format_price($purchase->paid_amount) }}</td>
                                    <td class="text-end text-danger fw-semibold">{{ format_price($remaining) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No purchases found up to this date.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script>
        $(document).ready(function () {
            if ($('#purchasesInvoicesTable tbody tr').length > 0 && !$('#purchasesInvoicesTable tbody tr td').hasClass('dataTables_empty') && $('#purchasesInvoicesTable tbody tr td[colspan]').length === 0) {
                $('#purchasesInvoicesTable').DataTable({
                    responsive: true,
                    order: [],
                    columnDefs: [{ targets: 0, orderable: false }],
                });
            }
        });
    </script>
@endsection

