@extends('layouts.app', ['title' => 'Pakiety — postac.ai'])

@section('content')
    <section class="relative overflow-hidden py-12">
        <div class="bg-blob"></div>

        <div class="container-app relative z-10 mx-auto flex w-full max-w-3xl flex-col gap-6">
            <header class="flex flex-col gap-2">
                <h1 class="text-display-md">Twoje pakiety</h1>
                <p class="text-sm text-ink-dim">Limity wiadomości — dzienne i dokupione.</p>
            </header>

            @if ($limits->isEmpty())
                <div class="card-glass p-8 text-center">
                    <p class="font-display text-lg">Brak limitów</p>
                    <p class="mt-2 text-sm text-ink-dim">Nie masz jeszcze żadnych pakietów ani przyznanych limitów dziennych.</p>
                    <a href="{{ route('buy.index') }}" class="btn-glow mt-6">Zobacz pakiety &rarr;</a>
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($limits as $limit)
                        @php
                            $used = (int) $limit->used;
                            $quota = (int) $limit->quota;
                            $percent = $quota > 0 ? min(100, (int) round($used / $quota * 100)) : 0;
                        @endphp
                        <div class="card-glass p-5 flex flex-col gap-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="font-display font-semibold text-base text-ink">{{ $limit->model_type?->label() ?? '—' }}</h3>
                                    <p class="text-xs text-ink-mute">{{ $limit->limit_type?->value }}</p>
                                </div>
                                <span class="badge badge-ghost">prio {{ $limit->priority }}</span>
                            </div>
                            <progress
                                class="progress {{ $percent >= 90 ? 'progress-error' : 'progress-primary' }}"
                                value="{{ $used }}"
                                max="{{ $quota }}"
                            ></progress>
                            <p class="text-sm text-ink-dim">
                                Wykorzystano <span class="font-semibold text-ink">{{ $used }}</span> z <span class="font-semibold text-ink">{{ $quota }}</span>
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
