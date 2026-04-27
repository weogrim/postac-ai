@props(['open' => false])

<dialog id="register-gate" class="modal {{ $open ? 'modal-open' : '' }}" @if ($open) open @endif>
    <div class="modal-box max-w-md text-center">
        <div class="mb-3 text-4xl">👋</div>
        <h3 class="text-xl font-semibold">Limit gościa wyczerpany</h3>
        <p class="mt-2 text-sm text-base-content/70">
            Założ konto, żeby kontynuować rozmowę. Wszystkie Twoje czaty zostaną zachowane.
        </p>
        <div class="mt-6 flex flex-col gap-2">
            <a href="{{ route('register') }}" class="btn btn-primary" hx-boost="false">Zarejestruj się</a>
            <a href="{{ route('login') }}" class="btn btn-ghost btn-sm" hx-boost="false">Mam już konto</a>
        </div>
    </div>
</dialog>
