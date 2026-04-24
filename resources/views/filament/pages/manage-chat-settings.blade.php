<x-filament::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end gap-2">
            <x-filament::button type="submit">Zapisz</x-filament::button>
        </div>
    </form>
</x-filament::page>
