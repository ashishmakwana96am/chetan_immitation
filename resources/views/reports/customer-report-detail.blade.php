@extends('layouts.app')

@section('title', 'Customer Details')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.css') }}" />
    <style>
        #transactionsTable tbody tr.group-header td,
        #salesTable tbody tr.group-header td {
            background-color: #f0f2f5;
            font-weight: 600;
            font-size: 0.85rem;
            color: #566a7f;
            padding: 8px 14px;
            letter-spacing: 0.3px;
            text-align: center;
            vertical-align: middle;
        }
        #transactionsTable tbody tr.group-header td .group-header-inner,
        #salesTable tbody tr.group-header td .group-header-inner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            line-height: 1;
        }
        #transactionsTable tbody tr.group-header td .group-header-inner i,
        #salesTable tbody tr.group-header td .group-header-inner i {
            font-size: 1rem;
            line-height: 1;
            display: flex;
            align-items: center;
        }
        #salesTable tbody tr:not(.group-header) {
            cursor: pointer;
        }
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
                @if($customer->is_credit_customer)
                    <button class="btn btn-primary" data-common-modal="{{ route('admin.accounting.customer-balance.create', ['customer_id' => $customer->id]) }}">
                        <i class="ti ti-plus me-1"></i> Add Credit Balance
                    </button>
                @endif
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

            @if($customer->is_credit_customer)
                <div class="card">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span class="card-title-icon card-title-icon-success"><i class="ti ti-report-money"></i></span>
                        <h6 class="mb-0 fw-semibold">Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="ledger-info-row">
                            @php $currentBal = $customer->customerBalance->balance ?? 0; @endphp
                            <span class="ledger-info-value {{ $currentBal < 0 ? 'text-danger' : 'text-primary' }}">{{ format_price($currentBal) }}</span>
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
            @endif
        </div>

        @if($customer->is_credit_customer)
            <!-- Transactions List Card (Right Column) -->
            <div class="col-lg-8 col-md-7">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <div class="d-flex align-items-center gap-2">
                            <span class="card-title-icon"><i class="ti ti-receipt"></i></span>
                            <h6 class="mb-0 fw-semibold">Transactions</h6>
                        </div>
                        <form method="GET" action="{{ route('admin.reports.customer-report.detail') }}" id="transactionTypeFilterForm" class="d-flex align-items-center gap-2">
                            <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                            <label class="form-label mb-0 text-nowrap">Type</label>
                            <select name="type" class="form-select form-select-sm no-select2" style="min-width: 140px" onchange="this.form.submit()">
                                <option value="" {{ !$transactionType ? 'selected' : '' }}>All</option>
                                <option value="credit" {{ $transactionType === 'credit' ? 'selected' : '' }}>Credit</option>
                                <option value="debit" {{ $transactionType === 'debit' ? 'selected' : '' }}>Debit</option>
                            </select>
                        </form>
                    </div>
                    <div class="card-datatable table-responsive">
                        <table class="table border-top" id="transactionsTable">
                            <thead>
                                <tr>
                                    <th style="width: 5%">#</th>
                                    <th>Source</th>
                                    <th>Type</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Balance After</th>
                                    <th>Notes</th>
                                    <th>Done By</th>
                                    <th>Action</th>
                                    <th>date_group</th>
                                    <th>date_sort</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $transaction)
                                    <tr @if($transaction->linked_order) data-modal-url="{{ route('admin.reports.customer-report.sale-products', ['order_id' => $transaction->linked_order->id]) }}" data-modal-size="half" style="cursor: pointer;" @endif>
                                        <td></td>
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
                                        <td class="text-end fw-semibold {{ $transaction->balance_after < 0 ? 'text-danger' : 'text-heading' }}">{{ format_price($transaction->balance_after) }}</td>
                                        <td>{{ $transaction->notes ?? '-' }}</td>
                                        <td>{{ $transaction->createdBy->name ?? '-' }}</td>
                                        <td>
                                            <div class="dropdown table-action-dropdown">
                                                <button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                    <span>Actions</span>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">
                                                    @if($transaction->linked_order)
                                                        <a href="javascript:void(0);" class="dropdown-item" data-common-modal="{{ route('admin.reports.customer-report.sale-products', ['order_id' => $transaction->linked_order->id]) }}" data-size="half">
                                                            <i class="ti ti-eye me-2"></i>View
                                                        </a>
                                                    @endif
                                                    @can('manage customer balance')
                                                        <a href="{{ route('admin.accounting.customer-balance.thermal', ['transaction' => $transaction->id, 'auto_print' => 1]) }}" class="dropdown-item" target="_blank">
                                                            <i class="ti ti-printer me-2"></i>Receipt
                                                        </a>
                                                    @endcan
                                                    @if(empty($transaction->linked_order) && !preg_match('/Sale #/i', $transaction->notes ?? ''))
                                                        @can('edit customer balance')
                                                            <a href="javascript:void(0);" class="dropdown-item" data-common-modal="{{ route('admin.accounting.customer-balance.edit', $transaction->id) }}">
                                                                <i class="ti ti-pencil me-2"></i>Edit
                                                            </a>
                                                        @endcan
                                                        @can('delete customer balance')
                                                            <a href="javascript:void(0);" class="dropdown-item text-danger" data-common-delete="{{ route('admin.accounting.customer-balance.destroy', $transaction->id) }}">
                                                                <i class="ti ti-trash me-2"></i>Delete
                                                            </a>
                                                        @endcan
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $transaction->created_at->format('d M Y') }}</td>
                                        <td>{{ $transaction->created_at->format('YmdHis') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- Sales List Card -->
        <div class="{{ $customer->is_credit_customer ? 'col-12' : 'col-lg-8 col-md-7' }}">
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="card-title-icon"><i class="ti ti-shopping-cart"></i></span>
                    <h6 class="mb-0 fw-semibold">Sales</h6>
                </div>
                <div class="card-datatable table-responsive">
                    <table class="table border-top" id="salesTable">
                        <thead>
                            <tr>
                                <th style="width: 5%">#</th>
                                <th>Sale No</th>
                                <th>Location</th>
                                <th class="text-end">Amount</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>date_group</th>
                                <th>date_sort</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $statusLabels = [
                                    \App\Models\Order::STATUS_PENDING => ['Pending', 'bg-label-secondary'],
                                    \App\Models\Order::STATUS_APPROVE => ['Approve', 'bg-label-success'],
                                    \App\Models\Order::STATUS_SHIPPED => ['Shipped', 'bg-label-info'],
                                    \App\Models\Order::STATUS_OUT_FOR_DELIVERY => ['Out For Delivery', 'bg-label-info'],
                                    \App\Models\Order::STATUS_DELIVERED => ['Delivered', 'bg-label-success'],
                                    \App\Models\Order::STATUS_DECLINE => ['Decline', 'bg-label-danger'],
                                ];
                                $paymentStatusLabels = [
                                    \App\Models\Order::PAYMENT_STATUS_PENDING => ['Pending', 'bg-label-warning'],
                                    \App\Models\Order::PAYMENT_STATUS_PAID => ['Paid', 'bg-label-success'],
                                    \App\Models\Order::PAYMENT_STATUS_PARTIAL => ['Partially Paid', 'bg-label-info'],
                                ];
                            @endphp
                            @foreach($sales as $sale)
                                @php
                                    [$statusLabel, $statusClass] = $statusLabels[(int) $sale->status] ?? ['-', 'bg-label-secondary'];
                                    [$paymentLabel, $paymentClass] = $paymentStatusLabels[(int) $sale->payment_status] ?? ['-', 'bg-label-secondary'];
                                @endphp
                                <tr data-href="{{ route('admin.sales.show', $sale->id) }}">
                                    <td></td>
                                    <td><a href="{{ route('admin.sales.show', $sale->id) }}"><code>{{ $sale->order_no }}</code></a></td>
                                    <td>{{ $sale->location->name ?? '-' }}</td>
                                    <td class="text-end fw-semibold text-heading">{{ format_price($sale->final_amount) }}</td>
                                    <td><span class="badge {{ $paymentClass }}">{{ $paymentLabel }}</span></td>
                                    <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                    <td>{{ $sale->created_at->format('d M Y') }}</td>
                                    <td>{{ $sale->created_at->format('YmdHis') }}</td>
                                </tr>
                            @endforeach
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
            window.refreshTable = function () {
                window.location.reload();
            };
            function initGroupedTable(selector, groupNoun) {
                const $table = $(selector);
                if (!$table.length) {
                    return;
                }

                const headCols = $table.find('thead th').length;
                const groupCol = headCols - 2;
                const sortCol = headCols - 1;
                const visibleColCount = groupCol;

                $table.DataTable({
                    responsive: false,
                    order: [[sortCol, 'desc']],
                    columnDefs: [
                        {
                            targets: 0,
                            orderable: false,
                            searchable: false,
                            render: function (data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1;
                            }
                        },
                        { targets: [groupCol, sortCol], visible: false }
                    ],
                    rowGroup: {
                        dataSrc: groupCol,
                        startRender: function (rows, group) {
                            return $('<tr class="group-header"/>')
                                .append('<td colspan="' + visibleColCount + '"><div class="group-header-inner"><i class="ti ti-calendar-event"></i><span>' + group + '</span><span class="badge bg-label-primary">' + rows.count() + ' ' + groupNoun + (rows.count() > 1 ? 's' : '') + '</span></div></td>');
                        }
                    },
                    drawCallback: function () {
                        const api = this.api();
                        api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                            cell.innerHTML = api.page.info().start + i + 1;
                        });
                    }
                });
            }

            initGroupedTable('#transactionsTable', 'transaction');
            initGroupedTable('#salesTable', 'sale');

            $('#salesTable tbody').on('dblclick', 'tr:not(.group-header)', function (e) {
                if ($(e.target).closest('a').length) {
                    return;
                }
                const href = $(this).data('href');
                if (href) {
                    window.location.href = href;
                }
            });

            $('#transactionsTable tbody').on('dblclick', 'tr[data-modal-url]', function (e) {
                if ($(e.target).closest('.dropdown').length) {
                    return;
                }
                window.openCommonModal($(this).data('modal-url'), $(this).data('modal-size'));
            });
        });
    </script>
@endsection
