<div class="text-center mb-4">
    <h3 class="mb-2">Edit Payment Collection</h3>
    <p class="text-muted">Update collection details</p>
</div>

<form id="commonModalForm" action="{{ route('admin.reports.daily-report.collections.update', $salePayment) }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="edit_payment_method">Payment Method <span class="text-danger">*</span></label>
            <select class="form-select no-select2" id="edit_payment_method" name="payment_method" required>
                <option value="cash" {{ $salePayment->payment_method === 'cash' ? 'selected' : '' }}>Cash</option>
                <option value="online" {{ $salePayment->payment_method === 'online' ? 'selected' : '' }}>Online</option>
                <option value="online_cash" {{ $salePayment->payment_method === 'online_cash' ? 'selected' : '' }}>Cash + Online</option>
            </select>
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-12" id="editCashCol">
            <label class="form-label" for="edit_cash_amount">Cash Amount <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text">{{ currency_symbol() }}</span>
                <input type="number" step="0.01" min="0" max="{{ $maxAllowed }}" class="form-control" id="edit_cash_amount" name="cash_amount" value="{{ (float) $salePayment->cash_amount > 0 ? $salePayment->cash_amount : '' }}" placeholder="0.00" />
                <div class="invalid-feedback"></div>
            </div>
        </div>

        <div class="col-12" id="editOnlineCol">
            <label class="form-label" for="edit_online_amount">Online Amount <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text">{{ currency_symbol() }}</span>
                <input type="number" step="0.01" min="0" max="{{ $maxAllowed }}" class="form-control" id="edit_online_amount" name="online_amount" value="{{ (float) $salePayment->online_amount > 0 ? $salePayment->online_amount : '' }}" placeholder="0.00" />
                <div class="invalid-feedback"></div>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label" for="edit_notes">Notes / Reference</label>
            <textarea class="form-control" id="edit_notes" name="notes" rows="3" placeholder="Cheque no, UPI ref, etc...">{{ $salePayment->notes }}</textarea>
            <div class="invalid-feedback"></div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Update</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>

<script>
    (function () {
        var maxAllowed = {{ (float) $maxAllowed }};

        function syncSplit(source) {
            var m = $('#edit_payment_method').val();
            if (m === 'cash') {
                var c = parseFloat($('#edit_cash_amount').val()) || 0;
                if (c > maxAllowed) {
                    $('#edit_cash_amount').val(maxAllowed);
                } else if (c < 0) {
                    $('#edit_cash_amount').val(0);
                }
                $('#edit_online_amount').val(0);
                return;
            }
            if (m === 'online') {
                var o = parseFloat($('#edit_online_amount').val()) || 0;
                if (o > maxAllowed) {
                    $('#edit_online_amount').val(maxAllowed);
                } else if (o < 0) {
                    $('#edit_online_amount').val(0);
                }
                $('#edit_cash_amount').val(0);
                return;
            }
            if (m === 'online_cash') {
                if (source === 'online') {
                    var valStr = $('#edit_online_amount').val();
                    if (valStr === '') {
                        $('#edit_cash_amount').val(maxAllowed);
                        return;
                    }
                    var o = parseFloat(valStr) || 0;
                    if (o < 0) {
                        o = 0;
                        $('#edit_online_amount').val(0);
                    }
                    if (o > maxAllowed) {
                        o = maxAllowed;
                        $('#edit_online_amount').val(maxAllowed);
                    }
                    var c = Math.max(0, Math.round((maxAllowed - o) * 100) / 100);
                    $('#edit_cash_amount').val(c);
                } else if (source === 'cash') {
                    var valStr = $('#edit_cash_amount').val();
                    if (valStr === '') {
                        $('#edit_online_amount').val(maxAllowed);
                        return;
                    }
                    var c = parseFloat(valStr) || 0;
                    if (c < 0) {
                        c = 0;
                        $('#edit_cash_amount').val(0);
                    }
                    if (c > maxAllowed) {
                        c = maxAllowed;
                        $('#edit_cash_amount').val(maxAllowed);
                    }
                    var o = Math.max(0, Math.round((maxAllowed - c) * 100) / 100);
                    $('#edit_online_amount').val(o);
                }
            }
        }

        function syncMethodFields() {
            var m = $('#edit_payment_method').val();
            if (m === 'cash') {
                $('#editCashCol').show();
                $('#editOnlineCol').hide();
                var c = parseFloat($('#edit_cash_amount').val()) || 0;
                if (c <= 0 || c > maxAllowed) {
                    $('#edit_cash_amount').val(maxAllowed);
                }
                $('#edit_online_amount').val(0);
            } else if (m === 'online') {
                $('#editOnlineCol').show();
                $('#editCashCol').hide();
                var o = parseFloat($('#edit_online_amount').val()) || 0;
                if (o <= 0 || o > maxAllowed) {
                    $('#edit_online_amount').val(maxAllowed);
                }
                $('#edit_cash_amount').val(0);
            } else {
                $('#editCashCol').show();
                $('#editOnlineCol').show();
                var c = parseFloat($('#edit_cash_amount').val()) || 0;
                var o = parseFloat($('#edit_online_amount').val()) || 0;
                if (c <= 0 && o <= 0) {
                    $('#edit_cash_amount').val(maxAllowed);
                    $('#edit_online_amount').val(0);
                } else if (Math.round((c + o) * 100) / 100 !== maxAllowed) {
                    if (o > 0 && o <= maxAllowed) {
                        $('#edit_cash_amount').val(Math.max(0, Math.round((maxAllowed - o) * 100) / 100));
                    } else if (c > 0 && c <= maxAllowed) {
                        $('#edit_online_amount').val(Math.max(0, Math.round((maxAllowed - c) * 100) / 100));
                    } else {
                        $('#edit_cash_amount').val(maxAllowed);
                        $('#edit_online_amount').val(0);
                    }
                }
            }
        }

        $('#edit_payment_method').on('change', syncMethodFields);
        $('#edit_online_amount').on('input', function () {
            syncSplit('online');
        });
        $('#edit_cash_amount').on('input', function () {
            syncSplit('cash');
        });

        $('#commonModalForm').on('submit', function (e) {
            var m = $('#edit_payment_method').val();
            var c = parseFloat($('#edit_cash_amount').val()) || 0;
            var o = parseFloat($('#edit_online_amount').val()) || 0;
            if (m === 'cash') {
                o = 0;
                $('#edit_online_amount').val(0);
            } else if (m === 'online') {
                c = 0;
                $('#edit_cash_amount').val(0);
            }
            var sum = Math.round((c + o) * 100) / 100;
            if (sum <= 0) {
                e.preventDefault();
                e.stopImmediatePropagation();
                if (typeof toastr !== 'undefined') {
                    toastr.error('Total collected amount must be greater than 0.');
                }
                return false;
            }
            if (sum > (maxAllowed + 0.01)) {
                e.preventDefault();
                e.stopImmediatePropagation();
                if (m === 'cash') {
                    $('#edit_cash_amount').val(maxAllowed);
                } else if (m === 'online') {
                    $('#edit_online_amount').val(maxAllowed);
                } else {
                    if (o > maxAllowed) {
                        $('#edit_online_amount').val(maxAllowed);
                        $('#edit_cash_amount').val(0);
                    } else {
                        $('#edit_cash_amount').val(Math.max(0, Math.round((maxAllowed - o) * 100) / 100));
                    }
                }
                return false;
            }
        });

        syncMethodFields();
    })();
</script>