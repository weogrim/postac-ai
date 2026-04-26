# Architektura — zasady (RULES)

> Skondensowane zasady dla AI / codziennej pracy. Pełna dokumentacja z uzasadnieniem każdej decyzji: `ARCHITEKTURA.md`. Ten plik trzyma się imperatywu — *rób / nie rób*.

> Wymagany **Laravel 11+** (atrybuty modeli `#[ObservedBy]`, `bootstrap/app.php`).

---

## Filozofia

1. **Domena ponad warstwę.** Folder w `app/` reprezentuje koncept biznesowy, nie warstwę techniczną. Programista wchodzący do projektu po raz pierwszy ma rozumieć, **co** projekt robi, zanim zrozumie **jak** to robi.
2. **Wygoda ponad ortodoksję.** Nie robimy z modułów „package-style Laravel modules" z własnymi providerami, route'ami i configami. Zmiany tylko tam, gdzie wygrywają domenowo (`app/`); klasyka tam, gdzie klasyka działa (testy, migracje, configi, routing).
3. **Brak fasad / wejść do modułu.** Moduł nie ma wymaganej klasy wejściowej. To po prostu folder z klasami w spójnym namespace'ie.
4. **Brak Service Layer.** Klasy biznesowe nazywamy od **akcji** lub **roli**, nie od warstwy. Wydzielamy je tylko, gdy operacja ma realną logikę domenową.
5. **Auto-discovery > rejestracja ręczna.** Wykorzystujemy mechanizmy Laravela 11+ (atrybuty PHP, `withCommands`, `withEvents`) zamiast pisać ServiceProvidery per moduł.
6. **Luźne reguły, nie restrykcyjne.** Nie ma ścisłej taksonomii „moduł domenowy" / „infrastrukturalny" / „integracyjny". Moduł to moduł.
7. **Logika domyślnie żyje w kontrolerze.** Większość kontrolerów to „weź dane → zwaliduj → wrzuć do bazy → zwróć response". Operacje trywialne zostają w kontrolerze — nie wyciągamy ich proaktywnie do klas biznesowych. Klasę biznesową wydzielamy dopiero, gdy kontroler tłuścieje albo gdy ta sama logika ma być wywołana z drugiego miejsca.

---

## Nazwa modułu

- **PascalCase**, liczba **pojedyncza** (DDD): `Order`, nie `Orders`. `User`, nie `Users`.
- Bez separatorów: `WorkTime`, `DefaultShift`.
- Wyjątek dopuszczalny tylko gdy singular brzmi nienaturalnie (niepoliczalne, np. `News`).

---

## Sufiksy nazewnicze (twarde)

| Klasa                     | Wzorzec                                  |
| ------------------------- | ---------------------------------------- |
| Eloquent Model            | `<Foo>Model`                             |
| Pivot                     | `<Foo><Bar>Pivot`                        |
| Klasa biznesowa           | `<Module><Action>` (np. `OrderRefund`)   |
| Controller (web/Inertia)  | `<Foo>Controller`                        |
| Controller (JSON API)     | `<Foo>ApiController`                     |
| Single-action Controller  | `<Resource><Action>Controller`           |
| FormRequest               | `<Foo>Request`                           |
| JsonResource              | `<Foo>Resource`                          |
| Observer                  | `<FooModel>Observer`                     |
| Event                     | `<Foo>Event`                             |
| Listener                  | `<Foo>Listener`                          |
| Job                       | `<Foo>Job`                               |
| Notification              | `<Foo>Notification`                      |
| Mailable                  | `<Foo>Mail`                              |
| DTO                       | `<Foo>DTO`                               |
| Enum                      | `<Foo>Enum`                              |
| Exception                 | `<Foo>Exception`                         |
| Trait                     | `<Foo>Trait`                             |
| Artisan Command           | `<Foo>Command`                           |

---

## Gdzie co kłaść

### W module (`app/<Module>/`)

| Klasa                  | Podfolder w module               |
| ---------------------- | -------------------------------- |
| Model / Pivot          | `Models/`                        |
| Controller             | `Controllers/`                   |
| FormRequest            | `Requests/`                      |
| JsonResource           | `Resources/`                     |
| Observer               | `Observers/`                     |
| Event / Listener / Job | `Events/`, `Listeners/`, `Jobs/` |
| Notification / Mail    | `Notifications/`, `Mail/`        |
| DTO                    | `DTO/`                           |
| Enum (domena)          | `Enums/`                         |
| Exception (domena)     | `Exceptions/`                    |
| Pipe                   | `Pipes/`                         |
| Command (domena)       | `Commands/`                      |
| **Klasa biznesowa**    | **root modułu — BEZ podfolderu** |

