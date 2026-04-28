@extends('layouts.app')

@section('title', 'Pakiety wiadomości')

@section('content')
    <section class="relative overflow-hidden py-12 sm:py-20">
        <div class="bg-blob"></div>

        <div class="container-app relative z-10">
            <div class="mb-12 text-center">
                <span class="eyebrow">Pakiety</span>
                <h1 class="text-display-lg mt-6">Wybierz pakiet <span class="text-gradient-brand">albo Premium</span></h1>
                <p class="mt-4 max-w-xl mx-auto text-ink-dim">
                    Pakiety jednorazowe nigdy nie wygasają. Premium odblokowuje nielimitowane wiadomości i najlepszy model.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($packages as $package)
                    @php
                        $isPremium = $package->isSubscription();
                        $isFeatured = $package === \App\Billing\Package::Ten;
                    @endphp
                    <div class="card-glass p-6 flex flex-col {{ $isFeatured ? 'ring-2 ring-magenta/60' : '' }} {{ $isPremium ? 'ring-2 ring-cyan/60' : '' }}">
                        @if ($isFeatured)
                            <div class="badge badge-primary badge-sm self-start">{{ $package->tagline() }}</div>
                        @elseif ($isPremium)
                            <div class="badge badge-accent badge-sm self-start">{{ $package->tagline() }}</div>
                        @else
                            <div class="text-xs font-semibold uppercase tracking-wide text-ink-mute">{{ $package->tagline() }}</div>
                        @endif

                        <h2 class="font-display text-xl font-semibold mt-3">{{ $package->label() }}</h2>

                        <div class="my-3 flex items-baseline gap-1">
                            <span class="text-display-md">{{ $package->priceZloty() }}</span>
                            <span class="text-base text-ink-dim">zł{{ $isPremium ? '/mies.' : '' }}</span>
                        </div>

                        <ul class="mt-2 space-y-2 text-sm text-ink-dim flex-1">
                            @if ($isPremium)
                                <li><span class="text-cyan">✓</span> Nielimitowane wiadomości</li>
                                <li><span class="text-cyan">✓</span> Dostęp do najlepszego modelu</li>
                                <li><span class="text-cyan">✓</span> Anuluj kiedy chcesz</li>
                            @else
                                <li><span class="text-cyan">✓</span> {{ $package->messageLimit() }} wiadomości</li>
                                <li><span class="text-cyan">✓</span> Model {{ $package->model()?->value }}</li>
                                <li><span class="text-cyan">✓</span> Pakiet nie wygasa</li>
                            @endif
                        </ul>

                        <form method="POST" action="{{ route('buy.store', $package) }}" class="w-full mt-6" hx-boost="false">
                            @csrf
                            @if ($isPremium || $isFeatured)
                                <button type="submit" class="btn-glow w-full">
                                    {{ $isPremium ? 'Zostań Premium' : 'Kup pakiet' }}
                                </button>
                            @else
                                <button type="submit" class="btn btn-outline w-full rounded-full">Kup pakiet</button>
                            @endif
                        </form>
                    </div>
                @endforeach
            </div>

            <p class="mt-10 text-center text-xs text-ink-mute">
                Płatności obsługuje Stripe. Faktury dostępne po zakupie w panelu rozliczeń.
            </p>
        </div>
    </section>
@endsection
