@extends('layouts.app')

@section('title', 'New Stock Transfer')

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
</style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-semibold mb-0">New Stock Transfer</h4>
        <a href="{{ route('admin.stock-transfers.index') }}" class="btn btn-label-secondary">
            <i class="ti ti-arrow-left me-1"></i> Back
        </a>
    </div>

    <form id="stockTransferForm" action="{{ route('admin.stock-transfers.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">Transfer Details</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Transfer No</label>
                                <input type="text" class="form-control" value="{{ $transferNo }}" disabled>
                                <small class="text-muted">Auto-generated on save</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Source Location <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" value="{{ $defaultLocation->name }}" disabled>
                                <input type="hidden" name="from_location_id" id="fromLocation" value="{{ $defaultLocation->id }}">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Destination Location <span class="text-danger">*</span></label>
                                <select name="to_location_id" id="toLocation" class="form-select">
                                    <option value="">Select Destination</option>
                                    @foreach($destinationLocations as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
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
                                <input type="text" id="productSearchInput" class="form-control" placeholder="Search product by name, SKU or barcode..." autocomplete="off">
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
                                        <th style="width: 140px; min-width: 140px;">Available</th>
                                        <th style="width: 140px; min-width: 140px;">Qty</th>
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
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">Summary</h5></div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total Items</span>
                            <span id="summaryItems" class="fw-semibold">0</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Total Qty</span>
                            <span id="summaryQty" class="fw-bold text-primary">0</span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 py-2 fs-5" id="submitBtn">
                            <i class="ti ti-device-floppy me-1"></i> Save Request
                        </button>
                        <a href="{{ route('admin.stock-transfers.index') }}" class="btn btn-label-secondary w-100 py-2 fs-5 mt-3">Cancel</a>
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
                    </div>
                </div>
            </td>
            <td class="align-middle">
                <span class="badge bg-label-secondary stock-info-display">Select source</span>
            </td>
            <td class="align-middle">
                <input type="number" class="form-control item-qty" min="1" value="1">
            </td>
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

    @php
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
                $data['variants'] = $p->variants->filter(fn($v) => $v->status == 1)->values()->map(function($v) {
                    return [
                        'id' => $v->id,
                        'purchase_price' => $v->purchase_price,
                        'attr_name' => $v->attributeValue->attribute->name ?? '',
                        'value_name' => $v->attributeValue->value ?? '',
                    ];
                })->all();
            }
            return $data;
        })->values()->all();
    @endphp

    const allProducts = @json($mappedProducts);
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
            (p.sku && String(p.sku).toLowerCase() === q) ||
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

    function addItemRow(product, selectedVariantId = null) {
        let initialVariantId = null;

        if (product.type !== 'variable' && hasDuplicate(product.id, null)) {
            toastr.warning('Product is already in the list.');
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
        row.find('.product-name-display').text(product.name);
        row.find('.product-sku-display').text(product.sku ? 'SKU: ' + product.sku : '');
        setProductImage(row.find('.product-image-container'), product);

        if (product.type === 'variable' && product.variants && product.variants.length) {
            let options = '';
            product.variants.forEach(function (variant) {
                const selected = initialVariantId == variant.id ? 'selected' : '';
                const price = variant.purchase_price != null ? variant.purchase_price : 0;
                const label = `${variant.attr_name}: ${variant.value_name} (${symbol}${price})`;
                options += `<option value="${variant.id}" ${selected}>${label}</option>`;
            });
            row.find('.variant-select-container').html(`<select class="form-select form-select-sm variant-select mt-2 no-select2">${options}</select>`);
            row.data('variant-id', row.find('.variant-select').val() || null);
        }

        $('#itemsBody').append(row);
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
            row.data('available', qty);
            row.find('.stock-info-display')
                .removeClass('bg-label-secondary bg-label-warning bg-label-danger')
                .addClass(qty > 0 ? 'bg-label-success' : 'bg-label-danger')
                .attr('title', titleText)
                .css('cursor', 'help')
                .text(qty + ' pcs');
        }).fail(function () {
            row.find('.stock-info-display').removeClass('bg-label-success bg-label-secondary').addClass('bg-label-danger').text('N/A');
        });
    }

    function updateSummary() {
        let items = 0;
        let qty = 0;
        $('.item-row').each(function () {
            const rowQty = parseInt($(this).find('.item-qty').val()) || 0;
            if (rowQty > 0) {
                items++;
                qty += rowQty;
            }
        });
        $('#summaryItems').text(items);
        $('#summaryQty').text(qty);
        $('#noItemsMsg').toggle(items === 0);
        $('#itemsTable').toggle(items > 0);
    }

    function selectSearchProduct(product) {
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
            if (firstItem.length) firstItem.click();
        }
    });

    searchInput.on('input', function() {
        const query = $(this).val().toLowerCase().trim();
        searchResults.empty();
        if (!query) {
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
                            <small class="text-muted">SKU: ${product.sku || '-'}${product.barcode ? ' | Barcode: ' + product.barcode : ''}</small>
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
        refreshRowStock(row);
    });

    $(document).on('input', '.item-qty', updateSummary);
    $(document).on('click', '.remove-item-btn', function () {
        $(this).closest('.item-row').remove();
        updateSummary();
    });

    $('#stockTransferForm').on('submit', function (e) {
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

            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][product_id]" value="${product.id}">`);
            hiddenContainer.append(`<input type="hidden" name="items[${submitIdx}][product_variant_id]" value="${variantId}">`);
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
                toastr.success(res.message);
                setTimeout(() => window.location.href = '{{ route('admin.stock-transfers.index') }}', 800);
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
                    $.each(errors, function (field, messages) {
                        const input = form.find('[name="' + field + '"]');
                        if (input.length) {
                            input.addClass('is-invalid');
                            input.siblings('.invalid-feedback').text(messages[0]).show();
                        } else {
                            toastr.error(messages[0]);
                        }
                    });
                } else {
                    toastr.error(xhr.responseJSON?.message || 'Something went wrong. Please try again.');
                }
            }
        });
    });

});
</script>
@endsection
