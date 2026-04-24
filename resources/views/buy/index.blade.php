@extends('layouts.app')

@section('title', 'Pakiety wiadomości')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-16">
        <div class="mb-10 text-center">
            <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Pakiety wiadomości</h1>
            <p class="mt-3 text-base opacity-70">
                Wybierz pakiet jednorazowy albo odblokuj nielimitowane wiadomości subskrypcją Premium.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($packages as $package)
                @php
                    $isPremium = $package->isSubscription();
                    $isFeatured = $package === \App\Billing\Package::Ten;
                @endphp
                <div class="card bg-base-200 {{ $isFeatured ? 'ring-2 ring-primary' : '' }} {{ $isPremium ? 'border-2 border-accent' : '' }} transition hover:-translate-y-1 hover:shadow-xl">
                    <div class="card-body">
                        @if ($isFeatured)
                            <div class="badge badge-primary badge-sm self-start">{{ $package->tagline() }}</div>
                        @elseif ($isPremium)
                            <div class="badge badge-accent badge-sm self-start">{{ $package->tagline() }}</div>
                        @else
                            <div class="text-xs font-semibold uppercase tracking-wide opacity-60">{{ $package->tagline() }}</div>
                        @endif

                        <h2 class="card-title text-2xl">{{ $package->label() }}</h2>

                        <div class="my-2 flex items-baseline gap-1">
                            <span class="text-4xl font-bold">{{ $package->priceZloty() }}</span>
                            <span class="text-base opacity-70">zł{{ $isPremium ? '/mies.' : '' }}</span>
                        </div>

                        <ul class="mt-2 space-y-2 text-sm">
                            @if ($isPremium)
                                <li>✓ Nielimitowane wiadomości</li>
                                <li>✓ Dostęp do najlepszego modelu</li>
                                <li>✓ Anuluj kiedy chcesz</li>
                            @else
                                <li>✓ {{ $package->messageLimit() }} wiadomości</li>
                                <li>✓ Model {{ $package->model()?->value }}</li>
                                <li>✓ Pakiet nie wygasa</li>
                            @endif
                        </ul>

                        <div class="card-actions mt-4">
                            <form method="POST" action="{{ route('buy.store', $package) }}" class="w-full" hx-boost="false">
                                @csrf
                                <button type="submit" class="btn w-full {{ $isPremium ? 'btn-accent' : ($isFeatured ? 'btn-primary' : 'btn-outline') }}">
                                    {{ $isPremium ? 'Zostań Premium' : 'Kup pakiet' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <p class="mt-8 text-center text-xs opacity-60">
            Płatności obsługuje Stripe. Faktury dostępne po zakupie w panelu rozliczeń.
        </p>
    </div>
@endsection
