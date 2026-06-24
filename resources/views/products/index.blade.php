@extends('layouts.app')

@section('title', 'Products')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <style>
        tr.sortable-ghost { opacity: 0.4; background: #e7e3ff !important; }
        tr.sortable-chosen { background: #f0eeff !important; }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Products List</h4>
        <div class="d-flex gap-2 align-items-center">
            <button type="button" id="bulkPrintBarcodesBtn" class="btn btn-label-primary d-none">
                <i class="ti ti-printer me-1"></i> <span id="bulkPrintBtnText">Bulk Print Barcodes</span>
            </button>

            {{-- Filter Dropdown --}}
            <div class="dropdown d-inline-block" id="filterDropdownContainer">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-boundary="viewport" aria-expanded="false">
                    <i class="ti ti-filter me-1"></i> Filter
                </button>
                <div class="dropdown-menu dropdown-menu-end p-4" style="min-width: 320px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.05); border-radius: 8px;">
                    <h5 class="dropdown-header px-0 mb-3 text-start fw-semibold fs-5 text-dark">Filters</h5>

                    {{-- Category --}}
                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium text-muted mb-1" for="filter-category">Category</label>
                        <select id="filter-category" class="form-select">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Status --}}
                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium text-muted mb-1" for="filter-status">Status</label>
                        <select id="filter-status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>

                    {{-- Stock Status --}}
                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium text-muted mb-1" for="filter-stock-status">Stock Status</label>
                        <select id="filter-stock-status" class="form-select">
                            <option value="">All</option>
                            <option value="in_stock">In Stock</option>
                            <option value="out_of_stock">Sold Out</option>
                        </select>
                    </div>

                    <div class="dropdown-divider"></div>

                    <div class="d-flex justify-content-between gap-2 pt-2">
                        <button type="button" class="btn btn-label-secondary btn-sm flex-grow-1" id="btnClearFilter">Clear Filter</button>
                        <button type="button" class="btn btn-primary btn-sm flex-grow-1" id="btnApplyFilter">Apply Filter</button>
                    </div>
                </div>
            </div>

            @can('create products')
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> Add Product
                </a>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="productsTable">
                <thead>
                    <tr>
                        <th style="width: 30px;"><input type="checkbox" id="selectAllProducts" class="form-check-input"></th>
                        <th>#</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>SKU</th>
                        <th>Barcode</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <th>Purchase Price</th>
                        <th>Sale Price</th>
                        <th>MRP</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>


