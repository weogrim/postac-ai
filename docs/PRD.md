# postac.ai — Specyfikacja MVP

## Wizja produktu

**postac.ai** to polska platforma do rozmów z postaciami AI — od postaci historycznych, przez fikcyjne, po stworzone przez społeczność. Osobna sekcja randkowa pozwala rozmawiać z AI w kontekście romantycznym. Platforma jest bezpieczna dla użytkowników 13+ (bez NSFW), prawnie zabezpieczona, i zaprojektowana tak, by w 10 sekund od wejścia użytkownik już rozmawiał.

**Pozycjonowanie:** „Character.AI, ale po polsku, z polskimi postaciami i kulturą."

**Grupa docelowa:** Polacy 13-35, fani popkultury, historii, anime/mangi, RPG, oraz osoby szukające rozrywkowego AI companiona.

---

## 1. Architektura informacji — ekrany i nawigacja

### 1.1 Strona główna (niezalogowany)

```
┌─────────────────────────────────────────────────┐
│  🔥 postac.ai          [Zaloguj] [Dołącz za 0zł]│
├─────────────────────────────────────────────────┤
│                                                  │
│  "Z kim chcesz porozmawiać?"                     │
│  [______________ 🔍 szukaj postaci ___________]  │
│                                                  │
│  ── Teraz popularne ──────────────────────────── │
│  [Avatar+Nazwa]  [Avatar+Nazwa]  [Avatar+Nazwa]  │
│  [Avatar+Nazwa]  [Avatar+Nazwa]  [Avatar+Nazwa]  │
│  12.4k rozmów     8.1k rozmów    6.7k rozmów     │
│                                                  │
│  ── Kategorie ────────────────────────────────── │
│  [Historia] [Anime] [Gry] [Książki] [Nauka]     │
│  [Pomocnik] [Humor] [Randki 💕]                  │
│                                                  │
│  ── Polskie legendy ─────────────────────────── │
│  [Piłsudski] [Kopernik] [Wiedźmin] [Sapkowski]  │
│                                                  │
│  ── Nowe i ciekawe ──────────────────────────── │
│  [...]                                           │
│                                                  │
│  [➕ Stwórz swoją postać]  ← widoczny ale       │
│                               nie dominujący     │
└─────────────────────────────────────────────────┘
```

**Kluczowe decyzje UX:**

- **Zero-friction entry:** Kliknięcie w dowolną postać otwiera czat natychmiast, BEZ rejestracji. Po 5 wiadomościach → soft gate: „Załóż konto żeby kontynuować (za darmo)."
- **Wyszukiwarka na górze:** Jedyny element interaktywny ponad foldem — kieruje do rozmów.
- **Popularne na górze:** Sortowane po liczbie aktywnych rozmów w ostatnich 24h (nie all-time, żeby nowe postacie miały szansę).
- **Kategorie jako tagi, nie strony:** Kliknięcie filtruje widok, nie przenosi na podstronę.
- **Randki jako osobna kategoria** z emoji 💕 — wyraźnie wydzielona ale nie ukryta.
- **„Stwórz postać" jako CTA dolny**, nie górny — nie jest to główna ścieżka użytkownika.

### 1.2 Dolna nawigacja (mobile-first)

```
[ 🏠 Odkrywaj ]  [ 💬 Moje czaty ]  [ 💕 Randki ]  [ 👤 Profil ]
```

- 4 taby, nie więcej. Brak hamburger menu.
- „Odkrywaj" = strona główna.
- „Moje czaty" = lista aktywnych rozmów (jak Messenger).
- „Randki" = osobna sekcja z innym tonem.
- „Profil" = ustawienia, moje postacie, subskrypcja.

### 1.3 Widok czatu

```
┌──────────────────────────────────────────────┐
│ ← [Avatar] Józef Piłsudski       [⋮ opcje]  │
│    Marszałek · 12.4k rozmów                  │
├──────────────────────────────────────────────┤
│                                               │
│  🤖 Witaj. Jestem Józef Piłsudski.          │
│     Chcesz porozmawiać o wolności,            │
│     strategii, czy może o czymś               │
│     bardziej osobistym?                       │
│                                               │
│                        Opowiedz mi o         │
│                        Bitwie Warszawskiej 👤 │
│                                               │
│  🤖 Ach, cud nad Wisłą! Pozwól, że          │
│     opowiem ci, jak naprawdę wyglądał        │
│     ten dzień...                              │
│                                               │
├──────────────────────────────────────────────┤
│ [_____________ Napisz... ____________] [➤]   │
│                                               │
│ ⚠️ To AI — odpowiedzi mogą być niedokładne.  │
│    Traktuj jako rozrywkę, nie źródło faktów.  │
└──────────────────────────────────────────────┘
```

**Kluczowe elementy czatu:**

