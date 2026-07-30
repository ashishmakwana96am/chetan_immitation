<div id="chart-data"
     data-total-sales="{{ $totalSales }}"
     data-total-purchases="{{ $totalPurchases }}"
     data-total-expenses="{{ $totalExpenses }}">
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <span class="text-muted">Total Sales</span>
                        <h4 class="mb-0 mt-1" id="totalSalesAmount">{{ format_price($totalSales) }}</h4>
                        <small class="text-muted" id="totalSalesCount">{{ $totalSalesCount }} order{{ $totalSalesCount == 1 ? '' : 's' }}</small>
                    </div>
                    <span class="badge bg-label-success rounded p-2"><i class="ti ti-shopping-cart ti-sm"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <span class="text-muted">Total Purchases</span>
                        <h4 class="mb-0 mt-1" id="totalPurchasesAmount">{{ format_price($totalPurchases) }}</h4>
                        <small class="text-muted" id="totalPurchasesCount">{{ $totalPurchasesCount }} invoice{{ $totalPurchasesCount == 1 ? '' : 's' }}</small>
                    </div>
                    <span class="badge bg-label-info rounded p-2"><i class="ti ti-truck-delivery ti-sm"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <span class="text-muted">Total Expenses</span>
                        <h4 class="mb-0 mt-1" id="totalExpensesAmount">{{ format_price($totalExpenses) }}</h4>
                        <small class="text-muted" id="totalExpensesCount">{{ $totalExpensesCount }} entr{{ $totalExpensesCount == 1 ? 'y' : 'ies' }}</small>
                    </div>
                    <span class="badge bg-label-danger rounded p-2"><i class="ti ti-wallet ti-sm"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <span class="text-muted">Total Purchase Bill</span>
                        <h4 class="mb-0 mt-1" id="totalTransfersCount">{{ $totalTransfersCount }}</h4>
                        <small class="text-muted" id="totalTransfersQty">{{ $totalTransfersQty }} unit{{ $totalTransfersQty == 1 ? '' : 's' }}</small>
                    </div>
                    <span class="badge bg-label-warning rounded p-2"><i class="ti ti-file-invoice ti-sm"></i></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Overview Chart -->
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Overview</h5></div>
    <div class="card-body">
        <div id="dailyOverviewChart"></div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Filter Report</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.daily-report') }}" id="filterForm" class="row g-3">
            <div class="col-md-3 col-sm-6">
                <label class="form-label">Date</label>
                <input type="text" name="date" class="form-control flatpickr" value="{{ $date }}" placeholder="DD-MM-YYYY" />
            </div>
            @if($isSuperAdmin)
                <div class="col-md-3 col-sm-6">
                    <label class="form-label">Branch</label>
                    <select name="location_id" class="form-select">
                        <option value="">All Branches</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" {{ (string) $locationId === (string) $location->id ? 'selected' : '' }}>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-12 d-flex justify-content-end gap-2 mt-4 d-none" id="filterActionButtons">
                <button type="button" id="clearFiltersBtn" class="btn btn-outline-primary">
                    <i class="ti ti-refresh me-1"></i> Clear
                </button>
                <button type="button" id="applyFiltersBtn" class="btn btn-primary">
                    <i class="ti ti-filter me-1"></i> Apply
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Branch-wise Breakdown -->
<div class="card mb-4" id="branchBreakdownCard" style="{{ $branchRows->count() > 1 ? '' : 'display:none;' }}">
    <div class="card-header"><h5 class="mb-0">Branch-wise Breakdown</h5></div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead class="table-light">
                <tr>
                    <th>Branch</th>
                    <th class="text-end">Sales</th>
                    <th class="text-end">Purchases</th>
                    <th class="text-end">Expenses</th>
                    <th class="text-end">Purchase Bill</th>
                </tr>
            </thead>
            <tbody id="branchBreakdownBody">
                @foreach($branchRows as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row['location_name'] }}</td>
                        <td class="text-end">{{ format_price($row['sales_amount']) }} <small class="text-muted">({{ $row['sales_count'] }})</small></td>
                        <td class="text-end">{{ format_price($row['purchase_amount']) }} <small class="text-muted">({{ $row['purchase_count'] }})</small></td>
                        <td class="text-end">{{ format_price($row['expense_amount']) }} <small class="text-muted">({{ $row['expense_count'] }})</small></td>
                        <td class="text-end">{{ $row['transfer_count'] }} <small class="text-muted">({{ $row['transfer_qty'] }} units)</small></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Sales -->
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Sales</h5></div>
    <div class="card-datatable table-responsive">
        <table class="table border-top" id="dailySalesTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Sale No</th>
                    <th>Customer</th>
                    <th>Location</th>
                    <th>Source</th>
                    <th class="text-end">Amount</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Method</th>
                </tr>
            </thead>
            <tbody id="dailySalesBody">
                @foreach($salesRows as $row)
                    <tr>
                        <td>{{ $row['index'] }}</td>
                        <td><code>{{ $row['sale_no'] }}</code></td>
                        <td>{{ $row['customer'] }}</td>
                        <td>{{ $row['location'] }}</td>
                        <td><span class="badge {{ strtoupper($row['source']) === 'ONLINE' ? 'bg-label-primary' : 'bg-label-secondary' }}">{{ $row['source'] }}</span></td>
                        <td class="text-end">{{ format_price($row['amount']) }}</td>
                        <td>{!! $row['status'] !!}</td>
                        <td>{!! $row['payment_status'] !!}</td>
                        <td>{{ $row['method'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Purchases -->
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Purchases</h5></div>
    <div class="card-datatable table-responsive">
        <table class="table border-top" id="dailyPurchasesTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Purchase No</th>
                    <th>Supplier</th>
                    <th class="text-end">Total Amount</th>
                    <th>Status</th>
                    <th>Payment Status</th>
                </tr>
            </thead>
            <tbody id="dailyPurchasesBody">
                @foreach($purchaseRows as $row)
                    <tr>
                        <td>{{ $row['index'] }}</td>
                        <td><code>{{ $row['purchase_no'] }}</code></td>
                        <td>{{ $row['supplier'] }}</td>
                        <td class="text-end">{{ format_price($row['total_amount']) }}</td>
                        <td>{!! $row['status'] !!}</td>
                        <td>{!! $row['payment_status'] !!}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Expenses -->
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Expenses</h5></div>
    <div class="card-datatable table-responsive">
        <table class="table border-top" id="dailyExpensesTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th class="text-end">Amount</th>
                    <th>Payment Method</th>
                    <th>Location</th>
                    <th>Expense Date</th>
                    <th>Created By</th>
                </tr>
            </thead>
            <tbody id="dailyExpensesBody">
                @foreach($expenseRows as $row)
                    <tr>
                        <td>{{ $row['index'] }}</td>
                        <td>{{ $row['title'] }}</td>
                        <td>{{ $row['category'] }}</td>
                        <td class="text-end">{{ format_price($row['amount']) }}</td>
                        <td>{{ $row['payment_method'] }}</td>
                        <td>{{ $row['location'] }}</td>
                        <td>{{ $row['expense_date'] }}</td>
                        <td>{{ $row['created_by'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Purchase Bill -->
<div class="card">
    <div class="card-header"><h5 class="mb-0">Purchase Bill</h5></div>
    <div class="card-datatable table-responsive">
        <table class="table border-top" id="dailyPurchaseBillTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Bill No</th>
                    <th>Source</th>
                    <th>Destination</th>
                    <th>Total Quantity</th>
                    <th class="text-end">Amount</th>
                    <th>Status</th>
                    <th>Created By</th>
                </tr>
            </thead>
            <tbody id="dailyPurchaseBillBody">
                @foreach($purchaseBillRows as $row)
                    <tr>
                        <td>{{ $row['index'] }}</td>
                        <td><code>{{ $row['bill_no'] }}</code></td>
                        <td>{{ $row['source'] }}</td>
                        <td>{{ $row['destination'] }}</td>
                        <td>{{ number_format($row['total_quantity']) }}</td>
                        <td class="text-end">{{ format_price($row['amount']) }}</td>
                        <td>{!! $row['status'] !!}</td>
                        <td>{{ $row['created_by'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
