@extends('layouts.app')

@section('title', 'Payables Payment History')

@section('page-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-semibold mb-0">Payables Payment History</h4>
            <small class="text-muted">Log of all payments made towards supplier purchases</small>
        </div>
        <div>
            <a href="{{ route('admin.accounting.outstanding-payables') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back to Outstanding Payables
            </a>
        </div>
    </div>

    <div class="card mb-4" id="filterReportCard">
        <div class="card-header">
            <h5 class="mb-0">Filter Payment History</h5>
        </div>
        <div class="card-body">
            <form id="filterForm" class="row g-3" onsubmit="return false;">
                <div class="col-md-6">
                    <label class="form-label">Start Date</label>
                    <input type="text" id="filter-start-date" class="form-control flatpickr-log" placeholder="DD-MM-YYYY" readonly />
                </div>
                <div class="col-md-6">
                    <label class="form-label">End Date</label>
                    <input type="text" id="filter-end-date" class="form-control flatpickr-log" placeholder="DD-MM-YYYY" readonly />
                </div>
                <div class="col-12 d-flex justify-content-end gap-2 mt-4 d-none" id="filterActionButtons">
                    <button type="button" id="clearFiltersBtn" class="btn btn-outline-primary">
                        <i class="ti ti-refresh me-1"></i> Clear
                    </button>
                    <button type="button" id="applyFiltersBtn" class="btn btn-primary">
                        <i class="ti ti-filter me-1"></i> Apply
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table border-top" id="paymentHistoryTable">
                <thead>
                    <tr>
                        <th style="width: 5%">#</th>
                        <th>Date & Time</th>
                        <th>Supplier</th>
                        <th class="text-end">Paid Amount</th>
                        <th>Payment Method</th>
                        <th>Paid By</th>
                        <th style="width: 10%">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Edit Payable Payment Modal -->
    <div class="modal fade" id="editPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Edit Payment Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editPaymentForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit-payment-id" name="payment_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required fw-semibold">Paid Amount (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" min="0.01" id="edit-payment-amount" name="amount" class="form-control" required />
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required fw-semibold">Payment Method</label>
                            <select id="edit-payment-method" name="payment_method" class="form-select" required>
                                <option value="cash">Cash</option>
                                <option value="online">Online</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="editPaymentSubmitBtn" class="btn btn-primary">
                            <i class="ti ti-check me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('page-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function () {
            const startPicker = $('#filter-start-date').flatpickr({
                altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d', allowInput: false, maxDate: 'today',
                onChange: function (selectedDates) { endPicker.set('minDate', selectedDates.length ? selectedDates[0] : null); }
            });
            const endPicker = $('#filter-end-date').flatpickr({
                altInput: true, altFormat: 'd-m-Y', dateFormat: 'Y-m-d', allowInput: false, maxDate: 'today',
                onChange: function (selectedDates) { startPicker.set('maxDate', selectedDates.length ? selectedDates[0] : 'today'); }
            });

            let isFiltered = false;

            function updateFilterButtonsVisibility() {
                const hasValue = $('#filterForm').find('input, select').toArray().some(function (el) {
                    return $(el).val();
                });
                $('#filterActionButtons').toggleClass('d-none', !hasValue);

                if (!hasValue && isFiltered) {
                    isFiltered = false;
                    window.refreshTable();
                }
            }

            $(document).on('input change', '#filterForm', function () {
                updateFilterButtonsVisibility();
            });
            updateFilterButtonsVisibility();

            function currentFilters() {
                return {
                    start_date: $('#filter-start-date').val(),
                    end_date: $('#filter-end-date').val(),
                };
            }

            const table = $('#paymentHistoryTable').DataTable({
                responsive: false,
                order: [[1, 'desc']],
                ajax: {
                    url: '{{ route('admin.accounting.outstanding-payables.payment-history.data') }}',
                    cache: false,
                    data: function (d) { Object.assign(d, currentFilters()); },
                    dataSrc: function (json) {
                        return json.data;
                    },
                },
                columns: [
                    { data: 'index', orderable: false },
                    { data: 'date', className: 'fw-semibold text-heading' },
                    { data: 'supplier', className: 'fw-semibold' },
                    { 
                        data: 'amount', 
                        className: 'text-end fw-semibold' 
                    },
                    { 
                        data: 'payment_method',
                        render: function(data) {
                            const isOnline = String(data).toLowerCase() === 'online';
                            return `<span class="badge ${isOnline ? 'bg-label-primary' : 'bg-label-success'}">${data}</span>`;
                        }
                    },
                    { 
                        data: 'created_by',
                        render: function(data) {
                            return `<span class="badge bg-label-secondary">${data}</span>`;
                        }
                    },
                    { data: 'actions', orderable: false, searchable: false },
                ],
                drawCallback: function () {
                    const api = this.api();
                    api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                        cell.innerHTML = i + 1;
                    });
                }
            });

            window.refreshTable = function () {
                window.showAjaxLoader && window.showAjaxLoader();
                table.ajax.reload(function () {
                    window.hideAjaxLoader && window.hideAjaxLoader();
                }, false);
            };

            $(document).on('click', '#applyFiltersBtn', function (e) {
                e.preventDefault();
                isFiltered = true;
                window.refreshTable();
            });

            $(document).on('click', '#clearFiltersBtn', function (e) {
                e.preventDefault();
                isFiltered = false;
                startPicker.clear();
                endPicker.clear();
                startPicker.set('maxDate', 'today');
                endPicker.set('minDate', null);
                $('#filter-supplier').val(null).trigger('change');
                updateFilterButtonsVisibility();
                window.refreshTable();
            });

            // Edit Modal Handler
            $(document).on('click', '.edit-payable-payment-btn', function () {
                const id = $(this).data('id');
                const amount = $(this).data('amount');
                const method = $(this).data('method');

                $('#edit-payment-id').val(id);
                $('#edit-payment-amount').val(amount);
                $('#edit-payment-method').val(method.toLowerCase());

                const modal = new bootstrap.Modal(document.getElementById('editPaymentModal'));
                modal.show();
            });

            // Submit Edit Form AJAX
            $('#editPaymentForm').on('submit', function (e) {
                e.preventDefault();
                const id = $('#edit-payment-id').val();
                const submitBtn = $('#editPaymentSubmitBtn');
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');

                let updateUrl = '{{ route('admin.accounting.outstanding-payables.payment-history.update', ':id') }}';
                updateUrl = updateUrl.replace(':id', id);

                $.ajax({
                    url: updateUrl,
                    type: 'POST',
                    data: $(this).serialize(),
                    headers: { 'X-HTTP-Method-Override': 'PUT' },
                    success: function (res) {
                        submitBtn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> Save Changes');
                        if (res.status === 'success') {
                            bootstrap.Modal.getInstance(document.getElementById('editPaymentModal')).hide();
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: res.message || 'Payment entry updated successfully.',
                                customClass: { confirmButton: 'btn btn-primary' }
                            });
                            window.refreshTable();
                        }
                    },
                    error: function (xhr) {
                        submitBtn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> Save Changes');
                        const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'An error occurred while updating.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: typeof msg === 'object' ? Object.values(msg).flat().join('\n') : msg,
                            customClass: { confirmButton: 'btn btn-primary' }
                        });
                    }
                });
            });

            // Delete Handler
            $(document).on('click', '.delete-payable-payment-btn', function () {
                const id = $(this).data('id');

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Deleting this payment will revert the paid balance on associated purchase bills!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'btn btn-danger me-3',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false
                }).then(function (result) {
                    if (result.isConfirmed) {
                        let deleteUrl = '{{ route('admin.accounting.outstanding-payables.payment-history.destroy', ':id') }}';
                        deleteUrl = deleteUrl.replace(':id', id);

                        $.ajax({
                            url: deleteUrl,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function (res) {
                                if (res.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: res.message || 'Payment entry deleted successfully.',
                                        customClass: { confirmButton: 'btn btn-primary' }
                                    });
                                    window.refreshTable();
                                }
                            },
                            error: function (xhr) {
                                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to delete payment entry.';
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: msg,
                                    customClass: { confirmButton: 'btn btn-primary' }
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
