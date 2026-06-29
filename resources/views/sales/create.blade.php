@extends('layouts.app')

@section('title', 'New Sale')

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
        width: 10% !important;
        min-width: 85px !important;
    }
    #itemsTable th:nth-child(3), #itemsTable td:nth-child(3) {
        width: 18% !important;
        min-width: 140px !important;
    }
    #itemsTable th:nth-child(4), #itemsTable td:nth-child(4) {
        width: 22% !important;
        min-width: 180px !important;
    }
    #itemsTable th:nth-child(5), #itemsTable td:nth-child(5) {
        width: 12% !important;
        min-width: 140px !important;
    }
    #itemsTable th:nth-child(6), #itemsTable td:nth-child(6) {
        width: 3% !important;
    }
    
    /* Make inputs look consistent and prevent clipping */
    #itemsTable .item-qty {
        border-radius: 0.375rem !important;
    }
    #itemsTable .input-group {
        flex-wrap: nowrap !important;
    }
</style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">New Sale</h4>
        <a href="{{ route('admin.sales.index') }}" class="btn btn-label-secondary">
            <i class="ti ti-arrow-left me-1"></i> Back
        </a>
    </div>

    <form id="orderForm" action="{{ route('admin.sales.store') }}" method="POST">
        @csrf
        <div class="row g-4">

            <!-- Sale Details (Full Width) -->
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Sale Details</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Sale No</label>
                                <input type="text" class="form-control" value="{{ $orderNo }}" disabled />
                                <small class="text-muted">Auto-generated on save</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Location <span class="text-danger">*</span></label>
                                @if(auth()->user()->location_id)
                                    <input type="hidden" name="location_id" value="{{ auth()->user()->location_id }}" />
                                    <input type="text" class="form-control" value="{{ $locations->firstWhere('id', auth()->user()->location_id)?->name ?? '-' }}" disabled />
                                @else
                                    <select name="location_id" class="form-select" id="locationSelect">
                                        <option value="">-- Select Location --</option>
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}"
                                                {{ auth()->user()->location_id == $location->id ? 'selected' : '' }}>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Customer</label>
                                <div class="input-group">
                                    <select name="customer_id" class="form-select" id="customerSelect">
                                        <option value="">-- Walk-in Customer --</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->phone ? ' - ' . $customer->phone : '' }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-label-primary"
                                        data-common-modal="{{ route('admin.customers.create') }}"
                                        data-bs-toggle="tooltip"
                                        title="Add Customer">
                                        <i class="ti ti-plus"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-select">
                                    <option value="cash">Cash</option>
                                    <option value="online">Online</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sale Items (Full Width) -->
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header border-bottom pb-3" style="z-index: 10;">
                        <h5 class="mb-3">Sale Items</h5>
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
                                        <th style="width: 35%;">Product</th>
                                        <th style="width: 15%;">Qty</th>
                                        <th style="width: 15%;">Price</th>
                                        <th style="width: 20%;">Discount</th>
                                        <th style="width: 12%;">Total</th>
                                        <th style="width: 3%;"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody"></tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="4" class="text-end fw-semibold">Items Total</td>
                                        <td class="fw-bold text-primary text-nowrap" id="itemsTotal">{{ currency_symbol() }} 0.00</td>
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

            <!-- Bottom widgets: Summary (col-md-4) -->
            <div class="col-md-4" id="summaryColumn">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Sale Summary</h5></div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Items Total</span>
                            <span id="summaryItemsTotal" class="fw-semibold">{{ currency_symbol() }} 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 d-none">
                            <span class="text-muted">Discount</span>
                            <span id="summaryDiscountAmount" class="fw-semibold text-danger">{{ currency_symbol() }} 0.00</span>
                        </div>
                        <hr />
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold">Final Amount</span>
                            <span id="summaryFinal" class="fw-bold text-primary fs-5">{{ currency_symbol() }} 0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales Status (col-md-4) -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Sales Status</h5></div>
                    <div class="card-body">
                        <select name="status" class="form-select no-select2">
                            <option value="1">Pending</option>
                            <option value="2" selected>Approve</option>
                            <option value="6">Decline</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Payment Status (col-md-4) -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Payment Status</h5></div>
                    <div class="card-body">
                        <select name="payment_status" class="form-select no-select2">
                            <option value="1" selected>Pending</option>
                            <option value="2">Paid</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Save and Cancel Buttons (col-12, 50% / 50% width) -->
            <div class="col-12 mt-3">
                <div class="row g-3">
                    <div class="col-6">
                        <button type="submit" class="btn btn-primary w-100 py-2 fs-5 fw-semibold" id="submitBtn">
                            <i class="ti ti-device-floppy me-1"></i> Save Sale
                        </button>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('admin.sales.index') }}" class="btn btn-label-secondary w-100 py-2 fs-5 fw-semibold d-flex align-items-center justify-content-center">Cancel</a>
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
                    <div class="product-image-container me-3"></div>
                    <div class="d-flex flex-column mb-1">
                        <span class="product-name-display fw-semibold text-heading"></span>
                        <small class="product-sku-display text-muted"></small>
                        <div class="variant-select-container"></div>
                        <div><span class="badge stock-display mt-1"></span></div>
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
                    <input type="number" name="items[__INDEX__][price]"
                        class="form-control item-price"
                        placeholder="0.00" step="0.01" min="0" value="0" />
                </div>
            </td>
            <td class="align-middle">
                <div class="input-group">
                    <select name="items[__INDEX__][discount_type]" class="form-select item-discount-type no-select2" style="max-width: 130px;">
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
    const symbol      = '{{ currency_symbol() }}';
    function formatPrice(val) {
        return parseFloat(val).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    const allProducts = @json($allProducts);
    updateSummary();

    window.refreshTable = function () {
        $.get('{{ route('admin.customers.data') }}', function (res) {
            const select = $('#customerSelect');
            const current = select.val();
            select.find('option:not(:first)').remove();
            res.data.forEach(function (c) {
                select.append($('<option>', { value: c.id, text: c.name + (c.phone !== '-' ? ' - ' + c.phone : '') }));
            });
            select.val(current);
        });
    };

    // -------------------------------------------------------
    // Product Search and Selection
    // -------------------------------------------------------
    const searchInput = $('#productSearchInput');
    const searchResults = $('#productSearchResults');

    searchInput.on('keydown', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
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

        const matchedProducts = allProducts.filter(p => 
            p.label.toLowerCase().includes(query) ||
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
                : '<span class="badge bg-label-primary">' + symbol + ' ' + formatPrice(p.price) + '</span>';
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

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#productSearchInput, #productSearchResults').length) {
            searchResults.hide();
        }
    });

    $(document).on('click', '.search-result-item', function() {
        const product = $(this).data('product');
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
        } else {
            addItemRow(product);
        }

        searchInput.val('');
        searchResults.hide().empty();
        searchInput.focus();
    });

    function addItemRow(product, selectedVariantId = null, qty = 1, price = null, discountType = 'flat', discountValue = 0) {
        const template = document.getElementById('itemRowTemplate').innerHTML
            .replaceAll('__INDEX__', itemIndex);

        $('#itemsBody').append(template);
        $('#noItemsMsg').addClass('d-none');

        const row = $('#itemsBody .item-row').last();
        row.find('.product-id-input').val(product.id);
        row.find('.product-name-display').text(product.name);
        
        const rowImgHtml = product.image 
            ? `<img src="${product.image}" class="rounded" style="width: 40px; height: 40px; object-fit: cover;" alt="${product.name}" />`
            : `<div class="rounded bg-label-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;"><i class="ti ti-photo text-secondary fs-4"></i></div>`;
        row.find('.product-image-container').html(rowImgHtml);
        
        row.data('product', product);
        row.data('index', itemIndex);

        if (product.type === 'variable') {
            // Build variant select dropdown
            let selectHtml = `<select class="form-select form-select-sm variant-select mt-2 no-select2">`;
            product.variants.forEach(v => {
                const optPrice = v.sale_price != null ? v.sale_price : 0;
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
            row.find('.item-price').val(initialPrice);
            row.find('.product-sku-display').text('SKU: ' + product.sku);
        } else {
            row.find('.product-sku-display').text('SKU: ' + product.sku);
            row.find('.item-price').val(price != null ? price : (product.price != null ? product.price : 0));
        }

        row.find('.item-qty').val(qty);
        row.find('.item-discount-type').val(discountType);
        row.find('.item-discount-value').val(discountValue);

        updateRowTotal(row);
        updateStockInfo(row);

        itemIndex++;
        updateSummary();
    }

    $(document).on('change', '.variant-select', function() {
        const row = $(this).closest('.item-row');
        const selectedOpt = $(this).find('option:selected');
        const variantId = selectedOpt.val();
        const price = selectedOpt.data('price');
        
        row.attr('data-variant-id', variantId);
        row.data('variant-id', variantId);
        row.find('.item-price').val(price);
        
        updateRowTotal(row);
        updateStockInfo(row);
        updateSummary();
    });

    // -------------------------------------------------------
    // Remove Item Row
    // -------------------------------------------------------
    $(document).on('click', '.remove-item-btn', function () {
        const row = $(this).closest('.item-row');
        row.remove();
        if ($('#itemsBody .item-row').length === 0) {
            $('#noItemsMsg').removeClass('d-none');
        }
        updateSummary();
    });

    function getLocationId() {
        return $('#locationSelect').val() || $('input[name="location_id"]').val() || '';
    }

    // -------------------------------------------------------
    // Stock info display
    // -------------------------------------------------------
    $(document).on('change', '#locationSelect', function () {
        $('#itemsBody .item-row').each(function () {
            updateStockInfo($(this));
        });
    });

    function updateStockInfo(row) {
        const productId  = row.find('.product-id-input').val();
        const locationId = getLocationId();
        const stockDisplay = row.find('.stock-display');
        const variantId = row.attr('data-variant-id') || row.data('variant-id');

        if (!productId) {
            stockDisplay.text('').removeAttr('title').css('cursor', '').hide();
            return;
        }

        $.get('{{ route('admin.inventory.stock') }}', { product_id: productId, location_id: locationId, variant_id: variantId })
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
                    .addClass(qty > 0 ? (qty < 10 ? 'bg-label-warning' : 'bg-label-success') : 'bg-label-danger')
                    .show();
            });
    }

    // -------------------------------------------------------
    // Price / Qty / Discount change
    // -------------------------------------------------------
    $(document).on('input change', '.item-price, .item-qty, .item-discount-value, .item-discount-type, #overallDiscountValue, #overallDiscountType', function () {
        const row = $(this).closest('.item-row');
        if (row.length > 0) {
            updateRowTotal(row);
        } else {
            updateSummary();
        }
    });

    function updateRowTotal(row) {
        const price    = parseFloat(row.find('.item-price').val()) || 0;
        const qty      = parseInt(row.find('.item-qty').val()) || 0;
        const discVal  = parseFloat(row.find('.item-discount-value').val()) || 0;
        const discType = row.find('.item-discount-type').val() || 'flat';

        const subtotal = price * qty;
        let discount = 0;
        if (discType === 'flat') {
            discount = discVal;
        } else if (discType === 'percentage') {
            discount = subtotal * (discVal / 100);
        }

        if (discount > subtotal) discount = subtotal;

        const total    = subtotal - discount;
        if (row.hasClass('parent-row')) {
            row.find('.parent-total').text(symbol + ' ' + formatPrice(total));
        } else {
            row.find('.item-total').text(symbol + ' ' + formatPrice(total));
        }
        updateSummary();
    }

    function updateSummary() {
        let subtotalSum = 0;
        let discountSum = 0;
        let count       = 0;
        $('#itemsBody .item-row').each(function () {
            const qty      = parseInt($(this).find('.item-qty').val()) || 0;
            if (qty <= 0) return;

            const price    = parseFloat($(this).find('.item-price').val()) || 0;
            const discVal  = parseFloat($(this).find('.item-discount-value').val()) || 0;
            const discType = $(this).find('.item-discount-type').val() || 'flat';

            const subtotal = price * qty;
            let discount = 0;
            if (discType === 'flat') {
                discount = discVal;
            } else if (discType === 'percentage') {
                discount = subtotal * (discVal / 100);
            }

            if (discount > subtotal) discount = subtotal;

            subtotalSum += subtotal;
            discountSum += discount;
            count++;
        });

        const finalAmount = subtotalSum - discountSum;

        $('#itemsTotal').text(symbol + ' ' + formatPrice(finalAmount));
        $('#summaryItemsTotal').text(symbol + ' ' + formatPrice(subtotalSum));
        $('#summaryDiscountAmount').text(symbol + ' ' + formatPrice(discountSum));
        $('#summaryFinal').text(symbol + ' ' + formatPrice(finalAmount));

        if (discountSum > 0) {
            $('#summaryDiscountAmount').closest('.d-flex').removeClass('d-none');
        } else {
            $('#summaryDiscountAmount').closest('.d-flex').addClass('d-none');
        }

        if (count > 0) {
            $('#itemsTotal').closest('tr').show();
            $('#summaryColumn').show();
        } else {
            $('#itemsTotal').closest('tr').hide();
            $('#summaryColumn').hide();
        }
    }

    // -------------------------------------------------------
    // Submit
    // -------------------------------------------------------
    $('#orderForm').on('submit', function (e) {
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

        const form = $(this);
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').text('');

        // Remove any previously appended hidden mapping container
        $('#hiddenSubmitContainer').remove();

        // Create a container for our mapped inputs
        const hiddenContainer = $('<div id="hiddenSubmitContainer" style="display: none;"></div>');
        form.append(hiddenContainer);

        // Disable all inputs in the visible table so they are NOT serialized
        const visibleInputs = $('#itemsTable').find('input, select');
        visibleInputs.prop('disabled', true);

        // Group UI rows by product ID to prepare the backend format
        const itemsByProduct = {};
        $('.item-row').each(function() {
            const row = $(this);
            const product = row.data('product');
            const qty = parseInt(row.find('.item-qty').val()) || 0;
            if (qty <= 0) return; // skip rows with 0 qty

            if (!itemsByProduct[product.id]) {
                itemsByProduct[product.id] = [];
            }
            itemsByProduct[product.id].push({
                row: row,
                product: product,
                qty: qty,
                variantId: row.data('variant-id'),
                price: parseFloat(row.find('.item-price').val()) || 0,
                discount_type: row.find('.item-discount-type').val() || 'flat',
                discount_value: parseFloat(row.find('.item-discount-value').val()) || 0
            });
        });

        let submitIdx = 0;

        Object.keys(itemsByProduct).forEach(productId => {
            const productItems = itemsByProduct[productId];
            const firstItem = productItems[0];
            const product = firstItem.product;

            if (product.type === 'variable') {
                // Variant Records (must be in the exact order of product.variants)
                product.variants.forEach(v => {
                    const matchedItems = productItems.filter(item => item.variantId == v.id);
                    let vQty = 0;
                    let vPrice = v.sale_price != null ? v.sale_price : 0;
                    let vDiscType = 'flat';
                    let vDiscVal = 0;

                    if (matchedItems.length > 0) {
                        matchedItems.forEach(item => {
                            vQty += item.qty;
                        });
                        vPrice = matchedItems[0].price;
                        vDiscType = matchedItems[0].discount_type;
                        vDiscVal = matchedItems[0].discount_value;
                    }

                    hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][product_id]" value="${product.id}" class="v-input" data-qty="${vQty}">`);
                    hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][product_variant_id]" value="${v.id}" class="v-input" data-qty="${vQty}">`);
                    hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][quantity]" value="${vQty}" class="v-input" data-qty="${vQty}">`);
                    hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][price]" value="${vPrice}" class="v-input" data-qty="${vQty}">`);
                    hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][discount_type]" value="${vDiscType}" class="v-input" data-qty="${vQty}">`);
                    hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][discount_value]" value="${vDiscVal}" class="v-input" data-qty="${vQty}">`);
                    submitIdx++;
                });
            } else {
                hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][product_id]" value="${product.id}">`);
                hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][product_variant_id]" value="">`);
                hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][quantity]" value="${firstItem.qty}">`);
                hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][price]" value="${firstItem.price}">`);
                hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][discount_type]" value="${firstItem.discount_type}">`);
                hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][discount_value]" value="${firstItem.discount_value}">`);
                submitIdx++;
            }
        });

        // Disable variant inputs that have quantity <= 0
        hiddenContainer.find('.v-input').each(function() {
            if (parseInt($(this).attr('data-qty')) <= 0) {
                $(this).prop('disabled', true);
            }
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
                    setTimeout(() => window.location.href = '{{ route('admin.sales.index') }}', 800);
                }
            },
            error   : function (xhr) {
                visibleInputs.prop('disabled', false);
                hiddenContainer.remove();
                $('#submitBtn').prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Save Sale');
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.message || {};
                    $.each(errors, function (field, messages) {
                        toastr.error(messages[0]);
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
