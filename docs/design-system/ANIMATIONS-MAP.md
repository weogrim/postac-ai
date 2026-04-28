# Animations Map — postac.ai

Każdy efekt z obecnej landing → strategia migracji na stack `Tailwind 4 + HTMX + Alpine` bez własnego JS.

**Legenda:**
- 🟢 **Pure CSS** — `@keyframes` + Tailwind animation utility. Zero JS. Najtańsze utrzymanie.
- 🔵 **Alpine x-intersect** — uruchamiamy animację gdy element wjedzie w viewport. Wymaga pluginu `@alpinejs/intersect`.
- 🟡 **Alpine x-data + x-init** — efekt wymaga state'a (timer, kolejność, cycle). Najdroższy z dopuszczonych.
- ⚪ **Wytnij** — efekt nie jest wart utrzymania, lub łatwo go zastąpić statycznie.

---

## Tabela

| # | Efekt | Obecnie (postac.ai) | Strategia | Uzasadnienie |
|---|---|---|---|---|
| 1 | **Rotujące imiona w hero** ("Piłsudskim" → "Kopernikiem" → …) | vanilla JS `setInterval` + manual class swap | 🟡 Alpine `x-data` + `x-init` | Potrzebny state (current name) i timer. Nie da się czysto w CSS. ~10 linii. |
| 2 | **Marquee z nazwami postaci** | CSS `@keyframes translateX` (już pure) | 🟢 Pure CSS — `.marquee` w `app.css` | Już jest pure, tylko nazwij utility. |
| 3 | **Floating elementy** (emoji 🎭 📚 💕 obok hero) | CSS `@keyframes translateY` | 🟢 Pure CSS — `.float-slow`, `.float-medium` | Klasy w `app.css`, ~6 linii. |
| 4 | **Animowany chat preview** (typing → bubble appears) | vanilla JS sequencer | 🟡 Alpine `x-data` + sekwencja `setTimeout` | Sekwencja jest ważna dla pierwszego wrażenia. Warto zachować. ~30 linii Alpine. |
| 5 | **Swipe cards autocycle** (sekcja Randki) | vanilla JS interval + class swap | 🟡 Alpine `x-data` z `top` index + `x-init` interval | Zachowujemy. ~20 linii Alpine. |
| 6 | **Scroll reveal animations** (cards pojawiają się przy przewijaniu) | `IntersectionObserver` + class toggle | 🔵 Alpine `x-intersect.once="shown = true"` | Alpine ma to wbudowane. Jedna linia per element. |
| 7 | **Count-up stats** (1247, 12400 itp.) | vanilla JS animacja liczb | ⚪ **Wytnij** | Liczby w obecnej wersji są fake (zostały już usunięte przed deployem). Jak wrócą prawdziwe — najwyżej napiszemy mały Alpine component, ale dziś brak. |
| 8 | **Confetti przy submit** (waitlist) | vanilla JS particle system (~60 elementów) | 🟡 Alpine `x-data` + render template OR ⚪ wytnij | Trade-off: zostawienie kosztuje ~40 linii Alpine + DOM cleanup. Wytnięcie = brak emocji ale prostota. **Decyzja:** wytnij na MVP, dodajemy w v1.1. |
| 9 | **Rotujący gradient na tytułach** | CSS `@keyframes` background-position | 🟢 Pure CSS — `.text-gradient-brand--animated` | Klasa wariant. Domyślny gradient jest statyczny. |
| 10 | **Background blobs (radialny gradient drift)** | CSS `@keyframes` translate + scale | 🟢 Pure CSS — `.bg-blob` | Jeden klocek w `app.css`. |
| 11 | **Hover lift na karcie** (`translateY(-4px)`) | CSS transition | 🟢 Pure CSS — Tailwind `transition-transform hover:-translate-y-1` | Czysty Tailwind, zero custom CSS. |
| 12 | **Button glow przy hover** (box-shadow magenta) | CSS transition | 🟢 Pure CSS — `.btn-glow` w `app.css` | Klasa utility. |
| 13 | **Pulsujący live-dot** ("LIVE" badge na karcie) | CSS `@keyframes` opacity | 🟢 Pure CSS — Tailwind `animate-pulse` (built-in) | Zero custom. |
| 14 | **Filtrowalna siatka postaci** (kategorie) | vanilla JS show/hide | 🟡 Alpine `x-data` + `x-show` z transition | Standardowy pattern Alpine, ~15 linii. |
| 15 | **Smooth scroll na anchor links** | CSS `scroll-behavior: smooth` | 🟢 Pure CSS — w `:root`, już jest | Bez zmian. |
| 16 | **Reduced-motion respect** | `@media (prefers-reduced-motion)` | 🟢 Pure CSS — wszystkie animacje pod media query | W `app.css` zawijamy wszystkie animacje w `@media (prefers-reduced-motion: no-preference)`. |

---

## Co to oznacza w praktyce

**Liczby:**
- 🟢 Pure CSS: **9 efektów** (najwięcej, bo właśnie tak ma być)
- 🔵 Alpine x-intersect: **1 efekt** (scroll reveal — używany na ~30 elementach na stronie, ale wszystkie tym samym wzorcem)
- 🟡 Alpine x-data: **5 efektów** (rotator, chat, swipe, filter, *opcjonalnie* confetti)
- ⚪ Wytnij: **2 efekty** (count-up, confetti) — odzyskujemy ~150 linii JS

**Łączny budżet Alpine** żeby zachować 100% feel postac.ai: ~80-100 linii kodu w `x-data` blokach (rozproszone po HTML, nie wymaga osobnego JS bundle).

**Plugin Alpine'a do zainstalowania:** `@alpinejs/intersect` (potrzebny dla scroll reveal). Nic poza tym.

---

## Kolejność implementacji (sugestia)

Gdy programista zacznie pracę na branchu:

1. **Najpierw 🟢 pure CSS w `app.css`** — wszystkie keyframes, marquee, blob, glow. To jest "wizualny szkielet". 1-2h.
2. **Potem 🔵 reveal-on-scroll** — bo używamy go na 30+ elementach, dobrze mieć szybko. 30 min.
3. **Potem 🟡 najważniejsze Alpine** — rotujące imiona w hero (najbardziej widoczne) + animowany chat. Po 1h każdy.
4. **Na końcu 🟡 swipe deck + filter** — bo to "drugorzędne" sekcje. 1-2h.
5. **Confetti i count-up dopiero w v1.1** — jeśli w ogóle.

Total budżet: **~6-8h pracy programisty** żeby mieć całą animacyjną tożsamość postac.ai w nowym stacku.

---

## Reduced motion — bez wyjątków

Każda animacja, którą dodajesz, musi być w `app.css` opakowana w:

```css
@media (prefers-reduced-motion: no-preference) {
  /* tutaj keyframes / transitions */
}
```

albo użyj Tailwind 4 modifier `motion-safe:`:

```html
<div class="motion-safe:animate-blob"></div>
```

**Powód:** użytkownicy z migrenami / vestibular issues. To nie jest opcjonalne.
