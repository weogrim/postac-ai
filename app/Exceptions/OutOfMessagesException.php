<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class OutOfMessagesException extends RuntimeException
{
    public function __construct(string $message = 'Wyczerpałeś dzienny limit wiadomości.')
    {
        parent::__construct($message);
    }
}
