@php
    $rating = max(0, min(5, (float) ($rating ?? 0)));
    $size = $size ?? 'sm';
    $starSize = $size === 'lg' ? 25 : ($size === 'md' ? 20 : 16);
    $filledColor = '#B4771E';
    $emptyColor = $size === 'lg' ? '#D5D5D5' : ($size === 'md' ? '#D2D2D2' : '#D5D5D5');
    $starPath = 'm512 197.816-186.039-12.231L255.898 9.569l-70.063 176.016L0 197.816l142.534 121.026-46.772 183.589L255.898 401.21l160.137 101.221-46.772-183.589z';
@endphp
<div class="flex items-center gap-0.5 {{ $class ?? '' }}">
    @for ($i = 1; $i <= 5; $i++)
        @php
            $fill = min(1, max(0, $rating - ($i - 1)));
            $clipRight = $fill > 0 ? (100 - ($fill * 100)) : 100;
        @endphp
        <span class="relative inline-block shrink-0" style="width: {{ $starSize }}px; height: {{ $starSize }}px;">
            <svg xmlns="http://www.w3.org/2000/svg"
                width="{{ $starSize }}"
                height="{{ $starSize }}"
                viewBox="0 0 512 512"
                fill="{{ $emptyColor }}"
                aria-hidden="true">
                <path d="{{ $starPath }}"/>
            </svg>
            @if($fill > 0)
            <svg xmlns="http://www.w3.org/2000/svg"
                class="absolute top-0 left-0"
                width="{{ $starSize }}"
                height="{{ $starSize }}"
                viewBox="0 0 512 512"
                fill="{{ $filledColor }}"
                style="clip-path: inset(0 {{ $clipRight }}% 0 0);"
                aria-hidden="true">
                <path d="{{ $starPath }}"/>
            </svg>
            @endif
        </span>
    @endfor
</div>
