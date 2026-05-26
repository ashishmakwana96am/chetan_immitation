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
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Purchase Items</h5>
                        <button type="button" class="btn btn-sm btn-primary" id="addItemBtn">
                            <i class="ti ti-plus me-1"></i> Add Item
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th width="160">Price</th>
                                        <th width="100">Qty</th>
                                        <th width="140">Total</th>
                                        <th width="40"></th>
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
                <div class="card mb-4" id="allocationCard" style="display:none!important;">
                    <div class="card-header">
                        <h5 class="mb-0">Location Allocation</h5>
                        <small class="text-muted">Allocate each item's quantity across locations. Total allocated must equal item quantity.</small>
                    </div>
                    <div class="card-body" id="allocationBody">
                        <!-- populated by JS -->
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

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="ti ti-device-floppy me-1"></i> Save as Draft
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
                <select name="items[__INDEX__][product_id]" class="form-select form-select-sm product-select">
                    <option value="">-- Select Product --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}"
                            data-price="{{ $product->purchase_price }}"
                            data-name="{{ $product->name }}">
                            {{ $product->name }} ({{ $product->sku }})
                        </option>
                    @endforeach
                </select>
                <div class="invalid-feedback"></div>
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
                <input type="number" name="items[__INDEX__][quantity]"
                    class="form-control form-control-sm item-qty"
                    placeholder="0" min="1" value="1" />
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
@endsection

