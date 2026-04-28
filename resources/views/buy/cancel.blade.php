@extends('layouts.app')

@section('title', 'Płatność anulowana')

@section('content')
    <section class="relative overflow-hidden py-16 sm:py-24">
        <div class="bg-blob"></div>
        <div class="container-app relative z-10 mx-auto max-w-xl">
            <div class="card-glass p-8 text-center">
                <div class="text-5xl">🛒</div>
                <h1 class="font-display text-2xl font-semibold mt-3">Płatność anulowana</h1>
                <p class="text-ink-dim mt-2">
                    Nic nie zostało pobrane. Możesz wrócić do wyboru pakietu albo kontynuować rozmowę w ramach darmowych limitów.
                </p>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('buy.index') }}" class="btn-glow">Zobacz pakiety</a>
                    <a href="{{ route('home') }}" class="btn btn-ghost">Strona główna</a>
                </div>
            </div>
        </div>
    </section>
@endsection
