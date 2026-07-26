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
    #itemsTable {
        min-width: 780px !important;
    }

    /* Column Width Alignments */
    #itemsTable th:nth-child(1), #itemsTable td:nth-child(1) {
        width: 30% !important;
    }
    #itemsTable th:nth-child(2), #itemsTable td:nth-child(2) {
        width: 12% !important;
        min-width: 80px !important;
    }
    #itemsTable th:nth-child(3), #itemsTable td:nth-child(3) {
        width: 20% !important;
        min-width: 130px !important;
    }
    #itemsTable th:nth-child(4), #itemsTable td:nth-child(4) {
        width: 23% !important;
        min-width: 190px !important;
    }
    #itemsTable th:nth-child(5), #itemsTable td:nth-child(5) {
        width: 15% !important;
        min-width: 70px !important;
    }
    #itemsTable th:nth-child(6), #itemsTable td:nth-child(6) {
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
        width: fit-content !important;
    }
    #itemsTable .product-sku-display {
        white-space: nowrap !important;
    }
    .compact-entry-layout .card.mb-4 {
        margin-bottom: 0 !important;
    }

    /* Fixed, industry-standard widths for Qty / Price / Discount inputs on every screen size */
    #itemsTable .item-qty {
        width: 70px !important;
        min-width: 70px !important;
        max-width: 70px !important;
        text-align: center;
        margin: 0 auto;
    }
    #itemsTable .purchase-price {
        width: 110px !important;
        min-width: 110px !important;
        max-width: 110px !important;
        flex: 0 0 110px !important;
    }
    #itemsTable .item-discount-type {
        width: 110px !important;
        flex: 0 0 110px !important;
    }
    #itemsTable .item-discount-value {
        width: 80px !important;
        min-width: 80px !important;
        max-width: 80px !important;
        flex: 0 0 80px !important;
    }

    /* Prevent large amounts from ever breaking the sidebar layout, on any screen size */
    #summaryColumn .d-flex.justify-content-between,
    #discountColumn .d-flex.justify-content-between {
        flex-wrap: wrap;
        row-gap: 4px;
    }
    #summaryColumn .d-flex.justify-content-between > span,
    #discountColumn .d-flex.justify-content-between > span {
        min-width: 0;
    }
    #summaryColumn .d-flex.justify-content-between > span:last-child,
    #discountColumn .d-flex.justify-content-between > span:last-child {
        overflow-wrap: anywhere;
        word-break: break-word;
        text-align: right;
        flex: 1 1 auto;
    }

