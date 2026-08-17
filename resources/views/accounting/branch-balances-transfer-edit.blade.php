<div class="text-center mb-4">
    <h3 class="mb-2">Edit Balance Transfer</h3>
    <p class="text-muted">Update balance transfer details for {{ $transfer->transfer_no }}</p>
</div>

<form id="commonModalForm" action="{{ route('admin.accounting.opening-balances.transfer-update', $transfer->id) }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="fromLocation">From Location <span class="text-danger">*</span></label>
            @php
                $user = auth()->user();
                $isSuperAdmin = $user->hasRole('super-admin');
                $userLocationId = $user->location_id;
            @endphp
            @if(!$isSuperAdmin && $userLocationId)
                @php
                    $fromLoc = $locations->firstWhere('id', $transfer->from_location_id);
                @endphp
                <input type="hidden" name="from_location_id" value="{{ $transfer->from_location_id }}">
                <input type="text" class="form-control" value="{{ $fromLoc->name ?? '' }} (Cash: {{ currency_symbol() }}{{ number_format($fromLoc->balance->cash_balance ?? 0, 2) }}, Bank: {{ currency_symbol() }}{{ number_format($fromLoc->balance->bank_balance ?? 0, 2) }})" readonly disabled />
            @else
                <select id="fromLocation" name="from_location_id" class="form-select">
                    <option value="">Select From Location</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ (int)$transfer->from_location_id === (int)$loc->id ? 'selected' : '' }}>
                            {{ $loc->name }} (Cash: {{ currency_symbol() }}{{ number_format($loc->balance->cash_balance ?? 0, 2) }}, Bank: {{ currency_symbol() }}{{ number_format($loc->balance->bank_balance ?? 0, 2) }})
                        </option>
                    @endforeach
                </select>
            @endif
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="toLocation">To Location <span class="text-danger">*</span></label>
            <select id="toLocation" name="to_location_id" class="form-select">
                <option value="">Select To Location</option>
                @foreach($locations as $loc)
                    @if(!$isSuperAdmin && $userLocationId && (int)$loc->id === (int)$userLocationId)
                        @continue
                    @endif
                    <option value="{{ $loc->id }}" {{ (int)$transfer->to_location_id === (int)$loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                @endforeach
            </select>
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-12">
            <label class="form-label" for="transferBalanceType">Balance Type <span class="text-danger">*</span></label>
            <select id="transferBalanceType" name="balance_type" class="form-select no-select2">
                <option value="">Select Balance Type</option>
                <option value="cash" {{ $transfer->balance_type === 'cash' ? 'selected' : '' }}>Cash</option>
                <option value="bank" {{ $transfer->balance_type === 'bank' ? 'selected' : '' }}>Bank</option>
            </select>
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-12">
            <label class="form-label" for="transferAmount">Amount <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text">{{ currency_symbol() }}</span>
                <input type="number" id="transferAmount" name="amount"
                    class="form-control" value="{{ $transfer->amount }}" step="0.01" min="0.01" autofocus />
                <div class="invalid-feedback"></div>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label" for="transferNotes">Notes</label>
            <textarea id="transferNotes" name="notes" class="form-control" rows="3" placeholder="Optional note for transfer">{{ $transfer->notes }}</textarea>
            <div class="invalid-feedback"></div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Update Transfer</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>

<script>
    $(document).ready(function () {
        function filterToLocations() {
            const selectedFrom = $('#fromLocation').val();
            $('#toLocation option').each(function () {
                const val = $(this).val();
                if (val && val === selectedFrom) {
                    $(this).addClass('d-none').prop('disabled', true);
                    if ($('#toLocation').val() === val) {
                        $('#toLocation').val('');
                    }
                } else {
                    $(this).removeClass('d-none').prop('disabled', false);
                }
            });
        }

        $(document).on('change', '#fromLocation', filterToLocations);
        filterToLocations();
    });
</script>
