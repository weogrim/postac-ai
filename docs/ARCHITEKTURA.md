# ARCHITEKTURA — Modularny Laravel

Ten dokument opisuje **konwencję organizacji projektów Laravel używaną w naszych aplikacjach**. Jest agnostyczny — nie odwołuje się do żadnej konkretnej domeny. Służy jako:

1. Referencja dla zespołu (gdzie co kłaść, jak nazywać).
2. Instrukcja dla narzędzi AI: jak przerobić projekt z klasycznej struktury Laravela (`app/Http`, `app/Models`, `app/Services`) na strukturę modularną.

> Wymagany Laravel: **11+** (z uwagi na `bootstrap/app.php` i atrybuty modeli takie jak `#[ObservedBy]`).

---

## 1. TL;DR

- W `app/` **nie ma** folderów `Http/`, `Models/`, `Services/`, `Console/`, `Exceptions/`, `Providers/`. Są tylko **moduły** — po jednym katalogu na domenę biznesową — i jeden specjalny katalog `System/`.
- Po otwarciu `app/` od razu widać **z czego składa się projekt** (np. `Order`, `Invoice`, `User`), a nie jakie warstwy techniczne ma framework.
- Każdy moduł zawiera wszystko, czego potrzebuje (modele, kontrolery, requesty, resourcy, eventy, joby, observery, klasy biznesowe).
- Modele Eloquent mają sufix `Model` (`OrderModel.php`), pivoty mają sufix `Pivot` (`OrderItemPivot.php`). Dzięki temu „czysta" nazwa (`Order`, `Invoice`) zostaje **wolna dla klas logiki biznesowej**.
- **Service Layer** w klasycznym sensie Laravelowym jest **anty-wzorcem**. Klasa o nazwie kończącej się na `Service` istnieje **tylko** dla wrapperów zewnętrznych systemów (np. `StripeService`).
- Routing, testy, migracje, seederzy, factory i configi pozostają w **klasycznych miejscach Laravela** — nie modularyzujemy ich.

---

## 2. Filozofia

1. **Domena ponad warstwę.** Folder w `app/` reprezentuje koncept biznesowy, nie warstwę techniczną. Programista wchodzący do projektu po raz pierwszy ma rozumieć, **co** ten projekt robi, zanim zrozumie **jak** to robi.
2. **Wygoda ponad ortodoksję.** Nie robimy z modułów „package-style Laravel modules" z własnymi providerami, route'ami i configami. To prowadzi do walki z frameworkiem i nadmiaru boilerplate'u. Zachowujemy zmiany tam, gdzie wygrywają domenowo (`app/`), i klasykę tam, gdzie klasyka działa (testy, migracje, configi, routing).
3. **Brak fasad / wejść do modułu.** Moduł nie ma żadnej wymaganej klasy wejściowej. To po prostu folder z klasami w spójnym namespace'ie.
4. **Brak Service Layer.** Klasy biznesowe nazywamy od **akcji** lub **roli**, nie od warstwy. Wydzielamy je tylko wtedy, gdy operacja ma realną logikę domenową — patrz §5.2.
5. **Auto-discovery > rejestracja ręczna.** Wykorzystujemy mechanizmy Laravela 11+ (atrybuty PHP, `withCommands`, `withEvents`) zamiast pisać własne service providery dla każdego modułu.
6. **Luźne reguły, nie restrykcyjne.** Nie ma ścisłej taksonomii „moduł domenowy" / „moduł infrastrukturalny" / „moduł integracyjny". Moduł to moduł.
7. **Logika domyślnie żyje w kontrolerze.** Większość kontrolerów to „weź dane → zwaliduj → wrzuć do bazy → zwróć response". Ta logika jest jednorazowa, nie-powtarzalna i już dobrze wyizolowana (siedzi w module, w sufiksowanym pliku). Operacje trywialne zostają w kontrolerze — nie wyciągamy ich proaktywnie do klas biznesowych. Klasę biznesową wydzielamy dopiero, kiedy kontroler tłuścieje albo gdy ta sama logika faktycznie ma być wywołana z drugiego miejsca. Patrz §5.2 i §7.1.

---

## 3. Co widzisz po otwarciu `app/`

Klasyczny Laravel:

```
app/
├── Console/
├── Exceptions/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Models/
├── Providers/
└── Services/
```

Nasz Laravel:

```
app/
├── Order/            ← domena
├── Invoice/          ← domena
├── User/             ← domena
├── Stripe/           ← integracja z zewnętrznym API
└── System/           ← framework-glue, rzeczy bezdomne
```

Pierwszy kontakt z projektem = lista jego domen biznesowych. Nie musisz wiedzieć nic o frameworku, żeby zrozumieć, **z czego** projekt jest zbudowany.

---

## 4. Anatomia modułu

Moduł to folder w `app/` o nazwie:

- **PascalCase**,
- w **liczbie pojedynczej** (zgodnie z duchem DDD: `Order`, nie `Orders`; `User`, nie `Users`). Wyjątki dopuszczalne tylko gdy singular brzmi nienaturalnie po angielsku (np. `News` jest niepoliczalne).
- jedno słowo lub złożenie bez separatorów (`WorkTime`, `DefaultShift`).

Przykładowa pełna struktura modułu (żaden podfolder nie jest **obowiązkowy** — twórz tylko te, których faktycznie używasz):

