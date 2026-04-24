@props([
    'target' => 'toasts',
])

<div id="{{ $target }}" hx-swap-oob="beforeend">
    {{ $slot }}
</div>
