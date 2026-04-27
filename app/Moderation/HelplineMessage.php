<?php

declare(strict_types=1);

namespace App\Moderation;

class HelplineMessage
{
    public function polish(): string
    {
        return 'Widzę, że możesz przechodzić trudny moment. Jestem AI i nie zastąpię człowieka, który Cię wesprze. '.
            'Zadzwoń: Telefon Zaufania dla Dzieci i Młodzieży **116 111** (24/7, bezpłatnie). '.
            'Centrum Wsparcia dla Osób w Stanie Kryzysu Psychicznego **800 70 2222** (24/7, bezpłatnie). '.
            'Jeśli zagrożenie jest natychmiastowe — **112**.';
    }

    public function fallback(): string
    {
        return 'Przepraszam, ten temat mnie przerasta. Zmieńmy wątek — opowiedz mi o czymś innym.';
    }
}
