<?php

declare(strict_types=1);

use App\Dating\PromptTemplates;

it('flirt template forbids NSFW and instructs deflection', function () {
    $text = app(PromptTemplates::class)->flirt();

    expect($text)
        ->toContain('Randki')
        ->toContain('NIGDY nie generujesz treści seksualnych')
        ->toContain('postacią AI')
        ->toContain('po polsku');
});