```
app/Order/
├── Commands/                     # Artisan commands
├── Controllers/                  # kontrolery (HTTP, API, Inertia)
├── DTO/                          # Data Transfer Objects
├── Enums/                        # enumy specyficzne dla domeny
├── Events/                       # eventy
├── Exceptions/                   # wyjątki specyficzne dla domeny
├── Jobs/                         # joby (queue)
├── Listeners/                    # listenery eventów
├── Mail/                         # Mailable
├── Models/                       # Eloquent modele i pivoty
│   ├── OrderModel.php
│   └── OrderItemPivot.php
├── Notifications/                # Notification
├── Observers/                    # Observery modeli
├── Pipes/                        # klasy do Laravel\Pipeline
├── Requests/                     # FormRequesty
├── Resources/                    # JsonResource'y
├── OrderRefund.php               # ← klasa biznesowa (root modułu)
├── OrderPriceCalculator.php      # ← klasa biznesowa
└── OrderStatusTransition.php     # ← klasa biznesowa
```

**Klasy logiki biznesowej leżą bezpośrednio w roocie modułu**, bez żadnego podfolderu typu `Services/`, `Actions/`, `UseCases/`. To jest świadoma decyzja — patrz §5.

### Konwencja namespace

PSR-4 mapuje 1:1: folder → namespace.

```php
// Plik: app/Order/Models/OrderModel.php
namespace App\Order\Models;

// Plik: app/Order/OrderRefund.php
namespace App\Order;
```

W `composer.json` wystarczy standardowe:

```json
"autoload": {
    "files": [
        "app/System/Utilities/helpers.php"
    ],
    "psr-4": {
        "App\\": "app/",
        "Database\\Factories\\": "database/factories/",
        "Database\\Seeders\\": "database/seeders/"
    }
}
```

Płaska struktura — **nie** `App\Modules\Order`, **nie** `App\Domain\Order`. Po prostu `App\Order`.

---

## 5. Klasy logiki biznesowej — sedno modułu

### 5.1 Gdzie leżą

W **roocie modułu**, nie w żadnym podfolderze.

```
app/Order/
├── Models/
│   └── OrderModel.php
├── OrderRefund.php           ← klasa biznesowa
├── OrderPriceCalculator.php  ← klasa biznesowa
└── OrderStatusTransition.php ← klasa biznesowa
```

### 5.2 Kiedy wydzielić osobną klasę

Ta architektura **nie jest** typu „każda akcja = osobna klasa". Wzorca single-purpose / Action class / „one class per use case" **świadomie nie promujemy** — generuje boilerplate i klikologię. Klasa `OrderCreate` zawierająca jedną linię `OrderModel::create($data)` to wrapper na Eloquenta, który niczego nie dodaje.

Klasę biznesową wydzielamy wtedy, kiedy operacja **ma realną logikę**:

- składa się z kilku kroków (np. zwrot zamówienia: cofnij stan magazynowy → wystaw korektę faktury → zwróć pieniądze przez bramkę → wyślij powiadomienie),
- zawiera regułę domenową, którą chcesz testować w izolacji (liczenie ceny z rabatami, walidacja przejścia statusu, generator faktury PDF),
- integruje wiele modułów lub wywołuje zewnętrzną usługę,
- jest na tyle obszerna, że obciąża kontroler.

Operacje **trywialne zostają w kontrolerze** (lub jako metoda na modelu, jeśli to czysta operacja na danych). Kontroler woła `OrderModel::create($request->validated())` bezpośrednio — nie ma potrzeby tworzyć klasy `OrderCreate` tylko po to, by ją mieć.

Granica „za mało klas" / „za dużo klas":

| Sytuacja                                                       | Decyzja                                              |
| -------------------------------------------------------------- | ---------------------------------------------------- |
| `create()` to `Model::create($data)` (+ ewentualny event)      | Zostaje w kontrolerze.                               |
| `cancel()` to zmiana statusu i koniec                          | Zostaje w kontrolerze (lub metoda na modelu).        |
| `refund()` to wieloetapowy proces z efektami w kilku miejscach | Wydziel klasę `OrderRefund`.                         |
| `calculatePrice()` to algorytm wymagający testów               | Wydziel klasę `OrderPriceCalculator`.                |
| `OrderService` z 12 metodami CRUD                              | Rozbij — ale po linii **logiki**, nie po linii akcji REST. Niektóre metody znikną zupełnie, niektóre staną się klasami. |

Cel: **ani god-object, ani 50 mikro-klas**. Klasa istnieje wtedy, kiedy ma co robić.

### 5.3 Jak je nazywamy

Wzorzec: **`<Module><Action>`** lub **`<Module><Role>`** — czasownik akcji albo rzeczownik z rolą.

| Nazwa                       | Co reprezentuje                                                    |
| --------------------------- | ------------------------------------------------------------------ |
| `OrderRefund`               | zwrot zamówienia (cofnięcie stanu, korekta faktury, zwrot środków) |
| `OrderPriceCalculator`      | wyliczenie ceny zamówienia z rabatami i podatkami                  |
| `OrderStatusTransition`     | walidowane przejście statusu (state machine)                       |
| `InvoiceGenerator`          | wygenerowanie faktury z zamówienia                                 |
| `InvoicePdfRenderer`        | renderowanie faktury do PDF                                        |

Dopuszczalna swoboda — czasem czasownik (`OrderRefund`), czasem rzeczownik z sufiksem `-or`/`-er` (`OrderPriceCalculator`, `InvoiceGenerator`). **Jedyne, czego unikamy, to sufix `Service`.**

### 5.4 Dlaczego klasa biznesowa, nie `Service`?

Bo `Service` z definicji nic nie znaczy. To worek na luźną logikę i magnes na god-objecty (`UserService` z 2000 linii). Kiedy klasa nazywa się `OrderRefund`, jej zakres jest oczywisty z nazwy. Kiedy nazywa się `OrderService` — nie wiadomo, co tam jest, dopóki nie otworzysz pliku.

