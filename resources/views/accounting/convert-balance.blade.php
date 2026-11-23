<div class="text-center mb-4">
    <h3 class="mb-2">Convert Balance</h3>
    <p class="text-muted">Convert between Cash and Bank balance for a branch</p>
</div>

<form id="commonModalForm" action="{{ route('admin.accounting.convert-balance-store') }}" method="POST" class="d-flex flex-column flex-grow-1">
    @csrf
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="convertLocation">Branch <span class="text-danger">*</span></label>
            @php
                $user = auth()->user();
                $isSuperAdmin = $user->hasRole('super-admin');
                $userLocationId = $user->location_id;
            @endphp
            @if(!$isSuperAdmin && $userLocationId)
                @php
                    $userLoc = $locations->firstWhere('id', $userLocationId);
                @endphp
                <input type="hidden" name="location_id" value="{{ $userLocationId }}">
                <input type="text" class="form-control" value="{{ $userLoc->name ?? '' }} (Cash: {{ currency_symbol() }}{{ number_format($userLoc->balance->cash_balance ?? 0, 2) }}, Bank: {{ currency_symbol() }}{{ number_format($userLoc->balance->bank_balance ?? 0, 2) }})" readonly disabled />
            @else
                <select id="convertLocation" name="location_id" class="form-select">
                    <option value="">Select Branch</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}">
                            {{ $loc->name }} (Cash: {{ currency_symbol() }}{{ number_format($loc->balance->cash_balance ?? 0, 2) }}, Bank: {{ currency_symbol() }}{{ number_format($loc->balance->bank_balance ?? 0, 2) }})
                        </option>
                    @endforeach
                </select>
            @endif
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-12">
            <label class="form-label" for="conversionType">Conversion Type <span class="text-danger">*</span></label>
            <select id="conversionType" name="conversion_type" class="form-select no-select2">
                <option value="">Select Conversion Type</option>
                <option value="cash_to_bank">Cash to Bank</option>
                <option value="bank_to_cash">Bank to Cash</option>
            </select>
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-12">
            <label class="form-label" for="convertAmount">Amount <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text">{{ currency_symbol() }}</span>
                <input type="number" id="convertAmount" name="amount"
                    class="form-control" placeholder="0.00" step="0.01" min="0.01" autofocus />
                <div class="invalid-feedback"></div>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label" for="convertNotes">Notes</label>
            <textarea id="convertNotes" name="notes" class="form-control" rows="3" placeholder="Optional note for conversion"></textarea>
            <div class="invalid-feedback"></div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-auto pt-3 border-top">
        <button type="submit" class="btn btn-primary w-50">Convert Balance</button>
        <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
