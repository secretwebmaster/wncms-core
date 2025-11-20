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
        // Generate the reset URL
        $resetUrl = url(route('frontend.users.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

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
