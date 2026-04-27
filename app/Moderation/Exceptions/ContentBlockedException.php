<?php

declare(strict_types=1);

namespace App\Moderation\Exceptions;

use RuntimeException;

class ContentBlockedException extends RuntimeException
{
    /**
     * @param  array<string, float>  $categories
     */
    public function __construct(
        public readonly array $categories,
        public readonly string $direction,
        ?string $message = null,
    ) {
        parent::__construct($message ?? 'Hej, zmieńmy temat — to nie pasuje do naszej rozmowy.');
    }
}
