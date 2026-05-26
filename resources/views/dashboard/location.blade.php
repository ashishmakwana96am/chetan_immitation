@extends('layouts.app')

@section('title', 'Dashboard')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
@endsection

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-semibold mb-0">Dashboard</h4>
            <small class="text-muted">
                Welcome back, {{ auth()->user()->name }}
                @if($location)
                    &nbsp;|&nbsp; <span class="badge bg-label-primary">{{ $location->name }}</span>
                @endif
            </small>
        </div>
        @can('create sales')
            <a href="{{ route('admin.sales.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i> New Sale
            </a>
        @endcan
    </div>

    <!-- Sales Stats -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Today's Sales</span>
                            <h4 class="mb-0 mt-1 text-primary">{{ format_price($salesStats['today']) }}</h4>
                        </div>
                        <span class="badge bg-label-primary rounded p-2"><i class="ti ti-currency-dollar ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">This Month</span>
                            <h4 class="mb-0 mt-1 text-success">{{ format_price($salesStats['this_month']) }}</h4>
                        </div>
                        <span class="badge bg-label-success rounded p-2"><i class="ti ti-trending-up ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Pending Orders</span>
                            <h4 class="mb-0 mt-1 text-warning">{{ $salesStats['pending'] }}</h4>
                        </div>
                        <span class="badge bg-label-warning rounded p-2"><i class="ti ti-clock ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Completed Orders</span>
                            <h4 class="mb-0 mt-1 text-success">{{ $salesStats['completed'] }}</h4>
                        </div>
                        <span class="badge bg-label-success rounded p-2"><i class="ti ti-check ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Stats -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Products in Stock</span>
                            <h4 class="mb-0 mt-1">{{ $stockStats['total_products'] }}</h4>
                        </div>
                        <span class="badge bg-label-info rounded p-2"><i class="ti ti-box ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Total Stock Units</span>
                            <h4 class="mb-0 mt-1">{{ $stockStats['total_units'] }}</h4>
                        </div>
                        <span class="badge bg-label-info rounded p-2"><i class="ti ti-stack ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Low Stock</span>
                            <h4 class="mb-0 mt-1 text-warning">{{ $stockStats['low_stock'] }}</h4>
                        </div>
                        <span class="badge bg-label-warning rounded p-2"><i class="ti ti-alert-triangle ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Out of Stock</span>
                            <h4 class="mb-0 mt-1 text-danger">{{ $stockStats['out_of_stock'] }}</h4>
                        </div>
                        <span class="badge bg-label-danger rounded p-2"><i class="ti ti-x ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">

        <!-- Monthly Sales Chart -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Sales — Last 6 Months</h5>
                    <span class="badge bg-label-primary">{{ format_price($salesStats['total']) }} Total</span>
                </div>
                <div class="card-body">
                    <div id="monthlySalesChart"></div>
                </div>
            </div>
        </div>

        <!-- Order Status Donut -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Order Status</h5></div>
                <div class="card-body">
                    <div id="orderStatusChart"></div>
                </div>
            </div>
        </div>

    </div>

    <!-- Bottom Row -->
    <div class="row g-4">

        <!-- Recent Sales -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Sales</h5>
                    <a href="{{ route('admin.sales.index') }}" class="btn btn-sm btn-label-primary">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Sale No</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSales as $sale)
                                @php $statusColors = ['pending' => 'bg-label-warning', 'paid' => 'bg-label-info', 'completed' => 'bg-label-success', 'cancelled' => 'bg-label-danger']; @endphp
                                <tr>
                                    <td><a href="{{ route('admin.sales.show', $sale) }}"><code>{{ $sale->order_no }}</code></a></td>
                                    <td>{{ $sale->customer->name ?? 'Walk-in' }}</td>
                                    <td><span class="badge {{ $statusColors[$sale->status] ?? 'bg-label-secondary' }}">{{ ucfirst($sale->status) }}</span></td>
                                    <td class="text-end fw-semibold text-primary">{{ format_price($sale->final_amount) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">No sales yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Top Products + Low Stock -->
        <div class="col-lg-6">
            <div class="row g-4">

                <!-- Top Products -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><h5 class="mb-0">Top Selling Products</h5></div>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-end">Qty Sold</th>
                                        <th class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topProducts as $item)
                                        <tr>
                                            <td>{{ $item->product->name ?? '-' }}</td>
                                            <td class="text-end">{{ $item->total_qty }}</td>
                                            <td class="text-end text-primary fw-semibold">{{ format_price($item->total_revenue) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-muted py-3">No data yet</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Low Stock -->
                <div class="col-12">
                    <div class="card border-warning">
                        <div class="card-header bg-label-warning">
                            <h5 class="mb-0"><i class="ti ti-alert-triangle me-1"></i> Low Stock Alert</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-end">Stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($lowStock as $inventory)
                                        <tr>
                                            <td>{{ $inventory->product->name ?? '-' }}</td>
                                            <td class="text-end">
                                                <span class="badge {{ $inventory->quantity == 0 ? 'bg-label-danger' : 'bg-label-warning' }}">
                                                    {{ $inventory->quantity }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2" class="text-center text-muted py-3">All products well stocked</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script>
    $(document).ready(function () {

        const monthlySales = @json($monthlySales);

        // Monthly Sales Chart
        new ApexCharts(document.getElementById('monthlySalesChart'), {
            chart     : { type: 'area', height: 250, toolbar: { show: false } },
            series    : [
                { name: 'Revenue', data: monthlySales.map(m => m.amount) },
                { name: 'Orders',  data: monthlySales.map(m => m.count) },
            ],
            xaxis     : { categories: monthlySales.map(m => m.month) },
            colors    : ['#7367f0', '#28c76f'],
            stroke    : { curve: 'smooth', width: 2 },
            fill      : { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
            dataLabels: { enabled: false },
            legend    : { position: 'top' },
            yaxis     : [
                { title: { text: 'Revenue' } },
                { opposite: true, title: { text: 'Orders' } },
            ],
        }).render();

        // Order Status Donut
        new ApexCharts(document.getElementById('orderStatusChart'), {
            chart   : { type: 'donut', height: 250 },
            series  : [{{ $salesStats['pending'] }}, {{ $salesStats['completed'] }}, {{ $salesStats['cancelled'] }}],
            labels  : ['Pending', 'Completed', 'Cancelled'],
            colors  : ['#ff9f43', '#28c76f', '#ea5455'],
            legend  : { position: 'bottom' },
            noData  : { text: 'No orders yet' },
        }).render();

    });
    </script>
@endsection
