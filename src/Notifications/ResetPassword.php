<?php

namespace Wncms\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends BaseResetPassword
{
    /**
     * Build the mail representation of the notification.
     *
     * @param  string  $token
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $callback = trim((string) config('wncms-api-v2.auth_security.client_callback_url', ''));
        if (filter_var($callback, FILTER_VALIDATE_URL) === false || ! in_array(parse_url($callback, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new \RuntimeException('API authentication client callback URL is unavailable.');
        }
        $resetUrl = $callback.(str_contains($callback, '?') ? '&' : '?').http_build_query([
            'flow' => 'password_reset',
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        // Create the MailMessage with translatable text
        $mailMessage = (new MailMessage)
            ->greeting(__('wncms::word.hello'))
            ->line(__('wncms::word.reset_password_intro'))
            ->line(__('wncms::word.reset_password_instructions'))
            ->action(__('wncms::word.reset_password_button'), $resetUrl)
            ->line(__('wncms::word.reset_password_disclaimer'));

        // Check for a custom view
        $website = wncms()->website()->get();
        if ($website && view()->exists("frontend.themes.{$website->theme}.emails.password_reset")) {
            return $mailMessage
            ->subject($website->name . " " . __('wncms::word.reset_password_notification'))
            ->view("frontend.themes.{$website->theme}.emails.password_reset");
        }

        // Return the default message if no custom view exists
        return $mailMessage
        ->subject(__('wncms::word.reset_password_notification'))
        ->view("wncms::emails.password_reset");
    }
}
