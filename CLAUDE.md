# CLAUDE.md — postac.ai

Polski klon characters.ai (czat z postaciami AI). Laravel 13 / PHP 8.5 / Postgres 16 + pgvector / Filament 5 / `laravel/ai` / FrankenPHP + Octane / HTMX 4 + DaisyUI 5.

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
- **`plank/laravel-mediable`** (nie Spatie) — warianty obrazów przez Intervention v3 + GD.
- **Spatie Tags** — kategorie i tagi w jednym mechanizmie (pole `type`).

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
- **`@property` docblocki** dla casts (datetime, enum, bool, jsonb) — Larastan tego wymaga.

## Architektura modularna

Pełna referencja: **`docs/ARCHITEKTURA.md`** (uzasadnienia) + **`docs/ARCHITEKTURA_RULES.md`** (zasady, imperatyw). Tu skondensowane minimum.

`app/` zawiera **moduły domenowe** + jeden specjalny `System/`. **Brak** `Http/`, `Models/`, `Services/`, `Providers/`, `Actions/` na poziomie głównym. Namespace płasko: `App\<Module>` (NIE `App\Modules\<X>` / `App\Domain\<X>`).

### Moduły

| Moduł | Co robi |
| --- | --- |
| `Auth/` | rejestracja / login / reset / verify / social (Google OAuth + enum providerów); upgrade flow ghosta |
| `Billing/` | Cashier flow, `Package` enum (jedno źródło prawdy), Stripe webhook, billing portal |
| `Character/` | `CharacterModel` + slug + popularity recalc (cron 5 min) + Policy + Resource |
| `Chat/` | `Chat`/`Message`/`MessageLimit` + SSE streaming + limity wiadomości + `ChatSettings` |
| `Dating/` | sekcja Randki: profil 1:1 z postacią (`kind=Dating`), onboarding consent, prompt template SFW flirt |
| `Filament/` | panel admina (`/admin`): Resources / Pages / Widgets |
| `Home/` | strona główna (browse + search + ranking) |
| `Legal/` | dokumenty (`terms`/`privacy`/`dating-terms`) + zgody (`Consent`) + versioning + commonmark render |
| `Moderation/` | input/output check (provider-agnostic), NSFW + self-harm protocol, helpline, audit `SafetyEvent` |
| `Reporting/` | polymorphic zgłoszenia (message/character) + rate limit + Filament SLA-aware list |
| `User/` | `UserModel` + profil + hasło + ghost user pattern + GC ghostów + Policies |
| `System/` | framework-glue: `Providers/`, `Middleware/`, `Trait/`, `Utilities/`, generyczne enums/jobs/listeners |

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

### Wewnątrz modułu

`Models/`, `Controllers/`, `Requests/`, `Resources/`, `Observers/`, `Events/`, `Listeners/`, `Jobs/`, `Notifications/`, `Mail/`, `DTO/`, `Enums/`, `Exceptions/`, `Pipes/`, `Commands/`, `Policies/`, `Settings/` (Spatie Settings). **Klasa biznesowa leży w roocie modułu — bez podfolderu `Services/`/`Actions/`/`UseCases/`** (np. `app/Chat/MessageStreamer.php`).

### Klasy biznesowe — kiedy wydzielać

**TAK** — w roocie modułu — gdy operacja: jest wieloetapowa, zawiera regułę domenową do testowania w izolacji, integruje moduły / zewnętrzne usługi, albo obciąża kontroler.
**NIE** — zostaw w kontrolerze / na modelu — gdy to wrapper jednego `Model::create($data)`, 1–3 linie bez gałęzi, albo równie dobrze metoda na modelu.

**Klasa biznesowa NIE używa `__invoke`** — ma normalne nazwane metody (`record`, `forUser`, `reserve`, `grant`, ...). Reguła `__invoke` dotyczy **tylko single-action controllerów**.

**Brak DI w parametry/konstruktory.** Pobieramy z kontenera przez `app(Class::class)->method(...)`. Wyjątki tylko: `Request`, `FormRequest` w sygnaturach kontrolerów.

### Controllers — REST + single-action

