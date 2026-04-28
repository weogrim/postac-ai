@extends('layouts.app', ['title' => 'Przerwa techniczna'])

@section('content')
    <section class="relative overflow-hidden py-16 sm:py-24">
        <div class="bg-blob"></div>

        <div class="container-app relative z-10 mx-auto max-w-xl text-center">
            <div class="text-display-xl text-gradient-brand">503</div>
            <h1 class="font-display text-2xl font-semibold mt-4">Przerwa techniczna</h1>
            <p class="text-ink-dim mt-3">
                Konserwacja w toku. Wracamy za chwilę — odśwież za parę minut.
            </p>
        </div>
    </section>
@endsection
