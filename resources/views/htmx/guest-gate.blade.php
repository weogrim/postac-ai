{{-- OOB: zastąp composer formy CTA-paskiem --}}
<div hx-swap-oob="outerHTML:#composer">
    @include('chat._composer', ['chat' => null, 'locked' => true])
</div>

{{-- OOB: pokaż modal blokujący --}}
<div hx-swap-oob="outerHTML:#register-gate">
    @include('chat._gate-modal', ['open' => true])
</div>
