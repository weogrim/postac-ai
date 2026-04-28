<?php

declare(strict_types=1);

namespace App\Auth\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;

class ResetPasswordNotification extends ResetPassword
{
    /**
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $minutes = Config::get('auth.passwords.'.Config::get('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Reset hasła — postac.ai')
            ->greeting('Cześć!')
            ->line('Otrzymujesz tę wiadomość, ponieważ poproszono o reset hasła do Twojego konta.')
            ->action('Zresetuj hasło', $url)
            ->line("Link wygaśnie za {$minutes} minut.")
            ->line('Jeśli nie prosiłaś/eś o reset hasła, zignoruj tę wiadomość.')
            ->salutation('Pozdrawiamy, zespół postac.ai');
    }
}
