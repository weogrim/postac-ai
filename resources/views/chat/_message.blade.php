@php
    /** @var \App\Chat\Models\MessageModel $message */
    /** @var bool $streaming */
    $streaming = $streaming ?? false;
    $isCharacter = $message->sender_role->value === 'character';
@endphp

<div data-message-id="{{ $message->id }}" class="flex flex-col {{ $isCharacter ? 'items-start' : 'items-end' }}">
    @if ($isCharacter)
        <div
            class="bg-panel-2 text-ink rounded-2xl rounded-tl-sm px-4 py-3 max-w-[85%] text-sm whitespace-pre-line"
            @if ($streaming) data-streaming="true" @endif
        >{{ $message->content }}</div>

        @if (! $streaming && $message->content !== '')
            <div class="mt-1 opacity-60">
                <x-report-button type="message" :id="$message->id" />
            </div>
        @endif
    @else
        <div class="bg-gradient-to-br from-violet to-magenta text-white rounded-2xl rounded-tr-sm px-4 py-3 max-w-[85%] text-sm whitespace-pre-line">{{ $message->content }}</div>
    @endif
</div>
