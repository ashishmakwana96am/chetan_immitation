<div class="text-center mb-4">
    <h3 class="mb-2">Transfer Balance</h3>
    <p class="text-muted">Transfer cash or bank balance from one location to another</p>
</div>

<form id="commonModalForm" action="{{ route('admin.accounting.opening-balances.transfer-store') }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="fromLocation">From Location <span class="text-danger">*</span></label>
            <select id="fromLocation" name="from_location_id" class="form-select">
                <option value="">Select From Location</option>
                @foreach($locations as $loc)
                    <option value="{{ $loc->id }}">
                        {{ $loc->name }} (Cash: {{ currency_symbol() }}{{ number_format($loc->balance->cash_balance ?? 0, 2) }}, Bank: {{ currency_symbol() }}{{ number_format($loc->balance->bank_balance ?? 0, 2) }})
                    </option>
                @endforeach
            </select>
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="toLocation">To Location <span class="text-danger">*</span></label>
            <select id="toLocation" name="to_location_id" class="form-select">
                <option value="">Select To Location</option>
                @foreach($locations as $loc)
                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                @endforeach
            </select>
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-12">
            <label class="form-label" for="transferBalanceType">Balance Type <span class="text-danger">*</span></label>
            <select id="transferBalanceType" name="balance_type" class="form-select no-select2">
                <option value="">Select Balance Type</option>
                <option value="cash">Cash</option>
                <option value="bank">Bank</option>
            </select>
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-12">
            <label class="form-label" for="transferAmount">Amount <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text">{{ currency_symbol() }}</span>
                <input type="number" id="transferAmount" name="amount"
                    class="form-control" placeholder="0.00" step="0.01" min="0.01" autofocus />
                <div class="invalid-feedback"></div>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label" for="transferNotes">Notes</label>
            <textarea id="transferNotes" name="notes" class="form-control" rows="3" placeholder="Optional note for transfer"></textarea>
            <div class="invalid-feedback"></div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Transfer Balance</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