### 5.5 Dlaczego model ma sufix `Model`?

Właśnie po to, by nazwa **`Order`** była wolna dla klas domenowych:

```php
// app/Order/Models/OrderModel.php
class OrderModel extends Model { ... }

// app/Order/Order.php          ← rzadko potrzebne; tylko jeśli ma realną logikę domeny, nie jako wrapper na model
class Order { ... }

// app/Order/OrderRefund.php
class OrderRefund { ... }
```

Bez sufiksu `Model` nazwy `Order` (klasa domenowa) i `Order` (model Eloquent) kolidowałyby — i znów lądujemy w ślepej uliczce „nazwijmy to OrderService".

### 5.6 Brak fasady modułu

Moduł **nie wymaga** klasy o nazwie identycznej z nazwą modułu (np. `Order.php` w `app/Order/`). To może istnieć, jeśli zachodzi naturalna potrzeba domenowa, ale nie jest to wymagane ani zalecane.

---

## 6. Modele Eloquent

### 6.1 Sufix `Model`

**Każdy** model Eloquent w aplikacji ma sufix `Model`:

```php
// app/Order/Models/OrderModel.php
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderModel extends Model
{
    protected $table = 'orders';
    // ...
}
```

### 6.2 Pivoty

Tabele pośrednie (`many-to-many`) reprezentujemy dedykowanym modelem z sufiksem `Pivot` (zamiast `Model`):

```php
// app/Order/Models/OrderItemPivot.php
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class OrderItemPivot extends Pivot
{
    protected $table = 'order_items';
    // ...
}
```

### 6.3 Modele z paczek third-party

Modele dostarczane przez paczki (Spatie Permission, Laravel Sanctum, Spatie Settings itp.) **zostawiamy w spokoju** — nie zmieniamy ich nazw, nie przenosimy do modułów. Działają tam, gdzie są.

**Wyjątek:** jeśli model paczki jest często używany i paczka wspiera nadpisywanie modelu, możemy stworzyć własną wersję, dziedziczącą po modelu paczki, w odpowiednim module:

```php
// app/User/Models/RoleModel.php
namespace App\User\Models;

use Spatie\Permission\Models\Role as BaseRole;

class RoleModel extends BaseRole
{
    // ...
}
```

Następnie wskazujemy własną klasę w configu paczki (np. `config/permission.php`: `'role' => \App\User\Models\RoleModel::class`).

### 6.4 Observery

Observery żyją w `<Module>/Observers/` i nazywają się **`<NazwaModelu>Observer`** (czyli sufix `Model` zostaje w nazwie observera):

```
app/Order/Observers/OrderModelObserver.php
```

Rejestracja **nie wymaga** kodu w żadnym ServiceProviderze — używamy atrybutu `#[ObservedBy]` na modelu (Laravel 11+):

```php
use App\Order\Observers\OrderModelObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([OrderModelObserver::class])]
class OrderModel extends Model { ... }
```

---

## 7. HTTP — Controllers / Requests / Resources

W tej architekturze **nie istnieje folder `app/Http/`**. Cały kod związany z HTTP jest wewnątrz modułu, do którego należy.

### 7.1 Controllers

```
app/Order/Controllers/
├── OrderController.php          # CRUD: index/create/store/show/edit/update/destroy
├── OrderApiController.php       # endpointy JSON API
└── OrderCancelController.php    # pojedyncza akcja nie-REST (Invokable lub REST)
```

- Sufix `Controller`. Dla endpointów JSON API dodatkowy sufix `Api`: `OrderApiController`.
- **Kontroler nie jest „chudy z założenia"** — siedzi w nim logika obsługi requestu (walidacja → operacja na danych → response). Klasy biznesowe wydzielamy dopiero, gdy ma to sens — patrz §2.7, §5.2, §8.2.

#### Trzymamy się metod REST

W ~99% przypadków kontroler ma **wyłącznie** standardowe metody REST: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`. To jest twarda zasada — nie wymyślamy własnych nazw metod jak `list()`, `save()`, `cancelOrder()`.

#### Akcje nie-REST → osobny kontroler

Gdy w `OrderController` chciałbyś dopisać metodę `cancel()`, `archive()`, `publish()` — zamiast tego twórz **osobny kontroler** dla tej akcji. Wzorzec nazwy: `<Resource><Action>Controller`.

```
app/Order/Controllers/
├── OrderController.php           # standardowy CRUD
├── OrderCancelController.php     # akcja: anulowanie
├── OrderArchiveController.php    # akcja: archiwizacja
└── OrderPublishController.php    # akcja: publikacja
```

#### Single-action controllers — dwa warianty

**1. Bez formularza** (lub bez naturalnego podziału na „wyświetl coś" + „wykonaj") → kontroler **Invokable** z metodą `__invoke()`:

```php
class OrderCancelController
{
    public function __invoke(OrderCancelRequest $request, OrderModel $order)
    {
        // ... wykonanie akcji ...
        return redirect()->back()->with('success', 'order.cancel.success');
    }
}
```

**2. Z formularzem** (wyświetl formularz + wykonaj akcję) → **trzymamy się metod REST** nawet w jedno-akcyjnym kontrolerze:

```php
class OrderRefundController
{
    public function create(OrderModel $order)
    {
        // widok formularza zwrotu
    }

