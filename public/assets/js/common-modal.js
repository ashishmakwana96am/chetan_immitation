$(document).ready(function () {

    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------
    function showSpinner() {
        return '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
    }

    function clearFormErrors(form) {
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').text('');
    }

    function showFormErrors(form, errors) {
        $.each(errors, function (field, messages) {
            let inputName = field;
            if (field.includes('.')) {
                let parts = field.split('.');
                inputName = parts[0] + '[' + parts.slice(1).join('][') + ']';
            }
            let input = form.find('[name="' + inputName + '"], [name="' + inputName + '[]"]');
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
    }

    function disableBtn(btn, loadingText) {
        btn.prop('disabled', true)
           .data('original-text', btn.html())
           .html('<span class="spinner-border spinner-border-sm me-1" role="status"></span>' + (loadingText || 'Processing...'));
    }

    function enableBtn(btn) {
        btn.prop('disabled', false)
           .html(btn.data('original-text'));
    }

    // Expose helpers globally so any page can call them without duplicating code
    window.showAjaxLoader = function (btn) {
        if (btn) {
            disableBtn($(btn), 'Processing...');
        } else {
            var el = document.getElementById('ajaxLoaderOverlay');
            if (el) {
                el.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }
    };
    window.hideAjaxLoader = function (btn) {
        if (btn) {
            enableBtn($(btn));
        }
        var el = document.getElementById('ajaxLoaderOverlay');
        if (el) {
            el.style.display = 'none';
        }
        var pageLoader = document.getElementById('pageLoader');
        if (pageLoader) {
            pageLoader.classList.add('fade-out');
            setTimeout(function() {
                if (pageLoader && pageLoader.parentNode) {
                    pageLoader.parentNode.removeChild(pageLoader);
                }
            }, 350);
        }
        document.body.style.overflow = '';
    };

    // -------------------------------------------------------
    // Clear validation error on input/change globally
    // -------------------------------------------------------
    $(document).on('input change', '.is-invalid', function () {
        $(this).removeClass('is-invalid');
        let feedback = $(this).siblings('.invalid-feedback');
        if (feedback.length === 0 && $(this).parent('.input-group').length) {
            feedback = $(this).parent('.input-group').siblings('.invalid-feedback');
        }
        feedback.text('');
    });

    // -------------------------------------------------------
    // DataTables global pagination & tooltip fix
    // -------------------------------------------------------
    $(document).on('draw.dt', function (e, settings) {
        try {
            if ($.fn.dataTable && $.fn.dataTable.Api) {
                const api = new $.fn.dataTable.Api(settings);
                const info = api.page.info();
                if (info.pages > 0 && info.page >= info.pages) {
                    api.page('previous').draw('page');
                }
            }
        } catch (err) {
            console.warn('DataTables pagination fix warning:', err);
        }

        try {
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                const apiNode = settings.nTable;
                if (apiNode) {
                    const tooltipTriggerList = [].slice.call(apiNode.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.map(function (tooltipTriggerEl) {
                        const instance = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
                        if (instance) {
                            try {
                                instance.dispose();
                            } catch (e) {}
                        }
                        return new bootstrap.Tooltip(tooltipTriggerEl, { container: 'body' });
                    });
                }
            }
        } catch (err) {
            console.error('DataTables tooltip init error:', err);
        }
    });

    // -------------------------------------------------------
    // Open common modal
    // Trigger : [data-common-modal="url"]
    // Optional: [data-size="half"] — widens the panel to 50vw. Used only by
    // Customer Credit Report's sale "View" and the Purchase Import History
    // panel; every other [data-common-modal] trigger keeps the default width.
    // -------------------------------------------------------
    window.openCommonModal = function (url, size) {
        $('#commonModalBody').html(showSpinner());
        $('#commonModal').css('width', size === 'half' ? '50vw' : '600px');
        $('#commonModal').offcanvas('show');

        $.get(url)
            .done(function (html) {
                $('#commonModalBody').html(html);

                let body = $('#commonModalBody');

                if (typeof $.fn.flatpickr !== 'undefined') {
                    body.find('.flatpickr').each(function() {
                        let config = {
                            altInput: true,
                            altFormat: 'd-m-Y',
                            dateFormat: 'Y-m-d',
                            allowInput: false
                        };
                        if ($(this).attr('data-max-date')) {
                            config.maxDate = $(this).attr('data-max-date');
                        }
                        if ($(this).attr('data-min-date')) {
                            config.minDate = $(this).attr('data-min-date');
                        }
                        $(this).flatpickr(config);
                    });
                }

                let titleBlock = body.find('.text-center.mb-4');
                
                if (titleBlock.length) {
                    $('#commonModalTitle').text(titleBlock.find('h3').text() || 'Details');
                    titleBlock.remove();
                } else {
                    $('#commonModalTitle').text('Details');
                }
                
                let form = body.find('form');
                if (form.length) {
                    form.addClass('d-flex flex-column flex-grow-1 h-100 mb-0');
                    
                    let row = form.find('.row');
                    if (row.length === 0) {
                        row = $('<div class="row g-3"></div>');
                        let fields = form.children('div').filter('.mb-3, .mb-4').filter(function() {
                            let el = $(this);
                            return el.find('table').length === 0 && el.find('input, select, textarea').length > 0;
                        });
                        if (fields.length > 0) {
                            fields.each(function() {
                                let col = $('<div class="col-md-6 col-12"></div>');
                                $(this).before(col);
                                col.append($(this));
                            });
                            form.find('.col-md-6').appendTo(row);
                            form.prepend(row);
                        }
                    } else {
                        row.find('> div')
                            .removeClass('col-12 col-md-12 col-md-6 col-sm-6 col-lg-6 col-md-4 col-lg-4')
                            .addClass('col-md-6 col-12');
                    }
                    
                    let btnDiv = form.find('button[type="submit"]').parent();
                    let scrollWrapper = $('<div class="flex-grow-1 p-4" style="overflow-y: auto;"></div>');
                    
                    form.children().not(btnDiv).appendTo(scrollWrapper);
                    form.prepend(scrollWrapper);
                    
                    if(btnDiv.length) {
                        btnDiv.removeClass('text-center mt-4 pt-3 gap-2 border-top').addClass('d-flex p-4 border-top gap-3 mt-auto mb-0');
                        let submitBtn = btnDiv.find('button[type="submit"]');
                        let cancelBtn = btnDiv.find('button[data-bs-dismiss="modal"]');
                        
                        submitBtn.removeClass('me-2').addClass('flex-fill m-0 w-50');
                        cancelBtn.addClass('flex-fill m-0 w-50 btn-label-secondary');
                        cancelBtn.removeClass('btn-secondary'); // ensure label style
                    }
                }
            })
            .fail(function () {
                $('#commonModalBody').html('<p class="text-center text-danger p-4">Failed to load content.</p>');
            });
    };

    $(document).on('click', '[data-common-modal]', function (e) {
        e.preventDefault();
        window.openCommonModal($(this).attr('data-common-modal'), $(this).attr('data-size') || '');
    });

    // Reset offcanvas on close
    $('#commonModal').on('hidden.bs.offcanvas', function () {
        $('#commonModalBody').html(showSpinner());
    });

    // Support for modal dismiss buttons inside the loaded HTML
    $(document).on('click', '#commonModal [data-bs-dismiss="modal"]', function() {
        $('#commonModal').offcanvas('hide');
    });

    // Global Helper: Show In-Use Products in a Bootstrap Modal with DataTables
    window.showInUseProductsModal = function (options) {
        options = options || {};
        var title = options.title || 'Cannot Delete Item';
        var message = options.message || 'This item cannot be deleted because it is in use by the products listed below. Please remove it from these products first:';
        var products = options.products || [];

        $('#inUseProductsModalTitle').html('<i class="ti ti-alert-triangle me-2 fs-4"></i> ' + title);
        $('#inUseProductsModalMessage').text(message);

        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#inUseProductsTable')) {
            $('#inUseProductsTable').DataTable().destroy();
        }

        var $tbody = $('#inUseProductsTable tbody');
        $tbody.empty();

        products.forEach(function (prod, idx) {
            var barcodeBadge = prod.barcode
                ? '<span class="badge bg-label-primary fw-semibold">' + prod.barcode + '</span>'
                : '<span class="text-muted small">-</span>';
            var nameLink = prod.url
                ? '<a href="' + prod.url + '" target="_blank" class="fw-semibold text-primary text-decoration-none">' + prod.name + '</a>'
                : '<span class="fw-semibold text-dark">' + prod.name + '</span>';

            var tr = '<tr>' +
                '<td>' + (idx + 1) + '</td>' +
                '<td>' + nameLink + '</td>' +
                '<td>' + barcodeBadge + '</td>' +
                '</tr>';
            $tbody.append(tr);
        });

        if ($.fn.DataTable) {
            $('#inUseProductsTable').DataTable({
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                ordering: true,
                responsive: true,
                destroy: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search product or barcode..."
                }
            });
        }

        var modalEl = document.getElementById('inUseProductsModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl, {
                backdrop: 'static',
                keyboard: false
            });
            modal.show();
        }
    };

    $(document).on('show.bs.modal', '#inUseProductsModal', function () {
        $('html, body, .layout-wrapper, .layout-page, .content-wrapper').addClass('modal-open').css('overflow', 'hidden');
    });

    $(document).on('hidden.bs.modal', '#inUseProductsModal', function () {
        $('html, body, .layout-wrapper, .layout-page, .content-wrapper').removeClass('modal-open').css('overflow', '');
    });

    // -------------------------------------------------------
    // Submit common modal form
    // Form must have: id="commonModalForm"
    // -------------------------------------------------------
    $(document).on('submit', '#commonModalForm', function (e) {
        e.preventDefault();

        const form      = $(this);
        const url       = form.attr('action');
        const method    = form.find('input[name="_method"]').val() || 'POST';
        const submitBtn = form.find('[type="submit"]');

        const dateInput = form.find('input[name="expense_date"], input[name="date"], input[name="purchase_date"], input[name="order_date"], input[name="transfer_date"]').val();
        if (dateInput && typeof window.checkAndConfirmDateSubmission === 'function') {
            let partyName = form.find('input[name="title"]').val() || form.find('select[name="category"]').val() || '';
            let amountVal = form.find('input[name="amount"]').val() ? ('₹' + form.find('input[name="amount"]').val()) : '';
            let moduleName = 'Expense / Record Entry';
            if (form.find('input[name="expense_date"]').length) moduleName = 'Expense Record';

            const confirmed = window.checkAndConfirmDateSubmission(this, dateInput, {
                module: moduleName,
                partyLabel: 'Title / Category:',
                partyName: partyName,
                amount: amountVal,
                dateFormatted: dateInput,
                submitBtn: submitBtn
            });

            if (!confirmed) {
                return false;
            }
        }

        clearFormErrors(form);
        disableBtn(submitBtn, 'Saving...');

        $.ajax({
            url     : url,
            type    : method,
            data    : form.serialize(),
            success : function (res) {
                if (res.status === 'success') {
                    $('#commonModal').offcanvas('hide');
                    toastr.success(res.message);
                    if (res.print_url) {
                        window.open(res.print_url, '_blank');
                    }
                    if (typeof window.refreshTable === 'function') {
                        window.refreshTable(res);
                    } else {
                        setTimeout(() => location.reload(), 800);
                    }
                    if (typeof window.refreshPurchaseBillBadge === 'function') {
                        window.refreshPurchaseBillBadge();
                    }
                    if (typeof window.refreshBranchLedgerBadge === 'function') {
                        window.refreshBranchLedgerBadge();
                    }
                } else if (res.status === 'error') {
                    enableBtn(submitBtn);
                    if (res.in_use || (res.products && res.products.length)) {
                        window.showInUseProductsModal(res);
                    } else {
                        toastr.error(typeof res.message === 'string' ? res.message : 'Error updating record.');
                    }
                }
            },
            error : function (xhr) {
                enableBtn(submitBtn);
                const json = xhr.responseJSON;
                if (xhr.status === 422 && json) {
                    if (json.in_use || (json.products && json.products.length)) {
                        window.showInUseProductsModal(json);
                    } else {
                        const errors = json.message || json.errors || {};
                        showFormErrors(form, errors);
                    }
                } else {
                    toastr.error('Something went wrong. Please try again.');
                }
            }
        });
    });

    // -------------------------------------------------------
    // Delete confirmation modal (SweetAlert)
    // Trigger : [data-common-delete="url"]
    // Required: [data-row-id="row-element-id"]
    // -------------------------------------------------------
    $(document).on('click', '[data-common-delete]', function (e) {
        e.preventDefault();

        const url = $(this).attr('data-common-delete');
        const rowId = $(this).attr('data-row-id');

        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Delete',
            customClass: {
                confirmButton: 'btn btn-danger me-3',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then(function (result) {
            if (result.value) {
                $.ajax({
                    url  : url,
                    type : 'DELETE',
                    data : { _token: csrfToken },
                    success : function (res) {
                        if (res.status === 'success') {
                            toastr.success(res.message);
                            if (typeof window.refreshTable === 'function') {
                                window.refreshTable();
                            } else if (rowId) {
                                $('#' + rowId).fadeOut(400, function () { $(this).remove(); });
                            }
                        } else if (res.status === 'error') {
                            if (res.in_use || (res.products && res.products.length)) {
                                window.showInUseProductsModal(res);
                            } else {
                                Swal.fire({
                                    title: res.title || 'Cannot Delete',
                                    html: res.message,
                                    icon: 'error',
                                    confirmButtonText: 'OK',
                                    customClass: { confirmButton: 'btn btn-primary' },
                                    buttonsStyling: false
                                });
                            }
                        }
                    },
                    error : function (xhr) {
                        const json = xhr.responseJSON;
                        if (xhr.status === 422 && json) {
                            if (json.in_use || (json.products && json.products.length)) {
                                window.showInUseProductsModal(json);
                            } else {
                                const htmlMsg = (typeof json.message === 'string')
                                    ? json.message
                                    : (json.errors ? Object.values(json.errors).flat().join('<br>') : 'Cannot delete this record.');
                                Swal.fire({
                                    title: json.title || 'Cannot Delete',
                                    html: htmlMsg,
                                    icon: 'error',
                                    confirmButtonText: 'OK',
                                    customClass: { confirmButton: 'btn btn-primary' },
                                    buttonsStyling: false
                                });
                            }
                        } else {
                            toastr.error('Something went wrong. Please try again.');
                        }
                    }
                });
            }
        });
    });

    // -------------------------------------------------------
    // Common Confirm Modal (SweetAlert)
    // Trigger : [data-common-confirm="url"]
    // Required: [data-confirm-method="PATCH|POST|PUT"]
    // Optional: [data-confirm-title="Title text"]
    //           [data-confirm-text="Body text"]
    //           [data-confirm-btn="Button label"]
    //           [data-confirm-btn-class="btn-success|btn-warning..."]
    //           [data-confirm-data='{"key":"value"}']
    // -------------------------------------------------------
    $(document).on('click', '[data-common-confirm]', function (e) {
        e.preventDefault();

        const url    = $(this).attr('data-common-confirm');
        const method = $(this).attr('data-confirm-method') || 'POST';
        let data   = {};

        try {
            data = JSON.parse($(this).attr('data-confirm-data') || '{}');
        } catch (err) {}

        const title    = $(this).attr('data-confirm-title')     || 'Are you sure?';
        const text     = $(this).attr('data-confirm-text')      || '';
        const btnLabel = $(this).attr('data-confirm-btn')       || 'Confirm';
        const btnClass = $(this).attr('data-confirm-btn-class') || 'btn-primary';

        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: btnLabel,
            customClass: {
                confirmButton: 'btn ' + btnClass + ' me-3',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then(function (result) {
            if (result.value) {
                const ajaxData = Object.assign({ _token: csrfToken }, data);

                window.showAjaxLoader();

                $.ajax({
                    url     : url,
                    type    : method,
                    data    : ajaxData,
                    success : function (res) {
                        window.hideAjaxLoader();
                        if (res.status === 'success') {
                            toastr.success(res.message);
                            if (typeof window.onConfirmSuccess === 'function') {
                                window.onConfirmSuccess(res);
                            } else {
                                setTimeout(() => location.reload(), 800);
                            }
                        }
                    },
                    error : function (xhr) {
                        window.hideAjaxLoader();
                        const msg = xhr.responseJSON?.message || 'Something went wrong. Please try again.';
                        toastr.error(typeof msg === 'string' ? msg : Object.values(msg)[0][0]);
                    }
                });
            }
        });
    });

    // Global dynamic tooltip delegation to support any static/dynamic element seamlessly
    $(document).on('mouseenter', '[data-bs-toggle="tooltip"]', function () {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            try {
                let instance = bootstrap.Tooltip.getInstance(this);
                let hasBodyContainer = false;
                if (instance) {
                    let container = (instance._config && instance._config.container) || (instance.options && instance.options.container);
                    if (container === 'body') {
                        hasBodyContainer = true;
                    }
                }
                if (instance && !hasBodyContainer) {
                    try {
                        instance.dispose();
                    } catch (e) {}
                    instance = null;
                }
                if (!instance) {
                    instance = new bootstrap.Tooltip(this, {
                        container: 'body'
                    });
                    instance.show();
                }
            } catch (err) {
                console.error('Tooltip init error:', err);
            }
        }
    });

    $(document).on('mouseleave click', '[data-bs-toggle="tooltip"]', function () {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            try {
                const instance = bootstrap.Tooltip.getInstance(this);
                if (instance) {
                    instance.hide();
                }
            } catch (e) {}
        }
    });

    // Global backdrop scroll preventer for all offcanvases in the project
    $(document).on('show.bs.offcanvas', '.offcanvas', function () {
        $('html, body').addClass('offcanvas-locked');
    });

    $(document).on('hidden.bs.offcanvas', '.offcanvas', function () {
        if ($('.offcanvas.show').length === 0) {
            $('html, body').removeClass('offcanvas-locked');
        }
    });

});
