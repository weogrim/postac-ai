@props([
    'type',
    'id',
    'label' => 'Zgłoś',
])

@php
    $modalId = 'report-modal-'.$type.'-'.$id;
@endphp

<button
    type="button"
    class="btn btn-ghost btn-xs text-base-content/60 hover:text-error"
    onclick="document.getElementById('{{ $modalId }}').showModal()"
    aria-label="Zgłoś treść"
>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 2H21l-3 6 3 6h-8.5l-1-2H5a2 2 0 00-2 2zm0 0h2" />
    </svg>
    <span>{{ $label }}</span>
</button>

<dialog id="{{ $modalId }}" class="modal">
    <div class="modal-box">
        <h3 class="text-lg font-bold">Zgłoś treść</h3>
        <p class="mt-1 text-sm text-base-content/70">
            Powiedz nam, co jest nie tak — admin zajmie się sprawą.
        </p>

        <form
            class="mt-4 space-y-4"
            hx-post="{{ route('report.store') }}"
            hx-swap="none"
            hx-on::after-request="if (event.detail.successful) { document.getElementById('{{ $modalId }}').close(); this.reset(); }"
        >
            @csrf
            <input type="hidden" name="reportable_type" value="{{ $type }}">
            <input type="hidden" name="reportable_id" value="{{ $id }}">

            <label class="form-control w-full">
                <div class="label"><span class="label-text">Powód</span></div>
                <select name="reason" class="select select-bordered w-full" required>
                    <option value="">— wybierz —</option>
                    @foreach (\App\Reporting\Enums\ReportReason::cases() as $reason)
                        <option value="{{ $reason->value }}">{{ $reason->getLabel() }}</option>
                    @endforeach
                </select>
            </label>

            <label class="form-control w-full">
                <div class="label"><span class="label-text">Opis (opcjonalnie)</span></div>
                <textarea
                    name="description"
                    class="textarea textarea-bordered w-full"
                    rows="3"
                    maxlength="1000"
                    placeholder="Co konkretnie jest nie tak?"
                ></textarea>
            </label>

            <div class="modal-action">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('{{ $modalId }}').close()">
                    Anuluj
                </button>
                <button type="submit" class="btn btn-error">Zgłoś</button>
            </div>
        </form>
    </div>

    <form method="dialog" class="modal-backdrop">
        <button>Zamknij</button>
    </form>
</dialog>