    public function store(OrderRefundRequest $request, OrderModel $order)
    {
        // wykonanie zwrotu
    }
}
```

Reguła: **pasuje do REST → używaj REST**, nawet jeśli kontroler ma tylko jedną „logiczną" akcję. Sięgamy po `__invoke()` tylko wtedy, gdy żadna z metod REST nie pasuje semantycznie.

### 7.2 Requests

```
app/Order/Requests/
├── OrderCreateRequest.php
└── OrderUpdateRequest.php
```

- Klasy `FormRequest`.
- Sufix `Request`.

### 7.3 Resources

```
app/Order/Resources/
├── OrderResource.php
└── OrderListResource.php
```

- Klasy `JsonResource`.
- Sufix `Resource`.

### 7.4 Subdivision per audience (opcjonalnie)

Jeśli aplikacja ma kilka odrębnych „odbiorców" (np. panel admina, panel klienta, publiczne API z osobnymi endpointami) i prowadzi to do dużej liczby plików w jednym module, **dopuszczalne** jest wprowadzenie podziału:

```
app/Order/
├── Controllers/
│   ├── Admin/
│   ├── Customer/
│   └── Api/
└── Requests/
    ├── Admin/
    ├── Customer/
    └── Api/
```

To jednak **nie jest reguła** — to reakcja na konkretne potrzeby projektu. W większości projektów płaski układ wystarcza.

---

## 8. Service Layer — anty-wzorzec

### 8.1 Czego unikamy

Klasycznego Laravel Service Layer:

```
app/Services/
├── OrderService.php       ← ❌ create(), update(), delete(), refund()...
├── UserService.php        ← ❌ register(), login(), logout(), updateProfile()...
└── PaymentService.php     ← ❌ wszystko o płatnościach
```

Problemy:

1. **God object.** `OrderService` rośnie do tysięcy linii.
2. **Brak granularności.** Nie da się sensownie wstrzyknąć „tylko części" tej logiki.
3. **Mylące nazwy.** „Service" nic nie znaczy — to kontrakt o niczym.
4. **Sztuczna warstwa.** Service Layer często duplikuje API Eloquent (`OrderService::create($data)` to opakowany `OrderModel::create($data)`).

### 8.2 Co robimy zamiast

Dwie zasady, w tej kolejności:

1. **Trywialnych operacji nie wydzielamy.** Jeżeli „akcja" to opakowanie jednego wywołania Eloquenta (`OrderModel::create($data)`), zostaje w kontrolerze (lub na modelu). Service Layer jest zły m.in. **dlatego**, że wymusza klasę dla operacji, która klasy nie potrzebuje. Tworzenie zamiast `OrderService` zestawu `OrderCreate` / `OrderUpdate` / `OrderCancel` / `OrderShow` / `OrderDelete` to ten sam problem w innej skórze — klikologia.

2. **Klasy biznesowe wydzielamy dla operacji z realną logiką** — w roocie odpowiedniego modułu, nazwane od **tego, co robią**, nie od metody REST. Patrz §5.2.

Przykład — zamiast god-objectu `OrderService` z `create / update / cancel / refund / calculatePrice` zwykle wychodzi:

- `create`, `update` — w kontrolerze, wprost na modelu.
- `cancel` — w kontrolerze, jeśli to tylko zmiana statusu; wydzielona klasa `OrderCancel` tylko jeśli pociąga efekty domenowe (powiadomienia, zwolnienie rezerwacji, korekta dokumentów).
- `refund` — wydzielone do `app/Order/OrderRefund.php`, bo to wieloetapowy proces.
- `calculatePrice` — wydzielone do `app/Order/OrderPriceCalculator.php`, bo to czysta logika domenowa wymagająca testów.

Wynik: nie god-object **i** nie 50 mikro-klas. Każda istniejąca klasa ma co robić.

### 8.3 Kiedy „Service" JEST dopuszczalny

**Tylko jako wrapper integracji z zewnętrznym systemem** — gdy „service" oznacza „cudzy serwis, do którego się łączymy".

```
app/Stripe/
├── StripeService.php          ← klient HTTP do Stripe API
├── StripeChargeService.php    ← OK, jeśli rozbijamy klienta na kawałki
└── DTO/
    ├── ChargeRequestDTO.php
    └── ChargeResponseDTO.php
```

```php
namespace App\Stripe;

