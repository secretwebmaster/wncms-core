<?php

namespace Wncms\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApiSecurityLink extends Notification
{
    use Queueable;

    public function __construct(
        private string $flow,
        private ?string $token = null,
        private ?string $email = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)->subject('WNCMS account security')->line('A security action was requested for your WNCMS account.');
        if ($this->token === null) {
            return $message->line('If you did not request this change, contact your administrator.');
        }

        $callback = trim((string) config('wncms-api-v2.auth_security.client_callback_url', ''));
        if (filter_var($callback, FILTER_VALIDATE_URL) === false || ! in_array(parse_url($callback, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new \RuntimeException('API authentication client callback URL is unavailable.');
        }
        $separator = str_contains($callback, '?') ? '&' : '?';
        $url = $callback.$separator.http_build_query(array_filter([
            'flow' => $this->flow,
            'token' => $this->token,
            'email' => $this->email,
        ], static fn (mixed $value): bool => $value !== null));

        return $message->action('Continue securely', $url)->line('This link is expiring and single-use.');
    }
}
