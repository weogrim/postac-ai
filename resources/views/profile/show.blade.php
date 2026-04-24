@extends('layouts.app', ['title' => 'Profil — postac.ai'])

@section('content')
    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 px-4 py-8 sm:py-12">
        <header class="flex flex-col gap-2">
            <h1 class="text-3xl font-bold tracking-tight">Twój profil</h1>
            <p class="text-sm text-base-content/70">Zarządzaj kontem, hasłem i ustawieniami.</p>
        </header>

        <section class="card bg-base-100 shadow">
            <div class="card-body gap-4 p-6 sm:p-8">
                <h2 class="card-title">Dane konta</h2>
                <form method="POST" action="{{ route('profile.update') }}" class="flex flex-col gap-4">
                    @csrf
                    @method('PATCH')

                    <x-form-input
                        name="name"
                        label="Nazwa użytkownika"
                        :value="$user->name"
                        autocomplete="username"
                        required
                    />

                    <x-form-input
                        name="email"
                        label="Email"
                        type="email"
                        :value="$user->email"
                        autocomplete="email"
                        required
                        hint="Zmiana emaila wymusi ponowną weryfikację."
                    />

                    <div class="card-actions justify-end">
                        <button type="submit" class="btn btn-primary">Zapisz zmiany</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="card bg-base-100 shadow">
            <div class="card-body gap-4 p-6 sm:p-8">
                <h2 class="card-title">
                    Hasło
                    @if ($user->password === null)
                        <span class="badge badge-info badge-sm">Konto Google</span>
                    @endif
                </h2>
                <p class="text-sm text-base-content/70">
                    @if ($user->password === null)
                        Ustaw hasło, żeby móc się logować również mailem.
                    @else
                        Zmień hasło. Będziesz musiał/a podać obecne.
                    @endif
                </p>

                <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-4">
                    @csrf
                    @method('PATCH')

                    @if ($user->password !== null)
                        <x-form-input
                            name="current_password"
                            label="Obecne hasło"
                            type="password"
                            autocomplete="current-password"
                            required
                        />
                    @endif

                    <x-form-input
                        name="password"
                        label="Nowe hasło"
                        type="password"
                        autocomplete="new-password"
                        required
                        hint="Min. 8 znaków."
                    />

                    <x-form-input
                        name="password_confirmation"
                        label="Powtórz nowe hasło"
                        type="password"
                        autocomplete="new-password"
                        required
                    />

                    <div class="card-actions justify-end">
                        <button type="submit" class="btn btn-primary">Zapisz hasło</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="card border border-error/30 bg-base-100 shadow">
            <div class="card-body gap-4 p-6 sm:p-8">
                <h2 class="card-title text-error">Usuń konto</h2>
                <p class="text-sm text-base-content/70">
                    Ta operacja jest nieodwracalna. Usuniemy wszystkie Twoje postacie, czaty i wiadomości.
                    Wpisz <span class="font-mono font-semibold">USUŃ</span>, żeby potwierdzić.
                </p>

                <form method="POST" action="{{ route('profile.destroy') }}" class="flex flex-col gap-4" hx-boost="false">
                    @csrf
                    @method('DELETE')

                    <x-form-input
                        name="confirm"
                        label="Potwierdzenie"
                        autocomplete="off"
                        required
                        hint="Wpisz dokładnie: USUŃ"
                    />

                    <div class="card-actions justify-end">
                        <button type="submit" class="btn btn-error">Usuń konto na zawsze</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection
