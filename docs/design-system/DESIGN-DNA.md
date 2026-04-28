# Design DNA — postac.ai

Język wizualny postac.ai opisany przez tokeny + 7 paternów. Każdy patern ma minimalny przykład HTML gotowy do wklejenia w projekt na Tailwind 4 + DaisyUI 5.5.

---

## 1. Filozofia

| Co | Jak |
|---|---|
| **Tonacja** | Dark first, premium, polskie. Bez rzucających się "AI sci-fi" wizualizacji. |
| **Kolor** | Tło prawie czarne z fioletowym podtonem. Akcenty: fiolet → magenta (gradient). Pomarańcz / róż w sekcji "Randki". |
| **Typografia** | Display = Space Grotesk (geometryczny, polski rytm). Body = Inter (czytelny, neutralny). Mono = JetBrains Mono (chat / kod). |
| **Ruch** | Slow drift na tłach, subtelne hover lift, szybki spring na klik. Nigdy looped wow-effects. |
| **Forma** | Border-radius 12-20px (zaokrąglenia, nie ostre kanty). Glassmorphism akceptowalny tylko na panelach nad gradientami. |
| **Density** | Powietrza dużo. Hero `padding: 80-120px`. Karty `padding: 24-28px`. Linia `line-height: 1.6-1.7`. |

---

## 2. Paleta

Kolory w **`oklch()`** (Tailwind 4 native). Hex podany jako fallback / referencja.

### Tło / powierzchnie

| Token | oklch | hex | Zastosowanie |
|---|---|---|---|
| `--color-bg` | `oklch(0.107 0.011 271)` | `#0a0a0f` | Główne tło aplikacji |
| `--color-panel` | `oklch(0.142 0.018 274)` | `#11111a` | Karty, modale, sticky nav |
| `--color-panel-2` | `oklch(0.165 0.020 274)` | `#16161f` | Hover na karcie, alt-tła |
| `--color-line` | `oklch(1 0 0 / 0.08)` | `rgba(255,255,255,0.08)` | Bordery, separatory |

### Tekst

| Token | oklch | hex | Zastosowanie |
|---|---|---|---|
| `--color-ink` | `oklch(0.929 0.005 286)` | `#e9e9f0` | Tytuły, główny tekst |
| `--color-ink-dim` | `oklch(0.770 0.014 273)` | `#b8b8c6` | Body, opisy |
| `--color-ink-mute` | `oklch(0.475 0.020 272)` | `#6a6a7d` | Captions, eyebrowy, placeholdery |

### Brand (gradient akcenty)

| Token | oklch | hex | Tailwind alias | Zastosowanie |
|---|---|---|---|---|
| `--color-violet` | `oklch(0.633 0.260 304)` | `#a855f7` | `purple-500` | Główny brand, początek gradientu |
| `--color-magenta` | `oklch(0.656 0.241 354)` | `#ec4899` | `pink-500` | Drugi brand, koniec gradientu, CTA |
| `--color-rose` | `oklch(0.658 0.231 16)` | `#f43f5e` | `rose-500` | Sekcja Randki, miłość-warianty |
| `--color-cyan` | `oklch(0.788 0.157 215)` | `#22d3ee` | `cyan-400` | Akcent informacyjny, weryfikacja |
| `--color-orange` | `oklch(0.760 0.184 53)` | `#fb923c` | `orange-400` | Trzeci kolor w gradientach 3-stop |
| `--color-crimson` | `oklch(0.561 0.238 25)` | `#dc143c` | — | Polska czerwień (Piłsudski, akcenty PL) |

### Statusy

| Token | oklch | Zastosowanie |
|---|---|---|
| `--color-success` | `oklch(0.696 0.170 162)` | "Gotowa" badge, success toast |
| `--color-warning` | `oklch(0.795 0.184 86)` | Pending, ostrzeżenia |
| `--color-error` | `oklch(0.628 0.258 29)` | Błędy formularzy |

---

## 3. Typografia

### Skala

| Klasa | Tailwind | Wartość | Zastosowanie |
|---|---|---|---|
| Display XL | `text-display-xl` | `clamp(48px, 7vw, 88px)` Space Grotesk 700 | Hero h1 |
| Display L | `text-display-lg` | `clamp(36px, 5vw, 56px)` Space Grotesk 700 | Sekcja h2 |
| Display M | `text-display-md` | `28-32px` Space Grotesk 600 | Karty h3 |
| Body L | `text-lg` | `18px` Inter 400 | Subtitle, lead |
| Body M | `text-base` | `16px` Inter 400 | Default body |
| Body S | `text-sm` | `14px` Inter 400 | Captions, meta |
| Eyebrow | `text-xs uppercase tracking-[0.12em]` | `12px` Inter 600 | Section labels |
| Mono | `font-mono` | Inter Mono | Chat, code, ID |

### Reguły

- **Display ZAWSZE Space Grotesk.** Body ZAWSZE Inter. Nie mieszaj.
- **Tytuły z gradientem** — używaj `.text-gradient-brand` (patern niżej), nigdy nie nakładaj `bg-clip-text` ad hoc.
- **Tracking.** Display: `tracking-[-0.02em]` (lekko zwężaj). Eyebrow: `tracking-[0.12em] uppercase`.
- **Line height.** Display: `1.05-1.15`. Body: `1.6-1.7`. Lista: `1.5`.

---

## 4. Spacing & rhythm

Tailwind default (4px) wystarcza. Dodatkowe sekcyjne paddingi:

| Token | Wartość | Zastosowanie |
|---|---|---|
| `--spacing-section-y` | `clamp(64px, 10vw, 128px)` | Padding sekcji `<section>` |
| `--spacing-container` | `1240px` | Max-width głównego kontenera |
| `--spacing-card-x` | `28px` | Wewnątrz kart |
| `--spacing-card-y` | `24px` | j.w. |

W kodzie:
```html
<section class="py-section">
  <div class="container max-w-[var(--spacing-container)] mx-auto px-6">
```

---

## 5. Easing & timing

| Token | Wartość | Zastosowanie |
|---|---|---|
| `--ease-spring` | `cubic-bezier(0.2, 0.8, 0.2, 1)` | Hover, click, slide-in (90% przypadków) |
| `--ease-glide` | `cubic-bezier(0.4, 0, 0.2, 1)` | Long fades, scrolls |
| `--ease-snap` | `cubic-bezier(0.6, 0, 0.4, 1.4)` | Pop (overshoot) — używaj rzadko |
| `--duration-fast` | `150ms` | Hover, focus |
| `--duration-base` | `300ms` | Większość transitions |
| `--duration-slow` | `600ms` | Reveal, page transitions |
| `--duration-ambient` | `8-20s` | Floating tła, marquee, slow drift |

Reguła: **interakcja = `--ease-spring` + `--duration-base`.** Wszystko inne uzasadniaj.

---

## 6. Patterny wizualne

7 wzorców które tworzą "to wygląda jak postac.ai". Każdy ma utility class w `app.css` (definicja w [INSTALLATION.md](INSTALLATION.md)).

### 6.1 Gradient text (`.text-gradient-brand`)

Tytuły z fioletowo-magentowym przejściem. **Brand mark.** Używaj na 1 słowie / frazie w sekcji, nie na całym akapicie.

```html
<h1 class="text-display-xl">
  Rozmawiaj z
  <span class="text-gradient-brand">Piłsudskim</span>
  po polsku
</h1>
```

Wariant 3-stop (pomarańcz w środku) — `.text-gradient-warm` — sekcja Randki.

### 6.2 Glass card (`.card-glass`)

Półprzezroczysty panel z blurem nad gradientowym tłem. Border subtelny, hover lift 4px.

```html
<div class="card-glass p-7">
  <div class="text-3xl mb-4">🎭</div>
  <h3 class="text-display-md mb-2">Stwórz własną postać</h3>
  <p class="text-ink-dim">Opisujesz w naturalny sposób — my generujemy resztę.</p>
</div>
```