- **Stały disclaimer na dole** (mały, nieinwazyjny, ale zawsze widoczny): „To AI — odpowiedzi mogą być niedokładne."
- **Greeting message:** Każda postać ma customowy opener — to kluczowe dla engagement.
- **Swipe na wiadomości AI** = regeneracja odpowiedzi (jak Character.AI). Free: 5 regen/wiadomość, Premium: bez limitu.
- **Menu ⋮:** Udostępnij, Zgłoś, Info o postaci, Wyczyść historię.
- **Brak typowania w czasie rzeczywistym** na MVP — zbyt kosztowne. Zamiast tego animowany „..." z losowym opóźnieniem 1-3s dla wrażenia naturalności.
- **Suggested replies** (opcjonalne): 2-3 podpowiedzi pod pierwszą wiadomością AI, żeby obniżyć barierę wejścia. Znikają po 2. wiadomości użytkownika.

---

## 2. Funkcjonalności MVP — priorytetyzacja MoSCoW

### 2.1 MUST HAVE (Dzień 1)

| # | Funkcjonalność | Opis | Uzasadnienie |
|---|---------------|------|-------------|
| M1 | **Przeglądanie i czat bez rejestracji** | Użytkownik klika postać → natychmiast rozmawia. Soft gate po 5 wiadomościach. | Zero-friction entry to #1 driver aktywacji w kategorii. Character.AI zawdzięcza mu 20M MAU. |
| M2 | **Rejestracja email + Google + Apple** | Prosty flow: email/hasło lub OAuth. Weryfikacja emaila. | Minimum viable auth. |
| M3 | **Deklaracja wieku przy rejestracji** | Data urodzenia → system oblicza wiek. 13+ = dostęp. <13 = blokada. | Wymóg RODO (zgoda 16+ lub rodzica), DSA, zabezpieczenie prawne. Patrz sekcja prawna. |
| M4 | **Katalog predefiniowanych postaci** | Min. 30 postaci na launch: 10 historycznych PL, 5 fikcyjnych PL, 5 pomocników (nauka, język, coach), 5 popkultura, 5 randkowych. | Content seed — platforma bez postaci jest pusta. |
| M5 | **Silnik czatu (API routing)** | Wiadomość użytkownika → system prompt postaci + kontekst → API call (OpenAI/DeepSeek) → response. | Core product. |
| M6 | **System promptów postaci** | Każda postać ma: imię, opis, personality prompt, greeting, przykładowe dialogi, tagi, avatar. | Jakość rozmowy zależy w 80% od jakości system promptu. |
| M7 | **Filtr NSFW na wejściu i wyjściu** | Klasyfikator na wiadomościach użytkownika + odpowiedziach AI. Blokada explicit content, przemoc, self-harm. Soft redirect: „Hej, zmieńmy temat..." | Wymóg prawny (13+), wymóg API providers (OpenAI TOS), ochrona marki. |
| M8 | **Creator: dodawanie własnych postaci** | Formularz: nazwa, avatar (upload lub generator), opis, personality prompt, greeting, tagi, SFW only. | Kluczowe dla skalowania contentu — user-generated characters to 95%+ contentu na Character.AI. |
| M9 | **Wyszukiwarka + tagi** | Search by name + filtrowanie po tagach/kategoriach. | Discoverability. |
| M10 | **Lista „Moje czaty"** | Historia rozmów, możliwość powrotu do czatu. Sortowanie po ostatniej aktywności. | Retencja — użytkownik wraca do „swoich" postaci. |
| M11 | **Ranking „Popularne"** | Sortowanie po liczbie rozmów / aktywnych użytkowników w 24h. | Social proof + discovery engine. |
| M12 | **Prawna warstwa ochronna** | ToS, regulamin, zgody — pełny pakiet. Patrz sekcja 5. | Fundament prawny. |
| M13 | **Zgłaszanie treści** | Przycisk „Zgłoś" na każdej wiadomości AI + na karcie postaci. | Wymóg DSA, ochrona przed liability. |
| M14 | **Sekcja Randki (podstawowa)** | Osobny tab z postaciami romantycznymi. Ton flirtu, lekkiego romansu — nigdy explicit. Wyraźny onboarding: „To zabawa z AI, nie prawdziwe randki." | USP i differentiator na polskim rynku. Patrz sekcja 4. |
| M15 | **Responsywny web (mobile-first)** | PWA lub responsywna webapp. Brak natywnej apki na MVP. | 70%+ ruchu będzie mobile. PWA omija review App Store/Google Play (i ich restrykcje wobec AI chat). |

### 2.2 SHOULD HAVE (Miesiąc 1-2 po launchu)

