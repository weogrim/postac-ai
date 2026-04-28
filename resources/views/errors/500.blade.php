@extends('layouts.app', ['title' => '500 — coś poszło nie tak'])

@section('content')
    <section class="relative overflow-hidden py-16 sm:py-24">
        <div class="bg-blob"></div>

        <div class="container-app relative z-10 mx-auto max-w-xl text-center">
            <div class="text-display-xl text-gradient-brand">500</div>
            <h1 class="font-display text-2xl font-semibold mt-4">Coś poszło nie tak</h1>
            <p class="text-ink-dim mt-3">
                Wewnętrzny błąd serwera. Już o tym wiemy i pracujemy nad naprawą.
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ url('/') }}" class="btn-glow">Wróć na start</a>
            </div>
        </div>
    </section>
@endsection
