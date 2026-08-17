<!DOCTYPE html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed" dir="ltr" data-theme="theme-default"
    data-assets-path="{{ asset('assets') }}/" data-template="vertical-menu-template-no-customizer">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>@yield('title', 'Dashboard') | Chetan Imitation</title>
    <meta name="description" content="" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon/favicon.png') }}" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" media="print" onload="this.media='all'" />
    <noscript><link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" /></noscript>

    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/fontawesome.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/tabler-icons.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/flag-icons.css') }}" />

    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/theme-default.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}?v={{ filemtime(public_path('assets/css/custom.css')) }}" />

    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/node-waves/node-waves.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/typeahead-js/typeahead.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/toastr/toastr.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />

    @yield('page-css')

    <style>
    .page-loader {
        position: fixed; 
        inset: 0; 
        z-index: 999999;
        background: rgba(255, 255, 255, 0.82);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        display: flex; 
        align-items: center; 
        justify-content: center;
        transition: opacity 0.35s ease;
    }
    .page-loader.fade-out { 
        opacity: 0; 
        pointer-events: none; 
    }

    .loader-card {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(0, 0, 0, 0.06);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
        border-radius: 20px;
        padding: 2.2rem 3rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1.25rem;
    }

    .loader-visual {
        position: relative;
        width: 80px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .loader-ring {
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        border: 3px solid rgba(180, 119, 30, 0.12);
        border-top-color: #B4771E;
        border-right-color: #328693;
        animation: spinRing 1.1s linear infinite;
    }

    .loader-logo-container {
        position: absolute;
        width: 52px;
        height: 52px;
        background: #ffffff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .loader-logo-img {
        width: 28px;
        height: 28px;
        object-fit: contain;
    }

    .loader-text-wrapper {
        display: flex;
        align-items: center;
        gap: 2px;
    }

    .loader-status {
        font-size: 0.85rem;
        font-weight: 600;
        color: #444;
        letter-spacing: 0.5px;
    }

    .loader-dots span {
        font-size: 1.1rem;
        font-weight: 700;
        color: #B4771E;
        display: inline-block;
        animation: dotPulse 1.4s infinite ease-in-out;
    }

    .loader-dots span:nth-child(2) { animation-delay: 0.2s; }
    .loader-dots span:nth-child(3) { animation-delay: 0.4s; }

    @keyframes spinRing {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @keyframes dotPulse {
        0%, 100% { transform: translateY(0); opacity: 0.3; }
        50% { transform: translateY(-3px); opacity: 1; }
    }
    </style>

    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets/js/config.js') }}"></script>
</head>

<body>
    <!-- Global Page Loader -->
    <div class="page-loader" id="pageLoader">
        <div class="loader-card">
            <div class="loader-visual">
                <div class="loader-ring"></div>
                <div class="loader-logo-container">
                    <img src="{{ asset('assets/img/favicon/favicon.png') }}" alt="Logo" class="loader-logo-img" />
                </div>
            </div>
            <div class="loader-text-wrapper">
                <span class="loader-status">Loading</span>
                <span class="loader-dots"><span>.</span><span>.</span><span>.</span></span>
            </div>
        </div>
    </div>

    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">

            @include('layouts.partials.sidebar')

            <div class="layout-page">

                @include('layouts.partials.navbar')

                <div class="content-wrapper">
                    <div class="container-fluid flex-grow-1 container-p-y">
                        @yield('content')
                    </div>

                    <footer class="content-footer footer bg-footer-theme">
                        <div class="container-fluid">
                            <div
                                class="footer-container d-flex align-items-center justify-center justify-content-between py-2 flex-md-row flex-column text-center sm:text-left">
                                <div>©
                                    <script>
                                        document.write(new Date().getFullYear());
                                    </script>, Chetan Imitation. All Rights Reserved | Developed by <a href="https://risingstarinfotech.com/" target="_blank"
                                        class="fw-semibold">Rising Star Infotech</a>
                                </div>
                            </div>
                        </div>
                    </footer>

                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>

        <div class="layout-overlay layout-menu-toggle"></div>
        <div class="drag-target"></div>
    </div>

    {{-- Common Side Panel --}}
    <div class="offcanvas offcanvas-end" id="commonModal" aria-hidden="true" data-bs-backdrop="static" data-bs-scroll="false" data-bs-keyboard="false" data-bs-focus="false" style="width: 600px; max-width: 100vw;">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title" id="commonModalTitle">Details</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0 d-flex flex-column" id="commonModalBody">
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
        </div>
    </div>

    @stack('modals')



    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script>
        $.ajaxSetup({
            cache: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $(document).ajaxError(function(event, xhr, settings, thrownError) {
            if (xhr.status === 401 || xhr.status === 419) {
                window.location.reload();
            }
        });
    </script>
    <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/node-waves/node-waves.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/hammer/hammer.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/i18n/i18n.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/typeahead-js/typeahead.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/menu.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/toastr/toastr.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset('assets/js/common-modal.js') }}"></script>
    <script>
        $(document).ready(function() {
            var activeDataTables = 0;
            var hasDataTables = false;
            var loaderHidden = false;

            window.hideAjaxLoader = function() {
                loaderHidden = true;
                $('#pageLoader').addClass('fade-out');
                var el = document.getElementById('ajaxLoaderOverlay');
                if (el) {
                    el.style.display = 'none';
                }
                document.body.style.overflow = '';
                setTimeout(function() {
                    $('#pageLoader').remove();
                }, 350);
            };

            window.showAjaxLoader = function() {
                if ($('#pageLoader').length === 0) {
                    $('body').append(`
                        <div class="page-loader" id="pageLoader">
                            <div class="loader-card">
                                <div class="loader-visual">
                                    <div class="loader-ring"></div>
                                    <div class="loader-logo-container">
                                        <img src="{{ asset('assets/img/favicon/favicon.png') }}" alt="Logo" class="loader-logo-img" />
                                    </div>
                                </div>
                                <div class="loader-text-wrapper">
                                    <span class="loader-status">Loading</span>
                                    <span class="loader-dots"><span>.</span><span>.</span><span>.</span></span>
                                </div>
                            </div>
                        </div>
                    `);
                }
                loaderHidden = false;
                $('#pageLoader').removeClass('fade-out');
            };

            var fallbackTimeout = setTimeout(function() {
                window.hideAjaxLoader();
            }, 6000);

            function checkAndHideLoader() {
                if (window.jQuery && $.active > 0) {
                    return;
                }
                if (activeDataTables > 0) {
                    return;
                }
                clearTimeout(fallbackTimeout);
                window.hideAjaxLoader();
            }

            $(document).on('preInit.dt', function(e, settings) {
                if (settings && settings.oFeatures) {
                    settings.oFeatures.bProcessing = false;
                }
                activeDataTables++;
                hasDataTables = true;
            });

            $(document).on('draw.dt init.dt error.dt xhr.dt', function(e, settings) {
                activeDataTables = Math.max(0, activeDataTables - 1);
                setTimeout(checkAndHideLoader, 150);
            });

            $(document).ajaxStop(function() {
                setTimeout(checkAndHideLoader, 150);
            });

            $(window).on('load', function() {
                setTimeout(checkAndHideLoader, 250);
            });

            $('.flatpickr').each(function() {
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

            window.addEventListener('scroll', function () {
                document.querySelectorAll('.flatpickr-input').forEach(function (el) {
                    if (el._flatpickr && el._flatpickr.isOpen) {
                        el._flatpickr.close();
                    }
                });
            }, true);
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "timeOut": "5000"
            };
            @if(session('success'))
                toastr.success("{{ session('success') }}");
            @endif
            @if(session('error'))
                toastr.error("{{ session('error') }}");
            @endif
            @if(session('warning'))
                toastr.warning("{{ session('warning') }}");
            @endif
            @if(session('info'))
                toastr.info("{{ session('info') }}");
            @endif

            // Global password show/hide toggle
            $(document).on('click', '.input-group-text.cursor-pointer', function () {
                const input = $(this).siblings('input');
                const icon = $(this).find('i');
                if (input.length) {
                    if (input.attr('type') === 'password') {
                        input.attr('type', 'text');
                        icon.removeClass('ti-eye-off').addClass('ti-eye');
                    } else {
                        input.attr('type', 'password');
                        icon.removeClass('ti-eye').addClass('ti-eye-off');
                    }
                }
            });

            function initGlobalSelect2() {
                if (typeof $.fn.select2 === 'undefined') {
                    console.error('Select2 library is not loaded!');
                    return;
                }
                
                const targets = $('form select:not(.datatables-select):not([name$="_length"]):not(.no-select2):not([name*="status"]):not([name*="payment_method"])');
                targets.each(function() {
                    const selectEl = $(this);
                    if (selectEl.hasClass('select2-hidden-accessible')) {
                        return;
                    }
                    
                    if (selectEl.closest('.ql-toolbar').length || selectEl.closest('.ql-container').length || selectEl.closest('.ql-snow').length) {
                        return;
                    }
                    
                    selectEl.addClass('select2');
                    
                    const parentModal = selectEl.closest('#commonModal');
                    const hasEmptyOpt = selectEl.find('option[value=""]').length > 0;
                    
                    selectEl.select2({
                        dropdownParent: parentModal.length ? parentModal : $(document.body),
                        placeholder: hasEmptyOpt ? (selectEl.find('option[value=""]').text() || 'Select an option') : false,
                        allowClear: hasEmptyOpt,
                        width: '100%'
                    });
                });
            }

            initGlobalSelect2();

            const commonModalEl = document.getElementById('commonModal');
            if (commonModalEl) {
                commonModalEl.addEventListener('shown.bs.offcanvas', function () {
                    initGlobalSelect2();
                    setTimeout(initGlobalSelect2, 150);
                    setTimeout(initGlobalSelect2, 350);
                });
            }

            $(document).ajaxComplete(function() {
                initGlobalSelect2();
                setTimeout(initGlobalSelect2, 50);
            });


            // Double click row to view details
            $(document).on('dblclick', 'table tbody tr', function(e) {
                // Ignore clicks on interactive/form elements, buttons, and dropdowns
                if ($(e.target).closest('a, button, input, select, textarea, .dropdown-toggle, .dropdown-menu, [data-bs-toggle]').length) {
                    return;
                }
                
                // Find the View link within the row
                const viewLink = $(this).find('a').filter(function() {
                    const text = $(this).text().trim().toLowerCase();
                    const hasEyeIcon = $(this).find('.ti-eye, .fa-eye').length > 0;
                    return text === 'view' || hasEyeIcon;
                }).first();
                
                if (viewLink.length) {
                    const href = viewLink.attr('href');
                    if (href && href !== '#' && href !== 'javascript:void(0);') {
                        if (viewLink.attr('target') === '_blank') {
                            window.open(href, '_blank');
                        } else {
                            window.location.href = href;
                        }
                    }
                }
            });

            $(document).on('click', 'a', function (e) {
                const link = this;
                const href = $(link).attr('href');
                if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('tel:') || href.startsWith('mailto:')) {
                    return;
                }

                try {
                    const linkUrl = new URL(link.href);
                    if (linkUrl.host !== window.location.host) {
                        $(link).attr('target', '_blank');
                        const existingRel = $(link).attr('rel') || '';
                        if (!existingRel.includes('noopener')) {
                            $(link).attr('rel', $.trim(existingRel + ' noopener noreferrer'));
                        }
                    }
                } catch (err) {
                    // Ignore invalid URLs
                }
            });

            // Product thumbnail image popup modal
            $(document).on('click', '.product-thumbnail', function() {
                const src = $(this).attr('src') || $(this).data('src');
                if (!src) return;
                
                let $modal = $('#globalImageModal');
                if ($modal.length === 0) {
                    $('body').append(`
                        <div class="modal fade" id="globalImageModal" tabindex="-1" aria-hidden="true" style="z-index: 10000000;">
                            <div class="modal-dialog modal-dialog-centered" style="max-width: fit-content; width: auto;">
                                <div class="modal-content bg-transparent border-0 shadow-none" style="width: fit-content;">
                                    <div class="modal-body d-inline-block p-0 position-relative">
                                        <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2" data-bs-dismiss="modal" aria-label="Close" style="filter: drop-shadow(0px 1px 3px rgba(0,0,0,0.5));"></button>
                                        <img id="globalModalImage" src="" alt="Image Preview" class="rounded d-block" style="max-height: 85vh; max-width: 85vw; object-fit: contain; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);
                    $modal = $('#globalImageModal');
                }
                
                $modal.find('#globalModalImage').attr('src', src);
                const modalEl = new bootstrap.Modal($modal[0]);
                modalEl.show();
            });
        });
    </script>

    <script>
        $(document).on('pointerdown focus', '.table-responsive [data-bs-toggle="dropdown"], .table-action-dropdown [data-bs-toggle="dropdown"], table [data-bs-toggle="dropdown"]', function () {
            if (!this.hasAttribute('data-bs-popper-config')) {
                this.setAttribute('data-bs-popper-config', '{"strategy":"fixed"}');
            }
        });
    </script>

    <script>
        window.Apex = {
            noData: {
                text: 'No data available',
                align: 'center',
                verticalAlign: 'middle',
                style: {
                    color: '#8592a3',
                    fontSize: '16px',
                    fontFamily: 'Public Sans'
                }
            }
        };

        // Dynamically intercept and wrap ApexCharts globally to hide empty grids and axes
        (function() {
            let privateApexCharts = undefined;
            Object.defineProperty(window, 'ApexCharts', {
                get: function() {
                    return privateApexCharts;
                },
                set: function(originalValue) {
                    if (typeof originalValue === 'function') {
                        privateApexCharts = class extends originalValue {
                            constructor(el, options) {
                                const targetEl = typeof el === 'string' ? document.querySelector(el) : el;
                                
                                let hasData = false;
                                if (options && options.series && options.series.length > 0) {
                                    if (typeof options.series[0] === 'number') {
                                        hasData = options.series.some(val => val > 0);
                                    } else if (typeof options.series[0] === 'object') {
                                        hasData = options.series.some(s => s && s.data && s.data.length > 0 && s.data.some(val => val > 0));
                                    }
                                }
                                
                                if (!hasData) {
                                    if (targetEl) {
                                        targetEl.innerHTML = '<div class="d-flex flex-column align-items-center justify-content-center w-100 h-100 text-muted" style="min-height: 280px;"><i class="ti ti-chart-bar fs-1 mb-2" style="font-size: 2.8rem !important; color: #a1b0cb;"></i><span class="fw-semibold">No data available</span></div>';
                                    }
                                    return {
                                        render: function() { return Promise.resolve(); },
                                        destroy: function() {},
                                        updateOptions: function() {},
                                        updateSeries: function() {}
                                    };
                                }
                                
                                super(el, options);
                            }
                        };
                    } else {
                        privateApexCharts = originalValue;
                    }
                },
                configurable: true
            });
        })();
    </script>

    @yield('page-js')
    {{-- Branded Full-Page AJAX Loader Overlay --}}
    <div id="ajaxLoaderOverlay" class="page-loader" style="display:none; z-index:9999999;">
        <div class="loader-card">
            <div class="loader-visual">
                <div class="loader-ripple"></div>
                <div class="loader-ring loader-ring-1"></div>
                <div class="loader-ring loader-ring-2"></div>
                <div class="loader-ring loader-ring-3"></div>
                <div class="loader-logo-container">
                    <img src="{{ asset('assets/img/favicon/favicon.png') }}" alt="Logo" class="loader-logo-img" />
                </div>
            </div>
            <div class="loader-text-wrapper">
                <span class="loader-status">Loading</span>
                <span class="loader-dots"><span>.</span><span>.</span><span>.</span></span>
            </div>
        </div>
    </div>
    <script>
        window.showAjaxLoader = function () {
            var el = document.getElementById('ajaxLoaderOverlay');
            el.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        };
        window.hideAjaxLoader = function () {
            var el = document.getElementById('ajaxLoaderOverlay');
            el.style.display = 'none';
            document.body.style.overflow = '';
        };

        // Shared barcode-print launcher: stashes the item list server-side and
        // opens the print tab with a short token instead of a giant query
        // string, since long item lists can exceed the server's max URL length.
        window.startBarcodePrint = function (items) {
            if (!items || !items.length) return;

            const printTab = window.open('', '_blank');

            $.ajax({
                url: '{{ route("admin.products.print-barcodes.prepare") }}',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    items: items
                },
                success: function (res) {
                    if (res.status === 'success' && printTab) {
                        printTab.location.href = '{{ route("admin.products.print-barcodes") }}?auto_print=1&token=' + res.token;
                    } else if (printTab) {
                        printTab.close();
                    }
                },
                error: function () {
                    if (printTab) printTab.close();
                    if (window.toastr) {
                        toastr.error('Something went wrong while preparing barcodes. Please try again.');
                    }
                }
            });
        };
        
        setInterval(function () {
            if (document.visibilityState === 'visible') {
                $.get('{{ route("admin.ping") }}').fail(function () {});
            }
        }, 600000);
    </script>

    <style>
        html.modal-open,
        body.modal-open,
        body.modal-open .layout-wrapper,
        body.modal-open .layout-page,
        body.modal-open .content-wrapper,
        body.modal-open .menu-inner {
            overflow: hidden !important;
        }
        #inUseProductsModal {
            overflow-y: auto !important;
        }
        #inUseProductsModal .modal-body {
            max-height: none !important;
            overflow-y: visible !important;
        }
    </style>

    <!-- Shared Bootstrap Modal for In-Use Products List (DataTables) -->
    <div class="modal fade" id="inUseProductsModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-label-danger py-3 border-bottom">
                    <h5 class="modal-title text-danger fw-bold d-flex align-items-center" id="inUseProductsModalTitle">
                        <i class="ti ti-alert-triangle me-2 fs-4"></i> Cannot Delete Item
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning mb-3 d-flex align-items-start" role="alert">
                        <i class="ti ti-info-circle me-2 fs-4 mt-1 flex-shrink-0"></i>
                        <div id="inUseProductsModalMessage" class="fw-semibold text-dark">
                            This item cannot be deleted because it is currently in use by the products listed below. Please remove it from these products first.
                        </div>
                    </div>
                    <div class="table-responsive border rounded p-2">
                        <table class="table table-hover align-middle mb-0 text-nowrap w-100" id="inUseProductsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 8%;">#</th>
                                    <th style="width: 62%;">Product Name</th>
                                    <th style="width: 30%;">Barcode</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
