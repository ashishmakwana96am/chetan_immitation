@if ($paginator->hasPages())
    <nav class="flex items-center justify-center gap-2 sm:gap-3 md:gap-4 mt-8 md:mt-10 flex-wrap" role="navigation">
        {{-- Previous Page --}}
        @if ($paginator->onFirstPage())
            <span class="px-3 sm:px-4 py-2 sm:py-[13px] h-[40px] sm:h-[47px] border border-[#D5D5D5] text-[#757575] text-sm sm:text-base lg:text-xl font-medium flex items-center justify-center gap-1 sm:gap-2 opacity-50 cursor-not-allowed">
                <span class="text-xl sm:text-[32px] leading-none">&lsaquo;</span>
                <span class="hidden sm:block">Previous</span>
            </span>
        @else
            <button onclick="goToPage({{ $paginator->currentPage() - 1 }})" class="px-3 sm:px-4 py-2 sm:py-[13px] h-[40px] sm:h-[47px] border border-[#D5D5D5] text-[#757575] text-sm sm:text-base lg:text-xl font-medium flex items-center justify-center gap-1 sm:gap-2 hover:bg-gray-50 cursor-pointer">
                <span class="text-xl sm:text-[32px] leading-none">&lsaquo;</span>
                <span class="hidden sm:block">Previous</span>
            </button>
        @endif

        {{-- Page Numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="w-[40px] sm:w-auto px-3 sm:px-4 py-2 sm:py-[13px] h-[40px] sm:h-[47px] border border-[#D5D5D5] text-[#757575] text-sm sm:text-base lg:text-xl font-medium flex items-center justify-center">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <button onclick="goToPage({{ $page }})" class="w-[40px] sm:w-auto px-3 sm:px-4 py-2 sm:py-[13px] h-[40px] sm:h-[47px] bg-[#B67A1E] text-white border border-[#7575751E] text-sm sm:text-base lg:text-[18px] font-medium cursor-pointer">{{ $page }}</button>
                    @else
                        <button onclick="goToPage({{ $page }})" class="w-[40px] sm:w-auto px-3 sm:px-4 py-2 sm:py-[13px] h-[40px] sm:h-[47px] border border-[#D5D5D5] text-[#757575] text-sm sm:text-base lg:text-xl font-medium hover:bg-gray-50 cursor-pointer">{{ $page }}</button>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page --}}
        @if ($paginator->hasMorePages())
            <button onclick="goToPage({{ $paginator->currentPage() + 1 }})" class="px-3 sm:px-4 py-2 sm:py-[13px] h-[40px] sm:h-[47px] border border-[#D5D5D5] text-[#757575] text-sm sm:text-base lg:text-xl font-medium flex items-center justify-center gap-1 sm:gap-2 hover:bg-gray-50 cursor-pointer">
                <span class="hidden sm:block">Next</span>
                <span class="text-xl sm:text-[32px] leading-none">&rsaquo;</span>
            </button>
        @else
            <span class="px-3 sm:px-4 py-2 sm:py-[13px] h-[40px] sm:h-[47px] border border-[#D5D5D5] text-[#757575] text-sm sm:text-base lg:text-xl font-medium flex items-center justify-center gap-1 sm:gap-2 opacity-50 cursor-not-allowed">
                <span class="hidden sm:block">Next</span>
                <span class="text-xl sm:text-[32px] leading-none">&rsaquo;</span>
            </span>
        @endif
    </nav>
@endif
