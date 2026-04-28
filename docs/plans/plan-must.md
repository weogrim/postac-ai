# plan-must — postac.ai launch-blocking MVP

## Context

Po niedawnym refactorze modularnym repo ma czystą strukturę `app/<Module>/`, ale jeszcze nie ma większości MUST-have features z `docs/PRD.md`. Audit pokazał: 8 z 11 obszarów MUST = zero kodu, 2 częściowo, tylko `MessageLimit` framework działa (dla auth users).

Plan jest **niezależny i samowystarczalny** — można go realizować bez znajomości plan-should/plan-could. Każda z 6 faz to osobny milestone deploy-walny w izolacji. Foundation-first: zaczynamy od schema/legal/age (warunki konieczne dla rejestracji + reszty), potem discovery (żeby home page wyglądał jak produkt), potem guest flow (USP — zero-friction entry), potem moderation+reporting (legal cover dla content), potem Randki, na końcu catalog seed (czysty content).

PRD nie jest ostatecznym wyznacznikiem — odchylenia od PRD są zaznaczone w sekcji **Decyzje strategiczne**.

## Decyzje strategiczne (zatwierdzone)

| Temat | Decyzja | Odchylenie od PRD |
|---|---|---|
| Billing | Premium subscription + paczki one-off (130/270/400 wiadomości). Zostajemy z obecnym `App\Billing\Package` enum. | PRD chciał tylko Premium 19.99 PLN/msc |
| OAuth providers | email + Google. Bez Apple. | PRD chciał Apple |
| Czat bez rejestracji | **Ghost user pattern**: pierwsza wysłana wiadomość przez niezalogowanego → tworzymy `users` rekord z `email IS NULL`, `password IS NULL`. `Auth::login()`. Cała reszta kodu (ChatController, ReserveMessageQuota, Cashier guards) działa po `auth()->user()` bez gałęzi. | PRD nie precyzował implementacji |
| Identyfikacja guesta | `email IS NULL` (single source of truth). Brak osobnej kolumny `is_guest`. Helper `UserModel::isGuest(): bool` + scope `scopeGuests()`. | — |
| Próg wieku | 13+ (deklaracja DOB przy rejestracji). Treści ToS / Privacy / Dating-Terms wpisuje admin w Filamencie — kod tylko trzyma model dokumentu i zgodę. | — |
| Moderacja | Agnostyczny interface `App\Moderation\ModerationProvider` z implementacjami `OpenAiModerationProvider` (default) + `NoOpProvider` (testy). Perspective API dopisany później przez dodanie kolejnego providera, bez refactora callsites. | — |
| Sekcja Randki | Osobny moduł `app/Dating/` + `DatingProfileModel` 1:1 z `CharacterModel` (FK `character_id` PK). `CharacterModel` dostaje enum `kind: regular\|dating`. Reuse całej infrastruktury chat/streaming/limits. | — |
| Tagi i kategorie | `spatie/laravel-tags` z polem `type`. Kategoria = tag z `type=category`, tag = tag z `type=tag`. Adminem zarządza w Filamencie (TagResource + filtry per type). User nie tworzy. | PRD nie precyzował |
| Predefined catalog | **Plan obejmuje tylko infrastrukturę**: kolumna `is_official` (bool) + `author_id` (nullable FK na users). 30+ promptów wpisuje admin ręcznie w Filamencie — to nie scope kodu. | PRD chciał seedy |
| Konflikt ghost vs istniejące konto | Przy rejestracji email/Google jeśli email istnieje → priorytet ma istniejące konto, ghost record + jego chats kasujemy hard. Bez merge. | — |
| Self-harm protocol | Detekcja przez moderation provider (kategoria `self-harm`) → AI wychodzi z roli, wyświetla numery zaufania (116 111, 800 70 2222), rate limit 3 wiadomości/5 min na chacie. | PRD wymóg |
| Legal versioning | Od razu z wersjonowaniem. `LegalDocument` ma `version` int, `Consent` wskazuje konkretną wersję, nowa wersja → modal "Zaakceptuj nowy regulamin". | — |

## Nowe moduły

```
app/
├── Dating/          # nowy: profile randkowe
│   ├── Controllers/
│   ├── Models/      # DatingProfileModel
│   └── Enums/       # CharacterKind
├── Legal/           # nowy: dokumenty + zgody
│   ├── Controllers/
│   ├── Models/      # LegalDocumentModel, ConsentModel
│   └── Enums/       # DocumentSlug
├── Moderation/      # nowy: NSFW + safety filter
│   ├── Contracts/   # ModerationProvider interface
│   ├── Providers/   # OpenAiModerationProvider, NoOpProvider
│   ├── DTO/         # ModerationResult
│   └── Exceptions/  # ContentBlockedException
└── Reporting/       # nowy: zgłaszanie treści
    ├── Controllers/
    ├── Models/      # ReportModel
    └── Enums/       # ReportReason, ReportStatus
```

Plus rozbudowa istniejących: `Auth/` (DOB, consent), `Character/` (kind, is_official, greeting, popularity), `Chat/` (LimitType::Guest, gc command), `User/` (isGuest helper, GC scope), `Filament/` (resources dla tagów, dokumentów, raportów).

---

## ✅ Faza 1 — Foundation: Legal + Age + Schema fixes — DONE (2026-04-27)

**Status:** zamknięta. Pint + PHPStan + Pest 134/344 zielone na zamknięciu.

**Co poszło zgodnie z planem:**
- 5 migracji (`add_birthdate_to_users`, `make_users_email_nullable`, `extend_characters_table`, `create_legal_documents_table`, `create_consents_table`).
- Modele `LegalDocumentModel`, `ConsentModel` + enumy `DocumentSlug` (terms/privacy/dating-terms), `CharacterKind` (regular/dating, w `app/Character/Enums/`).
- `CharacterModel` rozbudowany: scopes `Official`/`Regular`/`Dating`, casty `kind`/`is_official`/`popularity_24h`, fillable `description`/`greeting`.
- `UserModel` rozbudowany: `birthdate` fillable + `date` cast, relacja `consents()`, factory dorzuca random 18+ DOB w default state.
- Auth: `RegisterRequest` + `AuthCompleteRequest` walidują DOB (`before:13 years ago`) + 2 osobne consents + warunkowy parental dla 13–15 (helper `requiresParentalConsent()` z Carbon). `RegisterController::store` i `AuthCompleteController::store` używają `app(RecordConsents::class)->record(...)`. `SocialAuthController::callback` redirectuje świeżego usera na `/onboarding` (route name `auth.complete`) gdy `birthdate IS NULL`.
- Klasa biznesowa `App\Legal\RecordConsents` (metoda `record(UserModel, list<DocumentSlug>, Request)`, NIE `__invoke`).
- Publiczny widok `GET /legal/{slug}` (implicit enum binding) → `LegalDocumentController::show` → CommonMark render z `html_input => escape` (XSS guard).
- Filament: `LegalDocumentResource` (CRUD, `MarkdownEditor`, custom action `duplicate` tworzy version+1 draft), `ConsentResource` (read-only audit trail, `canCreate: false`).
- Middleware shell `EnsureLatestConsents` w `app/Legal/Middleware/` (niezarejestrowany — pełne enforce w Fazie 5).
- Morph map zaktualizowana o `legal_document` i `consent`.
- `tests/Pest.php` — global helper `loginAsAdmin(): UserModel` (przeniesiony z CharacterResourceTest dla deduplikacji w nadchodzących fazach).
- `composer require league/commonmark` (^2.8) — markdown rendering.
- CLAUDE.md zaktualizowany: nowa sekcja "Legal", reguła "klasy biznesowe nie używają `__invoke`, brak DI w parametry poza Request/FormRequest", anty-wzorce, plus rozbudowy w sekcji Character/Auth/Pliki pod nadzorem.

**Nowe testy (23):** RegisterTest +7 (DOB <13, parental 13–15, consents recorded, brak terms/privacy → fail), SocialAuthTest +1 (existing user bez DOB → onboarding redirect), AuthCompleteTest +7, LegalDocumentTest +5, LegalDocumentResourceTest +4 (Filament CRUD + duplicate action).

**Świadome odejścia od planu:**
- Brak nowej kolumny `author_id` na characters — istniejący `user_id` z relacją `author()` to ta sama informacja. Dla `is_official=true` UI ukrywa autora w Fazie 6.
- `CharacterKind` w `app/Character/Enums/`, nie w `app/Dating/Enums/` (zgodne z faktem że to property `CharacterModel`).
- Route OAuth completion `/onboarding` zamiast `/auth/complete` — kolizja z `/auth/{provider}` enum binding.
- `RecordConsents` jako klasa z metodą `record()`, NIE invokable — zgodnie z user feedback w trakcie Fazy 1 (memory: `feedback_no_di_no_invoke_business.md`).

**Cel:** Fundament prawny i pola schemy bez których dalsze fazy się nie ruszą. Po tej fazie rejestracja jest legally clean (DOB + consent), ale UX jest jeszcze stary (bez guesta, bez kategorii).

### 1.1 Migracje schemy

- `add_birthdate_to_users_table` — `users.birthdate DATE NULL`. Backfill istniejących userów: NULL (admin może dopisać). Walidacja w aplikacji.
- `make_users_email_nullable` — `users.email NULLABLE` (UNIQUE constraint zostaje — Postgres pozwala wiele NULL przy UNIQUE). Wymagane przez ghost user (Faza 3) ale lepiej zmigrować teraz w Foundation.
- `add_kind_to_characters_table` — `characters.kind` enum-string (`regular`, `dating`) default `regular`. Plus `characters.is_official` BOOL default false. Plus `characters.greeting` TEXT NULL. Plus `characters.popularity_24h` INT default 0 (do rankingu z Fazy 2). Plus `characters.author_id` BIGINT NULL FK do `users` cascade null.
  - W `CharacterModel::booted()` dopisać invariant: `is_official=true ⟹ author_id` może być null (admin nie musi mieć imienia).
- `create_legal_documents_table` — `id`, `slug` (unique enum: `terms`, `privacy`, `dating-terms`), `version` int, `title` string, `content` text (markdown), `published_at` datetime, `created_at`, `updated_at`. Composite unique `(slug, version)`.
- `create_consents_table` — `id`, `user_id` FK cascade, `legal_document_id` FK restrict, `accepted_at` datetime, `ip_address` string nullable, `user_agent` string nullable. Index `(user_id, legal_document_id)`.

