@php
    /** @var \App\Models\Message $message */
    $isCharacter = $message->sender_role->value === 'character';
    $side = $isCharacter ? 'chat-start' : 'chat-end';
@endphp

<div class="chat {{ $side }}">
    @if ($isCharacter)
        <div class="chat-image avatar">
            <div class="h-10 w-10 rounded-full">
                <img src="{{ $message->character?->avatarUrl('square') }}" alt="">
            </div>
        </div>
    @endif

    <div class="chat-bubble {{ $isCharacter ? 'chat-bubble-neutral' : 'chat-bubble-primary' }} whitespace-pre-line">{{ $message->content }}</div>
</div>