class StripeService
{
    public function charge(int $amountCents, string $currency, string $token): array
    {
        // wywołanie Stripe API
    }
}
```

Reguła praktyczna: **jeśli klasa nie wykonuje requestu HTTP / wywołania SDK / komunikacji z zewnętrznym systemem, nie może mieć w nazwie `Service`.**

---

## 9. `app/System/` — framework-glue

### 9.1 Co tam trafia

Wszystko, co nie należy do żadnej domeny biznesowej, ale gdzieś musi być:

```
app/System/
├── Controllers/         # healthcheck, Swagger UI, generyczne
├── Enums/               # enumy systemowe (np. role, statusy globalne)
├── Exceptions/          # globalne wyjątki
├── Jobs/                # joby systemowe
├── Listeners/           # listenery systemowe
├── Middleware/          # WSZYSTKIE Laravel middleware
├── Providers/           # WSZYSTKIE Laravel ServiceProvidery
├── Requests/            # generyczne FormRequesty
├── Trait/               # globalne traity (np. SearchTrait używany w wielu modelach)
├── Testing/             # bazowe klasy / helpery do testów
├── Utilities/           # helpers.php, klasy pomocnicze (Date, Time, Files, ...)
└── Commands/            # generyczne komendy (specyficzne komendy żyją w modułach)
```

### 9.2 Reguła decyzyjna

Pytanie: „Czy ta klasa jest częścią konkretnej domeny biznesowej?"

- **TAK** → idzie do tego modułu.
- **NIE** → idzie do `System/`.

Przykłady:

| Klasa                      | Gdzie                          |
| -------------------------- | ------------------------------ |
| `OrderModel`               | `app/Order/Models/`            |
| `OrderRefund`              | `app/Order/`                   |
| `AuthMiddleware`           | `app/System/Middleware/`       |
| `LocaleMiddleware`         | `app/System/Middleware/`       |
| `AppServiceProvider`       | `app/System/Providers/`        |
| `helpers.php`              | `app/System/Utilities/`        |
| `SearchTrait` (cross-cut)  | `app/System/Trait/`            |
| `OrderRefundJob`           | `app/Order/Jobs/`              |
| `CleanupOldSessionsJob`    | `app/System/Jobs/`             |

### 9.3 Wrappery niskopoziomowych protokołów

Klasy obsługujące **protokół** (nie konkretną zewnętrzną usługę), z których korzystają różne moduły — np. wrapper RabbitMQ, wrapper Redis Streams — mogą trafiać do `System/`. To nie jest osobna domena ani integracja z konkretnym dostawcą, tylko warstwa techniczna.

```
app/System/RabbitMq/
├── RabbitMqClient.php
├── Connection.php
└── ...
```

Jeśli jednak wrapper jest mocno specyficzny dla jednej zewnętrznej usługi (np. AWS SQS, Stripe, SendGrid), zostaje **własnym modułem** w `app/`.

---

## 10. Czego NIE modularyzujemy

Lista miejsc, w których **zachowujemy klasyczny układ Laravela**. Powód we wszystkich punktach jest ten sam: nie chcemy walczyć z domyślnym setupem frameworka.

### 10.1 Routing — `routes/`

Routing zostaje w `routes/`, jak w klasycznym Laravelu:

```
routes/
├── web.php
├── api.php
├── console.php
└── channels.php
```

Wewnątrz tych plików **importujemy** kontrolery z modułów:

```php
use App\Order\Controllers\OrderController;
use App\Order\Controllers\OrderApiController;

Route::get('/orders', [OrderController::class, 'index']);
```

W większych projektach dopuszczalne jest rozbicie `web.php` na pliki per audience (`routes/admin.php`, `routes/api.php`), montowane w `bootstrap/app.php`. To opcja, nie reguła.

### 10.2 Testy — `tests/`

Testy zostają w klasycznej strukturze PHPUnit:

```
tests/
├── Feature/
├── Unit/
├── TestCase.php
└── TestCaseDatabase.php
```

Najpierw dzielimy **funkcjonalnie** (Feature/Unit), dopiero potem opcjonalnie **per moduł**:

```
tests/Feature/Order/OrderRefundTest.php
tests/Unit/Order/OrderPriceCalculatorTest.php
```

### 10.3 Migracje, seeders, factories — `database/`

Pozostają w klasycznej strukturze:

```
database/
├── factories/
├── migrations/
└── seeders/
```

### 10.4 Konfiguracja — `config/`

Pliki configów żyją własnym życiem w `config/`. **Plik configa nie musi reprezentować modułu** — może to być config aplikacyjny (`app.php`), config funkcji przekrojowej (`audit.php`, `permission.php`) albo wewnętrzny „kombajn" (`<projectname>.php`) zbierający różne ustawienia.

```
config/
├── app.php
├── audit.php
├── permission.php
├── queue.php
└── <project>.php
```

### 10.5 ServiceProvidery — minimalnie i centralnie

**Nie tworzymy ServiceProvidera per moduł.** Mamy jeden centralny `App\System\Providers\AppServiceProvider`, plus opcjonalnie kilka generycznych (np. `EventServiceProvider`, `RouteServiceProvider`) jeśli potrzeba.

Filozofia rejestracji rzeczy:

1. **Jeśli się da, używaj atrybutów PHP / auto-discovery.**
   - `#[ObservedBy(...)]` na modelu zamiast `Model::observe(...)` w providerze.
2. **Jeśli się da, rejestruj generycznie w `bootstrap/app.php`** używając `glob()` po katalogach modułów.
3. **Dopiero jeśli się nie da generycznie**, wpis ląduje w `App\System\Providers\AppServiceProvider`.

Przykład generycznej rejestracji w `bootstrap/app.php` (Laravel 11+):

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withProviders()
    ->withRouting(
        web:      __DIR__.'/../routes/web.php',
        api:      __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ...
    })
    // Auto-discover Artisan commands z każdego modułu
    ->withCommands(commands: glob(__DIR__.'/../app/*/Commands'))
    // Auto-discover Listeners z każdego modułu
    ->withEvents(discover: glob(__DIR__.'/../app/*/Listeners'))
    ->withExceptions(function (Exceptions $exceptions) {
        // ...
    })
    ->create();
```

Dzięki temu nowy moduł z folderem `Commands/` lub `Listeners/` jest **automatycznie** podpięty bez żadnych zmian w kodzie rejestracyjnym.

---

## 11. Lokalizacja / przepływ requestu

Dla orientacji — pełny przepływ HTTP requestu w tej architekturze:

```
HTTP Request
    │
    ▼
routes/web.php                                  (klasycznie)
    │
    ▼
App\Order\Controllers\OrderController          (moduł)
    │
    ├── walidacja:    App\Order\Requests\OrderRefundRequest
    ├── logika:       App\Order\OrderRefund    (klasa biznesowa — wydzielona, bo wieloetapowa)
    │                     ├── App\Order\Models\OrderModel
    │                     └── App\Stripe\StripeService    (integracja)
    └── serializacja: App\Order\Resources\OrderResource
    │
    ▼
