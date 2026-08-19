@extends('layouts.app')

@section('title', 'Supplier Advance Payment History')

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
            <h4 class="fw-semibold mb-0">Supplier Advance Payment History</h4>
            <small class="text-muted">Advance credit payment logs for <strong>{{ $supplier->name }}</strong></small>
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
                        <span class="ledger-info-label">GST No</span>
                        <span class="ledger-info-value">{{ $supplier->gst_no ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon card-title-icon-success"><i class="ti ti-wallet"></i></span>
                    <h6 class="mb-0 fw-semibold">Advance Summary</h6>
                </div>
                <div class="card-body">
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Total Advance Paid</span>
                        <span class="ledger-info-value text-primary">{{ format_price($totalAdvancePaid) }}</span>
                    </div>
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Used in Purchases</span>
                        <span class="ledger-info-value text-warning">{{ format_price($totalAdvanceUsed) }}</span>
                    </div>
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Current Advance Balance</span>
                        <span class="ledger-info-value text-success fw-bold fs-6">{{ format_price($supplier->advance_balance) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advance Payments Table Card (Right Column) -->
        <div class="col-lg-8 col-md-7">
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon card-title-icon-success"><i class="ti ti-history"></i></span>
                    <h6 class="mb-0 fw-semibold">Advance Payment Transactions</h6>
                </div>
                <div class="card-datatable table-responsive">
                    <table class="table border-top table-hover mb-0" id="advancePaymentsTable">
                        <thead>
                            <tr>
                                <th style="width: 5%">#</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th class="text-end">Paid Amount</th>
                                <th class="text-end">Used Amount</th>
                                <th class="text-end">Remaining Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($advancePayments as $index => $adv)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ format_date($adv->created_at) }}</td>
                                    <td>
                                        <span class="badge bg-label-info text-capitalize">
                                            {{ $adv->payment_method ?? 'Cash' }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-semibold text-heading">{{ format_price($adv->total_amount) }}</td>
                                    <td class="text-end text-warning fw-semibold">{{ format_price($adv->used_amount) }}</td>
                                    <td class="text-end text-success fw-bold">{{ format_price($adv->remaining_amount) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No advance payment transactions found for this supplier.
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
            if ($('#advancePaymentsTable tbody tr').length > 0 && !$('#advancePaymentsTable tbody tr td').hasClass('dataTables_empty') && $('#advancePaymentsTable tbody tr td[colspan]').length === 0) {
                $('#advancePaymentsTable').DataTable({
                    responsive: true,
                    order: [],
                    columnDefs: [{ targets: 0, orderable: false }],
                });
            }
        });
    </script>
@endsection
