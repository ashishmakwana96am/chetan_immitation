@extends('layouts.app')

@section('title', 'Edit Sale')

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
        min-width: 920px !important;
    }
    
    /* Column Width Alignments */
    #itemsTable th:nth-child(1), #itemsTable td:nth-child(1) {
        width: 26% !important;
    }
    #itemsTable th:nth-child(2), #itemsTable td:nth-child(2) {
        width: 16% !important;
        min-width: 80px !important;
    }
    #itemsTable th:nth-child(3), #itemsTable td:nth-child(3) {
        width: 12% !important;
        min-width: 90px !important;
    }
    #itemsTable th:nth-child(4), #itemsTable td:nth-child(4) {
        width: 28% !important;
        min-width: 210px !important;
    }
    #itemsTable th:nth-child(5), #itemsTable td:nth-child(5) {
        width: 10% !important;
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
    #itemsTable .item-price-display {
        display: inline-block;
        min-width: 90px;
        text-align: right;
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
    #summaryColumn .d-flex.justify-content-between > span:last-child {
        overflow-wrap: anywhere;
        word-break: break-word;
        text-align: right;
        flex: 1 1 auto;
    }
    /* Pair type toggle */
    .pair-type-toggle { display: inline-flex; border-radius: 6px; overflow: hidden; border: 1.5px solid #B4771E; }
    .pair-type-toggle .pair-btn {
        padding: 3px 14px; font-size: .8rem; font-weight: 600; border: none; cursor: pointer;
        background: #fff; color: #B4771E; transition: background .15s, color .15s;
    }
    .pair-type-toggle .pair-btn + .pair-btn { border-left: 1.5px solid #B4771E; }
    .pair-type-toggle .pair-btn.active { background: #B4771E; color: #fff; }
</style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Edit Sale <code>{{ $order->order_no }}</code></h4>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.sales.show', $order) }}" class="btn btn-label-secondary">
                <i class="ti ti-eye me-1"></i> View
            </a>
            <a href="{{ route('admin.sales.index') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <form id="orderForm" action="{{ route('admin.sales.update', $order) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-3 compact-entry-layout">
            <div class="col-lg-8">
                <div class="row g-3">

            <!-- Sale Details (Full Width) -->
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Sale Details</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Sale No</label>
                                <input type="text" class="form-control" value="{{ $order->order_no }}" disabled />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Location <span class="text-danger">*</span></label>
                                @if(auth()->user()->location_id)
                                    <input type="hidden" name="location_id" value="{{ auth()->user()->location_id }}" />
                                    <input type="text" class="form-control" value="{{ $locations->firstWhere('id', auth()->user()->location_id)?->name ?? '-' }}" disabled />
                                @else
                                    <select name="location_id" class="form-select" id="locationSelect">
                                        <option value="">Select Location</option>
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}"
                                                {{ $order->location_id == $location->id ? 'selected' : (auth()->user()->location_id == $location->id && !$order->location_id ? 'selected' : '') }}>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Customer <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select name="customer_id" class="form-select" id="customerSelect">
                                        <option value=""></option>
                                        <option value="0" {{ is_null($order->customer_id) ? 'selected' : '' }}>Walk-in Customer</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ $order->customer_id === $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }}{{ $customer->phone ? ' - ' . $customer->phone : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-label-primary"
                                        data-common-modal="{{ route('admin.customers.create') }}"
                                        data-bs-toggle="tooltip"
                                        title="Add Customer">
                                        <i class="ti ti-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-select">
                                    <option value="cash"   {{ $order->payment_method === 'cash'   ? 'selected' : '' }}>Cash</option>
                                    <option value="online" {{ $order->payment_method === 'online' ? 'selected' : '' }}>Online</option>
                                </select>
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
                                <input type="text" id="productSearchInput" class="form-control" placeholder="Search product by name or barcode..." autocomplete="off">
                            </div>
                            <div id="productSearchResults" class="list-group position-absolute w-100 mt-1 bg-white" style="z-index: 9999; background-color: #ffffff; display: none; max-height: 250px; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 0.375rem;">
                                <!-- Search results will appear here -->
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0 {{ $order->items->isEmpty() ? 'd-none' : '' }}" id="itemsTable">
                                    <thead>
                                        <tr class="table-light">
                                            <th style="min-width: 250px;">Product</th>
                                            <th style="width: 100px; min-width: 100px;">Qty</th>
                                            <th style="width: 120px; min-width: 120px;">Price</th>
                                            <th style="width: 200px; min-width: 200px;">Discount</th>
                                            <th style="width: 120px; min-width: 120px;">Total</th>
                                            <th style="width: 44px;"></th>
                                        </tr>
                                    </thead>
                                <tbody id="itemsBody"></tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="4" class="text-end fw-semibold">Items Total</td>
                                        <td class="fw-bold text-primary text-nowrap" id="itemsTotal">{{ format_price($order->final_amount) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div id="noItemsMsg" class="text-center text-muted py-4">No items added yet.</div>
                    </div>
                </div>
            </div>

            <input type="hidden" id="overallDiscountType" name="order_discount_type" value="{{ $order->order_discount_type ?? 'flat' }}" />
            <input type="hidden" id="overallDiscountValue" name="order_discount_value" value="{{ $order->order_discount_value ?? 0 }}" />
                </div>
            </div>

            <div class="col-lg-4">
                <div class="row g-3">

                    <!-- Discount on order -->
                    <div class="col-12" id="discountColumn">
                        <div class="card mb-4">
                            <div class="card-header"><h5 class="mb-0">Discount on order</h5></div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <select id="orderDiscountTypeSelect" class="form-select no-select2">
                                            <option value="flat" {{ ($order->order_discount_type ?? 'flat') === 'flat' ? 'selected' : '' }}>Flat</option>
                                            <option value="percentage" {{ ($order->order_discount_type ?? 'flat') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <input type="number" id="orderDiscountValueInput" class="form-control" value="{{ (float) ($order->order_discount_value ?? 0) }}" min="0" step="0.01" placeholder="Enter Discount" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom widgets: Summary -->
                    <div class="col-12" id="summaryColumn">
                        <div class="card mb-4">
                            <div class="card-header"><h5 class="mb-0">Sale Summary</h5></div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Items Total</span>
                                    <span id="summaryItemsTotal" class="fw-semibold">{{ format_price($order->final_amount) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3 d-none">
                                    <span class="text-muted">Discount</span>
                                    <span id="summaryDiscountAmount" class="fw-semibold text-danger">0.00</span>
                                </div>
                                <hr />
                                <div class="d-flex justify-content-between">
                                    <span class="fw-semibold">Final Amount</span>
                                    <span id="summaryFinal" class="fw-bold text-primary fs-5">{{ format_price($order->final_amount) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

            <!-- Sales Status -->
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Sales Status</h5></div>
                    <div class="card-body">
                        <select name="status" class="form-select no-select2">
                            <option value="1" {{ $order->status == 1 ? 'selected' : '' }}>Pending</option>
                            <option value="2" {{ $order->status == 2 ? 'selected' : '' }}>Approve</option>
                            <option value="6" {{ $order->status == 6 ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Payment Status -->
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Payment Status</h5></div>
                    <div class="card-body">
                        <select name="payment_status" class="form-select no-select2">
                            <option value="1" {{ ($order->payment_status ?? 1) == 1 ? 'selected' : '' }}>Pending</option>
                            <option value="2" {{ ($order->payment_status ?? 1) == 2 ? 'selected' : '' }}>Paid</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Save and Cancel Buttons -->
            <div class="col-12">
                <div class="row g-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                            <i class="ti ti-device-floppy me-1"></i> Update Sale
                        </button>
                    </div>
                    <div class="col-12">
                        <a href="{{ route('admin.sales.index') }}" class="btn btn-label-secondary w-100 d-flex align-items-center justify-content-center">Cancel</a>
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
                            <span class="badge stock-display text-nowrap"></span>
                        </div>
                        <div class="variant-select-container"></div>
                        <div class="pair-type-container mt-1"></div>
                    </div>
                </div>
                <input type="hidden" name="items[__INDEX__][product_id]" class="product-id-input" value="">
                <input type="hidden" name="items[__INDEX__][pair_type]" class="pair-type-input" value="single">
                <div class="invalid-feedback"></div>
            </td>
            <td class="align-middle">
                <input type="number" name="items[__INDEX__][quantity]"
                    class="form-control item-qty"
                    placeholder="1" min="1" value="1" />
            </td>
            <td class="align-middle">
                <span class="item-price-display fw-semibold text-nowrap">{{ currency_symbol() }} 0.00</span>
                <input type="hidden" name="items[__INDEX__][price]" class="item-price" value="0" />
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
    const symbol      = '{{ currency_symbol() }}';
    function formatPrice(val) {
        return parseFloat(val).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function setItemPrice(row, price) {
        const val = parseFloat(price) || 0;
        row.find('.item-price').val(val);
        row.find('.item-price-display').text(symbol + ' ' + formatPrice(val));
    }
    function getMinAllowedTotal(row) {
        if (row.data('bypass-min-price')) return 0;

        const qty = parseInt(row.find('.item-qty').val()) || 0;
        let purchasePrice = parseFloat(row.data('purchase-price')) || 0;
        const product = row.data('product');
        const isPair = row.find('.pair-type-input').val() === 'pair';

        if (product && product.pair_product && !isPair) {
            purchasePrice = purchasePrice / 2;
        }
        return purchasePrice > 0 ? qty * purchasePrice * 1.10 : 0;
    }
    function setProductImage(container, product) {
        if (product.image) {
            container.html(`<img src="${product.image}" class="rounded product-thumbnail" style="width: 40px; height: 40px; object-fit: cover;" alt="${product.name || ''}" />`);
        } else {
            container.html(`<div class="rounded bg-label-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="ti ti-photo text-muted" style="font-size: 1.25rem;"></i></div>`);
        }
    }
    const allProducts = @json($allProducts);
    const existingItems = @json($existingItems);
    updateSummary();

    window.refreshTable = function (resData) {
        $.get('{{ route('admin.customers.data') }}?_t=' + new Date().getTime(), function (res) {
            const select  = $('#customerSelect');
            let current = select.val();
            if (resData && resData.status === 'success' && resData.data && resData.data.id) {
                current = resData.data.id;
            }
            select.empty();
            select.append('<option value=""></option>');
            select.append('<option value="0">Walk-in Customer</option>');
            res.data.forEach(function (c) {
                select.append($('<option>', { value: c.id, text: c.name + (c.phone !== '-' ? ' - ' + c.phone : '') }));
            });
            select.val(current).trigger('change');
        });
    };

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
        } else {
            addItemRow(product);
        }
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

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#productSearchInput, #productSearchResults').length) {
            searchResults.hide();
        }
    });

    $(document).on('click', '.search-result-item', function() {
        selectSearchProduct($(this).data('product'));
    });

    function addItemRow(product, selectedVariantId = null, qty = 1, price = null, discountType = 'flat', discountValue = 0, pairType = 'single') {
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

        if (product.type === 'variable') {
            // Build variant select dropdown
            let selectHtml = `<select class="form-select form-select-sm variant-select mt-2 no-select2">`;
            product.variants.forEach(v => {
                const optPrice = v.sale_price != null ? v.sale_price : 0;
                const optPurchasePrice = v.purchase_price != null ? v.purchase_price : 0;
                const selected = (selectedVariantId && selectedVariantId == v.id) || (!selectedVariantId && product.variants[0].id == v.id) ? 'selected' : '';
                selectHtml += `<option value="${v.id}" data-price="${optPrice}" data-purchase-price="${optPurchasePrice}" ${selected}>${v.attr_name}: ${v.value_name} (${symbol}${optPrice})</option>`;
            });
            selectHtml += `</select>`;
            row.find('.variant-select-container').html(selectHtml);

            // Set initial variant
            const selectedOpt = row.find('.variant-select option:selected');
            const initialVariantId = selectedOpt.val();
            const initialPrice = price != null ? price : selectedOpt.data('price');

            row.attr('data-variant-id', initialVariantId);
            row.data('variant-id', initialVariantId);
            row.data('purchase-price', selectedOpt.data('purchase-price'));
            row.data('bypass-min-price', !!product.bypass_min_price);
            setItemPrice(row, initialPrice);
            row.find('.product-sku-display').text('Barcode: ' + product.barcode);
        } else {
            row.find('.product-sku-display').text('Barcode: ' + product.barcode);
            row.data('purchase-price', product.purchase_price != null ? product.purchase_price : 0);
            row.data('bypass-min-price', !!product.bypass_min_price);
            setItemPrice(row, price != null ? price : (product.price != null ? product.price : 0));
        }

        // Pair product selector
        if (product.pair_product) {
            const singlePrice = product.single_price != null ? product.single_price : 0;
            const pairPrice   = product.pair_price != null ? product.pair_price : 0;
            const pairHtml = `
                <div class="pair-type-toggle mt-1" data-single-price="${singlePrice}" data-pair-price="${pairPrice}">
                    <button type="button" class="pair-btn ${pairType !== 'pair' ? 'active' : ''}" data-value="single">Piece</button>
                    <button type="button" class="pair-btn ${pairType === 'pair' ? 'active' : ''}" data-value="pair">Pair</button>
                </div>`;
            row.find('.pair-type-container').html(pairHtml);
            row.find('.pair-type-input').val(pairType);
            // Set correct price immediately
            row.find('.item-price').val(pairType === 'pair' ? pairPrice : singlePrice);
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
        row.data('purchase-price', selectedOpt.data('purchase-price'));
        setItemPrice(row, price);

        updateRowTotal(row);
        updateStockInfo(row);
        updateSummary();
    });

    // Pair type toggle (Single / Pair)
    $(document).on('click', '.pair-btn', function () {
        const toggle = $(this).closest('.pair-type-toggle');
        const row = $(this).closest('.item-row');
        const selected = $(this).data('value');
        toggle.find('.pair-btn').removeClass('active');
        $(this).addClass('active');
        row.find('.pair-type-input').val(selected);
        const price = selected === 'pair'
            ? parseFloat(toggle.data('pair-price'))
            : parseFloat(toggle.data('single-price'));
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
            $('#noItemsMsg').addClass('d-none');
        }
        updateSummary();
    });

    // Pre-populate existing items correctly grouping under their parent products
    function loadExistingItems() {
        if (!existingItems || existingItems.length === 0) return;

        // Group existing items by product_id
        const grouped = {};
        existingItems.forEach(function(item) {
            if (!grouped[item.product_id]) {
                grouped[item.product_id] = [];
            }
            grouped[item.product_id].push(item);
        });

        // For each unique product_id
        Object.keys(grouped).forEach(function(productId) {
            const product = allProducts.find(p => p.id == productId);
            if (!product) return;

            const itemsForProduct = grouped[productId];

            if (product.type === 'variable') {
                const variantItems = itemsForProduct.filter(item => item.product_variant_id != null);
                
                variantItems.forEach(item => {
                    if (parseInt(item.quantity) <= 0) return;
                    
                    let matchedVariant = product.variants.find(v => v.id == item.product_variant_id);
                    
                    if (!matchedVariant) {
                        matchedVariant = product.variants.find(v => parseFloat(v.sale_price) == parseFloat(item.price));
                    }
                    
                    if (!matchedVariant && product.variants.length > 0) {
                        matchedVariant = product.variants[0];
                    }
                    
                    if (matchedVariant) {
                        addItemRow(product, matchedVariant.id, item.quantity, item.price, item.discount_type, item.discount_value, item.pair_type || 'single');
                    }
                });
            } else {
                const item = itemsForProduct[0];
                addItemRow(product, null, item.quantity, item.price, item.discount_type, item.discount_value, item.pair_type || 'single');
            }
        });
    }

    loadExistingItems();

    $(document).on('click', '.remove-item-btn', function () {
        $(this).closest('.item-row').remove();
        if ($('#itemsBody .item-row').length === 0) $('#noItemsMsg').removeClass('d-none');
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

    $(document).on('change', '#locationSelect', function () {
        $('#itemsBody .item-row').each(function () { updateStockInfo($(this)); });
    });

    function updateStockInfo(row) {
        const productId  = row.find('.product-id-input').val();
        const locationId = getLocationId();
        const stockDisplay = row.find('.stock-display');
        const variantId = row.attr('data-variant-id') || row.data('variant-id');
        const product = row.data('product');
        const isPair = row.find('.pair-type-input').val() === 'pair';
        if (!productId) { stockDisplay.text('').removeAttr('title').css('cursor', '').hide(); return; }
        $.get('{{ route('admin.inventory.stock') }}', { product_id: productId, location_id: locationId, variant_id: variantId })
            .done(function (res) {
                let rawQty = res.data?.quantity ?? 0;
                const displayQty = (product && product.pair_product && isPair)
                    ? Math.floor(rawQty / 2)
                    : rawQty;

                const breakdown = res.data?.breakdown || [];
                let titleText = 'Stock Breakdown:\n';
                if (breakdown.length > 0) {
                    breakdown.forEach(item => {
                        const bQty = (product && product.pair_product && isPair)
                            ? Math.floor(item.quantity / 2)
                            : item.quantity;
                        titleText += `- ${item.location_name}: ${bQty}\n`;
                    });
                } else {
                    titleText += 'No stock in any branch';
                }

                stockDisplay.text(displayQty === 0 ? 'Out of Stock' : 'Stock: ' + displayQty)
                    .attr('title', titleText.trim())
                    .css('cursor', 'help')
                    .removeClass('bg-label-success bg-label-danger bg-label-warning')
                    .addClass(displayQty > 0 ? (displayQty < 10 ? 'bg-label-warning' : 'bg-label-success') : 'bg-label-danger')
                    .show();
            });
    }

    $(document).on('input change', '.item-price, .item-qty, .item-discount-value, .item-discount-type', function () {
        const row = $(this).closest('.item-row');
        if (row.length > 0) {
            updateRowTotal(row);
        } else {
            updateSummary();
        }
    });

    $(document).on('input change', '#orderDiscountTypeSelect, #orderDiscountValueInput', function () {
        $('#overallDiscountType').val($('#orderDiscountTypeSelect').val());
        $('#overallDiscountValue').val(parseFloat($('#orderDiscountValueInput').val()) || 0);
        updateSummary();
    });

    $('#orderDiscountTypeSelect').trigger('change');

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

        const total = subtotal - discount;
        let violatesFloor;
        if (row.data('bypass-min-price')) {
            violatesFloor = total <= 0;
        } else {
            const minTotal = getMinAllowedTotal(row);
            violatesFloor = minTotal > 0 && total < minTotal - 0.01;
        }
        row.find('.item-discount-value').toggleClass('is-invalid', violatesFloor);
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
        let minFloorTotal = 0;
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

            const minTotal = getMinAllowedTotal($(this));
            if (minTotal > 0) {
                minFloorTotal += minTotal;
            }

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
        const orderViolatesFloor = (minFloorTotal > 0 && finalAmount < minFloorTotal - 0.01)
            || (count > 0 && finalAmount <= 0);
        $('#orderDiscountValueInput').toggleClass('is-invalid', orderViolatesFloor);
        const totalDiscount = discountSum + orderDiscountAmount;

        $('#itemsTotal').text(symbol + ' ' + formatPrice(itemsTotal));
        $('#summaryItemsTotal').text(symbol + ' ' + formatPrice(subtotalSum));
        $('#summaryDiscountAmount').text(symbol + ' ' + formatPrice(totalDiscount));
        $('#summaryFinal').text(symbol + ' ' + formatPrice(finalAmount));

        if (totalDiscount > 0) {
            $('#summaryDiscountAmount').closest('.d-flex').removeClass('d-none');
        } else {
            $('#summaryDiscountAmount').closest('.d-flex').addClass('d-none');
        }

        if (count > 0) {
            $('#itemsTotal').closest('tr').show();
            $('#summaryColumn').show();
            $('#discountColumn').show();
            $('#noItemsMsg').addClass('d-none');
            $('#itemsTable').removeClass('d-none');
        } else {
            $('#itemsTotal').closest('tr').hide();
            $('#summaryColumn').hide();
            $('#discountColumn').hide();
            $('#noItemsMsg').removeClass('d-none');
            $('#itemsTable').addClass('d-none');
        }
    }

    // Recomputes the discount floor from scratch (mirrors the server-side check)
    // and returns an error message if the current discount values violate it.
    // Returns null when everything is within the allowed limit.
    function validateDiscounts() {
        let itemsTotal = 0;
        let minFloorTotal = 0;
        let errorMsg = null;

        $('#itemsBody .item-row').each(function () {
            if (errorMsg) return;

            const qty = parseInt($(this).find('.item-qty').val()) || 0;
            if (qty <= 0) return;

            const price    = parseFloat($(this).find('.item-price').val()) || 0;
            const discVal  = parseFloat($(this).find('.item-discount-value').val()) || 0;
            const discType = $(this).find('.item-discount-type').val() || 'flat';

            const subtotal = price * qty;
            let discount = discType === 'percentage' ? subtotal * (discVal / 100) : discVal;
            if (discount > subtotal) discount = subtotal;

            const itemTotal = subtotal - discount;
            itemsTotal += itemTotal;

            if ($(this).data('bypass-min-price')) {
                if (itemTotal <= 0) {
                    $(this).find('.item-discount-value').addClass('is-invalid');
                    errorMsg = 'Item amount must be greater than 0.';
                }
                return;
            }

            const minTotal = getMinAllowedTotal($(this));
            if (minTotal > 0) {
                minFloorTotal += minTotal;
                if (itemTotal < minTotal - 0.01) {
                    const product = $(this).data('product');
                    const name = (product && product.name) ? product.name : 'item';
                    $(this).find('.item-discount-value').addClass('is-invalid');
                    errorMsg = 'Discount is not applicable';
                }
            }
        });

        if (errorMsg) return errorMsg;

        const orderDiscType = $('#overallDiscountType').val() || 'flat';
        const orderDiscVal = parseFloat($('#overallDiscountValue').val()) || 0;
        let orderDiscountAmount = orderDiscType === 'percentage' ? itemsTotal * (orderDiscVal / 100) : orderDiscVal;
        if (orderDiscountAmount > itemsTotal) orderDiscountAmount = itemsTotal;

        const finalAmount = itemsTotal - orderDiscountAmount;

        if (finalAmount <= 0) {
            $('#orderDiscountValueInput').addClass('is-invalid');
            return 'Order total must be greater than 0.';
        }

        if (minFloorTotal > 0 && finalAmount < minFloorTotal - 0.01) {
            $('#orderDiscountValueInput').addClass('is-invalid');
            return 'Order total cannot be less than ' + symbol + ' ' + formatPrice(minFloorTotal)
                + ' (combined purchase price + 10% of all items).';
        }

        return null;
    }

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

        const discountError = validateDiscounts();
        if (discountError) {
            toastr.error(discountError);
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
            const price = parseFloat(row.find('.item-price').val()) || 0;
            const discountType = row.find('.item-discount-type').val() || 'flat';
            const discountValue = parseFloat(row.find('.item-discount-value').val()) || 0;

            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][product_id]" value="${product.id}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][product_variant_id]" value="${variantId}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][pair_type]" value="${row.find('.pair-type-input').val() || 'single'}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][quantity]" value="${qty}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][price]" value="${price}">`);
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
                    setTimeout(() => window.location.href = '{{ route('admin.sales.index') }}', 800);
                }
            },
            error   : function (xhr) {
                visibleInputs.prop('disabled', false);
                hiddenContainer.remove();
                $('#submitBtn').prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Update Sale');
                
                const responseJSON = xhr.responseJSON;
                const errors = responseJSON?.errors || responseJSON?.message;

                if (xhr.status === 422 && errors && typeof errors === 'object') {
                    let shownToastr = false;
                    $.each(errors, function (field, messages) {
                        if (field === 'items' || field.startsWith('items.')) {
                            if (!shownToastr) {
                                toastr.error(Array.isArray(messages) ? messages[0] : messages);
                                shownToastr = true;
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
                    if (!shownToastr && Object.keys(errors).length === 0) {
                        toastr.error('Something went wrong. Please try again.');
                    }
                } else {
                    const errorMsg = typeof errors === 'string' 
                        ? errors 
                        : (typeof responseJSON?.message === 'string' ? responseJSON.message : 'Something went wrong. Please try again.');
                    toastr.error(errorMsg);
                }
            }
        });
    });

});
</script>
@endsection
