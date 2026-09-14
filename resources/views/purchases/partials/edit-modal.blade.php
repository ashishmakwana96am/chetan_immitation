<!-- Purchase Edit Impact Confirmation Bootstrap Modal -->
<div class="modal fade" id="purchaseEditImpactModal" tabindex="-1" aria-labelledby="purchaseEditImpactModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-semibold d-flex align-items-center mb-0" id="purchaseEditImpactModalLabel">
                    <i class="ti ti-alert-triangle text-warning me-2 fs-4"></i>
                    <span>Confirm Purchase Update Impact <code class="ms-1" id="editModalPurchaseInvoiceNo">-</code></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <!-- Summary Alert -->
                <div class="alert alert-warning d-flex align-items-start mb-4" role="alert">
                    <i class="ti ti-info-circle fs-4 me-2 flex-shrink-0 mt-1"></i>
                    <div>
                        <h6 class="alert-heading fw-bold mb-1">Impact Analysis Before Saving</h6>
                        <p class="mb-0 small">
                            Please review the changes and linked records below before saving. Stock updates will automatically be adjusted while preserving completed sales and branch transfers.
                        </p>
                    </div>
                </div>

                <!-- Items Changed Summary -->
                <div class="card border shadow-none mb-4">
                    <div class="card-header bg-light py-2 px-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center">
                            <i class="ti ti-list-details me-2 text-primary"></i> Items & Stock Adjustment Summary
                        </h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Old Qty</th>
                                    <th class="text-center">New Qty</th>
                                    <th class="text-center">Stock Change</th>
                                    <th class="text-end">Old Price</th>
                                    <th class="text-end">New Price</th>
                                </tr>
                            </thead>
                            <tbody id="editModalItemsTableBody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Transfers Section -->
                <div id="editModalTransfersSection" class="mb-4 d-none">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold text-primary mb-0 d-flex align-items-center">
                            <i class="ti ti-truck-delivery fs-5 me-2"></i>
                            Stock Transferred to Branch Locations (<span id="editModalTransfersCount">0</span>)
                        </h6>
                    </div>
                    <p class="text-muted small mb-2">
                        Items from this purchase were transferred to branches via Purchase Bills. Branch stock allocations will remain aligned with transferred quantities.
                    </p>
                    <div class="table-responsive border rounded mb-0">
                        <table class="table table-sm table-hover table-striped align-middle mb-0" style="font-size: 0.82rem;">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 120px;">Transfer No</th>
                                    <th style="min-width: 160px;">Destination Branch</th>
                                    <th style="min-width: 220px;">Items & Qty</th>
                                    <th style="min-width: 140px;">Transfer Date</th>
                                    <th style="min-width: 90px;" class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="editModalTransfersTableBody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Sales Orders Section -->
                <div id="editModalSalesSection" class="mb-4 d-none">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold text-success mb-0 d-flex align-items-center">
                            <i class="ti ti-shopping-cart fs-5 me-2"></i>
                            Associated Sales Orders Found (<span id="editModalSalesCount">0</span>)
                        </h6>
                    </div>
                    <p class="text-muted small mb-2">
                        The following sales orders have used stock from this purchase batch. These sales will remain intact:
                    </p>
                    <div class="table-responsive border rounded mb-0">
                        <table class="table table-sm table-hover table-striped align-middle mb-0" style="font-size: 0.82rem;">
                            <thead class="table-light">
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
                            <tbody id="editModalSalesTableBody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Confirmation Notice -->
                <div class="border-top pt-3 text-center">
                    <p class="text-dark fw-semibold mb-0">
                        Do you want to confirm and proceed with these updates?
                    </p>
                </div>
            </div>

            <div class="modal-footer border-top py-3">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" id="editModalConfirmSaveBtn">
                    <i class="ti ti-device-floppy me-1"></i> Confirm & Save Changes
                </button>
            </div>
        </div>
    </div>
</div>
