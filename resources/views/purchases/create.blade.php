@extends('layouts.app')

@section('title', 'New Purchase')

@section('page-css')
<style>
    /* Hide spinners in input fields */
    #itemsTable input[type=number]::-webkit-outer-spin-button,
    #itemsTable input[type=number]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    #itemsTable input[type=number] {
        -moz-appearance: textfield;
    }
    
    /* Column Width Alignments */
    #itemsTable th:nth-child(1), #itemsTable td:nth-child(1) {
        width: 35% !important;
    }
    #itemsTable th:nth-child(2), #itemsTable td:nth-child(2) {
        width: 18% !important;
        min-width: 80px !important;
    }
    #itemsTable th:nth-child(3), #itemsTable td:nth-child(3) {
        width: 27% !important;
        min-width: 110px !important;
    }
    #itemsTable th:nth-child(4), #itemsTable td:nth-child(4) {
        width: 20% !important;
        min-width: 70px !important;
    }
    #itemsTable th:nth-child(5), #itemsTable td:nth-child(5) {
        width: 44px !important;
        min-width: 44px !important;
        max-width: 44px !important;
        padding-left: 0.25rem !important;
        padding-right: 0.25rem !important;
        text-align: center !important;
    }
    
    /* Make inputs look consistent and prevent clipping */
    #itemsTable .item-qty {
        border-radius: 0.375rem !important;
    }
    #itemsTable .input-group {
        flex-wrap: nowrap !important;
    }
    #itemsTable .product-sku-display {
        white-space: nowrap !important;
    }
    .compact-entry-layout .card.mb-4 {
        margin-bottom: 0 !important;
    }
