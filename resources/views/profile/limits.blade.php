@extends('layouts.app', ['title' => 'Pakiety — postac.ai'])

@section('content')
    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-8 sm:py-12">
        <header class="flex flex-col gap-2">
            <h1 class="text-3xl font-bold tracking-tight">Twoje pakiety</h1>
            <p class="text-sm text-base-content/70">Limity wiadomości — dzienne i dokupione.</p>
        </header>

        @if ($limits->isEmpty())
            <x-alert type="info" style="soft" title="Brak limitów">
                Nie masz jeszcze żadnych pakietów ani przyznanych limitów dziennych.
            </x-alert>
        @else
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($limits as $limit)
                    @php
                        $used = (int) $limit->used;
                        $quota = (int) $limit->quota;
                        $percent = $quota > 0 ? min(100, (int) round($used / $quota * 100)) : 0;
                    @endphp
                    <div class="card bg-base-100 shadow">
                        <div class="card-body gap-3 p-5">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="card-title text-base">{{ $limit->model_type?->label() ?? '—' }}</h3>
                                    <p class="text-xs text-base-content/60">{{ $limit->limit_type?->value }}</p>
                                </div>
                                <span class="badge badge-ghost">prio {{ $limit->priority }}</span>
                            </div>
                            <progress
                                class="progress {{ $percent >= 90 ? 'progress-error' : 'progress-primary' }}"
                                value="{{ $used }}"
                                max="{{ $quota }}"
                            ></progress>
                            <p class="text-sm text-base-content/70">
                                Wykorzystano <span class="font-semibold">{{ $used }}</span> z <span class="font-semibold">{{ $quota }}</span>
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