@section('page-js')
<script>
$(document).ready(function () {

    let itemIndex = 0;
    const symbol  = '{{ currency_symbol() }}';
    const locations = @json($locations->map(fn($l) => ['id' => $l->id, 'name' => $l->name]));

    // -------------------------------------------------------
    // Add Item Row
    // -------------------------------------------------------
    $('#addItemBtn').on('click', function () {
        addItemRow();
    });

    function addItemRow(data = null) {
        const template = document.getElementById('itemRowTemplate').innerHTML
            .replaceAll('__INDEX__', itemIndex);

        $('#itemsBody').append(template);
        $('#noItemsMsg').addClass('d-none');

        const row = $('#itemsBody .item-row').last();

        if (data) {
            row.find('.product-select').val(data.product_id).trigger('change');
            row.find('.purchase-price').val(data.purchase_price);
            row.find('.item-qty').val(data.quantity);
            updateRowTotal(row);
        }

        itemIndex++;
        syncProductDropdowns();
        updateGrandTotal();
    }

    // -------------------------------------------------------
    // Remove Item Row
    // -------------------------------------------------------
    $(document).on('click', '.remove-item-btn', function () {
        const row = $(this).closest('.item-row');
        const idx = row.data('index');
        row.remove();
        $('#allocation-item-' + idx).remove();

        if ($('#itemsBody .item-row').length === 0) {
            $('#noItemsMsg').removeClass('d-none');
            $('#allocationCard').hide();
        }
        syncProductDropdowns();
        updateGrandTotal();
    });

    // -------------------------------------------------------
    // Product Select — auto-fill price, update allocation
    // -------------------------------------------------------
    $(document).on('change', '.product-select', function () {
        const row   = $(this).closest('.item-row');
        const price = $(this).find(':selected').data('price') || 0;
        const name  = $(this).find(':selected').data('name') || '';
        row.find('.purchase-price').val(price);
        row.data('product-name', name);
        updateRowTotal(row);
        syncProductDropdowns();
        renderAllocationSection();
    });

    // -------------------------------------------------------
    // Sync dropdowns — hide selected products in other rows
    // -------------------------------------------------------
    function syncProductDropdowns() {
        // Collect all selected product IDs
        const selected = [];
        $('.product-select').each(function () {
            const val = $(this).val();
            if (val) selected.push(val);
        });

        // Show all options first, then hide selected ones in other rows
        $('.product-select').each(function () {
            const currentVal = $(this).val();
            $(this).find('option').each(function () {
                const optVal = $(this).val();
                if (!optVal) return; // keep placeholder
                if (optVal === currentVal) {
                    $(this).show(); // always show own selection
                } else if (selected.includes(optVal)) {
                    $(this).hide(); // hide if selected elsewhere
                } else {
                    $(this).show();
                }
            });
        });
    }

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
        const price = parseFloat(row.find('.purchase-price').val()) || 0;
        const qty   = parseInt(row.find('.item-qty').val()) || 0;
        row.find('.item-total').text(symbol + ' ' + (price * qty).toFixed(2));
        updateGrandTotal();
    }

    function updateGrandTotal() {
        let grand = 0, count = 0;
        $('.item-row').each(function () {
            grand += (parseFloat($(this).find('.purchase-price').val()) || 0)
                   * (parseInt($(this).find('.item-qty').val()) || 0);
            count++;
        });
        $('#grandTotal, #summaryTotal').text(symbol + ' ' + grand.toFixed(2));
        $('#summaryItems').text(count);
    }

    // -------------------------------------------------------
    // Render full allocation section
    // -------------------------------------------------------
    function renderAllocationSection() {
        $('#allocationBody').empty();
        let hasItems = false;

        $('#itemsBody .item-row').each(function () {
            const row        = $(this);
            const idx        = row.data('index');
            const productId  = row.find('.product-select').val();
            const productName = row.find('.product-select option:selected').data('name') || row.find('.product-select option:selected').text();
            const qty        = parseInt(row.find('.item-qty').val()) || 0;

            if (!productId || qty <= 0) return;

            hasItems = true;

            const block = $('<div>', { id: 'allocation-item-' + idx, class: 'mb-4 pb-4 border-bottom' });

            block.append(`
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="fw-semibold">${productName}</span>
                        <span class="badge bg-label-primary ms-2">Overall Qty: ${qty}</span>
                    </div>
                    <span class="text-muted small alloc-remaining-${idx}">Remaining: <strong>${qty}</strong></span>
                </div>
            `);

            const locationsHtml = $('<div>', { class: 'row g-2' });

            locations.forEach(function (loc, locIdx) {
                locationsHtml.append(`
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2">
                            <label class="form-label mb-0 text-nowrap" style="min-width:100px;">${loc.name}</label>
                            <input type="number"
                                name="items[${idx}][allocations][${locIdx}][quantity]"
                                class="form-control form-control-sm alloc-qty"
                                data-item-idx="${idx}"
                                placeholder="0" min="0" value="0" />
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
        const idx  = row.data('index');
        const qty  = parseInt(row.find('.item-qty').val()) || 0;
        const name = row.find('.product-select option:selected').data('name') || '';

        $('#allocation-item-' + idx + ' .badge').text('Overall Qty: ' + qty);
        updateRemainingQty(idx, qty);
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
        const el = $('.alloc-remaining-' + idx + ' strong');
        el.text(remaining);
        el.closest('span').toggleClass('text-danger', remaining !== 0).toggleClass('text-success', remaining === 0);
    }

    // -------------------------------------------------------
    // Validate allocations
    // -------------------------------------------------------
    function validateAllocations() {
        let valid = true;

        $('#itemsBody .item-row').each(function () {
            const row     = $(this);
            const idx     = row.data('index');
            const itemQty = parseInt(row.find('.item-qty').val()) || 0;
            const productId = row.find('.product-select').val();

            if (!productId) return;

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

        if ($('#itemsBody .item-row').length === 0) {
            toastr.error('Please add at least one item.');
            return;
        }

        if (!validateAllocations()) {
            toastr.error('Please fix allocation quantities before saving.');
            return;
        }

        const form = $(this);
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').text('');
        $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        $.ajax({
            url     : form.attr('action'),
            type    : 'POST',
            data    : form.serialize(),
            success : function (res) {
                if (res.status === 'success') {
                    toastr.success(res.message);
                    setTimeout(() => window.location.href = '{{ route('admin.purchases.index') }}', 800);
                }
            },
            error   : function (xhr) {
                $('#submitBtn').prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Save as Draft');
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
