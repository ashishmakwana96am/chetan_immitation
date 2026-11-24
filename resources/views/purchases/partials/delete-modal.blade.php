<style>
    .modal-impact-tabs-header {
        border-bottom: 1px solid rgba(75, 70, 92, 0.08) !important;
        background: transparent !important;
        padding: 0 !important;
        margin-bottom: 1rem !important;
    }
    .modal-impact-tabs {
        border-bottom: none !important;
        margin: 0 !important;
        padding: 0 !important;
        display: flex !important;
        flex-wrap: nowrap !important;
        overflow-x: auto !important;
        scrollbar-width: none !important;
    }
    .modal-impact-tabs::-webkit-scrollbar {
        display: none !important;
    }
    .modal-impact-tabs .nav-link {
        padding: 0.65rem 1.15rem !important;
        margin-top: 0 !important;
        margin-bottom: -1px !important;
        font-size: 0.875rem !important;
        font-weight: 500 !important;
        color: #6f6b7d !important;
        border: none !important;
        border-bottom: 2px solid transparent !important;
        border-radius: 0 !important;
        background: transparent !important;
        transition: color 0.15s, border-color 0.15s;
        white-space: nowrap !important;
        display: inline-flex !important;
        align-items: center !important;
        box-shadow: none !important;
    }
    .modal-impact-tabs .nav-link:hover {
        color: #B4771E !important;
        border-bottom-color: rgba(180, 119, 30, 0.4) !important;
        background: transparent !important;
    }
    .modal-impact-tabs .nav-link.active {
        font-weight: 600 !important;
        color: #B4771E !important;
        border-bottom: 2px solid #B4771E !important;
        background: transparent !important;
        box-shadow: none !important;
    }
    .modal-impact-tabs .nav-link i {
        font-size: 1.05rem !important;
        vertical-align: text-bottom !important;
    }
    .modal-impact-tabs .nav-link .badge {
        font-size: 0.72rem !important;
        padding: 0.2rem 0.5rem !important;
        border-radius: 50rem !important;
    }
    .dependency-items-scroll::-webkit-scrollbar {
        width: 4px;
    }
    .dependency-items-scroll::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 4px;
    }
    .dependency-items-scroll::-webkit-scrollbar-track {
        background: transparent;
    }
    .dependency-item-pill {
        background: #FAF7F2;
        color: #B4771E;
        border: 1px solid #EFE7DB;
        font-weight: 600;
        font-size: 0.72rem;
        padding: 2px 7px;
        border-radius: 4px;
        white-space: nowrap;
    }
    .dependency-table-card .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 3px 9px !important;
        font-size: 0.8rem;
    }
    .dependency-table-card .dataTables_wrapper .dataTables_filter input {
        font-size: 0.8rem;
        padding: 4px 10px;
        border-radius: 6px;
        border: 1px solid #d8d6de;
    }
    .dependency-table-card .dataTables_wrapper .dataTables_length select {
        font-size: 0.8rem;
        padding: 3px 24px 3px 8px;
        border-radius: 6px;
    }
</style>

