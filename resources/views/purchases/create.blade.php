@extends('layouts.app')

@section('title', 'New Purchase Invoice')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">New Purchase Invoice</h4>
        <a href="{{ route('admin.purchases.index') }}" class="btn btn-label-secondary">
            <i class="ti ti-arrow-left me-1"></i> Back
        </a>
    </div>

    <form id="purchaseForm" action="{{ route('admin.purchases.store') }}" method="POST">
        @csrf
        <div class="row g-4">

            <!-- Main -->
            <div class="col-lg-8">

                <!-- Invoice Details -->
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Invoice Details</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Invoice No</label>
                                <input type="text" class="form-control" value="{{ $invoiceNo }}" disabled />
                                <small class="text-muted">Auto-generated on save</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Supplier <span class="text-danger">*</span></label>
                                <select name="supplier_id" class="form-select">
                                    <option value="">-- Select Supplier --</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Purchase Items -->
                <div class="card mb-4">
                    <div class="card-header border-bottom pb-3" style="z-index: 10;">
                        <h5 class="mb-3">Purchase Items</h5>
                        <div class="position-relative">
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="ti ti-search"></i></span>
                                <input type="text" id="productSearchInput" class="form-control" placeholder="Search product by name or SKU..." autocomplete="off">
                            </div>
                            <div id="productSearchResults" class="list-group position-absolute w-100 mt-1 bg-white" style="z-index: 9999; background-color: #ffffff; display: none; max-height: 250px; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 0.375rem;">
                                <!-- Search results will appear here -->
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0" id="itemsTable">
                                    <thead>
                                        <tr class="table-light">
                                            <th width="30%">Product</th>
                                            <th width="20%">Qty</th>
                                            <th width="25%">Price</th>
                                            <th width="20%">Total</th>
                                            <th width="5%"></th>
                                        </tr>
                                    </thead>
                                <tbody id="itemsBody"></tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="3" class="text-end fw-semibold">Grand Total</td>
                                        <td class="fw-bold text-primary" id="grandTotal">{{ currency_symbol() }} 0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div id="noItemsMsg" class="text-center text-muted py-4">
                            No items added yet. Click "Add Item" to start.
                        </div>
                    </div>
                </div>

                <!-- Location Allocation -->
                <style>
                    #allocationItemsList .list-group-item.active {
                        background-color: #B4771E !important;
                        border-color: #B4771E !important;
                        color: #ffffff !important;
                    }
                    #allocationItemsList .list-group-item.active .text-heading,
                    #allocationItemsList .list-group-item.active .text-muted,
                    #allocationItemsList .list-group-item.active small {
                        color: #ffffff !important;
                    }
                    #allocationItemsList .list-group-item:hover:not(.active) {
                        background-color: rgba(180, 119, 30, 0.08) !important;
                        color: #B4771E !important;
                    }
                    #allocationItemsList .list-group-item:hover:not(.active) .text-heading {
                        color: #B4771E !important;
                    }
                    #allocationItemsList .list-group-item:hover:not(.active) small {
                        color: rgba(180, 119, 30, 0.8) !important;
                    }
                </style>
                <div class="card mb-4" id="allocationCard" style="display:none!important;">
                    <div class="card-header border-bottom">
                        <h5 class="mb-0">Location Allocation</h5>
                        <small class="text-muted">Allocate each item's quantity across locations. Total allocated must equal item quantity.</small>
                    </div>
                    <div class="row g-0">
                        <!-- Left Panel: Item List with Search -->
                        <div class="col-md-4 border-end d-flex flex-column" style="height: 450px;">
                            <div class="p-2 border-bottom">
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="ti ti-search fs-5"></i></span>
                                    <input type="text" id="allocationSearch" class="form-control form-control-sm" placeholder="Search product..." />
                                </div>
                            </div>
                            <div class="list-group list-group-flush flex-grow-1" id="allocationItemsList" style="overflow-y: auto;">
                                <!-- populated by JS -->
                            </div>
                        </div>
                        <!-- Right Panel: Allocation Form -->
                        <div class="col-md-8 d-flex flex-column" style="height: 450px; overflow-y: auto;">
                            <div class="card-body h-100" id="allocationBody" style="min-height: 250px;">
                                <!-- populated by JS -->
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Summary</h5></div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total Items</span>
                            <span id="summaryItems" class="fw-semibold">0</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Grand Total</span>
                            <span id="summaryTotal" class="fw-bold text-primary">{{ currency_symbol() }} 0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Purchase Status -->
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Purchase Status</h5></div>
                    <div class="card-body">
                        <select name="status" class="form-select no-select2">
                            <option value="pending">Pending</option>
                            <option value="approve" selected>Approve</option>
                            <option value="decline">Decline</option>
                        </select>
                    </div>
                </div>

                <!-- Supplier Payment Status -->
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Supplier Payment Status</h5></div>
                    <div class="card-body">
                        <select name="payment_status" class="form-select no-select2">
                            <option value="pending" selected>Pending</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="ti ti-device-floppy me-1"></i> Save Purchase
                    </button>
                    <a href="{{ route('admin.purchases.index') }}" class="btn btn-label-secondary">Cancel</a>
                </div>
            </div>

        </div>
    </form>

    <!-- Item Row Template -->
    <template id="itemRowTemplate">
        <tr class="item-row" data-index="__INDEX__">
            <td>
                <div class="d-flex flex-column mb-1">
                    <span class="product-name-display fw-semibold text-heading"></span>
                    <small class="product-sku-display text-muted"></small>
                </div>
                <input type="hidden" name="items[__INDEX__][product_id]" class="product-id-input" value="">
                <div class="invalid-feedback"></div>
                <small class="text-muted stock-info-display"></small>
            </td>
            <td>
                <input type="number" name="items[__INDEX__][quantity]"
                    class="form-control form-control-sm item-qty"
                    placeholder="0" min="1" value="1" />
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">{{ currency_symbol() }}</span>
                    <input type="number" name="items[__INDEX__][purchase_price]"
                        class="form-control form-control-sm purchase-price"
                        placeholder="0.00" step="0.01" min="0" value="0" />
                </div>
            </td>
            <td>
                <span class="item-total fw-semibold">{{ currency_symbol() }} 0.00</span>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-icon btn-label-danger remove-item-btn">
                    <i class="ti ti-trash"></i>
                </button>
            </td>
        </tr>
    </template>

    <!-- Parent Row Template -->
    <template id="parentRowTemplate">
        <tr class="item-row parent-row" data-product-id="__PRODUCT_ID__" data-variant-id="parent" data-index="__INDEX__">
            <td>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="d-flex flex-column ms-2">
                        <span class="product-name-display fw-semibold text-heading"></span>
                        <small class="product-sku-display text-muted"></small>
                    </div>
                </div>
                <input type="hidden" name="items[__INDEX__][product_id]" class="product-id-input" value="">
                <div class="invalid-feedback"></div>
                <small class="text-muted stock-info-display ms-2"></small>
            </td>
            <td>
                <input type="number" name="items[__INDEX__][quantity]"
                    class="form-control form-control-sm item-qty"
                    placeholder="0" min="0" value="0" />
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">{{ currency_symbol() }}</span>
                    <input type="number" name="items[__INDEX__][purchase_price]"
                        class="form-control form-control-sm purchase-price"
                        placeholder="0.00" step="0.01" min="0" value="0" />
                </div>
            </td>
            <td>
                <span class="parent-total fw-semibold">{{ currency_symbol() }} 0.00</span>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-icon btn-label-danger remove-parent-btn" title="Remove Product">
                    <i class="ti ti-trash"></i>
                </button>
            </td>
        </tr>
    </template>

    <!-- Variant Row Template -->
    <template id="variantRowTemplate">
        <tr class="item-row variant-row" data-parent-id="__PARENT_ID__" data-variant-id="__VARIANT_ID__" data-index="__INDEX__">
            <td style="padding-left: 4.5rem;">
                <div class="d-flex flex-column mb-1">
                    <div>
                        <span class="text-muted me-2 fw-bold" style="font-size: 1.1rem;">↳</span>
                        <span class="variant-name-display fw-semibold text-heading"></span>
                    </div>
                </div>
                <input type="hidden" name="items[__INDEX__][product_id]" class="product-id-input" value="">
                <small class="text-muted stock-info-display ms-3"></small>
            </td>
            <td>
                <input type="number" name="items[__INDEX__][quantity]"
                    class="form-control form-control-sm item-qty"
                    placeholder="0" min="0" value="0" />
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">{{ currency_symbol() }}</span>
                    <input type="number" name="items[__INDEX__][purchase_price]"
                        class="form-control form-control-sm purchase-price"
                        placeholder="0.00" step="0.01" min="0" value="0" />
                </div>
            </td>
            <td>
                <span class="item-total fw-semibold">{{ currency_symbol() }} 0.00</span>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-icon btn-label-danger remove-variant-btn" title="Remove Variant">
                    <i class="ti ti-trash"></i>
                </button>
            </td>
        </tr>
    </template>

