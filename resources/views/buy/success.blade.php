@extends('layouts.app')

@section('title', 'Dziękujemy za zakup')

@section('content')
    <section class="relative overflow-hidden py-16 sm:py-24">
        <div class="bg-blob"></div>
        <div class="container-app relative z-10 mx-auto max-w-xl">
            <div class="card-glass p-8 text-center">
                <div class="text-5xl">✅</div>
                <h1 class="font-display text-2xl font-semibold mt-3">Dziękujemy za zakup!</h1>
                <p class="text-ink-dim mt-2">
                    Płatność się udała. Twoje wiadomości pojawią się w ciągu kilku sekund —
                    odśwież panel limitów jeśli jeszcze ich nie widzisz.
                </p>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('chat.index') }}" class="btn-glow">Wróć do czatu</a>
                    <a href="{{ route('profile.limits') }}" class="btn btn-ghost">Moje limity</a>
                </div>
            </div>
        </div>
    </section>
@endsection
