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
stripe listen --forward-to localhost:43080/stripe/webhook

# Diagnostyka
docker ps
docker logs new-app-1 --tail 50
docker exec new-app-1 supervisorctl status
```

**Porty hosta** (przestawione na 43xxx żeby nie kolidowały z innymi projektami): app `43080`, Postgres `43432`, Redis `43379`, Mailpit SMTP `43025` / UI `43825`, Adminer `43081`. Wewnątrz kontenerów procesy nasłuchują na oryginalnych portach (8080/5432/6379/1025/8025), w sieci Dockera używaj ich (`postgres:5432`, `redis:6379`, `mailpit:1025` itd.).

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

### Home / Character / Chat (Faza 4)

- **Medialibrary**: `plank/laravel-mediable` (nie Spatie). `Character implements MediableInterface + use Mediable`. Upload przez `MediaUploader::fromSource(...)->toDestination('public','characters')->useHashForFilename()->upload()` — zwraca `Media` record. Variant: `ImageManipulator::createImageVariant($media, 'square')`. Attach: `$character->attachMedia($media, 'avatar')`. URL: `$character->avatarUrl('square')` helper z fallbackiem DiceBear SVG.
- **Warianty zdefiniowane w `AppServiceProvider::boot()`**: `square` (512×512 WebP Q85, `$image->cover(512,512)` = fit-crop center), `thumb` (96×96 WebP Q80). Intervention v3 — GD driver (Imagick nie jest w kontenerze), auto-wybierany przez `intervention/image-laravel`. Optimizer wyłączony w `config/mediable.php` bo brak binariów jpegoptim itd. w obrazie — WebP Q85 jest wystarczające.
- **Polimorficzny ID**: `mediables` tabela ma `mediable_type` i `mediable_id` jako `string` (NIE bigint) bo User jest int a Character jest ULID. Migracja paczkowa używa `$table->morphs()` → bigint → psuje ULID. **Fix**: `config('mediable.ignore_migrations') => true` + ręczna edycja opublikowanej migracji na `string/string`.
- **`HomeController::index`** — paginate 24, `latest()`, z eager `['author','media']`. HTMX infinite scroll: `hx-trigger="revealed"` na sentinelu → partial `_character-grid-page`.
- **Karta postaci** (`<x-character-card>`): `aspect-[3/4]` portret, WebP tło, gradient overlay `from-black/85`, **click-anywhere** przez `<form>` (auth) albo `<a href="/login">` (guest) jako `absolute inset-0 z-10` nad contentem `pointer-events-none`. Hover: `-translate-y-1` + `ring-primary/60`.
- **`CharacterController`** — `create` form + `store` w `DB::transaction` (Character + opcjonalny upload/variant/attach + `Chat::firstOrCreate`). Po store user leci prosto do `chat.show` z nowo utworzoną postacią (legacy redirectował do home → zero flow).
- **`ChatController::show`** — `abort_unless($chat->user_id === auth()->id(), 404)` (inline authz, nie Policy). ULID route binding na Chat.
- **`ChatController::store`** — `firstOrCreate(user_id+character_id)` race-safe dzięki partial unique index z Fazy 1 (`WHERE deleted_at IS NULL`). Walidacja `character_id` przez `Rule::exists`.
- **Widok czatu**: DaisyUI `drawer lg:drawer-open` — sidebar (lista czatów) zawsze widoczny >= lg, off-canvas < lg. Main area: sticky header (avatar + nazwa), `#messages` scrollable, sticky bottom input **wyłączony** w Fazie 4 (streaming to Faza 5). Bubbles: `chat-start/end` + `chat-bubble-neutral/primary` dla character/user.
- **Seeder sample data** używa `Character::factory()->withAvatar()->recycle($users)->create()`. State `withAvatar()` generuje solid-color PNG przez GD w tempfile → MediaUploader → createImageVariant → attach. Zero zewnętrznych URLi, testy działają offline.

