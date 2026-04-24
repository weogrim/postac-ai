@php
    $user = auth()->user();
@endphp

<div class="navbar sticky top-0 z-40 bg-base-100/90 px-4 shadow-sm backdrop-blur sm:px-6">
    <div class="navbar-start">
        <div class="dropdown sm:hidden">
            <button tabindex="0" class="btn btn-circle btn-ghost" aria-label="Menu">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 w-52 rounded-box bg-base-100 p-2 shadow-lg">
                <li><a href="{{ route('home') }}">Strona główna</a></li>
                @auth
                    <li><a href="{{ route('buy.index') }}">Pakiety</a></li>
                    <li><a href="{{ route('profile.show') }}">Profil</a></li>
                    <li><a href="{{ route('profile.limits') }}">Moje limity</a></li>
                @endauth
            </ul>
        </div>

        <a href="{{ route('home') }}" class="btn btn-ghost px-2 text-xl font-bold tracking-tight">
            <span class="text-primary">postac</span><span class="opacity-60">.ai</span>
        </a>
    </div>

    <div class="navbar-center hidden sm:flex">
        <ul class="menu menu-horizontal gap-1 px-1">
            <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Postacie</a></li>
            @auth
                <li><a href="{{ route('buy.index') }}" class="{{ request()->routeIs('buy.*') ? 'active' : '' }}">Pakiety</a></li>
            @endauth
        </ul>
    </div>

    <div class="navbar-end gap-2">
        @guest
            <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Zaloguj</a>
            <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Zarejestruj</a>
        @endguest

        @auth
            <div class="dropdown dropdown-end">
                <button tabindex="0" class="btn btn-circle btn-ghost avatar avatar-placeholder" aria-label="Menu profilu">
                    <div class="w-10 rounded-full bg-primary text-primary-content">
                        <span class="text-sm font-semibold">{{ mb_strtoupper(mb_substr($user->name ?? '?', 0, 2)) }}</span>
                    </div>
                </button>
                <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 w-56 rounded-box bg-base-100 p-2 shadow-lg">
                    <li class="menu-title px-3 pt-2 pb-1">
                        <span class="truncate text-xs">{{ $user->email }}</span>
                    </li>
                    <li><a href="{{ route('profile.show') }}">Profil</a></li>
                    <li><a href="{{ route('profile.limits') }}">Moje limity</a></li>
                    <li><a href="{{ route('buy.index') }}">Kup wiadomości</a></li>
                    @if ($user?->hasStripeId())
                        <li><a href="{{ route('billing.portal') }}">Faktury i płatności</a></li>
                    @endif
                    <li>
                        <form method="POST" action="{{ route('logout') }}" hx-boost="false">
                            @csrf
                            <button type="submit" class="w-full justify-start">Wyloguj</button>
                        </form>
                    </li>
                </ul>
            </div>
        @endauth
    </div>
</div>
