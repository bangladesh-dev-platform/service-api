<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

class AuthenticationException extends \Exception
{
    public function __construct(string $message = 'Authentication failed', int $code = 401)
    {
        parent::__construct($message, $code);
    }
}
