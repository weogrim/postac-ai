@extends('layouts.app')

@section('title', 'Dziękujemy za zakup')

@section('content')
    <div class="mx-auto max-w-xl px-4 py-16 sm:px-6 sm:py-24">
        <div class="card bg-base-200">
            <div class="card-body items-center text-center">
                <div class="text-5xl">✅</div>
                <h1 class="card-title mt-2 text-2xl">Dziękujemy za zakup!</h1>
                <p class="opacity-70">
                    Płatność się udała. Twoje wiadomości pojawią się w ciągu kilku sekund —
                    odśwież panel limitów jeśli jeszcze ich nie widzisz.
                </p>
                <div class="card-actions mt-4">
                    <a href="{{ route('chat.index') }}" class="btn btn-primary">Wróć do czatu</a>
                    <a href="{{ route('profile.limits') }}" class="btn btn-ghost">Moje limity</a>
                </div>
            </div>
        </div>
    </div>
@endsection
