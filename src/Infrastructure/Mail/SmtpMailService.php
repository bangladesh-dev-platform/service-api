<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Domain\Notification\MailService;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

class SmtpMailService implements MailService
{
    public function __construct(
        private array $config,
        private string $appUrl
    ) {
    }

    public function sendPasswordReset(string $email, string $token, ?string $name = null): void
    {
        $mailer = $this->createMailer();

        $mailer->addAddress($email, $name ?? '');
        $mailer->isHTML(true);
        $mailer->Subject = 'Reset your Bangladesh Auth password';

        $resetLink = $this->buildResetLink($token);

        $mailer->Body = $this->buildHtmlBody($resetLink);
        $mailer->AltBody = $this->buildTextBody($resetLink);

        try {
            $mailer->send();
        } catch (MailException $e) {
            throw new \RuntimeException('Unable to send password reset email: ' . $e->getMessage(), 0, $e);
        }
    }

    private function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(
            $this->config['from_address'] ?? 'noreply@banglade.sh',
            $this->config['from_name'] ?? 'Bangladesh Auth'
        );

        if (($this->config['driver'] ?? 'smtp') === 'smtp') {
            $mail->isSMTP();
            $mail->Host = $this->config['host'] ?? 'localhost';
            $mail->Port = (int)($this->config['port'] ?? 587);
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'] ?? '';
            $mail->Password = $this->config['password'] ?? '';

            $encryption = $this->config['encryption'] ?? '';
            if (!empty($encryption) && strtolower($encryption) !== 'none') {
                $mail->SMTPSecure = $encryption;
            }
        }

        return $mail;
    }

    private function buildResetLink(string $token): string
    {
        $base = rtrim($this->appUrl, '/');
        return sprintf('%s/reset-password.html?token=%s', $base, urlencode($token));
    }

    private function buildHtmlBody(string $resetLink): string
    {
        return <<<HTML
<p>We received a request to reset your Bangladesh Auth password.</p>
<p><a href="{$resetLink}" target="_blank">Click here to reset your password</a>. This link will expire in 60 minutes.</p>
<p>If you did not request this, you can safely ignore this email.</p>
HTML;
    }

    private function buildTextBody(string $resetLink): string
    {
        return "Reset your Bangladesh Auth password using the link below (valid for 60 minutes):\n{$resetLink}\nIf you did not request this, ignore this email.";
    }
}
