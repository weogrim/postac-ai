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
- **Spatie Settings** — typowane klasy (np. `App\Chat\Settings\ChatSettings`).
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

## Architektura modularna

Pełna referencja: **`docs/ARCHITEKTURA.md`** (uzasadnienia) + **`docs/ARCHITEKTURA_RULES.md`** (zasady, imperatyw). Tu skondensowane minimum.

### Układ `app/`

`app/` zawiera **moduły domenowe** + jeden specjalny `System/`. **Brak** `Http/`, `Models/`, `Services/`, `Providers/`, `Actions/` na poziomie głównym.

Moduły obecnie w projekcie:

```
app/
├── Auth/         # rejestracja/login/reset/verify/social
├── Billing/      # Cashier flow, Package enum, Stripe webhook
├── Character/    # Character + Policy + Resource
├── Chat/         # Chat/Message/MessageLimit + streaming + limity + settings
├── Filament/     # panel admina (Resources/Pages/Widgets)
├── Home/         # strona główna
├── User/         # profil / hasło / policies / UserModel
└── System/       # framework-glue (Providers + co tam wpadnie)
```

Reguła decyzyjna `<Module>` vs `System/`: *czy klasa jest częścią konkretnej domeny biznesowej?* TAK → moduł, NIE → `System/`.

### Sufiksy nazewnicze (twarde)

| Klasa                     | Wzorzec                                  |
| ------------------------- | ---------------------------------------- |
| Eloquent Model            | `<Foo>Model` (np. `CharacterModel`)      |
| Pivot                     | `<Foo><Bar>Pivot`                        |
| Klasa biznesowa           | `<Module><Action>` (np. `MessageStreamer`, `ReserveMessageQuota`) |
| Controller (web)          | `<Foo>Controller`                        |
| Controller (JSON API)     | `<Foo>ApiController`                     |
| Single-action Controller  | `<Resource><Action>Controller` (np. `BuySuccessController`, `MessageStreamController`) |
| FormRequest               | `<Foo>Request`                           |
| Observer                  | `<FooModel>Observer`                     |
| Event / Listener / Job    | `<Foo>Event` / `<Foo>Listener` / `<Foo>Job` |
| Notification / Mailable   | `<Foo>Notification` / `<Foo>Mail`        |
| DTO / Enum / Exception    | `<Foo>DTO` / `<Foo>Enum` / `<Foo>Exception` |
| Trait / Command           | `<Foo>Trait` / `<Foo>Command`            |

### Gdzie co kłaść

Wewnątrz modułu: `Models/`, `Controllers/`, `Requests/`, `Resources/`, `Observers/`, `Events/`, `Listeners/`, `Jobs/`, `Notifications/`, `Mail/`, `DTO/`, `Enums/`, `Exceptions/`, `Pipes/`, `Commands/`, `Policies/`, `Settings/` (Spatie Settings). **Klasa biznesowa leży w roocie modułu — bez podfolderu `Services/`/`Actions/`/`UseCases/`** (np. `app/Chat/MessageStreamer.php`, `app/Chat/ReserveMessageQuota.php`).

W `app/System/`: `Providers/`, `Middleware/`, `Trait/`, `Utilities/`, generyczne `Enums/Exceptions/Jobs/Listeners/Commands/Controllers/`, wrappery niskopoziomowych protokołów (np. `RabbitMq/`).

Klasycznie (poza `app/`): migracje (`database/migrations/`), seedery, factories, configi, trasy (`routes/web.php` + `console.php`), testy (`tests/Feature/<Module>/`, `tests/Unit/<Module>/`).

### Klasy biznesowe — kiedy wydzielać

**TAK** — w roocie modułu — gdy operacja: jest wieloetapowa, zawiera regułę domenową do testowania w izolacji, integruje moduły / zewnętrzne usługi, albo obciąża kontroler.
**NIE** — zostaw w kontrolerze / na modelu — gdy to wrapper jednego `Model::create($data)`, 1–3 linie bez gałęzi, albo równie dobrze metoda na modelu.

Cel: ani god-object, ani 50 mikro-klas. **Logika domyślnie żyje w kontrolerze**; klasę wydzielamy dopiero, gdy ma to sens.

**Klasa biznesowa NIE używa `__invoke`** — ma normalne nazwane metody (`record`, `forUser`, `reserve`, `grant`, ...). Reguła `__invoke` w tej dokumentacji dotyczy **tylko single-action controllerów**.

**Brak DI w parametry/konstruktory.** Klasy biznesowe (i wszystkie inne, które nie są kontrolerami z RESTowym Request/FormRequest) pobiera się z kontenera przez `app(Class::class)->method(...)`. Wyjątki dozwolone tylko: `Request`, `FormRequest` w sygnaturach kontrolerów.

```php
// ✅ DOBRZE
public function store(RegisterRequest $request): RedirectResponse
{
    app(RecordConsents::class)->record($user, [...], $request);
}

// ❌ ŹLE — wstrzykiwanie zależności w parametr
public function store(RegisterRequest $request, RecordConsents $recordConsents): RedirectResponse {}

// ❌ ŹLE — klasa biznesowa z __invoke
class RecordConsents { public function __invoke(...) {} }
```

### Controllers — REST + single-action

