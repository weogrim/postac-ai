@extends('layouts.app')

@section('title', 'Płatność anulowana')

@section('content')
    <div class="mx-auto max-w-xl px-4 py-16 sm:px-6 sm:py-24">
        <div class="card bg-base-200">
            <div class="card-body items-center text-center">
                <div class="text-5xl">🛒</div>
                <h1 class="card-title mt-2 text-2xl">Płatność anulowana</h1>
                <p class="opacity-70">
                    Nic nie zostało pobrane. Możesz wrócić do wyboru pakietu albo kontynuować rozmowę w ramach darmowych limitów.
                </p>
                <div class="card-actions mt-4">
                    <a href="{{ route('buy.index') }}" class="btn btn-primary">Zobacz pakiety</a>
                    <a href="{{ route('home') }}" class="btn btn-ghost">Strona główna</a>
                </div>
            </div>
        </div>
    </div>
@endsection
