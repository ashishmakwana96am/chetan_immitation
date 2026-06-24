@extends('layouts.app')

@section('title', 'New Sale')

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

            <!-- Main -->
            <div class="col-lg-8">

                <!-- Sale Details -->
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
                            <div class="col-md-6">
                                <label class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="text" name="date" class="form-control flatpickr" value="{{ date('Y-m-d') }}" placeholder="DD-MM-YYYY" />
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sale Items -->
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
                                        <th width="25%">Product</th>
                                        <th width="15%">Qty</th>
                                        <th width="20%">Price</th>
                                        <th width="20%">Discount</th>
                                        <th width="15%">Total</th>
                                        <th width="5%"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody"></tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="4" class="text-end fw-semibold">Items Total</td>
                                        <td class="fw-bold text-primary" id="itemsTotal">{{ currency_symbol() }} 0.00</td>
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

            <!-- Sidebar -->
            <div class="col-lg-4">

                <!-- Sale Summary -->
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

                <input type="hidden" id="overallDiscountType" name="discount_type" value="flat" />
                <input type="hidden" id="overallDiscountValue" name="discount_value" value="0" />

                <!-- Sales Status -->
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Sales Status</h5></div>
                    <div class="card-body">
                        <select name="status" class="form-select no-select2">
                            <option value="1">Pending</option>
                            <option value="2" selected>Approve</option>
                            <option value="3">Decline</option>
                        </select>
                    </div>
                </div>

                <!-- Payment Status -->
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Payment Status</h5></div>
                    <div class="card-body">
                        <select name="payment_status" class="form-select no-select2">
                            <option value="1" selected>Pending</option>
                            <option value="2">Paid</option>
                        </select>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="ti ti-device-floppy me-1"></i> Save Sale
                    </button>
                    <a href="{{ route('admin.sales.index') }}" class="btn btn-label-secondary">Cancel</a>
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
                    placeholder="1" min="1" value="1" />
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">{{ currency_symbol() }}</span>
                    <input type="number" name="items[__INDEX__][price]"
                        class="form-control form-control-sm item-price"
                        placeholder="0.00" step="0.01" min="0" value="0" />
                </div>
            </td>
            <td>
                <div class="input-group input-group-sm flex-nowrap" style="min-width: 140px;">
                    <select name="items[__INDEX__][discount_type]" class="form-select form-select-sm item-discount-type no-select2" style="width: 75px; flex-shrink: 0; flex-grow: 0; padding-left: 8px; padding-right: 18px; background-position: right 4px center;">
                        <option value="flat">Flat</option>
                        <option value="percentage">%</option>
                    </select>
                    <input type="number" name="items[__INDEX__][discount_value]"
                        class="form-control form-control-sm item-discount-value"
                        placeholder="0" min="0" step="0.01" value="0" />
                </div>
            </td>
            <td>
                <span class="item-total fw-semibold text-nowrap">{{ currency_symbol() }} 0.00</span>
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
                    placeholder="0" min="0" value="1" />
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">{{ currency_symbol() }}</span>
                    <input type="number" name="items[__INDEX__][price]"
                        class="form-control form-control-sm item-price"
                        placeholder="0.00" step="0.01" min="0" value="0" />
                </div>
            </td>
            <td>
                <div class="input-group input-group-sm flex-nowrap" style="min-width: 140px;">
                    <select name="items[__INDEX__][discount_type]" class="form-select form-select-sm item-discount-type no-select2" style="width: 75px; flex-shrink: 0; flex-grow: 0; padding-left: 8px; padding-right: 18px; background-position: right 4px center;">
                        <option value="flat">Flat</option>
                        <option value="percentage">%</option>
                    </select>
                    <input type="number" name="items[__INDEX__][discount_value]"
                        class="form-control form-control-sm item-discount-value"
                        placeholder="0" min="0" step="0.01" value="0" />
                </div>
            </td>
            <td>
                <span class="parent-total fw-semibold text-nowrap">{{ currency_symbol() }} 0.00</span>
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
                    <input type="number" name="items[__INDEX__][price]"
                        class="form-control form-control-sm item-price"
                        placeholder="0.00" step="0.01" min="0" value="0" />
                </div>
            </td>
            <td>
                <div class="input-group input-group-sm flex-nowrap" style="min-width: 140px;">
                    <select name="items[__INDEX__][discount_type]" class="form-select form-select-sm item-discount-type no-select2" style="width: 75px; flex-shrink: 0; flex-grow: 0; padding-left: 8px; padding-right: 18px; background-position: right 4px center;">
                        <option value="flat">Flat</option>
                        <option value="percentage">%</option>
                    </select>
                    <input type="number" name="items[__INDEX__][discount_value]"
                        class="form-control form-control-sm item-discount-value"
                        placeholder="0" min="0" step="0.01" value="0" />
                </div>
            </td>
            <td>
                <span class="item-total fw-semibold text-nowrap">{{ currency_symbol() }} 0.00</span>
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
            const item = $(`
                <a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center search-result-item bg-white" style="background-color: #ffffff;" data-id="${p.id}">
                    <div>
                        <div class="fw-semibold">${p.name}</div>
                        <small class="text-muted">SKU: ${p.sku}${p.barcode ? ' | Barcode: ' + p.barcode : ''}</small>
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
        $('.product-id-input').each(function() {
            if ($(this).val() == product.id) {
                exists = true;
            }
        });

        if (exists) {
            toastr.warning('Product is already in the list.');
        } else {
            addItemRow(product);
        }

        searchInput.val('');
        searchResults.hide().empty();
        searchInput.focus();
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
            parentRow.find('.item-price').val(product.price != null ? product.price : 0);
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
                    vRow.find('.item-price').val(v.sale_price != null ? v.sale_price : 0);
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
            row.find('.item-price').val(product.price != null ? product.price : 0);
            row.find('.item-qty').val(1);

            updateRowTotal(row);
            updateStockInfo(row);

            itemIndex++;
        }
        updateSummary();
    }

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

    // Remove Parent Product Row and all its variants
    $(document).on('click', '.remove-parent-btn', function () {
        const parentRow = $(this).closest('.parent-row');
        const productId = parentRow.data('product-id');
        
        $(`.variant-row[data-parent-id="${productId}"]`).remove();
        parentRow.remove();
        
        if ($('#itemsBody .item-row').length === 0) {
            $('#noItemsMsg').removeClass('d-none');
        }
        updateSummary();
    });

    // Remove single variant row
    $(document).on('click', '.remove-variant-btn', function () {
        const row = $(this).closest('.variant-row');
        row.remove();
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
        const stockDisplay = row.find('.stock-info-display');
        const variantId = row.attr('data-variant-id') || row.data('variant-id');

        if (!productId || !locationId) {
            stockDisplay.text('').removeAttr('title').css('cursor', '');
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
                    .text('Stock: ' + qty)
                    .attr('title', titleText.trim())
                    .css('cursor', 'help')
                    .removeClass('text-success text-danger')
                    .addClass(qty > 0 ? 'text-success' : 'text-danger');
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
            $('#summaryFinal').closest('.card').show();
        } else {
            $('#itemsTotal').closest('tr').hide();
            $('#summaryFinal').closest('.card').hide();
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

        const disabledInputs = [];
        $('.variant-row, .parent-row').each(function () {
            const qty = parseInt($(this).find('.item-qty').val()) || 0;
            if (qty <= 0) {
                $(this).find('input, select').each(function () {
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
                    setTimeout(() => window.location.href = '{{ route('admin.sales.index') }}', 800);
                }
            },
            error   : function (xhr) {
                disabledInputs.forEach(input => input.prop('disabled', false));
                $('#submitBtn').prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Save Sale');
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
                            let feedback = input.siblings('.invalid-feedback');
                            if (feedback.length === 0 && input.parent('.input-group').length) {
                                feedback = input.parent('.input-group').siblings('.invalid-feedback');
                            }
                            if (feedback.length) {
                                feedback.text(messages[0]);
                            } else {
                                toastr.error(messages[0]);
                            }
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
