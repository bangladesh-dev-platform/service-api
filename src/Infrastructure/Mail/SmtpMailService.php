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
        private string $portalUrl
    ) {
    }

    public function sendPasswordReset(string $email, string $token, ?string $name = null): void
    {
        $mailer = $this->createMailer();

        $mailer->addAddress($email, $name ?? '');
        $mailer->isHTML(true);
        $mailer->Subject = 'Reset your Bangladesh Auth password';

        $resetLink = $this->buildPortalLink('reset-password.html', ['token' => $token]);

        $mailer->Body = $this->buildHtmlBody(
            'We received a request to reset your Bangladesh Auth password.',
            'Click the button below to set a new password. This link will expire in 60 minutes.',
            $resetLink,
            'Reset Password'
        );
        $mailer->AltBody = $this->buildTextBody('Reset your password using the link below (valid for 60 minutes):', $resetLink);

        try {
            $mailer->send();
        } catch (MailException $e) {
            throw new \RuntimeException('Unable to send password reset email: ' . $e->getMessage(), 0, $e);
        }
    }

    public function sendEmailVerification(string $email, string $token, ?string $name = null): void
    {
        $mailer = $this->createMailer();

        $mailer->addAddress($email, $name ?? '');
        $mailer->isHTML(true);
        $mailer->Subject = 'Verify your Bangladesh Auth email';

        $verifyLink = $this->buildPortalLink('verify-email.html', ['token' => $token]);

        $mailer->Body = $this->buildHtmlBody(
            'Welcome to Bangladesh Digital Auth!',
            'Please confirm your email address by clicking the button below. This link expires in 24 hours.',
            $verifyLink,
            'Verify Email'
        );
        $mailer->AltBody = $this->buildTextBody('Verify your email using the link below (valid for 24 hours):', $verifyLink);

        try {
            $mailer->send();
        } catch (MailException $e) {
            throw new \RuntimeException('Unable to send verification email: ' . $e->getMessage(), 0, $e);
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

    private function buildPortalLink(string $path, array $queryParams): string
    {
        $base = rtrim($this->portalUrl, '/');
        $query = http_build_query($queryParams);
        return sprintf('%s/%s?%s', $base, ltrim($path, '/'), $query);
    }

    private function buildHtmlBody(string $headline, string $instructions, string $link, string $buttonText): string
    {
        return <<<HTML
<p>{$headline}</p>
<p>{$instructions}</p>
<p style="margin:24px 0;"><a href="{$link}" target="_blank" style="display:inline-block;padding:12px 20px;background:#2563EB;color:#fff;border-radius:6px;text-decoration:none;">{$buttonText}</a></p>
<p>If you did not request this, you can safely ignore this email.</p>
HTML;
    }

    private function buildTextBody(string $intro, string $link): string
    {
        return "{$intro}\n{$link}\nIf you did not request this, ignore this email.";
    }
}