@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>

    <script>
        $(document).ready(function () {
            const columns = [];
            columns.push(
                { 
                    data: null, 
                    orderable: false, 
                    searchable: false,
                    width: '3%',
                    render: function (data, type, row) {
                        if (row.raw_barcode) {
                            const tempDiv = document.createElement("div");
                            tempDiv.innerHTML = row.name;
                            const plainName = tempDiv.textContent || tempDiv.innerText || "";
                            
                            return `<input type="checkbox" class="form-check-input product-select-checkbox" 
                                value="${row.id}" 
                                data-barcode="${row.raw_barcode}" 
                                data-name="${plainName.replace(/"/g, '&quot;')}">`;
                        }
                        return '';
                    }
                },
                { data: 'index',          width: '5%' },
                { data: 'image',          orderable: false },
                { data: 'name' },
                { data: 'sku' },
                { data: 'barcode',       orderable: false },
                { data: 'category' },
                { data: 'stock' },
                { data: 'purchase_price' },
                { data: 'sale_price' },
                { data: 'mrp' },
                { data: 'status',         orderable: false },
                { data: 'actions',        orderable: false },
            );

            const table = $('#productsTable').DataTable({
                responsive : false,
                ajax       : { 
                    url: '{{ route('admin.products.data') }}', 
                    dataSrc: 'data',
                    cache: false,
                    data: function(d) {
                        d.category_id = $('#filter-category').val();
                        d.status = $('#filter-status').val();
                        d.stock_status = $('#filter-stock-status').val();
                    }
                },
                columns    : columns,
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };



            // Apply Filter button handler
            $(document).on('click', '#btnApplyFilter', function (e) {
                e.preventDefault();
                window.refreshTable();
                
                // Close the dropdown after applying
                const dropdownToggleEl = document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]');
                if (dropdownToggleEl) {
                    const dropdownInstance = bootstrap.Dropdown.getInstance(dropdownToggleEl) || new bootstrap.Dropdown(dropdownToggleEl);
                    dropdownInstance.hide();
                }
            });

            // Clear Filter button handler
            $(document).on('click', '#btnClearFilter', function (e) {
                e.preventDefault();
                $('#filter-category').val('');
                $('#filter-status').val('');
                $('#filter-stock-status').val('');
                window.refreshTable();
                
                // Close the dropdown after clearing
                const dropdownToggleEl = document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]');
                if (dropdownToggleEl) {
                    const dropdownInstance = bootstrap.Dropdown.getInstance(dropdownToggleEl) || new bootstrap.Dropdown(dropdownToggleEl);
                    dropdownInstance.hide();
                }
            });

            window.viewBarcode = function(barcode, productId) {
                const barcodeUrl = '{{ route('admin.products.barcode', ':id') }}'.replace(':id', productId);
                const modal = `
                    <div class="modal fade" id="barcodeModal" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered modal-sm">
                            <div class="modal-content">
                                <div class="modal-header border-bottom-0 pb-0">
                                    <h5 class="modal-title fw-semibold">Product Barcode</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center pt-2">
                                    <!-- Spinner Loader -->
                                    <div id="barcodeLoader" class="py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="text-muted small mt-2 mb-0">Generating barcode...</p>
                                    </div>
                                    
                                    <!-- Barcode Content -->
                                    <div id="barcodeContent" class="d-none">
                                        <div class="bg-light p-3 rounded mb-3 d-inline-block w-100">
                                            <div class="mb-2">
                                                <img id="barcodeImage" src="${barcodeUrl}" alt="Barcode" class="img-fluid" style="max-height: 80px;">
                                            </div>
                                            <p class="fw-bold mb-0 text-dark font-monospace fs-5">${barcode}</p>
                                        </div>
                                        
                                        <div class="form-group mb-3 text-start">
                                            <label for="printQty" class="form-label fw-medium text-secondary small">Print Quantity</label>
                                            <input type="number" id="printQty" class="form-control" value="1" min="1" max="100">
                                        </div>
                                        
                                        <button type="button" class="btn btn-primary w-100" id="printBarcodeBtn">
                                            <i class="ti ti-printer me-1"></i> Print Barcode
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                $('#barcodeModal').remove();
                $('body').append(modal);
                const modalEl = new bootstrap.Modal(document.getElementById('barcodeModal'));
                modalEl.show();
                
                const $loader = $('#barcodeLoader');
                const $content = $('#barcodeContent');
                const $img = $('#barcodeImage');
                const $printBtn = $('#printBarcodeBtn');
                const $printQty = $('#printQty');
                
                // Show content when image loads
                $img.on('load', function() {
                    $loader.addClass('d-none');
                    $content.removeClass('d-none');
                }).on('error', function() {
                    $loader.html('<p class="text-danger mb-0">Failed to load barcode image.</p>');
                });
                
                // Handle printing
                $printBtn.on('click', function() {
                    const qty = parseInt($printQty.val()) || 1;
                    const printWindow = window.open('', '_blank');
                    let html = '<!DOCTYPE html><html><head><title>Print Barcodes</title>';
                    html += '<style>';
                    html += 'body { font-family: Arial, sans-serif; margin: 0; padding: 10px; display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; }';
                    html += '.barcode-label { border: 1px dashed #999; padding: 15px; text-align: center; width: 220px; page-break-inside: avoid; display: flex; flex-direction: column; align-items: center; justify-content: center; border-radius: 4px; }';
                    html += '.barcode-value { font-size: 14px; margin-bottom: 8px; font-family: monospace; font-weight: bold; }';
                    html += '.barcode-image { max-width: 100%; height: auto; }';
                    html += '@media print { body { padding: 0; } .barcode-label { border: 1px solid #000; page-break-inside: avoid; } }';
                    html += '</style>';
                    html += '</head><body>';
                    
                    for (let i = 0; i < qty; i++) {
                        html += '<div class="barcode-label">';
                        html += '<div class="barcode-value">' + barcode + '</div>';
                        html += '<img src="' + barcodeUrl + '" class="barcode-image" />';
                        html += '</div>';
                    }
                    
                    html += '<script>';
                    html += 'window.onload = function() {';
                    html += '    setTimeout(function() {';
                    html += '        window.print();';
                    html += '        window.onafterprint = function() { window.close(); };';
                    html += '        setTimeout(function() { window.close(); }, 500);';
                    html += '    }, 500);';
                    html += '};';
                    html += '<\/script>';
                    html += '</body></html>';
                    
                    printWindow.document.write(html);
                    printWindow.document.close();
                });
                
                document.getElementById('barcodeModal').addEventListener('hidden.bs.modal', function () {
                    this.remove();
                });
            };

            // Checkbox selection behavior
            $(document).on('change', '#selectAllProducts', function() {
                const checked = this.checked;
                $('.product-select-checkbox').each(function() {
                    this.checked = checked;
                });
                toggleBulkPrintButton();
            });

            $(document).on('change', '.product-select-checkbox', function() {
                const totalCheckboxes = $('.product-select-checkbox').length;
                const checkedCheckboxes = $('.product-select-checkbox:checked').length;
                $('#selectAllProducts').prop('checked', totalCheckboxes === checkedCheckboxes && totalCheckboxes > 0);
                toggleBulkPrintButton();
            });

            // When Datatable redraws (e.g. pagination, sorting), reset Select All & checkboxes
            table.on('draw', function() {
                $('#selectAllProducts').prop('checked', false);
                toggleBulkPrintButton();
            });

            function toggleBulkPrintButton() {
                const checkedCount = $('.product-select-checkbox:checked').length;
                if (checkedCount > 0) {
                    $('#bulkPrintBarcodesBtn').removeClass('d-none');
                    $('#bulkPrintBtnText').text(`Bulk Print Barcodes (${checkedCount})`);
                } else {
                    $('#bulkPrintBarcodesBtn').addClass('d-none');
                }
            }

            // Bulk print modal triggers and operations
            $(document).on('click', '#bulkPrintBarcodesBtn', function() {
                $('#bulkBarcodeModal').remove();
                
                let listHtml = '';
                $('.product-select-checkbox:checked').each(function() {
                    const id = $(this).val();
                    const barcode = $(this).data('barcode');
                    const name = $(this).data('name');
                    
                    listHtml += `
                        <tr class="bulk-item-row" data-id="${id}" data-barcode="${barcode}">
                            <td>
                                <div class="fw-semibold text-dark">${name}</div>
                            </td>
                            <td><code>${barcode}</code></td>
                            <td>
                                <input type="number" class="form-control form-control-sm bulk-item-qty" value="1" min="1" max="100">
                            </td>
                        </tr>
                    `;
                });
                
                const modalHtml = `
                    <div class="modal fade" id="bulkBarcodeModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header border-bottom">
                                    <h5 class="modal-title fw-semibold">Bulk Print Barcodes</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3 d-flex align-items-center gap-2 bg-light p-2 rounded">
                                        <label for="bulkDefaultQty" class="form-label mb-0 fw-medium small text-secondary">Set Qty for All:</label>
                                        <input type="number" id="bulkDefaultQty" class="form-control form-control-sm w-25" value="1" min="1">
                                        <button type="button" id="applyBulkDefaultQty" class="btn btn-sm btn-primary">Apply</button>
                                    </div>
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-sm table-bordered">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Barcode</th>
                                                    <th style="width: 100px;">Qty</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${listHtml}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="modal-footer border-top-0 pt-0">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" id="startBulkPrintBtn">
                                        <i class="ti ti-printer me-1"></i> Print Barcodes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                $('body').append(modalHtml);
                const modalEl = new bootstrap.Modal(document.getElementById('bulkBarcodeModal'));
                modalEl.show();
            });

            // Apply bulk quantity
            $(document).on('click', '#applyBulkDefaultQty', function() {
                const val = parseInt($('#bulkDefaultQty').val()) || 1;
                $('.bulk-item-qty').val(val);
            });

            // Start bulk printing
            $(document).on('click', '#startBulkPrintBtn', function() {
                const printItems = [];
                $('.bulk-item-row').each(function() {
                    const id = $(this).data('id');
                    const barcode = $(this).data('barcode');
                    const qty = parseInt($(this).find('.bulk-item-qty').val()) || 1;
                    const barcodeUrl = '{{ route('admin.products.barcode', ':id') }}'.replace(':id', id);
                    printItems.push({ barcodeUrl, barcode, qty });
                });

                if (printItems.length === 0) return;

                const printWindow = window.open('', '_blank');
                let html = '<!DOCTYPE html><html><head><title>Print Barcodes</title>';
                html += '<style>';
                html += 'body { font-family: Arial, sans-serif; margin: 0; padding: 10px; display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; }';
                html += '.barcode-label { border: 1px dashed #999; padding: 15px; text-align: center; width: 220px; page-break-inside: avoid; display: flex; flex-direction: column; align-items: center; justify-content: center; border-radius: 4px; }';
                html += '.barcode-value { font-size: 14px; margin-bottom: 8px; font-family: monospace; font-weight: bold; }';
                html += '.barcode-image { max-width: 100%; height: auto; }';
                html += '@media print { body { padding: 0; } .barcode-label { border: 1px solid #000; page-break-inside: avoid; } }';
                html += '</style>';
                html += '</head><body>';

                printItems.forEach(item => {
                    for (let i = 0; i < item.qty; i++) {
                        html += '<div class="barcode-label">';
                        html += '<div class="barcode-value">' + item.barcode + '</div>';
                        html += '<img src="' + item.barcodeUrl + '" class="barcode-image" />';
                        html += '</div>';
                    }
                });

                html += '<script>';
                html += 'window.onload = function() {';
                html += '    setTimeout(function() {';
                html += '        window.print();';
                html += '        window.onafterprint = function() { window.close(); };';
                html += '        setTimeout(function() { window.close(); }, 500);';
                html += '    }, 500);';
                html += '};';
                html += '<\/script>';
                html += '</body></html>';

                printWindow.document.write(html);
                printWindow.document.close();
                
                // Hide bulk modal
                bootstrap.Modal.getInstance(document.getElementById('bulkBarcodeModal')).hide();
            });
        });
    </script>
@endsection