### 6.3 Glow button (`.btn-glow`)

Główne CTA. Różowy gradient + box-shadow który "pulsuje" przy hover. Używać max 1× w widoku.

```html
<a href="#waitlist" class="btn-glow">
  Dołącz do listy →
</a>
```

DaisyUI fallback dla zwykłych przycisków (form submit, nav action): `btn btn-primary`.

### 6.4 Blob background (`.bg-blob`)

Animowany radialny gradient w tle hero / sekcji, slow drift. Pure CSS, brak JS.

```html
<section class="relative overflow-hidden">
  <div class="bg-blob"></div>
  <div class="container relative z-10">…content…</div>
</section>
```

### 6.5 Marquee (`.marquee`)

Pozioma przewijająca lista (np. nazwy postaci, social proof). Pure CSS animation, infinite loop.

```html
<div class="marquee">
  <div class="marquee-track">
    <span>Piłsudski</span><span>Kopernik</span><span>Wiedźmin</span>
    <!-- duplikuj zawartość ×2 dla seamless loop -->
    <span>Piłsudski</span><span>Kopernik</span><span>Wiedźmin</span>
  </div>
</div>
```

### 6.6 Reveal on scroll (`.reveal` + Alpine `x-intersect`)

Element pojawia się gdy wjedzie w viewport. Używamy Alpine `x-intersect` (z pluginu `@alpinejs/intersect`), nie własnego IntersectionObserver.

```html
<div
  class="reveal"
  x-data="{ shown: false }"
  x-intersect.once="shown = true"
  :class="shown && 'reveal-in'"
>
  …content…
</div>
```

### 6.7 Swipe deck (`.swipe-deck`)

Stos kart z autocycle (sekcja Randki). Wymaga Alpine `x-data` dla state.

```html
<div
  class="swipe-deck"
  x-data="{
    cards: ['Maja', 'Anita', 'Kasia'],
    top: 0,
    cycle() { this.top = (this.top + 1) % this.cards.length }
  }"
  x-init="setInterval(() => cycle(), 4500)"
>
  <template x-for="(card, i) in cards" :key="i">
    <div class="swipe-card" :class="i === top ? 'swipe-top' : 'swipe-back'">
      <span x-text="card"></span>
    </div>
  </template>
</div>
```

---

## 7. DaisyUI — kiedy używać, kiedy nie

**Używaj DaisyUI dla:**
- `btn`, `btn-ghost`, `btn-outline` (zwykłe akcje, nawigacja)
- `input`, `textarea`, `select`, `checkbox`, `radio` (formularze)
- `alert`, `toast` (komunikaty)
- `dropdown`, `menu` (nav menu)
- `modal`, `drawer` (overlays)
- `tooltip`, `badge` (mikrokomponenty)
- `tabs`, `breadcrumbs` (nawigacja)

**NIE używaj DaisyUI dla (rób z naszych utilities):**
- Hero / sekcji intro (każdy projekt wygląda generycznie z DaisyUI hero)
- Brand-CTA (główny `Dołącz` przycisk → `.btn-glow`, nie `btn-primary`)
- Kart "feature" / "character" (→ `.card-glass`)
- Animowanych tytułów (→ `.text-gradient-brand`)
- Tła sekcji (→ `.bg-blob`, gradient surfaces)

**Theme DaisyUI:** w `app.css` jest `@plugin "daisyui/theme"` który podmienia kolory Daisy na nasze (primary = magenta, secondary = violet). Dzięki temu `btn-primary` wygląda spójnie.

---

## 8. Co dalej

- Skontaktuj się z Łukaszem przed tym jak design ewoluuje (nowe kolory, nowe paterny) — żeby tokeny w `app.css` zostały zaktualizowane w jednym miejscu.
- Jeśli pojawi się 2-gi produkt (nie postac.ai) używający tej DNA — wyciągnij `@theme` block do osobnego pliku `tokens.css` i importuj w obu projektach.
- Storybook / wersjonowanie tokenów rozważcie dopiero przy 3+ produktach.
