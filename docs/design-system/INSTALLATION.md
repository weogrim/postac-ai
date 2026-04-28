# Installation — postac.ai design DNA

Krok po kroku, jak wpiąć tę paczkę w istniejący projekt na **Tailwind 4 + DaisyUI 5.5**.

Zakładamy że projekt już ma:
- `package.json` z `tailwindcss@^4` i `daisyui@^5.5`
- Jakąś konfigurację build'a (Vite / esbuild / Tailwind CLI / framework który ma to w sobie)
- Główny plik CSS (np. `src/app.css` albo `assets/main.css`)
- Alpine.js zainstalowane (`alpinejs` + `@alpinejs/intersect` plugin)
- HTMX zainstalowane (opcjonalnie)

Jeśli **nie masz** czegoś z powyższych, dolny rozdział "Quick start od zera" pokazuje minimum.

---

## A. Integracja w istniejący projekt

### 1. Skopiuj fonty

```bash
# Z folderu postac-design-system/, do swojego repo:
cp -r fonts/ /sciezka/do/twojego/repo/public/fonts/
# albo gdzie hostujesz statics: src/assets/fonts/, static/fonts/, etc.
```

Pliki:
- `inter-latin.woff2`, `inter-latin-ext.woff2`
- `space-grotesk-latin.woff2`, `space-grotesk-latin-ext.woff2`
- `jetbrains-mono-latin.woff2`, `jetbrains-mono-latin-ext.woff2`

**Ważne:** ścieżka w CSS musi pasować. W `app.css` z paczki używamy `url('/fonts/inter-latin.woff2')` (absolutna od root). Jeśli hostujesz pod inną ścieżką (np. `/static/fonts/`), zmień prefix.

### 2. Zmerguj `app.css`

Otwórz mój `app.css` z paczki i swój główny plik CSS. Mój ma 4 sekcje:

| Sekcja w moim `app.css` | Co zrobić |
|---|---|
| **A. `@import "tailwindcss"`** | Zostaw jak masz, nie duplikuj. |
| **B. `@plugin "daisyui"` + theme override** | Skopiuj ten blok do swojego pliku — to nadpisanie kolorów Daisy żeby `btn-primary` wyglądał jak nasz brand. Jeśli masz już własny theme Daisy, scal kolory. |
| **C. `@font-face` (×6 deklaracji)** | Skopiuj. **Sprawdź ścieżki url()** — jeśli fonty nie są w `/fonts/`, podmień. |
| **D. `@theme { ... }`** | Skopiuj cały blok do swojego CSS. Jeśli już masz `@theme`, scal property po property — moje nazwy mają prefix `--color-*`, `--ease-*`, `--duration-*`, więc kolizji być nie powinno (chyba że masz inne barwy też jako `--color-bg`). |
| **E. `@layer components` + `@layer utilities`** | Skopiuj. Zawiera `.text-gradient-brand`, `.card-glass`, `.btn-glow`, `.bg-blob`, `.marquee`, `.reveal`, `.swipe-deck` i powiązane keyframes. |

Po sklejeniu zrób build (`npm run build` albo cokolwiek masz) i sprawdź czy nie ma errorów.

### 3. Zainstaluj plugin Alpine'a

```bash
npm install @alpinejs/intersect
```

W kodzie inicjalizującym Alpine:

```js
import Alpine from 'alpinejs'
import intersect from '@alpinejs/intersect'

Alpine.plugin(intersect)
Alpine.start()
```

Bez tego pluginu nie zadziała `x-intersect` (używamy do scroll reveal — patrz `ANIMATIONS-MAP.md` poz. 6).

### 4. Smoke test

Otwórz dowolną podstronę swojego projektu i wklej do `<body>`:

```html
<section class="py-24 bg-[var(--color-bg)] relative overflow-hidden">
  <div class="bg-blob"></div>
  <div class="container max-w-6xl mx-auto px-6 relative z-10">
    <h1 class="text-display-xl text-ink">
      Smoke test
      <span class="text-gradient-brand">design DNA</span>
    </h1>
    <p class="text-ink-dim mt-4">Jeśli to widzisz w fioletowo-magentowym gradencie, działa.</p>
    <a href="#" class="btn-glow mt-8 inline-flex">Sprawdź →</a>
  </div>
</section>
```