### Streaming / FrankenPHP + Octane / SSE (Faza 5)

- **App server**: **FrankenPHP przez `laravel/octane`** (worker mode), nie nginx + php-fpm. Obraz `dunglas/frankenphp:php8.5` (Debian bookworm), Caddy wbudowany, PHP jako SAPI. `supervisord` zarządza `octane:start --server=frankenphp --workers=auto --max-requests=500` + queue + cron. `OCTANE_SERVER=frankenphp` w `.env`.
- **Octane gotchas do pilnowania**: worker mode = app boots once i obsługuje wiele requestów. `AppServiceProvider::boot` wykonuje się raz (wariantów Mediable/Intervention nie trzeba re-rejestrować — `defineVariant` jest idempotent). **Nie używaj statycznych properties** do per-request state. Jeśli paczka trzyma singleton z request-scoped danymi (typu token aktualnego usera), potrzebny listener w `config/octane.php` który resetuje.
- **Streaming PHP w FrankenPHP**: `response()->stream(closure)` + `echo "data: ...\n\n"` + `if (ob_get_level() > 0) @ob_flush(); flush();` w pętli. **Zero fastcgi_buffering** bo nie ma fastcgi. `ob_flush()` bez aktywnego OB generuje notice który gubi output — dlatego guard. `X-Accel-Buffering: no` i `Cache-Control: no-cache` headers zostają dla safety.
- **`laravel/ai` 0.6.3 wzorzec** (`AnonymousAgent`): `new AnonymousAgent(instructions: $character->prompt, messages: [new UserMessage('…'), new AssistantMessage('…')], tools: [])->stream(prompt: $latestUserText, provider: Lab::OpenRouter, model: 'openai/gpt-4o-mini')` → `StreamableAgentResponse` (IteratorAggregate). Pętla: `TextDelta->$delta` to słowa do append, `StreamEnd->$usage->$completionTokens` do zapisu po streamie. Fake: `AnonymousAgent::fake(['Response text'])` → FakeTextGateway splituje po spacji, yielduje pełne event sequence (StreamStart→TextStart→TextDelta*→TextEnd→StreamEnd).
- **Chat SSE flow**: dwa endpointy. `POST /chat/{chat}/messages` (MessageController@store) — w jednej transakcji user Message + empty character Message, zwrot HTML z dwoma bubble'ami. Header `X-Character-Message-Id` identyfikuje streaming bubble. `GET /chat/{chat}/messages/stream` (MessageController@stream) — podnosi ostatni pusty character Message, buduje payload (system prompt + N historii + wrappers z `ChatSettings`), streamuje `laravel/ai`, po finish zapisuje `content` + `tokens_usage`.
- **Frontend SSE w chat.show**: form HTMX (`hx-post=message.store`, `hx-target=#messages`, `hx-swap=beforeend`, `hx-disable=this`). `form.addEventListener('htmx:after:request', ...)` (HTMX 4 **dwukropki**!) otwiera natywny `EventSource(messageStreamRoute)`. `onmessage` appenduje `payload.delta` do `[data-streaming="true"]` bubble. Na `{stop:true}` removuje atrybut i zamyka ES. Auto-scroll do dołu po każdym chunku. Enter (bez shift) submituje.
- **Konwencje testowe dla streamingu**: `AnonymousAgent::fake([...])` przed requestem. W Pest `$response->baseResponse->sendContent()` triggeruje closure — ale `ob_flush()` wypycha output ponad test's output buffer (jeden poziom `ob_start` to za mało). Sprawdzamy **DB update** (character Message content + tokens_usage) jako invariant zamiast body capture. SSE output widoczny w stderr Pesta podczas uruchomienia testu.
- **Route REST**: POST/GET pod `/chat/{chat:ulid}/messages(/stream)`. Authz inline: `abort_unless($chat->user_id === $request->user()?->id, 404)`. ULID route binding.

### Limity wiadomości (Faza 6)