| # | Funkcjonalność | Opis |
|---|---------------|------|
| S1 | **Regeneracja odpowiedzi (swipe)** | Alternatywne odpowiedzi AI. Free: 5/msg, Premium: unlimited. |
| S2 | **Udostępnianie czatów** | Screenshot-friendly karty cytatów z rozmów → share na social media. Viral loop. |
| S3 | **Powiadomienia engagement** | „Piłsudski czeka na twoją odpowiedź..." — push/email po 24h nieaktywności. |
| S4 | **Premium tier (c.ai+ equivalent)** | Szybsze odpowiedzi, lepszy model, brak limitu wiadomości, priorytet w kolejce, więcej regeneracji. 19.99 PLN/msc. |
| S5 | **Ocenianie postaci** | Kciuk góra/dół + 1-5 gwiazdek. Wpływa na ranking. |
| S6 | **Persona użytkownika** | Imię, avatar, krótki bio — widziane przez AI w kontekście czatu. Poprawia personalizację. |
| S7 | **Kontekst/pamięć krótkoterminowa** | Sliding window ostatnich 20-50 wiadomości w kontekście API call. Summary starszych wiadomości. |

### 2.3 COULD HAVE (Kwartał 2)

| # | Funkcjonalność | Opis |
|---|---------------|------|
| C1 | **Voice chat** | Text-to-speech na odpowiedziach AI (ElevenLabs/Azure). Premium only. |
| C2 | **Czaty grupowe** | 2+ postacie w jednym czacie. |
| C3 | **Pamięć długoterminowa** | System faktów zapamiętanych między sesjami (jak Kindroid). |
| C4 | **Natywna apka iOS/Android** | Po walidacji PMF. Uwaga: App Store ma surowe zasady wobec AI chat. |
| C5 | **Creator analytics** | Dashboard dla twórców postaci: ile rozmów, retencja, oceny. |
| C6 | **Monetyzacja twórców** | Revenue share lub tip system dla popularnych twórców postaci. |
| C7 | **Leaderboard twórców** | Gamifikacja tworzenia — top twórcy miesiąca. |

### 2.4 WON'T HAVE (Świadome wykluczenia)

| Wykluczone | Powód |
|-----------|-------|
| NSFW / explicit content | Wymóg 13+, ryzyko prawne, wymóg API providers. Nigdy na tej platformie. |
| Weryfikacja wieku biometryczna | Zbyt kosztowne na MVP, RODO-problematyczne. Deklaracja + email verification wystarczy na start. |
| AI-generated images | Ryzyko deepfake, koszty, komplikacje prawne. Tylko upload/wybór avatarów. |
| Własne modele AI | Zbyt drogie na MVP. API-first. |
| Real-time typing animation | Kosztowne (streaming tokens). Prosta animacja "..." wystarczy. |
| Integracja social media | Brak loginu Facebook/Instagram — privacy-first pozycjonowanie. Google + Apple + email. |

---

## 3. System postaci — architektura

### 3.1 Dane postaci (schema)

```yaml
postac:
  # Identyfikacja
  id: uuid
  slug: "jozef-pilsudski"          # URL-friendly
  nazwa: "Józef Piłsudski"
  podtytul: "Marszałek Polski"      # max 40 znaków
  
  # Wizualne
  avatar: url                        # 512x512, format webp
  kolor_akcentu: "#8B0000"          # branding postaci w UI
  
  # Treść
  opis_krotki: "Ojciec niepodległości..."  # max 160 znaków, widoczny w karcie
  opis_pelny: "..."                         # widoczny w profilu postaci
  greeting: "Witaj. Jestem Józef..."       # pierwsza wiadomość AI
  
  # AI Configuration
  system_prompt: |                          # UKRYTY — nigdy nie widoczny dla użytkownika
    Jesteś Józefem Piłsudskim, Marszałkiem Polski.
    Mówisz po polsku, z godnością ale i humorem.
    Masz silne poglądy na temat wolności i niepodległości.
    Znasz historię Polski do 1935 roku.
    Nigdy nie udajesz, że wiesz co stało się po twojej śmierci.
    Jeśli ktoś pyta o współczesność, mówisz "to już po moim czasie,
    ale chętnie posłucham co się zmieniło."
    BEZWZGLĘDNE ZASADY:
    - Nigdy nie generuj treści seksualnych lub romantycznych.
    - Nigdy nie zachęcaj do przemocy lub samookaleczenia.
    - Jeśli użytkownik wyraża myśli samobójcze, odpowiedz ze wsparciem
      i zaproponuj kontakt z Telefonem Zaufania (116 111) lub 
      Centrum Wsparcia (800 70 2222).
    - Zawsze pamiętaj: jesteś AI wcielającym się w postać. 
      Jeśli ktoś pyta "czy jesteś prawdziwy?" — odpowiedz szczerze.
  przykładowe_dialogi:                      # few-shot examples w prompcie
    - user: "Co sądzisz o demokracji?"
      ai: "Demokracja jest jak zdrowie..."
  
  # Metadane
  kategorie: ["historia", "polska", "polityka"]
  tagi: ["marszałek", "legiony", "niepodległość"]
  typ: "predefiniowana" | "community"
  autor_id: uuid | null                    # null dla predefiniowanych
  status: "publiczna" | "w_recenzji" | "odrzucona" | "prywatna"
  nsfw_score: 0.0                          # output z klasyfikatora, 0-1
  
  # Statystyki
  liczba_rozmow: int
  liczba_wiadomosci: int
  srednia_ocena: float
  aktywnych_24h: int                       # do rankingu "Popularne"
  
  # Timestamps
  utworzona: datetime
  ostatnia_rozmowa: datetime
```