### W `app/System/` (cross-cutting, framework-glue)

`Providers/`, `Middleware/`, `Trait/`, `Utilities/` (helpers, Date/Time/Files), `Enums/`, `Exceptions/`, `Jobs/`, `Listeners/`, `Commands/`, `Controllers/` (healthcheck/Swagger), `Testing/`, wrappery protokołów (`RabbitMq/`, `Redis/` itp.).

### Klasycznie (poza `app/`)

| Co              | Gdzie                                                      |
| --------------- | ---------------------------------------------------------- |
| Migracje        | `database/migrations/`                                     |
| Seedery         | `database/seeders/`                                        |
| Factory         | `database/factories/`                                      |
| Testy           | `tests/Feature/<Module>/`, `tests/Unit/<Module>/`          |
| Configi         | `config/<foo>.php`                                         |
| Trasy           | `routes/web.php`, `routes/api.php`                         |
| Helpers (plik)  | `app/System/Utilities/helpers.php` (przez `autoload.files`) |

### Integracje zewnętrzne

- Klient zewnętrznego API → **osobny moduł** `app/<Vendor>/` (np. `app/Stripe/`).
- W tym module klasa może nazywać się `<Vendor>Service` — to **jedyny** dopuszczalny przypadek użycia sufiksu `Service`.

---

## Modele

- Eloquent → `<Foo>Model`. Pivot → `<Foo><Bar>Pivot`.
- Modele dostarczane przez paczki 3rd-party — **zostawiamy w spokoju**, nie zmieniamy nazw, nie przenosimy.
- Override modelu paczki: `class RoleModel extends BaseRole` + wpis w configu paczki (`config/permission.php`).
- Observer podpinamy **atrybutem** `#[ObservedBy(FooModelObserver::class)]` na modelu — **nie** przez ServiceProvider.

---

## Controllers — REST + single-action

- W ~99% kontrolerów **wyłącznie** metody REST: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`.
- **Nie wymyślamy** własnych nazw metod (`list()`, `save()`, `cancelOrder()`).
- Akcja nie-REST (np. `cancel`, `archive`, `publish`) → **osobny kontroler** `<Resource><Action>Controller`.
- Single-action **bez formularza** → kontroler Invokable z `__invoke()`.
- Single-action **z formularzem** → wciąż REST (`create` + `store`), nawet gdy kontroler ma jedną „logiczną" akcję.
- Reguła wyboru: **pasuje do REST → używaj REST**. `__invoke()` tylko jeśli żadna metoda REST nie pasuje semantycznie.

---

## Klasy biznesowe — kiedy wydzielać

**TAK** — wydziel klasę w roocie modułu, gdy operacja:

- składa się z kilku kroków (np. zwrot: cofnij stan → korekta faktury → zwrot pieniędzy → notyfikacja),
- zawiera regułę domenową, którą chcesz testować w izolacji (algorytm cen, walidacja przejścia statusu, generator PDF),
- integruje wiele modułów albo wywołuje zewnętrzną usługę,
- jest na tyle obszerna, że obciąża kontroler.

**NIE** — zostaw w kontrolerze (lub na modelu), gdy operacja:

- to wrapper jednego wywołania Eloquent (`Model::create($data)`),
- to 1–3 linie bez gałęzi i decyzji,
- mogłaby równie dobrze być metodą na modelu.

**Cel:** ani god-object, ani 50 mikro-klas. Klasa istnieje, gdy ma co robić.

**Nazewnictwo:** `<Module><Action>` (czasownik akcji) lub `<Module><Role>` z sufiksem `-or`/`-er` (rzeczownik z rolą). Przykłady: `OrderRefund`, `OrderPriceCalculator`, `InvoiceGenerator`, `OrderStatusTransition`.

---

## Anty-wzorce (czerwone flagi)

- ❌ `OrderService`, `UserService`, `PaymentService` z metodami CRUD.
- ❌ Single-purpose dla każdej akcji REST (`OrderCreate`, `OrderUpdate`, `OrderShow`, `OrderDelete`) — to ten sam problem co Service Layer w innej skórze (klikologia).
- ❌ Klasa biznesowa będąca wrapperem na jedno wywołanie Eloquent.
- ❌ Folder `Services/`, `Actions/`, `UseCases/` wewnątrz modułu.
- ❌ Foldery `app/Http/`, `app/Models/`, `app/Services/`, `app/Providers/` na poziomie głównym.
- ❌ Wymyślne nazwy metod kontrolera (`list`, `save`, `cancelOrder`, `doSomething`).
- ❌ Akcja nie-REST jako kolejna metoda w kontrolerze CRUD (zamiast osobnego kontrolera).
- ❌ ServiceProvider per moduł.
- ❌ Routing per moduł, migracje per moduł, configi per moduł.
- ❌ Wymóg fasady modułu (klasa `Order.php` jako obowiązkowa).
- ❌ Namespace `App\Modules\Order` albo `App\Domain\Order`.
- ❌ Tworzenie pliku/klasy „bo musi być" — jeśli nie ma logiki do nazwania, nie tworzymy klasy.

---

## Rejestracja — `bootstrap/app.php`

Generyczne auto-discovery z modułów:

```php
->withCommands(commands: glob(__DIR__.'/../app/*/Commands'))
->withEvents(discover: glob(__DIR__.'/../app/*/Listeners'))
```

Hierarchia rejestracji:

1. **Atrybut PHP** / auto-discovery (np. `#[ObservedBy(...)]` na modelu) — pierwszy wybór.
2. **Generycznie w `bootstrap/app.php`** przez `glob()` po katalogach modułów.
3. **Dopiero gdy się nie da** → `App\System\Providers\AppServiceProvider`.

