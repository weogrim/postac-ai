<x-filament::page>
    <div class="space-y-4">
        <div class="flex flex-wrap gap-6 text-sm">
            <div>
                <div class="text-xs uppercase opacity-60">Użytkownik</div>
                <div class="font-medium">{{ $record->user->email }}</div>
            </div>
            <div>
                <div class="text-xs uppercase opacity-60">Postać</div>
                <div class="font-medium">{{ $record->character->name }}</div>
            </div>
            <div>
                <div class="text-xs uppercase opacity-60">Utworzono</div>
                <div class="font-medium">{{ $record->created_at->format('Y-m-d H:i') }}</div>
            </div>
            <div>
                <div class="text-xs uppercase opacity-60">Wiadomości</div>
                <div class="font-medium">{{ $record->messages->count() }}</div>
            </div>
        </div>

        <div class="space-y-3 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
            @forelse ($record->messages as $message)
                @php
                    $isUser = $message->sender_role === \App\Messaging\SenderRole::User;
                @endphp
                <div class="flex {{ $isUser ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-xl rounded-lg px-4 py-2 {{ $isUser ? 'bg-primary-500 text-white' : 'bg-gray-100 dark:bg-gray-800' }}">
                        <div class="mb-1 text-xs opacity-70">
                            {{ $isUser ? $record->user->email : $record->character->name }} ·
                            {{ $message->created_at->format('H:i') }}
                        </div>
                        <div class="whitespace-pre-wrap">{{ $message->content }}</div>
                    </div>
                </div>
            @empty
                <div class="py-8 text-center text-sm opacity-60">Brak wiadomości.</div>
            @endforelse
        </div>
    </div>
</x-filament::page>
