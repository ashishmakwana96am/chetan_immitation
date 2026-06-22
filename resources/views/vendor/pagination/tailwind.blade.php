@if ($paginator->hasPages())
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
    @endphp
    <nav class="mt-8 md:mt-10 w-full max-w-full overflow-visible px-1" role="navigation">
        <div class="flex w-full max-w-full flex-nowrap items-center justify-center gap-2 md:gap-3">
        {{-- Previous Page --}}
        @if ($paginator->onFirstPage())
            <span class="h-10 sm:h-[47px] min-w-10 sm:min-w-[104px] max-w-full px-2 sm:px-3 border border-[#D5D5D5] text-[#757575] text-sm sm:text-base lg:text-lg font-medium flex items-center justify-center gap-1 sm:gap-2 opacity-50 cursor-not-allowed whitespace-nowrap">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 18L9 12L15 6" />
                </svg>
                <span class="hidden sm:inline">Previous</span>
            </span>
        @else
            <button onclick="goToPage({{ $currentPage - 1 }})" class="h-10 sm:h-[47px] min-w-10 sm:min-w-[104px] max-w-full px-2 sm:px-3 border border-[#D5D5D5] text-[#757575] text-sm sm:text-base lg:text-lg font-medium flex items-center justify-center gap-1 sm:gap-2 hover:bg-gray-50 cursor-pointer whitespace-nowrap">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 18L9 12L15 6" />
                </svg>
                <span class="hidden sm:inline">Previous</span>
            </button>
        @endif

        {{-- Page Numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="h-10 sm:h-[47px] min-w-10 sm:min-w-[47px] px-2 sm:px-4 border border-[#D5D5D5] text-[#757575] text-sm sm:text-base lg:text-lg font-medium flex justify-center items-center">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @php
                        $isImportantPage = $page == 1 || $page == $lastPage || $page == $currentPage;
                        $responsiveVisibility = $isImportantPage ? 'flex' : 'hidden xl:flex';
                    @endphp
                    @if ($page == $currentPage)
                        <button onclick="goToPage({{ $page }})" class="h-10 sm:h-[47px] min-w-10 sm:min-w-[47px] px-2 sm:px-4 bg-[#B67A1E] text-white border border-[#7575751E] text-sm sm:text-base lg:text-lg font-medium cursor-pointer flex justify-center items-center">{{ $page }}</button>
                    @else
                        <button onclick="goToPage({{ $page }})" class="h-10 sm:h-[47px] min-w-10 sm:min-w-[47px] px-2 sm:px-4 border border-[#D5D5D5] text-[#757575] text-sm sm:text-base lg:text-lg font-medium hover:bg-gray-50 cursor-pointer {{ $responsiveVisibility }} justify-center items-center">{{ $page }}</button>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page --}}
        @if ($paginator->hasMorePages())
            <button onclick="goToPage({{ $currentPage + 1 }})" class="h-10 sm:h-[47px] min-w-10 sm:min-w-[84px] max-w-full px-2 sm:px-3 border border-[#D5D5D5] text-[#757575] text-sm sm:text-base lg:text-lg font-medium flex items-center justify-center gap-1 sm:gap-2 hover:bg-gray-50 cursor-pointer whitespace-nowrap">
                <span class="hidden sm:inline">Next</span>
                <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 6L15 12L9 18" />
                </svg>
            </button>
        @else
            <span class="h-10 sm:h-[47px] min-w-10 sm:min-w-[84px] max-w-full px-2 sm:px-3 border border-[#D5D5D5] text-[#757575] text-sm sm:text-base lg:text-lg font-medium flex items-center justify-center gap-1 sm:gap-2 opacity-50 cursor-not-allowed whitespace-nowrap">
                <span class="hidden sm:inline">Next</span>
                <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 6L15 12L9 18" />
                </svg>
            </span>
        @endif
        </div>
    </nav>
@endif
