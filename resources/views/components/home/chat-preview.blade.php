@props([])

<div {{ $attributes->merge(['class' => 'card-glass w-full max-w-md p-5']) }}>
    <div class="flex items-center gap-3 pb-4 border-b border-line">
        <div class="rounded-full bg-gradient-to-br from-crimson to-magenta flex items-center justify-center font-bold text-white h-10 w-10 text-sm shrink-0">
            JP
        </div>
        <div class="min-w-0">
            <div class="font-semibold flex items-center gap-1 text-ink">
                Józef Piłsudski
                <svg class="w-4 h-4 text-cyan shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="text-xs text-ink-mute truncate">Marszałek · Demo rozmowy</div>
        </div>
    </div>

    <div class="mt-4 space-y-3">
        <div class="bg-panel-2 rounded-2xl rounded-tl-sm px-4 py-3 max-w-[80%] text-sm text-ink">
            Witaj. Jestem Józef Piłsudski. Chcesz porozmawiać o wolności, strategii, czy o czymś bardziej osobistym?
        </div>

        <div class="bg-gradient-to-br from-violet to-magenta rounded-2xl rounded-tr-sm px-4 py-3 max-w-[80%] text-sm text-white ml-auto">
            Opowiedz mi o Bitwie Warszawskiej 🇵🇱
        </div>

        <div class="bg-panel-2 rounded-2xl rounded-tl-sm px-4 py-3 max-w-[80%] text-sm text-ink">
            Ach, Cud nad Wisłą! Pozwól, że opowiem ci, jak naprawdę wyglądał ten dzień w 1920 roku…
        </div>
    </div>

    <div class="mt-5 pt-4 border-t border-line flex items-center gap-3">
        <div class="flex-1 px-4 py-2.5 rounded-full bg-panel-2 border border-line text-sm text-ink-mute select-none">
            Napisz wiadomość…
        </div>
        <button type="button" disabled aria-label="Wyślij"
                class="btn btn-primary btn-circle btn-sm">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 5l7 7-7 7"/>
            </svg>
        </button>
    </div>

    <p class="mt-4 text-xs text-ink-mute text-center">
        <span class="text-orange">⚠</span>
        To AI — odpowiedzi mogą być niedokładne. Traktuj jako rozrywkę.
    </p>
</div>
