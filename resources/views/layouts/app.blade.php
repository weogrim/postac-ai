<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if ($sentryDsn = config('sentry.dsn'))
        <meta name="sentry-dsn" content="{{ $sentryDsn }}">
        <meta name="sentry-environment" content="{{ config('sentry.environment') ?? app()->environment() }}">
        @if ($release = config('sentry.release'))
            <meta name="sentry-release" content="{{ $release }}">
        @endif
    @endif

    <title>{{ $title ?? config('app.name', 'postac.ai') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="flex min-h-screen flex-col text-base-content antialiased"
    hx-boost:inherited="true"
    hx-target:inherited="body"
    hx-swap:inherited="innerHTML transition:true"
>
    <x-navbar />

    <main class="flex flex-1 flex-col min-h-0">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    @hasSection('footer')
        @yield('footer')
    @else
        <footer class="border-t border-line py-8 mt-auto">
            <div class="container-app text-center text-sm text-ink-mute">
                <p>&copy; {{ date('Y') }} postac.ai — rozmawiaj z postaciami, które kochasz.</p>
                <div class="mt-3 flex flex-wrap justify-center gap-4 text-xs">
                    <a href="{{ route('legal.show', 'terms') }}" class="hover:text-ink-dim transition-colors">Regulamin</a>
                    <a href="{{ route('legal.show', 'privacy') }}" class="hover:text-ink-dim transition-colors">Prywatność</a>
                    <a href="{{ route('legal.show', 'dating-terms') }}" class="hover:text-ink-dim transition-colors">Regulamin Randki</a>
                </div>
            </div>
        </footer>
    @endif

    @if (session('status'))
        <x-toast>
            <x-alert type="success" style="soft" title="{{ session('status') }}" />
        </x-toast>
    @endif

    <div id="toasts" class="toast toast-top toast-end z-50"></div>
</body>
</html>
