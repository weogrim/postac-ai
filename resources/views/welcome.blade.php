@extends('layouts.app')

@section('content')
    <div class="hero min-h-[70vh] px-4">
        <div class="hero-content flex-col gap-8 text-center">
            <div class="flex flex-col gap-3">
                <h1 class="text-4xl font-bold tracking-tight sm:text-6xl">
                    Rozmawiaj z <span class="text-primary">postaciami</span>, które kochasz
                </h1>
                <p class="mx-auto max-w-xl text-base text-base-content/70 sm:text-lg">
                    Twórz postacie, wchodź w role, śmiej się i żyj historiami. Rozmowa zmienia wszystko.
                </p>
            </div>

            @guest
                <div class="flex flex-wrap justify-center gap-3">
                    <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Zacznij za darmo</a>
                    <a href="{{ route('login') }}" class="btn btn-ghost btn-lg">Mam już konto</a>
                </div>
            @endguest

            @auth
                <div class="flex flex-wrap justify-center gap-3">
                    <a href="{{ route('profile.show') }}" class="btn btn-primary btn-lg">Twój profil</a>
                </div>
            @endauth
        </div>
    </div>
@endsection
