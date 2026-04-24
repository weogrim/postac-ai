# CLAUDE.md — postac.ai

`postac.ai` — polski klon characters.ai (czat z postaciami AI). Laravel 13 / PHP 8.5 / Postgres 16 + pgvector / Filament 5 / `laravel/ai` / FrankenPHP + Octane / HTMX 4 + DaisyUI 5.

**Zasada**: kwestionuj decyzje, sprawdzaj aktualną dokumentację paczek (`laravel/ai` pre-1.0, Filament 5 świeży), nie wciskaj abstrakcji których nie potrzebujesz (zobacz memory: `feedback_challenge_decisions.md`).

## Stack

- **Laravel** 13, **PHP** 8.5 w kontenerze (composer `^8.4`; property hooks i asymmetric visibility OK).
- **Postgres 16** + **pgvector** — natywne ENUMy, JSONB, CHECK constraints, ULIDy.
- **Redis** — sessions / cache / queue.
- **FrankenPHP + Octane** worker mode (zero nginx/php-fpm).
- **Filament 5** (Schema API).
- **`laravel/ai`** (namespace `Laravel\Ai\`) zamiast `openai-php/laravel`.
- **Spatie Permission + Filament Shield** — rola `super_admin`, zero hardcoded emaili.
- **Spatie Settings** — typowane klasy (np. `App\Settings\ChatSettings`).
- **Cashier** + backed enum `App\Billing\Package` + price IDs przez `.env`.
- **HTMX 4 beta** (`htmx.org@4.0.0-beta2`, https://four.htmx.org) + **Blade** + **SSE**.
- **DaisyUI 5.5.19** + **Tailwind 4.2.4** + **Vite 8.0.10**.
- **Mailpit** (SMTP), **Adminer** (DB UI).

## Komendy

NIE używamy Sail. Wszystko przez `docker exec` do `postac-ai-app-1` jako user `dev` (UID 1000, żeby pliki na zamontowanym volumenie miały dobre uprawnienia).

```bash
# Artisan / composer / npm
docker exec -u dev postac-ai-app-1 php artisan migrate
docker exec -u dev postac-ai-app-1 composer require <paczka>
docker exec -u dev postac-ai-app-1 npm run dev

# Tinker (XDG_CONFIG_HOME workaround dla psysh w read-only /config)
docker exec -u dev -e XDG_CONFIG_HOME=/tmp postac-ai-app-1 php artisan tinker

# Po npm run build — ZAWSZE octane:reload, worker cache'uje Vite manifest
docker exec -u dev postac-ai-app-1 npm run build && docker exec -u dev postac-ai-app-1 php artisan octane:reload

# Po zmianach .env — octane:reload nie wystarczy (FrankenPHP cache env w master process)
docker restart postac-ai-app-1

# Testy / statyka / lint
docker exec -u dev postac-ai-app-1 php artisan test
docker exec -u dev postac-ai-app-1 vendor/bin/phpstan analyse --memory-limit=512M
docker exec -u dev postac-ai-app-1 vendor/bin/pint --test

# Baza testowa (raz; testy używają .env.testing → postacai_testing)
docker exec postac-ai-postgres-1 psql -U postacai -d postgres -c "CREATE DATABASE postacai_testing OWNER postacai;"

# Stripe CLI (na hoście, nie w kontenerze)
stripe listen --forward-to localhost:43080/stripe/webhook

# Diagnostyka
docker logs postac-ai-app-1 --tail 50
docker exec postac-ai-app-1 supervisorctl -c /etc/supervisor/conf.d/supervisord.conf status
```

**Porty hosta** (43xxx): app `43080`, Postgres `43432`, Redis `43379`, Mailpit SMTP `43025` / UI `43825`, Adminer `43081`. W sieci Dockera procesy nasłuchują na oryginalnych: `postgres:5432`, `redis:6379`, `mailpit:1025` itd.

**Rebuild obrazu** (`docker compose up -d --build`) gdy zmienia się `Dockerfile`/`Dockerfile.dev`/`Docker/supervisord.conf` albo dodajesz rozszerzenie PHP.

## Konwencje

- **Polski** w user-facing stringach. Kod / nazwy klas po angielsku.
- **Pest** (nie PHPUnit). Factory dla każdego modelu. `/** @var TestCase $this */` docblock w testach (Pest closure rebind dla PHPStan).
- **PHP 8.4/8.5 features** (property hooks, asymmetric visibility, readonly classes) — tam gdzie czystsze, nie na siłę.
- **Minimalne komentarze** — nazwy robią robotę. Komentarz tylko dla ukrytego constraintu / workaroundu.
- **Bez sekretów w repo**: `.env` lokalne, `.env.example` synchronizujemy.

## Architektura

### HTMX 4

API różni się znacząco od v2/v3 — **nie ufaj intuicji z LLMa** (trening głównie na v1/v2). Reference: https://four.htmx.org/docs/get-started/migration.

Najczęstsze pułapki:

- **Inheritance jest explicit**: `hx-boost`, `hx-target`, `hx-swap` nie spływają w dół. Trzeba `:inherited` (np. `hx-boost:inherited="true"` na `<body>`).
- **4xx/5xx swapują domyślnie** (tylko 204/304 nie). Serwer przy błędzie MUSI zwracać użyteczny HTML fragment.
- **Eventy z dwukropkami**: `htmx:before:request`, `htmx:after:request`, `htmx:after:swap`, `htmx:finally:request`. `htmx:beforeRequest` itd. nie istnieje.
- **`htmx:after:request` fires PRZED swapem DOM**. Dla `querySelector` na nowo wstawionych elementach użyj `htmx:after:swap`.
- **`e.detail` to `{ ctx }`** (nie `{xhr, successful}` jak v2). Status: `e.detail?.ctx?.response?.status`. Pattern check: `if (status < 200 || status >= 300) return;`.
- **`hx-disable` → `hx-ignore`** (wyłączenie processing). Stare `hx-disabled-elt` → nowe `hx-disable` (blokowanie podczas requestu).
- **`hx-delete` nie wysyła form data** — dodaj `hx-include="closest form"`.
- **OOB swap order**: main najpierw, potem OOB.

**Wzorzec postac.ai**:

- Zero macros na Request/Response. W kontrolerze inline: `$request->header('HX-Request') === 'true'`. Redirect: `response()->noContent()->header('HX-Location', $url)`.
- Globalny renderer `ValidationException` w `bootstrap/app.php`: HTMX → 422 + OOB toast + `HX-Reswap: none`. Non-HTMX → Laravel default (redirect-back + session errors). FormRequesty zero boilerplatu.
- Komponenty: `<x-alert type="info|success|warning|error" style="soft|outline|dash" title="..." :error-list="$errors">`, `<x-toast>` (OOB do `#toasts` w layoutie z `hx-swap-oob="beforeend"`).

### DaisyUI 5 + Vite 8

```css
@import 'tailwindcss';
@plugin 'daisyui/index.js' { themes: light --default, dark --prefersdark; }
```

**Rolldown gotcha**: `@plugin 'daisyui'` (bare) wywala build, bo Rolldown resolvuje przez `browser` field paczki (wskazuje `.css` plik → Node ESM loader pada). Trzeba **jawny path `daisyui/index.js`**. Nie zmieniaj bez testu builda.

### Auth + profil

- Email+password działa real, **`MustVerifyEmail` obowiązkowe** (Mailpit lokalnie łapie wszystko). `verified` middleware na `/me*`.
- **Google OAuth** przez enum `App\Auth\SocialProvider`. Route `/auth/{provider}` używa Laravelowego implicit enum binding → 404 dla nieznanego providera automatycznie.
- **Socialite callback**: firstOrCreate po emailu, **bez nadpisywania hasła**. Istniejący user z hasłem loguje się bez zmian; nowy ma `password=null`, `email_verified_at=now()`. Nazwa: `Socialite->getName() || Str::before($email, '@')` + losowy suffix `Str::random(4)` przy konflikcie.
- **Rate limit loginu** w `LoginRequest::authenticate()` — `RateLimiter::hit/clear` na kluczu `lower(email)|ip`, 5/min, potem `Lockout` event. Nie middleware — FormRequest zamyka logikę w jednym miejscu.
- **Account delete**: hard delete z potwierdzeniem `confirm=USUŃ` (działa też dla OAuth userów bez password). User bez soft delete — dane giną twardo dla prywatności.
- **Kontrolery split by concern**: `Auth\{Register,Login,PasswordResetLink,NewPassword,EmailVerificationNotice,VerifyEmail,EmailVerificationResend,SocialAuth}Controller` + `ProfileController` + `PasswordController`. Form Requests, nie inline `$request->validate()`.
- Logout form ma `hx-boost="false"` — POST nie boostowany, żeby nie kolidował z CSRF/session cycle.

### Character / Chat / Media

- **`plank/laravel-mediable`** (nie Spatie). `Character implements MediableInterface + use Mediable`. URL przez helper `$character->avatarUrl('square')` z fallbackiem DiceBear.
- **Warianty w `AppServiceProvider::boot`**: `square` (512×512 WebP Q85), `thumb` (96×96 WebP Q80). Intervention v3 + GD driver (Imagick nie ma w obrazie). Optimizer wyłączony — WebP Q85 wystarcza.
- **Polimorficzny ID jako string** (NIE bigint) — User=int, Character=ULID. `config('mediable.ignore_migrations') => true` + ręczna edycja opublikowanej migracji na `string/string`.
- **`ChatController::store`**: `firstOrCreate(user_id+character_id)` race-safe dzięki **partial unique index** `WHERE deleted_at IS NULL`.
- **Authz inline**: `abort_unless($chat->user_id === auth()->id(), 404)`. Bez Policy dla user-facing — Policy dopiero w admin/Filament.
- **Cascade soft delete**: `Character::booted()` z `static::deleting` cascade na chats, `static::restoring` przywraca trashed. Guard `isForceDeleting()` — DB FK `cascadeOnDelete()` i tak hardusunie przy force delete.

### Streaming (FrankenPHP + SSE + laravel/ai)

- **Worker mode**: app boot raz, obsługuje wiele requestów. **Nie używaj statycznych properties** dla per-request state. Singleton z user-scoped data → listener w `config/octane.php` resetuje między requestami. `AppServiceProvider::boot` powinien być idempotent (`defineVariant` jest).
- **Streaming PHP**: `response()->stream(closure)` + `echo "data: ...\n\n"` + `if (ob_get_level() > 0) @ob_flush(); flush();`. **`ob_flush` bez aktywnego OB generuje notice** który gubi output — guard obowiązkowy. Headers: `Content-Type: text/event-stream`, `Cache-Control: no-cache`, `X-Accel-Buffering: no`.
- **`laravel/ai` 0.6.3 wzorzec**:

  ```php
  $agent = new AnonymousAgent(instructions: $character->prompt, messages: $history, tools: []);
  $response = $agent->stream(prompt: $latestUser, provider: Lab::OpenRouter, model: 'openai/gpt-4o-mini');
  foreach ($response as $event) {
      if ($event instanceof TextDelta)  { echo "data: ".json_encode(['delta' => $event->delta])."\n\n"; }
      if ($event instanceof StreamEnd)  { $tokens = $event->usage->completionTokens; }
  }
  ```

- **Empty assistant content w historii → OpenRouter rzuca 400**. Filtruj puste character messages przy budowaniu historii (puste = stream wcześniej padł, do nadpisania przy retry).
- **Chat SSE flow**: `POST /chat/{chat}/messages` (store) tworzy user + empty character w jednej transakcji, zwraca HTML z dwoma bubble'ami + header `X-Character-Message-Id`. `GET /chat/{chat}/messages/stream` zatwierdza ostatni pusty char msg, streamuje, zapisuje `content` + `tokens_usage` na finish.
- **Frontend**: form `hx-post=message.store hx-target=#messages hx-swap=beforeend hx-disable=this`. Listener **`htmx:after:swap`** (NIE `htmx:after:request` — odpala się przed swapem) otwiera `EventSource(messageStreamRoute)`. Auto-scroll po każdym chunku. Enter (bez shift) submituje.
- **Test streaming**: `AnonymousAgent::fake([...])` przed requestem. Sprawdzaj DB update zamiast capture body — test's `ob_start` nie łapie naszego `ob_flush`.

### Limity wiadomości

- **`App\Actions\ReserveMessageQuota`** invokable. Premium (`$user->subscribed()`) → zwraca `ChatSettings::defaultModel` bez DB. Free → `GrantDailyLimits::forUser()` (on-demand UPSERT) → `DB::transaction + lockForUpdate` → query `forUser/forCurrentWindow/available/orderByPriority desc/first` → `increment('used')`. Brak → `OutOfMessagesException`.
- **`GrantDailyLimits` idempotentne**: in-window → no-op (preserve `used`). Out-of-window (`period_start < now-1d`) → reset `used=0, period_start=now` + update `quota/priority` do defaultów. Brak rekordu → insert. Nie dotyka `limit_type=package`.
- **`config/premium.php`**: `daily` to lista `[[model, quota, priority], ...]`. Wyższy priority wygrywa. Pakiety dostają priority 3 z webhook Cashier'a.
- **`OutOfMessagesException` renderer** w `bootstrap/app.php`: HTMX → 403 + `htmx/out-of-messages` view (OOB toast) + `HX-Reswap: none`. Non-HTMX → 403 + pełna strona.
- **PHPStan + `period_start`**: cast `'datetime'` nie wystarcza Larastanowi — trzeba `@property Carbon|null $period_start` w docblocku modelu. Dodawaj `@property` dla każdej datetime kolumny.
- **Test atomicity**: `config()->set('premium.daily', [])` żeby on-demand grant nie re-seedował preset model w teście.

### Billing (Cashier + Stripe)

- **`App\Billing\Package` enum** = jedno źródło prawdy: `priceId()`, `fromPriceId()`, `isSubscription()`, `messageLimit()` (130/270/400/null), `model()`, `priority()`, `label()`, `tagline()`, `priceZloty()`. Stripe trzyma tylko cenę. Dodajesz pakiet = nowy case + `STRIPE_PRICE_*` w `.env`, nic więcej.
- **Route-model binding na enumie**: `POST /buy/{package}` → `BuyController::store(Package $package, ...)` → 404 dla nieznanego case automatycznie.
- **Checkout**: `$user->newSubscription('default', $priceId)->checkout($callbacks)` dla Premium, `$user->checkout([$priceId => 1], $callbacks)` dla one-off.
- **Webhook**: `StripeWebhookController extends Cashier WebhookController`. `handleCheckoutSessionCompleted` robi `Package::fromPriceId` reverse lookup. Premium pomijany (Cashier sam tworzy `subscriptions` row przez `customer.subscription.created`); pakiety → `MessageLimit::create` z `limit_type=Package`, `period_start=null`. Unknown price ID loguje error i kontynuuje (nie abortuje webhooka).
- **Webhook test**: anonymous subclass `StripeWebhookController` z override'em `protected lineItemsForSession(string): array<string>` — bez mockowania static `Cashier::stripe()`. Controller instancjuje się bezpośrednio i wołasz `handleCheckoutSessionCompleted($payload)`.
- **CSRF exclude**: `validateCsrfTokens(except: ['stripe/webhook'])`. **`Cashier::ignoreRoutes()`** w `AppServiceProvider::register()` — wyłącza auto-rejestrację Cashierowego defaultowego webhook route, własny w `routes/web.php`.
- **`.env.testing` potrzebuje placeholderów `STRIPE_PRICE_*`** (`price_test_five` itd.) — `config('billing.prices.X')` zwraca null bez nich.
- **PHPStan + Cashier**: parent `getUserByStripeId` zwraca `Billable|null` (trait, nie klasa) → `class.notFound`. Override w naszej klasie zwracający `?User` z inline `/** @var User|null */` docblock cast.
- **Octane cache routes**: po zmianie `routes/web.php` → `octane:reload` lub `docker restart`. Testy bootują świeżo.
- **Billing portal**: `GET /me/billing` → `BillingPortalController::__invoke` → `abort_unless($user->hasStripeId(), 404)` → `redirectToBillingPortal(profile.show)`. Stripe hostowany pokazuje invoices, payment methods, cancel sub. Link w navbar **conditional** na `$user?->hasStripeId()`.
- **Stripe CLI dev**: `stripe listen --forward-to localhost:43080/stripe/webhook` daje `whsec_` → wkleić do `.env`. Test cards: `4242 4242 4242 4242` sukces, `4000 0000 0000 0002` decline.

### Filament admin

- Panel `/admin`. `User::canAccessPanel() → hasRole('super_admin')`.
- **Filament 5 struktura**: `Resources/<Plural>/{<Resource>.php, Schemas/, Tables/, Pages/}`. Auto-discover w `AdminPanelProvider`. Read-only resource = usuń `Pages/Create*` + `Pages/Edit*` + override `canCreate(): bool { return false; }`.
- **Schema API**: `Filament\Schemas\Schema $schema->components([...])`. Komponenty z `Filament\Forms\Components\*`.
- **Spatie Settings page** (custom, extends `Filament\Pages\Page`): `mount` → `form->fill`, `save` → `form->getState` → cast → `$settings->save()`. PHPStan: `@property Schema $form` docblock dla magic prop z `InteractsWithSchemas`.
- **Widgety natywne** (`ChartWidget`, `StatsOverviewWidget`). Query przez **`DB::table('messages')`** (nie Eloquent), bo `DB::raw('SUM(...) as total')` na Eloquent nie daje PHPStanowi typu `$row->total`. Postgres time-series: `DATE_TRUNC('day', ...)` + `to_char(..., 'YYYY-MM-DD')`.
- **Shield + Spatie**: `shield:install admin` + `shield:generate --all --panel=admin --option=policies_and_permissions`. **Pomiń `shield:setup`/`shield:super-admin`** (interactive prompts → fail w no-interaction). Super-admin bypass przez `Gate::before` w `AppServiceProvider::boot`:

  ```php
  Gate::before(fn (User $user): ?bool => $user->hasRole('super_admin') ? true : null);
  ```

- **Heroicon**: `Filament\Support\Icons\Heroicon` enum. Nie zgaduj nazw — `grep` w `vendor/filament/support/src/Icons/Heroicon.php`. Np. nie ma `OutlinedGauge`, jest `OutlinedChartBarSquare`.
- **Test Filament**: `Livewire::test(<Page>::class)->fillForm([...])->call('create'|'save')->assertHasNoFormErrors()`. Actions: `->callAction('delete')`. Helper function `loginAsAdmin(): User` (Pest closure nie zbindowuje `$this->property` dla PHPStan). `auth()->login($user)` zamiast `test()->actingAs()`.

### Sentry

- Backend: `Integration::handles($exceptions)` w `bootstrap/app.php::withExceptions` (pierwsza linia, potem własne renderery). Bez DSN no-op. `traces_sample_rate` nieustawione (null) — same errors.
- Frontend: `@sentry/browser` init w `resources/js/app.js` z guardem na meta tag DSN. Meta tagi w `layouts/app.blade.php` (`sentry-dsn`, `sentry-environment`, `sentry-release`) z `config('sentry.*')`. `tracesSampleRate: 0`. Bundle +152KB gzip.

## Pliki pod nadzorem

- `composer.json` / `package.json` — zmiany przez `composer require` / `npm i` w kontenerze, nie ręczna edycja.
- `.env` / `.env.example` — synchronizuj strukturę.
- `bootstrap/app.php` — globalne renderery (`ValidationException`, `OutOfMessagesException`), CSRF exclude `stripe/webhook`, Sentry `Integration::handles`.
- `app/Providers/AppServiceProvider.php` — `Gate::before` super_admin, `Cashier::ignoreRoutes`, `ImageManipulator::defineVariant`.
- `app/Models/Character.php` — `booted()` cascade soft delete na chats.
- `app/Http/Controllers/MessageController.php` — store + stream (serce streamingu).
- `app/Actions/{GrantDailyLimits,ReserveMessageQuota}.php` — atomic select+increment, premium bypass, on-demand grant.
- `app/Billing/Package.php` + `config/billing.php` + `app/Http/Controllers/{BuyController,StripeWebhookController}.php` — billing flow.
- `app/Auth/SocialProvider.php` — enum OAuth providerów. Nowy provider = nowy case + `config/services.php`.
- `app/Filament/{Resources,Pages,Widgets}/` + `app/Policies/` — Filament 5 struktura + Shield.
- `routes/{web,console}.php` — routing + schedule (Laravel 13: bez `Kernel.php`). Routy auth w grupach `guest`/`auth`/`verified`.
- `resources/views/layouts/app.blade.php` — `hx-boost:inherited`, `#toasts` container, Sentry meta tagi.
- `resources/views/components/{alert,toast,navbar,auth-card,form-input,character-card}.blade.php` — DaisyUI komponenty, mobile-first.
- `resources/views/chat/show.blade.php` + `chat/_message.blade.php` — frontend streamingu (EventSource, `data-streaming` attr, `htmx:after:swap`).
- `resources/css/app.css` / `resources/js/app.js` — Tailwind + DaisyUI + HTMX + Sentry browser wire-up.
- `Dockerfile` / `Dockerfile.dev` — `dunglas/frankenphp:php8.5` Debian. Rebuild gdy zmieniasz rozszerzenia PHP. Zmiana glibc wymaga `rm -rf node_modules package-lock.json && npm install` w kontenerze (Rolldown bindings).
- `Docker/supervisord.conf` — octane + queue + cron, socket w `/tmp/`. Rebuild po zmianie.
- `docker-compose.yml` — `environment:` dla service `app` dostarcza DB_* entrypointowi.
- `config/octane.php` + `public/frankenphp-worker.php` — Octane wire-up. Wykluczone z Pint.
- `config/mediable.php` — `ignore_migrations => true`, `image_optimization.enabled => false`. Wykluczone z Pint.
- `config/sentry.php` — published. Wykluczone z Pint (paczka publikuje bez `declare(strict_types)`).
- `database/migrations/2026_04_24_013132_create_mediable_tables.php` — string-based polymorphic ID (mixed ULID/int).
