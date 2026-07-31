@extends('layouts.app')

@section('title', 'Branch Ledger Details')

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
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-semibold mb-0">Branch Ledger Details</h4>
            <small class="text-muted">Purchase Bills (Transfers) for <strong>{{ $location->name ?? 'All Locations' }}</strong> on <strong>{{ format_date($date) }}</strong></small>
        </div>
        <div>
            <a href="{{ route('admin.ledgers.branch') }}" class="btn btn-label-secondary">
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
                    <h6 class="mb-0 fw-semibold">Branch Info</h6>
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
                        <span class="ledger-info-label">Transfer In</span>
                        <span class="ledger-info-value text-success">{{ format_price($totalInAmount) }} ({{ $totalInQtyText }})</span>
                    </div>
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Transfer Out</span>
                        <span class="ledger-info-value text-danger">{{ format_price($totalOutAmount) }} ({{ $totalOutQtyText }})</span>
                    </div>
                    <div class="ledger-info-row">
                        <span class="ledger-info-label">Outstanding</span>
                        <span class="ledger-info-value">{{ format_price($outstandingValue) }} ({{ $outstandingQtyText }})</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transfers List Card (Right Column) -->
        <div class="col-lg-8 col-md-7">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon card-title-icon-success"><i class="ti ti-arrow-down"></i></span>
                    <h6 class="mb-0 fw-semibold">Transfer In (Stock Received)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive text-nowrap">
                        <table class="table border-top table-hover mb-0" id="transferInTable">
                            <thead>
                                <tr>
                                    <th style="width: 5%">#</th>
                                    <th>Transfer No</th>
                                    <th>From Branch</th>
                                    <th class="text-end">Amount (Qty)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transferIn as $index => $transfer)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <a href="{{ route('admin.purchase-bills.show', $transfer->id) }}" class="fw-bold">
                                                {{ $transfer->transfer_no }}
                                            </a>
                                        </td>
                                        <td>{{ $transfer->fromLocation->name ?? '-' }}</td>
                                        <td class="text-end fw-semibold text-success">
                                            {{ format_price(($transferAmount)($transfer)) }} ({{ ($singleTransferQtyText)($transfer) }})
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            No incoming transfers for this date.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon card-title-icon-danger"><i class="ti ti-arrow-up"></i></span>
                    <h6 class="mb-0 fw-semibold">Transfer Out (Stock Sent)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive text-nowrap">
                        <table class="table border-top table-hover mb-0" id="transferOutTable">
                            <thead>
                                <tr>
                                    <th style="width: 5%">#</th>
                                    <th>Transfer No</th>
                                    <th>To Branch</th>
                                    <th class="text-end">Amount (Qty)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transferOut as $index => $transfer)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <a href="{{ route('admin.purchase-bills.show', $transfer->id) }}" class="fw-bold">
                                                {{ $transfer->transfer_no }}
                                            </a>
                                        </td>
                                        <td>{{ $transfer->toLocation->name ?? '-' }}</td>
                                        <td class="text-end fw-semibold text-danger">
                                            {{ format_price(($transferAmount)($transfer)) }} ({{ ($singleTransferQtyText)($transfer) }})
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            No outgoing transfers for this date.
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

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script>
        $(document).ready(function () {
            if ($('#transferInTable tbody tr').length > 0 && $('#transferInTable tbody tr td[colspan]').length === 0) {
                $('#transferInTable').DataTable({
                    responsive: true,
                    order: [],
                    columnDefs: [{ targets: 0, orderable: false }],
                });
            }
            if ($('#transferOutTable tbody tr').length > 0 && $('#transferOutTable tbody tr td[colspan]').length === 0) {
                $('#transferOutTable').DataTable({
                    responsive: true,
                    order: [],
                    columnDefs: [{ targets: 0, orderable: false }],
                });
            }
        });
    </script>
@endsection