HTTP Response
```

> Trywialne akcje (`store`, `update`, `destroy`) **nie mają osobnej klasy biznesowej** — kontroler woła model wprost. Diagram pokazuje przepływ z wydzieloną klasą tylko po to, żeby zilustrować, gdzie ona się wpina, gdy już istnieje.

---

## 12. Refactoring klasycznego Laravela → modularny

Przepis dla AI / dewelopera, który dostaje istniejący projekt Laravelowy i ma go przepisać na tę konwencję.

### Krok 1 — Zidentyfikuj domeny

Otwórz `app/Models/` i `app/Http/Controllers/`. Pogrupuj klasy w domeny biznesowe. Przykład:

```
Modele: User, Profile, Address           → moduł User
Modele: Order, OrderItem, OrderStatus    → moduł Order
Modele: Invoice, InvoiceLine             → moduł Invoice
Kontrolery: StripeController, ...        → moduł Stripe (integracja)
```

Jeśli waha się między dwoma modułami — wybierz ten, do którego należy główny agregat. Modele można później przenosić.

### Krok 2 — Utwórz foldery modułów

```bash
mkdir -p app/{Order,Invoice,User,System}
mkdir -p app/System/{Providers,Middleware,Controllers,Exceptions,Trait,Utilities,Enums,Jobs,Listeners}
```

### Krok 3 — Przenieś modele i dodaj sufix `Model`

Dla każdego modelu:

1. Plik `app/Models/Order.php` → `app/Order/Models/OrderModel.php`.
2. Zmień nazwę klasy: `class Order` → `class OrderModel`.
3. Zmień namespace: `namespace App\Models` → `namespace App\Order\Models`.
4. Update wszystkich użyć: `App\Models\Order` → `App\Order\Models\OrderModel`.
5. Pivoty (`many-to-many`) zmień analogicznie z sufiksem `Pivot`: `OrderItem` → `OrderItemPivot`.

> Wyjątek: modele dostarczane przez paczki (np. `Spatie\Permission\Models\Role`) zostają w paczce — nie kopiujemy ich.

### Krok 4 — Przenieś kontrolery

Każdy kontroler `app/Http/Controllers/<Foo>Controller.php` → `app/<Module>/Controllers/<Foo>Controller.php`.

Update namespace: `App\Http\Controllers` → `App\<Module>\Controllers`.

Jeśli kontroler obsługuje **wyłącznie** endpointy API/JSON, dopisz sufix `Api`: `OrderController` → `OrderApiController`.

### Krok 5 — Przenieś FormRequesty i Resourcy

- `app/Http/Requests/` → `app/<Module>/Requests/`.
- `app/Http/Resources/` → `app/<Module>/Resources/`.

Update namespace'ów i wszystkich importów.

### Krok 6 — Przenieś Observery, Eventy, Listenery, Joby, Notifications, Mail

- `app/Observers/<FooModel>Observer.php` → `app/<Module>/Observers/<FooModel>Observer.php`.
- `app/Events/...`                       → `app/<Module>/Events/...`.
- `app/Listeners/...`                    → `app/<Module>/Listeners/...` lub `app/System/Listeners/` jeśli nie należy do domeny.
- `app/Jobs/...`                         → `app/<Module>/Jobs/...` lub `app/System/Jobs/`.
- `app/Notifications/...`                → `app/<Module>/Notifications/...`.
- `app/Mail/...`                         → `app/<Module>/Mail/...`.

Dla observerów dodaj atrybut `#[ObservedBy]` na modelu i **usuń** rejestrację ręczną z ServiceProvidera.

### Krok 7 — Service Layer → kontroler / klasa biznesowa

Dla każdego pliku `app/Services/<Foo>Service.php`:

1. **Czy cały plik to wrapper zewnętrznego API?** Jeśli tak — przenieś jako moduł integracyjny: `app/<Vendor>/<Vendor>Service.php` (np. `app/Stripe/StripeService.php`). Sufix `Service` zostaje, klasa zostaje w jednym kawałku.

2. **Jeśli nie**, przeanalizuj **metoda po metodzie** i zadaj pytanie: *czy ta metoda ma realną logikę, czy to wrapper na Eloquenta?*

   - **Wrapper na Eloquenta lub jedna linia kodu** → metoda znika z Service'u, kontroler wywołuje model wprost. **Nie** tworzymy zastępczej klasy.
   - **Realna logika domenowa** → wydziel do osobnej klasy w roocie modułu, nazwanej od tego, co robi.

   ```php
   // PRZED:
   class OrderService {
       public function create(array $data) { ... }                 // wrapper na Model::create
       public function cancel(Order $order) { ... }                // 1 linia: zmiana statusu
       public function refund(Order $order, int $amount) { ... }   // wieloetapowy proces
       public function calculatePrice(array $items) { ... }        // algorytm z testami
   }

   // PO:
   - create()         → znika; kontroler woła OrderModel::create($data)
   - cancel()         → znika lub trafia jako metoda na OrderModel
   - refund()         → app/Order/OrderRefund.php   (wydzielone, bo realna logika)
   - calculatePrice() → app/Order/OrderPriceCalculator.php
   ```

   Reguła: **klasa istnieje tam, gdzie istnieje logika do nazwania.** Jeśli metoda była tylko sklejką nad Eloquentem, zastępczej klasy nie tworzymy. Patrz §5.2 i §8.2.

3. Update wywołań:
   - `app(OrderService::class)->create($data)` → `OrderModel::create($data)` (kontroler).
   - `app(OrderService::class)->refund($order, $amount)` → `app(OrderRefund::class)->handle($order, $amount)` (lub wstrzyknij konstruktorem).

### Krok 8 — Middleware, Providery, Exceptions