W ~99% wyłącznie metody REST: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`. Akcja nie-REST (`cancel`, `archive`, `publish`) → **osobny kontroler** `<Resource><Action>Controller`. Single-action bez formularza → kontroler Invokable z `__invoke()` (np. `MessageStreamController`, `BillingPortalController`, `BuySuccessController`). Z formularzem → wciąż REST (`create` + `store`).

### Anty-wzorce (czerwone flagi)

- ❌ `OrderService`, `UserService` z metodami CRUD; sufix `Service` **tylko** dla wrapperów zewnętrznych API w osobnym module integracyjnym.
- ❌ Single-purpose dla każdej akcji REST (`OrderCreate`, `OrderUpdate`, `OrderShow`).
- ❌ Klasa biznesowa-wrapper na jedno `Model::create`.
- ❌ Folder `Services/`/`Actions/`/`UseCases/` w module.
- ❌ Foldery `app/Http/`, `app/Models/`, `app/Services/`, `app/Providers/` na poziomie głównym.
- ❌ Wymyślne nazwy metod kontrolera (`list`, `save`, `cancelOrder`).
- ❌ Akcja nie-REST jako kolejna metoda w CRUD-controllerze.
- ❌ ServiceProvider per moduł, routing per moduł, configi per moduł, fasada modułu.
- ❌ Namespace `App\Modules\<X>` / `App\Domain\<X>` — płasko: `App\<Module>`.
- ❌ Klasa biznesowa z `__invoke` (invoke tylko dla single-action controllerów).
- ❌ Wstrzykiwanie zależności w parametry metod / konstruktory (poza `Request`/`FormRequest`). Pobierajmy przez `app(Class::class)->method(...)`.

### Rejestracja — hierarchia

1. Atrybut PHP / auto-discovery (np. `#[ObservedBy(...)]`).
2. Generycznie w `bootstrap/app.php` przez `glob()` po katalogach modułów (`->withCommands(commands: glob(__DIR__.'/../app/*/Commands'))`, `->withEvents(discover: glob(__DIR__.'/../app/*/Listeners'))`).
3. Dopiero gdy się nie da → `App\System\Providers\AppServiceProvider`.

Centralni rejestratorzy: `App\System\Providers\AppServiceProvider` + `App\System\Providers\AdminPanelProvider` (wpisane w `bootstrap/providers.php`). Brak ServiceProviderów per moduł.

## Architektura — szczegóły techniczne

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

### Auth + profil (moduły `Auth/` + `User/`)

- Email+password działa real, **`MustVerifyEmail` obowiązkowe** (Mailpit lokalnie łapie wszystko). `verified` middleware na `/me*`.
- **Google OAuth** przez enum `App\Auth\SocialProvider`. Route `/auth/{provider}` używa Laravelowego implicit enum binding → 404 dla nieznanego providera automatycznie.
- **Socialite callback**: firstOrCreate po emailu, **bez nadpisywania hasła**. Istniejący user z hasłem loguje się bez zmian; nowy ma `password=null`, `email_verified_at=now()`. Nazwa: `Socialite->getName() || Str::before($email, '@')` + losowy suffix `Str::random(4)` przy konflikcie.
- **Rate limit loginu** w `LoginRequest::authenticate()` — `RateLimiter::hit/clear` na kluczu `lower(email)|ip`, 5/min, potem `Lockout` event. Nie middleware — FormRequest zamyka logikę w jednym miejscu.
- **Account delete**: hard delete z potwierdzeniem `confirm=USUŃ` (działa też dla OAuth userów bez password). `UserModel` bez soft delete — dane giną twardo dla prywatności.
- **Kontrolery split by concern**:
  - `app/Auth/Controllers/{Register,Login,PasswordResetLink,NewPassword,EmailVerificationNotice,VerifyEmail,EmailVerificationResend,SocialAuth}Controller`,
  - `app/User/Controllers/{Profile,Password}Controller`.
  Form Requesty (`app/Auth/Requests/`, `app/User/Requests/`) zamiast inline `$request->validate()`.
- Logout form ma `hx-boost="false"` — POST nie boostowany, żeby nie kolidował z CSRF/session cycle.

### Character / Chat / Media (moduły `Character/` + `Chat/`)

- **`plank/laravel-mediable`** (nie Spatie). `CharacterModel implements MediableInterface + use Mediable`. URL przez helper `$character->avatarUrl('square')` z fallbackiem DiceBear.
- **Warianty w `App\System\Providers\AppServiceProvider::boot`**: `square` (512×512 WebP Q85), `thumb` (96×96 WebP Q80). Intervention v3 + GD driver (Imagick nie ma w obrazie). Optimizer wyłączony — WebP Q85 wystarcza.
- **Polimorficzny ID jako string** (NIE bigint) — User=int, Character=ULID. `config('mediable.ignore_migrations') => true` + ręczna edycja opublikowanej migracji na `string/string`.
- **Morph map** w `AppServiceProvider::boot` (`Relation::enforceMorphMap`): aliasy `user`, `character`, `chat`, `message`, `message_limit` → odpowiednie `*Model` klasy. Alias `Model`-suffix dropped, dzięki czemu DB rows nie wiedzą o nazewnictwie klas PHP.
- **Factory naming**: w `AppServiceProvider::boot` `Factory::guessFactoryNamesUsing` strips `Model` suffix → `CharacterModel` → `Database\Factories\CharacterFactory`. Factories zostają w `database/factories/` klasycznie.
- **`ChatController::store`**: `firstOrCreate(user_id+character_id)` race-safe dzięki **partial unique index** `WHERE deleted_at IS NULL`.
- **Authz inline**: `abort_unless($chat->user_id === auth()->id(), 404)`. Bez Policy dla user-facing — Policy (w `app/Chat/Policies/`, `app/Character/Policies/`, `app/User/Policies/`) używane tylko w admin/Filament.
- **Cascade soft delete**: `CharacterModel::booted()` z `static::deleting` cascade na chats, `static::restoring` przywraca trashed. Guard `isForceDeleting()` — DB FK `cascadeOnDelete()` i tak hardusunie przy force delete.

