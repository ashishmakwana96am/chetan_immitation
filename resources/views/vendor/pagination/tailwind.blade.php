@if ($paginator->hasPages())
    <nav class="flex items-center justify-center gap-1 sm:gap-3 md:gap-4 mt-8 md:mt-10 flex-nowrap" role="navigation">
        {{-- Previous Page --}}
        @if ($paginator->onFirstPage())
            <span class="px-2 sm:px-4 py-2 sm:py-[13px] h-[40px] sm:h-[47px] border border-[#D5D5D5] text-[#757575] text-sm sm:text-base lg:text-xl font-medium flex items-center justify-center gap-1 sm:gap-2 opacity-50 cursor-not-allowed">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 18L9 12L15 6" />
                </svg>
                <span>Previous</span>
            </span>
        @else
            <button onclick="goToPage({{ $paginator->currentPage() - 1 }})" class="px-2 sm:px-4 py-2 sm:py-[13px] h-[40px] sm:h-[47px] border border-[#D5D5D5] text-[#757575] text-sm sm:text-base lg:text-xl font-medium flex items-center justify-center gap-1 sm:gap-2 hover:bg-gray-50 cursor-pointer">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 18L9 12L15 6" />
                </svg>
                <span>Previous</span>
            </button>
        @endif

        {{-- Page Numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="w-[32px] sm:w-auto px-1 sm:px-4 py-2 sm:py-[13px] h-[32px] sm:h-[47px] border border-[#D5D5D5] text-[#757575] text-xs sm:text-base lg:text-xl font-medium flex justify-center items-center">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <button onclick="goToPage({{ $page }})" class="w-[32px] sm:w-auto px-1 sm:px-4 py-2 sm:py-[13px] h-[32px] sm:h-[47px] bg-[#B67A1E] text-white border border-[#7575751E] text-xs sm:text-base lg:text-[18px] font-medium cursor-pointer flex justify-center items-center">{{ $page }}</button>
                    @else
                        <button onclick="goToPage({{ $page }})" class="w-[32px] sm:w-auto px-1 sm:px-4 py-2 sm:py-[13px] h-[32px] sm:h-[47px] border border-[#D5D5D5] text-[#757575] text-xs sm:text-base lg:text-xl font-medium hover:bg-gray-50 cursor-pointer flex justify-center items-center">{{ $page }}</button>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page --}}
        @if ($paginator->hasMorePages())
            <button onclick="goToPage({{ $paginator->currentPage() + 1 }})" class="px-2 sm:px-4 py-2 sm:py-[13px] h-[40px] sm:h-[47px] border border-[#D5D5D5] text-[#757575] text-sm sm:text-base lg:text-xl font-medium flex items-center justify-center gap-1 sm:gap-2 hover:bg-gray-50 cursor-pointer">
                <span>Next</span>
                <svg class="w-5 h-5 sm:w-6 sm:h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 6L15 12L9 18" />
                </svg>
            </button>
        @else
            <span class="px-2 sm:px-4 py-2 sm:py-[13px] h-[40px] sm:h-[47px] border border-[#D5D5D5] text-[#757575] text-sm sm:text-base lg:text-xl font-medium flex items-center justify-center gap-1 sm:gap-2 opacity-50 cursor-not-allowed">
                <span>Next</span>
                <svg class="w-5 h-5 sm:w-6 sm:h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 6L15 12L9 18" />
                </svg>
            </span>
        @endif
    </nav>
@endif