### 1.2 Models + enums

- `app/Legal/Models/LegalDocumentModel.php` — `Mediable`-friendly nie potrzebne (markdown w bazie). Cast `published_at => datetime`.
- `app/Legal/Models/ConsentModel.php` — relacje `user()`, `document()`. Cast `accepted_at => datetime`.
- `app/Legal/Enums/DocumentSlug.php` — backed enum `terms`, `privacy`, `dating-terms` z labelem (`label(): string`) po polsku.
- `app/Dating/Enums/CharacterKind.php` — backed enum `regular`, `dating` z labelem.
- `app/Character/Models/CharacterModel.php` — dodać `kind` cast na `CharacterKind::class`, `is_official` cast bool, `greeting` w `$fillable`/`$casts`. Dodać scopes: `scopeOfficial()`, `scopeRegular()`, `scopeDating()`.

### 1.3 Auth — DOB + consent

- `app/Auth/Requests/RegisterRequest.php` — dodać:
  - `birthdate: required|date|before:13 years ago` (custom rule lub `before:`-z-Carbon).
  - `accepted_terms: accepted` (checkbox required).
  - `accepted_privacy: accepted`.
  - Dla 13-15 letnich: `accepted_parental: accepted` (warunkowy `required_if`-na podstawie wyliczenia wieku z birthdate).
- `app/Auth/Controllers/RegisterController.php` — w `store()`:
  - Po `User::create([...])` — `ConsentModel::create()` dla każdego accepted document w bieżącej wersji (`LegalDocument::query()->where('slug', 'terms')->orderByDesc('version')->first()`).
  - Zapisać `birthdate` na user record.
- `app/Auth/Controllers/SocialAuthController.php::callback` — analogicznie:
  - Jeśli `firstOrCreate` zwrócił recently_created=true → wymagamy DOB+consent **przed** zwróceniem do home. Redirect na `/auth/complete` (nowy route+view: form z DOB + 2 checkboxy).
  - Jeśli existing → standardowy login.
- `resources/views/auth/register.blade.php` — dodać input `<input type="date" name="birthdate">` + checkboxy z linkami do `/legal/terms` i `/legal/privacy` (open w new tab). Komunikaty walidacji.
- `resources/views/auth/complete.blade.php` — nowy widok dla post-OAuth completion (DOB + checkboxy).
- Route: `POST /auth/complete` w grupie `auth` (ale BEZ `verified`, bo OAuth może zwrócić user z verified email a brak DOB).

### 1.4 Legal — publiczny widok dokumentów

- Route: `GET /legal/{slug}` (parametr binding na `DocumentSlug` enum) → `App\Legal\Controllers\LegalDocumentController::show`. Kontroler bierze najnowszą opublikowaną wersję dokumentu, parsuje markdown (`league/commonmark` jeśli nie ma — sprawdzić `composer.json`; jeśli brak, dodać) i zwraca widok.
- Widok `resources/views/legal/show.blade.php` — proste centered-prose, link do poprzednich wersji opcjonalnie (dla revsion history compliance).

### 1.5 Filament — Legal management

- `app/Filament/Resources/LegalDocuments/` — pełen CRUD dla dokumentów. Form: slug (select z enum), version (auto-increment hint), title, content (Markdown editor — Filament 5 ma `MarkdownEditor`).
- `app/Filament/Resources/Consents/` — read-only, lista zgód userów (filtrowanie po slug, version, user). Komponent dla audit trail.
- Po opublikowaniu nowej wersji dokumentu: NIE invalidate-ujemy istniejących `Consent` rekordów (zostają jako historia). Zamiast tego — middleware `EnsureLatestConsents` (Faza 1 dorzuca w basic form, full enforce w Faza 5):
  - Sprawdza czy auth user ma consent na bieżącą wersję każdego MUST document (`terms`, `privacy`).
  - Brak → redirect na `/legal/accept` z modalem "Regulamin został zaktualizowany".

### 1.6 Update CLAUDE.md

- Po Fazie 1 dopisać do CLAUDE.md sekcję `### Legal` w "Architektura — szczegóły techniczne" z opisem flow consents + middleware + DocumentSlug enum.

### Definition of Done — Faza 1

- [x] Nowy user może się zarejestrować z DOB + checkboxami.
- [x] User <13 dostaje błąd walidacji (po polsku).
- [x] Admin może w Filamencie dodać/edytować dokumenty `terms`, `privacy`.
- [x] `GET /legal/terms` renderuje markdown.
- [x] OAuth user (Google) trafia na `/onboarding` jeśli świeżo utworzony.
- [x] `php artisan migrate` przechodzi clean, `php artisan test` zielony (134/344).
- [x] PHPStan zielony.

---

## ✅ Faza 2 — Discovery: kategorie, tagi, search, ranking, profil postaci, greeting — DONE (2026-04-27)

**Status:** zamknięta. Pint + PHPStan + Pest 154/396 zielone na zamknięciu.

**Co poszło zgodnie z planem:**
- Refactor `App\Chat\ReserveMessageQuota`: zniknął `__construct` z DI i `__invoke`, zastąpione metodą `reserve(UserModel): ModelType` + `app(ReserveMessageQuota::class)->reserve($user)`. Update `MessageController` + 8 testów (`ReserveMessageQuotaTest`, `StripeWebhookTest`).
- `composer require spatie/laravel-tags ^4.11` + opublikowana migracja `create_tag_tables` zmodyfikowana: `taggable_id` jako string (CharacterModel ULID), `name`/`slug` jako `jsonb` (Postgres `json` nie ma `=` dla DISTINCT).
- `CharacterModel use HasTags` + relacje `categories()` i `freeTags()` (filtrowane `tags()->where('type', ...)` jako `MorphToMany`, NIE `tagsWithType()` które zwraca Collection).
- Filament `TagResource` (CRUD: name, type select, order_column, defaultGroup po type) + Filament Resource w `app/Filament/Resources/Tags/`.
- `CharacterForm` rozszerzony: `kind` (CharacterKind enum), `is_official` (Toggle), `description`/`greeting` (Textarea), multi-select `categories` (max 3) + `freeTags`.
- Migracja `enable_pg_trgm_and_index_characters` — `CREATE EXTENSION pg_trgm` + GIN `gin_trgm_ops` index na `characters.name` i `characters.description`.
- `CharacterController` rozbudowany: `index` (search ILIKE, filter category slug, sort popular/new), `search` (HTMX endpoint), `show` (profil postaci, 404 dla `kind=dating`). Plus `buildBrowseQuery()` private helper (escape `%`/`_` w search).
- Routes: `/characters` (publiczny), `/characters/search` (HTMX), `/characters/{character}` (publiczny show, kolejność po `/characters/create`).
- `app/Character/Commands/RecalculatePopularityCommand.php` (`characters:recalc-popularity`) + cron `everyFiveMinutes()->withoutOverlapping()` w `routes/console.php`. UPDATE z subquery `COUNT(DISTINCT chats.id)` z wiadomościami w 24h.
- Auto-discovery komend w `bootstrap/app.php`: `->withCommands([...glob(__DIR__.'/../app/*/Commands') ?: []])`.
- `HomeController` rewrite: 6 popular (sortowane is_official + popularity_24h), lista kategorii, 6 latest. `home.blade.php` zgodne z PRD §1.1: heading "Z kim chcesz porozmawiać?", search bar HTMX live, sekcje "Teraz popularne"/"Kategorie" pills/"Nowe i ciekawe", CTA "Stwórz postać" dla auth.
- `<x-character-card>` rewrite: link do `/characters/{id}` (nie POST chat), badge "Oficjalna" gdy `is_official=true`, autor ukryty gdy `is_official`, popularity counter.
- `characters/{index,_grid,show}.blade.php` — browse + HTMX grid + profil postaci (avatar 192px, nazwa, badge "Oficjalna", description, kategorie+tagi badges, statystyki, CTA "Rozpocznij rozmowę", disclaimer historyczny dla `is_official`).
- `ChatController::store` — przy `wasRecentlyCreated AND filled($character->greeting)` tworzy `MessageModel` z greeting jako pierwsza wiadomość AI (bez quota, bez tokens_usage).
- `@property` docblocks na `CharacterModel` (kind/is_official/popularity_24h/etc.) — Larastan wymaga dla cast'ów.
- CLAUDE.md: nowa sekcja "Discovery" z patternami search/ranking/greeting/Spatie Tags pułapki, plus rozbudowy w "Pliki pod nadzorem".

**Nowe testy (20):** `CharacterBrowseTest` +11 (index, search ILIKE name+description, hide dating, filter category, sort popular/new, search HTMX endpoint, show profil, 404 dla dating, badge oficjalna), `GreetingMessageTest` +3 (greeting jako first message, brak gdy null, brak duplikatu na ponowny store), `RecalculatePopularityTest` +2 (count distinct chats w 24h, ignore >24h), `TagResourceTest` +4 (Filament CRUD).

**Świadome odejścia od planu:**
- Postgres migracja używa `jsonb` zamiast `json` w Spatie tag tables (workaround dla `DISTINCT` na JSON columns w Filament `Select::multiple()->relationship()`).
- Relacje `categories()`/`freeTags()` używają `tags()->where('type', ...)` zwracające `MorphToMany`, NIE Spatie `tagsWithType()` które zwraca Collection (Filament `Select` wymaga MorphToMany).
- W Filament `Select` BEZ `modifyQueryUsing` — relacja sama filtruje po type, dodatkowy `where` daje duplikat constraintu i SQL crash.

**Cel:** Strona główna staje się produktem (PRD §1.1). Kategorie + tagi + search + ranking + karta profilu postaci + greeting przy starcie chatu.

### 2.1 Spatie Tags integration

- `composer require spatie/laravel-tags`.
- Publish migracje + opublikowany `tags` + `taggables` table. **Czytaj przed migrate** — zmiana polymorphic ID na string może być potrzebna (CharacterModel ma ULID).
  - `config('tags.taggable.morph_type')` zostaje string by default; sprawdzić czy `taggables.taggable_id` jest `unsignedBigInteger` czy `string`. Jeśli BigInt → zmienić w opublikowanej migracji na `string` (pattern jak w `mediable_tables` migration).
