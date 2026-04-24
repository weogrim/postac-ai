@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-8 sm:px-6 sm:py-12">
        <header class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Nowa postać</h1>
            <p class="mt-1 text-sm text-base-content/70">Określ, kim jest postać i jak ma się zachowywać.</p>
        </header>

        <form
            method="POST"
            action="{{ route('character.store') }}"
            enctype="multipart/form-data"
            hx-boost="false"
            class="space-y-5 rounded-2xl bg-base-100 p-6 shadow-md sm:p-8"
        >
            @csrf

            <fieldset class="fieldset">
                <legend class="fieldset-legend">Avatar</legend>

                <div class="flex items-center gap-4">
                    <div class="avatar">
                        <div class="h-20 w-20 rounded-2xl bg-base-200">
                            <img id="avatar-preview" src="" alt="" class="hidden h-full w-full rounded-2xl object-cover">
                            <div id="avatar-placeholder" class="flex h-full w-full items-center justify-center text-base-content/40">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h3l2-3h6l2 3h3v13H4zM12 10a4 4 0 100 8 4 4 0 000-8z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <input
                        type="file"
                        name="avatar"
                        accept="image/png,image/jpeg,image/webp"
                        class="file-input file-input-bordered @error('avatar') file-input-error @enderror w-full"
                        onchange="const f=this.files[0]; if(!f) return; const p=document.getElementById('avatar-preview'); p.src=URL.createObjectURL(f); p.classList.remove('hidden'); document.getElementById('avatar-placeholder').classList.add('hidden');"
                    >
                </div>

                @error('avatar')
                    <p class="label text-error text-xs">{{ $message }}</p>
                @else
                    <p class="label text-xs text-base-content/60">JPG, PNG lub WebP, max 5 MB. Obraz zostanie przycięty do kwadratu.</p>
                @enderror
            </fieldset>

            <x-form-input
                name="name"
                label="Nazwa"
                :value="old('name')"
                placeholder="Np. Sherlock Holmes"
                required
                autofocus
            />

            <fieldset class="fieldset">
                <legend class="fieldset-legend">Prompt</legend>
                <textarea
                    name="prompt"
                    rows="8"
                    required
                    placeholder="Opisz postać: kim jest, jak mówi, co wie, jak reaguje..."
                    class="textarea textarea-bordered w-full @error('prompt') textarea-error @enderror"
                >{{ old('prompt') }}</textarea>
                @error('prompt')
                    <p class="label text-error text-xs">{{ $message }}</p>
                @else
                    <p class="label text-xs text-base-content/60">Im bardziej szczegółowy prompt, tym lepiej postać utrzyma charakter.</p>
                @enderror
            </fieldset>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('home') }}" class="btn btn-ghost">Anuluj</a>
                <button type="submit" class="btn btn-primary">Stwórz i rozmawiaj</button>
            </div>
        </form>
    </section>
@endsection
