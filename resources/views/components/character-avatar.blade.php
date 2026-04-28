@props([
    'character',
    'size' => 'md',
    'status' => false,
])

@php
    $sizes = [
        'sm' => 'h-10 w-10 text-sm',
        'md' => 'h-14 w-14 text-base',
        'lg' => 'h-16 w-16 text-lg',
        'xl' => 'h-32 w-32 text-3xl sm:h-40 sm:w-40 sm:text-4xl',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $dotSize = match ($size) {
        'sm' => 'h-2.5 w-2.5',
        'xl' => 'h-4 w-4',
        default => 'h-3 w-3',
    };
@endphp

<div {{ $attributes->merge(['class' => 'relative inline-flex shrink-0']) }}>
    <div class="rounded-full flex items-center justify-center font-bold text-white {{ $character->avatar_gradient_class }} {{ $sizeClass }}">
        {{ $character->initials }}
    </div>

    @if ($status)
        <span class="absolute bottom-0 right-0 rounded-full bg-success ring-2 ring-base-100 {{ $dotSize }}"></span>
    @endif
</div>
