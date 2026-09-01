@extends('layouts.app')

@section('title', 'New Purchase Bill')

@section('page-css')
<style>
    #itemsTable input[type=number]::-webkit-outer-spin-button,
    #itemsTable input[type=number]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    #itemsTable input[type=number] {
        -moz-appearance: textfield;
    }
    #itemsTable {
        min-width: 600px !important;
    }

    /* Fixed, industry-standard width for Qty input on every screen size */
    #itemsTable .item-qty {
        width: 70px !important;
        min-width: 70px !important;
        max-width: 70px !important;
        text-align: center;
        margin: 0 auto;
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

    /* Pair type toggle */
    .pair-type-toggle { display: inline-flex; border-radius: 6px; overflow: hidden; border: 1.5px solid #B4771E; }
    .pair-type-toggle .pair-btn {
        padding: 3px 14px; font-size: .8rem; font-weight: 600; border: none; cursor: pointer;
        background: #fff; color: #B4771E; transition: background .15s, color .15s;
    }
    .pair-type-toggle .pair-btn + .pair-btn { border-left: 1.5px solid #B4771E; }
    .pair-type-toggle .pair-btn.active { background: #B4771E; color: #fff; }

    /* Size toggle styles */
    .size-toggle {
        display: inline-flex;
        border-radius: 6px;
        overflow: hidden;
        border: 1.5px solid #B4771E;
        margin-top: 5px;
    }
    .size-toggle .size-btn {
        background: #fff;
        border: none;
        color: #B4771E;
        padding: 4px 10px;
        font-size: 11px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .size-toggle .size-btn + .size-btn { border-left: 1.5px solid #B4771E; }
    .size-toggle .size-btn.active { background: #B4771E; color: #fff; }
</style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-semibold mb-0">New Purchase Bill</h4>
        <a href="{{ route('admin.purchase-bills.index') }}" class="btn btn-label-secondary">
            <i class="ti ti-arrow-left me-1"></i> Back
        </a>
    </div>

    <form id="purchaseBillForm" action="{{ route('admin.purchase-bills.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">Transfer Details</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Bill No</label>
                                <input type="text" class="form-control" value="{{ $transferNo }}" disabled>
                                <small class="text-muted">Auto-generated on save</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" id="paymentMethodSelect" class="form-select no-select2">
                                    <option value="cash">Cash</option>
                                    <option value="online">Online</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Status <span class="text-danger">*</span></label>
                                <select name="payment_status" id="paymentStatusSelect" class="form-select no-select2">
                                    <option value="1">Pending</option>
                                    <option value="2">Paid</option>
                                    <option value="3">Partial</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Source Location <span class="text-danger">*</span></label>
                                @if($canChooseSource)
                                    <select name="from_location_id" id="fromLocation" class="form-select">
                                        @foreach($sourceLocations as $location)
                                            <option value="{{ $location->id }}" {{ $location->id === $defaultLocation->id ? 'selected' : '' }}>{{ $location->name }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" class="form-control" value="{{ $defaultLocation->name }}" disabled>
                                    <input type="hidden" name="from_location_id" id="fromLocation" value="{{ $defaultLocation->id }}">
                                @endif
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Destination Location <span class="text-danger">*</span></label>
                                <select name="to_location_id" id="toLocation" class="form-select">
                                    <option value="">Select Destination</option>
                                    @foreach($destinationLocations as $location)
                                        <option value="{{ $location->id }}" {{ $location->id === $defaultLocation->id ? 'disabled' : '' }}>{{ $location->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Remarks</label>
                                <textarea name="remarks" class="form-control" rows="2" placeholder="Optional note for this transfer"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-bottom pb-3" style="z-index: 10;">
                        <h5 class="mb-3">Transfer Items</h5>
                        <div class="position-relative">
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="ti ti-search"></i></span>
                                <input type="text" id="productSearchInput" class="form-control" placeholder="Search product by name or barcode..." autocomplete="off">
                            </div>
                            <div id="productSearchResults" class="list-group position-absolute w-100 mt-1 bg-white" style="z-index: 9999; display: none; max-height: 250px; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 0.375rem;"></div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0" id="itemsTable" style="display: none;">
                                <thead>
                                    <tr class="table-light">
                                        <th style="min-width: 250px;">Product</th>
                                        <th style="width: 140px; min-width: 140px;">Unit</th>
                                        <th style="width: 140px; min-width: 140px;">Available</th>
                                        <th style="width: 140px; min-width: 140px;">Qty</th>
                                        <th style="width: 120px; min-width: 120px;">Price</th>
                                        <th style="width: 130px; min-width: 130px;">Total</th>
                                        <th style="width: 58px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody"></tbody>
                            </table>
                        </div>
                        <div id="noItemsMsg" class="text-center text-muted py-4">
                            No items added yet.
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-3" id="summaryColumn">
                    <div class="card-header"><h5 class="mb-0">Summary</h5></div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total Items</span>
                            <span id="summaryItems" class="fw-semibold">0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total Qty</span>
                            <span id="summaryQty" class="fw-bold text-primary">0</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Total Amount</span>
                            <span id="summaryAmount" class="fw-bold text-primary">{{ currency_symbol() }} 0.00</span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                            <i class="ti ti-device-floppy me-1"></i> Save Request
                        </button>
                        <a href="{{ route('admin.purchase-bills.index') }}" class="btn btn-label-secondary w-100 mt-3">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <template id="itemRowTemplate">
        <tr class="item-row" data-index="__INDEX__">
            <td class="align-middle">
                <div class="d-flex align-items-center">
                    <div class="product-image-container me-3 flex-shrink-0"></div>
                    <div class="d-flex flex-column">
                        <span class="product-name-display fw-semibold text-heading"></span>
                        <small class="product-sku-display text-muted"></small>
                        <div class="variant-select-container mt-2"></div>
                        <div class="size-select-container"></div>
                        <div class="batch-select-container"></div>
                    </div>
                </div>
            </td>
            <td class="align-middle">
                <div class="pair-type-container"></div>
            </td>
            <td class="align-middle">
                <span class="badge bg-label-secondary stock-info-display">Select source</span>
            </td>
            <td class="align-middle">
                <input type="number" class="form-control item-qty" min="1" value="1">
            </td>
            <td class="align-middle price-display"></td>
            <td class="align-middle text-end fw-semibold total-display"></td>
            <td class="align-middle text-end">
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

    let allProducts = [];
    $.getJSON('{{ route("admin.purchase-bills.products-json") }}', function(res) {
        allProducts = res || [];
    });
    const symbol = '{{ currency_symbol() }}';
    const searchInput = $('#productSearchInput');
    const searchResults = $('#productSearchResults');

    function formatPrice(val) {
        return parseFloat(val || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function setProductImage(container, product) {
        if (product.image) {
            container.html(`<img src="${product.image}" class="rounded product-thumbnail" style="width: 40px; height: 40px; object-fit: cover;" alt="${product.name || ''}">`);
        } else {
            container.html(`<div class="rounded bg-label-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="ti ti-photo text-muted" style="font-size: 1.25rem;"></i></div>`);
        }
    }

    function findExactProductMatch(query) {
        const q = query.toLowerCase().trim();
        if (!q) return null;
        const matches = allProducts.filter(p =>
            p.name.toLowerCase() === q ||
            (p.barcode && String(p.barcode).toLowerCase() === q)
        );
        return matches.length === 1 ? matches[0] : null;
    }

    function hasDuplicate(productId, variantId) {
        let exists = false;
        $('.item-row').each(function () {
            const row = $(this);
            if (row.data('product-id') == productId && String(row.data('variant-id') || '') === String(variantId || '')) {
                exists = true;
            }
        });
        return exists;
    }

    function incrementExistingRowQty(productId, variantId) {
        let updated = false;
        $('.item-row').each(function () {
            const row = $(this);
            if (row.data('product-id') == productId && String(row.data('variant-id') || '') === String(variantId || '')) {
                const qtyInput = row.find('.item-qty');
                qtyInput.val((parseInt(qtyInput.val()) || 0) + 1);
                updated = true;
                return false;
            }
        });
        if (updated) updateSummary();
        return updated;
    }

    function getAvailableVariantId(product) {
        if (!product.variants || !product.variants.length) return null;
        const used = new Set();
        $('.item-row').each(function () {
            const row = $(this);
            if (row.data('product-id') == product.id) {
                const vId = row.data('variant-id');
                if (vId) used.add(String(vId));
            }
        });
        const free = product.variants.find(v => !used.has(String(v.id)));
        return free ? free.id : null;
    }

    function getEffectiveCustomSizes(product, variantId) {
        if (variantId && product.variants) {
            const variant = product.variants.find(v => v.id == variantId);
            if (variant && variant.custom_sizes && variant.custom_sizes.length) {
                return variant.custom_sizes;
            }
        }
        return product.custom_sizes || [];
    }

    function buildTransferSizeToggleHtml(sizes, defSize) {
        let sizeHtml = `<div class="size-toggle" data-selected="${defSize}">`;
        sizes.forEach(function (cs) {
            const active = defSize && defSize == cs.size ? 'active' : '';
            sizeHtml += `<button type="button" class="size-btn ${active}" data-value="${cs.size}">${cs.size} pcs</button>`;
        });
        sizeHtml += `</div>`;
        return sizeHtml;
    }

    function addItemRow(product, selectedVariantId = null, selectedPurchasePrice = null, selectedBatchId = null) {
        let initialVariantId = null;

        if (product.type !== 'variable' && !(product.pair_product && product.custom_sizes && product.custom_sizes.length) && hasDuplicate(product.id, null)) {
            incrementExistingRowQty(product.id, null);
            return;
        }

        if (product.type === 'variable' && product.variants && product.variants.length) {
            initialVariantId = selectedVariantId || getAvailableVariantId(product);
            if (!initialVariantId) {
                toastr.warning('All variants of this product are already added.');
                return;
            }
        }

        const idx = itemIndex++;
        const html = $('#itemRowTemplate').html().replace(/__INDEX__/g, idx);
        const row = $(html);
        row.data('product', product);
        row.data('product-id', product.id);
        if (selectedPurchasePrice !== null && selectedPurchasePrice !== undefined) {
            row.data('selected-purchase-price', parseFloat(selectedPurchasePrice));
        }
        if (selectedBatchId !== null && selectedBatchId !== undefined) {
            row.data('selected-batch-id', selectedBatchId);
        }
        row.find('.product-name-display').text(product.name);
        row.find('.product-sku-display').text(product.barcode ? 'Barcode: ' + product.barcode : '');
        setProductImage(row.find('.product-image-container'), product);

        if (product.type === 'variable' && product.variants && product.variants.length) {
            const activeLocId = $('#fromLocation').val();
            let options = `<option value="" disabled ${!selectedVariantId ? 'selected' : ''}>-- Select Variant --</option>`;
            product.variants.forEach(function (variant) {
                const selected = selectedVariantId && selectedVariantId == variant.id ? 'selected' : '';
                const price = variant.purchase_price != null ? variant.purchase_price : 0;

                let vStock = 0;
                if (activeLocId && product.stock_by_location && product.stock_by_location[activeLocId] && product.stock_by_location[activeLocId].variants) {
                    vStock = parseInt(product.stock_by_location[activeLocId].variants[variant.id]) || 0;
                }
                const isDisabled = (vStock <= 0 && !selected) ? 'disabled' : '';
                const stockLabel = (activeLocId && vStock <= 0) ? ' (Out of stock)' : '';

                const label = `${variant.attr_name}: ${variant.value_name} (${symbol}${price})${stockLabel}`;
                options += `<option value="${variant.id}" ${selected} ${isDisabled}>${label}</option>`;
            });
            row.find('.variant-select-container').html(`<select class="form-select form-select-sm variant-select mt-2 no-select2">${options}</select>`);
            const currentVarVal = row.find('.variant-select').val() || null;
            row.data('variant-id', currentVarVal);
        }

        if (product.pair_product) {
            const effectiveSizes = getEffectiveCustomSizes(product, initialVariantId);
            if (effectiveSizes.length) {
                const firstSizeVal = effectiveSizes[0].size;
                row.find('.size-select-container').html(buildTransferSizeToggleHtml(effectiveSizes, firstSizeVal));
                row.data('custom-size-value', firstSizeVal);
            }
        }

        updateRowPrice(row);

        // Pair product indicator
        if (product.pair_product) {
            row.find('.pair-type-container').html(`
                <span class="badge bg-label-warning">Pair Size</span>
                <input type="hidden" class="pair-type-input" value="single">`);
        } else {
            row.find('.pair-type-container').html(`
                <span class="badge bg-label-secondary">Piece</span>
                <input type="hidden" class="pair-type-input" value="single">`);
        }

        $('#itemsBody').prepend(row);
        refreshRowStock(row);
        updateSummary();
    }

    function refreshRowStock(row) {
        const sourceLocationId = $('#fromLocation').val();
        const product = row.data('product');
        const variantId = row.find('.variant-select').val() || '';
        row.data('variant-id', variantId || null);

        if (!sourceLocationId || !product) {
            row.find('.stock-info-display').removeClass('bg-label-success bg-label-danger').addClass('bg-label-secondary').text('Select source');
            return;
        }
        if (product.type === 'variable' && !variantId) {
            row.find('.stock-info-display').removeClass('bg-label-success bg-label-danger').addClass('bg-label-warning').text('Select variant');
            return;
        }

        $.get('{{ route('admin.inventory.stock') }}', {
            product_id: product.id,
            location_id: sourceLocationId,
            variant_id: variantId
        }).done(function (res) {
            const qty = parseInt(res.data.quantity || 0);
            const totalQty = parseInt(res.data.total_quantity || 0);
            const breakdown = res.data.breakdown || [];
            let titleText = 'Source stock: ' + qty + '\nTotal stock: ' + totalQty;
            if (breakdown.length > 0) {
                titleText += '\n\nStock Breakdown:';
                breakdown.forEach(item => {
                    titleText += `\n- ${item.location_name}: ${item.quantity}`;
                });
            }
            const selOpt = row.find('.batch-select option:selected');
            const availAttr = selOpt.attr('data-available-qty');
            if (selOpt.length && availAttr !== undefined && availAttr !== false) {
                row.data('available-pcs', parseInt(availAttr) || 0);
            } else {
                row.data('available-pcs', qty);
            }
            updateRowAvailableStockDisplay(row);
            row.find('.stock-info-display').attr('title', titleText).css('cursor', 'help');
        }).fail(function () {
            row.find('.stock-info-display').removeClass('bg-label-success bg-label-secondary').addClass('bg-label-danger').text('N/A');
        });

        loadTransferProductBatches(row, row.data('selected-batch-id'), row.data('selected-purchase-price'));
    }

    function loadTransferProductBatches(row, selectedBatchId = null, selectedPrice = null) {
        const product = row.data('product');
        const variantId = row.attr('data-variant-id') || row.data('variant-id') || row.find('.variant-select').val() || '';
        const locationId = $('#fromLocation').val();
        const batchContainer = row.find('.batch-select-container');

        if (selectedBatchId === null || selectedBatchId === undefined) {
            selectedBatchId = row.data('selected-batch-id') || null;
        }
        if (selectedPrice === null || selectedPrice === undefined) {
            selectedPrice = row.data('selected-purchase-price') !== undefined ? row.data('selected-purchase-price') : null;
        }

        if (!product || !locationId) {
            batchContainer.empty();
            updateRowPrice(row);
            return;
        }

        $.get('{{ route("admin.sales.product-batches") }}', {
            product_id: product.id,
            product_variant_id: variantId,
            location_id: locationId
        }, function(res) {
            if (res.status === 'success' && res.batches && res.batches.length > 0) {
                row.data('batches', res.batches);
                let html = '<div class="d-flex align-items-center gap-1 mt-1"><span class="badge bg-label-info text-nowrap" style="font-size: 0.7rem;">Purchase Price:</span><select class="form-select form-select-sm batch-select no-select2" style="font-size: 0.78rem; padding-top: 2px; padding-bottom: 2px;">';
                
                let foundSelected = false;
                res.batches.forEach(b => {
                    let sel = false;
                    if (selectedBatchId && b.purchase_item_id == selectedBatchId) {
                        sel = true;
                    } else if (!foundSelected && selectedPrice !== null && selectedPrice !== undefined && Math.abs(parseFloat(b.purchase_price) - parseFloat(selectedPrice)) < 0.001) {
                        sel = true;
                    }
                    if (sel) foundSelected = true;

                    const bStock = parseInt(b.available_qty) || 0;
                    const isDisabled = (bStock <= 0 && !sel) ? 'disabled' : '';
                    const stockTag = bStock > 0 ? ` (${bStock} Pcs)` : ' (Out of stock)';

                    html += `<option value="${b.purchase_price}" data-purchase-item-id="${b.purchase_item_id || ''}" data-purchase-price="${b.purchase_price}" data-available-qty="${b.available_qty}" ${sel ? 'selected' : ''} ${isDisabled}>${b.label}${stockTag}</option>`;
                });

                if (!foundSelected && selectedPrice !== null && selectedPrice !== undefined && parseFloat(selectedPrice) > 0) {
                    const formatted = symbol + ' ' + formatPrice(selectedPrice);
                    html += `<option value="${selectedPrice}" data-purchase-price="${selectedPrice}" data-available-qty="0" selected disabled>${formatted} (Out of stock)</option>`;
                }

                html += '</select></div>';
                batchContainer.html(html);

                const selOpt = batchContainer.find('.batch-select option:selected');
                if (selOpt.length) {
                    const pVal = selOpt.attr('data-purchase-price') !== undefined ? selOpt.attr('data-purchase-price') : selOpt.val();
                    if (pVal !== undefined && pVal !== null && pVal !== '') {
                        row.data('selected-purchase-price', parseFloat(pVal));
                    }
                    const bId = selOpt.attr('data-purchase-item-id');
                    if (bId) row.data('selected-batch-id', bId);

                    const availAttr = selOpt.attr('data-available-qty');
                    if (availAttr !== undefined && availAttr !== false) {
                        row.data('available-pcs', parseInt(availAttr) || 0);
                        updateRowAvailableStockDisplay(row);
                    }
                }

                updateRowPrice(row);
            } else {
                row.data('batches', []);
                let targetPrice = (selectedPrice !== null && selectedPrice !== undefined)
                    ? parseFloat(selectedPrice)
                    : (product.purchase_price ? parseFloat(product.purchase_price) : 0);
                const defPrice = symbol + ' ' + formatPrice(targetPrice);
                let html = '<div class="d-flex align-items-center gap-1 mt-1"><span class="badge bg-label-info text-nowrap" style="font-size: 0.7rem;">Purchase Price:</span><select class="form-select form-select-sm batch-select no-select2" style="font-size: 0.78rem; padding-top: 2px; padding-bottom: 2px;">';
                html += `<option value="${targetPrice}" data-purchase-price="${targetPrice}" data-available-qty="0" selected>${defPrice}</option>`;
                html += '</select></div>';
                batchContainer.html(html);

                row.data('selected-purchase-price', targetPrice);
                row.data('available-pcs', 0);
                updateRowAvailableStockDisplay(row);
                updateRowPrice(row);
            }
        }).fail(function() {
            row.data('batches', []);
            batchContainer.empty();
            updateRowPrice(row);
        });
    }

    function updateRowAvailableStockDisplay(row) {
        const qtyPcs = parseInt(row.data('available-pcs') || 0);
        const product = row.data('product');
        
        let stockText = qtyPcs + ' Pcs';
        let displayQty = qtyPcs;
        
        if (product && product.pair_product) {
            let pairSize = 2;
            if (product.custom_sizes && Array.isArray(product.custom_sizes) && product.custom_sizes.length > 0) {
                const sizes = product.custom_sizes.map(s => typeof s === 'object' && s !== null ? parseFloat(s.size) : parseFloat(s)).filter(Boolean);
                if (sizes.length > 0) pairSize = Math.max(...sizes);
            }
            const pairsCount = Math.floor(qtyPcs / pairSize);
            const remPcs = qtyPcs % pairSize;
            let parts = [];
            if (pairsCount > 0) parts.push(pairsCount + (pairsCount > 1 ? ' Pairs' : ' Pair'));
            if (remPcs > 0) parts.push(remPcs + ' Pcs');
            stockText = parts.length > 0 ? parts.join('<br>') : '0';
            displayQty = pairsCount;
        }
        
        row.data('available', displayQty);
        row.find('.stock-info-display')
            .html('Stock: ' + stockText)
            .removeClass('bg-label-secondary bg-label-warning bg-label-danger bg-label-success')
            .addClass(qtyPcs > 0 ? 'bg-label-success' : 'bg-label-danger');
    }

    function getRowPrice(row) {
        const product = row.data('product');
        if (!product) return 0;
        let basePrice = 0;

        const variantId = row.find('.variant-select').val() || row.attr('data-variant-id') || row.data('variant-id') || null;

        const batchSelect = row.find('.batch-select');
        if (batchSelect.length) {
            const selOpt = batchSelect.find('option:selected');
            if (selOpt.length) {
                const optPrice = selOpt.attr('data-purchase-price') !== undefined ? selOpt.attr('data-purchase-price') : selOpt.val();
                if (optPrice !== undefined && optPrice !== null && optPrice !== '') {
                    basePrice = parseFloat(optPrice) || 0;
                }
            }
        }

        if (basePrice === 0 && row.data('selected-purchase-price') !== undefined) {
            basePrice = parseFloat(row.data('selected-purchase-price')) || 0;
        }

        if (basePrice === 0) {
            if (variantId && product.variants) {
                const variant = product.variants.find(v => String(v.id) === String(variantId));
                if (variant) basePrice = parseFloat(variant.purchase_price || 0);
            } else {
                basePrice = parseFloat(product.purchase_price || 0);
            }
        }
        
        let multiplier = 1.0;
        const pairType = row.find('.pair-type-input').val() || 'single';
        const customSizeValue = parseFloat(row.data('custom-size-value') || 0);
        
        if (product.pair_product && customSizeValue > 0) {
            const sizes = getEffectiveCustomSizes(product, variantId);
            let maxSize = 0;
            if (sizes && sizes.length) {
                const numSizes = sizes.map(s => parseFloat(s.size)).filter(s => s > 0);
                if (numSizes.length) maxSize = Math.max(...numSizes);
            }
            if (maxSize > 0) {
                multiplier = customSizeValue / maxSize;
            }
        } else if (pairType === 'pair') {
            multiplier = 1.0;
        }
        
        return basePrice * multiplier;
    }

    function updateRowPrice(row) {
        const price = getRowPrice(row);
        row.data('price', price);
        row.find('.price-display').text(symbol + ' ' + formatPrice(price));
        updateRowTotal(row);
    }

    function updateRowTotal(row) {
        const price = parseFloat(row.data('price') || 0);
        const qty = parseInt(row.find('.item-qty').val()) || 0;
        const total = price * qty;
        row.data('total', total);
        row.find('.total-display').text(symbol + ' ' + formatPrice(total));
    }

    function updateSummary() {
        let items = 0;
        let qty = 0;
        let amount = 0;
        $('.item-row').each(function () {
            const row = $(this);
            updateRowTotal(row);
            const rowQty = parseInt(row.find('.item-qty').val()) || 0;
            if (rowQty > 0) {
                items++;
                qty += rowQty;
                amount += parseFloat(row.data('total') || 0);
            }
        });
        $('#summaryItems').text(items);
        $('#summaryQty').text(qty);
        $('#summaryAmount').text(symbol + ' ' + formatPrice(amount));
        $('#noItemsMsg').toggle(items === 0);
        $('#itemsTable').toggle(items > 0);
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
            if (firstItem.length) firstItem.click();
        }
    });

    searchInput.on('input', function() {
        const query = $(this).val().toLowerCase().trim();
        searchResults.empty();

        if (scanMatchTimer) { clearTimeout(scanMatchTimer); scanMatchTimer = null; }

        if (!query) {
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
        if (!matchedProducts.length) {
            searchResults.html('<div class="list-group-item text-muted">No products found</div>').show();
            return;
        }
        matchedProducts.slice(0, 20).forEach(function(product) {
            const isVar = product.type === 'variable';
            const priceBadge = isVar
                ? '<span class="badge bg-label-warning">Variable</span>'
                : '<span class="badge bg-label-primary">' + symbol + ' ' + formatPrice(product.purchase_price || 0) + '</span>';
            const imgHtml = product.image
                ? `<img src="${product.image}" class="rounded me-3" style="width: 40px; height: 40px; object-fit: cover;" alt="${product.name}" />`
                : `<div class="rounded me-3 bg-label-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;"><i class="ti ti-photo text-secondary fs-4"></i></div>`;
            const item = $(`
                <a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center search-result-item bg-white" style="background-color: #ffffff;" data-id="${product.id}">
                    <div class="d-flex align-items-center">
                        ${imgHtml}
                        <div>
                            <div class="fw-semibold">${product.name}</div>
                            <small class="text-muted">Barcode: ${product.barcode || '-'}</small>
                        </div>
                    </div>
                    ${priceBadge}
                </a>
            `);
            item.data('product', product);
            item.on('click', function () {
                selectSearchProduct($(this).data('product'));
            });
            searchResults.append(item);
        });
        searchResults.show();
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#productSearchInput, #productSearchResults').length) {
            searchResults.hide();
        }
    });

    $(document).on('click', '.size-btn', function () {
        const row = $(this).closest('.item-row');
        const toggle = $(this).closest('.size-toggle');
        toggle.find('.size-btn').removeClass('active');
        $(this).addClass('active');
        row.data('custom-size-value', $(this).data('value'));
        updateRowAvailableStockDisplay(row);
        updateRowPrice(row);
        updateSummary();
        
        const qty = parseInt(row.find('.item-qty').val()) || 0;
        const available = parseInt(row.data('available') || 0);
        if (qty > available) {
            row.find('.item-qty').addClass('is-invalid');
        } else {
            row.find('.item-qty').removeClass('is-invalid');
        }
    });

    $(document).on('click', '.pair-type-toggle .pair-btn', function () {
        const btn = $(this);
        const toggle = btn.closest('.pair-type-toggle');
        const row = btn.closest('.item-row');
        
        toggle.find('.pair-btn').removeClass('active');
        btn.addClass('active');
        
        const val = btn.data('value') || 'single';
        row.find('.pair-type-input').val(val);
        
        updateRowAvailableStockDisplay(row);
        updateRowPrice(row);
        updateSummary();
        
        // Also trigger check validation
        const qty = parseInt(row.find('.item-qty').val()) || 0;
        const available = parseInt(row.data('available') || 0);
        if (qty > available) {
            row.find('.item-qty').addClass('is-invalid');
        } else {
            row.find('.item-qty').removeClass('is-invalid');
        }
        
        updateSummary();
    });

    $(document).on('change', '.variant-select', function () {
        const row = $(this).closest('.item-row');
        const product = row.data('product');
        let variantId = $(this).val() || null;

        if (variantId && hasDuplicate(product.id, variantId)) {
            toastr.warning('This variant is already in the list.');
            $(this).val('');
            variantId = null;
        }
        row.data('variant-id', variantId);

        // Pack sizes can differ per variant — rebuild the toggle for the new variant,
        // trying to keep the same size selected if it still exists.
        if (product.pair_product) {
            const effectiveSizes = getEffectiveCustomSizes(product, variantId);
            if (effectiveSizes.length) {
                const currentSize = parseFloat(row.data('custom-size-value'));
                const stillValid = effectiveSizes.find(cs => cs.size == currentSize);
                const defSize = stillValid ? currentSize : effectiveSizes[0].size;
                row.find('.size-select-container').html(buildTransferSizeToggleHtml(effectiveSizes, defSize));
                row.data('custom-size-value', defSize);
            }
        }

        refreshRowStock(row);
        updateRowPrice(row);
    });

    $(document).on('change', '.batch-select', function() {
        const row = $(this).closest('.item-row');
        const selOpt = $(this).find('option:selected');
        if (selOpt.length) {
            const pVal = selOpt.attr('data-purchase-price') !== undefined ? selOpt.attr('data-purchase-price') : selOpt.val();
            if (pVal !== undefined && pVal !== null && pVal !== '') {
                row.data('selected-purchase-price', parseFloat(pVal));
            }
            const bId = selOpt.attr('data-purchase-item-id');
            if (bId) {
                row.data('selected-batch-id', bId);
            }
            const availAttr = selOpt.attr('data-available-qty');
            if (availAttr !== undefined && availAttr !== false) {
                row.data('available-pcs', parseInt(availAttr) || 0);
                updateRowAvailableStockDisplay(row);
            }
        }
        updateRowPrice(row);
        updateSummary();
    });

    $('#fromLocation').on('change', function() {
        $('.item-row').each(function() {
            refreshRowStock($(this));
        });
    });

    $(document).on('input', '.item-qty', updateSummary);
    $(document).on('click', '.remove-item-btn', function () {
        $(this).closest('.item-row').remove();
        updateSummary();
    });

    $('#purchaseBillForm').on('submit', function (e) {
        e.preventDefault();
        const form = $(this);

        if ($('#fromLocation').val() && $('#fromLocation').val() === $('#toLocation').val()) {
            toastr.error('Source and destination locations must be different.');
            return;
        }

        const rows = $('.item-row');
        if (!rows.length) {
            toastr.error('Please add at least one transfer item.');
            return;
        }

        let variantMissing = false;
        rows.each(function () {
            const row = $(this);
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

        const dateInput = $('#created_at').val() || $('#transfer_date').val() || $('#invoice_date').val();
        if (dateInput && typeof window.checkAndConfirmDateSubmission === 'function') {
            const toLocName = $('#toLocation option:selected').text() || 'Location';
            const totalAmt = $('#footerTotalAmountText').text() || ('₹' + $('#grandTotal').val());

            const confirmed = window.checkAndConfirmDateSubmission(this, dateInput, {
                module: 'Purchase Bill / Stock Transfer',
                partyLabel: 'To Location:',
                partyName: toLocName,
                amount: totalAmt,
                dateFormatted: dateInput
            });

            if (!confirmed) {
                return;
            }
        }

        let valid = true;
        form.find('.is-invalid').removeClass('is-invalid');
        $('#hiddenSubmitContainer').remove();
        const hiddenContainer = $('<div id="hiddenSubmitContainer" style="display:none;"></div>');
        form.append(hiddenContainer);

        let submitIdx = 0;
        rows.each(function () {
            const row = $(this);
            const product = row.data('product');
            const variantId = row.find('.variant-select').val() || '';
            const qty = parseInt(row.find('.item-qty').val()) || 0;

            if (product.type === 'variable' && !variantId) {
                toastr.error('Please select variants for all variable products.');
                valid = false;
                return false;
            }
            if (qty <= 0) {
                toastr.error('Quantity must be greater than 0.');
                valid = false;
                return false;
            }
            const available = parseInt(row.data('available') || 0);
            if (qty > available) {
                toastr.error('Transfer quantity cannot be greater than available source stock.');
                valid = false;
                return false;
            }

            if (product.pair_product && product.custom_sizes && product.custom_sizes.length) {
                const customSizeValue = row.data('custom-size-value');
                if (!customSizeValue) {
                    toastr.error('Please select a size for each custom size product before saving.');
                    valid = false;
                    return false;
                }
                hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][custom_size_value]" value="${customSizeValue}">`);
            }

            const batchSel = row.find('.batch-select');
            let selPrice = null;
            let selItemId = null;
            if (batchSel.length) {
                const selOpt = batchSel.find('option:selected');
                if (selOpt.length) {
                    selPrice = selOpt.attr('data-purchase-price') !== undefined ? selOpt.attr('data-purchase-price') : selOpt.val();
                    selItemId = selOpt.attr('data-purchase-item-id');
                }
            }
            if ((selPrice === null || selPrice === undefined || selPrice === '') && row.data('selected-purchase-price') !== undefined) {
                selPrice = row.data('selected-purchase-price');
            }
            if (selPrice !== null && selPrice !== undefined && selPrice !== '') {
                hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][purchase_price]" value="${selPrice}">`);
            }
            if (selItemId) {
                hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][purchase_item_id]" value="${selItemId}">`);
            }

            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][product_id]" value="${product.id}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][product_variant_id]" value="${variantId}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][pair_type]" value="${row.find('.pair-type-input').val() || 'single'}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][quantity]" value="${qty}">`);
            submitIdx++;
        });

        if (!valid) {
            hiddenContainer.remove();
            return;
        }

        $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function (res) {
                if (typeof window.refreshPurchaseBillBadge === 'function') window.refreshPurchaseBillBadge();
                if (typeof window.refreshBranchLedgerBadge === 'function') window.refreshBranchLedgerBadge();
                toastr.success(res.message);
                setTimeout(() => window.location.href = '{{ route('admin.purchase-bills.index') }}', 800);
            },
            error: function (xhr) {
                hiddenContainer.remove();
                $('#submitBtn').prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Save Request');
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.message || {};
                    if (typeof errors === 'string') {
                        toastr.error(errors);
                        return;
                    }
                    const itemFieldLabels = {
                        product_id: 'Product',
                        product_variant_id: 'Variant',
                        pair_type: 'Pair Type',
                        quantity: 'Quantity',
                    };
                    let shownToastr = false;
                    $.each(errors, function (field, messages) {
                        const itemMatch = field.match(/^items\.(\d+)\.(.+)$/);
                        if (itemMatch) {
                            const itemNo = parseInt(itemMatch[1], 10) + 1;
                            const label = itemFieldLabels[itemMatch[2]] || itemMatch[2];
                            const friendly = String(messages[0]).replace(field, label);
                            toastr.error('Item ' + itemNo + ': ' + friendly);
                            shownToastr = true;
                            return;
                        }
                        const input = form.find('[name="' + field + '"]');
                        if (input.length) {
                            input.addClass('is-invalid');
                            input.siblings('.invalid-feedback').text(messages[0]).show();
                        } else {
                            toastr.error(messages[0]);
                            shownToastr = true;
                        }
                    });
                    if (!shownToastr && Object.keys(errors).length === 0) {
                        toastr.error('Something went wrong. Please try again.');
                    }
                } else {
                    toastr.error(xhr.responseJSON?.message || 'Something went wrong. Please try again.');
                }
            }
        });
    });

    // -------------------------------------------------------
    // Source Location change (super-admin only, since #fromLocation
    // is a fixed hidden input for branch-restricted users)
    // -------------------------------------------------------
    function syncDestinationOptions() {
        const sourceVal = $('#fromLocation').val();
        $('#toLocation option').each(function () {
            $(this).prop('disabled', $(this).val() !== '' && $(this).val() === sourceVal);
        });
        if (sourceVal && $('#toLocation').val() === sourceVal) {
            $('#toLocation').val('');
        }
    }
    syncDestinationOptions();

    $(document).on('change', 'select#fromLocation', function () {
        syncDestinationOptions();
        const sourceLocId = $('#fromLocation').val();
        $('#itemsBody .item-row').each(function () {
            const row = $(this);
            const product = row.data('product');
            if (product && product.type === 'variable' && product.variants) {
                const currentVarId = row.find('.variant-select').val();
                let options = `<option value="" disabled ${!currentVarId ? 'selected' : ''}>-- Select Variant --</option>`;
                product.variants.forEach(function (variant) {
                    const selected = currentVarId && currentVarId == variant.id ? 'selected' : '';
                    const price = variant.purchase_price != null ? variant.purchase_price : 0;

                    let vStock = 0;
                    if (sourceLocId && product.stock_by_location && product.stock_by_location[sourceLocId] && product.stock_by_location[sourceLocId].variants) {
                        vStock = parseInt(product.stock_by_location[sourceLocId].variants[variant.id]) || 0;
                    }
                    const isDisabled = (vStock <= 0 && !selected) ? 'disabled' : '';
                    const stockLabel = (sourceLocId && vStock <= 0) ? ' (Out of stock)' : '';

                    const label = `${variant.attr_name}: ${variant.value_name} (${symbol}${price})${stockLabel}`;
                    options += `<option value="${variant.id}" ${selected} ${isDisabled}>${label}</option>`;
                });
                row.find('.variant-select-container').html(`<select class="form-select form-select-sm variant-select mt-2 no-select2">${options}</select>`);
            }
            refreshRowStock(row);
        });
    });

});
</script>
@endsection
