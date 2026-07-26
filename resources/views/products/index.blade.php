@extends('layouts.app')

@section('title', 'Products')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <style>
        tr.sortable-ghost { opacity: 0.4; background: #e7e3ff !important; }
        tr.sortable-chosen { background: #f0eeff !important; }
        @media (max-width: 991.98px) {
            #bulkImageOffcanvas, #bulkImageHistoryOffcanvas, #productImportHistoryOffcanvas { width: 100vw !important; }
        }
        .custom-size-stock-tooltip .tooltip-inner {
            background-color: #0f172a;
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            padding: 10px 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
            font-size: 0.75rem;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">Products List</h4>
        <div class="d-flex gap-2 align-items-center flex-wrap">
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

                    {{-- Location (Only for non-restricted users) --}}
                    @if(!$isRestricted)
                        <div class="mb-3 text-start">
                            <label class="form-label fw-medium text-muted mb-1" for="filter-location">Location</label>
                            <select id="filter-location" class="form-select">
                                <option value="">All Locations</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

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

            @can('bulk upload product images')
                <button type="button" class="btn btn-outline-primary" id="bulkImageUploadBtn">
                    <i class="ti ti-file-zip me-1"></i> Bulk Image Upload
                </button>
            @endcan
            @can('create products')
                <button type="button" class="btn btn-outline-primary" id="productImportBtn">
                    <i class="ti ti-upload me-1"></i> Import
                </button>
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

    @can('bulk upload product images')
        <div class="offcanvas offcanvas-end" id="bulkImageOffcanvas" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" style="width: 600px; max-width: 100vw;">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title">Bulk Product Image Upload</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-0 d-flex flex-column" style="overflow: hidden;" id="bulkImageOffcanvasBody">
                @include('products.bulk_image_upload')
            </div>
        </div>

        <div class="offcanvas offcanvas-end" id="bulkImageHistoryOffcanvas" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" style="width: 600px; max-width: 100vw;">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title">Bulk Image Upload Details & History</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-4" style="overflow-y: auto; overflow-x: hidden;">
                <h6 class="fw-semibold mb-3">Upload Summary</h6>
                <div class="row g-3 mb-4" id="historySummaryCards"></div>

                <h6 class="fw-semibold mb-3">Barcode-wise Report</h6>
                <div class="card-datatable table-responsive">
                    <table class="table table-hover border-top" id="bulkImageHistoryTable" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Barcode</th>
                                <th>Status</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    @endcan

    @can('create products')
        <div class="offcanvas offcanvas-end" id="productImportOffcanvas" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" style="width: 600px; max-width: 100vw;">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title">Import Products</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-0 d-flex flex-column" style="overflow: hidden;" id="productImportOffcanvasBody">
                @include('products.product_import')
            </div>
        </div>

        <div class="offcanvas offcanvas-end" id="productImportHistoryOffcanvas" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" style="width: 55vw; max-width: 100vw;">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title">Product Import Details & History</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-4" style="overflow-y: auto; overflow-x: hidden;">
                <h6 class="fw-semibold mb-3">Import Summary</h6>
                <div class="row g-3 mb-4" id="productImportHistorySummaryCards"></div>

                <h6 class="fw-semibold mb-3">Barcode-wise Report</h6>
                <div class="card-datatable table-responsive">
                    <table class="table table-hover border-top" id="productImportHistoryTable" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Barcode</th>
                                <th>Product</th>
                                <th>Status</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody id="productImportHistoryTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    @endcan

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
                        const variationsEscaped = (row.variations ?? '').replace(/"/g, '&quot;');
                        const categoryEscaped = (row.category ?? '').replace(/"/g, '&quot;');
                        const salePriceEscaped = (row.sale_price ?? '').replace(/"/g, '&quot;');
                        const customSizesEscaped = (row.custom_sizes ? JSON.stringify(row.custom_sizes) : '').replace(/"/g, '&quot;');
                        const variantsListEscaped = (row.variants_list && row.variants_list.length ? JSON.stringify(row.variants_list) : '').replace(/"/g, '&quot;');
                        return `<input type="checkbox" class="form-check-input product-select-checkbox"
                            value="${row.id}"
                            data-barcode="${row.raw_barcode}"
                            data-name="${(row.name ?? '').replace(/"/g, '&quot;')}"
                            data-category="${categoryEscaped}"
                            data-variations="${variationsEscaped}"
                            data-sale-price="${salePriceEscaped}"
                            data-pair-product="${row.pair_product ? 1 : 0}"
                            data-pair-mode="${row.pair_mode || ''}"
                            data-custom-sizes="${customSizesEscaped}"
                            data-variants-list="${variantsListEscaped}">`;
                    }
                },
                { data: 'index', orderable: false, width: '5%', render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                { data: 'image',          orderable: false },
                { data: 'name' },
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
                order: [],
                ajax       : {
                    url: '{{ route('admin.products.data') }}', 
                    dataSrc: 'data',
                    cache: false,
                    data: function(d) {
                        d.category_id = $('#filter-category').val();
                        d.status = $('#filter-status').val();
                        d.stock_status = $('#filter-stock-status').val();
                        d.location_id = $('#filter-location').val();
                    }
                },
                columns    : columns,
            });

            window.refreshTable = function () {
                table.ajax.reload(null, false);
            };

            $(document).on('click', '#btnApplyFilter', function (e) {
                e.preventDefault();
                window.refreshTable();
                
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
                $('#filter-location').val('');
                window.refreshTable();
                
                // Close the dropdown after clearing
                const dropdownToggleEl = document.querySelector('#filterDropdownContainer button[data-bs-toggle="dropdown"]');
                if (dropdownToggleEl) {
                    const dropdownInstance = bootstrap.Dropdown.getInstance(dropdownToggleEl) || new bootstrap.Dropdown(dropdownToggleEl);
                    dropdownInstance.hide();
                }
            });

            window.buildBarcodeLabelsHtml = function(items) {
                const esc = function(str) {
                    return String(str ?? '').replace(/[&<>"']/g, function(c) {
                        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
                    });
                };
                let body = '';
                items.forEach(function(item) {
                    const qty = item.qty || 1;
                    for (let i = 0; i < qty; i++) {
                        body += '<div class="label">';
                        body +=   '<div class="zone-front">';
                        body +=     '<div class="mrp-line">MRP : ' + esc(item.salePrice) + '</div>';
                        body +=     '<div class="code-line">' + esc(item.barcodeText) + '</div>';
                        body +=   '</div>';
                        body +=   '<div class="zone-back">';
                        body +=     '<div class="variations-line">' + esc(item.variations || '&nbsp;') + '</div>';
                        body +=     '<img class="barcode-img" src="' + item.barcodeUrl + '" />';
                        body +=     '<div class="category-line">' + esc(item.category) + '</div>';
                        body +=   '</div>';
                        body +=   '<div class="zone-tab"></div>';
                        body += '</div>';
                    }
                });

                return '<!DOCTYPE html><html><head><title>Print Labels</title><style>' +
                    '@page{size:82mm;margin:0 !important;}' +
                    '*{box-sizing:border-box;}' +
                    'html,body{margin:0 !important;padding:0 !important;width:82mm !important;max-width:82mm !important;background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact;font-family:Arial,Helvetica,sans-serif !important;overflow:hidden !important;}' +
                    '.label{width:82mm !important;height:12mm !important;max-height:12mm !important;overflow:hidden !important;display:flex !important;flex-direction:row !important;align-items:center !important;page-break-inside:avoid !important;break-inside:avoid !important;margin:0 !important;padding:0 !important;font-family:Arial,Helvetica,sans-serif !important;color:#000;}' +
                    '.zone-front{width:37mm !important;height:100% !important;display:flex !important;flex-direction:column !important;justify-content:space-between !important;padding:0.8mm 1.5mm !important;overflow:hidden !important;border:0.5px solid #000 !important;border-radius:4px !important;}' +
                    '.zone-back{width:29mm !important;height:100% !important;display:flex !important;flex-direction:column !important;justify-content:space-between !important;align-items:center !important;overflow:hidden !important;padding:0.8mm 1px !important;border:0.5px solid #000 !important;border-radius:4px !important;}' +
                    '.zone-tab{width:16mm !important;height:100% !important;}' +
                    '.mrp-line{font-size:8.5pt !important;font-weight:700 !important;line-height:1 !important;white-space:nowrap;overflow:hidden;}' +
                    '.category-line{font-size:5.5pt !important;font-weight:normal !important;line-height:1 !important;white-space:nowrap;overflow:hidden;}' +
                    '.variations-line{font-size:5.5pt !important;font-weight:normal !important;line-height:1 !important;white-space:nowrap;overflow:hidden;}' +
                    '.code-line{font-size:5.5pt !important;font-weight:normal !important;line-height:1 !important;white-space:nowrap;overflow:hidden;}' +
                    '.barcode-img{width:22mm !important;height:3.5mm !important;object-fit:fill !important;margin:0 !important;display:block;}' +
                    '</style>' +
                    '<script>window.onload=function(){setTimeout(function(){window.print();window.onafterprint=function(){window.close();};setTimeout(function(){window.close();},500);},500);};<\/script>' +
                    '</head><body>' + body + '</body></html>';
            };

            window.viewBarcode = function(barcode, productId) {
                const barcodeUrl = '{{ route('admin.products.barcode', ':id') }}'.replace(':id', productId);

                // The action button HTML comes from the server with only barcode + id,
                // so pull the rest (mrp/product_code/name) from the already-loaded row data.
                const rowData = table.rows().data().toArray().find(r => String(r.id) === String(productId)) || {};
                const rowCategory = rowData.category ?? '';
                const rowVariations = rowData.variations ?? '';
                const rowSalePrice = rowData.sale_price ?? '';
                const rowRawBarcode = rowData.raw_barcode ?? '';

                const customSizes = rowData.custom_sizes || null;
                let customSizeSelectHtml = '';
                if (rowData.pair_product && customSizes && customSizes.length > 0) {
                    customSizeSelectHtml += '<div class="form-group mb-3 text-start">';
                    customSizeSelectHtml += '  <label for="printCustomSize" class="form-label fw-medium text-secondary small">Select Custom Size</label>';
                    customSizeSelectHtml += '  <select id="printCustomSize" class="form-select">';
                    customSizes.forEach(function(sz) {
                        const sizeVal = typeof sz === 'object' && sz !== null ? sz.size : sz;
                        const formattedSize = String(sizeVal).includes('pcs') ? sizeVal : sizeVal + ' pcs';
                        customSizeSelectHtml += `<option value="${formattedSize}">${formattedSize}</option>`;
                    });
                    customSizeSelectHtml += '  </select>';
                    customSizeSelectHtml += '</div>';
                }

                // Variant select for variable products
                const variantsList = rowData.variants_list || [];
                let variantSelectHtml = '';
                if (variantsList.length > 0) {
                    variantSelectHtml += '<div class="form-group mb-3 text-start">';
                    variantSelectHtml += '  <label for="printVariantSelect" class="form-label fw-medium text-secondary small">Select Variant</label>';
                    variantSelectHtml += '  <select id="printVariantSelect" class="form-select">';
                    variantSelectHtml += '  <option value="">-- Select Varient --</option>';
                    variantsList.forEach(function(v) {
                        variantSelectHtml += `<option value="${v.id}">${v.value}</option>`;
                    });
                    variantSelectHtml += '  </select>';
                    variantSelectHtml += '</div>';
                }

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
                                        
                                        ${customSizeSelectHtml}
                                        ${variantSelectHtml}

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
                    const item = { id: productId, qty: qty };
                    if ($('#printCustomSize').length > 0) {
                        item.selected_size = $('#printCustomSize').val();
                    }
                    if ($('#printVariantSelect').length > 0 && $('#printVariantSelect').val()) {
                        item.selected_variant_id = $('#printVariantSelect').val();
                    }
                    window.startBarcodePrint([item]);
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
                    const category = $(this).data('category');
                    const variations = $(this).data('variations');
                    const salePrice = $(this).data('sale-price');
                    const pairProduct = parseInt($(this).data('pair-product')) || 0;
                    const pairMode = $(this).data('pair-mode') || '';
                    const rawCustomSizes = $(this).data('custom-sizes');
                    let customSizes = [];
                    if (rawCustomSizes) {
                        try {
                            customSizes = typeof rawCustomSizes === 'string' ? JSON.parse(rawCustomSizes) : rawCustomSizes;
                        } catch(e){}
                    }
                    const rawVariantsList = $(this).data('variants-list');
                    let variantsList = [];
                    if (rawVariantsList) {
                        try {
                            variantsList = typeof rawVariantsList === 'string' ? JSON.parse(rawVariantsList) : rawVariantsList;
                        } catch(e){}
                    }

                    let customSizeSelectHtml = '';
                    if (pairProduct && pairMode === 'custom_size' && customSizes && customSizes.length) {
                        customSizeSelectHtml = '<div class="mt-1 d-flex align-items-center gap-1"><small class="text-secondary fw-medium">Size:</small><select class="form-select form-select-sm bulk-item-custom-size" style="width: auto; min-width: 90px;">';
                        customSizes.forEach(function(cs) {
                            const sizeVal = typeof cs === 'object' && cs !== null ? cs.size : cs;
                            const label = String(sizeVal).includes('pcs') ? sizeVal : sizeVal + ' pcs';
                            customSizeSelectHtml += `<option value="${label}">${label}</option>`;
                        });
                        customSizeSelectHtml += '</select></div>';
                    }

                    let variantSelectBulkHtml = '';
                    if (variantsList && variantsList.length > 0) {
                        variantSelectBulkHtml = '<div class="mt-1 d-flex align-items-center gap-1"><small class="text-secondary fw-medium">Variant:</small><select class="form-select form-select-sm bulk-item-variant-select" style="width: auto; min-width: 110px;">';
                        variantSelectBulkHtml += '<option value="">-- All --</option>';
                        variantsList.forEach(function(v) {
                            variantSelectBulkHtml += `<option value="${v.id}">${v.value}</option>`;
                        });
                        variantSelectBulkHtml += '</select></div>';
                    }

                    listHtml += `
                        <tr class="bulk-item-row" data-id="${id}" data-barcode="${barcode}" data-category="${category}" data-variations="${variations}" data-sale-price="${salePrice}">
                            <td>
                                <div class="fw-semibold text-dark">${name}</div>
                                ${customSizeSelectHtml}
                                ${variantSelectBulkHtml}
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
                const items = [];
                $('.bulk-item-row').each(function() {
                    const id = $(this).data('id');
                    const qty = parseInt($(this).find('.bulk-item-qty').val()) || 1;
                    const item = { id: id, qty: qty };
                    const $sizeSelect = $(this).find('.bulk-item-custom-size');
                    if ($sizeSelect.length > 0 && $sizeSelect.val()) {
                        item.selected_size = $sizeSelect.val();
                    }
                    const $variantSelect = $(this).find('.bulk-item-variant-select');
                    if ($variantSelect.length > 0 && $variantSelect.val()) {
                        item.selected_variant_id = $variantSelect.val();
                    }
                    items.push(item);
                });

                window.startBarcodePrint(items);

                // Hide bulk modal
                bootstrap.Modal.getInstance(document.getElementById('bulkBarcodeModal')).hide();
            });

            // Bulk Product Image Upload — side panel (content is already server-rendered
            // in the DOM, so opening it is instant with no AJAX fetch/spinner delay)
            $(document).on('click', '#bulkImageUploadBtn', function () {
                const offcanvasEl = document.getElementById('bulkImageOffcanvas');
                bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl).show();
            });

            // Import Products — side panel (content is already server-rendered
            // in the DOM, so opening it is instant with no AJAX fetch/spinner delay)
            $(document).on('click', '#productImportBtn', function () {
                const offcanvasEl = document.getElementById('productImportOffcanvas');
                bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl).show();
            });
        });
    </script>
@endsection