### Streaming (FrankenPHP + SSE + laravel/ai)

- **Worker mode**: app boot raz, obsługuje wiele requestów. **Nie używaj statycznych properties** dla per-request state. Singleton z user-scoped data → listener w `config/octane.php` resetuje między requestami. `AppServiceProvider::boot` powinien być idempotent (`defineVariant` jest).
- **Streaming PHP**: `response()->stream(closure)` + `echo "data: ...\n\n"` + `if (ob_get_level() > 0) @ob_flush(); flush();`. **`ob_flush` bez aktywnego OB generuje notice** który gubi output — guard obowiązkowy. Headers: `Content-Type: text/event-stream`, `Cache-Control: no-cache`, `X-Accel-Buffering: no`.
- **Klasa biznesowa `App\Chat\MessageStreamer`** (root modułu Chat) — buduje historię konwersacji + uruchamia `AnonymousAgent`, yielduje eventy SSE jako tablice. Kontroler je serializuje do `data: ...`. Wzorcowy przykład wydzielenia logiki domenowej — bo wieloetapowa, integruje moduł Chat z `laravel/ai`, testowalna w izolacji.
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
- **Chat SSE flow**: `POST /chat/{chat}/messages` (`MessageController::store`) tworzy user + empty character w jednej transakcji, zwraca HTML z dwoma bubble'ami + header `X-Character-Message-Id`. `GET /chat/{chat}/messages/stream` (`MessageStreamController::__invoke` — single-action invokable) zatwierdza ostatni pusty char msg, streamuje, zapisuje `content` + `tokens_usage` na finish.
- **Frontend**: form `hx-post=message.store hx-target=#messages hx-swap=beforeend hx-disable=this`. Listener **`htmx:after:swap`** (NIE `htmx:after:request` — odpala się przed swapem) otwiera `EventSource(messageStreamRoute)`. Auto-scroll po każdym chunku. Enter (bez shift) submituje.
- **Test streaming**: `AnonymousAgent::fake([...])` przed requestem. Sprawdzaj DB update zamiast capture body — test's `ob_start` nie łapie naszego `ob_flush`.

### Limity wiadomości (moduł `Chat/`)

- **`App\Chat\ReserveMessageQuota`** klasa biznesowa (root modułu) z metodą `reserve(UserModel): ModelType`. Wywołanie: `app(ReserveMessageQuota::class)->reserve($user)`. Premium (`$user->subscribed()`) → zwraca `ChatSettings::defaultModel` bez DB. Free → `app(GrantDailyLimits::class)->forUser($user)` (on-demand UPSERT) → `DB::transaction + lockForUpdate` → query `forUser/forCurrentWindow/available/orderByPriority desc/first` → `increment('used')`. Brak → `OutOfMessagesException`.
- **`App\Chat\GrantDailyLimits` idempotentne**: in-window → no-op (preserve `used`). Out-of-window (`period_start < now-1d`) → reset `used=0, period_start=now` + update `quota/priority` do defaultów. Brak rekordu → insert. Nie dotyka `limit_type=package`.
- **`config/premium.php`**: `daily` to lista `[[model, quota, priority], ...]`. Wyższy priority wygrywa. Pakiety dostają priority 3 z webhook Cashier'a.
- **`App\Chat\Exceptions\OutOfMessagesException` renderer** w `bootstrap/app.php`: HTMX → 403 + `htmx/out-of-messages` view (OOB toast) + `HX-Reswap: none`. Non-HTMX → 403 + pełna strona.
- **PHPStan + `period_start`**: cast `'datetime'` nie wystarcza Larastanowi — trzeba `@property Carbon|null $period_start` w docblocku modelu. Dodawaj `@property` dla każdej datetime kolumny.
- **Test atomicity**: `config()->set('premium.daily', [])` żeby on-demand grant nie re-seedował preset model w teście.

### Billing (moduł `Billing/` + Cashier + Stripe)

