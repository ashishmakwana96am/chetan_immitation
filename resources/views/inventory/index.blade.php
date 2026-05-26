@extends('layouts.app')

@section('title', 'Inventory Report')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Inventory Report</h4>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Total Products</span>
                            <h4 class="mb-0 mt-1">{{ count($products) }}</h4>
                        </div>
                        <span class="badge bg-label-primary rounded p-2"><i class="ti ti-box ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Total Stock</span>
                            <h4 class="mb-0 mt-1">{{ $products->sum('total') }}</h4>
                        </div>
                        <span class="badge bg-label-success rounded p-2"><i class="ti ti-stack ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted">Locations</span>
                            <h4 class="mb-0 mt-1">{{ $locations->count() }}</h4>
                        </div>
                        <span class="badge bg-label-info rounded p-2"><i class="ti ti-map-pin ti-sm"></i></span>
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
                            <h4 class="mb-0 mt-1">{{ $products->where('total', 0)->count() }}</h4>
                        </div>
                        <span class="badge bg-label-danger rounded p-2"><i class="ti ti-alert-triangle ti-sm"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Filter by Category</label>
                    <select id="filterCategory" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Filter by Location</label>
                    <select id="filterLocation" class="form-select">
                        <option value="">All Locations</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Filter by Stock</label>
                    <select id="filterStock" class="form-select">
                        <option value="">All</option>
                        <option value="in">In Stock</option>
                        <option value="out">Out of Stock</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory Table -->
    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="inventoryTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Category</th>
                        @foreach($locations as $location)
                            <th class="text-center location-col" data-location-id="{{ $location->id }}">
                                {{ $location->name }}
                            </th>
                        @endforeach
                        <th class="text-center">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $index => $product)
                        <tr data-category-id="{{ $product['category_id'] }}"
                            data-total="{{ $product['total'] }}">
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <a href="{{ route('admin.products.show', $product['id']) }}" class="fw-semibold">
                                    {{ $product['name'] }}
                                </a>
                            </td>
                            <td><code>{{ $product['sku'] }}</code></td>
                            <td>
                                <span class="badge bg-label-primary">{{ $product['category'] }}</span>
                            </td>
                            @foreach($locations as $location)
                                @php $qty = $product['stock'][$location->id] ?? 0; @endphp
                                <td class="text-center location-col" data-location-id="{{ $location->id }}">
                                    <span class="badge {{ $qty > 0 ? 'bg-label-success' : 'bg-label-secondary' }}">
                                        {{ $qty }}
                                    </span>
                                </td>
                            @endforeach
                            <td class="text-center">
                                <span class="badge {{ $product['total'] > 0 ? 'bg-label-primary' : 'bg-label-danger' }} fw-bold">
                                    {{ $product['total'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script>
        $(document).ready(function () {

            const table = $('#inventoryTable').DataTable({
                responsive  : true,
                order       : [[1, 'asc']],
                columnDefs  : [{ orderable: false, targets: [0] }],
            });

            // Filter by category
            $('#filterCategory').on('change', function () {
                const val = $(this).val();
                table.rows().every(function () {
                    const row = $(this.node());
                    if (!val || row.data('category-id') == val) {
                        $(this.node()).show();
                    } else {
                        $(this.node()).hide();
                    }
                });
                table.draw();
            });

            // Filter by stock
            $('#filterStock').on('change', function () {
                const val = $(this).val();
                $.fn.dataTable.ext.search.pop();
                if (val === 'in') {
                    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                        return parseInt($(table.row(dataIndex).node()).data('total')) > 0;
                    });
                } else if (val === 'out') {
                    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                        return parseInt($(table.row(dataIndex).node()).data('total')) === 0;
                    });
                }
                table.draw();
            });

            // Filter by location — hide/show location columns
            $('#filterLocation').on('change', function () {
                const val = $(this).val();
                if (!val) {
                    $('.location-col').show();
                } else {
                    $('.location-col').hide();
                    $('.location-col[data-location-id="' + val + '"]').show();
                }
            });

        });
    </script>
@endsection