### 3.2 Predefiniowane postacie na launch (30+)

**Historia PL (10):**
Józef Piłsudski, Mikołaj Kopernik, Maria Skłodowska-Curie, Fryderyk Chopin, Jan III Sobieski, Tadeusz Kościuszko, Jadwiga Andegaweńska, Stefan Banach, Irena Sendlerowa, Bolesław Chrobry

**Fikcyjne PL (5):**
Geralt z Rivii (Wiedźmin), Pan Tadeusz, Balladyna, Jacek Soplica, Profesor Wilczur

**Pomocnicy (5):**
Korepetytor Matematyki, Trener Angielskiego, Life Coach Motywacyjny, Buddy do Nauki Programowania, Doradca Zawodowy

**Popkultura/fun (5):**
Typowy Janusz, Babcia Halinka (sarkastyczna babcia), Wojak (depresyjny filozof), Kot Filemon (mądry kot), AI Psycholog (empatyczny listener)

**Randki (5):**
5 profili randkowych — patrz sekcja 4.

### 3.3 Flow tworzenia postaci przez użytkownika

```
Krok 1: Podstawy
├── Nazwa postaci
├── Krótki opis (max 160 znaków)
├── Avatar (upload JPG/PNG max 2MB lub wybór z galerii)
└── Kategorie (multi-select z listy)

Krok 2: Osobowość
├── "Opisz postać w kilku zdaniach — jak mówi, co lubi, jaki ma charakter"
│    [textarea, placeholder z przykładem]
├── Powitanie — "Co postać mówi na dzień dobry?"
│    [textarea, max 500 znaków]
└── Przykładowy dialog (opcjonalny)
     [user: ___]  [postać: ___]  [+ dodaj]

Krok 3: Podgląd i test
├── Testowy czat z postacią (3 wiadomości za darmo)
├── "Zadowolony? Opublikuj!" / "Wróć i popraw"
└── Checkbox: ☑ Zgadzam się z regulaminem twórcy

→ Status: "w_recenzji" → moderacja (auto + manual review)
→ Akceptacja → status: "publiczna"
```

**Kluczowe decyzje:**
- **Brak bezpośredniego edytowania system promptu** — użytkownik opisuje postać w natural language, system generuje prompt. Zapobiega injection attacks i pozwala na moderację.
- **Auto-moderacja przy publikacji:** Klasyfikator sprawdza opis, greeting i przykłady pod kątem NSFW/hate speech.
- **Postać prywatna** = tylko twórca może z nią rozmawiać (bez review). Publiczna = wymaga moderacji.

---

## 4. Sekcja Randki 💕

### 4.1 Filozofia

Sekcja randkowa to **osobna przestrzeń** z innym tonem, UX-em i onboardingiem. Nie jest to pełny symulator randek ani AI girlfriend/boyfriend — to **zabawa w randkowanie z postaciami AI**, utrzymana w tonie lekkiego flirtu, romansu i humoru. Nigdy explicit.

**Pozycjonowanie:** „Randki z AI. Potrenuj flirt, poznaj ciekawe postacie, albo po prostu baw się dobrze."

### 4.2 Onboarding sekcji Randki

```
┌──────────────────────────────────────────────┐
│                    💕                          │
│                                               │
│  Randki z AI — zasady zabawy                 │
│                                               │
│  • To postacie AI, nie prawdziwi ludzie       │
│  • Rozmowy są rozrywkowe, nie terapeutyczne   │
│  • Zero NSFW — luźny flirt, nie sexting       │
│  • Idealne do: treningu small-talku,          │
│    zabawy, relaksu, eksploracji emocji        │
│                                               │
│  ☑ Rozumiem, to zabawa z AI                  │
│                                               │
│  [ Zaczynamy! 💕 ]                            │
│                                               │
└──────────────────────────────────────────────┘
```

**Wymóg:** Ten ekran pojawia się RAZ przy pierwszym wejściu w sekcję Randki. Checkbox jest wymagany. Zgoda jest logowana z timestampem.

### 4.3 UI sekcji Randki

