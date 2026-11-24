@php
    $activeBooking = null;
    $activeBookingData = null;
    if (auth('customer')->check()) {
        $activeBooking = \App\Models\VideoCallBooking::getActiveBookingForCustomer(auth('customer')->id());
        if ($activeBooking) {
            $activeBookingData = [
                'booking_no' => $activeBooking->booking_no,
                'booking_date' => $activeBooking->booking_date->format('d M Y'),
                'time_slot' => \Carbon\Carbon::parse($activeBooking->time_slot_start)->format('h:i A') . ' - ' . \Carbon\Carbon::parse($activeBooking->time_slot_end)->format('h:i A'),
                'google_meet_link' => $activeBooking->google_meet_link,
                'end_datetime' => \Carbon\Carbon::parse($activeBooking->booking_date->format('Y-m-d') . ' ' . $activeBooking->time_slot_end)->toIso8601String(),
            ];
        }
    }
@endphp

<style>
    .vc-slot-btn {
        border-radius: 4px !important;
        border: 1px solid #E5E5E5 !important;
        background-color: #FAFAFA !important;
        color: #131615 !important;
        transition: all 0.25s ease !important;
        white-space: nowrap !important;
    }
    .vc-slot-btn:hover:not(:disabled):not(.is-active) {
        border-color: #B4771E !important;
        color: #B4771E !important;
        background-color: #FAF7F2 !important;
    }
    .vc-slot-btn.is-active {
        background-color: #B4771E !important;
        border-color: #B4771E !important;
        color: #ffffff !important;
    }
    .vc-slot-btn.is-active:hover {
        background-color: #131615 !important;
        border-color: #131615 !important;
        color: #ffffff !important;
    }
    .vc-slot-btn:disabled {
        background-color: #f3f4f6 !important;
        border-color: #e5e7eb !important;
        color: #9ca3af !important;
        cursor: not-allowed !important;
    }
    html.vc-modal-open,
    body.vc-modal-open {
        overflow: hidden !important;
        height: 100% !important;
        overscroll-behavior: none !important;
    }
</style>

