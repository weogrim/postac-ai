@props([
    'heading',
    'subheading' => null,
])

<div class="mx-auto flex w-full max-w-md flex-col gap-6 px-4 py-12 sm:py-20">
    <div class="text-center">
        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ $heading }}</h1>
        @if ($subheading)
            <p class="mt-3 text-sm text-base-content/70">{{ $subheading }}</p>
        @endif
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body gap-4 p-6 sm:p-8">
            {{ $slot }}
        </div>
    </div>

    @if (isset($footer))
        <div class="text-center text-sm text-base-content/70">
            {{ $footer }}
        </div>
    @endif
</div>
