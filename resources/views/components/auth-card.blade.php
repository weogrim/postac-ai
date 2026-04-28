@props([
    'heading',
    'subheading' => null,
])

<section class="relative overflow-hidden py-12 sm:py-20">
    <div class="bg-blob"></div>

    <div class="relative z-10 mx-auto flex w-full max-w-md flex-col gap-6 px-4">
        <div class="text-center">
            <h1 class="text-display-md">{{ $heading }}</h1>
            @if ($subheading)
                <p class="mt-3 text-sm text-ink-dim">{{ $subheading }}</p>
            @endif
        </div>

        <div class="card-glass p-6 sm:p-8 flex flex-col gap-4">
            {{ $slot }}
        </div>

        @if (isset($footer))
            <div class="text-center text-sm text-ink-dim">
                {{ $footer }}
            </div>
        @endif
    </div>
</section>