- `app/Http/Middleware/*`  → `app/System/Middleware/*`.
- `app/Providers/*`        → `app/System/Providers/*` (jeśli zostawiamy; często można usunąć większość po przejściu na atrybuty).
- `app/Exceptions/*`       → `app/System/Exceptions/*`.

### Krok 9 — Console / Commands

- `app/Console/Kernel.php` zostaje (jeśli istnieje).
- Komendy specyficzne dla domeny: `app/<Module>/Commands/<Foo>Command.php`.
- Komendy systemowe: `app/System/Commands/<Foo>Command.php`.
- Włącz auto-discovery komend w `bootstrap/app.php`:

```php
->withCommands(commands: glob(__DIR__.'/../app/*/Commands'))
```

### Krok 10 — Usuń `app/Http/`

Po przeniesieniu wszystkiego folder `app/Http/` powinien być pusty. Usuń go.

### Krok 11 — Update `composer.json` i regeneruj autoload

```json
"autoload": {
    "files": ["app/System/Utilities/helpers.php"],
    "psr-4": {
        "App\\": "app/",
        "Database\\Factories\\": "database/factories/",
        "Database\\Seeders\\": "database/seeders/"
    }
}
```

```bash
composer dump-autoload
```

### Krok 12 — Update `bootstrap/app.php`

Wskaż nowe lokalizacje providerów i middleware:

```php
use App\System\Providers\AppServiceProvider;
use App\System\Middleware\LocaleMiddleware;
// ...
```

### Krok 13 — Uruchom testy i `php artisan route:list`

Cele:

- Wszystkie testy pass.
- `php artisan route:list` pokazuje te same trasy co przed refaktoringiem.
- `php artisan about` nie zgłasza brakujących klas.
- `composer dump-autoload` przechodzi bez ostrzeżeń.

### Krok 14 — Czyszczenie

- Usuń puste foldery (`app/Models/`, `app/Http/`, `app/Services/`).
- Sprawdź wszystkie odwołania w `config/*.php`, `routes/*.php`, `resources/views/*.blade.php` i Vue/Inertia (jeśli używasz Inertii i nazywasz komponenty od kontrolerów).
- Sprawdź migracje: kolumny typu `model` (jak `morphs`) mogą wymagać aktualizacji `Relation::enforceMorphMap` jeśli używasz aliasów.

---

## 13. Quick reference — gdzie co kłaść

| Klasa                      | Lokalizacja                                                |
| -------------------------- | ---------------------------------------------------------- |
| Eloquent Model             | `app/<Module>/Models/<Foo>Model.php`                       |
| Pivot                      | `app/<Module>/Models/<Foo><Bar>Pivot.php`                  |
| Klasa biznesowa            | `app/<Module>/<Module><Action>.php`                        |
| Controller (web/Inertia)   | `app/<Module>/Controllers/<Foo>Controller.php`             |
| Controller (API/JSON)      | `app/<Module>/Controllers/<Foo>ApiController.php`          |
| FormRequest                | `app/<Module>/Requests/<Foo>Request.php`                   |
| JsonResource               | `app/<Module>/Resources/<Foo>Resource.php`                 |
| Observer                   | `app/<Module>/Observers/<FooModel>Observer.php`            |
| Event                      | `app/<Module>/Events/<Foo>Event.php`                       |
| Listener                   | `app/<Module>/Listeners/<Foo>Listener.php`                 |
| Job                        | `app/<Module>/Jobs/<Foo>Job.php`                           |
| Notification               | `app/<Module>/Notifications/<Foo>Notification.php`         |
| Mailable                   | `app/<Module>/Mail/<Foo>Mail.php`                          |
| DTO                        | `app/<Module>/DTO/<Foo>DTO.php`                            |
| Enum (domena)              | `app/<Module>/Enums/<Foo>Enum.php`                         |
| Enum (system)              | `app/System/Enums/<Foo>Enum.php`                           |
| Exception (domena)         | `app/<Module>/Exceptions/<Foo>Exception.php`               |
| Exception (system)         | `app/System/Exceptions/<Foo>Exception.php`                 |
| Pipe (Laravel Pipeline)    | `app/<Module>/Pipes/<Foo>.php`                             |
| Artisan Command (domena)   | `app/<Module>/Commands/<Foo>Command.php`                   |
| Artisan Command (system)   | `app/System/Commands/<Foo>Command.php`                     |
| Middleware                 | `app/System/Middleware/<Foo>.php`                          |
| ServiceProvider            | `app/System/Providers/<Foo>Provider.php`                   |
| Globalny Trait             | `app/System/Trait/<Foo>Trait.php`                          |
| Utility / helpers          | `app/System/Utilities/`                                    |
| Wrapper protokołu          | `app/System/<Protocol>/`                                   |
| Klient zewnętrznego API    | osobny moduł `app/<Vendor>/<Vendor>Service.php`            |
| Migracja                   | `database/migrations/` (klasycznie)                        |
| Seeder                     | `database/seeders/` (klasycznie)                           |
| Factory                    | `database/factories/` (klasycznie)                         |
| Test                       | `tests/Feature/...` lub `tests/Unit/...` (klasycznie)      |
| Config                     | `config/<foo>.php` (klasycznie)                            |
| Route                      | `routes/web.php`, `routes/api.php` (klasycznie)            |

---

## 14. Decyzje i ich uzasadnienie

