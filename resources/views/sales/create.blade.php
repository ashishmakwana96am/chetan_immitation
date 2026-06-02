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
                            No items added yet. Click "Add Item" to start.
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
                            <option value="pending">Pending</option>
                            <option value="approve" selected>Approve</option>
                            <option value="decline">Decline</option>
                        </select>
                    </div>
                </div>

                <!-- Payment Status -->
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Payment Status</h5></div>
                    <div class="card-body">
                        <select name="payment_status" class="form-select no-select2">
                            <option value="pending" selected>Pending</option>
                            <option value="paid">Paid</option>
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

    // After adding customer via modal, reload customer dropdown
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

    searchInput.on('input', function() {
        const query = $(this).val().toLowerCase().trim();
        searchResults.empty();

        if (query.length === 0) {
            searchResults.hide();
            return;
        }

        const matchedProducts = allProducts.filter(p => 
            p.label.toLowerCase().includes(query)
        );

        if (matchedProducts.length === 0) {
            searchResults.html('<div class="list-group-item text-muted">No products found</div>');
            searchResults.show();
            return;
        }

        matchedProducts.forEach(p => {
            const item = $(`
                <a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center search-result-item bg-white" style="background-color: #ffffff;" data-id="${p.id}">
                    <div>
                        <div class="fw-semibold">${p.name}</div>
                        <small class="text-muted">SKU: ${p.sku}</small>
                    </div>
                    <span class="badge bg-label-primary">${symbol} ${formatPrice(p.price)}</span>
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
            addItemRow({
                product_id: product.id,
                price: product.price,
                quantity: 1
            }, product);
        }

        searchInput.val('');
        searchResults.hide().empty();
        searchInput.focus();
    });

    function addItemRow(data = null, productObj = null) {
        const template = document.getElementById('itemRowTemplate').innerHTML
            .replaceAll('__INDEX__', itemIndex);
        $('#itemsBody').append(template);
        $('#noItemsMsg').addClass('d-none');

        const row = $('#itemsBody .item-row').last();

        if (data) {
            const product = productObj || allProducts.find(p => p.id == data.product_id);
            row.find('.product-id-input').val(data.product_id);
            if (product) {
                row.find('.product-name-display').text(product.name);
                row.find('.product-sku-display').text('SKU: ' + product.sku);
            }
            row.find('.item-price').val(data.price);
            row.find('.item-qty').val(data.quantity);
            updateRowTotal(row);
            updateStockInfo(row);
        }

        itemIndex++;
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

        if (!productId || !locationId) {
            stockDisplay.text('').removeAttr('title').css('cursor', '');
            return;
        }

        $.get('{{ route('admin.inventory.stock') }}', { product_id: productId, location_id: locationId })
            .done(function (res) {
                const qty = res.data?.quantity ?? 0;
                const totalQty = res.data?.total_quantity ?? 0;
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
    // Sync product dropdowns (Removed as no longer applicable)
    // -------------------------------------------------------

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
        row.find('.item-total').text(symbol + ' ' + formatPrice(total));
        updateSummary();
    }

    function updateSummary() {
        let subtotalSum = 0;
        let discountSum = 0;
        let count       = 0;
        $('#itemsBody .item-row').each(function () {
            const price    = parseFloat($(this).find('.item-price').val()) || 0;
            const qty      = parseInt($(this).find('.item-qty').val()) || 0;
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

        if ($('#itemsBody .item-row').length === 0) {
            toastr.error('Please add at least one item.');
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
                    setTimeout(() => window.location.href = '{{ route('admin.sales.index') }}', 800);
                }
            },
            error   : function (xhr) {
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
