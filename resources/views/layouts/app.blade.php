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
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet" />

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
        background: radial-gradient(circle at 15% 20%, rgba(180, 119, 30, 0.07) 0%, transparent 40%),
                    radial-gradient(circle at 85% 80%, rgba(50, 134, 147, 0.07) 0%, transparent 40%),
                    rgba(255, 255, 255, 0.75);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        display: flex; 
        align-items: center; 
        justify-content: center;
        transition: opacity 0.35s cubic-bezier(0.25, 1, 0.5, 1);
    }
    .page-loader.fade-out { 
        opacity: 0; 
        pointer-events: none; 
    }

    .loader-card {
        background: rgba(255, 255, 255, 0.65);
        border: 1px solid rgba(255, 255, 255, 0.6);
        box-shadow: 
            0 10px 30px rgba(0, 0, 0, 0.02),
            0 30px 60px rgba(0, 0, 0, 0.04),
            inset 0 1px 0 rgba(255, 255, 255, 0.8);
        border-radius: 28px;
        padding: 3rem 4rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2rem;
        animation: cardEntrance 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes cardEntrance {
        0% { transform: scale(0.9) translateY(20px); opacity: 0; }
        100% { transform: scale(1) translateY(0); opacity: 1; }
    }

    .loader-visual {
        position: relative;
        width: 120px;
        height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .loader-ring {
        position: absolute;
        border-radius: 50%;
        border: 3px solid transparent;
    }

    .loader-ripple {
        position: absolute;
        width: 100%;
        height: 100%;
        border: 2px solid rgba(180, 119, 30, 0.3);
        border-radius: 50%;
        animation: sonarPulse 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
    }

    .loader-ring-1 {
        width: 100%;
        height: 100%;
        border-top-color: #B4771E;
        border-right-color: rgba(180, 119, 30, 0.1);
        border-bottom-color: #B4771E;
        border-left-color: rgba(180, 119, 30, 0.1);
        animation: rotateClockwise 2.2s cubic-bezier(0.68, -0.4, 0.265, 1.4) infinite;
        filter: drop-shadow(0 0 6px rgba(180, 119, 30, 0.3));
    }

    .loader-ring-2 {
        width: 80%;
        height: 80%;
        border-top-color: rgba(50, 134, 147, 0.1);
        border-right-color: #328693;
        border-bottom-color: rgba(50, 134, 147, 0.1);
        border-left-color: #328693;
        animation: rotateCounterClockwise 1.7s cubic-bezier(0.68, -0.4, 0.265, 1.4) infinite;
        filter: drop-shadow(0 0 5px rgba(50, 134, 147, 0.25));
    }

    .loader-ring-3 {
        width: 60%;
        height: 60%;
        border-top-color: #B4771E;
        border-right-color: rgba(180, 119, 30, 0.1);
        border-bottom-color: #328693;
        border-left-color: rgba(50, 134, 147, 0.1);
        animation: rotateClockwise 1.2s linear infinite;
        filter: drop-shadow(0 0 4px rgba(180, 119, 30, 0.2));
    }

    .loader-logo-container {
        position: absolute;
        width: 44%;
        height: 44%;
        background: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
        animation: logoPulse 2s ease-in-out infinite;
        overflow: hidden;
    }

    .loader-logo-img {
        width: 60%;
        height: 60%;
        object-fit: contain;
    }

    .loader-text-wrapper {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .loader-status {
        font-size: 0.85rem;
        font-weight: 700;
        letter-spacing: 2px;
        text-transform: uppercase;
        background: linear-gradient(90deg, #444 0%, #B4771E 25%, #328693 50%, #B4771E 75%, #444 100%);
        background-size: 200% auto;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: textShimmer 3s linear infinite;
    }

    .loader-dots span {
        font-size: 1.2rem;
        font-weight: 700;
        color: #328693;
        display: inline-block;
        animation: dotBounce 1.4s infinite ease-in-out;
    }

    .loader-dots span:nth-child(2) {
        animation-delay: 0.2s;
    }

    .loader-dots span:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes rotateClockwise {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @keyframes rotateCounterClockwise {
        0% { transform: rotate(360deg); }
        100% { transform: rotate(0deg); }
    }

    @keyframes logoPulse {
        0%, 100% { transform: scale(1); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06); }
        50% { transform: scale(1.08); box-shadow: 0 4px 25px rgba(180, 119, 30, 0.25); }
    }

    @keyframes sonarPulse {
        0% { transform: scale(0.6); opacity: 1; }
        100% { transform: scale(1.3); opacity: 0; }
    }

    @keyframes textShimmer {
        0% { background-position: 0% center; }
        100% { background-position: -200% center; }
    }

    @keyframes dotBounce {
        0%, 100% { transform: translateY(0); opacity: 0.4; }
        50% { transform: translateY(-5px); opacity: 1; }
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
                                class="footer-container d-flex align-items-center justify-center sm:justify-content-between py-2 flex-md-row flex-column">
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

            function hideLoader() {
                if (loaderHidden) return;
                loaderHidden = true;
                $('#pageLoader').addClass('fade-out');
                setTimeout(function() {
                    $('#pageLoader').remove();
                }, 350);
            }

            var fallbackTimeout = setTimeout(function() {
                hideLoader();
            }, 5000);

            $(document).on('preInit.dt', function(e, settings) {
                if (settings && settings.oFeatures) {
                    settings.oFeatures.bProcessing = false;
                }
                activeDataTables++;
                hasDataTables = true;
            });

            $(document).on('init.dt', function(e, settings) {
                activeDataTables--;
                setTimeout(function() {
                    if (activeDataTables <= 0 && hasDataTables) {
                        clearTimeout(fallbackTimeout);
                        hideLoader();
                    }
                }, 50);
            });

            setTimeout(function() {
                if (!hasDataTables || activeDataTables <= 0) {
                    clearTimeout(fallbackTimeout);
                    hideLoader();
                }
            }, 200);

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
    </script>
</body>

</html>