| Decyzja                                                  | Powód                                                                                                  |
| -------------------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| Moduły zamiast warstw                                    | Pierwszy kontakt z `app/` mówi, **co** robi projekt, nie **jak** Laravel jest zbudowany.               |
| Sufix `Model`                                            | Zwalnia czystą nazwę (`Order`) dla klas biznesowych. Eliminuje pokusę nazywania ich `OrderService`.    |
| Sufix `Pivot`                                            | Pivot to nie pełnoprawny model, zasługuje na osobną nazwę. Łatwiej ich szukać.                          |
| Klasy biznesowe w roocie modułu (bez `Services/`)        | Każda klasa ma jasny zakres. Brak boilerplate'u w postaci kolejnego folderu.                            |
| Service Layer = anty-wzorzec                             | Magnes na god-objecty, nazwa nic nie znaczy, zwykle dubluje API Eloquent.                               |
| `Service` tylko dla zewnętrznych integracji              | Tu nazwa „service" oznacza coś konkretnego: klient czyjegoś serwisu.                                    |
| Routing klasycznie                                       | Walka z setupem Laravela > korzyść z modularności routingu.                                             |
| Testy klasycznie                                         | PHPUnit oczekuje `tests/Feature` i `tests/Unit`. Walka z tym = ból.                                     |
| Migracje klasycznie                                      | `php artisan migrate` oczekuje `database/migrations`. Walka = ból.                                      |
| Configi klasycznie                                       | Plik configa nie zawsze odpowiada modułowi (`audit.php`, `app.php`). Wymuszanie 1:1 = sztuczność.       |
| Brak ServiceProviderów per moduł                         | Laravel 11+ ma atrybuty i auto-discovery. Per-modułowe providery to boilerplate bez wartości.            |
| `bootstrap/app.php` z `glob()`                           | Generyczna rejestracja Commands/Listeners/Routes z modułów bez pisania nowego kodu dla nowego modułu.    |
| Brak fasady modułu                                       | Moduł nie potrzebuje klasy wejściowej. Każda klasa biznesowa stoi samodzielnie.                          |
| `System/` jako worek na bezdomne                         | Lepiej mieć jeden zdefiniowany worek niż pluć system providerami i utilkami w `app/`.                    |

---

## 15. Edge cases i wątpliwości

### 15.1 Klasa pasuje do dwóch modułów

Wybierz moduł, w którym leży główny **agregat** — czyli ten, który „posiada" dane. `OrderRefund` należy do `Order`, nawet jeśli używa `Stripe`.

### 15.2 Cross-module trait

Trait używany w modelach z różnych modułów (np. `SearchTrait`, `AuditableTrait`) trafia do `app/System/Trait/`.

### 15.3 Co jeśli mam klasę bez naturalnej domeny?

Pierwsze pytanie: czy ona naprawdę musi być? Jeśli tak — `app/System/`.

### 15.4 Co z paczkami publikującymi `app/...`?

Niektóre paczki przy `vendor:publish` próbują wrzucać pliki w domyślne lokalizacje Laravela (`app/Models/Foo.php`, `app/Http/Middleware/Bar.php`) — czyli foldery, których w tej architekturze **nie ma**.

1. Jeśli paczka pozwala nadpisać klasę przez config — przenieś plik do odpowiedniego modułu (`app/<Module>/Models/FooModel.php` lub `app/System/...`), zaktualizuj namespace, wskaż nowy w configu paczki.
2. Jeśli paczka twardo wymaga konkretnej ścieżki — sprawdź, czy faktycznie musisz publikować plik (często wystarczy domyślna implementacja z `vendor/`). Jeśli musisz, dopuść taki plik jako lokalny wyjątek i odnotuj go w README z uzasadnieniem.

### 15.5 Aplikacja ma wiele „odbiorców" (admin/klient/api)

Patrz §7.4. Jeśli to powoduje ścisk plików w module — dziel `Controllers/` i `Requests/` na podfoldery per audience. To **opcja**, nie wymóg.

### 15.6 Co jeśli moduł ma 3 pliki?

Nic. Mały moduł to prawidłowy moduł. Lepiej mieć `app/Tip/Models/TipModel.php` + `app/Tip/Controllers/TipController.php` niż walczyć o „zlepienie z czymś".

### 15.7 Co jeśli moduł ma 80 plików?

Sprawdź, czy nie zlewa dwóch domen w jedną. Jeśli zlewa — rozdziel. Jeśli naprawdę jest taki duży — to jest po prostu duży moduł, i dobrze, że jest osobno, a nie utopiony w `app/Http/Controllers/`.

---

## 16. Checklist dla nowego modułu

Tworzysz nowy moduł. Sprawdź:

- [ ] Nazwa modułu jest w PascalCase, w liczbie pojedynczej.
- [ ] Folder `app/<Module>/` istnieje.
- [ ] Wszystkie modele Eloquent w module mają sufix `Model`.
- [ ] Pivoty mają sufix `Pivot`.
- [ ] Nie ma w module pliku z sufiksem `Service` (chyba że to moduł integracji).
- [ ] Klasy biznesowe (jeśli istnieją) są w roocie modułu, nie w podfolderze.
- [ ] Observery są podpięte przez `#[ObservedBy]`, nie przez ServiceProvider.
- [ ] Kontrolery web mają sufix `Controller`, kontrolery JSON API mają `ApiController`.
- [ ] Trasy są w `routes/web.php` lub `routes/api.php`, importują z modułu.
- [ ] Migracje są w `database/migrations/`.
- [ ] Testy są w `tests/Feature/<Module>/` lub `tests/Unit/<Module>/`.
- [ ] Jeśli moduł ma `Commands/` — są auto-discoverowane przez `bootstrap/app.php`.
- [ ] Jeśli moduł ma `Listeners/` — są auto-discoverowane przez `bootstrap/app.php`.