- **`App\Billing\Package` enum** = jedno źródło prawdy: `priceId()`, `fromPriceId()`, `isSubscription()`, `messageLimit()` (130/270/400/null), `model()`, `priority()`, `label()`, `tagline()`, `priceZloty()`. Stripe trzyma tylko cenę. Dodajesz pakiet = nowy case + `STRIPE_PRICE_*` w `.env`, nic więcej.
- **Route-model binding na enumie**: `POST /buy/{package}` → `App\Billing\Controllers\BuyController::store(Package $package, ...)` → 404 dla nieznanego case automatycznie.
- **Checkout**: `$user->newSubscription('default', $priceId)->checkout($callbacks)` dla Premium, `$user->checkout([$priceId => 1], $callbacks)` dla one-off.
- **Webhook**: `App\Billing\Controllers\StripeWebhookController extends Cashier WebhookController`. `handleCheckoutSessionCompleted` robi `Package::fromPriceId` reverse lookup. Premium pomijany (Cashier sam tworzy `subscriptions` row przez `customer.subscription.created`); pakiety → `MessageLimitModel::create` z `limit_type=Package`, `period_start=null`. Unknown price ID loguje error i kontynuuje (nie abortuje webhooka).
- **Webhook test**: anonymous subclass `StripeWebhookController` z override'em `protected lineItemsForSession(string): array<string>` — bez mockowania static `Cashier::stripe()`. Controller instancjuje się bezpośrednio i wołasz `handleCheckoutSessionCompleted($payload)`.
- **CSRF exclude**: `validateCsrfTokens(except: ['stripe/webhook'])`. **`Cashier::ignoreRoutes()`** w `App\System\Providers\AppServiceProvider::register()` — wyłącza auto-rejestrację Cashierowego defaultowego webhook route, własny w `routes/web.php`.
- **`Cashier::useCustomerModel(UserModel::class)`** w `register()` — bo nasz model ma sufix `Model`, Cashier domyślnie szuka `App\Models\User`.
- **`.env.testing` potrzebuje placeholderów `STRIPE_PRICE_*`** (`price_test_five` itd.) — `config('billing.prices.X')` zwraca null bez nich.
- **PHPStan + Cashier**: parent `getUserByStripeId` zwraca `Billable|null` (trait, nie klasa) → `class.notFound`. Override w naszej klasie zwracający `?UserModel` z inline `/** @var UserModel|null */` docblock cast.
- **Octane cache routes**: po zmianie `routes/web.php` → `octane:reload` lub `docker restart`. Testy bootują świeżo.
- **Billing portal**: `GET /me/billing` → `App\Billing\Controllers\BillingPortalController::__invoke` → `abort_unless($user->hasStripeId(), 404)` → `redirectToBillingPortal(profile.show)`. Stripe hostowany pokazuje invoices, payment methods, cancel sub. Link w navbar **conditional** na `$user?->hasStripeId()`.
- **Stripe CLI dev**: `stripe listen --forward-to localhost:43080/stripe/webhook` daje `whsec_` → wkleić do `.env`. Test cards: `4242 4242 4242 4242` sukces, `4000 0000 0000 0002` decline.

### Legal (moduł `Legal/`)

- **Modele**: `LegalDocumentModel` (slug + version + title + content + published_at, unique `(slug, version)`), `ConsentModel` (user_id + legal_document_id + accepted_at + ip_address + user_agent). `consents.legal_document_id` FK **restrict** — admin nie usuwa dokumentu z istniejącymi zgodami; nowa wersja = nowy rekord.
- **`App\Legal\Enums\DocumentSlug`** — backed enum z `HasLabel` (Filament). Cases: `terms`, `privacy`, `dating-terms` (Faza 5).
- **Versioning**: każda zmiana treści = nowy rekord z incrementowanym `version`. Stare wersje zostają w DB jako historia zgód. Filament TableAction `duplicate` kopiuje rekord z `version+1` i pustym `published_at` (draft).
- **Publiczny widok**: `GET /legal/{slug}` (implicit enum binding) → `App\Legal\Controllers\LegalDocumentController::show` → bierze `latest published version` (`whereNotNull('published_at')->orderByDesc('version')->firstOrFail()`) → renderuje markdown przez **`League\CommonMark\CommonMarkConverter`** z `html_input => 'escape'` + `allow_unsafe_links => false` (XSS guard). Brak opublikowanej wersji → 404.
- **Klasa biznesowa `App\Legal\RecordConsents`** (root modułu, metoda `record(UserModel $user, array $slugs, Request $request): void`) — dla każdego slug znajduje latest published doc i tworzy `ConsentModel` z IP+UA. Wywołanie: `app(RecordConsents::class)->record(...)` (NIE `__invoke`, NIE wstrzykiwana w parametry). Reuse w `RegisterController::store` i `AuthCompleteController::store`.
- **DOB validation** w `RegisterRequest` / `AuthCompleteRequest`:
  - `birthdate: required|date|before:'.now()->subYears(13)->toDateString()`.
  - `accepted_terms: accepted` + `accepted_privacy: accepted` (osobne checkboxy, granular consent zgodnie z RODO).
  - `accepted_parental: Rule::when($this->requiresParentalConsent(), ['accepted'])` — gdzie helper liczy wiek z DOB przez Carbon (`Carbon::parse($dob)->diffInYears(now())`) i zwraca true dla 13–15 lat.
- **OAuth completion flow**: `SocialAuthController::callback` po `Auth::login` sprawdza `$user->birthdate === null` → redirect `/onboarding` (route name `auth.complete`, NIE `/auth/complete` bo by kolidowało z `/auth/{provider}` enum binding → 404). `AuthCompleteController::show` redirectuje home jeśli birthdate już ustawione (idempotent).
- **Middleware `EnsureLatestConsents`** (shell w Fazie 1, full enforce w Fazie 5) — przyjmuje `string ...$slugs`, sprawdza czy auth user ma `Consent` na bieżącą wersję każdego doc, brak → redirect `/legal/{slug}`. **Niezarejestrowany w `bootstrap/app.php`** dopóki nie potrzebny.
- **Filament**: `LegalDocumentResource` (CRUD, `MarkdownEditor` na content, DateTimePicker na `published_at`, `defaultGroup('slug')`); `ConsentResource` read-only (`canCreate: false`, brak Edit/Create pages).
- **Email NULLABLE** (`make_users_email_nullable` migration) — Postgres pozwala wiele NULL przy UNIQUE constraint (`NULL ≠ NULL`), constraint nie wymaga drop. Wymagane przez ghost user (Faza 3) ale zmigrowane już w Fazie 1.

