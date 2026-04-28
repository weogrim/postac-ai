@extends('layouts.app', ['title' => 'Profil — postac.ai'])

@section('content')
    <section class="relative overflow-hidden py-12">
        <div class="bg-blob"></div>

        <div class="container-app relative z-10 mx-auto flex w-full max-w-3xl flex-col gap-8">
            <header class="flex flex-col gap-2">
                <h1 class="text-display-md">Twój profil</h1>
                <p class="text-sm text-ink-dim">Zarządzaj kontem, hasłem i ustawieniami.</p>
            </header>

            <section class="card-glass p-6 sm:p-8">
                <h2 class="font-display text-xl font-semibold mb-4">Dane konta</h2>
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

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary rounded-full px-6">Zapisz zmiany</button>
                    </div>
                </form>
            </section>

            <section class="card-glass p-6 sm:p-8">
                <h2 class="font-display text-xl font-semibold mb-2 flex items-center gap-2">
                    Hasło
                    @if ($user->password === null)
                        <span class="badge badge-info badge-sm">Konto Google</span>
                    @endif
                </h2>
                <p class="text-sm text-ink-dim mb-4">
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

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary rounded-full px-6">Zapisz hasło</button>
                    </div>
                </form>
            </section>

            <section class="card-glass p-6 sm:p-8 border-error/30">
                <h2 class="font-display text-xl font-semibold text-error mb-2">Usuń konto</h2>
                <p class="text-sm text-ink-dim mb-4">
                    Ta operacja jest nieodwracalna. Usuniemy wszystkie Twoje postacie, czaty i wiadomości.
                    Wpisz <span class="font-mono font-semibold text-ink">USUŃ</span>, żeby potwierdzić.
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

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-error rounded-full px-6">Usuń konto na zawsze</button>
                    </div>
                </form>
            </section>
        </div>
    </section>
@endsection