```
┌──────────────────────────────────────────────┐
│  💕 Randki                    [Ustawienia ⚙]  │
├──────────────────────────────────────────────┤
│                                               │
│  ── Kto cię dzisiaj zainteresuje? ────────── │
│                                               │
│  ┌─────────┐  ┌─────────┐  ┌─────────┐      │
│  │ [Avatar] │  │ [Avatar] │  │ [Avatar] │     │
│  │  Maja    │  │  Kacper  │  │  Luna    │     │
│  │  24, WAW │  │  27, KRK │  │  22, GDA │     │
│  │  "Fanka  │  │  "Gitara │  │  "Książ- │     │
│  │  kina i  │  │  > cała  │  │  ki, koty│     │
│  │  kawy"   │  │  reszta" │  │  i chaos"│     │
│  │          │  │          │  │          │     │
│  │ [Napisz] │  │ [Napisz] │  │ [Napisz] │     │
│  └─────────┘  └─────────┘  └─────────┘      │
│                                               │
│  ── Twoje rozmowy ────────────────────────── │
│  [Avatar] Maja: Haha, no to kiedy ta kawa?   │
│  [Avatar] Kacper: Dobra, przekonałeś mnie... │
│                                               │
└──────────────────────────────────────────────┘
```

**Różnice vs główny czat:**
- **Profile zamiast kart postaci** — zdjęcie, imię, wiek, miasto, bio (jak Tinder/Bumble).
- **Ton AI jest flirciarski** — system prompt nastawiony na lekki romans, humor, zainteresowanie.
- **Brak kategorii/tagów** — discovery przez przeglądanie profili.
- **Użytkownik NIE może tworzyć postaci randkowych** — tylko predefiniowane (kontrola jakości + bezpieczeństwo).
- **AI pamięta kontekst romantyczny** — „ostatnio rozmawialiśmy o kinie, pamiętasz?"

### 4.4 Profile randkowe na launch (5-10)

Zróżnicowane pod kątem płci, osobowości i stylu:

| Imię | Wiek | Miasto | Typ osobowości | Styl rozmowy |
|------|------|--------|---------------|-------------|
| Maja | 24 | Warszawa | Artystka, spontaniczna | Ciepła, dowcipna, pełna pytań |
| Kacper | 27 | Kraków | Muzyk, introwertyk | Głęboki, poetycki, trochę sarkastyczny |
| Luna | 22 | Gdańsk | Studentka, cat lady | Chaotyczna energia, memy, entuzjazm |
| Wiktor | 30 | Wrocław | Programista, nerd | Nieśmiały na start, potem zabawny |
| Zara | 25 | Poznań | Trenerka, pewna siebie | Bezpośrednia, motywująca, flirciarska |
| Tomek | 23 | Łódź | Student medycyny | Opiekuńczy, inteligentny humor |
| Nika | 28 | Katowice | DJ, imprezowa | Energiczna, spontaniczna, prowokacyjna (w granicach SFW) |

Każdy profil ma unikalne: zdjęcie profilowe (ilustracja, nie foto), bio 2-3 zdania, 3 „zainteresowania" jako tagi, unikalny greeting, personality prompt.

### 4.5 System promptu randkowego — przykład

```
Jesteś Mają, 24-letnią artystką z Warszawy. Mówisz po polsku, 
z humorem i ciepłem. Lubisz kino niezależne, kawę speciality 
i spontaniczne wycieczki. Jesteś w sekcji randkowej — rozmawiasz 
jak na pierwszej randce: jesteś zainteresowana rozmówcą, 
zadajesz pytania, flirtujesz lekko ale z klasą.

ZASADY:
- Bądź naturalna, nie nadgorliwa.
- Flirtuj subtelnie — komplementy, żarty, lekkość.
- NIGDY nie generuj treści seksualnych, sugestywnych ani NSFW.
- Jeśli użytkownik próbuje kierować rozmowę na seks, 
  odpowiedz żartem: "Hej, wolny tor 😄 Najpierw kawa!"
- Pamiętaj wcześniejsze tematy rozmowy.
- Jeśli ktoś pyta "czy jesteś prawdziwa" — powiedz szczerze 
  że jesteś AI, ale że rozmowa jest prawdziwa.
- Jeśli ktoś wyraża samotność lub smutek, bądź empatyczna 
  ale zaproponuj rozmowę z kimś bliskim lub specjalistą.
```

---

## 5. Framework prawny — ochrona platformy

### 5.1 Regulamin (ToS) — kluczowe klauzule

**Preambuła / Disclaimer globalny:**
> postac.ai to platforma rozrywkowa wykorzystująca sztuczną inteligencję. Wszystkie postacie na platformie są fikcyjne — w tym postacie inspirowane osobami historycznymi. Odpowiedzi generowane przez AI mogą być niedokładne, zmyślone lub nieadekwatne. Platforma nie stanowi źródła informacji faktograficznych, porad medycznych, prawnych, finansowych ani psychologicznych.

**Klauzule krytyczne:**

