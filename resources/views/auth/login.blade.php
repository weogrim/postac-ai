@extends('layouts.app', ['title' => 'Zaloguj się — postac.ai'])

@section('content')
    <x-auth-card
        heading="Z powrotem do rozmów"
        subheading="Zaloguj się i kontynuuj historię."
    >
        <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-4">
            @csrf

            <x-form-input
                name="email"
                label="Email"
                type="email"
                autocomplete="email"
                required
                autofocus
            />

            <x-form-input
                name="password"
                label="Hasło"
                type="password"
                autocomplete="current-password"
                required
            />

            <div class="flex items-center justify-between text-sm">
                <label class="label cursor-pointer gap-2 p-0">
                    <input type="checkbox" name="remember" class="checkbox checkbox-sm" />
                    <span class="label-text">Zapamiętaj mnie</span>
                </label>
                <a href="{{ route('password.request') }}" class="link link-hover text-base-content/70">Zapomniałeś hasła?</a>
            </div>

            <button type="submit" class="btn btn-primary mt-2 w-full">Zaloguj się</button>

            <div class="divider text-xs text-base-content/60">LUB</div>

            <a
                href="{{ route('auth.social', 'google') }}"
                hx-boost="false"
                class="btn btn-outline w-full"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 48 48" aria-hidden="true">
                    <path fill="#FFC107" d="M43.6 20.1H42V20H24v8h11.3c-1.6 4.7-6.1 8-11.3 8-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34 6.2 29.3 4.3 24 4.3 12.9 4.3 4 13.2 4 24s8.9 19.7 20 19.7 20-8.9 20-19.7c0-1.3-.1-2.6-.4-3.9z"/>
                    <path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.6 15.1 18.9 12 24 12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34 6.2 29.3 4.3 24 4.3 16.3 4.3 9.7 8.4 6.3 14.7z"/>
                    <path fill="#4CAF50" d="M24 43.7c5.2 0 9.9-1.8 13.5-4.8l-6.2-5.2c-2 1.4-4.5 2.3-7.3 2.3-5.2 0-9.6-3.3-11.2-7.9l-6.5 5C9.6 39.7 16.2 43.7 24 43.7z"/>
                    <path fill="#1976D2" d="M43.6 20.1H42V20H24v8h11.3c-.8 2.3-2.3 4.3-4.3 5.7l6.2 5.2c-.4.4 6.8-4.9 6.8-14.9 0-1.3-.1-2.6-.4-3.9z"/>
                </svg>
                Zaloguj przez Google
            </a>
        </form>

        <x-slot name="footer">
            Nie masz konta?
            <a href="{{ route('register') }}" class="link link-primary">Zarejestruj się</a>
        </x-slot>
    </x-auth-card>
@endsection
