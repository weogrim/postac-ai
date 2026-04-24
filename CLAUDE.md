# CLAUDE.md — postac.ai (new)

Wskazówki dla Claude Code przy pracy w tym folderze.

## Projekt

`postac.ai` — polski klon characters.ai (czat z postaciami AI). Ten folder to **aktywny rebuild** pod Laravel 13 / PHP 8.5 / Postgres + pgvector / Filament 5 / `laravel/ai`. Poprzednia wersja żyje w `../legacy/` (Laravel 10, Filament 3, MySQL) i jest **read-only**: służy wyłącznie jako referencja co przenieść i jak wyglądały niuanse biznesowe.

Cały plan migracji + konkretne odejścia od legacy znajdują się w `/home/darek/.claude/plans/breezy-moseying-wave.md`. To jest źródło prawdy — przy każdej fazie wracaj do niego.

**Zasada refaktoru**: nie portujemy 1:1. Każdy legacy wzorzec poddajemy pod wątpliwość, przed portem robimy krótki reality check i proponujemy modernizację (zobacz memory: `feedback_challenge_decisions.md`).

## Stack

- **Laravel** 13, **PHP** `^8.4` w `composer.json` (kontener ma PHP 8.5 → property hooks, asymmetric visibility dozwolone).
- **Postgres 16** + **pgvector** (nie MySQL — korzystamy z natywnych ENUMów, JSONB, CHECK constraints, ULIDów).
- **Redis** — sessions / cache / queue.
- **Filament** 5 (Schema API, nie Form/Table z 3-ki).
- **`laravel/ai`** (namespace `Laravel\Ai\`) zamiast `openai-php/laravel`.
- **Spatie Permission + Filament Shield** — role `admin` / `super_admin`, zero hardcoded emaili.
- **Spatie Settings** — typowane klasy ustawień (`App\Settings\ChatSettings`), nie DB key/value z JSON-em.
- **Cashier** — billing, pakiety jako backed enum `App\Billing\Package` + `config/billing.php` tylko dla price IDs z `.env`.
- **HTMX 4 beta** (`htmx.org@4.0.0-beta2`, docs: https://four.htmx.org) + **Blade** + **SSE** — żadnego Livewire/SPA; chat streamuje przez Server-Sent Events. **Świadoma decyzja** wziąć v4 beta mimo że jest świeża, żeby uniknąć wiązania się ze starszym API.
- **DaisyUI 5.5.19** + **Tailwind 4.2.4** (via `@tailwindcss/vite`). **Vite 8.0.10** + **laravel-vite-plugin 3.0.1**.
- **Mailpit** (SMTP lokalny), **Adminer** (UI bazy).

## Komendy

**NIE używamy Sail**. Wszystko idzie przez `docker exec` do kontenera `new-app-1` pod userem `dev` (UID 1000, żeby pliki na zamontowanym volumenie miały właściwe uprawnienia).

```bash
# Artisan / composer / npm
docker exec -u dev new-app-1 php artisan migrate
docker exec -u dev new-app-1 composer require <paczka>
docker exec -u dev new-app-1 npm run dev

# Testy / statyka / lint
docker exec -u dev new-app-1 php artisan test
docker exec -u dev new-app-1 vendor/bin/phpstan analyse --memory-limit=512M
docker exec -u dev new-app-1 vendor/bin/pint --test

# Baza testowa: jednorazowo (testy używają .env.testing → postacai_testing)
docker exec new-postgres-1 psql -U postacai -d postgres -c "CREATE DATABASE postacai_testing OWNER postacai;"

# Stripe CLI (webhook lokalnie) — uruchamiane na hoście, nie w kontenerze
stripe listen --forward-to localhost:8080/stripe/webhook

# Diagnostyka
docker ps
docker logs new-app-1 --tail 50
docker exec new-app-1 supervisorctl status
```

**Porty hosta**: app `8080`, Postgres `5432`, Redis `6379`, Mailpit SMTP `1025` / UI `8025`, Adminer `8081`.

**Rebuild obrazu** potrzebny gdy: zmienia się `Dockerfile`/`Dockerfile.dev`, dodawane rozszerzenie PHP, zmienia się `supervisord.conf`. Wtedy: `docker compose up -d --build`.

## Konwencje

- **Polski** w user-facing stringach (nazwach pakietów, komunikatach błędów dla użytkownika, widokach). Kod, komentarze (minimalne), nazwy klas po angielsku.
- **Kwestionuj legacy.** Przed portem pliku z `../legacy/` wypisz w głowie: co jest archaiczne, co hardcoded, co da się typowo/enumowo/modernie. Dopiero potem pisz nowy kod.
- **Sprawdzaj aktualną dokumentację paczek** (WebFetch / repo) — `laravel/ai` jest pre-1.0 i API może się zmieniać, Filament 5 jest świeżo po wydaniu.
- **Unikaj niepotrzebnych komentarzy** w kodzie — nazwy robią robotę. Komentarz tylko gdy ukryty constraint / workaround.
- **Testy Pest** (nie PHPUnit). Factories obowiązkowo dla każdego modelu.
- **PHP 8.4/8.5 features** (property hooks, asymmetric visibility, readonly classes) — tam gdzie czynią kod czystszym, nie na siłę.
- **Nie commituj sekretów** (STRIPE_*, OPENAI_*, SENTRY_*). `.env` zostaje lokalnie, `.env.example` trzyma strukturę.

## Architektura (rośnie z implementacją)

### Frontend / HTMX 4 pattern (Faza 2)

HTMX 4 jest **beta** i ma **inne API niż v2** — modele językowe były trenowane głównie na v1/v2, więc **nie wierz intuicji ani przykładom z head'em z pamięci**. Jeśli piszesz `hx-*` i nie jesteś pewien — zerknij na https://four.htmx.org/docs/get-started/migration.

**Kluczowe różnice v2 → v4 które psują intuicję:**

- **Explicit inheritance**: atrybuty nie spływają w dół. Żeby `hx-boost`, `hx-target`, `hx-swap` działały na dzieciach — musi być `:inherited` suffix (np. `hx-boost:inherited="true"`). W `resources/views/layouts/app.blade.php` `<body>` ma właśnie taki zestaw.
- **4xx/5xx swapują domyślnie** (tylko 204/304 nie). Serwer przy błędzie MUSI zwracać użyteczny HTML fragment, nie sam status.
- **`hx-disable` → `hx-ignore`** (do wyłączenia HTMX processing). Stare `hx-disabled-elt` → nowe `hx-disable` (szarzenie/blokowanie podczas requestu). **UWAGA na kolejność rename'ów przy migracji.**
- **`hx-delete` nie wysyła form data**. Dodaj jawnie `hx-include="closest form"`.
- **Nowe nazwy eventów**: `htmx:before:request`, `htmx:after:swap`, `htmx:error` (wszystkie błędy skonsolidowane). Stare `htmx:beforeRequest` itd. nie działają.
- **Config keys**: `defaultSwap` (nie `defaultSwapStyle`), `defaultTimeout` (nie `timeout`, default 60s), `transitions` (nie `globalViewTransitions`).
- **`fetch()` zamiast XHR** — natywny streaming dla Server-Sent Events (wykorzystamy w Fazie 5).
- **Brak history cache w localStorage** — back button robi pełny refetch.
- **OOB swap order**: main swap najpierw, potem OOB (odwrotnie niż w v2).

**Wzorzec postac.ai:**

- **Zero macros** na Request/Response. Sprawdzenie HTMX w kontrolerze inline: `$request->header('HX-Request') === 'true'`. Redirect HTMX: `response()->noContent()->header('HX-Location', $url)`.
- **Walidacja** przechodzi przez globalny renderer w `bootstrap/app.php`. Jeśli `HX-Request`, zwraca status 422 + OOB toast + header `HX-Reswap: none` (czyli tylko toast się przypnie, main target nie jest podmieniany). Non-HTMX → Laravel default (redirect-back + session errors). **Żaden FormRequest nie potrzebuje boilerplate'u.**
- **Alerty i toasty** przez Blade components:
  - `<x-alert type="info|success|warning|error" style="soft|outline|dash" title="..." :error-list="$errors">Body</x-alert>` — single component, wariantowany, SVG wbudowane.
  - `<x-toast target="toasts">...</x-toast>` — opakowanie OOB na kontener `#toasts` w layoucie (`hx-swap-oob="beforeend"`), stackuje toasty.
- **Layout** (`layouts/app.blade.php`) ma jeden kontener `#toasts` (DaisyUI `toast toast-top toast-end`) na który lecą OOB swapy z błędami/powiadomieniami.

**Config HTMX** w `resources/js/app.js`:

```js
import htmx from 'htmx.org';
window.htmx = htmx;
htmx.config.transitions = true;  // View Transitions API
```

**DaisyUI 5** przez Tailwind 4 `@plugin`:

```css
@import 'tailwindcss';
@plugin 'daisyui/index.js' {
    themes: light --default, dark --prefersdark;
}
```

**Uwaga Rolldown (Vite 8)**: `@plugin 'daisyui'` (bare) zawodzi bo Rolldown resolvuje przez `browser` field z `package.json` DaisyUI (wskazuje na `.css` plik → Node ESM loader się wywala). Fix: **jawny path `daisyui/index.js`**. Tego nie zmieniaj bez testu build'a.

### Auth + profil (Faza 3)

- **Email+password działa real** (nie jak w legacy gdzie formularz był `disabled`). Rejestracja → `Registered` event → autologin → `verification.notice` screen. Email verification **obowiązkowa** (Mailpit lokalnie łapie wszystko), zasłania routy `/me*` przez middleware `verified`.
- **Google OAuth** jako jedyny social provider, ale przez **enum** `App\Auth\SocialProvider` z `->scopes()` / `->label()`. Route `/auth/{provider}` używa Laravelowego implicit enum binding — **404 automatycznie** jeśli provider nie match (czyli nie `in_array(config(...))` jak w legacy).
- **Socialite callback**: firstOrCreate po emailu **bez nadpisywania hasła** (legacy nadpisywał placeholderem). Istniejący user z hasłem logowany bez zmian; nowy user tworzony z `password = null`, `email_verified_at = now()`. Nazwa generowana z `Socialite->getName() || Str::before($email, '@')`, deduplikacja przez losowy suffix `Str::random(4)`.
- **Rate limit loginu**: `LoginRequest::authenticate()` używa `RateLimiter::hit/clear` na kluczu `lower(email)|ip` — 5 prób/min, potem `Lockout` event. Nie `RateLimiter::for('login')` (middleware-style) — tu nie mamy per-route throttle, a FormRequest zamyka logikę w jednym miejscu.
- **Account delete**: hard delete z potwierdzeniem tekstowym `confirm=USUŃ` (działa dla OAuth i email userów, bez password). Cascade na characters/chats/messages przez soft delete relacji (nie w tej fazie — Faza 4/5 dopinie FK). User bez soft delete — dane giną twardo dla prywatności.
- **Kontrolery split by concern**: `Auth\{Register,Login,PasswordResetLink,NewPassword,EmailVerificationNotice,VerifyEmail,EmailVerificationResend,SocialAuth}Controller` + `ProfileController` + `PasswordController`. Form Requests per kontekst (nie inline `$request->validate()`).
- **Auth views** używają `<x-auth-card>` (hero + card + optional footer slot), `<x-form-input>` (DaisyUI 5 `fieldset`+`input-error`+hint), `<x-navbar>` (sm: horizontal menu + dropdown profilu, <sm: hamburger drawer). **Mobile-first domyślnie** — DaisyUI `card-body p-6 sm:p-8`, `text-3xl sm:text-4xl`, navbar start/center/end.
- **Logout**: `hx-boost="false"` na form — POST nie jest boostowany przez HTMX, żeby nie było konfliktu CSRF/session cycle.
- **Konwencje testowe**: `/** @var TestCase $this */` docblock (Pest closure rebind), `uses(RefreshDatabase::class)` top of file, `Mockery` dla Socialite, `Notification::fake()` + `assertSentTo($user, ResetPassword::class, fn (ResetPassword $n) => ...)` dla wyciągnięcia tokenu resetu.

### Dalsze sekcje (uzupełniamy z kolejnymi fazami)

Chat flow, limit resolution, billing, Filament admin — dopiszemy gdy powstaną.

## Pliki pod specjalnym nadzorem

- `composer.json` — zmiany paczek robi się przez `composer require` w kontenerze, nie ręcznym edytowaniem.
- `package.json` — `npm i` w kontenerze; uwaga na Vite 8 / DaisyUI 5 (fix: `@plugin 'daisyui/index.js'`).
- `.env` / `.env.example` — trzymaj strukturę obu w synchronizacji.
- `bootstrap/app.php` — m.in. globalny HTMX `ValidationException` renderer.
- `resources/views/layouts/app.blade.php` — `hx-boost:inherited` + kontener `#toasts`.
- `resources/views/components/{alert,toast}.blade.php` — wariantowane DaisyUI komponenty.
- `resources/css/app.css` / `resources/js/app.js` — Tailwind 4 + DaisyUI 5 + HTMX 4 wire-up.
- `app/Providers/AppServiceProvider.php` — rejestracja macro, event listenery, binding.
- `routes/{web,console}.php` — routing + schedule (Laravel 13: brak `Kernel.php`). Routy auth są w grupach `guest`/`auth`/`verified`.
- `app/Auth/SocialProvider.php` — enum OAuth providerów (dziś tylko Google). Nowy provider = nowy case + entry w `config/services.php`.
- `app/Http/Controllers/Auth/*` — split by concern. Nie dodawaj tam helpera "AuthController" robiącego wszystko.
- `app/Http/Requests/{Auth,Profile}/*` — walidacja, nie w kontrolerach.
- `resources/views/components/{navbar,auth-card,form-input}.blade.php` — komponenty UI. Mobile-first, DaisyUI 5.
- `Docker/{Dockerfile,nginx_default.conf,supervisord.conf}` — infra, zmiana wymaga rebuildu.

## Co może się zmienić

- **Docker server**: w Fazie 5 (streaming) możliwe przejście z nginx+fpm na **FrankenPHP** przez `laravel/octane`, jeśli spike pokaże że nginx buforuje SSE mimo `fastcgi_buffering off`. Dokumentacja w planie refaktoru.
- Root `../CLAUDE.md` ma stare wzmianki o Sail — nieaktualne. Zostaw dopóki user sam nie usunie, ale nie polegaj na nim.