- `app/Character/Models/CharacterModel.php` — dodać `use Spatie\Tags\HasTags`.
- Skonfigurować typy tagów: w aplikacji używamy types `category` i `tag` (luźny). Helpery na CharacterModel:
  - `categories(): BelongsToMany` (filter na `tagsWithType('category')`).
  - `freeTags(): BelongsToMany` (`tagsWithType('tag')`).

### 2.2 Filament — TagResource

- `app/Filament/Resources/Tags/` — CRUD dla tagów Spatie.
  - Form: name (translatable jeśli włączone — domyślnie OFF, zostawiamy single locale `pl`), type (select: `category` / `tag`), slug (auto z name), order_column (drag-and-drop).
  - Table: filter po `type`.
  - Bulk action: "Przenieś do kategorii" / "Przenieś do tagów" (zmiana type).
- `app/Filament/Resources/Characters/Schemas/CharacterForm.php` — w formie postaci dodać:
  - Multi-select `categories` (tagi z type=category, max 3).
  - Multi-select `tags` (tagi z type=tag, freely creatable jeśli admin chce).

### 2.3 Search + ranking — backend

- `app/Character/Controllers/CharacterController.php` — dodać metodę `index()` (publiczna lista) z parametrami query: `q` (search), `category` (slug), `sort` (popular/new). Bez auth/verified.
- Routes:
  - `GET /characters` → `CharacterController::index` (browseable list).
  - `GET /characters/{character}` → `CharacterController::show` (profil postaci).
  - `GET /characters/search` → HTMX endpoint dla live search z home page (zwraca fragment HTMLa z grid kart).
- Search implementation: Postgres `ilike` na `name` + `prompt` z `WHERE kind='regular' AND deleted_at IS NULL`. Bez FTS na MVP (overkill dla <1k postaci). Index na `name` + GIN trigram (`pg_trgm` extension):
  - Migracja `enable_pg_trgm_and_index_characters` — `CREATE EXTENSION IF NOT EXISTS pg_trgm; CREATE INDEX characters_name_trgm_idx ON characters USING GIN (name gin_trgm_ops);`.

### 2.4 Ranking "Popularne 24h"

- Postgres-side count nie jest tani per request — zamiast tego cron przelicza do `characters.popularity_24h`:
  - `app/Character/Commands/RecalculatePopularityCommand.php` — `php artisan characters:recalc-popularity`. Query: `UPDATE characters c SET popularity_24h = (SELECT COUNT(DISTINCT chat_id) FROM messages m JOIN chats ch ON ch.id=m.chat_id WHERE ch.character_id=c.id AND m.created_at > NOW() - INTERVAL '24 hours');`.
  - `routes/console.php` — `Schedule::command('characters:recalc-popularity')->everyFiveMinutes()`.
- Auto-discovery komendy via `bootstrap/app.php` glob (zgodne z konwencją w CLAUDE.md). Sprawdzić czy glob jest już zarejestrowany — jeśli nie, dopisać `->withCommands(commands: glob(__DIR__.'/../app/*/Commands'))`.

### 2.5 Home page redesign

- `app/Home/Controllers/HomeController.php::index` — zwraca:
  - 6 popularnych postaci (`Character::regular()->orderByDesc('popularity_24h')->limit(6)->get()`).
  - Lista kategorii (Tags z type=category, ordered by `order_column`).
  - 6 nowych postaci (`->latest()->limit(6)`).
