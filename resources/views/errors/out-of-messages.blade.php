@extends('layouts.app')

@section('content')
    <section class="relative overflow-hidden py-16">
        <div class="bg-blob"></div>

        <div class="container-app relative z-10 mx-auto max-w-md">
            <div class="card-glass p-8 text-center">
                <div class="text-5xl">⏳</div>
                <h1 class="font-display text-2xl font-semibold mt-4">Limit wiadomości wyczerpany</h1>
                <p class="text-ink-dim mt-3">{{ $message }}</p>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('buy.index') }}" class="btn-glow">Zobacz pakiety</a>
                    <a href="{{ route('home') }}" class="btn btn-ghost">Strona główna</a>
                </div>
            </div>
        </div>
    </section>
@endsection