</style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
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
                                <select name="supplier_id" id="supplier_select" class="form-select">
                                    <option value="">Select Supplier</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" data-state="{{ $supplier->state }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" id="paymentMethodSelect" class="form-select no-select2">
                                    <option value="cash">Cash</option>
                                    <option value="online">Online</option>
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
                                <input type="text" id="productSearchInput" class="form-control" placeholder="Search product by name or barcode..." autocomplete="off">
                            </div>
                            <div id="productSearchResults" class="list-group position-absolute w-100 mt-1 bg-white" style="z-index: 9999; background-color: #ffffff; display: none; max-height: 250px; overflow-y: auto; overflow-x: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 0.375rem;">
                                <!-- Search results will appear here -->
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0 d-none" id="itemsTable">
                                    <thead>
                                        <tr class="table-light">
                                            <th style="min-width: 250px;">Product</th>
                                            <th style="width: 100px; min-width: 100px;">Qty</th>
                                            <th style="width: 150px; min-width: 150px;">Price</th>
                                            <th style="width: 200px; min-width: 200px;">Discount</th>
                                            <th style="width: 120px; min-width: 120px;">Total</th>
                                            <th style="width: 44px;"></th>
                                        </tr>
                                    </thead>
                                <tbody id="itemsBody"></tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="4" class="text-end fw-semibold">Grand Total</td>
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

            <input type="hidden" id="overallDiscountType" name="discount_type" value="flat" />
            <input type="hidden" id="overallDiscountValue" name="discount_value" value="0" />
                </div>
            </div>

            <div class="col-lg-4">
                <div class="row g-3">

            <!-- Discount on purchase -->
            <div class="col-12" id="discountColumn" style="display: none;">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Discount on purchase</h5></div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-6">
                                <select id="orderDiscountTypeSelect" class="form-select no-select2">
                                    <option value="flat">Flat</option>
                                    <option value="percentage">Percentage</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="number" id="orderDiscountValueInput" class="form-control" placeholder="0" min="0" value="0" />
                                <div class="invalid-feedback">Cannot exceed total.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tax Details -->
            <div class="col-12" id="taxColumn" style="display: none;">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Tax Details</h5></div>
                    <div class="card-body">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_gst_switch" name="is_gst" value="1" />
                            <label class="form-check-label" for="is_gst_switch">GST Bill</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom widgets: Summary -->
            <div class="col-12" id="summaryColumn" style="display: none;">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Summary</h5></div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total Items</span>
                            <span id="summaryItems" class="fw-semibold">0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span id="summaryItemsTotal" class="fw-semibold">0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 d-none" id="summaryDiscountRow">
                            <span class="text-muted">Discount</span>
                            <span id="summaryDiscountAmount" class="fw-semibold text-danger">0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 d-none" id="summaryCGSTRow">
                            <span class="text-muted" id="summaryCGSTLabel">CGST (1.5%)</span>
                            <span id="summaryCGSTAmount" class="fw-semibold">0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 d-none" id="summarySGSTRow">
                            <span class="text-muted" id="summarySGSTLabel">SGST (1.5%)</span>
                            <span id="summarySGSTAmount" class="fw-semibold">0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 d-none" id="summaryIGSTRow">
                            <span class="text-muted" id="summaryIGSTLabel">IGST (3%)</span>
                            <span id="summaryIGSTAmount" class="fw-semibold">0.00</span>
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
                        <select name="payment_status" id="paymentStatusSelect" class="form-select no-select2">
                            <option value="1" selected>Pending</option>
                            <option value="3">Partially Paid</option>
                            <option value="2">Paid</option>
                        </select>
                        <div class="mt-3 d-none" id="paidAmountWrapper">
                            <label class="form-label">Amount Paid</label>
                            <input type="number" name="paid_amount" id="paidAmountInput" class="form-control" min="0.01" step="0.01" placeholder="Enter amount paid" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="col-12">
                <div class="row g-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                            <i class="ti ti-device-floppy me-1"></i> Save Purchase
                        </button>
                    </div>
                    <div class="col-12">
                        <a href="{{ route('admin.purchases.index') }}" class="btn btn-label-secondary w-100 d-flex align-items-center justify-content-center">Cancel</a>
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
                            <span class="badge bg-label-warning pair-product-badge text-nowrap d-none">Pair Product</span>
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
                <div class="input-group flex-nowrap" style="min-width: 190px;">
                    <select name="items[__INDEX__][discount_type]" class="form-select item-discount-type no-select2" style="width: 110px; flex-shrink: 0; flex-grow: 0; padding-left: 8px; padding-right: 18px; background-position: right 4px center;">
                        <option value="flat">Flat</option>
                        <option value="percentage">Percentage</option>
                    </select>
                    <input type="number" name="items[__INDEX__][discount_value]"
                        class="form-control item-discount-value"
                        placeholder="0" min="0" step="0.01" value="0" />
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
    const symbol  = '{{ currency_symbol() }}';
    const supplierEditUrlTemplate = '{{ route('admin.suppliers.edit', ['supplier' => '__ID__']) }}';
    let pendingGstFixSupplierId = null;

    window.refreshTable = function (res) {
        if (!pendingGstFixSupplierId || !res || !res.data) return;
        pendingGstFixSupplierId = null;

        if (res.data.gst_no) {
            $('#is_gst_switch').prop('checked', true).removeClass('is-invalid');
            $('#purchaseForm').trigger('submit');
        }
    };

    // If the supplier modal is closed without saving, don't auto-retry on some later unrelated save.
    $('#commonModal').on('hidden.bs.offcanvas', function () {
        pendingGstFixSupplierId = null;
    });

    function formatPrice(val) {
        return parseFloat(val).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function formatPriceNoDecimals(val) {
        return parseFloat(val).toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }
    function setProductImage(container, product) {
        if (product.image) {
            container.html(`<img src="${product.image}" class="rounded product-thumbnail" style="width: 40px; height: 40px; object-fit: cover;" alt="${product.name || ''}" />`);
        } else {
            container.html(`<div class="rounded bg-label-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="ti ti-photo text-muted" style="font-size: 1.25rem;"></i></div>`);
        }
    }
    @php
        $mappedProducts = $products->map(function($p) {
            $data = [
                'id' => $p->id,
                'name' => $p->name,
                'barcode' => $p->barcode,
                'type' => $p->type,
                'purchase_price' => $p->purchase_price,
                'image' => $p->primary_image_url,
                'pair_product' => (bool) $p->pair_product,
                'pair_mode' => $p->pair_mode,
                'custom_sizes' => $p->custom_sizes ?? [],
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
                        'custom_sizes' => $v->custom_sizes ?? [],
                        'attr_name' => $v->attributeValue->attribute->name ?? '',
                        'value_name' => $v->attributeValue->value ?? '',
                    ];
                })->all();
            }

            // Calculate stock by location
            $stockByLocation = [];
            if ($p->type === 'variable') {
                $variantStock = $p->getVariantStock();
                foreach ($variantStock as $locId => $locData) {
                    $stockByLocation[$locId] = [
                        'parent' => $locData['parent'],
                        'variants' => $locData['variants']
                    ];
                }
            } else {
                foreach ($p->inventories as $inv) {
                    $stockByLocation[$inv->location_id] = $inv->quantity;
                }
            }
            $data['stock_by_location'] = $stockByLocation;

            return $data;
        })->values()->all();
    @endphp
    const allProducts = @json($mappedProducts);
    const locations = @json($locations->map(fn ($l) => ['id' => $l->id, 'name' => $l->name])->values());
    updateGrandTotal();

    // Show/hide the "Amount Paid" input based on selected Payment Status
    function togglePaidAmountInput() {
        const isPartial = $('#paymentStatusSelect').val() === '3';
        $('#paidAmountWrapper').toggleClass('d-none', !isPartial);
        $('#paidAmountInput').prop('required', isPartial);
        $('#paidAmountInput').prop('disabled', !isPartial);
    }
    $(document).on('change', '#paymentStatusSelect', togglePaidAmountInput);
    togglePaidAmountInput();

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
            (p.barcode && String(p.barcode).toLowerCase() === q)
        );
        return matches.length === 1 ? matches[0] : null;
    }

    function hasUnselectedVariableProduct() {
        let unselected = false;
        $('.item-row').each(function () {
            const row = $(this);
            const product = row.data('product');
            if (product && product.type === 'variable') {
                const variantVal = row.find('.variant-select').val();
                if (!variantVal || variantVal === '') {
                    unselected = true;
                }
            }
        });
        return unselected;
    }

    function selectSearchProduct(product) {
        if (hasUnselectedVariableProduct()) {
            toastr.warning('Please select a variant for the existing variable product before scanning or adding another product.');
            searchInput.val('');
            searchResults.hide().empty();
            return;
        }

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

    let scanMatchTimer = null;

    searchInput.on('keydown', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            if (scanMatchTimer) { clearTimeout(scanMatchTimer); scanMatchTimer = null; }
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

        if (scanMatchTimer) { clearTimeout(scanMatchTimer); scanMatchTimer = null; }

        if (query.length === 0) {
            searchResults.hide();
            return;
        }

        // Barcode scanners (that don't send an Enter/Tab terminator) fire an
        // input event per character. Wait briefly for the burst to finish
        // before treating an exact match as a completed scan, so a shorter
        // barcode that is a prefix of the one being scanned isn't added by mistake.
        scanMatchTimer = setTimeout(function() {
            if (searchInput.val().toLowerCase().trim() !== query) return;
            const exactMatch = findExactProductMatch(query);
            if (exactMatch) selectSearchProduct(exactMatch);
        }, 100);

        const matchedProducts = allProducts.filter(p =>
            p.name.toLowerCase().includes(query) ||
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
                            <small class="text-muted">Barcode: ${p.barcode}</small>
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

    // A variant with its own pack-size list overrides the product's shared list.
    function getEffectiveCustomSizes(product, variantId) {
        if (variantId && product.variants) {
            const variant = product.variants.find(v => v.id == variantId);
            if (variant && variant.custom_sizes && variant.custom_sizes.length) {
                return variant.custom_sizes;
            }
        }
        return product.custom_sizes || [];
    }

    function maxSizeOf(sizes) {
        return sizes.reduce((max, cs) => (cs.size > max ? cs.size : max), sizes[0].size);
    }

    function addItemRow(product, selectedVariantId = null, qty = 1, price = null, discountType = 'flat', discountValue = 0, selectedCustomSize = null) {
        const template = document.getElementById('itemRowTemplate').innerHTML
            .replaceAll('__INDEX__', itemIndex);

        $('#itemsBody').append(template);
        $('#noItemsMsg').addClass('d-none');
        $('#itemsTable').removeClass('d-none');

        const row = $('#itemsBody .item-row').last();
        row.find('.product-id-input').val(product.id);
        row.find('.product-name-display').text(product.name);
        setProductImage(row.find('.product-image-container'), product);

        row.data('product', product);
        row.data('index', itemIndex);

        if (product.pair_product) {
            row.find('.pair-product-badge').removeClass('d-none');
        }

        if (product.type === 'variable') {
            // Build variant select dropdown
            let selectHtml = `<select class="form-select form-select-sm variant-select mt-2 no-select2">`;
            selectHtml += `<option value="" disabled ${!selectedVariantId ? 'selected' : ''}>-- Select Variant --</option>`;
            product.variants.forEach(v => {
                const optPrice = v.purchase_price != null ? v.purchase_price : 0;
                const selected = selectedVariantId && selectedVariantId == v.id ? 'selected' : '';
                selectHtml += `<option value="${v.id}" data-price="${optPrice}" ${selected}>${v.attr_name}: ${v.value_name} (${symbol}${optPrice})</option>`;
            });
            selectHtml += `</select>`;
            row.find('.variant-select-container').html(selectHtml);

            // Set initial variant
            const selectedOpt = row.find('.variant-select option:selected');
            const initialVariantId = selectedOpt.val() || '';
            const initialPrice = price != null ? price : (selectedOpt.data('price') || 0);

            row.attr('data-variant-id', initialVariantId);
            row.data('variant-id', initialVariantId);
            row.find('.purchase-price').val(initialPrice);
            row.find('.product-sku-display').text('Barcode: ' + product.barcode);

            const variantText = selectedOpt.text().split(' (')[0];
            row.data('product-name', product.name + (initialVariantId ? ' (' + variantText + ')' : ''));
        } else {
            row.find('.product-sku-display').text('Barcode: ' + product.barcode);
            row.find('.purchase-price').val(price != null ? price : (product.purchase_price != null ? product.purchase_price : 0));
            row.data('product-name', product.name);
        }

        if (product.pair_product) {
            const effectiveSizes = getEffectiveCustomSizes(product, row.data('variant-id'));
            if (effectiveSizes && effectiveSizes.length) {
                row.data('custom-size-value', selectedCustomSize || maxSizeOf(effectiveSizes));
            } else {
                row.data('custom-size-value', 2);
            }
        }

        row.find('.item-qty').val(qty);
        row.find('.item-discount-type').val(discountType);
        row.find('.item-discount-value').val(discountValue);

        updateRowTotal(row);
        updateStockInfo(row);

        itemIndex++;
        updateGrandTotal();
    }

    $(document).on('change', '.variant-select', function() {
        const row = $(this).closest('.item-row');
        const product = row.data('product');
        const selectedOpt = $(this).find('option:selected');
        const variantId = selectedOpt.val();
        const price = selectedOpt.data('price') || 0;
        
        row.attr('data-variant-id', variantId);
        row.data('variant-id', variantId);
        row.find('.purchase-price').val(price);

        if (product && product.pair_product) {
            const effectiveSizes = getEffectiveCustomSizes(product, variantId);
            if (effectiveSizes && effectiveSizes.length) {
                row.data('custom-size-value', maxSizeOf(effectiveSizes));
            } else {
                row.data('custom-size-value', 2);
            }
        }

        const variantText = selectedOpt.text().split(' (')[0];
        row.data('product-name', product.name + ' (' + variantText + ')');

        updateRowTotal(row);
        updateStockInfo(row);
        updateGrandTotal();
    });

    // Remove Item Row
    $(document).on('click', '.remove-item-btn', function () {
        const row = $(this).closest('.item-row');
        const idx = row.data('index');
        row.remove();

        if ($('#itemsBody .item-row').length === 0) {
            $('#noItemsMsg').removeClass('d-none');
            $('#itemsTable').addClass('d-none');
        }
        updateGrandTotal();
    });

    // Remove Variant Row
    $(document).on('click', '.remove-variant-btn', function () {
        const row = $(this).closest('.variant-row');
        const idx = row.data('index');
        const parentId = row.data('parent-id');
        row.remove();

        if ($('#itemsBody .item-row').length === 0) {
            $('#noItemsMsg').removeClass('d-none');
            $('#itemsTable').addClass('d-none');
        }
        updateParentTotal(parentId);
        updateGrandTotal();
    });

    // -------------------------------------------------------
    // Price / Qty / Discount change
    // -------------------------------------------------------
    $(document).on('input', '.purchase-price', function () {
        updateRowTotal($(this).closest('.item-row'));
    });

    $(document).on('input', '.item-qty', function () {
        const row = $(this).closest('.item-row');
        updateRowTotal(row);
    });

    $(document).on('change', '.item-discount-type', function () {
        const row = $(this).closest('.item-row');
        const discValueInput = row.find('.item-discount-value');
        if ($(this).val() === 'percentage' && parseFloat(discValueInput.val()) > 100) {
            discValueInput.val(100);
        }
        updateGrandTotal();
    });

    $(document).on('input', '.item-discount-value', function () {
        const row = $(this).closest('.item-row');
        const discType = row.find('.item-discount-type').val();
        if (discType === 'percentage' && parseFloat($(this).val()) > 100) {
            $(this).val(100);
        }
        updateGrandTotal();
    });

    $(document).on('change', '#orderDiscountTypeSelect', function () {
        const valInput = $('#orderDiscountValueInput');
        if ($(this).val() === 'percentage' && parseFloat(valInput.val()) > 100) {
            valInput.val(100);
            $('#overallDiscountValue').val(100);
        }
        $('#overallDiscountType').val($(this).val());
        updateGrandTotal();
    });

    $(document).on('input', '#orderDiscountValueInput', function () {
        const discType = $('#orderDiscountTypeSelect').val();
        if (discType === 'percentage' && parseFloat($(this).val()) > 100) {
            $(this).val(100);
        }
        $('#overallDiscountValue').val($(this).val());
        updateGrandTotal();
    });

    $(document).on('change', '#supplier_select', function () {
        updateGrandTotal();
    });

    $(document).on('change', '#is_gst_switch', function () {
        updateGrandTotal();
    });

    function updateRowTotal(row) {
        updateGrandTotal();
    }

    function updateGrandTotal() {
        let subtotalSum = 0;
        let discountSum = 0;
        let count = 0;

        $('.item-row').each(function () {
            const qty = parseInt($(this).find('.item-qty').val()) || 0;
            if (qty <= 0) return;

            const price = parseFloat($(this).find('.purchase-price').val()) || 0;
            const discVal = parseFloat($(this).find('.item-discount-value').val()) || 0;
            const discType = $(this).find('.item-discount-type').val() || 'flat';

            const subtotal = price * qty;
            let discount = 0;
            if (discType === 'flat') {
                discount = discVal;
            } else if (discType === 'percentage') {
                discount = subtotal * (discVal / 100);
            }

            if (discount > subtotal) discount = subtotal;

            const itemTotal = subtotal - discount;
            $(this).find('.item-total').text(symbol + ' ' + formatPrice(itemTotal));

            subtotalSum += subtotal;
            discountSum += discount;
            count++;
        });

        const itemsTotal = subtotalSum - discountSum;

        const orderDiscType = $('#overallDiscountType').val() || 'flat';
        const orderDiscVal = parseFloat($('#overallDiscountValue').val()) || 0;
        let orderDiscountAmount = 0;

        if (orderDiscVal > 0) {
            if (orderDiscType === 'flat') {
                orderDiscountAmount = orderDiscVal;
            } else if (orderDiscType === 'percentage') {
                orderDiscountAmount = itemsTotal * (orderDiscVal / 100);
            }
        }

        if (orderDiscountAmount > itemsTotal) {
            orderDiscountAmount = itemsTotal;
        }

        const finalAmount = itemsTotal - orderDiscountAmount;
        const totalDiscount = discountSum + orderDiscountAmount;

        const isGst = $('#is_gst_switch').is(':checked');
        const storeState = @json(strtolower(trim(\App\Models\Setting::getValue('store_state', 'gujarat'))));
        const gstRate = @json(\App\Models\Setting::getValue('purchase_gst_rate', 3));
        let taxAmount = 0;
        
        if (isGst) {
            if (storeState === 'gujarat') {
                const halfRate = gstRate / 2;
                const cgst = finalAmount * (halfRate / 100);
                const sgst = finalAmount * (halfRate / 100);
                taxAmount = cgst + sgst;

                $('#summaryCGSTLabel').text('CGST (' + halfRate + '%)');
                $('#summaryCGSTAmount').text(symbol + ' ' + formatPrice(cgst));
                $('#summarySGSTLabel').text('SGST (' + halfRate + '%)');
                $('#summarySGSTAmount').text(symbol + ' ' + formatPrice(sgst));

                $('#summaryCGSTRow').removeClass('d-none');
                $('#summarySGSTRow').removeClass('d-none');
                $('#summaryIGSTRow').addClass('d-none');
            } else {
                const igst = finalAmount * (gstRate / 100);
                taxAmount = igst;

                $('#summaryIGSTLabel').text('IGST (' + gstRate + '%)');
                $('#summaryIGSTAmount').text(symbol + ' ' + formatPrice(igst));

                $('#summaryCGSTRow').addClass('d-none');
                $('#summarySGSTRow').addClass('d-none');
                $('#summaryIGSTRow').removeClass('d-none');
            }
        } else {
            $('#summaryCGSTRow').addClass('d-none');
            $('#summarySGSTRow').addClass('d-none');
            $('#summaryIGSTRow').addClass('d-none');
        }

        const grandTotalAmount = Math.round(finalAmount + taxAmount);

        $('#grandTotal').text(symbol + ' ' + formatPriceNoDecimals(grandTotalAmount));
        $('#summaryItems').text(count);
        $('#summaryItemsTotal').text(symbol + ' ' + formatPrice(subtotalSum));
        $('#summaryDiscountAmount').text(symbol + ' ' + formatPrice(totalDiscount));
        $('#summaryTotal').text(symbol + ' ' + formatPriceNoDecimals(grandTotalAmount));

        if (totalDiscount > 0) {
            $('#summaryDiscountRow').removeClass('d-none');
        } else {
            $('#summaryDiscountRow').addClass('d-none');
        }

        if (count > 0) {
            $('#grandTotal').closest('tr').show();
            $('#taxColumn').show();
            $('#summaryColumn').show();
            $('#discountColumn').show();
        } else {
            $('#grandTotal').closest('tr').hide();
            $('#taxColumn').hide();
            $('#summaryColumn').hide();
            $('#discountColumn').hide();
        }
    }

    function updateStockInfo(row) {
        const productId = row.find('.product-id-input').val();
        const stockDisplay = row.find('.stock-info-display');
        const variantId = row.attr('data-variant-id') || row.data('variant-id');

        if (!productId) {
            stockDisplay.text('').removeAttr('title').css('cursor', '').removeClass('bg-label-success bg-label-danger bg-label-warning text-success text-danger text-warning').hide();
            return;
        }

        const product = allProducts.find(p => p.id == productId);
        if (!product) {
            stockDisplay.text('').removeAttr('title').css('cursor', '').removeClass('bg-label-success bg-label-danger bg-label-warning text-success text-danger text-warning').hide();
            return;
        }

        let qty = 0;
        let breakdownText = 'Stock Breakdown:\n';
        let hasStock = false;

        if (product.type === 'variable') {
            Object.keys(product.stock_by_location || {}).forEach(locId => {
                const locStockData = product.stock_by_location[locId];
                const loc = locations.find(l => l.id == locId);
                const locName = loc ? loc.name : 'Unknown';
                let lQty = 0;
                if (variantId === 'parent') {
                    lQty = locStockData.parent ?? 0;
                } else if (variantId) {
                    lQty = locStockData.variants?.[variantId] ?? 0;
                }
                qty += lQty;
                if (lQty > 0) {
                    breakdownText += `- ${locName}: ${lQty}\n`;
                    hasStock = true;
                }
            });
        } else {
            Object.keys(product.stock_by_location || {}).forEach(locId => {
                const lQty = product.stock_by_location[locId] ?? 0;
                const loc = locations.find(l => l.id == locId);
                const locName = loc ? loc.name : 'Unknown';
                qty += lQty;
                if (lQty > 0) {
                    breakdownText += `- ${locName}: ${lQty}\n`;
                    hasStock = true;
                }
            });
        }

        if (!hasStock) {
            breakdownText += 'No stock in any branch';
        }

        let stockLabelText = 'Out of Stock';
        if (qty > 0) {
            let formattedQty = qty;
            if (product && product.pair_product) {
                const effectiveSizes = getEffectiveCustomSizes(product, variantId);
                let pairSize = 0;
                if (effectiveSizes && effectiveSizes.length > 0) {
                    const sizes = effectiveSizes.map(s => typeof s === 'object' && s !== null ? parseFloat(s.size) : parseFloat(s)).filter(s => s > 0);
                    if (sizes.length > 0) pairSize = Math.max(...sizes);
                }
                if (!pairSize) pairSize = 1;

                const pairsCount = Math.floor(qty / pairSize);
                const remPcs = qty % pairSize;
                let parts = [];
                if (pairsCount > 0) parts.push(pairsCount + (pairsCount > 1 ? ' Pairs' : ' Pair'));
                if (remPcs > 0) parts.push(remPcs + ' Pcs');
                formattedQty = parts.length > 0 ? parts.join(', ') : '0';
            }
            stockLabelText = 'Stock: ' + formattedQty;
        }

        stockDisplay
            .text(stockLabelText)
            .attr('title', breakdownText.trim())
            .css('cursor', 'help')
            .removeClass('bg-label-success bg-label-danger bg-label-warning text-success text-danger text-warning')
            .addClass(qty > 0 ? (qty < 10 ? 'bg-label-warning' : 'bg-label-success') : 'bg-label-danger')
            .show();
    }

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

        let variantMissing = false;
        $('.item-row').each(function () {
            const row = $(this);
            const qty = parseInt(row.find('.item-qty').val()) || 0;
            if (qty <= 0) return;
            const product = row.data('product');
            if (product && product.type === 'variable') {
                const vVal = row.find('.variant-select').val();
                if (!vVal) {
                    variantMissing = true;
                    row.find('.variant-select').addClass('is-invalid');
                }
            }
        });
        if (variantMissing) {
            toastr.error('Please select a variant for each variable product before saving.');
            return;
        }

        const form = $(this);
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.select2-container .select2-selection').css('border-color', '');
        form.find('.invalid-feedback').text('').hide();

        $('#hiddenSubmitContainer').remove();

        const hiddenContainer = $('<div id="hiddenSubmitContainer" style="display: none;"></div>');
        form.append(hiddenContainer);

        const visibleInputs = $('#itemsTable').find('input, select');
        visibleInputs.prop('disabled', true);

        let submitIdx = 0;
        $('.item-row').each(function() {
            const row = $(this);
            const product = row.data('product');
            const qty = parseInt(row.find('.item-qty').val()) || 0;
            if (qty <= 0) return;

            const variantId = row.data('variant-id') || '';
            let customSizeValue = row.data('custom-size-value') || '';
            if (product && product.pair_product && !customSizeValue) {
                const effectiveSizes = getEffectiveCustomSizes(product, variantId);
                if (effectiveSizes && effectiveSizes.length) {
                    customSizeValue = maxSizeOf(effectiveSizes);
                } else {
                    customSizeValue = 2;
                }
            }
            const purchasePrice = parseFloat(row.find('.purchase-price').val()) || 0;
            const discountType = row.find('.item-discount-type').val() || 'flat';
            const discountValue = parseFloat(row.find('.item-discount-value').val()) || 0;

            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][product_id]" value="${product.id}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][product_variant_id]" value="${variantId}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][custom_size_value]" value="${customSizeValue}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][quantity]" value="${qty}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][purchase_price]" value="${purchasePrice}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][discount_type]" value="${discountType}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][discount_value]" value="${discountValue}">`);
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
                    if (typeof errors === 'string') {
                        toastr.error(errors);
                    } else {
                        $.each(errors, function (field, messages) {
                            if (field === 'is_gst') {
                                $('#is_gst_switch').prop('checked', false).addClass('is-invalid');
                                toastr.error(messages[0]);
                                const supplierId = $('#supplier_select').val();
                                if (supplierId) {
                                    pendingGstFixSupplierId = supplierId;
                                    window.openCommonModal(supplierEditUrlTemplate.replace('__ID__', supplierId));
                                }
                                return;
                            }
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
                    }
                } else {
                    toastr.error('Something went wrong. Please try again.');
                }
            }
        });
    });

});
</script>
@endsection