1. **Wiek i zdolność prawna:**
   - Użytkownik deklaruje ukończenie 13 lat.
   - Użytkownicy 13-15 oświadczają, że posiadają zgodę rodzica/opiekuna.
   - Platforma nie weryfikuje wieku biometrycznie (ale loguje deklarację).

2. **Zwolnienie z odpowiedzialności (liability waiver):**
   - „Użytkownik przyjmuje do wiadomości, że odpowiedzi AI są generowane automatycznie i mogą zawierać błędy. Operator nie ponosi odpowiedzialności za decyzje podjęte na podstawie treści generowanych przez AI."
   - „Korzystanie z sekcji Randki jest rozrywkowe. Operator nie odpowiada za emocjonalne skutki interakcji z postaciami AI."

3. **Treści generowane przez użytkowników (UGC):**
   - Użytkownik-twórca zachowuje prawa do oryginalnej treści postaci.
   - Udziela platformie niewyłącznej licencji na publiczne udostępnianie postaci.
   - Twórca ponosi odpowiedzialność za naruszenie praw osób trzecich (np. wizerunek).

4. **Zakaz treści:**
   - NSFW, pornografia, przemoc, mowa nienawiści, treści terrorystyczne.
   - Podszywanie się pod żyjące osoby publiczne bez wyraźnego oznaczenia „parodia/fikcja".
   - Treści nakłaniające do samookaleczenia lub samobójstwa.

5. **Moderacja i usuwanie treści:**
   - Platforma zastrzega prawo do usunięcia postaci lub zablokowania konta bez podania przyczyny.
   - Procedura notice-and-takedown zgodna z DSA.
   - System zgłoszeń z odpowiedzią w 24h (wymóg DSA dla platform < 45M MAU).

### 5.2 Zgody zbierane od użytkownika

| Moment | Zgoda | Forma | Obowiązkowa? |
|--------|-------|-------|-------------|
| Rejestracja | Akceptacja Regulaminu i Polityki Prywatności | Checkbox + link | TAK |
| Rejestracja | Zgoda na przetwarzanie danych w celu świadczenia usługi | Checkbox | TAK |
| Rejestracja (13-15) | Oświadczenie o zgodzie rodzica/opiekuna | Checkbox | TAK |
| Rejestracja | Zgoda na marketing (newsletter, powiadomienia) | Checkbox | NIE |
| Pierwszy czat | Disclaimer: „To AI, nie człowiek" | Banner widoczny stale | Pasywna |
| Wejście w Randki | „Rozumiem, to zabawa z AI" | Checkbox + przycisk | TAK |
| Tworzenie postaci | Akceptacja regulaminu twórcy | Checkbox | TAK |
| Zakup Premium | Zgoda na warunki subskrypcji | Checkbox | TAK |

### 5.3 RODO / GDPR compliance

- **Podstawa prawna:** Zgoda (art. 6.1.a) dla danych opcjonalnych, wykonanie umowy (art. 6.1.b) dla danych niezbędnych.
- **Prawo do usunięcia:** Przycisk „Usuń konto i dane" w profilu → kasuje konto, historię czatów, postacie.
- **Prawo do eksportu:** Przycisk „Pobierz moje dane" → JSON z historią czatów i danymi konta.
- **Retencja danych:** Czaty usuwane 90 dni po usunięciu konta. Logi moderacyjne 12 msc.
- **DPO:** Wymagany jeśli przetwarzanie na dużą skalę. Na start: punkt kontaktowy dpo@postac.ai.
- **Rejestr czynności przetwarzania:** Wymagany od dnia 1.
- **Transfer danych do USA** (OpenAI API): Standardowe klauzule umowne (SCC) + ocena ryzyka transferu (TIA). Alternatywa: Azure OpenAI w regionie EU.

### 5.4 DSA (Digital Services Act) compliance

