@php
    $user = auth()->user();
@endphp

<nav class="nav-sticky">
    <div class="container-app flex items-center justify-between gap-4 py-3">
        <div class="flex items-center gap-3">
            <div class="dropdown md:hidden">
                <button tabindex="0" class="btn btn-circle btn-ghost btn-sm" aria-label="Menu">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 w-56 rounded-box border border-line bg-panel p-2 shadow-2xl">
                    <li><a href="{{ route('home') }}">Strona główna</a></li>
                    <li><a href="{{ route('character.index') }}">Postacie</a></li>
                    <li><a href="{{ route('dating.index') }}">Randki</a></li>
                    @registered
                        <li><a href="{{ route('chat.index') }}">Twoje czaty</a></li>
                        <li><a href="{{ route('buy.index') }}">Pakiety</a></li>
                        <li><a href="{{ route('profile.show') }}">Profil</a></li>
                    @endregistered
                </ul>
            </div>

            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-violet to-magenta font-display text-lg font-bold text-white shadow-[0_4px_16px_oklch(0.656_0.241_354/0.5)]">
                    p
                </span>
                <span class="font-display text-lg font-semibold tracking-tight">
                    postac<span class="text-ink-mute">.ai</span>
                </span>
            </a>
        </div>

        <div class="hidden md:flex items-center gap-1">
            <a href="{{ route('character.index') }}"
               class="px-3 py-2 text-sm rounded-full transition-colors hover:bg-panel-2 {{ request()->routeIs('character.*') ? 'text-ink' : 'text-ink-dim' }}">
                Postacie
            </a>
            <a href="{{ route('dating.index') }}"
               class="px-3 py-2 text-sm rounded-full transition-colors hover:bg-panel-2 {{ request()->routeIs('dating.*') ? 'text-ink' : 'text-ink-dim' }}">
                Randki
            </a>
            @registered
                <a href="{{ route('chat.index') }}"
                   class="px-3 py-2 text-sm rounded-full transition-colors hover:bg-panel-2 {{ request()->routeIs('chat.*') ? 'text-ink' : 'text-ink-dim' }}">
                    Twoje czaty
                </a>
                <a href="{{ route('buy.index') }}"
                   class="px-3 py-2 text-sm rounded-full transition-colors hover:bg-panel-2 {{ request()->routeIs('buy.*') ? 'text-ink' : 'text-ink-dim' }}">
                    Pakiety
                </a>
            @endregistered
        </div>

        <div class="flex items-center gap-2">
            @registered
                <div class="dropdown dropdown-end">
                    <button tabindex="0" class="btn btn-circle btn-ghost avatar avatar-placeholder" aria-label="Menu profilu">
                        <div class="w-10 rounded-full bg-gradient-to-br from-violet to-magenta text-white">
                            <span class="text-sm font-semibold">{{ mb_strtoupper(mb_substr($user->name ?? '?', 0, 2)) }}</span>
                        </div>
                    </button>
                    <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 w-56 rounded-box border border-line bg-panel p-2 shadow-2xl">
                        <li class="menu-title px-3 pt-2 pb-1">
                            <span class="truncate text-xs text-ink-mute">{{ $user->email }}</span>
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
            @else
                <a href="{{ route('login') }}" class="btn btn-ghost btn-sm hidden sm:inline-flex">Zaloguj</a>
                <a href="{{ route('register') }}" class="btn btn-primary btn-sm rounded-full px-5">
                    Zarejestruj <span class="hidden sm:inline">&rarr;</span>
                </a>
            @endregistered
        </div>
    </div>
</nav>
