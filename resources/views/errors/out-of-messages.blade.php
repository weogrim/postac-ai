@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-lg px-4 py-16">
        <x-alert type="warning" style="soft" title="Limit wiadomości wyczerpany">
            {{ $message }}
        </x-alert>
    </div>
@endsection
