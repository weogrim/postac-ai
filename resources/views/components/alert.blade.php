@props([
    'type' => 'info',
    'style' => null,
    'title' => null,
    'errorList' => [],
])

@php
    $typeClass = match ($type) {
        'success' => 'alert-success',
        'warning' => 'alert-warning',
        'error' => 'alert-error',
        default => 'alert-info',
    };

    $styleClass = $style ? "alert-{$style}" : null;

    $iconPath = match ($type) {
        'success' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'warning' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        'error' => 'M6 18L18 6M6 6l12 12',
        default => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    };
@endphp

<div role="alert" {{ $attributes->class(['alert', $typeClass, $styleClass, 'alert-vertical sm:alert-horizontal']) }}>
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-6 w-6 shrink-0 stroke-current">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}" />
    </svg>
    <div>
        @if ($title)
            <h3 class="font-bold">{{ $title }}</h3>
        @endif

        @if (trim($slot) !== '')
            <div @class(['text-xs' => $title])>{{ $slot }}</div>
        @endif

        @if (count($errorList) > 0)
            <ul class="list-inside list-disc text-xs">
                @foreach ($errorList as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