<div id="videoCallModal" class="fixed inset-0 bg-black/60 z-[9999] hidden flex items-center justify-center p-2.5 sm:p-4 overflow-y-auto" style="overscroll-behavior: contain;">
    <div class="bg-white rounded-sm shadow-2xl max-w-lg w-full max-h-[95vh] sm:max-h-[90vh] flex flex-col overflow-hidden relative overscroll-contain border-t-4 border-[#B4771E]" onclick="event.stopPropagation();">
        <div class="bg-white p-4 sm:p-5 flex items-center justify-between shrink-0 border-b border-[#EFE7DB]">
            <div>
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-[#FAF7F2] text-[#B4771E] text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider mb-1 border border-[#EFE7DB] rounded-sm">
                    <i class="fa-solid fa-video text-[10px]"></i> Virtual Experience
                </div>
                <h3 class="text-base sm:text-lg md:text-xl font-bold text-[#131615]">
                    Book a Video Shopping Call
                </h3>
                <p class="text-[11px] sm:text-xs text-[#757575] mt-0.5">Explore our handcrafted jewellery live with our expert stylist</p>
            </div>
            <button type="button" id="closeVideoCallModal" class="w-8 h-8 rounded-sm border border-[#EFE7DB] text-gray-400 hover:text-[#131615] hover:bg-[#FAF7F2] flex items-center justify-center text-xl transition" aria-label="Close">&times;</button>
        </div>

        <div id="vcFormState" class="p-4 sm:p-6 overflow-y-auto flex-1">
            @auth('customer')
            <div class="bg-[#FAF7F2] border border-[#EFE7DB] p-2.5 sm:p-3 mb-4 flex items-center justify-between gap-2 text-xs sm:text-sm rounded-sm">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-sm bg-[#B4771E] text-white flex items-center justify-center font-bold text-xs shrink-0">
                        {{ strtoupper(substr(auth('customer')->user()->name, 0, 1)) }}
                    </div>
                    <div>
                        <span class="text-gray-400 text-[10px] sm:text-[11px] block leading-none">Booking as</span>
                        <strong class="text-[#131615] font-semibold text-xs sm:text-sm">{{ auth('customer')->user()->name }}</strong>
                    </div>
                </div>
                <span class="text-gray-500 font-medium text-xs">{{ auth('customer')->user()->phone ?? auth('customer')->user()->email }}</span>
            </div>
            @endauth

            <form id="videoCallBookingForm">
                @csrf
                <div class="mb-4">
                    <label class="block text-xs sm:text-sm font-semibold text-[#131615] mb-1.5">Select Date <span class="text-red-500">*</span></label>
                    <input type="date" id="vcBookingDate" name="booking_date" min="{{ date('Y-m-d') }}" max="{{ date('Y-m-d', strtotime('+3 months')) }}" value="{{ date('Y-m-d') }}" class="w-full border border-gray-300 rounded-sm px-3.5 py-2.5 text-xs sm:text-sm outline-none focus:border-[#B4771E] bg-white cursor-pointer transition">
                </div>

                <div class="mb-4">
                    <div class="flex items-center justify-between mb-1.5 flex-wrap gap-1">
                        <label class="block text-xs sm:text-sm font-semibold text-[#131615]">Select Slot <span class="text-red-500">*</span></label>
                        <span class="text-[11px] text-[#B4771E] font-medium flex items-center gap-1">
                            <i class="fa-regular fa-clock"></i> 09:00 AM – 08:00 PM
                        </span>
                    </div>
                    <div id="vcSlotsGrid" class="grid grid-cols-2 sm:grid-cols-3 gap-1.5 sm:gap-2 max-h-44 sm:max-h-52 overflow-y-auto p-1.5 border border-gray-200 bg-[#FAFAFA] rounded-sm">
                    </div>
                    <input type="hidden" id="vcSlotStart" name="time_slot_start" value="">
                    <input type="hidden" id="vcSlotEnd" name="time_slot_end" value="">
                    <p id="vcSlotError" class="text-xs text-red-600 mt-1 hidden"></p>
                </div>

                <div class="mb-5">
                    <label class="block text-xs sm:text-sm font-semibold text-[#131615] mb-1.5">Notes / Preferences</label>
                    <textarea id="vcNotes" name="notes" rows="2" placeholder="e.g. Bridal choker sets, Kundan bangles, earrings..." class="w-full border border-gray-300 rounded-sm px-3.5 py-2 text-xs sm:text-sm outline-none focus:border-[#B4771E] resize-none"></textarea>
                </div>

                <button type="submit" id="btnSubmitVideoCall" class="w-full bg-[#B4771E] hover:bg-[#131615] text-white font-medium py-3 text-sm sm:text-base transition-all duration-300 flex items-center justify-center gap-2 rounded-sm shadow-sm disabled:opacity-50 tracking-wide uppercase">
                    <span id="vcSubmitBtnText">Confirm Booking</span>
                    <svg id="vcSubmitSpinner" class="hidden animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                </button>
            </form>
        </div>

        <div id="vcSuccessState" class="p-5 sm:p-7 text-center hidden overflow-y-auto flex-1">
            <div class="w-14 h-14 bg-[#FAF7F2] text-[#B4771E] border border-[#EFE7DB] flex items-center justify-center mx-auto mb-3.5 text-2xl rounded-sm">
                <i class="fa-solid fa-check"></i>
            </div>
            <h4 id="vcSuccessTitle" class="text-lg sm:text-xl font-bold text-[#131615] mb-1">Booking Confirmed!</h4>
            <p id="vcSuccessSubtitle" class="text-xs sm:text-sm text-gray-600 mb-4">Your video shopping call has been scheduled successfully.</p>
            
            <div class="bg-[#FAF7F2] border border-[#EFE7DB] p-3.5 sm:p-4 text-left mb-4 text-xs sm:text-sm space-y-2 rounded-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Booking No:</span>
                    <strong id="vcSuccessBookingNo" class="text-[#B4771E]"></strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Date:</span>
                    <span id="vcSuccessDate" class="font-medium text-[#131615]"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Time Slot:</span>
                    <span id="vcSuccessSlot" class="font-medium text-[#131615]"></span>
                </div>
            </div>

            <div id="vcMeetLinkWrap" class="mb-5 text-left hidden">
                <label class="block text-xs font-semibold text-[#131615] mb-1.5">Google Meet Link:</label>
                <div class="flex items-stretch border border-[#EFE7DB] bg-[#FAF7F2] rounded-sm overflow-hidden">
                    <a id="vcSuccessMeetLink" href="#" target="_blank" class="px-3 py-2 text-xs sm:text-sm text-[#B4771E] hover:underline font-medium truncate flex-1 flex items-center gap-1.5" title="Click to join meet">
                        <i class="fa-solid fa-video text-xs text-[#B4771E] shrink-0"></i>
                        <span id="vcSuccessMeetLinkText" class="truncate"></span>
                    </a>
                    <button type="button" id="btnCopyMeetLink" class="px-3.5 py-2 bg-white hover:bg-[#FAF7F2] text-[#131615] hover:text-[#B4771E] border-s border-[#EFE7DB] text-xs font-semibold flex items-center gap-1.5 transition shrink-0" title="Copy Link">
                        <i class="fa-regular fa-copy"></i>
                        <span id="copyBtnText">Copy</span>
                    </button>
                </div>
                <p class="text-[11px] text-gray-500 mt-1.5 flex items-center gap-1">
                    <i class="fa-solid fa-circle-info text-[#B4771E]"></i>
                    Click the link to join directly, or copy it. You will also receive an invite in your email.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" id="vcDoneBtn" class="flex-1 bg-[#131615] hover:bg-[#B4771E] text-white font-medium py-2.5 rounded-sm text-xs sm:text-sm transition duration-300 cursor-pointer">
                    Close
                </button>
                <button type="button" id="vcCancelBookingBtn" class="flex-1 bg-red-600 hover:bg-[#131615] text-white font-medium py-2.5 rounded-sm text-xs sm:text-sm transition duration-300 flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                    <span id="vcCancelBookingText">Cancel Call</span>
                </button>
            </div>
            <p id="vcCancelError" class="text-xs text-red-600 mt-2 hidden text-center"></p>
        </div>

        <div id="vcConfirmCancelModal" class="absolute inset-0 bg-black/60 backdrop-blur-[2px] z-50 flex items-center justify-center p-4 hidden">
            <div class="bg-white rounded-sm p-5 sm:p-6 max-w-sm w-full text-center shadow-2xl border-t-4 border-red-600" onclick="event.stopPropagation();">
                <div class="w-12 h-12 bg-red-50 text-red-600 border border-red-200 flex items-center justify-center mx-auto mb-3 text-xl rounded-sm">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h4 class="text-base sm:text-lg font-bold text-[#131615] mb-1.5">Cancel Video Call?</h4>
                <p class="text-xs text-gray-600 mb-5 leading-relaxed">
                    Are you sure you want to cancel your video shopping call? The Google Meet link will also be cancelled.
                </p>
                <p id="vcConfirmCancelError" class="text-xs text-red-600 mb-3 hidden"></p>
                <div class="flex items-center gap-2">
                    <button type="button" id="btnDismissCancelModal" class="flex-1 bg-gray-100 hover:bg-gray-200 text-[#131615] font-medium py-2.5 rounded-sm text-xs sm:text-sm transition duration-300 cursor-pointer">
                        Keep Call
                    </button>
                    <button type="button" id="btnConfirmCancelModal" class="flex-1 bg-red-600 hover:bg-[#131615] text-white font-medium py-2.5 rounded-sm text-xs sm:text-sm transition duration-300 flex items-center justify-center gap-1.5 shadow-sm cursor-pointer">
                        <i class="fa-solid fa-xmark"></i>
                        <span id="vcConfirmCancelText">Yes, Cancel</span>
                        <svg id="vcConfirmCancelSpinner" class="hidden animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const isCustomerLoggedIn = {{ auth('customer')->check() ? 'true' : 'false' }};
    const modal = document.getElementById('videoCallModal');
    const closeBtn = document.getElementById('closeVideoCallModal');
    const doneBtn = document.getElementById('vcDoneBtn');
    const dateInput = document.getElementById('vcBookingDate');
    const slotsGrid = document.getElementById('vcSlotsGrid');
    const slotStartInput = document.getElementById('vcSlotStart');
    const slotEndInput = document.getElementById('vcSlotEnd');
    const slotError = document.getElementById('vcSlotError');
    const bookingForm = document.getElementById('videoCallBookingForm');
    const submitBtn = document.getElementById('btnSubmitVideoCall');
    const submitBtnText = document.getElementById('vcSubmitBtnText');
    const submitSpinner = document.getElementById('vcSubmitSpinner');

    const formState = document.getElementById('vcFormState');
    const successState = document.getElementById('vcSuccessState');
    const successTitle = document.getElementById('vcSuccessTitle');
    const successSubtitle = document.getElementById('vcSuccessSubtitle');

    const copyBtn = document.getElementById('btnCopyMeetLink');
    const copyText = document.getElementById('copyBtnText');
    const meetLinkEl = document.getElementById('vcSuccessMeetLink');
    const meetLinkText = document.getElementById('vcSuccessMeetLinkText');
    const meetWrap = document.getElementById('vcMeetLinkWrap');
    const cancelBookingBtn = document.getElementById('vcCancelBookingBtn');
    const confirmCancelModal = document.getElementById('vcConfirmCancelModal');
    const btnDismissCancelModal = document.getElementById('btnDismissCancelModal');
    const btnConfirmCancelModal = document.getElementById('btnConfirmCancelModal');
    const confirmCancelText = document.getElementById('vcConfirmCancelText');
    const confirmCancelSpinner = document.getElementById('vcConfirmCancelSpinner');
    const confirmCancelError = document.getElementById('vcConfirmCancelError');

    let currentActiveBooking = @json($activeBookingData);

    const ALL_SLOTS = [
        { start: '09:00', end: '10:00', label: '09:00 - 10:00 AM' },
        { start: '10:00', end: '11:00', label: '10:00 - 11:00 AM' },
        { start: '11:00', end: '12:00', label: '11:00 - 12:00 PM' },
        { start: '12:00', end: '13:00', label: '12:00 - 01:00 PM' },
        { start: '13:00', end: '14:00', label: '01:00 - 02:00 PM' },
        { start: '14:00', end: '15:00', label: '02:00 - 03:00 PM' },
        { start: '15:00', end: '16:00', label: '03:00 - 04:00 PM' },
        { start: '16:00', end: '17:00', label: '04:00 - 05:00 PM' },
        { start: '17:00', end: '18:00', label: '05:00 - 06:00 PM' },
        { start: '18:00', end: '19:00', label: '06:00 - 07:00 PM' },
        { start: '19:00', end: '20:00', label: '07:00 - 08:00 PM' }
    ];

    function getTodayString() {
        const now = new Date();
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const d = String(now.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function getMaxDateString() {
        const now = new Date();
        now.setMonth(now.getMonth() + 3);
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const d = String(now.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function isBookingStillActive(booking) {
        if (!booking || !booking.end_datetime) return false;
        return new Date(booking.end_datetime).getTime() > Date.now();
    }

    function showActiveBookingPopup(booking, isNewlyBooked = false) {
        document.getElementById('vcSuccessBookingNo').textContent = booking.booking_no;
        document.getElementById('vcSuccessDate').textContent = booking.booking_date;
        document.getElementById('vcSuccessSlot').textContent = booking.time_slot;

        if (booking.google_meet_link) {
            meetLinkEl.href = booking.google_meet_link;
            meetLinkText.textContent = booking.google_meet_link;
            meetWrap.classList.remove('hidden');
        } else {
            meetWrap.classList.add('hidden');
        }

        if (confirmCancelModal) confirmCancelModal.classList.add('hidden');

        if (isNewlyBooked) {
            successTitle.textContent = 'Booking Confirmed!';
            successSubtitle.textContent = 'Your video shopping call has been scheduled successfully.';
        } else {
            successTitle.textContent = 'Active Video Call Booking';
            successSubtitle.textContent = 'You have an active scheduled video call. You can book a new call once this slot ends.';
        }

        formState.classList.add('hidden');
        successState.classList.remove('hidden');
        modal.classList.remove('hidden');

        document.documentElement.classList.add('overflow-hidden', 'vc-modal-open');
        document.body.classList.add('overflow-hidden', 'vc-modal-open');
        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';
    }

    function openModal() {
        if (!isCustomerLoggedIn) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('open_video_call', '1');
            window.location.href = "{{ route('login') }}?intended=" + encodeURIComponent(currentUrl.toString());
            return;
        }

        if (isBookingStillActive(currentActiveBooking)) {
            showActiveBookingPopup(currentActiveBooking, false);
            return;
        }

        currentActiveBooking = null;
        formState.classList.remove('hidden');
        successState.classList.add('hidden');
        modal.classList.remove('hidden');

        document.documentElement.classList.add('overflow-hidden', 'vc-modal-open');
        document.body.classList.add('overflow-hidden', 'vc-modal-open');
        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';

        if (dateInput) {
            dateInput.value = getTodayString();
        }
        renderSlots();
    }

    function closeModal() {
        if (confirmCancelModal) confirmCancelModal.classList.add('hidden');
        modal.classList.add('hidden');
        document.documentElement.classList.remove('overflow-hidden', 'vc-modal-open');
        document.body.classList.remove('overflow-hidden', 'vc-modal-open');
        document.documentElement.style.overflow = '';
        document.body.style.overflow = '';
    }

    modal.addEventListener('wheel', function (e) {
        if (e.target === modal) {
            e.preventDefault();
        }
    }, { passive: false });

    modal.addEventListener('touchmove', function (e) {
        if (e.target === modal) {
            e.preventDefault();
        }
    }, { passive: false });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (confirmCancelModal && !confirmCancelModal.classList.contains('hidden')) {
                confirmCancelModal.classList.add('hidden');
                return;
            }
            if (!modal.classList.contains('hidden')) {
                closeModal();
            }
        }
    });

    document.querySelectorAll('.btnOpenVideoCall').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            openModal();
        });
    });

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (doneBtn) doneBtn.addEventListener('click', closeModal);

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    function selectSlot(slot, clickedBtn) {
        document.querySelectorAll('#vcSlotsGrid .slot-btn').forEach(b => {
            b.classList.remove('is-active');
        });

        clickedBtn.classList.add('is-active');

        slotStartInput.value = slot.start;
        slotEndInput.value = slot.end;
        slotError.classList.add('hidden');
    }

    function renderSlots() {
        slotStartInput.value = '';
        slotEndInput.value = '';
        slotError.classList.add('hidden');

        const selectedDate = dateInput ? dateInput.value : getTodayString();
        const now = new Date();
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const d = String(now.getDate()).padStart(2, '0');
        const todayStr = `${y}-${m}-${d}`;

        const isToday = (selectedDate === todayStr);
        const minAllowed = new Date(now.getTime() + (60 * 60 * 1000));

        slotsGrid.innerHTML = '';
        let hasAvailable = false;

        ALL_SLOTS.forEach(slot => {
            let isTooSoon = false;
            if (isToday) {
                const [sh, sm] = slot.start.split(':').map(Number);
                const slotStartTime = new Date();
                slotStartTime.setHours(sh, sm, 0, 0);
                if (slotStartTime < minAllowed) {
                    isTooSoon = true;
                }
            }

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'slot-btn vc-slot-btn py-2 px-1 sm:px-2 text-[11px] sm:text-xs text-center font-medium';
            btn.textContent = slot.label;
            btn.disabled = isTooSoon;

            if (!isTooSoon) {
                hasAvailable = true;
                btn.addEventListener('click', function () {
                    selectSlot(slot, btn);
                });
            }

            slotsGrid.appendChild(btn);
        });

        if (!hasAvailable) {
            slotsGrid.innerHTML = '<div class="col-span-2 sm:col-span-3 text-center py-4 text-xs text-amber-700">No more slots available for today (1-hour advance required). Please choose another date.</div>';
        }
    }

    if (dateInput) {
        function handleDateChange() {
            const minDate = getTodayString();
            const maxDate = getMaxDateString();
            if (dateInput.value) {
                if (dateInput.value < minDate) {
                    dateInput.value = minDate;
                } else if (dateInput.value > maxDate) {
                    dateInput.value = maxDate;
                }
            }
            renderSlots();
        }

        dateInput.addEventListener('change', handleDateChange);
        dateInput.addEventListener('input', handleDateChange);
    }

    if (copyBtn && meetLinkEl) {
        copyBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const link = meetLinkEl.getAttribute('href');
            if (!link || link === '#') return;

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(link).then(showCopiedState);
            } else {
                const tempInput = document.createElement('input');
                tempInput.value = link;
                document.body.appendChild(tempInput);
                tempInput.select();
                try {
                    document.execCommand('copy');
                    showCopiedState();
                } catch (err) {}
                document.body.removeChild(tempInput);
            }
        });
    }

    function showCopiedState() {
        if (!copyText) return;
        const orig = copyText.textContent;
        copyText.textContent = 'Copied!';
        copyBtn.classList.add('text-[#B4771E]', 'bg-[#FAF7F2]');
        setTimeout(function () {
            copyText.textContent = orig;
            copyBtn.classList.remove('text-[#B4771E]', 'bg-[#FAF7F2]');
        }, 2200);
    }

    if (bookingForm) {
        bookingForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const minDate = getTodayString();
            const maxDate = getMaxDateString();
            if (!dateInput.value || dateInput.value < minDate || dateInput.value > maxDate) {
                slotError.textContent = 'Please select a date between today and next 3 months.';
                slotError.classList.remove('hidden');
                return;
            }

            if (!slotStartInput.value || !slotEndInput.value) {
                slotError.textContent = 'Please select a time slot.';
                slotError.classList.remove('hidden');
                return;
            }

            submitBtn.disabled = true;
            submitBtnText.textContent = 'Booking...';
            submitSpinner.classList.remove('hidden');

            const payload = {
                booking_date: dateInput.value,
                time_slot_start: slotStartInput.value,
                time_slot_end: slotEndInput.value,
                notes: document.getElementById('vcNotes').value.trim(),
                _token: '{{ csrf_token() }}'
            };

            fetch("{{ route('video-call.book') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtnText.textContent = 'Confirm Booking';
                submitSpinner.classList.add('hidden');

                if (data.status === 'success') {
                    currentActiveBooking = data.booking;
                    showActiveBookingPopup(data.booking, true);
                } else {
                    slotError.textContent = data.message || 'Something went wrong. Please try again.';
                    slotError.classList.remove('hidden');
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                submitBtnText.textContent = 'Confirm Booking';
                submitSpinner.classList.add('hidden');
                slotError.textContent = 'Network error. Please try again.';
                slotError.classList.remove('hidden');
            });
        });
    }

    if (cancelBookingBtn) {
        cancelBookingBtn.addEventListener('click', function () {
            if (confirmCancelError) confirmCancelError.classList.add('hidden');
            if (confirmCancelModal) confirmCancelModal.classList.remove('hidden');
        });
    }

    if (btnDismissCancelModal) {
        btnDismissCancelModal.addEventListener('click', function () {
            if (confirmCancelModal) confirmCancelModal.classList.add('hidden');
        });
    }

    if (confirmCancelModal) {
        confirmCancelModal.addEventListener('click', function (e) {
            if (e.target === confirmCancelModal) {
                confirmCancelModal.classList.add('hidden');
            }
        });
    }

    if (btnConfirmCancelModal) {
        btnConfirmCancelModal.addEventListener('click', function () {
            btnConfirmCancelModal.disabled = true;
            if (confirmCancelText) confirmCancelText.textContent = 'Cancelling...';
            if (confirmCancelSpinner) confirmCancelSpinner.classList.remove('hidden');
            if (confirmCancelError) confirmCancelError.classList.add('hidden');

            const payload = {
                booking_no: (currentActiveBooking && currentActiveBooking.booking_no) ? currentActiveBooking.booking_no : '',
                _token: '{{ csrf_token() }}'
            };

            fetch("{{ route('video-call.cancel') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                btnConfirmCancelModal.disabled = false;
                if (confirmCancelText) confirmCancelText.textContent = 'Yes, Cancel';
                if (confirmCancelSpinner) confirmCancelSpinner.classList.add('hidden');

                if (data.status === 'success') {
                    if (confirmCancelModal) confirmCancelModal.classList.add('hidden');
                    currentActiveBooking = null;
                    if (window.showWishlistToast) {
                        window.showWishlistToast(data.message || 'Video call booking cancelled.', true);
                    }
                    successState.classList.add('hidden');
                    formState.classList.remove('hidden');
                    if (dateInput) {
                        dateInput.value = getTodayString();
                    }
                    renderSlots();
                } else {
                    if (confirmCancelError) {
                        confirmCancelError.textContent = data.message || 'Could not cancel booking.';
                        confirmCancelError.classList.remove('hidden');
                    } else if (window.showWishlistToast) {
                        window.showWishlistToast(data.message || 'Could not cancel booking.', false);
                    }
                }
            })
            .catch(err => {
                btnConfirmCancelModal.disabled = false;
                if (confirmCancelText) confirmCancelText.textContent = 'Yes, Cancel';
                if (confirmCancelSpinner) confirmCancelSpinner.classList.add('hidden');
                if (confirmCancelError) {
                    confirmCancelError.textContent = 'Network error. Please try again.';
                    confirmCancelError.classList.remove('hidden');
                }
            });
        });
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('open_video_call') === '1' && isCustomerLoggedIn) {
        urlParams.delete('open_video_call');
        const cleanUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
        window.history.replaceState({}, '', cleanUrl);
        openModal();
    }
});
</script>
