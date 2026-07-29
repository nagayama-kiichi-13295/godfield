@php
    $size = $size ?? 48;
    $color = $color ?? 'currentColor';
@endphp
<svg class="emblem" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24"
     fill="{{ $color }}" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    @switch($icon)
        @case('sword')
            <path d="M12 1.5 L15 8 V14.5 L12 17.5 L9 14.5 V8 Z"/>
            <rect x="5" y="15" width="14" height="2.2" rx="1.1"/>
            <rect x="11" y="17.5" width="2" height="5" rx="1"/>
            @break

        @case('shield')
            <path d="M12 1.8 L20 5 V12 C20 17 16.4 20.8 12 22.2 C7.6 20.8 4 17 4 12 V5 Z"/>
            @break

        @case('staff')
            <circle cx="12" cy="5.2" r="3.4"/>
            <rect x="11" y="8.4" width="2" height="13.8" rx="1"/>
            @break

        @case('scales')
            <rect x="11" y="2.5" width="2" height="17" rx="1"/>
            <rect x="4" y="6" width="16" height="2" rx="1"/>
            <path d="M6.5 8.5 L3 14.5 H10 Z"/>
            <path d="M17.5 8.5 L14 14.5 H21 Z"/>
            <rect x="7" y="19.5" width="10" height="2" rx="1"/>
            @break

        @case('cpu')
            <rect x="11" y="1.5" width="2" height="3.5" rx="1"/>
            <rect x="3.5" y="5" width="17" height="14" rx="3.5"/>
            <rect x="6" y="20" width="4" height="2.5" rx="1"/>
            <rect x="14" y="20" width="4" height="2.5" rx="1"/>
            @break
    @endswitch
</svg>