- **Jedna invokable action `App\Actions\ReserveMessageQuota`** robi select + increment atomowo. Premium (`$user->subscribed()` przez Cashier) → zwraca `ChatSettings::defaultModel` bez dotykania DB. Free user → `GrantDailyLimits::forUser()` (on-demand UPSERT) → `DB::transaction + lockForUpdate` → `MessageLimit` query `forUser/forCurrentWindow/available/orderByPriority desc/first` → `increment('used')`. Brak dostępnego limitu → `throw App\Exceptions\OutOfMessagesException`.
- **On-demand grant zamiast sample nightly**: pierwszy messagę nowego usera sam załatwia grant; cron `RefreshDailyLimits` @ 00:05 jest passive refreshem dla aktywnych userów (nie trigger'em). Daje to użytkownikowi natychmiastowe limity przy pierwszej wiadomości bez listenera na `Registered`.
- **`GrantDailyLimits` jest idempotentne**: in-window rekord daily → no-op (preserve `used`). Out-of-window (`period_start < now - 1d`) → reset `used=0, period_start=now`, plus aktualizacja `quota/priority` do defaultów. Brak rekordu → insert. Nie dotyka `limit_type=package`. `forUser(User)` dla pojedynczego usera (wywoływane też przez `ReserveMessageQuota`); `forAll(chunk)` dla cron'a.
- **Config `config/premium.php`**: `daily` to lista `[[model, quota, priority], ...]`. Wyższy priority wygrywa w Reserve (GPT-4o priority 2 wyżej niż GPT-4o mini priority 1). Pakiety mają priority 3 (jeszcze wyżej) — powstają przez webhook Cashier'a w Fazie 7.
- **`MessageController@store` integracja**: `ReserveMessageQuota` na samym początku, zanim coś tworzymy w DB. Model z action zapisywany na empty character Message (zamiast hardcoded `Gpt4oMini`). Jeśli action rzuca `OutOfMessagesException`:
  - HTMX (`HX-Request: true`) → 403 + view `htmx/out-of-messages.blade.php` (OOB toast) + `HX-Reswap: none`. User widzi toast, input zostaje z tekstem do retry po kupnie pakietu.
  - Non-HTMX → 403 + view `errors/out-of-messages.blade.php` (pełna strona z alertem).
- **`UserFactory::premium()` state** tworzy aktywną Cashier `Subscription` do testów przed Fazą 7. Zawsze tylko w pamięci testów — prod subskrypcje idą przez webhook.
- **Testy atomicity**: `increments atomically under repeated calls` wymaga `config()->set('premium.daily', [])` na czas testu, żeby on-demand grant nie re-seedował fresh GPT-4o z defaultów (wyższe priority niż presetowane w teście mini) i test faktycznie sprawdzał izolowaną atomowość inkrementu. Memo dla przyszłości: przy testach Reserve które zakładają konkretny stan limitów, wyłączaj default'y.
- **PHPStan gotcha dla `period_start`**: `casts(): ['period_start' => 'datetime']` nie wystarczy Larastan'owi do wydedukowania typu `Carbon` — musi być `@property Carbon|null $period_start` w docblock modelu. Dodane przy Fazie 6; kolejne kolumny datetime dodawaj z `@property` od razu.

### Billing / Cashier + Stripe (Faza 7)

- **Pakiety jako enum `App\Billing\Package`** — jedno źródło prawdy: `priceId()`, `fromPriceId()`, `isSubscription()`, `messageLimit()` (130/270/400/null), `model()`, `priority()` (3 dla pakietów, 0 dla Premium), `label()`, `tagline()`, `priceZloty()`. Stripe trzyma tylko cenę — shape pakietu w enumie, nie w `price.metadata`. Dodajesz nowy pakiet = nowy case w enumie + nowy `STRIPE_PRICE_*` w `.env`, nic więcej.
- **Route-model binding na enumie**: `POST /buy/{package}` z `BuyController::store(Package $package, Request $request)` — Laravel 13 automatycznie 404 na nieznanym case. Legacy `match($package)` z `default => abort(404)` znika.
- **Checkout flow**: `$user->newSubscription('default', $priceId)->checkout($callbacks)` dla Premium, `$user->checkout([$priceId => 1], $callbacks)` dla pakietów one-off. Success/cancel URLs pod `/buy/success|cancel` w grupie `auth+verified`. Stripe podmienia `{CHECKOUT_SESSION_ID}` w success_url — używane ewentualnie do confirmation widoku (teraz pomijane).
- **Webhook**: `StripeWebhookController extends Cashier\Http\Controllers\WebhookController`. `handleCheckoutSessionCompleted($payload)` pobiera user przez `getUserByStripeId` (override zwracający `?User` dla PHPStan), iteruje line items, `Package::fromPriceId($priceId)` robi reverse lookup, premium skipa (Cashier sam tworzy subscription row przez `customer.subscription.created`), pakiety → `MessageLimit::create([...])` z `limit_type=Package`, `period_start=null` (pakiety nie wygasają). Unknown price ID loguje error i kontynuuje (nie abortuje webhooka).
- **Testowalność webhooka**: `protected lineItemsForSession(string $sessionId): array<string>` wyciąga lookup Stripe SDK do osobnej metody. Test używa **anonymous subclass** `StripeWebhookController` z override'em, zamiast mockować static `Cashier::stripe()`. Controller instancjuje się bezpośrednio w teście i woła metodę `handleCheckoutSessionCompleted($payload)` — bypassuje sygnaturę webhooka i HTTP layer.
- **CSRF exclude**: `$middleware->validateCsrfTokens(except: ['stripe/webhook'])` w `bootstrap/app.php`. `Cashier::ignoreRoutes()` w `AppServiceProvider::register()` wyłącza auto-rejestrację Cashier'owego defaultowego webhook route — własny w `routes/web.php` pod `name('cashier.webhook')`.
- **Stripe CLI do dev webhooków**: `stripe listen --forward-to localhost:43080/stripe/webhook` (lokalnie na hoście, nie w kontenerze). Output wypluwa `whsec_...` → wkleić do `STRIPE_WEBHOOK_SECRET` w `.env`. `stripe trigger checkout.session.completed` odgrywa event. Karty testowe: `4242 4242 4242 4242` (sukces), `4000 0000 0000 0002` (decline).
- **`.env.testing` potrzebuje `STRIPE_PRICE_*` placeholderów** — `config('billing.prices.X')` zwraca null bez nich, a test porównuje priceId ze stringiem. Placeholder wartości (`price_test_five` itd.) wystarczają, faktyczne sandbox IDs są w `.env`.
- **Cashier + PHPStan**: `getUserByStripeId()` w parencie zwraca `Billable|null` (trait, nie klasa) → PHPStan `class.notFound` przy `$user->id`. Fix: override w naszej klasie zwracający `?User` z `/** @var User|null $user */` inline docblock nad `parent::getUserByStripeId(...)`. `instanceof User` na tym zwraca `instanceof.alwaysFalse` — trzeba docblock cast.
- **Octane cache routes**: po zmianie `routes/web.php` trzeba `docker exec new-app-1 php artisan octane:reload` albo `docker restart new-app-1` — bez tego dev curl serwuje stare routes. Testy Pest bootują Laravel świeżo, nie ma problemu.
- **Faktury**: Faza 9 wpina `$user->redirectToBillingPortal(...)` — Stripe hostowany portal pokazuje listę faktur z PDF + zarządza metodą płatności + anuluje sub. Zero własnego kodu na PDFy i listę. Link tylko dla userów z `stripe_id != null`.

### Dalsze sekcje (uzupełniamy z kolejnymi fazami)

Filament admin — dopiszemy gdy powstanie.

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
- `app/Http/Requests/{Auth,Profile,Character}/*` — walidacja, nie w kontrolerach.
- `resources/views/components/{navbar,auth-card,form-input,character-card}.blade.php` — komponenty UI. Mobile-first, DaisyUI 5.
- `app/Providers/AppServiceProvider.php` — `ImageManipulator::defineVariant` (square, thumb) w `boot()`.
- `config/mediable.php` — `ignore_migrations => true`, `image_optimization.enabled => false`. Exclude'owane z Pint.
- `database/migrations/2026_04_24_013132_create_mediable_tables.php` — string-based polymorphic ID (nie bigint) dla mixed ULID/int.
- `docker-compose.yml` — `environment:` dla service `app` dostarcza DB_* entrypointowi (który pollował bez env_file w Fazie 1 i wisiał w loopie).
- `Dockerfile` / `Dockerfile.dev` — baza `dunglas/frankenphp:php8.5` (Debian), **bez nginx/php-fpm**. Rebuild gdy zmieniamy rozszerzenia PHP. Zmiana Alpine→Debian wymaga `rm -rf node_modules package-lock.json && npm install` w kontenerze żeby bindingi Rolldown zrekompilowały się pod nową libc.
- `Docker/supervisord.conf` — octane + queue + cron. Nginx/fpm wykasowane.
- `config/octane.php` — server `frankenphp`, listenery resetujące state między requestami (domyślne Octane'a). Wykluczone z Pint przez `notName`.
- `public/frankenphp-worker.php` — entrypoint Octane workera (wymagane przez `octane:start --server=frankenphp`). Wykluczone z Pint.
- `app/Http/Controllers/MessageController.php` — serce streamingu (store + stream). `store()` injectuje `ReserveMessageQuota` i to ona dyktuje wybrany model.
- `resources/views/chat/show.blade.php` + `chat/_message.blade.php` — frontend streamingu (EventSource wire-up, `data-streaming` attr, HTMX 4 events z dwukropkami).
- `app/Actions/{GrantDailyLimits,ReserveMessageQuota}.php` — serce limitów. Atomic select+increment w TX z lockForUpdate, premium bypass przez `$user->subscribed()`, on-demand grant.
- `app/Jobs/RefreshDailyLimits.php` + `routes/console.php` — nightly cron @ 00:05 iterujący wszystkich userów przez `GrantDailyLimits::forAll`.
- `config/premium.php` — tylko defaults daily (model/quota/priority). Pakiety dojdą w Fazie 7.
- `app/Exceptions/OutOfMessagesException.php` + `bootstrap/app.php` (render handler) + `resources/views/{htmx,errors}/out-of-messages.blade.php` — HTMX-aware 403 z toastem / non-HTMX pełna strona.
- `app/Billing/Package.php` — enum pakietów, jedyne źródło prawdy o quota/priority/model/labels. Zmiana quota = edit enum, nie Stripe.
- `config/billing.php` — tylko map `package.value => env('STRIPE_PRICE_*')`, zero logiki.
- `app/Http/Controllers/BuyController.php` + `StripeWebhookController.php` — checkout flow + webhook handler. `lineItemsForSession` oddzielone jako protected dla testowalności (override w anonymous subclass).
- `bootstrap/app.php` — CSRF exclude `stripe/webhook` + render handlers (`ValidationException` + `OutOfMessagesException`).
- `app/Providers/AppServiceProvider.php` — `Cashier::ignoreRoutes()` w `register()` (własny route zamiast Cashier default), `ImageManipulator::defineVariant` w `boot()`.
- `resources/views/buy/{index,success,cancel}.blade.php` — UI billing. `index` ma pricing cards grid; Ten/Premium wyróżnione `ring-primary`/`border-accent`.
- `resources/views/components/navbar.blade.php` — `Pakiety` link → `/buy`, dropdown profilu dodaje "Kup wiadomości" + "Moje limity".

## Co może się zmienić

- Root `../CLAUDE.md` ma stare wzmianki o Sail — nieaktualne. Zostaw dopóki user sam nie usunie, ale nie polegaj na nim.