<div class="modal fade" id="purchaseDeleteImpactModal" tabindex="-1" aria-labelledby="purchaseDeleteImpactModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold d-flex align-items-center mb-0" id="purchaseDeleteImpactModalLabel">
                    <span class="avatar avatar-sm bg-label-danger me-2 rounded d-flex align-items-center justify-content-center">
                        <i class="ti ti-trash fs-5"></i>
                    </span>
                    <span>Delete Purchase <code class="ms-1 fs-6 text-danger" id="modalPurchaseInvoiceNo">-</code></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div id="deleteModalTabsWrapper" class="d-none">
                    <div class="border-bottom modal-impact-tabs-header mb-3">
                        <ul class="nav nav-tabs card-header-tabs modal-impact-tabs" id="deleteImpactTabs" role="tablist">
                            <li class="nav-item" role="presentation" id="deleteTabItemTransfers">
                                <button class="nav-link active" id="deleteTabBtnTransfers" data-bs-toggle="tab" data-bs-target="#deleteTabPaneTransfers" type="button" role="tab" aria-selected="true">
                                    <i class="ti ti-truck-delivery me-1"></i>
                                    <span>Stock Transferred</span>
                                    <span class="badge bg-label-primary ms-1" id="modalTransfersCount">0</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation" id="deleteTabItemSales">
                                <button class="nav-link" id="deleteTabBtnSales" data-bs-toggle="tab" data-bs-target="#deleteTabPaneSales" type="button" role="tab" aria-selected="false">
                                    <i class="ti ti-shopping-cart me-1"></i>
                                    <span>Associated Sales Orders</span>
                                    <span class="badge bg-label-danger ms-1" id="modalSalesCount">0</span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content border rounded-3 p-3 bg-white shadow-xs mb-3 dependency-table-card" id="deleteImpactTabsContent">
                        <div class="tab-pane fade show active" id="deleteTabPaneTransfers" role="tabpanel">
                            <div class="alert alert-primary bg-primary-subtle border-primary-subtle d-flex align-items-center py-2 px-3 mb-3 small">
                                <i class="ti ti-info-circle fs-5 me-2 text-primary flex-shrink-0"></i>
                                <span>Stock from this purchase was transferred via Purchase Bills. The transferred quantity will be deducted from destination branches as well.</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0 w-100" id="modalTransfersTable" style="font-size: 0.82rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 16%;">Transfer No</th>
                                            <th style="width: 20%;">Destination Branch</th>
                                            <th style="width: 38%;">Items & Qty</th>
                                            <th style="width: 14%;">Transfer Date</th>
                                            <th style="width: 12%;" class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modalTransfersTableBody"></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="deleteTabPaneSales" role="tabpanel">
                            <div class="alert alert-danger bg-danger-subtle border-danger-subtle d-flex align-items-center py-2 px-3 mb-3 small">
                                <i class="ti ti-alert-triangle fs-5 me-2 text-danger flex-shrink-0"></i>
                                <span>Sales have been recorded for items in this purchase. Please review sales orders below and select an action below.</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0 w-100" id="modalSalesTable" style="font-size: 0.82rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 14%;">Order No</th>
                                            <th style="width: 16%;">Customer</th>
                                            <th style="width: 14%;">Branch</th>
                                            <th style="width: 28%;">Items Sold</th>
                                            <th style="width: 12%;">Date</th>
                                            <th style="width: 9%;" class="text-end">Total</th>
                                            <th style="width: 7%;" class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modalSalesTableBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="modalDecisionSection" class="card border border-warning-subtle bg-warning-subtle bg-opacity-10 shadow-none mb-0 d-none">
                    <div class="card-body p-3">
                        <label class="form-label fw-bold text-dark mb-2 d-flex align-items-center">
                            <i class="ti ti-adjustments-horizontal text-warning me-1 fs-5"></i>
                            Select Deletion Action:
                        </label>
                        <div class="list-group mb-0">
                            <label class="list-group-item list-group-item-action d-flex align-items-start p-2.5 border rounded mb-2 bg-white cursor-pointer">
                                <input class="form-check-input me-3 mt-1 flex-shrink-0" type="radio" name="delete_sales_choice" id="choiceKeepSales" value="0" checked>
                                <div class="w-100">
                                    <div class="fw-bold text-dark mb-0.5 d-flex align-items-center small">
                                        <i class="ti ti-shield-check text-success me-1"></i> Delete Purchase Only (Keep Sales Intact)
                                    </div>
                                    <div class="text-muted" style="font-size: 0.76rem;">
                                        Stock deduction from inventory without modifying or altering existing sales orders.
                                    </div>
                                </div>
                            </label>

                            <label class="list-group-item list-group-item-action d-flex align-items-start p-2.5 border rounded border-danger-subtle bg-danger-subtle bg-opacity-10 mb-0 cursor-pointer">
                                <input class="form-check-input me-3 mt-1 flex-shrink-0" type="radio" name="delete_sales_choice" id="choiceDeleteSales" value="1">
                                <div class="w-100">
                                    <div class="fw-bold text-danger mb-0.5 d-flex align-items-center small">
                                        <i class="ti ti-trash-x text-danger me-1"></i> Delete Purchase & Remove Associated Items from Sales
                                    </div>
                                    <div class="text-muted" style="font-size: 0.76rem;">
                                        Removes this purchase's items from sales and recalculates remaining sale totals & ledgers. If an order has no other items left, it will be deleted.
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div id="modalSimpleNotice" class="text-center py-4 d-none">
                    <div class="avatar avatar-lg bg-label-danger mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center">
                        <i class="ti ti-trash fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Delete Purchase?</h5>
                    <p class="text-muted mb-0 small">Are you sure you want to delete this purchase? This action cannot be undone.</p>
                </div>
            </div>

            <div class="modal-footer border-top py-3">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="modalConfirmDeleteBtn">
                    <i class="ti ti-trash me-1"></i> Confirm & Delete
                </button>
            </div>
        </div>
    </div>
</div>
