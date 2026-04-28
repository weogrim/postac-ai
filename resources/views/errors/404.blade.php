@extends('layouts.app', ['title' => '404 — strona nie istnieje'])

@section('content')
    <section class="relative overflow-hidden py-16 sm:py-24">
        <div class="bg-blob"></div>

        <div class="container-app relative z-10 mx-auto max-w-xl text-center">
            <div class="text-display-xl text-gradient-brand">404</div>
            <h1 class="font-display text-2xl font-semibold mt-4">Strona nie istnieje</h1>
            <p class="text-ink-dim mt-3">
                Adres, który podajesz, prowadzi w pustkę. Może postać została usunięta albo link się posypał.
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('home') }}" class="btn-glow">Wróć na start</a>
                <a href="{{ route('character.index') }}" class="btn btn-ghost">Zobacz postacie</a>
            </div>
        </div>
    </section>
@endsection
