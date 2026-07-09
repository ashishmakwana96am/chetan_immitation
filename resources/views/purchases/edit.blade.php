@extends('layouts.app')

@section('title', 'Edit Purchase')

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
        width: 35% !important;
    }
    #itemsTable th:nth-child(2), #itemsTable td:nth-child(2) {
        width: 18% !important;
        min-width: 80px !important;
    }
    #itemsTable th:nth-child(3), #itemsTable td:nth-child(3) {
        width: 27% !important;
        min-width: 150px !important;
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

    /* Fixed, industry-standard widths for Qty / Price inputs on every screen size */
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

    /* Prevent large amounts from ever breaking the sidebar layout, on any screen size */
    #summaryColumn .d-flex.justify-content-between {
        flex-wrap: wrap;
        row-gap: 4px;
    }
    #summaryColumn .d-flex.justify-content-between > span {
        min-width: 0;
    }
    #summaryColumn .d-flex.justify-content-between > span:last-child {
        overflow-wrap: anywhere;
        word-break: break-word;
        text-align: right;
        flex: 1 1 auto;
    }
</style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">Edit Purchase <code>{{ $purchase->invoice_no }}</code></h4>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.purchases.show', $purchase) }}" class="btn btn-label-secondary">
                <i class="ti ti-eye me-1"></i> View
            </a>
            <a href="{{ route('admin.purchases.index') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <form id="purchaseForm" action="{{ route('admin.purchases.update', $purchase) }}" method="POST">
        @csrf
        @method('PUT')
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
                                <input type="text" class="form-control" value="{{ $purchase->invoice_no }}" disabled />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Supplier <span class="text-danger">*</span></label>
                                <select name="supplier_id" class="form-select">
                                    <option value="">Select Supplier</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ $purchase->supplier_id === $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
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
                                <input type="text" id="productSearchInput" class="form-control" placeholder="Search product by name or barcode..." autocomplete="off">
                            </div>
                            <div id="productSearchResults" class="list-group position-absolute w-100 mt-1 bg-white" style="z-index: 9999; background-color: #ffffff; display: none; max-height: 250px; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 0.375rem;">
                                <!-- Search results will appear here -->
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0 {{ $purchase->items->isEmpty() ? 'd-none' : '' }}" id="itemsTable">
                                    <thead>
                                        <tr class="table-light">
                                            <th style="min-width: 250px;">Product</th>
                                            <th style="width: 100px; min-width: 100px;">Qty</th>
                                            <th style="width: 150px; min-width: 150px;">Price</th>
                                            <th style="width: 120px; min-width: 120px;">Total</th>
                                            <th style="width: 44px;"></th>
                                        </tr>
                                    </thead>
                                <tbody id="itemsBody"></tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="3" class="text-end fw-semibold">Grand Total</td>
                                        <td class="fw-bold text-primary text-nowrap" id="grandTotal">{{ format_price($purchase->total_amount) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div id="noItemsMsg" class="text-center text-muted py-4 d-none">
                            No items added yet.
                        </div>
                    </div>
                </div>
            </div>

                </div>
            </div>

            <div class="col-lg-4">
                <div class="row g-3">

            <!-- Bottom widgets: Summary -->
            <div class="col-12" id="summaryColumn">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Summary</h5></div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total Items</span>
                            <span id="summaryItems" class="fw-semibold">{{ $purchase->items->count() }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Grand Total</span>
                            <span id="summaryTotal" class="fw-bold text-primary">{{ format_price($purchase->total_amount) }}</span>
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
                            <option value="1" {{ $purchase->status == 1 ? 'selected' : '' }}>Pending</option>
                            <option value="2" {{ $purchase->status == 2 ? 'selected' : '' }}>Approve</option>
                            <option value="3" {{ $purchase->status == 3 ? 'selected' : '' }}>Decline</option>
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
                            <option value="1" {{ ($purchase->payment_status ?? 1) == 1 ? 'selected' : '' }}>Pending</option>
                            <option value="2" {{ ($purchase->payment_status ?? 1) == 2 ? 'selected' : '' }}>Paid</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="col-12">
                <div class="row g-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary w-100 py-2 fs-5" id="submitBtn">
                            <i class="ti ti-device-floppy me-1"></i> Update Purchase
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
    const symbol    = '{{ currency_symbol() }}';
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
        $mappedProducts = $products->map(function($p) {
            $data = [
                'id' => $p->id,
                'name' => $p->name,
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

    function addItemRow(product, selectedVariantId = null, qty = 1, price = null) {
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
            let selectHtml = `<select class="form-select form-select-sm variant-select mt-2 no-select2" name="items[${itemIndex}][variant_id]">`;
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
            row.find('.product-sku-display').text('Barcode: ' + product.barcode);

            const variantText = selectedOpt.text().split(' (')[0];
            row.data('product-name', product.name + ' (' + variantText + ')');
        } else {
            row.find('.product-sku-display').text('Barcode: ' + product.barcode);
            row.find('.purchase-price').val(price != null ? price : (product.purchase_price != null ? product.purchase_price : 0));
            row.data('product-name', product.name);
        }

        row.find('.item-qty').val(qty);

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
        const price = selectedOpt.data('price');
        
        row.attr('data-variant-id', variantId);
        row.data('variant-id', variantId);
        row.find('.purchase-price').val(price);
        
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

    // Pre-populate existing items correctly
    function loadExistingItems() {
        const existingItems = @json($existingItems);
        if (!existingItems || existingItems.length === 0) return;

        existingItems.forEach(function(item) {
            if (parseInt(item.quantity) <= 0) return;

            const product = allProducts.find(p => p.id == item.product_id);
            if (!product) return;

            if (product.type === 'variable') {
                let matchedVariant = product.variants.find(v => v.id == item.product_variant_id);

                if (!matchedVariant) {
                    matchedVariant = product.variants.find(v => parseFloat(v.purchase_price) == parseFloat(item.purchase_price));
                }

                if (!matchedVariant && product.variants.length > 0) {
                    matchedVariant = product.variants[0];
                }

                if (matchedVariant) {
                    addItemRow(product, matchedVariant.id, item.quantity, item.purchase_price);
                }
            } else {
                addItemRow(product, null, item.quantity, item.purchase_price);
            }
        });
    }

    // Call loadExistingItems to pre-populate items
    loadExistingItems();

    $(document).on('input', '.purchase-price', function () {
        updateRowTotal($(this).closest('.item-row'));
    });

    $(document).on('input', '.item-qty', function () {
        const row = $(this).closest('.item-row');
        updateRowTotal(row);
    });

    function updateRowTotal(row) {
        const price = parseFloat(row.find('.purchase-price').val()) || 0;
        const qty   = parseInt(row.find('.item-qty').val()) || 0;
        row.find('.item-total').text(symbol + ' ' + formatPrice(price * qty));
        updateGrandTotal();
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

        const form = $(this);
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.select2-container .select2-selection').css('border-color', '');
        form.find('.invalid-feedback').text('').hide();

        // Remove any previously appended hidden mapping container
        $('#hiddenSubmitContainer').remove();

        // Create a container for our mapped inputs
        const hiddenContainer = $('<div id="hiddenSubmitContainer" style="display: none;"></div>');
        form.append(hiddenContainer);

        // Disable all inputs in the visible table so they are NOT serialized
        const visibleInputs = $('#itemsTable').find('input, select');
        visibleInputs.prop('disabled', true);

        // Submit each visible row separately so variant quantities and allocations stay independent.
        let submitIdx = 0;
        $('.item-row').each(function() {
            const row = $(this);
            const product = row.data('product');
            const qty = parseInt(row.find('.item-qty').val()) || 0;
            if (qty <= 0) return; // skip rows with 0 qty

            const variantId = row.data('variant-id') || '';
            const purchasePrice = parseFloat(row.find('.purchase-price').val()) || 0;

            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][product_id]" value="${product.id}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][product_variant_id]" value="${variantId}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][quantity]" value="${qty}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][purchase_price]" value="${purchasePrice}">`);
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
                $('#submitBtn').prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Update Purchase');
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