W ~99% wyłącznie metody REST: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`. Akcja nie-REST (`cancel`, `archive`, `publish`) → **osobny kontroler** `<Resource><Action>Controller`. Single-action bez formularza → kontroler Invokable z `__invoke()`. Z formularzem → wciąż REST (`create` + `store`).

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
- ❌ Klasa biznesowa z `__invoke`.
- ❌ Wstrzykiwanie zależności w parametry metod / konstruktory (poza `Request`/`FormRequest`).

### Rejestracja — hierarchia

1. Atrybut PHP / auto-discovery (np. `#[ObservedBy(...)]`).
2. Generycznie w `bootstrap/app.php` przez `glob()` po katalogach modułów (`->withCommands(commands: glob(__DIR__.'/../app/*/Commands'))`, `->withEvents(discover: glob(__DIR__.'/../app/*/Listeners'))`).
3. Dopiero gdy się nie da → `App\System\Providers\AppServiceProvider`.

Centralni rejestratorzy: `App\System\Providers\{App,AdminPanel}ServiceProvider` (wpisane w `bootstrap/providers.php`). Brak ServiceProviderów per moduł.

## Footguny

### Octane / FrankenPHP worker mode

- App boot raz, obsługuje wiele requestów. **Zero static properties dla per-request state.** Singleton z user-scoped data → listener w `config/octane.php` resetuje między requestami. `AppServiceProvider::boot` musi być idempotent.
- Po `npm run build` → `octane:reload` (worker cache'uje Vite manifest).
- Po zmianie `.env` → `docker restart postac-ai-app-1` (octane:reload nie wystarczy, FrankenPHP cache env w master process).
- Po zmianie `routes/web.php` → `octane:reload`. Testy bootują świeżo.

### HTMX 4

API mocno różni się od v2/v3 — **nie ufaj intuicji z LLMa** (trening głównie na v1/v2). Reference: https://four.htmx.org/docs/get-started/migration.

- **Inheritance jest explicit**: `hx-boost`, `hx-target`, `hx-swap` nie spływają w dół. Trzeba `:inherited` (np. `hx-boost:inherited="true"` na `<body>`).
- **4xx/5xx swapują domyślnie** (tylko 204/304 nie). Serwer przy błędzie MUSI zwracać użyteczny HTML fragment.
- **Eventy z dwukropkami**: `htmx:before:request`, `htmx:after:request`, `htmx:after:swap`, `htmx:finally:request`. `htmx:beforeRequest` itd. nie istnieje.
- **`htmx:after:request` fires PRZED swapem DOM**. Dla `querySelector` na nowo wstawionych elementach użyj `htmx:after:swap` (np. EventSource po stream-trigger).
- **`e.detail` to `{ ctx }`** (nie `{xhr, successful}` jak v2). Status: `e.detail?.ctx?.response?.status`. Pattern: `if (status < 200 || status >= 300) return;`.
- **`hx-disable` → `hx-ignore`** (wyłączenie processing). Stare `hx-disabled-elt` → nowe `hx-disable` (blokowanie podczas requestu).
- **`hx-delete` nie wysyła form data** — dodaj `hx-include="closest form"`.
- **OOB swap order**: main najpierw, potem OOB.

**Wzorzec postac.ai**: zero macros na Request/Response. Inline `$request->header('HX-Request') === 'true'`. Redirect: `response()->noContent()->header('HX-Location', $url)`. Globalny renderer `ValidationException` w `bootstrap/app.php`: HTMX → 422 + OOB toast + `HX-Reswap: none`. Komponenty: `<x-alert>`, `<x-toast>` (OOB do `#toasts` w layoutie).

### Streaming SSE

- `response()->stream(closure)` + `echo "data: ...\n\n"` + `if (ob_get_level() > 0) @ob_flush(); flush();`. **`ob_flush` bez aktywnego OB generuje notice** który gubi output — guard obowiązkowy.
- Headers: `Content-Type: text/event-stream`, `Cache-Control: no-cache`, `X-Accel-Buffering: no`.
- **`laravel/ai` 0.6.3**: pusty assistant content w historii → OpenRouter rzuca 400. Filtruj puste character messages przy budowaniu historii.
- **Test streaming**: `AnonymousAgent::fake([...])` przed requestem. Sprawdzaj DB update zamiast capture body — test's `ob_start` nie łapie naszego `ob_flush`. Drain stream przez `@$response->baseResponse->sendContent()`.

### DaisyUI 5 + Vite 8

```css
@import 'tailwindcss';
@plugin 'daisyui/index.js' { themes: light --default, dark --prefersdark; }
```

**Rolldown gotcha**: `@plugin 'daisyui'` (bare) wywala build — Rolldown resolvuje przez `browser` field paczki (wskazuje `.css` plik → Node ESM loader pada). **Jawny path `daisyui/index.js`** wymagany. Nie zmieniaj bez testu builda.

### Postgres / migracje / persistence

- **Polimorficzny ID jako string** (NIE bigint) — User=int, Character=ULID. Dla `mediable_tables` i `taggables` ręcznie edytujemy opublikowane migracje na `string/string`. `config('mediable.ignore_migrations') => true`.
- **Spatie Tags**: `name`/`slug` jako `jsonb` (NIE `json`). Postgres `json` nie ma equality operatora → DISTINCT pada `could not identify an equality operator for type json`. Bez tej zmiany Filament `Select::multiple()` na relacji tags rzuca SQL error.
- **Morph map** w `AppServiceProvider::boot` (`Relation::enforceMorphMap`): aliasy `user`, `character`, `chat`, `message`, `message_limit`, `report`, `safety_event` → bez sufiksu `Model`. DB rows nie wiedzą o nazewnictwie klas PHP.
- **Factory naming**: `Factory::guessFactoryNamesUsing` strips `Model` suffix → `CharacterModel` → `Database\Factories\CharacterFactory`. Factories zostają w `database/factories/` klasycznie.
- **Race-safe `firstOrCreate`**: partial unique index `WHERE deleted_at IS NULL` (np. `chats(user_id, character_id)`).
- **Cascade soft delete**: w `*Model::booted()` cascade na relacje + `static::restoring` przywraca trashed. Guard `isForceDeleting()`.
- **Email NULLABLE**: Postgres pozwala wiele NULL przy UNIQUE (`NULL ≠ NULL`) — wymagane przez ghost user pattern.
- **`protected $attributes`** dla defaultów które muszą być widoczne **w eventach** (`static::saving`/`creating`). DB defaulty aktywują się dopiero przy INSERT, więc event ich nie widzi (np. `kind=regular` w `CharacterModel` dla slug auto-gen).

### Authz / Spatie Permission

- **Super-admin bypass** przez `Gate::before` w `AppServiceProvider::boot`: `Gate::before(fn (UserModel $user) => $user->hasRole('super_admin') ? true : null);`. Zero hardcoded emaili.
- **Policies w modułach**: auto-discovery przez nazwę modelu (`CharacterModel` → strip `Model` → `CharacterPolicy`).
- **Authz user-facing**: `abort_unless($x->user_id === auth()->id(), 404)` inline. Policy tylko dla admin/Filament.
- **Shield install**: `shield:install admin` + `shield:generate --all --panel=admin --option=policies_and_permissions`. **Pomiń `shield:setup`/`shield:super-admin`** (interactive prompts → fail w no-interaction).

### Filament 5

- **Schema API**: `Filament\Schemas\Schema $schema->components([...])`. Komponenty z `Filament\Forms\Components\*`.
- Struktura: `app/Filament/Resources/<Plural>/{<Resource>.php, Schemas/, Tables/, Pages/}`. Read-only resource = usuń `Pages/Create*` + `Pages/Edit*` + override `canCreate(): bool { return false; }`.
- **Heroicon**: `Filament\Support\Icons\Heroicon` enum. Nie zgaduj nazw — `grep` w `vendor/filament/support/src/Icons/Heroicon.php` (np. nie ma `OutlinedGauge`, jest `OutlinedChartBarSquare`).
- **Bulk action test**: `Livewire::test(<Page>)->callTableBulkAction('promote', [$id1, $id2])`. NIE `callAction` z `records`.
- **Settings page**: custom extends `Filament\Pages\Page`, `mount` → `form->fill`, `save` → `form->getState` → cast → `$settings->save()`. PHPStan: `@property Schema $form` docblock.
- **Widgety natywne** (`ChartWidget`, `StatsOverviewWidget`): query przez **`DB::table('messages')`** (nie Eloquent), bo `DB::raw('SUM(...) as total')` na Eloquent nie daje PHPStanowi typu `$row->total`. Postgres time-series: `DATE_TRUNC('day', ...)` + `to_char(..., 'YYYY-MM-DD')`.

### Cashier / Stripe

- **`Cashier::ignoreRoutes()` + `Cashier::useCustomerModel(UserModel::class)`** w `AppServiceProvider::register()`. Bez `useCustomerModel` Cashier szuka `App\Models\User`.
- **CSRF exclude**: `validateCsrfTokens(except: ['stripe/webhook'])` w `bootstrap/app.php`.
- **`.env.testing` potrzebuje placeholderów `STRIPE_PRICE_*`** (`price_test_five` itd.) — `config('billing.prices.X')` zwraca null bez nich.
- **PHPStan + Cashier**: parent `getUserByStripeId` zwraca `Billable|null` (trait, nie klasa) → `class.notFound`. Override w naszej klasie zwracający `?UserModel` z inline `/** @var UserModel|null */` docblock cast.
- **Webhook test**: anonymous subclass `StripeWebhookController` z override'em `protected lineItemsForSession(string): array<string>` — bez mockowania static `Cashier::stripe()`.
- **Stripe CLI dev**: `stripe listen --forward-to localhost:43080/stripe/webhook` daje `whsec_` → wkleić do `.env`. Test cards: `4242 4242 4242 4242` sukces, `4000 0000 0000 0002` decline.

### Test patterns

- **`loginAsAdmin(): UserModel`** w `tests/Pest.php` — globalna funkcja, tworzy `super_admin` role + user + login.
- **Pest closure** nie zbindowuje `$this->property` dla PHPStan — używaj `auth()->login($user)` zamiast `test()->actingAs()`.
- **Bind w testach**: `app()->bind(...)` (NIE `$this->app->bind` — protected w Pest closure). Np. `app()->bind(ModerationProvider::class, fn () => new FakeModerationProvider(flagged: true, ...))`.
- **Atomicity test limitów**: `config()->set('premium.daily', [])` żeby on-demand grant nie re-seedował preset modeli.

### Sentry

- Backend: `Integration::handles($exceptions)` w `bootstrap/app.php::withExceptions` (pierwsza linia, potem własne renderery). Bez DSN no-op. `traces_sample_rate` nieustawione — same errors.
- Frontend: `@sentry/browser` init w `resources/js/app.js` z guardem na meta tag DSN. `tracesSampleRate: 0`. Bundle +152KB gzip.

## Pliki pod nadzorem

Tylko te które wymagają uwagi przed edycją (nie pełna lista plików projektu — od tego jest `ls`/grep).

- `composer.json` / `package.json` — zmiany przez `composer require` / `npm i` w kontenerze, nie ręczna edycja. Autoload PSR-4: `"App\\": "app/"` (płasko, bez `App\Modules\...`).
- `.env` / `.env.example` — synchronizuj strukturę.
- `bootstrap/app.php` — globalne renderery (`ValidationException`, `App\Chat\Exceptions\OutOfMessagesException`, `App\Moderation\Exceptions\ContentBlockedException`), CSRF exclude `stripe/webhook`, alias `guest.ghost`, Sentry `Integration::handles`, auto-discovery przez `glob()`.
- `bootstrap/providers.php` — tylko `App\System\Providers\{App,AdminPanel}ServiceProvider`. Brak ServiceProviderów per moduł.
- `app/System/Providers/AppServiceProvider.php` — centralny: `Gate::before` super_admin, `Cashier::ignoreRoutes` + `useCustomerModel`, `bind(ModerationProvider::class)` przez `match` na `config('moderation.default')`, `ImageManipulator::defineVariant`, `Relation::enforceMorphMap`, `Factory::guessFactoryNamesUsing`.
- `app/System/Providers/AdminPanelProvider.php` — Filament panel `/admin` config + auto-discovery resources/widgets/pages.
- `Dockerfile` / `Dockerfile.dev` / `Docker/supervisord.conf` — `dunglas/frankenphp:php8.5` Debian. Rebuild gdy zmieniasz rozszerzenia PHP. Zmiana glibc wymaga `rm -rf node_modules package-lock.json && npm install` w kontenerze (Rolldown bindings).
- `docker-compose.yml` — `environment:` dla service `app` dostarcza DB_* entrypointowi.
- `config/octane.php` + `public/frankenphp-worker.php` — Octane wire-up. Wykluczone z Pint.
- `config/mediable.php` — `ignore_migrations => true`, `image_optimization.enabled => false`. Wykluczone z Pint.
- `config/sentry.php` — published. Wykluczone z Pint (paczka publikuje bez `declare(strict_types)`).
- `database/migrations/*_create_mediable_tables.php` — string-based polymorphic ID (mixed ULID/int).
- `database/migrations/*_create_tag_tables.php` — Spatie published, ZMODYFIKOWANE: `taggable_id` string + `name`/`slug` jsonb (zamiast json).
- `database/migrations/*_enable_pg_trgm_and_index_characters.php` — `CREATE EXTENSION pg_trgm` + GIN trgm indexy na `characters.name`/`description`.
- `tests/Pest.php` — globalne helpery (`loginAsAdmin()`).
- `docs/ARCHITEKTURA.md` + `docs/ARCHITEKTURA_RULES.md` — pełna referencja architektury. Zaglądamy tu, jeśli pojawia się pytanie „gdzie to położyć?" lub „jak to nazwać?".
