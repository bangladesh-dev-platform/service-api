<?php

declare(strict_types=1);

namespace App\Domain\Notification;

interface MailService
{
    public function sendPasswordReset(string $email, string $token, ?string $name = null): void;
}
