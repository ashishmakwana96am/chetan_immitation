<!-- Purchase Return & Dependency Impact Bootstrap Modal -->
<div class="modal fade" id="purchaseReturnImpactModal" tabindex="-1" aria-labelledby="purchaseReturnImpactModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold d-flex align-items-center" id="purchaseReturnImpactModalLabel">
                    <i class="ti ti-rotate text-danger me-2"></i>
                    <span>Return Purchase <code class="ms-1" id="returnModalPurchaseInvoiceNo">-</code></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <!-- Transfers Section -->
                <div id="returnModalTransfersSection" class="mb-4 d-none">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold text-primary mb-0 d-flex align-items-center">
                            <i class="ti ti-truck-delivery fs-5 me-2"></i>
                            Stock Transferred to Other Branches (<span id="returnModalTransfersCount">0</span>)
                        </h6>
                    </div>
                    <p class="text-muted small mb-2">
                        Stock from this purchase was transferred via Purchase Bills. Review dependencies below:
                    </p>
                    <div class="table-responsive border rounded">
                        <table class="table table-sm table-hover table-striped align-middle mb-0" style="font-size: 0.82rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="min-width: 120px;">Transfer No</th>
                                    <th style="min-width: 160px;">Destination Branch</th>
                                    <th style="min-width: 220px;">Items & Qty</th>
                                    <th style="min-width: 140px;">Transfer Date</th>
                                    <th style="min-width: 90px;" class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="returnModalTransfersTableBody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Sales Orders Section -->
                <div id="returnModalSalesSection" class="mb-4 d-none">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold text-danger mb-0 d-flex align-items-center">
                            <i class="ti ti-shopping-cart fs-5 me-2"></i>
                            Associated Sales Orders Found (<span id="returnModalSalesCount">0</span>)
                        </h6>
                    </div>
                    <p class="text-muted small mb-2">
                        Sales have been recorded for the items in this purchase. Please choose how to handle this return:
                    </p>
                    <div class="table-responsive border rounded">
                        <table class="table table-sm table-hover table-striped align-middle mb-0" style="font-size: 0.82rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="min-width: 120px;">Order No</th>
                                    <th style="min-width: 130px;">Customer</th>
                                    <th style="min-width: 140px;">Branch</th>
                                    <th style="min-width: 200px;">Items Sold</th>
                                    <th style="min-width: 130px;">Date</th>
                                    <th style="min-width: 100px;" class="text-end">Order Total</th>
                                    <th style="min-width: 80px;" class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="returnModalSalesTableBody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Return Action Choices (if transfers or sales exist) -->
                <div id="returnModalDecisionSection" class="d-none">
                    <label class="form-label fw-bold text-dark mb-2">Select Return Action:</label>
                    <div class="list-group">
                        <label class="list-group-item list-group-item-action d-flex align-items-start p-3 border rounded mb-2 cursor-pointer">
                            <input class="form-check-input me-3 mt-1 flex-shrink-0" type="radio" name="return_type_choice" id="choiceReturnPurchaseOnly" value="purchase_only" checked>
                            <div class="w-100">
                                <div class="fw-bold text-dark mb-1 d-flex align-items-center">
                                    <i class="ti ti-shield-check text-success me-1"></i> Return Purchase Only (Keep Transfers & Sales Intact)
                                </div>
                                <div class="text-muted small">
                                    Only returns stock currently available at the purchase branch. Does not remove or alter stock transferred to other branches or already sold. Paid amount is refunded to branch balance.
                                </div>
                            </div>
                        </label>

                        <label class="list-group-item list-group-item-action d-flex align-items-start p-3 border rounded border-danger-subtle bg-danger-subtle bg-opacity-10 cursor-pointer">
                            <input class="form-check-input me-3 mt-1 flex-shrink-0" type="radio" name="return_type_choice" id="choiceReturnCascade" value="cascade_all">
                            <div class="w-100">
                                <div class="fw-bold text-danger mb-1 d-flex align-items-center">
                                    <i class="ti ti-refresh text-danger me-1"></i> Return Purchase, Purchase Bills & Sales (Full Cascade Reversal)
                                </div>
                                <div class="text-muted small">
                                    Deducts transferred stock across all destination branches, removes purchase items from associated sales orders (recalculating customer balances), and refunds paid amount.
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- No Sales/Transfers Notice -->
                <div id="returnModalSimpleNotice" class="text-center py-3 d-none">
                    <p class="text-muted mb-0">Are you sure you want to return this purchase? Stock will be deducted from inventory and paid amount will be refunded to location balance.</p>
                </div>
            </div>

            <div class="modal-footer border-top py-3">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="modalConfirmReturnBtn">
                    <i class="ti ti-rotate me-1"></i> Confirm & Return
                </button>
            </div>
        </div>
    </div>
</div>