Nie tworzymy ServiceProviderów per moduł.

---

## Namespace

- `App\<Module>` mapuje 1:1 do `app/<Module>/`.
- **Płasko**: nie `App\Modules\<Module>`, nie `App\Domain\<Module>`.
- `composer.json` autoload PSR-4: `"App\\": "app/"`.

---

## Reguła decyzyjna `System/` vs moduł

Pytanie: *„Czy ta klasa jest częścią konkretnej domeny biznesowej?"*

- **TAK** → idzie do tego modułu.
- **NIE** → `app/System/`.

Wrappery niskopoziomowych protokołów (RabbitMQ, Redis Streams) — `app/System/<Protocol>/`. Klient konkretnej zewnętrznej usługi (Stripe, AWS SQS, SendGrid) — osobny moduł `app/<Vendor>/`.

---

## Checklist — nowy moduł

- [ ] Nazwa: PascalCase, liczba pojedyncza.
- [ ] Wszystkie modele Eloquent mają sufix `Model`. Pivoty — `Pivot`.
- [ ] Brak pliku z sufiksem `Service` w module (chyba że to moduł integracyjny, wrapper zewn. API).
- [ ] Klasy biznesowe (jeśli istnieją) — w roocie modułu, wzorzec `<Module><Action>`, **bez** podfolderu `Services/`/`Actions/`.
- [ ] Controllers mają tylko metody REST. Akcje nie-REST → osobne kontrolery.
- [ ] Observery podpięte przez `#[ObservedBy]`, nie przez ServiceProvider.
- [ ] Trasy w `routes/web.php` lub `routes/api.php`, importują kontrolery z modułu.
- [ ] Migracje w `database/migrations/`.
- [ ] Testy w `tests/Feature/<Module>/` lub `tests/Unit/<Module>/`.
- [ ] `Commands/` i `Listeners/` (jeśli istnieją) są auto-discoverowane przez `bootstrap/app.php`.

---

## Przykład — wzorcowy moduł

```
app/Order/
├── Models/
│   ├── OrderModel.php
│   └── OrderItemPivot.php
├── Controllers/
│   ├── OrderController.php           # CRUD: index/create/store/show/edit/update/destroy
│   ├── OrderApiController.php        # JSON API
│   └── OrderCancelController.php     # akcja nie-REST (Invokable lub REST)
├── Requests/
│   ├── OrderCreateRequest.php
│   └── OrderUpdateRequest.php
├── Resources/
│   └── OrderResource.php
├── Observers/
│   └── OrderModelObserver.php
├── Events/
│   └── OrderCreatedEvent.php
├── Jobs/
│   └── OrderRefundJob.php
├── DTO/
│   └── OrderRefundDTO.php
├── OrderRefund.php                   # ← klasa biznesowa (wieloetapowy proces)
└── OrderPriceCalculator.php          # ← klasa biznesowa (algorytm)
```