### Discovery (kategorie, tagi, search, ranking, profil postaci)

- **Spatie Tags** (`spatie/laravel-tags` ^4.11) — kategorie i tagi w jednym mechanizmie z polem `type`. Kategoria = `type='category'`, tag = `type='tag'`. Adminem zarządza w Filamencie (`TagResource`), user nie tworzy.
- **Migracja `tags`/`taggables` ZMODYFIKOWANA z opublikowanej**: (1) `taggable_id` jako `string` zamiast BigInt (CharacterModel ma ULID — analogicznie jak `mediable_tables`), (2) `name` i `slug` jako `jsonb` zamiast `json` (Postgres `json` nie ma equality operatora → DISTINCT na tabeli z JSON kolumnami pada `could not identify an equality operator for type json`; jsonb wspiera `=`). Bez tej zmiany Filament `Select::multiple()` na relacji tags rzuca SQL error.
- **`CharacterModel`** — `use Spatie\Tags\HasTags`. Plus własne relacje `categories()` i `freeTags()` (filtrowane po `type` na bazowej `tags()` MorphToMany — `$this->tags()->where('type', 'category')`). NIE używamy `tagsWithType()` Spatie (zwraca Collection, nie relację — Filament Select wymaga relacji).
- **Filament `Select::make('categories')->multiple()->relationship(...)` BEZ `modifyQueryUsing`** — relacja `categories()` już filtruje po type. Duplikat `where('type','category')` daje `where "type" = category and "type" = category` (raz z relacji, raz z modyfikatora) → SQL crash.
- **Browse + search**: `GET /characters` (publiczny, `CharacterController::index`) — search ILIKE na `name`+`description` (z escape'em `%` i `_`), filter po category slug (`->whereHas('categories', fn($q) => $q->where('slug->pl', $slug))`), sort `popular` (default: `is_official desc, popularity_24h desc, created_at desc`) lub `new`. **Migracja `enable_pg_trgm_and_index_characters`** włącza `pg_trgm` extension + GIN indexy `gin_trgm_ops` na `characters.name` i `characters.description` — ILIKE z `%query%` automatycznie korzysta z GIN trigram. Plus HTMX endpoint `GET /characters/search` zwraca fragment `_grid.blade.php` (HTMX `hx-trigger="input changed delay:300ms"` na home + `/characters` index).
- **Routes order**: `/characters/{character}` MUSI być po `/characters/create`, inaczej `create` matchuje `{character}` z value 'create' i ULID binding daje 404. Plus `/characters/{character}` poza middleware `verified` (publiczny profil), ale `/chat/store` z form na profilu wciąż za auth+verified (Faza 3 zdejmie).
- **Ranking "Popularne 24h"**: `app/Character/Commands/RecalculatePopularityCommand.php` (`php artisan characters:recalc-popularity`) — cron co 5 min (`Schedule::command(...)->everyFiveMinutes()->withoutOverlapping()` w `routes/console.php`). UPDATE z subquery `COUNT(DISTINCT chats.id)` z wiadomościami w 24h. Engagement breadth (spam-resistant: jeden user = +1).
- **Auto-discovery komend** w `bootstrap/app.php`: `->withCommands([...glob(__DIR__.'/../app/*/Commands') ?: []])`. Nowa komenda w dowolnym module = działa bez ręcznej rejestracji.
- **Greeting message** (`characters.greeting` TEXT NULL): `ChatController::store` po `firstOrCreate(user_id+character_id)` — jeśli `wasRecentlyCreated AND filled($character->greeting)` → `MessageModel::create([sender_role=Character, character_id, content=greeting])`. Bez `tokens_usage`, bez `ReserveMessageQuota` (greeting jest free intro, nie konsumuje quota). AI widzi greeting w history (naturalna kontynuacja).
- **`CharacterModel::kind === CharacterKind::Dating` → 404 na publicznym profilu**: `CharacterController::show` aborts dla dating characters (Faza 5 doda osobny endpoint `/randki/{character}`). Wszystkie scope'y na home/index/search filtrują przez `regular()` scope.
- **`<x-character-card>`**: link do `/characters/{id}` (nie POST chat — chat-flow przez profil), badge "Oficjalna" gdy `is_official=true`, autor hidden gdy `is_official`, popularity counter ("X rozmów dziś") gdy `popularity_24h > 0`.
- **PHPStan + Spatie Tags**: cast enum/scalar properties wymagają `@property` docblocków (`@property CharacterKind $kind`, `@property bool $is_official`, `@property int $popularity_24h`). `tagsWithType()` zwraca Collection — nie używamy w relacji.

### Guest flow (moduły `User/` + `Auth/` + `Chat/`)

- **Ghost user pattern**: niezalogowany odwiedzający wysyłający `POST /chat` lub `POST /chat/{chat}/messages` dostaje user record z `email IS NULL`, `password IS NULL`, `birthdate IS NULL`, `email_verified_at IS NULL`, `name='Gość'`. `Auth::login()` z `remember: true`. Single source of truth `email IS NULL` (helper `UserModel::isGuest()` + scopes `guests()` / `registered()`).
- **`App\User\EnsureGhostUser`** klasa biznesowa (root modułu, metoda `forRequest(Request): UserModel`) — wywołanie: `app(EnsureGhostUser::class)->forRequest($request)`. Zwraca już zalogowanego usera lub świeżo utworzonego ghosta. **IP rate limit** 5 attempts/min (klucz `ghost:{ip}` przez `RateLimiter`) → przekroczenie rzuca `Illuminate\Http\Exceptions\ThrottleRequestsException` (Laravel domyślnie zwraca 429).
- **`/chat*` routes poza `auth+verified`** — `routes/web.php` ma chat endpointy globalnie. ChatController/MessageController wywołują `EnsureGhostUser` na `store`. `show` używa standardowego `abort_unless($chat->user_id === $request->user()?->id, 404)` — niezalogowany dostaje 404 bo nie zna ID cudzego chata. `index` redirectuje do home dla niezalogowanego.
- **`LimitType::Guest`** + gałąź w `App\Chat\GrantDailyLimits::forUser`: jeśli `$user->isGuest()` → idempotent insert pojedynczego `MessageLimitModel` z quota=5, period_start=null, priority=0. **Bez resetu** — zużyte=zużyte. `MessageLimitModel::scopeForCurrentWindow` traktuje Guest jak Package (always-in-window, bez sprawdzania `period_start`). `forAll()` w `RefreshDailyLimits` skipuje ghostów (`->registered()`).
- **Soft gate UX (modal + input lock)**: renderer `OutOfMessagesException` w `bootstrap/app.php` rozróżnia ghost vs registered (`$request->user()?->isGuest()`). HTMX response dla ghosta zwraca `htmx/guest-gate.blade.php` z dwoma OOB swap fragmentami (`<div hx-swap-oob="outerHTML:#composer">` + `<div hx-swap-oob="outerHTML:#register-gate">`). **Persist po refresh** — `ChatController::show` wylicza `$gateLocked` (limit Guest used >= quota) i `chat/show.blade.php` renderuje `_composer.blade.php` z `locked=true` zamiast formy + `_gate-modal.blade.php` z `open=true`.
- **Upgrade flow rejestracji email/password** w `RegisterController::store` (cała logika inline, REST-only — bez prywatnych helperów):
  1. `$current = $request->user()`. Jeśli `$current && $current->isGuest()` → upgrade flow:
     - Email kolizja z innym non-ghost userem → `$current->delete()` + `Auth::login($existing)` + redirect home z toastem.
     - Brak kolizji → `$current->forceFill([name, email, password=Hash::make, birthdate])->save()` + `RecordConsents` + `event(Registered)` → redirect verify.
  2. Inaczej → standardowy `UserModel::create` + login + verify.
- **`RegisterRequest`**: gdy `$user->isGuest()` → email rule **bez** `unique` (kontroler obsługuje konflikt). Name zawsze `unique:users,name` (z `->ignore($currentId)`).
- **OAuth ghost upgrade** w `SocialAuthController::callback` (inline w jednym REST-method):
  1. Email z Google istnieje na non-ghost userze → kasuj ghosta (jeśli auth) + `Auth::login(existing)` + redirect home/onboarding.
  2. Email wolny + auth ghost → `$current->forceFill([name, email, email_verified_at=now])->save()` + redirect `/onboarding` (DOB+consent).
  3. Email wolny + brak auth → standardowy create + redirect `/onboarding`. **`email_verified_at` nie jest `Fillable`** — używamy `forceFill` po `create` (mass-assignment by je odrzuciło).
- **Custom middleware `App\Auth\Middleware\RedirectIfRegistered`** (alias `guest.ghost` w `bootstrap/app.php`) — wpuszcza anon i ghostów na `/register`, `/login`, `/auth/{provider}*`. Registered (`!isGuest()`) → redirect home. `/forgot-password` i `/reset-password` zostają pod standard `guest` middleware (ghost nie ma email — bezpieczeństwo).
- **`EmailVerificationNoticeController::show` guard**: ghost na `/verify-email` → redirect `/register` (ghost nie ma email do weryfikacji).
- **GC ghostów**: `php artisan users:gc-guests --inactive-days=7` (cron daily). Query: `User::query()->guests()->where('created_at', '<', now()-N)->whereNotIn('id', recent_message_user_ids)`. Hard delete (cascade chats/messages przez FK). Proxy aktywności: `messages.created_at` zamiast `users.updated_at` (touch nie zawsze).
- **Filament UserResource** — kolumna `account_type` (Gość/Niezweryfikowany/Zweryfikowany badge) + filter (custom `query` callback, bez schemy).
- **Cashier guard nie potrzebny** — `/buy*` i `/me/billing` w grupie `verified`, ghost (no email_verified_at) automatycznie odbity.

### Filament admin (moduł `Filament/`)

- Panel `/admin`. `UserModel::canAccessPanel() → hasRole('super_admin')`.
- **Filament 5 struktura**: `app/Filament/Resources/<Plural>/{<Resource>.php, Schemas/, Tables/, Pages/}`. Auto-discover w `App\System\Providers\AdminPanelProvider`. Read-only resource = usuń `Pages/Create*` + `Pages/Edit*` + override `canCreate(): bool { return false; }`.
- **Schema API**: `Filament\Schemas\Schema $schema->components([...])`. Komponenty z `Filament\Forms\Components\*`.
- **Spatie Settings page** (custom, extends `Filament\Pages\Page`) w `app/Filament/Pages/ManageChatSettings.php`: `mount` → `form->fill`, `save` → `form->getState` → cast → `$settings->save()`. PHPStan: `@property Schema $form` docblock dla magic prop z `InteractsWithSchemas`. Klasa Settings: `App\Chat\Settings\ChatSettings`.
- **Widgety natywne** (`ChartWidget`, `StatsOverviewWidget`) w `app/Filament/Widgets/`. Query przez **`DB::table('messages')`** (nie Eloquent), bo `DB::raw('SUM(...) as total')` na Eloquent nie daje PHPStanowi typu `$row->total`. Postgres time-series: `DATE_TRUNC('day', ...)` + `to_char(..., 'YYYY-MM-DD')`.
- **Shield + Spatie**: `shield:install admin` + `shield:generate --all --panel=admin --option=policies_and_permissions`. **Pomiń `shield:setup`/`shield:super-admin`** (interactive prompts → fail w no-interaction). Super-admin bypass przez `Gate::before` w `App\System\Providers\AppServiceProvider::boot`:

  ```php
  Gate::before(fn (UserModel $user): ?bool => $user->hasRole('super_admin') ? true : null);
  ```

- **Policies w modułach**: `app/Character/Policies/CharacterPolicy.php`, `app/Chat/Policies/{Chat,Message,MessageLimit}Policy.php`, `app/User/Policies/{User,Role}Policy.php`. Auto-discovery przez nazwę modelu (`CharacterModel` → strip `Model` suffix → `Character` → `CharacterPolicy`).
- **Heroicon**: `Filament\Support\Icons\Heroicon` enum. Nie zgaduj nazw — `grep` w `vendor/filament/support/src/Icons/Heroicon.php`. Np. nie ma `OutlinedGauge`, jest `OutlinedChartBarSquare`.
- **Test Filament**: `Livewire::test(<Page>::class)->fillForm([...])->call('create'|'save')->assertHasNoFormErrors()`. Actions: `->callAction('delete')`, `->callTableAction('actionName', $record)`. Helper `loginAsAdmin(): UserModel` w `tests/Pest.php` (globalna funkcja, tworzy super_admin role + user + login). Pest closure nie zbindowuje `$this->property` dla PHPStan, używaj `auth()->login($user)` zamiast `test()->actingAs()`.

### Sentry

- Backend: `Integration::handles($exceptions)` w `bootstrap/app.php::withExceptions` (pierwsza linia, potem własne renderery). Bez DSN no-op. `traces_sample_rate` nieustawione (null) — same errors.
- Frontend: `@sentry/browser` init w `resources/js/app.js` z guardem na meta tag DSN. Meta tagi w `layouts/app.blade.php` (`sentry-dsn`, `sentry-environment`, `sentry-release`) z `config('sentry.*')`. `tracesSampleRate: 0`. Bundle +152KB gzip.

## Pliki pod nadzorem

- `composer.json` / `package.json` — zmiany przez `composer require` / `npm i` w kontenerze, nie ręczna edycja. Autoload PSR-4: `"App\\": "app/"` (płasko, bez `App\Modules\...`).
- `.env` / `.env.example` — synchronizuj strukturę.
- `bootstrap/app.php` — globalne renderery (`ValidationException`, `App\Chat\Exceptions\OutOfMessagesException`), CSRF exclude `stripe/webhook`, Sentry `Integration::handles`.
- `bootstrap/providers.php` — rejestruje `App\System\Providers\{App,AdminPanel}ServiceProvider`. Brak ServiceProviderów per moduł.
- `app/System/Providers/AppServiceProvider.php` — `Gate::before` super_admin, `Cashier::ignoreRoutes` + `useCustomerModel`, `ImageManipulator::defineVariant`, `Relation::enforceMorphMap`, `Factory::guessFactoryNamesUsing` (strip `Model` suffix).
- `app/System/Providers/AdminPanelProvider.php` — Filament panel `/admin` config + auto-discovery resources/widgets/pages.
- `app/Character/Models/CharacterModel.php` — `booted()` cascade soft delete na chats; `MediableInterface` + `Mediable`; `HasTags` (Spatie); `kind` enum cast (`CharacterKind`), `is_official` bool, `greeting`/`description`/`popularity_24h` columns; scopes `Official`/`Regular`/`Dating`; relacje `categories()`/`freeTags()` (`tags()->where('type', ...)`).
- `app/Character/Enums/CharacterKind.php` — backed enum `regular`/`dating` z `HasLabel`.
- `app/Character/Controllers/CharacterController.php` — `index` (browse + ILIKE search + category filter + sort), `search` (HTMX fragment), `show` (profil, 404 dla dating), `create`/`store` (auth+verified).
- `app/Character/Commands/RecalculatePopularityCommand.php` — `characters:recalc-popularity` (cron co 5 min, count distinct chats z wiadomościami w 24h).
- `app/Filament/Resources/Tags/` — CRUD dla Spatie Tags (name, type=category|tag, order_column).
- `database/migrations/*_create_tag_tables.php` — Spatie published, ZMODYFIKOWANE: `taggable_id` string + `name`/`slug` jsonb (zamiast json — Postgres equality dla DISTINCT).
- `database/migrations/*_enable_pg_trgm_and_index_characters.php` — `CREATE EXTENSION pg_trgm` + GIN trgm indexy na `characters.name` i `characters.description`.
- `app/Chat/Controllers/MessageController.php` — `store` (tworzenie user+empty character w transakcji).
- `app/Chat/Controllers/MessageStreamController.php` — single-action invokable, SSE stream (serce streamingu, używa `App\Chat\MessageStreamer`).
- `app/Chat/MessageStreamer.php` — klasa biznesowa: budowa historii + `AnonymousAgent` streaming.
- `app/Chat/{GrantDailyLimits,ReserveMessageQuota}.php` — atomic select+increment, premium bypass, on-demand grant. Klasy biznesowe w roocie modułu.
- `app/Chat/Settings/ChatSettings.php` — Spatie Settings (typowane pola: `defaultModel`, `historyLength`).
- `app/Chat/Exceptions/OutOfMessagesException.php` — wraz z rendererem w `bootstrap/app.php`.
- `app/Chat/Enums/{LimitType,ModelType,SenderRole}.php` — enumy domenowe.
- `app/Billing/Package.php` + `config/billing.php` + `app/Billing/Controllers/{Buy,BuySuccess,BuyCancel,StripeWebhook,BillingPortal}Controller.php` — billing flow.
- `app/Auth/SocialProvider.php` — enum OAuth providerów. Nowy provider = nowy case + `config/services.php`.
- `app/Auth/Controllers/{Register,Login,PasswordResetLink,NewPassword,EmailVerificationNotice,VerifyEmail,EmailVerificationResend,SocialAuth,AuthComplete}Controller.php` + `app/Auth/Requests/{Login,Register,AuthComplete}Request.php`. Register/Social inline upgrade-flow ghosta (bez prywatnych helperów).
- `app/Auth/Middleware/RedirectIfRegistered.php` — alias `guest.ghost` w `bootstrap/app.php`. Wpuszcza ghostów na /register i /login.
- `app/Legal/{Models/{LegalDocumentModel,ConsentModel},Enums/DocumentSlug,Controllers/LegalDocumentController,Middleware/EnsureLatestConsents,RecordConsents}.php` — moduł prawny (dokumenty + zgody + versioning + commonmark render).
- `app/User/Models/UserModel.php` — `isGuest()` + scopes `guests()`/`registered()`, `@property` docblocki dla casts. Cashier `Billable`, Spatie `HasRoles`, Filament `FilamentUser`, `MustVerifyEmail`.
- `app/User/EnsureGhostUser.php` — klasa biznesowa, `forRequest(Request): UserModel`, IP rate limit 5/min.
- `app/User/Commands/GcGuestUsersCommand.php` — `users:gc-guests --inactive-days=7` (cron daily), kasuje ghostów bez wiadomości.
- `app/User/Controllers/{Profile,Password}Controller.php` + `app/User/Requests/{ProfileUpdate,PasswordUpdate}Request.php`.
- `app/Filament/{Resources,Pages,Widgets}/` — Filament 5 struktura.
- `app/{Character,Chat,User}/Policies/` — Policies per moduł, auto-discovery przez nazwę modelu (po strip sufiksu `Model`).
- `routes/{web,console}.php` — routing + schedule (Laravel 13: bez `Kernel.php`). Routy auth w grupach `guest`/`auth`/`verified`. Importy zawsze z modułów (`use App\Chat\Controllers\MessageController;`).
- `resources/views/layouts/app.blade.php` — `hx-boost:inherited`, `#toasts` container, Sentry meta tagi.
- `resources/views/components/{alert,toast,navbar,auth-card,form-input,character-card}.blade.php` — DaisyUI komponenty, mobile-first.
- `resources/views/chat/show.blade.php` + `chat/_message.blade.php` — frontend streamingu (EventSource, `data-streaming` attr, `htmx:after:swap`).
- `resources/views/chat/{_composer,_gate-modal}.blade.php` — composer (form lub locked CTA-bar) + dialog modal dla guest soft-gate.
- `resources/views/htmx/guest-gate.blade.php` — OOB swap dla `OutOfMessagesException` ghosta (replace `#composer` + `#register-gate`).
- `resources/css/app.css` / `resources/js/app.js` — Tailwind + DaisyUI + HTMX + Sentry browser wire-up.
- `Dockerfile` / `Dockerfile.dev` — `dunglas/frankenphp:php8.5` Debian. Rebuild gdy zmieniasz rozszerzenia PHP. Zmiana glibc wymaga `rm -rf node_modules package-lock.json && npm install` w kontenerze (Rolldown bindings).
- `Docker/supervisord.conf` — octane + queue + cron, socket w `/tmp/`. Rebuild po zmianie.
- `docker-compose.yml` — `environment:` dla service `app` dostarcza DB_* entrypointowi.
- `config/octane.php` + `public/frankenphp-worker.php` — Octane wire-up. Wykluczone z Pint.
- `config/mediable.php` — `ignore_migrations => true`, `image_optimization.enabled => false`. Wykluczone z Pint.
- `config/sentry.php` — published. Wykluczone z Pint (paczka publikuje bez `declare(strict_types)`).
- `database/migrations/2026_04_24_013132_create_mediable_tables.php` — string-based polymorphic ID (mixed ULID/int).
- `database/factories/` — klasycznie (poza modułami). `*Factory` matchowane do `*Model` przez callback w `AppServiceProvider::boot`.
- `tests/Feature/<Module>/` — testy ułożone per moduł (`Auth/`, `Billing/`, `Character/`, `Chat/`, `Filament/`, `Home/`, `Legal/`, `User/`, `System/`).
- `tests/Pest.php` — globalne helpery testowe (m.in. `loginAsAdmin(): UserModel`).
- `docs/ARCHITEKTURA.md` + `docs/ARCHITEKTURA_RULES.md` — pełna referencja architektury modularnej. Zaglądamy tu, jeśli pojawia się pytanie „gdzie to położyć?" lub „jak to nazwać?".
