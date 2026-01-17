<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

class AuthorizationException extends \Exception
{
    public function __construct(string $message = 'Unauthorized access', int $code = 403)
    {
        parent::__construct($message, $code);
    }
}