</style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">New Purchase</h4>
        <a href="{{ route('admin.purchases.index') }}" class="btn btn-label-secondary">
            <i class="ti ti-arrow-left me-1"></i> Back
        </a>
    </div>

    <form id="purchaseForm" action="{{ route('admin.purchases.store') }}" method="POST">
        @csrf
        <div class="row g-3 compact-entry-layout">
            <div class="col-lg-8">
                <div class="row g-3">

            <!-- Purchase Details (Full Width) -->
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Purchase Details</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Purchase No</label>
                                <input type="text" class="form-control" value="{{ $invoiceNo }}" disabled />
                                <small class="text-muted">Auto-generated on save</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Supplier <span class="text-danger">*</span></label>
                                <select name="supplier_id" class="form-select">
                                    <option value="">Select Supplier</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Purchase Items (Full Width) -->
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header border-bottom pb-3" style="z-index: 10;">
                        <h5 class="mb-3">Purchase Items</h5>
                        <div class="position-relative">
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="ti ti-search"></i></span>
                                <input type="text" id="productSearchInput" class="form-control" placeholder="Search product by name, SKU or barcode..." autocomplete="off">
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
                                        <th>Product</th>
                                        <th>Qty</th>
                                        <th>Price</th>
                                        <th>Total</th>
                                            <th style="width: 44px;"></th>
                                        </tr>
                                    </thead>
                                <tbody id="itemsBody"></tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="3" class="text-end fw-semibold">Grand Total</td>
                                        <td class="fw-bold text-primary text-nowrap" id="grandTotal">{{ currency_symbol() }} 0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div id="noItemsMsg" class="text-center text-muted py-4">
                            No items added yet.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Location Allocation (Full Width) -->
            <div class="col-12" id="allocationCard" style="display:none!important;">
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
                <div class="card mb-4">
                    <div class="card-header border-bottom">
                        <h5 class="mb-0">Location Allocation</h5>
                        <small class="text-muted">Allocate each item's quantity across locations. Total allocated must equal item quantity.</small>
                    </div>
                    <div class="row g-0">
                        <!-- Left Panel: Item List with Search -->
                        <div class="col-md-4 border-end d-flex flex-column" style="height: 450px; overflow-x: hidden;">
                            <div class="p-2 border-bottom">
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="ti ti-search fs-5"></i></span>
                                    <input type="text" id="allocationSearch" class="form-control form-control-sm" placeholder="Search product..." />
                                </div>
                            </div>
                            <div class="list-group list-group-flush flex-grow-1" id="allocationItemsList" style="overflow-y: auto; overflow-x: hidden;">
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
                </div>
            </div>

            <div class="col-lg-4">
                <div class="row g-3">

            <!-- Bottom widgets: Summary -->
            <div class="col-12" id="summaryColumn" style="display: none;">
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
            </div>

            <!-- Purchase Status -->
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Purchase Status</h5></div>
                    <div class="card-body">
                        <select name="status" class="form-select no-select2">
                            <option value="1">Pending</option>
                            <option value="2" selected>Approve</option>
                            <option value="3">Decline</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Supplier Payment Status -->
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Supplier Payment Status</h5></div>
                    <div class="card-body">
                        <select name="payment_status" class="form-select no-select2">
                            <option value="1" selected>Pending</option>
                            <option value="2">Paid</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="col-12">
                <div class="row g-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary w-100 py-2 fs-5" id="submitBtn">
                            <i class="ti ti-device-floppy me-1"></i> Save Purchase
                        </button>
                    </div>
                    <div class="col-12">
                        <a href="{{ route('admin.purchases.index') }}" class="btn btn-label-secondary w-100 py-2 fs-5 d-flex align-items-center justify-content-center">Cancel</a>
                    </div>
                </div>
            </div>
                </div>
            </div>

        </div>
    </form>

    <!-- Item Row Template -->
    <template id="itemRowTemplate">
        <tr class="item-row" data-index="__INDEX__">
            <td class="align-middle">
                <div class="d-flex align-items-center">
                    <div class="product-image-container me-3 flex-shrink-0"></div>
                    <div class="d-flex flex-column mb-1">
                        <span class="product-name-display fw-semibold text-heading"></span>
                        <div class="d-flex align-items-center gap-2 flex-nowrap">
                            <small class="product-sku-display text-muted"></small>
                            <span class="badge stock-info-display text-nowrap"></span>
                        </div>
                        <div class="variant-select-container"></div>
                    </div>
                </div>
                <input type="hidden" name="items[__INDEX__][product_id]" class="product-id-input" value="">
                <div class="invalid-feedback"></div>
            </td>
            <td class="align-middle">
                <input type="number" name="items[__INDEX__][quantity]"
                    class="form-control item-qty"
                    placeholder="1" min="1" value="1" />
            </td>
            <td class="align-middle">
                <div class="input-group">
                    <span class="input-group-text">{{ currency_symbol() }}</span>
                    <input type="number" name="items[__INDEX__][purchase_price]"
                        class="form-control purchase-price"
                        placeholder="0.00" step="0.01" min="0" value="0" />
                </div>
            </td>
            <td class="align-middle">
                <span class="item-total fw-semibold text-nowrap">{{ currency_symbol() }} 0.00</span>
            </td>
            <td class="align-middle">
                <button type="button" class="btn btn-sm btn-icon btn-label-danger remove-item-btn" title="Remove Item">
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
    function setProductImage(container, product) {
        if (product.image) {
            container.html(`<img src="${product.image}" class="rounded product-thumbnail" style="width: 40px; height: 40px; object-fit: cover;" alt="${product.name || ''}" />`);
        } else {
            container.html(`<div class="rounded bg-label-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="ti ti-photo text-muted" style="font-size: 1.25rem;"></i></div>`);
        }
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
                'barcode' => $p->barcode,
                'type' => $p->type,
                'purchase_price' => $p->purchase_price,
                'image' => $p->primaryImage ? $p->primaryImage->image_url : null,
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

    function findExactProductMatch(query) {
        const q = query.toLowerCase().trim();
        if (!q) return null;
        const matches = allProducts.filter(p =>
            p.name.toLowerCase() === q ||
            (p.sku && String(p.sku).toLowerCase() === q) ||
            (p.barcode && String(p.barcode).toLowerCase() === q)
        );
        return matches.length === 1 ? matches[0] : null;
    }

    function selectSearchProduct(product) {
        let exists = false;
        if (product.type !== 'variable') {
            $('.product-id-input').each(function() {
                const row = $(this).closest('.item-row');
                const rowProduct = row.data('product');
                if (rowProduct && rowProduct.type !== 'variable' && $(this).val() == product.id) {
                    exists = true;
                }
            });
        }
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
    }

    searchInput.on('keydown', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            const exactMatch = findExactProductMatch($(this).val());
            if (exactMatch) {
                selectSearchProduct(exactMatch);
                return;
            }
            const firstItem = searchResults.find('.search-result-item').first();
            if (firstItem.length > 0) {
                firstItem.click();
            }
        }
    });

    searchInput.on('input', function() {
        const query = $(this).val().toLowerCase().trim();
        searchResults.empty();

        if (query.length === 0) {
            searchResults.hide();
            return;
        }

        const exactMatch = findExactProductMatch(query);
        if (exactMatch) {
            selectSearchProduct(exactMatch);
            return;
        }

        const matchedProducts = allProducts.filter(p => 
            p.name.toLowerCase().includes(query) || 
            (p.sku && p.sku.toLowerCase().includes(query)) ||
            (p.barcode && p.barcode.toLowerCase().includes(query))
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
            const imgHtml = p.image 
                ? `<img src="${p.image}" class="rounded me-3" style="width: 40px; height: 40px; object-fit: cover;" alt="${p.name}" />`
                : `<div class="rounded me-3 bg-label-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;"><i class="ti ti-photo text-secondary fs-4"></i></div>`;

            const item = $(`
                <a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center search-result-item bg-white" style="background-color: #ffffff;" data-id="${p.id}">
                    <div class="d-flex align-items-center">
                        ${imgHtml}
                        <div>
                            <div class="fw-semibold">${p.name}</div>
                            <small class="text-muted">SKU: ${p.sku}${p.barcode ? ' | Barcode: ' + p.barcode : ''}</small>
                        </div>
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
        selectSearchProduct($(this).data('product'));
    });

    function addItemRow(product, selectedVariantId = null, qty = 1, price = null) {
        const template = document.getElementById('itemRowTemplate').innerHTML
            .replaceAll('__INDEX__', itemIndex);

        $('#itemsBody').append(template);
        $('#noItemsMsg').addClass('d-none');

        const row = $('#itemsBody .item-row').last();
        row.find('.product-id-input').val(product.id);
        row.find('.product-name-display').text(product.name);
        setProductImage(row.find('.product-image-container'), product);

        row.data('product', product);
        row.data('index', itemIndex);

        if (product.type === 'variable') {
            // Build variant select dropdown
            let selectHtml = `<select class="form-select form-select-sm variant-select mt-2 no-select2">`;
            product.variants.forEach(v => {
                const optPrice = v.purchase_price != null ? v.purchase_price : 0;
                const selected = (selectedVariantId && selectedVariantId == v.id) || (!selectedVariantId && product.variants[0].id == v.id) ? 'selected' : '';
                selectHtml += `<option value="${v.id}" data-price="${optPrice}" ${selected}>${v.attr_name}: ${v.value_name} (${symbol}${optPrice})</option>`;
            });
            selectHtml += `</select>`;
            row.find('.variant-select-container').html(selectHtml);
            
            // Set initial variant
            const selectedOpt = row.find('.variant-select option:selected');
            const initialVariantId = selectedOpt.val();
            const initialPrice = price != null ? price : selectedOpt.data('price');
            
            row.attr('data-variant-id', initialVariantId);
            row.data('variant-id', initialVariantId);
            row.find('.purchase-price').val(initialPrice);
            row.find('.product-sku-display').text('SKU: ' + product.sku);

            const variantText = selectedOpt.text().split(' (')[0];
            row.data('product-name', product.name + ' (' + variantText + ')');
        } else {
            row.find('.product-sku-display').text('SKU: ' + product.sku);
            row.find('.purchase-price').val(price != null ? price : (product.purchase_price != null ? product.purchase_price : 0));
            row.data('product-name', product.name);
        }

        row.find('.item-qty').val(qty);

        updateRowTotal(row);
        updateStockInfo(row);

        itemIndex++;
        updateGrandTotal();
        renderAllocationSection();
    }

    $(document).on('change', '.variant-select', function() {
        const row = $(this).closest('.item-row');
        const product = row.data('product');
        const selectedOpt = $(this).find('option:selected');
        const variantId = selectedOpt.val();
        const price = selectedOpt.data('price');
        
        row.attr('data-variant-id', variantId);
        row.data('variant-id', variantId);
        row.find('.purchase-price').val(price);
        
        const variantText = selectedOpt.text().split(' (')[0];
        row.data('product-name', product.name + ' (' + variantText + ')');
        
        updateRowTotal(row);
        updateStockInfo(row);
        updateGrandTotal();
        renderAllocationSection();
    });

    // Remove Item Row
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
            $('#summaryColumn').show();
        } else {
            $('#grandTotal').closest('tr').hide();
            $('#summaryColumn').hide();
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
                    .text(qty === 0 ? 'Out of Stock' : 'Stock: ' + qty)
                    .attr('title', titleText.trim())
                    .css('cursor', 'help')
                    .removeClass('bg-label-success bg-label-danger bg-label-warning text-success text-danger text-warning')
                    .addClass(qty > 0 ? (qty < 10 ? 'bg-label-warning' : 'bg-label-success') : 'bg-label-danger');
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
                            <small class="text-muted">${sku.replace('SKU: ', 'SKU: ')} | Quantity: <span class="fw-bold">${qty}</span></small>
                        </div>
                        <span class="badge bg-label-secondary alloc-remaining-wrapper-${idx}" style="display: none;">
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
                block.find('h5').text(productName);
                block.find('.alloc-remaining-wrapper-' + idx).prev().find('small').html(`${sku} | Quantity: <span class="fw-bold">${qty}</span>`);
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
                    <div class="d-flex flex-column min-w-0 me-2" style="width: 100%; overflow: hidden;">
                        <span class="fw-semibold text-heading text-truncate" style="display: block; width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${productName}</span>
                    </div>
                    <div class="badge-container d-none"></div>
                `);

                itemsList.append(listItem);
            } else {
                listItem.find('span.text-heading').text(productName);
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
    // Live remaining qty counter with auto adjustment
    // -------------------------------------------------------
    $(document).on('input', '.alloc-qty', function () {
        const changedInput = $(this);
        const idx = changedInput.data('item-idx');
        const itemRow = $('#itemsBody .item-row[data-index="' + idx + '"]');
        const total = parseInt(itemRow.find('.item-qty').val()) || 0;

        // Auto adjustment logic
        let val = parseInt(changedInput.val()) || 0;
        if (val < 0) {
            val = 0;
            changedInput.val(val);
        }
        if (val > total) {
            val = total;
            changedInput.val(val);
        }

        const allocInputs = $('.alloc-qty[data-item-idx="' + idx + '"]');
        if (allocInputs.length > 1) {
            const otherInputs = allocInputs.not(changedInput);
            const remaining = total - val;
            
            let otherSum = 0;
            otherInputs.each(function() {
                otherSum += parseInt($(this).val()) || 0;
            });

            let allocatedToOthers = 0;
            const otherCount = otherInputs.length;

            otherInputs.each(function(i) {
                let newVal = 0;
                if (i === otherCount - 1) {
                    // Last item takes the exact remaining difference to avoid rounding issues
                    newVal = remaining - allocatedToOthers;
                } else {
                    if (otherSum > 0) {
                        const currentVal = parseInt($(this).val()) || 0;
                        newVal = Math.round(currentVal * (remaining / otherSum));
                    } else {
                        newVal = Math.round(remaining / otherCount);
                    }
                    allocatedToOthers += newVal;
                }
                if (newVal < 0) newVal = 0;
                $(this).val(newVal);
            });
        }

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
        form.find('.select2-container .select2-selection').css('border-color', '');
        form.find('.invalid-feedback').text('').hide();

        // Remove any previously appended hidden mapping container
        $('#hiddenSubmitContainer').remove();

        // Create a container for our mapped inputs
        const hiddenContainer = $('<div id="hiddenSubmitContainer" style="display: none;"></div>');
        form.append(hiddenContainer);

        // Disable all inputs in the visible table and allocation body so they are NOT serialized
        const visibleInputs = $('#itemsTable, #allocationBody').find('input, select');
        visibleInputs.prop('disabled', true);

        // Submit each visible row separately so variant quantities and allocations stay independent.
        let submitIdx = 0;
        $('.item-row').each(function() {
            const row = $(this);
            const product = row.data('product');
            const qty = parseInt(row.find('.item-qty').val()) || 0;
            if (qty <= 0) return; // skip rows with 0 qty

            // Get allocations from the allocation section for this row index
            const uiIdx = row.data('index');
            const allocations = [];
            $(`.alloc-qty[data-item-idx="${uiIdx}"]`).each(function() {
                const allocInput = $(this);
                const locId = allocInput.siblings('input[type="hidden"]').val();
                const allocQty = parseInt(allocInput.val()) || 0;
                allocations.push({
                    location_id: locId,
                    quantity: allocQty
                });
            });

            const variantId = row.data('variant-id') || '';
            const purchasePrice = parseFloat(row.find('.purchase-price').val()) || 0;

            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][product_id]" value="${product.id}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][product_variant_id]" value="${variantId}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][quantity]" value="${qty}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][purchase_price]" value="${purchasePrice}">`);

            allocations.forEach((alloc, locIdx) => {
                hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][allocations][${locIdx}][location_id]" value="${alloc.location_id}">`);
                hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][allocations][${locIdx}][quantity]" value="${alloc.quantity}">`);
            });
            submitIdx++;
        });

        $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        $.ajax({
            url     : form.attr('action'),
            type    : 'POST',
            data    : form.serialize(),
            success : function (res) {
                visibleInputs.prop('disabled', false);
                hiddenContainer.remove();
                if (res.status === 'success') {
                    toastr.success(res.message);
                    setTimeout(() => window.location.href = '{{ route('admin.purchases.index') }}', 800);
                }
            },
            error   : function (xhr) {
                visibleInputs.prop('disabled', false);
                hiddenContainer.remove();
                $('#submitBtn').prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Save Purchase');
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.message || {};
                    $.each(errors, function (field, messages) {
                        let input = form.find('[name="' + field + '"], [name="' + field + '[]"]');
                        if (input.length > 0) {
                            input.addClass('is-invalid');
                            
                            if (input.hasClass('select2-hidden-accessible')) {
                                input.next('.select2-container').find('.select2-selection').css('border-color', '#ea5455');
                            }
                            
                            let container = input.closest('.input-group');
                            if (container.length > 0) {
                                container.siblings('.invalid-feedback').text(messages[0]).show();
                            } else {
                                input.siblings('.invalid-feedback').text(messages[0]).show();
                            }
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