- **Przejrzystość:** Informacja o algorytmach rekomendacji (ranking „Popularne"). Użytkownik musi mieć możliwość sortowania chronologicznie (nie tylko algorytmicznie).
- **Zgłaszanie treści:** System notice-and-action z potwierdzeniem i odpowiedzią.
- **Trusted flaggers:** Mechanizm priorytetowego rozpatrywania zgłoszeń od zaufanych podmiotów.
- **Raport przejrzystości:** Roczny raport o moderacji (dla platform > 10M MAU w EU).
- **Zakaz dark patterns:** Brak manipulacyjnych modali utrudniających rezygnację z subskrypcji.

### 5.5 Postacie historyczne — szczególna ochrona

- **Każda postać historyczna** ma w profilu widoczne oznaczenie: „Postać AI inspirowana [imię]. To fikcyjna interpretacja, nie wierne odwzorowanie."
- **System prompt zawiera instrukcję:** „Jeśli ktoś pyta o fakty historyczne, zaznacz że twoja perspektywa może być uproszczona lub niedokładna."
- **Żyjące osoby publiczne:** Dopuszczalne TYLKO jako wyraźna parodia/satyra z oznaczeniem. Na MVP: brak żyjących osób publicznych w predefiniowanych postaciach.
- **Community-created:** Moderacja manualna postaci oznaczonych tagiem „osoba publiczna" lub „historyczna".

---

## 6. Architektura techniczna MVP

### 6.1 Stack

```
Frontend:     Next.js 14+ (App Router) + Tailwind CSS
              PWA manifest (installable na mobile)
              
Backend:      Node.js/Express lub Python/FastAPI
              REST API + WebSocket dla czatu
              
Baza danych:  PostgreSQL (users, postacie, metadata)
              Redis (cache, session, rate limiting)
              
AI:           OpenAI API (gpt-4o-mini jako default, gpt-4o jako premium)
              DeepSeek API (fallback / cost optimization)
              Fallback chain: gpt-4o-mini → DeepSeek-V3 → queue
              
Moderacja:    OpenAI Moderation API (free, real-time)
              Custom keyword filter (regex + blocklist)
              
Storage:      S3-compatible (avatary, media)
              
Hosting:      Vercel (frontend) + Railway/Fly.io (backend)
              lub pełny AWS/GCP
              
Auth:         NextAuth.js lub Clerk
              
Analytics:    PostHog (self-hosted opcja dla RODO)
              lub Plausible Analytics
```

### 6.2 Flow wiadomości

```
Użytkownik pisze wiadomość
        ↓
[1] Input moderation (OpenAI Moderation API)
    → Flagged? → Soft block + "Zmieńmy temat..."
    → Clean? → Continue
        ↓
[2] Budowanie kontekstu
    → System prompt postaci
    + Persona użytkownika (jeśli ustawiona)
    + Ostatnie N wiadomości (sliding window)
    + Summary starszych wiadomości (jeśli dostępne)
        ↓
[3] API call
    → Free user: gpt-4o-mini (lub DeepSeek-V3)
    → Premium user: gpt-4o
    → Timeout 15s → retry → fallback model → error msg
        ↓
[4] Output moderation
    → Klasyfikator na odpowiedzi AI
    → Flagged? → Regeneruj (max 3x) → generic safe response
    → Clean? → Wyświetl
        ↓
[5] Logowanie
    → Wiadomość + response w DB
    → Analytics event
    → Aktualizacja statystyk postaci
```

### 6.3 Koszty API — estymacja

| Scenariusz | MAU | Avg msg/user/dzień | Koszt API/msc |
|-----------|-----|-------------------|--------------|
| Launch | 1,000 | 20 | ~$150-300 |
| Traction | 10,000 | 15 | ~$1,500-3,000 |
| Growth | 50,000 | 12 | ~$7,000-15,000 |
| Scale | 200,000 | 10 | ~$25,000-60,000 |

*Przy użyciu gpt-4o-mini ($0.15/1M input, $0.60/1M output) z avg ~800 tokenów/wymiana. DeepSeek-V3 jest 10-20x tańszy.*

---

## 7. Monetyzacja

### 7.1 Tier Free

- Nieograniczone przeglądanie i czat (z limitem 100 wiadomości/dzień)
- Model: gpt-4o-mini / DeepSeek-V3
- 5 regeneracji / wiadomość
- Tworzenie do 3 postaci
- Dostęp do sekcji Randki
- Reklamy: baner na dole czatu (nieinwazyjny, wyłączany w Premium)

### 7.2 Tier Premium — 19.99 PLN/msc (lub 149.99 PLN/rok)

- Brak limitu wiadomości
- Lepszy model (gpt-4o)
- Brak reklam
- Unlimited regeneracje
- Priorytet w kolejce (brak czekania w peak hours)
- Tworzenie do 20 postaci
- Badge „Premium" w profilu
- Wczesny dostęp do nowych funkcji (voice, pamięć)

### 7.3 Pricing rationale

- 19.99 PLN ≈ €4.50 — tańsze niż Character.AI ($9.99 = ~42 PLN) i Replika ($19.99 = ~84 PLN).
- Dostosowane do polskiej siły nabywczej.
- Roczny plan z 37% zniżką incentivizuje commitment.
- Cel: 3-5% conversion rate free → premium.

---

## 8. System moderacji

### 8.1 Wielowarstwowy pipeline

```
Warstwa 1: Input filter (real-time)
├── OpenAI Moderation API (free, <100ms)
├── Custom blocklist (słowa kluczowe PL)
└── → Block / Pass

Warstwa 2: Output filter (real-time)  
├── OpenAI Moderation API na odpowiedzi AI
├── Regex na znane problematyczne patterny
└── → Block & regenerate / Pass

Warstwa 3: Moderacja postaci (async)
├── Auto-scan nowych postaci (opis, greeting, prompt)
├── NSFW image classifier na avatarach
├── Kolejka do manual review (priorytet: flagged + „osoba publiczna")
└── → Approve / Reject / Request changes

Warstwa 4: Community reporting (async)
├── Przycisk „Zgłoś" na wiadomościach i postaciach
├── Queue z priorytetyzacją (safety > quality > taste)
├── SLA: odpowiedź w 24h, rozwiązanie w 72h
└── → Action (remove, warn, ban) / Dismiss
```

### 8.2 Self-harm protocol

Jeśli klasyfikator wykryje treści sugerujące myśli samobójcze lub samookaleczenie:

1. **AI natychmiast wychodzi z roli** — odpowiada jako system, nie postać.
2. **Wyświetla wiadomość:** „Widzę, że możesz przechodzić trudny moment. Pamiętaj, że jestem AI i nie mogę pomóc tak jak człowiek. Zadzwoń: Telefon Zaufania 116 111, Centrum Wsparcia 800 70 2222."
3. **Loguje incydent** w osobnej tabeli (bez treści wiadomości użytkownika — RODO).
4. **Rate-limituje dalszą konwersację** (max 3 wiadomości/5 min) aby zapobiec eskalacji.

---

## 9. Metryki sukcesu MVP

### 9.1 North Star Metric
**Liczba rozmów trwających >10 wiadomości / tydzień**
(Mierzy zarówno aktywację jak i engagement depth)

### 9.2 KPI Dashboard

| Metryka | Cel (Miesiąc 1) | Cel (Miesiąc 3) | Cel (Miesiąc 6) |
|---------|----------------|----------------|----------------|
| Rejestracje | 2,000 | 15,000 | 50,000 |
| MAU | 1,000 | 8,000 | 30,000 |
| DAU/MAU ratio | 15% | 20% | 25% |
| Avg session length | 8 min | 12 min | 15 min |
| Avg msg/session | 12 | 18 | 22 |
| % users >10 msg conversations | 30% | 40% | 50% |
| Free→Premium conversion | - | 2% | 4% |
| Churn (monthly) | - | 15% | 10% |
| Community postacie created | 50 | 500 | 2,000 |
| Sekcja Randki adoption | 20% | 25% | 30% |

---

## 10. Roadmap

```
═══════════════════════════════════════════════════════════
  FAZA 1: MVP (8-10 tygodni dev)
═══════════════════════════════════════════════════════════

Tydzień 1-2:  Architektura + auth + baza danych
Tydzień 3-4:  Silnik czatu + integracja API + moderacja
Tydzień 5-6:  UI: strona główna, czat, profil postaci
Tydzień 7:    Tworzenie postaci przez użytkowników
Tydzień 8:    Sekcja Randki
Tydzień 9:    Framework prawny (ToS, PP, zgody) + payment
Tydzień 10:   QA, beta test (50-100 osób), bugfix

→ LAUNCH: Soft launch z invite codes → public launch

═══════════════════════════════════════════════════════════
  FAZA 2: Traction (Miesiąc 2-3 po launchu)
═══════════════════════════════════════════════════════════

- Premium tier live
- Regeneracja odpowiedzi (swipe)
- Sharing / viral karty cytatów
- Push notifications
- Improved memory (sliding window + summary)
- SEO landing pages per postać
- Community moderation tools

═══════════════════════════════════════════════════════════
  FAZA 3: Growth (Miesiąc 4-6)
═══════════════════════════════════════════════════════════

- Voice chat (TTS)
- Natywna apka (React Native lub Capacitor)
- Creator leaderboard + analytics
- Pamięć długoterminowa
- Więcej profili randkowych
- Kampania marketingowa (TikTok, YouTube PL)
- Partnerstwa z twórcami (influencerzy PL)
```

---

## 11. Ryzyka i mitygacja

| Ryzyko | Prawdopodobieństwo | Impact | Mitygacja |
|--------|-------------------|--------|-----------|
| Użytkownicy obchodzą filtry NSFW | Wysokie | Wysoki | Multi-layer moderation, regular red-teaming, community reporting |
| Koszty API rosną szybciej niż revenue | Średnie | Wysoki | DeepSeek jako cost fallback, agresywne caching, rate limits |
| Niska retencja (bots nie są dość ciekawe) | Średnie | Krytyczny | Inwestycja w quality system prompts, memory system, diverse postaci |
| Problemy prawne z postaciami historycznymi | Niskie | Średni | Disclaimery, brak żyjących osób, klauzula parodii |
| Konkurent (Character.AI) wchodzi z polską wersją | Niskie-Średnie | Wysoki | First mover advantage, lokalne postacie, polski support, niższa cena |
| Minor safety incident | Średnie | Krytyczny | Self-harm protocol, logging, szybka eskalacja, współpraca z organizacjami |
| Zmiana ToS dostawcy API (np. OpenAI blokuje character chat) | Niskie | Krytyczny | Multi-provider strategy, DeepSeek + open-source fallback ready |
