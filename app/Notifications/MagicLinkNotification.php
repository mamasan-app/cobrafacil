<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagicLinkNotification extends Notification
{
    use Queueable;

    protected string $magicLinkUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $magicLinkUrl)
    {
        $this->magicLinkUrl = $magicLinkUrl;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Enlace de Inicio de Sesión')
            ->line('Haz clic en el siguiente enlace para iniciar sesión en tu cuenta.')
            ->action('Iniciar Sesión', $this->magicLinkUrl)
            ->line('Si no solicitaste este enlace, ignora este mensaje.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