- `resources/views/home.blade.php` — przerobić na układ z PRD §1.1:
  - Heading + search bar (HTMX live, target #characters-grid).
  - "Teraz popularne" — 6 kart.
  - "Kategorie" — pills, klik → filter (HTMX, query params).
  - "Polskie legendy" — kategoria specjalna (jeśli admin oznaczy).
  - "Nowe i ciekawe" — 6 kart.
  - CTA "Stwórz swoją postać" (dla auth users) na dole.
- Karta postaci: avatar (square variant), nazwa, podtitle, kolor akcentu (PRD), liczba rozmów. Komponent `<x-character-card :character="$character">` w `resources/views/components/`.

### 2.6 Profil postaci

- Route: `GET /characters/{character:id}` (ULID binding).
- `CharacterController::show($character)` — abort 404 jeśli `kind=dating` (Randki mają osobny profil w Fazie 5) lub `is_official=false AND author != auth user` (private characters — przyszłość).
- Widok `resources/views/characters/show.blade.php`:
  - Hero: avatar 512×512, nazwa, podtitle.
  - "Postać AI inspirowana [imię]. To fikcyjna interpretacja, nie wierne odwzorowanie." (jeśli `is_official` — adminowska klauzula PRD §5.5).
  - Bio / opis (z `prompt` lub osobne pole `description` — DECYZJA: dodajemy `characters.description TEXT NULL` w migracji 1.1 do scope; UPDATE Foundation phase task list).
  - Kategorie + tagi (badges).
  - Statystyki: `popularity_24h`, total chats.
  - CTA "Rozpocznij rozmowę" → `POST /chat` (z `character_id`).

### 2.7 Greeting message

- Nowa kolumna `characters.greeting` (Faza 1.1, już zaplanowana).
- `app/Chat/Controllers/ChatController.php::store` — po `firstOrCreate(user_id+character_id)`:
  - Jeśli chat właśnie utworzony (`wasRecentlyCreated`) AND `character->greeting` not empty → `MessageModel::create([role=character, content=greeting, chat_id=...])`. Bez tokens_usage (greeting jest free, nie liczymy). Bez `ReserveMessageQuota` call.
- Filament CharacterForm — pole `greeting` (Textarea, max 500 znaków, hint "Pierwsza wiadomość AI w nowej rozmowie").

### Definition of Done — Faza 2

- [x] Home page wygląda jak PRD §1.1 (popularne, kategorie, nowe).
- [x] Search (live HTMX) działa po nazwie i opisie.
- [x] Filtrowanie po kategorii działa.
- [x] Klik w postać → profil postaci (`/characters/{id}`) → CTA → otwiera chat z greeting.
- [x] Cron `characters:recalc-popularity` zarejestrowany na `everyFiveMinutes()` w schedule, `popularity_24h` aktualizuje się.
- [x] Admin w Filamencie zarządza tagami + przypisuje je postaciom.
- [x] Wszystko nadal za auth — Faza 3 to otwiera.

---

## ✅ Faza 3 — Guest flow + soft gate — DONE (2026-04-27)

**Status:** zamknięta. 167/167 testów zielonych, PHPStan czysty, Pint zielony.

**Co zrobione:**
- `LimitType::Guest` enum case + gałąź w `GrantDailyLimits::forUser` (idempotent insert quota=5, period_start=null, priority=0). `MessageLimitModel::scopeForCurrentWindow` traktuje Guest jak Package (always-in-window).
- `App\User\EnsureGhostUser::forRequest(Request): UserModel` — IP rate limit 5/min przez `RateLimiter`, klucz `ghost:{ip}`, throw `ThrottleRequestsException` (429). Wywołanie: `app(EnsureGhostUser::class)->forRequest($request)`.
- `UserModel::isGuest()` + scopes `guests()`/`registered()` + `@property` docblocki dla Larastan.
- `routes/web.php`: `/chat*` wyniesione poza `auth+verified` — kontrolery same wywołują `EnsureGhostUser` na `store`.
- `ChatController` (store używa EnsureGhostUser, show wylicza `$gateLocked`, index 404 dla anon), `MessageController` (store ghost-aware), `MessageStreamController` (refactor DI w parametrze → `app()`).
- Soft gate: renderer `OutOfMessagesException` rozróżnia ghost vs registered. `htmx/guest-gate.blade.php` zwraca dwa OOB swap fragmenty (`#composer` + `#register-gate`). Server-side persist po refresh w `ChatController::show` + `chat/show.blade.php` rendering `_composer.blade.php`/`_gate-modal.blade.php`.
- `RegisterController::store` — inline upgrade ghost flow (REST-only, bez prywatnych helperów). `RegisterRequest`: gdy isGuest skipuje `unique:users,email` (kontroler obsługuje konflikt).
- `SocialAuthController::callback` — inline 3 ścieżki (existing email login, ghost upgrade, anon create) + `forceFill` dla `email_verified_at`.
- `App\Auth\Middleware\RedirectIfRegistered` (alias `guest.ghost`) — wpuszcza anon i ghostów na /register, /login, /auth/{provider}*. Standard `guest` zostaje na /forgot-password, /reset-password.
- `EmailVerificationNoticeController::show` — guard: ghost → redirect /register.
- `App\User\Commands\GcGuestUsersCommand` (`users:gc-guests --inactive-days=7`) + cron daily.
- Filament `UsersTable` — kolumna `account_type` (badge Gość/Niezweryfikowany/Zweryfikowany) + filtr.
- 11 nowych testów (`GuestFlowTest` 8, `GcGuestUsersTest` 3) + zaktualizowane `ChatTest`, `MessageStreamingTest`, `SocialAuthTest` (+2 ghost OAuth scenarios).
- CLAUDE.md: nowa sekcja `### Guest flow`, zaktualizowane `Pliki pod nadzorem`.

**Decyzje strategiczne (zatwierdzone 2026-04-27):**

| Temat | Decyzja |
|---|---|
| Limit guest | 5 wiadomości / sesję ghosta (jednorazowo, bez resetu) |
| IP rate limit dla tworzenia ghosta | 5 attempts / minutę (realnie >1 ghost / minutę nie powinien się tworzyć) |
| GC inactive ghostów | 7 dni bez wiadomości |
| Soft gate UI | Modal + zablokowanie inputu CTA (gate zostaje po refresh — render server-side w widoku chatu) |

### 3.1 Routes — zdejmujemy `verified` z chat read/write

`routes/web.php` rewrite:

```
Route::middleware('auth')->group(function (): void {
    // ... istniejące auth routes (logout, verify-email, ...)

    Route::middleware('verified')->group(function (): void {
        Route::get('/me', [ProfileController::class, 'show'])->name('profile.show');
        Route::patch('/me', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/me', [ProfileController::class, 'destroy'])->name('profile.destroy');
        // ... cała reszta /me*, /buy*, /characters/create
    });

    // Chat poza `verified` — guest też może
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat', [ChatController::class, 'store'])->name('chat.store');
    Route::get('/chat/{chat}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{chat}/messages', [MessageController::class, 'store'])->name('message.store');
    Route::get('/chat/{chat}/messages/stream', MessageStreamController::class)->name('message.stream');
});
```

### 3.2 Ghost user creation — entry point

- `app/User/EnsureGhostUser.php` — klasa biznesowa w roocie modułu User. Metoda `forRequest(Request $request): UserModel`:
  - Jeśli `auth()->check()` → return auth user.
  - Else: rate limit `ip:{ip}` (5 attempts/min) — przekroczenie → throw `TooManyRequestsException` (rendered jako 429 + HTMX banner "Zwolnij trochę").
  - Else: `User::create(['name' => 'Gość', 'email' => null, 'password' => null, 'birthdate' => null, 'email_verified_at' => null])`. `Auth::login($user, remember: true)`. Return user.
- `MessageController::store` — pierwsza linia: `$user = app(EnsureGhostUser::class)->forRequest($request);` (przez container, NIE wstrzykiwane w parametr). Reszta unchanged — `ReserveMessageQuota` dostaje już ghost usera.
- `ChatController::store` — analogicznie: `$user = app(EnsureGhostUser::class)->forRequest($request);`.

### 3.3 LimitType::Guest

- `app/Chat/Enums/LimitType.php` — dodać case `Guest`.
- `app/Chat/GrantDailyLimits.php::forUser(UserModel $user): void` — gałąź:
  - Jeśli `$user->isGuest()` → grant `MessageLimit::create(['user_id' => $user->id, 'model_type' => ChatSettings::defaultModel, 'limit_type' => LimitType::Guest, 'priority' => 0, 'quota' => 5, 'used' => 0, 'period_start' => null])` jednorazowo (idempotent — jeśli już jest, no-op).
- `ReserveMessageQuota` — bez zmian (już query'uje per `user_id` w priority desc).
- Po 5 wiadomościach → `OutOfMessagesException` (już mamy renderer w `bootstrap/app.php`).

### 3.4 Soft gate UI — modal + input lock + persist po refresh

- **Renderer `OutOfMessagesException`** w `bootstrap/app.php`: dodać `is_guest` flag (`auth()->user()?->isGuest()`).
  - Guest → HTMX response 403 + view `htmx/out-of-messages-modal.blade.php` zwracający OOB modal (`<dialog id="register-gate" class="modal modal-open">…</dialog>`) + OOB swap inputu chatu (`<form id="message-form" hx-swap-oob="outerHTML">…CTA "Zarejestruj się"…</form>`). Kombinacja `HX-Reswap: none` na main targetcie + dwa OOB to standard HTMX 4.
  - Auth-free → istniejący toast (zachowany).
- **Persist po refresh** — `ChatController::show` dla ghosta z wykorzystanym limitem (`MessageLimitModel::query()->forUser($user)->where('limit_type', LimitType::Guest)->whereColumn('used','>=','quota')->exists()`) ustawia flag `$gate = true` przekazaną do widoku. `chat/show.blade.php` renderuje pasek-CTA zamiast formy gdy `$gate=true`. Modal pokazany on-load (Blade `@if($gate)` + `<dialog open>`).
- Komunikat na CTA: "Załóż konto, żeby pisać dalej. Twoja rozmowa zostanie zachowana." + button "Zarejestruj się" (link do `/register`) + button "Mam konto" (link do `/login`).

### 3.5 Upgrade ghost → real user (rejestracja email/password)

- `app/Auth/Controllers/RegisterController.php::store(RegisterRequest $request)`:
  - Walidacja: email + password + name + DOB + consents (z Fazy 1).
  - Czy `auth()->check() && auth()->user()->isGuest()`?
    - **TAK** → upgrade flow:
      - Czy email z requesta już istnieje w bazie jako non-ghost?
        - TAK → konflikt: usuwamy ghost record (cascade chats/messages), `Auth::login(existingUser)`. Komunikat "To konto już istnieje, zostałeś zalogowany." Redirect home.
        - NIE → UPDATE current ghost user: `email`, `password=Hash::make`, `name`, `birthdate`. `event(new Registered($user))`. Tworzymy `Consent` rekordy (terms, privacy). Redirect `/verify-email`. Chats zostają.
    - **NIE** → standardowy create (jak teraz).
- Walidacja dla upgrade: `email: required|email|unique:users,email,{currentGhostId}` (ignoruje siebie samego).

### 3.6 Upgrade ghost → real user (Google OAuth)

- `app/Auth/Controllers/SocialAuthController.php::callback`:
  - Po `Socialite::driver()->user()` → mamy email z Google.
  - Czy user z tym emailem już istnieje (non-ghost)?
    - TAK → konflikt: jeśli auth() jest ghostem → usuwamy ghosta + cascade. `Auth::login(existing)`. Redirect home.
    - NIE → czy auth() jest ghostem?
      - TAK → UPDATE ghost: email z Google, name z Google, `email_verified_at=now()`, password zostaje NULL. Redirect `/auth/complete` (DOB + consent — z Fazy 1).
      - NIE → standardowy `firstOrCreate` (jak teraz).

### 3.7 Garbage collection

- `app/User/Commands/GcGuestUsersCommand.php` — `php artisan users:gc-guests --inactive-days=7`. Query: ghost (`email IS NULL`) bez wiadomości >7 dni (`messages.user_id NOT IN (SELECT user_id FROM messages WHERE created_at > NOW() - 7 days)` — pewniej niż `users.updated_at`, bo wiadomość = aktywność). Hard delete cascade (chats+messages cascade FK).
- `routes/console.php` — `Schedule::command('users:gc-guests')->daily()`.

### 3.8 UserModel helpery

- `app/User/Models/UserModel.php`:
  - `public function isGuest(): bool { return $this->email === null; }`.
  - `public function scopeGuests($query) { return $query->whereNull('email'); }`.
  - `public function scopeRegistered($query) { return $query->whereNotNull('email'); }`.

### 3.9 Cashier / billing guards

- `App\Billing\Controllers\BuyController` — endpointy są w `/buy*` route group za `verified` middleware → ghost (no email_verified_at) automatycznie odbity. Nie potrzeba dodatkowej logiki.
- `BillingPortalController` — analogicznie, w `/me/billing` za `verified`.
- Sprawdzić: czy gdzieś w `App\Billing\` jest call `Cashier::stripe()->customers->create()` na user record bez email? Jeśli tak — guard. Czytać `App\Billing\Controllers\BuyController::store`.

### 3.10 Filament — ghost users widoczność

- `app/Filament/Resources/Users/Tables/UserTable.php` — dodać kolumnę "Typ" (Guest/Registered/Verified) + filtr. Bez zmian schemy, derived z `email IS NULL` + `email_verified_at IS NULL`.

### Definition of Done — Faza 3

- [x] Niezalogowany może kliknąć postać → otworzyć chat → wysłać do 5 wiadomości.
- [x] Po 5. wiadomości guest dostaje banner "Załóż konto" (HTMX OOB modal + composer lock).
- [x] Klik "Zarejestruj się" → form → po submit ten sam ghost user staje się real user, jego chaty zostają z `user_id` (ten sam ID).
- [x] Google OAuth z ghost session → analogiczny upgrade.
- [x] Konflikt email (istniejące konto) → ghost umiera, login as existing.
- [x] Cron `users:gc-guests` chodzi codziennie, ghost users >7 dni inactive są usuwane.
- [x] Modal + input lock: po refresh strony ghost z wykorzystanym limitem nadal widzi gate (server-side rendering).
- [x] IP rate limit 5/min na tworzenie ghosta — bot test daje 429.
- [x] PHPStan zielony, testy zielone.

---

## ✅ Faza 4 — Moderation + Reporting — DONE (2026-04-28)

**Cel:** NSFW filter input/output (PRD M7) + system zgłoszeń (PRD M13). Bez tego nie wypuszczamy na 13+.

### Decyzje strategiczne (Faza 4, zatwierdzone)

| Pytanie | Decyzja |
|---|---|
| Output flagged przez moderation | **Replace całością fallbackiem** — SSE event `replace` nadpisuje content bubble. Bez regenerate (uproszczone vs PRD-3x). |
| Self-harm protocol — input czy output? | **Input + output (oba kierunki)**. Input: helpline content od razu w `MessageController::store` (no streaming). Output: SSE replace na helpline po stream-end. |
| Reporter wymagany czy nullable? | **Wymagany — auth zawsze**. Niezalogowany dostaje ghost session przez `EnsureGhostUser` zanim zgłosi. |
| Filament SLA UI | **Widget StatsOverview + kolumna pending-age + filter**. Stat-y: Pending/Overdue/Resolved today + sidebar badge (color='danger' gdy overdue). |

### Co zaimplementowano

**Moderation (`app/Moderation/`)**:
- `Contracts/ModerationProvider.php` interface, `DTO/ModerationResult.php` (readonly + `isSelfHarm()` helper).
- `Providers/OpenAiModerationProvider.php` — `Http::withToken` na `/v1/moderations` (free, `omni-moderation-latest`), 3s timeout, fail-open.
- `Providers/NoOpProvider.php` — zwraca flagged=false (default w `.env.testing` przez `MODERATION_PROVIDER=noop`).
- `config/moderation.php` — `default => env('MODERATION_PROVIDER', 'openai')` + `self_harm.rate_limit/window_seconds` config.
- Bind w `AppServiceProvider::register()` przez `match (config('moderation.default'))`.
- `Exceptions/ContentBlockedException.php` + renderer w `bootstrap/app.php` (HTMX 422 + OOB toast / non-HTMX `back()->withErrors`).
- `HelplineMessage.php` klasa biznesowa — `polish()` (116 111 + 800 70 2222 + 112) i `fallback()`. Wywołanie przez `app(HelplineMessage::class)->polish()`.
- `Models/SafetyEventModel.php` — audit trail (user_id, category, created_at, indexes).

**Pipeline integration**:
- `MessageController::store` — pre-`ReserveMessageQuota` self-harm rate limit guard, input moderation check (self-harm shortcut → helpline content w character msg, no streaming; flagged inny → ContentBlockedException).
- `MessageStreamController::__invoke` — post-stream output moderation (self-harm → SSE replace + log SafetyEvent + RateLimiter::hit; inne flagged → SSE replace fallback).
- Frontend `chat/show.blade.php` — handler obsługuje `payload.replace` (nadpisuje content) zamiast `payload.delta` (append).

**Reporting (`app/Reporting/`)**:
- `Models/ReportModel.php` polymorphic z `pending()`/`overdue()` scopes + PHPStan generics docblocks.
- `Enums/{ReportReason,ReportStatus}.php` z `HasLabel`/`HasColor`.
- `Requests/ReportRequest.php` — Rule::in(['message','character']) whitelist + Rule::enum(ReportReason).
- `Controllers/ReportController.php` — REST `store`, `EnsureGhostUser` inline (anonimowy reporter dostaje ghost session), rate limit `report:{user_id}` 5/min, existence check via `match` na MessageModel/CharacterModel.
- Route `POST /reports` (poza auth+verified, `EnsureGhostUser` zarządza).
- `<x-report-button type=... :id=...>` komponent (DaisyUI dialog modal + HTMX hx-post).
- `chat/_message.blade.php` — `<x-report-button>` w `chat-footer` pod każdą AI msg (gdy nie streaming + content !== '').
- `views/htmx/{content-blocked,report-thanks}.blade.php`.

**Filament (`app/Filament/Resources/Reports/` + `Widgets/PendingReportsOverview.php`)**:
- `ReportResource` — read-only (`canCreate: false`), navigation badge z pending count + danger color gdy overdue.
- `ReportsTable` — kolumna **„Czeka"** z stanem dynamicznym (godziny, color='danger' gdy >24h pending), filtry status/reason/reportable_type/overdue, akcje row Resolve/Dismiss z confirmation.
- `PendingReportsOverview` widget StatsOverview (3 stat-y: Pending / Overdue >24h danger / Resolved today) — zarejestrowany w `AdminPanelProvider`.

**Migracje**:
- `2026_04_28_120000_create_safety_events_table.php` — id, user_id FK cascade, category, created_at, indexy `(user_id, created_at)` + `category`.
- `2026_04_28_120100_create_reports_table.php` — id, reporter_id FK NOT NULL, reportable_type/id (string), reason, description, status, resolved_by FK nullable, resolved_at, indexy `(reportable_type, reportable_id)` + `status` + `(status, created_at)`.

**Morph map** (`AppServiceProvider::boot`): dodano `safety_event` + `report`.

**Testy** (15 nowych, wszystkie zielone):
- `tests/Feature/Moderation/InputModerationTest.php` (4 testy): blocks input flagged HTMX 422, detects self-harm input + helpline + SafetyEvent, blocks after rate limit threshold, NoOp passes through.
- `tests/Feature/Moderation/OutputModerationTest.php` (3 testy): replaces flagged AI output with fallback, overrides self-harm output with helpline + SafetyEvent, keeps original when passes.
- `tests/Feature/Moderation/NoOpProviderTest.php` (2 testy): NoOp always flagged=false, default test env binds NoOp.
- `tests/Feature/Reporting/ReportTest.php` (6 testów): stores report message/character, rejects unknown type, 404 for missing reportable, ghost reporter created, rate limit 5/min.
- `tests/Support/FakeModerationProvider.php` — test double bind przez `app()->bind()` (NIE `$this->app->bind` — protected w Pest closure).

### QA — wszystko zielone

- **182/182 tests passed** (496 assertions, 16.6s) — wszystkie poprzednie + 15 z Faza 4.
- **PHPStan**: 0 errors (po dodaniu `@return MorphTo<Model, $this>` / `@param Builder<ReportModel>` docblocków).
- **Pint**: 213 plików clean (3 auto-fixes: single_quote, unary_operator_space, fully_qualified_strict_types).
- **Octane reload**: OK.

### Definition of Done — Faza 4

- [x] Wiadomość zawierająca explicit content (test: "show me NSFW") jest blokowana przed wysłaniem do AI.
- [x] AI próbujący wygenerować NSFW jest **zastępowany fallbackiem** (zamiast regenerate 3x — uproszczone, decyzja w sekcji „Decyzje strategiczne").
- [x] Self-harm → AI nadpisuje content helpline message, log SafetyEvent, rate limit 3/5min.
- [x] User klika "Zgłoś" pod wiadomością → modal → submit → admin widzi w Filamencie.
- [x] Admin może resolve/dismiss report (action z confirmation, ustawia resolved_by + resolved_at).
- [x] PHPStan zielony, testy zielone (NoOp w `.env.testing`, FakeModerationProvider dla bind w testach).
- [x] CLAUDE.md zaktualizowany — sekcje `### Moderation (moduł Moderation/)` i `### Reporting (moduł Reporting/)` w „Architektura — szczegóły techniczne", plus wpisy w „Pliki pod nadzorem".

### Odchylenia od literalnego planu (zatwierdzone)

- **Output regenerate 3x → SSE replace fallback (1x).** Prostsze UX (nie znika tekst, tylko nadpisuje), brak counter-state w cache, brak dodatkowych SSE roundtripów.
- **Self-harm protocol pokrywa input + output**, nie tylko output (plan-must literalnie). Input self-harm = od razu helpline w character msg bez streamu (nie throw ContentBlockedException — to nie blokuje, to redirect do pomocy).
- **Reporter zawsze wymagany** (FK NOT NULL), bo `EnsureGhostUser` daje każdemu user_id. Plan-must mówił „nullable (guest też zgłasza)" — nasz model jest ekwiwalentny ale prostszy (jednolity rate limit per user, bez per-IP).
- **Filament „Delete reported content" action pominięta** (plan-must opcjonalnie). Admin może zrobić to przez CharacterResource/MessageResource manualnie. Można dodać osobno gdy będzie potrzebne.

---

## Faza 4 — Moderation + Reporting (oryginalny plan)

**Cel:** NSFW filter input/output (PRD M7) + system zgłoszeń (PRD M13). Bez tego nie wypuszczamy na 13+.

### 4.1 Moderation module

- `app/Moderation/Contracts/ModerationProvider.php` — interface:
  ```
  interface ModerationProvider
  {
      public function check(string $text): ModerationResult;
  }
  ```
- `app/Moderation/DTO/ModerationResult.php` — readonly class: `flagged: bool`, `categories: array<string, float>` (np. `['sexual' => 0.8, 'self-harm' => 0.05]`), `score: float` (max).
- `app/Moderation/Providers/OpenAiModerationProvider.php` — implementacja:
  - Wywołanie OpenAI Moderation endpoint (`/v1/moderations`, free) przez Guzzle lub `Laravel\Ai\` jeśli paczka udostępnia (sprawdzić w docs `laravel/ai` 0.6.3).
  - Map response na `ModerationResult`.
  - Timeout 5s (sync, blokuje SSE — must be fast).
- `app/Moderation/Providers/NoOpProvider.php` — zwraca `ModerationResult(flagged: false, categories: [], score: 0)`. Default w `phpunit.xml.dist` lub `.env.testing`.
- `config/moderation.php` — `default => env('MODERATION_PROVIDER', 'openai')`, `providers => ['openai' => ['api_key' => ...], 'noop' => []]`.
- `App\System\Providers\AppServiceProvider::register()` — bind:
  ```
  $this->app->bind(ModerationProvider::class, fn ($app) => match (config('moderation.default')) {
      'openai' => $app->make(OpenAiModerationProvider::class),
      'noop' => $app->make(NoOpProvider::class),
  });
  ```
- `app/Moderation/Exceptions/ContentBlockedException.php` — z polem `categories` + `inputType` (input/output). Renderer w `bootstrap/app.php`:
  - HTMX → 422 + view `htmx/content-blocked.blade.php` (OOB toast "Hej, zmieńmy temat...").
  - Non-HTMX → 422 + redirect back.

### 4.2 Pipeline integration

- `app/Chat/Controllers/MessageController.php::store` — przed `ReserveMessageQuota`:
  - `$result = app(ModerationProvider::class)->check($request->input('content'));`
  - `if ($result->flagged) throw new ContentBlockedException($result->categories, 'input');`
- `app/Chat/MessageStreamer.php` — po pełnym streamingu (przed save):
  - Buffer pełnego output do zmiennej `$fullText`.
  - `$result = app(ModerationProvider::class)->check($fullText);` (przez container — bez DI w MessageStreamer).
  - `if ($result->flagged)`:
    - **Self-harm category special-case**: zamiast regenerate, **wymuszamy** safe response → nadpisujemy character message: "Widzę, że możesz przechodzić trudny moment. Pamiętaj, że jestem AI i nie mogę pomóc tak jak człowiek. Zadzwoń: Telefon Zaufania 116 111 (dla dzieci/młodzieży), Centrum Wsparcia 800 70 2222 (dorośli)." Logujemy incydent (osobna tabela `safety_events` — bez treści wiadomości; tylko `user_id`, `category`, `created_at`).
    - Inne kategorie → regenerate (max 3x). Jeśli i tak flagged → fallback "Przepraszam, ten temat mnie przerasta. Zmieńmy wątek." + zapisz jak normalna message.
- Migracja `create_safety_events_table` — `id`, `user_id` FK, `category` string, `created_at`.

### 4.3 Self-harm rate limit

- Po self-harm event → set rate limit `selfharm:{user_id}` na 5 min (Redis) — w tym czasie max 3 wiadomości od usera. Próba 4. → `OutOfMessagesException` z special message "Daj sobie chwilę odpocząć, jesteś dla nas ważny. Zadzwoń jeśli potrzebujesz wsparcia: 116 111."

### 4.4 Reporting module

- Migracja `create_reports_table`:
  - `id`, `reporter_id` FK users nullable (guest też może zgłaszać), `reportable_type` string, `reportable_id` string (polymorphic — message/character), `reason` enum (z `ReportReason`), `description` text nullable, `status` enum (z `ReportStatus`), `resolved_by` FK users nullable, `resolved_at` datetime nullable, `created_at`, `updated_at`. Index `(reportable_type, reportable_id)`, `(status)`.
- `app/Reporting/Models/ReportModel.php` — relacja `morphTo reportable`, `reporter`, `resolvedBy`.
- `app/Reporting/Enums/ReportReason.php` — `nsfw`, `harassment`, `misinformation`, `impersonation`, `self_harm_promotion`, `other`.
- `app/Reporting/Enums/ReportStatus.php` — `pending`, `resolved`, `dismissed`.
- Routes (auth, NIE verified — guest też zgłasza):
  - `POST /reports` → `App\Reporting\Controllers\ReportController::store(ReportRequest $request)`. Body: `reportable_type`, `reportable_id`, `reason`, `description`. `reporter_id = auth()->id()`.
- `app/Reporting/Requests/ReportRequest.php` — walidacja + sprawdzenie czy `reportable_type` jest dozwolony (`message`, `character` only — przez whitelist zamiast `Relation::morphMap`).
- UI: `<x-report-button :reportable="$message">` — modal HTMX z formem (`hx-post="/reports"`). Przycisk widoczny:
  - Na każdej AI message (chat view, dropdown).
  - Na karcie postaci (profil + grid).
- `app/Filament/Resources/Reports/` — pełen panel admina:
  - Lista filtrowalna po `status`, `reason`, `reportable_type`.
  - Akcja "Resolve" → `status=resolved`, `resolved_by`, `resolved_at`. Plus opcja "Delete reported content" (jeśli message → soft delete; jeśli character → soft delete cascade).
  - SLA dashboard: ile zgłoszeń pending >24h (PRD §5.2).

### 4.5 Update CLAUDE.md

- Po Fazie 4 dopisać sekcję `### Moderation` w "Architektura — szczegóły techniczne" + `### Reporting`.

### Definition of Done — Faza 4

- [ ] Wiadomość zawierająca explicit content (test: "show me NSFW") jest blokowana przed wysłaniem do AI.
- [ ] AI próbujący wygenerować NSFW jest regenerowany (max 3x), potem fallback safe message.
- [ ] Self-harm → AI wychodzi z roli, numery zaufania, rate limit.
- [ ] User klika "Zgłoś" pod wiadomością → modal → submit → admin widzi w Filamencie.
- [ ] Admin może resolve report + opcjonalnie delete content.
- [ ] PHPStan zielony, testy zielone (mock OpenAI Moderation API w testach).

---

## ✅ Faza 5 — Sekcja Randki — DONE (2026-04-28)

**Cel:** Osobna sekcja `/randki` (PRD §4) — postacie randkowe z osobnym profilem, onboardingiem, tonem rozmowy. Pełny SFW.

### Decyzje strategiczne (Faza 5, auto w trybie autonomicznym)

| Pytanie | Decyzja |
|---|---|
| Wiek profili randkowych | **18+** (CHECK constraint `dating_profiles_age_check` na `age >= 18 AND age <= 99`). Walidacja Filament + DB jako last line of defense. Postać dorosła chroni przed problematycznym flirtem z minorem-AI. |
| Onboarding: modal czy strona | **Dedykowana strona `/randki/onboarding`** (nie modal). Łatwiej testowalne + clean URL. Spec PRD §4.2 mówił "modal" wizualnie, ale flow jest pełnostronicowy. |
| Brak opublikowanego `dating-terms` | **Failover otwarty** — `HasAcceptedDatingTerms::check()` zwraca true gdy doc nie istnieje. Sekcja działa zanim legal team wpisze treść; po publikacji 1. wersji wszystko dynamic-up. |
| Prompt template injection point | **`MessageStreamer::stream`** — append `app(PromptTemplates::class)->flirt()` do `instructions` przed `new AnonymousAgent`. Klasa biznesowa `App\Dating\PromptTemplates` z `flirt(): string` (named, NIE `__invoke`). |
| NSFW w dating chat | **Reuse Fazy 4 moderation pipeline** + prompt template instruuje subtle deflection ("Hej, wolny tor 😄"). Moderation pierwszy (input/output check), AI deflektuje subtle attempts które nie przeszły progu. |
| Guest na dating chat | **`ChatController::store` inline guard** — kind=Dating + (anon\|ghost) → redirect login. Tylko dla dating; regular zostaje ghost-friendly. Guard inline, bez middleware (musi *najpierw* znaleźć character w DB). |
| Dating profile PK | **`character_id` ULID jako PK** (1:1 cascade FK). Wymaga `$primaryKey/$incrementing/$keyType` na modelu. Brak osobnego auto-increment id — natural key. |

### Co zaimplementowano

**Models + schema (`app/Dating/`)**:
- `Models/DatingProfileModel.php` — `character_id` ULID PK, `age` smallint, `city`, `bio`, `interests` jsonb, `accent_color`. PHPStan generics: `BelongsTo<CharacterModel, $this>` na `character()`.
- `database/migrations/2026_04_28_130000_create_dating_profiles_table.php` — wraz z DB CHECK constraint `dating_profiles_age_check (age >= 18 AND age <= 99)`.
- `database/factories/DatingProfileFactory.php` — generuje character (kind=Dating, is_official) + profile.

**Klasy biznesowe (root modułu, named methods, no `__invoke`)**:
- `app/Dating/PromptTemplates.php` — `flirt(): string`. Template po polsku z zakazem NSFW, instrukcją deflection, zakazem udawania prawdziwego człowieka.
- `app/Dating/HasAcceptedDatingTerms.php` — `check(UserModel $user): bool`. Reused w 3 miejscach (DatingController, ChatController guard).

**Controllers**:
- `app/Dating/Controllers/DatingController.php` — `index` (publiczny, redirectuje auth-without-consent na onboarding), `show` (publiczny, 404 dla regular character).
- `app/Dating/Controllers/DatingOnboardingController.php` — `show` (idempotent — redirect home gdy consent już jest), `store` używa `RecordConsents`.
- `app/Dating/Requests/DatingOnboardingRequest.php` — `accepted_dating_terms: accepted` + authorize() (auth + non-guest).

**Pipeline integration**:
- `MessageStreamer::stream` — gdy `character->kind === Dating` doklejam `flirt()` do `instructions`. Moderation z Fazy 4 nadal działa.
- `ChatController::store` — inline guard kind=Dating: anon/ghost → redirect login; auth bez consent → redirect dating.onboarding.
- `CharacterModel.datingProfile()` — `HasOne<DatingProfileModel, $this>`.

**Filament `DatingProfileResource`**:
- Pełen CRUD ale tworzy 2 modele atomowo: `CreateDatingProfile::handleRecordCreation` w `DB::transaction` tworzy `CharacterModel` (kind=Dating, is_official=true, user_id=admin) + `DatingProfileModel`.
- `EditDatingProfile::mutateFormDataBeforeFill` ładuje pola Charactera; `handleRecordUpdate` synchronizuje oba w transakcji.
- Form z `Section`-ami (Postać + Profil randkowy), `TagsInput` na `interests`, `ColorPicker` na `accent_color`, walidacja `age` 18-99 w polu numeric.

**Routes** (`routes/web.php`):
- `GET /randki` (publiczny index).
- `GET|POST /randki/onboarding` (auth-only group).
- `GET /randki/{character}` (publiczny profil).
- Kolejność: index → middleware('auth') onboarding → show — żeby `/randki/onboarding` nie matchował `{character}` ULID-bindingu.

**Views**:
- `resources/views/dating/index.blade.php` — grid kart z avatarem, imieniem+wiek, miastem, bio (line-clamp), accent_color CSS var.
- `resources/views/dating/show.blade.php` — pełen profil: avatar duży z accent shadow, imię+wiek, miasto, interests badges, bio, dyskla­imer "to zabawa, nie prawdziwa osoba", CTA "Napisz".
- `resources/views/dating/onboarding.blade.php` — dedykowana strona z 4 punktami (AI/rozrywka/no-NSFW/regulamin) + checkbox + submit.

**Tests** (`tests/Feature/Dating/`, 17 testów):
- `DatingIndexTest.php` (6) — listing dla guest, skip profile-less, redirect auth-no-consent, show z consent, 404 dla regular, profil detail.
- `DatingOnboardingTest.php` (5) — auth required, show form, store records consent, validation, idempotent.
- `DatingChatGuardTest.php` (3) — anon→login, no-consent→onboarding, with-consent→chat.
- `PromptTemplateTest.php` (1) — flirt template zawiera Randki/no-NSFW/postacią AI/po polsku.

**CLAUDE.md** — dopisana sekcja `### Dating (moduł Dating/)` + Pliki pod nadzorem (DatingProfile + Filament Resources + migracja + views + integracje w MessageStreamer/ChatController).

### Odchylenia od planu

- **Onboarding to dedykowana strona, nie modal** — plan-must mówił "modal", zaimplementowano jako stronę. Łatwiej testowalne, clean URL, łatwiejsze do udostępnienia.
- **Failover otwarty dla braku dating-terms** — sekcja działa zanim legal team wpisze treść (zwraca consent=true). Po publikacji 1. wersji wszystko dynamic-up.
- **`HasAcceptedDatingTerms` jako osobna klasa biznesowa** — nie było w spec, ale DRY: ta sama logika w 3 miejscach (DatingController index/show + ChatController guard).
- **`dating.onboarding.store` route name dropped** — POST `/randki/onboarding` używa tej samej nazwy `dating.onboarding` co GET (REST-only). Spec wskazywał `dating.onboarding.store` ale był to non-REST nazewnik — uproszczone.

### QA results

```
docker exec -u dev postac-ai-app-1 php artisan migrate
docker exec -u dev postac-ai-app-1 php artisan test
docker exec -u dev postac-ai-app-1 vendor/bin/phpstan analyse --memory-limit=512M
docker exec -u dev postac-ai-app-1 vendor/bin/pint --test
docker exec -u dev postac-ai-app-1 php artisan octane:reload
```

- Testy: **197/197 passed** (15 nowych z Fazy 4 + 17 nowych z Fazy 5).
- PHPStan: **0 errors** (214 plików).
- Pint: **231 files clean** (1 auto-fix: `RuntimeException` import w CreateDatingProfile).
- Octane: reload OK.

### Definition of Done — Faza 5

- [x] Auth user wchodzi na `/randki` → onboarding (jeśli brak consent) → akceptuje → grid profili.
- [x] Klik profil → strona profilu → "Napisz" → chat z dating prompt template (`flirt()` doklejony do instructions).
- [x] Próba NSFW w dating chat → blocked (Faza 4 moderation) + AI prompt-template-instructed do flirciarskiej deflekcji.
- [x] Guest na `/randki` widzi profile, klik "Napisz" → login redirect.
- [x] Admin w Filamencie tworzy nowy dating profile (character + dating_profile w transaction).
- [x] Brak dating-terms consent → onboarding strona blokuje dostęp.

---

## Faza 5 — Sekcja Randki (oryginalna spec, archiwalna)

**Cel:** Osobna sekcja `/randki` (PRD §4) — postacie randkowe z osobnym profilem, onboardingiem, tonem rozmowy. Pełny SFW.

### 5.1 Schema

- Migracja `create_dating_profiles_table`:
  - `character_id` ULID PK + FK `characters` cascade (1:1).
  - `age` smallint NOT NULL (range 18-99 — PRD nie precyzuje, my przyjmujemy 18+ profile, bo flirt z 24-letnią Mają jest ok; postać 16-letnia byłaby problematyczna).
  - `city` string(64) NOT NULL.
  - `bio` text NOT NULL (max 500 znaków, walidacja w aplikacji).
  - `interests` jsonb NOT NULL default `[]` (array of strings, max 5).
  - `accent_color` string(7) nullable (hex).
  - `timestamps`.
- `CharacterModel.kind` ustawione na `dating` dla profili randkowych. Plus `is_official=true` (tylko admin tworzy).

### 5.2 Models

- `app/Dating/Models/DatingProfileModel.php` — relacja `character()` belongsTo. Cast `interests` jako array.
- `CharacterModel` — relacja `datingProfile(): HasOne` (do rewrite gdy `kind=dating`).

### 5.3 Routes

```
Route::get('/randki', [DatingController::class, 'index'])->name('dating.index');
Route::get('/randki/onboarding', [DatingOnboardingController::class, 'show'])->name('dating.onboarding');
Route::post('/randki/onboarding', [DatingOnboardingController::class, 'store'])->name('dating.onboarding.store');
Route::get('/randki/{character}', [DatingController::class, 'show'])->name('dating.show');
```

`DatingOnboardingController` w grupie `auth`. Reszta (`index`, `show`) poza `verified` — guest może oglądać profile, ale nie czatować bez consent.

### 5.4 Onboarding

- `dating-terms` to nowy `LegalDocument` slug (już w enum z Fazy 1).
- Onboarding flow:
  - Pierwsze wejście na `/randki` (auth user) → check czy ma `Consent` na `dating-terms` w bieżącej wersji. Brak → redirect `/randki/onboarding`.
  - `dating-onboarding.blade.php` — PRD §4.2 modal:
    - "To postacie AI, nie prawdziwi ludzie"
    - "Rozmowy są rozrywkowe, nie terapeutyczne"
    - "Zero NSFW — luźny flirt, nie sexting"
    - Checkbox "Rozumiem, to zabawa z AI" (required).
    - Submit → tworzy `Consent` na `dating-terms`. Redirect `/randki`.
  - Guest na `/randki` → widzi profile ale klik na "Napisz" → redirect `/login` (z return-back). Po loginie → onboarding.

### 5.5 UI

- `dating/index.blade.php` (`resources/views/dating/index.blade.php`) — PRD §4.3:
  - Heading "💕 Randki".
  - Cards z profilami: avatar, imię, wiek, miasto, krótkie bio (1 linia), CTA "Napisz".
  - Sekcja "Twoje rozmowy" (auth users with `kind=dating` chats).
- `dating/show.blade.php` — pełen profil: avatar duży, imię/wiek/miasto, bio (3 zdania), 3 interests jako badges, CTA "Napisz" (POST /chat z character_id).

### 5.6 Filament — DatingProfileResource

- `app/Filament/Resources/DatingProfiles/` — CRUD dla profili randkowych. Form: pola DatingProfile + ScrollTo `CharacterForm` parts (avatar, prompt, greeting). Tworzenie nowego dating profile = tworzenie character + dating_profile w transaction.
- Walidacja w resource: `kind` automatycznie set na `dating`, `is_official=true`.

### 5.7 Chat dla dating

- ChatController już działa po `character_id` — bez zmian.
- Self-harm protocol + moderation działają tak samo (Faza 4 cover).
- Dodatkowy guard w `MessageStreamer` lub `ChatSettings`: jeśli `character->kind === dating` → użyj `dating-flirt` system prompt template (postpended do `character->prompt`):
  - "BEZWZGLĘDNE ZASADY: Nigdy nie generuj treści seksualnych. Flirtuj subtelnie. Jeśli user próbuje seksu, odpowiedz żartem 'Hej, wolny tor 😄 Najpierw kawa!'. Pamiętaj wcześniejsze tematy."
  - Trzymane w `App\Dating\PromptTemplates::flirt(): string`.

### 5.8 Update CLAUDE.md

- Sekcja `### Dating` w "Architektura — szczegóły techniczne".

### Definition of Done — Faza 5

- [ ] Auth user wchodzi na `/randki` → onboarding modal → akceptuje → grid profili.
- [ ] Klik profil → strona profilu → "Napisz" → chat z dating prompt template.
- [ ] Próba NSFW w dating chat → blocked (z Fazy 4) + character respondsem flirciarsko-deflektorskim.
- [ ] Guest na `/randki` widzi profile, klik "Napisz" → login redirect.
- [ ] Admin w Filamencie tworzy nowy dating profile (character + dating_profile w transaction).
- [ ] Brak dating-terms consent → modal blokuje dostęp.

---

## ✅ Faza 6 — Catalog infrastructure — DONE (2026-04-28)

**Status:** Wszystkie pkt DoD zaakceptowane. Faza zamknięta.

### Decyzje strategiczne (zatwierdzone przez usera)

| Temat | Decyzja | Notatka |
|---|---|---|
| URL strategy | **Tylko slug primary** (`/postacie/{slug}`), `/characters/{ULID}` znika (404) | Czysty URL, jeden master endpoint dla profilu. Stary URL nie istnieje. |
| Slug regen | **Pole edytowalne w Filamencie** + auto-gen raz przy create | Generowany jednorazowo; rename `name` nie regeneruje. Admin może override w form. User non-admin nie ma dostępu do pola. |
| Sekcja "Polskie legendy" | **Pominięta** w MVP | Out of scope infrastruktury — sort `is_official desc` + tag-by-category-page wystarczy. |
| `?official=true` filter | **Query param + widoczny przycisk** | Checkbox "Tylko oficjalne" na `/characters` index, integracja z HTMX. |

### Co zaimplementowano

- **Migracja `add_slug_to_characters_table`** — `slug VARCHAR(160) UNIQUE NULLABLE` + backfill `kind=regular` (Str::slug + numeryczny dedup `-2`/`-3`).
- **`CharacterModel::booted` z `static::saving`** — generuje slug gdy `kind === Regular AND slug NULL/empty`. Dedup query z `withTrashed()`. **Nie regeneruje** przy update name.
- **`protected $attributes`** — defaults `kind=regular`, `is_official=false`, `popularity_24h=0` (bez tego saving event nie widziałby kindu, bo DB default odpala dopiero przy INSERT).
- **`@property string|null $slug`** + slug w fillable.
- **Route**: `Route::get('/postacie/{character:slug}', ...)->name('character.show')`. Stary `/characters/{character}` usunięty. `/characters` (lista) i `/characters/search` (HTMX) zachowane.
- **Filament `CharacterForm`** — `TextInput::make('slug')` z regex `^[a-z0-9-]+$`, `unique(ignoreRecord: true)`, helperText o ryzyku łamania linków.
- **Filament `CharactersTable`**:
  - `IconColumn::make('is_official')->boolean()->sortable()`
  - `TernaryFilter::make('is_official')` (Wszystkie/Oficjalne/Nieoficjalne)
  - `BulkAction::make('promote')` + `BulkAction::make('unpromote')` z confirmation
- **`CharacterController::index`** — `?official=1` filter w `buildBrowseQuery` przez `$request->boolean('official')`.
- **`resources/views/characters/index.blade.php`** — checkbox "Tylko oficjalne" w form filtrów + HTMX trigger `change from:input[name=official]`.
- **Update tests** — `CharacterBrowseTest` zaktualizowany (wszystkie show-testy używają `/postacie/{slug}` zamiast `/characters/{id}`).
- **Nowe testy** (12):
  - `tests/Feature/Character/SlugTest.php` (7) — auto-gen, no-regen, admin override, dedup, dating null, route resolves, old URL 404.
  - `tests/Feature/Character/OfficialFilterTest.php` (3) — filter on, filter off, UI checkbox renders.
  - `tests/Feature/Filament/CharacterPromoteBulkTest.php` (2) — promote, unpromote bulk.

### Odchylenia od oryginalnej spec

1. **Old ULID URL nie redirectuje** — usera wybrał "Tylko slug, ULID znika" zamiast 301 alias. Czystsze, ale zewnętrzne linki na `/characters/{ULID}` zwrócą 404 (akceptowalne, bo prod nie ma jeszcze ruchu).
2. **Slug edytowalny w Filamencie** — oryginał zakładał auto-only, user wybrał admin override. Plus regex validation i unique constraint w form.
3. **"Polskie legendy" sekcja** — pominięta jako out-of-MVP (wymaga ręcznego oznaczania kategorią).
4. **`protected $attributes`** dodane na `CharacterModel` — wymagane żeby `static::saving` widział `kind` przy CREATE (DB default aktywuje się dopiero przy INSERT, nie w event).
5. **Test bulk action API** — Filament 5 wymaga `callTableBulkAction(string, array)` zamiast `callAction(string, records: array)`.

### QA wyniki

- `php artisan test` → **209/209 passed** (562 assertions)
- `vendor/bin/phpstan analyse` → 0 errors
- `vendor/bin/pint --test` → 235 files clean
- `php artisan octane:reload` → workers reloaded

### Definition of Done — wszystkie spełnione

- [x] Admin zaznacza `is_official` na postaci → karta postaci ma badge "Oficjalna" + author ukryty.
- [x] Home page wyróżnia oficjalne (sort priority `is_official desc`).
- [x] `/postacie/{slug}` (np. `jozef-pilsudski`) działa jako jedyny URL profilu (ULID URL znikł).
- [x] Slug auto-generowany przy create, nie regeneruje przy rename, dedupowany.
- [x] Admin może override slug w Filamencie.
- [x] `?official=true` filter + UI checkbox na `/characters`.
- [x] Bulk action "Promote/Unpromote" w Filament działa.

---

## Faza 6 — Catalog infrastructure (oryginalna spec, archiwalna)

**Cel:** UI/UX traktuje `is_official` postacie inaczej (badges, sortowanie, ekspozycja). Po tej fazie content team może zacząć wpisywać 30+ postaci ręcznie w Filamencie i będą wyglądać jak należy.

### 6.1 UI distinction

- Karta postaci (`<x-character-card>`) — jeśli `is_official=true`:
  - Badge "Oficjalna" lub "Polecana".
  - Author hidden (PRD: "zamaskujemy po fladze is_official").
  - Wyższa ekspozycja w sortowaniu home page.
- `HomeController::index` — "Polskie legendy" sekcja: `Character::official()->whereHas('tags', fn($q) => $q->where('slug', 'historia'))->limit(6)`. Nazwa sekcji konfigurowalna przez tag.

### 6.2 Filament — Promote action

- `app/Filament/Resources/Characters/Pages/ListCharacters.php` — bulk action "Promote to official" → ustawia `is_official=true`.
- Form: toggle `is_official` z hint "Postać widoczna jako oficjalna na stronie głównej".

### 6.3 Sortowanie + filtrowanie

- `CharacterController::index` — domyślne sort: `official desc, popularity_24h desc, created_at desc`.
- Query param `?official=true` filtruje tylko oficjalne.

### 6.4 SEO landing pages

- Route `GET /postacie/{character:slug}` jako alias na `/characters/{character:id}` z 301 (lepszy URL dla SEO). Wymaga `slug` na `CharacterModel` — migracja `add_slug_to_characters_table` (string unique).
  - Auto-generate z `name` przy zapisie (`Str::slug` + dedup).
  - Dla istniejących userów: backfill przez data migration.

### Definition of Done — Faza 6

- [ ] Admin zaznacza `is_official` na postaci → karta postaci ma badge "Oficjalna" + author ukryty.
- [ ] Home page wyróżnia oficjalne (sekcja "Polskie legendy" + sort priority).
- [ ] `/postacie/jozef-pilsudski` (slug) działa jako alias do ULID URL.
- [ ] Bulk action w Filament działa.

---

## Critical files — modyfikacje + nowe

### Migracje (database/migrations/)

- `add_birthdate_to_users_table` (Faza 1)
- `make_users_email_nullable` (Faza 1)
- `add_kind_is_official_greeting_popularity_to_characters_table` (Faza 1)
- `add_description_to_characters_table` (Faza 1)
- `create_legal_documents_table` (Faza 1)
- `create_consents_table` (Faza 1)
- `enable_pg_trgm_and_index_characters` (Faza 2)
- Spatie tags published migrations (Faza 2)
- `create_dating_profiles_table` (Faza 5)
- `create_safety_events_table` (Faza 4)
- `create_reports_table` (Faza 4)
- `add_slug_to_characters_table` (Faza 6)

### Nowe moduły

- `app/Legal/` (Faza 1) — `Models/LegalDocumentModel`, `Models/ConsentModel`, `Enums/DocumentSlug`, `Controllers/LegalDocumentController`, `Middleware/EnsureLatestConsents`.
- `app/Moderation/` (Faza 4) — `Contracts/ModerationProvider`, `Providers/{OpenAi,NoOp}Provider`, `DTO/ModerationResult`, `Exceptions/ContentBlockedException`.
- `app/Reporting/` (Faza 4) — `Models/ReportModel`, `Enums/{ReportReason,ReportStatus}`, `Controllers/ReportController`, `Requests/ReportRequest`.
- `app/Dating/` (Faza 5) — `Models/DatingProfileModel`, `Enums/CharacterKind` (lub w `app/Character/Enums/`), `Controllers/{DatingController,DatingOnboardingController}`, `PromptTemplates`.

### Modyfikacje istniejących plików

- `app/User/Models/UserModel.php` — `isGuest()`, `scopeGuests()`, `scopeRegistered()`, fillable `birthdate`.
- `app/User/EnsureGhostUser.php` (nowa klasa biznesowa) — Faza 3.
- `app/User/Commands/GcGuestUsersCommand.php` (nowa) — Faza 3.
- `app/Auth/Requests/RegisterRequest.php` — DOB + consents validation (Faza 1).
- `app/Auth/Controllers/RegisterController.php` — guest upgrade flow (Faza 3).
- `app/Auth/Controllers/SocialAuthController.php` — DOB completion + ghost upgrade (Faza 1, 3).
- `app/Auth/Controllers/AuthCompleteController.php` (nowy, Faza 1) — DOB+consent po OAuth.
- `app/Character/Models/CharacterModel.php` — kind cast, scopes, HasTags trait (Fazy 1+2).
- `app/Character/Controllers/CharacterController.php` — `index` + `show` (Faza 2).
- `app/Character/Commands/RecalculatePopularityCommand.php` (nowa) — Faza 2.
- `app/Chat/Enums/LimitType.php` — `Guest` case (Faza 3).
- `app/Chat/GrantDailyLimits.php` — guest grant gałąź (Faza 3).
- `app/Chat/Controllers/ChatController.php` — greeting insert (Faza 2), ghost user resolution (Faza 3).
- `app/Chat/Controllers/MessageController.php` — moderation pre-call + ghost user resolution (Fazy 3, 4).
- `app/Chat/MessageStreamer.php` — output moderation + dating prompt template (Fazy 4, 5).
- `app/Home/Controllers/HomeController.php` — full rewrite z sekcjami popular/categories/new (Faza 2).
- `app/System/Providers/AppServiceProvider.php` — bind `ModerationProvider`, ewentualnie aktualizacja morph map o `dating_profile`, `legal_document`, `consent`, `report`, `safety_event` (Faza 1+).
- `bootstrap/app.php` — renderery dla `ContentBlockedException`, `TooManyRequestsException` (HTMX-aware), ewentualna rejestracja consent middleware globalnie. Plus `->withCommands(commands: glob(__DIR__.'/../app/*/Commands'))` jeśli brak.
- `routes/web.php` — restrukturyzacja middleware groups (Faza 3), nowe routes dla `/legal`, `/randki`, `/characters` browse, `/reports`, `/auth/complete`, `/postacie/{slug}` redirect.
- `routes/console.php` — `Schedule::command('characters:recalc-popularity')->everyFiveMinutes()`, `Schedule::command('users:gc-guests')->daily()`.
- `resources/views/home.blade.php` — full rewrite (Faza 2).
- `resources/views/auth/register.blade.php` — DOB + checkboxy (Faza 1).
- `resources/views/auth/complete.blade.php` (nowy) — Faza 1.
- `resources/views/legal/show.blade.php` (nowy) — Faza 1.
- `resources/views/dating/{index,show,onboarding}.blade.php` (nowe) — Faza 5.
- `resources/views/characters/{index,show}.blade.php` (nowe) — Faza 2.
- `resources/views/htmx/out-of-messages.blade.php` — gałąź guest (Faza 3).
- `resources/views/htmx/content-blocked.blade.php` (nowy) — Faza 4.
- `resources/views/components/{character-card,report-button}.blade.php` (nowe).
- `app/Filament/Resources/{LegalDocuments,Consents,Tags,DatingProfiles,Reports}/` (nowe).
- `app/Filament/Resources/Characters/` — pole `is_official`, `greeting`, kategorie+tagi (Fazy 2, 6).
- `CLAUDE.md` — dopisać sekcje per moduł po każdej fazie (Legal/Moderation/Reporting/Dating).

### Reused (bez zmian!)

- `app/Chat/ReserveMessageQuota.php` — działa per `user_id`, ghost ma user_id.
- `app/Chat/MessageStreamer.php` — core streaming nie zmienia się, tylko hooks pre/post moderation.
- `app/Billing/` — cały moduł nieruszany.
- `app/Filament/Resources/Users/` — drobny dodatek (kolumna typ), reszta jak jest.

---

## Verification — full E2E

Po każdej fazie:

```
docker exec -u dev postac-ai-app-1 php artisan migrate
docker exec -u dev postac-ai-app-1 php artisan test
docker exec -u dev postac-ai-app-1 vendor/bin/phpstan analyse --memory-limit=512M
docker exec -u dev postac-ai-app-1 vendor/bin/pint --test
docker exec -u dev postac-ai-app-1 npm run build
docker exec -u dev postac-ai-app-1 php artisan octane:reload
```

E2E manual (po Fazie 6 — full launch readiness):

1. Czyste DB. Wejść jako gość na `/`.
2. Zobaczyć grid postaci, kategorie, search działa.
3. Klik postać → profil → "Rozpocznij rozmowę" (jeszcze nie tworzymy ghost).
4. Pisać 1-2 wiadomości → ghost user się tworzy, AI odpowiada (greeting + reply).
5. Wpisać NSFW → blocked (toast).
6. Wpisać "I want to hurt myself" → AI wychodzi z roli, numery zaufania, rate limit.
7. Pisać do 5 wiadomości → soft gate "Załóż konto".
8. Klik "Zarejestruj" → form → DOB <13 → walidacja błąd.
9. Submit z DOB ≥13 + checkboxy → upgrade ghost → email verification.
10. Klik link verify → wracamy → chats zostały, kontynuacja rozmowy.
11. Wejść na `/randki` → onboarding modal → akceptacja.
12. Klik profil randkowy → chat → próba NSFW → blocked + flirtful deflection.
13. Klik "Zgłoś" pod wiadomością → modal → submit → admin widzi w Filamencie.
14. W Filamencie: dodać `LegalDocument` v2 (terms) → published → user przy następnej akcji widzi modal "Zaakceptuj nowy regulamin".
15. Wylogować się, czekać 30 dni (lub `php artisan users:gc-guests --inactive-days=0` w tinkerze) → ghost users sprzątnięci.

---

## Checklist przed launchem

- [ ] Wszystkie 6 faz zamknięte.
- [ ] CLAUDE.md zaktualizowany.
- [ ] Konto admina założone (`super_admin` role) + `is_official=true` na 30+ postaciach (content team).
- [ ] Treść `terms`, `privacy`, `dating-terms` wpisana w Filament (legal team).
- [ ] OpenAI API key + Stripe keys + Sentry DSN w prod `.env`.
- [ ] Stripe price IDs (`STRIPE_PRICE_PREMIUM`, `STRIPE_PRICE_PACKAGE_*`) sprawdzone w produkcji Stripe.
- [ ] DNS + SSL.
- [ ] Sentry frontend wire-up zweryfikowany (sprawdzić `app.blade.php` meta tagi).
- [ ] Backups Postgres skonfigurowane.
- [ ] Cron daemon w kontenerze włączony (supervisor).
- [ ] `OpenAiModerationProvider` zwraca real wyniki w prod (smoke test).