Powinieneś zobaczyć:
- Bardzo ciemne tło z fioletowym blobem driftującym
- Tytuł w Space Grotesk, słowo "design DNA" w gradent fiolet→magenta
- Akapit w jasno-szarym Inter
- Przycisk magenta z glow-shadow

Jeśli czegoś brakuje — patrz "Troubleshooting" niżej.

### 5. Skopiuj examples (opcjonalnie)

`examples/hero.html`, `card.html`, `cta.html` to fragmenty HTML do podejrzenia konwencji. Możesz je trzymać poza source jako referencję, albo wkleić co potrzebujesz do swoich szablonów.

`examples/_preview.html` jest standalone — otwiera się w przeglądarce i kompiluje Tailwind 4 na żywo (`@tailwindcss/browser@4` z unpkg). Użyteczny do quick-check'u "czy to wygląda jak miało".

---

## B. Quick start od zera (jeśli projekt jest pusty)

```bash
mkdir my-project && cd my-project
npm init -y
npm install -D tailwindcss@4 daisyui@5.5 @tailwindcss/cli
npm install alpinejs @alpinejs/intersect
```

Stwórz `src/app.css` — skopiuj cały zawartość mojego `app.css` z paczki.

Stwórz `index.html`:

```html
<!doctype html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="/dist/app.css" />
  <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.x.x/dist/cdn.min.js"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
  <!-- … -->
</body>
</html>
```

Build:

```bash
npx @tailwindcss/cli -i src/app.css -o dist/app.css --watch
```

Skopiuj fonty do `public/fonts/` i `dist/app.css` razem z `index.html` na statyczny hosting.

---

## C. Troubleshooting

### Tytuł nie ma gradientu, jest biały

Twój build nie skompilował naszego `@layer utilities`. Sprawdź:
1. Czy `app.css` zawiera `@import "tailwindcss";` PRZED `@layer`.
2. Czy build nie cache'uje — `rm -rf dist/ && npm run build`.

### Fonty się nie ładują (widać systemowy)

Sprawdź `Network` tab w DevTools — pewnie 404 na `.woff2`. Najczęstsza przyczyna:
- Ścieżka w `@font-face` `url('/fonts/inter-latin.woff2')` nie zgadza się z lokalizacją plików.
- Brak CORS — jeśli serwujesz fonty z innego origina, dodaj `crossorigin` na `<link>` i headers na serwerze.

### `btn-primary` z DaisyUI ma "nie nasz" kolor

Brakuje bloku `@plugin "daisyui/theme"` w twoim `app.css`. Skopiuj sekcję B z mojego `app.css`.

### Alpine `x-intersect` nic nie robi

Brak pluginu. `npm install @alpinejs/intersect` + `Alpine.plugin(intersect)` przed `Alpine.start()`.

### Animacje są zbyt szybkie / brak płynności

Sprawdź `prefers-reduced-motion` w systemie. Wszystkie nasze animacje są wyłączone gdy user ma to włączone — to **feature**, nie bug.

### `@theme` i `@plugin` rzucają błąd "unknown at-rule"

Twój Tailwind to wersja 3, nie 4. Te dyrektywy są ekskluzywne dla v4. Albo upgrade'uj do v4, albo backport tokenów do `tailwind.config.js` w stylu v3 (dłużej).

---

## D. Co zrobi LLM jak zobaczy ten setup

Gdy w projekcie jest CLAUDE.md / instrukcje dla AI, dodaj sekcję:

> ### Design system
> Projekt używa custom design DNA opartego o postac.ai. Tokeny i custom utilities są w `src/app.css`. Przed pisaniem nowych komponentów:
> 1. Sprawdź `DESIGN-DNA.md` dla palety i paternów.
> 2. Brand komponenty (hero, gradient text, glass cards, glow buttons) używaj naszych utility classes (`.text-gradient-brand`, `.card-glass`, `.btn-glow`, `.bg-blob`).
> 3. Formularze, alerty, dropdowny, modale → DaisyUI (`btn`, `input`, `alert`, `dropdown`).
> 4. Animacje → patrz `ANIMATIONS-MAP.md`. Domyślnie pure CSS, jak nie da się — Alpine `x-intersect` / `x-data`. **Nigdy** vanilla JS.
> 5. NIE pisz inline `<style>` w plikach stron. Wszystko w `app.css`.

Skopiuj te 5 punktów bezpośrednio do swojego CLAUDE.md — to wymusi spójność niezależnie od tego, kto generuje strony.
