<?php

declare(strict_types=1);

namespace App\Auth\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    /**
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Zweryfikuj swój adres email — postac.ai')
            ->greeting('Cześć!')
            ->line('Kliknij przycisk poniżej, żeby zweryfikować swój adres email.')
            ->action('Zweryfikuj email', $url)
            ->line('Jeśli nie zakładałaś/eś konta, możesz zignorować tę wiadomość.')
            ->salutation('Pozdrawiamy, zespół postac.ai');
    }
}