@endsection

@section('page-js')
<script>
$(document).ready(function () {

    let itemIndex = 0;
    let activeAllocationIdx = null;
    const symbol  = '{{ currency_symbol() }}';
    function formatPrice(val) {
        return parseFloat(val).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    @php
        $mappedLocations = $locations->map(function($l) {
            return ['id' => $l->id, 'name' => $l->name];
        })->values()->all();

        $mappedProducts = $products->map(function($p) {
            $data = [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'type' => $p->type,
                'purchase_price' => $p->purchase_price,
            ];
            if ($p->type === 'variable') {
                $data['variants'] = $p->variants->filter(function($v) {
                    return $v->status == 1;
                })->values()->map(function($v) {
                    return [
                        'id' => $v->id,
                        'attribute_value_id' => $v->attribute_value_id,
                        'purchase_price' => $v->purchase_price,
                        'sale_price' => $v->sale_price,
                        'attr_name' => $v->attributeValue->attribute->name ?? '',
                        'value_name' => $v->attributeValue->value ?? '',
                    ];
                })->all();
            }
            return $data;
        })->values()->all();
    @endphp
    const locations = @json($mappedLocations);
    const allProducts = @json($mappedProducts);
    updateGrandTotal();

    // -------------------------------------------------------
    // Product Search and Selection
    // -------------------------------------------------------
    const searchInput = $('#productSearchInput');
    const searchResults = $('#productSearchResults');

    searchInput.on('input', function() {
        const query = $(this).val().toLowerCase().trim();
        searchResults.empty();

        if (query.length === 0) {
            searchResults.hide();
            return;
        }

        const matchedProducts = allProducts.filter(p => 
            p.name.toLowerCase().includes(query) || 
            (p.sku && p.sku.toLowerCase().includes(query))
        );

        if (matchedProducts.length === 0) {
            searchResults.html('<div class="list-group-item text-muted">No products found</div>');
            searchResults.show();
            return;
        }

        matchedProducts.forEach(p => {
            const isVar = p.type === 'variable';
            const priceBadge = isVar
                ? '<span class="badge bg-label-warning">Variable</span>'
                : '<span class="badge bg-label-primary">' + symbol + ' ' + formatPrice(p.purchase_price) + '</span>';
            const item = $(`
                <a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center search-result-item bg-white" style="background-color: #ffffff;" data-id="${p.id}">
                    <div>
                        <div class="fw-semibold">${p.name}</div>
                        <small class="text-muted">SKU: ${p.sku}</small>
                    </div>
                    ${priceBadge}
                </a>
            `);
            item.data('product', p);
            searchResults.append(item);
        });

        searchResults.show();
    });

    // Hide search results when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#productSearchInput, #productSearchResults').length) {
            searchResults.hide();
        }
    });

    // Handle product selection
    $(document).on('click', '.search-result-item', function() {
        const product = $(this).data('product');

        let exists = false;
        // Check if product or its variants are already in the list
        $('.product-id-input').each(function() {
            if ($(this).val() == product.id) {
                exists = true;
            }
        });

        if (exists) {
            toastr.warning('Product is already in the list.');
            searchInput.val('');
            searchResults.hide().empty();
            searchInput.focus();
            return;
        }

        addItemRow(product);

        searchInput.val('');
        searchResults.hide().empty();
        searchInput.focus();
    });

    // Expand/Collapse variants toggle
    $(document).on('click', '.toggle-variants-btn', function () {
        const parentRow = $(this).closest('.parent-row');
        const productId = parentRow.data('product-id');
        const icon = $(this).find('i');
        
        const variantRows = $(`.variant-row[data-parent-id="${productId}"]`);
        variantRows.toggle();
        
        if (icon.hasClass('ti-chevron-down')) {
            icon.removeClass('ti-chevron-down').addClass('ti-chevron-right');
        } else {
            icon.removeClass('ti-chevron-right').addClass('ti-chevron-down');
        }
    });

    function addItemRow(product) {
        if (product.type === 'variable') {
            // 1. Add Parent Row
            const parentTemplate = document.getElementById('parentRowTemplate').innerHTML
                .replaceAll('__INDEX__', itemIndex)
                .replaceAll('__PRODUCT_ID__', product.id);
            $('#itemsBody').append(parentTemplate);
            $('#noItemsMsg').addClass('d-none');

            const parentRow = $('#itemsBody .parent-row').last();
            parentRow.find('.product-id-input').val(product.id);
            parentRow.find('.product-name-display').text(product.name);
            parentRow.find('.product-sku-display').text('SKU: ' + product.sku);
            parentRow.find('.purchase-price').val(product.purchase_price != null ? product.purchase_price : 0);
            parentRow.find('.item-qty').val(1); // Default parent qty to 1

            parentRow.data('product-name', product.name);
            parentRow.data('index', itemIndex);

            updateRowTotal(parentRow);
            updateStockInfo(parentRow);

            itemIndex++;

            // 2. Add Variant Rows under it
            if (product.variants && product.variants.length > 0) {
                product.variants.forEach(function(v) {
                    const variantTemplate = document.getElementById('variantRowTemplate').innerHTML
                        .replaceAll('__INDEX__', itemIndex)
                        .replaceAll('__PARENT_ID__', product.id)
                        .replaceAll('__VARIANT_ID__', v.id);

                    $('#itemsBody').append(variantTemplate);
                    const vRow = $('#itemsBody .variant-row').last();

                    vRow.find('.product-id-input').val(product.id);
                    vRow.find('.variant-name-display').text(v.attr_name + ': ' + v.value_name);
                    vRow.find('.purchase-price').val(v.purchase_price != null ? v.purchase_price : 0);
                    vRow.find('.item-qty').val(0); // Default to 0

                    vRow.data('product-name', product.name + ' (' + v.attr_name + ': ' + v.value_name + ')');
                    vRow.data('index', itemIndex);

                    updateRowTotal(vRow);
                    updateStockInfo(vRow);

                    itemIndex++;
                });
            }
        } else {
            // Normal product
            const template = document.getElementById('itemRowTemplate').innerHTML
                .replaceAll('__INDEX__', itemIndex);

            $('#itemsBody').append(template);
            $('#noItemsMsg').addClass('d-none');

            const row = $('#itemsBody .item-row').last();
            row.find('.product-id-input').val(product.id);
            row.find('.product-name-display').text(product.name);
            row.find('.product-sku-display').text('SKU: ' + product.sku);
            row.data('product-name', product.name);
            row.data('index', itemIndex);
            row.find('.purchase-price').val(product.purchase_price != null ? product.purchase_price : 0);
            row.find('.item-qty').val(1);

            updateRowTotal(row);
            updateStockInfo(row);

            itemIndex++;
        }
        updateGrandTotal();
        renderAllocationSection();
    }

    // Remove Parent Product Row and all its variants
    $(document).on('click', '.remove-parent-btn', function () {
        const parentRow = $(this).closest('.parent-row');
        const productId = parentRow.data('product-id');
        
        $(`.variant-row[data-parent-id="${productId}"]`).each(function() {
            const idx = $(this).data('index');
            $('#allocation-item-' + idx).remove();
            $('#allocation-list-item-' + idx).remove();
            $(this).remove();
        });
        
        parentRow.remove();

        if ($('#itemsBody .item-row').length === 0) {
            $('#noItemsMsg').removeClass('d-none');
            $('#allocationCard').hide();
        } else {
            renderAllocationSection();
        }
        updateGrandTotal();
    });

    // Remove Normal Item Row
    $(document).on('click', '.remove-item-btn', function () {
        const row = $(this).closest('.item-row');
        const idx = row.data('index');
        row.remove();
        $('#allocation-item-' + idx).remove();
        $('#allocation-list-item-' + idx).remove();

        if ($('#itemsBody .item-row').length === 0) {
            $('#noItemsMsg').removeClass('d-none');
            $('#allocationCard').hide();
        } else {
            renderAllocationSection();
        }
        updateGrandTotal();
    });

    // Remove Variant Row
    $(document).on('click', '.remove-variant-btn', function () {
        const row = $(this).closest('.variant-row');
        const idx = row.data('index');
        const parentId = row.data('parent-id');
        row.remove();
        $('#allocation-item-' + idx).remove();
        $('#allocation-list-item-' + idx).remove();

        if ($('#itemsBody .item-row').length === 0) {
            $('#noItemsMsg').removeClass('d-none');
            $('#allocationCard').hide();
        }
        updateParentTotal(parentId);
        updateGrandTotal();
        renderAllocationSection();
    });

    // -------------------------------------------------------
    // Price / Qty change
    // -------------------------------------------------------
    $(document).on('input', '.purchase-price', function () {
        updateRowTotal($(this).closest('.item-row'));
    });

    $(document).on('input', '.item-qty', function () {
        const row = $(this).closest('.item-row');
        updateRowTotal(row);
        updateAllocationItem(row);
    });

    function updateRowTotal(row) {
        if (row.hasClass('variant-row')) {
            const price = parseFloat(row.find('.purchase-price').val()) || 0;
            const qty   = parseInt(row.find('.item-qty').val()) || 0;
            row.find('.item-total').text(symbol + ' ' + formatPrice(price * qty));

            const parentId = row.data('parent-id');
            updateParentTotal(parentId);
        } else if (row.hasClass('parent-row')) {
            const parentId = row.data('product-id');
            updateParentTotal(parentId);
        } else {
            const price = parseFloat(row.find('.purchase-price').val()) || 0;
            const qty   = parseInt(row.find('.item-qty').val()) || 0;
            row.find('.item-total').text(symbol + ' ' + formatPrice(price * qty));
        }
        updateGrandTotal();
    }

    function updateParentTotal(parentId) {
        const parentRow = $(`.parent-row[data-product-id="${parentId}"]`);
        if (parentRow.length === 0) return;

        const parentPrice = parseFloat(parentRow.find('.purchase-price').val()) || 0;
        const parentQty   = parseInt(parentRow.find('.item-qty').val()) || 0;
        let parentTotal   = parentPrice * parentQty;

        $(`.variant-row[data-parent-id="${parentId}"]`).each(function () {
            const price = parseFloat($(this).find('.purchase-price').val()) || 0;
            const qty   = parseInt($(this).find('.item-qty').val()) || 0;
            parentTotal += price * qty;
        });

        parentRow.find('.parent-total').text(symbol + ' ' + formatPrice(parentTotal));
    }

    function updateGrandTotal() {
        let grand = 0, count = 0;
        $('.item-row').each(function () {
            const qty = parseInt($(this).find('.item-qty').val()) || 0;
            const price = parseFloat($(this).find('.purchase-price').val()) || 0;
            grand += price * qty;
            if (qty > 0) {
                count++;
            }
        });
        $('#grandTotal, #summaryTotal').text(symbol + ' ' + formatPrice(grand));
        $('#summaryItems').text(count);

        if ($('.item-row').length > 0) {
            $('#grandTotal').closest('tr').show();
            $('#summaryTotal').closest('.card').show();
        } else {
            $('#grandTotal').closest('tr').hide();
            $('#summaryTotal').closest('.card').hide();
        }
    }

    function updateStockInfo(row) {
        const productId = row.find('.product-id-input').val();
        const stockDisplay = row.find('.stock-info-display');
        const variantId = row.attr('data-variant-id') || row.data('variant-id');

        if (!productId) {
            stockDisplay.text('').removeAttr('title').css('cursor', '');
            return;
        }

        $.get('{{ route('admin.inventory.stock') }}', { product_id: productId, variant_id: variantId })
            .done(function (res) {
                const qty = res.data?.quantity ?? 0;
                const breakdown = res.data?.breakdown || [];
                
                let titleText = 'Stock Breakdown:\n';
                if (breakdown.length > 0) {
                    breakdown.forEach(item => {
                        titleText += `- ${item.location_name}: ${item.quantity}\n`;
                    });
                } else {
                    titleText += 'No stock in any branch';
                }
                
                stockDisplay
                    .text('Total Stock: ' + qty)
                    .attr('title', titleText.trim())
                    .css('cursor', 'help')
                    .removeClass('text-success text-danger')
                    .addClass(qty > 0 ? 'text-success' : 'text-danger');
            });
    }

    // -------------------------------------------------------
    // Render full allocation section
    // -------------------------------------------------------
    function autoDistribute(idx, qty) {
        const allocInputs = $('.alloc-qty[data-item-idx="' + idx + '"]');
        const count = allocInputs.length;
        if (count === 0) return;

        const base      = Math.floor(qty / count);
        const remainder = qty % count;

        allocInputs.each(function (i) {
            $(this).val(i === 0 ? base + remainder : base);
        });

        updateRemainingQty(idx, qty);
    }
    function renderAllocationSection() {
        let hasItems = false;
        const itemsList = $('#allocationItemsList');
        const allocationBody = $('#allocationBody');

        // Collect all active item indices currently in the purchase form
        const activeIndices = [];
        $('#itemsBody .item-row').each(function () {
            const row         = $(this);
            const idx         = row.data('index');
            const productId   = row.find('.product-id-input').val();
            const qty         = parseInt(row.find('.item-qty').val()) || 0;

            if (productId && qty > 0) {
                activeIndices.push(idx);
            }
        });

        // If activeAllocationIdx is not in activeIndices, select the first one
        if (activeIndices.length > 0) {
            if (activeAllocationIdx === null || !activeIndices.includes(activeAllocationIdx)) {
                activeAllocationIdx = activeIndices[0];
            }
        } else {
            activeAllocationIdx = null;
        }

        // Remove any list items or detail blocks that are no longer active
        itemsList.find('.list-group-item').each(function () {
            const idx = $(this).data('item-idx');
            if (!activeIndices.includes(idx)) {
                $(this).remove();
            }
        });
        allocationBody.find('.allocation-item-block').each(function () {
            const idx = $(this).data('item-idx');
            if (!activeIndices.includes(idx)) {
                $(this).remove();
            }
        });

        // Loop and build/update active items
        $('#itemsBody .item-row').each(function () {
            const row         = $(this);
            const idx         = row.data('index');
            const productId   = row.find('.product-id-input').val();
            const productName = row.data('product-name') || '';
            const sku         = row.find('.product-sku-display').text() || '';
            const qty         = parseInt(row.find('.item-qty').val()) || 0;

            if (!productId || qty <= 0) {
                return;
            }

            hasItems = true;

            const existingAllocations = row.data('existing-allocations') || [];

            // Check if block already exists
            let block = $('#allocation-item-' + idx);
            if (block.length === 0) {
                // Create detail block
                block = $('<div>', {
                    id: 'allocation-item-' + idx,
                    class: 'allocation-item-block d-none',
                    'data-item-idx': idx
                });

                block.append(`
                    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                        <div>
                            <h5 class="mb-0 text-primary fw-bold">${productName}</h5>
                            <small class="text-muted">${sku.replace('SKU: ', 'SKU: ')} | Target Quantity: <span class="fw-bold">${qty}</span></small>
                        </div>
                        <span class="badge bg-label-secondary alloc-remaining-wrapper-${idx}">
                            Remaining: <strong class="alloc-remaining-${idx}">${qty}</strong>
                        </span>
                    </div>
                `);

                const locationsHtml = $('<div>', { class: 'row g-3' });
                locations.forEach(function (loc, locIdx) {
                    const existingAlloc = existingAllocations.find(a => a.location_id == loc.id);
                    const allocQty      = existingAlloc ? existingAlloc.quantity : 0;

                    locationsHtml.append(`
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-2">
                                <label class="form-label mb-0 text-nowrap" style="min-width:180px; flex-shrink: 0;">${loc.name}</label>
                                <input type="number"
                                    name="items[${idx}][allocations][${locIdx}][quantity]"
                                    class="form-control form-control-sm alloc-qty"
                                    data-item-idx="${idx}"
                                    placeholder="0" min="0" value="${allocQty}" />
                                <input type="hidden"
                                    name="items[${idx}][allocations][${locIdx}][location_id]"
                                    value="${loc.id}" />
                            </div>
                        </div>
                    `);
                });

                block.append(locationsHtml);
                block.append(`<div class="text-danger small mt-2 alloc-error-${idx} d-none"></div>`);
                $('#allocationBody').append(block);

                if (existingAllocations.length > 0) {
                    updateRemainingQty(idx, qty);
                } else {
                    autoDistribute(idx, qty);
                }
            } else {
                // If block already exists (e.g. user changes quantity after adding)
                block.find('.alloc-remaining-wrapper-' + idx).prev().find('small').html(`${sku} | Target Quantity: <span class="fw-bold">${qty}</span>`);
                autoDistribute(idx, qty);
            }

            // Check if list item already exists
            let listItem = $('#allocation-list-item-' + idx);
            if (listItem.length === 0) {
                listItem = $('<a>', {
                    id: 'allocation-list-item-' + idx,
                    href: 'javascript:void(0)',
                    class: 'list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3',
                    'data-item-idx': idx
                });
                
                listItem.append(`
                    <div class="d-flex flex-column min-w-0 me-2">
                        <span class="fw-semibold text-heading text-truncate">${productName}</span>
                        <small class="text-muted text-truncate">Target: ${qty}</small>
                    </div>
                    <div class="badge-container"></div>
                `);

                itemsList.append(listItem);
            } else {
                listItem.find('span.text-heading').text(productName);
                listItem.find('small').text('Target: ' + qty);
            }

            // Sync current list item badge status
            const total = qty;
            let allocated = 0;
            $('.alloc-qty[data-item-idx="' + idx + '"]').each(function () {
                allocated += parseInt($(this).val()) || 0;
            });
            const remaining = total - allocated;
            const badgeContainer = listItem.find('.badge-container');
            badgeContainer.empty();
            if (remaining === 0) {
                badgeContainer.append(`<span class="badge bg-label-success rounded-pill px-2 py-1"><i class="ti ti-check fs-6"></i></span>`);
            } else {
                badgeContainer.append(`<span class="badge bg-label-warning rounded-pill px-2 py-1">${allocated}/${total}</span>`);
            }
        });

        // Update active classes and visibility for list items and blocks
        activeIndices.forEach(function (idx) {
            const block = $('#allocation-item-' + idx);
            const listItem = $('#allocation-list-item-' + idx);
            if (idx === activeAllocationIdx) {
                block.removeClass('d-none');
                listItem.addClass('active');
            } else {
                block.addClass('d-none');
                listItem.removeClass('active');
            }
        });

        if (hasItems) {
            $('#allocationCard').css('display', '');
        } else {
            $('#allocationCard').hide();
        }
    }

    // -------------------------------------------------------
    // Update single allocation item when qty changes
    // -------------------------------------------------------
    function updateAllocationItem(row) {
        renderAllocationSection();
    }

    // -------------------------------------------------------
    // Live remaining qty counter
    // -------------------------------------------------------
    $(document).on('input', '.alloc-qty', function () {
        const idx     = $(this).data('item-idx');
        const itemRow = $('#itemsBody .item-row[data-index="' + idx + '"]');
        const total   = parseInt(itemRow.find('.item-qty').val()) || 0;
        updateRemainingQty(idx, total);
    });

    function updateRemainingQty(idx, total) {
        let allocated = 0;
        $('.alloc-qty[data-item-idx="' + idx + '"]').each(function () {
            allocated += parseInt($(this).val()) || 0;
        });
        const remaining = total - allocated;
        
        const el = $('.alloc-remaining-' + idx);
        if (el.length > 0) {
            el.text(remaining);
            el.removeClass('text-success text-danger');
            el.addClass(remaining === 0 ? 'text-success' : 'text-danger');
        }

        // Update badge on the left list item
        const listItem = $('#allocation-list-item-' + idx);
        if (listItem.length > 0) {
            const badgeContainer = listItem.find('.badge-container');
            badgeContainer.empty();
            if (remaining === 0) {
                badgeContainer.append(`<span class="badge bg-label-success rounded-pill px-2 py-1"><i class="ti ti-check fs-6"></i></span>`);
            } else {
                badgeContainer.append(`<span class="badge bg-label-warning rounded-pill px-2 py-1">${allocated}/${total}</span>`);
            }
        }
    }

    // -------------------------------------------------------
    // Handle list item selection click
    // -------------------------------------------------------
    $(document).on('click', '#allocationItemsList .list-group-item', function () {
        const idx = $(this).data('item-idx');
        activeAllocationIdx = idx;

        // Toggle list active classes
        $('#allocationItemsList .list-group-item').each(function () {
            const currentIdx = $(this).data('item-idx');
            if (currentIdx === activeAllocationIdx) {
                $(this).addClass('active');
            } else {
                $(this).removeClass('active');
            }
        });

        // Toggle details block visibility
        $('.allocation-item-block').each(function () {
            const currentIdx = $(this).data('item-idx');
            if (currentIdx === activeAllocationIdx) {
                $(this).removeClass('d-none');
            } else {
                $(this).addClass('d-none');
            }
        });
    });

    // -------------------------------------------------------
    // Handle list search/filter
    // -------------------------------------------------------
    $(document).on('input', '#allocationSearch', function () {
        const query = $(this).val().toLowerCase();
        $('#allocationItemsList .list-group-item').each(function () {
            const productName = $(this).find('span.text-heading').text().toLowerCase();
            if (productName.includes(query)) {
                $(this).removeClass('d-none');
            } else {
                $(this).addClass('d-none');
            }
        });
    });

    // -------------------------------------------------------
    // Validate allocations
    // -------------------------------------------------------
    function validateAllocations() {
        let valid = true;

        $('#itemsBody .item-row').each(function () {
            const row     = $(this);
            const idx     = row.data('index');
            const itemQty = parseInt(row.find('.item-qty').val()) || 0;
            const productId = row.find('.product-id-input').val();

            if (!productId) return;
            if (row.hasClass('variant-row') && itemQty <= 0) return;

            let allocated = 0;
            $('.alloc-qty[data-item-idx="' + idx + '"]').each(function () {
                allocated += parseInt($(this).val()) || 0;
            });

            const errorEl = $('.alloc-error-' + idx);
            if (allocated !== itemQty) {
                errorEl.text('Allocated (' + allocated + ') must equal item quantity (' + itemQty + ').').removeClass('d-none');
                valid = false;
            } else {
                errorEl.addClass('d-none');
            }
        });

        return valid;
    }

    // -------------------------------------------------------
    // Submit
    // -------------------------------------------------------
    $('#purchaseForm').on('submit', function (e) {
        e.preventDefault();

        let activeCount = 0;
        $('.item-row').each(function () {
            if ((parseInt($(this).find('.item-qty').val()) || 0) > 0) {
                activeCount++;
            }
        });

        if (activeCount === 0) {
            toastr.error('Please add at least one item with quantity greater than 0.');
            return;
        }

        if (!validateAllocations()) {
            toastr.error('Please fix allocation quantities before saving.');
            return;
        }

        const form = $(this);
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').text('');

        const disabledInputs = [];
        $('.variant-row, .parent-row').each(function () {
            const qty = parseInt($(this).find('.item-qty').val()) || 0;
            if (qty <= 0) {
                $(this).find('input').each(function () {
                    if (!$(this).prop('disabled')) {
                        $(this).prop('disabled', true);
                        disabledInputs.push($(this));
                    }
                });
            }
        });

        $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        $.ajax({
            url     : form.attr('action'),
            type    : 'POST',
            data    : form.serialize(),
            success : function (res) {
                disabledInputs.forEach(input => input.prop('disabled', false));
                if (res.status === 'success') {
                    toastr.success(res.message);
                    setTimeout(() => window.location.href = '{{ route('admin.purchases.index') }}', 800);
                }
            },
            error   : function (xhr) {
                disabledInputs.forEach(input => input.prop('disabled', false));
                $('#submitBtn').prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Save Purchase');
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.message || {};
                    $.each(errors, function (field, messages) {
                        let inputName = field;
                        if (field.includes('.')) {
                            let parts = field.split('.');
                            inputName = parts[0] + '[' + parts.slice(1).join('][') + ']';
                        }
                        let input = form.find('[name="' + inputName + '"]');
                        if (input.length) {
                            input.addClass('is-invalid');
                            input.siblings('.invalid-feedback').text(messages[0]);
                        } else {
                            toastr.error(messages[0]);
                        }
                    });
                } else {
                    toastr.error('Something went wrong. Please try again.');
                }
            }
        });
    });

});
</script>
@endsection
